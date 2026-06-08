(() => {
  const params = new URLSearchParams(window.location.search);
  const shouldPrintDocuments = params.get('print') === 'documents';
  const documentsSection = document.getElementById('employee-received-documents');
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

  const modalControllers = [];

  const initSignatureModal = ({
    modalSelector,
    formSelector,
    openButtonsSelector,
    closeButtonsSelector,
    isOpenAllowed,
    onBeforeOpen,
    emptySignatureMessage,
  }) => {
    const modal = document.querySelector(modalSelector);
    const form = document.querySelector(formSelector);
    if (!modal || !form) return null;

    if (modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }

    const openButtons = Array.from(document.querySelectorAll(openButtonsSelector));
    const closeButtons = Array.from(document.querySelectorAll(closeButtonsSelector));
    const canvas = form.querySelector('[data-signature-canvas]');
    const dataInput = form.querySelector('[data-signature-data]');
    const errorNode = form.querySelector('[data-signature-error]');
    const clearBtn = form.querySelector('[data-signature-clear]');
    if (!canvas || !dataInput) return null;

    const ctx = canvas.getContext('2d');
    if (!ctx) return null;

    let drawing = false;
    let hasStroke = false;
    let activePointerId = null;
    let isModalOpen = !modal.hidden;

    function resizeCanvasForDevicePixelRatio() {
      if (modal.hidden) {
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

    function openModal(triggerButton) {
      if (typeof onBeforeOpen === 'function') {
        onBeforeOpen(triggerButton, form);
      }

      modal.hidden = false;
      document.documentElement.classList.add('modal-open');
      document.body.classList.add('modal-open');
      isModalOpen = true;
      window.requestAnimationFrame(() => {
        resizeCanvasForDevicePixelRatio();
        const firstField = form.querySelector('select:not([disabled]), button:not([disabled]), textarea:not([disabled]), input:not([type="hidden"]):not([disabled])');
        if (firstField) {
          firstField.focus();
        }
      });
    }

    function closeModal() {
      modal.hidden = true;
      document.documentElement.classList.remove('modal-open');
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

    openButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        if (typeof isOpenAllowed === 'function' && !isOpenAllowed(button)) {
          return;
        }
        openModal(button);
      });
    });

    closeButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        closeModal();
      });
    });

    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && isModalOpen && !modal.hidden) {
        closeModal();
      }
    });

    form.addEventListener('submit', (event) => {
      if (!hasStroke) {
        event.preventDefault();
        if (errorNode) {
          errorNode.textContent = emptySignatureMessage;
        }
        return;
      }

      if (errorNode) errorNode.textContent = '';
      dataInput.value = canvas.toDataURL('image/png');
    });

    if (!modal.hidden) {
      resizeCanvasForDevicePixelRatio();
    }
    window.addEventListener('resize', resizeCanvasForDevicePixelRatio);

    return { closeModal };
  };

  const attendanceModal = initSignatureModal({
    modalSelector: '[data-attendance-modal]',
    formSelector: '[data-employee-signature-form]',
    openButtonsSelector: '[data-attendance-modal-open]',
    closeButtonsSelector: '[data-attendance-modal-close]',
    isOpenAllowed: (button) => {
      const isAriaDisabled = String(button.getAttribute('aria-disabled') || '').toLowerCase() === 'true';
      const isExplicitlyAllowed = String(button.getAttribute('data-attendance-allowed') || '').toLowerCase() === 'true';
      return !isAriaDisabled && isExplicitlyAllowed;
    },
    emptySignatureMessage: 'Please draw your signature before confirming attendance.',
  });
  if (attendanceModal) {
    modalControllers.push(attendanceModal);
  }

  const documentModal = initSignatureModal({
    modalSelector: '[data-document-sign-modal]',
    formSelector: '[data-document-signature-form]',
    openButtonsSelector: '[data-document-sign-open]',
    closeButtonsSelector: '[data-document-sign-close]',
    onBeforeOpen: (triggerButton, form) => {
      const requestId = String(triggerButton?.getAttribute('data-document-sign-request-id') || '').trim();
      const documentName = String(triggerButton?.getAttribute('data-document-sign-title') || 'Document').trim();
      const requestInput = form.querySelector('[data-document-request-id]');
      const nameNode = document.querySelector('[data-document-sign-name]');
      if (requestInput) requestInput.value = requestId;
      if (nameNode) nameNode.textContent = documentName;
    },
    emptySignatureMessage: 'Please draw your signature before signing this document.',
  });
  if (documentModal) {
    modalControllers.push(documentModal);
  }

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
})();
