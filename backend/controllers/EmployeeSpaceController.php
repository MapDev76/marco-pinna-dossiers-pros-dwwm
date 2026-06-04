<?php
/**
 * Employee space controller.
 *
 * Displays the personal area for users with the `employee` role. Handles
 * attendance signing and personal requests. Attendance signing can be
 * restricted by company Wi-Fi IP when configured by admin/super admin.
 */
require_once __DIR__ . '/../bootstrap.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please log in to continue.');
    redirectTo('login');
}

$currentUser = currentUser();
if (($currentUser['role'] ?? null) !== 'employee') {
    setFlash('error', 'Access restricted to employees.');
    redirectTo('dashboard');
}

$pdo = getPDO();
$pageTitle = 'My Employee Space';
$viewFile = __DIR__ . '/../../public/views/employee/space.php';
$error = null;
$todayDate = appNow()->format('Y-m-d');

$normalizeIp = static function (?string $ip): string {
    $raw = trim((string) $ip);
    if ($raw === '') {
        return '';
    }

    $normalized = strtolower($raw);
    if (str_starts_with($normalized, '::ffff:')) {
        return substr($normalized, 7);
    }

    return $normalized;
};

$detectClientIp = static function (): string {
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['HTTP_X_REAL_IP'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate);
        if ($candidate === '') {
            continue;
        }

        if (str_contains($candidate, ',')) {
            $parts = array_map('trim', explode(',', $candidate));
            $candidate = (string) ($parts[0] ?? '');
        }

        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
};

$clientIpRaw = $detectClientIp();
$clientIp = $normalizeIp($clientIpRaw);

$hasCompanySignatureIpColumn = false;
try {
    $signatureIpColumnCheck = $pdo->query("SHOW COLUMNS FROM companies LIKE 'signature_ip'");
    $hasCompanySignatureIpColumn = (bool) $signatureIpColumnCheck->fetch();
} catch (Throwable $e) {
    $hasCompanySignatureIpColumn = false;
}

$companySignatureIpSelect = $hasCompanySignatureIpColumn ? 'c.signature_ip' : 'NULL AS signature_ip';

$signaturePolicyStatement = $pdo->prepare(
    'SELECT ' . $companySignatureIpSelect . ',
            d.name AS department_name,
            c.name AS company_name
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     LEFT JOIN companies c ON c.id = d.company_id
     WHERE u.id = :user_id
     LIMIT 1'
);
$signaturePolicyStatement->execute(['user_id' => (int) $currentUser['id']]);
$profileRow = $signaturePolicyStatement->fetch(PDO::FETCH_ASSOC) ?: [];
$requiredSignatureIpRaw = (string) ($profileRow['signature_ip'] ?? '');
$requiredSignatureIp = $normalizeIp($requiredSignatureIpRaw);
$isSignatureIpRestricted = $requiredSignatureIp !== '';
$isCurrentNetworkAuthorized = !$isSignatureIpRestricted || ($clientIp !== '' && $clientIp === $requiredSignatureIp);

