<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';

/**
 * API dashboard endpoint returning JSON useful for AJAX/REST clients.
 *
 * Requires an authenticated session. Returns user/profile and role based
 * stats tailored to the current user's permissions.
 */
if (!isLoggedIn()) {
    jsonResponse([
        'success' => false,
        'message' => 'Login required.',
    ], 401);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'view');

$pdo = getPDO();
$userModel = new UserModel($pdo);
$companyModel = new CompanyModel($pdo);
$departmentModel = new DepartmentModel($pdo);
$user = currentUser();
$role = $user['role'] ?? 'employee';
$profile = $userModel->profileWithRelations((int) $user['id']) ?? [];

if (in_array($action, ['assign_shift', 'move_shift'], true)) {
    if (!in_array($role, ['admin', 'department_manager'], true)) {
        jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
    }

    $assignmentId = (int) ($input['assignment_id'] ?? 0);
    $userId = (int) ($input['user_id'] ?? 0);
    $shiftId = (int) ($input['shift_id'] ?? 0);
    $workDate = trim((string) ($input['work_date'] ?? ''));
    $status = trim((string) ($input['status'] ?? 'assigned'));

    $assignmentUserId = $userId;
    if ($action === 'move_shift' && $assignmentId > 0) {
        $assignmentLookup = $pdo->prepare('SELECT user_id, shift_id FROM user_shifts WHERE id = :id LIMIT 1');
        $assignmentLookup->execute(['id' => $assignmentId]);
        $assignmentRow = $assignmentLookup->fetch(PDO::FETCH_ASSOC) ?: [];
        $assignmentUserId = (int) ($assignmentRow['user_id'] ?? 0);
        if ($shiftId <= 0) {
            $shiftId = (int) ($assignmentRow['shift_id'] ?? 0);
        }
    }

    if ($workDate === '' || $shiftId <= 0 || ($action === 'assign_shift' && $userId <= 0)) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }

    $shiftCheck = $pdo->prepare(
        'SELECT s.id, s.department_id, s.icon, s.color, d.company_id, d.name AS department_name
         FROM shifts s
         INNER JOIN departments d ON d.id = s.department_id
         WHERE s.id = :shift_id
         LIMIT 1'
    );
    $shiftCheck->execute(['shift_id' => $shiftId]);
    $shift = $shiftCheck->fetch(PDO::FETCH_ASSOC);
    if (!$shift) {
        jsonResponse(['success' => false, 'error' => 'Shift not found'], 404);
    }

    $userCheck = $pdo->prepare(
        'SELECT u.id, u.department_id, d.company_id
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.id = :id
         LIMIT 1'
    );
    if ($assignmentUserId > 0) {
        $userCheck->execute(['id' => $assignmentUserId]);
        $targetUser = $userCheck->fetch(PDO::FETCH_ASSOC);
        if (!$targetUser) {
            jsonResponse(['success' => false, 'error' => 'User not found'], 404);
        }
        if ($role === 'department_manager' && (int) $targetUser['department_id'] !== (int) ($profile['department_id'] ?? 0)) {
            jsonResponse(['success' => false, 'error' => 'Target user is outside your department'], 403);
        }
        if ($role === 'admin' && (int) $targetUser['company_id'] !== (int) ($profile['company_id'] ?? 0)) {
            jsonResponse(['success' => false, 'error' => 'Target user is outside your company'], 403);
        }
    }

    if ($role === 'department_manager' && (int) $shift['department_id'] !== (int) ($profile['department_id'] ?? 0)) {
        jsonResponse(['success' => false, 'error' => 'Shift is outside your department'], 403);
    }
    if ($role === 'admin' && (int) $shift['company_id'] !== (int) ($profile['company_id'] ?? 0)) {
        jsonResponse(['success' => false, 'error' => 'Shift is outside your company'], 403);
    }

    if ($action === 'assign_shift') {
        $existing = $pdo->prepare(
            'SELECT id FROM user_shifts WHERE user_id = :user_id AND shift_id = :shift_id AND work_date = :work_date LIMIT 1'
        );
        $existing->execute([
            'user_id' => $assignmentUserId,
            'shift_id' => $shiftId,
            'work_date' => $workDate,
        ]);
        $existingId = (int) ($existing->fetchColumn() ?: 0);
        if ($existingId > 0) {
            $update = $pdo->prepare('UPDATE user_shifts SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute(['status' => $status, 'id' => $existingId]);
            $assignmentId = $existingId;
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO user_shifts (shift_id, user_id, work_date, status)
                 VALUES (:shift_id, :user_id, :work_date, :status)'
            );
            $insert->execute([
                'shift_id' => $shiftId,
                'user_id' => $assignmentUserId,
                'work_date' => $workDate,
                'status' => $status,
            ]);
            $assignmentId = (int) $pdo->lastInsertId();
        }
    } else {
        if ($assignmentId <= 0) {
            jsonResponse(['success' => false, 'error' => 'assignment_id is required'], 400);
        }
        $update = $pdo->prepare('UPDATE user_shifts SET shift_id = :shift_id, work_date = :work_date, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute([
            'shift_id' => $shiftId,
            'work_date' => $workDate,
            'status' => $status ?: 'assigned',
            'id' => $assignmentId,
        ]);
    }

    $assignmentLookup = $pdo->prepare(
        'SELECT us.id AS assignment_id, us.work_date, us.status, us.notes,
                s.id AS shift_id, s.name AS shift_name, s.icon AS shift_icon, s.color AS shift_color, s.start_time, s.end_time,
                d.id AS department_id, d.name AS department_name,
                u.id AS user_id, CONCAT(u.first_name, " ", u.last_name) AS user_name
         FROM user_shifts us
         INNER JOIN shifts s ON s.id = us.shift_id
         INNER JOIN departments d ON d.id = s.department_id
         LEFT JOIN users u ON u.id = us.user_id
         WHERE us.id = :id
         LIMIT 1'
    );
    $assignmentLookup->execute(['id' => $assignmentId]);
    $assignment = $assignmentLookup->fetch(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'ok' => true, 'assignment' => $assignment]);
}

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