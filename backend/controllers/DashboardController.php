<?php
/**
 * Dashboard controller
 *
 * Prepares data for the shared dashboard UI and for the modal CRUD templates.
 * The controller enforces authentication and role-based scoping and populates
 * `$dashboardSidebarSections` and the `$dashboardModal*` arrays consumed by
 * `app/layout/*` templates.
 */
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

$companyId = isset($profile['company_id']) ? (int) $profile['company_id'] : null;
$departmentId = isset($profile['department_id']) ? (int) $profile['department_id'] : null;

if ($role === 'admin' && (int) ($companyId ?? 0) <= 0) {
    $fallbackCompanyId = (int) ($currentUser['company_id'] ?? 0);

    if ($fallbackCompanyId <= 0 && (int) ($departmentId ?? 0) > 0) {
        $fallbackDepartment = $departmentModel->findById((int) $departmentId);
        $fallbackCompanyId = (int) ($fallbackDepartment['company_id'] ?? 0);
    }

    if ($fallbackCompanyId <= 0) {
        $companiesFallback = $companyModel->all();
        $fallbackCompanyId = (int) ($companiesFallback[0]['id'] ?? 0);
    }

    if ($fallbackCompanyId > 0) {
        $companyId = $fallbackCompanyId;
        $fallbackCompany = $companyModel->findById($fallbackCompanyId);
        if (!empty($fallbackCompany['name'])) {
            $profile['company_name'] = $fallbackCompany['name'];
        }
        if (!empty($fallbackCompany['type'])) {
            $profile['company_type'] = $fallbackCompany['type'];
        }
    }
}

$pageTitle = match ($role) {
    'super_admin' => 'Super Admin Dashboard',
    'admin' => 'Admin Dashboard',
    'department_manager' => 'Department Manager Dashboard',
    'employee' => 'Employee Dashboard',
    default => 'Dashboard',
};

$viewFile = __DIR__ . '/../../public/views/admin/dashboard.php';

$dashboardCalendarToday = date('Y-m-d');
$dashboardCalendarMode = in_array($role, ['admin', 'department_manager'], true) ? 'week' : 'month';
$dashboardCalendarScopeLabel = '';
$dashboardCalendarEvents = [];
$dashboardPlannerData = [
    'departments' => [],
    'users' => [],
    'shifts' => [],
    'company' => [
        'id' => $companyId,
        'name' => $profile['company_name'] ?? ($currentUser['company_name'] ?? ''),
        'type' => $profile['company_type'] ?? ($currentUser['company_type'] ?? ''),
    ],
    'companies' => [],
    'active_department_id' => null,
    'active_shift_id' => null,
    'today' => $dashboardCalendarToday,
    'mode' => $dashboardCalendarMode,
    'assignments' => [],
];

