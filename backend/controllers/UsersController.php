<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';

if (!isLoggedIn()) {
    setFlash('error', 'Veuillez vous connecter pour continuer.');
    redirectTo('login');
}

$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';

if (!in_array($role, ['super_admin', 'admin', 'department_manager'], true)) {
    setFlash('error', 'Accès refusé.');
    redirectTo('dashboard');
}

$pdo = getPDO();
$userModel = new UserModel($pdo);
$departmentModel = new DepartmentModel($pdo);
$companyModel = new CompanyModel($pdo);

$scopeStatement = $pdo->prepare(
    'SELECT u.id, u.department_id, d.company_id
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     WHERE u.id = :id
     LIMIT 1'
);
$scopeStatement->execute(['id' => $currentUser['id']]);
$scope = $scopeStatement->fetch() ?: [];
$scopeCompanyId = isset($scope['company_id']) ? (int) $scope['company_id'] : null;
$scopeDepartmentId = isset($scope['department_id']) ? (int) $scope['department_id'] : null;

$pageTitle = 'Gestion des utilisateurs';
$viewFile = __DIR__ . '/../../public/views/admin/users.php';
$error = null;
$editingUser = null;
$formData = [
    'department_id' => '',
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
    $payload = [
        'department_id' => $departmentId,
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'role' => trim((string) ($_POST['role'] ?? 'employee')),
        'status' => trim((string) ($_POST['status'] ?? 'active')),
    ];
    $formData = $payload + ['password' => ''];

    if ($action === 'delete') {
        if ($id > 0) {
            $userModel->delete($id);
            setFlash('success', 'Utilisateur supprimé.');
        }
        redirectTo('users');
    }

    if ($payload['first_name'] === '' || $payload['last_name'] === '' || $payload['email'] === '') {
        $error = 'Les champs prénom, nom et email sont obligatoires.';
    } elseif (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (!in_array($payload['role'], ['super_admin', 'admin', 'department_manager', 'employee'], true)) {
        $error = 'Rôle invalide.';
    } elseif (!in_array($payload['status'], ['active', 'inactive'], true)) {
        $error = 'Statut invalide.';
    } else {
        $password = trim((string) ($_POST['password'] ?? ''));

        if ($action === 'create') {
            if ($password === '') {
                $error = 'Le mot de passe est obligatoire pour la création.';
            } else {
                $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
                $userModel->create($payload);
                setFlash('success', 'Utilisateur créé.');
                redirectTo('users');
            }
        } elseif ($action === 'update' && $id > 0) {
            if ($password !== '') {
                $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $userModel->update($id, $payload);
            setFlash('success', 'Utilisateur mis à jour.');
            redirectTo('users');
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
