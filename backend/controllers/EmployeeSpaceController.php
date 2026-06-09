<?php
/**
 * Employee space controller.
 *
 * Displays the personal area for users. Handles
 * attendance signing and personal requests. Attendance signing can be
 * restricted by company Wi-Fi IP when configured by admin/super admin.
 */
require_once __DIR__ . '/../bootstrap.php';

if (!isLoggedIn()) {
    setFlash('error', t('common.login_required'));
    redirectTo('login');
}

$currentUser = currentUser();
$allowedRoles = ['employee', 'admin', 'department_manager'];
if (!in_array((string) ($currentUser['role'] ?? ''), $allowedRoles, true)) {
    setFlash('error', t('common.access_restricted'));
    redirectTo('dashboard');
}

$pdo = getPDO();
ensureDocumentStorageSchema($pdo);
$pageTitle = 'My Staff Space';
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
            c.id AS company_id,
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
$employeeCompanyId = (int) ($profileRow['company_id'] ?? 0);

$documentShareRecipients = [];
try {
    $recipientSql =
        'SELECT u.id,
                u.role,
                CONCAT(u.first_name, " ", u.last_name) AS full_name,
                d.company_id
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.status = "active"
           AND u.id <> :current_user_id';

    if ($employeeCompanyId > 0) {
        $recipientSql .= ' AND d.company_id = :company_id';
    }

    $recipientSql .= ' ORDER BY FIELD(u.role, "employee", "department_manager", "admin", "super_admin"), full_name ASC';

    $recipientStmt = $pdo->prepare($recipientSql);
    $recipientParams = ['current_user_id' => (int) $currentUser['id']];
    if ($employeeCompanyId > 0) {
        $recipientParams['company_id'] = $employeeCompanyId;
    }
    $recipientStmt->execute($recipientParams);
    $documentShareRecipients = $recipientStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $documentShareRecipients = [];
}

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
    'SELECT r.id AS request_id,
                        r.title,
                        r.message,
                        r.type,
                        r.status,
            r.user_id AS sender_id,
            r.recipient_id,
                        r.created_at,
                        d.id AS document_id,
                        d.file_name,
            d.file_path,
        d.file_mime_type,
            (d.file_blob IS NOT NULL) AS has_db_content,
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

