<?php
// Bandeau partagé de l'application : logo, navigation rapide et icônes contextuelles.
$route = $route ?? ($_GET['route'] ?? 'home');
$currentUser = currentUser();
$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'department_manager' => 'Chef de département',
    'employee' => 'Employé',
];
$isPublicPage = in_array($route, ['home', 'login'], true);
$basePath = $basePath ?? (function () {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
})();
?>
<header class="admin-header<?php echo $isPublicPage ? ' is-public' : ''; ?><?php echo $route === 'login' ? ' is-login' : ''; ?>">
    <nav class="admin-navbar" aria-label="Navigation principale">
        <div class="admin-navbar-inner">
            <?php if ($isPublicPage): ?>
            <div class="public-navbar-center">
                <a href="<?php echo appUrl('home'); ?>" class="public-brand-link" aria-label="StaffEase Pro">
                    <img src="<?php echo $basePath; ?>/assets/images/LogoStaffeasePro.jpg" alt="StaffEase Pro" class="public-brand-logo">
                </a>
            </div>
            <?php if ($route === 'home'): ?>
            <div class="public-navbar-right">
                <a href="<?php echo appUrl('login'); ?>" class="icon-btn public-login-btn" title="Connexion">
                    <img src="<?php echo $basePath; ?>/assets/icons/log-in.svg" alt="login" class="nav-icon">
                </a>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <!-- Partie gauche: résumé utilisateur -->
            <div class="admin-navbar-section admin-navbar-left">
                <div class="brand-small">
                    <!-- Logo compact à gauche -->
                </div>
                <?php if ($currentUser !== null): ?>
                <div class="hotel-meta">
                    <div class="hotel-name">StaffEase Pro</div>
                    <div class="hotel-submeta">
                        <span class="employee-name"><?php echo e(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')); ?></span>
                        <span class="employee-role"><?php echo e($roleLabels[$currentUser['role'] ?? 'employee'] ?? ucfirst((string) ($currentUser['role'] ?? 'employee'))); ?></span>
                        <span class="employee-email"><?php echo e($currentUser['email'] ?? 'employee@example.com'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Centre: titre principal -->
            <div class="admin-navbar-section admin-navbar-center">
                <div class="admin-brand-large">
                    <img src="<?php echo $basePath; ?>/assets/images/LogoStaffeasePro.jpg" alt="StaffEase Pro" class="admin-brand-icon">
                    <div class="admin-brand-text">Dashboard <strong>Admin</strong></div>
                </div>
            </div>

            <!-- Droite: actions rapides -->
            <div class="admin-navbar-section admin-navbar-right">
                <div class="icon-group" role="toolbar" aria-label="Actions rapides">
                    <?php if ($currentUser !== null): ?>
                        <a href="<?php echo appUrl('logout'); ?>" class="icon-btn" title="Logout">
                            <img src="<?php echo $basePath; ?>/assets/icons/log-in.svg" alt="logout" class="nav-icon">
                        </a>
                        <button type="button" class="icon-btn" title="Settings" data-modal-target="modal-settings">
                            <img src="<?php echo $basePath; ?>/assets/icons/setting.svg" alt="settings" class="nav-icon">
                        </button>
                        <a href="#" class="icon-btn" title="Documents">
                            <img src="<?php echo $basePath; ?>/assets/icons/document.svg" alt="documents" class="nav-icon">
                        </a>
                        <a href="#" class="icon-btn" title="Print">
                            <img src="<?php echo $basePath; ?>/assets/icons/print-outline.svg" alt="print" class="nav-icon">
                        </a>
                    <?php else: ?>
                        <a href="<?php echo appUrl('login'); ?>" class="icon-btn" title="Login">
                            <img src="<?php echo $basePath; ?>/assets/icons/log-in.svg" alt="login" class="nav-icon">
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </nav>
</header>