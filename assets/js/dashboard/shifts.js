(() => {
  const feedback = window.DashboardFeedback;

  function notifyError(message) {
    if (feedback) {
      feedback.error('Oops!', message);
      return;
    }
    alert(message);
  }

  function notifySuccess(message) {
    if (feedback) {
      feedback.success('Done', message);
      return;
    }
  }

  function getDefaultIcon() {
    const row = getCreateRow();
    const input = row ? row.querySelector('input[data-field="icon"]') : null;
    return (input?.defaultValue || input?.value || '🕒').trim();
  }

  function getDefaultColor() {
    const row = getCreateRow();
    const input = row ? row.querySelector('input[data-field="color"]') : null;
    return (input?.defaultValue || input?.value || '#2f6fed').trim();
  }

  function isShiftsPanelActive() {
    const panel = document.querySelector('.settings-panel[data-settings-panel="shifts"]');
    return !!panel && !panel.hidden;
  }

  function getCreateRow() {
    return document.querySelector('[data-shift-create-row]');
  }

  function getShiftRow(el) {
    return el.closest && el.closest('[data-shift-id]');
  }

  function getShiftDrawer(row) {
    return row ? row.querySelector('.settings-edit-drawer') : null;
  }

  function closeAllDrawers() {
    document.querySelectorAll('[data-shift-id] .settings-edit-drawer').forEach((drawer) => {
      drawer.hidden = true;
    });
  }

  function openDrawer(row) {
    if (!row) return;
    closeAllDrawers();
    const drawer = getShiftDrawer(row);
    if (drawer) drawer.hidden = false;
  }

  function closeDrawer(row) {
    const drawer = getShiftDrawer(row);
    if (drawer) drawer.hidden = true;
  }

  function closeAllPickers(exceptPopover = null) {
    document.querySelectorAll('[data-shift-create-row] [data-picker-popover], [data-shift-id] [data-picker-popover]').forEach((popover) => {
      const shouldKeepOpen = exceptPopover && popover === exceptPopover;
      popover.hidden = !shouldKeepOpen;
      const stack = popover.closest('.settings-picker-stack');
      const toggle = stack ? stack.querySelector('[data-picker-toggle]') : null;
      if (toggle) toggle.setAttribute('aria-expanded', shouldKeepOpen ? 'true' : 'false');
    });
  }

  function syncChoiceState(scope) {
    if (!scope) return;
    scope.querySelectorAll('[data-choice-field]').forEach((group) => {
      const field = group.getAttribute('data-choice-field');
      const input = scope.querySelector(`input[data-field="${field}"]`);
      const currentValue = (input?.value || '').trim();
      group.querySelectorAll('[data-choice-value]').forEach((btn) => {
        const isSelected = btn.getAttribute('data-choice-value') === currentValue;
        btn.classList.toggle('is-selected', isSelected);
        btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });
    });
  }

  function setChoiceValue(scope, field, value) {
    if (!scope || !field) return;
    const input = scope.querySelector(`input[data-field="${field}"]`);
    if (!input) return;
    input.value = value;
    syncChoiceState(scope);
    closeAllPickers();
  }

  function togglePicker(scope, field) {
    if (!scope || !field) return;
    const popover = scope.querySelector(`[data-picker-popover="${field}"]`);
    const toggle = scope.querySelector(`[data-picker-toggle="${field}"]`);
    if (!popover || !toggle) return;
    const willOpen = popover.hidden;
    closeAllPickers(willOpen ? popover : null);
    popover.hidden = !willOpen;
    toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  }

  function resetCreateShiftForm() {
    const row = getCreateRow();
    if (!row) return;
    const activeDepartmentId = Number(window.DashboardPlannerData?.active_department_id || 0);
    const deptSelect = row.querySelector('select[data-field="department_id"]');
    if (deptSelect && activeDepartmentId) {
      deptSelect.value = String(activeDepartmentId);
    }
    const defaults = {
      'input[data-field="name"]': '',
      'input[data-field="icon"]': row.querySelector('input[data-field="icon"]')?.defaultValue || getDefaultIcon(),
      'input[data-field="color"]': row.querySelector('input[data-field="color"]')?.defaultValue || getDefaultColor(),
      'input[data-field="start_time"]': '09:00',
      'input[data-field="end_time"]': '17:00',
    };
    Object.entries(defaults).forEach(([selector, value]) => {
      const input = row.querySelector(selector);
      if (input) input.value = value;
    });
    syncChoiceState(row);
  }

  async function createShift() {
    const row = getCreateRow();
    if (!row) return;
    const departmentId = parseInt(row.querySelector('select[data-field="department_id"]')?.value || '0', 10) || 0;
    const name = row.querySelector('input[data-field="name"]')?.value.trim() || '';
    const icon = row.querySelector('input[data-field="icon"]')?.value.trim() || getDefaultIcon();
    const color = row.querySelector('input[data-field="color"]')?.value.trim() || getDefaultColor();
    const start_time = row.querySelector('input[data-field="start_time"]')?.value || '';
    const end_time = row.querySelector('input[data-field="end_time"]')?.value || '';

    if (!departmentId) return notifyError('Choose a department for the new shift.');
    if (!name) return notifyError('Enter a shift name.');

    try {
      const res = await AppAPI.shifts.create(window.DashboardConfig.apiShifts, {
        department_id: departmentId,
        name,
        start_time,
        end_time,
        icon,
        color,
      });
      if (res?.ok) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('shifts', 'Done', 'Shift created successfully.');
        } else {
          notifySuccess('Shift created successfully.');
          location.reload();
        }
      } else {
        notifyError('Failed to create shift: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      notifyError('Error creating shift.');
    }
  }

  async function saveShift(row) {
    if (!row) return;
    const id = parseInt(row.dataset.shiftId || '0', 10) || 0;
    if (!id) return;
    const payload = {
      id,
      name: row.querySelector('input[data-field="name"]')?.value || '',
      icon: row.querySelector('input[data-field="icon"]')?.value || getDefaultIcon(),
      color: row.querySelector('input[data-field="color"]')?.value || getDefaultColor(),
      start_time: row.querySelector('input[data-field="start_time"]')?.value || '',
      end_time: row.querySelector('input[data-field="end_time"]')?.value || '',
    };
    try {
      const res = await AppAPI.shifts.update(window.DashboardConfig.apiShifts, payload);
      if (res?.ok) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('shifts', 'Done', 'Shift updated successfully.');
        } else {
          notifySuccess('Shift updated successfully.');
          location.reload();
        }
      } else {
        notifyError('Failed to save shift: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      notifyError('Error saving shift.');
    }
  }

  async function deleteShift(row) {
    if (!row) return;
    const id = parseInt(row.dataset.shiftId || '0', 10) || 0;
    if (!id) return;
    if (!confirm('Delete this shift?')) return;
    try {
      const res = await AppAPI.shifts.delete(window.DashboardConfig.apiShifts, id);
      if (res?.ok) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('shifts', 'Done', 'Shift deleted successfully.');
        } else {
          notifySuccess('Shift deleted successfully.');
          row.remove();
        }
      } else {
        notifyError('Failed to delete shift: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      notifyError('Error deleting shift.');
    }
  }

  document.addEventListener('click', (ev) => {
    if (!isShiftsPanelActive()) return;

    const pickerToggle = ev.target.closest && ev.target.closest('[data-picker-toggle]');
    if (pickerToggle) {
      ev.preventDefault();
      const scope = pickerToggle.closest('[data-shift-id], [data-shift-create-row]');
      const field = pickerToggle.getAttribute('data-picker-toggle');
      togglePicker(scope, field);
      return;
    }

    const choiceBtn = ev.target.closest && ev.target.closest('[data-choice-field] [data-choice-value]');
    if (choiceBtn) {
      ev.preventDefault();
      const group = choiceBtn.closest('[data-choice-field]');
      const scope = choiceBtn.closest('[data-shift-id], [data-shift-create-row]');
      const field = group?.getAttribute('data-choice-field');
      const value = choiceBtn.getAttribute('data-choice-value') || '';
      if (scope && field && value) {
        setChoiceValue(scope, field, value);
      }
      return;
    }

    const createBtn = ev.target.closest && ev.target.closest('.settings-shift-create');
    if (createBtn) {
      ev.preventDefault();
      createShift();
      return;
    }

    const resetBtn = ev.target.closest && ev.target.closest('.settings-shift-reset');
    if (resetBtn) {
      ev.preventDefault();
      resetCreateShiftForm();
      return;
    }

    const editBtn = ev.target.closest && ev.target.closest('.settings-shift-edit');
    if (editBtn) {
      ev.preventDefault();
      openDrawer(getShiftRow(editBtn));
      return;
    }

    const cancelBtn = ev.target.closest && ev.target.closest('.settings-shift-cancel');
    if (cancelBtn) {
      ev.preventDefault();
      closeDrawer(getShiftRow(cancelBtn));
      return;
    }

    const saveBtn = ev.target.closest && ev.target.closest('.settings-shift-save');
    if (saveBtn) {
      ev.preventDefault();
      saveShift(getShiftRow(saveBtn));
      return;
    }

    const deleteBtn = ev.target.closest && ev.target.closest('.settings-shift-delete');
    if (deleteBtn) {
      ev.preventDefault();
      deleteShift(getShiftRow(deleteBtn));
      return;
    }

    if (!ev.target.closest('.settings-picker-stack')) {
      closeAllPickers();
    }
  });

  document.querySelectorAll('[data-shift-create-row], [data-shift-id]').forEach((scope) => {
    syncChoiceState(scope);
  });

  resetCreateShiftForm();
})();
