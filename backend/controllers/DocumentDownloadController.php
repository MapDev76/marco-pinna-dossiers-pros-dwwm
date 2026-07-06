<?php
/**
 * Serve protected document downloads.
 *
 * Validates the current session and authorizes downloads for super admins,
 * company admins (for their company), department managers (their department),
 * or the owner of the document. The controller resolves candidate filesystem
 * paths from the stored document path and streams the file with proper
 * headers.
 */
if (!isLoggedIn()) {
    setFlash('error', t('common.login_required'));
    redirectTo('login');
}

$pdo = getPDO();
ensureDocumentStorageSchema($pdo);
$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';
$documentId = (int) ($_GET['id'] ?? 0);
$contentDisposition = strtolower(trim((string) ($_GET['disposition'] ?? '')));
$serveInline = $contentDisposition === 'inline';
$previewMode = in_array(strtolower(trim((string) ($_GET['preview'] ?? ''))), ['1', 'true', 'yes', 'on'], true);
$printPreview = in_array(strtolower(trim((string) ($_GET['print_preview'] ?? ''))), ['1', 'true', 'yes', 'on'], true);
$sourceRoute = trim((string) ($_GET['from'] ?? ''));
$referer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
$redirectRoute = ($role === 'employee') ? 'my-space' : 'dashboard';
if ($sourceRoute === 'my-space' || str_contains($referer, 'route=my-space')) {
    $redirectRoute = 'my-space';
}

if ($documentId <= 0) {
    setFlash('error', t('common.document_not_found'));
    redirectTo($redirectRoute);
}

$statement = $pdo->prepare(
    'SELECT d.id, d.user_id, d.file_name, d.file_path, d.file_blob, d.file_mime_type, u.department_id, dep.company_id
     FROM documents d
     INNER JOIN users u ON u.id = d.user_id
     LEFT JOIN departments dep ON dep.id = u.department_id
     WHERE d.id = :id
     LIMIT 1'
);
$statement->execute(['id' => $documentId]);
$document = $statement->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    setFlash('error', t('common.document_not_found'));
    redirectTo($redirectRoute);
}

$currentUserId = (int) $currentUser['id'];
$allowed = false;
$currentCompanyId = currentUserCompanyId($currentUser);

if ($role === 'super_admin') {
    $allowed = true;
} elseif ($role === 'admin' && $currentCompanyId !== null && (int) $document['company_id'] === $currentCompanyId) {
    $allowed = true;
} elseif ($role === 'department_manager' && !empty($currentUser['department_id']) && (int) $document['department_id'] === (int) $currentUser['department_id']) {
    $allowed = true;
} elseif ((int) $document['user_id'] === $currentUserId) {
    $allowed = true;
} else {
    $recipientLookup = $pdo->prepare(
        'SELECT id
         FROM requests
         WHERE recipient_id = :recipient_id
           AND document_id = :document_id
         LIMIT 1'
    );
    $recipientLookup->execute([
        'recipient_id' => $currentUserId,
        'document_id' => $documentId,
    ]);
    $allowed = (bool) $recipientLookup->fetchColumn();
}

if (!$allowed) {
    setFlash('error', t('common.access_denied'));
    redirectTo($redirectRoute);
}

