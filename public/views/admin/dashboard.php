<!-- Tableau de bord Super Admin : affiche les indicateurs principaux et les raccourcis d'administration. -->
<?php $currentUser = currentUser(); ?>
<div class="admin-shell">
    <div class="admin-hero">
        <h1>Dashboard Super Admin</h1>
        <p>Bienvenue <?php echo e($currentUser['first_name'] ?? 'Super Admin'); ?>. Depuis cet espace, tu peux gérer les utilisateurs, les entreprises et les départements.</p>
    </div>

    <div class="admin-grid">
        <section class="admin-card admin-stat">
            <span class="admin-stat-value"><?php echo e($stats['users'] ?? 0); ?></span>
            <span class="admin-stat-label">Utilisateurs</span>
        </section>
        <section class="admin-card admin-stat">
            <span class="admin-stat-value"><?php echo e($stats['companies'] ?? 0); ?></span>
            <span class="admin-stat-label">Companies</span>
        </section>
        <section class="admin-card admin-stat">
            <span class="admin-stat-value"><?php echo e($stats['departments'] ?? 0); ?></span>
            <span class="admin-stat-label">Départements</span>
        </section>
    </div>

    <div class="admin-actions">
        <a class="admin-action-link" href="<?php echo appUrl('users'); ?>">Gérer les utilisateurs</a>
        <a class="admin-action-link" href="<?php echo appUrl('companies'); ?>">Gérer les companies</a>
        <a class="admin-action-link" href="<?php echo appUrl('departments'); ?>">Gérer les départements</a>
    </div>
</div>