$employeeDisplayName = trim((string) (($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')));
if ($employeeDisplayName === '') {
    $employeeDisplayName = (string) ($currentUser['email'] ?? 'Employee');
}
$employeeDepartmentName = trim((string) ($profileRow['department_name'] ?? ''));
$employeeCompanyName = trim((string) ($profileRow['company_name'] ?? 'StaffEase Pro'));

$shiftsStatement = $pdo->prepare(
    'SELECT us.id,
            us.work_date,
            us.status AS assignment_status,
            s.name AS shift_name,
            s.kind AS shift_kind,
            s.icon AS shift_icon,
            s.color AS shift_color,
            s.start_time,
            s.end_time,
            d.name AS department_name,
            a.id AS attendance_id,
            a.status AS attendance_status,
            a.check_in_time,
            a.check_out_time
     FROM user_shifts us
     INNER JOIN shifts s ON s.id = us.shift_id
     INNER JOIN departments d ON d.id = s.department_id
     LEFT JOIN attendances a ON a.user_shift_id = us.id AND a.user_id = us.user_id
     WHERE us.user_id = :user_id
     ORDER BY us.work_date DESC, s.start_time ASC, us.id DESC'
);
$shiftsStatement->execute(['user_id' => $currentUser['id']]);
$shifts = $shiftsStatement->fetchAll();

$attendancesStatement = $pdo->prepare(
    'SELECT a.id, a.work_date, a.status, a.check_in_time, a.check_out_time, s.name AS shift_name
     FROM attendances a
     LEFT JOIN user_shifts us ON us.id = a.user_shift_id
     LEFT JOIN shifts s ON s.id = us.shift_id
     WHERE a.user_id = :user_id
     ORDER BY a.work_date DESC, a.id DESC'
);
$attendancesStatement->execute(['user_id' => $currentUser['id']]);
$attendances = $attendancesStatement->fetchAll();

$incomingDocumentsStatement = $pdo->prepare(
        'SELECT r.id,
                        r.title,
                        r.message,
                        r.type,
                        r.status,
                        r.created_at,
                        d.id AS document_id,
                        d.file_name,
                        d.upload_date,
                        CONCAT(sender.first_name, " ", sender.last_name) AS sender_name
         FROM requests r
         INNER JOIN documents d ON d.id = r.document_id
         INNER JOIN users sender ON sender.id = r.user_id
         WHERE r.recipient_id = :user_id
             AND r.document_id IS NOT NULL
             AND r.type IN ("notification", "document_signature")
         ORDER BY r.created_at DESC, r.id DESC'
);
$incomingDocumentsStatement->execute(['user_id' => (int) $currentUser['id']]);
$incomingDocuments = $incomingDocumentsStatement->fetchAll(PDO::FETCH_ASSOC);

$buildShiftDateTime = static function (string $workDate, ?string $timeValue) use ($todayDate): ?DateTimeImmutable {
    $normalizedTime = trim((string) $timeValue);
    if ($workDate === '' || $normalizedTime === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($workDate . ' ' . $normalizedTime);
    } catch (Throwable $e) {
        return null;
    }
};

$now = appNow();
$todaySignableShifts = [];
$todayTimelineShifts = [];
$upcomingShifts = [];
$currentShiftCard = null;

foreach ($shifts as &$shift) {
    $workDate = (string) ($shift['work_date'] ?? '');
    $assignmentStatus = (string) ($shift['assignment_status'] ?? 'assigned');
    $shiftKind = strtolower(trim((string) ($shift['shift_kind'] ?? 'work')));
    if (!in_array($shiftKind, ['work', 'rest', 'vacation', 'sick', 'overtime'], true)) {
        $shiftKind = 'work';
    }
    $shiftColor = trim((string) ($shift['shift_color'] ?? ''));
    if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $shiftColor)) {
        $shiftColor = '#b58e14';
    }
    $isWorkShift = $shiftKind === 'work' || $shiftKind === 'overtime';
    $startAt = $buildShiftDateTime($workDate, (string) ($shift['start_time'] ?? ''));
    $endAt = $buildShiftDateTime($workDate, (string) ($shift['end_time'] ?? ''));
    if ($startAt !== null && $endAt !== null && $endAt <= $startAt) {
        $endAt = $endAt->modify('+1 day');
    }

    $opensAt = $startAt?->modify('-5 minutes');
    $hasAttendance = (int) ($shift['attendance_id'] ?? 0) > 0 && trim((string) ($shift['check_in_time'] ?? '')) !== '';
    $isCancelled = $assignmentStatus === 'cancelled';
    $isSignWindowOpen = $isWorkShift
        && !$isCancelled
        && !$hasAttendance
        && $opensAt !== null
        && $endAt !== null
        && $now >= $opensAt
        && $now <= $endAt;
    $isBeforeWindow = $isWorkShift && !$isCancelled && !$hasAttendance && $opensAt !== null && $now < $opensAt;
    $isPastWindow = $isWorkShift && !$isCancelled && !$hasAttendance && $endAt !== null && $now > $endAt;

    $minutesUntilOpen = null;
    if ($isBeforeWindow && $opensAt !== null) {
        $minutesUntilOpen = max(0, (int) ceil(($opensAt->getTimestamp() - $now->getTimestamp()) / 60));
    }

    $shift['status'] = $assignmentStatus;
    $shift['shift_kind'] = $shiftKind;
    $shift['shift_color'] = $shiftColor;
    $shift['attendance_recorded'] = $hasAttendance;
    $shift['attendance_label'] = $hasAttendance ? 'Attendance already recorded' : ($shift['attendance_status'] ?? '');
    $shift['starts_at_iso'] = $startAt?->format(DateTimeInterface::ATOM) ?? null;
    $shift['ends_at_iso'] = $endAt?->format(DateTimeInterface::ATOM) ?? null;
    $shift['sign_open_at_iso'] = $opensAt?->format(DateTimeInterface::ATOM) ?? null;
    $shift['is_sign_window_open'] = $isSignWindowOpen;
    $shift['is_before_window'] = $isBeforeWindow;
    $shift['is_past_window'] = $isPastWindow;
    $shift['minutes_until_open'] = $minutesUntilOpen;

    if ($workDate >= $todayDate && !$isCancelled) {
        $upcomingShifts[] = $shift;
    }

    if ($workDate === $todayDate && in_array($assignmentStatus, ['assigned', 'in_progress', 'completed'], true)) {
        $todayTimelineShifts[] = $shift;
        if ($isWorkShift && $isSignWindowOpen) {
            $todaySignableShifts[] = $shift;
            if ($currentShiftCard === null) {
                $currentShiftCard = $shift;
            }
        } elseif ($currentShiftCard === null && ($startAt !== null && $endAt !== null) && $now <= $endAt) {
            $currentShiftCard = $shift;
        }
    }
}
unset($shift);

usort($upcomingShifts, static function (array $left, array $right): int {
    $leftStamp = strtotime((string) ($left['work_date'] ?? '') . ' ' . (string) ($left['start_time'] ?? '00:00:00')) ?: 0;
    $rightStamp = strtotime((string) ($right['work_date'] ?? '') . ' ' . (string) ($right['start_time'] ?? '00:00:00')) ?: 0;
    return $leftStamp <=> $rightStamp;
});

$upcomingShifts = array_slice($upcomingShifts, 0, 6);

if ($currentShiftCard === null && !empty($todayTimelineShifts)) {
    $currentShiftCard = $todayTimelineShifts[0];
}

$employeeUiState = [
    'server_time' => $now->format(DateTimeInterface::ATOM),
    'can_sign_now' => !empty($todaySignableShifts) && $isCurrentNetworkAuthorized,
    'has_shift_today' => !empty($todayTimelineShifts),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'sign_attendance') {
        $userShiftId = (int) ($_POST['user_shift_id'] ?? 0);
        $signatureData = trim((string) ($_POST['signature_data'] ?? ''));
        $shiftStartAt = null;
        $isLateCheckIn = false;
        if ($userShiftId <= 0) {
            $error = 'Please select a valid shift.';
        } elseif ($signatureData === '') {
            $error = 'Please draw your signature before confirming attendance.';
        } else {
            $shiftCheck = $pdo->prepare(
                'SELECT us.id,
                        us.work_date,
                    s.kind AS shift_kind,
                        s.start_time,
                        s.end_time,
                        ' . $companySignatureIpSelect . '
                 FROM user_shifts us
                 INNER JOIN shifts s ON s.id = us.shift_id
                 INNER JOIN departments d ON d.id = s.department_id
                 INNER JOIN companies c ON c.id = d.company_id
                 WHERE us.id = :id
                   AND us.user_id = :user_id
                 LIMIT 1'
            );
            $shiftCheck->execute(['id' => $userShiftId, 'user_id' => $currentUser['id']]);
            $assignedShift = $shiftCheck->fetch();

            if (!$assignedShift) {
                $error = 'Unauthorized shift.';
            } elseif ((string) ($assignedShift['work_date'] ?? '') !== $todayDate) {
                $error = 'You can only sign attendance for today\'s shift.';
            } elseif (!in_array(strtolower(trim((string) ($assignedShift['shift_kind'] ?? 'work'))), ['work', 'overtime'], true)) {
                $error = 'Attendance signing is available only for working shifts.';
            } else {
                $shiftRequiredIp = $normalizeIp((string) ($assignedShift['signature_ip'] ?? ''));
                if ($shiftRequiredIp !== '' && $clientIp !== $shiftRequiredIp) {
                    $error = 'Attendance signature allowed only from company Wi-Fi IP: ' . $shiftRequiredIp . '.';
                }

                $shiftStartAt = $buildShiftDateTime((string) ($assignedShift['work_date'] ?? ''), (string) ($assignedShift['start_time'] ?? ''));
                $shiftEndAt = $buildShiftDateTime((string) ($assignedShift['work_date'] ?? ''), (string) ($assignedShift['end_time'] ?? ''));
                if ($shiftStartAt !== null && $shiftEndAt !== null && $shiftEndAt <= $shiftStartAt) {
                    $shiftEndAt = $shiftEndAt->modify('+1 day');
                }

                if ($error === null && $shiftStartAt !== null && $shiftEndAt !== null) {
                    $signWindowStart = $shiftStartAt->modify('-5 minutes');
                    if ($now < $signWindowStart) {
                        $error = 'Attendance signing opens 5 minutes before your shift starts.';
                    } elseif ($now > $shiftEndAt) {
                        $error = 'Attendance signing is no longer available after shift end.';
                    }
                }

                if ($error === null && $shiftStartAt !== null && $now > $shiftStartAt) {
                    $isLateCheckIn = true;
                }
            }

            if ($error === null) {
                $attendanceStatus = $isLateCheckIn ? 'late' : 'present';
                $insertSignature = $pdo->prepare(
                    'INSERT INTO digital_signatures (user_id, signature_type, signature_data)
                     VALUES (:user_id, :signature_type, :signature_data)'
                );
                $insertSignature->execute([
                    'user_id' => (int) $currentUser['id'],
                    'signature_type' => 'touchscreen',
                    'signature_data' => $signatureData,
                ]);
                $digitalSignatureId = (int) $pdo->lastInsertId();

                $attendanceCheck = $pdo->prepare('SELECT id, check_in_time FROM attendances WHERE user_id = :user_id AND user_shift_id = :user_shift_id AND work_date = :work_date LIMIT 1');
                $attendanceCheck->execute([
                    'user_id' => $currentUser['id'],
                    'user_shift_id' => $userShiftId,
                    'work_date' => $todayDate,
                ]);
                $existingAttendance = $attendanceCheck->fetch(PDO::FETCH_ASSOC) ?: null;
                $existingAttendanceId = (int) ($existingAttendance['id'] ?? 0);

                if ($existingAttendanceId > 0 && trim((string) ($existingAttendance['check_in_time'] ?? '')) !== '') {
                    $error = 'Attendance already recorded for this shift. Ask your manager if it needs to be updated.';
                }

                if ($error === null && $existingAttendanceId > 0) {
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
                        'check_in_time' => $now->format('H:i:s'),
                        'id' => (int) $existingAttendanceId,
                    ]);
                } elseif ($error === null) {
                    $insertAttendance = $pdo->prepare(
                        'INSERT INTO attendances (user_id, user_shift_id, digital_signature_id, work_date, check_in_time, status)
                         VALUES (:user_id, :user_shift_id, :digital_signature_id, :work_date, :check_in_time, :status)'
                    );
                    $insertAttendance->execute([
                        'user_id' => $currentUser['id'],
                        'user_shift_id' => $userShiftId,
                        'digital_signature_id' => $digitalSignatureId,
                        'work_date' => $todayDate,
                        'check_in_time' => $now->format('H:i:s'),
                        'status' => $attendanceStatus,
                    ]);
                }

                if ($error === null) {
                    if ($isLateCheckIn) {
                        setFlash('success', 'Attendance recorded. You checked in late after scheduled start time.');
                    } else {
                        setFlash('success', 'Attendance recorded with touchscreen signature.');
                    }
                    redirectTo('my-space');
                }
            }
        }
    }

}