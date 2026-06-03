(() => {
  const apiUrl = window.DashboardConfig?.apiDashboard;
  const feedback = window.DashboardFeedback;
  const RULES_STORAGE_KEY = 'staffease:auto-assign-rules:v1';

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

  function loadRules() {
    try {
      const raw = localStorage.getItem(RULES_STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (_error) {
      return {};
    }
  }

  function saveRules(rules) {
    try {
      localStorage.setItem(RULES_STORAGE_KEY, JSON.stringify(rules || {}));
    } catch (_error) {
      // Ignore storage failures and keep UI functional.
    }
  }

  function toRuleReasonLabel(reason) {
    switch (String(reason || 'special')) {
      case 'rest':
        return 'Weekly rest';
      case 'leave':
        return 'Leave';
      case 'vacation':
        return 'Vacation';
      case 'sick':
        return 'Sick leave';
      default:
        return 'Special day';
    }
  }

  function getRuleRows() {
    return Array.from(document.querySelectorAll('[data-auto-rule-user-id]'));
  }

  function readRuleFromRow(row) {
    if (!row) return null;
    const userId = String(row.dataset.autoRuleUserId || '').trim();
    if (!userId) return null;

    const scope = row.querySelector('[data-auto-rule-scope]')?.value || 'all';
    const offWeekdays = Array.from(row.querySelectorAll('[data-auto-rule-weekday]:checked'))
      .map((checkbox) => parseInt(checkbox.getAttribute('data-auto-rule-weekday') || '-1', 10))
      .filter((weekday) => weekday >= 0 && weekday <= 6);

    const specialDates = [];
    row.querySelectorAll('[data-auto-rule-special-list] [data-date][data-reason]').forEach((chip) => {
      const date = chip.getAttribute('data-date') || '';
      const reason = chip.getAttribute('data-reason') || 'special';
      if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        specialDates.push({ date, reason });
      }
    });

    return {
      userId,
      rule: {
        scope,
        off_weekdays: Array.from(new Set(offWeekdays)).sort(),
        special_dates: specialDates,
      },
    };
  }

  function renderSpecialDates(row, specialDates) {
    const wrap = row?.querySelector('[data-auto-rule-special-list]');
    if (!wrap) return;
    const list = Array.isArray(specialDates) ? specialDates : [];
    if (!list.length) {
      wrap.innerHTML = '';
      return;
    }

    wrap.innerHTML = list.map((item) => {
      const safeDate = String(item?.date || '');
      const safeReason = String(item?.reason || 'special');
      return `<span class="settings-auto-rule-chip" data-date="${safeDate}" data-reason="${safeReason}">${safeDate} • ${toRuleReasonLabel(safeReason)}<button type="button" data-auto-rule-remove-special="${safeDate}" aria-label="Remove unavailable date">×</button></span>`;
    }).join('');
  }

  function applyRulesToRows(rules) {
    getRuleRows().forEach((row) => {
      const userId = String(row.dataset.autoRuleUserId || '').trim();
      const userRule = (rules && rules[userId]) || null;
      const scope = userRule?.scope || 'all';
      const offWeekdays = new Set(Array.isArray(userRule?.off_weekdays) ? userRule.off_weekdays.map((x) => parseInt(x, 10)) : []);
      const specialDates = Array.isArray(userRule?.special_dates) ? userRule.special_dates : [];

      const scopeSelect = row.querySelector('[data-auto-rule-scope]');
      if (scopeSelect) {
        scopeSelect.value = scope;
      }

      row.querySelectorAll('[data-auto-rule-weekday]').forEach((checkbox) => {
        const weekday = parseInt(checkbox.getAttribute('data-auto-rule-weekday') || '-1', 10);
        checkbox.checked = offWeekdays.has(weekday);
      });

      renderSpecialDates(row, specialDates);
    });
  }

  function collectRulesFromRows() {
    const rules = {};
    getRuleRows().forEach((row) => {
      const payload = readRuleFromRow(row);
      if (payload) {
        rules[payload.userId] = payload.rule;
      }
    });
    return rules;
  }

  function persistRulesFromRows() {
    saveRules(collectRulesFromRows());
  }

  function addSpecialDateToRow(row) {
    if (!row) return;
    const dateInput = row.querySelector('[data-auto-rule-special-date]');
    const reasonInput = row.querySelector('[data-auto-rule-special-reason]');
    const dateValue = (dateInput?.value || '').trim();
    const reasonValue = (reasonInput?.value || 'special').trim() || 'special';

    if (!/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
      notifyError('Choose a valid unavailable date.');
      return;
    }

    const currentRule = readRuleFromRow(row)?.rule || { scope: 'all', off_weekdays: [], special_dates: [] };
    const hasDate = currentRule.special_dates.some((item) => item.date === dateValue);
    if (hasDate) {
      notifyError('This unavailable date already exists for the employee.');
      return;
    }
    currentRule.special_dates.push({ date: dateValue, reason: reasonValue });
    currentRule.special_dates.sort((a, b) => String(a.date).localeCompare(String(b.date)));
    renderSpecialDates(row, currentRule.special_dates);
    if (dateInput) {
      dateInput.value = '';
    }
    persistRulesFromRows();
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
      const employeeRules = collectRulesFromRows();
      const res = await AppAPI.postJSON(apiUrl, {
        action: 'auto_assign_open',
        scope_shift_id: parseInt(document.querySelector('[data-auto-assign-shift]')?.value || '0', 10) || 0,
        range_start: document.querySelector('[data-auto-assign-range-start]')?.value || '',
        range_end: document.querySelector('[data-auto-assign-range-end]')?.value || '',
        max_hours_per_month: parseInt(document.querySelector('[data-auto-assign-max-hours]')?.value || '176', 10) || 176,
        max_days_per_month: parseInt(document.querySelector('[data-auto-assign-max-days]')?.value || '22', 10) || 22,
        employee_rules: employeeRules,
      });
      if (res?.ok || res?.success) {
        const skippedByRules = parseInt(res.skipped_by_rules || '0', 10) || 0;
        const message = `Assigned ${res.assigned_count || 0} shifts. Open remaining: ${res.open_remaining || 0}.` + (skippedByRules > 0 ? ` Skipped by rules: ${skippedByRules}.` : '');
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

    const addRuleDateBtn = ev.target.closest && ev.target.closest('[data-auto-rule-add-special]');
    if (addRuleDateBtn) {
      ev.preventDefault();
      addSpecialDateToRow(addRuleDateBtn.closest('[data-auto-rule-user-id]'));
      return;
    }

    const removeRuleDateBtn = ev.target.closest && ev.target.closest('[data-auto-rule-remove-special]');
    if (removeRuleDateBtn) {
      ev.preventDefault();
      const row = removeRuleDateBtn.closest('[data-auto-rule-user-id]');
      if (!row) return;
      const dateToRemove = removeRuleDateBtn.getAttribute('data-auto-rule-remove-special') || '';
      const currentRule = readRuleFromRow(row)?.rule || { scope: 'all', off_weekdays: [], special_dates: [] };
      currentRule.special_dates = currentRule.special_dates.filter((item) => item.date !== dateToRemove);
      renderSpecialDates(row, currentRule.special_dates);
      persistRulesFromRows();
      return;
    }

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

  document.addEventListener('change', (ev) => {
    if (!isAssignmentsPanelActive()) return;
    const target = ev.target;
    if (
      target.matches('[data-auto-rule-scope]')
      || target.matches('[data-auto-rule-weekday]')
      || target.matches('[data-auto-rule-special-reason]')
    ) {
      persistRulesFromRows();
    }
  });

  applyRulesToRows(loadRules());
})();
