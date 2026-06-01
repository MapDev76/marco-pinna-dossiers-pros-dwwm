(() => {
  const apiUrl = window.DashboardConfig?.apiDepartments;
  if (!apiUrl || !window.AppAPI) return;

  function getCreateCard() {
    return document.querySelector('[data-dept-create-row]');
  }

  function getDepartmentCard(el) {
    return el.closest && el.closest('[data-department-id]');
  }

  function getDepartmentDrawer(card) {
    return card ? card.querySelector('.settings-edit-drawer') : null;
  }

  function closeAllDepartmentDrawers() {
    document.querySelectorAll('[data-department-id] .settings-edit-drawer').forEach((drawer) => {
      drawer.hidden = true;
    });
  }

  function openDepartmentDrawer(card) {
    if (!card) return;
    closeAllDepartmentDrawers();
    const drawer = getDepartmentDrawer(card);
    if (drawer) drawer.hidden = false;
  }

  function closeDepartmentDrawer(card) {
    const drawer = getDepartmentDrawer(card);
    if (drawer) drawer.hidden = true;
  }

  function resetCreateDepartmentForm() {
    const card = getCreateCard();
    if (!card) return;
    const defaults = {
      'input[data-field="name"]': '',
      'input[data-field="icon"]': '🏷️',
      'input[data-field="color"]': '#b98b12',
    };
    Object.entries(defaults).forEach(([selector, value]) => {
      const input = card.querySelector(selector);
      if (input) input.value = value;
    });
  }

  async function createDepartment(card) {
    if (!card) return;
    const name = card.querySelector('input[data-field="name"]')?.value.trim() || '';
    const icon = card.querySelector('input[data-field="icon"]')?.value.trim() || null;
    const color = card.querySelector('input[data-field="color"]')?.value.trim() || null;
    const companyId = parseInt(card.querySelector('select[data-field="company_id"]')?.value || '0', 10) || 0;

    if (!name) return alert('Enter department name.');
    if (!companyId) return alert('Choose a company.');

    try {
      const res = await AppAPI.departments.create(apiUrl, companyId, name);
      if (!res?.ok) {
        alert('Create failed: ' + (res?.error || 'unknown'));
        return;
      }

      // Save icon/color if the API created successfully and returned id.
      const deptId = parseInt(res?.department?.id || '0', 10) || 0;
      if (deptId && (icon || color)) {
        await AppAPI.departments.update(apiUrl, {
          id: deptId,
          company_id: companyId,
          name,
          icon,
          color,
        });
      }

      location.reload();
    } catch (e) {
      console.error(e);
      alert('Error creating department');
    }
  }

  async function saveDepartment(card) {
    if (!card) return;
    const id = parseInt(card.dataset.departmentId || '0', 10) || 0;
    if (!id) return;

    const name = card.querySelector('input[data-field="name"]')?.value.trim() || '';
    const icon = card.querySelector('input[data-field="icon"]')?.value.trim() || null;
    const color = card.querySelector('input[data-field="color"]')?.value.trim() || null;

    if (!name) return alert('Enter department name.');

    try {
      const res = await AppAPI.departments.update(apiUrl, { id, name, icon, color });
      if (res?.ok) {
        location.reload();
      } else {
        alert('Save failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      alert('Error saving department');
    }
  }

  async function deleteDepartment(card) {
    if (!card) return;
    const id = parseInt(card.dataset.departmentId || '0', 10) || 0;
    if (!id) return;
    if (!confirm('Delete this department?')) return;

    try {
      const res = await AppAPI.departments.delete(apiUrl, id);
      if (res?.ok) {
        card.remove();
      } else {
        alert('Delete failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      alert('Error deleting department');
    }
  }

  document.addEventListener('click', (ev) => {
    const createBtn = ev.target.closest && ev.target.closest('.settings-dept-create');
    if (createBtn) {
      ev.preventDefault();
      createDepartment(getCreateCard());
      return;
    }

    const resetBtn = ev.target.closest && ev.target.closest('.settings-dept-reset');
    if (resetBtn) {
      ev.preventDefault();
      resetCreateDepartmentForm();
      return;
    }

    const saveBtn = ev.target.closest && ev.target.closest('.settings-dept-save');
    if (saveBtn) {
      ev.preventDefault();
      saveDepartment(getDepartmentCard(saveBtn));
      return;
    }

    const editBtn = ev.target.closest && ev.target.closest('.settings-dept-edit');
    if (editBtn) {
      ev.preventDefault();
      openDepartmentDrawer(getDepartmentCard(editBtn));
      return;
    }

    const cancelBtn = ev.target.closest && ev.target.closest('.settings-dept-cancel');
    if (cancelBtn) {
      ev.preventDefault();
      closeDepartmentDrawer(getDepartmentCard(cancelBtn));
      return;
    }

    const deleteBtn = ev.target.closest && ev.target.closest('.settings-dept-delete');
    if (deleteBtn) {
      ev.preventDefault();
      deleteDepartment(getDepartmentCard(deleteBtn));
    }
  });

  resetCreateDepartmentForm();
})();
