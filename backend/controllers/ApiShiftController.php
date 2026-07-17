<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/ShiftModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';

$currentUser = currentUser();
$currentRole = (string) ($currentUser['role'] ?? '');

if (!isLoggedIn() || !in_array($currentRole, ['super_admin', 'admin', 'department_manager'], true)) {
            jsonResponse(['error' => t('common.unauthorized')], 403);
}

$pdo = getPDO();
ensureSchedulerSchema($pdo);
$shiftModel = new ShiftModel($pdo);
$departmentModel = new DepartmentModel($pdo);

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

$isProtectedTemplate = static function (?array $shiftRow): bool {
    $kind = strtolower(trim((string) ($shiftRow['kind'] ?? 'work')));
    if (in_array($kind, ['rest', 'vacation', 'sick'], true)) {
        return true;
    }

    // Legacy fallback: some historical rows may still have kind=work.
    $name = strtolower(trim((string) ($shiftRow['name'] ?? '')));
    return in_array($name, ['rest day', 'vacation', 'sick leave'], true);
};

$resolveUserCompanyId = static function (PDO $pdo, array $user): int {
    $companyId = (int) ($user['company_id'] ?? 0);
    if ($companyId > 0) {
        return $companyId;
    }

    $userId = (int) ($user['id'] ?? 0);
    if ($userId <= 0) {
        return 0;
    }

    $statement = $pdo->prepare(
        'SELECT d.company_id
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.id = :user_id
         LIMIT 1'
    );
    $statement->execute(['user_id' => $userId]);

    return (int) ($statement->fetchColumn() ?: 0);
};

$resolveShiftCompanyId = static function (PDO $pdo, array $shift): int {
    $departmentId = (int) ($shift['department_id'] ?? 0);
    if ($departmentId <= 0) {
        return 0;
    }

    $statement = $pdo->prepare('SELECT company_id FROM departments WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $departmentId]);

    return (int) ($statement->fetchColumn() ?: 0);
};

$adminCompanyId = $currentRole === 'admin' ? $resolveUserCompanyId($pdo, $currentUser) : 0;

$normalizeWeekdays = static function ($rawValue): array {
    if (is_string($rawValue)) {
        $decoded = json_decode($rawValue, true);
        $rawValue = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($rawValue)) {
        $rawValue = [];
    }

    $weekdays = [];
    foreach ($rawValue as $candidate) {
        $weekday = (int) $candidate;
        if ($weekday >= 0 && $weekday <= 6) {
            $weekdays[$weekday] = $weekday;
        }
    }

    return array_values($weekdays);
};

$normalizeMonths = static function ($rawValue): array {
    if (is_string($rawValue)) {
        $decoded = json_decode($rawValue, true);
        $rawValue = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($rawValue)) {
        $rawValue = [];
    }

    $months = [];
    foreach ($rawValue as $candidate) {
        $month = (int) $candidate;
        if ($month >= 1 && $month <= 12) {
            $months[$month] = $month;
        }
    }

    if (empty($months)) {
        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = $month;
        }
    }

    return array_values($months);
};

$buildDateRange = static function (string $rangeStart, string $rangeEnd): array {
    $start = new DateTimeImmutable($rangeStart);
    $end = new DateTimeImmutable($rangeEnd);
    if ($end < $start) {
        [$start, $end] = [$end, $start];
    }
    return [$start, $end];
};

$collectRestDates = static function (
    DateTimeImmutable $start,
    DateTimeImmutable $end,
    array $restWeekdays,
    string $repeatMode,
    string $scaleMode,
    array $monthNumbers
): array {
    if (empty($restWeekdays)) {
        return [];
    }

    $restWeekdaySet = array_fill_keys(array_map('intval', $restWeekdays), true);
    $monthSet = array_fill_keys(array_map('intval', $monthNumbers), true);
    $result = [];

    if ($repeatMode === 'weekly') {
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day')) as $date) {
            $weekday = (int) $date->format('w');
            $monthNumber = (int) $date->format('n');
            if (!isset($restWeekdaySet[$weekday]) || !isset($monthSet[$monthNumber])) {
                continue;
            }
            $result[$date->format('Y-m-d')] = true;
        }
        return array_keys($result);
    }

    $monthCursor = new DateTimeImmutable($start->format('Y-m-01'));
    $monthIndex = 0;
    while ($monthCursor <= $end) {
        $monthNumber = (int) $monthCursor->format('n');
        if (!isset($monthSet[$monthNumber])) {
            $monthCursor = $monthCursor->modify('+1 month');
            $monthIndex++;
            continue;
        }

        $monthEnd = $monthCursor->modify('last day of this month');
        $windowStart = $monthCursor > $start ? $monthCursor : $start;
        $windowEnd = $monthEnd < $end ? $monthEnd : $end;

        foreach (array_keys($restWeekdaySet) as $weekday) {
            $candidates = [];
            foreach (new DatePeriod($windowStart, new DateInterval('P1D'), $windowEnd->modify('+1 day')) as $date) {
                if ((int) $date->format('w') === (int) $weekday) {
                    $candidates[] = $date;
                }
            }

            if (empty($candidates)) {
                continue;
            }

            if ($scaleMode === 'monthly') {
                $picked = $candidates[0];
            } else {
                $pickIndex = $monthIndex % count($candidates);
                $picked = $candidates[$pickIndex];
            }

            $result[$picked->format('Y-m-d')] = true;
        }

        $monthCursor = $monthCursor->modify('+1 month');
        $monthIndex++;
    }

    return array_keys($result);
};

