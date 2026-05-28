<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please log in to continue.');
    redirectTo('login');
}

$pdo = getPDO();
$userModel = new UserModel($pdo);
$companyModel = new CompanyModel($pdo);
$departmentModel = new DepartmentModel($pdo);

$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';
$profile = $userModel->profileWithRelations((int) $currentUser['id']) ?? [];

$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Administrator',
    'department_manager' => 'Department Manager',
    'employee' => 'Employee',
];

$dashboardSidebarRoleLabel = $roleLabels[$role] ?? 'User';

$dashboardSidebarSections = [];
if ($role === 'super_admin') {
    $dashboardSidebarSections = [
        [
            'title' => 'Administration',
            'icon' => '⚙',
            'buttons' => [
                ['label' => 'Companies', 'target' => 'crud-modal', 'entity' => 'companies', 'title' => 'Companies', 'variant' => 'active'],
                ['label' => 'Users', 'target' => 'crud-modal', 'entity' => 'users', 'title' => 'Users'],
                ['label' => 'Departments', 'target' => 'crud-modal', 'entity' => 'departments', 'title' => 'Departments'],
                ['label' => 'Documents', 'target' => 'crud-modal', 'entity' => 'documents', 'title' => 'Documents'],
                ['label' => 'Requests', 'target' => 'crud-modal', 'entity' => 'requests', 'title' => 'Requests'],
                ['label' => 'Notifications', 'target' => 'crud-modal', 'entity' => 'notifications', 'title' => 'Notifications'],
            ],
        ],
    ];
} elseif ($role === 'admin') {
    $dashboardSidebarSections = [
        [
            'title' => 'Management',
            'icon' => '⚙',
            'buttons' => [
                ['label' => 'Users', 'target' => 'crud-modal', 'entity' => 'users', 'title' => 'Users', 'variant' => 'active'],
                ['label' => 'Companies', 'target' => 'crud-modal', 'entity' => 'companies', 'title' => 'Companies'],
                ['label' => 'Departments', 'target' => 'crud-modal', 'entity' => 'departments', 'title' => 'Departments'],
            ],
        ],
        [
            'title' => 'Departments',
            'icon' => '📁',
            'buttons' => [
                ['label' => 'Reception', 'target' => 'crud-modal', 'entity' => 'departments', 'title' => 'Departments', 'variant' => 'active'],
                ['label' => 'Employees', 'target' => 'crud-modal', 'entity' => 'users', 'title' => 'Users'],
                ['label' => 'Requests', 'target' => 'crud-modal', 'entity' => 'requests', 'title' => 'Requests'],
                ['label' => 'Notifications', 'target' => 'crud-modal', 'entity' => 'notifications', 'title' => 'Notifications'],
            ],
        ],
        [
            'title' => 'Requests',
            'icon' => '✉',
            'buttons' => [
                ['label' => 'My requests', 'target' => 'crud-modal', 'entity' => 'requests', 'title' => 'Requests'],
            ],
        ],
        [
            'title' => 'Notifications',
            'icon' => '🔔',
            'buttons' => [
                ['label' => 'My notifications', 'target' => 'crud-modal', 'entity' => 'notifications', 'title' => 'Notifications'],
            ],
        ],
    ];
} elseif ($role === 'department_manager') {
    $dashboardSidebarSections = [
        [
            'title' => 'Management',
            'icon' => '⚙',
            'buttons' => [
                ['label' => 'Team', 'target' => 'crud-modal', 'entity' => 'team', 'title' => 'Team', 'variant' => 'active'],
            ],
        ],
        [
            'title' => 'Employees',
            'icon' => '👥',
            'buttons' => [
                ['label' => 'Team', 'target' => 'crud-modal', 'entity' => 'team', 'title' => 'Team', 'variant' => 'active'],
            ],
        ],
        [
            'title' => 'Requests',
            'icon' => '✉',
            'buttons' => [
                ['label' => 'My requests', 'target' => 'crud-modal', 'entity' => 'requests', 'title' => 'Requests'],
            ],
        ],
        [
            'title' => 'Notifications',
            'icon' => '🔔',
            'buttons' => [
                ['label' => 'My notifications', 'target' => 'crud-modal', 'entity' => 'notifications', 'title' => 'Notifications'],
            ],
        ],
    ];
} else {
    $dashboardSidebarSections = [
        [
            'title' => 'Management',
            'icon' => '⚙',
            'buttons' => [
                ['label' => 'Requests', 'target' => 'crud-modal', 'entity' => 'requests', 'title' => 'Requests', 'variant' => 'active'],
                ['label' => 'Notifications', 'target' => 'crud-modal', 'entity' => 'notifications', 'title' => 'Notifications'],
            ],
        ],
        [
            'title' => 'Requests',
            'icon' => '✉',
            'buttons' => [
                ['label' => 'My requests', 'target' => 'crud-modal', 'entity' => 'requests', 'title' => 'Requests', 'variant' => 'active'],
            ],
        ],
        [
            'title' => 'Notifications',
            'icon' => '🔔',
            'buttons' => [
                ['label' => 'My notifications', 'target' => 'crud-modal', 'entity' => 'notifications', 'title' => 'Notifications', 'variant' => 'active'],
            ],
        ],
    ];
}

