(() => {
  const apiUrl = window.DashboardConfig?.apiUsers;
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

  function getCreateCard() { return document.querySelector('[data-user-create-row]'); }
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
    const data = collectCreateData(); if (!data) return notifyError('Form not found.');
    if (!data.first_name || !data.last_name || !data.email) return notifyError('Fill required fields.');
    try {
      const res = await AppAPI.users.create(apiUrl, data);
      if (res?.ok) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('users', 'Done', 'User created successfully.');
        } else {
          notifySuccess('User created successfully.');
          location.reload();
        }
      } else {
        notifyError('Create failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) { console.error(e); notifyError('Error creating user.'); }
  }

  function getUserCard(el) { return el.closest && el.closest('.settings-list-item-wrap[data-user-id]'); }

  function getUserDrawer(card) {
    return card ? card.querySelector('.settings-edit-drawer') : null;
  }

  function closeAllDrawers() {
    document.querySelectorAll('[data-user-id] .settings-edit-drawer').forEach((drawer) => {
      drawer.hidden = true;
    });
  }

  function openDrawer(card) {
    if (!card) return;
    closeAllDrawers();
    const drawer = getUserDrawer(card);
    if (drawer) drawer.hidden = false;
  }

  function closeDrawer(card) {
    const drawer = getUserDrawer(card);
    if (drawer) drawer.hidden = true;
  }

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
      status: card.querySelector('[data-field="status"]')?.value || 'active',
    };
    const pwd = card.querySelector('[data-field="password"]')?.value || '';
    if (pwd) payload.password = pwd;
    try {
      const res = await AppAPI.users.update(apiUrl, payload);
      if (res?.ok) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('users', 'Done', 'User updated successfully.');
        } else {
          notifySuccess('User updated successfully.');
          location.reload();
        }
      } else {
        notifyError('Save failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) { console.error(e); notifyError('Error saving user.'); }
  }

  async function deleteUser(card) {
    if (!card) return;
    const id = parseInt(card.dataset.userId || '0', 10); if (!id) return;
    const canDelete = feedback?.confirm ? await feedback.confirm('Delete this user?','Confirm deletion') : confirm('Delete this user?');
    if (!canDelete) return;
    try {
      const res = await AppAPI.users.delete(apiUrl, id);
      if (res?.ok) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('users', 'Done', 'User deleted successfully.');
        } else {
          notifySuccess('User deleted successfully.');
          card.remove();
        }
      } else {
        notifyError('Delete failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) { console.error(e); notifyError('Error deleting user.'); }
  }

  document.addEventListener('click', (ev) => {
    const createBtn = ev.target.closest && ev.target.closest('.settings-user-create');
    if (createBtn) { ev.preventDefault(); createUser(); return; }
    const resetBtn = ev.target.closest && ev.target.closest('.settings-user-reset');
    if (resetBtn) { ev.preventDefault(); resetCreateUserForm(); return; }
    const editBtn = ev.target.closest && ev.target.closest('.settings-user-edit');
    if (editBtn) { ev.preventDefault(); const card = getUserCard(editBtn); openDrawer(card); return; }
    const cancelBtn = ev.target.closest && ev.target.closest('.settings-user-cancel');
    if (cancelBtn) { ev.preventDefault(); const card = getUserCard(cancelBtn); closeDrawer(card); return; }
    const saveBtn = ev.target.closest && ev.target.closest('.settings-user-save');
    if (saveBtn) { ev.preventDefault(); const card = getUserCard(saveBtn); saveUser(card); return; }
    const delBtn = ev.target.closest && ev.target.closest('.settings-user-delete');
    if (delBtn) { ev.preventDefault(); const card = getUserCard(delBtn); deleteUser(card); return; }
  });

  resetCreateUserForm();
})();
