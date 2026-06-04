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

if (in_array($action, ['assign_shift', 'move_shift', 'unassign_shift', 'auto_assign_open', 'clear_assignments_scope', 'record_attendance_signature', 'update_attendance', 'cancel_attendance'], true)) {
    $allowedRoles = in_array($action, ['record_attendance_signature', 'update_attendance', 'cancel_attendance'], true)
        ? ['super_admin', 'admin', 'department_manager']
        : ['admin', 'department_manager'];
    if (!in_array($role, $allowedRoles, true)) {
        jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
    }

    $assignmentId = (int) ($input['assignment_id'] ?? 0);
    $userId = (int) ($input['user_id'] ?? 0);
    $shiftId = (int) ($input['shift_id'] ?? 0);
    $workDate = trim((string) ($input['work_date'] ?? ''));
    $status = trim((string) ($input['status'] ?? 'assigned'));
    $forceOverride = !empty($input['force_override']);

    $attendanceScopeWhere = '1=1';
    $attendanceScopeParams = [];
    if ($role === 'department_manager') {
        $attendanceScopeWhere = 'd.id = :department_id';
        $attendanceScopeParams['department_id'] = (int) ($profile['department_id'] ?? 0);
    } elseif ($role === 'admin') {
        $attendanceScopeWhere = 'd.company_id = :company_id';
        $attendanceScopeParams['company_id'] = (int) ($profile['company_id'] ?? 0);
    }

    $normalizeTimeOrNull = static function ($rawValue): ?string {
        $value = trim((string) $rawValue);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return null;
    };

    if ($action === 'update_attendance' || $action === 'cancel_attendance') {
        $attendanceId = (int) ($input['attendance_id'] ?? 0);
        if ($attendanceId <= 0) {
            jsonResponse(['success' => false, 'error' => 'attendance_id is required'], 400);
        }

        $attendanceLookup = $pdo->prepare(
            'SELECT a.id, a.user_id, a.user_shift_id, d.id AS department_id, d.company_id
             FROM attendances a
             LEFT JOIN user_shifts us ON us.id = a.user_shift_id
             LEFT JOIN shifts s ON s.id = us.shift_id
             LEFT JOIN departments d ON d.id = s.department_id
             WHERE a.id = :attendance_id
               AND ' . $attendanceScopeWhere . '
             LIMIT 1'
        );
        $attendanceLookup->execute(array_merge([
            'attendance_id' => $attendanceId,
        ], $attendanceScopeParams));
        $attendanceRow = $attendanceLookup->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$attendanceRow) {
            jsonResponse(['success' => false, 'error' => 'Attendance not found or out of scope'], 404);
        }

        if ($action === 'cancel_attendance') {
            $deleteAttendance = $pdo->prepare('DELETE FROM attendances WHERE id = :attendance_id LIMIT 1');
            $deleteAttendance->execute(['attendance_id' => $attendanceId]);

            jsonResponse([
                'success' => true,
                'ok' => true,
                'attendance_id' => $attendanceId,
            ]);
        }

        $attendanceStatus = trim((string) ($input['attendance_status'] ?? 'present'));
        if (!in_array($attendanceStatus, ['present', 'absent', 'late', 'early_departure'], true)) {
            jsonResponse(['success' => false, 'error' => 'Invalid attendance status'], 400);
        }

        $checkInTime = $normalizeTimeOrNull($input['check_in_time'] ?? '');
        $checkOutTime = $normalizeTimeOrNull($input['check_out_time'] ?? '');

        $updateAttendance = $pdo->prepare(
            'UPDATE attendances
             SET status = :status,
                 check_in_time = :check_in_time,
                 check_out_time = :check_out_time,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :attendance_id'
        );
        $updateAttendance->execute([
            'status' => $attendanceStatus,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'attendance_id' => $attendanceId,
        ]);

        jsonResponse([
            'success' => true,
            'ok' => true,
            'attendance_id' => $attendanceId,
            'status' => $attendanceStatus,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
        ]);
    }

    if ($action === 'record_attendance_signature') {
        $currentAppNow = appNow();
        $currentAppTime = $currentAppNow->format('H:i:s');
        $targetUserId = (int) ($input['user_id'] ?? 0);
        $targetUserShiftId = (int) ($input['user_shift_id'] ?? 0);
        $signatureData = trim((string) ($input['signature_data'] ?? ''));
        $attendanceStatus = trim((string) ($input['attendance_status'] ?? 'present'));
        if (!in_array($attendanceStatus, ['present', 'absent', 'late', 'early_departure'], true)) {
            $attendanceStatus = 'present';
        }

        if ($targetUserId <= 0 || $targetUserShiftId <= 0 || $signatureData === '') {
            jsonResponse(['success' => false, 'error' => 'user_id, user_shift_id and signature_data are required'], 400);
        }

        $assignmentLookup = $pdo->prepare(
            'SELECT us.id, us.user_id, us.work_date, us.shift_id, s.start_time, d.id AS department_id
             FROM user_shifts us
             INNER JOIN shifts s ON s.id = us.shift_id
             INNER JOIN departments d ON d.id = s.department_id
             WHERE us.id = :user_shift_id
               AND us.user_id = :user_id
               AND ' . $attendanceScopeWhere . '
             LIMIT 1'
        );
        $assignmentLookup->execute(array_merge([
            'user_shift_id' => $targetUserShiftId,
            'user_id' => $targetUserId,
        ], $attendanceScopeParams));
        $assignment = $assignmentLookup->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$assignment) {
            jsonResponse(['success' => false, 'error' => 'Assignment not found or out of scope'], 404);
        }

        $workDate = (string) ($assignment['work_date'] ?? '');
        if ($workDate === '' || $workDate > date('Y-m-d')) {
            jsonResponse(['success' => false, 'error' => 'Attendance cannot be recorded for future dates'], 400);
        }

        $shiftStartTime = trim((string) ($assignment['start_time'] ?? ''));
        if ($attendanceStatus === 'present' && $workDate === $currentAppNow->format('Y-m-d') && $shiftStartTime !== '') {
            try {
                $shiftStartAt = new DateTimeImmutable($workDate . ' ' . $shiftStartTime, appTimezone());
                if ($currentAppNow > $shiftStartAt) {
                    $attendanceStatus = 'late';
                }
            } catch (Throwable $e) {
                // Keep requested status when shift time cannot be parsed.
            }
        }

        $insertSignature = $pdo->prepare(
            'INSERT INTO digital_signatures (user_id, signature_type, signature_data)
             VALUES (:user_id, :signature_type, :signature_data)'
        );
        $insertSignature->execute([
            'user_id' => $targetUserId,
            'signature_type' => 'touchscreen',
            'signature_data' => $signatureData,
        ]);
        $digitalSignatureId = (int) $pdo->lastInsertId();

        $attendanceLookup = $pdo->prepare(
            'SELECT id
             FROM attendances
             WHERE user_id = :user_id
               AND user_shift_id = :user_shift_id
               AND work_date = :work_date
             LIMIT 1'
        );
        $attendanceLookup->execute([
            'user_id' => $targetUserId,
            'user_shift_id' => $targetUserShiftId,
            'work_date' => $workDate,
        ]);
        $attendanceId = (int) ($attendanceLookup->fetchColumn() ?: 0);

        if ($attendanceId > 0) {
            $updateAttendance = $pdo->prepare(
                'UPDATE attendances
                 SET status = :status,
                     digital_signature_id = :digital_signature_id,
                     check_in_time = COALESCE(check_in_time, :check_in_time),
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $updateAttendance->execute([
                'status' => $attendanceStatus,
                'digital_signature_id' => $digitalSignatureId,
                'check_in_time' => $currentAppTime,
                'id' => $attendanceId,
            ]);
        } else {
            $insertAttendance = $pdo->prepare(
                'INSERT INTO attendances (user_id, user_shift_id, digital_signature_id, work_date, check_in_time, status)
                 VALUES (:user_id, :user_shift_id, :digital_signature_id, :work_date, :check_in_time, :status)'
            );
            $insertAttendance->execute([
                'user_id' => $targetUserId,
                'user_shift_id' => $targetUserShiftId,
                'digital_signature_id' => $digitalSignatureId,
                'work_date' => $workDate,
                'check_in_time' => $currentAppTime,
                'status' => $attendanceStatus,
            ]);
            $attendanceId = (int) $pdo->lastInsertId();
        }

        jsonResponse([
            'success' => true,
            'ok' => true,
            'attendance_id' => $attendanceId,
            'digital_signature_id' => $digitalSignatureId,
            'work_date' => $workDate,
            'status' => $attendanceStatus,
        ]);
    }

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

        return $check->fetchColumn() ? 'Employee already has an assigned shift on this date.' : null;
    };

    $isPastWorkDate = static function (string $date): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        return $date < date('Y-m-d');
    };

    if ($action === 'auto_assign_open' || $action === 'clear_assignments_scope') {
        $scopeShiftId = max(0, (int) ($input['scope_shift_id'] ?? 0));
        $rangeStart = trim((string) ($input['range_start'] ?? date('Y-m-01')));
        $rangeEnd = trim((string) ($input['range_end'] ?? date('Y-m-t')));
        $currentMonthStart = date('Y-m-01');
        $rangeStart = max($rangeStart, $currentMonthStart);
        $rangeEnd = max($rangeStart, $rangeEnd);

        if ($action === 'clear_assignments_scope') {
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

            $clearShiftFilter = $scopeShiftId > 0 ? ' AND s.id = :scope_shift_id' : '';
            if ($scopeShiftId > 0) {
                $scopeParams['scope_shift_id'] = $scopeShiftId;
            }

            $clearStmt = $pdo->prepare(
                'UPDATE user_shifts us
                 INNER JOIN shifts s ON s.id = us.shift_id
                 INNER JOIN departments d ON d.id = s.department_id
                 SET us.user_id = NULL,
                     us.status = "open",
                     us.updated_at = CURRENT_TIMESTAMP
                 WHERE ' . $scopeWhere . '
                   AND us.work_date BETWEEN :range_start AND :range_end
                   AND us.user_id IS NOT NULL
                   AND us.status <> "cancelled"
                   AND s.kind = "work"' . $clearShiftFilter
            );
            $clearStmt->execute($scopeParams);

            jsonResponse([
                'success' => true,
                'ok' => true,
                'cleared_count' => (int) $clearStmt->rowCount(),
            ]);
        }

        $minEmployeesPerShiftDay = max(0, (int) ($input['min_employees_per_shift_day'] ?? 1));
        $maxEmployeesPerShiftDay = max(1, (int) ($input['max_employees_per_shift_day'] ?? 3));
        if ($minEmployeesPerShiftDay > $maxEmployeesPerShiftDay) {
            $minEmployeesPerShiftDay = $maxEmployeesPerShiftDay;
        }
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
            $dayBusy[$uid][$date] = true;
        }

        $coverageParams = $scopeParams;
        $coverageStmt = $pdo->prepare(
            'SELECT us.shift_id, us.work_date,
                    SUM(CASE WHEN us.user_id IS NOT NULL AND us.status <> "cancelled" THEN 1 ELSE 0 END) AS assigned_count
             FROM user_shifts us
             INNER JOIN shifts s ON s.id = us.shift_id
             INNER JOIN departments d ON d.id = s.department_id
             WHERE ' . $scopeWhere . '
               AND us.work_date BETWEEN :range_start AND :range_end
               AND s.kind = "work"' . $openShiftFilter . '
             GROUP BY us.shift_id, us.work_date'
        );
        $coverageStmt->execute($coverageParams);
        $coverageByShiftDate = [];
        foreach ($coverageStmt->fetchAll(PDO::FETCH_ASSOC) as $coverageRow) {
            $coverageKey = ((int) ($coverageRow['shift_id'] ?? 0)) . '|' . (string) ($coverageRow['work_date'] ?? '');
            $coverageByShiftDate[$coverageKey] = (int) ($coverageRow['assigned_count'] ?? 0);
        }

        $openRowsByShiftDate = [];
        foreach ($openRows as $openRow) {
            $groupKey = ((int) ($openRow['shift_id'] ?? 0)) . '|' . (string) ($openRow['work_date'] ?? '');
            if (!isset($openRowsByShiftDate[$groupKey])) {
                $openRowsByShiftDate[$groupKey] = [];
            }
            $openRowsByShiftDate[$groupKey][] = $openRow;
            if (!array_key_exists($groupKey, $coverageByShiftDate)) {
                $coverageByShiftDate[$groupKey] = 0;
            }
        }

        $groupOrder = array_keys($openRowsByShiftDate);
        usort($groupOrder, static function (string $a, string $b) use ($coverageByShiftDate, $minEmployeesPerShiftDay): int {
            [$aShift, $aDate] = array_pad(explode('|', $a, 2), 2, '');
            [$bShift, $bDate] = array_pad(explode('|', $b, 2), 2, '');
            $aAssigned = (int) ($coverageByShiftDate[$a] ?? 0);
            $bAssigned = (int) ($coverageByShiftDate[$b] ?? 0);
            $aDeficit = max($minEmployeesPerShiftDay - $aAssigned, 0);
            $bDeficit = max($minEmployeesPerShiftDay - $bAssigned, 0);
            if ($aDeficit !== $bDeficit) {
                return $bDeficit <=> $aDeficit;
            }
            if ($aDate !== $bDate) {
                return $aDate <=> $bDate;
            }

            return ((int) $aShift) <=> ((int) $bShift);
        });

        $updateAssignment = $pdo->prepare(
            'UPDATE user_shifts SET user_id = :user_id, status = "assigned", updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $assignedCount = 0;
        $skippedByRules = 0;
        foreach ($groupOrder as $groupKey) {
            $assignedInGroup = (int) ($coverageByShiftDate[$groupKey] ?? 0);
            if ($assignedInGroup >= $maxEmployeesPerShiftDay) {
                continue;
            }

            foreach ($openRowsByShiftDate[$groupKey] as $openRow) {
                if ($assignedInGroup >= $maxEmployeesPerShiftDay) {
                    break;
                }

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
                    if ((int) ($candidateUser['department_id'] ?? 0) !== $slotDepartmentId) {
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
                    $dayBusy[$candidate][$slotDate] = true;
                    $assignedInGroup++;
                    $coverageByShiftDate[$groupKey] = $assignedInGroup;
                    $assignedCount++;
                }
            }
        }

        $groupsBelowMin = 0;
        foreach ($coverageByShiftDate as $assignedInGroup) {
            if ((int) $assignedInGroup < $minEmployeesPerShiftDay) {
                $groupsBelowMin++;
            }
        }

        jsonResponse([
            'success' => true,
            'ok' => true,
            'assigned_count' => $assignedCount,
            'open_remaining' => max(count($openRows) - $assignedCount, 0),
            'skipped_by_rules' => $skippedByRules,
            'groups_below_min' => $groupsBelowMin,
            'min_employees_per_shift_day' => $minEmployeesPerShiftDay,
            'max_employees_per_shift_day' => $maxEmployeesPerShiftDay,
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

        $unassignLookup = $pdo->prepare('SELECT work_date FROM user_shifts WHERE id = :id LIMIT 1');
        $unassignLookup->execute(['id' => $assignmentId]);
        $unassignRow = $unassignLookup->fetch(PDO::FETCH_ASSOC) ?: [];
        $unassignDate = (string) ($unassignRow['work_date'] ?? '');
        if ($isPastWorkDate($unassignDate)) {
            jsonResponse(['success' => false, 'error' => 'Past dates are read-only and cannot be modified.'], 400);
        }

        $update = $pdo->prepare('UPDATE user_shifts SET user_id = NULL, status = "open", updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute(['id' => $assignmentId]);
        jsonResponse([
            'success' => true,
            'ok' => true,
            'assignment' => [
                'assignment_id' => $assignmentId,
                'status' => 'open',
                'user_id' => 0,
                'user_name' => '',
            ],
        ]);
    }

    if ($action !== 'unassign_shift' && ($workDate === '' || $shiftId <= 0 || ($action === 'assign_shift' && $userId <= 0))) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    if ($action !== 'unassign_shift' && $isPastWorkDate($workDate)) {
        jsonResponse(['success' => false, 'error' => 'Past dates are read-only and cannot be modified.'], 400);
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

        if ($shift && (int) ($targetUser['department_id'] ?? 0) !== (int) ($shift['department_id'] ?? 0)) {
            jsonResponse(['success' => false, 'error' => 'Employee and shift must belong to the same department'], 400);
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
        if ($conflict !== null && !$forceOverride) {
            jsonResponse(['success' => false, 'error' => $conflict], 400);
        }

        if ($conflict !== null && $forceOverride) {
            $existingByDay = $pdo->prepare(
                'SELECT id
                 FROM user_shifts
                 WHERE user_id = :user_id
                   AND work_date = :work_date
                   AND status <> "cancelled"
                 ORDER BY id ASC
                 LIMIT 1'
            );
            $existingByDay->execute([
                'user_id' => $assignmentUserId,
                'work_date' => $workDate,
            ]);
            $existingByDayId = (int) ($existingByDay->fetchColumn() ?: 0);
            if ($existingByDayId > 0) {
                $forceUpdate = $pdo->prepare(
                    'UPDATE user_shifts
                     SET shift_id = :shift_id,
                         user_id = :user_id,
                         status = :status,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $forceUpdate->execute([
                    'shift_id' => $shiftId,
                    'user_id' => $assignmentUserId,
                    'status' => $status ?: 'assigned',
                    'id' => $existingByDayId,
                ]);
                $assignmentId = $existingByDayId;
            }
        }

        if (!isset($assignmentId) || (int) $assignmentId <= 0) {

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