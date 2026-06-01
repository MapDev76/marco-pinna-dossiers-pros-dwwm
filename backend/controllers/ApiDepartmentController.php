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

$profile = currentUser();
$role = $profile['role'] ?? null;
$isSuperAdmin = $role === 'super_admin';
$isAdmin = $role === 'admin';

if (!isLoggedIn() || (!$isSuperAdmin && !$isAdmin)) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$pdo = getPDO();
$deptModel = new DepartmentModel($pdo);

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

$requestedCompanyId = (int) ($input['company_id'] ?? ($_GET['company_id'] ?? 0));
$profileCompanyId = (int) ($profile['company_id'] ?? 0);
$effectiveAdminCompanyId = $profileCompanyId > 0 ? $profileCompanyId : $requestedCompanyId;

try {
    switch ($action) {
        case 'list':
            $companyId = $isAdmin
                ? $effectiveAdminCompanyId
                : (int) ($input['company_id'] ?? ($_GET['company_id'] ?? 0));
            if ($companyId <= 0) jsonResponse(['ok' => false, 'error' => 'company_id required'], 400);
            $rows = $deptModel->byCompanyId($companyId);
            jsonResponse(['ok' => true, 'departments' => $rows]);
            break;

        case 'create':
            $companyId = $isAdmin
                ? $effectiveAdminCompanyId
                : (int) ($input['company_id'] ?? 0);
            $name = trim((string) ($input['name'] ?? ''));
            if ($companyId <= 0 || $name === '') jsonResponse(['ok' => false, 'error' => 'company_id and name required'], 400);
            $id = $deptModel->create([
                'company_id' => $companyId,
                'name' => $name,
                'icon' => $input['icon'] ?? null,
                'color' => $input['color'] ?? null,
                'description' => $input['description'] ?? null,
                'head_user_id' => $input['head_user_id'] ?? null,
            ]);
            $dept = $deptModel->findById($id);
            jsonResponse(['ok' => true, 'department' => $dept]);
            break;

        case 'update':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            if ($isAdmin) {
                if ($effectiveAdminCompanyId <= 0) {
                    jsonResponse(['ok' => false, 'error' => 'company_id required'], 400);
                }
                $target = $deptModel->findById($id);
                if (!$target || ($effectiveAdminCompanyId > 0 && (int) ($target['company_id'] ?? 0) !== $effectiveAdminCompanyId)) {
                    jsonResponse(['ok' => false, 'error' => 'Forbidden'], 403);
                }
            }
            $deptModel->update($id, [
                'company_id' => $isAdmin ? ($effectiveAdminCompanyId > 0 ? $effectiveAdminCompanyId : ((int) ($input['company_id'] ?? 0))) : $input['company_id'],
                'name' => $input['name'],
                'icon' => $input['icon'] ?? null,
                'color' => $input['color'] ?? null,
                'description' => $input['description'] ?? null,
                'head_user_id' => $input['head_user_id'] ?? null,
            ]);
            jsonResponse(['ok' => true]);
            break;

        case 'delete':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            if ($isAdmin) {
                if ($effectiveAdminCompanyId <= 0) {
                    jsonResponse(['ok' => false, 'error' => 'company_id required'], 400);
                }
                $target = $deptModel->findById($id);
                if (!$target || ($effectiveAdminCompanyId > 0 && (int) ($target['company_id'] ?? 0) !== $effectiveAdminCompanyId)) {
                    jsonResponse(['ok' => false, 'error' => 'Forbidden'], 403);
                }
            }
            $deptModel->delete($id);
            jsonResponse(['ok' => true]);
            break;

        default:
            jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
}
