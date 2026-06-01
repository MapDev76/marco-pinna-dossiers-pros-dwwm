<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/ShiftModel.php';

if (!isLoggedIn() || !in_array((currentUser()['role'] ?? ''), ['super_admin', 'admin', 'department_manager'], true)) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$pdo = getPDO();
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
                'start_time' => $input['start_time'],
                'end_time' => $input['end_time'],
            ]);

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
