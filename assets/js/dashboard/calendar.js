/**
 * Dashboard calendar interactions module.
 *
 * Handles user interactions with the calendar, including clicking on date cards
 * to open the date view and clicking on assignment cards to open the associated
 * date. Relies on custom data attributes for identifying interactive elements and
 * communicates with the main dashboard logic via provided callback functions.
 */

(function(){
  var feedback = window.DashboardFeedback;
  var locale = String(document.documentElement.getAttribute('lang') || 'en').toLowerCase();
  var isFr = locale.indexOf('fr') === 0;
  var iconsBase = String((window.DashboardConfig && window.DashboardConfig.iconsBase) || '/assets/icons/');
  function tr(enText, frText) {
    return isFr ? frText : enText;
  }

  function notifyError(message) {
    if (feedback && typeof feedback.error === 'function') {
      feedback.error(tr('Oops!', 'Erreur'), message);
      return;
    }
    console.error(message);
  }

  function notifySuccess(message) {
    if (feedback && typeof feedback.success === 'function') {
      feedback.success(tr('Done', 'Termine'), message);
      return;
    }
  }

  function createBulkAssignModal(assignShift, events, isUserAvailableForDate) {
    var state = {
      assignment: null,
      dates: [],
    };

    var host = document.createElement('div');
    host.className = 'calendar-bulk-assign-overlay';
    host.hidden = true;
    host.innerHTML = '\n      <section class="calendar-bulk-assign-modal" role="dialog" aria-modal="true" aria-label="Assign employee to other dates">\n        <header class="calendar-bulk-assign-head">\n          <h3>Assign Employee to Other Dates</h3>\n          <button type="button" class="calendar-bulk-assign-close" data-calendar-bulk-close aria-label="Close">×</button>\n        </header>\n        <div class="calendar-bulk-assign-employee">\n          <span class="calendar-bulk-assign-label">Employee</span>\n          <strong data-calendar-bulk-employee-name></strong>\n        </div>\n        <p class="calendar-bulk-assign-meta" data-calendar-bulk-meta></p>\n        <div class="calendar-bulk-assign-picker">\n          <input type="date" data-calendar-bulk-date-input>\n          <button type="button" class="calendar-bulk-assign-btn" data-calendar-bulk-add-date>Add date</button>\n        </div>\n        <div class="calendar-bulk-assign-range">\n          <input type="date" data-calendar-bulk-range-start>\n          <input type="date" data-calendar-bulk-range-end>\n          <button type="button" class="calendar-bulk-assign-btn is-secondary" data-calendar-bulk-add-range>Add range</button>\n        </div>\n        <div class="calendar-bulk-assign-available">\n          <div class="calendar-bulk-assign-available-head">\n            <span data-calendar-bulk-available-count>Available dates this month: 0</span>\n            <button type="button" class="calendar-bulk-assign-btn is-secondary" data-calendar-bulk-add-available>Add all available</button>\n          </div>\n          <div class="calendar-bulk-assign-available-list" data-calendar-bulk-available-list></div>\n        </div>\n        <div class="calendar-bulk-assign-dates" data-calendar-bulk-dates></div>\n        <footer class="calendar-bulk-assign-actions">\n          <button type="button" class="calendar-bulk-assign-btn" data-calendar-bulk-auto-assign-available>Auto assign available dates</button>\n          <button type="button" class="calendar-bulk-assign-btn" data-calendar-bulk-apply>Assign selected dates</button>\n        </footer>\n      </section>\n    ';

    document.body.appendChild(host);

    var meta = host.querySelector('[data-calendar-bulk-meta]');
    var employeeName = host.querySelector('[data-calendar-bulk-employee-name]');
    var dateInput = host.querySelector('[data-calendar-bulk-date-input]');
    var rangeStartInput = host.querySelector('[data-calendar-bulk-range-start]');
    var rangeEndInput = host.querySelector('[data-calendar-bulk-range-end]');
    var datesWrap = host.querySelector('[data-calendar-bulk-dates]');
    var availableCount = host.querySelector('[data-calendar-bulk-available-count]');
    var availableList = host.querySelector('[data-calendar-bulk-available-list]');
    var addDateButton = host.querySelector('[data-calendar-bulk-add-date]');
    var addRangeButton = host.querySelector('[data-calendar-bulk-add-range]');
    var addAvailableButton = host.querySelector('[data-calendar-bulk-add-available]');
    var autoAssignAvailableButton = host.querySelector('[data-calendar-bulk-auto-assign-available]');
    var applyButton = host.querySelector('[data-calendar-bulk-apply]');
    var closeButtons = host.querySelectorAll('[data-calendar-bulk-close]');
    var modalPanel = host.querySelector('.calendar-bulk-assign-modal');

    function toLocalDate(value) {
      if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
      var parsed = new Date(value + 'T12:00:00');
      return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    function toDateKey(date) {
      var year = String(date.getFullYear());
      var month = String(date.getMonth() + 1).padStart(2, '0');
      var day = String(date.getDate()).padStart(2, '0');
      return year + '-' + month + '-' + day;
    }

    function isDateAlreadyAssigned(dateValue) {
      if (!state.assignment) return false;
      return (events || []).some(function (item) {
        return Number(item.user_id || 0) === Number(state.assignment.user_id || 0)
          && Number(item.shift_id || 0) === Number(state.assignment.shift_id || 0)
          && String(item.work_date || '') === String(dateValue || '')
          && Number(item.assignment_id || 0) !== Number(state.assignment.assignment_id || 0)
          && Number(item.user_id || 0) > 0;
      });
    }

    function getAvailableDatesInMonth() {
      if (!state.assignment) return [];
      var currentWorkDate = String(state.assignment.work_date || '');
      if (!/^\d{4}-\d{2}-\d{2}$/.test(currentWorkDate)) return [];
      var monthPrefix = currentWorkDate.slice(0, 7);
      var shiftId = Number(state.assignment.shift_id || 0);
      var unique = new Set();

      (events || []).forEach(function (item) {
        var dateValue = String(item.work_date || '');
        if (!dateValue.startsWith(monthPrefix)) return;
        if (Number(item.shift_id || 0) !== shiftId) return;
        if (Number(item.user_id || 0) > 0) return;
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) return;
        if (typeof isUserAvailableForDate === 'function' && !isUserAvailableForDate(Number(state.assignment.user_id || 0), dateValue)) return;
        if (isDateAlreadyAssigned(dateValue)) return;
        unique.add(dateValue);
      });

      return Array.from(unique).sort();
    }

    function renderAvailableDates() {
      if (!availableCount || !availableList) return;
      var dates = getAvailableDatesInMonth();
      availableCount.textContent = 'Available dates this month: ' + dates.length;
      if (!dates.length) {
        availableList.innerHTML = '<p class="calendar-bulk-assign-empty">No open dates available for this shift in the same month.</p>';
        return;
      }
      availableList.innerHTML = dates.map(function (dateValue) {
        return '<button type="button" class="calendar-bulk-assign-chip is-clickable" data-calendar-bulk-pick-available="' + dateValue + '">' + dateValue + '</button>';
      }).join('');
    }

    function renderDates() {
      if (!datesWrap) return;
      if (!state.dates.length) {
        datesWrap.innerHTML = '<p class="calendar-bulk-assign-empty">No dates selected yet.</p>';
        return;
      }

      datesWrap.innerHTML = state.dates.map(function (dateValue) {
        return '<span class="calendar-bulk-assign-chip">' + dateValue + '<button type="button" data-calendar-bulk-remove-date="' + dateValue + '" aria-label="Remove date">×</button></span>';
      }).join('');
    }

    function closeModal() {
      host.hidden = true;
      state.assignment = null;
      state.dates = [];
      if (dateInput) {
        dateInput.value = '';
      }
      if (rangeStartInput) {
        rangeStartInput.value = '';
      }
      if (rangeEndInput) {
        rangeEndInput.value = '';
      }
      renderDates();
      renderAvailableDates();
    }

    function openModal(assignment) {
      state.assignment = assignment;
      state.dates = [];
      if (meta) {
        var userName = (assignment.user_name || 'Employee').trim();
        if (!userName) {
          userName = Number(assignment.user_id || 0) > 0 ? ('Employee #' + Number(assignment.user_id || 0)) : 'Employee';
        }
        var shiftName = assignment.shift_name || 'Shift';
        meta.textContent = 'Shift: ' + shiftName;
        if (employeeName) {
          employeeName.textContent = userName || 'Employee';
        }
      }
      if (dateInput) {
        dateInput.value = '';
      }
      renderDates();
      renderAvailableDates();
      host.hidden = false;
    }

    function pushDate(value) {
      if (!value) return;
      if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        notifyError('Use date format YYYY-MM-DD.');
        return;
      }
      if (typeof isUserAvailableForDate === 'function' && !isUserAvailableForDate(Number(state.assignment?.user_id || 0), value)) {
        notifyError('This employee is unavailable on ' + value + '.');
        return;
      }
      if (isDateAlreadyAssigned(value)) {
        notifyError('This employee is already assigned to the same shift on ' + value + '.');
        return;
      }
      if (!state.dates.includes(value)) {
        state.dates.push(value);
        state.dates.sort();
        renderDates();
      }
    }

    function pushRange(startValue, endValue) {
      var startDate = toLocalDate(startValue);
      var endDate = toLocalDate(endValue);
      if (!startDate || !endDate) {
        notifyError('Set a valid start and end date.');
        return;
      }
      if (endDate < startDate) {
        notifyError('Range end date must be after start date.');
        return;
      }

      var duplicateCount = 0;
      for (var cursor = new Date(startDate); cursor <= endDate; cursor.setDate(cursor.getDate() + 1)) {
        var key = toDateKey(cursor);
        if (isDateAlreadyAssigned(key)) {
          duplicateCount += 1;
          continue;
        }
        if (!state.dates.includes(key)) {
          state.dates.push(key);
        }
      }

      state.dates.sort();
      renderDates();
      if (duplicateCount > 0) {
        notifyError('Skipped ' + duplicateCount + ' already assigned date(s).');
      }
    }

    if (addDateButton) {
      addDateButton.addEventListener('click', function () {
        pushDate(dateInput ? dateInput.value : '');
        if (dateInput) {
          dateInput.value = '';
        }
      });
    }

    if (addRangeButton) {
      addRangeButton.addEventListener('click', function () {
        pushRange(rangeStartInput ? rangeStartInput.value : '', rangeEndInput ? rangeEndInput.value : '');
      });
    }

    if (addAvailableButton) {
      addAvailableButton.addEventListener('click', function () {
        getAvailableDatesInMonth().forEach(function (dateValue) {
          pushDate(dateValue);
        });
      });
    }

    if (dateInput) {
      dateInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          pushDate(dateInput.value);
          dateInput.value = '';
        }
      });
    }

    if (modalPanel) {
      modalPanel.addEventListener('click', function (event) {
        event.stopPropagation();
      });
    }

    closeButtons.forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        closeModal();
      });
    });

    host.addEventListener('click', function (event) {
      if (event.target === host) {
        closeModal();
        return;
      }

      var removeButton = event.target.closest('[data-calendar-bulk-remove-date]');
      if (removeButton) {
        var dateValue = removeButton.getAttribute('data-calendar-bulk-remove-date') || '';
        state.dates = state.dates.filter(function (item) { return item !== dateValue; });
        renderDates();
        return;
      }

      var availablePick = event.target.closest('[data-calendar-bulk-pick-available]');
      if (availablePick) {
        var availableDate = availablePick.getAttribute('data-calendar-bulk-pick-available') || '';
        pushDate(availableDate);
      }
    });

    if (autoAssignAvailableButton) {
      autoAssignAvailableButton.addEventListener('click', function () {
        if (!state.assignment || typeof assignShift !== 'function') {
          notifyError('Missing assignment context.');
          return;
        }

        var availableDates = getAvailableDatesInMonth();
        if (!availableDates.length) {
          notifyError('No available dates in this month for auto assignment.');
          return;
        }

        (async function () {
          var successCount = 0;
          for (var index = 0; index < availableDates.length; index += 1) {
            try {
              await assignShift({
                user_id: Number(state.assignment.user_id || 0),
                shift_id: Number(state.assignment.shift_id || 0),
                work_date: availableDates[index],
                status: 'assigned',
              });
              successCount += 1;
            } catch (error) {
              // Continue with other dates even if one assignment fails.
            }
          }

          if (successCount > 0) {
            notifySuccess('Assigned ' + successCount + ' available date(s).');
            closeModal();
            return;
          }
          notifyError('Unable to auto assign available dates.');
        })();
      });
    }

    if (applyButton) {
      applyButton.addEventListener('click', function () {
      if (!state.assignment || !state.dates.length || typeof assignShift !== 'function') {
        notifyError('Select at least one date.');
        return;
      }

      (async function () {
        try {
          for (var index = 0; index < state.dates.length; index += 1) {
            await assignShift({
              user_id: Number(state.assignment.user_id || 0),
              shift_id: Number(state.assignment.shift_id || 0),
              work_date: state.dates[index],
              status: 'assigned',
            });
          }
          notifySuccess('Employee assigned to selected dates.');
          closeModal();
        } catch (error) {
          notifyError((error && error.message) || 'Unable to assign employee to selected dates.');
        }
      })();
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !host.hidden) {
        closeModal();
      }
    });

    return {
      open: openModal,
    };
  }

  function createDayFullscreenModal(events, toLocalDate, openDate, options) {
    options = options || {};
    var host = document.createElement('div');
    host.className = 'calendar-day-fullscreen-overlay';
    host.hidden = true;
    host.innerHTML = '\n      <section class="calendar-day-fullscreen-modal" role="dialog" aria-modal="true" aria-label="' + tr('Day assignments', 'Affectations du jour') + '">\n        <header class="calendar-day-fullscreen-head">\n          <div>\n            <p class="calendar-day-fullscreen-kicker">' + tr('Daily overview', 'Apercu quotidien') + '</p>\n            <h3 data-calendar-day-fullscreen-title></h3>\n          </div>\n          <div class="calendar-day-fullscreen-head-actions">\n            <button type="button" class="calendar-day-fullscreen-nav" data-calendar-day-fullscreen-prev>' + tr('Previous day', 'Jour precedent') + '</button>\n            <button type="button" class="calendar-day-fullscreen-nav" data-calendar-day-fullscreen-next>' + tr('Next day', 'Jour suivant') + '</button>\n            <button type="button" class="calendar-day-fullscreen-close" data-calendar-day-fullscreen-close aria-label="' + tr('Close', 'Fermer') + '">×</button>\n          </div>\n        </header>\n        <section class="calendar-day-fullscreen-summary" data-calendar-day-fullscreen-summary></section>\n        <section class="calendar-day-fullscreen-manual" data-calendar-day-fullscreen-manual></section>\n        <section class="calendar-day-fullscreen-unavailable" data-calendar-day-fullscreen-unavailable></section>\n        <div class="calendar-day-fullscreen-list" data-calendar-day-fullscreen-list></div>\n      </section>\n    ';

    document.body.appendChild(host);

    var titleNode = host.querySelector('[data-calendar-day-fullscreen-title]');
    var summaryNode = host.querySelector('[data-calendar-day-fullscreen-summary]');
    var manualNode = host.querySelector('[data-calendar-day-fullscreen-manual]');
    var listNode = host.querySelector('[data-calendar-day-fullscreen-list]');
    var unavailableNode = host.querySelector('[data-calendar-day-fullscreen-unavailable]');
    var closeButton = host.querySelector('[data-calendar-day-fullscreen-close]');
    var prevButton = host.querySelector('[data-calendar-day-fullscreen-prev]');
    var nextButton = host.querySelector('[data-calendar-day-fullscreen-next]');
    var panel = host.querySelector('.calendar-day-fullscreen-modal');
    var titleFormatter = new Intl.DateTimeFormat(isFr ? 'fr-FR' : 'en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    var assignShift = typeof options.assignShift === 'function' ? options.assignShift : null;
    var getActiveUser = typeof options.getActiveUser === 'function' ? options.getActiveUser : null;
    var getActiveShift = typeof options.getActiveShift === 'function' ? options.getActiveShift : null;
    var getAbsenceTemplateShiftId = typeof options.getAbsenceTemplateShiftId === 'function' ? options.getAbsenceTemplateShiftId : null;
    var getAbsenceTemplateShift = typeof options.getAbsenceTemplateShift === 'function' ? options.getAbsenceTemplateShift : null;
    var isUserAvailableForDate = typeof options.isUserAvailableForDate === 'function' ? options.isUserAvailableForDate : null;
    var getUserAvailabilityStatus = typeof options.getUserAvailabilityStatus === 'function' ? options.getUserAvailabilityStatus : null;
    var attendances = Array.isArray(options.attendances) ? options.attendances : [];
    var state = {
      currentDate: '',
    };

    function closeModal() {
      host.hidden = true;
    }

    function toDateLabel(dateValue) {
      var dateObj = typeof toLocalDate === 'function' ? toLocalDate(dateValue) : new Date(dateValue + 'T12:00:00');
      if (!dateObj || Number.isNaN(dateObj.getTime())) return String(dateValue || tr('Selected date', 'Date selectionnee'));
      return titleFormatter.format(dateObj);
    }

    function resolveTemplateIconPath(shiftRecord, fallbackIconFile) {
      var rawIcon = String((shiftRecord && (shiftRecord.icon || shiftRecord.shift_icon)) || '').trim();
      if (!rawIcon) {
        return iconsBase + fallbackIconFile;
      }
      if (rawIcon.indexOf('data:') === 0 || rawIcon.indexOf('http://') === 0 || rawIcon.indexOf('https://') === 0 || rawIcon.indexOf('/') === 0) {
        return rawIcon;
      }
      return iconsBase + rawIcon.replace(/^\/+/, '');
    }

    function getAbsenceTemplateMeta(kind, fallbackIconFile) {
      var shiftRecord = getAbsenceTemplateShift ? (getAbsenceTemplateShift(kind) || null) : null;
      var shiftId = Number(shiftRecord && shiftRecord.id ? shiftRecord.id : (getAbsenceTemplateShiftId ? getAbsenceTemplateShiftId(kind) : 0));
      return {
        id: shiftId,
        iconPath: resolveTemplateIconPath(shiftRecord, fallbackIconFile),
      };
    }

    function renderAbsenceIcon(path, className) {
      return '<img src="' + path + '" class="' + className + '" alt="" aria-hidden="true">';
    }

    function groupByShift(dayEvents) {
      return dayEvents.reduce(function (map, event) {
        var key = String(event.shift_id || 0);
        if (!map.has(key)) {
          map.set(key, {
            shiftName: event.shift_name || tr('Shift', 'Poste'),
            shiftIcon: event.shift_icon || '🕒',
            shiftColor: event.shift_color || '#2f6fed',
            startTime: event.start_time || null,
            endTime: event.end_time || null,
            employees: [],
          });
        }
        if (Number(event.user_id || 0) > 0) {
          var fullName = String(event.user_name || '').trim();
          map.get(key).employees.push(fullName || (tr('Employee', 'Employe') + ' #' + Number(event.user_id || 0)));
        }
        return map;
      }, new Map());
    }

    function formatShiftTime(group) {
      var start = typeof group.startTime === 'string' ? group.startTime.slice(0, 5) : '--:--';
      var end = typeof group.endTime === 'string' ? group.endTime.slice(0, 5) : '--:--';
      return start + ' - ' + end;
    }

    function addDaysToKey(dateValue, delta) {
      var base = typeof toLocalDate === 'function' ? toLocalDate(dateValue) : new Date(dateValue + 'T12:00:00');
      if (!base || Number.isNaN(base.getTime())) return dateValue;
      base.setDate(base.getDate() + delta);
      var y = String(base.getFullYear());
      var m = String(base.getMonth() + 1).padStart(2, '0');
      var d = String(base.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + d;
    }

    function todayKey() {
      var now = new Date();
      var y = String(now.getFullYear());
      var m = String(now.getMonth() + 1).padStart(2, '0');
      var d = String(now.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + d;
    }

    function getUnavailableByReason(dayEvents) {
      var reasonMap = {
        rest: [],
        vacation: [],
        sick: [],
      };

      var templateShiftIds = {
        rest: getAbsenceTemplateShiftId ? Number(getAbsenceTemplateShiftId('rest') || 0) : 0,
        vacation: getAbsenceTemplateShiftId ? Number(getAbsenceTemplateShiftId('vacation') || 0) : 0,
        sick: getAbsenceTemplateShiftId ? Number(getAbsenceTemplateShiftId('sick') || 0) : 0,
      };

      dayEvents.forEach(function (event) {
        var kind = String(event.shift_kind || '').toLowerCase();
        var shiftId = Number(event.shift_id || 0);

        if (!kind && shiftId && shiftId === templateShiftIds.rest) {
          kind = 'rest';
        }
        if (!kind && shiftId && shiftId === templateShiftIds.vacation) {
          kind = 'vacation';
        }
        if (!kind && shiftId && shiftId === templateShiftIds.sick) {
          kind = 'sick';
        }

        if (!kind && String(event.status || '').toLowerCase() === 'rest') {
          kind = 'rest';
        }
        if (!kind && String(event.status || '').toLowerCase() === 'vacation') {
          kind = 'vacation';
        }
        if (!kind && String(event.status || '').toLowerCase() === 'sick') {
          kind = 'sick';
        }

        var normalizedReason = '';
        if (kind === 'rest' || kind === 'restday' || kind === 'rest_day') normalizedReason = 'rest';
        if (kind === 'vacation' || kind === 'holiday' || kind === 'leave' || kind === 'conge' || kind === 'holidays') normalizedReason = 'vacation';
        if (kind === 'sick' || kind === 'sickness' || kind === 'sickleave' || kind === 'sick_leave' || kind === 'maladie') normalizedReason = 'sick';
        if (!normalizedReason) return;

        var userId = Number(event.user_id || 0);
        if (!userId) return;
        var fullName = String(event.user_name || '').trim() || ('Employee #' + userId);
        if (!reasonMap[normalizedReason].includes(fullName)) {
          reasonMap[normalizedReason].push(fullName);
        }
      });

      return reasonMap;
    }

    function renderSummary(dayEvents, groups, unavailable, dateValue) {
      if (!summaryNode) return;

      var assignedUsers = new Set();
      dayEvents.forEach(function (event) {
        var userId = Number(event.user_id || 0);
        if (userId > 0) assignedUsers.add(userId);
      });

      var openShifts = groups.filter(function (group) {
        return (group.employees || []).length === 0;
      }).length;

      var dayAttendances = attendances.filter(function (item) {
        return String(item.work_date || '') === String(dateValue || '');
      });
      var lateCount = dayAttendances.filter(function (item) { return String(item.status || '').toLowerCase() === 'late'; }).length;
      var absentCount = dayAttendances.filter(function (item) { return String(item.status || '').toLowerCase() === 'absent'; }).length;

      summaryNode.innerHTML = '\n        <div class="calendar-day-fullscreen-summary-grid">\n          <article class="calendar-day-fullscreen-summary-card"><span>' + tr('Total shifts', 'Total postes') + '</span><strong>' + groups.length + '</strong></article>\n          <article class="calendar-day-fullscreen-summary-card"><span>' + tr('Assigned employees', 'Employes assignes') + '</span><strong>' + assignedUsers.size + '</strong></article>\n          <article class="calendar-day-fullscreen-summary-card"><span>' + tr('Open shifts', 'Postes ouverts') + '</span><strong>' + openShifts + '</strong></article>\n          <article class="calendar-day-fullscreen-summary-card"><span>' + tr('Rest / Vacation / Sick', 'Repos / Vacances / Maladie') + '</span><strong>' + unavailable.rest.length + ' / ' + unavailable.vacation.length + ' / ' + unavailable.sick.length + '</strong></article>\n          <article class="calendar-day-fullscreen-summary-card"><span>' + tr('Late attendances', 'Presences en retard') + '</span><strong>' + lateCount + '</strong></article>\n          <article class="calendar-day-fullscreen-summary-card"><span>' + tr('Absent attendances', 'Presences absentes') + '</span><strong>' + absentCount + '</strong></article>\n        </div>\n      ';
    }

    function renderManualAssignment(dateValue) {
      if (!manualNode) return;

      var selectedUser = getActiveUser ? (getActiveUser() || null) : null;
      var selectedUserId = Number(selectedUser && selectedUser.id ? selectedUser.id : 0);
      var selectedUserName = String(selectedUser && selectedUser.name ? selectedUser.name : '').trim();

      if (!selectedUserId) {
        manualNode.innerHTML = '<p class="calendar-day-fullscreen-manual-empty">' + tr('Select an employee from the sidebar to assign Rest, Vacation or Sick leave for this date.', 'Selectionnez un employe depuis la barre laterale pour attribuer repos, vacances ou arret maladie a cette date.') + '</p>';
        return;
      }

      if (String(dateValue || '') < todayKey()) {
        manualNode.innerHTML = '<p class="calendar-day-fullscreen-manual-empty">' + tr('Past dates are locked and cannot be edited.', 'Les dates passees sont verrouillees et ne peuvent pas etre modifiees.') + '</p>';
        return;
      }

      var restMeta = getAbsenceTemplateMeta('rest', 'moon.svg');
      var vacationMeta = getAbsenceTemplateMeta('vacation', 'parasol.svg');
      var sickMeta = getAbsenceTemplateMeta('sick', 'stethoscope.svg');
      if (!restMeta.id || !vacationMeta.id || !sickMeta.id) {
        manualNode.innerHTML = '<p class="calendar-day-fullscreen-manual-empty">' + tr('Absence templates are not available for this department.', 'Les modeles d absence ne sont pas disponibles pour ce departement.') + '</p>';
        return;
      }

      var activeShift = getActiveShift ? (getActiveShift() || null) : null;
      var activeShiftKind = String(activeShift && activeShift.kind ? activeShift.kind : '').toLowerCase();
      var activeWorkShiftId = activeShift && activeShiftKind === 'work' ? Number(activeShift.id || 0) : 0;
      var activeWorkShiftName = activeShift && activeShiftKind === 'work' ? String(activeShift.name || tr('Selected shift', 'Poste selectionne')) : tr('Selected shift', 'Poste selectionne');

      manualNode.innerHTML = '\n        <div class="calendar-day-fullscreen-manual-head">\n          <strong>' + tr('Manual force assignment', 'Affectation manuelle forcee') + '</strong>\n          <span>' + (selectedUserName || (tr('Employee', 'Employe') + ' #' + selectedUserId)) + ' • ' + dateValue + '</span>\n        </div>\n        <div class="calendar-day-fullscreen-manual-actions">\n          <button type="button" class="calendar-day-fullscreen-manual-btn" data-calendar-day-absence-assign="rest" data-user-id="' + selectedUserId + '" data-shift-id="' + restMeta.id + '" data-date="' + dateValue + '">' + renderAbsenceIcon(restMeta.iconPath, 'calendar-day-fullscreen-icon') + ' ' + tr('Rest', 'Repos') + '</button>\n          <button type="button" class="calendar-day-fullscreen-manual-btn" data-calendar-day-absence-assign="vacation" data-user-id="' + selectedUserId + '" data-shift-id="' + vacationMeta.id + '" data-date="' + dateValue + '">' + renderAbsenceIcon(vacationMeta.iconPath, 'calendar-day-fullscreen-icon') + ' ' + tr('Vacation', 'Vacances') + '</button>\n          <button type="button" class="calendar-day-fullscreen-manual-btn" data-calendar-day-absence-assign="sick" data-user-id="' + selectedUserId + '" data-shift-id="' + sickMeta.id + '" data-date="' + dateValue + '">' + renderAbsenceIcon(sickMeta.iconPath, 'calendar-day-fullscreen-icon') + ' ' + tr('Sick', 'Maladie') + '</button>\n          ' + (activeWorkShiftId > 0 ? ('<button type="button" class="calendar-day-fullscreen-manual-btn" data-calendar-day-force-work="1" data-user-id="' + selectedUserId + '" data-shift-id="' + activeWorkShiftId + '" data-date="' + dateValue + '">⏱ ' + tr('Work', 'Travail') + ': ' + activeWorkShiftName + '</button>') : '<span class="calendar-day-fullscreen-manual-note">' + tr('Select a work shift in sidebar to force work assignment.', 'Selectionnez un poste de travail dans la barre laterale pour forcer l affectation.') + '</span>') + '\n        </div>\n        <p class="calendar-day-fullscreen-manual-note">' + tr('Manual actions force assignment even if personal settings mark user unavailable.', 'Les actions manuelles forcent l affectation meme si les regles personnelles marquent l utilisateur indisponible.') + '</p>\n      ';
    }

    function openModal(dateValue) {
      var normalizedDate = String(dateValue || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(normalizedDate)) return;
      state.currentDate = normalizedDate;

      var dayEvents = (events || []).filter(function (event) {
        return String(event.work_date || '') === normalizedDate;
      });
      var groups = Array.from(groupByShift(dayEvents).values()).sort(function (a, b) {
        return String(a.startTime || '').localeCompare(String(b.startTime || ''));
      });
      var unavailable = getUnavailableByReason(dayEvents);

      renderSummary(dayEvents, groups, unavailable, normalizedDate);
      renderManualAssignment(normalizedDate);

      if (titleNode) {
        titleNode.textContent = toDateLabel(normalizedDate);
      }

      if (listNode) {
        if (!groups.length) {
          listNode.innerHTML = '<p class="calendar-day-fullscreen-empty">' + tr('No shifts scheduled for this date.', 'Aucun poste planifie pour cette date.') + '</p>';
        } else {
          listNode.innerHTML = groups.map(function (group) {
            var employees = group.employees.length
              ? group.employees
              : [tr('Open shift', 'Poste ouvert')];
            return '\n              <article class="calendar-day-fullscreen-shift" style="--fullscreen-shift-color:' + group.shiftColor + '">\n                <div class="calendar-day-fullscreen-shift-head">\n                  <span class="calendar-day-fullscreen-shift-title">' + group.shiftName + '</span>\n                  <span class="calendar-day-fullscreen-shift-time">' + formatShiftTime(group) + '</span>\n                </div>\n                <ul class="calendar-day-fullscreen-employees">' + employees.map(function (name) {
                  return '<li>' + name + '</li>';
                }).join('') + '</ul>\n              </article>\n            ';
          }).join('');
        }
      }

      if (unavailableNode) {
        var hasUnavailable = unavailable.rest.length || unavailable.vacation.length || unavailable.sick.length;
        if (!hasUnavailable) {
          unavailableNode.innerHTML = '';
          unavailableNode.hidden = true;
        } else {
          unavailableNode.hidden = false;
          var restTemplate = getAbsenceTemplateMeta('rest', 'moon.svg');
          var vacationTemplate = getAbsenceTemplateMeta('vacation', 'parasol.svg');
          var sickTemplate = getAbsenceTemplateMeta('sick', 'stethoscope.svg');
          var restIcon = renderAbsenceIcon(restTemplate.iconPath, 'calendar-day-fullscreen-icon');
          var vacationIcon = renderAbsenceIcon(vacationTemplate.iconPath, 'calendar-day-fullscreen-icon');
          var sickIcon = renderAbsenceIcon(sickTemplate.iconPath, 'calendar-day-fullscreen-icon');
          unavailableNode.innerHTML = '\n            <div class="calendar-day-fullscreen-unavailable-head">' + tr('Unavailable employees', 'Employes indisponibles') + '</div>\n            <div class="calendar-day-fullscreen-unavailable-grid">\n              <section class="calendar-day-fullscreen-unavailable-card is-rest">\n                <h4>' + restIcon + tr('Rest day', 'Jour de repos') + '</h4>\n                ' + (unavailable.rest.length ? ('<ul>' + unavailable.rest.map(function (name) { return '<li>' + restIcon + name + '</li>'; }).join('') + '</ul>') : '<p>' + tr('None', 'Aucun') + '</p>') + '\n              </section>\n              <section class="calendar-day-fullscreen-unavailable-card is-vacation">\n                <h4>' + vacationIcon + tr('Vacation / Holidays', 'Vacances / Conges') + '</h4>\n                ' + (unavailable.vacation.length ? ('<ul>' + unavailable.vacation.map(function (name) { return '<li>' + vacationIcon + name + '</li>'; }).join('') + '</ul>') : '<p>' + tr('None', 'Aucun') + '</p>') + '\n              </section>\n              <section class="calendar-day-fullscreen-unavailable-card is-sick">\n                <h4>' + sickIcon + tr('Sick leave', 'Arret maladie') + '</h4>\n                ' + (unavailable.sick.length ? ('<ul>' + unavailable.sick.map(function (name) { return '<li>' + sickIcon + name + '</li>'; }).join('') + '</ul>') : '<p>' + tr('None', 'Aucun') + '</p>') + '\n              </section>\n            </div>\n          ';
        }
      }

      host.hidden = false;
    }

    if (panel) {
      panel.addEventListener('click', function (event) {
        event.stopPropagation();
      });
    }

    if (closeButton) {
      closeButton.addEventListener('click', function () {
        closeModal();
      });
    }

    if (prevButton) {
      prevButton.addEventListener('click', function () {
        var previousDate = addDaysToKey(state.currentDate, -1);
        if (typeof openDate === 'function') {
          openDate(toLocalDate(previousDate));
        }
        openModal(previousDate);
      });
    }

    if (nextButton) {
      nextButton.addEventListener('click', function () {
        var nextDate = addDaysToKey(state.currentDate, 1);
        if (typeof openDate === 'function') {
          openDate(toLocalDate(nextDate));
        }
        openModal(nextDate);
      });
    }

    host.addEventListener('click', function (event) {
      var manualAssignButton = event.target.closest('[data-calendar-day-absence-assign]');
      if (manualAssignButton) {
        event.preventDefault();
        if (!assignShift) {
          notifyError('Assignment service not available.');
          return;
        }

        var assignUserId = Number(manualAssignButton.getAttribute('data-user-id') || 0);
        var assignShiftId = Number(manualAssignButton.getAttribute('data-shift-id') || 0);
        var assignDate = String(manualAssignButton.getAttribute('data-date') || '');
        if (!assignUserId || !assignShiftId || !assignDate) {
          notifyError('Missing assignment context.');
          return;
        }

        (async function () {
          try {
            await assignShift({
              user_id: assignUserId,
              shift_id: assignShiftId,
              work_date: assignDate,
              status: 'assigned',
              force_override: true,
            });
            notifySuccess('Absence assigned successfully.');
          } catch (error) {
            notifyError((error && error.message) || 'Unable to assign absence template.');
          }
        })();
        return;
      }

      var manualForceWorkButton = event.target.closest('[data-calendar-day-force-work]');
      if (manualForceWorkButton) {
        event.preventDefault();
        if (!assignShift) {
          notifyError('Assignment service not available.');
          return;
        }

        var forceUserId = Number(manualForceWorkButton.getAttribute('data-user-id') || 0);
        var forceShiftId = Number(manualForceWorkButton.getAttribute('data-shift-id') || 0);
        var forceDate = String(manualForceWorkButton.getAttribute('data-date') || '');
        if (!forceUserId || !forceShiftId || !forceDate) {
          notifyError('Missing force assignment context.');
          return;
        }

        (async function () {
          try {
            await assignShift({
              user_id: forceUserId,
              shift_id: forceShiftId,
              work_date: forceDate,
              status: 'assigned',
              force_override: true,
            });
            notifySuccess('Work shift assigned successfully.');
          } catch (error) {
            notifyError((error && error.message) || 'Unable to force work assignment.');
          }
        })();
        return;
      }

      if (event.target === host) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !host.hidden) {
        closeModal();
      }
    });

    return {
      open: openModal,
      close: closeModal,
    };
  }

  function initCalendarInteractions(options) {
    var calendarShell = options.calendarShell;
    var events = options.events || [];
    var attendances = options.attendances || [];
    var state = options.state || {};
    var toLocalDate = options.toLocalDate;
    var openDate = options.openDate;
    var unassignAssignment = options.unassignAssignment;
    var assignShift = options.assignShift;
    var isUserAvailableForDate = options.isUserAvailableForDate;
    var getUserAvailabilityStatus = options.getUserAvailabilityStatus;
    var getActiveUser = options.getActiveUser;
    var getActiveShift = options.getActiveShift;
    var getAbsenceTemplateShiftId = options.getAbsenceTemplateShiftId;
    var getAbsenceTemplateShift = options.getAbsenceTemplateShift;
    var bulkAssignModal = createBulkAssignModal(assignShift, events, isUserAvailableForDate);
    var dayFullscreenModal = createDayFullscreenModal(events, toLocalDate, openDate, {
      assignShift: assignShift,
      getActiveUser: getActiveUser,
      getActiveShift: getActiveShift,
      getAbsenceTemplateShiftId: getAbsenceTemplateShiftId,
      getAbsenceTemplateShift: getAbsenceTemplateShift,
      isUserAvailableForDate: isUserAvailableForDate,
      getUserAvailabilityStatus: getUserAvailabilityStatus,
      attendances: attendances,
    });
    var badgeActionState = {
      assignmentId: 0,
      userId: 0,
      workDate: '',
      anchorKey: '',
    };

    function resolveTemplateIconPath(kind, fallbackIconFile) {
      var templateShift = typeof getAbsenceTemplateShift === 'function' ? (getAbsenceTemplateShift(kind) || null) : null;
      var rawIcon = String((templateShift && (templateShift.icon || templateShift.shift_icon)) || '').trim();
      if (!rawIcon) return iconsBase + fallbackIconFile;
      if (rawIcon.indexOf('data:') === 0 || rawIcon.indexOf('http://') === 0 || rawIcon.indexOf('https://') === 0 || rawIcon.indexOf('/') === 0) {
        return rawIcon;
      }
      return iconsBase + rawIcon.replace(/^\/+/, '');
    }

    function renderBadgeActionsMenu() {
      var restIconPath = resolveTemplateIconPath('rest', 'moon.svg');
      var vacationIconPath = resolveTemplateIconPath('vacation', 'parasol.svg');
      var sickIconPath = resolveTemplateIconPath('sick', 'stethoscope.svg');
      badgeActionMenu.innerHTML = [
        '<button type="button" class="calendar-badge-actions-btn" data-calendar-badge-action="rest"><img class="calendar-badge-actions-icon" src="' + restIconPath + '" alt="" aria-hidden="true"> ' + tr('Rest', 'Repos') + '</button>',
        '<button type="button" class="calendar-badge-actions-btn" data-calendar-badge-action="vacation"><img class="calendar-badge-actions-icon" src="' + vacationIconPath + '" alt="" aria-hidden="true"> ' + tr('Vacation', 'Vacances') + '</button>',
        '<button type="button" class="calendar-badge-actions-btn" data-calendar-badge-action="sick"><img class="calendar-badge-actions-icon" src="' + sickIconPath + '" alt="" aria-hidden="true"> ' + tr('Sick', 'Maladie') + '</button>',
        '<button type="button" class="calendar-badge-actions-btn is-danger" data-calendar-badge-action="unassign"><img class="calendar-badge-actions-icon" src="' + iconsBase + 'x.svg" alt="" aria-hidden="true"> ' + tr('Unassign', 'Desassigner') + '</button>',
        '<button type="button" class="calendar-badge-actions-btn" data-calendar-badge-action="cancel">' + tr('Cancel', 'Annuler') + '</button>'
      ].join('');
    }

    var badgeActionMenu = document.createElement('div');
    badgeActionMenu.className = 'calendar-badge-actions-menu';
    badgeActionMenu.hidden = true;
    renderBadgeActionsMenu();
    document.body.appendChild(badgeActionMenu);

    function closeBadgeActionMenu() {
      badgeActionMenu.hidden = true;
      badgeActionState.assignmentId = 0;
      badgeActionState.userId = 0;
      badgeActionState.workDate = '';
      badgeActionState.anchorKey = '';
    }

    function openBadgeActionMenu(anchorNode, context, triggerEvent) {
      var targetAnchorKey = String(context.assignmentId || 0) + '|' + String(context.userId || 0) + '|' + String(context.workDate || '');
      if (!badgeActionMenu.hidden && badgeActionState.anchorKey === targetAnchorKey) {
        closeBadgeActionMenu();
        return;
      }

      renderBadgeActionsMenu();

      var rect = anchorNode && typeof anchorNode.getBoundingClientRect === 'function'
        ? anchorNode.getBoundingClientRect()
        : null;
      if (rect && rect.left === 0 && rect.top === 0 && rect.width === 0 && rect.height === 0) {
        rect = null;
      }

      if (!rect && triggerEvent && triggerEvent.target && typeof triggerEvent.target.closest === 'function') {
        var fallbackAnchor = triggerEvent.target.closest('.calendar-event-slot-card[data-calendar-slot-toggle], .calendar-event-user-badge, [data-calendar-slot-toggle]');
        if (fallbackAnchor && typeof fallbackAnchor.getBoundingClientRect === 'function') {
          rect = fallbackAnchor.getBoundingClientRect();
        }
      }

      var eventClientX = Number(triggerEvent && triggerEvent.clientX || 0);
      var eventClientY = Number(triggerEvent && triggerEvent.clientY || 0);
      if (rect && eventClientX > 20 && eventClientY > 20 && rect.left <= 2 && rect.top <= 2) {
        rect = null;
      }

      var top = rect ? (rect.bottom + 6) : (eventClientY + 10);
      var left = rect ? rect.left : (eventClientX + 10);

      badgeActionMenu.hidden = false;

      var viewportWidth = Math.max(
        Number(window.innerWidth || 0),
        Number((window.visualViewport && window.visualViewport.width) || 0),
        Number(document.documentElement && document.documentElement.clientWidth || 0),
        Number(document.body && document.body.clientWidth || 0)
      );
      var viewportHeight = Math.max(
        Number(window.innerHeight || 0),
        Number((window.visualViewport && window.visualViewport.height) || 0),
        Number(document.documentElement && document.documentElement.clientHeight || 0),
        Number(document.body && document.body.clientHeight || 0)
      );
      var menuWidth = Number(badgeActionMenu.offsetWidth || 170);
      var menuHeight = Number(badgeActionMenu.offsetHeight || 130);
      var boundary = 8;

      if (viewportWidth > 0 && left + menuWidth > viewportWidth - boundary) {
        left = Math.max(boundary, viewportWidth - menuWidth - boundary);
      }
      if (viewportHeight > 0 && top + menuHeight > viewportHeight - boundary) {
        var aboveTop = rect ? (rect.top - menuHeight - 6) : (viewportHeight - menuHeight - boundary);
        top = Math.max(boundary, aboveTop);
      }

      top = Math.max(boundary, top);
      left = Math.max(boundary, left);
      badgeActionMenu.style.top = top + 'px';
      badgeActionMenu.style.left = left + 'px';

      badgeActionState.assignmentId = Number(context.assignmentId || 0);
      badgeActionState.userId = Number(context.userId || 0);
      badgeActionState.workDate = String(context.workDate || '');
      badgeActionState.anchorKey = targetAnchorKey;
    }

    badgeActionMenu.addEventListener('click', function (event) {
      var actionButton = event.target.closest('[data-calendar-badge-action]');
      if (!actionButton) return;
      event.preventDefault();
      event.stopPropagation();

      var action = String(actionButton.getAttribute('data-calendar-badge-action') || '').toLowerCase();
      var assignmentId = Number(badgeActionState.assignmentId || 0);
      var userId = Number(badgeActionState.userId || 0);
      var workDate = String(badgeActionState.workDate || '');

      if (action === 'cancel') {
        closeBadgeActionMenu();
        return;
      }

      if (!workDate || isPastDateKey(workDate)) {
        notifyError('Past days are locked and cannot be edited.');
        closeBadgeActionMenu();
        return;
      }

      if (action === 'unassign') {
        if (!assignmentId || typeof unassignAssignment !== 'function') {
          notifyError('Missing unassign context.');
          return;
        }
        (async function () {
          try {
            await unassignAssignment(assignmentId);
            notifySuccess(tr('Shift unassigned successfully.', 'Poste desassigne avec succes.'));
            closeBadgeActionMenu();
          } catch (error) {
            notifyError((error && error.message) || tr('Unable to unassign shift.', 'Impossible de desassigner le poste.'));
          }
        })();
        return;
      }

      if (!assignShift || !userId) {
        notifyError('Missing assignment context.');
        return;
      }

      var absenceShiftId = getAbsenceTemplateShiftId ? Number(getAbsenceTemplateShiftId(action) || 0) : 0;
      if (!absenceShiftId) {
        notifyError(tr('Absence template is not available.', 'Le modele d absence n est pas disponible.'));
        return;
      }

      (async function () {
        try {
          await assignShift({
            user_id: userId,
            shift_id: absenceShiftId,
            work_date: workDate,
            status: 'assigned',
            force_override: true,
          });
          notifySuccess(tr('Absence assigned successfully.', 'Absence assignee avec succes.'));
          closeBadgeActionMenu();
        } catch (error) {
          notifyError((error && error.message) || tr('Unable to assign absence.', 'Impossible d assigner l absence.'));
        }
      })();
    });

    document.addEventListener('click', function (event) {
      if (badgeActionMenu.hidden) return;
      if (event.target.closest('.calendar-badge-actions-menu')) return;
      closeBadgeActionMenu();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !badgeActionMenu.hidden) {
        closeBadgeActionMenu();
      }
    });

    var toDateKey = function (dateObj) {
      var y = String(dateObj.getFullYear());
      var m = String(dateObj.getMonth() + 1).padStart(2, '0');
      var d = String(dateObj.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + d;
    };

    var isPastDateKey = function (dateValue) {
      if (!/^\d{4}-\d{2}-\d{2}$/.test(String(dateValue || ''))) return false;
      var now = new Date();
      now.setHours(12, 0, 0, 0);
      return String(dateValue) < toDateKey(now);
    };

    if (!calendarShell) return;

    calendarShell.addEventListener('click', function (event) {
      var userBadge = event.target.closest('.calendar-event-user-badge');
      if (userBadge) {
        var slotCardButton = userBadge.closest('.calendar-event-slot-card[data-calendar-slot-toggle]');
        if (slotCardButton) {
          event.preventDefault();
          event.stopPropagation();
          openBadgeActionMenu(userBadge, {
            assignmentId: Number(slotCardButton.getAttribute('data-assignment-id') || 0),
            userId: Number(slotCardButton.getAttribute('data-user-id') || 0),
            workDate: String(slotCardButton.getAttribute('data-work-date') || ''),
          }, event);
          return;
        }
      }

      var slotCardQuickActions = event.target.closest('.calendar-event-slot-card[data-calendar-slot-toggle]');
      if (slotCardQuickActions && !event.target.closest('.calendar-event-slot-expanded')) {
        event.preventDefault();
        event.stopPropagation();
        openBadgeActionMenu(slotCardQuickActions, {
          assignmentId: Number(slotCardQuickActions.getAttribute('data-assignment-id') || 0),
          userId: Number(slotCardQuickActions.getAttribute('data-user-id') || 0),
          workDate: String(slotCardQuickActions.getAttribute('data-work-date') || ''),
        }, event);
        return;
      }

      var sidebarAssignButton = event.target.closest('[data-calendar-sidebar-assign]');
      if (sidebarAssignButton) {
        event.preventDefault();
        event.stopPropagation();
        var selectedUserId = Number(sidebarAssignButton.getAttribute('data-user-id'));
        var selectedShiftId = Number(sidebarAssignButton.getAttribute('data-shift-id'));
        var selectedWorkDate = String(sidebarAssignButton.getAttribute('data-work-date') || '');
        var selectedUserName = String(sidebarAssignButton.getAttribute('data-user-name') || state.activeUserName || 'Employee');
        if (!selectedUserId || !selectedShiftId || !selectedWorkDate || typeof assignShift !== 'function') {
          notifyError('Missing assignment context.');
          return;
        }
        if (isPastDateKey(selectedWorkDate)) {
          notifyError('Past days are locked and cannot be edited.');
          return;
        }
        if (typeof isUserAvailableForDate === 'function' && !isUserAvailableForDate(selectedUserId, selectedWorkDate)) {
          var unavailableReason = (typeof getUserAvailabilityStatus === 'function')
            ? String((getUserAvailabilityStatus(selectedUserId, selectedWorkDate) || {}).reason || '')
            : '';
          notifyError(unavailableReason || (selectedUserName + ' is unavailable on ' + selectedWorkDate + '.'));
          return;
        }

        (async function () {
          try {
            await assignShift({
              user_id: selectedUserId,
              shift_id: selectedShiftId,
              work_date: selectedWorkDate,
              status: 'assigned',
            });
            notifySuccess(selectedUserName + ' assigned successfully.');
          } catch (error) {
            notifyError((error && error.message) || 'Unable to assign employee to this open shift.');
          }
        })();
        return;
      }

      var unassignButton = event.target.closest('[data-calendar-unassign]');
      if (unassignButton) {
        event.preventDefault();
        event.stopPropagation();
        var assignmentToUnassign = Number(unassignButton.getAttribute('data-calendar-unassign'));
        var assignmentForUnassign = events.find(function (item) { return Number(item.assignment_id) === assignmentToUnassign; });
        if (assignmentForUnassign && isPastDateKey(String(assignmentForUnassign.work_date || ''))) {
          notifyError('Past days are locked and cannot be edited.');
          return;
        }
        if (assignmentToUnassign && typeof unassignAssignment === 'function') {
          (async function () {
            try {
              await unassignAssignment(assignmentToUnassign);
              notifySuccess('Shift unassigned successfully.');
            } catch (error) {
              notifyError((error && error.message) || 'Unable to unassign shift.');
            }
          })();
        }
        return;
      }

      var slotToggle = event.target.closest('[data-calendar-slot-toggle]');
      if (slotToggle) {
        event.preventDefault();
        var assignmentCardForSlot = slotToggle.closest('.calendar-event');
        if (!assignmentCardForSlot) return;
        if (assignmentCardForSlot.getAttribute('data-is-past-day') === '1') {
          return;
        }
        var slotUserId = slotToggle.getAttribute('data-user-id') || '';

        calendarShell.querySelectorAll('.calendar-event-slot-expanded').forEach(function (panel) {
          var parentCard = panel.closest('.calendar-event');
          var panelUserId = panel.getAttribute('data-user-id') || '';
          var isSameCard = parentCard === assignmentCardForSlot && panelUserId === slotUserId;
          panel.hidden = isSameCard ? !panel.hidden : true;
          if (!isSameCard && parentCard) {
            parentCard.classList.remove('is-slot-expanded');
            var button = parentCard.querySelector('[data-calendar-slot-toggle]');
            if (button) button.setAttribute('aria-expanded', 'false');
          }
        });

        var ownPanel = slotUserId
          ? assignmentCardForSlot.querySelector('.calendar-event-slot-expanded[data-user-id="' + slotUserId + '"]')
          : assignmentCardForSlot.querySelector('.calendar-event-slot-expanded');
        var isExpanded = ownPanel ? !ownPanel.hidden : false;
        assignmentCardForSlot.classList.toggle('is-slot-expanded', isExpanded);
        slotToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        return;
      }

        var slotSetAbsenceButton = event.target.closest('[data-calendar-slot-set-absence]');
        if (slotSetAbsenceButton) {
          event.preventDefault();
          var absenceKind = String(slotSetAbsenceButton.getAttribute('data-calendar-slot-set-absence') || '').toLowerCase();
          var absenceUserId = Number(slotSetAbsenceButton.getAttribute('data-user-id') || 0);
          var absenceDate = String(slotSetAbsenceButton.getAttribute('data-work-date') || '');
          var absenceShiftId = getAbsenceTemplateShiftId ? Number(getAbsenceTemplateShiftId(absenceKind) || 0) : 0;
          if (!absenceShiftId || !absenceUserId || !absenceDate || typeof assignShift !== 'function') {
            notifyError('Missing absence assignment context.');
            return;
          }
          if (isPastDateKey(absenceDate)) {
            notifyError('Past days are locked and cannot be edited.');
            return;
          }

          (async function () {
            try {
              await assignShift({
                user_id: absenceUserId,
                shift_id: absenceShiftId,
                work_date: absenceDate,
                status: 'assigned',
                force_override: true,
              });
              notifySuccess('Absence forced successfully.');
            } catch (error) {
              notifyError((error && error.message) || 'Unable to force absence assignment.');
            }
          })();
          return;
        }

        var slotForceWorkButton = event.target.closest('[data-calendar-slot-force-active-shift]');
        if (slotForceWorkButton) {
          event.preventDefault();
          var activeShift = getActiveShift ? (getActiveShift() || null) : null;
          var activeWorkShiftId = Number(activeShift && String(activeShift.kind || '').toLowerCase() === 'work' ? (activeShift.id || 0) : 0);
          var forceUserId = Number(slotForceWorkButton.getAttribute('data-user-id') || 0);
          var forceDate = String(slotForceWorkButton.getAttribute('data-work-date') || '');
          if (!activeWorkShiftId || !forceUserId || !forceDate || typeof assignShift !== 'function') {
            notifyError('Select a work shift in sidebar before forcing work assignment.');
            return;
          }
          if (isPastDateKey(forceDate)) {
            notifyError('Past days are locked and cannot be edited.');
            return;
          }

          (async function () {
            try {
              await assignShift({
                user_id: forceUserId,
                shift_id: activeWorkShiftId,
                work_date: forceDate,
                status: 'assigned',
                force_override: true,
              });
              notifySuccess('Work shift forced successfully.');
            } catch (error) {
              notifyError((error && error.message) || 'Unable to force work shift assignment.');
            }
          })();
          return;
        }

      var assignOtherDatesButton = event.target.closest('[data-calendar-assign-other-dates]');
      if (assignOtherDatesButton) {
        event.preventDefault();
        var assignmentIdForDuplicate = Number(assignOtherDatesButton.getAttribute('data-calendar-assign-other-dates'));
        var sourceAssignment = events.find(function (item) { return Number(item.assignment_id) === assignmentIdForDuplicate; });
        if (!sourceAssignment || !Number(sourceAssignment.user_id || 0) || !Number(sourceAssignment.shift_id || 0) || typeof assignShift !== 'function') {
          return;
        }
        if (isPastDateKey(String(sourceAssignment.work_date || ''))) {
          notifyError('Past days are locked and cannot be edited.');
          return;
        }

        bulkAssignModal.open(sourceAssignment);
        return;
      }

      if (event.target.closest('[data-calendar-slot-panel]')) {
        return;
      }

      var dateCard = event.target.closest('[data-calendar-date]');
      if (dateCard) {
        var dateValue = dateCard.getAttribute('data-calendar-date');
        if (dateValue) {
          openDate(toLocalDate(dateValue));
          dayFullscreenModal.open(dateValue);
        }
        return;
      }

      var assignmentCard = event.target.closest('[data-assignment-id]');
      if (assignmentCard) {
        var assignmentId = Number(assignmentCard.getAttribute('data-assignment-id'));
        var assignment = events.find(function (item) { return Number(item.assignment_id) === assignmentId; });
        if (assignment && assignment.work_date) {
          openDate(toLocalDate(assignment.work_date));
          dayFullscreenModal.open(String(assignment.work_date || ''));
        }
      }
    });
  }

  window.DashboardCalendar = {
    init: initCalendarInteractions,
  };
})();
