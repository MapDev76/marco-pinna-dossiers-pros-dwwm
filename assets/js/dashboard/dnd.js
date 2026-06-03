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
    var getUserAvailabilityStatus = options.getUserAvailabilityStatus;

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

    var dropHint = document.createElement('div');
    dropHint.className = 'calendar-drop-hint';
    dropHint.hidden = true;
    document.body.appendChild(dropHint);

    var hideDropHint = function () {
      dropHint.hidden = true;
    };

    var updateDropHint = function (event, dropContext) {
      var targetAssignmentCard = dropContext && dropContext.targetAssignmentCard ? dropContext.targetAssignmentCard : null;
      var dateCard = dropContext && dropContext.dateCard ? dropContext.dateCard : null;
      var workDate = String(targetAssignmentCard && targetAssignmentCard.getAttribute('data-work-date') || dateCard && dateCard.getAttribute('data-calendar-date') || '');
      if (!workDate) {
        hideDropHint();
        return;
      }

      var shiftLabel = '';
      if (targetAssignmentCard) {
        shiftLabel = String(targetAssignmentCard.querySelector('.calendar-event-title') && targetAssignmentCard.querySelector('.calendar-event-title').textContent || '').trim();
      }
      if (!shiftLabel) {
        var activeShift = getActiveShift && getActiveShift();
        shiftLabel = String(activeShift && activeShift.name || 'Shift');
      }

      dropHint.textContent = 'Drop on: ' + shiftLabel + ' - ' + workDate;
      dropHint.style.left = String(event.clientX + 14) + 'px';
      dropHint.style.top = String(event.clientY + 14) + 'px';
      dropHint.hidden = false;
    };

    var clearDropHighlights = function () {
      calendarShell.querySelectorAll('.calendar-event.is-drag-target').forEach(function (node) {
        node.classList.remove('is-drag-target');
      });
      calendarShell.querySelectorAll('.calendar-day-card.is-drop-target').forEach(function (node) {
        node.classList.remove('is-drop-target');
      });
      hideDropHint();
    };

    var pickNearestEventCard = function (dateCard, clientY) {
      if (!dateCard) return null;
      var cards = Array.from(dateCard.querySelectorAll('.calendar-event'));
      if (!cards.length) return null;
      if (!Number.isFinite(clientY)) return cards[0];
      var bestCard = cards[0];
      var bestDistance = Number.POSITIVE_INFINITY;
      cards.forEach(function (card) {
        var rect = card.getBoundingClientRect();
        var distance = 0;
        if (clientY < rect.top) {
          distance = rect.top - clientY;
        } else if (clientY > rect.bottom) {
          distance = clientY - rect.bottom;
        }
        if (distance < bestDistance) {
          bestDistance = distance;
          bestCard = card;
        }
      });
      return bestCard;
    };

    var resolveDropContext = function (event) {
      var pointerTarget = null;
      if (typeof document.elementFromPoint === 'function' && Number.isFinite(event.clientX) && Number.isFinite(event.clientY)) {
        pointerTarget = document.elementFromPoint(event.clientX, event.clientY);
      }
      var rawTarget = pointerTarget || event.target;
      var targetAssignmentCard = rawTarget && rawTarget.closest ? rawTarget.closest('.calendar-event') : null;
      var dateCard = rawTarget && rawTarget.closest ? rawTarget.closest('[data-calendar-date]') : null;
      if (!dateCard && event.target && event.target.closest) {
        dateCard = event.target.closest('[data-calendar-date]');
      }
      if (!targetAssignmentCard && dateCard) {
        targetAssignmentCard = pickNearestEventCard(dateCard, event.clientY);
      }
      return {
        targetAssignmentCard: targetAssignmentCard,
        dateCard: dateCard,
      };
    };

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
      var dropContext = resolveDropContext(event);
      if (!dropContext.dateCard && !dropContext.targetAssignmentCard) return;
      event.preventDefault();
      clearDropHighlights();
      if (dropContext.targetAssignmentCard) {
        dropContext.targetAssignmentCard.classList.add('is-drag-target');
      }
      if (dropContext.dateCard) {
        dropContext.dateCard.classList.add('is-drop-target');
      }
      updateDropHint(event, dropContext);
    });

    calendarShell.addEventListener('dragleave', function (event) {
      var related = event.relatedTarget;
      if (!related || !calendarShell.contains(related)) {
        clearDropHighlights();
      }
    });

    calendarShell.addEventListener('dragend', function () {
      clearDropHighlights();
    });

    calendarShell.addEventListener('drop', async function (event) {
      var dropContext = resolveDropContext(event);
      clearDropHighlights();
      var targetAssignmentCard = dropContext.targetAssignmentCard;
      var dateCard = dropContext.dateCard;
      if (!dateCard && !targetAssignmentCard) return;
      event.preventDefault();
      var workDate = String(targetAssignmentCard ? (targetAssignmentCard.getAttribute('data-work-date') || '') : (dateCard.getAttribute('data-calendar-date') || ''));
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
          var activeShiftForDrop = getActiveShift();
          var sourceShiftId = Number(data.shiftId || 0);
          var targetShiftId = Number(targetAssignmentCard && targetAssignmentCard.getAttribute('data-shift-id') || 0);
          if (!targetShiftId) {
            targetShiftId = Number(activeShiftForDrop && activeShiftForDrop.id || 0) || sourceShiftId;
          }
          if (!targetShiftId) return;
          if (typeof isUserAvailableForDate === 'function' && !isUserAvailableForDate(Number(data.userId || 0), String(workDate))) {
            var reason = (typeof getUserAvailabilityStatus === 'function')
              ? String((getUserAvailabilityStatus(Number(data.userId || 0), String(workDate)) || {}).reason || '')
              : '';
            notifyError(reason || ('This employee is unavailable on ' + String(workDate) + '.'));
            return;
          }
          var sourceAssignmentId = Number(data.assignmentId || 0);
          if (sourceAssignmentId > 0) {
            await moveShift({
              assignment_id: sourceAssignmentId,
              user_id: Number(data.userId || 0),
              shift_id: targetShiftId,
              work_date: String(workDate),
              status: 'assigned',
            });
          } else {
            await assignShift({
              user_id: Number(data.userId || 0),
              shift_id: targetShiftId,
              work_date: String(workDate),
              status: 'assigned',
            });
          }
          return;
        }

        if (data.type === 'assignment' && data.assignmentId) {
          var targetShiftForMove = Number(targetAssignmentCard && targetAssignmentCard.getAttribute('data-shift-id') || 0);
          await moveShift({ assignment_id: Number(data.assignmentId), shift_id: targetShiftForMove || undefined, work_date: workDate });
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
          var unavailable = (typeof getUserAvailabilityStatus === 'function')
            ? String((getUserAvailabilityStatus(Number(userId), workDate) || {}).reason || '')
            : '';
          notifyError(unavailable || ('This employee is unavailable on ' + workDate + '.'));
          return;
        }
        var targetShiftFromCard = Number(targetAssignmentCard && targetAssignmentCard.getAttribute('data-shift-id') || 0);
        await assignShift({
          user_id: Number(userId),
          shift_id: targetShiftFromCard > 0 ? targetShiftFromCard : Number(activeShift.id),
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
