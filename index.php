<?php
require_once __DIR__ . '/backend/bootstrap.php';

// Front controller entry point: resolves route, loads controller/view, then renders shared layout.
$route = appRouteFromRequest();
$_GET['route'] = $route;
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
$isCommercialRoute = $route === 'commercial';
$isLoginRoute = $route === 'login';
$isMySpaceRoute = $route === 'my-space';
$isLegalRoute = $route === 'legal';
$isContactsRoute = $route === 'contacts';
$isCreatorRoute = $route === 'creator';
$isStaticInfoRoute = $isLegalRoute || $isContactsRoute || $isCreatorRoute;
$isPublicRoute = $isHomeRoute || $isCommercialRoute || $isLoginRoute || $isStaticInfoRoute;
$locale = appLocale();
$shouldShowLoadingOverlay = $isDashboardRoute || $isMySpaceRoute;
$requiresApiClient = ($isDashboardRoute || $isMySpaceRoute) && isLoggedIn();
$hasFlashUi = $flashSuccess !== null || $flashError !== null || (($loginError ?? null) !== null);
$useFullAppStyles = $isDashboardRoute || $isMySpaceRoute;
// Compact dashboard mode: removes padding and some UI elements for admins/managers to show more content.
$isCompactDashboard = $isDashboardRoute && isLoggedIn() && in_array((currentUser()['role'] ?? ''), ['admin', 'department_manager'], true);
$bodyClasses = [];
if ($isDashboardRoute) {
        $bodyClasses[] = 'route-dashboard';
}
if ($isHomeRoute) {
        $bodyClasses[] = 'route-home';
}
if ($isCommercialRoute) {
        $bodyClasses[] = 'route-commercial';
}
if ($isLoginRoute) {
        $bodyClasses[] = 'route-login';
}
if ($isMySpaceRoute) {
        $bodyClasses[] = 'route-my-space';
}
if ($isStaticInfoRoute) {
        $bodyClasses[] = 'route-legal';
}
if ($isPublicRoute) {
        $bodyClasses[] = 'route-public';
}
// Precompute CSS version based on file modification time for cache busting. If the file is missing, use current time to avoid caching issues during development.
$stylesheetBaseFile = $useFullAppStyles ? 'style.css' : 'public.css';
$stylesheetMinFile = $useFullAppStyles ? 'style.min.css' : 'public.min.css';
$stylesheetFile = is_file(__DIR__ . '/assets/css/' . $stylesheetMinFile) ? $stylesheetMinFile : $stylesheetBaseFile;
$cssVersion = (string) (@filemtime(__DIR__ . '/assets/css/' . $stylesheetFile) ?: time());

$routeMeta = [
        'home' => [
                'title' => 'StaffEase Pro | Workforce Management Platform',
                'description' => 'StaffEase Pro centralises planning, attendance, documents and internal communication for your teams.',
        ],
        'commercial' => [
                'title' => 'StaffEase Pro | Commercial Overview',
                'description' => 'Discover StaffEase Pro features for scheduling, attendance, documents and daily team operations.',
        ],
        'contacts' => [
                'title' => 'StaffEase Pro | Contact',
                'description' => 'Contact StaffEase Pro for demos, support and account creation.',
        ],
        'legal' => [
                'title' => 'StaffEase Pro | Legal',
                'description' => 'Legal mentions and regulatory information for StaffEase Pro.',
        ],
        'creator' => [
                'title' => 'StaffEase Pro | Creator',
                'description' => 'Meet the creator behind StaffEase Pro and learn about the platform vision.',
        ],
        'login' => [
                'title' => 'StaffEase Pro | Login',
                'description' => 'Secure access to your StaffEase Pro workspace.',
        ],
        'dashboard' => [
                'title' => 'StaffEase Pro | Dashboard',
                'description' => 'Manage teams, departments and shift planning from the StaffEase Pro dashboard.',
        ],
        'my-space' => [
                'title' => 'StaffEase Pro | Employee Space',
                'description' => 'Employee area for attendance, documents and personal shift information.',
        ],
];

