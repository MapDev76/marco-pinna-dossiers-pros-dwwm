<?php
// Bandeau partagé de l'application : logo, navigation rapide et icônes contextuelles.
$route = $route ?? ($_GET['route'] ?? 'home');
$currentUser = currentUser();
$basePath = $basePath ?? (function () {
    // Calcule le préfixe du site pour que les ressources restent valides en racine ou en sous-dossier.
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
})();
?>
<header class="header">
    <nav class="topnav" aria-label="Main navigation">
        <div class="topnav-inner">
            <div class="nav-left">
                <?php if ($route === 'dashboard'): ?>
                    <div class="icon-group" aria-label="Quick actions left">
                        <a href="#" class="icon-btn" aria-label="Document">
                            <img src="<?php echo $basePath; ?>/assets/icons/document.svg" alt="" class="nav-icon">
                        </a>
                        <a href="#" class="icon-btn" aria-label="Print">
                            <img src="<?php echo $basePath; ?>/assets/icons/print-outline.svg" alt="" class="nav-icon">
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="nav-center">
                <a href="?route=home" class="logo-link" aria-label="StaffEase Pro Home">
                    <img src="<?php echo $basePath; ?>/assets/images/LogoStaffeasePro.jpg" alt="StaffEase Pro" class="logo">
                </a>
            </div>

            <div class="nav-right">
                <div class="icon-group" aria-label="Quick actions right">
                    <?php if ($currentUser !== null): ?>
                        <a href="<?php echo appUrl('dashboard'); ?>" class="icon-btn" aria-label="Dashboard">
                            <img src="<?php echo $basePath; ?>/assets/icons/home.svg" alt="" class="nav-icon">
                        </a>
                        <a href="<?php echo appUrl('users'); ?>" class="icon-btn" aria-label="Users">
                            <img src="<?php echo $basePath; ?>/assets/icons/document.svg" alt="" class="nav-icon">
                        </a>
                        <a href="<?php echo appUrl('companies'); ?>" class="icon-btn" aria-label="Companies">
                            <img src="<?php echo $basePath; ?>/assets/icons/setting.svg" alt="" class="nav-icon">
                        </a>
                        <a href="<?php echo appUrl('departments'); ?>" class="icon-btn" aria-label="Departments">
                            <img src="<?php echo $basePath; ?>/assets/icons/language.svg" alt="" class="nav-icon">
                        </a>
                        <a href="<?php echo appUrl('logout'); ?>" class="icon-btn" aria-label="Logout">
                            <img src="<?php echo $basePath; ?>/assets/icons/log-in.svg" alt="" class="nav-icon">
                        </a>
                    <?php elseif ($route === 'home'): ?>
                        <a href="?route=login" class="icon-btn" aria-label="Login">
                            <img src="<?php echo $basePath; ?>/assets/icons/log-in.svg" alt="" class="nav-icon">
                        </a>
                    <?php else: ?>
                        <a href="?route=home" class="icon-btn" aria-label="Back to home">
                            <img src="<?php echo $basePath; ?>/assets/icons/home.svg" alt="" class="nav-icon">
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>