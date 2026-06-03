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
$todayDate = date('Y-m-d');

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
    'SELECT ' . $companySignatureIpSelect . '
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     LEFT JOIN companies c ON c.id = d.company_id
     WHERE u.id = :user_id
     LIMIT 1'
);
$signaturePolicyStatement->execute(['user_id' => (int) $currentUser['id']]);
$requiredSignatureIpRaw = (string) ($signaturePolicyStatement->fetchColumn() ?: '');
$requiredSignatureIp = $normalizeIp($requiredSignatureIpRaw);
$isSignatureIpRestricted = $requiredSignatureIp !== '';
$isCurrentNetworkAuthorized = !$isSignatureIpRestricted || ($clientIp !== '' && $clientIp === $requiredSignatureIp);

$shiftsStatement = $pdo->prepare(
    'SELECT us.id, us.work_date, us.status, s.name AS shift_name, s.start_time, s.end_time, d.name AS department_name
     FROM user_shifts us
     INNER JOIN shifts s ON s.id = us.shift_id
     INNER JOIN departments d ON d.id = s.department_id
     WHERE us.user_id = :user_id
     ORDER BY us.work_date DESC, us.id DESC'
);
$shiftsStatement->execute(['user_id' => $currentUser['id']]);
$shifts = $shiftsStatement->fetchAll();

$requestsStatement = $pdo->prepare(
    'SELECT id, type, title, message, status, created_at
     FROM requests
     WHERE user_id = :user_id
     ORDER BY created_at DESC, id DESC'
);
$requestsStatement->execute(['user_id' => $currentUser['id']]);
$requests = $requestsStatement->fetchAll();

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

$todaySignableShifts = array_values(array_filter(
    $shifts,
    static fn (array $shift): bool => (string) ($shift['work_date'] ?? '') === date('Y-m-d')
        && in_array((string) ($shift['status'] ?? 'assigned'), ['assigned', 'in_progress', 'completed'], true)
));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'sign_attendance') {
        $userShiftId = (int) ($_POST['user_shift_id'] ?? 0);
        $signatureData = trim((string) ($_POST['signature_data'] ?? ''));
        if ($userShiftId <= 0) {
            $error = 'Please select a valid shift.';
        } elseif ($signatureData === '') {
            $error = 'Please draw your signature before confirming attendance.';
        } else {
            $shiftCheck = $pdo->prepare(
                'SELECT us.id, us.work_date, ' . $companySignatureIpSelect . '
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
            } else {
                $shiftRequiredIp = $normalizeIp((string) ($assignedShift['signature_ip'] ?? ''));
                if ($shiftRequiredIp !== '' && $clientIp !== $shiftRequiredIp) {
                    $error = 'Attendance signature allowed only from company Wi-Fi IP: ' . $shiftRequiredIp . '.';
                }
            }

            if ($error === null) {
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

                $attendanceCheck = $pdo->prepare('SELECT id FROM attendances WHERE user_id = :user_id AND user_shift_id = :user_shift_id AND work_date = :work_date LIMIT 1');
                $attendanceCheck->execute([
                    'user_id' => $currentUser['id'],
                    'user_shift_id' => $userShiftId,
                    'work_date' => $todayDate,
                ]);
                $existingAttendanceId = $attendanceCheck->fetchColumn();

                if ($existingAttendanceId) {
                    $updateAttendance = $pdo->prepare(
                        'UPDATE attendances
                         SET status = :status,
                             digital_signature_id = :digital_signature_id,
                             check_in_time = COALESCE(check_in_time, CURRENT_TIME),
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id'
                    );
                    $updateAttendance->execute([
                        'status' => 'present',
                        'digital_signature_id' => $digitalSignatureId,
                        'id' => (int) $existingAttendanceId,
                    ]);
                } else {
                    $insertAttendance = $pdo->prepare(
                        'INSERT INTO attendances (user_id, user_shift_id, digital_signature_id, work_date, check_in_time, status)
                         VALUES (:user_id, :user_shift_id, :digital_signature_id, :work_date, CURRENT_TIME, :status)'
                    );
                    $insertAttendance->execute([
                        'user_id' => $currentUser['id'],
                        'user_shift_id' => $userShiftId,
                        'digital_signature_id' => $digitalSignatureId,
                        'work_date' => $todayDate,
                        'status' => 'present',
                    ]);
                }

                setFlash('success', 'Attendance recorded with touchscreen signature.');
                redirectTo('my-space');
            }
        }
    }

    if ($action === 'create_request') {
        $type = trim((string) ($_POST['type'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($type === '' || $message === '') {
            $error = 'Type and message are required.';
        } else {
            $requestInsert = $pdo->prepare(
                'INSERT INTO requests (user_id, type, title, message, status)
                 VALUES (:user_id, :type, :title, :message, :status)'
            );
            $requestInsert->execute([
                'user_id' => $currentUser['id'],
                'type' => $type,
                'title' => $title !== '' ? $title : null,
                'message' => $message,
                'status' => 'pending',
            ]);

            setFlash('success', 'Request sent.');
            redirectTo('my-space');
        }
    }
}