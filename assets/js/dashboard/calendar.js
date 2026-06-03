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

  function notifyError(message) {
    if (feedback && typeof feedback.error === 'function') {
      feedback.error('Oops!', message);
      return;
    }
    window.alert(message);
  }

  function notifySuccess(message) {
    if (feedback && typeof feedback.success === 'function') {
      feedback.success('Done', message);
      return;
    }
  }

  function createBulkAssignModal(assignShift, events) {
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

  function initCalendarInteractions(options) {
    var calendarShell = options.calendarShell;
    var events = options.events || [];
    var toLocalDate = options.toLocalDate;
    var openDate = options.openDate;
    var unassignAssignment = options.unassignAssignment;
    var assignShift = options.assignShift;
    var bulkAssignModal = createBulkAssignModal(assignShift, events);

    if (!calendarShell) return;

    calendarShell.addEventListener('click', function (event) {
      var slotToggle = event.target.closest('[data-calendar-slot-toggle]');
      if (slotToggle) {
        event.preventDefault();
        var assignmentCardForSlot = slotToggle.closest('.calendar-event');
        if (!assignmentCardForSlot) return;

        calendarShell.querySelectorAll('.calendar-event-slot-expanded').forEach(function (panel) {
          var parentCard = panel.closest('.calendar-event');
          var isSameCard = parentCard === assignmentCardForSlot;
          panel.hidden = isSameCard ? !panel.hidden : true;
          if (!isSameCard && parentCard) {
            parentCard.classList.remove('is-slot-expanded');
            var button = parentCard.querySelector('[data-calendar-slot-toggle]');
            if (button) button.setAttribute('aria-expanded', 'false');
          }
        });

        var ownPanel = assignmentCardForSlot.querySelector('.calendar-event-slot-expanded');
        var isExpanded = ownPanel ? !ownPanel.hidden : false;
        assignmentCardForSlot.classList.toggle('is-slot-expanded', isExpanded);
        slotToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
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

        bulkAssignModal.open(sourceAssignment);
        return;
      }

      if (event.target.closest('[data-calendar-slot-panel]')) {
        return;
      }

      var unassignButton = event.target.closest('[data-calendar-unassign]');
      if (unassignButton) {
        event.preventDefault();
        var assignmentToUnassign = Number(unassignButton.getAttribute('data-calendar-unassign'));
        if (assignmentToUnassign && typeof unassignAssignment === 'function') {
          unassignAssignment(assignmentToUnassign);
        }
        return;
      }

      var dateCard = event.target.closest('[data-calendar-date]');
      if (dateCard) {
        var dateValue = dateCard.getAttribute('data-calendar-date');
        if (dateValue) openDate(toLocalDate(dateValue));
        return;
      }

      var assignmentCard = event.target.closest('[data-assignment-id]');
      if (assignmentCard) {
        var assignmentId = Number(assignmentCard.getAttribute('data-assignment-id'));
        var assignment = events.find(function (item) { return Number(item.assignment_id) === assignmentId; });
        if (assignment && assignment.work_date) {
          openDate(toLocalDate(assignment.work_date));
        }
      }
    });
  }

  window.DashboardCalendar = {
    init: initCalendarInteractions,
  };
})();