$plannerCompanyId = null;
if ($role === 'super_admin') {
    $requestedCompanyId = (int) ($_GET['settings_company_id'] ?? 0);
    $allCompanies = $companyModel->all();
    if (!empty($allCompanies)) {
        if ($requestedCompanyId > 0) {
            foreach ($allCompanies as $companyRow) {
                if ((int) ($companyRow['id'] ?? 0) === $requestedCompanyId) {
                    $plannerCompanyId = $requestedCompanyId;
                    break;
                }
            }
        }
        if ($plannerCompanyId === null) {
            $plannerCompanyId = (int) ($allCompanies[0]['id'] ?? 0);
        }
    }

    if ($plannerCompanyId !== null && $plannerCompanyId > 0) {
        $companyName = '';
        $companyType = '';
        foreach ($allCompanies as $companyRow) {
            if ((int) ($companyRow['id'] ?? 0) === $plannerCompanyId) {
                $companyName = (string) ($companyRow['name'] ?? '');
                $companyType = (string) ($companyRow['type'] ?? '');
                break;
            }
        }

        $departmentRows = $departmentModel->byCompanyId($plannerCompanyId);
        $userRows = $userModel->companyUsersByCompanyId($plannerCompanyId);
        $departmentIds = array_values(array_map(static fn (array $department): int => (int) $department['id'], $departmentRows));
        $shiftRows = [];
        if (!empty($departmentIds)) {
            $shiftSelect = 'id, department_id, name, icon, color, start_time, end_time';
            $placeholders = implode(', ', array_fill(0, count($departmentIds), '?'));
            $shiftStatement = $pdo->prepare(
                'SELECT ' . $shiftSelect . ' FROM shifts WHERE department_id IN (' . $placeholders . ') ORDER BY department_id ASC, start_time ASC, id ASC'
            );
            $shiftStatement->execute($departmentIds);
            $shiftRows = $shiftStatement->fetchAll();
        }

        $groupedUsers = [];
        foreach ($userRows as $userRow) {
            $deptKey = (int) ($userRow['department_id'] ?? 0);
            if (!isset($groupedUsers[$deptKey])) {
                $groupedUsers[$deptKey] = [];
            }
            $groupedUsers[$deptKey][] = $userRow;
        }

        $groupedShifts = [];
        foreach ($shiftRows as $shiftRow) {
            $deptKey = (int) ($shiftRow['department_id'] ?? 0);
            if (!isset($groupedShifts[$deptKey])) {
                $groupedShifts[$deptKey] = [];
            }
            $groupedShifts[$deptKey][] = $shiftRow;
        }

        foreach ($departmentRows as $departmentRow) {
            $deptId = (int) $departmentRow['id'];
            $dashboardPlannerData['departments'][] = [
                'id' => $deptId,
                'company_id' => (int) ($departmentRow['company_id'] ?? $plannerCompanyId),
                'name' => $departmentRow['name'] ?? '',
                'description' => $departmentRow['description'] ?? '',
                'head_user_id' => (int) ($departmentRow['head_user_id'] ?? 0),
                'head_user_name' => $departmentRow['head_user_name'] ?? '',
                'users' => $groupedUsers[$deptId] ?? [],
                'shifts' => $groupedShifts[$deptId] ?? [],
            ];
        }

        $dashboardPlannerData['users'] = $userRows;
        $dashboardPlannerData['shifts'] = $shiftRows;
        $dashboardPlannerData['companies'] = $allCompanies;
        $dashboardPlannerData['company'] = [
            'id' => $plannerCompanyId,
            'name' => $companyName,
            'type' => $companyType,
        ];
        $dashboardPlannerData['active_department_id'] = $departmentRows[0]['id'] ?? null;
        $dashboardPlannerData['active_shift_id'] = $shiftRows[0]['id'] ?? null;

        $calendarStatement = $pdo->prepare(
            'SELECT us.id AS assignment_id,
                    us.work_date,
                    us.status,
                    us.notes,
                    s.id AS shift_id,
                    s.name AS shift_name,
                    s.icon AS shift_icon,
                    s.color AS shift_color,
                    s.start_time,
                    s.end_time,
                    d.id AS department_id,
                    d.name AS department_name,
                    u.id AS user_id,
                    CONCAT(u.first_name, " ", u.last_name) AS user_name
             FROM user_shifts us
             INNER JOIN shifts s ON s.id = us.shift_id
             INNER JOIN departments d ON d.id = s.department_id
             LEFT JOIN users u ON u.id = us.user_id
             WHERE d.company_id = :company_id
             ORDER BY us.work_date ASC, s.start_time ASC, us.id ASC'
        );
        $calendarStatement->execute(['company_id' => $plannerCompanyId]);
        $dashboardPlannerData['assignments'] = $calendarStatement->fetchAll();
    }
}

