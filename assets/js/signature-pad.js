/**
 * Shared signature-pad helper.
 *
 * Exposes window.AppSignaturePad.init(canvas, options) which wires up a
 * drawing canvas and an optional marker-position overlay.  Both the
 * employee-space flow and the dashboard flow use this module so the canvas
 * drawing logic stays in one place.
 *
 * Returns an object with:
 *   hasStroke()   – true if at least one stroke has been drawn
 *   getDataUrl()  – PNG data-URL of the current canvas content
 *   clear()       – erase the canvas and reset hasStroke
 *   resize()      – recalculate DPR scaling (call after the canvas becomes visible)
 */
window.AppSignaturePad = (() => {
  /**
   * @param {HTMLCanvasElement} canvas
   * @param {{
   *   errorNode?: HTMLElement|null,
   *   clearBtn?: HTMLElement|null,
   *   positionLayer?: HTMLElement|null,
   *   positionDot?: HTMLElement|null,
   *   posXInput?: HTMLInputElement|null,
   *   posYInput?: HTMLInputElement|null,
   *   defaultPosX?: number,
   *   defaultPosY?: number,
   * }} options
   */
  const init = (canvas, options = {}) => {
    const {
      errorNode = null,
      clearBtn = null,
      positionLayer = null,
      positionDot = null,
      posXInput = null,
      posYInput = null,
      defaultPosX = 86,
      defaultPosY = 84,
    } = options;

    const ctx = canvas && typeof canvas.getContext === 'function' ? canvas.getContext('2d') : null;
    if (!canvas || !ctx) return null;

    let drawing = false;
    let activePointerId = null;
    let strokeRecorded = false;

    const drawBackground = () => {
      const width = canvas.clientWidth;
      const height = canvas.clientHeight;
      ctx.clearRect(0, 0, width, height);
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, width, height);
      ctx.strokeStyle = 'rgba(0,0,0,0.12)';
      ctx.lineWidth = 1;
      ctx.strokeRect(0.5, 0.5, width - 1, height - 1);
      ctx.beginPath();
      ctx.moveTo(10, height - 30);
      ctx.lineTo(width - 10, height - 30);
      ctx.stroke();
      ctx.strokeStyle = '#111827';
      ctx.lineWidth = 2.2;
    };

    const resize = () => {
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      const cssWidth = canvas.clientWidth || canvas.width;
      const cssHeight = canvas.clientHeight || canvas.height;
      canvas.width = Math.floor(cssWidth * ratio);
      canvas.height = Math.floor(cssHeight * ratio);
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.lineWidth = 2.2;
      ctx.strokeStyle = '#111827';
      drawBackground();
    };

    const pointFromEvent = (event) => {
      const rect = canvas.getBoundingClientRect();
      return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    };

    const beginStroke = (event) => {
      if (activePointerId !== null) return;
      activePointerId = event.pointerId;
      drawing = true;
      const p = pointFromEvent(event);
      ctx.beginPath();
      ctx.moveTo(p.x, p.y);
      strokeRecorded = true;
      event.preventDefault();
    };

    const drawStroke = (event) => {
      if (!drawing || event.pointerId !== activePointerId) return;
      const p = pointFromEvent(event);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
      event.preventDefault();
    };

    const endStroke = (event) => {
      if (event.pointerId !== activePointerId) return;
      drawing = false;
      activePointerId = null;
      ctx.closePath();
    };

    canvas.addEventListener('pointerdown', beginStroke, { passive: false });
    canvas.addEventListener('pointermove', drawStroke, { passive: false });
    canvas.addEventListener('pointerup', endStroke);
    canvas.addEventListener('pointercancel', endStroke);
    canvas.addEventListener('pointerleave', endStroke);

    const clear = () => {
      strokeRecorded = false;
      if (errorNode) errorNode.textContent = '';
      drawBackground();
    };

    if (clearBtn) {
      clearBtn.addEventListener('click', (event) => {
        event.preventDefault();
        clear();
      });
    }

    // Position marker (drag-to-place the signature on the document preview).
    const updatePosition = (xPercent, yPercent) => {
      const nx = Math.min(96, Math.max(4, Number(xPercent) || defaultPosX));
      const ny = Math.min(96, Math.max(4, Number(yPercent) || defaultPosY));
      if (posXInput) posXInput.value = String(nx);
      if (posYInput) posYInput.value = String(ny);
      if (positionDot) {
        positionDot.style.left = `${nx}%`;
        positionDot.style.top = `${ny}%`;
      }
      return { x: nx, y: ny };
    };

    if (positionLayer) {
      let markerDrag = false;

      const placeFromPointer = (event) => {
        const rect = positionLayer.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        updatePosition(
          ((event.clientX - rect.left) / rect.width) * 100,
          ((event.clientY - rect.top) / rect.height) * 100
        );
      };

      positionLayer.addEventListener('pointerdown', (event) => {
        markerDrag = true;
        if (typeof positionLayer.setPointerCapture === 'function') {
          try { positionLayer.setPointerCapture(event.pointerId); } catch (e) { /* noop */ }
        }
        placeFromPointer(event);
      });

      positionLayer.addEventListener('pointermove', (event) => {
        if (!markerDrag) return;
        placeFromPointer(event);
      });

      const stopDrag = (event) => {
        markerDrag = false;
        if (typeof positionLayer.releasePointerCapture === 'function') {
          try { positionLayer.releasePointerCapture(event.pointerId); } catch (e) { /* noop */ }
        }
      };

      positionLayer.addEventListener('pointerup', stopDrag);
      positionLayer.addEventListener('pointercancel', stopDrag);
      positionLayer.addEventListener('pointerleave', (event) => {
        if (markerDrag) stopDrag(event);
      });
      positionLayer.addEventListener('click', (event) => {
        event.preventDefault();
        placeFromPointer(event);
      });
    }

    return {
      hasStroke: () => strokeRecorded,
      getDataUrl: () => canvas.toDataURL('image/png'),
      clear,
      resize,
      updatePosition,
    };
  };

  return { init };
})();
