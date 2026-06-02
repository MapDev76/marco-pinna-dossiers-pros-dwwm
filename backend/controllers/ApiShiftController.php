<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/ShiftModel.php';

if (!isLoggedIn() || !in_array((currentUser()['role'] ?? ''), ['super_admin', 'admin', 'department_manager'], true)) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$pdo = getPDO();
ensureSchedulerSchema($pdo);
$shiftModel = new ShiftModel($pdo);

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

try {
    switch ($action) {
        case 'list':
            $departmentId = (int) ($input['department_id'] ?? 0);
            if ($departmentId > 0) {
                $rows = $shiftModel->byDepartmentId($departmentId);
                jsonResponse(['ok' => true, 'shifts' => $rows]);
            }
            jsonResponse(['ok' => false, 'error' => 'department_id required'], 400);
            break;

        case 'create':
            $required = ['department_id', 'name', 'start_time', 'end_time'];
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
                'kind' => $input['kind'] ?? 'work',
                'start_time' => $input['start_time'],
                'end_time' => $input['end_time'],
            ]);

            $rangeStart = trim((string) ($input['range_start'] ?? ''));
            $rangeEnd = trim((string) ($input['range_end'] ?? ''));
            $rangeMode = trim((string) ($input['range_mode'] ?? 'none'));
            $activeDays = max(0, min(31, (int) ($input['active_days'] ?? 0)));
            if ($rangeStart !== '' && $rangeEnd !== '' && $rangeMode !== 'none') {
                $start = new DateTimeImmutable($rangeStart);
                $end = new DateTimeImmutable($rangeEnd);
                if ($end < $start) {
                    [$start, $end] = [$end, $start];
                }

                $datesToInsert = [];
                $cursorIndex = 0;
                foreach (new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day')) as $date) {
                    $include = false;
                    if ($rangeMode === 'all' || $rangeMode === 'date_range') {
                        $include = true;
                    } elseif ($rangeMode === 'weekly') {
                        $dayOfCycle = ($cursorIndex % 7) + 1;
                        $include = $dayOfCycle <= max(0, min(7, $activeDays));
                    } elseif ($rangeMode === 'monthly') {
                        $dayOfMonth = (int) $date->format('j');
                        $include = $dayOfMonth <= max(0, min(31, $activeDays));
                    }

                    if ($include) {
                        $datesToInsert[] = $date->format('Y-m-d');
                    }
                    $cursorIndex++;
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
            }

            $shift = $shiftModel->findById($id);
            jsonResponse(['ok' => true, 'shift' => $shift]);
            break;

        case 'update':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $shiftModel->update($id, $input);
            jsonResponse(['ok' => true]);
            break;

        case 'delete':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $shiftModel->delete($id);
            jsonResponse(['ok' => true]);
            break;

        default:
            jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
}
