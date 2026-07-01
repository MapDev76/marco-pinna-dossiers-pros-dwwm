<?php
require_once __DIR__ . '/backend/bootstrap.php';

// Front controller entry point: resolves route, loads controller/view, then renders shared layout.
$route = $_GET['route'] ?? 'home';
$targetFile = require __DIR__ . '/app/router.php';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

if (str_starts_with($targetFile, __DIR__ . '/backend/controllers/')) {
        require $targetFile;
} else {
        $viewFile = $targetFile;
}
// Controllers can set $viewFile to override the default view for the route.
$pageTitle = $pageTitle ?? 'StaffEase Pro';
$viewFile = $viewFile ?? $targetFile;
$flashSuccess = getFlash('success');
$flashError = getFlash('error');
$isDashboardRoute = $route === 'dashboard';
$isHomeRoute = $route === 'home';
$isLoginRoute = $route === 'login';
$isMySpaceRoute = $route === 'my-space';
$isLegalRoute = $route === 'legal';
$locale = appLocale();
// Compact dashboard mode: removes padding and some UI elements for admins/managers to show more content.
$isCompactDashboard = $isDashboardRoute && isLoggedIn() && in_array((currentUser()['role'] ?? ''), ['admin', 'department_manager'], true);
$bodyClasses = [];
if ($isDashboardRoute) {
        $bodyClasses[] = 'route-dashboard';
}
if ($isHomeRoute) {
        $bodyClasses[] = 'route-home';
}
if ($isLoginRoute) {
        $bodyClasses[] = 'route-login';
}
if ($isMySpaceRoute) {
        $bodyClasses[] = 'route-my-space';
}
if ($isLegalRoute) {
        $bodyClasses[] = 'route-legal';
}
// Precompute CSS version based on file modification time for cache busting. If the file is missing, use current time to avoid caching issues during development.
$cssVersion = (string) (@filemtime(__DIR__ . '/assets/css/style.css') ?: time());
?>
<!DOCTYPE html>
<html lang="<?php echo e($locale); ?>">
<head>
         <meta name="description" content="StaffEase Pro - Manage your staff efficiently">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?php echo e($pageTitle); ?></title>
        <link rel="icon" href="<?php echo $basePath; ?>/assets/images/faviconStaffeasePro.jpg" type="image/jpeg">
        <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css?v=<?php echo e($cssVersion); ?>">
        <script defer src="<?php echo $basePath; ?>/assets/js/flash.js?v=<?php echo filemtime(__DIR__ . '/assets/js/flash.js'); ?>"></script>
</head>
<body class="<?php echo e(implode(' ', $bodyClasses)); ?>">
    <div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.9); display: flex; justify-content: center; align-items: center; z-index: 9999;">
        <div style="text-align: center;">
            <img src="<?php echo $basePath; ?>/assets/icons/loader-circle.svg" alt="Loading..." style="width: 50px; height: 50px; animation: spin 1s linear infinite; display: block; margin: 0 auto;">
            <p style="margin-top: 10px; font-size: 16px; color: #333;"><?php echo e(t('loading.message')); ?></p>
        </div>
    </div>
    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
    <script>
        window.addEventListener('load', function() {
            document.getElementById('loading-overlay').style.display = 'none';
        });
    </script>

<?php
// Shared header used by all pages.
require __DIR__ . '/app/layout/header.php';
?>

<?php if ($isDashboardRoute && isLoggedIn()): ?>
<?php if ((currentUser()['role'] ?? '') !== 'super_admin'): ?>
<?php require __DIR__ . '/app/layout/sidebar.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/app/layout/settings-panel.php'; ?>
<?php require __DIR__ . '/app/layout/schedule.php'; ?>
<?php require __DIR__ . '/app/layout/print-modal.php'; ?>
<?php endif; ?>

<main class="<?php echo $isCompactDashboard ? 'dashboard-content' : 'content' . ($isDashboardRoute ? ' content-dashboard' : ''); ?>">
<?php if ($flashSuccess !== null): ?>
                <div id="flash-backdrop-success" class="flash-backdrop"></div>
                <div id="flash-success" class="flash flash-success" role="alert" aria-live="assertive">
                                <span class="flash-icon" aria-hidden="true">
                                        <img src="<?php echo $basePath; ?>/assets/icons/alert-circle.svg" alt="" aria-hidden="true" />
                                </span>
                        <div class="flash-body">
                                <div class="flash-title"><?php echo e(t('common.done')); ?></div>
                                <p><?php echo e($flashSuccess); ?></p>
                        </div>
                </div>
<?php endif; ?>

<?php if ($flashError !== null): ?>
                <div id="flash-backdrop-error" class="flash-backdrop"></div>
                <div id="flash-error" class="flash flash-error" role="alert" aria-live="assertive">
                        <span class="flash-icon" aria-hidden="true">
                                <img src="<?php echo $basePath; ?>/assets/icons/alert-circle.svg" alt="" aria-hidden="true" />
                        </span>
                        <div class="flash-body">
                                <div class="flash-title"><?php echo e(t('common.oops')); ?></div>
                                <p><?php echo e($flashError); ?></p>
                        </div>
                </div>
<?php endif; ?>

<?php
// Final view resolved by the router.
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
                apiUsers: '<?php echo appUrl('api-users'); ?>',
                apiDashboard: '<?php echo appUrl('api-dashboard'); ?>',
                apiShifts: '<?php echo appUrl('api-shifts'); ?>',
                iconsBase: '<?php echo $basePath; ?>/assets/icons/'
        };
        window.DashboardCurrentUser = <?php echo json_encode([
                'id' => (int) (currentUser()['id'] ?? 0),
                'name' => trim((string) ((currentUser()['first_name'] ?? '') . ' ' . (currentUser()['last_name'] ?? ''))),
                'role' => (string) (currentUser()['role'] ?? ''),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.DashboardPlannerData = <?php echo json_encode($dashboardPlannerData ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/sidebar.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/sidebar.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/navigator.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/navigator.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/calendar-renderer.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/calendar-renderer.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/calendar.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/calendar.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/feedback.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/feedback.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/dnd.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/dnd.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/departments.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/departments.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/users.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/users.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/shifts.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/shifts.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/companies.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/companies.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/attendances.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/attendances.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/print.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/print.js'); ?>"></script>
<?php endif; ?>

<?php if ($isMySpaceRoute && isLoggedIn()): ?>
<script src="<?php echo $basePath; ?>/assets/js/employee-space.js?v=<?php echo filemtime(__DIR__ . '/assets/js/employee-space.js'); ?>"></script>
<?php endif; ?>

<script src="<?php echo $basePath; ?>/assets/js/ui-hints.js?v=<?php echo filemtime(__DIR__ . '/assets/js/ui-hints.js'); ?>"></script>

</body>
</html>