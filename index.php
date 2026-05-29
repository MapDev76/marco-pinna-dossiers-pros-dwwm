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
<?php require __DIR__ . '/app/layout/settings.php'; ?>
<?php require __DIR__ . '/app/layout/schedule.php'; ?>
<?php endif; ?>

<main class="content<?php echo $isDashboardRoute ? ' content-dashboard' : ''; ?>">
<?php if ($flashSuccess !== null): ?>
                <div id="flash-backdrop-success" class="flash-backdrop"></div>
                <div id="flash-success" class="flash flash-success" role="alert" aria-live="assertive">
                        <button class="flash-close" aria-label="close message">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true"><path d="M18.3 5.71L12 12l6.3 6.29-1.41 1.42L10.59 13.41 4.29 19.71 2.88 18.29 9.18 12 2.88 5.71 4.29 4.29 10.59 10.59 17.89 4.29z"/></svg>
                        </button>
                                <span class="flash-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                                                <path class="triangle" d="M1 21h22L12 2 1 21z" />
                                                <path class="exclam" d="M12 8v5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <circle class="exclam" cx="12" cy="17" r="1.2" />
                                        </svg>
                                </span>
                        <div class="flash-body">
                                <div class="flash-title">Congratulations!</div>
                                <p><?php echo e($flashSuccess); ?></p>
                        </div>
                </div>
<?php endif; ?>

<?php if ($flashError !== null): ?>
                <div id="flash-backdrop-error" class="flash-backdrop"></div>
                <div id="flash-error" class="flash flash-error" role="alert" aria-live="assertive">
                        <button class="flash-close" aria-label="Chiudi messaggio">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true"><path d="M18.3 5.71L12 12l6.3 6.29-1.41 1.42L10.59 13.41 4.29 19.71 2.88 18.29 9.18 12 2.88 5.71 4.29 4.29 10.59 10.59 17.89 4.29z"/></svg>
                        </button>
                        <span class="flash-icon" aria-hidden="true">
                                <img src="<?php echo $basePath; ?>/assets/icons/Icon%20alert.png" alt="" />
                        </span>
                        <div class="flash-body">
                                <div class="flash-title">Oops!</div>
                                <p><?php echo e($flashError); ?></p>
                        </div>
                </div>
<?php endif; ?>

<?php
// Vue finale déterminée par le routeur.
require $viewFile;
?>
</main>

<?php if ($isDashboardRoute && isLoggedIn()): ?>
<?php require __DIR__ . '/app/layout/crud-modal.php'; ?>
<?php endif; ?>

<script src="<?php echo $basePath; ?>/assets/js/api.js"></script>
<?php if ($isDashboardRoute && isLoggedIn()): ?>
<script>
        window.DashboardConfig = {
                apiCompanies: '<?php echo appUrl('api-companies'); ?>',
                apiDepartments: '<?php echo appUrl('api-departments'); ?>',
                apiUsers: '<?php echo appUrl('api-users'); ?>'
        };
</script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard.js"></script>
<?php endif; ?>

</body>
</html>