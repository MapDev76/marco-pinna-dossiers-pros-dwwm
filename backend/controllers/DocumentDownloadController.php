<?php

if (!isLoggedIn()) {
    setFlash('error', 'Please log in to continue.');
    redirectTo('login');
}

$pdo = getPDO();
$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';
$documentId = (int) ($_GET['id'] ?? 0);

if ($documentId <= 0) {
    setFlash('error', 'Document not found.');
    redirectTo('dashboard');
}

$statement = $pdo->prepare(
    'SELECT d.id, d.user_id, d.file_name, d.file_path, u.department_id, dep.company_id
     FROM documents d
     INNER JOIN users u ON u.id = d.user_id
     LEFT JOIN departments dep ON dep.id = u.department_id
     WHERE d.id = :id
     LIMIT 1'
);
$statement->execute(['id' => $documentId]);
$document = $statement->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    setFlash('error', 'Document not found.');
    redirectTo('dashboard');
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
}

if (!$allowed) {
    setFlash('error', 'Access denied.');
    redirectTo('dashboard');
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

if ($resolvedPath === null) {
    setFlash('error', 'File not available.');
    redirectTo('dashboard', ['modal' => 'documents']);
}

$downloadName = basename((string) ($document['file_name'] ?? basename($resolvedPath)));
$mimeType = mime_content_type($resolvedPath) ?: 'application/octet-stream';

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($resolvedPath));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
readfile($resolvedPath);
exit;