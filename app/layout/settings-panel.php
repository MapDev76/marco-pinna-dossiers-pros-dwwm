<?php
/**
 * Settings modal module.
 *
 * This panel groups the management rubrics for departments, users, roles,
 * shifts and assignments inside one modal shell.
 */
$currentUser = currentUser();
$planner = $dashboardPlannerData ?? [];
$departments = is_array($planner['departments'] ?? null) ? $planner['departments'] : [];
$users = is_array($planner['users'] ?? null) ? $planner['users'] : [];
$plannerCompany = is_array($planner['company'] ?? null) ? $planner['company'] : [];
$scopeCompanyId = (int) ($plannerCompany['id'] ?? ($departments[0]['company_id'] ?? ($currentUser['company_id'] ?? 0)));
$scopeCompanyName = (string) ($plannerCompany['name'] ?? ($currentUser['company_name'] ?? 'StaffEase Pro'));
$scopeCompanySignatureIp = trim((string) ($plannerCompany['signature_ip'] ?? ''));
$scopeCompanies = is_array($planner['companies'] ?? null) ? $planner['companies'] : [];
$visibleUsers = $users;
$currentRole = $currentUser['role'] ?? '';
if ($currentRole === 'admin') {
    $visibleUsers = array_values(array_filter($users, static function($u) use ($scopeCompanyId) {
        $rowCompanyId = (int) ($u['company_id'] ?? 0);
        if ($scopeCompanyId > 0 && $rowCompanyId > 0 && $rowCompanyId !== $scopeCompanyId) {
            return false;
        }

        return (($u['role'] ?? '') !== 'super_admin');
    }));
}
$shifts = is_array($planner['shifts'] ?? null) ? $planner['shifts'] : [];
$assignments = is_array($planner['assignments'] ?? null) ? $planner['assignments'] : [];
$attendances = is_array($planner['attendances'] ?? null) ? $planner['attendances'] : [];
$roleLabels = [
    'super_admin' => t('roles.super_admin'),
    'admin' => t('roles.admin'),
    'department_manager' => t('roles.department_manager'),
    'employee' => t('roles.employee'),
];
$roleCatalog = [
    ['key' => 'super_admin', 'label' => t('roles.super_admin'), 'color' => '#1f2937', 'icon' => '🛡️'],
    ['key' => 'admin', 'label' => t('roles.admin'), 'color' => '#b98b12', 'icon' => '⚙'],
    ['key' => 'department_manager', 'label' => t('roles.department_manager'), 'color' => '#2f6fed', 'icon' => '👔'],
    ['key' => 'employee', 'label' => t('roles.employee'), 'color' => '#5b6472', 'icon' => '👤'],
];
$companyTypeRaw = (string) ($plannerCompany['type'] ?? ($currentUser['company_type'] ?? ''));
if ($companyTypeRaw === '' && $scopeCompanyId > 0 && !empty($scopeCompanies)) {
    foreach ($scopeCompanies as $companyOption) {
        if ((int) ($companyOption['id'] ?? 0) === $scopeCompanyId) {
            $companyTypeRaw = (string) ($companyOption['type'] ?? '');
            break;
        }
    }
}
$companyTypeNormalized = strtolower(trim($companyTypeRaw));
$companyDomain = 'generic';
if (
    str_contains($companyTypeNormalized, 'hospital')
    || str_contains($companyTypeNormalized, 'clinic')
    || str_contains($companyTypeNormalized, 'medical')
    || str_contains($companyTypeNormalized, 'health')
) {
    $companyDomain = 'healthcare';
} elseif (
    str_contains($companyTypeNormalized, 'hotel')
    || str_contains($companyTypeNormalized, 'resort')
    || str_contains($companyTypeNormalized, 'hospitality')
) {
    $companyDomain = 'hospitality';
}

$departmentIconCatalogMap = [
    'hospitality' => ['🛎️', '🧹', '🍽️', '🍸', '🚗', '🏊', '🧖', '🛏️', '🧳', '🎟️', '🍳', '🥐', '🍷', '🪟', '🌺', '🧴', '🧯', '🧰', '🗝️', '🪴', '🛗', '🧺', '🧼', '🪣', '📞', '🧻'],
    'healthcare' => ['🏥', '🩺', '💉', '🧪', '🩻', '🧬', '🚑', '💊', '🫀', '🧫', '🧑‍⚕️', '🧑‍🔬', '🩹', '🧴', '🧯', '🛏️', '🧠', '📋', '🫁', '🦴', '🧻', '🧼', '⚕️', '🩸', '🧎', '🚪'],
    'generic' => ['🏷️', '🧑‍💼', '🔧', '📦', '📁', '🛠️', '💼', '🧭', '📌', '🧾', '🧰', '📊', '🧑‍🏭', '🧑‍🎨', '🧪', '🛰️', '🔒', '📣', '📎', '🗂️', '🗄️', '🧮', '🖨️', '📬', '🛒', '📡'],
];
$shiftIconCatalogMap = [
    'hospitality' => ['🌅', '☀️', '🌇', '🌙', '🛎️', '🍽️', '🧹', '🚗', '🛌', '🌴', '🏖️', '🧘', '☕', '🤒', '💤', '🍳', '🥐', '🍷', '🚪', '🧺', '🧽', '🧯', '🧳', '🗝️', '🛗', '🪴', '🧴', '🪣'],
    'healthcare' => ['🩺', '💉', '🚑', '🏥', '🌙', '☀️', '🧪', '💊', '🛌', '🧘', '☕', '💤', '🤒', '🏖️', '🌴', '🧑‍⚕️', '🧑‍🔬', '🩹', '🧼', '🧯', '📋', '🫁', '🩸', '🦴', '⚕️', '🚪', '🧴', '🧻'],
    'generic' => ['🕒', '☀️', '🌙', '🛠️', '📦', '👥', '🧭', '⚙️', '🛌', '💤', '🧘', '☕', '🌴', '🏖️', '🤒', '🧑‍💻', '📞', '📬', '🚚', '🧹', '🧯', '📈', '🖨️', '🧮', '📡', '🗂️', '🪛', '🔌'],
];

$departmentIconCatalog = $departmentIconCatalogMap[$companyDomain] ?? $departmentIconCatalogMap['generic'];
$shiftIconCatalog = $shiftIconCatalogMap[$companyDomain] ?? $shiftIconCatalogMap['generic'];
$pickerColorCatalogMap = [
    '#b98b12' => 'Warm Amber',
    '#d97706' => 'Golden Hour',
    '#f59e0b' => 'Sunbeam',
    '#fbbf24' => 'Honey',
    '#facc15' => 'Lemon Glow',
    '#84cc16' => 'Lime Spark',
    '#22c55e' => 'Fresh Green',
    '#16a34a' => 'Forest Green',
    '#10b981' => 'Mint',
    '#14b8a6' => 'Teal Mist',
    '#0f766e' => 'Deep Teal',
    '#06b6d4' => 'Sky Tide',
    '#0891b2' => 'Ocean Blue',
    '#0ea5e9' => 'Clear Blue',
    '#3b82f6' => 'Bright Blue',
    '#2f6fed' => 'Royal Blue',
    '#6366f1' => 'Indigo Pulse',
    '#4f46e5' => 'Blue Violet',
    '#7c3aed' => 'Violet Bloom',
    '#a855f7' => 'Lavender',
    '#d946ef' => 'Orchid',
    '#ec4899' => 'Rose Bloom',
    '#be123c' => 'Crimson',
    '#ef4444' => 'Vivid Red',
    '#f97316' => 'Tangerine',
    '#ea580c' => 'Ember',
    '#c2410c' => 'Burnt Orange',
    '#374151' => 'Slate',
    '#475569' => 'Steel',
    '#64748b' => 'Mist Gray',
    '#6b7280' => 'Stone Gray',
    '#1f2937' => 'Midnight',
    '#111827' => 'Ink',
];
$pickerColorCatalog = array_keys($pickerColorCatalogMap);
$pickerColorLabel = static function (string $color) use ($pickerColorCatalogMap): string {
    return $pickerColorCatalogMap[$color] ?? $color;
};
$activeDepartment = null;
if (!empty($departments)) {
    foreach ($departments as $department) {
        if ((int) ($department['id'] ?? 0) === (int) ($planner['active_department_id'] ?? 0)) {
            $activeDepartment = $department;
            break;
        }
    }
    if (!$activeDepartment) {
        $activeDepartment = $departments[0];
    }
}
$activeDepartmentAssignments = [];
if ($activeDepartment) {
    $activeDepartmentAssignments = array_values(array_filter(
        $assignments,
        static fn (array $assignment): bool => (int) ($assignment['department_id'] ?? 0) === (int) ($activeDepartment['id'] ?? 0)
    ));
}
$assignmentCountByDepartment = [];
foreach ($assignments as $assignment) {
    $deptId = (int) ($assignment['department_id'] ?? 0);
    $assignmentCountByDepartment[$deptId] = ($assignmentCountByDepartment[$deptId] ?? 0) + 1;
}

$visibleDepartments = $departments;
if (($currentRole ?? '') === 'admin') {
    $visibleDepartments = array_values(array_filter($departments, static fn($d) => (int) ($d['company_id'] ?? 0) === $scopeCompanyId));
}

$activeAssignmentStatuses = ['assigned', 'in_progress', 'completed'];
$durationHoursForTimes = static function (?string $startTime, ?string $endTime): float {
    if (!$startTime || !$endTime) {
        return 0.0;
    }

    $startParts = explode(':', $startTime);
    $endParts = explode(':', $endTime);
    if (count($startParts) < 2 || count($endParts) < 2) {
        return 0.0;
    }

    $startMinutes = ((int) $startParts[0] * 60) + (int) $startParts[1];
    $endMinutes = ((int) $endParts[0] * 60) + (int) $endParts[1];
    $delta = $endMinutes - $startMinutes;
    if ($delta <= 0) {
        $delta += 24 * 60;
    }

    return round($delta / 60, 2);
};

