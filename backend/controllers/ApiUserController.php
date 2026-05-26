<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$pdo = getPDO();
$userModel = new UserModel($pdo);

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

try {
    switch ($action) {
        case 'list_by_company':
            $companyId = (int) ($input['company_id'] ?? ($_GET['company_id'] ?? 0));
            if ($companyId <= 0) jsonResponse(['ok' => false, 'error' => 'company_id required'], 400);
            $rows = $userModel->companyUsersByCompanyId($companyId);
            jsonResponse(['ok' => true, 'users' => $rows]);
            break;

        case 'create':
            $data = [
                'department_id' => $input['department_id'] ?? null,
                'first_name' => trim((string) ($input['first_name'] ?? '')),
                'last_name' => trim((string) ($input['last_name'] ?? '')),
                'email' => trim((string) ($input['email'] ?? '')),
                'phone' => $input['phone'] ?? null,
                'password' => password_hash($input['password'] ?? 'changeme', PASSWORD_DEFAULT),
                'role' => $input['role'] ?? 'employee',
                'status' => $input['status'] ?? 'active',
            ];
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
            $payload = [
                'department_id' => $input['department_id'] ?? null,
                'first_name' => $input['first_name'] ?? '',
                'last_name' => $input['last_name'] ?? '',
                'email' => $input['email'] ?? '',
                'phone' => $input['phone'] ?? null,
                'role' => $input['role'] ?? 'employee',
                'status' => $input['status'] ?? 'active',
            ];
            if (!empty($input['password'])) {
                $payload['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            $userModel->update($id, $payload);
            jsonResponse(['ok' => true]);
            break;

        case 'delete':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
            $userModel->delete($id);
            jsonResponse(['ok' => true]);
            break;

        case 'assign_head':
            $userId = (int) ($input['user_id'] ?? 0);
            $departmentId = (int) ($input['department_id'] ?? 0);
            if ($userId <= 0 || $departmentId <= 0) jsonResponse(['ok' => false, 'error' => 'user_id and department_id required'], 400);
            // Set role and department
            $userModel->update($userId, ['department_id' => $departmentId, 'first_name' => $userModel->findById($userId)['first_name'], 'last_name' => $userModel->findById($userId)['last_name'], 'email' => $userModel->findById($userId)['email'], 'phone' => $userModel->findById($userId)['phone'], 'role' => 'department_manager', 'status' => 'active']);
            jsonResponse(['ok' => true]);
            break;

        default:
            jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
}