$markRead = in_array(strtolower(trim((string) ($_GET['mark_read'] ?? ''))), ['1', 'true', 'yes', 'on'], true);
$requestId = (int) ($_GET['request_id'] ?? 0);
if ($markRead && $requestId > 0) {
    $markReadStatement = $pdo->prepare(
        'UPDATE requests
         SET status = "read",
             updated_at = CURRENT_TIMESTAMP
         WHERE id = :id
           AND recipient_id = :recipient_id
           AND document_id = :document_id
           AND type IN ("notification", "document_signature")
           AND status IN ("pending", "unread")
         LIMIT 1'
    );
    $markReadStatement->execute([
        'id' => $requestId,
        'recipient_id' => $currentUserId,
        'document_id' => $documentId,
    ]);

    if ($markReadStatement->rowCount() > 0) {
        $requestInfoLookup = $pdo->prepare(
            'SELECT user_id, title, document_id
             FROM requests
             WHERE id = :id
               AND recipient_id = :recipient_id
             LIMIT 1'
        );
        $requestInfoLookup->execute([
            'id' => $requestId,
            'recipient_id' => $currentUserId,
        ]);
        $requestInfo = $requestInfoLookup->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($requestInfo) {
            $senderId = (int) ($requestInfo['user_id'] ?? 0);
            if ($senderId > 0) {
                $viewerName = trim((string) (($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')));
                if ($viewerName === '') {
                    $viewerName = (string) ($currentUser['email'] ?? 'Employee');
                }

                $insertNotification = $pdo->prepare(
                    'INSERT INTO requests (user_id, recipient_id, type, title, message, status, document_id)
                     VALUES (:user_id, :recipient_id, :type, :title, :message, :status, :document_id)'
                );
                $insertNotification->execute([
                    'user_id' => $currentUserId,
                    'recipient_id' => $senderId,
                    'type' => 'notification',
                    'title' => 'Document viewed',
                    'message' => $viewerName . ' viewed document "' . ((string) ($requestInfo['title'] ?? 'Document')) . '" successfully.',
                    'status' => 'unread',
                    'document_id' => (int) ($requestInfo['document_id'] ?? 0),
                ]);
            }
        }
    }
}

$filePath = trim((string) ($document['file_path'] ?? ''));
$candidatePaths = [];

if ($filePath !== '') {
    $candidatePaths[] = $filePath;
    $candidatePaths[] = __DIR__ . '/../../' . ltrim($filePath, '/');
    $candidatePaths[] = __DIR__ . '/../../public/' . ltrim($filePath, '/');
}

$resolvedPath = null;
foreach ($candidatePaths as $candidatePath) {
    if (is_string($candidatePath) && $candidatePath !== '' && is_file($candidatePath)) {
        $resolvedPath = $candidatePath;
        break;
    }
}

$downloadName = basename((string) ($document['file_name'] ?? ($resolvedPath !== null ? basename($resolvedPath) : 'document.bin')));
$downloadUrl = appUrl('document-download', [
    'id' => $documentId,
    'from' => $redirectRoute,
]);

$renderDocumentPreview = static function (string $content, string $mimeType, string $documentName, bool $autoPrint, string $downloadHref): never {
    $safeName = htmlspecialchars($documentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $normalizedMime = strtolower(trim($mimeType));
    $safeDownloadHref = htmlspecialchars($downloadHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $bodyHtml = '';

    if (str_contains($normalizedMime, 'html') && $autoPrint) {
        $htmlContent = $content;
        if (!str_contains(strtolower($htmlContent), 'window.print(')) {
            $printScript = '<script>window.addEventListener("load", function(){ setTimeout(function(){ try { window.print(); } catch(e) {} }, 160); });</script>';
            if (str_contains(strtolower($htmlContent), '</body>')) {
                $htmlContent = preg_replace('/<\/body>/i', $printScript . '</body>', $htmlContent, 1) ?? ($htmlContent . $printScript);
            } else {
                $htmlContent .= $printScript;
            }
        }

        header('Content-Type: text/html; charset=utf-8');
        echo $htmlContent;
        exit;
    }

    if (str_contains($normalizedMime, 'html')) {
        $bodyHtml = '<iframe class="print-preview-frame" srcdoc="' . htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"></iframe>';
    } elseif (str_starts_with($normalizedMime, 'image/')) {
        $bodyHtml = '<img src="data:' . htmlspecialchars($mimeType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';base64,' . base64_encode($content) . '" alt="' . $safeName . '" class="print-preview-image">';
    } elseif (str_contains($normalizedMime, 'pdf')) {
        $bodyHtml = '<iframe class="print-preview-frame" src="data:application/pdf;base64,' . base64_encode($content) . '"></iframe>';
    } elseif (str_starts_with($normalizedMime, 'video/')) {
        $bodyHtml = '<video class="print-preview-media" controls><source src="data:' . htmlspecialchars($mimeType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';base64,' . base64_encode($content) . '"></video>';
    } elseif (str_starts_with($normalizedMime, 'audio/')) {
        $bodyHtml = '<audio class="print-preview-audio" controls><source src="data:' . htmlspecialchars($mimeType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';base64,' . base64_encode($content) . '"></audio>';
    } elseif (str_starts_with($normalizedMime, 'text/') || str_contains($normalizedMime, 'json') || str_contains($normalizedMime, 'xml') || str_contains($normalizedMime, 'csv')) {
        $bodyHtml = '<pre class="print-preview-text">' . htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
    } else {
        $bodyHtml = '<p>Preview is not available for this file type.</p>';
    }

    $actionsHtml = '<div class="print-preview-actions">'
        . '<a class="print-preview-link" href="' . $safeDownloadHref . '">Download file</a>';
    if ($autoPrint) {
        $actionsHtml .= '<button type="button" class="print-preview-link" onclick="window.print()">Print now</button>';
    }
    $actionsHtml .= '</div>';

    $scriptHtml = '';
    if ($autoPrint) {
        $scriptHtml = '<script>window.addEventListener("load", function(){ setTimeout(function(){ try { window.print(); } catch(e) {} }, 160); });</script>';
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>' . ($autoPrint ? 'Print preview' : 'Document preview') . ' - ' . $safeName . '</title>'
        . '<style>body{margin:0;font-family:Arial,sans-serif;background:#f7f7f7;color:#111;}main{max-width:1100px;margin:0 auto;padding:16px;}h1{margin:0 0 12px;font-size:20px;} .print-preview-actions{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 14px;} .print-preview-link{display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;border:1px solid #c5c5c5;border-radius:8px;background:#fff;color:#111;text-decoration:none;cursor:pointer;font-size:14px;} .print-preview-link:hover{background:#f4f4f4;} .print-preview-frame{width:100%;height:80vh;border:1px solid #d5d5d5;border-radius:8px;background:#fff;} .print-preview-image{max-width:100%;height:auto;border:1px solid #d5d5d5;border-radius:8px;background:#fff;} .print-preview-media{width:100%;max-height:75vh;border:1px solid #d5d5d5;border-radius:8px;background:#111;} .print-preview-audio{width:100%;} .print-preview-text{white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid #d5d5d5;border-radius:8px;padding:12px;min-height:60vh;} @media print{body{background:#fff;}main{max-width:none;padding:0;}h1,.print-preview-actions{display:none;} .print-preview-frame{height:100vh;border:0;} .print-preview-text,.print-preview-image,.print-preview-media{border:0;}}</style>'
        . '</head><body><main><h1>' . $safeName . '</h1>' . $actionsHtml . $bodyHtml . '</main>' . $scriptHtml . '</body></html>';
    exit;
};

if ($resolvedPath !== null) {
    $mimeType = mime_content_type($resolvedPath) ?: 'application/octet-stream';
    if ($previewMode || $printPreview) {
        $previewContent = @file_get_contents($resolvedPath);
        if (is_string($previewContent)) {
            $renderDocumentPreview($previewContent, $mimeType, $downloadName, $printPreview, $downloadUrl);
        }
    }
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . (string) filesize($resolvedPath));
    header('Content-Disposition: ' . ($serveInline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $downloadName) . '"');
    readfile($resolvedPath);
    exit;
}

$blobContent = $document['file_blob'] ?? null;
if (is_string($blobContent) && $blobContent !== '') {
    $mimeType = trim((string) ($document['file_mime_type'] ?? '')) ?: 'application/octet-stream';
    if ($previewMode || $printPreview) {
        $renderDocumentPreview($blobContent, $mimeType, $downloadName, $printPreview, $downloadUrl);
    }
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . (string) strlen($blobContent));
    header('Content-Disposition: ' . ($serveInline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $downloadName) . '"');
    echo $blobContent;
    exit;
}

setFlash('error', t('common.file_not_available'));
if ($redirectRoute === 'my-space') {
    redirectTo('my-space', ['focus' => 'employee-received-documents']);
}
redirectTo('dashboard', ['modal' => 'documents']);