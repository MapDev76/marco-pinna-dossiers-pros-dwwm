/*
    * Dashboard calendar navigator module.
    * Manages the calendar navigator panel, including toggling visibility, switching
    * between calendar modes (day, week, month, etc.), and navigating to previous/next
    * periods or today. Relies on custom data attributes for identifying interactive
    * elements and communicates with the main dashboard logic via provided callback
    * functions for rendering the calendar and updating the UI.
 */

(function(){
  function initNavigator(options) {
    var navigatorPanel = options.navigatorPanel;
    var calendarToday = options.calendarToday;
    var state = options.state;
    var calendarShell = options.calendarShell;
    var plannerData = options.plannerData || {};
    var toLocalDate = options.toLocalDate;
    var addDays = options.addDays;
    var renderCalendar = options.renderCalendar;
    var updateChrome = options.updateChrome;

    document.querySelectorAll('[data-calendar-mode]').forEach(function (button) {
      button.addEventListener('click', function () {
        var mode = button.getAttribute('data-calendar-mode');
        if (!mode) return;
        if (mode === 'day') {
          state.mode = 'week';
          state.navigationMode = 'day';
        } else {
          state.mode = mode;
          state.navigationMode = mode;
        }
        if (typeof renderCalendar === 'function') renderCalendar();
      });
    });

    document.querySelectorAll('[data-calendar-nav]').forEach(function (button) {
      button.addEventListener('click', function () {
        var action = button.getAttribute('data-calendar-nav');
        var navMode = state.navigationMode || state.mode;
        var stepByDay = state.mode === 'week';
        if (action === 'prev') {
          if (navMode === 'year') state.focusDate = new Date(state.focusDate.getFullYear() - 1, 0, 1, 12, 0, 0, 0);
          else if (navMode === 'month') state.focusDate = new Date(state.focusDate.getFullYear(), state.focusDate.getMonth() - 1, 1, 12, 0, 0, 0);
          else if (stepByDay) state.focusDate = addDays(state.focusDate, -1);
          else if (navMode === 'fortnight') state.focusDate = addDays(state.focusDate, -14);
          else if (navMode === 'week') state.focusDate = addDays(state.focusDate, -7);
          else state.focusDate = addDays(state.focusDate, -1);
        }
        if (action === 'next') {
          if (navMode === 'year') state.focusDate = new Date(state.focusDate.getFullYear() + 1, 0, 1, 12, 0, 0, 0);
          else if (navMode === 'month') state.focusDate = new Date(state.focusDate.getFullYear(), state.focusDate.getMonth() + 1, 1, 12, 0, 0, 0);
          else if (stepByDay) state.focusDate = addDays(state.focusDate, 1);
          else if (navMode === 'fortnight') state.focusDate = addDays(state.focusDate, 14);
          else if (navMode === 'week') state.focusDate = addDays(state.focusDate, 7);
          else state.focusDate = addDays(state.focusDate, 1);
        }
        if (action === 'today') {
          var today = toLocalDate(calendarShell ? calendarShell.getAttribute('data-calendar-today') : plannerData.today);
          state.focusDate = new Date(today);
          state.selectedDate = new Date(today);
          state.calendarExpanded = false;
          document.body.classList.remove('calendar-expanded');
        }
        if (typeof renderCalendar === 'function') renderCalendar();
      });
    });

    if (navigatorPanel) {
      navigatorPanel.classList.remove('is-open');
      navigatorPanel.setAttribute('aria-hidden', 'true');
    }
    if (typeof updateChrome === 'function') updateChrome();
  }

  window.DashboardNavigator = {
    init: initNavigator,
  };
})();
