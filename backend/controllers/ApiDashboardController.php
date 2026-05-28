<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';

if (!isLoggedIn()) {
    jsonResponse([
        'success' => false,
        'message' => 'Login required.',
    ], 401);
}

// Cet endpoint JSON expose uniquement les données utiles aux clients REST ou AJAX.
$pdo = getPDO();
$userModel = new UserModel($pdo);
$companyModel = new CompanyModel($pdo);
$departmentModel = new DepartmentModel($pdo);
$user = currentUser();
$role = $user['role'] ?? 'employee';
$profile = $userModel->profileWithRelations((int) $user['id']) ?? [];

$payload = [
    'success' => true,
    'user' => [
        'id' => (int) $user['id'],
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'email' => $user['email'] ?? '',
        'role' => $role,
    ],
    'profile' => $profile,
    'dashboard_route' => 'dashboard',
];

if ($role === 'super_admin') {
    $payload['stats'] = [
        'users' => $userModel->count(),
        'companies' => $companyModel->count(),
        'departments' => $departmentModel->count(),
    ];
}

if ($role === 'admin' && !empty($profile['company_id'])) {
    $payload['stats'] = [
        'users' => $userModel->countByCompanyId((int) $profile['company_id']),
        'departments' => $departmentModel->countByCompanyId((int) $profile['company_id']),
    ];
}

if ($role === 'employee') {
    $payload['items'] = [
        'shifts' => $userModel->employeeShifts((int) $user['id']),
        'requests' => $userModel->employeeRequests((int) $user['id']),
    ];
}

jsonResponse($payload);