$generateScheduleSlots = static function (
    PDO $pdo,
    array $workShiftIdsByDepartment,
    string $rangeStart,
    string $rangeEnd,
    array $workWeekdays,
    array $monthNumbers,
    array $weeklyRestWeekdays,
    bool $includeRestday,
    array $restdayWeekdays,
    string $restdayRepeatMode,
    string $restdayScaleMode,
    bool $replaceWorkSlots
) use ($buildDateRange, $collectRestDates): void {
    if (empty($workShiftIdsByDepartment)) {
        return;
    }

    [$start, $end] = $buildDateRange($rangeStart, $rangeEnd);
    $workWeekdaySet = array_fill_keys(array_map('intval', $workWeekdays), true);
    if (empty($workWeekdaySet)) {
        $workWeekdaySet = array_fill_keys([0, 1, 2, 3, 4, 5, 6], true);
    }
    $monthSet = array_fill_keys(array_map('intval', $monthNumbers), true);
    $weeklyRestSet = array_fill_keys(array_map('intval', $weeklyRestWeekdays), true);

    $workDates = [];
    foreach (new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day')) as $date) {
        $weekday = (int) $date->format('w');
        $monthNumber = (int) $date->format('n');
        if (!isset($monthSet[$monthNumber])) {
            continue;
        }
        if (!isset($workWeekdaySet[$weekday])) {
            continue;
        }
        if (isset($weeklyRestSet[$weekday])) {
            continue;
        }
        $workDates[] = $date->format('Y-m-d');
    }

    $deleteWorkStmt = $pdo->prepare(
        'DELETE FROM user_shifts
         WHERE shift_id = :shift_id
           AND user_id IS NULL
           AND status = "open"
           AND work_date BETWEEN :range_start AND :range_end'
    );
    $existingStmt = $pdo->prepare(
        'SELECT id FROM user_shifts
         WHERE shift_id = :shift_id
           AND work_date = :work_date
         LIMIT 1'
    );
    $insertStmt = $pdo->prepare(
        'INSERT INTO user_shifts (shift_id, user_id, work_date, status)
         VALUES (:shift_id, NULL, :work_date, "open")'
    );

    foreach ($workShiftIdsByDepartment as $departmentId => $shiftIds) {
        $departmentShiftIds = array_values(array_unique(array_filter(array_map('intval', is_array($shiftIds) ? $shiftIds : []))));
        if (empty($departmentShiftIds)) {
            continue;
        }

        foreach ($departmentShiftIds as $shiftId) {
            if ($replaceWorkSlots) {
                $deleteWorkStmt->execute([
                    'shift_id' => $shiftId,
                    'range_start' => $start->format('Y-m-d'),
                    'range_end' => $end->format('Y-m-d'),
                ]);
            }

            foreach ($workDates as $workDate) {
                $existingStmt->execute([
                    'shift_id' => $shiftId,
                    'work_date' => $workDate,
                ]);
                if ($existingStmt->fetchColumn()) {
                    continue;
                }

                $insertStmt->execute([
                    'shift_id' => $shiftId,
                    'work_date' => $workDate,
                ]);
            }
        }

        if (!$includeRestday) {
            continue;
        }

        $restDates = $collectRestDates($start, $end, $restdayWeekdays, $restdayRepeatMode, $restdayScaleMode, $monthNumbers);
        if (empty($restDates)) {
            continue;
        }

        $templateIds = ensureDepartmentAbsenceShiftTemplates($pdo, (int) $departmentId);
        $restShiftId = (int) ($templateIds['rest'] ?? 0);
        if ($restShiftId <= 0) {
            continue;
        }

        foreach ($restDates as $restDate) {
            $existingStmt->execute([
                'shift_id' => $restShiftId,
                'work_date' => $restDate,
            ]);
            if ($existingStmt->fetchColumn()) {
                continue;
            }

            $insertStmt->execute([
                'shift_id' => $restShiftId,
                'work_date' => $restDate,
            ]);
        }
    }
};

