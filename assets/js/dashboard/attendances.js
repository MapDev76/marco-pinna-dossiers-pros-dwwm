(function initAttendancesManager(global) {
    const app = global.StaffEaseDashboard;
    if (!app || !app.utils || !app.feedback || !app.api || !app.endpoints) {
        return;
    }

    const { toArray, parseEmbeddedJson } = app.utils;
    const { pushSuccess, pushError } = app.feedback;
    const { postJSON } = app.api;
    const { apiDashboard } = app.endpoints;

    const modal = document.querySelector('[data-attendance-employee-modal]');
    if (!modal) {
        return;
    }

    const assignmentCatalog = parseEmbeddedJson('[data-attendance-assignment-catalog]', []);
    const openButtons = toArray(document.querySelectorAll('[data-attendance-employee-open]'));
    const closeButton = modal.querySelector('[data-attendance-employee-close]');
    const titleNode = modal.querySelector('[data-attendance-modal-title]');
    const subtitleNode = modal.querySelector('[data-attendance-modal-subtitle]');
    const assignmentSelect = modal.querySelector('[data-attendance-modal-user-shift]');
    const statusSelect = modal.querySelector('[data-attendance-modal-status]');
    const saveButton = modal.querySelector('[data-attendance-signature-save]');
    const clearButton = modal.querySelector('[data-attendance-signature-clear]');
    const signatureCanvas = modal.querySelector('[data-attendance-signature-canvas]');
    const signatureErrorNode = modal.querySelector('[data-attendance-signature-error]');

    if (!assignmentSelect || !statusSelect || !saveButton || !signatureCanvas) {
        return;
    }

    let currentUserId = 0;
    let currentUserName = '';
    let currentDepartmentName = '';
    let isDrawing = false;
    let hasSignature = false;

    const context = signatureCanvas.getContext('2d');
    if (!context) {
        return;
    }

    context.lineWidth = 2;
    context.lineJoin = 'round';
    context.lineCap = 'round';
    context.strokeStyle = '#111827';

    function resetSignatureCanvas() {
        context.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        hasSignature = false;
        if (signatureErrorNode) {
            signatureErrorNode.textContent = '';
        }
    }

    function openModal(userButton) {
        currentUserId = Number(userButton.getAttribute('data-user-id') || 0);
        currentUserName = (userButton.getAttribute('data-user-name') || '').trim();
        currentDepartmentName = (userButton.getAttribute('data-user-department-name') || '').trim();

        const userAssignments = assignmentCatalog.filter((assignment) => Number(assignment.user_id || 0) === currentUserId);

        assignmentSelect.innerHTML = '<option value="">Select assigned shift</option>';
        userAssignments.forEach((assignment) => {
            const option = document.createElement('option');
            option.value = String(assignment.assignment_id || '');
            const statusText = String(assignment.status || 'assigned');
            const shiftLabel = String(assignment.shift_name || 'Shift');
            const workDate = String(assignment.work_date || '');
            option.textContent = `${workDate} - ${shiftLabel} (${statusText})`;
            assignmentSelect.appendChild(option);
        });

        if (titleNode) {
            titleNode.textContent = `Attendance signature: ${currentUserName || 'Employee'}`;
        }
        if (subtitleNode) {
            subtitleNode.textContent = currentDepartmentName
                ? `${currentDepartmentName} - Select shift and sign to record attendance.`
                : 'Select shift and sign to record attendance.';
        }

        statusSelect.value = 'present';
        resetSignatureCanvas();
        modal.hidden = false;
    }

    function closeModal() {
        modal.hidden = true;
        currentUserId = 0;
        currentUserName = '';
        currentDepartmentName = '';
        assignmentSelect.value = '';
        statusSelect.value = 'present';
        resetSignatureCanvas();
    }

    function pointFromEvent(event) {
        const rect = signatureCanvas.getBoundingClientRect();
        return {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top,
        };
    }

    signatureCanvas.addEventListener('pointerdown', (event) => {
        isDrawing = true;
        signatureCanvas.setPointerCapture(event.pointerId);
        const point = pointFromEvent(event);
        context.beginPath();
        context.moveTo(point.x, point.y);
    });

    signatureCanvas.addEventListener('pointermove', (event) => {
        if (!isDrawing) {
            return;
        }
        const point = pointFromEvent(event);
        context.lineTo(point.x, point.y);
        context.stroke();
        hasSignature = true;
        if (signatureErrorNode) {
            signatureErrorNode.textContent = '';
        }
    });

    function stopDrawing(event) {
        if (!isDrawing) {
            return;
        }
        isDrawing = false;
        try {
            signatureCanvas.releasePointerCapture(event.pointerId);
        } catch (error) {
            // noop
        }
    }

    signatureCanvas.addEventListener('pointerup', stopDrawing);
    signatureCanvas.addEventListener('pointercancel', stopDrawing);
    signatureCanvas.addEventListener('pointerleave', stopDrawing);

    openButtons.forEach((button) => {
        button.addEventListener('click', () => openModal(button));
    });

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    if (clearButton) {
        clearButton.addEventListener('click', resetSignatureCanvas);
    }

    saveButton.addEventListener('click', async () => {
        if (currentUserId <= 0) {
            pushError('Unable to determine user for attendance record.');
            return;
        }

        const selectedShiftId = Number(assignmentSelect.value || 0);
        if (selectedShiftId <= 0) {
            pushError('Select an assigned shift before recording attendance.');
            return;
        }

        if (!hasSignature) {
            if (signatureErrorNode) {
                signatureErrorNode.textContent = 'Digital signature is required.';
            }
            pushError('Digital signature is required.');
            return;
        }

        saveButton.disabled = true;
        try {
            const response = await postJSON(apiDashboard, {
                action: 'record_attendance_signature',
                user_id: currentUserId,
                user_shift_id: selectedShiftId,
                attendance_status: statusSelect.value || 'present',
                signature_data: signatureCanvas.toDataURL('image/png'),
            });

            if (response && response.success === false) {
                pushError(response.error || 'Unable to record attendance.');
                return;
            }

            pushSuccess('Attendance recorded with digital signature.');
            global.setTimeout(() => {
                global.location.reload();
            }, 450);
        } catch (error) {
            pushError(error && error.message ? error.message : 'Unable to record attendance.');
        } finally {
            saveButton.disabled = false;
        }
    });
})(window);
