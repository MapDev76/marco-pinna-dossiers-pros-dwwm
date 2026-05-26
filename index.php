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
        <div class="flash flash-success"><?php echo e($flashSuccess); ?></div>
<?php endif; ?>

<?php if ($flashError !== null): ?>
        <div class="flash flash-error"><?php echo e($flashError); ?></div>
<?php endif; ?>

<?php
// Vue finale déterminée par le routeur.
require $viewFile;
?>
</main>

</body>
</html>