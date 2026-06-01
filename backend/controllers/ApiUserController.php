<?php
/**
 * API endpoint for user-related AJAX operations used by the dashboard.
 *
 * Expects authenticated requests from a Super Admin. Accepts JSON POSTs or
 * form-encoded requests with an `action` parameter (list_by_company, create,
 * update, delete, assign_head). Responses are JSON objects.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';

require_once __DIR__ . '/../models/DepartmentModel.php';

if (!isLoggedIn() || (!isSuperAdmin() && !isAdmin())) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$profile = currentUser();

$pdo = getPDO();
$userModel = new UserModel($pdo);
$departmentModel = new DepartmentModel($pdo);

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

try {
    switch ($action) {
        case 'list_by_company':
            if (isAdmin()) {
                $companyId = (int) ($profile['company_id'] ?? 0);
            } else {
                $companyId = (int) ($input['company_id'] ?? ($_GET['company_id'] ?? 0));
            }
            if ($companyId <= 0) jsonResponse(['ok' => false, 'error' => 'company_id is required'], 400);
            $rows = $userModel->companyUsersByCompanyId($companyId);
            // Admins must not see super_admin accounts
            if (isAdmin()) {
                $rows = array_values(array_filter($rows, static fn($u) => ($u['role'] ?? '') !== 'super_admin'));
            }
            jsonResponse(['ok' => true, 'users' => $rows]);
            break;

        case 'create':
            $departmentId = $input['department_id'] ?? null;
            $userRole = trim((string) ($input['role'] ?? 'employee'));

            if ($userRole === 'super_admin') {
                if (isAdmin()) jsonResponse(['ok' => false, 'error' => 'Forbidden role'], 403);
                $departmentId = null;
            } elseif ($userRole === 'admin') {
                if ($departmentId === null || $departmentId === '') {
                    $reception = $departmentModel->findByNameAndCompanyId('Reception');
                    if ($reception) {
                        $departmentId = (int) $reception['id'];
                    }
                }
            } else {
                if ($departmentId === null || $departmentId === '') {
                    jsonResponse(['ok' => false, 'error' => 'department_id is required for department_manager/employee'], 400);
                }
                $department = $departmentModel->findById((int) $departmentId);
                if (!$department) {
                    jsonResponse(['ok' => false, 'error' => 'Department not found'], 404);
                }
            }
            $data = [
                'department_id' => $departmentId,
                'first_name' => trim((string) ($input['first_name'] ?? '')),
                'last_name' => trim((string) ($input['last_name'] ?? '')),
                'email' => trim((string) ($input['email'] ?? '')),
                'phone' => $input['phone'] ?? null,
                'password' => password_hash($input['password'] ?? 'changeme', PASSWORD_DEFAULT),
                'role' => $userRole,
                'status' => $input['status'] ?? 'active',
            ];
            if (isAdmin() && !empty($departmentId)) {
                $dept = $departmentModel->findById((int) $departmentId);
                if (!$dept || (int) ($dept['company_id'] ?? 0) !== (int) ($profile['company_id'] ?? 0)) {
                    jsonResponse(['ok' => false, 'error' => 'Forbidden'], 403);
                }
            }
            if ($data['first_name'] === '' || $data['last_name'] === '' || $data['email'] === '') {
                jsonResponse(['ok' => false, 'error' => 'Missing required fields'], 400);
            }
            $id = $userModel->create($data);
            $user = $userModel->findById($id);
            jsonResponse(['ok' => true, 'user' => $user]);
            break;

        case 'update':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $departmentId = $input['department_id'] ?? null;
            $userRole = trim((string) ($input['role'] ?? 'employee'));

            if ($userRole === 'super_admin') {
                if (isAdmin()) jsonResponse(['ok' => false, 'error' => 'Forbidden role'], 403);
                $departmentId = null;
            } elseif ($userRole === 'admin') {
                if ($departmentId === null || $departmentId === '') {
                    $reception = $departmentModel->findByNameAndCompanyId('Reception');
                    if ($reception) {
                        $departmentId = (int) $reception['id'];
                    }
                }
            } else {
                if ($departmentId === null || $departmentId === '') {
                    jsonResponse(['ok' => false, 'error' => 'department_id is required for department_manager/employee'], 400);
                }
                $department = $departmentModel->findById((int) $departmentId);
                if (!$department) {
                    jsonResponse(['ok' => false, 'error' => 'Department not found'], 404);
                }
            }
            // Prevent admins from updating super_admin accounts
            $targetUser = $userModel->findById($id);
            if ($targetUser && ($targetUser['role'] ?? '') === 'super_admin' && isAdmin()) {
                jsonResponse(['ok' => false, 'error' => 'Forbidden'], 403);
            }

            $payload = [
                'department_id' => $departmentId,
                'first_name' => $input['first_name'] ?? '',
                'last_name' => $input['last_name'] ?? '',
                'email' => $input['email'] ?? '',
                'phone' => $input['phone'] ?? null,
                'role' => $userRole,
                'status' => $input['status'] ?? 'active',
            ];
            if (!empty($input['password'])) {
                $payload['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            if (isAdmin() && !empty($departmentId)) {
                $dept = $departmentModel->findById((int) $departmentId);
                if (!$dept || (int) ($dept['company_id'] ?? 0) !== (int) ($profile['company_id'] ?? 0)) {
                    jsonResponse(['ok' => false, 'error' => 'Forbidden'], 403);
                }
            }
            $userModel->update($id, $payload);
            jsonResponse(['ok' => true]);
            break;

        case 'delete':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $targetUser = $userModel->findById($id);
            if ($targetUser && ($targetUser['role'] ?? '') === 'super_admin' && isAdmin()) {
                jsonResponse(['ok' => false, 'error' => 'Forbidden'], 403);
            }
            // Admins may only delete users within their company
            if (isAdmin()) {
                $dept = $departmentModel->findById((int) ($targetUser['department_id'] ?? 0));
                if (!$dept || (int) ($dept['company_id'] ?? 0) !== (int) ($profile['company_id'] ?? 0)) {
                    jsonResponse(['ok' => false, 'error' => 'Forbidden'], 403);
                }
            }
            $userModel->delete($id);
            jsonResponse(['ok' => true]);
            break;

        case 'assign_head':
            $userId = (int) ($input['user_id'] ?? 0);
            $departmentId = (int) ($input['department_id'] ?? 0);
            if ($userId <= 0 || $departmentId <= 0) jsonResponse(['ok' => false, 'error' => 'user_id and department_id required'], 400);
            // Définir le rôle et le département
            $userModel->update($userId, ['department_id' => $departmentId, 'first_name' => $userModel->findById($userId)['first_name'], 'last_name' => $userModel->findById($userId)['last_name'], 'email' => $userModel->findById($userId)['email'], 'phone' => $userModel->findById($userId)['phone'], 'role' => 'department_manager', 'status' => 'active']);
            jsonResponse(['ok' => true]);
            break;

        default:
            jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
}