try {
    switch ($action) {
        case 'list':
            $departmentId = (int) ($input['department_id'] ?? 0);
            if ($departmentId > 0) {
                $rows = $shiftModel->byDepartmentId($departmentId);
                jsonResponse(['ok' => true, 'shifts' => $rows]);
            }
            jsonResponse(['ok' => false, 'error' => t('common.department_required')], 400);
            break;

        case 'create':
            if (!in_array($currentRole, ['admin', 'super_admin'], true)) {
                jsonResponse(['ok' => false, 'error' => t('common.unauthorized')], 403);
            }
            $requestedKind = strtolower(trim((string) ($input['kind'] ?? 'work')));
            if (!in_array($requestedKind, ['work', 'rest', 'vacation', 'sick'], true)) {
                $requestedKind = 'work';
            }
            if ($requestedKind !== 'work') {
                jsonResponse(['ok' => false, 'error' => t('settings.system_shift_auto_managed')], 400);
            }

            $required = ['department_id', 'start_time', 'end_time'];
            foreach ($required as $r) {
                if (empty($input[$r]) && $input[$r] !== '0') {
                    jsonResponse(['ok' => false, 'error' => $r . ' required'], 400);
                }
            }

            if (trim((string) ($input['name'] ?? '')) === '') {
                jsonResponse(['ok' => false, 'error' => t('settings.shift_name_required')], 400);
            }

            $createDepartmentIds = [];
            $rawDepartmentIds = $input['department_ids'] ?? null;
            if (is_array($rawDepartmentIds)) {
                foreach ($rawDepartmentIds as $candidateId) {
                    $id = (int) $candidateId;
                    if ($id > 0) {
                        $createDepartmentIds[] = $id;
                    }
                }
            }

            if (empty($createDepartmentIds)) {
                $fallbackDepartmentId = (int) ($input['department_id'] ?? 0);
                if ($fallbackDepartmentId > 0) {
                    $createDepartmentIds[] = $fallbackDepartmentId;
                }
            }

            $createDepartmentIds = array_values(array_unique($createDepartmentIds));
            if (empty($createDepartmentIds)) {
                jsonResponse(['ok' => false, 'error' => t('common.department_required')], 400);
            }

            foreach ($createDepartmentIds as $createDepartmentId) {
                $createDepartment = $departmentModel->findById($createDepartmentId);
                if (!$createDepartment) {
                    jsonResponse(['ok' => false, 'error' => 'Department not found'], 404);
                }

                if ($currentRole === 'admin') {
                    $departmentCompanyId = (int) ($createDepartment['company_id'] ?? 0);
                    if ($adminCompanyId <= 0 || $departmentCompanyId <= 0 || $departmentCompanyId !== $adminCompanyId) {
                        jsonResponse(['ok' => false, 'error' => t('common.unauthorized')], 403);
                    }
                }

            }

            $createdShiftIds = [];
            $name = trim((string) $input['name']);
            $icon = $input['icon'] ?? null;
            $color = $input['color'] ?? null;
            $description = $input['description'] ?? null;
            $startTime = $input['start_time'];
            $endTime = $input['end_time'];
            $rangeStart = trim((string) ($input['range_start'] ?? ''));
            $rangeEnd = trim((string) ($input['range_end'] ?? ''));
            $workWeekdays = $normalizeWeekdays($input['work_weekdays'] ?? [0, 1, 2, 3, 4, 5, 6]);
            if (empty($workWeekdays)) {
                $workWeekdays = [0, 1, 2, 3, 4, 5, 6];
            }
            $monthNumbers = $normalizeMonths($input['month_numbers'] ?? []);
            $weeklyRestWeekdays = $normalizeWeekdays($input['weekly_rest_weekdays'] ?? []);
            $includeRestdayRaw = strtolower(trim((string) ($input['include_restday'] ?? '0')));
            $includeRestday = in_array($includeRestdayRaw, ['1', 'true', 'yes', 'on'], true);
            $restdayWeekdays = $normalizeWeekdays($input['restday_weekdays'] ?? []);
            $restdayRepeatMode = strtolower(trim((string) ($input['restday_repeat_mode'] ?? 'weekly')));
            if (!in_array($restdayRepeatMode, ['weekly', 'monthly'], true)) {
                $restdayRepeatMode = 'weekly';
            }
            $restdayScaleMode = strtolower(trim((string) ($input['restday_scale_mode'] ?? 'weekly')));
            if (!in_array($restdayScaleMode, ['weekly', 'monthly'], true)) {
                $restdayScaleMode = 'weekly';
            }

            $lookupTemplateShift = $pdo->prepare(
                'SELECT id
                 FROM shifts
                 WHERE department_id = :department_id
                   AND kind = :kind
                 ORDER BY id ASC
                 LIMIT 1'
            );

            foreach ($createDepartmentIds as $createDepartmentId) {
                if (in_array($requestedKind, ['rest', 'vacation', 'sick'], true)) {
                    $lookupTemplateShift->execute([
                        'department_id' => $createDepartmentId,
                        'kind' => $requestedKind,
                    ]);
                    $existingTemplateId = (int) ($lookupTemplateShift->fetchColumn() ?: 0);
                    if ($existingTemplateId > 0) {
                        $shiftModel->update($existingTemplateId, [
                            'department_id' => $createDepartmentId,
                            'name' => $name,
                            'icon' => $icon,
                            'color' => $color,
                            'description' => $description,
                            'kind' => $requestedKind,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ]);
                        $createdShiftIds[] = $existingTemplateId;
                        continue;
                    }
                }

                $createdShiftIds[] = $shiftModel->create([
                    'department_id' => $createDepartmentId,
                    'name' => $name,
                    'icon' => $icon,
                    'color' => $color,
                    'description' => $description,
                    'kind' => $requestedKind,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            }

            if ($requestedKind === 'work') {
                if ($rangeStart === '' || $rangeEnd === '') {
                    jsonResponse(['ok' => false, 'error' => 'range_start and range_end required'], 400);
                }

                $workShiftIdsByDepartment = [];
                foreach ($createDepartmentIds as $index => $departmentId) {
                    $shiftId = (int) ($createdShiftIds[$index] ?? 0);
                    if ($shiftId <= 0) {
                        continue;
                    }
                    $workShiftIdsByDepartment[(int) $departmentId] = [$shiftId];
                }

                $generateScheduleSlots(
                    $pdo,
                    $workShiftIdsByDepartment,
                    $rangeStart,
                    $rangeEnd,
                    $workWeekdays,
                    $monthNumbers,
                    $weeklyRestWeekdays,
                    $includeRestday,
                    $restdayWeekdays,
                    $restdayRepeatMode,
                    $restdayScaleMode,
                    false
                );
            }

            $firstShiftId = (int) ($createdShiftIds[0] ?? 0);
            $shift = $firstShiftId > 0 ? $shiftModel->findById($firstShiftId) : null;
            jsonResponse([
                'ok' => true,
                'shift' => $shift,
                'shift_ids' => $createdShiftIds,
                'department_ids' => $createDepartmentIds,
                'weekly_rest_weekdays' => $weeklyRestWeekdays,
                'work_weekdays' => $workWeekdays,
                'month_numbers' => $monthNumbers,
            ]);
            break;

        case 'update':
            if (!in_array($currentRole, ['admin', 'super_admin'], true)) {
                jsonResponse(['ok' => false, 'error' => t('common.unauthorized')], 403);
            }
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $existingShift = $shiftModel->findById($id);
            if (!$existingShift) {
                jsonResponse(['ok' => false, 'error' => 'Shift not found'], 404);
            }

            if ($currentRole === 'admin' && $isProtectedTemplate($existingShift)) {
                jsonResponse(['ok' => false, 'error' => 'System absence templates cannot be modified.'], 400);
            }

            if ($currentRole === 'admin') {
                $shiftCompanyId = $resolveShiftCompanyId($pdo, $existingShift);
                if ($adminCompanyId <= 0 || $shiftCompanyId <= 0 || $shiftCompanyId !== $adminCompanyId) {
                    jsonResponse(['ok' => false, 'error' => t('common.unauthorized')], 403);
                }
            }

            $normalizeTime = static function ($value, $fallback) {
                $candidate = trim((string) $value);
                if ($candidate === '') {
                    return trim((string) $fallback);
                }
                return strlen($candidate) >= 5 ? substr($candidate, 0, 5) : $candidate;
            };

            $resolvedDepartmentId = (int) ($input['department_id'] ?? ($existingShift['department_id'] ?? 0));
            if ($resolvedDepartmentId <= 0) {
                jsonResponse(['ok' => false, 'error' => t('common.department_required')], 400);
            }

            $resolvedPayload = [
                'department_id' => $resolvedDepartmentId,
                'name' => trim((string) ($input['name'] ?? ($existingShift['name'] ?? ''))),
                'icon' => array_key_exists('icon', $input) ? $input['icon'] : ($existingShift['icon'] ?? null),
                'color' => array_key_exists('color', $input) ? $input['color'] : ($existingShift['color'] ?? null),
                'description' => array_key_exists('description', $input) ? $input['description'] : ($existingShift['description'] ?? null),
                'kind' => (string) ($input['kind'] ?? ($existingShift['kind'] ?? 'work')),
                'start_time' => $normalizeTime($input['start_time'] ?? '', $existingShift['start_time'] ?? ''),
                'end_time' => $normalizeTime($input['end_time'] ?? '', $existingShift['end_time'] ?? ''),
            ];

            if ($resolvedPayload['name'] === '') {
                $resolvedPayload['name'] = trim((string) ($existingShift['name'] ?? ''));
            }
            if ($resolvedPayload['name'] === '') {
                jsonResponse(['ok' => false, 'error' => t('settings.shift_name_required')], 400);
            }
            if ($resolvedPayload['start_time'] === '' || $resolvedPayload['end_time'] === '') {
                jsonResponse(['ok' => false, 'error' => 'start_time and end_time required'], 400);
            }

            $shiftModel->update($id, $resolvedPayload);

            $regenerateSlotsRaw = strtolower(trim((string) ($input['regenerate_slots'] ?? '0')));
            $regenerateSlots = in_array($regenerateSlotsRaw, ['1', 'true', 'yes', 'on'], true);
            if ($regenerateSlots && strtolower((string) ($resolvedPayload['kind'] ?? 'work')) === 'work') {
                $rangeStart = trim((string) ($input['range_start'] ?? ''));
                $rangeEnd = trim((string) ($input['range_end'] ?? ''));
                if ($rangeStart !== '' && $rangeEnd !== '') {
                    $workWeekdays = $normalizeWeekdays($input['work_weekdays'] ?? [0, 1, 2, 3, 4, 5, 6]);
                    if (empty($workWeekdays)) {
                        $workWeekdays = [0, 1, 2, 3, 4, 5, 6];
                    }
                    $monthNumbers = $normalizeMonths($input['month_numbers'] ?? []);
                    $weeklyRestWeekdays = $normalizeWeekdays($input['weekly_rest_weekdays'] ?? []);
                    $includeRestdayRaw = strtolower(trim((string) ($input['include_restday'] ?? '0')));
                    $includeRestday = in_array($includeRestdayRaw, ['1', 'true', 'yes', 'on'], true);
                    $restdayWeekdays = $normalizeWeekdays($input['restday_weekdays'] ?? []);
                    $restdayRepeatMode = strtolower(trim((string) ($input['restday_repeat_mode'] ?? 'weekly')));
                    if (!in_array($restdayRepeatMode, ['weekly', 'monthly'], true)) {
                        $restdayRepeatMode = 'weekly';
                    }
                    $restdayScaleMode = strtolower(trim((string) ($input['restday_scale_mode'] ?? 'weekly')));
                    if (!in_array($restdayScaleMode, ['weekly', 'monthly'], true)) {
                        $restdayScaleMode = 'weekly';
                    }

                    $generateScheduleSlots(
                        $pdo,
                        [$resolvedDepartmentId => [$id]],
                        $rangeStart,
                        $rangeEnd,
                        $workWeekdays,
                        $monthNumbers,
                        $weeklyRestWeekdays,
                        $includeRestday,
                        $restdayWeekdays,
                        $restdayRepeatMode,
                        $restdayScaleMode,
                        true
                    );
                }
            }

            jsonResponse(['ok' => true]);
            break;

        case 'delete':
            if (!in_array($currentRole, ['admin', 'super_admin'], true)) {
                jsonResponse(['ok' => false, 'error' => t('common.unauthorized')], 403);
            }
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $existingShift = $shiftModel->findById($id);
            if (!$existingShift) {
                jsonResponse(['ok' => false, 'error' => 'Shift not found'], 404);
            }

            if ($currentRole === 'admin' && $isProtectedTemplate($existingShift)) {
                jsonResponse(['ok' => false, 'error' => 'System absence templates cannot be deleted.'], 400);
            }

            if ($currentRole === 'admin') {
                $shiftCompanyId = $resolveShiftCompanyId($pdo, $existingShift);
                if ($adminCompanyId <= 0 || $shiftCompanyId <= 0 || $shiftCompanyId !== $adminCompanyId) {
                    jsonResponse(['ok' => false, 'error' => t('common.unauthorized')], 403);
                }
            }

            $shiftModel->delete($id);
            jsonResponse(['ok' => true]);
            break;

        default:
            jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
}
