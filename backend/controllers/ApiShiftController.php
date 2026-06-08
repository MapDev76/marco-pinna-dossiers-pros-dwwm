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
            $weeklyRestWeekdaysRaw = $input['weekly_rest_weekdays'] ?? [];
            if (is_string($weeklyRestWeekdaysRaw)) {
                $decodedWeeklyRest = json_decode($weeklyRestWeekdaysRaw, true);
                $weeklyRestWeekdaysRaw = is_array($decodedWeeklyRest) ? $decodedWeeklyRest : [];
            }
            $weeklyRestWeekdays = [];
            if (is_array($weeklyRestWeekdaysRaw)) {
                foreach ($weeklyRestWeekdaysRaw as $weekdayRaw) {
                    $weekday = (int) $weekdayRaw;
                    if ($weekday >= 0 && $weekday <= 6) {
                        $weeklyRestWeekdays[$weekday] = true;
                    }
                }
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
                $rangeStart = trim((string) ($input['range_start'] ?? ''));
                $rangeEnd = trim((string) ($input['range_end'] ?? ''));
                if ($rangeStart === '' || $rangeEnd === '') {
                    jsonResponse(['ok' => false, 'error' => 'range_start and range_end required'], 400);
                }

                $start = new DateTimeImmutable($rangeStart);
                $end = new DateTimeImmutable($rangeEnd);
                if ($end < $start) {
                    [$start, $end] = [$end, $start];
                }

                $datesToInsert = [];
                foreach (new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day')) as $date) {
                    $weekday = (int) $date->format('w');
                    if (!empty($weeklyRestWeekdays[$weekday])) {
                        continue;
                    }
                    $datesToInsert[] = $date->format('Y-m-d');
                }

                if (!empty($datesToInsert)) {
                    $existingStmt = $pdo->prepare(
                        'SELECT id FROM user_shifts WHERE shift_id = :shift_id AND work_date = :work_date LIMIT 1'
                    );
                    $insertStmt = $pdo->prepare(
                        'INSERT INTO user_shifts (shift_id, user_id, work_date, status)
                         VALUES (:shift_id, NULL, :work_date, "open")'
                    );
                    foreach ($createdShiftIds as $createdShiftId) {
                        foreach ($datesToInsert as $workDate) {
                            $existingStmt->execute([
                                'shift_id' => $createdShiftId,
                                'work_date' => $workDate,
                            ]);
                            if ($existingStmt->fetchColumn()) {
                                continue;
                            }
                            $insertStmt->execute([
                                'shift_id' => $createdShiftId,
                                'work_date' => $workDate,
                            ]);
                        }
                    }
                }
            }

            $firstShiftId = (int) ($createdShiftIds[0] ?? 0);
            $shift = $firstShiftId > 0 ? $shiftModel->findById($firstShiftId) : null;
            jsonResponse([
                'ok' => true,
                'shift' => $shift,
                'shift_ids' => $createdShiftIds,
                'department_ids' => $createDepartmentIds,
                'weekly_rest_weekdays' => array_values(array_map('intval', array_keys($weeklyRestWeekdays))),
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
