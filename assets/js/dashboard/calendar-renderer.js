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

    var renderAssignmentCard = function (event, compact) {
      var assignee = event.user_name || 'Unassigned';
      var departmentName = event.department_name || 'Department';
      return '\n        <article class="calendar-event' + (compact ? ' is-compact' : '') + '" data-assignment-id="' + (event.assignment_id || '') + '" draggable="true">\n          <span class="calendar-event-time">' + formatEventTime(event) + '</span>\n          <span class="calendar-event-title">' + (event.shift_name || 'Shift') + '</span>\n          <span class="calendar-event-meta">' + departmentName + ' • ' + assignee + (event.status ? ' • ' + event.status : '') + '</span>\n        </article>\n      ';
    };

    var renderDayCard = function (date, options) {
      var key = dateKey(date);
      var dayEvents = (eventsByDate().get(key) || []).slice().sort(function (a, b) {
        return String(a.start_time || '').localeCompare(String(b.start_time || ''));
      });
      var isCurrentDay = key === dateKey(calendarToday);
      var isSelected = key === dateKey(state.selectedDate);
      var isMuted = !!(options && options.muted);
      var dayLabel = new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date).toUpperCase();
      return '\n        <article class="calendar-day-card' + (isCurrentDay ? ' is-current' : '') + (isSelected ? ' is-selected' : '') + (isMuted ? ' is-muted' : '') + '" data-calendar-date="' + key + '" data-date-key="' + key + '">\n          <header class="calendar-day-head">\n            <span class="calendar-day-weekday">' + dayLabel + '</span>\n            <span class="calendar-day-number">' + date.getDate() + '</span>\n          </header>\n          <div class="calendar-day-events">\n            ' + (dayEvents.length ? dayEvents.map(function (event) { return renderAssignmentCard(event, true); }).join('') : '<div class="calendar-empty">Drop a user here to assign a shift</div>') + '\n          </div>\n        </article>\n      ';
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
      var selectedEvents = (eventsByDate().get(key) || []).slice().sort(function (a, b) {
        return String(a.start_time || '').localeCompare(String(b.start_time || ''));
      });
      calendarDetail.innerHTML = '\n        <div class="dashboard-calendar-detail-title">\n          <span>' + fullDateFormatter.format(state.selectedDate) + '</span>\n          <span>' + selectedEvents.length + ' shifts</span>\n        </div>\n        <div class="dashboard-calendar-detail-meta">\n          <span class="dashboard-calendar-detail-chip">' + monthLabelFormatter.format(state.selectedDate) + '</span>\n          <span class="dashboard-calendar-detail-chip">' + dateKey(state.selectedDate) + '</span>\n        </div>\n        <div class="calendar-day-events">\n          ' + (selectedEvents.length ? selectedEvents.map(function (event) { return renderAssignmentCard(event, false); }).join('') : '<div class="dashboard-calendar-detail-empty">No assignments for this date.</div>') + '\n        </div>\n      ';
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
      if (state.mode === 'day') {
        cells.push(renderDayCard(state.selectedDate));
      } else if (state.mode === 'week') {
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