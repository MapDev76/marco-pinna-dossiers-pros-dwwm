<!-- Employee space: view your shifts, attendances and personal requests. -->
<?php
$shifts = $shifts ?? [];
$requests = $requests ?? [];
$attendances = $attendances ?? [];
$requestTypeLabels = [
    'shift_coverage' => 'Shift coverage',
    'leave' => 'Leave',
    'permission' => 'Permission',
    'document_signature' => 'Document signature',
    'notification' => 'Notification',
];
$statusLabels = [
    'assigned' => 'Assigned',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'in_progress' => 'In progress',
    'present' => 'Present',
    'absent' => 'Absent',
    'late' => 'Late',
    'early_departure' => 'Early departure',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'read' => 'Read',
    'unread' => 'Unread',
];
?>
<div class="admin-shell">
    <div class="admin-hero">
        <h1>My Employee Space</h1>
        <p>View your shifts, record attendance and create requests.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <h2>Record attendance</h2>
        <form method="post" class="admin-form admin-form-grid">
            <input type="hidden" name="action" value="sign_attendance">
            <label>
                <span>Select a shift</span>
                <select name="user_shift_id" required>
                    <option value="">Select</option>
                    <?php foreach ($shifts as $shift): ?>
                        <option value="<?php echo (int) $shift['id']; ?>"><?php echo e($shift['work_date'] . ' - ' . $shift['shift_name'] . ' - ' . $shift['department_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="form-actions span-2">
                <button type="submit">Record attendance</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Create a request</h2>
        <form method="post" class="admin-form admin-form-grid">
            <input type="hidden" name="action" value="create_request">
            <label>
                <span>Type</span>
                <select name="type" required>
                    <option value="">Select</option>
                    <option value="shift_coverage">Shift coverage</option>
                    <option value="leave">Leave</option>
                    <option value="permission">Permission</option>
                    <option value="document_signature">Document signature</option>
                    <option value="notification">Notification</option>
                </select>
            </label>
            <label>
                <span>Title</span>
                <input type="text" name="title" placeholder="Request summary">
            </label>
            <label class="span-2">
                <span>Message</span>
                <textarea name="message" rows="4" required></textarea>
            </label>
            <div class="form-actions span-2">
                <button type="submit">Submit request</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Mes quarts</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Shift</th>
                        <th>Department</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shifts)): ?>
                        <tr><td colspan="4">No shifts available.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($shifts as $shift): ?>
                        <tr>
                            <td><?php echo e($shift['work_date']); ?></td>
                            <td><?php echo e($shift['shift_name']); ?></td>
                            <td><?php echo e($shift['department_name']); ?></td>
                            <td><?php echo e($statusLabels[$shift['status']] ?? $shift['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <h2>My attendances</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Quart</th>
                        <th>Statut</th>
                        <th>Entrée</th>
                        <th>Sortie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendances)): ?>
                        <tr><td colspan="5">No attendance recorded.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($attendances as $attendance): ?>
                        <tr>
                            <td><?php echo e($attendance['work_date']); ?></td>
                            <td><?php echo e($attendance['shift_name'] ?? '-'); ?></td>
                            <td><?php echo e($statusLabels[$attendance['status']] ?? $attendance['status']); ?></td>
                            <td><?php echo e($attendance['check_in_time'] ?? '-'); ?></td>
                            <td><?php echo e($attendance['check_out_time'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <h2>My requests</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Titre</th>
                        <th>Message</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="5">No requests created.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo e($requestTypeLabels[$request['type']] ?? $request['type']); ?></td>
                            <td><?php echo e($request['title'] ?? '-'); ?></td>
                            <td><?php echo e($request['message']); ?></td>
                            <td><?php echo e($statusLabels[$request['status']] ?? $request['status']); ?></td>
                            <td><?php echo e($request['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>