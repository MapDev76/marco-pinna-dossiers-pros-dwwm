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

$pageTitle = $pageTitle ?? 'StaffEase Pro';
$viewFile = $viewFile ?? $targetFile;
$flashSuccess = getFlash('success');
$flashError = getFlash('error');
$isDashboardRoute = $route === 'dashboard';
$isHomeRoute = $route === 'home';
$isLoginRoute = $route === 'login';
$isMySpaceRoute = $route === 'my-space';
$locale = appLocale();
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
$cssVersion = (string) (@filemtime(__DIR__ . '/assets/css/style.css') ?: time());
?>
<!DOCTYPE html>
<html lang="<?php echo e($locale); ?>">
<head>
        <!-- Base metadata and main stylesheet -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?php echo e($pageTitle); ?></title>
        <link rel="icon" href="<?php echo $basePath; ?>/assets/images/faviconStaffeasePro.jpg" type="image/jpeg">
        <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css?v=<?php echo e($cssVersion); ?>">
        <script defer src="<?php echo $basePath; ?>/assets/js/flash.js?v=<?php echo filemtime(__DIR__ . '/assets/js/flash.js'); ?>"></script>
</head>
<body class="<?php echo e(implode(' ', $bodyClasses)); ?>">

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
                        <button class="flash-close" aria-label="<?php echo e(t('common.close_message')); ?>">
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
                                <div class="flash-title"><?php echo e(t('common.done')); ?></div>
                                <p><?php echo e($flashSuccess); ?></p>
                        </div>
                </div>
<?php endif; ?>

<?php if ($flashError !== null): ?>
                <div id="flash-backdrop-error" class="flash-backdrop"></div>
                <div id="flash-error" class="flash flash-error" role="alert" aria-live="assertive">
                        <button class="flash-close" aria-label="<?php echo e(t('common.close_message')); ?>">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true"><path d="M18.3 5.71L12 12l6.3 6.29-1.41 1.42L10.59 13.41 4.29 19.71 2.88 18.29 9.18 12 2.88 5.71 4.29 4.29 10.59 10.59 17.89 4.29z"/></svg>
                        </button>
                        <span class="flash-icon" aria-hidden="true">
                                <img src="<?php echo $basePath; ?>/assets/icons/Icon%20alert.png" alt="" />
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
                apiShifts: '<?php echo appUrl('api-shifts'); ?>'
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
<script src="<?php echo $basePath; ?>/assets/js/dashboard/assignments.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/assignments.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/attendances.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/attendances.js'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/js/dashboard/print.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/print.js'); ?>"></script>
<?php endif; ?>

<?php if ($isMySpaceRoute && isLoggedIn()): ?>
<script src="<?php echo $basePath; ?>/assets/js/employee-space.js?v=<?php echo filemtime(__DIR__ . '/assets/js/employee-space.js'); ?>"></script>
<?php endif; ?>

</body>
</html>