<?php

require_once __DIR__ . '/../models/CompanyModel.php';

if (!isLoggedIn()) {
    setFlash('error', 'Veuillez vous connecter pour continuer.');
    redirectTo('login');
}

$pdo = getPDO();
$departmentModel = new DepartmentModel($pdo);
$companyModel = new CompanyModel($pdo);
$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';

if (!in_array($role, ['super_admin', 'admin', 'department_manager'], true)) {
    setFlash('error', 'Accès refusé.');
    redirectTo('dashboard');
}

$scopeCompanyId = null;
$scopeDepartmentId = null;
if ($role !== 'super_admin') {
    $scopeStatement = $pdo->prepare(
        'SELECT u.department_id, d.company_id
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.id = :id
         LIMIT 1'
    );
    $scopeStatement->execute(['id' => $currentUser['id']]);
    $scope = $scopeStatement->fetch() ?: [];
    $scopeCompanyId = isset($scope['company_id']) ? (int) $scope['company_id'] : null;
    $scopeDepartmentId = isset($scope['department_id']) ? (int) $scope['department_id'] : null;
}

// Chaque rôle ne voit que sa zone: entreprise entière pour l'admin, département unique pour le chef de département.

$pageTitle = 'Gestion des départements';
$viewFile = __DIR__ . '/../../public/views/admin/departments.php';
$error = null;
$editingDepartment = null;
$formData = [
    'company_id' => '',
    'name' => '',
    'description' => '',
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
    ];
    $formData = $payload;

    if ($action === 'delete') {
        if ($id > 0) {
            $departmentModel->delete($id);
            setFlash('success', 'Département supprimé.');
        }
        redirectTo('departments');
    }

    if ($payload['name'] === '') {
        $error = 'Le nom du département est obligatoire.';
    } elseif ($payload['company_id'] <= 0) {
        $error = 'Veuillez choisir une entreprise.';
    } else {
        if ($action === 'create') {
            $departmentModel->create($payload);
            setFlash('success', 'Département créé.');
            redirectTo('departments');
        }

        if ($action === 'update' && $id > 0) {
            $departmentModel->update($id, $payload);
            setFlash('success', 'Département mis à jour.');
            redirectTo('departments');
        }
    }
}

$departments = $departmentModel->allWithCompany();

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