$resolveStoredDocumentPath = static function (array $row): ?string {
    $filePath = trim((string) ($row['file_path'] ?? ''));
    if ($filePath === '') {
        return null;
    }

    $candidates = [
        $filePath,
        __DIR__ . '/../../' . ltrim($filePath, '/'),
        __DIR__ . '/../../public/' . ltrim($filePath, '/'),
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
};

foreach ($incomingDocuments as &$incomingDocument) {
    $hasDbContent = !empty($incomingDocument['has_db_content']);
    $incomingDocument['is_download_available'] = $hasDbContent || ($resolveStoredDocumentPath($incomingDocument) !== null);
    $incomingDocument['can_sign'] = (string) ($incomingDocument['type'] ?? '') === 'document_signature'
        && in_array((string) ($incomingDocument['status'] ?? ''), ['pending', 'unread'], true)
        && (int) ($incomingDocument['recipient_id'] ?? 0) === (int) ($currentUser['id'] ?? 0);
}
unset($incomingDocument);

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

    if ($action === 'share_document_no_signature') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $recipientIdsRaw = $_POST['recipient_ids'] ?? [];
        $recipientIds = array_values(array_filter(array_map('intval', is_array($recipientIdsRaw) ? $recipientIdsRaw : [$recipientIdsRaw])));

        $file = $_FILES['document_file'] ?? null;
        $fileName = trim((string) ($file['name'] ?? ''));
        $tmpPath = trim((string) ($file['tmp_name'] ?? ''));
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $fileSize = (int) ($file['size'] ?? 0);

        if ($uploadError !== UPLOAD_ERR_OK || $tmpPath === '' || !is_uploaded_file($tmpPath)) {
            setFlash('error', t('employee.upload_missing', ['fallback' => 'Please choose a valid document to upload.']));
            redirectTo('my-space');
        }
        if ($fileSize <= 0 || $fileSize > (8 * 1024 * 1024)) {
            setFlash('error', t('employee.upload_too_large', ['fallback' => 'Document size must be between 1 byte and 8 MB.']));
            redirectTo('my-space');
        }

        $safeBaseName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $fileName ?: 'document');
        $safeBaseName = trim((string) $safeBaseName, '-.');
        if ($safeBaseName === '') {
            $safeBaseName = 'document-' . appNow()->format('Ymd-His');
        }
        if (strlen($safeBaseName) > 180) {
            $safeBaseName = substr($safeBaseName, 0, 180);
        }

        $payload = @file_get_contents($tmpPath);
        if (!is_string($payload) || $payload === '') {
            setFlash('error', t('employee.upload_read_error', ['fallback' => 'Unable to read the uploaded document.']));
            redirectTo('my-space');
        }

        $detectedMime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeProbe = @finfo_file($finfo, $tmpPath);
                if (is_string($mimeProbe) && trim($mimeProbe) !== '') {
                    $detectedMime = trim($mimeProbe);
                }
                @finfo_close($finfo);
            }
        }

        $allowedRecipientSet = [];
        foreach ($documentShareRecipients as $recipientRow) {
            $rid = (int) ($recipientRow['id'] ?? 0);
            if ($rid > 0) {
                $allowedRecipientSet[$rid] = true;
            }
        }

        if (!empty($recipientIds)) {
            $recipientIds = array_values(array_filter($recipientIds, static fn (int $id): bool => isset($allowedRecipientSet[$id])));
        }
        if (empty($recipientIds)) {
            $recipientIds = array_map('intval', array_keys($allowedRecipientSet));
        }

        if (empty($recipientIds)) {
            setFlash('error', t('employee.no_recipient_available', ['fallback' => 'No recipient is available for document sharing.']));
            redirectTo('my-space');
        }

        if ($title === '') {
            $title = 'Document shared by employee';
        }
        if ($message === '') {
            $message = 'Please review the attached document.';
        }

        $insertDocument = $pdo->prepare(
            'INSERT INTO documents (user_id, document_type, file_name, file_path, file_blob, file_mime_type, status)
             VALUES (:user_id, :document_type, :file_name, :file_path, :file_blob, :file_mime_type, :status)'
        );
        $insertRequest = $pdo->prepare(
            'INSERT INTO requests (user_id, recipient_id, type, title, message, status, document_id)
             VALUES (:user_id, :recipient_id, :type, :title, :message, :status, :document_id)'
        );

        $pdo->beginTransaction();
        try {
            $insertDocument->execute([
                'user_id' => (int) $currentUser['id'],
                'document_type' => 'other',
                'file_name' => $safeBaseName,
                'file_path' => '',
                'file_blob' => $payload,
                'file_mime_type' => $detectedMime,
                'status' => 'valid',
            ]);
            $documentId = (int) $pdo->lastInsertId();

            foreach ($recipientIds as $recipientId) {
                $insertRequest->execute([
                    'user_id' => (int) $currentUser['id'],
                    'recipient_id' => (int) $recipientId,
                    'type' => 'notification',
                    'title' => $title,
                    'message' => $message,
                    'status' => 'unread',
                    'document_id' => $documentId,
                ]);
            }

            $pdo->commit();
            setFlash('success', t('employee.document_shared_success', ['fallback' => 'Document uploaded and sent successfully.']));
            redirectTo('my-space');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlash('error', t('employee.document_share_failed', ['fallback' => 'Unable to share this document right now.']));
            redirectTo('my-space');
        }
    }

    if ($action === 'sign_document') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $signatureData = trim((string) ($_POST['signature_data'] ?? ''));

        if ($requestId <= 0) {
            $error = t('employee.select_valid_document', ['fallback' => 'Please select a valid document to sign.']);
        } elseif ($signatureData === '') {
            $error = t('employee.draw_signature_first');
        } else {
            $requestLookup = $pdo->prepare(
                'SELECT r.id,
                        r.user_id AS sender_id,
                        r.recipient_id,
                        r.type,
                        r.status,
                        r.title,
                        r.message,
                        r.document_id,
                        d.file_name,
                        d.file_path,
                        d.file_mime_type,
                        sender.first_name AS sender_first_name,
                        sender.last_name AS sender_last_name,
                        dep.company_id AS sender_company_id
                 FROM requests r
                 INNER JOIN documents d ON d.id = r.document_id
                 INNER JOIN users sender ON sender.id = r.user_id
                 LEFT JOIN departments dep ON dep.id = sender.department_id
                 WHERE r.id = :request_id
                   AND r.recipient_id = :recipient_id
                   AND r.type = "document_signature"
                 LIMIT 1'
            );
            $requestLookup->execute([
                'request_id' => $requestId,
                'recipient_id' => (int) $currentUser['id'],
            ]);
            $signatureRequest = $requestLookup->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$signatureRequest) {
                $error = t('common.unauthorized');
            } elseif (in_array((string) ($signatureRequest['status'] ?? ''), ['approved', 'rejected', 'cancelled'], true)) {
                $error = t('employee.document_already_signed', ['fallback' => 'This signature request has already been processed.']);
            } else {
                $senderName = trim((string) (($signatureRequest['sender_first_name'] ?? '') . ' ' . ($signatureRequest['sender_last_name'] ?? '')));
                if ($senderName === '') {
                    $senderName = 'Manager';
                }

                $signedFileBase = pathinfo((string) ($signatureRequest['file_name'] ?? 'document'), PATHINFO_FILENAME);
                $signedFileBase = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $signedFileBase ?: 'document') ?: 'document';
                $signedFileName = 'signed-' . $signedFileBase . '-' . appNow()->format('Ymd-His') . '.html';
                $signedAt = appNow()->format('Y-m-d H:i:s');
                $employeeName = trim((string) (($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')));
                if ($employeeName === '') {
                    $employeeName = (string) ($currentUser['email'] ?? 'Employee');
                }

                $sourceDownloadUrl = appUrl('document-download', ['id' => (int) ($signatureRequest['document_id'] ?? 0)]);
                $safeTitle = htmlspecialchars((string) ($signatureRequest['title'] ?? 'Signed document'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeSenderName = htmlspecialchars($senderName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeEmployeeName = htmlspecialchars($employeeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeSignedAt = htmlspecialchars($signedAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeSourceUrl = htmlspecialchars($sourceDownloadUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeSignatureData = htmlspecialchars($signatureData, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                $signedHtml = '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Signed document</title>'
                    . '<style>body{font-family:Arial,sans-serif;background:#f7f7f8;color:#111827;padding:32px;}main{max-width:880px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;}h1{font-size:1.4rem;margin:0 0 10px;}dl{display:grid;grid-template-columns:180px 1fr;gap:8px 14px;margin:18px 0;}dt{font-weight:700;color:#374151;}dd{margin:0;color:#111827;}img{display:block;max-width:420px;width:100%;height:auto;border:1px solid #d1d5db;border-radius:10px;background:#fff;padding:8px;}a{color:#9a7a14;}</style>'
                    . '</head><body><main><h1>' . $safeTitle . '</h1><p>Digitally signed copy generated by StaffEase Pro.</p>'
                    . '<dl><dt>Signed by</dt><dd>' . $safeEmployeeName . '</dd><dt>Requested by</dt><dd>' . $safeSenderName . '</dd><dt>Signed at</dt><dd>' . $safeSignedAt . '</dd><dt>Original document</dt><dd><a href="' . $safeSourceUrl . '">Download source file</a></dd></dl>'
                    . '<h2>Signature</h2><img src="' . $safeSignatureData . '" alt="Employee signature"></main></body></html>';

                $insertSignature = $pdo->prepare(
                    'INSERT INTO digital_signatures (user_id, signature_type, signature_data)
                     VALUES (:user_id, :signature_type, :signature_data)'
                );
                $insertSignedDocument = $pdo->prepare(
                    'INSERT INTO documents (user_id, document_type, file_name, file_path, file_blob, file_mime_type, status)
                     VALUES (:user_id, :document_type, :file_name, :file_path, :file_blob, :file_mime_type, :status)'
                );
                $updateRequest = $pdo->prepare(
                    'UPDATE requests
                     SET status = :status,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id
                       AND recipient_id = :recipient_id
                     LIMIT 1'
                );
                $insertNotification = $pdo->prepare(
                    'INSERT INTO requests (user_id, recipient_id, type, title, message, status, document_id)
                     VALUES (:user_id, :recipient_id, :type, :title, :message, :status, :document_id)'
                );

                $pdo->beginTransaction();
                try {
                    $insertSignature->execute([
                        'user_id' => (int) $currentUser['id'],
                        'signature_type' => 'touchscreen',
                        'signature_data' => $signatureData,
                    ]);

                    $insertSignedDocument->execute([
                        'user_id' => (int) $currentUser['id'],
                        'document_type' => 'other',
                        'file_name' => $signedFileName,
                        'file_path' => '',
                        'file_blob' => $signedHtml,
                        'file_mime_type' => 'text/html; charset=utf-8',
                        'status' => 'valid',
                    ]);
                    $signedDocumentId = (int) $pdo->lastInsertId();

                    $updateRequest->execute([
                        'status' => 'approved',
                        'id' => $requestId,
                        'recipient_id' => (int) $currentUser['id'],
                    ]);

                    $recipientIds = [(int) ($signatureRequest['sender_id'] ?? 0)];
                    $senderCompanyId = (int) ($signatureRequest['sender_company_id'] ?? 0);
                    $adminRecipientsSql = 'SELECT u.id
                                           FROM users u
                                           LEFT JOIN departments d ON d.id = u.department_id
                                           WHERE u.status = "active"
                                             AND (
                                               u.role = "super_admin"
                                               OR (u.role = "admin" AND d.company_id = :company_id)
                                             )';
                    $adminRecipients = $pdo->prepare($adminRecipientsSql);
                    $adminRecipients->execute(['company_id' => $senderCompanyId]);
                    $recipientIds = array_merge($recipientIds, array_map('intval', $adminRecipients->fetchAll(PDO::FETCH_COLUMN) ?: []));

                    $recipientIds = array_values(array_unique(array_filter($recipientIds, static fn (int $id): bool => $id > 0)));
                    $signedNotificationTitle = 'Signed document returned';
                    $signedNotificationMessage = $employeeName . ' signed "' . ((string) ($signatureRequest['file_name'] ?? 'document')) . '" on ' . $signedAt . '.';

                    foreach ($recipientIds as $recipientId) {
                        $insertNotification->execute([
                            'user_id' => (int) $currentUser['id'],
                            'recipient_id' => $recipientId,
                            'type' => 'notification',
                            'title' => $signedNotificationTitle,
                            'message' => $signedNotificationMessage,
                            'status' => 'unread',
                            'document_id' => $signedDocumentId,
                        ]);
                    }

                    $pdo->commit();
                    setFlash('success', t('flash.document_signed_success', ['fallback' => 'Document signed and shared successfully.']));
                    redirectTo('my-space');
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = t('employee.document_sign_failed', ['fallback' => 'Unable to sign this document right now.']);
                }
            }
        }
    }

    if ($action === 'sign_attendance') {
        $userShiftId = (int) ($_POST['user_shift_id'] ?? 0);
        $signatureData = trim((string) ($_POST['signature_data'] ?? ''));
        $shiftStartAt = null;
        $isLateCheckIn = false;
        if ($userShiftId <= 0) {
            $error = t('employee.select_valid_shift');
        } elseif ($signatureData === '') {
            $error = t('employee.draw_signature_first');
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
                $error = t('common.unauthorized_shift');
            } elseif ((string) ($assignedShift['work_date'] ?? '') !== $todayDate) {
                $error = t('employee.sign_today_only');
            } elseif (!in_array(strtolower(trim((string) ($assignedShift['shift_kind'] ?? 'work'))), ['work', 'overtime'], true)) {
                $error = t('employee.sign_work_shifts_only');
            } else {
                $shiftRequiredIp = $normalizeIp((string) ($assignedShift['signature_ip'] ?? ''));
                if ($shiftRequiredIp !== '' && $clientIp !== $shiftRequiredIp) {
                    $error = t('employee.signature_ip_required', ['ip' => $shiftRequiredIp]);
                }

                $shiftStartAt = $buildShiftDateTime((string) ($assignedShift['work_date'] ?? ''), (string) ($assignedShift['start_time'] ?? ''));
                $shiftEndAt = $buildShiftDateTime((string) ($assignedShift['work_date'] ?? ''), (string) ($assignedShift['end_time'] ?? ''));
                if ($shiftStartAt !== null && $shiftEndAt !== null && $shiftEndAt <= $shiftStartAt) {
                    $shiftEndAt = $shiftEndAt->modify('+1 day');
                }

                if ($error === null && $shiftStartAt !== null && $shiftEndAt !== null) {
                    $signWindowStart = $shiftStartAt->modify('-5 minutes');
                    if ($now < $signWindowStart) {
                        $error = t('employee.sign_open_before_shift');
                    } elseif ($now > $shiftEndAt) {
                        $error = t('employee.sign_closed_after_shift');
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
                    $error = t('employee.attendance_already_recorded');
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
                        setFlash('success', t('flash.attendance_recorded_late'));
                    } else {
                        setFlash('success', t('flash.attendance_recorded'));
                    }
                    redirectTo('my-space');
                }
            }
        }
    }

}