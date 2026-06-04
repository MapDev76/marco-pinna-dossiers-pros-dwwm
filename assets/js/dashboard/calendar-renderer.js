/**
 * Dashboard calendar renderer module.
 *
 * Produces all calendar HTML fragments and exposes `renderCalendar` used by
 * the dashboard coordinator.
 */

(function(){
  function createCalendarRenderer(options) {
    var state = options.state;
    var events = options.events || [];
    var calendarToday = options.calendarToday;
    var calendarShell = options.calendarShell;
    var calendarDetail = options.calendarDetail;
    var monthNames = options.monthNames || [];
    var addDays = options.addDays;
    var dateKey = options.dateKey;
    var startOfWeek = options.startOfWeek;
    var toLocalDate = options.toLocalDate;
    var pad = options.pad;
    var formatEventTime = options.formatEventTime;
    var fullDateFormatter = options.fullDateFormatter;
    var monthLabelFormatter = options.monthLabelFormatter;
    var updateChrome = options.updateChrome;
    var getActiveDepartment = options.getActiveDepartment;
    var isUserAvailableForDate = options.isUserAvailableForDate;
    var getUserAvailabilityStatus = options.getUserAvailabilityStatus;
    var getVisibleDateKeys = options.getVisibleDateKeys;
    var todayKey = dateKey(calendarToday);

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    var getVisibleEvents = function () {
      var activeDepartment = typeof getActiveDepartment === 'function' ? getActiveDepartment() : null;
      if (!activeDepartment || !activeDepartment.id) {
        return events;
      }

      return events.filter(function (event) {
        return Number(event.department_id || 0) === Number(activeDepartment.id);
      });
    };

    var eventsByDate = function () {
      return getVisibleEvents().reduce(function (map, event) {
        var key = event.work_date || '';
        if (!key) return map;
        if (!map.has(key)) map.set(key, []);
        map.get(key).push(event);
        return map;
      }, new Map());
    };

    var groupEventsByShiftDate = function (items) {
      return items.reduce(function (groups, event) {
        var shiftKey = String(event.shift_id || '') + '|' + String(event.work_date || '');
        if (!shiftKey || shiftKey === '|') return groups;
        if (!groups.has(shiftKey)) {
          groups.set(shiftKey, {
            key: shiftKey,
            base: event,
            events: [],
          });
        }
        groups.get(shiftKey).events.push(event);
        return groups;
      }, new Map());
    };

    var renderAssignmentCard = function (group, compact) {
      var baseEvent = group.base || group.events[0] || {};
      var shiftKind = (baseEvent.shift_kind || 'work').toLowerCase();
      var isVirtual = !!baseEvent.is_virtual_open;
      var shiftColor = baseEvent.shift_color || '#2f6fed';
      var shiftId = Number(baseEvent.shift_id || 0);
      var assignmentId = Number(baseEvent.assignment_id || 0);
      var activeUserId = Number(state.activeUserId || 0);
      var activeShiftId = Number(state.activeShiftId || 0);
      var activeUserName = (state.activeUserName || '').trim() || 'this employee';
      var badge = (baseEvent.shift_icon ? '<span class="calendar-event-badge" style="color: ' + shiftColor + '">' + (baseEvent.shift_icon || '') + '</span>' : '');
      var assignedEmployees = (group.events || []).filter(function (event) {
        return Number(event.user_id || 0) > 0;
      });
      var isOpenSlot = assignedEmployees.length === 0 && shiftKind === 'work';
      var isPastSlot = String(baseEvent.work_date || '') < String(todayKey || '');
      var isSidebarAssignCandidate = isOpenSlot
        && !isPastSlot
        && activeUserId > 0
        && shiftId > 0
        && shiftId === activeShiftId
        && (function () {
          if (typeof getVisibleDateKeys !== 'function') return true;
          var key = String(baseEvent.work_date || '');
          if (!key) return false;
          var keys = getVisibleDateKeys() || [];
          return keys.indexOf(key) >= 0;
        })();
      var availabilityStatus = isSidebarAssignCandidate
        ? (typeof getUserAvailabilityStatus === 'function'
          ? (getUserAvailabilityStatus(activeUserId, String(baseEvent.work_date || '')) || { available: true, reason: '' })
          : { available: (typeof isUserAvailableForDate !== 'function' || isUserAvailableForDate(activeUserId, String(baseEvent.work_date || ''))), reason: '' })
        : { available: true, reason: '' };
      var isSidebarAssignableTarget = isSidebarAssignCandidate && availabilityStatus.available;
      var sidebarUnavailableReason = (!availabilityStatus.available && availabilityStatus.reason)
        ? String(availabilityStatus.reason)
        : 'Unavailable.';
      var employeeSlot = '';

      if (assignedEmployees.length > 0) {
        employeeSlot = '\n            <div class="calendar-event-slot-stack">' + assignedEmployees.map(function (employeeEvent) {
          var employeeId = Number(employeeEvent.user_id || 0);
          var employeeAssignmentId = Number(employeeEvent.assignment_id || 0);
          var employeeName = (employeeEvent.user_name || '').trim();
          var employeeInitials = employeeName.split(' ').filter(Boolean).map(function (chunk) { return chunk.charAt(0).toUpperCase(); }).slice(0, 2).join('');
          var employeeBadge = employeeInitials ? '<span class="calendar-event-user-badge" style="--event-user-color:' + shiftColor + '; background:' + shiftColor + '; color:#ffffff;">' + employeeInitials + '</span>' : '';
          var employeeFullName = employeeName || 'Employee';
          var employeeLabel = '<span class="calendar-event-user-fullname">' + employeeFullName + '</span>';
          if (isPastSlot) {
            return '\n              <span class="calendar-event-slot-card is-locked" title="Past day locked">\n                ' + employeeBadge + employeeLabel + '\n              </span>\n            ';
          }
          return '\n              <button type="button" class="calendar-event-slot-card is-drag-source" data-calendar-slot-toggle data-calendar-slot-drag draggable="true" data-assignment-id="' + employeeAssignmentId + '" data-user-id="' + employeeId + '" data-shift-id="' + shiftId + '" data-work-date="' + (employeeEvent.work_date || '') + '" aria-expanded="false" title="' + employeeFullName + '">\n                ' + employeeBadge + employeeLabel + '\n              </button>\n              <div class="calendar-event-slot-expanded" data-calendar-slot-panel data-user-id="' + employeeId + '" hidden>\n                <span class="calendar-event-slot-name">' + employeeFullName + '</span>\n                <div class="calendar-event-slot-actions">\n                  <button type="button" class="calendar-event-slot-btn" data-calendar-assign-other-dates="' + employeeAssignmentId + '" data-user-id="' + employeeId + '" data-shift-id="' + shiftId + '" title="Assign this employee to other dates" aria-label="Assign this employee to other dates">+</button>\n                  <button type="button" class="calendar-event-slot-btn" data-calendar-slot-set-absence="rest" data-user-id="' + employeeId + '" data-work-date="' + (employeeEvent.work_date || '') + '">💤</button>\n                  <button type="button" class="calendar-event-slot-btn" data-calendar-slot-set-absence="vacation" data-user-id="' + employeeId + '" data-work-date="' + (employeeEvent.work_date || '') + '">🏖</button>\n                  <button type="button" class="calendar-event-slot-btn" data-calendar-slot-set-absence="sick" data-user-id="' + employeeId + '" data-work-date="' + (employeeEvent.work_date || '') + '">🤒</button>\n                  <button type="button" class="calendar-event-slot-btn" data-calendar-slot-force-active-shift="1" data-user-id="' + employeeId + '" data-work-date="' + (employeeEvent.work_date || '') + '" title="Force assign selected shift">⏱</button>\n                  <button type="button" class="calendar-event-slot-btn is-danger" data-calendar-unassign="' + employeeAssignmentId + '" aria-label="Unassign shift" title="Unassign shift">×</button>\n                </div>\n              </div>\n            ';
        }).join('') + '\n            </div>';
      } else if (isOpenSlot) {
        employeeSlot = isSidebarAssignableTarget
          ? '\n            <div class="calendar-event-slot-stack">\n              <button type="button" class="calendar-event-slot-empty is-sidebar-assign-prompt" data-calendar-sidebar-assign data-user-id="' + activeUserId + '" data-user-name="' + escapeHtml(activeUserName) + '" data-shift-id="' + shiftId + '" data-work-date="' + escapeHtml(baseEvent.work_date || '') + '" aria-label="Assign ' + escapeHtml(activeUserName) + ' to this open shift">\n                <span class="calendar-event-slot-empty-label">Open shift</span>\n                <span class="calendar-event-slot-empty-prompt">Assign ' + escapeHtml(activeUserName) + '?</span>\n              </button>\n            </div>'
          : (isSidebarAssignCandidate
            ? '\n            <div class="calendar-event-slot-stack">\n              <span class="calendar-event-slot-empty is-sidebar-unavailable" title="' + escapeHtml(sidebarUnavailableReason) + '">\n                <span class="calendar-event-slot-empty-label">Open shift</span>\n                <span class="calendar-event-slot-empty-prompt">' + escapeHtml(sidebarUnavailableReason) + '</span>\n              </span>\n            </div>'
            : '\n            <div class="calendar-event-slot-stack">\n              <span class="calendar-event-slot-empty">Open shift</span>\n            </div>');
      }

      return '\n        <article class="calendar-event' + (compact ? ' is-compact' : '') + (shiftKind !== 'work' ? ' is-nonwork is-kind-' + shiftKind : '') + ((assignedEmployees.length === 0) ? ' is-open' : '') + (isSidebarAssignableTarget ? ' is-sidebar-assign-target' : '') + ((!isSidebarAssignableTarget && isSidebarAssignCandidate) ? ' is-sidebar-unavailable-target' : '') + (isPastSlot ? ' is-past' : '') + '"' + (assignmentId > 0 ? ' data-assignment-id="' + assignmentId + '"' : '') + ' data-shift-id="' + shiftId + '" data-work-date="' + escapeHtml(baseEvent.work_date || '') + '" data-department-id="' + Number(baseEvent.department_id || 0) + '" data-department-name="' + escapeHtml(baseEvent.department_name || '') + '" data-shift-kind="' + escapeHtml(shiftKind) + '" data-is-open-slot="' + (isOpenSlot ? '1' : '0') + '" data-is-past-day="' + (isPastSlot ? '1' : '0') + '" draggable="' + ((!isPastSlot && !isVirtual && assignmentId > 0) ? 'true' : 'false') + '" style="--event-shift-color:' + shiftColor + '">\n          <div class="calendar-event-main-row">\n            <div class="calendar-event-shift-row">\n              <div class="calendar-event-top">' + badge + '</div>\n              <span class="calendar-event-time">' + formatEventTime(baseEvent) + '</span>\n              <span class="calendar-event-title">' + (baseEvent.shift_name || 'Shift') + '</span>\n            </div>\n            ' + employeeSlot + '\n          </div>\n        </article>\n      ';
    };

    var renderDayCard = function (date, options) {
      var key = dateKey(date);
      var dayEvents = (eventsByDate().get(key) || []).slice();
      var activeDepartment = typeof getActiveDepartment === 'function' ? getActiveDepartment() : null;
      var shiftTemplates = activeDepartment && Array.isArray(activeDepartment.shifts) ? activeDepartment.shifts : [];
      var scheduledShiftIds = {};

      dayEvents.forEach(function (event) {
        scheduledShiftIds[String(event.shift_id || '')] = true;
      });

      shiftTemplates.forEach(function (shift) {
        var shiftId = Number(shift.id || 0);
        var shiftKind = String(shift.kind || 'work').toLowerCase();
        if (!shiftId || shiftKind !== 'work' || scheduledShiftIds[String(shiftId)]) return;

        dayEvents.push({
          assignment_id: 0,
          work_date: key,
          status: 'open',
          shift_id: shiftId,
          shift_name: shift.name || 'Shift',
          shift_icon: shift.icon || '🕒',
          shift_color: shift.color || '#2f6fed',
          shift_kind: shift.kind || 'work',
          start_time: shift.start_time || null,
          end_time: shift.end_time || null,
          department_id: activeDepartment ? activeDepartment.id : 0,
          department_name: activeDepartment ? activeDepartment.name : 'Department',
          department_color: activeDepartment ? activeDepartment.color : null,
          user_id: 0,
          user_name: '',
          assignment_source: 'open',
          is_virtual_open: true,
        });
      });

      var groupedEvents = Array.from(groupEventsByShiftDate(dayEvents).values()).sort(function (a, b) {
        return String(a.base.start_time || '').localeCompare(String(b.base.start_time || ''));
      });
      var isCurrentDay = key === dateKey(calendarToday);
      var isSelected = key === dateKey(state.selectedDate);
      var isPastDay = key < todayKey;
      var isMuted = !!(options && options.muted);
      var dayLabel = new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date).toUpperCase();

      return '\n        <article class="calendar-day-card' + (isCurrentDay ? ' is-current' : '') + (isSelected ? ' is-selected' : '') + (isPastDay ? ' is-past' : '') + (isMuted ? ' is-muted' : '') + '" data-calendar-date="' + key + '" data-date-key="' + key + '">\n          <header class="calendar-day-head">\n            <span class="calendar-day-weekday">' + dayLabel + '</span>\n            <span class="calendar-day-number">' + date.getDate() + '</span>\n          </header>\n          <div class="calendar-day-events">\n            ' + (groupedEvents.length ? groupedEvents.map(function (group) { return renderAssignmentCard(group, true); }).join('') : '<div class="calendar-empty">Drop a user here to assign a shift</div>') + '\n          </div>\n        </article>\n      ';
    };

    var renderYearCard = function (monthIndex) {
      var monthDate = new Date(state.focusDate.getFullYear(), monthIndex, 1, 12, 0, 0, 0);
      var monthStart = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1, 12, 0, 0, 0);
      var monthEnd = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0, 12, 0, 0, 0);
      var monthEvents = getVisibleEvents().filter(function (event) {
        var date = toLocalDate(event.work_date);
        return date >= monthStart && date <= monthEnd;
      });
      var topEvents = monthEvents.slice(0, 2);
      return '\n        <article class="calendar-year-card' + (monthIndex === calendarToday.getMonth() ? ' is-current' : '') + '" data-calendar-date="' + monthDate.getFullYear() + '-' + pad(monthIndex + 1) + '-01">\n          <header class="calendar-year-head">\n            <span class="calendar-year-label">' + monthNames[monthIndex] + '</span>\n            <span class="calendar-year-number">' + monthEvents.length + '</span>\n          </header>\n          <div class="calendar-year-events">\n            ' + (topEvents.length ? topEvents.map(function (event) {
              return '\n              <div class="calendar-year-summary">\n                <span class="calendar-year-summary-title">' + event.work_date + '</span>\n                <span class="calendar-year-summary-meta">' + (event.shift_name || 'Shift') + (event.user_name ? ' • ' + event.user_name : '') + '</span>\n              </div>\n            ';
            }).join('') : '<div class="calendar-empty">No assignments</div>') + '\n          </div>\n        </article>\n      ';
    };

    var renderDetail = function () {
      if (!calendarDetail) return;
      var key = dateKey(state.selectedDate);
      var selectedEvents = Array.from(groupEventsByShiftDate((eventsByDate().get(key) || []).slice()).values()).sort(function (a, b) {
        return String(a.base.start_time || '').localeCompare(String(b.base.start_time || ''));
      });
      calendarDetail.innerHTML = '\n        <div class="dashboard-calendar-detail-title">\n          <span>' + fullDateFormatter.format(state.selectedDate) + '</span>\n          <span>' + selectedEvents.length + ' shifts</span>\n        </div>\n        <div class="dashboard-calendar-detail-meta">\n          <span class="dashboard-calendar-detail-chip">' + monthLabelFormatter.format(state.selectedDate) + '</span>\n          <span class="dashboard-calendar-detail-chip">' + dateKey(state.selectedDate) + '</span>\n        </div>\n        <div class="calendar-day-events">\n          ' + (selectedEvents.length ? selectedEvents.map(function (group) { return renderAssignmentCard(group, false); }).join('') : '<div class="dashboard-calendar-detail-empty">No assignments for this date.</div>') + '\n        </div>\n      ';
    };

    var renderCalendar = function () {
      if (!calendarShell) return;
      if (typeof updateChrome === 'function') updateChrome();
      renderDetail();

      if (state.mode === 'year') {
        calendarShell.innerHTML = monthNames.map(function (_, index) { return renderYearCard(index); }).join('');
        return;
      }

      var cells = [];
      if (state.mode === 'week') {
        var startWeek = startOfWeek(state.focusDate);
        for (var weekIndex = 0; weekIndex < 7; weekIndex += 1) {
          cells.push(renderDayCard(addDays(startWeek, weekIndex)));
        }
      } else if (state.mode === 'fortnight') {
        var startFortnight = startOfWeek(state.focusDate);
        for (var dayIndex = 0; dayIndex < 15; dayIndex += 1) {
          cells.push(renderDayCard(addDays(startFortnight, dayIndex)));
        }
      } else {
        var firstDay = startOfWeek(new Date(state.focusDate.getFullYear(), state.focusDate.getMonth(), 1, 12, 0, 0, 0));
        var endDay = addDays(startOfWeek(new Date(state.focusDate.getFullYear(), state.focusDate.getMonth() + 1, 0, 12, 0, 0, 0)), 6);
        for (var cursor = new Date(firstDay); cursor <= endDay; cursor = addDays(cursor, 1)) {
          cells.push(renderDayCard(cursor, { muted: cursor.getMonth() !== state.focusDate.getMonth() }));
        }
      }

      calendarShell.innerHTML = cells.join('');
    };

    return {
      renderCalendar: renderCalendar,
      renderDetail: renderDetail,
      renderDayCard: renderDayCard,
      renderYearCard: renderYearCard,
      renderAssignmentCard: renderAssignmentCard,
    };
  }

  window.DashboardCalendarRenderer = {
    create: createCalendarRenderer,
  };
})();