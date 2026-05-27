<?php
require_once __DIR__ . '/backend/bootstrap.php';

// Point d'entrée frontal : lit la route, charge le contrôleur ou la vue, puis affiche la mise en page commune.
$route = $_GET['route'] ?? 'home';
$targetFile = require __DIR__ . '/app/router.php';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

if (str_starts_with($targetFile, __DIR__ . '/backend/controllers/')) {
        require $targetFile;
} else {
        $viewFile = $targetFile;
}

$pageTitle = $pageTitle ?? 'StaffEase Pro';
$viewFile = $viewFile ?? $targetFile;
$flashSuccess = getFlash('success');
$flashError = getFlash('error');
$isDashboardRoute = $route === 'dashboard';
$cssVersion = (string) (@filemtime(__DIR__ . '/assets/css/style.css') ?: time());
?>
<!DOCTYPE html>
<html lang="en">
<head>
        <!-- Métadonnées de base et feuille de style principale -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?php echo e($pageTitle); ?></title>
        <link rel="icon" href="<?php echo $basePath; ?>/assets/images/faviconStaffeasePro.jpg" type="image/jpeg">
        <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css?v=<?php echo e($cssVersion); ?>">
        <script defer src="<?php echo $basePath; ?>/assets/js/flash.js?v=<?php echo filemtime(__DIR__ . '/assets/js/flash.js'); ?>"></script>
</head>
<body class="<?php echo $isDashboardRoute ? 'route-dashboard' : ''; ?>">

<?php
// En-tête partagé utilisé par toutes les pages.
require __DIR__ . '/app/layout/header.php';
?>

<?php if ($isDashboardRoute && isLoggedIn()): ?>
<?php require __DIR__ . '/app/layout/sidebar.php'; ?>
<?php require __DIR__ . '/app/layout/documents.php'; ?>
<?php require __DIR__ . '/app/layout/settings.php'; ?>
<?php require __DIR__ . '/app/layout/schedule.php'; ?>
<?php endif; ?>

<main class="content<?php echo $isDashboardRoute ? ' content-dashboard' : ''; ?>">
<?php if ($flashSuccess !== null): ?>
                <div id="flash-backdrop-success" class="flash-backdrop"></div>
                <div id="flash-success" class="flash flash-success" role="alert" aria-live="assertive">
                        <button class="flash-close" aria-label="Chiudi messaggio">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true"><path d="M18.3 5.71L12 12l6.3 6.29-1.41 1.42L10.59 13.41 4.29 19.71 2.88 18.29 9.18 12 2.88 5.71 4.29 4.29 10.59 10.59 17.89 4.29z"/></svg>
                        </button>
                        <span class="flash-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                                        <path d="M2 21h4V9H2v12zM22 10c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 2 9 7.17V19h9c.82 0 1.54-.5 1.84-1.25L23 12.5c.09-.23.14-.47.14-.72v-1.78c0-.55-.45-1-1-1z" />
                                </svg>
                        </span>
                        <div class="flash-body"><?php echo e($flashSuccess); ?></div>
                </div>
<?php endif; ?>

<?php if ($flashError !== null): ?>
                <div id="flash-backdrop-error" class="flash-backdrop"></div>
                <div id="flash-error" class="flash flash-error" role="alert" aria-live="assertive">
                        <button class="flash-close" aria-label="Chiudi messaggio">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true"><path d="M18.3 5.71L12 12l6.3 6.29-1.41 1.42L10.59 13.41 4.29 19.71 2.88 18.29 9.18 12 2.88 5.71 4.29 4.29 10.59 10.59 17.89 4.29z"/></svg>
                        </button>
                        <span class="flash-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm3.54 13.54L13.41 12l2.13-3.54L15.13 7 12 10.13 8.87 7 7.46 8.46 9.59 12 7.46 15.54 8.87 17 12 13.87 15.13 17z" />
                                </svg>
                        </span>
                        <div class="flash-body"><?php echo e($flashError); ?></div>
                </div>
<?php endif; ?>

<?php
// Vue finale déterminée par le routeur.
require $viewFile;
?>
</main>

</body>
</html>