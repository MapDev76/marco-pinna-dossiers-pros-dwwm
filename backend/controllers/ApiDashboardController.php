<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';

if (!isLoggedIn()) {
    jsonResponse([
        'success' => false,
        'message' => 'Connexion requise.',
    ], 401);
}

// Cet endpoint JSON expose uniquement les données utiles aux clients REST ou AJAX.
$pdo = getPDO();
$user = currentUser();
$role = $user['role'] ?? 'employee';

$profileStatement = $pdo->prepare(
    'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.department_id,
            d.name AS department_name, d.company_id, c.name AS company_name
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     LEFT JOIN companies c ON c.id = d.company_id
     WHERE u.id = :id
     LIMIT 1'
);
$profileStatement->execute(['id' => $user['id']]);
$profile = $profileStatement->fetch() ?: [];

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
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'companies' => (int) $pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn(),
        'departments' => (int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn(),
    ];
}

if ($role === 'admin' && !empty($profile['company_id'])) {
    $companyUsers = $pdo->prepare(
        'SELECT COUNT(*)
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE d.company_id = :company_id'
    );
    $companyUsers->execute(['company_id' => (int) $profile['company_id']]);

    $departmentsCountStatement = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE company_id = :company_id');
    $departmentsCountStatement->execute(['company_id' => (int) $profile['company_id']]);

    $payload['stats'] = [
        'users' => (int) $companyUsers->fetchColumn(),
        'departments' => (int) $departmentsCountStatement->fetchColumn(),
    ];
}

if ($role === 'employee') {
    $employeeShifts = $pdo->prepare(
        'SELECT us.id, us.work_date, us.status, s.name AS shift_name, s.start_time, s.end_time, d.name AS department_name
         FROM user_shifts us
         INNER JOIN shifts s ON s.id = us.shift_id
         INNER JOIN departments d ON d.id = s.department_id
         WHERE us.user_id = :user_id
         ORDER BY us.work_date DESC, us.id DESC
         LIMIT 10'
    );
    $employeeShifts->execute(['user_id' => (int) $user['id']]);

    $employeeRequests = $pdo->prepare(
        'SELECT id, type, title, status, created_at
         FROM requests
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT 10'
    );
    $employeeRequests->execute(['user_id' => (int) $user['id']]);

    $payload['items'] = [
        'shifts' => $employeeShifts->fetchAll(),
        'requests' => $employeeRequests->fetchAll(),
    ];
}

jsonResponse($payload);