if ($role === 'admin' && $companyId !== null) {
    $dashboardCalendarScopeLabel = trim((string) (($profile['company_name'] ?? 'Company') . ' calendar'));
    $departmentRows = $departmentModel->byCompanyId($companyId);
    $userRows = $userModel->companyUsersByCompanyId($companyId);
    $departmentIds = array_values(array_map(static fn (array $department): int => (int) $department['id'], $departmentRows));
    $shiftRows = [];
    if (!empty($departmentIds)) {
        $shiftSelect = 'id, department_id, name, icon, color, start_time, end_time';
        $placeholders = implode(', ', array_fill(0, count($departmentIds), '?'));
        $shiftStatement = $pdo->prepare(
            'SELECT ' . $shiftSelect . ' FROM shifts WHERE department_id IN (' . $placeholders . ') ORDER BY department_id ASC, start_time ASC, id ASC'
        );
        $shiftStatement->execute($departmentIds);
        $shiftRows = $shiftStatement->fetchAll();
    }

    $groupedUsers = [];
    foreach ($userRows as $userRow) {
        $deptKey = (int) ($userRow['department_id'] ?? 0);
        if (!isset($groupedUsers[$deptKey])) {
            $groupedUsers[$deptKey] = [];
        }
        $groupedUsers[$deptKey][] = $userRow;
    }

    $groupedShifts = [];
    foreach ($shiftRows as $shiftRow) {
        $deptKey = (int) ($shiftRow['department_id'] ?? 0);
        if (!isset($groupedShifts[$deptKey])) {
            $groupedShifts[$deptKey] = [];
        }
        $groupedShifts[$deptKey][] = $shiftRow;
    }

    foreach ($departmentRows as $departmentRow) {
        $deptId = (int) $departmentRow['id'];
        $dashboardPlannerData['departments'][] = [
            'id' => $deptId,
            'company_id' => (int) ($departmentRow['company_id'] ?? $companyId),
            'name' => $departmentRow['name'] ?? '',
            'description' => $departmentRow['description'] ?? '',
            'head_user_id' => (int) ($departmentRow['head_user_id'] ?? 0),
            'head_user_name' => $departmentRow['head_user_name'] ?? '',
            'users' => $groupedUsers[$deptId] ?? [],
            'shifts' => $groupedShifts[$deptId] ?? [],
        ];
    }

    $dashboardPlannerData['users'] = $userRows;
    $dashboardPlannerData['shifts'] = $shiftRows;
    $adminCompany = $companyModel->findById((int) $companyId);
    $dashboardPlannerData['company'] = [
        'id' => (int) $companyId,
        'name' => (string) ($adminCompany['name'] ?? ($profile['company_name'] ?? '')),
        'type' => (string) ($adminCompany['type'] ?? ($profile['company_type'] ?? '')),
    ];
    $dashboardPlannerData['companies'] = array_values(array_filter(
        $companyModel->all(),
        static fn (array $company): bool => (int) ($company['id'] ?? 0) === $companyId
    ));
    $dashboardPlannerData['active_department_id'] = $departmentRows[0]['id'] ?? null;
    $dashboardPlannerData['active_shift_id'] = $shiftRows[0]['id'] ?? null;
    $calendarStatement = $pdo->prepare(
        'SELECT us.id AS assignment_id,
                us.work_date,
                us.status,
                us.notes,
                s.id AS shift_id,
                s.name AS shift_name,
                s.icon AS shift_icon,
                s.color AS shift_color,
                s.start_time,
                s.end_time,
                d.id AS department_id,
                d.name AS department_name,
                u.id AS user_id,
                CONCAT(u.first_name, " ", u.last_name) AS user_name
         FROM user_shifts us
         INNER JOIN shifts s ON s.id = us.shift_id
         INNER JOIN departments d ON d.id = s.department_id
         LEFT JOIN users u ON u.id = us.user_id
         WHERE d.company_id = :company_id
         ORDER BY us.work_date ASC, s.start_time ASC, us.id ASC'
    );
    $calendarStatement->execute(['company_id' => $companyId]);
    $dashboardCalendarEvents = $calendarStatement->fetchAll();
    $dashboardPlannerData['assignments'] = $dashboardCalendarEvents;
}

