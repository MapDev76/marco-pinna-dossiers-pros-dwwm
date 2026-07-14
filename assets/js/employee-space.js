(() => {
  const params = new URLSearchParams(window.location.search);
  const shouldOpenDocumentsModal = params.get('modal') === 'documents';
  const scrollButtons = Array.from(document.querySelectorAll('[data-scroll-target]'));

  const cleanupUrlParam = (paramName) => {
    const currentUrl = new URL(window.location.href);
    if (!currentUrl.searchParams.has(paramName)) {
      return;
    }

    currentUrl.searchParams.delete(paramName);
    const nextUrl = currentUrl.pathname
      + (currentUrl.searchParams.toString() ? `?${currentUrl.searchParams.toString()}` : '')
      + currentUrl.hash;
    window.history.replaceState({}, document.title, nextUrl);
  };

  const initSignatureModal = ({
    modalSelector,
    formSelector,
    openButtonsSelector,
    closeButtonsSelector,
    isOpenAllowed,
    onBeforeOpen,
    onAfterClose,
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
    const signPreviewFrame = form.querySelector('[data-document-sign-preview-frame]');
    const signPositionLayer = form.querySelector('[data-document-sign-position-layer]');
    const signPositionDot = form.querySelector('[data-document-sign-position-dot]');
    const signPosXInput = form.querySelector('[data-signature-pos-x]');
    const signPosYInput = form.querySelector('[data-signature-pos-y]');
    if (!canvas || !dataInput) return null;

    let isModalOpen = !modal.hidden;

    const pad = window.AppSignaturePad && window.AppSignaturePad.init(canvas, {
      errorNode,
      clearBtn,
      positionLayer: signPositionLayer,
      positionDot: signPositionDot,
      posXInput: signPosXInput,
      posYInput: signPosYInput,
    });

    if (!pad) return null;

    function openModal(triggerButton) {
      if (typeof onBeforeOpen === 'function') {
        onBeforeOpen(triggerButton, form);
      }

      if (signPreviewFrame && triggerButton) {
        const previewUrl = String(triggerButton.getAttribute('data-document-sign-preview-url') || '').trim();
        signPreviewFrame.src = previewUrl || 'about:blank';
      }

      const startX = signPosXInput ? signPosXInput.value : '86';
      const startY = signPosYInput ? signPosYInput.value : '84';
      if (typeof pad.updatePosition === 'function') {
        pad.updatePosition(startX, startY);
      }

      modal.hidden = false;
      document.documentElement.classList.add('modal-open');
      document.body.classList.add('modal-open');
      isModalOpen = true;
      window.requestAnimationFrame(() => {
        pad.resize();
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
      pad.clear();
      if (signPreviewFrame) {
        signPreviewFrame.src = 'about:blank';
      }
      if (typeof onAfterClose === 'function') {
        onAfterClose(form);
      }
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
      if (!pad.hasStroke()) {
        event.preventDefault();
        if (errorNode) {
          errorNode.textContent = emptySignatureMessage;
        }
        return;
      }

      if (errorNode) errorNode.textContent = '';
      dataInput.value = pad.getDataUrl();
    });

    if (!modal.hidden) {
      pad.resize();
    }
    window.addEventListener('resize', () => pad.resize());

    return { closeModal };
  };

  initSignatureModal({
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

  initSignatureModal({
    modalSelector: '[data-employee-document-sign-modal]',
    formSelector: '[data-employee-document-sign-form]',
    openButtonsSelector: '[data-employee-document-sign-open]',
    closeButtonsSelector: '[data-employee-document-sign-close]',
    isOpenAllowed: () => true,
    onBeforeOpen: (triggerButton, form) => {
      const docsModal = document.querySelector('[data-employee-documents-inbox-modal]');
      if (docsModal && !docsModal.hidden) {
        docsModal.setAttribute('data-was-open-before-sign', '1');
        docsModal.hidden = true;
      }
      const requestInput = form.querySelector('[data-employee-document-sign-request-id]');
      if (!requestInput || !triggerButton) {
        return;
      }
      const requestId = String(triggerButton.getAttribute('data-employee-document-sign-request-id') || '').trim();
      requestInput.value = requestId;
    },
    onAfterClose: (form) => {
      const requestInput = form.querySelector('[data-employee-document-sign-request-id]');
      if (requestInput) {
        requestInput.value = '';
      }
      const docsModal = document.querySelector('[data-employee-documents-inbox-modal]');
      if (docsModal && docsModal.getAttribute('data-was-open-before-sign') === '1') {
        docsModal.hidden = false;
        docsModal.removeAttribute('data-was-open-before-sign');
      }
    },
    emptySignatureMessage: 'Please draw your signature before signing this document.',
  });

  const employeeDocumentsModal = document.querySelector('[data-employee-documents-inbox-modal]');
  if (employeeDocumentsModal) {
    if (employeeDocumentsModal.parentElement !== document.body) {
      document.body.appendChild(employeeDocumentsModal);
    }

    const openButtons = Array.from(document.querySelectorAll('[data-modal-target="employee-documents-inbox-modal"], [data-employee-documents-inbox-open]'));
    const closeButtons = Array.from(employeeDocumentsModal.querySelectorAll('[data-employee-documents-inbox-close]'));
    let isOpen = !employeeDocumentsModal.hidden;

    const openModal = () => {
      employeeDocumentsModal.hidden = false;
      document.documentElement.classList.add('modal-open');
      document.body.classList.add('modal-open');
      isOpen = true;
    };

    const closeModal = () => {
      employeeDocumentsModal.hidden = true;
      document.documentElement.classList.remove('modal-open');
      document.body.classList.remove('modal-open');
      isOpen = false;
    };

    openButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        openModal();
      });
    });

    closeButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        closeModal();
      });
    });

    employeeDocumentsModal.addEventListener('click', (event) => {
      if (event.target === employeeDocumentsModal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && isOpen && !employeeDocumentsModal.hidden) {
        closeModal();
      }
    });

    if (shouldOpenDocumentsModal) {
      openModal();
      cleanupUrlParam('modal');
    }
  }

  const employeeDocumentShareForm = document.querySelector('#employee-document-share-form');
  const employeeDocumentRecipients = employeeDocumentShareForm
    ? employeeDocumentShareForm.querySelector('[data-employee-document-recipient-ids]')
    : null;
  const employeeDocumentFileInput = employeeDocumentShareForm
    ? employeeDocumentShareForm.querySelector('[data-employee-document-file]')
    : null;
  const employeeDocumentExistingInput = employeeDocumentShareForm
    ? employeeDocumentShareForm.querySelector('[data-employee-document-existing-id]')
    : null;
  const employeeDocumentShareSubmit = employeeDocumentShareForm
    ? employeeDocumentShareForm.querySelector('[data-employee-document-share-submit]')
    : null;

  const shareExistingButtons = Array.from(document.querySelectorAll('[data-employee-document-share-existing-id]'));

  if (employeeDocumentExistingInput && employeeDocumentExistingInput.value) {
    const preselectedId = String(employeeDocumentExistingInput.value).trim();
    const preselectedButton = shareExistingButtons.find((button) => String(button.getAttribute('data-employee-document-share-existing-id') || '').trim() === preselectedId);
    if (preselectedButton) {
      preselectedButton.classList.add('is-active');
    }
  }

  if (employeeDocumentShareSubmit) {
    employeeDocumentShareSubmit.textContent = 'Condividi documento';
  }

  shareExistingButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      const docId = String(button.getAttribute('data-employee-document-share-existing-id') || '').trim();
      if (!docId || !employeeDocumentExistingInput) {
        return;
      }

      const wasActive = button.classList.contains('is-active');
      shareExistingButtons.forEach((item) => item.classList.remove('is-active'));

      if (wasActive) {
        employeeDocumentExistingInput.value = '';
        return;
      }

      button.classList.add('is-active');
      employeeDocumentExistingInput.value = docId;
      if (employeeDocumentFileInput) {
        employeeDocumentFileInput.value = '';
      }
      if (employeeDocumentsModal && employeeDocumentsModal.hidden) {
        employeeDocumentsModal.hidden = false;
      }
      const formTop = employeeDocumentShareForm ? employeeDocumentShareForm.offsetTop : 0;
      if (employeeDocumentsModal) {
        employeeDocumentsModal.scrollTo({ top: Math.max(0, formTop - 12), behavior: 'smooth' });
      }
    });
  });

  if (employeeDocumentShareForm) {
    employeeDocumentShareForm.addEventListener('submit', () => {
      if (!employeeDocumentFileInput || !employeeDocumentExistingInput) {
        return;
      }

      const hasFile = !!(employeeDocumentFileInput.files && employeeDocumentFileInput.files.length > 0);
      if (hasFile) {
        employeeDocumentExistingInput.value = '';
        shareExistingButtons.forEach((item) => item.classList.remove('is-active'));
      }
    });
  }

  if (employeeDocumentFileInput) {
    employeeDocumentFileInput.addEventListener('change', () => {
      const hasFile = !!(employeeDocumentFileInput.files && employeeDocumentFileInput.files.length > 0);
      if (!hasFile || !employeeDocumentExistingInput) {
        return;
      }
      employeeDocumentExistingInput.value = '';
      shareExistingButtons.forEach((item) => item.classList.remove('is-active'));
    });
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
