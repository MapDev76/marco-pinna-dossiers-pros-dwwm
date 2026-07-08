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
$pdfStreamMode = in_array(strtolower(trim((string) ($_GET['pdf_stream'] ?? ''))), ['1', 'true', 'yes', 'on'], true);
$embeddedPreview = in_array(strtolower(trim((string) ($_GET['embed'] ?? ''))), ['1', 'true', 'yes', 'on'], true);
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
    markRequestReadAndNotifySender($pdo, $requestId, $currentUserId, $currentUser, $documentId);
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
$pdfPreviewStreamUrl = appUrl('document-download', [
    'id' => $documentId,
    'from' => $redirectRoute,
    'preview' => '1',
    'pdf_stream' => '1',
    'embed' => $embeddedPreview ? '1' : null,
]);

$renderDocumentPreview = static function (string $content, string $mimeType, string $documentName, bool $autoPrint, string $downloadHref, string $pdfStreamHref = '', bool $embeddedPreview = false): never {
    $safeName = htmlspecialchars($documentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $normalizedMime = strtolower(trim($mimeType));
    $safeDownloadHref = htmlspecialchars($downloadHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $pdfStreamHrefRaw = trim((string) $pdfStreamHref);
    $safePdfStreamHref = htmlspecialchars($pdfStreamHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $bodyHtml = '';
    $extraScriptHtml = '';

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
        if ($pdfStreamHrefRaw !== '') {
            $bodyHtml = '<div class="print-preview-pdf-shell">'
                . '<canvas class="print-preview-pdf-canvas" id="print-preview-pdf-canvas"></canvas>'
                . '<p class="print-preview-pdf-error" id="print-preview-pdf-error" hidden>PDF preview is not available in this browser. Use Download file.</p>'
                . '</div>';

            $extraScriptHtml = '<script src="/assets/js/vendor/pdfjs/pdf.min.js"></script>'
                . '<script>(function(){'
                . 'const streamUrl=' . json_encode($pdfStreamHrefRaw) . ';'
                . 'const canvas=document.getElementById("print-preview-pdf-canvas");'
                . 'const errorNode=document.getElementById("print-preview-pdf-error");'
                . 'if(!streamUrl||!canvas){if(errorNode){errorNode.hidden=false;}return;}'
                . 'if(!window.pdfjsLib||typeof window.pdfjsLib.getDocument!=="function"){if(errorNode){errorNode.hidden=false;}return;}'
                . 'try{window.pdfjsLib.GlobalWorkerOptions.workerSrc="/assets/js/vendor/pdfjs/pdf.worker.min.js";}catch(_e){}'
                . 'const ctx=canvas.getContext("2d");'
                . 'const parsePage=function(){const hash=String(window.location.hash||"");const m=hash.match(/page=(\\d+)/i);return m?Math.max(1,parseInt(m[1],10)||1):1;};'
                . 'const render=function(){'
                . 'const wantedPage=parsePage();'
                . 'window.pdfjsLib.getDocument({url:streamUrl,withCredentials:true}).promise.then(function(pdf){'
                . 'const pageNo=Math.min(Math.max(1,wantedPage),pdf.numPages||1);'
                . 'return pdf.getPage(pageNo);'
                . '}).then(function(page){'
                . 'const base=page.getViewport({scale:1});'
                . 'const maxWidth=Math.max(320,Math.min(1100,window.innerWidth-60));'
                . 'const scale=Math.max(0.2,maxWidth/base.width);'
                . 'const viewport=page.getViewport({scale:scale});'
                . 'const ratio=Math.max(window.devicePixelRatio||1,1);'
                . 'canvas.width=Math.floor(viewport.width*ratio);'
                . 'canvas.height=Math.floor(viewport.height*ratio);'
                . 'canvas.style.width=Math.floor(viewport.width)+"px";'
                . 'canvas.style.height=Math.floor(viewport.height)+"px";'
                . 'ctx.setTransform(ratio,0,0,ratio,0,0);'
                . 'ctx.fillStyle="#fff";ctx.fillRect(0,0,viewport.width,viewport.height);'
                . 'return page.render({canvasContext:ctx,viewport:viewport}).promise;'
                . '}).catch(function(){if(errorNode){errorNode.hidden=false;}});'
                . '};'
                . 'render();'
                . 'window.addEventListener("hashchange",render);'
                . 'window.addEventListener("resize",function(){clearTimeout(window.__pdfPrevResizeT);window.__pdfPrevResizeT=setTimeout(render,120);});'
                . '})();</script>';
        } else {
            $bodyHtml = '<p>PDF preview is not available in this browser. Use Download file.</p>';
        }
    } elseif (str_starts_with($normalizedMime, 'video/')) {
        $bodyHtml = '<video class="print-preview-media" controls><source src="data:' . htmlspecialchars($mimeType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';base64,' . base64_encode($content) . '"></video>';
    } elseif (str_starts_with($normalizedMime, 'audio/')) {
        $bodyHtml = '<audio class="print-preview-audio" controls><source src="data:' . htmlspecialchars($mimeType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';base64,' . base64_encode($content) . '"></audio>';
    } elseif (str_starts_with($normalizedMime, 'text/') || str_contains($normalizedMime, 'json') || str_contains($normalizedMime, 'xml') || str_contains($normalizedMime, 'csv')) {
        $bodyHtml = '<pre class="print-preview-text">' . htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
    } else {
        $bodyHtml = '<p>Preview is not available for this file type.</p>';
    }

    $actionsHtml = '';
    if (!$embeddedPreview) {
        $actionsHtml = '<div class="print-preview-actions">'
            . '<a class="print-preview-link" href="' . $safeDownloadHref . '">Download file</a>';
        if ($autoPrint) {
            $actionsHtml .= '<button type="button" class="print-preview-link" onclick="window.print()">Print now</button>';
        }
        $actionsHtml .= '</div>';
    }

    $scriptHtml = '';
    if ($autoPrint) {
        $scriptHtml = '<script>window.addEventListener("load", function(){ setTimeout(function(){ try { window.print(); } catch(e) {} }, 160); });</script>';
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>' . ($autoPrint ? 'Print preview' : 'Document preview') . ' - ' . $safeName . '</title>'
        . '<style>body{margin:0;font-family:Arial,sans-serif;background:#f7f7f7;color:#111;}main{max-width:1100px;margin:0 auto;padding:16px;}h1{margin:0 0 12px;font-size:20px;} .print-preview-actions{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 14px;} .print-preview-link{display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;border:1px solid #c5c5c5;border-radius:8px;background:#fff;color:#111;text-decoration:none;cursor:pointer;font-size:14px;} .print-preview-link:hover{background:#f4f4f4;} .print-preview-frame{width:100%;height:80vh;border:1px solid #d5d5d5;border-radius:8px;background:#fff;} .print-preview-image{max-width:100%;height:auto;border:1px solid #d5d5d5;border-radius:8px;background:#fff;} .print-preview-media{width:100%;max-height:75vh;border:1px solid #d5d5d5;border-radius:8px;background:#111;} .print-preview-audio{width:100%;} .print-preview-text{white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid #d5d5d5;border-radius:8px;padding:12px;min-height:60vh;} .print-preview-pdf-shell{border:1px solid #d5d5d5;border-radius:8px;background:#fff;overflow:auto;padding:10px;} .print-preview-pdf-canvas{display:block;max-width:100%;height:auto;margin:0 auto;} .print-preview-pdf-error{margin:8px 0 0;color:#8b1f1f;font-weight:600;} body.embed-preview{background:#fff;} body.embed-preview main{max-width:none;padding:0;margin:0;} body.embed-preview h1, body.embed-preview .print-preview-actions{display:none;} body.embed-preview .print-preview-frame{height:100vh;border:0;border-radius:0;} body.embed-preview .print-preview-image, body.embed-preview .print-preview-text, body.embed-preview .print-preview-media, body.embed-preview .print-preview-pdf-shell{border:0;border-radius:0;} @media print{body{background:#fff;}main{max-width:none;padding:0;}h1,.print-preview-actions{display:none;} .print-preview-frame{height:100vh;border:0;} .print-preview-text,.print-preview-image,.print-preview-media,.print-preview-pdf-shell{border:0;}}</style>'
        . '</head><body class="' . ($embeddedPreview ? 'embed-preview' : '') . '"><main><h1>' . $safeName . '</h1>' . $actionsHtml . $bodyHtml . '</main>' . $extraScriptHtml . $scriptHtml . '</body></html>';
    exit;
};

if ($resolvedPath !== null) {
    $mimeType = mime_content_type($resolvedPath) ?: 'application/octet-stream';
    if ($previewMode || $printPreview) {
        $previewContent = @file_get_contents($resolvedPath);
        if (is_string($previewContent)) {
            if ($pdfStreamMode && str_contains(strtolower($mimeType), 'pdf')) {
                header('Content-Type: application/pdf');
                header('Content-Length: ' . (string) strlen($previewContent));
                header('Content-Disposition: inline; filename="' . str_replace('"', '', $downloadName) . '"');
                echo $previewContent;
                exit;
            }
            $renderDocumentPreview($previewContent, $mimeType, $downloadName, $printPreview, $downloadUrl, $pdfPreviewStreamUrl, $embeddedPreview);
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
        if ($pdfStreamMode && str_contains(strtolower($mimeType), 'pdf')) {
            header('Content-Type: application/pdf');
            header('Content-Length: ' . (string) strlen($blobContent));
            header('Content-Disposition: inline; filename="' . str_replace('"', '', $downloadName) . '"');
            echo $blobContent;
            exit;
        }
        $renderDocumentPreview($blobContent, $mimeType, $downloadName, $printPreview, $downloadUrl, $pdfPreviewStreamUrl, $embeddedPreview);
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