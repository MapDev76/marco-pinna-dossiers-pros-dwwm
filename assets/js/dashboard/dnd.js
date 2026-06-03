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
    var isUserAvailableForDate = options.isUserAvailableForDate;

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

    calendarShell.addEventListener('dragstart', function (event) {
      var slotCard = event.target.closest('[data-calendar-slot-drag]');
      if (slotCard) {
        if (isPastDateKey(String(slotCard.getAttribute('data-work-date') || ''))) {
          event.preventDefault();
          return;
        }
        state.draggingUserId = slotCard.getAttribute('data-user-id');
        state.draggingAssignmentId = slotCard.getAttribute('data-assignment-id');
        if (event.dataTransfer) {
          event.dataTransfer.setData('text/plain', JSON.stringify({
            type: 'slot',
            assignmentId: state.draggingAssignmentId,
            userId: state.draggingUserId,
            shiftId: slotCard.getAttribute('data-shift-id'),
            workDate: slotCard.getAttribute('data-work-date') || '',
          }));
          event.dataTransfer.effectAllowed = 'move';
        }
        return;
      }

      var userChip = event.target.closest('[data-user-id]');
      if (userChip) {
        state.draggingUserId = userChip.getAttribute('data-user-id');
        state.draggingAssignmentId = null;
        if (event.dataTransfer) {
          event.dataTransfer.setData('text/plain', JSON.stringify({ type: 'user', userId: state.draggingUserId }));
          event.dataTransfer.effectAllowed = 'copyMove';
        }
        return;
      }

      var assignmentCard = event.target.closest('[data-assignment-id]');
      if (assignmentCard) {
        if (isPastDateKey(String(assignmentCard.getAttribute('data-work-date') || ''))) {
          event.preventDefault();
          return;
        }
        state.draggingAssignmentId = assignmentCard.getAttribute('data-assignment-id');
        state.draggingUserId = null;
        if (event.dataTransfer) {
          event.dataTransfer.setData('text/plain', JSON.stringify({ type: 'assignment', assignmentId: state.draggingAssignmentId }));
          event.dataTransfer.effectAllowed = 'move';
        }
      }
    });

    calendarShell.addEventListener('dragover', function (event) {
      if (event.target.closest('[data-calendar-date]') || event.target.closest('[data-assignment-id]')) {
        event.preventDefault();
        var targetEvent = event.target.closest('[data-assignment-id]');
        if (targetEvent) {
          targetEvent.classList.add('is-drag-target');
        }
      }
    });

    calendarShell.addEventListener('dragleave', function (event) {
      var targetEvent = event.target.closest('[data-assignment-id]');
      if (targetEvent) {
        targetEvent.classList.remove('is-drag-target');
      }
    });

    calendarShell.addEventListener('drop', async function (event) {
      calendarShell.querySelectorAll('.calendar-event.is-drag-target').forEach(function (node) {
        node.classList.remove('is-drag-target');
      });

      var targetAssignmentCard = event.target.closest('[data-assignment-id]');
      var dateCard = event.target.closest('[data-calendar-date]');
      if (!dateCard && !targetAssignmentCard) return;
      event.preventDefault();
      var workDate = targetAssignmentCard ? targetAssignmentCard.closest('[data-calendar-date]')?.getAttribute('data-calendar-date') : dateCard.getAttribute('data-calendar-date');
      if (!workDate) return;
      if (isPastDateKey(workDate)) {
        notifyError('Past days are locked and cannot be edited.');
        return;
      }

      try {
        var data = safeParseJson(
          (event.dataTransfer && (event.dataTransfer.getData('application/json') || event.dataTransfer.getData('text/plain'))) || '{}',
          {}
        );

        if (data.type === 'slot') {
          if (!targetAssignmentCard) {
            return;
          }
          var targetAssignmentId = Number(targetAssignmentCard.getAttribute('data-assignment-id'));
          var targetAssignment = events.find(function (item) { return Number(item.assignment_id) === targetAssignmentId; });
          if (!targetAssignment) return;
          if (typeof isUserAvailableForDate === 'function' && !isUserAvailableForDate(Number(data.userId || 0), String(targetAssignment.work_date || workDate))) {
            notifyError('This employee is unavailable on ' + String(targetAssignment.work_date || workDate) + '.');
            return;
          }
          await assignShift({
            user_id: Number(data.userId || 0),
            shift_id: Number(targetAssignment.shift_id || 0),
            work_date: String(targetAssignment.work_date || workDate),
            status: 'assigned',
          });
          return;
        }

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
        if (typeof isUserAvailableForDate === 'function' && !isUserAvailableForDate(Number(userId), workDate)) {
          notifyError('This employee is unavailable on ' + workDate + '.');
          return;
        }
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
