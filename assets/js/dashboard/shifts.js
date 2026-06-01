(() => {
  const q = (sel, root = document) => Array.from((root || document).querySelectorAll(sel));

  function isShiftsPanelActive() {
    const panel = document.querySelector('.settings-panel[data-settings-panel="shifts"]');
    return !!panel && !panel.hidden;
  }

  function getCreateCard() {
    return document.querySelector('[data-shift-create-card]');
  }

  function resetCreateShiftForm() {
    const card = getCreateCard();
    if (!card) return;
    const activeDepartmentId = Number(window.DashboardPlannerData?.active_department_id || 0);
    const deptSelect = card.querySelector('select[data-field="department_id"]');
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
      const input = card.querySelector(selector);
      if (input) input.value = value;
    });
  }

  function collectShiftData() {
    return q('.settings-catalog-grid .settings-catalog-card[data-shift-id]').map(card => {
      const id = card.dataset.shiftId ? parseInt(card.dataset.shiftId, 10) : null;
      const name = card.querySelector('input[data-field="name"]')?.value ?? '';
      const icon = card.querySelector('input[data-field="icon"]')?.value ?? null;
      const color = card.querySelector('input[data-field="color"]')?.value ?? null;
      const start_time = card.querySelector('input[data-field="start_time"]')?.value ?? '';
      const end_time = card.querySelector('input[data-field="end_time"]')?.value ?? '';
      return { id, name, icon, color, start_time, end_time };
    }).filter(s => s.id !== null);
  }

  async function saveShifts() {
    const shifts = collectShiftData();
    if (!shifts.length) return alert('No shifts to save');
    const url = window.DashboardConfig.apiShifts;
    for (const s of shifts) {
      try {
        await AppAPI.shifts.update(url, {
          id: s.id,
          name: s.name,
          icon: s.icon,
          color: s.color,
          start_time: s.start_time,
          end_time: s.end_time
        });
      } catch (e) {
        console.error('Failed saving shift', s.id, e);
      }
    }
    location.reload();
  }

  async function createShift() {
    const card = getCreateCard();
    if (!card) return alert('Shift form not available');
    const departmentId = parseInt(card.querySelector('select[data-field="department_id"]')?.value || '0', 10) || 0;
    const name = card.querySelector('input[data-field="name"]')?.value.trim() || '';
    const icon = card.querySelector('input[data-field="icon"]')?.value.trim() || '🕒';
    const color = card.querySelector('input[data-field="color"]')?.value.trim() || '#2f6fed';
    const start_time = card.querySelector('input[data-field="start_time"]')?.value || '';
    const end_time = card.querySelector('input[data-field="end_time"]')?.value || '';

    if (!departmentId) return alert('Choose a department for the new shift.');
    if (!name) return alert('Enter a shift name.');

    const url = window.DashboardConfig.apiShifts;
    try {
      const res = await AppAPI.shifts.create(url, {
        department_id: departmentId,
        name,
        start_time,
        end_time,
        icon,
        color
      });
      if (res?.ok) location.reload();
      else alert('Failed to create shift: ' + (res?.error || 'unknown'));
    } catch (e) {
      console.error(e);
      alert('Error creating shift');
    }
  }

  function selectShiftCard(card) {
    q('.settings-catalog-grid .settings-catalog-card[data-shift-id]').forEach((item) => {
      item.classList.toggle('is-selected', item === card);
    });
  }

    function getSelectedShiftCard() {
      return document.querySelector('.settings-catalog-grid .settings-catalog-card[data-shift-id].is-selected');
    }

    async function deleteShiftCard(card) {
      if (!card) return false;
      const id = parseInt(card.dataset.shiftId, 10) || 0;
      if (!id) return false;
      if (!confirm('Delete this shift?')) return false;
      try {
        const res = await AppAPI.shifts.delete(window.DashboardConfig.apiShifts, id);
        if (res && res.ok) {
          card.remove();
          return true;
        }
        alert('Failed to delete shift: ' + (res?.error || 'unknown'));
      } catch (e) {
        console.error(e);
        alert('Error deleting shift');
      }
      return false;
    }

  document.addEventListener('click', (ev) => {
    if (!isShiftsPanelActive() && !ev.target.closest('.settings-shift-delete')) return;

    const saveBtn = ev.target.closest && ev.target.closest('.settings-actions .admin-action-link--save');
    if (saveBtn) { ev.preventDefault(); saveShifts(); return; }

      const editBtn = ev.target.closest && ev.target.closest('.settings-actions .admin-action-link');
      if (editBtn && editBtn.textContent.trim().toLowerCase() === 'edit') {
        ev.preventDefault();
        const selected = getSelectedShiftCard();
        const target = selected || q('.settings-catalog-grid .settings-catalog-card[data-shift-id]')[0] || null;
        if (target) {
          selectShiftCard(target);
          target.scrollIntoView({ block: 'center', behavior: 'smooth' });
          target.querySelector('input[data-field="name"]')?.focus();
        }
        return;
      }

    const createBtn = ev.target.closest && ev.target.closest('.settings-shift-create, .settings-actions .admin-action-link');
    if (createBtn && (createBtn.classList.contains('settings-shift-create') || createBtn.textContent.trim().toLowerCase() === 'create')) {
      ev.preventDefault();
      createShift();
      return;
    }

      const deleteBtn = ev.target.closest && ev.target.closest('.settings-actions .admin-action-link');
      if (deleteBtn && deleteBtn.textContent.trim().toLowerCase() === 'delete') {
        ev.preventDefault();
        const selected = getSelectedShiftCard();
        if (!selected) {
          alert('Select a shift card first.');
          return;
        }
        deleteShiftCard(selected);
        return;
      }

    const resetBtn = ev.target.closest && ev.target.closest('.settings-shift-reset');
    if (resetBtn) {
      ev.preventDefault();
      resetCreateShiftForm();
      return;
    }

    const shiftCard = ev.target.closest && ev.target.closest('.settings-catalog-grid .settings-catalog-card[data-shift-id]');
    if (shiftCard && !ev.target.closest('.settings-shift-delete')) {
      selectShiftCard(shiftCard);
    }
  });
  
  document.addEventListener('click', async (ev) => {
    if (!isShiftsPanelActive()) return;
    const del = ev.target.closest && ev.target.closest('.settings-shift-delete');
    if (!del) return;
    ev.preventDefault();
    const card = del.closest('.settings-catalog-card');
    if (card) {
      selectShiftCard(card);
      await deleteShiftCard(card);
    }
  });

  resetCreateShiftForm();
})();