$shiftById = [];
foreach ($shifts as $shift) {
    $shiftById[(int) ($shift['id'] ?? 0)] = $shift;
}

$currentMonthPrefix = date('Y-m');
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');
$currentMonthLabel = date('F Y');
$todayDate = date('Y-m-d');
$assignmentsCurrentMonth = array_values(array_filter(
    $assignments,
    static fn(array $assignment): bool => str_starts_with((string) ($assignment['work_date'] ?? ''), $currentMonthPrefix)
));

$assignmentTotals = [
    'total' => count($assignmentsCurrentMonth),
    'active' => 0,
    'cancelled' => 0,
    'assigned_hours' => 0.0,
    'covered_days' => 0,
    'days_range' => 0,
    'unassigned_shift_templates' => 0,
];
$assignmentRangeStart = '';
$assignmentRangeEnd = '';
$coveredDates = [];
$coverageByDepartment = [];
$departmentMetrics = [];
$userWorkloadMap = [];
$usedShiftIds = [];

foreach ($visibleDepartments as $department) {
    $deptId = (int) ($department['id'] ?? 0);
    if ($deptId <= 0) {
        continue;
    }
    $departmentMetrics[$deptId] = [
        'department_id' => $deptId,
        'department_name' => (string) ($department['name'] ?? 'Department'),
        'department_icon' => (string) ($department['icon'] ?? '🏷️'),
        'department_color' => (string) ($department['color'] ?? '#b98b12'),
        'assignments' => 0,
        'active_assignments' => 0,
        'hours' => 0.0,
        'uncovered_days' => 0,
    ];
}

foreach ($assignmentsCurrentMonth as $assignment) {
    $workDate = (string) ($assignment['work_date'] ?? '');
    $status = (string) ($assignment['status'] ?? 'assigned');
    $isActiveAssignment = in_array($status, $activeAssignmentStatuses, true);
    $deptId = (int) ($assignment['department_id'] ?? 0);
    $userId = (int) ($assignment['user_id'] ?? 0);
    $shiftId = (int) ($assignment['shift_id'] ?? 0);
    $shiftName = (string) ($assignment['shift_name'] ?? 'Shift');
    $shiftStart = (string) ($assignment['start_time'] ?? ($shiftById[$shiftId]['start_time'] ?? ''));
    $shiftEnd = (string) ($assignment['end_time'] ?? ($shiftById[$shiftId]['end_time'] ?? ''));
    $durationHours = $durationHoursForTimes($shiftStart, $shiftEnd);

    if ($workDate !== '') {
        if ($assignmentRangeStart === '' || $workDate < $assignmentRangeStart) {
            $assignmentRangeStart = $workDate;
        }
        if ($assignmentRangeEnd === '' || $workDate > $assignmentRangeEnd) {
            $assignmentRangeEnd = $workDate;
        }
    }

    if (isset($departmentMetrics[$deptId])) {
        $departmentMetrics[$deptId]['assignments']++;
    }

    if (!$isActiveAssignment) {
        $assignmentTotals['cancelled']++;
        continue;
    }

    $assignmentTotals['active']++;
    $assignmentTotals['assigned_hours'] += $durationHours;
    if ($workDate !== '') {
        $coveredDates[$workDate] = true;
        if (!isset($coverageByDepartment[$deptId])) {
            $coverageByDepartment[$deptId] = [];
        }
        $coverageByDepartment[$deptId][$workDate] = true;
    }

    if ($shiftId > 0) {
        $usedShiftIds[$shiftId] = true;
    }

    if (isset($departmentMetrics[$deptId])) {
        $departmentMetrics[$deptId]['active_assignments']++;
        $departmentMetrics[$deptId]['hours'] += $durationHours;
    }

    if ($userId > 0) {
        if (!isset($userWorkloadMap[$userId])) {
            $userWorkloadMap[$userId] = [
                'user_id' => $userId,
                'user_name' => (string) ($assignment['user_name'] ?? 'User'),
                'assignments' => 0,
                'hours' => 0.0,
                'days' => [],
                'shifts' => [],
            ];
        }
        $userWorkloadMap[$userId]['assignments']++;
        $userWorkloadMap[$userId]['hours'] += $durationHours;
        if ($workDate !== '') {
            $userWorkloadMap[$userId]['days'][$workDate] = true;
        }
        if (!isset($userWorkloadMap[$userId]['shifts'][$shiftName])) {
            $userWorkloadMap[$userId]['shifts'][$shiftName] = 0;
        }
        $userWorkloadMap[$userId]['shifts'][$shiftName]++;
    }
}

if ($assignmentRangeStart === '' || $assignmentRangeEnd === '') {
    $assignmentRangeStart = $todayDate;
    $assignmentRangeEnd = $currentMonthEnd;
}

$assignmentRangeStart = max($assignmentRangeStart, $currentMonthStart);
$assignmentRangeEnd = max($assignmentRangeStart, $assignmentRangeEnd);
$autoAssignDefaultStart = max($todayDate, $currentMonthStart);
$autoAssignDefaultEnd = max($autoAssignDefaultStart, $currentMonthEnd);

$periodStart = new DateTimeImmutable($assignmentRangeStart);
$periodEnd = new DateTimeImmutable($assignmentRangeEnd);
$periodEndExclusive = $periodEnd->modify('+1 day');
$periodDays = (int) $periodStart->diff($periodEnd)->days + 1;
$assignmentTotals['days_range'] = max(1, $periodDays);
$assignmentTotals['covered_days'] = count($coveredDates);
$assignmentTotals['unassigned_shift_templates'] = max(0, count($shifts) - count($usedShiftIds));

$dayKeysInRange = [];
foreach (new DatePeriod($periodStart, new DateInterval('P1D'), $periodEndExclusive) as $date) {
    $dayKeysInRange[] = $date->format('Y-m-d');
}
foreach ($departmentMetrics as $deptId => $metric) {
    $uncoveredCount = 0;
    foreach ($dayKeysInRange as $dayKey) {
        if (!isset($coverageByDepartment[$deptId][$dayKey])) {
            $uncoveredCount++;
        }
    }
    $departmentMetrics[$deptId]['uncovered_days'] = $uncoveredCount;
}

$departmentCoverageRows = array_values($departmentMetrics);
usort($departmentCoverageRows, static function (array $a, array $b): int {
    return $b['uncovered_days'] <=> $a['uncovered_days'];
});

$userWorkloadRows = array_values($userWorkloadMap);
foreach ($userWorkloadRows as &$workloadRow) {
    $workloadRow['hours'] = round((float) $workloadRow['hours'], 2);
    $workloadRow['days_count'] = count($workloadRow['days']);
    arsort($workloadRow['shifts']);
    $shiftChunks = [];
    foreach ($workloadRow['shifts'] as $shiftName => $count) {
        $shiftChunks[] = $shiftName . ' (' . $count . ')';
        if (count($shiftChunks) >= 3) {
            break;
        }
    }
    $workloadRow['shift_preview'] = implode(', ', $shiftChunks);
}
unset($workloadRow);
usort($userWorkloadRows, static function (array $a, array $b): int {
    if ($a['hours'] === $b['hours']) {
        return $b['assignments'] <=> $a['assignments'];
    }

    return $b['hours'] <=> $a['hours'];
});

$assignmentTotals['assigned_hours'] = round((float) $assignmentTotals['assigned_hours'], 2);
$openAssignmentsCount = count(array_filter(
    $assignmentsCurrentMonth,
    static fn(array $assignment): bool => (int) ($assignment['user_id'] ?? 0) <= 0 || (($assignment['status'] ?? '') === 'open')
));

$attendanceByUser = [];
foreach ($visibleUsers as $attendanceUser) {
    $attendanceByUser[(int) ($attendanceUser['id'] ?? 0)] = 0;
}
foreach ($attendances as $attendanceRow) {
    $attendanceUserId = (int) ($attendanceRow['user_id'] ?? 0);
    if ($attendanceUserId > 0 && isset($attendanceByUser[$attendanceUserId])) {
        $attendanceByUser[$attendanceUserId]++;
    }
}

$attendanceAssignableShifts = array_values(array_filter(
    $assignmentsCurrentMonth,
    static fn(array $assignment): bool => (int) ($assignment['user_id'] ?? 0) > 0
        && !in_array((string) ($assignment['status'] ?? ''), ['cancelled', 'open'], true)
));