$resolvedMeta = $routeMeta[$route] ?? [
        'title' => $pageTitle,
        'description' => 'StaffEase Pro workforce management application.',
];

$pageTitle = $pageTitle !== 'StaffEase Pro' ? $pageTitle : $resolvedMeta['title'];
$metaDescription = $resolvedMeta['description'];
$canonicalPath = appCurrentUrl(['lang' => null]);
$requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$requestHost = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$canonicalUrl = str_starts_with($canonicalPath, 'http://') || str_starts_with($canonicalPath, 'https://')
        ? $canonicalPath
        : ($requestScheme . '://' . $requestHost . $canonicalPath);
$isPrivateRoute = $isDashboardRoute || $isMySpaceRoute;
$robotsContent = $isPrivateRoute || $isLoginRoute ? 'noindex, nofollow' : 'index, follow';

$apiFile = __DIR__ . '/assets/js/api.js';
$apiVersion = (string) (@filemtime($apiFile) ?: time());

$publicUiFile = __DIR__ . '/assets/js/public-ui.js';
$publicUiVersion = (string) (@filemtime($publicUiFile) ?: time());

$uiHintsFile = __DIR__ . '/assets/js/ui-hints.js';
$uiHintsVersion = (string) (@filemtime($uiHintsFile) ?: time());

$dashboardBundleFile = __DIR__ . '/assets/js/dashboard.bundle.min.js';
$hasDashboardBundle = is_file($dashboardBundleFile);
$dashboardBundleVersion = (string) (@filemtime($dashboardBundleFile) ?: time());

$employeeSpaceFile = __DIR__ . '/assets/js/employee-space.js';
$employeeSpaceVersion = (string) (@filemtime($employeeSpaceFile) ?: time());
?>
<!DOCTYPE html>
<html lang="<?php echo e($locale); ?>">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta name="description" content="<?php echo e($metaDescription); ?>">
                <meta name="robots" content="<?php echo e($robotsContent); ?>">
                <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
                <meta property="og:title" content="<?php echo e($pageTitle); ?>">
                <meta property="og:description" content="<?php echo e($metaDescription); ?>">
                <meta property="og:type" content="website">
                <meta property="og:url" content="<?php echo e($canonicalUrl); ?>">
                <title><?php echo e($pageTitle); ?></title>
        <link rel="icon" href="<?php echo $basePath; ?>/assets/images/faviconStaffeasePro.jpg" type="image/jpeg">
                <link rel="preload" href="<?php echo $basePath; ?>/assets/images/LogoStaffeasePro.png" as="image" fetchpriority="high">
                <link rel="preload" href="<?php echo $basePath; ?>/assets/css/<?php echo e($stylesheetFile); ?>?v=<?php echo e($cssVersion); ?>" as="style">
                <?php if ($isDashboardRoute && $hasDashboardBundle): ?>
                <link rel="preload" href="<?php echo $basePath; ?>/assets/js/dashboard.bundle.min.js?v=<?php echo e($dashboardBundleVersion); ?>" as="script">
                <?php endif; ?>
        <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/<?php echo e($stylesheetFile); ?>?v=<?php echo e($cssVersion); ?>">
                <?php if ($hasFlashUi): ?>
                <script defer src="<?php echo $basePath; ?>/assets/js/flash.js?v=<?php echo filemtime(__DIR__ . '/assets/js/flash.js'); ?>"></script>
                <?php endif; ?>
</head>
<body class="<?php echo e(implode(' ', $bodyClasses)); ?>">
        <?php if ($shouldShowLoadingOverlay): ?>
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
        <?php endif; ?>

<a class="skip-link" href="#main-content"><?php echo e(t('common.quick_actions')); ?> - Skip to content</a>

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

<main id="main-content" class="<?php echo $isCompactDashboard ? 'dashboard-content' : 'content' . ($isDashboardRoute ? ' content-dashboard' : ''); ?>" tabindex="-1">
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

