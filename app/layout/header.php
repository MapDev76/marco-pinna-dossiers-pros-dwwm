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
                <?php if ($route === 'dashboard' && $currentUser !== null): ?>
                    <div class="header-user-info">
                        <div class="header-user-name"><?php echo e(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')); ?></div>
                        <div class="header-user-meta">
                            <?php echo e($currentUser['company_name'] ?? ''); ?><?php if (!empty($currentUser['department_name'])): ?> — <?php echo e($currentUser['department_name']); ?><?php endif; ?>
                        </div>
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
                            <button type="button" class="icon-btn" aria-label="Utilisateurs" data-modal-target="modal-global-requests">
                                <img src="<?php echo $basePath; ?>/assets/icons/document.svg" alt="" class="nav-icon">
                            </button>
                            <button type="button" class="icon-btn" aria-label="Entreprises" data-modal-target="modal-super-directory">
                                <img src="<?php echo $basePath; ?>/assets/icons/setting.svg" alt="" class="nav-icon">
                            </button>
                            <button type="button" class="icon-btn" aria-label="Paramètres" data-modal-target="modal-settings">
                                <img src="<?php echo $basePath; ?>/assets/icons/light.svg" alt="" class="nav-icon">
                            </button>
                        <?php elseif (($currentUser['role'] ?? null) === 'admin'): ?>
                            <button type="button" class="icon-btn" aria-label="Utilisateurs" data-modal-target="modal-admin-employees">
                                <img src="<?php echo $basePath; ?>/assets/icons/document.svg" alt="" class="nav-icon">
                            </button>
                            <button type="button" class="icon-btn" aria-label="Entreprises" data-modal-target="modal-admin-departments">
                                <img src="<?php echo $basePath; ?>/assets/icons/setting.svg" alt="" class="nav-icon">
                            </button>
                        <?php elseif (($currentUser['role'] ?? null) === 'department_manager'): ?>
                            <button type="button" class="icon-btn" aria-label="Utilisateurs" data-modal-target="modal-manager-team">
                                <img src="<?php echo $basePath; ?>/assets/icons/document.svg" alt="" class="nav-icon">
                            </button> 
                            <button type="button" class="icon-btn" aria-label="Paramètres" data-modal-target="modal-settings">
                                <img src="<?php echo $basePath; ?>/assets/icons/light.svg" alt="" class="nav-icon">
                            </button>
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