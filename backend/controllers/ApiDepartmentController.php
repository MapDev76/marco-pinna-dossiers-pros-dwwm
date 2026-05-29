<?php
/**
 * API endpoint for department operations (list/create/update/delete).
 *
 * This controller is intended for Super Admin authenticated calls and returns
 * JSON responses. It expects an `action` parameter and optional payload data
 * in JSON or form-encoded POST bodies.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/DepartmentModel.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$pdo = getPDO();
$deptModel = new DepartmentModel($pdo);

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

try {
    switch ($action) {
        case 'list':
            $companyId = (int) ($input['company_id'] ?? ($_GET['company_id'] ?? 0));
            if ($companyId <= 0) jsonResponse(['ok' => false, 'error' => 'company_id required'], 400);
            $rows = $deptModel->byCompanyId($companyId);
            jsonResponse(['ok' => true, 'departments' => $rows]);
            break;

        case 'create':
            $companyId = (int) ($input['company_id'] ?? 0);
            $name = trim((string) ($input['name'] ?? ''));
            if ($companyId <= 0 || $name === '') jsonResponse(['ok' => false, 'error' => 'company_id and name required'], 400);
            $id = $deptModel->create([
                'company_id' => $companyId,
                'name' => $name,
                'description' => $input['description'] ?? null,
                'head_user_id' => $input['head_user_id'] ?? null,
            ]);
            $dept = $deptModel->findById($id);
            jsonResponse(['ok' => true, 'department' => $dept]);
            break;

        case 'update':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $deptModel->update($id, [
                'company_id' => $input['company_id'],
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'head_user_id' => $input['head_user_id'] ?? null,
            ]);
            jsonResponse(['ok' => true]);
            break;

        case 'delete':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $deptModel->delete($id);
            jsonResponse(['ok' => true]);
            break;

        default:
            jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
}
