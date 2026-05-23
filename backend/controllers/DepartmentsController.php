<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/DepartmentModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';

requireSuperAdmin();

$pdo = getPDO();
$departmentModel = new DepartmentModel($pdo);
$companyModel = new CompanyModel($pdo);

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
$companies = $companyModel->all();
