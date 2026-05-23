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

$profileStatement = $pdo->prepare(
    'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.department_id,
            d.name AS department_name, d.company_id, c.name AS company_name
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     LEFT JOIN companies c ON c.id = d.company_id
     WHERE u.id = :id
     LIMIT 1'
);
$profileStatement->execute(['id' => $currentUser['id']]);
$profile = $profileStatement->fetch() ?: [];

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
    $employeeShifts = $pdo->prepare(
        'SELECT us.id, us.work_date, us.status, s.name AS shift_name, s.start_time, s.end_time, d.name AS department_name
         FROM user_shifts us
         INNER JOIN shifts s ON s.id = us.shift_id
         INNER JOIN departments d ON d.id = s.department_id
         WHERE us.user_id = :user_id
         ORDER BY us.work_date DESC, us.id DESC
         LIMIT 10'
    );
    $employeeShifts->execute(['user_id' => $currentUser['id']]);
    $moduleRows['shifts'] = $employeeShifts->fetchAll();

    $employeeRequests = $pdo->prepare(
        'SELECT id, type, title, status, created_at
         FROM requests
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT 10'
    );
    $employeeRequests->execute(['user_id' => $currentUser['id']]);
    $moduleRows['requests'] = $employeeRequests->fetchAll();
}

if ($role === 'department_manager' && $departmentId !== null) {
    $teamStatement = $pdo->prepare(
        'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status
         FROM users u
         WHERE u.department_id = :department_id
         ORDER BY u.last_name, u.first_name'
    );
    $teamStatement->execute(['department_id' => $departmentId]);
    $moduleRows['team'] = $teamStatement->fetchAll();
}

if ($role === 'admin' && $companyId !== null) {
    $companyUsers = $pdo->prepare(
        'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status, d.name AS department_name
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE d.company_id = :company_id
         ORDER BY u.last_name, u.first_name'
    );
    $companyUsers->execute(['company_id' => $companyId]);
    $moduleRows['company_users'] = $companyUsers->fetchAll();
}
