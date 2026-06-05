<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/CompanyModel.php';

// This controller handles modal-based CRUD submissions for companies.
if (!isLoggedIn()) {
    setFlash('error', t('common.login_required'));
    redirectTo('login');
}

$pdo = getPDO();
$companyModel = new CompanyModel($pdo);
$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';

if (!in_array($role, ['super_admin', 'admin'], true)) {
    setFlash('error', t('common.access_denied'));
    redirectTo('dashboard');
}

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

$pageTitle = t('common.companies') . ' - ' . t('common.management');

/**
 * Redirects back to the dashboard companies modal after a CRUD action.
 */
function companiesModalRedirect(): never
{
    redirectTo('dashboard', ['modal' => 'companies']);
}
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
        }
        companiesModalRedirect();
    }

    if ($payload['name'] === '') {
        setFlash('error', 'Company name is required.');
        companiesModalRedirect();
    } elseif (!in_array($payload['type'], ['hotel', 'hospital', 'clinic', 'elderly_center', 'restaurant', 'other'], true)) {
        setFlash('error', 'Invalid company type.');
        companiesModalRedirect();
    } else {
        if ($action === 'create') {
            $companyModel->create($payload);
            companiesModalRedirect();
        }

        if ($action === 'update' && $id > 0) {
            $companyModel->update($id, $payload);
            companiesModalRedirect();
        }

        companiesModalRedirect();
    }
}

companiesModalRedirect();
