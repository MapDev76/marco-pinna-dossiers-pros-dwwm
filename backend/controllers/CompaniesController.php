<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/CompanyModel.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please log in to continue.');
    redirectTo('login');
}

$pdo = getPDO();
$companyModel = new CompanyModel($pdo);
$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';

if (!in_array($role, ['super_admin', 'admin'], true)) {
    setFlash('error', 'Access denied.');
    redirectTo('dashboard');
}

$scopeCompanyId = null;
if ($role === 'admin') {
    $scopeStatement = $pdo->prepare(
        'SELECT d.company_id
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.id = :id
         LIMIT 1'
    );
    $scopeStatement->execute(['id' => $currentUser['id']]);
    $scopeCompanyId = (int) ($scopeStatement->fetchColumn() ?: 0) ?: null;
}

$pageTitle = 'Companies Management';
$viewFile = __DIR__ . '/../../public/views/admin/companies.php';
$error = null;
$successMessage = null;
$editingCompany = null;
$formData = [
    'name' => '',
    'type' => 'other',
    'address' => '',
    'city' => '',
    'zip_code' => '',
    'phone' => '',
    'email' => '',
    'logo_path' => '',
    'signature_ip' => '',
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
        'zip_code' => trim((string) ($_POST['zip_code'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'logo_path' => trim((string) ($_POST['logo_path'] ?? '')),
        'signature_ip' => trim((string) ($_POST['signature_ip'] ?? '')),
    ];
    $formData = $payload;

    if ($action === 'delete') {
        if ($id > 0) {
            $companyModel->delete($id);
            $successMessage = 'Company deleted.';
        }
    } elseif ($payload['name'] === '') {
        $error = 'Company name is required.';
    } elseif (!in_array($payload['type'], ['hotel', 'hospital', 'clinic', 'elderly_center', 'restaurant', 'other'], true)) {
        $error = 'Invalid company type.';
    } else {
        if ($action === 'create') {
            $companyModel->create($payload);
            $successMessage = 'Company created.';
        }

        if ($action === 'update' && $id > 0) {
            $companyModel->update($id, $payload);
            $successMessage = 'Company updated.';
        }
    }
}

$companies = $companyModel->all();

if ($role === 'admin' && $scopeCompanyId !== null) {
    $companyStatement = $pdo->prepare('SELECT * FROM companies WHERE id = :id ORDER BY created_at DESC, id DESC');
    $companyStatement->execute(['id' => $scopeCompanyId]);
    $companies = $companyStatement->fetchAll();
}
