<?php
/**
 * Shared header bar for the application.
 *
 * Purpose: render the top navigation, brand/logo and contextual quick actions
 * (home, settings, documents, print). The header adapts based on the current
 * route and the logged-in user's role.
 *
 * Available variables:
 * - `$route` (string) : current route name, falls back to GET['route'] or 'home'
 * - `currentUser()` provides the authenticated user array when available
 *
 * Notes: keep UI strings consistent with the localization layer.
 */
$route = $route ?? ($_GET['route'] ?? 'home');
$currentUser = currentUser();
$locale = appLocale();
$isPublicPage = in_array($route, ['home', 'login'], true);
$currentRole = $currentUser['role'] ?? null;
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
            'title' => t('common.app_name'),
            'subtitle' => trim(t('roles.super_admin') . ' - ' . $displayLabel),
        ];
    } else {
        $companyName = t('common.app_name');

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
            'subtitle' => trim($companyName . ' - ' . t('roles.' . (string) $role)),
        ];
    }
}

$rightIcons = [];
if ($route === 'home') {
    $rightIcons[] = [
        'type' => 'link',
        'href' => appUrl('login'),
        'title' => t('common.login'),
        'icon' => 'log-in.svg',
        'alt' => t('common.login'),
    ];
} elseif ($route === 'login') {
    $rightIcons[] = [
        'type' => 'link',
        'href' => appUrl('home'),
        'title' => t('common.home'),
        'icon' => 'home.svg',
        'alt' => t('common.home'),
    ];
} elseif ($currentUser !== null) {
    $role = $currentUser['role'] ?? 'employee';
    if ($route === 'dashboard') {
        $rightIcons[] = [
            'type' => 'button',
            'title' => t('common.settings'),
            'target' => 'modal-settings',
            'entity' => 'settings',
            'icon' => 'setting.svg',
            'alt' => t('common.settings'),
        ];
        $rightIcons[] = [
            'type' => 'button',
            'title' => t('common.documents'),
            'target' => 'crud-modal',
            'entity' => 'documents',
            'icon' => 'document.svg',
            'alt' => t('common.documents'),
        ];
        $rightIcons[] = [
            'type' => 'button',
            'title' => t('common.print'),
            'target' => 'modal-print',
            'icon' => 'print-outline.svg',
            'alt' => t('common.print'),
        ];

        if (in_array($role, ['admin', 'department_manager'], true)) {
            $rightIcons[] = [
                'type' => 'link',
                'href' => appUrl('my-space'),
                'title' => t('common.my_attendance'),
                'icon' => 'home.svg',
                'alt' => t('common.my_attendance'),
            ];
        }
    } else {
        if ($role === 'employee') {
            $rightIcons[] = [
                'type' => 'link',
                'href' => appUrl('my-space', ['print' => 'documents']) . '#employee-received-documents',
                'title' => t('common.print_documents'),
                'icon' => 'print-outline.svg',
                'alt' => t('common.print_documents'),
            ];
        }
        if ($role !== 'employee') {
            $rightIcons[] = [
                'type' => 'link',
                'href' => appUrl('dashboard'),
                'title' => $route === 'my-space' ? t('common.back_to_dashboard') : t('common.dashboard'),
                'icon' => $route === 'my-space' ? 'home.svg' : 'setting.svg',
                'alt' => t('common.dashboard'),
            ];

            if (in_array($role, ['admin', 'department_manager'], true)) {
                if ($route !== 'my-space') {
                    $rightIcons[] = [
                        'type' => 'link',
                        'href' => appUrl('my-space'),
                        'title' => t('common.my_attendance'),
                        'icon' => 'home.svg',
                        'alt' => t('common.my_attendance'),
                    ];
                }
                if ($route === 'my-space') {
                    $rightIcons[] = [
                        'type' => 'link',
                        'href' => appUrl('my-space', ['print' => 'documents']) . '#employee-received-documents',
                        'title' => t('common.print_documents'),
                        'icon' => 'print-outline.svg',
                        'alt' => t('common.print_documents'),
                    ];
                }
            }
        }
    }
    $rightIcons[] = [
        'type' => 'link',
        'href' => appUrl('logout'),
        'title' => t('common.logout'),
        'icon' => 'logout.svg',
        'alt' => t('common.logout'),
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
                <a href="<?php
                    if ($isPublicPage) {
                        echo appUrl('home');
                    } elseif ($currentRole === 'employee') {
                        echo appUrl('my-space');
                    } else {
                        echo appUrl('dashboard');
                    }
                ?>" class="site-brand-link" aria-label="<?php echo e(t('common.app_name')); ?>">
                    <img src="<?php echo $basePath; ?>/assets/images/LogoStaffeasePro.jpg" alt="<?php echo e(t('common.app_name')); ?>" class="site-brand-logo">
                </a>
            </div>

            <div class="site-navbar-right">
                <div class="site-icon-group" role="toolbar" aria-label="<?php echo e(t('common.quick_actions')); ?>">
                    <?php foreach ($rightIcons as $iconItem): ?>
                        <?php if (($iconItem['type'] ?? 'link') === 'button'): ?>
                            <button type="button" class="site-icon-btn" title="<?php echo e($iconItem['title']); ?>"<?php if (($iconItem['toggle'] ?? '') === 'calendar-navigator'): ?> data-calendar-navigator-toggle="true" aria-controls="dashboard-calendar-navigator" aria-expanded="false"<?php else: ?> data-modal-target="<?php echo e($iconItem['target']); ?>"<?php if (!empty($iconItem['entity'])): ?> data-modal-entity="<?php echo e($iconItem['entity']); ?>"<?php endif; ?><?php endif; ?>>
                                <?php if (!empty($iconItem['toggle'])): ?>
                                    <svg class="site-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                                        <path d="M4 6h16" />
                                        <path d="M4 12h16" />
                                        <path d="M4 18h16" />
                                    </svg>
                                <?php else: ?>
                                    <img src="<?php echo $basePath; ?>/assets/icons/<?php echo e($iconItem['icon']); ?>" alt="<?php echo e($iconItem['alt']); ?>" class="site-icon">
                                <?php endif; ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo e($iconItem['href']); ?>" class="site-icon-btn" title="<?php echo e($iconItem['title']); ?>">
                                <img src="<?php echo $basePath; ?>/assets/icons/<?php echo e($iconItem['icon']); ?>" alt="<?php echo e($iconItem['alt']); ?>" class="site-icon">
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="site-lang-switch" aria-label="<?php echo e(t('common.language')); ?>">
                    <a href="<?php echo e(appCurrentUrl(['lang' => 'fr'])); ?>" class="site-lang-link <?php echo $locale === 'fr' ? 'is-active' : ''; ?>">FR</a>
                    <a href="<?php echo e(appCurrentUrl(['lang' => 'en'])); ?>" class="site-lang-link <?php echo $locale === 'en' ? 'is-active' : ''; ?>">EN</a>
                </div>
            </div>
        </div>
    </nav>
</header>
