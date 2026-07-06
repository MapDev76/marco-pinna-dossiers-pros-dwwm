<!-- Employee space: mobile-first personal area for shifts and attendance. -->
<?php
$shifts = $shifts ?? [];
$todaySignableShifts = $todaySignableShifts ?? [];
$todayTimelineShifts = $todayTimelineShifts ?? [];
$upcomingShifts = $upcomingShifts ?? [];
$currentShiftCard = $currentShiftCard ?? null;
$attendances = $attendances ?? [];
$incomingDocuments = $incomingDocuments ?? [];
$archivedDocuments = $archivedDocuments ?? [];
$outgoingDocuments = $outgoingDocuments ?? [];
$requiredSignatureIp = trim((string) ($requiredSignatureIp ?? ''));
$clientIp = trim((string) ($clientIp ?? ''));
$basePath = $basePath ?? (function () {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
})();
$isSignatureIpRestricted = (bool) ($isSignatureIpRestricted ?? false);
$isCurrentNetworkAuthorized = (bool) ($isCurrentNetworkAuthorized ?? true);
$employeeDisplayName = trim((string) ($employeeDisplayName ?? t('employee.default_name')));
$employeeDepartmentName = trim((string) ($employeeDepartmentName ?? t('employee.default_department')));
$employeeCompanyName = trim((string) ($employeeCompanyName ?? 'StaffEase Pro'));
$employeeUiState = isset($employeeUiState) && is_array($employeeUiState) ? $employeeUiState : [];
$statusLabels = [
    'assigned' => t('employee.status_assigned', ['fallback' => 'Assigned']),
    'completed' => t('employee.status_completed', ['fallback' => 'Completed']),
    'cancelled' => t('employee.status_cancelled', ['fallback' => 'Cancelled']),
    'in_progress' => t('employee.status_in_progress', ['fallback' => 'In progress']),
    'present' => t('employee.status_present', ['fallback' => 'Present']),
    'absent' => t('employee.status_absent', ['fallback' => 'Absent']),
    'late' => t('employee.status_late', ['fallback' => 'Late']),
    'early_departure' => t('employee.status_early_departure', ['fallback' => 'Early departure']),
    'pending' => t('employee.status_pending', ['fallback' => 'Pending']),
    'approved' => t('employee.status_approved', ['fallback' => 'Approved']),
    'rejected' => t('employee.status_rejected', ['fallback' => 'Rejected']),
    'read' => t('employee.status_read', ['fallback' => 'Read']),
    'unread' => t('employee.status_unread', ['fallback' => 'Unread']),
];
$shiftKindLabels = [
    'work' => t('employee.kind_work'),
    'overtime' => t('employee.kind_overtime'),
    'rest' => t('employee.kind_rest'),
    'vacation' => t('employee.kind_vacation'),
    'sick' => t('employee.kind_sick'),
];
$shiftKindIcons = [
    'work' => '',
    'overtime' => '',
    'rest' => 'popcorn.svg',
    'vacation' => 'parasol.svg',
    'sick' => 'stethoscope.svg',
];
$renderEmployeeKindIcon = static function (string $iconFile, string $className = 'employee-kind-icon') use ($basePath): string {
    $iconFile = trim($iconFile);
    if ($iconFile === '') {
        return '';
    }

    return '<img src="' . e($basePath . '/assets/icons/' . $iconFile) . '" alt="" aria-hidden="true" class="' . e($className) . '">';
};
$stripLeadingEmoji = static function (string $label): string {
    return trim((string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $label));
};
$formatLongDate = static function (?string $dateValue): string {
    if (empty($dateValue)) {
        return '--';
    }

    try {
        $locale = appLocale();
        $date = new DateTimeImmutable((string) $dateValue);
        if ($locale === 'fr') {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
            return $formatter->format($date);
        } else {
            return $date->format('l, j F Y');
        }
    } catch (Throwable $e) {
        return (string) $dateValue;
    }
};
$formatTimeRange = static function (array $shift): string {
    $start = trim((string) ($shift['start_time'] ?? ''));
    $end = trim((string) ($shift['end_time'] ?? ''));
    if ($start === '' && $end === '') {
        return t('employee.time_not_available');
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
$unreadDocumentsCount = (int) ($employeeUiState['unread_documents_count'] ?? 0);
$primaryShift = is_array($currentShiftCard) ? $currentShiftCard : (!empty($todayTimelineShifts) ? $todayTimelineShifts[0] : null);
$todayDateValue = isset($todayDate) && is_string($todayDate) ? $todayDate : date('Y-m-d');
$todayCardDateLabel = $formatLongDate($todayDateValue);
$todayCardShiftName = is_array($primaryShift)
    ? trim((string) ($primaryShift['shift_name'] ?? t('employee.shift')))
    : '--';
$todayCardShiftTime = is_array($primaryShift)
    ? $formatTimeRange($primaryShift)
    : t('employee.time_not_available');
$hasAssignedShiftToday = is_array($primaryShift)
    && (int) ($primaryShift['id'] ?? 0) > 0
    && trim((string) ($primaryShift['work_date'] ?? '')) === $todayDateValue;
$defaultSignableShiftId = 0;
if (!empty($todaySignableShifts)) {
    $defaultSignableShiftId = (int) ($todaySignableShifts[0]['id'] ?? 0);
} elseif (is_array($primaryShift)) {
    $defaultSignableShiftId = (int) ($primaryShift['id'] ?? 0);
}
$primaryShiftStatusText = t('employee.state_no_shift_today');
$primaryActionLabel = t('employee.action_checkin');
$primaryActionDisabled = true;
$primaryActionNote = t('employee.state_no_shift_note');

if (is_array($primaryShift)) {
    $primaryShiftKind = strtolower(trim((string) ($primaryShift['shift_kind'] ?? 'work')));
    if ($primaryShiftKind === 'rest') {
        $primaryShiftStatusText = t('employee.state_rest_day');
        $primaryActionLabel = t('employee.action_rest');
        $primaryActionNote = t('employee.note_rest_day');
    } elseif ($primaryShiftKind === 'vacation') {
        $primaryShiftStatusText = t('employee.state_vacation_day');
        $primaryActionLabel = t('employee.action_vacation');
        $primaryActionNote = t('employee.note_vacation_day');
    } elseif ($primaryShiftKind === 'sick') {
        $primaryShiftStatusText = t('employee.state_sick_day');
        $primaryActionLabel = t('employee.action_sick');
        $primaryActionNote = t('employee.note_sick_day');
    } elseif (!empty($primaryShift['attendance_recorded'])) {
        $primaryShiftStatusText = t('employee.state_signed');
        $primaryActionLabel = t('employee.action_already_checked');
        $primaryActionNote = t('employee.note_already_signed');
    } elseif (!$isCurrentNetworkAuthorized) {
        $primaryShiftStatusText = t('employee.state_wifi_required');
        $primaryActionLabel = t('employee.action_wifi_required');
        $primaryActionNote = t('employee.note_wifi_required');
    } elseif (!empty($primaryShift['is_sign_window_open'])) {
        $primaryShiftStatusText = t('employee.state_sign_now');
        $primaryActionLabel = t('employee.action_checkin');
        $primaryActionDisabled = false;
        $primaryActionNote = t('employee.note_sign_window');
    } elseif (!empty($primaryShift['is_before_window'])) {
        $minutesUntilOpen = (int) ($primaryShift['minutes_until_open'] ?? 0);
        $primaryShiftStatusText = t('employee.state_not_open');
        $primaryActionLabel = t('employee.action_available_soon');
        $primaryActionNote = $minutesUntilOpen > 0
            ? t('employee.note_window_after', ['minutes' => (string) $minutesUntilOpen])
            : t('employee.note_window_before');
    } elseif (!empty($primaryShift['is_past_window'])) {
        $primaryShiftStatusText = t('employee.state_shift_ended');
        $primaryActionLabel = t('employee.action_window_closed');
        $primaryActionNote = t('employee.note_shift_ended');
    } else {
        $primaryShiftStatusText = t('employee.state_shift_scheduled');
        $primaryActionLabel = t('employee.action_select_shift');
        $primaryActionNote = t('employee.note_select_shift');
    }
}
?>
<div class="admin-shell employee-space-shell">
    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="employee-shift-stage">
        <div class="employee-shift-stage-card">
            <?php if (is_array($primaryShift)): ?>
                <?php
                    $primaryKind = strtolower(trim((string) ($primaryShift['shift_kind'] ?? 'work')));
                    $primaryKindIcon = $shiftKindIcons[$primaryKind] ?? '';
                    $primaryShiftName = trim((string) ($primaryShift['shift_name'] ?? t('employee.shift')));
                    $primaryPillIcon = $renderEmployeeKindIcon($primaryKindIcon, 'employee-kind-icon employee-stage-pill-icon');
                ?>
                <div class="employee-shift-stage-head">
                    <span class="employee-stage-pill"><?php echo $primaryPillIcon; ?><?php echo e($primaryShiftName); ?></span>
                </div>
            <?php endif; ?>

            <?php if (is_array($primaryShift)): ?>
                <div class="employee-shift-stage-summary">
                    <div>
                        <strong><?php echo e($primaryShiftStatusText); ?></strong>
                        <span><?php echo e($formatLongDate($primaryShift['work_date'] ?? null)); ?></span>
                    </div>
                    <div class="employee-shift-stage-time"><?php echo e($formatTimeRange($primaryShift)); ?></div>
                </div>
                <p class="employee-shift-stage-note"><?php echo e($primaryActionNote); ?></p>
            <?php endif; ?>

            <div class="employee-shift-stage-meta-row" aria-label="<?php echo e(t('employee.staff_profile_summary', ['fallback' => 'Daily shift summary'])); ?>">
                <div class="employee-shift-stage-meta-item">
                    <img src="<?php echo $basePath; ?>/assets/icons/calendar-days.svg" alt="" aria-hidden="true" class="employee-shift-stage-meta-icon">
                    <span><strong><?php echo e(t('employee.date')); ?>:</strong> <?php echo e($todayCardDateLabel); ?></span>
                </div>
                <div class="employee-shift-stage-meta-item">
                    <img src="<?php echo $basePath; ?>/assets/icons/briefcase.svg" alt="" aria-hidden="true" class="employee-shift-stage-meta-icon">
                    <span><strong><?php echo e(t('employee.today_assigned_shift')); ?>:</strong> <?php echo e($todayCardShiftName); ?></span>
                </div>
                <div class="employee-shift-stage-meta-item">
                    <img src="<?php echo $basePath; ?>/assets/icons/clock.svg" alt="" aria-hidden="true" class="employee-shift-stage-meta-icon">
                    <span><strong>Horaire:</strong> <?php echo e($todayCardShiftTime); ?></span>
                </div>
                <div class="employee-shift-stage-meta-item">
                    <img src="<?php echo $basePath; ?>/assets/icons/wifi-high.svg" alt="" aria-hidden="true" class="employee-shift-stage-meta-icon" style="color: <?php echo $isCurrentNetworkAuthorized ? 'green' : 'red'; ?>;">
                    <span><strong><?php echo e(t('employee.status_connected')); ?>:</strong> <?php echo e($isCurrentNetworkAuthorized ? t('employee.status_connected') : t('employee.status_restricted_network')); ?></span>
                </div>
            </div>

            <div class="employee-shift-stage-actions">
                <button
                    type="button"
                    class="employee-shift-stage-action <?php echo $primaryActionDisabled ? 'is-disabled' : 'is-ready'; ?>"
                    data-attendance-modal-open
                    data-attendance-allowed="<?php echo (!$primaryActionDisabled && $hasAssignedShiftToday && $isCurrentNetworkAuthorized) ? 'true' : 'false'; ?>"
                    aria-disabled="<?php echo $primaryActionDisabled ? 'true' : 'false'; ?>"
                >
                    <?php echo e($primaryActionLabel); ?>
                </button>
                <button
                    type="button"
                    class="employee-shift-stage-action is-secondary"
                    data-scroll-target="employee-next-shifts"
                >
                    <?php echo e(t('employee.view_next_shifts')); ?>
                </button>
            </div>
        </div>
    </section>

    <section class="employee-network-status-grid">
        <div class="employee-network-status-card <?php echo $isCurrentNetworkAuthorized ? 'is-ok' : 'is-blocked'; ?>">
            <div class="employee-network-status-head">
                <strong><?php echo e(t('employee.network_policy')); ?></strong>
                <span class="site-wifi-status <?php echo $isCurrentNetworkAuthorized ? 'is-connected' : 'is-blocked'; ?>" title="<?php echo e($isCurrentNetworkAuthorized ? t('employee.status_connected') : t('employee.status_restricted_network')); ?>" aria-label="<?php echo e($isCurrentNetworkAuthorized ? t('employee.status_connected') : t('employee.status_restricted_network')); ?>">
                    <img src="<?php echo $basePath; ?>/assets/icons/wifi-high.svg" alt="" aria-hidden="true" class="site-wifi-icon">
                </span>
            </div>
            <?php if (!$isCurrentNetworkAuthorized): ?>
                <span class="employee-network-status-alert"><?php echo e(t('employee.network_blocked')); ?></span>
                <span><?php echo e(t('employee.required_wifi_ip')); ?>: <?php echo e($requiredSignatureIp); ?></span>
                <span><?php echo e(t('employee.detected_ip')); ?>: <?php echo e($clientIp !== '' ? $clientIp : t('employee.ip_not_detected')); ?></span>
            <?php elseif ($isSignatureIpRestricted): ?>
                <span><?php echo e(t('employee.required_wifi_ip')); ?>: <?php echo e($requiredSignatureIp); ?></span>
                <span><?php echo e(t('employee.detected_ip')); ?>: <?php echo e($clientIp !== '' ? $clientIp : t('employee.ip_not_detected')); ?></span>
            <?php else: ?>
                <span><?php echo e(t('employee.no_ip_restriction')); ?></span>
            <?php endif; ?>
        </div>
    </section>
    <section class="employee-upcoming-shell" id="employee-next-shifts">
        <div class="employee-upcoming-head">
            <h2><?php echo e(t('employee.next_shifts')); ?></h2>
            <span><?php echo count($upcomingShifts); ?> <?php echo e(t('employee.planned_suffix')); ?></span>
        </div>
        <div class="employee-upcoming-list">
            <?php if (empty($upcomingShifts)): ?>
                <div class="employee-upcoming-empty"><?php echo e(t('employee.no_upcoming')); ?></div>
            <?php endif; ?>
            <?php foreach ($upcomingShifts as $shift): ?>
                <?php
                    $shiftKind = strtolower(trim((string) ($shift['shift_kind'] ?? 'work')));
                    $shiftKindLabel = $shiftKindLabels[$shiftKind] ?? t('employee.shift');
                    $shiftKindIcon = $shiftKindIcons[$shiftKind] ?? '';
                    $isNonWorkShift = in_array($shiftKind, ['rest', 'vacation', 'sick'], true);
                    $shiftBadge = $shift['shift_name'] ?? t('employee.shift');
                    $shiftBadgeClass = '';
                    if (($shift['status'] ?? '') === 'cancelled') {
                        $shiftBadge = t('employee.badge_cancelled');
                        $shiftBadgeClass = 'is-rest';
                    } elseif ($shiftKind === 'rest') {
                        $shiftBadge = $stripLeadingEmoji((string) t('employee.badge_rest'));
                        $shiftBadgeClass = 'is-rest';
                    } elseif ($shiftKind === 'vacation') {
                        $shiftBadge = $stripLeadingEmoji((string) t('employee.badge_vacation'));
                        $shiftBadgeClass = 'is-vacation';
                    } elseif ($shiftKind === 'sick') {
                        $shiftBadge = $stripLeadingEmoji((string) t('employee.badge_sick'));
                        $shiftBadgeClass = 'is-sick';
                    } elseif (!empty($shift['attendance_recorded'])) {
                        $shiftBadge = t('employee.badge_signed');
                        $shiftBadgeClass = 'is-signed';
                    }
                    $shiftCardClass = $isNonWorkShift ? 'is-non-work' : 'is-work';
                    $shiftColor = trim((string) ($shift['shift_color'] ?? '#b58e14'));
                    $shiftKindIconHtml = $renderEmployeeKindIcon($shiftKindIcon, 'employee-kind-icon employee-upcoming-kind-icon');
                    $shiftBadgeIconHtml = $renderEmployeeKindIcon($shiftKindIcon, 'employee-kind-icon employee-upcoming-badge-icon');
                ?>
                <article class="employee-upcoming-item <?php echo e($shiftCardClass); ?>" <?php if (!$isNonWorkShift): ?>style="--employee-shift-color: <?php echo e($shiftColor); ?>;"<?php endif; ?>>
                    <div>
                        <strong><?php echo e($formatLongDate($shift['work_date'] ?? null)); ?></strong>
                        <span><?php echo e($formatTimeRange($shift)); ?></span>
                    </div>
                    <span class="employee-upcoming-badge <?php echo e($shiftBadgeClass); ?>"><?php echo $shiftBadgeIconHtml; ?><?php echo e($shiftBadge); ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="employee-detail-grid">
        <article class="admin-card employee-detail-card" id="employee-attendance-records">
            <div class="employee-card-head">
                <div>
                    <span class="employee-stage-eyebrow"><?php echo e(t('employee.attendances')); ?></span>
                    <h2><?php echo e(t('employee.my_attendances')); ?></h2>
                </div>
                <span class="employee-metric-pill"><?php echo count($attendances); ?> <?php echo e(t('employee.records_suffix')); ?></span>
            </div>
            <div class="table-wrap employee-table-wrap">
                <table class="admin-table employee-table-compact">
                    <thead>
                        <tr>
                            <th><?php echo e(t('employee.date')); ?></th>
                            <th><?php echo e(t('employee.shift')); ?></th>
                            <th><?php echo e(t('employee.status')); ?></th>
                            <th><?php echo e(t('employee.check_in')); ?></th>
                            <th><?php echo e(t('employee.check_out')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendances)): ?>
                            <tr><td colspan="5"><?php echo e(t('employee.no_attendance')); ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($attendances as $attendance): ?>
                            <tr>
                                <td><?php echo e($attendance['work_date']); ?></td>
                                <td><?php echo e($attendance['shift_name'] ?? '-'); ?></td>
                                <td><?php echo e($statusLabels[(string) ($attendance['status'] ?? '')] ?? $attendance['status']); ?></td>
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
                    <span class="employee-stage-eyebrow"><?php echo e(t('employee.attendance_label')); ?></span>
                    <h3 id="employee-attendance-title"><?php echo e(t('employee.digital_signature')); ?></h3>
                </div>
            </div>

            <form method="post" class="admin-form employee-sign-form" data-employee-signature-form>
                <input type="hidden" name="action" value="sign_attendance">
                <input type="hidden" name="signature_data" value="" data-signature-data>
                <input type="hidden" name="user_shift_id" value="<?php echo (int) $defaultSignableShiftId; ?>">

                <div class="employee-signature-pad-shell">
                    <canvas width="520" height="180" data-signature-canvas aria-label="<?php echo e(t('employee.digital_signature')); ?>"></canvas>
                    <small class="employee-signature-error" data-signature-error></small>
                    <div class="employee-signature-pad-actions">
                        <button type="button" class="admin-action-link admin-action-link-secondary" data-signature-clear><?php echo e(t('employee.clear_signature')); ?></button>
                        <small><?php echo e(t('employee.sign_hint')); ?></small>
                    </div>
                </div>

                <?php if (!$isCurrentNetworkAuthorized): ?>
                    <p class="crud-modal-subtitle"><?php echo e(t('employee.network_blocked')); ?></p>
                <?php endif; ?>

                <div class="employee-attendance-dialog-actions">
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-attendance-modal-close><?php echo e(t('employee.cancel')); ?></button>
                    <button type="submit" class="admin-action-link" <?php echo (empty($todaySignableShifts) || !$canSignNow) ? 'disabled' : ''; ?>><?php echo e(t('employee.sign_attendance')); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="employee-attendance-modal" data-document-sign-modal hidden>
        <div class="employee-attendance-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-document-sign-title">
            <div class="employee-attendance-dialog-head">
                <div>
                    <span class="employee-stage-eyebrow"><?php echo e(t('employee.documents')); ?></span>
                    <h3 id="employee-document-sign-title"><?php echo e(t('employee.sign_document', ['fallback' => 'Sign document'])); ?></h3>
                    <p class="crud-modal-subtitle" data-document-sign-name></p>
                </div>
            </div>

            <form method="post" class="admin-form employee-sign-form" data-document-signature-form>
                <input type="hidden" name="action" value="sign_document">
                <input type="hidden" name="request_id" value="" data-document-request-id>
                <input type="hidden" name="signature_data" value="" data-signature-data>
                <input type="hidden" name="signature_pos_x" value="86" data-signature-pos-x>
                <input type="hidden" name="signature_pos_y" value="84" data-signature-pos-y>

                <div class="employee-document-sign-stage">
                    <p class="crud-modal-subtitle employee-document-sign-stage-hint">Scegli dove firmare: clicca nel riquadro anteprima documento.</p>
                    <div class="employee-document-sign-preview-wrap" data-document-sign-preview-wrap>
                        <iframe src="about:blank" class="employee-document-sign-preview" data-document-sign-preview-frame title="<?php echo e(t('crud.preview', ['fallback' => 'Preview'])); ?>"></iframe>
                        <button type="button" class="employee-document-sign-position-layer" data-document-sign-position-layer aria-label="Scegli posizione firma">
                            <span class="employee-document-sign-position-dot" data-document-sign-position-dot aria-hidden="true"></span>
                        </button>
                    </div>
                </div>

                <div class="employee-signature-pad-shell">
                    <canvas width="520" height="180" data-signature-canvas aria-label="<?php echo e(t('employee.digital_signature')); ?>"></canvas>
                    <small class="employee-signature-error" data-signature-error></small>
                    <div class="employee-signature-pad-actions">
                        <button type="button" class="admin-action-link admin-action-link-secondary" data-signature-clear><?php echo e(t('employee.clear_signature')); ?></button>
                        <small><?php echo e(t('employee.sign_hint')); ?></small>
                    </div>
                </div>

                <div class="employee-attendance-dialog-actions">
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-document-sign-close><?php echo e(t('employee.cancel')); ?></button>
                    <button type="submit" class="admin-action-link"><?php echo e(t('employee.sign_document', ['fallback' => 'Sign document'])); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="employee-attendance-modal" data-employee-messages-modal hidden>
        <div class="employee-attendance-dialog employee-documents-dialog employee-messages-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-messages-manage-title">
            <div class="employee-attendance-dialog-head">
                <div>
                    <h3 id="employee-messages-manage-title"><?php echo e(t('employee.messages', ['fallback' => 'Messages'])); ?></h3>
                    <p class="crud-modal-subtitle"><?php echo e(t('crud.create_message', ['fallback' => 'Create requests or notifications, optionally with a document to sign.'])); ?></p>
                </div>
                <div class="employee-modal-head-actions">
                    <button type="button"
                            class="admin-action-link admin-action-link-secondary"
                            data-employee-messages-expand
                            data-label-expand="<?php echo e(t('employee.expand', ['fallback' => 'Expand'])); ?>"
                            data-label-collapse="<?php echo e(t('employee.collapse', ['fallback' => 'Collapse'])); ?>"
                            aria-expanded="false"><?php echo e(t('employee.expand', ['fallback' => 'Expand'])); ?></button>
                    <button type="button" class="dashboard-modal-close" data-employee-messages-modal-close aria-label="<?php echo e(t('employee.cancel')); ?>">&times;</button>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="admin-form employee-documents-form employee-messages-form">
                <input type="hidden" name="action" value="send_employee_message">
                <label>
                    <?php echo e(t('crud.message_kind')); ?>
                    <select name="message_kind">
                        <option value="request"><?php echo e(t('crud.message_request')); ?></option>
                        <option value="notification"><?php echo e(t('crud.message_notification')); ?></option>
                    </select>
                </label>
                <label>
                    <?php echo e(t('crud.request_type')); ?>
                    <select name="request_type">
                        <?php foreach (($employeeMessageRequestTypes ?? []) as $requestType): ?>
                            <option value="<?php echo e($requestType); ?>">
                                <?php echo e(t('crud.request_' . $requestType, ['fallback' => ucfirst(str_replace('_', ' ', (string) $requestType))])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <?php echo e(t('employee.title')); ?>
                    <input type="text" name="title" maxlength="255" placeholder="<?php echo e(t('employee.document_notification')); ?>" required>
                </label>
                <label class="span-2">
                    <?php echo e(t('crud.message')); ?>
                    <textarea name="message" rows="4" placeholder="<?php echo e(t('employee.send_message_hint', ['fallback' => 'Write your request or notification details.'])); ?>" required></textarea>
                </label>
                <?php if (!empty($openShiftChoices ?? [])): ?>
                    <label>
                        <?php echo e(t('crud.request_shift_coverage')); ?>
                        <select name="shift_id">
                            <option value=""><?php echo e(t('crud.none')); ?></option>
                            <?php foreach (($openShiftChoices ?? []) as $shiftChoice): ?>
                                <option value="<?php echo (int) ($shiftChoice['id'] ?? 0); ?>">
                                    <?php echo e((string) ($shiftChoice['department_name'] ?? '')); ?> - <?php echo e((string) ($shiftChoice['name'] ?? 'Shift')); ?> (<?php echo e(substr((string) ($shiftChoice['start_time'] ?? '00:00'), 0, 5)); ?>-<?php echo e(substr((string) ($shiftChoice['end_time'] ?? '00:00'), 0, 5)); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <label class="span-2">
                    <?php echo e(t('crud.attach_document')); ?>
                    <input type="file" name="document_file">
                </label>
                <label class="span-2 employee-documents-signature-toggle">
                    <input type="checkbox" name="require_signature" value="1">
                    <span><?php echo e(t('employee.request_signature', ['fallback' => 'Request digital signature for this document'])); ?></span>
                </label>
                <?php if (!empty($messageRecipients ?? [])): ?>
                    <label class="span-2">
                        <?php echo e(t('crud.recipients')); ?>
                        <select name="recipient_ids[]" multiple size="4">
                            <?php foreach (($messageRecipients ?? []) as $recipient): ?>
                                <option value="<?php echo (int) ($recipient['id'] ?? 0); ?>">
                                    <?php echo e((string) ($recipient['full_name'] ?? ('User #' . (int) ($recipient['id'] ?? 0)))); ?>
                                    (<?php echo e((string) ($recipient['role'] ?? 'manager')); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <div class="employee-attendance-dialog-actions span-2">
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-employee-messages-modal-close><?php echo e(t('employee.cancel')); ?></button>
                    <button type="submit" class="admin-action-link"><?php echo e(t('crud.send_message')); ?></button>
                </div>
            </form>

            <div class="employee-documents-inbox-sections employee-messages-sections">
                <section class="employee-documents-inbox-section employee-message-list-launcher">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.received_messages', ['fallback' => 'Received messages'])); ?></h4>
                        <span class="employee-metric-pill"><?php echo count($incomingMessages ?? []); ?> <?php echo e(t('employee.messages_suffix', ['fallback' => 'messages'])); ?></span>
                    </div>
                    <p class="crud-modal-subtitle"><?php echo e(t('employee.open_received_messages', ['fallback' => 'Open received messages in a focused list modal.'])); ?></p>
                    <div class="employee-attendance-dialog-actions">
                        <button type="button" class="admin-action-link" data-employee-messages-list-open data-scope="incoming"><?php echo e(t('employee.open_received_messages_button', ['fallback' => 'Open received messages'])); ?></button>
                    </div>
                </section>

                <section class="employee-documents-inbox-section employee-message-list-launcher">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.sent_messages', ['fallback' => 'Sent messages'])); ?></h4>
                        <span class="employee-metric-pill"><?php echo count($outgoingMessages ?? []); ?> <?php echo e(t('employee.messages_suffix', ['fallback' => 'messages'])); ?></span>
                    </div>
                    <p class="crud-modal-subtitle"><?php echo e(t('employee.open_sent_messages', ['fallback' => 'Open sent messages in a focused list modal.'])); ?></p>
                    <div class="employee-attendance-dialog-actions">
                        <button type="button" class="admin-action-link" data-employee-messages-list-open data-scope="outgoing"><?php echo e(t('employee.open_sent_messages_button', ['fallback' => 'Open sent messages'])); ?></button>
                    </div>
                </section>
            </div>
        </div>
    </div>

        <div class="employee-attendance-modal"
            data-employee-messages-list-modal
            data-title-incoming="<?php echo e(t('employee.received_messages', ['fallback' => 'Received messages'])); ?>"
            data-title-outgoing="<?php echo e(t('employee.sent_messages', ['fallback' => 'Sent messages'])); ?>"
            hidden>
        <div class="employee-attendance-dialog employee-documents-dialog employee-documents-inbox-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-messages-list-title">
            <div class="employee-attendance-dialog-head">
                <div>
                    <span class="employee-stage-eyebrow"><?php echo e(t('employee.messages', ['fallback' => 'Messages'])); ?></span>
                    <h3 id="employee-messages-list-title" data-employee-messages-list-title><?php echo e(t('employee.received_messages', ['fallback' => 'Received messages'])); ?></h3>
                </div>
                <button type="button" class="dashboard-modal-close" data-employee-messages-list-close aria-label="<?php echo e(t('employee.cancel')); ?>">&times;</button>
            </div>

            <div class="employee-documents-inbox-sections employee-messages-sections" data-employee-messages-list-body>
                <section class="employee-documents-inbox-section" data-message-list-scope="incoming">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.received_messages', ['fallback' => 'Received messages'])); ?></h4>
                    </div>
                    <form method="post" class="employee-messages-list-form">
                        <input type="hidden" name="action" value="message_bulk_update">
                        <input type="hidden" name="scope" value="incoming">
                        <div class="table-wrap employee-table-wrap employee-documents-table-wrap employee-messages-table-wrap">
                            <table class="admin-table employee-table-compact">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th><?php echo e(t('employee.date')); ?></th>
                                        <th><?php echo e(t('employee.sender')); ?></th>
                                        <th><?php echo e(t('employee.title')); ?></th>
                                        <th><?php echo e(t('employee.status')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($incomingMessages ?? [])): ?>
                                        <tr><td colspan="5"><?php echo e(t('crud.no_messages')); ?></td></tr>
                                    <?php endif; ?>
                                    <?php foreach (($incomingMessages ?? []) as $messageItem): ?>
                                        <tr>
                                            <td><input type="checkbox" name="request_ids[]" value="<?php echo (int) ($messageItem['id'] ?? 0); ?>"></td>
                                            <td><?php echo e((string) ($messageItem['created_at'] ?? '')); ?></td>
                                            <td><?php echo e((string) ($messageItem['sender_name'] ?? '-')); ?></td>
                                            <td>
                                                <strong><?php echo e((string) ($messageItem['title'] ?? t('crud.message_title_default'))); ?></strong>
                                                <div class="employee-documents-unread-alert"><?php echo e((string) ($messageItem['message'] ?? '')); ?></div>
                                            </td>
                                            <td>
                                                <span class="employee-status-chip"><?php echo e((string) ($messageItem['type'] ?? '-')); ?></span>
                                                <span class="employee-status-chip"><?php echo e((string) ($messageItem['status'] ?? '-')); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="employee-attendance-dialog-actions">
                            <button type="submit" name="operation" value="read" class="admin-action-link admin-action-link-secondary"><?php echo e(t('employee.mark_read_selected', ['fallback' => 'Mark selected as read'])); ?></button>
                            <button type="submit" name="operation" value="delete" class="admin-action-link"><?php echo e(t('employee.delete_selected', ['fallback' => 'Delete selected'])); ?></button>
                        </div>
                    </form>
                </section>

                <section class="employee-documents-inbox-section" data-message-list-scope="outgoing" hidden>
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.sent_messages', ['fallback' => 'Sent messages'])); ?></h4>
                    </div>
                    <form method="post" class="employee-messages-list-form">
                        <input type="hidden" name="action" value="message_bulk_update">
                        <input type="hidden" name="scope" value="outgoing">
                        <div class="table-wrap employee-table-wrap employee-documents-table-wrap employee-messages-table-wrap">
                            <table class="admin-table employee-table-compact">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th><?php echo e(t('employee.date')); ?></th>
                                        <th><?php echo e(t('employee.recipient', ['fallback' => 'Recipient'])); ?></th>
                                        <th><?php echo e(t('employee.title')); ?></th>
                                        <th><?php echo e(t('employee.status')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($outgoingMessages ?? [])): ?>
                                        <tr><td colspan="5"><?php echo e(t('crud.no_messages')); ?></td></tr>
                                    <?php endif; ?>
                                    <?php foreach (($outgoingMessages ?? []) as $messageItem): ?>
                                        <tr>
                                            <td><input type="checkbox" name="request_ids[]" value="<?php echo (int) ($messageItem['id'] ?? 0); ?>"></td>
                                            <td><?php echo e((string) ($messageItem['created_at'] ?? '')); ?></td>
                                            <td><?php echo e((string) ($messageItem['recipient_name'] ?? '-')); ?></td>
                                            <td>
                                                <strong><?php echo e((string) ($messageItem['title'] ?? t('crud.message_title_default'))); ?></strong>
                                                <div class="employee-documents-unread-alert"><?php echo e((string) ($messageItem['message'] ?? '')); ?></div>
                                            </td>
                                            <td>
                                                <span class="employee-status-chip"><?php echo e((string) ($messageItem['type'] ?? '-')); ?></span>
                                                <span class="employee-status-chip"><?php echo e((string) ($messageItem['status'] ?? '-')); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="employee-attendance-dialog-actions">
                            <button type="submit" name="operation" value="delete" class="admin-action-link"><?php echo e(t('employee.delete_selected', ['fallback' => 'Delete selected'])); ?></button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>



    <div class="employee-attendance-modal" data-employee-documents-inbox-modal hidden>
        <div class="employee-attendance-dialog employee-documents-dialog employee-documents-inbox-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-documents-inbox-title">
            <div class="employee-attendance-dialog-head">
                <div>
                    <span class="employee-stage-eyebrow"><?php echo e(t('employee.documents')); ?></span>
                    <h3 id="employee-documents-inbox-title"><?php echo e(t('employee.manage_documents', ['fallback' => 'Manage documents'])); ?></h3>
                    <p class="crud-modal-subtitle"><?php echo e(t('employee.documents_inbox_hint', ['fallback' => 'Manage received and sent files, signatures, and archive actions.'])); ?></p>
                </div>
                <button type="button" class="dashboard-modal-close" data-employee-documents-inbox-close aria-label="<?php echo e(t('employee.cancel')); ?>">&times;</button>
            </div>

            <div class="employee-documents-inbox-sections crud-panel-grid crud-panel-grid-wide">
                <section class="employee-documents-inbox-section crud-panel">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.received_documents')); ?></h4>
                        <span class="employee-metric-pill"><?php echo count($incomingDocuments); ?> <?php echo e(t('employee.files_suffix')); ?></span>
                    </div>
                    <div class="company-grid employee-documents-card-grid">
                        <?php if (empty($incomingDocuments)): ?>
                            <div class="crud-empty-state"><?php echo e(t('employee.no_documents')); ?></div>
                        <?php endif; ?>
                        <?php foreach ($incomingDocuments as $documentMessage): ?>
                            <?php
                                $incomingFileName = (string) ($documentMessage['file_name'] ?? t('employee.document'));
                                $incomingExtension = strtoupper((string) pathinfo($incomingFileName, PATHINFO_EXTENSION));
                                $incomingThumbnail = function_exists('documentThumbnailDataUrl')
                                    ? documentThumbnailDataUrl($documentMessage, 220, 120)
                                    : null;
                                if ($incomingExtension === '') {
                                    $incomingExtension = 'DOC';
                                }
                            ?>
                            <article class="company-card company-card--stacked employee-doc-card">
                                <div class="document-preview-vignette" aria-hidden="true">
                                    <?php if (is_string($incomingThumbnail) && $incomingThumbnail !== ''): ?>
                                        <img src="<?php echo e($incomingThumbnail); ?>" alt="" class="document-preview-vignette-image" loading="lazy">
                                    <?php else: ?>
                                        <span><?php echo e($incomingExtension); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="company-card-head">
                                    <div class="company-card-title"><?php echo e((string) ($documentMessage['title'] ?? t('employee.document_notification'))); ?></div>
                                    <div class="company-card-meta"><?php echo e((string) ($documentMessage['sender_name'] ?? '-')); ?> • <?php echo e((string) ($documentMessage['created_at'] ?? '')); ?></div>
                                </div>
                                <div class="company-card-actions company-card-actions--inline">
                                    <?php if (!empty($documentMessage['is_new'])): ?>
                                        <span class="company-card-chip"><?php echo e(t('employee.status_unread', ['fallback' => 'Unread'])); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($documentMessage['is_signed_notification'])): ?>
                                        <span class="company-card-chip"><?php echo e(t('employee.signed_document_received_title', ['fallback' => 'Signed document received'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="company-card-actions employee-doc-actions-icons">
                                    <?php if (!empty($documentMessage['document_id']) && !empty($documentMessage['is_download_available'])): ?>
                                        <a class="company-card-action" target="_blank" rel="noopener" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'preview' => '1', 'request_id' => (int) ($documentMessage['request_id'] ?? 0), 'mark_read' => '1', 'from' => 'my-space']); ?>" title="<?php echo e(t('crud.preview', ['fallback' => 'Preview'])); ?>">
                                            <img src="<?php echo e($basePath . '/assets/icons/document.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                        </a>
                                        <a class="company-card-action" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'request_id' => (int) ($documentMessage['request_id'] ?? 0), 'mark_read' => '1', 'from' => 'my-space']); ?>" title="<?php echo e(t('employee.download_document', ['fallback' => 'Download'])); ?>">
                                            <img src="<?php echo e($basePath . '/assets/icons/circle-arrow-out-up-left.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                        </a>
                                        <a class="company-card-action" target="_blank" rel="noopener" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'disposition' => 'inline', 'print_preview' => '1', 'request_id' => (int) ($documentMessage['request_id'] ?? 0), 'mark_read' => '1', 'from' => 'my-space']); ?>" title="<?php echo e(t('employee.print_document', ['fallback' => 'Print'])); ?>">
                                            <img src="<?php echo e($basePath . '/assets/icons/printer.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                        </a>
                                        <?php if (!empty($documentMessage['is_new'])): ?>
                                            <form method="post" class="company-card-delete-form">
                                                <input type="hidden" name="action" value="mark_document_read">
                                                <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                                <button type="submit" class="company-card-action" title="<?php echo e(t('employee.read_document', ['fallback' => 'Read'])); ?>">
                                                    <img src="<?php echo e($basePath . '/assets/icons/mail-open.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (!empty($documentMessage['can_sign'])): ?>
                                            <button type="button"
                                                    class="company-card-action"
                                                    title="<?php echo e(t('employee.sign_document', ['fallback' => 'Sign'])); ?>"
                                                    data-document-sign-open
                                                    data-document-sign-request-id="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>"
                                                    data-document-sign-title="<?php echo e((string) ($documentMessage['file_name'] ?? t('employee.document'))); ?>"
                                                    data-document-sign-preview-url="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'preview' => '1', 'request_id' => (int) ($documentMessage['request_id'] ?? 0), 'mark_read' => '1', 'from' => 'my-space']); ?>">
                                                <img src="<?php echo e($basePath . '/assets/icons/signature.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                            </button>
                                        <?php endif; ?>
                                    <?php elseif (!empty($documentMessage['document_id'])): ?>
                                        <span class="company-card-chip"><?php echo e(t('employee.file_not_available')); ?></span>
                                    <?php else: ?>
                                        <span class="company-card-chip">-</span>
                                    <?php endif; ?>
                                    <?php if (!empty($documentMessage['can_archive'])): ?>
                                        <form method="post" class="company-card-delete-form">
                                            <input type="hidden" name="action" value="archive_received_document">
                                            <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                            <button type="submit" class="company-card-action" title="<?php echo e(t('employee.archive_document', ['fallback' => 'Archive'])); ?>">
                                                <img src="<?php echo e($basePath . '/assets/icons/notebook.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" class="company-card-delete-form">
                                        <input type="hidden" name="action" value="delete_document_entry">
                                        <input type="hidden" name="scope" value="incoming">
                                        <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                        <button type="submit" class="company-card-action is-danger" aria-label="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>" title="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>">
                                            <img src="<?php echo e($basePath . '/assets/icons/x.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="employee-documents-inbox-section crud-panel">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.sent_documents', ['fallback' => 'Sent documents'])); ?></h4>
                        <span class="employee-metric-pill"><?php echo count($outgoingDocuments); ?> <?php echo e(t('employee.files_suffix')); ?></span>
                    </div>
                    <div class="company-grid employee-documents-card-grid">
                        <?php if (empty($outgoingDocuments)): ?>
                            <div class="crud-empty-state"><?php echo e(t('employee.no_sent_documents', ['fallback' => 'No sent documents yet.'])); ?></div>
                        <?php endif; ?>
                        <?php foreach ($outgoingDocuments as $documentMessage): ?>
                            <?php
                                $outgoingFileName = (string) ($documentMessage['file_name'] ?? t('employee.document'));
                                $outgoingExtension = strtoupper((string) pathinfo($outgoingFileName, PATHINFO_EXTENSION));
                                $outgoingThumbnail = function_exists('documentThumbnailDataUrl')
                                    ? documentThumbnailDataUrl($documentMessage, 220, 120)
                                    : null;
                                if ($outgoingExtension === '') {
                                    $outgoingExtension = 'DOC';
                                }
                                $outgoingTitle = strtolower((string) ($documentMessage['title'] ?? ''));
                                $isSignedNotification = str_contains($outgoingTitle, 'signe') || str_contains($outgoingTitle, 'signed');
                            ?>
                            <article class="company-card company-card--stacked employee-doc-card">
                                <div class="document-preview-vignette" aria-hidden="true">
                                    <?php if (is_string($outgoingThumbnail) && $outgoingThumbnail !== ''): ?>
                                        <img src="<?php echo e($outgoingThumbnail); ?>" alt="" class="document-preview-vignette-image" loading="lazy">
                                    <?php else: ?>
                                        <span><?php echo e($outgoingExtension); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="company-card-head">
                                    <div class="company-card-title"><?php echo e((string) ($documentMessage['title'] ?? t('employee.document_notification'))); ?></div>
                                    <div class="company-card-meta" title="<?php echo e((string) ($documentMessage['recipient_names_label'] ?? '-')); ?>"><?php echo e((string) ($documentMessage['recipient_name'] ?? '-')); ?> • <?php echo e((string) ($documentMessage['created_at'] ?? '')); ?></div>
                                </div>
                                <div class="company-card-actions company-card-actions--inline">
                                    <span class="company-card-chip">
                                        <?php echo e($isSignedNotification ? t('employee.read_and_approved', ['fallback' => 'Read and approved']) : ($statusLabels[(string) ($documentMessage['status_display'] ?? '')] ?? ((string) ($documentMessage['status_display'] ?? '-')))); ?>
                                    </span>
                                    <?php if (((int) ($documentMessage['recipient_count'] ?? 0)) > 1): ?>
                                        <span class="company-card-chip"><?php echo (int) ($documentMessage['recipient_count'] ?? 0); ?> destinatari</span>
                                    <?php endif; ?>
                                    <?php if (!empty($documentMessage['is_signed_by_recipient']) || $isSignedNotification): ?>
                                        <span class="company-card-chip"><?php echo e(t('employee.signed_document_received_title', ['fallback' => 'Signed document received'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="company-card-actions employee-doc-actions-icons">
                                    <?php if (!empty($documentMessage['document_id']) && !empty($documentMessage['is_download_available'])): ?>
                                        <a class="company-card-action" target="_blank" rel="noopener" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'preview' => '1', 'from' => 'my-space']); ?>" title="<?php echo e(t('crud.preview', ['fallback' => 'Preview'])); ?>">
                                            <img src="<?php echo e($basePath . '/assets/icons/document.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                        </a>
                                        <a class="company-card-action" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'from' => 'my-space']); ?>" title="<?php echo e(t('employee.download_document', ['fallback' => 'Download'])); ?>">
                                            <img src="<?php echo e($basePath . '/assets/icons/circle-arrow-out-up-left.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                        </a>
                                        <a class="company-card-action" target="_blank" rel="noopener" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'disposition' => 'inline', 'print_preview' => '1', 'from' => 'my-space']); ?>" title="<?php echo e(t('employee.print_document', ['fallback' => 'Print'])); ?>">
                                            <img src="<?php echo e($basePath . '/assets/icons/printer.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                        </a>
                                    <?php else: ?>
                                        <span class="company-card-chip"><?php echo e(t('employee.file_not_available')); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($documentMessage['can_archive']) && !empty($documentMessage['document_id'])): ?>
                                        <form method="post" class="company-card-delete-form">
                                            <input type="hidden" name="action" value="archive_outgoing_document">
                                            <input type="hidden" name="document_id" value="<?php echo (int) ($documentMessage['document_id'] ?? 0); ?>">
                                            <button type="submit" class="company-card-action" title="<?php echo e(t('employee.archive_document', ['fallback' => 'Archive'])); ?>">
                                                <img src="<?php echo e($basePath . '/assets/icons/notebook.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" class="company-card-delete-form">
                                        <input type="hidden" name="action" value="delete_outgoing_document">
                                        <input type="hidden" name="document_id" value="<?php echo (int) ($documentMessage['document_id'] ?? 0); ?>">
                                        <button type="submit" class="company-card-action is-danger" aria-label="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>" title="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>">
                                            <img src="<?php echo e($basePath . '/assets/icons/x.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="employee-documents-inbox-section employee-documents-inbox-section-full crud-panel">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.archived_documents', ['fallback' => 'Archived documents'])); ?></h4>
                        <span class="employee-metric-pill"><?php echo count($archivedDocuments); ?> <?php echo e(t('employee.files_suffix')); ?></span>
                    </div>
                    <div class="employee-archived-list">
                        <?php if (empty($archivedDocuments)): ?>
                            <div class="crud-empty-state"><?php echo e(t('employee.no_archived_documents', ['fallback' => 'No archived documents.'])); ?></div>
                        <?php endif; ?>
                        <?php foreach ($archivedDocuments as $documentMessage): ?>
                            <div class="employee-archived-row">
                                <div class="employee-archived-row-main">
                                    <div class="employee-archived-row-title"><?php echo e((string) ($documentMessage['title'] ?? t('employee.document_notification'))); ?></div>
                                    <div class="employee-archived-row-description"><?php echo e((string) ($documentMessage['description'] ?? '-')); ?></div>
                                </div>
                                <?php if ((string) ($documentMessage['archive_scope'] ?? '') === 'outgoing'): ?>
                                    <form method="post" class="employee-archived-row-action">
                                        <input type="hidden" name="action" value="restore_archived_outgoing_document">
                                        <input type="hidden" name="document_id" value="<?php echo (int) ($documentMessage['document_id'] ?? 0); ?>">
                                        <button type="submit" class="admin-action-link admin-action-link-secondary"><?php echo e(t('employee.restore_document', ['fallback' => 'Restore'])); ?></button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="employee-archived-row-action">
                                        <input type="hidden" name="action" value="restore_archived_document">
                                        <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                        <button type="submit" class="admin-action-link admin-action-link-secondary"><?php echo e(t('employee.restore_document', ['fallback' => 'Restore'])); ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

            </div>
        </div>
    </div>
</div>