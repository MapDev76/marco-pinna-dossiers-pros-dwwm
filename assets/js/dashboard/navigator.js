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
    var navigatorToggleButtons = options.navigatorToggleButtons || [];
    var calendarToday = options.calendarToday;
    var state = options.state;
    var calendarShell = options.calendarShell;
    var plannerData = options.plannerData || {};
    var toLocalDate = options.toLocalDate;
    var addDays = options.addDays;
    var renderCalendar = options.renderCalendar;
    var updateChrome = options.updateChrome;

    var toggleNavigator = function () {
      if (!navigatorPanel) return;
      navigatorPanel.hidden = !navigatorPanel.hidden;
      if (typeof updateChrome === 'function') updateChrome();
    };

    navigatorToggleButtons.forEach(function (button) {
      button.addEventListener('click', function () { toggleNavigator(); });
    });

    document.querySelectorAll('#dashboard-calendar-navigator [data-calendar-mode]').forEach(function (button) {
      button.addEventListener('click', function () {
        var mode = button.getAttribute('data-calendar-mode');
        if (!mode) return;
        state.mode = mode;
        if (mode === 'day') {
          state.selectedDate = new Date(calendarToday);
          state.focusDate = new Date(calendarToday);
        }
        if (typeof renderCalendar === 'function') renderCalendar();
      });
    });

    document.querySelectorAll('#dashboard-calendar-navigator [data-calendar-nav]').forEach(function (button) {
      button.addEventListener('click', function () {
        var action = button.getAttribute('data-calendar-nav');
        if (action === 'prev') {
          if (state.mode === 'year') state.focusDate = new Date(state.focusDate.getFullYear() - 1, 0, 1, 12, 0, 0, 0);
          else if (state.mode === 'month') state.focusDate = new Date(state.focusDate.getFullYear(), state.focusDate.getMonth() - 1, 1, 12, 0, 0, 0);
          else if (state.mode === 'fortnight') state.focusDate = addDays(state.focusDate, -14);
          else if (state.mode === 'week') state.focusDate = addDays(state.focusDate, -7);
          else state.focusDate = addDays(state.focusDate, -1);
        }
        if (action === 'next') {
          if (state.mode === 'year') state.focusDate = new Date(state.focusDate.getFullYear() + 1, 0, 1, 12, 0, 0, 0);
          else if (state.mode === 'month') state.focusDate = new Date(state.focusDate.getFullYear(), state.focusDate.getMonth() + 1, 1, 12, 0, 0, 0);
          else if (state.mode === 'fortnight') state.focusDate = addDays(state.focusDate, 14);
          else if (state.mode === 'week') state.focusDate = addDays(state.focusDate, 7);
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

    var navigatorCloseBtn = document.querySelector('.dashboard-calendar-navigator-close');
    if (navigatorCloseBtn && navigatorPanel) {
      navigatorCloseBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        navigatorPanel.hidden = true;
        if (typeof updateChrome === 'function') updateChrome();
      });
    }

    if (navigatorPanel) {
      navigatorPanel.addEventListener('click', function (e) {
        if (e.target === navigatorPanel) {
          navigatorPanel.hidden = true;
          if (typeof updateChrome === 'function') updateChrome();
        }
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && navigatorPanel && !navigatorPanel.hidden) {
          navigatorPanel.hidden = true;
          if (typeof updateChrome === 'function') updateChrome();
        }
      });
    }
  }

  window.DashboardNavigator = {
    init: initNavigator,
  };
})();
