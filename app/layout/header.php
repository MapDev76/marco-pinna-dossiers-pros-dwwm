<?php
// Bandeau partagé de l'application : logo centré et actions contextuelles.
$route = $route ?? ($_GET['route'] ?? 'home');
$currentUser = currentUser();
$isPublicPage = in_array($route, ['home', 'login'], true);
$basePath = $basePath ?? (function () {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
})();

$headerLeft = '';
if (!$isPublicPage && $currentUser !== null) {
    $role = $currentUser['role'] ?? 'employee';
    $displayName = trim((string) (($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')));
    $displayLabel = $displayName !== '' ? $displayName : 'StaffEase Pro';

    if ($role === 'super_admin') {
        $headerLeft = [
            'title' => 'StaffEase Pro',
            'subtitle' => trim('Super Admin - ' . $displayLabel),
        ];
    } else {
        $companyName = 'StaffEase Pro';

        if (!empty($currentUser['department_id'])) {
            try {
                $pdo = getPDO();
                $statement = $pdo->prepare(
                    'SELECT c.name AS company_name
                     FROM departments d
                     LEFT JOIN companies c ON c.id = d.company_id
                     WHERE d.id = :department_id
                     LIMIT 1'
                );
                $statement->execute(['department_id' => (int) $currentUser['department_id']]);
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                if (!empty($row['company_name'])) {
                    $companyName = (string) $row['company_name'];
                }
            } catch (Throwable $e) {
                $companyName = 'StaffEase Pro';
            }
        }

        $headerLeft = [
            'title' => $displayLabel,
            'subtitle' => trim($companyName . ' - ' . ucfirst((string) $role)),
        ];
    }
}

$rightIcons = [];
if ($route === 'home') {
    $rightIcons[] = [
        'type' => 'link',
        'href' => appUrl('login'),
        'title' => 'Login',
        'icon' => 'log-in.svg',
        'alt' => 'Login',
    ];
} elseif ($route === 'login') {
    $rightIcons[] = [
        'type' => 'link',
        'href' => appUrl('home'),
        'title' => 'Home',
        'icon' => 'home.svg',
        'alt' => 'Home',
    ];
} elseif ($currentUser !== null) {
    $role = $currentUser['role'] ?? 'employee';
    $rightIcons[] = [
        'type' => 'link',
        'href' => appUrl('home'),
        'title' => 'Home',
        'icon' => 'home.svg',
        'alt' => 'Home',
    ];
    $rightIcons[] = [
        'type' => 'button',
        'title' => 'Settings',
        'target' => 'modal-settings',
        'icon' => 'setting.svg',
        'alt' => 'Settings',
    ];
    $rightIcons[] = [
        'type' => 'button',
        'title' => 'Documents',
        'target' => 'crud-modal',
        'entity' => 'documents',
        'icon' => 'document.svg',
        'alt' => 'Documents',
    ];
    $rightIcons[] = [
        'type' => 'button',
        'title' => 'Print',
        'target' => 'modal-print',
        'icon' => 'print-outline.svg',
        'alt' => 'Print',
    ];
}
?>
<header class="site-header">
    <nav class="site-navbar" aria-label="Primary navigation">
        <div class="site-navbar-inner">
            <div class="site-navbar-left">
                <?php if (is_array($headerLeft)): ?>
                    <div class="site-header-meta">
                        <div class="site-header-title"><?php echo e($headerLeft['title']); ?></div>
                        <div class="site-header-subtitle"><?php echo e($headerLeft['subtitle']); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="site-navbar-center">
                <a href="<?php echo $isPublicPage ? appUrl('home') : appUrl('dashboard'); ?>" class="site-brand-link" aria-label="StaffEase Pro">
                    <img src="<?php echo $basePath; ?>/assets/images/LogoStaffeasePro.jpg" alt="StaffEase Pro" class="site-brand-logo">
                </a>
            </div>

            <div class="site-navbar-right">
                <div class="site-icon-group" role="toolbar" aria-label="Quick actions">
                    <?php foreach ($rightIcons as $iconItem): ?>
                        <?php if (($iconItem['type'] ?? 'link') === 'button'): ?>
                            <button type="button" class="site-icon-btn" title="<?php echo e($iconItem['title']); ?>" data-modal-target="<?php echo e($iconItem['target']); ?>">
                                <img src="<?php echo $basePath; ?>/assets/icons/<?php echo e($iconItem['icon']); ?>" alt="<?php echo e($iconItem['alt']); ?>" class="site-icon">
                            </button>
                        <?php else: ?>
                            <a href="<?php echo e($iconItem['href']); ?>" class="site-icon-btn" title="<?php echo e($iconItem['title']); ?>">
                                <img src="<?php echo $basePath; ?>/assets/icons/<?php echo e($iconItem['icon']); ?>" alt="<?php echo e($iconItem['alt']); ?>" class="site-icon">
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </nav>
</header>