if ($role === 'department_manager' && $departmentId !== null) {
    $dashboardCalendarScopeLabel = trim((string) (($profile['department_name'] ?? 'Department') . ' calendar'));
    $departmentRows = $departmentModel->byCompanyId($companyId ?? 0);
    $teamRows = $userModel->teamByDepartmentId($departmentId);
    $shiftSelect = 'id, department_id, name, icon, color, start_time, end_time';

    $shiftStatement = $pdo->prepare(
        'SELECT ' . $shiftSelect . ' FROM shifts WHERE department_id = :department_id ORDER BY start_time ASC, id ASC'
    );
    $shiftStatement->execute(['department_id' => $departmentId]);
    $shiftRows = $shiftStatement->fetchAll();
    $teamGrouped = [$departmentId => $teamRows];
    $shiftGrouped = [$departmentId => $shiftRows];
    $dashboardPlannerData['departments'] = array_map(
        static function (array $departmentRow) use ($teamGrouped, $shiftGrouped): array {
            $deptId = (int) $departmentRow['id'];

            return [
                'id' => $deptId,
                'company_id' => (int) ($departmentRow['company_id'] ?? 0),
                'name' => $departmentRow['name'] ?? '',
                'description' => $departmentRow['description'] ?? '',
                'head_user_id' => (int) ($departmentRow['head_user_id'] ?? 0),
                'head_user_name' => $departmentRow['head_user_name'] ?? '',
                'users' => $teamGrouped[$deptId] ?? [],
                'shifts' => $shiftGrouped[$deptId] ?? [],
            ];
        },
        $departmentRows
    );
    $dashboardPlannerData['users'] = $teamRows;
    $dashboardPlannerData['shifts'] = $shiftRows;
    $dashboardPlannerData['companies'] = array_values(array_filter(
        $companyModel->all(),
        static fn (array $company): bool => (int) ($company['id'] ?? 0) === (int) ($companyId ?? 0)
    ));
    $dashboardPlannerData['active_department_id'] = $departmentId;
    $dashboardPlannerData['active_shift_id'] = $shiftRows[0]['id'] ?? null;
    $calendarStatement = $pdo->prepare(
        'SELECT us.id AS assignment_id,
                us.work_date,
                us.status,
                us.notes,
                s.id AS shift_id,
                s.name AS shift_name,
                s.icon AS shift_icon,
                s.color AS shift_color,
                s.start_time,
                s.end_time,
                d.id AS department_id,
                d.name AS department_name,
                u.id AS user_id,
                CONCAT(u.first_name, " ", u.last_name) AS user_name
         FROM user_shifts us
         INNER JOIN shifts s ON s.id = us.shift_id
         INNER JOIN departments d ON d.id = s.department_id
         LEFT JOIN users u ON u.id = us.user_id
         WHERE d.id = :department_id
         ORDER BY us.work_date ASC, s.start_time ASC, us.id ASC'
    );
    $calendarStatement->execute(['department_id' => $departmentId]);
    $dashboardCalendarEvents = $calendarStatement->fetchAll();
    $dashboardPlannerData['assignments'] = $dashboardCalendarEvents;
}

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
    'documents' => [],
    'messages' => [],
    'team' => [],
    'notifications' => [],
    'shifts' => [],
    'requests' => [],
    'attendances' => [],
];

