(() => {
    const apiUrl = window.DashboardConfig?.apiDashboard;
    const feedback = window.DashboardFeedback;
    const employeeModal = document.querySelector('[data-attendance-employee-modal]');
    const recordModal = document.querySelector('[data-attendance-record-modal]');

    if (!apiUrl || !window.AppAPI || !employeeModal) return;

    function notifyError(message) {
        if (feedback?.error) {
            feedback.error('Oops!', message);
            return;
        }
        console.error(message);
    }

    function notifySuccess(message) {
        if (feedback?.success) {
            feedback.success('Done', message);
        }
    }

    async function confirmAction(message, title = 'Confirm action') {
        if (feedback?.confirm) {
            return feedback.confirm(message, title);
        }
        notifyError('Confirmation dialog is not available.');
        return false;
    }

    function parseEmbeddedJson(selector, fallback) {
        const node = document.querySelector(selector);
        if (!node) return fallback;
        try {
            const parsed = JSON.parse(node.textContent || 'null');
            return Array.isArray(fallback) ? (Array.isArray(parsed) ? parsed : fallback) : (parsed || fallback);
        } catch (_error) {
            return fallback;
        }
    }

    function toTimeInputValue(value) {
        const raw = String(value || '').trim();
        return raw ? raw.slice(0, 5) : '';
    }

    const assignmentCatalog = parseEmbeddedJson('[data-attendance-assignment-catalog]', []);
    const attendanceRecordCatalog = parseEmbeddedJson('[data-attendance-record-catalog]', []);
    const openButtons = Array.from(document.querySelectorAll('[data-attendance-employee-open]'));
    const editButtons = Array.from(document.querySelectorAll('[data-attendance-record-edit]'));
    const cancelButtons = Array.from(document.querySelectorAll('[data-attendance-record-delete]'));

    const employeeCloseButton = employeeModal.querySelector('[data-attendance-employee-close]');
    const employeeTitleNode = employeeModal.querySelector('[data-attendance-modal-title]');
    const employeeSubtitleNode = employeeModal.querySelector('[data-attendance-modal-subtitle]');
    const assignmentSelect = employeeModal.querySelector('[data-attendance-modal-user-shift]');
    const statusSelect = employeeModal.querySelector('[data-attendance-modal-status]');
    const saveSignatureButton = employeeModal.querySelector('[data-attendance-signature-save]');
    const clearSignatureButton = employeeModal.querySelector('[data-attendance-signature-clear]');
    const signatureCanvas = employeeModal.querySelector('[data-attendance-signature-canvas]');
    const signatureErrorNode = employeeModal.querySelector('[data-attendance-signature-error]');
    const context = signatureCanvas?.getContext('2d');

    if (!assignmentSelect || !statusSelect || !saveSignatureButton || !signatureCanvas || !context) return;

    context.lineWidth = 2;
    context.lineJoin = 'round';
    context.lineCap = 'round';
    context.strokeStyle = '#111827';

    let currentUserId = 0;
    let currentUserName = '';
    let currentDepartmentName = '';
    let activeAttendanceId = 0;
    let isDrawing = false;
    let hasSignature = false;

    function resetSignatureCanvas() {
        context.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        hasSignature = false;
        if (signatureErrorNode) signatureErrorNode.textContent = '';
    }

    function closeEmployeeModal() {
        employeeModal.hidden = true;
        currentUserId = 0;
        currentUserName = '';
        currentDepartmentName = '';
        assignmentSelect.value = '';
        statusSelect.value = 'present';
        resetSignatureCanvas();
    }

    function openEmployeeModal(button) {
        currentUserId = Number(button.getAttribute('data-user-id') || 0);
        currentUserName = (button.getAttribute('data-user-name') || '').trim();
        currentDepartmentName = (button.getAttribute('data-user-department-name') || '').trim();

        const userAssignments = assignmentCatalog.filter((assignment) => Number(assignment.user_id || 0) === currentUserId);
        assignmentSelect.innerHTML = '<option value="">Select assigned shift</option>';
        userAssignments.forEach((assignment) => {
            const option = document.createElement('option');
            option.value = String(assignment.assignment_id || '');
            option.textContent = `${assignment.work_date || ''} - ${assignment.shift_name || 'Shift'} (${assignment.status || 'assigned'})`;
            assignmentSelect.appendChild(option);
        });

        if (employeeTitleNode) {
            employeeTitleNode.textContent = `Attendance signature: ${currentUserName || 'Employee'}`;
        }
        if (employeeSubtitleNode) {
            employeeSubtitleNode.textContent = currentDepartmentName
                ? `${currentDepartmentName} - Select shift and sign to record attendance.`
                : 'Select shift and sign to record attendance.';
        }

        statusSelect.value = 'present';
        resetSignatureCanvas();
        employeeModal.hidden = false;
    }

    function closeRecordModal() {
        if (!recordModal) return;
        recordModal.hidden = true;
        activeAttendanceId = 0;
    }

    function openRecordModal(attendanceId) {
        if (!recordModal) return;
        const record = attendanceRecordCatalog.find((item) => Number(item.id || 0) === Number(attendanceId));
        if (!record) {
            notifyError('Attendance record not found.');
            return;
        }

        activeAttendanceId = Number(record.id || 0);
        const titleNode = recordModal.querySelector('[data-attendance-record-title]');
        const subtitleNode = recordModal.querySelector('[data-attendance-record-subtitle]');
        const statusField = recordModal.querySelector('[data-attendance-record-status]');
        const checkInField = recordModal.querySelector('[data-attendance-record-checkin]');
        const checkOutField = recordModal.querySelector('[data-attendance-record-checkout]');

        if (titleNode) titleNode.textContent = `Edit attendance: ${record.user_name || 'Employee'}`;
        if (subtitleNode) subtitleNode.textContent = `${record.work_date || ''} - ${record.shift_name || 'Shift'} - ${record.department_name || 'Department'}`;
        if (statusField) statusField.value = record.status || 'present';
        if (checkInField) checkInField.value = toTimeInputValue(record.check_in_time);
        if (checkOutField) checkOutField.value = toTimeInputValue(record.check_out_time);

        recordModal.hidden = false;
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
        if (!isDrawing) return;
        const point = pointFromEvent(event);
        context.lineTo(point.x, point.y);
        context.stroke();
        hasSignature = true;
        if (signatureErrorNode) signatureErrorNode.textContent = '';
    });

    function stopDrawing(event) {
        if (!isDrawing) return;
        isDrawing = false;
        try {
            signatureCanvas.releasePointerCapture(event.pointerId);
        } catch (_error) {
            // noop
        }
    }

    signatureCanvas.addEventListener('pointerup', stopDrawing);
    signatureCanvas.addEventListener('pointercancel', stopDrawing);
    signatureCanvas.addEventListener('pointerleave', stopDrawing);

    openButtons.forEach((button) => {
        button.addEventListener('click', () => openEmployeeModal(button));
    });

    if (employeeCloseButton) {
        employeeCloseButton.addEventListener('click', closeEmployeeModal);
    }

    employeeModal.addEventListener('click', (event) => {
        if (event.target === employeeModal) closeEmployeeModal();
    });

    if (clearSignatureButton) {
        clearSignatureButton.addEventListener('click', resetSignatureCanvas);
    }

    saveSignatureButton.addEventListener('click', async () => {
        if (currentUserId <= 0) {
            notifyError('Unable to determine user for attendance record.');
            return;
        }

        const selectedShiftId = Number(assignmentSelect.value || 0);
        if (selectedShiftId <= 0) {
            notifyError('Select an assigned shift before recording attendance.');
            return;
        }

        if (!hasSignature) {
            if (signatureErrorNode) signatureErrorNode.textContent = 'Digital signature is required.';
            notifyError('Digital signature is required.');
            return;
        }

        saveSignatureButton.disabled = true;
        try {
            const response = await AppAPI.postJSON(apiUrl, {
                action: 'record_attendance_signature',
                user_id: currentUserId,
                user_shift_id: selectedShiftId,
                attendance_status: statusSelect.value || 'present',
                signature_data: signatureCanvas.toDataURL('image/png'),
            });

            if (!response?.ok) {
                notifyError(response?.error || 'Unable to record attendance.');
                return;
            }

            notifySuccess('Attendance recorded with digital signature.');
            globalThis.setTimeout(() => globalThis.location.reload(), 450);
        } catch (error) {
            notifyError(error?.message || 'Unable to record attendance.');
        } finally {
            saveSignatureButton.disabled = false;
        }
    });

    if (!recordModal) return;

    const recordCloseButtons = Array.from(recordModal.querySelectorAll('[data-attendance-record-close]'));
    const recordSaveButton = recordModal.querySelector('[data-attendance-record-save]');
    const recordCancelButton = recordModal.querySelector('[data-attendance-record-cancel-registration]');
    const recordStatusField = recordModal.querySelector('[data-attendance-record-status]');
    const recordCheckInField = recordModal.querySelector('[data-attendance-record-checkin]');
    const recordCheckOutField = recordModal.querySelector('[data-attendance-record-checkout]');

    editButtons.forEach((button) => {
        button.addEventListener('click', () => {
            openRecordModal(Number(button.getAttribute('data-attendance-id') || 0));
        });
    });

    async function cancelAttendance(attendanceId) {
        if (!attendanceId) return;
        const canProceed = await confirmAction('Cancel this attendance registration?', 'Confirm cancellation');
        if (!canProceed) return;
        const response = await AppAPI.postJSON(apiUrl, {
            action: 'cancel_attendance',
            attendance_id: attendanceId,
        });
        if (!response?.ok) {
            notifyError(response?.error || 'Unable to cancel attendance.');
            return;
        }
        notifySuccess('Attendance registration cancelled.');
        globalThis.setTimeout(() => globalThis.location.reload(), 450);
    }

    cancelButtons.forEach((button) => {
        button.addEventListener('click', () => {
            cancelAttendance(Number(button.getAttribute('data-attendance-id') || 0));
        });
    });

    recordCloseButtons.forEach((button) => {
        button.addEventListener('click', closeRecordModal);
    });

    recordModal.addEventListener('click', (event) => {
        if (event.target === recordModal) closeRecordModal();
    });

    if (recordSaveButton && recordStatusField && recordCheckInField && recordCheckOutField) {
        recordSaveButton.addEventListener('click', async () => {
            if (!activeAttendanceId) {
                notifyError('Attendance record not selected.');
                return;
            }
            recordSaveButton.disabled = true;
            try {
                const response = await AppAPI.postJSON(apiUrl, {
                    action: 'update_attendance',
                    attendance_id: activeAttendanceId,
                    attendance_status: recordStatusField.value || 'present',
                    check_in_time: recordCheckInField.value || '',
                    check_out_time: recordCheckOutField.value || '',
                });
                if (!response?.ok) {
                    notifyError(response?.error || 'Unable to update attendance.');
                    return;
                }
                notifySuccess('Attendance updated successfully.');
                globalThis.setTimeout(() => globalThis.location.reload(), 450);
            } catch (error) {
                notifyError(error?.message || 'Unable to update attendance.');
            } finally {
                recordSaveButton.disabled = false;
            }
        });
    }

    if (recordCancelButton) {
        recordCancelButton.addEventListener('click', () => {
            cancelAttendance(activeAttendanceId);
        });
    }
})();
