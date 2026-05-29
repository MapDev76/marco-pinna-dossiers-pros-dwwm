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
    const monthNames = Array.from({ length: 12 }, (_, index) => new Intl.DateTimeFormat('en-US', { month: 'short' }).format(new Date(2024, index, 1)));
    const weekdayNames = Array.from({ length: 7 }, (_, index) => new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(new Date(2024, 0, 1 + index)));
    const fullDateFormatter = new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    const shortDateFormatter = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: '2-digit', year: '2-digit' });
    const monthYearFormatter = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' });
    const monthLabelFormatter = new Intl.DateTimeFormat('en-US', { month: 'long' });

    const rawAssignments = Array.isArray(plannerData.assignments) ? plannerData.assignments : [];
    const departments = Array.isArray(plannerData.departments) ? plannerData.departments : [];
    const calendarToday = toLocalDate(plannerData.today || (calendarShell ? calendarShell.getAttribute('data-calendar-today') : ''));
    const events = rawAssignments.map((item) => ({ ...item }));
    let nextAssignmentId = events.reduce((max, item) => Math.max(max, Number(item.assignment_id || 0)), 0) + 1;

    const state = {
      mode: ['day', 'week', 'fortnight', 'month', 'year'].includes(plannerData.mode) ? plannerData.mode : 'week',
      focusDate: calendarToday,
      selectedDate: calendarToday,
      activeDepartmentId: Number(plannerData.active_department_id || departments[0]?.id || 0),
      activeShiftId: Number(plannerData.active_shift_id || 0),
      calendarExpanded: false,
      draggingUserId: null,
      draggingAssignmentId: null,
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

    const setActiveDepartment = (departmentId) => {
      const department = getDepartmentById(departmentId);
      if (!department) return;
      state.activeDepartmentId = Number(department.id);
      const firstShift = (department.shifts || [])[0] || null;
      state.activeShiftId = firstShift ? Number(firstShift.id) : 0;
      renderSidebarPlanner();
      renderCalendar();
    };

    const setActiveShift = (shiftId) => {
      state.activeShiftId = Number(shiftId);
      renderSidebarPlanner();
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
      const start = state.mode === 'fortnight' ? state.focusDate : startOfWeek(state.focusDate);
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
      const start = startOfWeek(state.focusDate);
      return { start, end: addDays(start, 6) };
    };

    const formatNavigatorRange = () => {
      const range = getVisibleRange();
      return `${shortDateFormatter.format(range.start)} - ${shortDateFormatter.format(range.end)}`;
    };

    const updateChrome = () => {
      if (calendarShell) {
        calendarShell.dataset.calendarView = state.mode;
      }

      document.querySelectorAll('[data-calendar-mode]').forEach((button) => {
        const isActive = button.getAttribute('data-calendar-mode') === state.mode;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      document.querySelectorAll('[data-calendar-navigator-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', navigatorPanel && !navigatorPanel.hidden ? 'true' : 'false');
      });

      if (calendarSection) {
        const modeLabel = calendarSection.querySelector('[data-calendar-mode-label]');
        const rangeLabel = calendarSection.querySelector('[data-calendar-range-label]');
        const subtitle = calendarSection.querySelector('[data-calendar-subtitle]');
        if (modeLabel) modeLabel.textContent = state.mode.charAt(0).toUpperCase() + state.mode.slice(1);
        if (rangeLabel) rangeLabel.textContent = formatRangeLabel();
        if (subtitle) subtitle.textContent = `${events.length} scheduled assignments`;
      }

      const navigatorRange = document.querySelector('[data-calendar-range-display]');
      if (navigatorRange) navigatorRange.value = formatNavigatorRange();
    };

    const renderAssignmentCard = (event, compact = false) => {
      const assignee = event.user_name || 'Unassigned';
      const departmentName = event.department_name || 'Department';
      return `
        <article class="calendar-event${compact ? ' is-compact' : ''}" data-assignment-id="${event.assignment_id || ''}" draggable="true">
          <span class="calendar-event-time">${formatEventTime(event)}</span>
          <span class="calendar-event-title">${event.shift_name || 'Shift'}</span>
          <span class="calendar-event-meta">${departmentName} • ${assignee}${event.status ? ` • ${event.status}` : ''}</span>
        </article>
      `;
    };

    const renderDayCard = (date, options = {}) => {
      const key = dateKey(date);
      const dayEvents = (eventsByDate().get(key) || []).slice().sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')));
      const isCurrentDay = key === dateKey(calendarToday);
      const isSelected = key === dateKey(state.selectedDate);
      const isMuted = options.muted || false;
      const dayLabel = new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date).toUpperCase();
      return `
        <article class="calendar-day-card${isCurrentDay ? ' is-current' : ''}${isSelected ? ' is-selected' : ''}${isMuted ? ' is-muted' : ''}" data-calendar-date="${key}" data-date-key="${key}">
          <header class="calendar-day-head">
            <span class="calendar-day-weekday">${dayLabel}</span>
            <span class="calendar-day-number">${date.getDate()}</span>
          </header>
          <div class="calendar-day-events">
            ${dayEvents.length ? dayEvents.map((event) => renderAssignmentCard(event, true)).join('') : '<div class="calendar-empty">Drop a user here to assign a shift</div>'}
          </div>
        </article>
      `;
    };

    const renderYearCard = (monthIndex) => {
      const monthDate = new Date(state.focusDate.getFullYear(), monthIndex, 1, 12, 0, 0, 0);
      const monthStart = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1, 12, 0, 0, 0);
      const monthEnd = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0, 12, 0, 0, 0);
      const monthEvents = events.filter((event) => {
        const date = toLocalDate(event.work_date);
        return date >= monthStart && date <= monthEnd;
      });
      const topEvents = monthEvents.slice(0, 2);
      return `
        <article class="calendar-year-card${monthIndex === calendarToday.getMonth() ? ' is-current' : ''}" data-calendar-date="${monthDate.getFullYear()}-${pad(monthIndex + 1)}-01">
          <header class="calendar-year-head">
            <span class="calendar-year-label">${monthNames[monthIndex]}</span>
            <span class="calendar-year-number">${monthEvents.length}</span>
          </header>
          <div class="calendar-year-events">
            ${topEvents.length ? topEvents.map((event) => `
              <div class="calendar-year-summary">
                <span class="calendar-year-summary-title">${event.work_date}</span>
                <span class="calendar-year-summary-meta">${event.shift_name || 'Shift'}${event.user_name ? ` • ${event.user_name}` : ''}</span>
              </div>
            `).join('') : '<div class="calendar-empty">No assignments</div>'}
          </div>
        </article>
      `;
    };

    const renderDetail = () => {
      if (!calendarDetail) return;
      const key = dateKey(state.selectedDate);
      const selectedEvents = (eventsByDate().get(key) || []).slice().sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')));
      calendarDetail.innerHTML = `
        <div class="dashboard-calendar-detail-title">
          <span>${fullDateFormatter.format(state.selectedDate)}</span>
          <span>${selectedEvents.length} shifts</span>
        </div>
        <div class="dashboard-calendar-detail-meta">
          <span class="dashboard-calendar-detail-chip">${monthLabelFormatter.format(state.selectedDate)}</span>
          <span class="dashboard-calendar-detail-chip">${dateKey(state.selectedDate)}</span>
        </div>
        <div class="calendar-day-events">
          ${selectedEvents.length ? selectedEvents.map((event) => renderAssignmentCard(event)).join('') : '<div class="dashboard-calendar-detail-empty">No assignments for this date.</div>'}
        </div>
      `;
    };

    const renderCalendar = () => {
      if (!calendarShell) return;
      updateChrome();
      renderDetail();

      if (state.mode === 'year') {
        calendarShell.innerHTML = monthNames.map((_, index) => renderYearCard(index)).join('');
        return;
      }

      const cells = [];
      if (state.mode === 'day') {
        cells.push(renderDayCard(state.selectedDate));
      } else if (state.mode === 'week') {
        const start = startOfWeek(state.focusDate);
        for (let index = 0; index < 7; index += 1) {
          cells.push(renderDayCard(addDays(start, index)));
        }
      } else if (state.mode === 'fortnight') {
        const start = startOfWeek(state.focusDate);
        for (let index = 0; index < 15; index += 1) {
          cells.push(renderDayCard(addDays(start, index)));
        }
      } else {
        const firstDay = startOfWeek(new Date(state.focusDate.getFullYear(), state.focusDate.getMonth(), 1, 12, 0, 0, 0));
        const endDay = addDays(startOfWeek(new Date(state.focusDate.getFullYear(), state.focusDate.getMonth() + 1, 0, 12, 0, 0, 0)), 6);
        for (let cursor = new Date(firstDay); cursor <= endDay; cursor = addDays(cursor, 1)) {
          cells.push(renderDayCard(cursor, { muted: cursor.getMonth() !== state.focusDate.getMonth() }));
        }
      }
      calendarShell.innerHTML = cells.join('');
    };

    const openSidebar = () => {
      document.body.classList.add('sidebar-expanded');
    };

    const closeSidebar = () => {
      document.body.classList.remove('sidebar-expanded');
    };

    const bindHoverSidebar = () => {
      if (!sidebar) return;
      let closeTimer = null;
      const cancelClose = () => {
        if (closeTimer) {
          window.clearTimeout(closeTimer);
          closeTimer = null;
        }
      };
      const scheduleClose = () => {
        cancelClose();
        closeTimer = window.setTimeout(() => {
          if (!sidebar.matches(':hover') && !sidebarHandle.matches(':hover')) {
            closeSidebar();
          }
        }, 180);
      };

      // Open only when hovering the slim handle; hovering the sidebar keeps it open.
      if (sidebarHandle) {
        sidebarHandle.addEventListener('mouseenter', () => {
          cancelClose();
          openSidebar();
        });
        sidebarHandle.addEventListener('mouseleave', scheduleClose);
        sidebarHandle.addEventListener('focusin', () => { cancelClose(); openSidebar(); });
      }

      // Sidebar itself should not open on hover, but should cancel scheduled close while hovered.
      sidebar.addEventListener('mouseenter', cancelClose);
      sidebar.addEventListener('mouseleave', scheduleClose);
    };

    const toggleNavigator = () => {
      if (!navigatorPanel) return;
      navigatorPanel.hidden = !navigatorPanel.hidden;
      updateChrome();
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
        plannerDetail.innerHTML = '<div class="dashboard-sidebar-planner-placeholder">No departments available.</div>';
        return;
      }

      const users = activeDepartment.users || [];
      const shifts = activeDepartment.shifts || [];
      plannerDetail.innerHTML = `
        <div class="dashboard-sidebar-planner-title">
          <span>${activeDepartment.name || 'Department'}</span>
          <span>${users.length} staff</span>
        </div>
        <div class="dashboard-sidebar-planner-description">${activeDepartment.description || 'Assigned team and shift list.'}</div>
        <div>
          <div class="dashboard-sidebar-group-title"><span>👤</span> Employees</div>
          <div class="dashboard-sidebar-chip-group">
            ${users.length ? users.map((user) => `
              <button type="button" class="dashboard-sidebar-user-chip" draggable="true" data-user-id="${user.id}" data-user-name="${(user.first_name || '') + ' ' + (user.last_name || '')}" title="Drag to calendar">
                ${user.first_name || ''} ${user.last_name || ''}
              </button>
            `).join('') : '<div class="dashboard-sidebar-planner-placeholder">No employees in this department.</div>'}
          </div>
        </div>
        <div>
          <div class="dashboard-sidebar-group-title"><span>⏱</span> Shifts</div>
          <div class="dashboard-sidebar-chip-group">
            ${shifts.length ? shifts.map((shift) => `
              <button type="button" class="dashboard-sidebar-shift-chip ${Number(shift.id) === Number(state.activeShiftId) ? 'is-active' : ''}" data-shift-id="${shift.id}">
                ${shift.name || 'Shift'} ${formatShiftTime(shift)}
              </button>
            `).join('') : '<div class="dashboard-sidebar-planner-placeholder">No shifts configured.</div>'}
          </div>
        </div>
      `;

      plannerDetail.querySelectorAll('[data-user-id]').forEach((button) => {
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
        start_time: assignment.start_time || null,
        end_time: assignment.end_time || null,
        department_id: Number(assignment.department_id || 0),
        department_name: assignment.department_name || '',
        user_id: Number(assignment.user_id || 0),
        user_name: assignment.user_name || '',
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
        throw new Error(response?.error || response?.message || 'Unable to save assignment');
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
        throw new Error(response?.error || response?.message || 'Unable to move assignment');
      }
      if (response.assignment) {
        upsertAssignment(response.assignment);
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

    if (sidebar && sidebarHandle) {
      bindHoverSidebar();
      closeSidebar();
    }

    // Bind management toggle buttons (collapsible list of management actions)
    document.querySelectorAll('.management-toggle').forEach((btn) => {
      btn.addEventListener('click', () => {
        const list = btn.nextElementSibling;
        if (!list) return;
        const willOpen = list.hidden === true;
        list.hidden = !willOpen;
        btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        btn.classList.toggle('is-active', willOpen);

        // If this is the departments list, ensure opening unhides all entries
        if (willOpen) {
          const deptButtons = list.querySelectorAll('.dashboard-sidebar-department-button');
          if (deptButtons && deptButtons.length) {
            deptButtons.forEach((b) => { b.hidden = false; });
          }
        }
      });
    });

    // Delegated handler for department buttons (attach to document to survive re-renders)
    (function bindDepartmentDelegation(){
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('.dashboard-sidebar-department-button');
        if (!btn) return;
        const deptList = btn.closest('.dashboard-sidebar-department-list') || btn.closest('.dashboard-management-list');
        if (!deptList) return;
        // run after other handlers to avoid being overridden
        setTimeout(() => {
          const buttons = Array.from(deptList.querySelectorAll('.dashboard-sidebar-department-button'));
          const visible = buttons.filter(b => !b.hidden);
          if (visible.length === 1 && visible[0] === btn) {
            buttons.forEach(b => { b.hidden = false; b.classList.remove('is-active'); });
            return;
          }
          buttons.forEach(b => { if (b === btn) { b.hidden = false; b.classList.add('is-active'); } else { b.hidden = true; b.classList.remove('is-active'); } });
          const deptId = btn.getAttribute('data-planner-department-id');
          if (deptId) setActiveDepartment(deptId);
        }, 0);
      });
    })();

    navigatorToggleButtons.forEach((button) => {
      button.addEventListener('click', () => toggleNavigator());
    });

    document.querySelectorAll('#dashboard-calendar-navigator [data-calendar-mode]').forEach((button) => {
      button.addEventListener('click', () => {
        const mode = button.getAttribute('data-calendar-mode');
        if (!mode) return;
        state.mode = mode;
        if (mode === 'day') {
          state.selectedDate = new Date(calendarToday);
          state.focusDate = new Date(calendarToday);
        }
        renderCalendar();
      });
    });

    document.querySelectorAll('#dashboard-calendar-navigator [data-calendar-nav]').forEach((button) => {
      button.addEventListener('click', () => {
        const action = button.getAttribute('data-calendar-nav');
        if (action === 'prev') {
          if (state.mode === 'year') state.focusDate = new Date(state.focusDate.getFullYear() - 1, 0, 1, 12, 0, 0, 0);
          else if (state.mode === 'month') state.focusDate = new Date(state.focusDate.getFullYear(), state.focusDate.getMonth() - 1, 1, 12, 0, 0, 0);
          else if (state.mode === 'fortnight') state.focusDate = addDays(state.focusDate, -14);
          else if (state.mode === 'week') state.focusDate = addDays(state.focusDate, -7);
          else state.focusDate = addDays(state.focusDate, -1);
        }
        if (action === 'next') {
          if (state.mode === 'year') state.focusDate = new Date(state.focusDate.getFullYear() + 1, 0, 1, 12, 0, 0, 0);
          else if (state.mode === 'month') state.focusDate = new Date(state.focusDate.getFullYear(), state.focusDate.getMonth() + 1, 1, 12, 0, 0, 0);
          else if (state.mode === 'fortnight') state.focusDate = addDays(state.focusDate, 14);
          else if (state.mode === 'week') state.focusDate = addDays(state.focusDate, 7);
          else state.focusDate = addDays(state.focusDate, 1);
        }
        if (action === 'today') {
          // Jump back to today's date
          const today = toLocalDate(calendarShell ? (calendarShell.getAttribute('data-calendar-today')) : plannerData.today);
          state.focusDate = new Date(today);
          state.selectedDate = new Date(today);
          state.calendarExpanded = false;
          document.body.classList.remove('calendar-expanded');
        }
        renderCalendar();
      });
    });

    // Ensure the navigator close button explicitly hides the panel
    const navigatorCloseBtn = document.querySelector('.dashboard-calendar-navigator-close');
    if (navigatorCloseBtn && navigatorPanel) {
      navigatorCloseBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        navigatorPanel.hidden = true;
        updateChrome();
      });
    }

    // Close navigator when clicking the overlay/background or pressing Escape
    if (navigatorPanel) {
      navigatorPanel.addEventListener('click', (e) => {
        if (e.target === navigatorPanel) {
          navigatorPanel.hidden = true;
          updateChrome();
        }
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && navigatorPanel && !navigatorPanel.hidden) {
          navigatorPanel.hidden = true;
          updateChrome();
        }
      });
    }

    plannerDepartmentButtons.forEach((button) => {
      button.addEventListener('click', () => {
        setActiveDepartment(button.getAttribute('data-planner-department-id'));
      });
    });

    // Departments now use the same structure as Management (management-toggle + dashboard-management-list)
    const departmentCurrent = document.querySelector('.dashboard-department-current');

    document.querySelectorAll('.dashboard-sidebar-department-button').forEach((btn) => {
      btn.addEventListener('click', () => {
        const list = btn.closest('.dashboard-sidebar-department-list') || btn.closest('.dashboard-management-list');
        const deptButtons = list ? Array.from(list.querySelectorAll('.dashboard-sidebar-department-button')) : [];

        // If only one visible (the current), clicking it reopens the full list
        const visible = deptButtons.filter((b) => !b.hidden);
        if (visible.length === 1 && visible[0] === btn) {
          deptButtons.forEach((b) => { b.hidden = false; b.classList.remove('is-active'); });
          return;
        }

        // Hide all others, keep clicked visible and mark active
        deptButtons.forEach((b) => {
          if (b === btn) { b.hidden = false; b.classList.add('is-active'); }
          else { b.hidden = true; b.classList.remove('is-active'); }
        });

        // Set active department in state and re-render planner
        const deptId = btn.getAttribute('data-planner-department-id');
        if (deptId) setActiveDepartment(deptId);
      });
    });

    // Initialize current department display if one is active
    (function initCurrentDepartmentDisplay(){
      const active = document.querySelector('.dashboard-sidebar-department-button.is-active');
      if (active && departmentCurrent) {
        departmentCurrent.innerHTML = '';
        const name = active.getAttribute('data-planner-department-name') || active.textContent.trim();
        const btnEl = document.createElement('button');
        btnEl.type = 'button';
        btnEl.className = 'dashboard-department-current-btn dashboard-sidebar-link';
        btnEl.textContent = name;
        btnEl.addEventListener('click', () => {
          // open the list for selecting another
          const list = document.querySelector('.dashboard-management-list');
          if (list) list.hidden = false;
          departmentCurrent.hidden = true;
        });
        departmentCurrent.appendChild(btnEl);
        departmentCurrent.hidden = false;
      }
    })();

    if (calendarShell) {
      calendarShell.addEventListener('click', (event) => {
        const dateCard = event.target.closest('[data-calendar-date]');
        if (dateCard) {
          const dateValue = dateCard.getAttribute('data-calendar-date');
          if (dateValue) openDate(toLocalDate(dateValue));
          return;
        }

        const assignmentCard = event.target.closest('[data-assignment-id]');
        if (assignmentCard) {
          const assignmentId = Number(assignmentCard.getAttribute('data-assignment-id'));
          const assignment = events.find((item) => Number(item.assignment_id) === assignmentId);
          if (assignment?.work_date) {
            openDate(toLocalDate(assignment.work_date));
          }
        }
      });

      calendarShell.addEventListener('dragstart', (event) => {
        const userChip = event.target.closest('[data-user-id]');
        if (userChip) {
          state.draggingUserId = userChip.getAttribute('data-user-id');
          state.draggingAssignmentId = null;
          event.dataTransfer?.setData('text/plain', JSON.stringify({ type: 'user', userId: state.draggingUserId }));
          return;
        }

        const assignmentCard = event.target.closest('[data-assignment-id]');
        if (assignmentCard) {
          state.draggingAssignmentId = assignmentCard.getAttribute('data-assignment-id');
          state.draggingUserId = null;
          event.dataTransfer?.setData('text/plain', JSON.stringify({ type: 'assignment', assignmentId: state.draggingAssignmentId }));
        }
      });

      calendarShell.addEventListener('dragover', (event) => {
        if (event.target.closest('[data-calendar-date]')) {
          event.preventDefault();
        }
      });

      calendarShell.addEventListener('drop', async (event) => {
        const dateCard = event.target.closest('[data-calendar-date]');
        if (!dateCard) return;
        event.preventDefault();
        const workDate = dateCard.getAttribute('data-calendar-date');
        if (!workDate) return;

        try {
          const data = safeParseJson(event.dataTransfer?.getData('application/json') || event.dataTransfer?.getData('text/plain') || '{}', {});
          if (data.type === 'assignment' && data.assignmentId) {
            const assignment = events.find((item) => Number(item.assignment_id) === Number(data.assignmentId));
            if (!assignment) return;
            await moveShift({ assignment_id: assignment.assignment_id, work_date: workDate });
            return;
          }

          const activeShift = getActiveShift();
          if (!activeShift) {
            alert('Select a shift first.');
            return;
          }

          const userId = data.userId || state.draggingUserId;
          if (!userId) return;
          await assignShift({
            user_id: Number(userId),
            shift_id: Number(activeShift.id),
            work_date: workDate,
            status: 'assigned',
          });
        } catch (error) {
          alert(error.message || 'Unable to update assignment.');
        }
      });
    }

    renderSidebarPlanner();
    renderCalendar();
  })();
})();