$departmentCreateHeadUsers = array_values(array_filter(
    $visibleUsers,
    static fn(array $u): bool => ((int) ($u['company_id'] ?? 0) === $scopeCompanyId) && ((int) ($u['department_id'] ?? 0) === 0)
));
?>
<section class="dashboard-modal dashboard-settings-modal" id="modal-settings" hidden>
    <div class="crud-modal-card">
        <button type="button" class="dashboard-modal-close" data-modal-close aria-label="<?php echo e(t('settings.close')); ?>">&times;</button>

        <div class="crud-modal-head settings-modal-head">
            <div>
                <h2 id="settings-modal-title"><?php echo e(t('settings.title')); ?></h2>
                <p id="settings-modal-subtitle" class="crud-modal-subtitle"><?php echo e(t('settings.subtitle')); ?></p>
            </div>
            <div class="settings-summary settings-summary--compact">
                <div class="settings-summary-card">
                    <span class="settings-summary-label"><?php echo e(t('settings.company')); ?></span>
                    <strong><?php echo e($scopeCompanyName); ?></strong>
                </div>
                <div class="settings-summary-card">
                    <span class="settings-summary-label"><?php echo e(t('settings.departments')); ?></span>
                    <strong><?php echo count($departments); ?></strong>
                </div>
                <div class="settings-summary-card">
                    <span class="settings-summary-label"><?php echo e(t('settings.users')); ?></span>
                    <strong><?php echo count($visibleUsers); ?></strong>
                </div>
                <div class="settings-summary-card">
                    <span class="settings-summary-label"><?php echo e(t('settings.shifts')); ?></span>
                    <strong><?php echo count($shifts); ?></strong>
                </div>
            </div>

            <?php if ($currentRole === 'super_admin' && !empty($scopeCompanies)): ?>
                <form method="get" class="settings-company-switcher">
                    <input type="hidden" name="route" value="dashboard">
                    <input type="hidden" name="modal" value="settings">
                    <input type="hidden" name="settings_tab" value="" data-settings-tab-input>
                    <label class="settings-field">
                        <?php echo e(t('settings.company_in_settings')); ?>
                        <select name="settings_company_id" data-settings-company-select>
                            <?php foreach ($scopeCompanies as $company): ?>
                                <option value="<?php echo (int) ($company['id'] ?? 0); ?>" <?php echo ((int) ($company['id'] ?? 0) === $scopeCompanyId) ? 'selected' : ''; ?>>
                                    <?php echo e($company['name'] ?? 'Company'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            <?php endif; ?>
        </div>

        <div class="settings-tabs" role="tablist" aria-label="<?php echo e(t('settings.management_rubrics')); ?>">
            <button type="button" class="settings-tab" data-settings-tab="users"><?php echo e(t('settings.users')); ?></button>
            <?php if ($currentRole !== 'department_manager'): ?>
                <button type="button" class="settings-tab" data-settings-tab="departments"><?php echo e(t('settings.departments')); ?></button>
            <?php endif; ?>
            <button type="button" class="settings-tab" data-settings-tab="shifts"><?php echo e(t('settings.shifts')); ?></button>
            <button type="button" class="settings-tab" data-settings-tab="assignments"><?php echo e(t('settings.assignments')); ?></button>
            <button type="button" class="settings-tab" data-settings-tab="attendances"><?php echo e(t('settings.attendances')); ?></button>
        </div>

        <div class="crud-modal-body settings-modal-body">
            <section class="crud-panel settings-panel" data-settings-panel="assignments" hidden>
                <?php
                    $employeeAssignmentStats = [];
                    foreach ($visibleUsers as $userStat) {
                        $statUserId = (int) ($userStat['id'] ?? 0);
                        if ($statUserId <= 0) {
                            continue;
                        }
                        $employeeAssignmentStats[$statUserId] = [
                            'assigned' => 0,
                            'sick' => 0,
                            'vacation' => 0,
                            'rest' => 0,
                        ];
                    }

                    foreach ($assignmentsCurrentMonth as $assignmentStat) {
                        $statUserId = (int) ($assignmentStat['user_id'] ?? 0);
                        if ($statUserId <= 0 || !isset($employeeAssignmentStats[$statUserId])) {
                            continue;
                        }

                        $statusValue = (string) ($assignmentStat['status'] ?? 'assigned');
                        if (in_array($statusValue, ['cancelled', 'open'], true)) {
                            continue;
                        }

                        $employeeAssignmentStats[$statUserId]['assigned']++;
                        $kindValue = strtolower((string) ($assignmentStat['shift_kind'] ?? 'work'));
                        if ($kindValue === 'sick') {
                            $employeeAssignmentStats[$statUserId]['sick']++;
                        } elseif ($kindValue === 'vacation') {
                            $employeeAssignmentStats[$statUserId]['vacation']++;
                        } elseif ($kindValue === 'rest') {
                            $employeeAssignmentStats[$statUserId]['rest']++;
                        }
                    }
                ?>
                <div class="settings-panel-head">
                    <div>
                        <h3><?php echo e(t('settings.assignments_head')); ?></h3>
                        <p class="crud-modal-subtitle"><?php echo e(t('settings.assignments_subtitle')); ?></p>
                    </div>
                </div>

                <div class="settings-create-row settings-auto-assign-row">
                    <div class="settings-list-cols settings-list-cols-shift-create">
                            <label class="settings-field"><?php echo e(t('settings.auto_assign_shift')); ?>
                            <select data-auto-assign-shift>
                                <option value="0"><?php echo e(t('settings.all_open_work_shifts')); ?></option>
                                <?php foreach ($shifts as $shift): ?>
                                    <option value="<?php echo (int) ($shift['id'] ?? 0); ?>"><?php echo e(($shift['icon'] ?? '🕒') . ' ' . ($shift['name'] ?? 'Shift')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="settings-field"><?php echo e(t('settings.from_date')); ?><input data-auto-assign-range-start type="date" value="<?php echo e($autoAssignDefaultStart); ?>" min="<?php echo e($currentMonthStart); ?>"></label>
                        <label class="settings-field"><?php echo e(t('settings.to_date')); ?><input data-auto-assign-range-end type="date" value="<?php echo e($autoAssignDefaultEnd); ?>" min="<?php echo e($currentMonthStart); ?>"></label>
                        <label class="settings-field"><?php echo e(t('settings.minimum_employees')); ?><input data-auto-assign-min-employees type="number" min="0" step="1" value="1"></label>
                        <label class="settings-field"><?php echo e(t('settings.maximum_employees')); ?><input data-auto-assign-max-employees type="number" min="1" step="1" value="3"></label>
                        <div class="settings-inline-actions">
                            <button type="button" class="admin-action-link" data-auto-assign-open><?php echo e(t('settings.auto_assign_open')); ?></button>
                            <button type="button" class="admin-action-link admin-action-link-secondary" data-auto-assign-clear><?php echo e(t('settings.clear_assigned_shifts')); ?></button>
                        </div>
                    </div>
                </div>

                <section class="settings-analytics-card settings-assignment-employee-index" data-assignment-employee-index>
                    <div class="settings-assignment-employee-index-head">
                        <h4><?php echo e(t('settings.employees')); ?></h4>
                        <p class="crud-modal-subtitle"><?php echo e(t('settings.select_employee_hint')); ?></p>
                    </div>
                    <?php if (empty($visibleUsers)): ?>
                        <div class="crud-empty-state"><?php echo e(t('settings.no_active_employees')); ?></div>
                    <?php else: ?>
                        <?php
                            $departmentNameById = [];
                            foreach ($visibleDepartments as $deptOption) {
                                $departmentNameById[(int) ($deptOption['id'] ?? 0)] = (string) ($deptOption['name'] ?? 'Department');
                            }

                            $usersByDepartment = [];
                            foreach ($visibleUsers as $employeeItem) {
                                $employeeDepartmentId = (int) ($employeeItem['department_id'] ?? 0);
                                $employeeDepartmentName = (string) ($employeeItem['department_name'] ?? ($departmentNameById[$employeeDepartmentId] ?? t('settings.unassigned')));
                                if ($employeeDepartmentName === '') {
                                    $employeeDepartmentName = t('settings.unassigned');
                                }
                                if (!isset($usersByDepartment[$employeeDepartmentName])) {
                                    $usersByDepartment[$employeeDepartmentName] = [];
                                }
                                $usersByDepartment[$employeeDepartmentName][] = $employeeItem;
                            }

                            ksort($usersByDepartment);
                        ?>
                        <?php foreach ($usersByDepartment as $departmentLabel => $departmentUsers): ?>
                            <section class="settings-assignment-employee-group">
                                <div class="settings-assignment-employee-group-title">🏷 <?php echo e($departmentLabel); ?></div>
                                <div class="settings-assignment-employee-list">
                                    <?php foreach ($departmentUsers as $employeeItem): ?>
                                        <?php
                                            $employeeId = (int) ($employeeItem['id'] ?? 0);
                                            $employeeName = trim((string) (($employeeItem['first_name'] ?? '') . ' ' . ($employeeItem['last_name'] ?? '')));
                                            if ($employeeName === '') {
                                                $employeeName = (string) ($employeeItem['email'] ?? ('Employee #' . $employeeId));
                                            }
                                            $employeeStat = $employeeAssignmentStats[$employeeId] ?? ['assigned' => 0, 'sick' => 0, 'vacation' => 0, 'rest' => 0];
                                        ?>
                                        <button
                                            type="button"
                                            class="settings-assignment-employee-item"
                                            data-assignment-employee-open
                                            data-user-id="<?php echo $employeeId; ?>"
                                            data-user-name="<?php echo e($employeeName); ?>"
                                            data-user-department-id="<?php echo (int) ($employeeItem['department_id'] ?? 0); ?>"
                                            data-user-department-name="<?php echo e($departmentLabel); ?>"
                                        >
                                            <strong><?php echo e($employeeName); ?></strong>
                                            <span><?php echo e($employeeItem['email'] ?? ''); ?></span>
                                            <small>
                                                <?php echo e(t('settings.assigned')); ?>: <?php echo (int) ($employeeStat['assigned'] ?? 0); ?>
                                                • <?php echo e(t('settings.sick')); ?>: <?php echo (int) ($employeeStat['sick'] ?? 0); ?>
                                                • <?php echo e(t('settings.vacation')); ?>: <?php echo (int) ($employeeStat['vacation'] ?? 0); ?>
                                                • <?php echo e(t('settings.rest')); ?>: <?php echo (int) ($employeeStat['rest'] ?? 0); ?>
                                            </small>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <script type="application/json" data-assignment-shift-catalog>
                    <?php
                        $shiftCatalog = array_map(static function (array $shift): array {
                            return [
                                'id' => (int) ($shift['id'] ?? 0),
                                'name' => (string) ($shift['name'] ?? 'Shift'),
                                'icon' => (string) ($shift['icon'] ?? '🕒'),
                                'kind' => (string) ($shift['kind'] ?? 'work'),
                                'start_time' => (string) ($shift['start_time'] ?? ''),
                                'end_time' => (string) ($shift['end_time'] ?? ''),
                                'department_id' => (int) ($shift['department_id'] ?? 0),
                                'department_name' => (string) ($shift['department_name'] ?? 'Department'),
                            ];
                        }, $shifts);
                        echo json_encode($shiftCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    ?>
                </script>

                <section class="settings-assignment-employee-modal" data-assignment-employee-modal hidden>
                    <div class="settings-assignment-employee-window">
                        <header class="settings-assignment-employee-window-head">
                            <div>
                                <h4 data-assignment-modal-title><?php echo e(t('settings.employee_details')); ?></h4>
                                <p class="crud-modal-subtitle" data-assignment-modal-subtitle><?php echo e(t('settings.assigned_shifts_hint')); ?></p>
                            </div>
                            <button type="button" class="dashboard-modal-close" data-assignment-employee-close aria-label="<?php echo e(t('settings.close')); ?>">&times;</button>
                        </header>
                        <div class="settings-assignment-employee-window-grid">
                            <section class="settings-analytics-card">
                                <h5><?php echo e(t('settings.availability_rules')); ?></h5>
                                <p class="crud-modal-subtitle"><?php echo e(t('settings.weekly_rest_and_dates')); ?></p>
                                <div class="settings-auto-rule-weekdays" data-assignment-modal-weekdays></div>
                                <div class="settings-auto-rule-specials">
                                    <label class="settings-field"><?php echo e(t('settings.unavailable_day')); ?><input type="date" data-assignment-modal-special-date></label>
                                    <label class="settings-field"><?php echo e(t('settings.reason')); ?>
                                        <select data-assignment-modal-special-reason>
                                            <option value="rest"><?php echo e(t('settings.weekly_rest')); ?></option>
                                            <option value="leave"><?php echo e(t('crud.request_leave')); ?></option>
                                            <option value="vacation"><?php echo e(t('settings.vacation')); ?></option>
                                            <option value="sick"><?php echo e(t('settings.sick')); ?></option>
                                            <option value="special"><?php echo e(t('settings.special_day')); ?></option>
                                        </select>
                                    </label>
                                    <button type="button" class="admin-action-link admin-action-link-secondary" data-assignment-modal-add-special><?php echo e(t('settings.add_date')); ?></button>
                                </div>
                                <div class="settings-assignment-modal-range-row">
                                    <label class="settings-field"><?php echo e(t('settings.from_date')); ?><input type="date" data-assignment-modal-special-from></label>
                                    <label class="settings-field">To date<input type="date" data-assignment-modal-special-to></label>
                                    <button type="button" class="admin-action-link admin-action-link-secondary" data-assignment-modal-add-special-range>Add range</button>
                                    <button type="button" class="admin-action-link admin-action-link-secondary" data-assignment-modal-rules-reset>Reset rules</button>
                                </div>
                                <div class="settings-auto-rule-special-list" data-assignment-modal-special-list></div>
                                <h5>Current Month Availability</h5>
                                <div class="settings-assignment-weekly-grid" data-assignment-modal-weekly></div>
                                <h5>Unavailable Dates (Current Month)</h5>
                                <div class="settings-auto-rule-special-list" data-assignment-modal-month-unavailable></div>
                            </section>
                            <section class="settings-analytics-card">
                                <h5>Assigned Shifts</h5>
                                <div class="settings-assignment-modal-shift-list" data-assignment-modal-shifts></div>
                            </section>
                            <section class="settings-analytics-card settings-assignment-modal-open-slots">
                                <h5>Open Shifts to Cover</h5>
                                <p class="crud-modal-subtitle">Select only open shifts in the employee department and within the date range below.</p>
                                <div class="settings-assignment-modal-open-range">
                                    <label class="settings-field">From date<input type="date" data-assignment-modal-open-from></label>
                                    <label class="settings-field">To date<input type="date" data-assignment-modal-open-to></label>
                                    <label class="settings-field">Shift
                                        <select data-assignment-modal-open-shift>
                                            <option value="0">All department shifts</option>
                                        </select>
                                    </label>
                                </div>
                                <div class="settings-inline-actions">
                                    <button type="button" class="admin-action-link" data-assignment-modal-open-cover-all>Cover all available dates</button>
                                    <button type="button" class="admin-action-link admin-action-link-secondary" data-assignment-modal-open-clear>Clear selection</button>
                                    <button type="button" class="admin-action-link admin-action-link-secondary" data-assignment-modal-open-reselect>Re-select available</button>
                                    <button type="button" class="admin-action-link" data-assignment-modal-open-assign>Assign selected</button>
                                </div>
                                <div class="settings-assignment-open-slot-list" data-assignment-modal-open-list></div>
                            </section>
                            <section class="settings-analytics-card settings-assignment-modal-open-slots">
                                <h5>Assign Absence Range</h5>
                                <p class="crud-modal-subtitle">Assign vacation, sick leave, or rest day from date to date.</p>
                                <div class="settings-assignment-modal-open-range">
                                    <label class="settings-field">From date<input type="date" data-assignment-modal-absence-from></label>
                                    <label class="settings-field">To date<input type="date" data-assignment-modal-absence-to></label>
                                    <label class="settings-field">Type
                                        <select data-assignment-modal-absence-type>
                                            <option value="vacation">Vacation</option>
                                            <option value="sick">Sick leave</option>
                                            <option value="rest">Rest day</option>
                                        </select>
                                    </label>
                                </div>
                                <div class="settings-inline-actions">
                                    <button type="button" class="admin-action-link" data-assignment-modal-absence-assign>Assign absence range</button>
                                    <button type="button" class="admin-action-link admin-action-link-secondary" data-assignment-modal-absence-reset>Reset dates</button>
                                </div>
                            </section>
                        </div>
                    </div>
                </section>

                <div class="settings-analytics-grid">
                    <section class="settings-analytics-card">
                        <h4>Coverage by Department</h4>
                        <p class="crud-modal-subtitle">
                            Current Month: <?php echo e($currentMonthLabel); ?> (range: <?php echo e($assignmentRangeStart); ?> to <?php echo e($assignmentRangeEnd); ?>)
                        </p>
                        <?php if (empty($departmentCoverageRows)): ?>
                            <div class="crud-empty-state">No department data available.</div>
                        <?php else: ?>
                            <div class="settings-analytics-list">
                                <?php foreach (array_slice($departmentCoverageRows, 0, 12) as $deptMetric): ?>
                                    <article class="settings-analytics-item">
                                        <div class="settings-analytics-item-head">
                                            <strong>
                                                <span class="settings-dept-title-icon" style="background: color-mix(in srgb, <?php echo e($deptMetric['department_color'] ?? '#b98b12'); ?> 18%, #ffffff 82%);">
                                                    <?php echo e($deptMetric['department_icon'] ?? '🏷️'); ?>
                                                </span>
                                                <?php echo e($deptMetric['department_name'] ?? 'Department'); ?>
                                            </strong>
                                        </div>
                                        <div class="settings-analytics-metrics">
                                            <span>Assignments: <?php echo (int) ($deptMetric['active_assignments'] ?? 0); ?></span>
                                            <span>Hours: <?php echo e(number_format((float) ($deptMetric['hours'] ?? 0), 2)); ?>h</span>
                                            <span class="settings-metric-warning">Uncovered days: <?php echo (int) ($deptMetric['uncovered_days'] ?? 0); ?></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="settings-analytics-card">
                        <h4>Workload by User</h4>
                        <p class="crud-modal-subtitle">Current Month <?php echo e($currentMonthLabel); ?>: hours, days, and most frequent shifts assigned to each user.</p>
                        <?php if (empty($userWorkloadRows)): ?>
                            <div class="crud-empty-state">No user workload data available.</div>
                        <?php else: ?>
                            <div class="settings-analytics-list">
                                <?php foreach (array_slice($userWorkloadRows, 0, 12) as $workload): ?>
                                    <article class="settings-analytics-item">
                                        <div class="settings-analytics-item-head">
                                            <strong><?php echo e($workload['user_name'] ?? 'User'); ?></strong>
                                            <span><?php echo (int) ($workload['assignments'] ?? 0); ?> assignments</span>
                                        </div>
                                        <div class="settings-analytics-metrics">
                                            <span>Hours: <?php echo e(number_format((float) ($workload['hours'] ?? 0), 2)); ?>h</span>
                                            <span>Days: <?php echo (int) ($workload['days_count'] ?? 0); ?></span>
                                            <span>Shifts: <?php echo e($workload['shift_preview'] ?: 'n/a'); ?></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="settings-inline-actions settings-assignment-list-toggle-row">
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-assignment-list-toggle aria-expanded="false">
                        Show daily assignments list
                    </button>
                </div>

                <div class="settings-list-wrap" data-assignment-list-wrap hidden>
                    <p class="crud-modal-subtitle">Daily assignments list for Current Month <?php echo e($currentMonthLabel); ?>.</p>
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-assignment">
                        <strong>Date</strong>
                        <span>Department</span>
                        <span>Shift</span>
                        <span>User</span>
                        <span>Workload</span>
                        <span>Status</span>
                        <span>Actions</span>
                    </div>

                    <?php if (empty($assignmentsCurrentMonth)): ?>
                        <div class="crud-empty-state">No assignments available.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($assignmentsCurrentMonth, 0, 250) as $assignment): ?>
                            <article
                                class="settings-list-item-wrap"
                                data-assignment-id="<?php echo (int) ($assignment['assignment_id'] ?? 0); ?>"
                                data-assignment-user-id="<?php echo (int) ($assignment['user_id'] ?? 0); ?>"
                                data-assignment-user-name="<?php echo e($assignment['user_name'] ?: 'Open slot'); ?>"
                                data-assignment-work-date="<?php echo e($assignment['work_date'] ?? ''); ?>"
                                data-assignment-shift-id="<?php echo (int) ($assignment['shift_id'] ?? 0); ?>"
                                data-assignment-shift-name="<?php echo e($assignment['shift_name'] ?? '--'); ?>"
                                data-assignment-shift-icon="<?php echo e($assignment['shift_icon'] ?? '🕒'); ?>"
                                data-assignment-shift-kind="<?php echo e($assignment['shift_kind'] ?? 'work'); ?>"
                                data-assignment-status="<?php echo e($assignment['status'] ?? ((int) ($assignment['user_id'] ?? 0) > 0 ? 'assigned' : 'open')); ?>"
                                data-assignment-start-time="<?php echo e($assignment['start_time'] ?? ''); ?>"
                                data-assignment-end-time="<?php echo e($assignment['end_time'] ?? ''); ?>"
                                data-assignment-department-id="<?php echo (int) ($assignment['department_id'] ?? 0); ?>"
                                data-assignment-department-name="<?php echo e($assignment['department_name'] ?? '--'); ?>"
                            >
                                <div class="settings-list-row settings-list-cols settings-list-cols-assignment">
                                    <strong><?php echo e($assignment['work_date'] ?? ''); ?></strong>
                                    <span><?php echo e($assignment['department_name'] ?? '--'); ?></span>
                                    <span>
                                        <?php echo e($assignment['shift_icon'] ?? '🕒'); ?>
                                        <?php echo e($assignment['shift_name'] ?? '--'); ?>
                                        <small class="settings-meta-inline"><?php echo e(ucfirst((string) ($assignment['shift_kind'] ?? 'work'))); ?></small>
                                        <?php if (!empty($assignment['shift_description'])): ?>
                                            <small class="settings-meta-inline"><?php echo e($assignment['shift_description']); ?></small>
                                        <?php endif; ?>
                                    </span>
                                    <span><?php echo e($assignment['user_name'] ?: 'Open slot'); ?></span>
                                    <?php
                                        $assignmentDuration = $durationHoursForTimes(
                                            (string) ($assignment['start_time'] ?? ''),
                                            (string) ($assignment['end_time'] ?? '')
                                        );
                                    ?>
                                    <span>
                                        <?php echo e(($assignment['start_time'] ?? '--:--') . ' - ' . ($assignment['end_time'] ?? '--:--')); ?>
                                        <small class="settings-meta-inline"><?php echo e(number_format($assignmentDuration, 2)); ?>h</small>
                                    </span>
                                    <span><?php echo e($assignment['status'] ?? ((int) ($assignment['user_id'] ?? 0) > 0 ? 'assigned' : 'open')); ?></span>
                                    <div class="settings-inline-actions">
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-assignment-edit" aria-label="Edit assignment" title="Edit assignment">✎</button>
                                    </div>
                                </div>
                                <div class="settings-edit-drawer" hidden>
                                    <div class="settings-list-cols settings-list-cols-assignment-edit">
                                        <label class="settings-field">Work date
                                            <input data-field="work_date" type="date" value="<?php echo e($assignment['work_date'] ?? ''); ?>">
                                        </label>
                                        <label class="settings-field">Shift
                                            <select data-field="shift_id">
                                                <?php foreach ($shifts as $shift): ?>
                                                    <option value="<?php echo (int) ($shift['id'] ?? 0); ?>" <?php echo ((int) ($assignment['shift_id'] ?? 0) === (int) ($shift['id'] ?? 0)) ? 'selected' : ''; ?>>
                                                        <?php echo e(($shift['icon'] ?? '🕒') . ' ' . ($shift['name'] ?? 'Shift') . ' • ' . ($shift['department_name'] ?? '')); ?><?php echo !empty($shift['description']) ? e(' • ' . $shift['description']) : ''; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="settings-field">Employee
                                            <select data-field="user_id">
                                                <option value="">Open slot</option>
                                                <?php foreach ($visibleUsers as $userOption): ?>
                                                    <option value="<?php echo (int) ($userOption['id'] ?? 0); ?>" <?php echo ((int) ($assignment['user_id'] ?? 0) === (int) ($userOption['id'] ?? 0)) ? 'selected' : ''; ?>>
                                                        <?php echo e(trim((string) (($userOption['first_name'] ?? '') . ' ' . ($userOption['last_name'] ?? ''))) ?: ($userOption['email'] ?? 'User')); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="settings-field">Status
                                            <select data-field="status">
                                                <?php $assignmentStatus = (string) ($assignment['status'] ?? ((int) ($assignment['user_id'] ?? 0) > 0 ? 'assigned' : 'open')); ?>
                                                <option value="open" <?php echo $assignmentStatus === 'open' ? 'selected' : ''; ?>>Open</option>
                                                <option value="assigned" <?php echo $assignmentStatus === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                <option value="in_progress" <?php echo $assignmentStatus === 'in_progress' ? 'selected' : ''; ?>>In progress</option>
                                                <option value="completed" <?php echo $assignmentStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $assignmentStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </label>
                                        <div class="settings-inline-actions">
                                            <button type="button" class="admin-action-link settings-assignment-save">Save</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-assignment-unassign">Unassign</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-assignment-cancel">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </section>

            <section class="crud-panel settings-panel" data-settings-panel="attendances" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Attendances</h3>
                        <p class="crud-modal-subtitle">Manage attendance signatures and presence registration by department and employee.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($attendances); ?> records</span>
                    </div>
                </div>

                <?php if (in_array($currentRole, ['super_admin', 'admin'], true) && $scopeCompanyId > 0): ?>
                    <div class="settings-list-item-wrap settings-company-ip-wrap">
                        <div class="settings-list-row settings-company-ip-row">
                            <div>
                                <strong>Authorized Wi-Fi IP for attendance signature</strong>
                                <p class="crud-modal-subtitle">Leave empty to allow attendance signature from any network. If set, signatures are accepted only from this IP.</p>
                            </div>
                            <div class="settings-company-ip-controls">
                                <input
                                    type="text"
                                    value="<?php echo e($scopeCompanySignatureIp); ?>"
                                    placeholder="Example: 192.168.1.120"
                                    data-company-signature-ip
                                    data-company-id="<?php echo (int) $scopeCompanyId; ?>"
                                >
                                <button type="button" class="admin-action-link" data-company-signature-ip-save>Save Wi-Fi IP</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <section class="settings-analytics-card settings-assignment-employee-index" data-attendance-employee-index>
                    <div class="settings-assignment-employee-index-head">
                        <h4>Employees by Department</h4>
                        <p class="crud-modal-subtitle">Open an employee profile, choose an assigned shift, and register attendance with digital signature.</p>
                    </div>
                    <?php if (empty($visibleUsers)): ?>
                        <div class="crud-empty-state">No users available for attendance management.</div>
                    <?php else: ?>
                        <?php
                            $attendanceUsersByDepartment = [];
                            foreach ($visibleUsers as $attendanceUserItem) {
                                $attendanceDepartmentName = (string) ($attendanceUserItem['department_name'] ?? 'Unassigned');
                                if ($attendanceDepartmentName === '') {
                                    $attendanceDepartmentName = 'Unassigned';
                                }
                                if (!isset($attendanceUsersByDepartment[$attendanceDepartmentName])) {
                                    $attendanceUsersByDepartment[$attendanceDepartmentName] = [];
                                }
                                $attendanceUsersByDepartment[$attendanceDepartmentName][] = $attendanceUserItem;
                            }
                            ksort($attendanceUsersByDepartment);
                        ?>
                        <?php foreach ($attendanceUsersByDepartment as $attendanceDepartmentLabel => $attendanceDepartmentUsers): ?>
                            <section class="settings-assignment-employee-group">
                                <div class="settings-assignment-employee-group-title">Dept: <?php echo e($attendanceDepartmentLabel); ?></div>
                                <div class="settings-assignment-employee-list">
                                    <?php foreach ($attendanceDepartmentUsers as $attendanceUserItem): ?>
                                        <?php
                                            $attendanceUserId = (int) ($attendanceUserItem['id'] ?? 0);
                                            $attendanceUserName = trim((string) (($attendanceUserItem['first_name'] ?? '') . ' ' . ($attendanceUserItem['last_name'] ?? '')));
                                            if ($attendanceUserName === '') {
                                                $attendanceUserName = (string) ($attendanceUserItem['email'] ?? ('User #' . $attendanceUserId));
                                            }
                                            $attendanceCount = (int) ($attendanceByUser[$attendanceUserId] ?? 0);
                                        ?>
                                        <button
                                            type="button"
                                            class="settings-assignment-employee-item"
                                            data-attendance-employee-open
                                            data-user-id="<?php echo $attendanceUserId; ?>"
                                            data-user-name="<?php echo e($attendanceUserName); ?>"
                                            data-user-department-name="<?php echo e($attendanceDepartmentLabel); ?>"
                                        >
                                            <strong><?php echo e($attendanceUserName); ?></strong>
                                            <span><?php echo e($attendanceUserItem['email'] ?? ''); ?></span>
                                            <small>Attendance records: <?php echo $attendanceCount; ?></small>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <script type="application/json" data-attendance-assignment-catalog>
                    <?php
                        $attendanceAssignmentCatalog = array_map(static function (array $assignment): array {
                            return [
                                'assignment_id' => (int) ($assignment['assignment_id'] ?? 0),
                                'user_id' => (int) ($assignment['user_id'] ?? 0),
                                'user_name' => (string) ($assignment['user_name'] ?? ''),
                                'work_date' => (string) ($assignment['work_date'] ?? ''),
                                'shift_id' => (int) ($assignment['shift_id'] ?? 0),
                                'shift_name' => (string) ($assignment['shift_name'] ?? 'Shift'),
                                'department_name' => (string) ($assignment['department_name'] ?? 'Department'),
                                'status' => (string) ($assignment['status'] ?? 'assigned'),
                            ];
                        }, $attendanceAssignableShifts);
                        echo json_encode($attendanceAssignmentCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    ?>
                </script>

                <script type="application/json" data-attendance-record-catalog>
                    <?php
                        $attendanceRecordCatalog = array_map(static function (array $attendance): array {
                            return [
                                'id' => (int) ($attendance['id'] ?? 0),
                                'user_id' => (int) ($attendance['user_id'] ?? 0),
                                'user_shift_id' => (int) ($attendance['user_shift_id'] ?? 0),
                                'user_name' => (string) ($attendance['user_name'] ?? ''),
                                'department_name' => (string) ($attendance['department_name'] ?? ''),
                                'shift_name' => (string) ($attendance['shift_name'] ?? ''),
                                'work_date' => (string) ($attendance['work_date'] ?? ''),
                                'status' => (string) ($attendance['status'] ?? 'present'),
                                'check_in_time' => (string) ($attendance['check_in_time'] ?? ''),
                                'check_out_time' => (string) ($attendance['check_out_time'] ?? ''),
                                'digital_signature_id' => (int) ($attendance['digital_signature_id'] ?? 0),
                            ];
                        }, $attendances);
                        echo json_encode($attendanceRecordCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    ?>
                </script>

                <div class="settings-assignment-employee-modal" data-attendance-employee-modal hidden>
                    <div class="settings-assignment-employee-window">
                        <header class="settings-assignment-employee-window-head">
                            <div>
                                <h4 data-attendance-modal-title>Attendance signature</h4>
                                <p class="crud-modal-subtitle" data-attendance-modal-subtitle>Select shift and sign to record attendance.</p>
                            </div>
                            <button type="button" class="dashboard-modal-close" data-attendance-employee-close aria-label="Close attendance modal">&times;</button>
                        </header>

                        <div class="settings-assignment-employee-window-grid">
                            <section class="settings-analytics-card">
                                <label class="settings-field">Assigned shift
                                    <select data-attendance-modal-user-shift>
                                        <option value="">Select assigned shift</option>
                                    </select>
                                </label>
                                <label class="settings-field">Attendance status
                                    <select data-attendance-modal-status>
                                        <option value="present">Present</option>
                                        <option value="late">Late</option>
                                        <option value="absent">Absent</option>
                                        <option value="early_departure">Early departure</option>
                                    </select>
                                </label>
                                <div class="employee-signature-pad-shell">
                                    <span>Digital signature</span>
                                    <canvas width="520" height="180" data-attendance-signature-canvas aria-label="Attendance signature pad"></canvas>
                                    <small class="employee-signature-error" data-attendance-signature-error></small>
                                    <div class="employee-signature-pad-actions">
                                        <button type="button" class="admin-action-link admin-action-link-secondary" data-attendance-signature-clear>Clear signature</button>
                                        <small>Use touch, stylus, or mouse to sign.</small>
                                    </div>
                                </div>
                                <div class="settings-inline-actions">
                                    <button type="button" class="admin-action-link" data-attendance-signature-save>Record attendance</button>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                <div class="settings-assignment-employee-modal" data-attendance-record-modal hidden>
                    <div class="settings-assignment-employee-window">
                        <header class="settings-assignment-employee-window-head">
                            <div>
                                <h4 data-attendance-record-title>Edit attendance</h4>
                                <p class="crud-modal-subtitle" data-attendance-record-subtitle>Update attendance status and times or cancel the registration.</p>
                            </div>
                            <button type="button" class="dashboard-modal-close" data-attendance-record-close aria-label="Close attendance edit modal">&times;</button>
                        </header>

                        <div class="settings-assignment-employee-window-grid">
                            <section class="settings-analytics-card">
                                <label class="settings-field">Attendance status
                                    <select data-attendance-record-status>
                                        <option value="present">Present</option>
                                        <option value="late">Late</option>
                                        <option value="absent">Absent</option>
                                        <option value="early_departure">Early departure</option>
                                    </select>
                                </label>
                                <div class="settings-assignment-modal-range-row">
                                    <label class="settings-field">Check-in time
                                        <input type="time" data-attendance-record-checkin>
                                    </label>
                                    <label class="settings-field">Check-out time
                                        <input type="time" data-attendance-record-checkout>
                                    </label>
                                </div>
                                <div class="settings-inline-actions">
                                    <button type="button" class="admin-action-link" data-attendance-record-save>Save changes</button>
                                    <button type="button" class="admin-action-link admin-action-link-secondary" data-attendance-record-cancel-registration>Cancel attendance</button>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                <div class="settings-list-wrap">
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-assignment">
                        <strong>Date</strong>
                        <span>Employee</span>
                        <span>Department</span>
                        <span>Shift</span>
                        <span>Status</span>
                        <span>Check-in</span>
                        <span>Signature</span>
                    </div>
                    <?php if (empty($attendances)): ?>
                        <div class="crud-empty-state">No attendance records available.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($attendances, 0, 250) as $attendance): ?>
                            <?php
                                $attendanceStatusRaw = strtolower(trim((string) ($attendance['status'] ?? 'present')));
                                $checkInTimeRaw = trim((string) ($attendance['check_in_time'] ?? ''));
                                $shiftStartTimeRaw = trim((string) ($attendance['shift_start_time'] ?? ''));
                                $isLateCheckIn = $attendanceStatusRaw === 'late';
                                if (!$isLateCheckIn && $checkInTimeRaw !== '' && $shiftStartTimeRaw !== '') {
                                    $isLateCheckIn = strtotime('1970-01-01 ' . $checkInTimeRaw) > strtotime('1970-01-01 ' . $shiftStartTimeRaw);
                                }
                                $displayAttendanceStatus = $isLateCheckIn ? 'Late' : ucfirst($attendanceStatusRaw !== '' ? $attendanceStatusRaw : 'present');
                            ?>
                            <article class="settings-list-item-wrap">
                                <div class="settings-list-row settings-list-cols settings-list-cols-assignment">
                                    <strong><?php echo e($attendance['work_date'] ?? ''); ?></strong>
                                    <span><?php echo e($attendance['user_name'] ?? '--'); ?></span>
                                    <span><?php echo e($attendance['department_name'] ?? '--'); ?></span>
                                    <span><?php echo e($attendance['shift_name'] ?? '--'); ?></span>
                                    <span><?php echo e($displayAttendanceStatus); ?></span>
                                    <span><?php echo e($attendance['check_in_time'] ?? '--'); ?></span>
                                    <div class="settings-inline-actions settings-inline-actions-compact">
                                        <span>
                                            <?php
                                                if (!empty($attendance['digital_signature_id'])) {
                                                    echo $isLateCheckIn ? 'Signed (Late)' : 'Signed';
                                                } else {
                                                    echo 'Missing';
                                                }
                                            ?>
                                        </span>
                                        <button
                                            type="button"
                                            class="admin-action-link admin-action-link-secondary"
                                            data-attendance-record-edit
                                            data-attendance-id="<?php echo (int) ($attendance['id'] ?? 0); ?>"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="admin-action-link admin-action-link-secondary"
                                            data-attendance-record-delete
                                            data-attendance-id="<?php echo (int) ($attendance['id'] ?? 0); ?>"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($currentRole !== 'department_manager'): ?>
            <section class="crud-panel settings-panel" data-settings-panel="departments" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Departments</h3>
                        <p class="crud-modal-subtitle">List view with inline edit drawer for each department.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($visibleDepartments); ?> items</span>
                    </div>
                </div>

                <div class="settings-list-head settings-create-row" data-dept-create-row>
                    <div class="settings-list-cols settings-list-cols-dept">
                        <label class="settings-field">Name<input data-field="name" type="text" value=""></label>
                        <label class="settings-field">Icon
                            <div class="settings-picker-stack">
                                <div class="settings-picker-row">
                                    <input data-field="icon" type="text" value="<?php echo e($departmentIconCatalog[0] ?? '🏷️'); ?>" readonly>
                                    <button type="button" class="settings-picker-toggle" data-picker-toggle="icon">Select</button>
                                </div>
                                <div class="settings-picker-popover" data-picker-popover="icon" hidden>
                                    <div class="settings-choice-grid settings-choice-grid-icons" data-choice-field="icon">
                                        <?php foreach ($departmentIconCatalog as $icon): ?>
                                            <button type="button" class="settings-choice-btn settings-choice-btn-icon" data-choice-value="<?php echo e($icon); ?>" aria-label="Choose icon <?php echo e($icon); ?>">
                                                <span aria-hidden="true"><?php echo e($icon); ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </label>
                        <label class="settings-field">Color
                            <div class="settings-picker-stack">
                                <div class="settings-picker-row">
                                    <input data-field="color" type="hidden" value="#b98b12">
                                    <input data-color-preview type="text" value="" readonly aria-label="Selected color preview" style="--selected-color: #b98b12;">
                                    <button type="button" class="settings-picker-toggle" data-picker-toggle="color">Select</button>
                                </div>
                                <div class="settings-picker-popover" data-picker-popover="color" hidden>
                                    <div class="settings-choice-grid" data-choice-field="color">
                                        <?php foreach ($pickerColorCatalog as $color): ?>
                                            <button type="button" class="settings-choice-btn settings-choice-btn-color" data-choice-value="<?php echo e($color); ?>" data-choice-label="<?php echo e($pickerColorLabel($color)); ?>" style="--choice-color: <?php echo e($color); ?>;" aria-label="Choose color <?php echo e($pickerColorLabel($color)); ?>">
                                                <span class="settings-color-swatch" aria-hidden="true"></span>
                                                <span class="settings-choice-label"><?php echo e($pickerColorLabel($color)); ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </label>
                        <label class="settings-field">Head of department
                            <select data-field="head_user_id">
                                <option value="">-- unassigned --</option>
                                <?php foreach ($departmentCreateHeadUsers as $userOption): ?>
                                    <option value="<?php echo (int) ($userOption['id'] ?? 0); ?>">
                                        <?php echo e(trim((string) (($userOption['first_name'] ?? '') . ' ' . ($userOption['last_name'] ?? ''))) ?: ($userOption['email'] ?? 'User')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <input data-field="company_id" type="hidden" value="<?php echo (int) $scopeCompanyId; ?>">
                        <div class="settings-inline-actions">
                            <button type="button" class="admin-action-link settings-dept-create">Create</button>
                            <button type="button" class="admin-action-link admin-action-link-secondary settings-dept-reset">Reset</button>
                        </div>
                    </div>
                </div>

                <div class="settings-list-wrap">
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-dept">
                        <strong>Name</strong>
                        <span>Company</span>
                        <span>Lead</span>
                        <span>Staff</span>
                        <span>Shifts</span>
                        <span>Actions</span>
                    </div>

                    <?php if (empty($visibleDepartments)): ?>
                        <div class="crud-empty-state">No departments available.</div>
                    <?php else: ?>
                        <?php foreach ($visibleDepartments as $department): ?>
                            <article class="settings-list-item-wrap" data-department-id="<?php echo (int) ($department['id'] ?? 0); ?>" data-company-id="<?php echo (int) ($department['company_id'] ?? $scopeCompanyId); ?>">
                                <div class="settings-list-row settings-list-cols settings-list-cols-dept">
                                    <strong class="settings-dept-title">
                                        <span class="settings-dept-title-icon" style="color: <?php echo e($department['color'] ?? '#b98b12'); ?>;"><?php echo e($department['icon'] ?? '🏷️'); ?></span>
                                        <span><?php echo e($department['name'] ?? 'Department'); ?></span>
                                    </strong>
                                    <span><?php echo e($department['company_name'] ?? $scopeCompanyName); ?></span>
                                    <span><?php echo e($department['head_user_name'] ?: 'Unassigned'); ?></span>
                                    <span><?php echo count($department['users'] ?? []); ?></span>
                                    <span><?php echo count($department['shifts'] ?? []); ?></span>
                                    <div class="settings-inline-actions">
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-dept-edit" aria-label="Edit department" title="Edit department">✎</button>
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-action-icon-danger settings-dept-delete" data-department-id="<?php echo (int) ($department['id'] ?? 0); ?>" aria-label="Delete department" title="Delete department">🗑</button>
                                    </div>
                                </div>
                                <div class="settings-edit-drawer" hidden>
                                    <div class="settings-list-cols settings-list-cols-dept-edit">
                                        <label class="settings-field">Name<input data-field="name" type="text" value="<?php echo e($department['name'] ?? 'Department'); ?>"></label>
                                        <label class="settings-field">Icon
                                            <div class="settings-picker-stack">
                                                <div class="settings-picker-row">
                                                    <input data-field="icon" type="text" value="<?php echo e($department['icon'] ?? ($departmentIconCatalog[0] ?? '🏷️')); ?>" readonly>
                                                    <button type="button" class="settings-picker-toggle" data-picker-toggle="icon">Select</button>
                                                </div>
                                                <div class="settings-picker-popover" data-picker-popover="icon" hidden>
                                                    <div class="settings-choice-grid settings-choice-grid-icons" data-choice-field="icon">
                                                        <?php foreach ($departmentIconCatalog as $icon): ?>
                                                            <button type="button" class="settings-choice-btn settings-choice-btn-icon" data-choice-value="<?php echo e($icon); ?>" aria-label="Choose icon <?php echo e($icon); ?>">
                                                                <span aria-hidden="true"><?php echo e($icon); ?></span>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="settings-field">Color
                                            <div class="settings-picker-stack">
                                                <div class="settings-picker-row">
                                                    <input data-field="color" type="hidden" value="<?php echo e($department['color'] ?? '#b98b12'); ?>">
                                                    <input data-color-preview type="text" value="" readonly aria-label="Selected color preview" style="--selected-color: <?php echo e($department['color'] ?? '#b98b12'); ?>;">
                                                    <button type="button" class="settings-picker-toggle" data-picker-toggle="color">Select</button>
                                                </div>
                                                <div class="settings-picker-popover" data-picker-popover="color" hidden>
                                                    <div class="settings-choice-grid" data-choice-field="color">
                                                        <?php foreach ($pickerColorCatalog as $color): ?>
                                                            <button type="button" class="settings-choice-btn settings-choice-btn-color" data-choice-value="<?php echo e($color); ?>" data-choice-label="<?php echo e($pickerColorLabel($color)); ?>" style="--choice-color: <?php echo e($color); ?>;" aria-label="Choose color <?php echo e($pickerColorLabel($color)); ?>">
                                                                <span class="settings-color-swatch" aria-hidden="true"></span>
                                                                <span class="settings-choice-label"><?php echo e($pickerColorLabel($color)); ?></span>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="settings-field">Head of department
                                            <select data-field="head_user_id">
                                                <option value="">-- unassigned --</option>
                                                <?php
                                                $deptIdForHead = (int) ($department['id'] ?? 0);
                                                $eligibleHeadUsers = array_values(array_filter(
                                                    $visibleUsers,
                                                    static fn(array $u): bool => ((int) ($u['company_id'] ?? 0) === $scopeCompanyId)
                                                        && (((int) ($u['department_id'] ?? 0) === 0) || ((int) ($u['department_id'] ?? 0) === $deptIdForHead))
                                                ));
                                                foreach ($eligibleHeadUsers as $userOption): ?>
                                                    <option value="<?php echo (int) ($userOption['id'] ?? 0); ?>" <?php echo ((int) ($department['head_user_id'] ?? 0) === (int) ($userOption['id'] ?? 0)) ? 'selected' : ''; ?>>
                                                        <?php echo e(trim((string) (($userOption['first_name'] ?? '') . ' ' . ($userOption['last_name'] ?? ''))) ?: ($userOption['email'] ?? 'User')); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <div class="settings-inline-actions">
                                            <button type="button" class="admin-action-link settings-dept-save">Save</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-dept-cancel">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="crud-panel settings-panel" data-settings-panel="users" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Users</h3>
                        <p class="crud-modal-subtitle">List view with edit action to open full editable fields.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($visibleUsers); ?> users</span>
                    </div>
                </div>

                <div class="settings-list-head settings-create-row" data-user-create-row>
                    <div class="settings-list-cols settings-list-cols-user-create">
                        <label class="settings-field">First name<input data-field="first_name" type="text" value=""></label>
                        <label class="settings-field">Last name<input data-field="last_name" type="text" value=""></label>
                        <label class="settings-field">Email<input data-field="email" type="email" value=""></label>
                        <label class="settings-field">Role
                            <select data-field="role">
                                <?php foreach ($roleCatalog as $r): ?>
                                    <?php if ($currentRole === 'admin' && $r['key'] === 'super_admin') continue; ?>
                                    <option value="<?php echo e($r['key']); ?>" <?php echo $r['key'] === 'employee' ? 'selected' : ''; ?>><?php echo e($r['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="settings-field">Department
                            <select data-field="department_id">
                                <option value="">-- none --</option>
                                <?php foreach ($visibleDepartments as $department): ?>
                                    <option value="<?php echo (int) ($department['id'] ?? 0); ?>"><?php echo e($department['name'] ?? 'Department'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="settings-field">Password<input data-field="password" type="text" value=""></label>
                        <div class="settings-inline-actions">
                            <button type="button" class="admin-action-link settings-user-create">Create user</button>
                            <button type="button" class="admin-action-link admin-action-link-secondary settings-user-reset">Reset</button>
                        </div>
                    </div>
                </div>

                <div class="settings-list-wrap">
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-user">
                        <strong>Name</strong>
                        <span>Email</span>
                        <span>Role</span>
                        <span>Department</span>
                        <span>Status</span>
                        <span>Actions</span>
                    </div>

                    <?php if (empty($visibleUsers)): ?>
                        <div class="crud-empty-state">No users assigned yet.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($visibleUsers, 0, 200) as $user): ?>
                            <article class="settings-list-item-wrap" data-user-id="<?php echo (int) ($user['id'] ?? 0); ?>">
                                <div class="settings-list-row settings-list-cols settings-list-cols-user">
                                    <strong><?php echo e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User'); ?></strong>
                                    <span><?php echo e($user['email'] ?? ''); ?></span>
                                    <span><?php echo e($roleLabels[$user['role'] ?? 'employee'] ?? ucfirst((string) ($user['role'] ?? 'employee'))); ?></span>
                                    <span><?php echo e($user['department_name'] ?? '--'); ?></span>
                                    <span><?php echo e($user['status'] ?? 'active'); ?></span>
                                    <div class="settings-inline-actions">
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-user-edit" aria-label="Edit user" title="Edit user">✎ Edit</button>
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon-danger settings-user-delete" data-user-id="<?php echo (int) ($user['id'] ?? 0); ?>" aria-label="Delete user" title="Delete user">🗑 Delete</button>
                                    </div>
                                </div>

                                <div class="settings-edit-drawer" hidden>
                                    <div class="settings-list-cols settings-list-cols-user-edit">
                                        <label class="settings-field">First name<input data-field="first_name" type="text" value="<?php echo e($user['first_name'] ?? ''); ?>"></label>
                                        <label class="settings-field">Last name<input data-field="last_name" type="text" value="<?php echo e($user['last_name'] ?? ''); ?>"></label>
                                        <label class="settings-field">Email<input data-field="email" type="email" value="<?php echo e($user['email'] ?? ''); ?>"></label>
                                        <label class="settings-field">Role
                                            <select data-field="role">
                                                <?php foreach ($roleCatalog as $r): ?>
                                                    <?php if ($currentRole === 'admin' && $r['key'] === 'super_admin') continue; ?>
                                                    <option value="<?php echo e($r['key']); ?>" <?php echo (($user['role'] ?? '') === $r['key']) ? 'selected' : ''; ?>><?php echo e($r['label']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="settings-field">Department
                                            <select data-field="department_id">
                                                <option value="">-- none --</option>
                                                <?php foreach ($visibleDepartments as $department): ?>
                                                    <option value="<?php echo (int) ($department['id'] ?? 0); ?>" <?php echo ((int) ($user['department_id'] ?? 0) === (int) ($department['id'] ?? 0)) ? 'selected' : ''; ?>><?php echo e($department['name'] ?? 'Department'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="settings-field">Status
                                            <select data-field="status">
                                                <option value="active" <?php echo (($user['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo (($user['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </label>
                                        <label class="settings-field">Password<input data-field="password" type="text" value="" placeholder="(leave blank to keep)"></label>
                                        <div class="settings-inline-actions">
                                            <button type="button" class="admin-action-link settings-user-save" data-user-id="<?php echo (int) ($user['id'] ?? 0); ?>">Save</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-user-cancel">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="crud-panel settings-panel" data-settings-panel="shifts" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Shifts</h3>
                        <p class="crud-modal-subtitle">List view with edit action for each shift.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($shifts); ?> shifts</span>
                    </div>
                </div>

                <div class="settings-list-head settings-create-row settings-create-row-shift" data-shift-create-row>
                    <div class="settings-list-cols settings-list-cols-shift-create">
                        <div class="settings-shift-create-column settings-shift-create-column-left">
                            <label class="settings-field">Department
                                <select data-field="department_id">
                                    <?php foreach ($visibleDepartments as $department): ?>
                                        <option value="<?php echo (int) ($department['id'] ?? 0); ?>" <?php echo (int) ($department['id'] ?? 0) === (int) ($planner['active_department_id'] ?? 0) ? 'selected' : ''; ?>>
                                            <?php echo e($department['name'] ?? 'Department'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="settings-field">Name<input data-field="name" type="text" value="" placeholder="Morning shift"></label>
                            <label class="settings-field">From date<input data-field="range_start" type="date" value=""></label>
                            <label class="settings-field">To date<input data-field="range_end" type="date" value=""></label>
                        </div>

                        <div class="settings-shift-create-column settings-shift-create-column-right">
                            <label class="settings-field">Icon
                                <div class="settings-picker-stack">
                                    <div class="settings-picker-row">
                                        <input data-field="icon" type="text" value="<?php echo e($shiftIconCatalog[0] ?? '🕒'); ?>" readonly>
                                        <button type="button" class="settings-picker-toggle" data-picker-toggle="icon">Select</button>
                                    </div>
                                    <div class="settings-picker-popover" data-picker-popover="icon" hidden>
                                        <div class="settings-choice-grid settings-choice-grid-icons" data-choice-field="icon">
                                            <?php foreach ($shiftIconCatalog as $icon): ?>
                                                <button type="button" class="settings-choice-btn settings-choice-btn-icon" data-choice-value="<?php echo e($icon); ?>" aria-label="Choose icon <?php echo e($icon); ?>">
                                                    <span aria-hidden="true"><?php echo e($icon); ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <label class="settings-field">Color
                                <div class="settings-picker-stack">
                                    <div class="settings-picker-row">
                                        <input data-field="color" type="hidden" value="#2f6fed">
                                        <input data-color-preview type="text" value="" readonly aria-label="Selected color preview" style="--selected-color: #2f6fed;">
                                        <button type="button" class="settings-picker-toggle" data-picker-toggle="color">Select</button>
                                    </div>
                                    <div class="settings-picker-popover" data-picker-popover="color" hidden>
                                        <div class="settings-choice-grid" data-choice-field="color">
                                            <?php foreach ($pickerColorCatalog as $color): ?>
                                                <button type="button" class="settings-choice-btn settings-choice-btn-color" data-choice-value="<?php echo e($color); ?>" data-choice-label="<?php echo e($pickerColorLabel($color)); ?>" style="--choice-color: <?php echo e($color); ?>;" aria-label="Choose color <?php echo e($pickerColorLabel($color)); ?>">
                                                    <span class="settings-color-swatch" aria-hidden="true"></span>
                                                    <span class="settings-choice-label"><?php echo e($pickerColorLabel($color)); ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <label class="settings-field">Description
                                <input data-field="description" type="text" value="" placeholder="Shift notes">
                            </label>
                            <label class="settings-field">Start<input data-field="start_time" type="time" value="09:00"></label>
                            <label class="settings-field">End<input data-field="end_time" type="time" value="17:00"></label>
                        </div>

                        <div class="settings-shift-create-actions">
                            <button type="button" class="admin-action-link settings-shift-create">Create shift</button>
                            <button type="button" class="admin-action-link admin-action-link-secondary settings-shift-reset">Reset</button>
                        </div>
                    </div>
                </div>

                <div class="settings-list-wrap">
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-shift">
                        <strong>Name</strong>
                        <span>Department</span>
                        <span>Description</span>
                        <span>Time</span>
                        <span>Icon</span>
                        <span>Color</span>
                        <span>Actions</span>
                    </div>

                    <?php if (empty($shifts)): ?>
                        <div class="crud-empty-state">No shifts available.</div>
                    <?php else: ?>
                        <?php foreach ($shifts as $shift): ?>
                            <?php $isSystemShiftTemplate = in_array(strtolower((string) ($shift['kind'] ?? 'work')), ['rest', 'vacation', 'sick'], true); ?>
                            <article class="settings-list-item-wrap" data-shift-id="<?php echo (int) ($shift['id'] ?? 0); ?>">
                                <div class="settings-list-row settings-list-cols settings-list-cols-shift">
                                    <strong><?php echo e($shift['name'] ?? 'Shift'); ?></strong>
                                    <span><?php echo e($shift['department_name'] ?? ''); ?></span>
                                    <span>
                                        <?php echo e($shift['description'] ?? '--'); ?>
                                        <?php if ($isSystemShiftTemplate): ?>
                                            <br><small>System template (read-only)</small>
                                        <?php endif; ?>
                                    </span>
                                    <span><?php echo e(($shift['start_time'] ?? '--:--') . ' - ' . ($shift['end_time'] ?? '--:--')); ?></span>
                                    <span><?php echo e($shift['icon'] ?? '🕒'); ?></span>
                                    <span class="settings-color-display" style="--choice-color: <?php echo e($shift['color'] ?? '#2f6fed'); ?>;">
                                        <span class="settings-color-swatch" aria-hidden="true"></span>
                                        <span><?php echo e($pickerColorLabel((string) ($shift['color'] ?? '#2f6fed'))); ?></span>
                                    </span>
                                    <div class="settings-inline-actions">
                                        <?php if ($isSystemShiftTemplate): ?>
                                            <span class="admin-action-link admin-action-link-secondary" aria-label="System template" title="System template">Locked</span>
                                        <?php else: ?>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-shift-edit" aria-label="Edit shift" title="Edit shift">✎</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-action-icon-danger settings-shift-delete" data-shift-id="<?php echo (int) ($shift['id'] ?? 0); ?>" aria-label="Delete shift" title="Delete shift">🗑</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!$isSystemShiftTemplate): ?>
                                    <div class="settings-edit-drawer" hidden>
                                        <div class="settings-list-cols settings-list-cols-shift-edit">
                                            <label class="settings-field">Name<input data-field="name" type="text" value="<?php echo e($shift['name'] ?? 'Shift'); ?>"></label>
                                            <label class="settings-field">Icon
                                                <div class="settings-picker-stack">
                                                    <div class="settings-picker-row">
                                                        <input data-field="icon" type="text" value="<?php echo e($shift['icon'] ?? ($shiftIconCatalog[0] ?? '🕒')); ?>" readonly>
                                                        <button type="button" class="settings-picker-toggle" data-picker-toggle="icon">Select</button>
                                                    </div>
                                                    <div class="settings-picker-popover" data-picker-popover="icon" hidden>
                                                        <div class="settings-choice-grid settings-choice-grid-icons" data-choice-field="icon">
                                                            <?php foreach ($shiftIconCatalog as $icon): ?>
                                                                <button type="button" class="settings-choice-btn settings-choice-btn-icon" data-choice-value="<?php echo e($icon); ?>" aria-label="Choose icon <?php echo e($icon); ?>">
                                                                    <span aria-hidden="true"><?php echo e($icon); ?></span>
                                                                </button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                            <label class="settings-field">Color
                                                <div class="settings-picker-stack">
                                                    <div class="settings-picker-row">
                                                        <input data-field="color" type="hidden" value="<?php echo e($shift['color'] ?? '#2f6fed'); ?>">
                                                        <input data-color-preview type="text" value="" readonly aria-label="Selected color preview" style="--selected-color: <?php echo e($shift['color'] ?? '#2f6fed'); ?>;">
                                                        <button type="button" class="settings-picker-toggle" data-picker-toggle="color">Select</button>
                                                    </div>
                                                    <div class="settings-picker-popover" data-picker-popover="color" hidden>
                                                        <div class="settings-choice-grid" data-choice-field="color">
                                                            <?php foreach ($pickerColorCatalog as $color): ?>
                                                                <button type="button" class="settings-choice-btn settings-choice-btn-color" data-choice-value="<?php echo e($color); ?>" data-choice-label="<?php echo e($pickerColorLabel($color)); ?>" style="--choice-color: <?php echo e($color); ?>;" aria-label="Choose color <?php echo e($pickerColorLabel($color)); ?>">
                                                                    <span class="settings-color-swatch" aria-hidden="true"></span>
                                                                    <span class="settings-choice-label"><?php echo e($pickerColorLabel($color)); ?></span>
                                                                </button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                            <label class="settings-field settings-field-wide">Description
                                                <input data-field="description" type="text" value="<?php echo e($shift['description'] ?? ''); ?>" placeholder="Shift notes">
                                            </label>
                                            <label class="settings-field">Start<input data-field="start_time" type="time" value="<?php echo e($shift['start_time'] ?? ''); ?>"></label>
                                            <label class="settings-field">End<input data-field="end_time" type="time" value="<?php echo e($shift['end_time'] ?? ''); ?>"></label>
                                            <div class="settings-inline-actions">
                                                <button type="button" class="admin-action-link settings-shift-save" data-shift-id="<?php echo (int) ($shift['id'] ?? 0); ?>">Save</button>
                                                <button type="button" class="admin-action-link admin-action-link-secondary settings-shift-cancel">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

    </div>
</section>
