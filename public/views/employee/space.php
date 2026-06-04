<!-- Employee space: mobile-first personal area for shifts and attendance. -->
<?php
$shifts = $shifts ?? [];
$todaySignableShifts = $todaySignableShifts ?? [];
$todayTimelineShifts = $todayTimelineShifts ?? [];
$upcomingShifts = $upcomingShifts ?? [];
$currentShiftCard = $currentShiftCard ?? null;
$attendances = $attendances ?? [];
$requiredSignatureIp = trim((string) ($requiredSignatureIp ?? ''));
$clientIp = trim((string) ($clientIp ?? ''));
$isSignatureIpRestricted = (bool) ($isSignatureIpRestricted ?? false);
$isCurrentNetworkAuthorized = (bool) ($isCurrentNetworkAuthorized ?? true);
$employeeDisplayName = trim((string) ($employeeDisplayName ?? 'Employee'));
$employeeDepartmentName = trim((string) ($employeeDepartmentName ?? 'Department'));
$employeeCompanyName = trim((string) ($employeeCompanyName ?? 'StaffEase Pro'));
$employeeUiState = isset($employeeUiState) && is_array($employeeUiState) ? $employeeUiState : [];
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
$shiftKindLabels = [
    'work' => 'Work shift',
    'overtime' => 'Overtime',
    'rest' => 'Rest day',
    'vacation' => 'Vacation',
    'sick' => 'Sick leave',
];
$shiftKindIcons = [
    'work' => '',
    'overtime' => '',
    'rest' => '💤',
    'vacation' => '🏖',
    'sick' => '🤒',
];
$formatLongDate = static function (?string $dateValue): string {
    if (empty($dateValue)) {
        return '--';
    }

    try {
        return (new DateTimeImmutable((string) $dateValue))->format('l, j F Y');
    } catch (Throwable $e) {
        return (string) $dateValue;
    }
};
$formatTimeRange = static function (array $shift): string {
    $start = trim((string) ($shift['start_time'] ?? ''));
    $end = trim((string) ($shift['end_time'] ?? ''));
    if ($start === '' && $end === '') {
        return 'Time not available';
    }
    return ($start !== '' ? substr($start, 0, 5) : '--:--') . ' - ' . ($end !== '' ? substr($end, 0, 5) : '--:--');
};
$employeeInitials = '';
foreach (preg_split('/\s+/', $employeeDisplayName) as $part) {
    if ($part !== '') {
        $employeeInitials .= strtoupper(substr($part, 0, 1));
    }
}
$employeeInitials = $employeeInitials !== '' ? substr($employeeInitials, 0, 2) : 'ST';
$canSignNow = (bool) ($employeeUiState['can_sign_now'] ?? false);
$primaryShift = is_array($currentShiftCard) ? $currentShiftCard : (!empty($todayTimelineShifts) ? $todayTimelineShifts[0] : null);
$primaryShiftStatusText = 'No shift scheduled today';
$primaryActionLabel = 'Check-in';
$primaryActionDisabled = true;
$primaryActionNote = 'No assigned shift available for today.';

