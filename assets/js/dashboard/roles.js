(() => {
  const apiUrl = window.DashboardConfig?.apiUsers;

  function isRolesPanelActive() {
    const panel = document.querySelector('.settings-panel[data-settings-panel="roles"]');
    return !!panel && !panel.hidden;
  }

  function getRow(el) {
    return el.closest && el.closest('[data-role-user-id]');
  }

  function getDrawer(row) {
    return row ? row.querySelector('.settings-edit-drawer') : null;
  }

  function closeAllDrawers() {
    document.querySelectorAll('[data-role-user-id] .settings-edit-drawer').forEach((drawer) => {
      drawer.hidden = true;
    });
  }

  function openDrawer(row) {
    if (!row) return;
    closeAllDrawers();
    const drawer = getDrawer(row);
    if (drawer) drawer.hidden = false;
  }

  function closeDrawer(row) {
    const drawer = getDrawer(row);
    if (drawer) drawer.hidden = true;
  }

  async function saveRole(row) {
    if (!row || !apiUrl || !window.AppAPI) return;
    const id = parseInt(row.dataset.roleUserId || '0', 10) || 0;
    if (!id) return;

    const payload = {
      id,
      first_name: row.dataset.firstName || '',
      last_name: row.dataset.lastName || '',
      email: row.dataset.email || '',
      role: row.querySelector('[data-field="role"]')?.value || 'employee',
      department_id: row.querySelector('[data-field="department_id"]')?.value || null,
      status: row.querySelector('[data-field="status"]')?.value || row.dataset.status || 'active',
    };

    try {
      const res = await AppAPI.users.update(apiUrl, payload);
      if (res?.ok) {
        location.reload();
      } else {
        alert('Save failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      alert('Error saving role');
    }
  }

  document.addEventListener('click', (ev) => {
    if (!isRolesPanelActive()) return;

    const editBtn = ev.target.closest && ev.target.closest('.settings-role-edit');
    if (editBtn) {
      ev.preventDefault();
      openDrawer(getRow(editBtn));
      return;
    }

    const cancelBtn = ev.target.closest && ev.target.closest('.settings-role-cancel');
    if (cancelBtn) {
      ev.preventDefault();
      closeDrawer(getRow(cancelBtn));
      return;
    }

    const saveBtn = ev.target.closest && ev.target.closest('.settings-role-save');
    if (saveBtn) {
      ev.preventDefault();
      saveRole(getRow(saveBtn));
    }
  });
})();
