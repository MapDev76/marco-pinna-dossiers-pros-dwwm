/**
 * Dashboard calendar interactions module.
 *
 * Handles user interactions with the calendar, including clicking on date cards
 * to open the date view and clicking on assignment cards to open the associated
 * date. Relies on custom data attributes for identifying interactive elements and
 * communicates with the main dashboard logic via provided callback functions.
 */

(function(){
  function initCalendarInteractions(options) {
    var calendarShell = options.calendarShell;
    var events = options.events || [];
    var toLocalDate = options.toLocalDate;
    var openDate = options.openDate;
    var unassignAssignment = options.unassignAssignment;

    if (!calendarShell) return;

    calendarShell.addEventListener('click', function (event) {
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