if (is_array($primaryShift)) {
    $primaryShiftKind = strtolower(trim((string) ($primaryShift['shift_kind'] ?? 'work')));
    if ($primaryShiftKind === 'rest') {
        $primaryShiftStatusText = 'Rest day scheduled';
        $primaryActionLabel = 'Rest day';
        $primaryActionNote = 'No attendance signature is required for a rest day.';
    } elseif ($primaryShiftKind === 'vacation') {
        $primaryShiftStatusText = 'Vacation day';
        $primaryActionLabel = 'Vacation';
        $primaryActionNote = 'No attendance signature is required during vacation.';
    } elseif ($primaryShiftKind === 'sick') {
        $primaryShiftStatusText = 'Sick leave day';
        $primaryActionLabel = 'Sick leave';
        $primaryActionNote = 'No attendance signature is required during sick leave.';
    } elseif (!empty($primaryShift['attendance_recorded'])) {
        $primaryShiftStatusText = 'Attendance already signed';
        $primaryActionLabel = 'Already checked in';
        $primaryActionNote = 'Your attendance for this shift is already registered.';
    } elseif (!$isCurrentNetworkAuthorized) {
        $primaryShiftStatusText = 'Company Wi-Fi required';
        $primaryActionLabel = 'Wi-Fi required';
        $primaryActionNote = 'Connect to the authorized company Wi-Fi IP before signing.';
    } elseif (!empty($primaryShift['is_sign_window_open'])) {
        $primaryShiftStatusText = 'You can sign now';
        $primaryActionLabel = 'Check-in';
        $primaryActionDisabled = false;
        $primaryActionNote = 'Attendance opens 5 minutes before start and stays available until shift end.';
    } elseif (!empty($primaryShift['is_before_window'])) {
        $minutesUntilOpen = (int) ($primaryShift['minutes_until_open'] ?? 0);
        $primaryShiftStatusText = 'Signature not open yet';
        $primaryActionLabel = 'Available soon';
        $primaryActionNote = $minutesUntilOpen > 0
            ? 'You can sign in about ' . $minutesUntilOpen . ' minute' . ($minutesUntilOpen === 1 ? '' : 's') . '.'
            : 'Attendance opens 5 minutes before the shift starts.';
    } elseif (!empty($primaryShift['is_past_window'])) {
        $primaryShiftStatusText = 'Shift ended';
        $primaryActionLabel = 'Window closed';
        $primaryActionNote = 'Ask your manager if attendance needs a manual correction.';
    } else {
        $primaryShiftStatusText = 'Shift scheduled';
        $primaryActionLabel = 'Select shift';
        $primaryActionNote = 'Open the attendance modal when your signing window is available.';
    }
}
?>
<div class="admin-shell employee-space-shell">
    <section class="employee-staff-bar" aria-label="Staff profile summary">
        <div class="employee-staff-avatar"><?php echo e($employeeInitials); ?></div>
        <div class="employee-staff-meta">
            <strong><?php echo e($employeeDisplayName); ?></strong>
            <span><?php echo e($employeeCompanyName); ?><?php if ($employeeDepartmentName !== ''): ?> • <?php echo e($employeeDepartmentName); ?><?php endif; ?></span>
        </div>
        <div class="employee-staff-status <?php echo $isCurrentNetworkAuthorized ? 'is-connected' : 'is-blocked'; ?>">
            <span class="employee-staff-status-dot" aria-hidden="true"></span>
            <?php echo $isCurrentNetworkAuthorized ? 'Connected' : 'Restricted network'; ?>
        </div>
    </section>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="employee-shift-stage">
        <div class="employee-shift-stage-card">
            <div class="employee-shift-stage-head">
                <div>
                    <span class="employee-stage-eyebrow">Today</span>
                    <h1>Schedule shift today</h1>
                </div>
                <?php if (is_array($primaryShift)): ?>
                    <?php
                        $primaryKind = strtolower(trim((string) ($primaryShift['shift_kind'] ?? 'work')));
                        $primaryKindIcon = $shiftKindIcons[$primaryKind] ?? '';
                        $primaryShiftName = trim((string) ($primaryShift['shift_name'] ?? 'Shift'));
                        $primaryPillText = $primaryKindIcon !== '' ? ($primaryKindIcon . ' ' . $primaryShiftName) : $primaryShiftName;
                    ?>
                    <span class="employee-stage-pill"><?php echo e($primaryPillText); ?></span>
                <?php else: ?>
                    <span class="employee-stage-pill is-muted">No shift</span>
                <?php endif; ?>
            </div>

            <?php if (is_array($primaryShift)): ?>
                <div class="employee-shift-stage-summary">
                    <div>
                        <strong><?php echo e($primaryShiftStatusText); ?></strong>
                        <span><?php echo e($formatLongDate($primaryShift['work_date'] ?? null)); ?></span>
                    </div>
                    <div class="employee-shift-stage-time"><?php echo e($formatTimeRange($primaryShift)); ?></div>
                </div>
                <p class="employee-shift-stage-note"><?php echo e($primaryActionNote); ?></p>
            <?php else: ?>
                <div class="employee-shift-stage-summary">
                    <div>
                        <strong>No shift scheduled</strong>
                        <span>Check back later for new assignments.</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="employee-shift-stage-actions">
                <button
                    type="button"
                    class="employee-shift-stage-action <?php echo $primaryActionDisabled ? 'is-disabled' : 'is-ready'; ?>"
                    data-attendance-modal-open
                    <?php echo $primaryActionDisabled ? 'disabled' : ''; ?>
                >
                    <?php echo e($primaryActionLabel); ?>
                </button>
                <button
                    type="button"
                    class="employee-shift-stage-action is-secondary"
                    data-scroll-target="employee-next-shifts"
                >
                    View next shifts
                </button>
            </div>
        </div>
    </section>

    <section class="employee-network-status-grid">
        <div class="employee-network-status-card <?php echo $isCurrentNetworkAuthorized ? 'is-ok' : 'is-blocked'; ?>">
            <strong>Attendance network policy</strong>
            <?php if ($isSignatureIpRestricted): ?>
                <span>Required Wi-Fi IP: <?php echo e($requiredSignatureIp); ?></span>
                <span>Detected IP: <?php echo e($clientIp !== '' ? $clientIp : 'Not detected'); ?></span>
            <?php else: ?>
                <span>No Wi-Fi IP restriction configured. Attendance can be signed from any network.</span>
            <?php endif; ?>
        </div>
    </section>

    <section class="employee-upcoming-shell" id="employee-next-shifts">
        <div class="employee-upcoming-head">
            <h2>Next shifts</h2>
            <span><?php echo count($upcomingShifts); ?> planned</span>
        </div>
        <div class="employee-upcoming-list">
            <?php if (empty($upcomingShifts)): ?>
                <div class="employee-upcoming-empty">No upcoming shifts assigned.</div>
            <?php endif; ?>
            <?php foreach ($upcomingShifts as $shift): ?>
                <?php
                    $shiftKind = strtolower(trim((string) ($shift['shift_kind'] ?? 'work')));
                    $shiftKindLabel = $shiftKindLabels[$shiftKind] ?? 'Shift';
                    $shiftKindIcon = $shiftKindIcons[$shiftKind] ?? '';
                    $isNonWorkShift = in_array($shiftKind, ['rest', 'vacation', 'sick'], true);
                    $shiftBadge = $shift['shift_name'] ?? 'Shift';
                    $shiftBadgeClass = '';
                    if (($shift['status'] ?? '') === 'cancelled') {
                        $shiftBadge = 'Cancelled';
                        $shiftBadgeClass = 'is-rest';
                    } elseif ($shiftKind === 'rest') {
                        $shiftBadge = '💤 Rest day';
                        $shiftBadgeClass = 'is-rest';
                    } elseif ($shiftKind === 'vacation') {
                        $shiftBadge = '🏖 Vacation';
                        $shiftBadgeClass = 'is-vacation';
                    } elseif ($shiftKind === 'sick') {
                        $shiftBadge = '🤒 Sick leave';
                        $shiftBadgeClass = 'is-sick';
                    } elseif (!empty($shift['attendance_recorded'])) {
                        $shiftBadge = 'Signed';
                        $shiftBadgeClass = 'is-signed';
                    }
                    $shiftCardClass = $isNonWorkShift ? 'is-non-work' : 'is-work';
                    $shiftColor = trim((string) ($shift['shift_color'] ?? '#b58e14'));
                ?>
                <article class="employee-upcoming-item <?php echo e($shiftCardClass); ?>" <?php if (!$isNonWorkShift): ?>style="--employee-shift-color: <?php echo e($shiftColor); ?>;"<?php endif; ?>>
                    <div>
                        <strong><?php echo e($formatLongDate($shift['work_date'] ?? null)); ?></strong>
                        <span><?php echo e($formatTimeRange($shift)); ?></span>
                        <small><?php echo e($shift['department_name'] ?? 'Department'); ?></small>
                        <small><?php echo e(($shiftKindIcon !== '' ? ($shiftKindIcon . ' ') : '') . $shiftKindLabel); ?></small>
                    </div>
                    <span class="employee-upcoming-badge <?php echo e($shiftBadgeClass); ?>"><?php echo e($shiftBadge); ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="employee-detail-grid">
        <article class="admin-card employee-detail-card">
            <div class="employee-card-head">
                <div>
                    <span class="employee-stage-eyebrow">Attendances</span>
                    <h2>My attendances</h2>
                </div>
                <span class="employee-metric-pill"><?php echo count($attendances); ?> records</span>
            </div>
            <div class="table-wrap employee-table-wrap">
                <table class="admin-table employee-table-compact">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Status</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
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
        </article>

    </section>

    <div class="employee-attendance-modal" data-attendance-modal hidden>
        <div class="employee-attendance-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-attendance-title">
            <div class="employee-attendance-dialog-head">
                <div>
                    <span class="employee-stage-eyebrow">Attendance</span>
                    <h3 id="employee-attendance-title">Digital signature</h3>
                    <p>Select your assigned shift and confirm your presence.</p>
                </div>
                <button type="button" class="dashboard-modal-close" data-attendance-modal-close aria-label="Close attendance dialog">&times;</button>
            </div>

            <form method="post" class="admin-form employee-sign-form" data-employee-signature-form>
                <input type="hidden" name="action" value="sign_attendance">
                <input type="hidden" name="signature_data" value="" data-signature-data>

                <label>
                    <span>Today assigned shift</span>
                    <select name="user_shift_id" required <?php echo empty($todaySignableShifts) ? 'disabled' : ''; ?>>
                        <option value="">Select</option>
                        <?php foreach ($todaySignableShifts as $shift): ?>
                            <option value="<?php echo (int) $shift['id']; ?>" <?php echo (is_array($primaryShift) && (int) ($primaryShift['id'] ?? 0) === (int) ($shift['id'] ?? 0)) ? 'selected' : ''; ?>><?php echo e(($shift['shift_name'] ?? 'Shift') . ' - ' . ($shift['department_name'] ?? 'Department') . ' - ' . $formatTimeRange($shift)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="employee-signature-pad-shell">
                    <span>Touchscreen signature</span>
                    <canvas width="520" height="180" data-signature-canvas aria-label="Signature pad"></canvas>
                    <small class="employee-signature-error" data-signature-error></small>
                    <div class="employee-signature-pad-actions">
                        <button type="button" class="admin-action-link admin-action-link-secondary" data-signature-clear>Clear signature</button>
                        <small>Sign with finger on mobile or mouse/stylus on desktop.</small>
                    </div>
                </div>

                <?php if (!$isCurrentNetworkAuthorized): ?>
                    <p class="crud-modal-subtitle">Attendance signing is blocked from this network. Connect to company Wi-Fi and retry.</p>
                <?php endif; ?>

                <div class="employee-attendance-dialog-actions">
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-attendance-modal-close>Cancel</button>
                    <button type="submit" <?php echo (empty($todaySignableShifts) || !$canSignNow) ? 'disabled' : ''; ?>>Sign attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>