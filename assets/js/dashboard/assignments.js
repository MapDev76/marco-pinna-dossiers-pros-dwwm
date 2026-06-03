(() => {
  const apiUrl = window.DashboardConfig?.apiDashboard;
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

  function isAssignmentsPanelActive() {
    const panel = document.querySelector('.settings-panel[data-settings-panel="assignments"]');
    return !!panel && !panel.hidden;
  }

  function getRow(el) {
    return el.closest && el.closest('[data-assignment-id]');
  }

  function getDrawer(row) {
    return row ? row.querySelector('.settings-edit-drawer') : null;
  }

  function closeAllDrawers() {
    document.querySelectorAll('[data-assignment-id] .settings-edit-drawer').forEach((drawer) => {
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

  async function saveAssignment(row) {
    if (!row || !apiUrl || !window.AppAPI) return;
    const assignmentId = parseInt(row.dataset.assignmentId || '0', 10) || 0;
    if (!assignmentId) return;

    const payload = {
      action: 'move_shift',
      assignment_id: assignmentId,
      shift_id: parseInt(row.querySelector('[data-field="shift_id"]')?.value || '0', 10) || 0,
      user_id: parseInt(row.querySelector('[data-field="user_id"]')?.value || '0', 10) || 0,
      work_date: row.querySelector('[data-field="work_date"]')?.value || '',
      status: row.querySelector('[data-field="status"]')?.value || 'assigned',
    };

    if (!payload.shift_id || !payload.work_date) {
      notifyError('Shift and work date are required.');
      return;
    }

    try {
      const res = await AppAPI.postJSON(apiUrl, payload);
      if (res?.ok || res?.success) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('assignments', 'Done', 'Assignment updated successfully.');
        } else {
          notifySuccess('Assignment updated successfully.');
          location.reload();
        }
      } else {
        notifyError('Save failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      notifyError('Error saving assignment.');
    }
  }

  async function unassignAssignment(row) {
    if (!row || !apiUrl || !window.AppAPI) return;
    const assignmentId = parseInt(row.dataset.assignmentId || '0', 10) || 0;
    if (!assignmentId) return;

    try {
      const res = await AppAPI.postJSON(apiUrl, {
        action: 'unassign_shift',
        assignment_id: assignmentId,
      });
      if (res?.ok || res?.success) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('assignments', 'Done', 'Shift unassigned successfully.');
        } else {
          notifySuccess('Shift unassigned successfully.');
          location.reload();
        }
      } else {
        notifyError('Unassign failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      notifyError('Error unassigning shift.');
    }
  }

  async function autoAssignOpen() {
    if (!apiUrl || !window.AppAPI) return;

    try {
      const res = await AppAPI.postJSON(apiUrl, {
        action: 'auto_assign_open',
        scope_shift_id: parseInt(document.querySelector('[data-auto-assign-shift]')?.value || '0', 10) || 0,
        range_start: document.querySelector('[data-auto-assign-range-start]')?.value || '',
        range_end: document.querySelector('[data-auto-assign-range-end]')?.value || '',
        max_hours_per_month: parseInt(document.querySelector('[data-auto-assign-max-hours]')?.value || '176', 10) || 176,
        max_days_per_month: parseInt(document.querySelector('[data-auto-assign-max-days]')?.value || '22', 10) || 22,
      });
      if (res?.ok || res?.success) {
        const message = `Assigned ${res.assigned_count || 0} shifts. Open remaining: ${res.open_remaining || 0}.`;
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('assignments', 'Done', message);
        } else {
          notifySuccess(message);
          location.reload();
        }
      } else {
        notifyError('Auto assignment failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      notifyError('Error running auto assignment.');
    }
  }

  document.addEventListener('click', (ev) => {
    if (!isAssignmentsPanelActive()) return;

    const editBtn = ev.target.closest && ev.target.closest('.settings-assignment-edit');
    if (editBtn) {
      ev.preventDefault();
      openDrawer(getRow(editBtn));
      return;
    }

    const cancelBtn = ev.target.closest && ev.target.closest('.settings-assignment-cancel');
    if (cancelBtn) {
      ev.preventDefault();
      closeDrawer(getRow(cancelBtn));
      return;
    }

    const saveBtn = ev.target.closest && ev.target.closest('.settings-assignment-save');
    if (saveBtn) {
      ev.preventDefault();
      saveAssignment(getRow(saveBtn));
      return;
    }

    const unassignBtn = ev.target.closest && ev.target.closest('.settings-assignment-unassign');
    if (unassignBtn) {
      ev.preventDefault();
      unassignAssignment(getRow(unassignBtn));
      return;
    }

    const autoAssignBtn = ev.target.closest && ev.target.closest('[data-auto-assign-open]');
    if (autoAssignBtn) {
      ev.preventDefault();
      autoAssignOpen();
    }
  });
})();
