(() => {
  const q = (sel, root = document) => Array.from((root || document).querySelectorAll(sel));
  const apiUrl = window.DashboardConfig?.apiUsers;
  if (!apiUrl) return;

  function getCreateCard() { return document.querySelector('[data-user-create-card]'); }
  function resetCreateUserForm() {
    const card = getCreateCard(); if (!card) return;
    ['first_name','last_name','email','role','department_id','password'].forEach((f) => {
      const el = card.querySelector('[data-field="' + f + '"]'); if (!el) return;
      if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = '';
    });
  }

  function collectCreateData() {
    const c = getCreateCard(); if (!c) return null;
    return {
      first_name: c.querySelector('[data-field="first_name"]')?.value.trim() || '',
      last_name: c.querySelector('[data-field="last_name"]')?.value.trim() || '',
      email: c.querySelector('[data-field="email"]')?.value.trim() || '',
      role: c.querySelector('[data-field="role"]')?.value || 'employee',
      department_id: c.querySelector('[data-field="department_id"]')?.value || null,
      password: c.querySelector('[data-field="password"]')?.value || ''
    };
  }

  async function createUser() {
    const data = collectCreateData();
    if (!data) return alert('No form');
    if (!data.first_name || !data.last_name || !data.email) return alert('Fill required fields');
    try {
      const res = await AppAPI.users.create(apiUrl, data);
      if (res?.ok) location.reload(); else alert('Create failed: ' + (res?.error || 'unknown'));
    } catch (e) { console.error(e); alert('Error creating user'); }
  }

  function getUserCard(el) { return el.closest && el.closest('.settings-catalog-card[data-user-id]'); }

  async function saveUser(card) {
    if (!card) return;
    const id = parseInt(card.dataset.userId || '0', 10);
    const payload = {
      id,
      first_name: card.querySelector('[data-field="first_name"]')?.value || '',
      last_name: card.querySelector('[data-field="last_name"]')?.value || '',
      email: card.querySelector('[data-field="email"]')?.value || '',
      role: card.querySelector('[data-field="role"]')?.value || 'employee',
      department_id: card.querySelector('[data-field="department_id"]')?.value || null,
    };
    const pwd = card.querySelector('[data-field="password"]')?.value || '';
    if (pwd) payload.password = pwd;
    try {
      const res = await AppAPI.users.update(apiUrl, payload);
      if (res?.ok) {
        alert('Saved');
        location.reload();
      } else {
        alert('Save failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      alert('Error saving user');
    }
  }

  async function deleteUser(card) {
    if (!card) return;
    const id = parseInt(card.dataset.userId || '0', 10);
    if (!id) return;
    if (!confirm('Delete this user?')) return;
    try {
      const res = await AppAPI.users.delete(apiUrl, id);
      if (res?.ok) { card.remove(); } else alert('Delete failed: ' + (res?.error || 'unknown'));
    } catch (e) {
      console.error(e);
      alert('Error deleting user');
    }
  }

  document.addEventListener('click', (ev) => {
    const createBtn = ev.target.closest && ev.target.closest('.settings-user-create');
    if (createBtn) { ev.preventDefault(); createUser(); return; }
    const resetBtn = ev.target.closest && ev.target.closest('.settings-user-reset');
    if (resetBtn) { ev.preventDefault(); resetCreateUserForm(); return; }
    const saveBtn = ev.target.closest && ev.target.closest('.settings-user-save');
    if (saveBtn) { ev.preventDefault(); const card = getUserCard(saveBtn); saveUser(card); return; }
    const delBtn = ev.target.closest && ev.target.closest('.settings-user-delete');
    if (delBtn) { ev.preventDefault(); const card = getUserCard(delBtn); deleteUser(card); return; }
  });

  resetCreateUserForm();
})();
