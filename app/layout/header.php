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
    <nav class="topnav" aria-label="Navigation principale">
        <div class="topnav-inner">
            <div class="nav-left">
                <?php if ($route === 'dashboard'): ?>
                    <div class="icon-group" aria-label="Actions rapides à gauche">
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
                <a href="<?php echo appUrl('home'); ?>" class="logo-link" aria-label="StaffEase Pro Home">
                    <img src="<?php echo $basePath; ?>/assets/images/LogoStaffeasePro.jpg" alt="StaffEase Pro" class="logo">
                </a>
            </div>

            <div class="nav-right">
                <div class="icon-group" aria-label="Actions rapides à droite">
                    <?php if ($currentUser !== null): ?>
                        <a href="<?php echo appUrl('dashboard'); ?>" class="icon-btn" aria-label="Tableau de bord">
                            <img src="<?php echo $basePath; ?>/assets/icons/home.svg" alt="" class="nav-icon">
                        </a>
                        <?php if (($currentUser['role'] ?? null) === 'super_admin'): ?>
                            <a href="<?php echo appUrl('users'); ?>" class="icon-btn" aria-label="Utilisateurs">
                                <img src="<?php echo $basePath; ?>/assets/icons/document.svg" alt="" class="nav-icon">
                            </a>
                            <a href="<?php echo appUrl('companies'); ?>" class="icon-btn" aria-label="Entreprises">
                                <img src="<?php echo $basePath; ?>/assets/icons/setting.svg" alt="" class="nav-icon">
                            </a>
                            <a href="<?php echo appUrl('departments'); ?>" class="icon-btn" aria-label="Départements">
                                <img src="<?php echo $basePath; ?>/assets/icons/language.svg" alt="" class="nav-icon">
                            </a>
                        <?php elseif (($currentUser['role'] ?? null) === 'admin'): ?>
                            <a href="<?php echo appUrl('users'); ?>" class="icon-btn" aria-label="Utilisateurs">
                                <img src="<?php echo $basePath; ?>/assets/icons/document.svg" alt="" class="nav-icon">
                            </a>
                            <a href="<?php echo appUrl('companies'); ?>" class="icon-btn" aria-label="Entreprises">
                                <img src="<?php echo $basePath; ?>/assets/icons/setting.svg" alt="" class="nav-icon">
                            </a>
                            <a href="<?php echo appUrl('departments'); ?>" class="icon-btn" aria-label="Départements">
                                <img src="<?php echo $basePath; ?>/assets/icons/language.svg" alt="" class="nav-icon">
                            </a>
                        <?php elseif (($currentUser['role'] ?? null) === 'department_manager'): ?>
                            <a href="<?php echo appUrl('users'); ?>" class="icon-btn" aria-label="Utilisateurs">
                                <img src="<?php echo $basePath; ?>/assets/icons/document.svg" alt="" class="nav-icon">
                            </a>
                            <a href="<?php echo appUrl('departments'); ?>" class="icon-btn" aria-label="Départements">
                                <img src="<?php echo $basePath; ?>/assets/icons/language.svg" alt="" class="nav-icon">
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo appUrl('logout'); ?>" class="icon-btn" aria-label="Déconnexion">
                            <img src="<?php echo $basePath; ?>/assets/icons/log-in.svg" alt="" class="nav-icon">
                        </a>
                    <?php elseif ($route === 'home'): ?>
                        <a href="<?php echo appUrl('login'); ?>" class="icon-btn" aria-label="Connexion">
                            <img src="<?php echo $basePath; ?>/assets/icons/log-in.svg" alt="" class="nav-icon">
                        </a>
                    <?php else: ?>
                        <a href="<?php echo appUrl('home'); ?>" class="icon-btn" aria-label="Retour à l'accueil">
                            <img src="<?php echo $basePath; ?>/assets/icons/home.svg" alt="" class="nav-icon">
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>