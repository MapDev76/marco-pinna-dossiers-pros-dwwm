<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';

if (!isLoggedIn()) {
    setFlash('error', 'Veuillez vous connecter pour continuer.');
    redirectTo('login');
}

$pdo = getPDO();
$userModel = new UserModel($pdo);
$companyModel = new CompanyModel($pdo);
$departmentModel = new DepartmentModel($pdo);

$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';
$profile = $userModel->profileWithRelations((int) $currentUser['id']) ?? [];

$companyId = isset($profile['company_id']) ? (int) $profile['company_id'] : null;
$departmentId = isset($profile['department_id']) ? (int) $profile['department_id'] : null;

$pageTitle = match ($role) {
    'super_admin' => 'Tableau de bord Super Admin',
    'admin' => 'Tableau de bord Administrateur',
    'department_manager' => 'Tableau de bord Chef de département',
    'employee' => 'Tableau de bord Employé',
    default => 'Tableau de bord',
};

$viewFile = __DIR__ . '/../../public/views/admin/dashboard.php';

$stats = [
    'users' => $userModel->count(),
    'companies' => $companyModel->count(),
    'departments' => $departmentModel->count(),
];

$moduleRows = [
    'shifts' => [],
    'requests' => [],
    'attendances' => [],
    'documents' => [],
];

if ($role === 'employee') {
    $moduleRows['shifts'] = $userModel->employeeShifts((int) $currentUser['id']);
    $moduleRows['requests'] = $userModel->employeeRequests((int) $currentUser['id']);
}

if ($role === 'department_manager' && $departmentId !== null) {
    $moduleRows['team'] = $userModel->teamByDepartmentId($departmentId);
}

if ($role === 'admin' && $companyId !== null) {
    $moduleRows['company_users'] = $userModel->companyUsersByCompanyId($companyId);
}
