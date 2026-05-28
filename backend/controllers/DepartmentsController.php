<?php

require_once __DIR__ . '/../models/DepartmentModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';

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

// Chaque rôle ne voit que sa zone: entreprise entière pour l'admin, département unique pour le chef de département.

$pageTitle = 'Departments Management';
$viewFile = __DIR__ . '/../../public/views/admin/departments.php';
$error = null;
$successMessage = null;
$editingDepartment = null;
$formData = [
    'company_id' => '',
    'name' => '',
    'description' => '',
    'head_user_id' => '',
];

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    $editingDepartment = $departmentModel->findById((int) $_GET['id']);
}

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
            $successMessage = 'Department deleted.';
        }
    } elseif ($payload['name'] === '') {
        $error = 'Department name is required.';
    } elseif ($payload['company_id'] <= 0) {
        $error = 'Please select a company.';
    } else {
        if ($action === 'create') {
            $departmentModel->create($payload);
            $successMessage = 'Department created.';
        }

        if ($action === 'update' && $id > 0) {
            $departmentModel->update($id, $payload);
            $successMessage = 'Department updated.';
        }
    }
}

$departments = $departmentModel->allWithCompany();
$users = $userModel->allForSelect();

if ($role === 'admin' && $scopeCompanyId !== null) {
    $departmentStatement = $pdo->prepare(
        'SELECT d.*, c.name AS company_name
         FROM departments d
         LEFT JOIN companies c ON c.id = d.company_id
         WHERE d.company_id = :company_id
         ORDER BY d.created_at DESC, d.id DESC'
    );
    $departmentStatement->execute(['company_id' => $scopeCompanyId]);
    $departments = $departmentStatement->fetchAll();
}

if ($role === 'department_manager' && $scopeDepartmentId !== null) {
    $departmentStatement = $pdo->prepare(
        'SELECT d.*, c.name AS company_name
         FROM departments d
         LEFT JOIN companies c ON c.id = d.company_id
         WHERE d.id = :department_id
         ORDER BY d.created_at DESC, d.id DESC'
    );
    $departmentStatement->execute(['department_id' => $scopeDepartmentId]);
    $departments = $departmentStatement->fetchAll();
}

$companies = $companyModel->all();

if ($role === 'admin' && $scopeCompanyId !== null) {
    $companies = array_values(array_filter($companies, static fn (array $company): bool => (int) $company['id'] === $scopeCompanyId));
    $usersStatement = $pdo->prepare(
        'SELECT u.id, u.first_name, u.last_name, u.email, u.role
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE d.company_id = :company_id
         ORDER BY u.first_name, u.last_name'
    );
    $usersStatement->execute(['company_id' => $scopeCompanyId]);
    $users = $usersStatement->fetchAll();
} elseif ($role === 'department_manager' && $scopeCompanyId !== null && $scopeDepartmentId !== null) {
    $companies = array_values(array_filter($companies, static fn (array $company): bool => (int) $company['id'] === $scopeCompanyId));
    $users = array_values(array_filter($users, static fn (array $user): bool => (int) ($user['id'] ?? 0) > 0));
}
