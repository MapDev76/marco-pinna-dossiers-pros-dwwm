(() => {
  const apiUrl = window.DashboardConfig?.apiDashboard;
  const locale = String(document.documentElement.getAttribute('lang') || 'en').toLowerCase();
  const isFr = locale.startsWith('fr');
  const tr = (enText, frText) => (isFr ? frText : enText);
  const feedback = window.DashboardFeedback;
  const RULES_STORAGE_KEY = 'staffease:auto-assign-rules:v1';
  const WEEKDAY_FORMATTER = new Intl.DateTimeFormat(isFr ? 'fr-FR' : 'en-US', { weekday: 'short' });
  const WEEKDAY_OPTIONS = [1, 2, 3, 4, 5, 6, 0].map((value) => {
    const baseDate = new Date(2024, 0, 1 + value, 12, 0, 0, 0);
    const label = WEEKDAY_FORMATTER.format(baseDate).replace('.', '');
    return { value, label: label.charAt(0).toUpperCase() + label.slice(1) };
  });
  const employeeModal = document.querySelector('[data-assignment-employee-modal]');
  const employeeModalTitle = employeeModal?.querySelector('[data-assignment-modal-title]') || null;
  const employeeModalSubtitle = employeeModal?.querySelector('[data-assignment-modal-subtitle]') || null;
  const employeeModalWeekdays = employeeModal?.querySelector('[data-assignment-modal-weekdays]') || null;
  const employeeModalSpecialDate = employeeModal?.querySelector('[data-assignment-modal-special-date]') || null;
  const employeeModalSpecialFrom = employeeModal?.querySelector('[data-assignment-modal-special-from]') || null;
  const employeeModalSpecialTo = employeeModal?.querySelector('[data-assignment-modal-special-to]') || null;
  const employeeModalSpecialReason = employeeModal?.querySelector('[data-assignment-modal-special-reason]') || null;
  const employeeModalSpecialList = employeeModal?.querySelector('[data-assignment-modal-special-list]') || null;
  const employeeModalMonthUnavailable = employeeModal?.querySelector('[data-assignment-modal-month-unavailable]') || null;
  const employeeModalShifts = employeeModal?.querySelector('[data-assignment-modal-shifts]') || null;
  const employeeModalWeekly = employeeModal?.querySelector('[data-assignment-modal-weekly]') || null;
  const employeeModalOpenFrom = employeeModal?.querySelector('[data-assignment-modal-open-from]') || null;
  const employeeModalOpenTo = employeeModal?.querySelector('[data-assignment-modal-open-to]') || null;
  const employeeModalOpenShift = employeeModal?.querySelector('[data-assignment-modal-open-shift]') || null;
  const employeeModalOpenList = employeeModal?.querySelector('[data-assignment-modal-open-list]') || null;
  const employeeModalOpenClear = employeeModal?.querySelector('[data-assignment-modal-open-clear]') || null;
  const employeeModalOpenReselect = employeeModal?.querySelector('[data-assignment-modal-open-reselect]') || null;
  const employeeModalOpenAssign = employeeModal?.querySelector('[data-assignment-modal-open-assign]') || null;
  const employeeModalAbsenceFrom = employeeModal?.querySelector('[data-assignment-modal-absence-from]') || null;
  const employeeModalAbsenceTo = employeeModal?.querySelector('[data-assignment-modal-absence-to]') || null;
  const employeeModalAbsenceType = employeeModal?.querySelector('[data-assignment-modal-absence-type]') || null;
  const shiftCatalogNode = document.querySelector('[data-assignment-shift-catalog]');
  const shiftCatalog = (() => {
    if (!shiftCatalogNode) return [];
    try {
      const parsed = JSON.parse(shiftCatalogNode.textContent || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch (_error) {
      return [];
    }
  })();
  let activeEmployeeUserId = 0;
  let activeEmployeeUserName = '';
  let activeEmployeeDepartmentId = 0;
  let activeEmployeeDepartmentName = '';
  let selectedOpenSlotKeys = new Set();

  function notifyError(message) {
    if (feedback) {
      feedback.error(tr('Oops!', 'Erreur'), message);
      return;
    }
    console.error(message);
  }

  function notifySuccess(message) {
    if (feedback) {
      feedback.success(tr('Done', 'Termine'), message);
      return;
    }
  }

  function isAssignmentsPanelActive() {
    const panel = document.querySelector('.settings-panel[data-settings-panel="assignments"]');
    return !!panel && !panel.hidden;
  }

  function getAssignmentsListWrap() {
    return document.querySelector('[data-assignment-list-wrap]');
  }

  function getAssignmentsListToggle() {
    return document.querySelector('[data-assignment-list-toggle]');
  }

  function setAssignmentsListVisible(visible) {
    const wrap = getAssignmentsListWrap();
    const toggle = getAssignmentsListToggle();
    if (!wrap || !toggle) return;
    wrap.hidden = !visible;
    toggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
    toggle.textContent = visible ? 'Hide daily assignments list' : 'Show daily assignments list';
  }

  function todayKey() {
    return new Date().toISOString().slice(0, 10);
  }

  function currentMonthStartKey() {
    const today = todayKey();
    return `${today.slice(0, 7)}-01`;
  }

  function currentMonthEndKey() {
    const today = new Date();
    const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    return dateKeyFromDate(end);
  }

  function isPastDateKey(dateKey) {
    return !!dateKey && dateKey < todayKey();
  }

  function normalizeCurrentMonthRange(start, end) {
    const minDate = currentMonthStartKey();
    const safeStart = start && start >= minDate ? start : minDate;
    const safeEnd = end && end >= safeStart ? end : safeStart;
    return { start: safeStart, end: safeEnd };
  }

  function normalizeDateRange(fromDate, toDate) {
    const from = normalizeDateKey(fromDate);
    const to = normalizeDateKey(toDate);
    if (!from || !to) return null;
    return from <= to ? { start: from, end: to } : { start: to, end: from };
  }

  function getDateKeysInRange(startKey, endKey) {
    const range = normalizeDateRange(startKey, endKey);
    if (!range) return [];
    const list = [];
    const cursor = new Date(`${range.start}T12:00:00`);
    const end = new Date(`${range.end}T12:00:00`);
    while (cursor <= end) {
      list.push(dateKeyFromDate(cursor));
      cursor.setDate(cursor.getDate() + 1);
    }
    return list;
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

  function getRuleForUser(userId) {
    const normalizedId = String(userId || '').trim();
    if (!normalizedId) {
      return { scope: 'all', off_weekdays: [], special_dates: [] };
    }

    const rules = loadRules();
    if (rules[normalizedId]) {
      return {
        scope: String(rules[normalizedId].scope || 'all'),
        off_weekdays: Array.isArray(rules[normalizedId].off_weekdays) ? rules[normalizedId].off_weekdays.map((x) => parseInt(x, 10)).filter((x) => x >= 0 && x <= 6) : [],
        special_dates: Array.isArray(rules[normalizedId].special_dates) ? rules[normalizedId].special_dates : [],
      };
    }

    const row = getRuleRows().find((item) => String(item.dataset.autoRuleUserId || '') === normalizedId);
    return readRuleFromRow(row)?.rule || { scope: 'all', off_weekdays: [], special_dates: [] };
  }

  function setRuleForUser(userId, nextRule) {
    const normalizedId = String(userId || '').trim();
    if (!normalizedId) return;
    const rules = loadRules();
    rules[normalizedId] = {
      scope: String(nextRule?.scope || 'all'),
      off_weekdays: Array.isArray(nextRule?.off_weekdays) ? Array.from(new Set(nextRule.off_weekdays.map((x) => parseInt(x, 10)).filter((x) => x >= 0 && x <= 6))).sort() : [],
      special_dates: Array.isArray(nextRule?.special_dates) ? nextRule.special_dates.filter((x) => /^\d{4}-\d{2}-\d{2}$/.test(String(x?.date || ''))).map((x) => ({ date: String(x.date), reason: String(x.reason || 'special') })) : [],
    };
    saveRules(rules);
    applyRulesToRows(rules);
  }

  function getUnavailableDatesInCurrentMonth(rule) {
    const monthStart = currentMonthStartKey();
    const monthEnd = currentMonthEndKey();
    const unavailable = new Map();
    const offWeekdays = new Set(Array.isArray(rule?.off_weekdays) ? rule.off_weekdays.map((value) => parseInt(value, 10)) : []);
    getDateKeysInRange(monthStart, monthEnd).forEach((dateKey) => {
      const weekday = new Date(`${dateKey}T12:00:00`).getDay();
      if (offWeekdays.has(weekday)) {
        unavailable.set(dateKey, 'rest');
      }
    });
    (Array.isArray(rule?.special_dates) ? rule.special_dates : []).forEach((item) => {
      const dateValue = normalizeDateKey(item?.date || '');
      if (!dateValue || dateValue < monthStart || dateValue > monthEnd) return;
      unavailable.set(dateValue, String(item?.reason || 'special'));
    });
    return Array.from(unavailable.entries())
      .sort((a, b) => a[0].localeCompare(b[0]))
      .map(([date, reason]) => ({ date, reason }));
  }

  function renderCurrentMonthUnavailable(rule) {
    if (!employeeModalMonthUnavailable) return;
    const dates = getUnavailableDatesInCurrentMonth(rule);
    if (!dates.length) {
      employeeModalMonthUnavailable.innerHTML = '<span class="crud-modal-subtitle">No unavailable dates in current month.</span>';
      return;
    }
    employeeModalMonthUnavailable.innerHTML = dates
      .map((item) => `<span class="settings-auto-rule-chip">${item.date} • ${toRuleReasonLabel(item.reason)}</span>`)
      .join('');
  }

  function syncRuleRangeDefaults() {
    const monthStart = currentMonthStartKey();
    const monthEnd = currentMonthEndKey();
    if (employeeModalSpecialDate && !normalizeDateKey(employeeModalSpecialDate.value)) {
      employeeModalSpecialDate.value = monthStart;
    }
    if (employeeModalSpecialFrom && !normalizeDateKey(employeeModalSpecialFrom.value)) {
      employeeModalSpecialFrom.value = monthStart;
    }
    if (employeeModalSpecialTo && !normalizeDateKey(employeeModalSpecialTo.value)) {
      employeeModalSpecialTo.value = monthEnd;
    }
  }

  function syncAbsenceRangeDefaults() {
    const monthStart = currentMonthStartKey();
    const monthEnd = currentMonthEndKey();
    if (employeeModalAbsenceFrom && !normalizeDateKey(employeeModalAbsenceFrom.value)) {
      employeeModalAbsenceFrom.value = monthStart;
    }
    if (employeeModalAbsenceTo && !normalizeDateKey(employeeModalAbsenceTo.value)) {
      employeeModalAbsenceTo.value = monthEnd;
    }
  }

  function populateOpenShiftFilter() {
    if (!employeeModalOpenShift) return;
    const options = shiftCatalog
      .filter((item) => Number(item?.department_id || 0) === Number(activeEmployeeDepartmentId || 0))
      .filter((item) => String(item?.kind || '').toLowerCase() === 'work')
      .sort((a, b) => String(a?.name || '').localeCompare(String(b?.name || '')));
    const currentValue = String(employeeModalOpenShift.value || '0');
    const optionHtml = options
      .map((item) => {
        const id = parseInt(String(item?.id || '0'), 10) || 0;
        const icon = String(item?.icon || '🕒');
        const name = String(item?.name || 'Shift');
        return `<option value="${id}">${icon} ${name}</option>`;
      })
      .join('');
    employeeModalOpenShift.innerHTML = `<option value="0">All department shifts</option>${optionHtml}`;
    employeeModalOpenShift.value = employeeModalOpenShift.querySelector(`option[value="${currentValue}"]`) ? currentValue : '0';
  }

  function getAbsenceShiftIdByType(type) {
    const targetType = String(type || '').toLowerCase();
    const match = shiftCatalog.find((item) => Number(item?.department_id || 0) === Number(activeEmployeeDepartmentId || 0) && String(item?.kind || '').toLowerCase() === targetType);
    return parseInt(String(match?.id || '0'), 10) || 0;
  }

  function addUnavailableRangeForActiveEmployee() {
    if (!activeEmployeeUserId) return;
    const range = normalizeDateRange(employeeModalSpecialFrom?.value || '', employeeModalSpecialTo?.value || '');
    const reason = String(employeeModalSpecialReason?.value || 'special').trim() || 'special';
    if (!range) {
      notifyError('Choose a valid date range.');
      return;
    }
    const monthStart = currentMonthStartKey();
    const monthEnd = currentMonthEndKey();
    const boundedStart = range.start < monthStart ? monthStart : range.start;
    const boundedEnd = range.end > monthEnd ? monthEnd : range.end;
    const boundedRange = normalizeDateRange(boundedStart, boundedEnd);
    if (!boundedRange) {
      notifyError('Range is outside current month.');
      return;
    }
    const currentRule = getRuleForUser(activeEmployeeUserId);
    const existing = new Map((currentRule.special_dates || []).map((item) => [String(item?.date || ''), String(item?.reason || 'special')]));
    getDateKeysInRange(boundedRange.start, boundedRange.end).forEach((dateKey) => existing.set(dateKey, reason));
    currentRule.special_dates = Array.from(existing.entries()).map(([date, itemReason]) => ({ date, reason: itemReason }));
    setRuleForUser(activeEmployeeUserId, currentRule);
    openEmployeeModal(activeEmployeeUserId, activeEmployeeUserName, activeEmployeeDepartmentId, activeEmployeeDepartmentName);
  }

  function resetRulesForActiveEmployee() {
    if (!activeEmployeeUserId) return;
    setRuleForUser(activeEmployeeUserId, { scope: 'all', off_weekdays: [], special_dates: [] });
    openEmployeeModal(activeEmployeeUserId, activeEmployeeUserName, activeEmployeeDepartmentId, activeEmployeeDepartmentName);
  }

  async function assignAbsenceRange() {
    if (!activeEmployeeUserId) return;
    const range = normalizeDateRange(employeeModalAbsenceFrom?.value || '', employeeModalAbsenceTo?.value || '');
    if (!range) {
      notifyError('Choose a valid absence date range.');
      return;
    }
    const absenceType = String(employeeModalAbsenceType?.value || 'vacation').toLowerCase();
    const shiftId = getAbsenceShiftIdByType(absenceType);
    if (!shiftId) {
      notifyError('No shift template found for selected absence type in this department.');
      return;
    }
    const targetDates = getDateKeysInRange(range.start, range.end).filter((dateKey) => !isPastDateKey(dateKey));
    if (!targetDates.length) {
      notifyError('No editable dates found in selected range.');
      return;
    }
    let assignedCount = 0;
    for (const dateKey of targetDates) {
      const success = await assignShiftForEmployee(shiftId, dateKey, '', { silent: true });
      if (success) assignedCount += 1;
    }
    const skippedCount = targetDates.length - assignedCount;
    if (assignedCount > 0) {
      const successMessage = `${assignedCount} absence day(s) assigned.` + (skippedCount > 0 ? ` Skipped: ${skippedCount}.` : '');
      if (feedback?.reloadSettingsTabWithSuccess) {
        feedback.reloadSettingsTabWithSuccess('assignments', 'Done', successMessage);
      } else {
        notifySuccess(successMessage);
        location.reload();
      }
      return;
    }
    notifyError('No absence days were assigned.');
  }

  function dateKeyFromDate(dateObj) {
    const y = String(dateObj.getFullYear());
    const m = String(dateObj.getMonth() + 1).padStart(2, '0');
    const d = String(dateObj.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  function getEmployeeModalField(selector) {
    return employeeModal?.querySelector(selector) || null;
  }

  function normalizeDateKey(value) {
    const normalized = String(value || '').trim();
    return /^\d{4}-\d{2}-\d{2}$/.test(normalized) ? normalized : '';
  }

  function getVisibleCalendarRange() {
    const dates = Array.from(document.querySelectorAll('[data-calendar-date]'))
      .map((el) => normalizeDateKey(el.getAttribute('data-calendar-date')))
      .filter(Boolean)
      .sort();

    if (!dates.length) {
      const today = new Date();
      return { start: dateKeyFromDate(today), end: dateKeyFromDate(today) };
    }

    return { start: dates[0], end: dates[dates.length - 1] };
  }

  function getOpenSlotEntries() {
    const fromValue = normalizeDateKey(getEmployeeModalField('[data-assignment-modal-open-from]')?.value || '');
    const toValue = normalizeDateKey(getEmployeeModalField('[data-assignment-modal-open-to]')?.value || '');
    const visibleRange = getVisibleCalendarRange();
    const todayKey = dateKeyFromDate(new Date());
    const rangeStart = fromValue || visibleRange.start;
    const rangeEnd = toValue || visibleRange.end;
    const selectedDepartmentId = Number(activeEmployeeDepartmentId || 0);
    const selectedShiftId = parseInt(String(getEmployeeModalField('[data-assignment-modal-open-shift]')?.value || '0'), 10) || 0;

    return Array.from(document.querySelectorAll('[data-calendar-date] .calendar-event.is-open[data-is-open-slot="1"]'))
      .map((card) => {
        const workDate = normalizeDateKey(card.getAttribute('data-work-date') || card.closest('[data-calendar-date]')?.getAttribute('data-calendar-date') || '');
        const shiftId = parseInt(card.getAttribute('data-shift-id') || '0', 10) || 0;
        const departmentId = parseInt(card.getAttribute('data-department-id') || '0', 10) || 0;
        const departmentName = String(card.getAttribute('data-department-name') || 'Department');
        const shiftKind = String(card.getAttribute('data-shift-kind') || 'work');
        const shiftName = String(card.querySelector('.calendar-event-title')?.textContent || 'Shift');
        const shiftTime = String(card.querySelector('.calendar-event-time')?.textContent || '');
        const key = `${shiftId}|${workDate}`;
        return { key, workDate, shiftId, departmentId, departmentName, shiftKind, shiftName, shiftTime };
      })
      .filter((slot) => {
        if (!slot.workDate || !slot.shiftId) return false;
        if (selectedDepartmentId <= 0 || slot.departmentId !== selectedDepartmentId) return false;
        if (selectedShiftId > 0 && slot.shiftId !== selectedShiftId) return false;
        if (slot.workDate < todayKey) return false;
        if (rangeStart && slot.workDate < rangeStart) return false;
        if (rangeEnd && slot.workDate > rangeEnd) return false;
        if (typeof isUserAvailableForDate === 'function' && !isUserAvailableForDate(activeEmployeeUserId, slot.workDate)) return false;
        return true;
      })
      .sort((a, b) => a.workDate.localeCompare(b.workDate) || a.shiftTime.localeCompare(b.shiftTime) || a.shiftName.localeCompare(b.shiftName));
  }

  function renderOpenSlotSelection() {
    const openList = getEmployeeModalField('[data-assignment-modal-open-list]');
    if (!openList) return;

    const slots = getOpenSlotEntries();
    if (!slots.length) {
      openList.innerHTML = '<div class="crud-empty-state">' + tr('No open shifts available for this employee in the selected range.', 'Aucun poste ouvert disponible pour cet employe dans la plage selectionnee.') + '</div>';
      return;
    }

    openList.innerHTML = slots.map((slot) => {
      const isSelected = selectedOpenSlotKeys.has(slot.key);
      return `
        <button type="button" class="settings-assignment-open-slot-item ${isSelected ? 'is-selected' : ''}" data-assignment-modal-open-toggle="${slot.key}">
          <strong>${slot.workDate}</strong>
          <span>${slot.shiftName}</span>
          <small>${slot.shiftTime} • ${slot.departmentName}</small>
        </button>
      `;
    }).join('');
  }

  function syncOpenSlotRangeDefaults() {
    const openFrom = getEmployeeModalField('[data-assignment-modal-open-from]');
    const openTo = getEmployeeModalField('[data-assignment-modal-open-to]');
    if (!openFrom || !openTo) return;
    const visibleRange = getVisibleCalendarRange();
    if (!normalizeDateKey(openFrom.value)) openFrom.value = visibleRange.start;
    if (!normalizeDateKey(openTo.value)) openTo.value = visibleRange.end;
  }

  function clearOpenSlotSelection() {
    selectedOpenSlotKeys = new Set();
    renderOpenSlotSelection();
  }

  function reselectionOpenSlots() {
    const available = getOpenSlotEntries();
    selectedOpenSlotKeys = new Set(available.map((slot) => slot.key));
    renderOpenSlotSelection();
  }

  async function assignSelectedOpenSlots() {
    if (!selectedOpenSlotKeys.size) {
      notifyError('Select at least one open shift.');
      return;
    }

    const slots = getOpenSlotEntries().filter((slot) => selectedOpenSlotKeys.has(slot.key));
    if (!slots.length) {
      notifyError(tr('No valid open shifts found in the current range.', 'Aucun poste ouvert valide trouve dans la plage courante.'));
      return;
    }

    try {
      let assignedCount = 0;
      for (const slot of slots) {
        // Re-check availability before each assignment to avoid stale UI selections.
        if (typeof isUserAvailableForDate === 'function' && !isUserAvailableForDate(activeEmployeeUserId, slot.workDate)) {
          continue;
        }
        const success = await assignShiftForEmployee(slot.shiftId, slot.workDate, 'Shift assigned successfully.', { silent: true });
        if (success) {
          assignedCount += 1;
        }
      }
      if (assignedCount > 0) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('assignments', tr('Done', 'Termine'), tr('Selected open shifts assigned successfully.', 'Les postes ouverts selectionnes ont ete assignes avec succes.'));
        } else {
          notifySuccess(tr('Selected open shifts assigned successfully.', 'Les postes ouverts selectionnes ont ete assignes avec succes.'));
          location.reload();
        }
      } else {
        notifyError(tr('No assignable open shifts were selected.', 'Aucun poste ouvert assignable n a ete selectionne.'));
      }
    } catch (error) {
      console.error(error);
      notifyError(tr('Error assigning selected open shifts.', 'Erreur lors de l affectation des postes ouverts selectionnes.'));
    }
  }

  function renderEmployeeWeeklyAvailability(rule) {
    if (!employeeModalWeekly) return;

    const offWeekdays = new Set(Array.isArray(rule?.off_weekdays) ? rule.off_weekdays.map((value) => parseInt(value, 10)) : []);
    const specialDateMap = new Map((Array.isArray(rule?.special_dates) ? rule.special_dates : []).map((item) => [String(item?.date || ''), String(item?.reason || 'special')]));

    const monthStart = currentMonthStartKey();
    const monthEnd = currentMonthEndKey();
    const monthDays = getDateKeysInRange(monthStart, monthEnd);
    const weekBlocks = [];
    let currentWeek = [];
    monthDays.forEach((dateKey) => {
      const dateObj = new Date(`${dateKey}T12:00:00`);
      const weekday = dateObj.getDay();
      const specialReason = specialDateMap.get(dateKey) || '';
      const blocked = !!specialReason || offWeekdays.has(weekday);
      currentWeek.push({
        key: dateKey,
        label: WEEKDAY_OPTIONS.find((item) => item.value === weekday)?.label || '',
        blocked,
        reason: specialReason || (offWeekdays.has(weekday) ? 'rest' : ''),
      });
      if (currentWeek.length === 7) {
        weekBlocks.push(currentWeek);
        currentWeek = [];
      }
    });
    if (currentWeek.length) {
      weekBlocks.push(currentWeek);
    }

    employeeModalWeekly.innerHTML = weekBlocks.map((days, index) => {
      return `
        <article class="settings-assignment-week-card">
          <strong>${tr('Week', 'Semaine')} ${index + 1}</strong>
          <div class="settings-assignment-week-days">
            ${days.map((day) => `
              <button
                type="button"
                class="settings-assignment-week-day ${day.blocked ? 'is-unavailable' : 'is-available'}"
                data-assignment-modal-week-day="${day.key}"
                data-assignment-modal-week-day-state="${day.blocked ? 'unavailable' : 'available'}"
                title="${day.reason ? toRuleReasonLabel(day.reason) : tr('Available', 'Disponible')}"
              >
                <small>${day.label}</small>
                <b>${day.key.slice(8)}</b>
              </button>
            `).join('')}
          </div>
        </article>
      `;
    }).join('');
  }

  async function assignShiftForEmployee(shiftId, workDate, successLabel, options = {}) {
    if (!apiUrl || !window.AppAPI || !activeEmployeeUserId || !shiftId || !workDate) return;

    try {
      const res = await AppAPI.postJSON(apiUrl, {
        action: 'assign_shift',
        user_id: activeEmployeeUserId,
        shift_id: parseInt(String(shiftId), 10) || 0,
        work_date: workDate,
        status: 'assigned',
      });

      if (res?.ok || res?.success) {
        if (options?.silent) {
          return true;
        }
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('assignments', tr('Done', 'Termine'), successLabel || tr('Shift assigned successfully.', 'Poste assigne avec succes.'));
        } else {
          notifySuccess(successLabel || tr('Shift assigned successfully.', 'Poste assigne avec succes.'));
          location.reload();
        }
      } else {
        if (options?.silent) {
          return false;
        }
        notifyError(tr('Assignment failed: ', 'Echec de l affectation : ') + (res?.error || tr('unknown', 'inconnue')));
      }
    } catch (error) {
      if (options?.silent) {
        throw error;
      }
      console.error(error);
      notifyError(tr('Error assigning shift.', 'Erreur lors de l affectation du poste.'));
    }
  }

  function getAssignmentRowsForUser(userId) {
    const normalizedId = String(userId || '').trim();
    if (!normalizedId) return [];
    return Array.from(document.querySelectorAll(`[data-assignment-id][data-assignment-user-id="${normalizedId}"]`));
  }

  function closeEmployeeModal() {
    if (!employeeModal) return;
    employeeModal.hidden = true;
    activeEmployeeUserId = 0;
    activeEmployeeUserName = '';
    activeEmployeeDepartmentId = 0;
    activeEmployeeDepartmentName = '';
  }

  function renderEmployeeWeekdays(rule) {
    if (!employeeModalWeekdays) return;
    const selected = new Set(Array.isArray(rule?.off_weekdays) ? rule.off_weekdays.map((x) => parseInt(x, 10)) : []);
    employeeModalWeekdays.innerHTML = WEEKDAY_OPTIONS.map((option) => {
      const checked = selected.has(option.value) ? 'checked' : '';
      return `<label><input type="checkbox" data-assignment-modal-weekday="${option.value}" ${checked}>${option.label}</label>`;
    }).join('');
  }

  function renderEmployeeSpecialDates(rule) {
    if (!employeeModalSpecialList) return;
    const list = Array.isArray(rule?.special_dates) ? rule.special_dates.slice().sort((a, b) => String(a.date).localeCompare(String(b.date))) : [];
    if (!list.length) {
      employeeModalSpecialList.innerHTML = '<span class="crud-modal-subtitle">' + tr('No unavailable dates defined.', 'Aucune date indisponible definie.') + '</span>';
      return;
    }

    employeeModalSpecialList.innerHTML = list.map((item) => {
      const dateValue = String(item?.date || '');
      const reason = String(item?.reason || 'special');
      return `<span class="settings-auto-rule-chip" data-date="${dateValue}" data-reason="${reason}">${dateValue} • ${toRuleReasonLabel(reason)}<button type="button" data-assignment-modal-remove-special="${dateValue}" aria-label="${tr('Remove unavailable date', 'Supprimer la date indisponible')}">×</button></span>`;
    }).join('');
  }

  function renderEmployeeShiftList(rows) {
    if (!employeeModalShifts) return;
    if (!rows.length) {
      employeeModalShifts.innerHTML = '<div class="crud-empty-state">' + tr('No shifts currently assigned to this employee.', 'Aucun poste actuellement assigne a cet employe.') + '</div>';
      return;
    }

    employeeModalShifts.innerHTML = rows.map((row) => {
      const assignmentId = String(row.dataset.assignmentId || '0');
      const workDate = String(row.dataset.assignmentWorkDate || '--');
      const shiftName = String(row.dataset.assignmentShiftName || 'Shift');
      const shiftIcon = String(row.dataset.assignmentShiftIcon || '🕒');
      const shiftKind = String(row.dataset.assignmentShiftKind || 'work');
      const departmentName = String(row.dataset.assignmentDepartmentName || '--');
      const status = String(row.dataset.assignmentStatus || 'assigned');
      const start = String(row.dataset.assignmentStartTime || '--:--');
      const end = String(row.dataset.assignmentEndTime || '--:--');
      return `
        <article class="settings-assignment-modal-shift-item">
          <div class="settings-assignment-modal-shift-item-head">
            <strong>${workDate}</strong>
            <span>${status}</span>
          </div>
          <div class="settings-assignment-modal-shift-item-meta">${shiftIcon} ${shiftName} • ${start || '--:--'} - ${end || '--:--'} • ${shiftKind} • ${departmentName}</div>
          <div class="settings-assignment-modal-shift-item-actions">
            <button type="button" class="admin-action-link admin-action-link-secondary" data-assignment-modal-edit="${assignmentId}">Modify</button>
            <button type="button" class="admin-action-link admin-action-link-secondary" data-assignment-modal-unassign="${assignmentId}">Unassign</button>
          </div>
        </article>
      `;
    }).join('');
  }

  function openEmployeeModal(userId, userName, departmentId = 0, departmentName = '') {
    if (!employeeModal) return;
    const normalizedId = parseInt(String(userId || '0'), 10) || 0;
    if (!normalizedId) return;

    activeEmployeeUserId = normalizedId;
    activeEmployeeUserName = String(userName || '').trim() || `Employee #${normalizedId}`;
    activeEmployeeDepartmentId = parseInt(String(departmentId || '0'), 10) || 0;
    activeEmployeeDepartmentName = String(departmentName || '').trim();

    const rule = getRuleForUser(normalizedId);
    const rows = getAssignmentRowsForUser(normalizedId).filter((row) => String(row.dataset.assignmentStatus || '') !== 'cancelled');
    const counts = {
      assigned: rows.length,
      sick: rows.filter((row) => String(row.dataset.assignmentShiftKind || '').toLowerCase() === 'sick').length,
      vacation: rows.filter((row) => String(row.dataset.assignmentShiftKind || '').toLowerCase() === 'vacation').length,
      rest: rows.filter((row) => String(row.dataset.assignmentShiftKind || '').toLowerCase() === 'rest').length,
    };

    if (employeeModalTitle) {
      employeeModalTitle.textContent = activeEmployeeUserName;
    }
    if (employeeModalSubtitle) {
      employeeModalSubtitle.textContent = `Assigned: ${counts.assigned} • Sick: ${counts.sick} • Vacation: ${counts.vacation} • Rest: ${counts.rest}`;
    }

    renderEmployeeWeekdays(rule);
    renderEmployeeSpecialDates(rule);
    renderEmployeeWeeklyAvailability(rule);
    renderCurrentMonthUnavailable(rule);
    renderEmployeeShiftList(rows);
    syncRuleRangeDefaults();
    syncAbsenceRangeDefaults();
    populateOpenShiftFilter();
    syncOpenSlotRangeDefaults();
    clearOpenSlotSelection();
    renderOpenSlotSelection();
    employeeModal.hidden = false;
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
    if (isPastDateKey(payload.work_date)) {
      notifyError('Past dates are read-only and cannot be edited.');
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
    const rowDate = String(row.dataset.assignmentWorkDate || '').trim();
    if (isPastDateKey(rowDate)) {
      notifyError('Past dates are read-only and cannot be edited.');
      return;
    }

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
      const range = normalizeCurrentMonthRange(
        document.querySelector('[data-auto-assign-range-start]')?.value || '',
        document.querySelector('[data-auto-assign-range-end]')?.value || ''
      );
      const rangeStartInput = document.querySelector('[data-auto-assign-range-start]');
      const rangeEndInput = document.querySelector('[data-auto-assign-range-end]');
      if (rangeStartInput) rangeStartInput.value = range.start;
      if (rangeEndInput) rangeEndInput.value = range.end;
      const minEmployeesRaw = parseInt(document.querySelector('[data-auto-assign-min-employees]')?.value || '1', 10);
      const maxEmployeesRaw = parseInt(document.querySelector('[data-auto-assign-max-employees]')?.value || '3', 10);
      const minEmployeesPerShiftDay = Number.isFinite(minEmployeesRaw) ? Math.max(0, minEmployeesRaw) : 1;
      const maxEmployeesPerShiftDay = Number.isFinite(maxEmployeesRaw) ? Math.max(1, maxEmployeesRaw) : 3;
      const normalizedMin = Math.min(minEmployeesPerShiftDay, maxEmployeesPerShiftDay);
      const normalizedMax = Math.max(minEmployeesPerShiftDay, maxEmployeesPerShiftDay);

      const employeeRules = {
        ...loadRules(),
        ...collectRulesFromRows(),
      };
      const res = await AppAPI.postJSON(apiUrl, {
        action: 'auto_assign_open',
        scope_shift_id: parseInt(document.querySelector('[data-auto-assign-shift]')?.value || '0', 10) || 0,
        range_start: range.start,
        range_end: range.end,
        min_employees_per_shift_day: normalizedMin,
        max_employees_per_shift_day: normalizedMax,
        employee_rules: employeeRules,
      });
      if (res?.ok || res?.success) {
        const skippedByRules = parseInt(res.skipped_by_rules || '0', 10) || 0;
        const groupsBelowMin = parseInt(res.groups_below_min || '0', 10) || 0;
        const message = `Assigned ${res.assigned_count || 0} shifts. Open remaining: ${res.open_remaining || 0}.`
          + (skippedByRules > 0 ? ` Skipped by rules: ${skippedByRules}.` : '')
          + (groupsBelowMin > 0 ? ` Shift-days below minimum: ${groupsBelowMin}.` : '');
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

  async function clearAssignedInScope() {
    if (!apiUrl || !window.AppAPI) return;

    const scopeShiftId = parseInt(document.querySelector('[data-auto-assign-shift]')?.value || '0', 10) || 0;
    const normalizedRange = normalizeCurrentMonthRange(
      document.querySelector('[data-auto-assign-range-start]')?.value || '',
      document.querySelector('[data-auto-assign-range-end]')?.value || ''
    );
    const rangeStartInput = document.querySelector('[data-auto-assign-range-start]');
    const rangeEndInput = document.querySelector('[data-auto-assign-range-end]');
    if (rangeStartInput) rangeStartInput.value = normalizedRange.start;
    if (rangeEndInput) rangeEndInput.value = normalizedRange.end;
    const rangeStart = normalizedRange.start;
    const rangeEnd = normalizedRange.end;
    const shiftLabel = document.querySelector('[data-auto-assign-shift]')?.selectedOptions?.[0]?.textContent?.trim() || 'selected shifts';
    const rangeLabel = rangeStart && rangeEnd ? `${rangeStart} to ${rangeEnd}` : 'the selected range';

    const canClear = feedback?.confirm
      ? await feedback.confirm(`Unassign all employees for ${scopeShiftId > 0 ? shiftLabel : 'all work shifts'} in ${rangeLabel}?`, 'Confirm action')
      : false;

    if (!canClear) {
      if (!feedback?.confirm) {
        notifyError('Confirmation dialog is not available.');
      }
      return;
    }

    try {
      const res = await AppAPI.postJSON(apiUrl, {
        action: 'clear_assignments_scope',
        scope_shift_id: scopeShiftId,
        range_start: rangeStart,
        range_end: rangeEnd,
      });
      if (res?.ok || res?.success) {
        const message = `Cleared ${res.cleared_count || 0} assigned shift(s).`;
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('assignments', 'Done', message);
        } else {
          notifySuccess(message);
          location.reload();
        }
      } else {
        notifyError('Clear assignments failed: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      notifyError('Error clearing assignments.');
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
      return;
    }

    const clearAssignmentsBtn = ev.target.closest && ev.target.closest('[data-auto-assign-clear]');
    if (clearAssignmentsBtn) {
      ev.preventDefault();
      clearAssignedInScope();
      return;
    }

    const assignmentListToggleBtn = ev.target.closest && ev.target.closest('[data-assignment-list-toggle]');
    if (assignmentListToggleBtn) {
      ev.preventDefault();
      const wrap = getAssignmentsListWrap();
      setAssignmentsListVisible(!!wrap?.hidden);
      return;
    }

    const openEmployeeBtn = ev.target.closest && ev.target.closest('[data-assignment-employee-open]');
    if (openEmployeeBtn) {
      ev.preventDefault();
      openEmployeeModal(
        openEmployeeBtn.getAttribute('data-user-id'),
        openEmployeeBtn.getAttribute('data-user-name'),
        openEmployeeBtn.getAttribute('data-user-department-id'),
        openEmployeeBtn.getAttribute('data-user-department-name'),
      );
      return;
    }

    const closeEmployeeBtn = ev.target.closest && ev.target.closest('[data-assignment-employee-close]');
    if (closeEmployeeBtn) {
      ev.preventDefault();
      closeEmployeeModal();
      return;
    }

    if (employeeModal && ev.target === employeeModal) {
      closeEmployeeModal();
      return;
    }

    const modalRemoveSpecialBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-remove-special]');
    if (modalRemoveSpecialBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      const dateToRemove = modalRemoveSpecialBtn.getAttribute('data-assignment-modal-remove-special') || '';
      const currentRule = getRuleForUser(activeEmployeeUserId);
      currentRule.special_dates = (currentRule.special_dates || []).filter((item) => String(item?.date || '') !== dateToRemove);
      setRuleForUser(activeEmployeeUserId, currentRule);
      openEmployeeModal(activeEmployeeUserId, activeEmployeeUserName, activeEmployeeDepartmentId, activeEmployeeDepartmentName);
      return;
    }

    const modalAddSpecialBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-add-special]');
    if (modalAddSpecialBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      const dateValue = (employeeModalSpecialDate?.value || '').trim();
      const reasonValue = (employeeModalSpecialReason?.value || 'special').trim() || 'special';
      if (!/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
        notifyError('Choose a valid unavailable date.');
        return;
      }
      const currentRule = getRuleForUser(activeEmployeeUserId);
      if ((currentRule.special_dates || []).some((item) => String(item?.date || '') === dateValue)) {
        notifyError('This unavailable date already exists for the employee.');
        return;
      }
      currentRule.special_dates = (currentRule.special_dates || []).concat([{ date: dateValue, reason: reasonValue }]);
      setRuleForUser(activeEmployeeUserId, currentRule);
      if (employeeModalSpecialDate) {
        employeeModalSpecialDate.value = '';
      }
      openEmployeeModal(activeEmployeeUserId, activeEmployeeUserName, activeEmployeeDepartmentId, activeEmployeeDepartmentName);
      return;
    }

    const modalAddSpecialRangeBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-add-special-range]');
    if (modalAddSpecialRangeBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      addUnavailableRangeForActiveEmployee();
      return;
    }

    const modalRulesResetBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-rules-reset]');
    if (modalRulesResetBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      resetRulesForActiveEmployee();
      return;
    }

    const weekDayToggleBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-week-day]');
    if (weekDayToggleBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      const dateValue = normalizeDateKey(weekDayToggleBtn.getAttribute('data-assignment-modal-week-day') || '');
      if (!dateValue) return;

      const selectedReason = String(employeeModalSpecialReason?.value || 'rest').trim() || 'rest';
      const currentRule = getRuleForUser(activeEmployeeUserId);
      const specialDates = Array.isArray(currentRule.special_dates) ? currentRule.special_dates.slice() : [];
      const existingIndex = specialDates.findIndex((item) => String(item?.date || '') === dateValue);

      if (existingIndex >= 0) {
        specialDates.splice(existingIndex, 1);
      } else {
        specialDates.push({ date: dateValue, reason: selectedReason });
      }

      currentRule.special_dates = specialDates.sort((a, b) => String(a?.date || '').localeCompare(String(b?.date || '')));
      setRuleForUser(activeEmployeeUserId, currentRule);
      openEmployeeModal(activeEmployeeUserId, activeEmployeeUserName, activeEmployeeDepartmentId, activeEmployeeDepartmentName);
      return;
    }

    const openSlotToggle = ev.target.closest && ev.target.closest('[data-assignment-modal-open-toggle]');
    if (openSlotToggle && activeEmployeeUserId > 0) {
      ev.preventDefault();
      const key = openSlotToggle.getAttribute('data-assignment-modal-open-toggle') || '';
      if (!key) return;
      if (selectedOpenSlotKeys.has(key)) {
        selectedOpenSlotKeys.delete(key);
      } else {
        selectedOpenSlotKeys.add(key);
      }
      renderOpenSlotSelection();
      return;
    }

    const openSlotClearBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-open-clear]');
    if (openSlotClearBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      clearOpenSlotSelection();
      return;
    }

    const openSlotReselectBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-open-reselect]');
    if (openSlotReselectBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      reselectionOpenSlots();
      return;
    }

    const openSlotAssignBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-open-assign]');
    if (openSlotAssignBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      assignSelectedOpenSlots();
      return;
    }

    const openSlotCoverAllBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-open-cover-all]');
    if (openSlotCoverAllBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      reselectionOpenSlots();
      assignSelectedOpenSlots();
      return;
    }

    const absenceAssignBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-absence-assign]');
    if (absenceAssignBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      assignAbsenceRange();
      return;
    }

    const absenceResetBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-absence-reset]');
    if (absenceResetBtn && activeEmployeeUserId > 0) {
      ev.preventDefault();
      syncAbsenceRangeDefaults();
      return;
    }

    const modalEditShiftBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-edit]');
    if (modalEditShiftBtn) {
      ev.preventDefault();
      const assignmentId = modalEditShiftBtn.getAttribute('data-assignment-modal-edit') || '';
      const row = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
      if (row) {
        setAssignmentsListVisible(true);
        openDrawer(row);
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      closeEmployeeModal();
      return;
    }

    const modalUnassignShiftBtn = ev.target.closest && ev.target.closest('[data-assignment-modal-unassign]');
    if (modalUnassignShiftBtn) {
      ev.preventDefault();
      const assignmentId = modalUnassignShiftBtn.getAttribute('data-assignment-modal-unassign') || '';
      const row = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
      if (row) {
        unassignAssignment(row);
      }
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
      return;
    }

    if (target.matches('[data-assignment-modal-weekday]') && activeEmployeeUserId > 0) {
      const checked = Array.from(employeeModalWeekdays?.querySelectorAll('[data-assignment-modal-weekday]:checked') || [])
        .map((checkbox) => parseInt(checkbox.getAttribute('data-assignment-modal-weekday') || '-1', 10))
        .filter((value) => value >= 0 && value <= 6);
      const currentRule = getRuleForUser(activeEmployeeUserId);
      currentRule.off_weekdays = Array.from(new Set(checked)).sort();
      setRuleForUser(activeEmployeeUserId, currentRule);
      renderEmployeeWeeklyAvailability(currentRule);
      renderCurrentMonthUnavailable(currentRule);
      renderOpenSlotSelection();
      return;
    }

    if (target.matches('[data-assignment-modal-open-shift]') && activeEmployeeUserId > 0) {
      selectedOpenSlotKeys = new Set();
      renderOpenSlotSelection();
      return;
    }

    if ((target.matches('[data-assignment-modal-open-from]') || target.matches('[data-assignment-modal-open-to]')) && activeEmployeeUserId > 0) {
      const openFrom = getEmployeeModalField('[data-assignment-modal-open-from]');
      const openTo = getEmployeeModalField('[data-assignment-modal-open-to]');
      if (normalizeDateKey(openFrom?.value || '') && normalizeDateKey(openTo?.value || '') && openFrom.value > openTo.value) {
        const tempValue = openFrom.value;
        openFrom.value = openTo.value;
        openTo.value = tempValue;
      }
      selectedOpenSlotKeys = new Set();
      renderOpenSlotSelection();
    }

    if ((target.matches('[data-assignment-modal-special-from]') || target.matches('[data-assignment-modal-special-to]') || target.matches('[data-assignment-modal-absence-from]') || target.matches('[data-assignment-modal-absence-to]')) && activeEmployeeUserId > 0) {
      const specialRange = normalizeDateRange(employeeModalSpecialFrom?.value || '', employeeModalSpecialTo?.value || '');
      if (specialRange) {
        if (employeeModalSpecialFrom) employeeModalSpecialFrom.value = specialRange.start;
        if (employeeModalSpecialTo) employeeModalSpecialTo.value = specialRange.end;
      }
      const absenceRange = normalizeDateRange(employeeModalAbsenceFrom?.value || '', employeeModalAbsenceTo?.value || '');
      if (absenceRange) {
        if (employeeModalAbsenceFrom) employeeModalAbsenceFrom.value = absenceRange.start;
        if (employeeModalAbsenceTo) employeeModalAbsenceTo.value = absenceRange.end;
      }
    }
  });

  applyRulesToRows(loadRules());
})();
