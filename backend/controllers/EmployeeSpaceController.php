<?php

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'sign_attendance') {
        $userShiftId = (int) ($_POST['user_shift_id'] ?? 0);
        if ($userShiftId <= 0) {
            $error = 'Please select a valid shift.';
        } else {
            $shiftCheck = $pdo->prepare('SELECT id FROM user_shifts WHERE id = :id AND user_id = :user_id LIMIT 1');
            $shiftCheck->execute(['id' => $userShiftId, 'user_id' => $currentUser['id']]);

                if (!$shiftCheck->fetchColumn()) {
                    $error = 'Unauthorized shift.';
                } else {
                $today = date('Y-m-d');
                $attendanceCheck = $pdo->prepare('SELECT id FROM attendances WHERE user_id = :user_id AND user_shift_id = :user_shift_id AND work_date = :work_date LIMIT 1');
                $attendanceCheck->execute([
                    'user_id' => $currentUser['id'],
                    'user_shift_id' => $userShiftId,
                    'work_date' => $today,
                ]);
                $existingAttendanceId = $attendanceCheck->fetchColumn();

                if ($existingAttendanceId) {
                    $updateAttendance = $pdo->prepare(
                        'UPDATE attendances
                         SET status = :status, updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id'
                    );
                    $updateAttendance->execute([
                        'status' => 'present',
                        'id' => (int) $existingAttendanceId,
                    ]);
                } else {
                    $insertAttendance = $pdo->prepare(
                        'INSERT INTO attendances (user_id, user_shift_id, work_date, status)
                         VALUES (:user_id, :user_shift_id, :work_date, :status)'
                    );
                    $insertAttendance->execute([
                        'user_id' => $currentUser['id'],
                        'user_shift_id' => $userShiftId,
                        'work_date' => $today,
                        'status' => 'present',
                    ]);
                }

                setFlash('success', 'Attendance recorded.');
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