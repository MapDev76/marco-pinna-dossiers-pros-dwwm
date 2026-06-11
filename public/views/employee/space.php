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
    'rest' => 'moon.svg',
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
        return (new DateTimeImmutable((string) $dateValue))->format('l, j F Y');
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
                        <small><?php echo e($shift['department_name'] ?? t('employee.default_department')); ?></small>
                        <small class="employee-upcoming-kind"><?php echo $shiftKindIconHtml; ?><?php echo e($shiftKindLabel); ?></small>
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

        <article class="admin-card employee-detail-card" id="employee-received-documents">
            <div class="employee-card-head">
                <div>
                    <span class="employee-stage-eyebrow"><?php echo e(t('employee.documents')); ?></span>
                    <h2><?php echo e(t('employee.received_documents')); ?></h2>
                    <?php if ($unreadDocumentsCount > 0): ?>
                        <p class="employee-documents-unread-alert"><?php echo e($unreadDocumentsCount . ' ' . t('employee.new_documents_notice', ['fallback' => 'new document(s) to read'])); ?></p>
                    <?php endif; ?>
                </div>
                <div class="employee-card-head-actions">
                    <span class="employee-metric-pill"><?php echo count($incomingDocuments); ?> <?php echo e(t('employee.files_suffix')); ?></span>
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-employee-documents-inbox-open><?php echo e(t('employee.manage_documents', ['fallback' => 'Manage'])); ?></button>
                </div>
            </div>
            <div class="table-wrap employee-table-wrap">
                <table class="admin-table employee-table-compact">
                    <thead>
                        <tr>
                            <th><?php echo e(t('employee.date')); ?></th>
                            <th><?php echo e(t('employee.title')); ?></th>
                            <th><?php echo e(t('employee.sender')); ?></th>
                            <th><?php echo e(t('employee.document')); ?></th>
                            <th><?php echo e(t('employee.status')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($incomingDocuments)): ?>
                            <tr><td colspan="5"><?php echo e(t('employee.no_documents')); ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($incomingDocuments as $documentMessage): ?>
                            <tr>
                                <td><?php echo e((string) ($documentMessage['created_at'] ?? '')); ?></td>
                                <td><?php echo e((string) ($documentMessage['title'] ?? t('employee.document_notification'))); ?></td>
                                <td><?php echo e((string) ($documentMessage['sender_name'] ?? '-')); ?></td>
                                <td><?php echo e((string) ($documentMessage['file_name'] ?? t('employee.document'))); ?></td>
                                <td>
                                    <div class="employee-document-status-list">
                                        <?php
                                            $docStatus = strtolower((string) ($documentMessage['status'] ?? ''));
                                            $docType = (string) ($documentMessage['type'] ?? '');
                                        ?>
                                        <?php if (!empty($documentMessage['is_signed_notification'])): ?>
                                            <span class="employee-status-chip"><?php echo e(t('employee.signed_document_received_title', ['fallback' => 'Signed document received'])); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($documentMessage['is_new'])): ?>
                                            <span class="employee-status-chip"><?php echo e(t('employee.status_new', ['fallback' => 'New'])); ?></span>
                                        <?php else: ?>
                                            <span class="employee-status-chip"><?php echo e(t('employee.status_old', ['fallback' => 'Old'])); ?></span>
                                        <?php endif; ?>
                                        <?php if ($docStatus === 'unread' || $docStatus === 'pending'): ?>
                                            <span class="employee-status-chip"><?php echo e(t('employee.status_unread', ['fallback' => 'Unread'])); ?></span>
                                        <?php elseif ($docStatus === 'approved' && $docType === 'document_signature'): ?>
                                            <span class="employee-status-chip"><?php echo e(t('employee.read_and_approved', ['fallback' => 'Read and approved'])); ?></span>
                                        <?php elseif ($docStatus === 'cancelled' || $docStatus === 'archived'): ?>
                                            <span class="employee-status-chip"><?php echo e(t('employee.status_archived', ['fallback' => 'Archived'])); ?></span>
                                        <?php elseif ($docStatus !== ''): ?>
                                            <span class="employee-status-chip"><?php echo e($statusLabels[$docStatus] ?? ucfirst($docStatus)); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($documentMessage['document_id']) && empty($documentMessage['is_download_available'])): ?>
                                            <span class="employee-status-chip"><?php echo e(t('employee.file_not_available')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
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
                    <button type="submit" <?php echo (empty($todaySignableShifts) || !$canSignNow) ? 'disabled' : ''; ?>><?php echo e(t('employee.sign_attendance')); ?></button>
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
                    <button type="submit"><?php echo e(t('employee.sign_document', ['fallback' => 'Sign document'])); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="employee-attendance-modal" data-employee-documents-modal hidden>
        <div class="employee-attendance-dialog employee-documents-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-documents-manage-title">
            <div class="employee-attendance-dialog-head">
                <div>
                    <span class="employee-stage-eyebrow"><?php echo e(t('employee.documents')); ?></span>
                    <h3 id="employee-documents-manage-title"><?php echo e(t('common.documents')); ?></h3>
                    <p class="crud-modal-subtitle">Partagez un document avec les responsables sans demande de signature.</p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="admin-form employee-documents-form">
                <input type="hidden" name="action" value="share_document_no_signature">
                <label>
                    Titre
                    <input type="text" name="title" maxlength="255" placeholder="Partage document" required>
                </label>
                <label class="span-2">
                    Message
                    <textarea name="message" rows="3" placeholder="Ajoutez un court message pour les responsables."></textarea>
                </label>
                <label class="span-2">
                    Document
                    <input type="file" name="document_file" required>
                </label>
                <label class="span-2 employee-documents-signature-toggle">
                    <input type="checkbox" name="require_signature" value="1">
                    <span><?php echo e(t('employee.request_signature', ['fallback' => 'Request digital signature for this document'])); ?></span>
                </label>
                <?php if (!empty($documentShareRecipients ?? [])): ?>
                    <label class="span-2">
                        Destinataires
                        <select name="recipient_ids[]" multiple size="6">
                            <?php foreach (($documentShareRecipients ?? []) as $recipient): ?>
                                <option value="<?php echo (int) ($recipient['id'] ?? 0); ?>">
                                    <?php echo e((string) ($recipient['full_name'] ?? ('User #' . (int) ($recipient['id'] ?? 0)))); ?>
                                    (<?php echo e((string) ($recipient['role'] ?? 'manager')); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <div class="employee-attendance-dialog-actions span-2">
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-employee-documents-modal-close><?php echo e(t('employee.cancel')); ?></button>
                    <button type="submit" class="admin-action-link"><?php echo e(t('employee.send_document_request', ['fallback' => 'Send request'])); ?></button>
                </div>
            </form>
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

            <div class="employee-documents-inbox-sections">
                <section class="employee-documents-inbox-section">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.received_documents')); ?></h4>
                        <span class="employee-metric-pill"><?php echo count($incomingDocuments); ?> <?php echo e(t('employee.files_suffix')); ?></span>
                    </div>
                    <div class="table-wrap employee-table-wrap employee-documents-table-wrap">
                        <table class="admin-table employee-table-compact">
                            <thead>
                                <tr>
                                    <th><?php echo e(t('employee.date')); ?></th>
                                    <th><?php echo e(t('employee.title')); ?></th>
                                    <th><?php echo e(t('employee.sender')); ?></th>
                                    <th><?php echo e(t('employee.action')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($incomingDocuments)): ?>
                                    <tr><td colspan="4"><?php echo e(t('employee.no_documents')); ?></td></tr>
                                <?php endif; ?>
                                <?php foreach ($incomingDocuments as $documentMessage): ?>
                                    <tr>
                                        <td><?php echo e((string) ($documentMessage['created_at'] ?? '')); ?></td>
                                        <td><?php echo e((string) ($documentMessage['title'] ?? t('employee.document_notification'))); ?></td>
                                        <td><?php echo e((string) ($documentMessage['sender_name'] ?? '-')); ?></td>
                                        <td>
                                            <div class="employee-document-actions employee-document-actions-inline">
                                                <?php if (!empty($documentMessage['is_new'])): ?>
                                                    <span class="employee-status-chip"><?php echo e(t('employee.status_unread', ['fallback' => 'Unread'])); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($documentMessage['is_signed_notification'])): ?>
                                                    <span class="employee-status-chip"><?php echo e(t('employee.signed_document_received_title', ['fallback' => 'Signed document received'])); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($documentMessage['document_id']) && !empty($documentMessage['is_download_available'])): ?>
                                                    <a class="admin-action-link" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'request_id' => (int) ($documentMessage['request_id'] ?? 0), 'mark_read' => '1', 'from' => 'my-space']); ?>"><?php echo e(t('employee.download_document', ['fallback' => 'Download'])); ?></a>
                                                    <a class="admin-action-link admin-action-link-secondary" target="_blank" rel="noopener" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'disposition' => 'inline', 'print_preview' => '1', 'request_id' => (int) ($documentMessage['request_id'] ?? 0), 'mark_read' => '1', 'from' => 'my-space']); ?>"><?php echo e(t('employee.print_document', ['fallback' => 'Print'])); ?></a>
                                                    <?php if (!empty($documentMessage['is_new'])): ?>
                                                        <form method="post">
                                                            <input type="hidden" name="action" value="mark_document_read">
                                                            <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                                            <button type="submit" class="admin-action-link admin-action-link-secondary"><?php echo e(t('employee.read_document', ['fallback' => 'Read'])); ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if (!empty($documentMessage['can_sign'])): ?>
                                                        <button type="button"
                                                                class="admin-action-link"
                                                                data-document-sign-open
                                                                data-document-sign-request-id="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>"
                                                                data-document-sign-title="<?php echo e((string) ($documentMessage['file_name'] ?? t('employee.document'))); ?>">
                                                            <?php echo e(t('employee.sign_document', ['fallback' => 'Sign'])); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php elseif (!empty($documentMessage['document_id'])): ?>
                                                    <span class="employee-status-chip"><?php echo e(t('employee.file_not_available')); ?></span>
                                                <?php else: ?>
                                                    <span class="employee-status-chip">-</span>
                                                <?php endif; ?>

                                                <?php if (!empty($documentMessage['can_archive'])): ?>
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="archive_received_document">
                                                        <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                                        <button type="submit" class="admin-action-link admin-action-link-secondary"><?php echo e(t('employee.archive_document', ['fallback' => 'Archive'])); ?></button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="delete_document_entry">
                                                    <input type="hidden" name="scope" value="incoming">
                                                    <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                                    <button type="submit" class="site-icon-btn employee-document-delete-btn" aria-label="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>" title="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>">
                                                        <img src="<?php echo e($basePath . '/assets/icons/x.svg'); ?>" alt="" aria-hidden="true">
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="employee-documents-inbox-section">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.sent_documents', ['fallback' => 'Sent documents'])); ?></h4>
                        <span class="employee-metric-pill"><?php echo count($outgoingDocuments); ?> <?php echo e(t('employee.files_suffix')); ?></span>
                    </div>
                    <div class="table-wrap employee-table-wrap employee-documents-table-wrap">
                        <table class="admin-table employee-table-compact">
                            <thead>
                                <tr>
                                    <th><?php echo e(t('employee.date')); ?></th>
                                    <th><?php echo e(t('employee.title')); ?></th>
                                    <th><?php echo e(t('employee.recipient', ['fallback' => 'Recipient'])); ?></th>
                                    <th><?php echo e(t('employee.status', ['fallback' => 'Status'])); ?></th>
                                    <th><?php echo e(t('employee.action')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($outgoingDocuments)): ?>
                                    <tr><td colspan="5"><?php echo e(t('employee.no_sent_documents', ['fallback' => 'No sent documents yet.'])); ?></td></tr>
                                <?php endif; ?>
                                <?php foreach ($outgoingDocuments as $documentMessage): ?>
                                    <tr>
                                        <td><?php echo e((string) ($documentMessage['created_at'] ?? '')); ?></td>
                                        <td><?php echo e((string) ($documentMessage['title'] ?? t('employee.document_notification'))); ?></td>
                                        <td><?php echo e((string) ($documentMessage['recipient_name'] ?? '-')); ?></td>
                                        <td>
                                            <?php
                                                $outgoingTitle = strtolower((string) ($documentMessage['title'] ?? ''));
                                                $isSignedNotification = str_contains($outgoingTitle, 'signe') || str_contains($outgoingTitle, 'signed');
                                            ?>
                                            <span class="employee-status-chip">
                                                <?php echo e($isSignedNotification ? t('employee.read_and_approved', ['fallback' => 'Read and approved']) : ($statusLabels[(string) ($documentMessage['status'] ?? '')] ?? ((string) ($documentMessage['status'] ?? '-')))); ?>
                                            </span>
                                            <?php if (!empty($documentMessage['is_signed_by_recipient']) || $isSignedNotification): ?>
                                                <span class="employee-status-chip"><?php echo e(t('employee.signed_document_received_title', ['fallback' => 'Signed document received'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="employee-document-actions employee-document-actions-inline">
                                                <?php if (!empty($documentMessage['document_id']) && !empty($documentMessage['is_download_available'])): ?>
                                                    <a class="admin-action-link" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'from' => 'my-space']); ?>"><?php echo e(t('employee.download_document', ['fallback' => 'Download'])); ?></a>
                                                    <a class="admin-action-link admin-action-link-secondary" target="_blank" rel="noopener" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'disposition' => 'inline', 'print_preview' => '1', 'from' => 'my-space']); ?>"><?php echo e(t('employee.print_document', ['fallback' => 'Print'])); ?></a>
                                                <?php else: ?>
                                                    <span class="employee-status-chip"><?php echo e(t('employee.file_not_available')); ?></span>
                                                <?php endif; ?>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="delete_document_entry">
                                                    <input type="hidden" name="scope" value="outgoing">
                                                    <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                                    <button type="submit" class="site-icon-btn employee-document-delete-btn" aria-label="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>" title="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>">
                                                        <img src="<?php echo e($basePath . '/assets/icons/x.svg'); ?>" alt="" aria-hidden="true">
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="employee-documents-inbox-section">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.archived_documents', ['fallback' => 'Archived documents'])); ?></h4>
                        <span class="employee-metric-pill"><?php echo count($archivedDocuments); ?> <?php echo e(t('employee.files_suffix')); ?></span>
                    </div>
                    <div class="table-wrap employee-table-wrap employee-documents-table-wrap">
                        <table class="admin-table employee-table-compact">
                            <thead>
                                <tr>
                                    <th><?php echo e(t('employee.date')); ?></th>
                                    <th><?php echo e(t('employee.title')); ?></th>
                                    <th><?php echo e(t('employee.sender')); ?></th>
                                    <th><?php echo e(t('employee.action')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($archivedDocuments)): ?>
                                    <tr><td colspan="4"><?php echo e(t('employee.no_archived_documents', ['fallback' => 'No archived documents.'])); ?></td></tr>
                                <?php endif; ?>
                                <?php foreach ($archivedDocuments as $documentMessage): ?>
                                    <tr>
                                        <td><?php echo e((string) ($documentMessage['created_at'] ?? '')); ?></td>
                                        <td><?php echo e((string) ($documentMessage['title'] ?? t('employee.document_notification'))); ?></td>
                                        <td><?php echo e((string) ($documentMessage['sender_name'] ?? '-')); ?></td>
                                        <td>
                                            <div class="employee-document-actions employee-document-actions-inline">
                                                <?php if (!empty($documentMessage['document_id']) && !empty($documentMessage['is_download_available'])): ?>
                                                    <a class="admin-action-link" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'from' => 'my-space']); ?>"><?php echo e(t('employee.download_document', ['fallback' => 'Download'])); ?></a>
                                                    <a class="admin-action-link admin-action-link-secondary" target="_blank" rel="noopener" href="<?php echo appUrl('document-download', ['id' => (int) $documentMessage['document_id'], 'disposition' => 'inline', 'print_preview' => '1', 'from' => 'my-space']); ?>"><?php echo e(t('employee.print_document', ['fallback' => 'Print'])); ?></a>
                                                <?php endif; ?>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="restore_archived_document">
                                                    <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                                    <button type="submit" class="admin-action-link admin-action-link-secondary"><?php echo e(t('employee.restore_document', ['fallback' => 'Restore'])); ?></button>
                                                </form>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="delete_document_entry">
                                                    <input type="hidden" name="scope" value="archived">
                                                    <input type="hidden" name="request_id" value="<?php echo (int) ($documentMessage['request_id'] ?? 0); ?>">
                                                    <button type="submit" class="site-icon-btn employee-document-delete-btn" aria-label="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>" title="<?php echo e(t('employee.delete_document', ['fallback' => 'Delete'])); ?>">
                                                        <img src="<?php echo e($basePath . '/assets/icons/x.svg'); ?>" alt="" aria-hidden="true">
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="employee-documents-inbox-section">
                    <div class="employee-card-head">
                        <h4><?php echo e(t('employee.send_request', ['fallback' => 'Send request'])); ?></h4>
                    </div>
                    <form method="post" enctype="multipart/form-data" class="admin-form employee-documents-form employee-documents-request-form">
                        <input type="hidden" name="action" value="share_document_no_signature">
                        <label>
                            <?php echo e(t('employee.title')); ?>
                            <input type="text" name="title" maxlength="255" placeholder="<?php echo e(t('employee.document_notification')); ?>" required>
                        </label>
                        <label>
                            <?php echo e(t('employee.document')); ?>
                            <input type="file" name="document_file" required>
                        </label>
                        <label class="span-2">
                            Message
                            <textarea name="message" rows="3" placeholder="Ajoutez un court message pour les responsables."></textarea>
                        </label>
                        <label class="span-2 employee-documents-signature-toggle">
                            <input type="checkbox" name="require_signature" value="1">
                            <span><?php echo e(t('employee.request_signature', ['fallback' => 'Request digital signature for this document'])); ?></span>
                        </label>
                        <?php if (!empty($documentShareRecipients ?? [])): ?>
                            <label class="span-2">
                                Destinataires
                                <select name="recipient_ids[]" multiple size="6">
                                    <?php foreach (($documentShareRecipients ?? []) as $recipient): ?>
                                        <option value="<?php echo (int) ($recipient['id'] ?? 0); ?>">
                                            <?php echo e((string) ($recipient['full_name'] ?? ('User #' . (int) ($recipient['id'] ?? 0)))); ?>
                                            (<?php echo e((string) ($recipient['role'] ?? 'manager')); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>
                        <div class="employee-attendance-dialog-actions span-2">
                            <button type="button" class="admin-action-link admin-action-link-secondary" data-employee-documents-inbox-close><?php echo e(t('employee.cancel')); ?></button>
                            <button type="submit" class="admin-action-link"><?php echo e(t('employee.send_document_request', ['fallback' => 'Send request'])); ?></button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>