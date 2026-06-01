/**
 * Dashboard drag-and-drop module.
 *
 * Enables dragging user chips to calendar dates to assign shifts, and dragging
 * existing assignments to reschedule. Relies on HTML5 Drag and Drop API and
 * custom data attributes for identifying draggable elements and drop targets.
 * Communicates with the backend via provided callback functions for shift
 * assignment and movement.
 */

(function(){
  var feedback = window.DashboardFeedback;

  function notifyError(message) {
    if (feedback) {
      feedback.error('Oops!', message);
      return;
    }
    alert(message);
  }

  function initCalendarDnd(options) {
    var calendarShell = options.calendarShell;
    var state = options.state;
    var events = options.events || [];
    var getActiveShift = options.getActiveShift;
    var assignShift = options.assignShift;
    var moveShift = options.moveShift;
    var safeParseJson = options.safeParseJson;

    if (!calendarShell) return;

    calendarShell.addEventListener('dragstart', function (event) {
      var userChip = event.target.closest('[data-user-id]');
      if (userChip) {
        state.draggingUserId = userChip.getAttribute('data-user-id');
        state.draggingAssignmentId = null;
        if (event.dataTransfer) {
          event.dataTransfer.setData('text/plain', JSON.stringify({ type: 'user', userId: state.draggingUserId }));
        }
        return;
      }

      var assignmentCard = event.target.closest('[data-assignment-id]');
      if (assignmentCard) {
        state.draggingAssignmentId = assignmentCard.getAttribute('data-assignment-id');
        state.draggingUserId = null;
        if (event.dataTransfer) {
          event.dataTransfer.setData('text/plain', JSON.stringify({ type: 'assignment', assignmentId: state.draggingAssignmentId }));
        }
      }
    });

    calendarShell.addEventListener('dragover', function (event) {
      if (event.target.closest('[data-calendar-date]')) {
        event.preventDefault();
      }
    });

    calendarShell.addEventListener('drop', async function (event) {
      var dateCard = event.target.closest('[data-calendar-date]');
      if (!dateCard) return;
      event.preventDefault();
      var workDate = dateCard.getAttribute('data-calendar-date');
      if (!workDate) return;

      try {
        var data = safeParseJson(
          (event.dataTransfer && (event.dataTransfer.getData('application/json') || event.dataTransfer.getData('text/plain'))) || '{}',
          {}
        );

        if (data.type === 'assignment' && data.assignmentId) {
          var assignment = events.find(function (item) { return Number(item.assignment_id) === Number(data.assignmentId); });
          if (!assignment) return;
          await moveShift({ assignment_id: assignment.assignment_id, work_date: workDate });
          return;
        }

        var activeShift = getActiveShift();
        if (!activeShift) {
          notifyError('Select a shift first.');
          return;
        }

        var userId = data.userId || state.draggingUserId;
        if (!userId) return;
        await assignShift({
          user_id: Number(userId),
          shift_id: Number(activeShift.id),
          work_date: workDate,
          status: 'assigned',
        });
      } catch (error) {
        notifyError((error && error.message) || 'Unable to update assignment.');
      }
    });
  }

  window.DashboardDnd = {
    init: initCalendarDnd,
  };
})();
