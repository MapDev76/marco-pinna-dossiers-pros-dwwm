/* Comportements de l'interface du tableau de bord : modales et actions sociétés.
  Attend window.DashboardConfig = { apiCompanies, apiDepartments, apiUsers }
  ainsi que window.AppAPI.
*/
(function(){
  const config = window.DashboardConfig || {};
  const apiCompanies = config.apiCompanies;
  const apiDepartments = config.apiDepartments;
  const apiUsers = config.apiUsers;

  // Gestion des modales
  (function setupModals(){
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

    const focusableSelector = [
      'a[href]',
      'button:not([disabled])',
      'input:not([disabled]):not([type="hidden"])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    const focusFirst = (container) => {
      const focusables = container ? container.querySelectorAll(focusableSelector) : [];
      const first = focusables[0];
      if (first && typeof first.focus === 'function') {
        first.focus();
      }
    };

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

    const setModalContent = (entity) => {
      if (!crudBody) return;
      if (entity === 'documents') {
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
          if (messageHeading) messageHeading.textContent = isNotification ? 'Create notification' : 'Create request';
          if (messageSubmit) messageSubmit.textContent = isNotification ? 'Send notification' : 'Send request';
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

        if (messageKind) {
          messageKind.addEventListener('change', syncKind);
        }

        if (resetMessage) resetMessage.addEventListener('click', resetMessageForm);
        syncKind();
        resetMessageForm();
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
          if (companyHeading) companyHeading.textContent = 'Create company';
          if (companyAction) companyAction.value = 'create';
          if (companyId) companyId.value = '';
          if (companySubmit) companySubmit.textContent = 'Create company';
          if (companyForm) companyForm.reset();
        };

        const fillCompanyForm = (card) => {
          if (!card) return;
          const data = card.dataset || {};
          if (companyHeading) companyHeading.textContent = 'Edit company';
          if (companyAction) companyAction.value = 'update';
          if (companyId) companyId.value = data.companyId || '';
          if (companySubmit) companySubmit.textContent = 'Update company';
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
          if (userHeading) userHeading.textContent = 'Create user';
          if (userAction) userAction.value = 'create';
          if (userId) userId.value = '';
          if (userSubmit) userSubmit.textContent = 'Create user';
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
          if (userHeading) userHeading.textContent = 'Edit user';
          if (userAction) userAction.value = 'update';
          if (userId) userId.value = data.userId || '';
          if (userSubmit) userSubmit.textContent = 'Update user';
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
          if (departmentHeading) departmentHeading.textContent = 'Create department';
          if (departmentAction) departmentAction.value = 'create';
          if (departmentId) departmentId.value = '';
          if (departmentSubmit) departmentSubmit.textContent = 'Create department';
          if (departmentForm) departmentForm.reset();
          filterDepartmentHeads('');
        };

        const fillDepartmentForm = (card) => {
          if (!card) return;
          const data = card.dataset || {};
          if (departmentHeading) departmentHeading.textContent = 'Edit department';
          if (departmentAction) departmentAction.value = 'update';
          if (departmentId) departmentId.value = data.departmentId || '';
          if (departmentSubmit) departmentSubmit.textContent = 'Update department';
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
              ? 'Create, edit and manage companies and departments.'
              : entity === 'users'
                ? 'Create, edit and assign users by role and department.'
                : entity === 'departments'
                  ? 'Create, edit and assign departments by company and head.'
                  : entity === 'messages'
                    ? 'Create requests or notifications and send them to selected users.'
                    : entity === 'documents'
                      ? 'Browse documents available for attachments.'
                : 'Common CRUD shell';
          }
          if (crudBody && template) crudBody.innerHTML = template.innerHTML;
          setModalContent(entity);
        }

        targetModal.hidden = false;
        targetModal.classList.add('is-open');
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

    const openModalFromQuery = () => {
      const params = new URLSearchParams(window.location.search);
      const modalEntity = params.get('modal');
      if (!modalEntity) return;

      const trigger = document.querySelector(`[data-modal-entity="${modalEntity}"]`);
      if (trigger && typeof trigger.click === 'function') {
        window.history.replaceState({}, '', `${window.location.pathname}${window.location.hash}`);
        trigger.click();
      }
    };

    window.setTimeout(openModalFromQuery, 0);
  })();

  // Actions sociétés / départements / utilisateurs
  (function setupCompanyActions(){
    if (!window.AppAPI) return;
    document.querySelectorAll('.dashboard-directory-card').forEach(card => {
      const companyId = card.getAttribute('data-company-id');
      if (!companyId) return;
      card.querySelectorAll('.company-actions [data-action]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const action = btn.getAttribute('data-action');
          try {
            if (action === 'set-ip') {
              const ip = prompt('Signature IP address (leave blank to remove):');
              if (ip === null) return;
              const j = await AppAPI.companies.setSignatureIp(apiCompanies, companyId, ip);
              if (!j.ok) alert('Error: ' + (j.error || 'unknown')); else alert('IP updated');
              return;
            }

            if (action === 'delete') {
              if (!confirm('Confirm deletion of this company?')) return;
              const j = await AppAPI.companies.delete(apiCompanies, companyId);
              if (!j.ok) alert('Error: ' + (j.error || 'unknown')); else location.reload();
              return;
            }

            if (action === 'manage-departments') {
              const j = await AppAPI.departments.list(apiDepartments, companyId);
              if (!j.ok) { alert('Error: ' + (j.error || 'unknown')); return; }
              const list = j.departments.map(d => `${d.id}: ${d.name}`).join('\n') || 'No departments';
              const cmd = prompt('Departments:\n' + list + '\n\nTo create: type a new name. To delete: del:<id>');
              if (!cmd) return;
              if (cmd.startsWith('del:')) {
                const id = cmd.split(':')[1];
                const jr = await AppAPI.departments.delete(apiDepartments, id);
                if (!jr.ok) alert('Error: ' + (jr.error || 'unknown')); else location.reload();
              } else {
                const jr = await AppAPI.departments.create(apiDepartments, companyId, cmd);
                if (!jr.ok) alert('Error: ' + (jr.error || 'unknown')); else location.reload();
              }
              return;
            }

            if (action === 'manage-employees') {
              const j = await AppAPI.users.listByCompany(apiUsers, companyId);
              if (!j.ok) { alert('Error: ' + (j.error || 'unknown')); return; }
              const list = j.users.map(u => `${u.id}: ${u.first_name} ${u.last_name} (${u.role})`).join('\n') || 'No employees';
              const cmd = prompt('Employees:\n' + list + '\n\nTo create: new:First Last,email,role. To delete: del:<id>');
              if (!cmd) return;
              if (cmd.startsWith('del:')) {
                const id = cmd.split(':')[1];
                const jr = await AppAPI.users.delete(apiUsers, id);
                if (!jr.ok) alert('Error: ' + (jr.error || 'unknown')); else location.reload();
              } else if (cmd.startsWith('new:')) {
                const payload = cmd.substring(4).split(',');
                const name = payload[0] || ''; const email = payload[1] || ''; const role = payload[2] || 'employee';
                const names = name.split(' '); const first = names.shift(); const last = names.join(' ') || '';
                const jr = await AppAPI.users.create(apiUsers, { department_id: null, first_name: first, last_name: last, email, role });
                if (!jr.ok) alert('Error: ' + (jr.error || 'unknown')); else location.reload();
              }
              return;
            }

            if (action === 'assign-head') { alert('Use the Manage Employees flow and assign-head through the future UI.'); return; }
            if (action === 'edit') { alert('Edit company — UI not implemented yet.'); return; }

          } catch (err) { alert('Network error: ' + err.message); }
        });
      });
    });
  })();
})();
