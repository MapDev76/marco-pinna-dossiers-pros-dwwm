(() => {
  const params = new URLSearchParams(window.location.search);
  const shouldPrintDocuments = params.get('print') === 'documents';
  const documentsSection = document.getElementById('employee-received-documents');
  const form = document.querySelector('[data-employee-signature-form]');
  const modal = document.querySelector('[data-attendance-modal]');
  const openButton = document.querySelector('[data-attendance-modal-open]');
  const closeButtons = Array.from(document.querySelectorAll('[data-attendance-modal-close]'));
  const scrollButtons = Array.from(document.querySelectorAll('[data-scroll-target]'));

  if (shouldPrintDocuments && documentsSection) {
    document.body.classList.add('employee-documents-print-mode');
    window.requestAnimationFrame(() => {
      documentsSection.scrollIntoView({ behavior: 'auto', block: 'start' });
      window.setTimeout(() => {
        window.print();
      }, 120);
    });
    window.addEventListener('afterprint', () => {
      document.body.classList.remove('employee-documents-print-mode');
    }, { once: true });
  }

  if (!form) return;

  const canvas = form.querySelector('[data-signature-canvas]');
  const dataInput = form.querySelector('[data-signature-data]');
  const errorNode = form.querySelector('[data-signature-error]');
  const clearBtn = form.querySelector('[data-signature-clear]');
  if (!canvas || !dataInput) return;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  let drawing = false;
  let hasStroke = false;
  let activePointerId = null;
  let isModalOpen = modal ? !modal.hidden : true;

  function resizeCanvasForDevicePixelRatio() {
    if (modal && modal.hidden) {
      return;
    }

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
    drawPadBackground();
  }

  function drawPadBackground() {
    const width = canvas.clientWidth;
    const height = canvas.clientHeight;

    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);

    ctx.strokeStyle = 'rgba(0, 0, 0, 0.12)';
    ctx.lineWidth = 1;
    ctx.strokeRect(0.5, 0.5, width - 1, height - 1);

    ctx.beginPath();
    ctx.moveTo(10, height - 30);
    ctx.lineTo(width - 10, height - 30);
    ctx.stroke();

    ctx.strokeStyle = '#111827';
    ctx.lineWidth = 2.2;
  }

  function pointFromEvent(event) {
    const rect = canvas.getBoundingClientRect();
    return {
      x: event.clientX - rect.left,
      y: event.clientY - rect.top,
    };
  }

  function beginStroke(event) {
    if (activePointerId !== null) return;
    activePointerId = event.pointerId;
    drawing = true;

    const point = pointFromEvent(event);
    ctx.beginPath();
    ctx.moveTo(point.x, point.y);
    hasStroke = true;
    event.preventDefault();
  }

  function drawStroke(event) {
    if (!drawing || event.pointerId !== activePointerId) return;
    const point = pointFromEvent(event);
    ctx.lineTo(point.x, point.y);
    ctx.stroke();
    event.preventDefault();
  }

  function endStroke(event) {
    if (event.pointerId !== activePointerId) return;
    drawing = false;
    activePointerId = null;
    ctx.closePath();
  }

  function clearSignature() {
    hasStroke = false;
    dataInput.value = '';
    if (errorNode) errorNode.textContent = '';
    drawPadBackground();
  }

  function openModal() {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('modal-open');
    isModalOpen = true;
    window.requestAnimationFrame(() => {
      resizeCanvasForDevicePixelRatio();
      const firstField = form.querySelector('select, input, button, textarea');
      if (firstField) {
        firstField.focus();
      }
    });
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('modal-open');
    isModalOpen = false;
    clearSignature();
  }

  canvas.addEventListener('pointerdown', beginStroke, { passive: false });
  canvas.addEventListener('pointermove', drawStroke, { passive: false });
  canvas.addEventListener('pointerup', endStroke);
  canvas.addEventListener('pointercancel', endStroke);
  canvas.addEventListener('pointerleave', endStroke);

  if (clearBtn) {
    clearBtn.addEventListener('click', (event) => {
      event.preventDefault();
      clearSignature();
    });
  }

  if (openButton && modal) {
    openButton.addEventListener('click', (event) => {
      event.preventDefault();
      openModal();
    });
  }

  closeButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      closeModal();
    });
  });

  if (modal) {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isModalOpen && modal && !modal.hidden) {
      closeModal();
    }
  });

  scrollButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      const targetId = button.getAttribute('data-scroll-target') || '';
      const target = targetId ? document.getElementById(targetId) : null;
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  form.addEventListener('submit', (event) => {
    if (!hasStroke) {
      event.preventDefault();
      if (errorNode) {
        errorNode.textContent = 'Please draw your signature before confirming attendance.';
      }
      return;
    }

    if (errorNode) errorNode.textContent = '';
    dataInput.value = canvas.toDataURL('image/png');
  });

  if (!modal || !modal.hidden) {
    resizeCanvasForDevicePixelRatio();
  }
  window.addEventListener('resize', resizeCanvasForDevicePixelRatio);
})();
