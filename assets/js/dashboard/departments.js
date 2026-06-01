
(() => {
  const q = (sel, root = document) => Array.from((root || document).querySelectorAll(sel));
  const apiUrl = window.DashboardConfig?.apiDepartments;
  if (!apiUrl) return;

  function getCreateCard() { return document.querySelector('[data-department-create-card], [data-dept-create-card]'); }
  function resetCreateForm() {
    const c = getCreateCard(); if (!c) return; ['name','icon','color'].forEach(f => { const el = c.querySelector('[data-field="'+f+'"]'); if (el) el.value = f === 'icon' ? '🏷️' : (f === 'color' ? '#b98b12' : ''); });
  }

  function collectCreate() {
    const c = getCreateCard(); if (!c) return null; return { name: c.querySelector('[data-field="name"]')?.value || '', icon: c.querySelector('[data-field="icon"]')?.value || null, color: c.querySelector('[data-field="color"]')?.value || null };
  }

  async function createDepartment() {
    const data = collectCreate();
    if (!data) return alert('No form');
    if (!data.name) return alert('Enter a name');
    try {
      const companyId = (window.DashboardPlannerData?.company_id) || (window.DashboardPlannerData?.companyId) || 0;
      const res = await AppAPI.departments.create(apiUrl, companyId, data.name);
      if (res?.ok) {
        location.reload();
      } else {
        alert('Create failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      alert('Error');
    }
  }

  function getCard(el) { return el.closest && el.closest('.settings-catalog-card[data-department-id]'); }

  async function saveDepartment(card) {
    if (!card) return;
    const id = parseInt(card.dataset.departmentId || '0', 10);
    if (!id) return;
    const payload = {
      id,
      name: card.querySelector('[data-field="name"]')?.value || '',
      icon: card.querySelector('[data-field="icon"]')?.value || null,
      color: card.querySelector('[data-field="color"]')?.value || null
    };
    try {
      const res = await AppAPI.departments.update(apiUrl, payload);
      if (res?.ok) {
        alert('Saved');
        location.reload();
      } else {
        alert('Save failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      alert('Error saving');
    }
  }

  async function deleteDepartment(card) {
    if (!card) return;
    const id = parseInt(card.dataset.departmentId || '0', 10);
    if (!id) return;
    if (!confirm('Delete this department?')) return;
    try {
      const res = await AppAPI.departments.delete(apiUrl, id);
      if (res?.ok) card.remove();
      else alert('Delete failed: ' + (res?.error || 'unknown'));
    } catch (e) {
      console.error(e);
      alert('Error deleting');
    }
  }

  document.addEventListener('click', (ev) => {
    const createBtn = ev.target.closest && ev.target.closest('.settings-department-create');
    if (createBtn) { ev.preventDefault(); createDepartment(); return; }

    const resetBtn = ev.target.closest && ev.target.closest('.settings-department-reset');
    if (resetBtn) { ev.preventDefault(); resetCreateForm(); return; }

    const saveBtn = ev.target.closest && ev.target.closest('.settings-department-save');
    if (saveBtn) { ev.preventDefault(); const card = getCard(saveBtn); saveDepartment(card); return; }

    const delBtn = ev.target.closest && ev.target.closest('.settings-department-delete');
    if (delBtn) { ev.preventDefault(); const card = getCard(delBtn); deleteDepartment(card); return; }
  });

  resetCreateForm();
})();
