<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/ShiftModel.php';

$currentUser = currentUser();
$currentRole = (string) ($currentUser['role'] ?? '');

if (!isLoggedIn() || !in_array($currentRole, ['super_admin', 'admin', 'department_manager'], true)) {
            jsonResponse(['error' => t('common.unauthorized')], 403);
}

$pdo = getPDO();
ensureSchedulerSchema($pdo);
$shiftModel = new ShiftModel($pdo);

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

$isProtectedTemplate = static function (?array $shiftRow): bool {
    $kind = strtolower(trim((string) ($shiftRow['kind'] ?? 'work')));
    return in_array($kind, ['rest', 'vacation', 'sick'], true);
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
            if ($currentRole !== 'admin') {
                jsonResponse(['ok' => false, 'error' => t('common.unauthorized')], 403);
            }
            $required = ['department_id', 'name', 'start_time', 'end_time', 'range_start', 'range_end'];
            foreach ($required as $r) {
                if (empty($input[$r]) && $input[$r] !== '0') {
                    jsonResponse(['ok' => false, 'error' => $r . ' required'], 400);
                }
            }

            $id = $shiftModel->create([
                'department_id' => (int) $input['department_id'],
                'name' => trim((string) $input['name']),
                'icon' => $input['icon'] ?? null,
                'color' => $input['color'] ?? null,
                'description' => $input['description'] ?? null,
                'kind' => 'work',
                'start_time' => $input['start_time'],
                'end_time' => $input['end_time'],
            ]);

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
                foreach ($datesToInsert as $workDate) {
                    $existingStmt->execute([
                        'shift_id' => $id,
                        'work_date' => $workDate,
                    ]);
                    if ($existingStmt->fetchColumn()) {
                        continue;
                    }
                    $insertStmt->execute([
                        'shift_id' => $id,
                        'work_date' => $workDate,
                    ]);
                }
            }

            $shift = $shiftModel->findById($id);
            jsonResponse(['ok' => true, 'shift' => $shift]);
            break;

        case 'update':
            if ($currentRole !== 'admin') {
                jsonResponse(['ok' => false, 'error' => t('common.unauthorized')], 403);
            }
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $existingShift = $shiftModel->findById($id);
            if (!$existingShift) {
                jsonResponse(['ok' => false, 'error' => 'Shift not found'], 404);
            }
            if ($isProtectedTemplate($existingShift)) {
                jsonResponse(['ok' => false, 'error' => 'System absence templates cannot be modified.'], 400);
            }
            $shiftModel->update($id, $input);
            jsonResponse(['ok' => true]);
            break;

        case 'delete':
            if ($currentRole !== 'admin') {
                jsonResponse(['ok' => false, 'error' => t('common.unauthorized')], 403);
            }
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $existingShift = $shiftModel->findById($id);
            if (!$existingShift) {
                jsonResponse(['ok' => false, 'error' => 'Shift not found'], 404);
            }
            if ($isProtectedTemplate($existingShift)) {
                jsonResponse(['ok' => false, 'error' => 'System absence templates cannot be deleted.'], 400);
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