$modalCompanies = [];
$modalUsers = [];
$modalDepartments = [];
$modalDocuments = [];
$modalMessages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dashboardAction = $_POST['dashboard_action'] ?? '';

    if ($dashboardAction === 'create_message') {
        $messageKind = trim((string) ($_POST['message_kind'] ?? 'request'));
        $requestType = trim((string) ($_POST['request_type'] ?? 'leave'));
        $messageTitle = trim((string) ($_POST['message_title'] ?? ''));
        $messageBody = trim((string) ($_POST['message_body'] ?? ''));
        $documentId = ($_POST['document_id'] ?? '') !== '' ? (int) $_POST['document_id'] : null;
        $recipientIds = $_POST['recipient_ids'] ?? [];
        $recipientIds = array_values(array_filter(array_map('intval', is_array($recipientIds) ? $recipientIds : [$recipientIds])));

        if ($messageTitle === '' || $messageBody === '' || empty($recipientIds)) {
            setFlash('error', 'Message title, body and at least one recipient are required.');
        } else {
            $messageType = $messageKind === 'notification' ? 'notification' : $requestType;
            $insertStatement = $pdo->prepare(
                'INSERT INTO requests (user_id, recipient_id, type, title, message, status, document_id)
                 VALUES (:user_id, :recipient_id, :type, :title, :message, :status, :document_id)'
            );

            $status = $messageType === 'notification' ? 'unread' : 'pending';
            foreach ($recipientIds as $recipientId) {
                $insertStatement->execute([
                    'user_id' => (int) $currentUser['id'],
                    'recipient_id' => $recipientId,
                    'type' => $messageType,
                    'title' => $messageTitle,
                    'message' => $messageBody,
                    'status' => $status,
                    'document_id' => $documentId,
                ]);
            }

            setFlash('success', 'Message sent successfully.');
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
    $modalDocuments = $pdo->query(
        'SELECT d.id, d.user_id, d.document_type, d.file_name, d.file_path, d.status, d.upload_date,
                u.first_name, u.last_name, dep.company_id, dep.name AS department_name
         FROM documents d
         INNER JOIN users u ON u.id = d.user_id
         LEFT JOIN departments dep ON dep.id = u.department_id
         ORDER BY d.upload_date DESC, d.id DESC'
    )->fetchAll();
    $modalMessages = $pdo->query(
        'SELECT r.id, r.recipient_id, r.document_id, r.type, r.title, r.message, r.status, r.created_at,
                CONCAT(u.first_name, " ", u.last_name) AS sender_name,
                CONCAT(ru.first_name, " ", ru.last_name) AS recipient_name,
                doc.file_name AS document_name
         FROM requests r
         INNER JOIN users u ON u.id = r.user_id
         LEFT JOIN users ru ON ru.id = r.recipient_id
         LEFT JOIN documents doc ON doc.id = r.document_id
         ORDER BY r.created_at DESC, r.id DESC'
    )->fetchAll();
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
    $modalDocuments = $pdo->prepare(
        'SELECT d.id, d.user_id, d.document_type, d.file_name, d.file_path, d.status, d.upload_date,
                u.first_name, u.last_name, dep.company_id, dep.name AS department_name
         FROM documents d
         INNER JOIN users u ON u.id = d.user_id
         LEFT JOIN departments dep ON dep.id = u.department_id
         WHERE dep.company_id = :company_id
         ORDER BY d.upload_date DESC, d.id DESC'
    );
    $modalDocuments->execute(['company_id' => $companyId]);
    $modalDocuments = $modalDocuments->fetchAll();
    $modalMessages = $pdo->prepare(
        'SELECT r.id, r.recipient_id, r.document_id, r.type, r.title, r.message, r.status, r.created_at,
                CONCAT(u.first_name, " ", u.last_name) AS sender_name,
                CONCAT(ru.first_name, " ", ru.last_name) AS recipient_name,
                doc.file_name AS document_name
         FROM requests r
         INNER JOIN users u ON u.id = r.user_id
         LEFT JOIN users ru ON ru.id = r.recipient_id
         LEFT JOIN documents doc ON doc.id = r.document_id
         LEFT JOIN departments dep ON dep.id = u.department_id
         WHERE dep.company_id = :company_id_sender
            OR EXISTS (SELECT 1 FROM users rx LEFT JOIN departments dx ON dx.id = rx.department_id WHERE rx.id = r.recipient_id AND dx.company_id = :company_id_recipient)
         ORDER BY r.created_at DESC, r.id DESC'
    );
    $modalMessages->execute([
        'company_id_sender' => $companyId,
        'company_id_recipient' => $companyId,
    ]);
    $modalMessages = $modalMessages->fetchAll();
}

if ($role === 'department_manager' && $departmentId !== null) {
    $modalUsers = $userModel->teamByDepartmentId($departmentId);
    $modalDepartments = $departmentModel->byCompanyId($companyId ?? 0);
    $modalDocuments = $pdo->prepare(
        'SELECT d.id, d.user_id, d.document_type, d.file_name, d.file_path, d.status, d.upload_date,
                u.first_name, u.last_name, dep.company_id, dep.name AS department_name
         FROM documents d
         INNER JOIN users u ON u.id = d.user_id
         LEFT JOIN departments dep ON dep.id = u.department_id
         WHERE u.department_id = :department_id
         ORDER BY d.upload_date DESC, d.id DESC'
    );
    $modalDocuments->execute(['department_id' => $departmentId]);
    $modalDocuments = $modalDocuments->fetchAll();
    $modalMessages = $pdo->prepare(
        'SELECT r.id, r.recipient_id, r.document_id, r.type, r.title, r.message, r.status, r.created_at,
                CONCAT(u.first_name, " ", u.last_name) AS sender_name,
                CONCAT(ru.first_name, " ", ru.last_name) AS recipient_name,
                doc.file_name AS document_name
         FROM requests r
         INNER JOIN users u ON u.id = r.user_id
         LEFT JOIN users ru ON ru.id = r.recipient_id
         LEFT JOIN documents doc ON doc.id = r.document_id
         WHERE u.department_id = :department_id_sender
            OR EXISTS (SELECT 1 FROM users rx WHERE rx.id = r.recipient_id AND rx.department_id = :department_id_recipient)
         ORDER BY r.created_at DESC, r.id DESC'
    );
    $modalMessages->execute([
        'department_id_sender' => $departmentId,
        'department_id_recipient' => $departmentId,
    ]);
    $modalMessages = $modalMessages->fetchAll();
}

if ($role === 'employee') {
    $modalDocuments = $pdo->prepare(
        'SELECT d.id, d.user_id, d.document_type, d.file_name, d.file_path, d.status, d.upload_date,
                u.first_name, u.last_name, dep.company_id, dep.name AS department_name
         FROM documents d
         INNER JOIN users u ON u.id = d.user_id
         LEFT JOIN departments dep ON dep.id = u.department_id
         WHERE u.id = :user_id
         ORDER BY d.upload_date DESC, d.id DESC'
    );
    $modalDocuments->execute(['user_id' => $currentUser['id']]);
    $modalDocuments = $modalDocuments->fetchAll();
    $modalMessages = $pdo->prepare(
        'SELECT r.id, r.recipient_id, r.document_id, r.type, r.title, r.message, r.status, r.created_at,
                CONCAT(u.first_name, " ", u.last_name) AS sender_name,
                CONCAT(ru.first_name, " ", ru.last_name) AS recipient_name,
                doc.file_name AS document_name
         FROM requests r
         INNER JOIN users u ON u.id = r.user_id
         LEFT JOIN users ru ON ru.id = r.recipient_id
         LEFT JOIN documents doc ON doc.id = r.document_id
         WHERE r.user_id = :user_id OR r.recipient_id = :user_id
         ORDER BY r.created_at DESC, r.id DESC'
    );
    $modalMessages->execute(['user_id' => $currentUser['id']]);
    $modalMessages = $modalMessages->fetchAll();
}

$dashboardModalCompanies = $modalCompanies;
$dashboardModalUsers = $modalUsers;
$dashboardModalDepartments = $modalDepartments;
$dashboardModalDocuments = $modalDocuments;
$dashboardModalMessages = $modalMessages;
$moduleRows['notifications'] = $userModel->userNotifications((int) $currentUser['id']);
