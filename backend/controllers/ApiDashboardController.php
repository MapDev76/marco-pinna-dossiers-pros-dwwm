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
ensureSchedulerSchema($pdo);
$userModel = new UserModel($pdo);
$companyModel = new CompanyModel($pdo);
$departmentModel = new DepartmentModel($pdo);
$user = currentUser();
$role = $user['role'] ?? 'employee';
$profile = $userModel->profileWithRelations((int) $user['id']) ?? [];

if (in_array($action, ['assign_shift', 'move_shift', 'unassign_shift', 'auto_assign_open'], true)) {
    if (!in_array($role, ['admin', 'department_manager'], true)) {
        jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
    }

    $assignmentId = (int) ($input['assignment_id'] ?? 0);
    $userId = (int) ($input['user_id'] ?? 0);
    $shiftId = (int) ($input['shift_id'] ?? 0);
    $workDate = trim((string) ($input['work_date'] ?? ''));
    $status = trim((string) ($input['status'] ?? 'assigned'));

    $validateSingleShiftPerDay = static function (PDO $pdo, int $targetUserId, string $targetDate, int $excludeAssignmentId = 0): ?string {
        if ($targetUserId <= 0 || $targetDate === '') {
            return null;
        }
        $check = $pdo->prepare(
            'SELECT id FROM user_shifts
             WHERE user_id = :user_id
               AND work_date = :work_date
               AND id <> :exclude_id
               AND status <> "cancelled"
             LIMIT 1'
        );
        $check->execute([
            'user_id' => $targetUserId,
            'work_date' => $targetDate,
            'exclude_id' => $excludeAssignmentId,
        ]);

        return $check->fetchColumn() ? 'Employee already has a shift for this day.' : null;
    };

    if ($action === 'auto_assign_open') {
        $scopeShiftId = max(0, (int) ($input['scope_shift_id'] ?? 0));
        $maxHoursPerMonth = max(1, (int) ($input['max_hours_per_month'] ?? 176));
        $maxDaysPerMonth = max(1, (int) ($input['max_days_per_month'] ?? 22));
        $rangeStart = trim((string) ($input['range_start'] ?? date('Y-m-01')));
        $rangeEnd = trim((string) ($input['range_end'] ?? date('Y-m-t')));
        $employeeRulesRaw = $input['employee_rules'] ?? [];

        if (is_string($employeeRulesRaw)) {
            $decodedRules = json_decode($employeeRulesRaw, true);
            $employeeRulesRaw = is_array($decodedRules) ? $decodedRules : [];
        }

        $employeeRules = [];
        if (is_array($employeeRulesRaw)) {
            foreach ($employeeRulesRaw as $rawUserId => $rawRule) {
                $normalizedUserId = (int) $rawUserId;
                if ($normalizedUserId <= 0 || !is_array($rawRule)) {
                    continue;
                }

                $scope = (string) ($rawRule['scope'] ?? 'all');
                if (!in_array($scope, ['all', 'current', 'next'], true)) {
                    $scope = 'all';
                }

                $offWeekdays = [];
                if (is_array($rawRule['off_weekdays'] ?? null)) {
                    foreach ($rawRule['off_weekdays'] as $weekday) {
                        $weekdayInt = (int) $weekday;
                        if ($weekdayInt >= 0 && $weekdayInt <= 6) {
                            $offWeekdays[$weekdayInt] = true;
                        }
                    }
                }

                $specialDates = [];
                if (is_array($rawRule['special_dates'] ?? null)) {
                    foreach ($rawRule['special_dates'] as $specialDate) {
                        if (!is_array($specialDate)) {
                            continue;
                        }
                        $dateValue = trim((string) ($specialDate['date'] ?? ''));
                        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                            continue;
                        }
                        $specialDates[$dateValue] = true;
                    }
                }

                $employeeRules[$normalizedUserId] = [
                    'scope' => $scope,
                    'off_weekdays' => $offWeekdays,
                    'special_dates' => $specialDates,
                ];
            }
        }

        $currentMonth = date('Y-m');
        $nextMonth = date('Y-m', strtotime('first day of next month'));

        $isBlockedByRule = static function (int $userId, string $slotDate) use ($employeeRules, $currentMonth, $nextMonth): bool {
            if ($userId <= 0 || $slotDate === '' || empty($employeeRules[$userId])) {
                return false;
            }

            $rule = $employeeRules[$userId];
            $slotMonth = substr($slotDate, 0, 7);
            $scope = (string) ($rule['scope'] ?? 'all');
            if ($scope === 'current' && $slotMonth !== $currentMonth) {
                return false;
            }
            if ($scope === 'next' && $slotMonth < $nextMonth) {
                return false;
            }

            if (!empty($rule['special_dates'][$slotDate])) {
                return true;
            }

            $weekday = (int) date('w', strtotime($slotDate));
            if (!empty($rule['off_weekdays'][$weekday])) {
                return true;
            }

            return false;
        };

        $scopeWhere = '1=1';
        $scopeParams = [
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
        ];
        if ($role === 'department_manager') {
            $scopeWhere = 'd.id = :department_id';
            $scopeParams['department_id'] = (int) ($profile['department_id'] ?? 0);
        } elseif ($role === 'admin') {
            $scopeWhere = 'd.company_id = :company_id';
            $scopeParams['company_id'] = (int) ($profile['company_id'] ?? 0);
        }

        $openShiftFilter = $scopeShiftId > 0 ? ' AND s.id = :scope_shift_id' : '';

        $openStmt = $pdo->prepare(
            'SELECT us.id, us.work_date, s.id AS shift_id, s.start_time, s.end_time, s.kind AS shift_kind, d.id AS department_id
             FROM user_shifts us
             INNER JOIN shifts s ON s.id = us.shift_id
             INNER JOIN departments d ON d.id = s.department_id
             WHERE ' . $scopeWhere . ' AND us.work_date BETWEEN :range_start AND :range_end AND us.user_id IS NULL AND us.status = "open" AND s.kind = "work"' . $openShiftFilter . '
             ORDER BY us.work_date ASC, s.start_time ASC, us.id ASC'
        );
        if ($scopeShiftId > 0) {
            $scopeParams['scope_shift_id'] = $scopeShiftId;
        }
        $openStmt->execute($scopeParams);
        $openRows = $openStmt->fetchAll(PDO::FETCH_ASSOC);

        $scopeOnlyParams = $scopeParams;
        unset($scopeOnlyParams['range_start'], $scopeOnlyParams['range_end']);
        $userStmt = $pdo->prepare(
            'SELECT u.id, u.department_id
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.status = "active" AND ' . $scopeWhere . '
             ORDER BY u.id ASC'
        );
        $userStmt->execute($scopeOnlyParams);
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        $hoursByUserMonth = [];
        $daysByUserMonth = [];
        $dayBusy = [];
        $snapshot = $pdo->prepare(
            'SELECT us.user_id, us.work_date, s.start_time, s.end_time
             FROM user_shifts us
             INNER JOIN shifts s ON s.id = us.shift_id
             WHERE us.user_id IS NOT NULL AND us.work_date BETWEEN :range_start AND :range_end AND us.status <> "cancelled"'
        );
        $snapshot->execute([
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
        ]);
        foreach ($snapshot->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $uid = (int) ($row['user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $date = (string) ($row['work_date'] ?? '');
            $month = substr($date, 0, 7);
            $startParts = explode(':', (string) ($row['start_time'] ?? '00:00:00'));
            $endParts = explode(':', (string) ($row['end_time'] ?? '00:00:00'));
            $startMinutes = ((int) ($startParts[0] ?? 0) * 60) + (int) ($startParts[1] ?? 0);
            $endMinutes = ((int) ($endParts[0] ?? 0) * 60) + (int) ($endParts[1] ?? 0);
            $delta = $endMinutes - $startMinutes;
            if ($delta <= 0) {
                $delta += 24 * 60;
            }
            $hoursByUserMonth[$uid][$month] = ($hoursByUserMonth[$uid][$month] ?? 0.0) + ($delta / 60);
            $daysByUserMonth[$uid][$month][$date] = true;
            $dayBusy[$uid][$date] = true;
        }

        $updateAssignment = $pdo->prepare(
            'UPDATE user_shifts SET user_id = :user_id, status = "assigned", updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $assignedCount = 0;
        $skippedByRules = 0;
        foreach ($openRows as $openRow) {
            $slotDate = (string) ($openRow['work_date'] ?? '');
            $slotDepartmentId = (int) ($openRow['department_id'] ?? 0);
            $slotMonth = substr($slotDate, 0, 7);
            $startParts = explode(':', (string) ($openRow['start_time'] ?? '00:00:00'));
            $endParts = explode(':', (string) ($openRow['end_time'] ?? '00:00:00'));
            $startMinutes = ((int) ($startParts[0] ?? 0) * 60) + (int) ($startParts[1] ?? 0);
            $endMinutes = ((int) ($endParts[0] ?? 0) * 60) + (int) ($endParts[1] ?? 0);
            $delta = $endMinutes - $startMinutes;
            if ($delta <= 0) {
                $delta += 24 * 60;
            }
            $slotHours = $delta / 60;

            $candidate = null;
            $candidateHours = PHP_FLOAT_MAX;
            foreach ($users as $candidateUser) {
                $uid = (int) ($candidateUser['id'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }
                if ($role === 'department_manager' && (int) ($candidateUser['department_id'] ?? 0) !== $slotDepartmentId) {
                    continue;
                }
                if (!empty($dayBusy[$uid][$slotDate])) {
                    continue;
                }
                if ($isBlockedByRule($uid, $slotDate)) {
                    $skippedByRules++;
                    continue;
                }
                $monthHours = (float) ($hoursByUserMonth[$uid][$slotMonth] ?? 0.0);
                $monthDaysCount = count($daysByUserMonth[$uid][$slotMonth] ?? []);
                if (($monthHours + $slotHours) > $maxHoursPerMonth || ($monthDaysCount + 1) > $maxDaysPerMonth) {
                    continue;
                }
                if ($monthHours < $candidateHours) {
                    $candidate = $uid;
                    $candidateHours = $monthHours;
                }
            }

            if ($candidate) {
                $updateAssignment->execute([
                    'user_id' => $candidate,
                    'id' => (int) ($openRow['id'] ?? 0),
                ]);
                $hoursByUserMonth[$candidate][$slotMonth] = ($hoursByUserMonth[$candidate][$slotMonth] ?? 0.0) + $slotHours;
                $daysByUserMonth[$candidate][$slotMonth][$slotDate] = true;
                $dayBusy[$candidate][$slotDate] = true;
                $assignedCount++;
            }
        }

        jsonResponse([
            'success' => true,
            'ok' => true,
            'assigned_count' => $assignedCount,
            'open_remaining' => max(count($openRows) - $assignedCount, 0),
            'skipped_by_rules' => $skippedByRules,
        ]);
    }

    $assignmentUserId = $userId;
    if ($action === 'move_shift' && $assignmentId > 0) {
        $assignmentLookup = $pdo->prepare('SELECT user_id, shift_id FROM user_shifts WHERE id = :id LIMIT 1');
        $assignmentLookup->execute(['id' => $assignmentId]);
        $assignmentRow = $assignmentLookup->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($assignmentUserId <= 0 && array_key_exists('user_id', $assignmentRow)) {
            $assignmentUserId = (int) ($assignmentRow['user_id'] ?? 0);
        }
        if ($shiftId <= 0) {
            $shiftId = (int) ($assignmentRow['shift_id'] ?? 0);
        }
    }

    if ($action === 'unassign_shift') {
        if ($assignmentId <= 0) {
            jsonResponse(['success' => false, 'error' => 'assignment_id is required'], 400);
        }
        $update = $pdo->prepare('UPDATE user_shifts SET user_id = NULL, status = "open", updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute(['id' => $assignmentId]);
        $status = 'open';
    }

    if ($action !== 'unassign_shift' && ($workDate === '' || $shiftId <= 0 || ($action === 'assign_shift' && $userId <= 0))) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }

    $shiftSelect = 's.id, s.department_id, s.name, s.icon, s.color, s.kind, s.start_time, s.end_time, d.company_id, d.name AS department_name, d.color AS department_color';

    $shift = null;
    if ($shiftId > 0) {
        $shiftCheck = $pdo->prepare(
            'SELECT ' . $shiftSelect . ' FROM shifts s INNER JOIN departments d ON d.id = s.department_id WHERE s.id = :shift_id LIMIT 1'
        );
        $shiftCheck->execute(['shift_id' => $shiftId]);
        $shift = $shiftCheck->fetch(PDO::FETCH_ASSOC);
        if (!$shift) {
            jsonResponse(['success' => false, 'error' => 'Shift not found'], 404);
        }
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

    if ($shift && $role === 'department_manager' && (int) $shift['department_id'] !== (int) ($profile['department_id'] ?? 0)) {
        jsonResponse(['success' => false, 'error' => 'Shift is outside your department'], 403);
    }
    if ($shift && $role === 'admin' && (int) $shift['company_id'] !== (int) ($profile['company_id'] ?? 0)) {
        jsonResponse(['success' => false, 'error' => 'Shift is outside your company'], 403);
    }

    if ($action === 'assign_shift') {
        $conflict = $validateSingleShiftPerDay($pdo, $assignmentUserId, $workDate);
        if ($conflict !== null) {
            jsonResponse(['success' => false, 'error' => $conflict], 400);
        }

        $openExisting = $pdo->prepare(
            'SELECT id FROM user_shifts WHERE shift_id = :shift_id AND work_date = :work_date AND user_id IS NULL LIMIT 1'
        );
        $openExisting->execute([
            'shift_id' => $shiftId,
            'work_date' => $workDate,
        ]);
        $existingOpenId = (int) ($openExisting->fetchColumn() ?: 0);
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
        } elseif ($existingOpenId > 0) {
            $update = $pdo->prepare('UPDATE user_shifts SET user_id = :user_id, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute([
                'user_id' => $assignmentUserId,
                'status' => $status ?: 'assigned',
                'id' => $existingOpenId,
            ]);
            $assignmentId = $existingOpenId;
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

        if ($assignmentUserId > 0) {
            $conflict = $validateSingleShiftPerDay($pdo, $assignmentUserId, $workDate, $assignmentId);
            if ($conflict !== null) {
                jsonResponse(['success' => false, 'error' => $conflict], 400);
            }
        }

        $update = $pdo->prepare('UPDATE user_shifts SET shift_id = :shift_id, user_id = :user_id, work_date = :work_date, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute([
            'shift_id' => $shiftId,
            'user_id' => $assignmentUserId > 0 ? $assignmentUserId : null,
            'work_date' => $workDate,
            'status' => ($assignmentUserId > 0 ? ($status ?: 'assigned') : 'open'),
            'id' => $assignmentId,
        ]);
    }

    $assignmentSelect = [
        'us.id AS assignment_id',
        'us.work_date',
        'us.status',
        'us.notes',
        's.id AS shift_id',
        's.name AS shift_name',
        's.icon AS shift_icon',
        's.color AS shift_color',
        's.description AS shift_description',
        's.kind AS shift_kind',
        's.start_time',
        's.end_time',
        'd.id AS department_id',
        'd.name AS department_name',
        'd.color AS department_color',
        'u.id AS user_id',
        'CONCAT(u.first_name, " ", u.last_name) AS user_name',
        'CASE WHEN us.user_id IS NULL THEN "open" ELSE "assigned" END AS assignment_source',
    ];

    $assignmentLookup = $pdo->prepare(
        'SELECT ' . implode(', ', $assignmentSelect) . ' FROM user_shifts us INNER JOIN shifts s ON s.id = us.shift_id INNER JOIN departments d ON d.id = s.department_id LEFT JOIN users u ON u.id = us.user_id WHERE us.id = :id LIMIT 1'
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