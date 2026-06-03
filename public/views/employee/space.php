<!-- Employee space: view your shifts, attendances and personal requests. -->
<?php
$shifts = $shifts ?? [];
$todaySignableShifts = $todaySignableShifts ?? [];
$requests = $requests ?? [];
$attendances = $attendances ?? [];
$requiredSignatureIp = trim((string) ($requiredSignatureIp ?? ''));
$clientIp = trim((string) ($clientIp ?? ''));
$isSignatureIpRestricted = (bool) ($isSignatureIpRestricted ?? false);
$isCurrentNetworkAuthorized = (bool) ($isCurrentNetworkAuthorized ?? true);
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
<div class="admin-shell employee-space-shell">
    <div class="admin-hero employee-space-hero">
        <h1>My Employee Space</h1>
        <p>Only your personal shifts are visible here. Attendance is signed with touchscreen.</p>

        <div class="employee-network-status-grid">
            <div class="employee-network-status-card <?php echo $isCurrentNetworkAuthorized ? 'is-ok' : 'is-blocked'; ?>">
                <strong>Network access</strong>
                <?php if ($isSignatureIpRestricted): ?>
                    <span>Required company Wi-Fi IP: <?php echo e($requiredSignatureIp); ?></span>
                    <span>Your connection IP: <?php echo e($clientIp !== '' ? $clientIp : 'Not detected'); ?></span>
                <?php else: ?>
                    <span>No Wi-Fi IP restriction configured. Attendance can be signed from any network.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="admin-card employee-sign-card">
        <h2>Sign today attendance</h2>
        <?php if (empty($todaySignableShifts)): ?>
            <p class="crud-modal-subtitle">No shift assigned to you for today.</p>
        <?php endif; ?>

        <form method="post" class="admin-form employee-sign-form" data-employee-signature-form>
            <input type="hidden" name="action" value="sign_attendance">
            <input type="hidden" name="signature_data" value="" data-signature-data>

            <label>
                <span>Today assigned shift</span>
                <select name="user_shift_id" required <?php echo empty($todaySignableShifts) ? 'disabled' : ''; ?>>
                    <option value="">Select</option>
                    <?php foreach ($todaySignableShifts as $shift): ?>
                        <option value="<?php echo (int) $shift['id']; ?>"><?php echo e($shift['work_date'] . ' - ' . $shift['shift_name'] . ' - ' . $shift['department_name'] . ' (' . ($shift['start_time'] ?? '--:--') . ' - ' . ($shift['end_time'] ?? '--:--') . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="employee-signature-pad-shell">
                <span>Touchscreen signature</span>
                <canvas width="520" height="180" data-signature-canvas aria-label="Signature pad"></canvas>
                <div class="employee-signature-pad-actions">
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-signature-clear>Clear signature</button>
                    <small>Sign with finger on mobile or mouse/stylus on desktop.</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" <?php echo (empty($todaySignableShifts) || !$isCurrentNetworkAuthorized) ? 'disabled' : ''; ?>>Sign attendance</button>
            </div>
        </form>

        <?php if (!$isCurrentNetworkAuthorized): ?>
            <p class="crud-modal-subtitle">Attendance signing is blocked from this network. Connect to company Wi-Fi and retry.</p>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <h2>My shifts</h2>
        <?php if (empty($shifts)): ?>
            <p class="crud-modal-subtitle">No shifts available.</p>
        <?php endif; ?>
        <div class="employee-shift-cards">
            <?php foreach ($shifts as $shift): ?>
                <article class="employee-shift-card">
                    <header>
                        <strong><?php echo e($shift['work_date']); ?></strong>
                        <span><?php echo e($statusLabels[$shift['status']] ?? $shift['status']); ?></span>
                    </header>
                    <p><?php echo e($shift['shift_name']); ?> | <?php echo e($shift['department_name']); ?></p>
                    <small><?php echo e(($shift['start_time'] ?? '--:--') . ' - ' . ($shift['end_time'] ?? '--:--')); ?></small>
                </article>
            <?php endforeach; ?>
        </div>
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