<?php if ($isPublicRoute): ?>
<footer class="site-reveal-footer" data-reveal-footer aria-label="<?php echo e(t('common.quick_actions')); ?>">
        <div class="site-reveal-footer-inner">
                <a class="site-reveal-footer-link" href="<?php echo e(appUrl('legal')); ?>"><?php echo e(t('common.legal_mentions')); ?></a>
                <a class="site-reveal-footer-link" href="<?php echo e(appUrl('commercial')); ?>"><?php echo e(t('common.commercial')); ?></a>
                <a class="site-reveal-footer-link" href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('common.contacts')); ?></a>
                <a class="site-reveal-footer-link" href="<?php echo e(appUrl('creator')); ?>"><?php echo e(t('common.app_creator')); ?></a>
        </div>
</footer>
<?php endif; ?>

<?php if ($isDashboardRoute && isLoggedIn()): ?>
<?php require __DIR__ . '/app/layout/crud-modal.php'; ?>
<?php endif; ?>

<?php if ($requiresApiClient): ?>
<script defer src="<?php echo $basePath; ?>/assets/js/api.js?v=<?php echo e($apiVersion); ?>"></script>
<?php endif; ?>
<?php if ($isDashboardRoute || $isMySpaceRoute): ?>
<script defer src="<?php echo $basePath; ?>/assets/js/signature-pad.js?v=<?php echo filemtime(__DIR__ . '/assets/js/signature-pad.js'); ?>"></script>
<?php endif; ?>
<?php if ($isDashboardRoute && isLoggedIn()): ?>
<script>
        window.DashboardConfig = {
                apiCompanies: '<?php echo appUrl('api-companies'); ?>',
                apiDepartments: '<?php echo appUrl('api-departments'); ?>',
                apiUsers: '<?php echo appUrl('api-users'); ?>',
                apiDashboard: '<?php echo appUrl('api-dashboard'); ?>',
                apiShifts: '<?php echo appUrl('api-shifts'); ?>',
                iconsBase: '<?php echo $basePath; ?>/assets/icons/',
                pdfjsLibSrc: '<?php echo $basePath; ?>/assets/js/vendor/pdfjs/pdf.min.js?v=<?php echo filemtime(__DIR__ . '/assets/js/vendor/pdfjs/pdf.min.js'); ?>',
                pdfjsWorkerSrc: '<?php echo $basePath; ?>/assets/js/vendor/pdfjs/pdf.worker.min.js'
        };
        window.DashboardCurrentUser = <?php echo json_encode([
                'id' => (int) (currentUser()['id'] ?? 0),
                'name' => trim((string) ((currentUser()['first_name'] ?? '') . ' ' . (currentUser()['last_name'] ?? ''))),
                'role' => (string) (currentUser()['role'] ?? ''),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.DashboardPlannerData = <?php echo json_encode($dashboardPlannerData ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<?php if ($hasDashboardBundle): ?>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard.bundle.min.js?v=<?php echo e($dashboardBundleVersion); ?>"></script>
<?php else: ?>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/sidebar.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/sidebar.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/navigator.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/navigator.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/calendar-renderer.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/calendar-renderer.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/calendar.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/calendar.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/feedback.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/feedback.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/dnd.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/dnd.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/departments.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/departments.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/users.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/users.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/shifts.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/shifts.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/companies.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/companies.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/attendances.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/attendances.js'); ?>"></script>
<script defer src="<?php echo $basePath; ?>/assets/js/dashboard/print.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard/print.js'); ?>"></script>
<?php endif; ?>
<?php endif; ?>

<?php if ($isMySpaceRoute && isLoggedIn()): ?>
<script defer src="<?php echo $basePath; ?>/assets/js/employee-space.js?v=<?php echo e($employeeSpaceVersion); ?>"></script>
<?php endif; ?>

<script defer src="<?php echo $basePath; ?>/assets/js/public-ui.js?v=<?php echo e($publicUiVersion); ?>"></script>

<script defer src="<?php echo $basePath; ?>/assets/js/ui-hints.js?v=<?php echo e($uiHintsVersion); ?>"></script>

</body>
</html>