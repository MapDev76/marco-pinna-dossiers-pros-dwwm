(() => {
  const params = new URLSearchParams(window.location.search);
  const shouldPrintDocuments = params.get('print') === 'documents';
  const shouldOpenDocumentsModal = params.get('modal') === 'documents';
  const documentsSection = document.getElementById('employee-received-documents');
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

  if (shouldPrintDocuments && documentsSection) {
    let printModeCleaned = false;
    const cleanupPrintMode = () => {
      if (printModeCleaned) {
        return;
      }

      printModeCleaned = true;
      document.body.classList.remove('employee-documents-print-mode');

      const currentUrl = new URL(window.location.href);
      if (currentUrl.searchParams.get('print') === 'documents') {
        currentUrl.searchParams.delete('print');
        const nextUrl = currentUrl.pathname
          + (currentUrl.searchParams.toString() ? `?${currentUrl.searchParams.toString()}` : '')
          + currentUrl.hash;
        window.history.replaceState({}, document.title, nextUrl);
      }

      window.removeEventListener('afterprint', cleanupPrintMode);
      window.removeEventListener('focus', handleFocusRestore);
      document.removeEventListener('visibilitychange', handleVisibilityRestore);
    };

    const handleFocusRestore = () => {
      window.setTimeout(cleanupPrintMode, 160);
    };

    const handleVisibilityRestore = () => {
      if (!document.hidden) {
        window.setTimeout(cleanupPrintMode, 160);
      }
    };

    document.body.classList.add('employee-documents-print-mode');
    window.addEventListener('afterprint', cleanupPrintMode);
    window.addEventListener('focus', handleFocusRestore);
    document.addEventListener('visibilitychange', handleVisibilityRestore);

    window.requestAnimationFrame(() => {
      documentsSection.scrollIntoView({ behavior: 'auto', block: 'start' });
      window.setTimeout(() => {
        try {
          window.print();
        } finally {
          window.setTimeout(cleanupPrintMode, 1400);
        }
      }, 120);
    });
  }

  const modalControllers = [];

  const initSimpleModal = ({
    modalSelector,
    openButtonsSelector,
    closeButtonsSelector,
  }) => {
    const modal = document.querySelector(modalSelector);
    if (!modal) return null;

    if (modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }

    const openButtons = Array.from(document.querySelectorAll(openButtonsSelector));
    const closeButtons = Array.from(document.querySelectorAll(closeButtonsSelector));
    let isModalOpen = !modal.hidden;

    const openModal = () => {
      modal.hidden = false;
      document.documentElement.classList.add('modal-open');
      document.body.classList.add('modal-open');
      isModalOpen = true;
    };

    const closeModal = () => {
      modal.hidden = true;
      document.documentElement.classList.remove('modal-open');
      document.body.classList.remove('modal-open');
      isModalOpen = false;
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

    return { openModal, closeModal };
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
      const inboxModal = document.querySelector('[data-employee-documents-inbox-modal]');
      if (inboxModal && !inboxModal.hasAttribute('hidden')) {
        inboxModal.dataset.wasOpenForSign = '1';
        inboxModal.hidden = true;
      }

      const requestId = String(triggerButton?.getAttribute('data-document-sign-request-id') || '').trim();
      const documentName = String(triggerButton?.getAttribute('data-document-sign-title') || 'Document').trim();
      const requestInput = form.querySelector('[data-document-request-id]');
      const nameNode = document.querySelector('[data-document-sign-name]');
      if (requestInput) requestInput.value = requestId;
      if (nameNode) nameNode.textContent = documentName;
    },
    onAfterClose: () => {
      const inboxModal = document.querySelector('[data-employee-documents-inbox-modal]');
      if (inboxModal && inboxModal.dataset.wasOpenForSign === '1') {
        inboxModal.hidden = false;
        delete inboxModal.dataset.wasOpenForSign;
      }
    },
    emptySignatureMessage: 'Please draw your signature before signing this document.',
  });
  if (documentModal) {
    modalControllers.push(documentModal);
  }

  const employeeMessagesModal = initSimpleModal({
    modalSelector: '[data-employee-messages-modal]',
    openButtonsSelector: '[data-modal-target="employee-messages-modal"], [data-employee-messages-modal-open]',
    closeButtonsSelector: '[data-employee-messages-modal-close]',
  });
  if (employeeMessagesModal) {
    modalControllers.push(employeeMessagesModal);
  }

  const employeeMessagesModalNode = document.querySelector('[data-employee-messages-modal]');
  const employeeMessagesExpandButton = employeeMessagesModalNode
    ? employeeMessagesModalNode.querySelector('[data-employee-messages-expand]')
    : null;
  const employeeMessagesExpandLabel = employeeMessagesExpandButton
    ? String(employeeMessagesExpandButton.getAttribute('data-label-expand') || 'Expand').trim()
    : 'Expand';
  const employeeMessagesCollapseLabel = employeeMessagesExpandButton
    ? String(employeeMessagesExpandButton.getAttribute('data-label-collapse') || 'Collapse').trim()
    : 'Collapse';
  const setEmployeeMessagesExpandedState = (expanded) => {
    if (!employeeMessagesModalNode || !employeeMessagesExpandButton) {
      return;
    }

    employeeMessagesModalNode.classList.toggle('is-expanded', expanded);
    employeeMessagesExpandButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    employeeMessagesExpandButton.textContent = expanded ? employeeMessagesCollapseLabel : employeeMessagesExpandLabel;
  };

  if (employeeMessagesModalNode && employeeMessagesExpandButton) {
    setEmployeeMessagesExpandedState(false);

    employeeMessagesExpandButton.addEventListener('click', (event) => {
      event.preventDefault();
      const isExpanded = employeeMessagesModalNode.classList.contains('is-expanded');
      setEmployeeMessagesExpandedState(!isExpanded);
    });

    const closeMessagesButtons = Array.from(employeeMessagesModalNode.querySelectorAll('[data-employee-messages-modal-close]'));
    closeMessagesButtons.forEach((button) => {
      button.addEventListener('click', () => {
        setEmployeeMessagesExpandedState(false);
      });
    });
  }

  const employeeDocumentsInboxModal = initSimpleModal({
    modalSelector: '[data-employee-documents-inbox-modal]',
    openButtonsSelector: '[data-modal-target="employee-documents-inbox-modal"], [data-employee-documents-inbox-open]',
    closeButtonsSelector: '[data-employee-documents-inbox-close]',
  });
  if (employeeDocumentsInboxModal) {
    modalControllers.push(employeeDocumentsInboxModal);
    if (shouldOpenDocumentsModal) {
      employeeDocumentsInboxModal.openModal();
      cleanupUrlParam('modal');
    }
  }

  const employeeMessagesListModal = document.querySelector('[data-employee-messages-list-modal]');
  if (employeeMessagesListModal) {
    const openButtons = Array.from(document.querySelectorAll('[data-employee-messages-list-open]'));
    const closeButtons = Array.from(employeeMessagesListModal.querySelectorAll('[data-employee-messages-list-close]'));
    const titleNode = employeeMessagesListModal.querySelector('[data-employee-messages-list-title]');
    const sections = Array.from(employeeMessagesListModal.querySelectorAll('[data-message-list-scope]'));
    let isOpen = !employeeMessagesListModal.hidden;

    const setScope = (scope) => {
      const normalized = String(scope || 'incoming').toLowerCase() === 'outgoing' ? 'outgoing' : 'incoming';
      const incomingTitle = String(employeeMessagesListModal.getAttribute('data-title-incoming') || 'Received messages').trim();
      const outgoingTitle = String(employeeMessagesListModal.getAttribute('data-title-outgoing') || 'Sent messages').trim();

      sections.forEach((section) => {
        section.hidden = section.getAttribute('data-message-list-scope') !== normalized;
      });

      if (titleNode) {
        titleNode.textContent = normalized === 'outgoing' ? outgoingTitle : incomingTitle;
      }
    };

    const openModal = (scope) => {
      setScope(scope);
      employeeMessagesListModal.hidden = false;
      document.documentElement.classList.add('modal-open');
      document.body.classList.add('modal-open');
      isOpen = true;
    };

    const closeModal = () => {
      employeeMessagesListModal.hidden = true;
      document.documentElement.classList.remove('modal-open');
      document.body.classList.remove('modal-open');
      isOpen = false;
    };

    openButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        openModal(button.getAttribute('data-scope'));
      });
    });

    closeButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        closeModal();
      });
    });

    employeeMessagesListModal.addEventListener('click', (event) => {
      if (event.target === employeeMessagesListModal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && isOpen && !employeeMessagesListModal.hidden) {
        closeModal();
      }
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