$companyId = isset($profile['company_id']) ? (int) $profile['company_id'] : null;
$departmentId = isset($profile['department_id']) ? (int) $profile['department_id'] : null;

$pageTitle = match ($role) {
    'super_admin' => 'Super Admin Dashboard',
    'admin' => 'Admin Dashboard',
    'department_manager' => 'Department Manager Dashboard',
    'employee' => 'Employee Dashboard',
    default => 'Dashboard',
};

$viewFile = __DIR__ . '/../../public/views/admin/dashboard.php';

$stats = [
    'users' => $userModel->count(),
    'companies' => $companyModel->count(),
    'departments' => $departmentModel->count(),
];

$moduleRows = [
    'company_directory' => [],
    'company_departments' => [],
    'company_requests' => [],
    'company_users' => [],
    'company_directory_users' => [],
    'company_directory_departments' => [],
    'departments' => [],
    'team' => [],
    'notifications' => [],
    'shifts' => [],
    'requests' => [],
    'attendances' => [],
    'documents' => [],
];

$modalCompanies = [];
$modalUsers = [];
$modalDepartments = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dashboardAction = $_POST['dashboard_action'] ?? '';

    if ($dashboardAction === 'create_request') {
        $requestTitle = trim((string) ($_POST['request_title'] ?? ''));
        $requestMessage = trim((string) ($_POST['request_message'] ?? ''));
        $requestType = trim((string) ($_POST['request_type'] ?? 'other'));

        if ($requestTitle === '' || $requestMessage === '') {
            setFlash('error', 'The request title and message are required.');
        } else {
            $userModel->createRequestForUser((int) $currentUser['id'], $requestType, $requestTitle, $requestMessage);
            setFlash('success', 'Request sent successfully.');
        }

        redirectTo('dashboard');
    }

    if ($dashboardAction === 'create_notification') {
        $notificationTitle = trim((string) ($_POST['notification_title'] ?? ''));
        $notificationMessage = trim((string) ($_POST['notification_message'] ?? ''));

        if ($notificationTitle === '' || $notificationMessage === '') {
            setFlash('error', 'The notification title and message are required.');
        } else {
            $userModel->createNotificationForUser((int) $currentUser['id'], $notificationTitle, $notificationMessage);
            setFlash('success', 'Notification created successfully.');
        }

        redirectTo('dashboard');
    }

    if ($dashboardAction === 'update_notification') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $notificationTitle = trim((string) ($_POST['notification_title'] ?? ''));
        $notificationMessage = trim((string) ($_POST['notification_message'] ?? ''));

        if ($notificationId <= 0 || $notificationTitle === '' || $notificationMessage === '') {
            setFlash('error', 'Incomplete notification data.');
            redirectTo('dashboard');
        }

        $updated = $userModel->updateNotificationForUser(
            $notificationId,
            (int) $currentUser['id'],
            $notificationTitle,
            $notificationMessage
        );

        if ($updated) {
            setFlash('success', 'Notification updated.');
        } else {
            setFlash('error', 'Notification not found or not allowed.');
        }

        redirectTo('dashboard');
    }
}

if ($role === 'super_admin') {
    $moduleRows['company_directory'] = $companyModel->directoryWithAdminsAndDepartments();
    $moduleRows['departments'] = $departmentModel->allWithCompany();
    $modalCompanies = $companyModel->all();
    $modalUsers = $userModel->allWithRelations();
    $modalDepartments = $departmentModel->allWithCompany();
}

if ($role === 'employee') {
    $moduleRows['shifts'] = $userModel->employeeShifts((int) $currentUser['id']);
}

$moduleRows['requests'] = $userModel->employeeRequests((int) $currentUser['id']);

if ($role === 'department_manager' && $departmentId !== null) {
    $moduleRows['team'] = $userModel->teamByDepartmentId($departmentId);
}

if ($role === 'admin' && $companyId !== null) {
    $moduleRows['company_users'] = $userModel->companyUsersByCompanyId($companyId);
    $moduleRows['company_departments'] = $departmentModel->byCompanyId($companyId);
    $moduleRows['company_requests'] = $userModel->companyRequestsByCompanyId($companyId);
    $moduleRows['departments'] = $departmentModel->byCompanyId($companyId);
    $modalCompanies = array_values(array_filter(
        $companyModel->all(),
        static fn (array $company): bool => (int) $company['id'] === $companyId
    ));
    $modalUsers = $userModel->companyUsersByCompanyId($companyId);
    $modalDepartments = $departmentModel->byCompanyId($companyId);
}

if ($role === 'department_manager' && $departmentId !== null) {
    $modalUsers = $userModel->teamByDepartmentId($departmentId);
    $modalDepartments = $departmentModel->byCompanyId($companyId ?? 0);
}

$dashboardModalCompanies = $modalCompanies;
$dashboardModalUsers = $modalUsers;
$dashboardModalDepartments = $modalDepartments;
$moduleRows['notifications'] = $userModel->userNotifications((int) $currentUser['id']);
