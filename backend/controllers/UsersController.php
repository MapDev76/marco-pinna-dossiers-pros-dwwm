<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please log in to continue.');
    redirectTo('login');
}

$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';

if (!in_array($role, ['super_admin', 'admin', 'department_manager'], true)) {
    setFlash('error', 'Access denied.');
    redirectTo('dashboard');
}

$pdo = getPDO();
$userModel = new UserModel($pdo);
$departmentModel = new DepartmentModel($pdo);
$companyModel = new CompanyModel($pdo);
$profile = $userModel->profileWithRelations((int) $currentUser['id']) ?? [];
$scopeCompanyId = isset($profile['company_id']) ? (int) $profile['company_id'] : null;
$scopeDepartmentId = isset($profile['department_id']) ? (int) $profile['department_id'] : null;
$defaultReceptionDepartment = $departmentModel->findByNameAndCompanyId('Reception', $scopeCompanyId);
$defaultReceptionDepartmentId = isset($defaultReceptionDepartment['id']) ? (int) $defaultReceptionDepartment['id'] : null;

$pageTitle = 'Users Management';
$viewFile = __DIR__ . '/../../public/views/admin/users.php';
$error = null;
$successMessage = null;
$editingUser = null;
$formData = [
    'department_id' => '',
    'company_id' => '',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'password' => '',
    'role' => 'employee',
    'status' => 'active',
];

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    $editingUser = $userModel->findById((int) $_GET['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $departmentId = $_POST['department_id'] !== '' ? (int) $_POST['department_id'] : null;
    $userRole = trim((string) ($_POST['role'] ?? 'employee'));

    if ($role === 'super_admin') {
        if ($userRole === 'super_admin') {
            $departmentId = null;
        } elseif ($userRole === 'admin') {
            if ($departmentId === null) {
                $departmentId = $defaultReceptionDepartmentId;
            }
        } elseif ($departmentId !== null) {
        }
    } elseif ($role === 'admin') {
        if (!in_array($userRole, ['admin', 'department_manager', 'employee'], true)) {
            $userRole = 'employee';
        }
        if ($userRole === 'admin') {
            if ($departmentId === null) {
                $departmentId = $defaultReceptionDepartmentId;
            }
        } elseif ($departmentId !== null) {
            $department = $departmentModel->findById($departmentId);
            if (isset($department['company_id']) && (int) $department['company_id'] !== $scopeCompanyId) {
                $error = 'The department must belong to your company.';
            }
        }
    } elseif ($role === 'department_manager') {
        $departmentId = $scopeDepartmentId;
        $userRole = 'employee';
    }

    $payload = [
        'department_id' => $departmentId,
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'role' => $userRole,
        'status' => trim((string) ($_POST['status'] ?? 'active')),
    ];
    $formData = $payload + ['password' => ''];

    if ($action === 'delete') {
        if ($id > 0) {
            $userModel->delete($id);
            $successMessage = 'User deleted.';
        }
    } elseif ($error === null) {
        if ($payload['first_name'] === '' || $payload['last_name'] === '' || $payload['email'] === '') {
            $error = 'First name, last name, and email are required.';
        } elseif (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (!in_array($payload['role'], ['super_admin', 'admin', 'department_manager', 'employee'], true)) {
            $error = 'Invalid role.';
        } elseif (!in_array($payload['status'], ['active', 'inactive'], true)) {
            $error = 'Invalid status.';
        } elseif ($payload['role'] === 'super_admin' && $payload['department_id'] !== null) {
            $error = 'A Super Admin must not be linked to any department.';
        } elseif ($payload['role'] === 'admin' && $payload['department_id'] === null) {
            $error = 'An Admin must be linked to a department.';
        } elseif (in_array($payload['role'], ['department_manager', 'employee'], true) && $payload['department_id'] === null) {
            $error = 'A department manager or employee must be linked to a department.';
        } else {
            $password = trim((string) ($_POST['password'] ?? ''));

            if ($action === 'create') {
                if ($password === '') {
                    $error = 'A password is required to create the user.';
                } else {
                    $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
                    $userModel->create($payload);
                    $successMessage = 'User created.';
                }
            } elseif ($action === 'update' && $id > 0) {
                if ($password !== '') {
                    $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $userModel->update($id, $payload);
                $successMessage = 'User updated.';
            }
        }
    }
}

if ($role === 'admin' && $scopeCompanyId !== null) {
    $usersStatement = $pdo->prepare(
        'SELECT u.*, d.name AS department_name, c.name AS company_name
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         LEFT JOIN companies c ON c.id = d.company_id
         WHERE d.company_id = :company_id
         ORDER BY u.created_at DESC, u.id DESC'
    );
    $usersStatement->execute(['company_id' => $scopeCompanyId]);
    $users = $usersStatement->fetchAll();
} elseif ($role === 'department_manager' && $scopeDepartmentId !== null) {
    $usersStatement = $pdo->prepare(
        'SELECT u.*, d.name AS department_name, c.name AS company_name
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         LEFT JOIN companies c ON c.id = d.company_id
         WHERE u.department_id = :department_id
         ORDER BY u.created_at DESC, u.id DESC'
    );
    $usersStatement->execute(['department_id' => $scopeDepartmentId]);
    $users = $usersStatement->fetchAll();
} else {
    $users = $userModel->allWithRelations();
}

$departments = $departmentModel->allForSelect();
$companies = $companyModel->all();

if ($role === 'admin' && $scopeCompanyId !== null) {
    $companies = array_values(array_filter($companies, static fn (array $company): bool => (int) $company['id'] === $scopeCompanyId));
    $departments = array_values(array_filter($departments, static fn (array $department): bool => (int) $department['company_id'] === $scopeCompanyId));
} elseif ($role === 'department_manager' && $scopeCompanyId !== null && $scopeDepartmentId !== null) {
    $companies = array_values(array_filter($companies, static fn (array $company): bool => (int) $company['id'] === $scopeCompanyId));
    $departments = array_values(array_filter($departments, static fn (array $department): bool => (int) $department['id'] === $scopeDepartmentId));
}
