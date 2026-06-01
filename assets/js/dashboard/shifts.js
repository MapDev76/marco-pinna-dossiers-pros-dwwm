(() => {
  const q = (sel, root = document) => Array.from((root || document).querySelectorAll(sel));

  function collectShiftData() {
    return q('.settings-catalog-grid .settings-catalog-card').map(card => {
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
    const name = prompt('Shift name');
    if (!name) return;
    const start_time = prompt('Start time (HH:MM)', '09:00');
    const end_time = prompt('End time (HH:MM)', '17:00');
    const deptId = window.DashboardPlannerData?.active_department_id ?? 0;
    if (!deptId) return alert('No active department to attach the shift to.');
    const url = window.DashboardConfig.apiShifts;
    try {
      const res = await AppAPI.shifts.create(url, {
        department_id: deptId,
        name,
        start_time,
        end_time,
        icon: '🕒',
        color: '#2f6fed'
      });
      if (res?.ok) location.reload();
      else alert('Failed to create shift: ' + (res?.error || 'unknown'));
    } catch (e) {
      console.error(e);
      alert('Error creating shift');
    }
  }

  document.addEventListener('click', (ev) => {
    const saveBtn = ev.target.closest && ev.target.closest('.settings-actions .admin-action-link--save');
    if (saveBtn) { ev.preventDefault(); saveShifts(); return; }
    const createBtn = ev.target.closest && ev.target.closest('.settings-actions .admin-action-link');
    if (createBtn && createBtn.textContent.trim().toLowerCase() === 'create') { ev.preventDefault(); createShift(); }
  });
})();
