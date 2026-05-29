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
  const apiCompanies = config.apiCompanies;
  const apiDepartments = config.apiDepartments;
  const apiUsers = config.apiUsers;

  /**
   * setupModals
   * Initialize modal behavior for the dashboard: open/close logic, overlay,
   * focus trapping and template-based content injection for the shared CRUD
   * modal. Templates are defined in `app/layout/crud-modal.php` and are
   * injected into the element with id `crud-modal-body`.
   */
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

      const trigger = document.querySelector(`[data-modal-entity="${modalEntity}"]`);
      if (trigger && typeof trigger.click === 'function') {
        window.history.replaceState({}, '', `${window.location.pathname}${window.location.hash}`);
        trigger.click();
      }
    };

    window.setTimeout(openModalFromQuery, 0);
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

  /**
   * setupSidebarAndCalendar()
   * Toggle the dashboard sidebar and render a real date-driven calendar using
   * the shift assignments provided by the backend.
   */
  (function setupSidebarAndCalendar(){
    const sidebarToggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
    const calendarShell = document.querySelector('[data-dashboard-calendar-shell]');
    const calendarSection = calendarShell ? calendarShell.closest('.dashboard-calendar-shell') : null;
    const modeButtons = document.querySelectorAll('[data-calendar-mode]');
    const navButtons = document.querySelectorAll('[data-calendar-nav]');
    const stateKey = 'staffease-dashboard-sidebar-collapsed';

    if (!sidebarToggleButtons.length && !calendarShell) {
      return;
    }

    const safeParseJson = (value, fallback) => {
      try {
        return JSON.parse(value);
      } catch (error) {
        return fallback;
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
    const startOfMonth = (date) => new Date(date.getFullYear(), date.getMonth(), 1, 12, 0, 0, 0);
    const endOfMonth = (date) => new Date(date.getFullYear(), date.getMonth() + 1, 0, 12, 0, 0, 0);
    const startOfYear = (date) => new Date(date.getFullYear(), 0, 1, 12, 0, 0, 0);
    const monthNames = Array.from({ length: 12 }, (_, index) => new Intl.DateTimeFormat('en-US', { month: 'short' }).format(new Date(2024, index, 1)));
    const weekdayNames = Array.from({ length: 7 }, (_, index) => new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(new Date(2024, 0, 1 + index)));
    const fullDateFormatter = new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    const shortDateFormatter = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' });
    const monthYearFormatter = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' });

    const rawEvents = calendarShell ? safeParseJson(calendarShell.getAttribute('data-calendar-events') || '[]', []) : [];
    const events = Array.isArray(rawEvents) ? rawEvents : [];
    const eventsByDate = events.reduce((map, event) => {
      const key = event.work_date || '';
      if (!key) return map;
      if (!map.has(key)) {
        map.set(key, []);
      }
      map.get(key).push(event);
      return map;
    }, new Map());

    const calendarToday = toLocalDate(calendarShell ? calendarShell.getAttribute('data-calendar-today') : '');
    const initialMode = calendarShell ? (calendarShell.getAttribute('data-calendar-mode') || 'week') : 'week';
    const state = {
      mode: ['day', 'week', 'fortnight', 'month', 'year'].includes(initialMode) ? initialMode : 'week',
      focusDate: calendarToday,
    };

    const getModeStep = (mode) => {
      if (mode === 'day') return 1;
      if (mode === 'week') return 7;
      if (mode === 'fortnight') return 14;
      return mode === 'year' ? 12 : 1;
    };

    const formatEventTime = (event) => {
      const start = typeof event.start_time === 'string' ? event.start_time.slice(0, 5) : '--:--';
      const end = typeof event.end_time === 'string' ? event.end_time.slice(0, 5) : '--:--';
      return `${start} - ${end}`;
    };

    const formatRangeLabel = () => {
      if (state.mode === 'year') {
        return String(state.focusDate.getFullYear());
      }

      if (state.mode === 'month') {
        return monthYearFormatter.format(state.focusDate);
      }

      if (state.mode === 'day') {
        return fullDateFormatter.format(state.focusDate);
      }

      const start = startOfWeek(state.focusDate);
      const days = state.mode === 'fortnight' ? 13 : 6;
      const end = addDays(start, days);
      return `${shortDateFormatter.format(start)} - ${shortDateFormatter.format(end)}`;
    };

    const updateControls = () => {
      if (calendarShell) {
        calendarShell.dataset.calendarView = state.mode;
      }

      modeButtons.forEach((button) => {
        const isActive = button.getAttribute('data-calendar-mode') === state.mode;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      if (calendarSection) {
        const modeLabel = calendarSection.querySelector('[data-calendar-mode-label]');
        const rangeLabel = calendarSection.querySelector('[data-calendar-range-label]');
        const subtitle = calendarSection.querySelector('[data-calendar-subtitle]');
        if (modeLabel) modeLabel.textContent = state.mode.charAt(0).toUpperCase() + state.mode.slice(1);
        if (rangeLabel) rangeLabel.textContent = formatRangeLabel();
        if (subtitle) subtitle.textContent = `${events.length} scheduled assignments`;
      }
    };

    const renderEvent = (event) => `
      <article class="calendar-event">
        <span class="calendar-event-time">${formatEventTime(event)}</span>
        <span class="calendar-event-title">${event.shift_name || 'Shift'}</span>
        <span class="calendar-event-meta">${event.department_name || ''}${event.user_name ? ` • ${event.user_name}` : ''}${event.status ? ` • ${event.status}` : ''}</span>
      </article>
    `;

    const renderDayCard = (date, options = {}) => {
      const key = dateKey(date);
      const dayEvents = eventsByDate.get(key) || [];
      const isCurrentDay = key === dateKey(calendarToday);
      const isMuted = options.muted || false;
      const dayLabel = new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date).toUpperCase();
      const numberLabel = String(date.getDate());
      return `
        <article class="calendar-day-card${isCurrentDay ? ' is-current' : ''}${isMuted ? ' is-muted' : ''}">
          <header class="calendar-day-head">
            <span class="calendar-day-weekday">${dayLabel}</span>
            <span class="calendar-day-number">${numberLabel}</span>
          </header>
          <div class="calendar-day-events">
            ${dayEvents.length ? dayEvents.map(renderEvent).join('') : '<div class="calendar-empty">No assignments</div>'}
          </div>
        </article>
      `;
    };

    const renderYearCard = (monthIndex) => {
      const monthDate = new Date(state.focusDate.getFullYear(), monthIndex, 1, 12, 0, 0, 0);
      const monthStart = startOfMonth(monthDate);
      const monthEnd = endOfMonth(monthDate);
      const monthEvents = events.filter((event) => {
        const date = toLocalDate(event.work_date);
        return date >= monthStart && date <= monthEnd;
      });
      const topEvents = monthEvents.slice(0, 3);
      const isCurrentMonth = monthIndex === calendarToday.getMonth() && state.focusDate.getFullYear() === calendarToday.getFullYear();

      return `
        <article class="calendar-year-card${isCurrentMonth ? ' is-current' : ''}">
          <header class="calendar-year-head">
            <span class="calendar-year-label">${monthNames[monthIndex]}</span>
            <span class="calendar-year-number">${monthEvents.length}</span>
          </header>
          <div class="calendar-year-events">
            ${topEvents.length ? topEvents.map((event) => `
              <div class="calendar-year-summary">
                <span class="calendar-year-summary-title">${event.work_date}</span>
                <span class="calendar-year-summary-meta">${event.shift_name || 'Shift'}${event.department_name ? ` • ${event.department_name}` : ''}</span>
              </div>
            `).join('') : '<div class="calendar-empty">No assignments</div>'}
          </div>
        </article>
      `;
    };

    const renderCalendar = () => {
      if (!calendarShell) return;
      updateControls();

      if (state.mode === 'year') {
        calendarShell.innerHTML = monthNames.map((_, index) => renderYearCard(index)).join('');
        return;
      }

      if (state.mode === 'month') {
        const firstDay = startOfWeek(startOfMonth(state.focusDate));
        const lastDay = addDays(startOfWeek(endOfMonth(state.focusDate)), 6);
        const cells = [];
        for (let cursor = new Date(firstDay); cursor <= lastDay; cursor = addDays(cursor, 1)) {
          cells.push(renderDayCard(cursor, { muted: cursor.getMonth() !== state.focusDate.getMonth() }));
        }
        calendarShell.innerHTML = cells.join('');
        return;
      }

      const totalDays = state.mode === 'fortnight' ? 14 : state.mode === 'week' ? 7 : 1;
      const startDate = state.mode === 'day' ? state.focusDate : startOfWeek(state.focusDate);
      const cells = [];
      for (let offset = 0; offset < totalDays; offset += 1) {
        cells.push(renderDayCard(addDays(startDate, offset)));
      }
      calendarShell.innerHTML = cells.join('');
    };

    const moveFocus = (direction) => {
      if (state.mode === 'year') {
        state.focusDate = addYears(state.focusDate, direction);
      } else if (state.mode === 'month') {
        state.focusDate = addMonths(state.focusDate, direction);
      } else {
        state.focusDate = addDays(state.focusDate, direction * getModeStep(state.mode));
      }
      renderCalendar();
    };

    sidebarToggleButtons.forEach((button) => {
      const applySidebarState = () => {
        const collapsed = document.body.classList.contains('sidebar-collapsed');
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      };

      button.addEventListener('click', () => {
        const collapsed = !document.body.classList.contains('sidebar-collapsed');
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        try {
          window.localStorage.setItem(stateKey, collapsed ? '1' : '0');
        } catch (error) {
          // Ignore storage failures.
        }
        applySidebarState();
      });

      applySidebarState();
    });

    try {
      if (window.localStorage.getItem(stateKey) === '1') {
        document.body.classList.add('sidebar-collapsed');
      }
    } catch (error) {
      // Ignore storage failures.
    }

    sidebarToggleButtons.forEach((button) => {
      button.setAttribute('aria-expanded', document.body.classList.contains('sidebar-collapsed') ? 'false' : 'true');
    });

    modeButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const mode = button.getAttribute('data-calendar-mode');
        if (!mode) return;
        state.mode = mode;
        if (mode === 'year') {
          state.focusDate = startOfYear(state.focusDate);
        }
        renderCalendar();
      });
    });

    navButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const action = button.getAttribute('data-calendar-nav');
        if (action === 'today') {
          state.focusDate = new Date(calendarToday);
        } else if (action === 'prev') {
          moveFocus(-1);
          return;
        } else if (action === 'next') {
          moveFocus(1);
          return;
        }
        renderCalendar();
      });
    });

    renderCalendar();
  })();
})();
