<?php

require_once __DIR__ . '/../models/DepartmentModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';

// This controller keeps department CRUD inside the modal flow and updates the head user role when needed.
if (!isLoggedIn()) {
    setFlash('error', 'Please log in to continue.');
    redirectTo('login');
}

$pdo = getPDO();
$userModel = new UserModel($pdo);
$departmentModel = new DepartmentModel($pdo);
$companyModel = new CompanyModel($pdo);
$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';

if (!in_array($role, ['super_admin', 'admin', 'department_manager'], true)) {
    setFlash('error', 'Access denied.');
    redirectTo('dashboard');
}

$profile = $userModel->profileWithRelations((int) $currentUser['id']) ?? [];
$scopeCompanyId = isset($profile['company_id']) ? (int) $profile['company_id'] : null;
$scopeDepartmentId = isset($profile['department_id']) ? (int) $profile['department_id'] : null;

// Each role only sees its own scope: the full company for admins and a single department for department managers.

$pageTitle = 'Departments Management';

/**
 * Redirects back to the dashboard departments modal after a CRUD action.
 */
function departmentsModalRedirect(): never
{
    redirectTo('dashboard', ['modal' => 'departments']);
}

$error = null;
$formData = [
    'company_id' => '',
    'name' => '',
    'description' => '',
    'head_user_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $companyId = (int) ($_POST['company_id'] ?? 0);
    $payload = [
        'company_id' => $companyId,
        'name' => trim((string) ($_POST['name'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'head_user_id' => ($_POST['head_user_id'] ?? '') !== '' ? (int) $_POST['head_user_id'] : null,
    ];
    $formData = $payload;

    if ($role === 'admin' && $scopeCompanyId !== null) {
        $payload['company_id'] = $scopeCompanyId;
    } elseif ($role === 'department_manager' && $scopeCompanyId !== null) {
        $payload['company_id'] = $scopeCompanyId;
    }

    if ($action === 'delete') {
        if ($id > 0) {
            $departmentModel->delete($id);
        }
        departmentsModalRedirect();
    }

    if ($payload['name'] === '') {
        $error = 'Department name is required.';
        departmentsModalRedirect();
    } elseif ($payload['company_id'] <= 0) {
        $error = 'Please select a company.';
        departmentsModalRedirect();
    } else {
        $pdo->beginTransaction();

        if ($action === 'create') {
            $departmentId = $departmentModel->create($payload);
        } elseif ($action === 'update' && $id > 0) {
            $departmentModel->update($id, $payload);
            $departmentId = $id;
        } else {
            $pdo->rollBack();
            departmentsModalRedirect();
        }

        if (!empty($payload['head_user_id'])) {
            $currentHead = $userModel->findById((int) $payload['head_user_id']);
            if ($currentHead) {
                $userModel->update((int) $payload['head_user_id'], [
                    'department_id' => $departmentId,
                    'first_name' => $currentHead['first_name'],
                    'last_name' => $currentHead['last_name'],
                    'email' => $currentHead['email'],
                    'phone' => $currentHead['phone'] ?? null,
                    'role' => 'department_manager',
                    'status' => $currentHead['status'] ?? 'active',
                    'password' => '',
                ]);
            }
        }

        $pdo->commit();
        departmentsModalRedirect();
    }
}

departmentsModalRedirect();
