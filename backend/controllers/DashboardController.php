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

$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Administrateur',
    'department_manager' => 'Chef de département',
    'employee' => 'Employé',
];

$dashboardSidebarRoleLabel = $roleLabels[$role] ?? 'Utilisateur';

$dashboardSidebarSections = [];
if ($role === 'super_admin') {
    $dashboardSidebarSections = [
        [
            'title' => 'Gestion',
            'icon' => '⚙',
            'buttons' => [
                ['label' => 'Utilisateurs', 'target' => 'modal-super-actions', 'variant' => 'active'],
                ['label' => 'Entreprises', 'target' => 'modal-super-directory'],
                ['label' => 'Départements', 'target' => 'modal-super-actions'],
            ],
        ],
        [
            'title' => 'Departments',
            'icon' => '📁',
            'buttons' => [
                ['label' => 'Companies', 'target' => 'modal-super-directory', 'variant' => 'active'],
                ['label' => 'Actions', 'target' => 'modal-super-actions'],
            ],
        ],
        [
            'title' => 'Requests',
            'icon' => '✉',
            'buttons' => [
                ['label' => 'Mes requests', 'target' => 'modal-global-requests'],
            ],
        ],
        [
            'title' => 'Notifications',
            'icon' => '🔔',
            'buttons' => [
                ['label' => 'Mes notifications', 'target' => 'modal-global-notifications'],
            ],
        ],
    ];
} elseif ($role === 'admin') {
    $dashboardSidebarSections = [
        [
            'title' => 'Gestion',
            'icon' => '⚙',
            'buttons' => [
                ['label' => 'Utilisateurs', 'target' => 'modal-admin-employees', 'variant' => 'active'],
                ['label' => 'Entreprises', 'target' => 'modal-admin-departments'],
                ['label' => 'Départements', 'target' => 'modal-admin-departments'],
            ],
        ],
        [
            'title' => 'Departments',
            'icon' => '📁',
            'buttons' => [
                ['label' => 'Reception', 'target' => 'modal-admin-departments', 'variant' => 'active'],
                ['label' => 'Employees', 'target' => 'modal-admin-employees'],
                ['label' => 'Requests', 'target' => 'modal-admin-requests'],
                ['label' => 'Notifications', 'target' => 'modal-admin-notifications'],
            ],
        ],
        [
            'title' => 'Requests',
            'icon' => '✉',
            'buttons' => [
                ['label' => 'Mes requests', 'target' => 'modal-global-requests'],
            ],
        ],
        [
            'title' => 'Notifications',
            'icon' => '🔔',
            'buttons' => [
                ['label' => 'Mes notifications', 'target' => 'modal-global-notifications'],
            ],
        ],
    ];
} elseif ($role === 'department_manager') {
    $dashboardSidebarSections = [
        [
            'title' => 'Gestion',
            'icon' => '⚙',
            'buttons' => [
                ['label' => 'Équipe', 'target' => 'modal-manager-team', 'variant' => 'active'],
            ],
        ],
        [
            'title' => 'Employees',
            'icon' => '👥',
            'buttons' => [
                ['label' => 'Team', 'target' => 'modal-manager-team', 'variant' => 'active'],
            ],
        ],
        [
            'title' => 'Requests',
            'icon' => '✉',
            'buttons' => [
                ['label' => 'Mes requests', 'target' => 'modal-global-requests'],
            ],
        ],
        [
            'title' => 'Notifications',
            'icon' => '🔔',
            'buttons' => [
                ['label' => 'Mes notifications', 'target' => 'modal-global-notifications'],
            ],
        ],
    ];
} else {
    $dashboardSidebarSections = [
        [
            'title' => 'Gestion',
            'icon' => '⚙',
            'buttons' => [
                ['label' => 'Requests', 'target' => 'modal-global-requests', 'variant' => 'active'],
                ['label' => 'Notifications', 'target' => 'modal-global-notifications'],
            ],
        ],
        [
            'title' => 'Requests',
            'icon' => '✉',
            'buttons' => [
                ['label' => 'Mes requests', 'target' => 'modal-global-requests', 'variant' => 'active'],
            ],
        ],
        [
            'title' => 'Notifications',
            'icon' => '🔔',
            'buttons' => [
                ['label' => 'Mes notifications', 'target' => 'modal-global-notifications', 'variant' => 'active'],
            ],
        ],
    ];
}

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
    'company_directory' => [],
    'company_departments' => [],
    'company_requests' => [],
    'company_users' => [],
    'team' => [],
    'notifications' => [],
    'shifts' => [],
    'requests' => [],
    'attendances' => [],
    'documents' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dashboardAction = $_POST['dashboard_action'] ?? '';

    if ($dashboardAction === 'create_request') {
        $requestTitle = trim((string) ($_POST['request_title'] ?? ''));
        $requestMessage = trim((string) ($_POST['request_message'] ?? ''));
        $requestType = trim((string) ($_POST['request_type'] ?? 'other'));

        if ($requestTitle === '' || $requestMessage === '') {
            setFlash('error', 'Le titre et le message de la demande sont obligatoires.');
        } else {
            $userModel->createRequestForUser((int) $currentUser['id'], $requestType, $requestTitle, $requestMessage);
            setFlash('success', 'Demande envoyée avec succès.');
        }

        redirectTo('dashboard');
    }

    if ($dashboardAction === 'create_notification') {
        $notificationTitle = trim((string) ($_POST['notification_title'] ?? ''));
        $notificationMessage = trim((string) ($_POST['notification_message'] ?? ''));

        if ($notificationTitle === '' || $notificationMessage === '') {
            setFlash('error', 'Le titre et le message de la notification sont obligatoires.');
        } else {
            $userModel->createNotificationForUser((int) $currentUser['id'], $notificationTitle, $notificationMessage);
            setFlash('success', 'Notification créée avec succès.');
        }

        redirectTo('dashboard');
    }

    if ($dashboardAction === 'update_notification') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $notificationTitle = trim((string) ($_POST['notification_title'] ?? ''));
        $notificationMessage = trim((string) ($_POST['notification_message'] ?? ''));

        if ($notificationId <= 0 || $notificationTitle === '' || $notificationMessage === '') {
            setFlash('error', 'Données de notification incomplètes.');
            redirectTo('dashboard');
        }

        $updated = $userModel->updateNotificationForUser(
            $notificationId,
            (int) $currentUser['id'],
            $notificationTitle,
            $notificationMessage
        );

        if ($updated) {
            setFlash('success', 'Notification mise à jour.');
        } else {
            setFlash('error', 'Notification introuvable ou non autorisée.');
        }

        redirectTo('dashboard');
    }
}

if ($role === 'super_admin') {
    $moduleRows['company_directory'] = $companyModel->directoryWithAdminsAndDepartments();
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
}

$moduleRows['notifications'] = $userModel->userNotifications((int) $currentUser['id']);
