(() => {
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
      'input[data-field="icon"]': '🕒',
      'input[data-field="color"]': '#2f6fed',
      'input[data-field="start_time"]': '09:00',
      'input[data-field="end_time"]': '17:00',
    };
    Object.entries(defaults).forEach(([selector, value]) => {
      const input = row.querySelector(selector);
      if (input) input.value = value;
    });
  }

  async function createShift() {
    const row = getCreateRow();
    if (!row) return;
    const departmentId = parseInt(row.querySelector('select[data-field="department_id"]')?.value || '0', 10) || 0;
    const name = row.querySelector('input[data-field="name"]')?.value.trim() || '';
    const icon = row.querySelector('input[data-field="icon"]')?.value.trim() || '🕒';
    const color = row.querySelector('input[data-field="color"]')?.value.trim() || '#2f6fed';
    const start_time = row.querySelector('input[data-field="start_time"]')?.value || '';
    const end_time = row.querySelector('input[data-field="end_time"]')?.value || '';

    if (!departmentId) return alert('Choose a department for the new shift.');
    if (!name) return alert('Enter a shift name.');

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
        location.reload();
      } else {
        alert('Failed to create shift: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      alert('Error creating shift');
    }
  }

  async function saveShift(row) {
    if (!row) return;
    const id = parseInt(row.dataset.shiftId || '0', 10) || 0;
    if (!id) return;
    const payload = {
      id,
      name: row.querySelector('input[data-field="name"]')?.value || '',
      icon: row.querySelector('input[data-field="icon"]')?.value || null,
      color: row.querySelector('input[data-field="color"]')?.value || null,
      start_time: row.querySelector('input[data-field="start_time"]')?.value || '',
      end_time: row.querySelector('input[data-field="end_time"]')?.value || '',
    };
    try {
      const res = await AppAPI.shifts.update(window.DashboardConfig.apiShifts, payload);
      if (res?.ok) {
        location.reload();
      } else {
        alert('Failed to save shift: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      alert('Error saving shift');
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
        row.remove();
      } else {
        alert('Failed to delete shift: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      alert('Error deleting shift');
    }
  }

  document.addEventListener('click', (ev) => {
    if (!isShiftsPanelActive()) return;

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
    }
  });

  resetCreateShiftForm();
})();
