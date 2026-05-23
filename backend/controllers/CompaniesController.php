<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/CompanyModel.php';

requireSuperAdmin();

$pdo = getPDO();
$companyModel = new CompanyModel($pdo);

$pageTitle = 'Gestion des entreprises';
$viewFile = __DIR__ . '/../../public/views/admin/companies.php';
$error = null;
$editingCompany = null;
$formData = [
    'name' => '',
    'type' => 'other',
    'address' => '',
    'city' => '',
    'province' => '',
    'zip_code' => '',
    'phone' => '',
    'email' => '',
];

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    $editingCompany = $companyModel->findById((int) $_GET['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $payload = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'type' => trim((string) ($_POST['type'] ?? 'other')),
        'address' => trim((string) ($_POST['address'] ?? '')),
        'city' => trim((string) ($_POST['city'] ?? '')),
        'province' => trim((string) ($_POST['province'] ?? '')),
        'zip_code' => trim((string) ($_POST['zip_code'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
    ];
    $formData = $payload;

    if ($action === 'delete') {
        if ($id > 0) {
            $companyModel->delete($id);
            setFlash('success', 'Entreprise supprimée.');
        }
        redirectTo('companies');
    }

    if ($payload['name'] === '') {
        $error = 'Le nom de l’entreprise est obligatoire.';
    } elseif (!in_array($payload['type'], ['hotel', 'hospital', 'clinic', 'elderly_center', 'restaurant', 'other'], true)) {
        $error = 'Type d’entreprise invalide.';
    } else {
        if ($action === 'create') {
            $companyModel->create($payload);
            setFlash('success', 'Entreprise créée.');
            redirectTo('companies');
        }

        if ($action === 'update' && $id > 0) {
            $companyModel->update($id, $payload);
            setFlash('success', 'Entreprise mise à jour.');
            redirectTo('companies');
        }
    }
}

$companies = $companyModel->all();
