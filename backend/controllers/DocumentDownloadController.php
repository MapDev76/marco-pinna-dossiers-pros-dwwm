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

if ($documentId <= 0) {
    setFlash('error', t('common.document_not_found'));
    redirectTo($role === 'employee' ? 'my-space' : 'dashboard');
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
    redirectTo($role === 'employee' ? 'my-space' : 'dashboard');
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
} elseif ($role === 'employee') {
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
    redirectTo($role === 'employee' ? 'my-space' : 'dashboard');
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

if ($resolvedPath !== null) {
    $mimeType = mime_content_type($resolvedPath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . (string) filesize($resolvedPath));
    header('Content-Disposition: ' . ($serveInline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $downloadName) . '"');
    readfile($resolvedPath);
    exit;
}

$blobContent = $document['file_blob'] ?? null;
if (is_string($blobContent) && $blobContent !== '') {
    $mimeType = trim((string) ($document['file_mime_type'] ?? '')) ?: 'application/octet-stream';
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . (string) strlen($blobContent));
    header('Content-Disposition: ' . ($serveInline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $downloadName) . '"');
    echo $blobContent;
    exit;
}

setFlash('error', t('common.file_not_available'));
if ($role === 'employee') {
    redirectTo('my-space');
}
redirectTo('dashboard', ['modal' => 'documents']);