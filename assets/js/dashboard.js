/*
 * Dashboard UI behaviors: modal shell, form wiring and company/user/department actions.
 * This module expects the following globals to be present before execution:
 * - `window.DashboardConfig` with `{ apiCompanies, apiDepartments, apiUsers }`
 * - `window.AppAPI` that implements the AJAX helpers used by buttons.
 *
 * The file contains two main self-invoking sections:
 * 1) Modal lifecycle and template wiring (`setupModals`) — mounts CRUD templates
 *    into the shared modal shell and manages focus/keyboard accessibility.
 * 2) Company actions (`setupCompanyActions`) — lightweight event handlers that
 *    call `AppAPI` to perform create/delete/manage operations on companies,
 *    departments and users.
 */
(function(){
  const config = window.DashboardConfig || {};
  const locale = (document.documentElement.getAttribute('lang') || 'en').toLowerCase();
  const isFr = locale.startsWith('fr');
  const tr = (enText, frText) => (isFr ? frText : enText);
  const apiCompanies = config.apiCompanies;
  const apiDepartments = config.apiDepartments;
  const apiUsers = config.apiUsers;
  const apiDashboard = config.apiDashboard;
  const iconsBase = String(config.iconsBase || '/assets/icons/');

  const escapeHtml = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

  const isIconAsset = (icon) => /\.(svg|png|jpe?g|gif|webp|ico)$/i.test(String(icon || ''));
  const renderIconHtml = (icon, color) => {
    if (!icon) return '';
    if (isIconAsset(icon)) {
      return `<img src="${iconsBase}${encodeURIComponent(icon)}" aria-hidden="true" class="calendar-icon-img">`;
    }
    return `<span style="color:${escapeHtml(color || '')}">${escapeHtml(icon)}</span>`;
  };

  const renderDepartmentTitleHtml = (icon, name, color) => {
    const safeName = escapeHtml(name || 'Department');
    const iconHtml = icon ? `${renderIconHtml(icon, color)} ` : '';
    return `${iconHtml}${safeName}`;
  };

  /**
   * setupModals
   * Initialize modal behavior for the dashboard: open/close logic, overlay,
   * focus trapping and template-based content injection for the shared CRUD
   * modal. Templates are defined in `app/layout/crud-modal.php` and are
   * injected into the element with id `crud-modal-body`.
   */
  (function setupModals(){
    const feedback = window.DashboardFeedback;
    const notifyError = (message) => {
      if (feedback?.error) {
        feedback.error('Oops!', message);
        return;
      }
      console.error(message);
    };
    const notifySuccess = (message) => {
      if (feedback?.success) {
        feedback.success('Done', message);
      }
    };

    const overlay = document.getElementById('dashboard-overlay');
    const modals = document.querySelectorAll('.dashboard-modal, .crud-modal');
    const openButtons = document.querySelectorAll('[data-modal-target]');
    const closeButtons = document.querySelectorAll('[data-modal-close]');
    const crudModal = document.getElementById('crud-modal');
    const crudTitle = document.getElementById('crud-modal-title');
    const crudSubtitle = document.getElementById('crud-modal-subtitle');
    const crudBody = document.getElementById('crud-modal-body');
    let lastFocusedElement = null;
    let activeModal = null;
    let requestedMessageDocument = null;

    const plannerData = window.DashboardPlannerData || {};
    const currentUser = window.DashboardCurrentUser || {};

    const collectDepartmentEmployeeIds = (recipientSelect) => {
      if (!recipientSelect) return [];
      const selectedDepartmentId = Number(plannerData.active_department_id || currentUser.department_id || 0);
      const fallbackToAnyDepartment = selectedDepartmentId <= 0;

      return Array.from(recipientSelect.options || [])
        .filter((option) => {
          if (!option || !option.value) return false;
          const role = String(option.getAttribute('data-role') || '').toLowerCase();
          const departmentId = Number(option.getAttribute('data-department-id') || 0);
          if (role !== 'employee') return false;
          return fallbackToAnyDepartment ? departmentId > 0 : departmentId === selectedDepartmentId;
        })
        .map((option) => Number(option.value))
        .filter((id) => Number.isInteger(id) && id > 0);
    };

    const focusableSelector = [
      'a[href]',
      'button:not([disabled])',
      'input:not([disabled]):not([type="hidden"])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    /**
     * focusFirst(container)
     * Move keyboard focus to the first tabbable element inside `container`.
     */
    const focusFirst = (container) => {
      const focusables = container ? container.querySelectorAll(focusableSelector) : [];
      const first = focusables[0];
      if (first && typeof first.focus === 'function') {
        first.focus();
      }
    };

    /**
     * syncDepartmentFilter(select, companyId)
     * Hide department options that don't belong to the provided companyId.
     * Used when the UI exposes both a company select and a department select
     * so the department list is contextually filtered.
     */
    const syncDepartmentFilter = (select, companyId) => {
      if (!select) return;
      const normalized = String(companyId || '');
      Array.from(select.options).forEach((option) => {
        if (option.value === '') return;
        const optionCompanyId = option.getAttribute('data-company-id') || '';
        option.hidden = normalized !== '' && optionCompanyId !== normalized;
      });
      if (select.value && select.selectedOptions[0] && select.selectedOptions[0].hidden) {
        select.value = '';
      }
    };

    /**
     * setModalContent(entity)
     * Given an entity name (companies|users|departments|documents|messages),
     * initialize the form fields and wire local UI handlers for that template's
     * controls. Templates are copied from the `<template>` nodes included in
     * `app/layout/crud-modal.php`.
     */
    const setModalContent = (entity) => {
      if (!crudBody) return;
      if (entity === 'documents') {
        const removeDocumentCard = (card) => {
          if (!card || !card.parentElement) return;
          card.remove();
          const remaining = crudBody.querySelectorAll('.company-card').length;
          if (remaining === 0) {
            const grid = crudBody.querySelector('.company-grid');
            if (grid) {
              grid.innerHTML = '<div class="crud-empty-state">No documents available.</div>';
            }
          }
        };

        crudBody.querySelectorAll('[data-document-send-id]').forEach((button) => {
          button.addEventListener('click', () => {
            requestedMessageDocument = {
              id: Number(button.getAttribute('data-document-send-id') || 0),
              name: String(button.getAttribute('data-document-send-name') || 'Document').trim(),
              recipientIds: [],
            };

            const messageTrigger = document.querySelector('[data-modal-entity="messages"]');
            if (messageTrigger && typeof messageTrigger.click === 'function') {
              messageTrigger.click();
              return;
            }

            const template = document.getElementById('crud-template-messages');
            if (template && crudBody) {
              if (crudTitle) crudTitle.textContent = tr('Messages', 'Messages');
              if (crudSubtitle) crudSubtitle.textContent = tr('Create requests or notifications and send them to selected users.', 'Creez des demandes ou notifications et envoyez-les aux utilisateurs selectionnes.');
              crudBody.innerHTML = template.innerHTML;
              setModalContent('messages');
            }
          });
        });

        crudBody.querySelectorAll('[data-document-send-all-id]').forEach((button) => {
          button.addEventListener('click', () => {
            const template = document.getElementById('crud-template-messages');
            const recipientProbe = template ? template.content.querySelector('#crud-message-recipient-ids') : null;
            const recipientIds = collectDepartmentEmployeeIds(recipientProbe);
            if (recipientIds.length === 0) {
              notifyError(tr('No employees found in the selected department.', 'Aucun employe trouve dans le departement selectionne.'));
              return;
            }

            requestedMessageDocument = {
              id: Number(button.getAttribute('data-document-send-all-id') || 0),
              name: String(button.getAttribute('data-document-send-all-name') || 'Document').trim(),
              recipientIds,
            };

            const messageTrigger = document.querySelector('[data-modal-entity="messages"]');
            if (messageTrigger && typeof messageTrigger.click === 'function') {
              messageTrigger.click();
              return;
            }

            if (template && crudBody) {
              if (crudTitle) crudTitle.textContent = tr('Messages', 'Messages');
              if (crudSubtitle) crudSubtitle.textContent = tr('Create requests or notifications and send them to selected users.', 'Creez des demandes ou notifications et envoyez-les aux utilisateurs selectionnes.');
              crudBody.innerHTML = template.innerHTML;
              setModalContent('messages');
            }
          });
        });

        crudBody.querySelectorAll('[data-document-delete-id]').forEach((button) => {
          button.addEventListener('click', async () => {
            if (!apiDashboard || !window.AppAPI || typeof window.AppAPI.postJSON !== 'function') {
              notifyError(tr('Dashboard API is not available.', 'API dashboard indisponible.'));
              return;
            }

            const documentId = Number(button.getAttribute('data-document-delete-id') || 0);
            const documentName = String(button.getAttribute('data-document-delete-name') || 'document');
            if (!documentId) {
              notifyError(tr('Invalid document id.', 'ID document invalide.'));
              return;
            }

            if (!window.confirm(tr('Delete document "' + documentName + '"? This action cannot be undone.', 'Supprimer le document "' + documentName + '" ? Cette action est irreversible.'))) {
              return;
            }

            button.disabled = true;
            try {
              const response = await window.AppAPI.postJSON(apiDashboard, {
                action: 'delete_document',
                document_id: documentId,
              });
              if (!response || response.ok === false || response.success === false) {
                throw new Error((response && (response.error || response.message)) || tr('Unable to delete document.', 'Impossible de supprimer le document.'));
              }
              removeDocumentCard(button.closest('.company-card'));
              notifySuccess(tr('Document deleted successfully.', 'Document supprime avec succes.'));
            } catch (error) {
              notifyError((error && error.message) || tr('Unable to delete document.', 'Impossible de supprimer le document.'));
            } finally {
              button.disabled = false;
            }
          });
        });
        return;
      }

      if (entity === 'messages') {
        const messageHeading = crudBody.querySelector('#crud-message-form-heading');
        const messageKind = crudBody.querySelector('#crud-message-kind');
        const requestType = crudBody.querySelector('#crud-message-request-type');
        const messageTitle = crudBody.querySelector('#crud-message-title');
        const messageBody = crudBody.querySelector('#crud-message-body');
        const documentId = crudBody.querySelector('#crud-message-document-id');
        const recipients = crudBody.querySelector('#crud-message-recipient-ids');
        const messageSubmit = crudBody.querySelector('#crud-message-submit');
        const resetMessage = crudBody.querySelector('[data-crud-reset-message]');

        const syncKind = () => {
          const isNotification = messageKind && messageKind.value === 'notification';
          if (requestType) {
            requestType.disabled = isNotification;
            requestType.closest('label')?.classList.toggle('is-hidden', isNotification);
          }
          if (messageHeading) messageHeading.textContent = isNotification ? tr('Create notification', 'Creer une notification') : tr('Create request', 'Creer une demande');
          if (messageSubmit) messageSubmit.textContent = isNotification ? tr('Send notification', 'Envoyer la notification') : tr('Send request', 'Envoyer la demande');
        };

        const resetMessageForm = () => {
          if (messageKind) messageKind.value = 'request';
          if (requestType) requestType.value = 'leave';
          if (messageTitle) messageTitle.value = '';
          if (messageBody) messageBody.value = '';
          if (documentId) documentId.value = '';
          if (recipients) Array.from(recipients.options).forEach((option) => { option.selected = false; });
          syncKind();
        };

        const applyRequestedDocument = () => {
          if (!requestedMessageDocument || !documentId) return;
          const requestedId = Number(requestedMessageDocument.id || 0);
          if (requestedId > 0) {
            documentId.value = String(requestedId);
            if (messageTitle && !messageTitle.value) {
              messageTitle.value = tr('Planning document: ', 'Document planning : ') + (requestedMessageDocument.name || tr('Document', 'Document'));
            }
            if (messageBody && !messageBody.value) {
              messageBody.value = tr('Please review the attached planning document.', 'Veuillez consulter le document de planning joint.');
            }
            if (messageKind) {
              messageKind.value = 'notification';
              syncKind();
            }

            if (recipients) {
              const wanted = new Set((requestedMessageDocument.recipientIds || []).map((id) => Number(id)));
              if (wanted.size > 0) {
                Array.from(recipients.options).forEach((option) => {
                  option.selected = wanted.has(Number(option.value || 0));
                });
              }
            }
          }
          requestedMessageDocument = null;
        };

        if (messageKind) {
          messageKind.addEventListener('change', syncKind);
        }

        if (resetMessage) resetMessage.addEventListener('click', resetMessageForm);
        syncKind();
        resetMessageForm();
        applyRequestedDocument();
        return;
      }

      if (entity === 'companies') {
        const companyHeading = crudBody.querySelector('#crud-company-form-heading');
        const companyAction = crudBody.querySelector('#crud-company-action');
        const companyId = crudBody.querySelector('#crud-company-id');
        const companySubmit = crudBody.querySelector('#crud-company-submit');
        const resetCompany = crudBody.querySelector('[data-crud-reset-company]');
        const companyForm = crudBody.querySelector('#crud-company-form');

        const resetCompanyForm = () => {
          if (companyHeading) companyHeading.textContent = tr('Create company', 'Creer une entreprise');
          if (companyAction) companyAction.value = 'create';
          if (companyId) companyId.value = '';
          if (companySubmit) companySubmit.textContent = tr('Create company', 'Creer une entreprise');
          if (companyForm) companyForm.reset();
        };

        const fillCompanyForm = (card) => {
          if (!card) return;
          const data = card.dataset || {};
          if (companyHeading) companyHeading.textContent = tr('Edit company', 'Modifier l entreprise');
          if (companyAction) companyAction.value = 'update';
          if (companyId) companyId.value = data.companyId || '';
          if (companySubmit) companySubmit.textContent = tr('Update company', 'Mettre a jour l entreprise');
          const fields = {
            'crud-company-name': data.companyName || '',
            'crud-company-type': data.companyType || 'other',
            'crud-company-city': data.companyCity || '',
            'crud-company-address': data.companyAddress || '',
            'crud-company-zip-code': data.companyZipCode || '',
            'crud-company-phone': data.companyPhone || '',
            'crud-company-email': data.companyEmail || '',
            'crud-company-logo-path': data.companyLogoPath || '',
            'crud-company-signature-ip': data.companySignatureIp || '',
          };
          Object.entries(fields).forEach(([id, value]) => {
            const input = crudBody.querySelector(`#${id}`);
            if (input) input.value = value;
          });
        };

        crudBody.querySelectorAll('[data-company-action="edit"]').forEach((button) => {
          button.addEventListener('click', () => fillCompanyForm(button.closest('.company-card')));
        });

        if (resetCompany) resetCompany.addEventListener('click', resetCompanyForm);
        resetCompanyForm();
      }

      if (entity === 'users') {
        const userHeading = crudBody.querySelector('#crud-user-form-heading');
        const userAction = crudBody.querySelector('#crud-user-action');
        const userId = crudBody.querySelector('#crud-user-id');
        const userSubmit = crudBody.querySelector('#crud-user-submit');
        const userForm = crudBody.querySelector('#crud-user-form');
        const userCompanyFilter = crudBody.querySelector('#crud-user-company-filter');
        const userDepartment = crudBody.querySelector('#crud-user-department-id');
        const resetUser = crudBody.querySelector('[data-crud-reset-user]');

        const resetUserForm = () => {
          if (userHeading) userHeading.textContent = tr('Create user', 'Creer un utilisateur');
          if (userAction) userAction.value = 'create';
          if (userId) userId.value = '';
          if (userSubmit) userSubmit.textContent = tr('Create user', 'Creer un utilisateur');
          if (userForm) userForm.reset();
          syncDepartmentFilter(userDepartment, '');
        };

        const setCompanyFilterFromDepartment = (departmentId) => {
          if (!userCompanyFilter || !userDepartment) return;
          const selectedOption = userDepartment.querySelector(`option[value="${departmentId}"]`);
          const companyId = selectedOption ? (selectedOption.getAttribute('data-company-id') || '') : '';
          userCompanyFilter.value = companyId;
          syncDepartmentFilter(userDepartment, companyId);
          if (departmentId) userDepartment.value = String(departmentId);
        };

        const fillUserForm = (card) => {
          if (!card) return;
          const data = card.dataset || [];
          if (userHeading) userHeading.textContent = tr('Edit user', 'Modifier l utilisateur');
          if (userAction) userAction.value = 'update';
          if (userId) userId.value = data.userId || '';
          if (userSubmit) userSubmit.textContent = tr('Update user', 'Mettre a jour l utilisateur');
          const fields = {
            'crud-user-first-name': data.userFirstName || '',
            'crud-user-last-name': data.userLastName || '',
            'crud-user-email': data.userEmail || '',
            'crud-user-phone': data.userPhone || '',
            'crud-user-role': data.userRole || 'employee',
            'crud-user-status': data.userStatus || 'active',
            'crud-user-password': '',
          };
          Object.entries(fields).forEach(([id, value]) => {
            const input = crudBody.querySelector(`#${id}`);
            if (input) input.value = value;
          });
          const departmentId = data.userDepartmentId || '';
          setCompanyFilterFromDepartment(departmentId);
          if (userDepartment && departmentId) userDepartment.value = String(departmentId);
        };

        if (userCompanyFilter && userDepartment) {
          userCompanyFilter.addEventListener('change', () => {
            syncDepartmentFilter(userDepartment, userCompanyFilter.value);
          });
        }

        crudBody.querySelectorAll('[data-user-action="edit"]').forEach((button) => {
          button.addEventListener('click', () => fillUserForm(button.closest('.company-card')));
        });

        if (resetUser) resetUser.addEventListener('click', resetUserForm);
        resetUserForm();
      }

      if (entity === 'departments') {
        const departmentHeading = crudBody.querySelector('#crud-department-form-heading');
        const departmentAction = crudBody.querySelector('#crud-department-action');
        const departmentId = crudBody.querySelector('#crud-department-id');
        const departmentSubmit = crudBody.querySelector('#crud-department-submit');
        const departmentForm = crudBody.querySelector('#crud-department-edit-form');
        const departmentCompany = crudBody.querySelector('#crud-department-company-select');
        const departmentHead = crudBody.querySelector('#crud-department-head-user-select');
        const resetDepartment = crudBody.querySelector('[data-crud-reset-department]');

        const filterDepartmentHeads = (companyId) => {
          if (!departmentHead) return;
          const normalized = String(companyId || '');
          Array.from(departmentHead.options).forEach((option) => {
            if (option.value === '') return;
            const optionCompanyId = option.getAttribute('data-company-id') || '';
            option.hidden = normalized !== '' && optionCompanyId !== normalized;
          });
          if (departmentHead.value && departmentHead.selectedOptions[0] && departmentHead.selectedOptions[0].hidden) {
            departmentHead.value = '';
          }
        };

        const resetDepartmentForm = () => {
          if (departmentHeading) departmentHeading.textContent = tr('Create department', 'Creer un departement');
          if (departmentAction) departmentAction.value = 'create';
          if (departmentId) departmentId.value = '';
          if (departmentSubmit) departmentSubmit.textContent = tr('Create department', 'Creer un departement');
          if (departmentForm) departmentForm.reset();
          filterDepartmentHeads('');
        };

        const fillDepartmentForm = (card) => {
          if (!card) return;
          const data = card.dataset || {};
          if (departmentHeading) departmentHeading.textContent = tr('Edit department', 'Modifier le departement');
          if (departmentAction) departmentAction.value = 'update';
          if (departmentId) departmentId.value = data.departmentId || '';
          if (departmentSubmit) departmentSubmit.textContent = tr('Update department', 'Mettre a jour le departement');
          if (departmentCompany) departmentCompany.value = data.departmentCompanyId || '';
          filterDepartmentHeads(data.departmentCompanyId || '');
          const fields = {
            'crud-department-name-input': data.departmentName || '',
            'crud-department-description-input': data.departmentDescription || '',
            'crud-department-head-user-select': data.departmentHeadUserId || '',
          };
          Object.entries(fields).forEach(([id, value]) => {
            const input = crudBody.querySelector(`#${id}`);
            if (input) input.value = value;
          });
        };

        if (departmentCompany) {
          departmentCompany.addEventListener('change', () => {
            filterDepartmentHeads(departmentCompany.value);
          });
        }

        crudBody.querySelectorAll('[data-department-action="edit"]').forEach((button) => {
          button.addEventListener('click', () => fillDepartmentForm(button.closest('.company-card')));
        });

        if (resetDepartment) resetDepartment.addEventListener('click', resetDepartmentForm);
        resetDepartmentForm();
      }
    };

    /**
     * closeAll()
     * Close any open dashboard modal and restore page state and focus.
     */
    const closeAll = () => {
      modals.forEach((modal) => {
        modal.hidden = true;
        modal.classList.remove('is-open');
      });
      if (overlay) {
        overlay.hidden = true;
        overlay.classList.remove('is-open');
      }
      document.body.classList.remove('modal-open');
      activeModal = null;
      if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
        lastFocusedElement.focus();
      }
    };

    openButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-modal-target');
        const entity = button.getAttribute('data-modal-entity') || '';
        const title = button.getAttribute('data-modal-title') || button.textContent.trim();
        const targetModal = document.getElementById(targetId);
        if (!targetModal) return;
        lastFocusedElement = document.activeElement;
        closeAll();
        openButtons.forEach((item) => item.classList.remove('is-active'));
        button.classList.add('is-active');

        if (targetId === 'crud-modal' && crudModal) {
          const templateId = entity ? `crud-template-${entity}` : 'crud-template-placeholder';
          const template = document.getElementById(templateId) || document.getElementById('crud-template-placeholder');
          if (crudTitle) crudTitle.textContent = title;
          if (crudSubtitle) {
            crudSubtitle.textContent = entity === 'companies'
              ? tr('Create, edit and manage companies and departments.', 'Creez, modifiez et gerez entreprises et departements.')
              : entity === 'users'
                ? tr('Create, edit and assign users by role and department.', 'Creez, modifiez et assignez les utilisateurs par role et departement.')
                : entity === 'departments'
                  ? tr('Create, edit and assign departments by company and head.', 'Creez, modifiez et assignez les departements par entreprise et responsable.')
                  : entity === 'messages'
                    ? tr('Create requests or notifications and send them to selected users.', 'Creez des demandes ou notifications et envoyez-les aux utilisateurs selectionnes.')
                    : entity === 'documents'
                      ? tr('Browse documents available for attachments.', 'Parcourez les documents disponibles pour les pieces jointes.')
                : tr('Common CRUD shell', 'Conteneur CRUD commun');
          }
          if (crudBody && template) crudBody.innerHTML = template.innerHTML;
          setModalContent(entity);
        }

        targetModal.hidden = false;
        targetModal.classList.add('is-open');
        targetModal.dispatchEvent(new CustomEvent('modal:open'));
        if (overlay) { overlay.hidden = false; overlay.classList.add('is-open'); }
        document.body.classList.add('modal-open');
        activeModal = targetModal;
        window.setTimeout(() => focusFirst(targetModal), 0);
      });
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeAll));
    if (overlay) overlay.addEventListener('click', closeAll);
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeAll();
        return;
      }

      if (event.key !== 'Tab' || !activeModal) {
        return;
      }

      const focusables = activeModal.querySelectorAll(focusableSelector);
      if (focusables.length === 0) {
        event.preventDefault();
        return;
      }

      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    /**
     * openModalFromQuery()
     * If the URL contains `?modal=<entity>`, attempt to open the corresponding
     * modal by triggering the matching sidebar/button element. This supports
     * deep linking to modal content for demos or bookmarking.
     */
    const openModalFromQuery = () => {
      const params = new URLSearchParams(window.location.search);
      const modalEntity = params.get('modal');
      if (!modalEntity) return;

      const trigger = document.querySelector(`[data-modal-entity="${modalEntity}"]`) ||
        (modalEntity === 'settings' ? document.querySelector('[data-modal-target="modal-settings"]') : null);
      if (trigger && typeof trigger.click === 'function') {
        window.__dashboardRequestedSettingsTab = params.get('settings_tab') || '';
        window.history.replaceState({}, '', `${window.location.pathname}${window.location.hash}`);
        trigger.click();
      }
    };

    const setupSettingsTabs = () => {
      const settingsModal = document.getElementById('modal-settings');
      if (!settingsModal) return;

      const tabButtons = Array.from(settingsModal.querySelectorAll('[data-settings-tab]'));
      const panels = Array.from(settingsModal.querySelectorAll('[data-settings-panel]'));
      const tabInput = settingsModal.querySelector('[data-settings-tab-input]');
      const companySelect = settingsModal.querySelector('[data-settings-company-select]');
      if (tabButtons.length === 0 || panels.length === 0) return;

      const activateTab = (tabName) => {
        tabButtons.forEach((button) => {
          const isActive = button.getAttribute('data-settings-tab') === tabName;
          button.classList.toggle('is-active', isActive);
          button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
          const isActive = panel.getAttribute('data-settings-panel') === tabName;
          panel.classList.toggle('is-active', isActive);
          panel.hidden = !isActive;
        });

        if (tabInput) {
          tabInput.value = tabName || '';
        }
      };

      const resetTabs = () => {
        tabButtons.forEach((button) => {
          button.classList.remove('is-active');
          button.setAttribute('aria-selected', 'false');
        });

        panels.forEach((panel) => {
          panel.classList.remove('is-active');
          panel.hidden = true;
        });
      };

      tabButtons.forEach((button) => {
        button.addEventListener('click', () => activateTab(button.getAttribute('data-settings-tab')));
      });

      if (companySelect) {
        companySelect.addEventListener('change', () => {
          const activeTab = settingsModal.querySelector('[data-settings-tab].is-active')?.getAttribute('data-settings-tab') || '';
          if (tabInput) tabInput.value = activeTab;
          companySelect.form?.submit();
        });
      }

      // Start with all panels closed; open only when a tab is clicked.
      resetTabs();

      // Each time settings modal opens, keep all panels closed until a tab click.
      settingsModal.addEventListener('modal:open', () => {
        resetTabs();
        const requestedTab = window.__dashboardRequestedSettingsTab || '';
        if (requestedTab && panels.some((panel) => panel.getAttribute('data-settings-panel') === requestedTab)) {
          activateTab(requestedTab);
        }
        window.__dashboardRequestedSettingsTab = '';
      });
    };

    window.setTimeout(openModalFromQuery, 0);
    setupSettingsTabs();
  })();

  /**
   * setupCompanyActions
   * Attach lightweight event handlers to directory/company cards. Actions
   * call `AppAPI` methods that perform API requests and then react to the
   * returned JSON (showing alerts or reloading when necessary). This code
   * intentionally keeps interactions simple and synchronous for the demo.
   */
  (function setupCompanyActions(){
    if (!window.AppAPI) return;
    const feedback = window.DashboardFeedback;
    const notifyError = (message) => {
      if (feedback?.error) {
        feedback.error('Oops!', message);
        return;
      }
      console.error(message);
    };
    const notifySuccess = (message) => {
      if (feedback?.success) {
        feedback.success('Done', message);
      }
    };
    const confirmAction = async (message) => {
      if (feedback?.confirm) {
        return feedback.confirm(message, tr('Confirm action', 'Confirmer l action'));
      }
      notifyError(tr('Confirmation dialog is not available.', 'La boite de confirmation n est pas disponible.'));
      return false;
    };

    document.querySelectorAll('.dashboard-directory-card').forEach(card => {
      const companyId = card.getAttribute('data-company-id');
      if (!companyId) return;
      card.querySelectorAll('.company-actions [data-action]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const action = btn.getAttribute('data-action');
          try {
            if (action === 'set-ip') {
              const ip = prompt(tr('Signature IP address (leave blank to remove):', 'Adresse IP de signature (laisser vide pour supprimer) :'));
              if (ip === null) return;
              const j = await AppAPI.companies.setSignatureIp(apiCompanies, companyId, ip);
              if (!j.ok) notifyError(tr('Error: ', 'Erreur : ') + (j.error || tr('unknown', 'inconnue'))); else notifySuccess(tr('Company Wi-Fi IP updated.', 'IP Wi-Fi entreprise mise a jour.'));
              return;
            }

            if (action === 'delete') {
              if (!await confirmAction(tr('Confirm deletion of this company?', 'Confirmer la suppression de cette entreprise ?'))) return;
              const j = await AppAPI.companies.delete(apiCompanies, companyId);
                if (!jr.ok) notifyError(tr('Error: ', 'Erreur : ') + (jr.error || tr('unknown', 'inconnue'))); else location.reload();
              return;
            }
                if (!jr.ok) notifyError(tr('Error: ', 'Erreur : ') + (jr.error || tr('unknown', 'inconnue'))); else location.reload();
            if (action === 'manage-departments') {
              const j = await AppAPI.departments.list(apiDepartments, companyId);
              if (!j.ok) { notifyError('Error: ' + (j.error || 'unknown')); return; }
              const list = j.departments.map(d => `${d.id}: ${d.name}`).join('\n') || tr('No departments', 'Aucun departement');
              const cmd = prompt(tr('Departments:\n', 'Departements :\n') + list + tr('\n\nTo create: type a new name. To delete: del:<id>', '\n\nPour creer : tapez un nouveau nom. Pour supprimer : del:<id>'));
              if (!cmd) return;
              if (cmd.startsWith('del:')) {
                const id = cmd.split(':')[1];
                const jr = await AppAPI.departments.delete(apiDepartments, id);
                if (!jr.ok) notifyError('Error: ' + (jr.error || 'unknown')); else location.reload();
              } else {
                const jr = await AppAPI.departments.create(apiDepartments, companyId, cmd);
                if (!jr.ok) notifyError('Error: ' + (jr.error || 'unknown')); else location.reload();
              }
              return;
            }

            if (action === 'manage-employees') {
              const j = await AppAPI.users.listByCompany(apiUsers, companyId);
              if (!j.ok) { notifyError('Error: ' + (j.error || 'unknown')); return; }
              const list = j.users.map(u => `${u.id}: ${u.first_name} ${u.last_name} (${u.role})`).join('\n') || tr('No employees', 'Aucun employe');
              const cmd = prompt(tr('Employees:\n', 'Employes :\n') + list + tr('\n\nTo create: new:First Last,email,role. To delete: del:<id>', '\n\nPour creer : new:Prenom Nom,email,role. Pour supprimer : del:<id>'));
              if (!cmd) return;
              if (cmd.startsWith('del:')) {
                const id = cmd.split(':')[1];
                const jr = await AppAPI.users.delete(apiUsers, id);
                if (!jr.ok) notifyError(tr('Error: ', 'Erreur : ') + (jr.error || tr('unknown', 'inconnue'))); else location.reload();
              } else if (cmd.startsWith('new:')) {
                const payload = cmd.substring(4).split(',');
                const name = payload[0] || ''; const email = payload[1] || ''; const role = payload[2] || 'employee';
                const names = name.split(' '); const first = names.shift(); const last = names.join(' ') || '';
                const jr = await AppAPI.users.create(apiUsers, { department_id: null, first_name: first, last_name: last, email, role });
                if (!jr.ok) notifyError(tr('Error: ', 'Erreur : ') + (jr.error || tr('unknown', 'inconnue'))); else location.reload();
              }
              return;
            }

            if (action === 'assign-head') { notifyError(tr('Use the Manage Employees flow and assign-head through the next UI step.', 'Utilisez le flux Gerer les employes puis assignez le responsable a l etape suivante.')); return; }
            if (action === 'edit') { notifyError(tr('Edit company UI is not implemented yet.', 'L interface de modification entreprise n est pas encore implementee.')); return; }

          } catch (err) { notifyError(tr('Network error: ', 'Erreur reseau : ') + err.message); }
        });
      });
    });
  })();

  /**
   * setupSidebarAndCalendar()
   * Wire the hover sidebar, the calendar navigator, the planner view, and
   * drag-and-drop shift assignments.
   */
  (function setupSidebarAndCalendar(){
    const sidebar = document.getElementById('dashboard-sidebar');
    const sidebarHandle = document.querySelector('[data-sidebar-hover-handle]');
    const navigatorToggleButtons = document.querySelectorAll('[data-calendar-navigator-toggle]');
    const navigatorPanel = document.getElementById('dashboard-calendar-navigator');
    const calendarShell = document.querySelector('[data-dashboard-calendar-shell]');
    const calendarSection = calendarShell ? calendarShell.closest('.dashboard-calendar-shell') : null;
    const calendarDetail = document.querySelector('[data-calendar-detail]');
    const plannerDetail = document.querySelector('[data-sidebar-planner-detail]');
    const plannerDepartmentButtons = document.querySelectorAll('[data-planner-department-id]');
    const plannerData = window.DashboardPlannerData || {};
    const apiDashboard = (window.DashboardConfig || {}).apiDashboard;
    const RULES_STORAGE_KEY = 'staffease:auto-assign-rules:v1';

    if (!sidebar && !calendarShell && !navigatorPanel) {
      return;
    }

    const safeParseJson = (value, fallback) => {
      try {
        return JSON.parse(value);
      } catch (error) {
        return fallback;
      }
    };

    const loadAssignmentRules = () => {
      try {
        const raw = window.localStorage.getItem(RULES_STORAGE_KEY);
        if (!raw) return {};
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : {};
      } catch (_error) {
        return {};
      }
    };

    const toLocalDate = (value) => {
      if (!value) return new Date();
      const candidate = new Date(`${value}T12:00:00`);
      return Number.isNaN(candidate.getTime()) ? new Date() : candidate;
    };

    const pad = (value) => String(value).padStart(2, '0');
    const dateKey = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const addDays = (date, amount) => {
      const next = new Date(date);
      next.setDate(next.getDate() + amount);
      return next;
    };
    const addMonths = (date, amount) => {
      const next = new Date(date);
      next.setMonth(next.getMonth() + amount);
      return next;
    };
    const addYears = (date, amount) => {
      const next = new Date(date);
      next.setFullYear(next.getFullYear() + amount);
      return next;
    };
    const startOfWeek = (date) => {
      const start = new Date(date);
      const day = start.getDay();
      const offset = day === 0 ? -6 : 1 - day;
      start.setDate(start.getDate() + offset);
      return start;
    };
    const localeForDates = isFr ? 'fr-FR' : 'en-US';
    const monthNames = Array.from({ length: 12 }, (_, index) => new Intl.DateTimeFormat(localeForDates, { month: 'short' }).format(new Date(2024, index, 1)));
    const weekdayNames = Array.from({ length: 7 }, (_, index) => new Intl.DateTimeFormat(localeForDates, { weekday: 'short' }).format(new Date(2024, 0, 1 + index)));
    const fullDateFormatter = new Intl.DateTimeFormat(localeForDates, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    const shortDateFormatter = new Intl.DateTimeFormat(isFr ? 'fr-FR' : 'en-GB', { day: '2-digit', month: '2-digit', year: '2-digit' });
    const monthYearFormatter = new Intl.DateTimeFormat(localeForDates, { month: 'long', year: 'numeric' });
    const monthLabelFormatter = new Intl.DateTimeFormat(localeForDates, { month: 'long' });

    const rawAssignments = Array.isArray(plannerData.assignments) ? plannerData.assignments : [];
    const attendances = Array.isArray(plannerData.attendances) ? plannerData.attendances : [];
    const departments = Array.isArray(plannerData.departments) ? plannerData.departments : [];
    const calendarToday = toLocalDate(plannerData.today || (calendarShell ? calendarShell.getAttribute('data-calendar-today') : ''));
    const events = rawAssignments.map((item) => ({ ...item }));
    let nextAssignmentId = events.reduce((max, item) => Math.max(max, Number(item.assignment_id || 0)), 0) + 1;

    const initialCalendarMode = ['day', 'week', 'fortnight', 'month', 'year'].includes(plannerData.mode) ? plannerData.mode : 'week';

    const state = {
      mode: initialCalendarMode === 'day' ? 'week' : initialCalendarMode,
      navigationMode: initialCalendarMode,
      focusDate: calendarToday,
      selectedDate: calendarToday,
      activeDepartmentId: Number(plannerData.active_department_id || departments[0]?.id || 0),
      activeShiftId: Number(plannerData.active_shift_id || 0),
      activeUserId: 0,
      activeUserName: '',
      calendarExpanded: false,
      draggingUserId: null,
      draggingAssignmentId: null,
    };

    const publishPlannerRuntime = () => {
      window.DashboardPlannerRuntime = {
        getState: () => ({
          mode: state.mode,
          navigationMode: state.navigationMode,
          focusDate: new Date(state.focusDate),
          selectedDate: new Date(state.selectedDate),
          activeDepartmentId: Number(state.activeDepartmentId || 0),
          activeShiftId: Number(state.activeShiftId || 0),
          activeUserId: Number(state.activeUserId || 0),
          activeUserName: String(state.activeUserName || ''),
          calendarExpanded: !!state.calendarExpanded,
        }),
        getDepartments: () => (departments || []).slice(),
        getEvents: () => (events || []).slice(),
      };
      document.dispatchEvent(new CustomEvent('dashboard:planner-updated'));
    };

    const eventDateKey = (event) => event.work_date || '';
    const eventsByDate = () => events.reduce((map, event) => {
      const key = eventDateKey(event);
      if (!key) return map;
      if (!map.has(key)) map.set(key, []);
      map.get(key).push(event);
      return map;
    }, new Map());

    const getDepartmentById = (departmentId) => departments.find((department) => Number(department.id) === Number(departmentId)) || departments[0] || null;
    const getActiveDepartment = () => getDepartmentById(state.activeDepartmentId);
    const getActiveShifts = () => (getActiveDepartment()?.shifts || []);
    const getActiveUsers = () => (getActiveDepartment()?.users || []);
    const getActiveShift = () => getActiveShifts().find((shift) => Number(shift.id) === Number(state.activeShiftId)) || getActiveShifts()[0] || null;
    const getActiveUser = () => getActiveUsers().find((user) => Number(user.id) === Number(state.activeUserId)) || null;
    const getActiveUserForCalendar = () => {
      const user = getActiveUser();
      if (!user) {
        return null;
      }
      const fullName = String((user.first_name || '') + ' ' + (user.last_name || '')).trim();
      return {
        id: Number(user.id || 0),
        name: fullName || String(user.email || 'Employee'),
      };
    };
    const normalizeAbsenceKind = (value) => {
      const normalized = String(value || '').toLowerCase().replace(/[^a-z]/g, '');
      if (!normalized) return '';
      if (normalized === 'rest' || normalized === 'restday' || normalized === 'dayoff' || normalized === 'reposo') return 'rest';
      if (normalized === 'vacation' || normalized === 'vacations' || normalized === 'holiday' || normalized === 'holidays' || normalized === 'leave' || normalized === 'conge' || normalized === 'conges' || normalized === 'ferie') return 'vacation';
      if (normalized === 'sick' || normalized === 'sickness' || normalized === 'sickleave' || normalized === 'maladie' || normalized === 'malattia') return 'sick';
      return normalized;
    };

    const getAbsenceTemplateShift = (kind) => {
      const targetKind = normalizeAbsenceKind(kind);
      if (!targetKind) return null;
      return getActiveShifts().find((shift) => normalizeAbsenceKind(shift?.kind || '') === targetKind) || null;
    };

    const getAbsenceTemplateShiftId = (kind) => {
      const match = getAbsenceTemplateShift(kind);
      return Number(match?.id || 0);
    };
    const getUserAvailabilityStatus = (userId, slotDate) => {
      const normalizedUserId = Number(userId || 0);
      const normalizedDate = String(slotDate || '').trim();
      if (!normalizedUserId || !/^\d{4}-\d{2}-\d{2}$/.test(normalizedDate)) {
        return { available: true, reason: '' };
      }

      const assignedOnDate = events.find((item) =>
        Number(item.user_id || 0) === normalizedUserId
        && String(item.work_date || '') === normalizedDate
        && String(item.status || '').toLowerCase() !== 'cancelled'
      );
      if (assignedOnDate) {
        const assignedKind = String(assignedOnDate.shift_kind || 'work').toLowerCase();
        if (assignedKind === 'rest') {
          return { available: false, reason: 'Unavailable: rest day.' };
        }
        if (assignedKind === 'sick') {
          return { available: false, reason: 'Unavailable: sick leave.' };
        }
        if (assignedKind === 'vacation') {
          return { available: false, reason: 'Unavailable: vacation.' };
        }
        return { available: false, reason: 'Unavailable: employee already assigned for this day.' };
      }

      const rules = loadAssignmentRules();
      const rule = rules[String(normalizedUserId)] || rules[normalizedUserId] || null;
      if (!rule || typeof rule !== 'object') {
        return { available: true, reason: '' };
      }

      const slotMonth = normalizedDate.slice(0, 7);
      const currentMonth = dateKey(calendarToday).slice(0, 7);
      const nextMonthDate = new Date(calendarToday.getFullYear(), calendarToday.getMonth() + 1, 1, 12, 0, 0, 0);
      const nextMonth = dateKey(nextMonthDate).slice(0, 7);
      const scope = String(rule.scope || 'all');
      if (scope === 'current' && slotMonth !== currentMonth) {
        return { available: true, reason: '' };
      }
      if (scope === 'next' && slotMonth < nextMonth) {
        return { available: true, reason: '' };
      }

      const specialDates = Array.isArray(rule.special_dates) ? rule.special_dates : [];
      const specialMatch = specialDates.find((item) => String(item?.date || '') === normalizedDate);
      if (specialMatch) {
        const reason = String(specialMatch.reason || 'special').toLowerCase();
        if (reason === 'rest') {
          return { available: false, reason: 'Unavailable: rest day.' };
        }
        if (reason === 'sick') {
          return { available: false, reason: 'Unavailable: sick leave.' };
        }
        if (reason === 'vacation') {
          return { available: false, reason: 'Unavailable: vacation.' };
        }
        if (reason === 'leave') {
          return { available: false, reason: 'Unavailable: leave.' };
        }
        return { available: false, reason: 'Unavailable.' };
      }

      const offWeekdays = Array.isArray(rule.off_weekdays) ? rule.off_weekdays.map((value) => Number(value)) : [];
      const weekday = new Date(`${normalizedDate}T12:00:00`).getDay();
      if (offWeekdays.includes(weekday)) {
        return { available: false, reason: 'Unavailable: rest day.' };
      }

      return { available: true, reason: '' };
    };

    const isUserAvailableForDate = (userId, slotDate) => getUserAvailabilityStatus(userId, slotDate).available;

    const setActiveDepartment = (departmentId) => {
      const department = getDepartmentById(departmentId);
      if (!department) return;
      state.activeDepartmentId = Number(department.id);
      const workShifts = (department.shifts || []).filter((shift) => String(shift?.kind || 'work').toLowerCase() === 'work');
      const firstShift = workShifts[0] || (department.shifts || [])[0] || null;
      state.activeShiftId = firstShift ? Number(firstShift.id) : 0;
      const nextUser = (department.users || []).find((user) => Number(user.id) === Number(state.activeUserId));
      state.activeUserId = nextUser ? Number(nextUser.id) : 0;
      state.activeUserName = nextUser ? `${nextUser.first_name || ''} ${nextUser.last_name || ''}`.trim() : '';
      renderSidebarPlanner();
      renderCalendar();
    };

    const setActiveShift = (shiftId) => {
      state.activeShiftId = Number(shiftId);
      renderSidebarPlanner();
      renderCalendar();
    };

    const setActiveUser = (userId, userName) => {
      const normalizedId = Number(userId || 0);
      if (normalizedId > 0 && normalizedId === Number(state.activeUserId || 0)) {
        state.activeUserId = 0;
        state.activeUserName = '';
      } else {
        state.activeUserId = normalizedId;
        state.activeUserName = String(userName || '').trim();
      }
      renderSidebarPlanner();
      renderCalendar();
    };

    const formatShiftTime = (shift) => {
      const start = typeof shift.start_time === 'string' ? shift.start_time.slice(0, 5) : '--:--';
      const end = typeof shift.end_time === 'string' ? shift.end_time.slice(0, 5) : '--:--';
      return `${start} - ${end}`;
    };

    const formatEventTime = (event) => formatShiftTime(event);

    const formatRangeLabel = () => {
      if (state.mode === 'year') return String(state.focusDate.getFullYear());
      if (state.mode === 'month') return monthYearFormatter.format(state.focusDate);
      if (state.mode === 'day') return fullDateFormatter.format(state.focusDate);
      const isSlidingWeek = state.mode === 'week' && state.navigationMode === 'day';
      const start = state.mode === 'fortnight'
        ? state.focusDate
        : (isSlidingWeek ? state.focusDate : startOfWeek(state.focusDate));
      const end = state.mode === 'fortnight' ? addDays(start, 14) : addDays(start, 6);
      return `${shortDateFormatter.format(start)} - ${shortDateFormatter.format(end)}`;
    };

    const getVisibleRange = () => {
      if (state.mode === 'year') {
        return { start: new Date(state.focusDate.getFullYear(), 0, 1, 12, 0, 0, 0), end: new Date(state.focusDate.getFullYear(), 11, 31, 12, 0, 0, 0) };
      }
      if (state.mode === 'month') {
        const first = startOfWeek(new Date(state.focusDate.getFullYear(), state.focusDate.getMonth(), 1, 12, 0, 0, 0));
        const last = addDays(startOfWeek(new Date(state.focusDate.getFullYear(), state.focusDate.getMonth() + 1, 0, 12, 0, 0, 0)), 6);
        return { start: first, end: last };
      }
      if (state.mode === 'fortnight') {
        const start = startOfWeek(state.focusDate);
        return { start, end: addDays(start, 14) };
      }
      if (state.mode === 'day') {
        return { start: state.selectedDate, end: state.selectedDate };
      }
      const start = state.navigationMode === 'day' ? new Date(state.focusDate) : startOfWeek(state.focusDate);
      return { start, end: addDays(start, 6) };
    };

    const formatNavigatorRange = () => {
      const range = getVisibleRange();
      return `${shortDateFormatter.format(range.start)} - ${shortDateFormatter.format(range.end)}`;
    };

    const getVisibleDateKeys = () => {
      const range = getVisibleRange();
      const keys = [];
      for (let cursor = new Date(range.start); cursor <= range.end; cursor = addDays(cursor, 1)) {
        keys.push(dateKey(cursor));
      }
      return keys;
    };

    const getCalendarCounters = () => {
      const activeDepartment = getActiveDepartment();
      if (!activeDepartment) {
        return { title: tr('Calendar', 'Calendrier'), totalShifts: 0, assignedShifts: 0, freeShifts: 0 };
      }

      const shifts = (activeDepartment.shifts || []).filter((shift) => String(shift?.kind || 'work').toLowerCase() === 'work');
      const visibleDateKeys = new Set(getVisibleDateKeys());
      const departmentAssignments = events.filter((event) => Number(event.department_id) === Number(activeDepartment.id));
      const assignedShifts = departmentAssignments.filter((event) => {
        if (!visibleDateKeys.has(event.work_date || '')) return false;
        return Number(event.user_id || 0) > 0;
      }).length;
      const totalShifts = shifts.length * visibleDateKeys.size;
      const freeShifts = Math.max(totalShifts - assignedShifts, 0);

      const departmentName = activeDepartment.name || 'Department';
      const departmentIcon = (activeDepartment.icon || '').toString().trim();
      const departmentColor = (activeDepartment.color || '#b98b12').toString();

      return {
        title: departmentName,
        titleIcon: departmentIcon,
        titleColor: departmentColor,
        totalShifts,
        assignedShifts,
        freeShifts,
      };
    };

    const updateChrome = () => {
      if (calendarShell) {
        calendarShell.dataset.calendarView = state.mode;
      }

      document.querySelectorAll('[data-calendar-mode]').forEach((button) => {
        const buttonMode = button.getAttribute('data-calendar-mode');
        const isActive = buttonMode === 'day'
          ? state.navigationMode === 'day'
          : (buttonMode === state.mode && state.navigationMode !== 'day');
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      document.querySelectorAll('[data-calendar-navigator-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', navigatorPanel && navigatorPanel.classList.contains('is-open') ? 'true' : 'false');
      });

      if (calendarSection) {
        const title = calendarSection.querySelector('[data-calendar-title]');
        const stats = calendarSection.querySelector('[data-calendar-stats]');
        const counters = getCalendarCounters();
        if (title) {
          title.innerHTML = renderDepartmentTitleHtml(counters.titleIcon || '', counters.title || '', counters.titleColor || '');
          title.style.color = counters.titleColor || '';
        }
        if (stats) {
          stats.textContent = isFr
            ? `${counters.totalShifts} postes • ${counters.assignedShifts} attribues • ${counters.freeShifts} libres`
            : `${counters.totalShifts} shifts • ${counters.assignedShifts} assigned • ${counters.freeShifts} free`;
        }
      }

      const navigatorRange = document.querySelector('[data-calendar-range-display]');
      if (navigatorRange) navigatorRange.value = formatNavigatorRange();
    };

    const calendarRenderer = (window.DashboardCalendarRenderer && typeof window.DashboardCalendarRenderer.create === 'function')
      ? window.DashboardCalendarRenderer.create({
        state,
        events,
        calendarToday,
        calendarShell,
        calendarDetail,
        monthNames,
        addDays,
        dateKey,
        startOfWeek,
        toLocalDate,
        pad,
        formatEventTime,
        fullDateFormatter,
        monthLabelFormatter,
        updateChrome,
        getActiveDepartment,
        isUserAvailableForDate,
        getUserAvailabilityStatus,
        getVisibleDateKeys,
      })
      : null;

    const renderCalendar = () => {
      if (calendarRenderer && typeof calendarRenderer.renderCalendar === 'function') {
        calendarRenderer.renderCalendar();
      }
      publishPlannerRuntime();
    };

    const syncPlannerSelection = () => {
      plannerDepartmentButtons.forEach((button) => {
        const isActive = Number(button.getAttribute('data-planner-department-id')) === Number(state.activeDepartmentId);
        button.classList.toggle('is-active', isActive);
      });
    };

    const renderSidebarPlanner = () => {
      syncPlannerSelection();
      if (!plannerDetail) return;
      const activeDepartment = getActiveDepartment();
      if (!activeDepartment) {
        plannerDetail.innerHTML = `<div class="dashboard-sidebar-planner-placeholder">${tr('No departments available.', 'Aucun departement disponible.')}</div>`;
        return;
      }

      const users = activeDepartment.users || [];
      const shifts = activeDepartment.shifts || [];
      const workShifts = shifts.filter((shift) => String(shift?.kind || 'work').toLowerCase() === 'work');
      if (workShifts.length > 0 && !workShifts.some((shift) => Number(shift.id) === Number(state.activeShiftId))) {
        state.activeShiftId = Number(workShifts[0].id || 0);
      }
      const deptName = activeDepartment.name || tr('Department', 'Departement');
      const deptIcon = (activeDepartment.icon || '').toString().trim() || '🏷️';
      const deptColor = (activeDepartment.color || '#b98b12').toString();
      const activeShift = getActiveShift();
      plannerDetail.innerHTML = `
        <div class="dashboard-sidebar-planner-title">
          <span style="color:${escapeHtml(deptColor)}">${renderIconHtml(deptIcon, deptColor)} ${escapeHtml(deptName)}</span>
          <span>${users.length} ${tr('staff', 'personnel')}</span>
        </div>
        <div class="dashboard-sidebar-planner-description">${activeDepartment.description || tr('Assigned team and shift list.', 'Equipe assignee et liste des postes.')}</div>
        <div>
          <div class="dashboard-sidebar-group-title"><span>👤</span> ${tr('Employees', 'Employes')}</div>
          <div class="dashboard-sidebar-chip-group">
            ${users.length ? users.map((user) => {
              const userId = Number(user.id || 0);
              const userName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || `${tr('Employee', 'Employe')} #${userId}`;
              const isActiveUser = userId === Number(state.activeUserId || 0);
              return `
              <article class="dashboard-sidebar-user-card ${isActiveUser ? 'is-active' : ''}" data-sidebar-user-card="${userId}" data-user-id="${userId}" data-user-name="${userName}">
                <button type="button" class="dashboard-sidebar-user-chip ${isActiveUser ? 'is-active' : ''}" draggable="true" data-user-id="${userId}" data-user-name="${userName}" title="${tr('Click or drag to calendar', 'Cliquez ou glissez vers le calendrier')}">
                  ${userName}
                </button>
              </article>
            `;
            }).join('') : `<div class="dashboard-sidebar-planner-placeholder">${tr('No employees in this department.', 'Aucun employe dans ce departement.')}</div>`}
          </div>
        </div>
        <div>
          <div class="dashboard-sidebar-group-title"><span>⏱</span> ${tr('Shifts', 'Postes')}</div>
          <div class="dashboard-sidebar-chip-group">
            ${workShifts.length ? workShifts.map((shift) => `
              <button type="button" class="dashboard-sidebar-shift-chip ${Number(shift.id) === Number(state.activeShiftId) ? 'is-active' : ''}" data-shift-id="${shift.id}" style="--shift-chip-color:${(shift.color || '#2f6fed')}">
                <span class="dashboard-sidebar-shift-icon">${renderIconHtml(shift.icon, shift.color || '#2f6fed')}</span>
                <span>${shift.name || tr('Shift', 'Poste')} ${formatShiftTime(shift)}</span>
              </button>
            `).join('') : `<div class="dashboard-sidebar-planner-placeholder">${tr('No work shifts configured.', 'Aucun poste de travail configure.')}</div>`}
          </div>
        </div>
      `;

      plannerDetail.querySelectorAll('[data-sidebar-user-card]').forEach((card) => {
        card.addEventListener('pointerdown', (event) => {
          if (event.button !== 0) return;
          setActiveUser(card.getAttribute('data-user-id'), card.getAttribute('data-user-name'));
        });
      });

      plannerDetail.querySelectorAll('.dashboard-sidebar-user-chip[data-user-id]').forEach((button) => {
        button.addEventListener('dragstart', (event) => {
          state.draggingUserId = button.getAttribute('data-user-id');
          state.draggingAssignmentId = null;
          event.dataTransfer?.setData('text/plain', JSON.stringify({ type: 'user', userId: state.draggingUserId }));
          event.dataTransfer?.setData('application/json', JSON.stringify({ type: 'user', userId: state.draggingUserId }));
        });
      });

      plannerDetail.querySelectorAll('[data-shift-id]').forEach((button) => {
        button.addEventListener('click', () => setActiveShift(button.getAttribute('data-shift-id')));
      });
    };

    const upsertAssignment = (assignment) => {
      const normalized = {
        assignment_id: Number(assignment.assignment_id || assignment.id || nextAssignmentId),
        work_date: assignment.work_date,
        status: assignment.status || 'assigned',
        notes: assignment.notes || null,
        shift_id: Number(assignment.shift_id || 0),
        shift_name: assignment.shift_name || '',
        shift_kind: assignment.shift_kind || 'work',
        shift_icon: assignment.shift_icon || '',
        shift_color: assignment.shift_color || '',
        start_time: assignment.start_time || null,
        end_time: assignment.end_time || null,
        department_id: Number(assignment.department_id || 0),
        department_name: assignment.department_name || '',
        department_color: assignment.department_color || '',
        user_id: Number(assignment.user_id || 0),
        user_name: assignment.user_name || '',
        assignment_source: assignment.assignment_source || (Number(assignment.user_id || 0) > 0 ? 'assigned' : 'open'),
      };
      const index = events.findIndex((item) => Number(item.assignment_id) === Number(normalized.assignment_id));
      if (index >= 0) {
        events[index] = normalized;
      } else {
        events.push(normalized);
      }
      nextAssignmentId += 1;
    };

    const assignShift = async (payload) => {
      if (!apiDashboard || !window.AppAPI) return;
      const response = await AppAPI.postJSON(apiDashboard, {
        action: 'assign_shift',
        ...payload,
      });
      if (!response || response.ok === false || response.success === false) {
        throw new Error(response?.error || response?.message || tr('Unable to save assignment', 'Impossible d enregistrer l affectation'));
      }
      if (response.assignment) {
        upsertAssignment(response.assignment);
      }
      renderCalendar();
    };

    const moveShift = async (payload) => {
      if (!apiDashboard || !window.AppAPI) return;
      const response = await AppAPI.postJSON(apiDashboard, {
        action: 'move_shift',
        ...payload,
      });
      if (!response || response.ok === false || response.success === false) {
        throw new Error(response?.error || response?.message || tr('Unable to move assignment', 'Impossible de deplacer l affectation'));
      }
      if (response.assignment) {
        upsertAssignment(response.assignment);
      }
      renderCalendar();
    };

    const unassignAssignment = async (assignmentId) => {
      if (!apiDashboard || !window.AppAPI || !assignmentId) return;
      const response = await AppAPI.postJSON(apiDashboard, {
        action: 'unassign_shift',
        assignment_id: Number(assignmentId),
      });
      if (!response || response.ok === false || response.success === false) {
        throw new Error(response?.error || response?.message || tr('Unable to unassign shift', 'Impossible de desaffecter le poste'));
      }
      const index = events.findIndex((item) => Number(item.assignment_id) === Number(assignmentId));
      if (index >= 0) {
        events[index] = {
          ...events[index],
          user_id: 0,
          user_name: '',
          status: 'open',
          assignment_source: 'open',
        };
      }
      renderCalendar();
    };

    const openDate = (date) => {
      state.selectedDate = new Date(date);
      if (state.mode === 'day') {
        state.focusDate = new Date(date);
      }
      renderCalendar();
    };

    const initCalendarRangePicker = () => {
      const rangeInput = document.querySelector('[data-calendar-range-display]');
      if (!rangeInput) return;

      const parseTypedDate = (rawValue) => {
        const value = String(rawValue || '').trim();
        if (!value) return null;

        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
          const isoDate = toLocalDate(value);
          return Number.isNaN(isoDate.getTime()) ? null : isoDate;
        }

        const match = value.match(/^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{2}|\d{4})$/);
        if (!match) return null;

        const day = Number(match[1]);
        const month = Number(match[2]);
        const year = Number(match[3].length === 2 ? `20${match[3]}` : match[3]);
        if (!day || !month || !year) return null;
        const parsed = new Date(year, month - 1, day, 12, 0, 0, 0);
        if (Number.isNaN(parsed.getTime())) return null;
        if (parsed.getDate() !== day || parsed.getMonth() !== month - 1 || parsed.getFullYear() !== year) return null;
        return parsed;
      };

      const applyTypedRange = (rawInputValue) => {
        const typed = String(rawInputValue || '').trim();
        if (!typed) {
          rangeInput.value = formatNavigatorRange();
          return;
        }

        const parts = typed.includes(' - ') ? typed.split(' - ') : [typed];
        const startDate = parseTypedDate(parts[0]);
        const endDate = parts[1] ? parseTypedDate(parts[1]) : startDate;
        if (!startDate || !endDate) {
          rangeInput.value = formatNavigatorRange();
          return;
        }

        const start = startDate <= endDate ? startDate : endDate;
        const end = endDate >= startDate ? endDate : startDate;
        const diffDays = Math.max(Math.round((end.getTime() - start.getTime()) / 86400000), 0);

        state.focusDate = new Date(start);
        state.selectedDate = new Date(start);

        if (diffDays === 14) {
          state.mode = 'fortnight';
          state.navigationMode = 'fortnight';
        } else if (diffDays >= 27 && diffDays <= 31) {
          state.mode = 'month';
          state.navigationMode = 'month';
          state.focusDate = new Date(start.getFullYear(), start.getMonth(), 1, 12, 0, 0, 0);
        } else if (diffDays === 6) {
          state.mode = 'week';
          state.navigationMode = 'day';
        } else if (diffDays === 0) {
          state.mode = 'week';
          state.navigationMode = 'day';
        } else {
          state.mode = 'week';
          state.navigationMode = 'day';
        }

        renderCalendar();
      };

      rangeInput.readOnly = false;
      rangeInput.setAttribute('inputmode', 'numeric');
      rangeInput.setAttribute('spellcheck', 'false');
      rangeInput.setAttribute('aria-label', tr('Type a date range for the calendar', 'Saisissez une plage de dates pour le calendrier'));

      let previousRangeValue = rangeInput.value;

      rangeInput.addEventListener('focus', () => {
        previousRangeValue = rangeInput.value;
        rangeInput.select();
      });

      rangeInput.addEventListener('click', () => {
        rangeInput.focus();
        rangeInput.select();
      });

      rangeInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          applyTypedRange(rangeInput.value);
          return;
        }
        if (event.key === 'Escape') {
          event.preventDefault();
          rangeInput.value = previousRangeValue || formatNavigatorRange();
          rangeInput.blur();
        }
      });

      rangeInput.addEventListener('blur', () => {
        applyTypedRange(rangeInput.value);
      });
    };

    if (window.DashboardSidebar && typeof window.DashboardSidebar.init === 'function') {
      window.DashboardSidebar.init({
        sidebar,
        sidebarHandle,
        plannerDepartmentButtons,
        setActiveDepartment,
      });
    }

    if (window.DashboardNavigator && typeof window.DashboardNavigator.init === 'function') {
      window.DashboardNavigator.init({
        navigatorPanel,
        navigatorToggleButtons,
        calendarToday,
        state,
        calendarShell,
        plannerData,
        toLocalDate,
        addDays,
        renderCalendar,
        updateChrome,
      });
    }

    if (window.DashboardCalendar && typeof window.DashboardCalendar.init === 'function') {
      window.DashboardCalendar.init({
        calendarShell,
        events,
        attendances,
        state,
        toLocalDate,
        openDate,
        unassignAssignment,
        assignShift,
        isUserAvailableForDate,
        getUserAvailabilityStatus,
        getActiveUser: getActiveUserForCalendar,
        getActiveShift,
        getAbsenceTemplateShiftId,
        getAbsenceTemplateShift,
      });
    }

    if (window.DashboardDnd && typeof window.DashboardDnd.init === 'function') {
      window.DashboardDnd.init({
        calendarShell,
        state,
        events,
        getActiveShift,
        assignShift,
        moveShift,
        safeParseJson,
        isUserAvailableForDate,
        getUserAvailabilityStatus,
      });
    }

    initCalendarRangePicker();

    renderSidebarPlanner();
    renderCalendar();
    publishPlannerRuntime();
  })();
})();
