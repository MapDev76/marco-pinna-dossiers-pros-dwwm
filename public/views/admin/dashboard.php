<!-- Tableau de bord commun : adapte le contenu selon le rôle connecté. -->
<?php
$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';
$profile = $profile ?? [];
$moduleRows = $moduleRows ?? [];
$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Administrateur',
    'department_manager' => 'Chef de département',
    'employee' => 'Employé',
];
?>
<div class="admin-shell">
    <div class="admin-hero">
        <h1><?php echo e($pageTitle ?? 'Tableau de bord'); ?></h1>
        <p>
            Bienvenue <?php echo e($currentUser['first_name'] ?? 'utilisateur'); ?>.
            <?php if (!empty($profile['company_name'])): ?>
                Entreprise: <?php echo e($profile['company_name']); ?>.
            <?php endif; ?>
            <?php if (!empty($profile['department_name'])): ?>
                Département: <?php echo e($profile['department_name']); ?>.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($role === 'super_admin'): ?>
        <div class="admin-grid">
            <section class="admin-card admin-stat">
                <span class="admin-stat-value"><?php echo e($stats['users'] ?? 0); ?></span>
                <span class="admin-stat-label">Utilisateurs</span>
            </section>
            <section class="admin-card admin-stat">
                <span class="admin-stat-value"><?php echo e($stats['companies'] ?? 0); ?></span>
                <span class="admin-stat-label">Entreprises</span>
            </section>
            <section class="admin-card admin-stat">
                <span class="admin-stat-value"><?php echo e($stats['departments'] ?? 0); ?></span>
                <span class="admin-stat-label">Départements</span>
            </section>
        </div>

        <div class="admin-actions">
            <a class="admin-action-link" href="<?php echo appUrl('users'); ?>">Gérer les utilisateurs</a>
            <a class="admin-action-link" href="<?php echo appUrl('companies'); ?>">Gérer les entreprises</a>
            <a class="admin-action-link" href="<?php echo appUrl('departments'); ?>">Gérer les départements</a>
        </div>
    <?php elseif ($role === 'admin'): ?>
        <div class="admin-grid">
            <section class="admin-card admin-stat">
                <span class="admin-stat-value"><?php echo e(count($moduleRows['company_users'] ?? [])); ?></span>
                <span class="admin-stat-label">Utilisateurs de l'entreprise</span>
            </section>
            <section class="admin-card admin-stat">
                <span class="admin-stat-value"><?php echo e($stats['departments'] ?? 0); ?></span>
                <span class="admin-stat-label">Départements</span>
            </section>
            <section class="admin-card admin-stat">
                <span class="admin-stat-value"><?php echo e($stats['companies'] ?? 0); ?></span>
                <span class="admin-stat-label">Entreprises</span>
            </section>
        </div>

        <div class="admin-actions">
            <a class="admin-action-link" href="<?php echo appUrl('users'); ?>">Gérer les utilisateurs de l'entreprise</a>
            <a class="admin-action-link" href="<?php echo appUrl('companies'); ?>">Gérer l'entreprise liée</a>
            <a class="admin-action-link" href="<?php echo appUrl('departments'); ?>">Gérer les départements de l'entreprise</a>
        </div>

        <section class="admin-card">
            <h2>Utilisateurs de mon entreprise</h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Département</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($moduleRows['company_users'] ?? [])): ?>
                            <tr>
                                <td colspan="5">Aucun utilisateur rattaché à cette entreprise.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (($moduleRows['company_users'] ?? []) as $companyUser): ?>
                            <tr>
                                <td><?php echo e($companyUser['first_name'] . ' ' . $companyUser['last_name']); ?></td>
                                <td><?php echo e($companyUser['email']); ?></td>
                                <td><?php echo e($roleLabels[$companyUser['role']] ?? $companyUser['role']); ?></td>
                                <td><?php echo e($companyUser['department_name'] ?? '-'); ?></td>
                                <td><?php echo e($companyUser['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php elseif ($role === 'department_manager'): ?>
        <div class="admin-grid">
            <section class="admin-card admin-stat">
                <span class="admin-stat-value"><?php echo e(count($moduleRows['team'] ?? [])); ?></span>
                <span class="admin-stat-label">Collègues du département</span>
            </section>
            <section class="admin-card admin-stat">
                <span class="admin-stat-value">1</span>
                <span class="admin-stat-label">Département géré</span>
            </section>
            <section class="admin-card admin-stat">
                <span class="admin-stat-value">0</span>
                <span class="admin-stat-label">Demandes en attente</span>
            </section>
        </div>

        <div class="admin-actions">
            <a class="admin-action-link" href="#">Gérer les utilisateurs du département</a>
            <a class="admin-action-link" href="#">Gestion des quarts et des présences</a>
            <a class="admin-action-link" href="#">Demandes et notifications</a>
        </div>

        <section class="admin-card">
            <h2>Équipe du département</h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($moduleRows['team'] ?? [])): ?>
                            <tr>
                                <td colspan="4">Aucun collègue trouvé dans ce département.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (($moduleRows['team'] ?? []) as $teamMember): ?>
                            <tr>
                                <td><?php echo e($teamMember['first_name'] . ' ' . $teamMember['last_name']); ?></td>
                                <td><?php echo e($teamMember['email']); ?></td>
                                <td><?php echo e($roleLabels[$teamMember['role']] ?? $teamMember['role']); ?></td>
                                <td><?php echo e($teamMember['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else: ?>
        <div class="admin-actions">
            <a class="admin-action-link" href="<?php echo appUrl('my-space'); ?>">Mes quarts</a>
            <a class="admin-action-link" href="<?php echo appUrl('my-space'); ?>">Signer la présence</a>
            <a class="admin-action-link" href="<?php echo appUrl('my-space'); ?>">Mes demandes</a>
        </div>

        <section class="admin-card">
            <h2>Mes quarts</h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Quart</th>
                            <th>Département</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($moduleRows['shifts'] ?? [])): ?>
                            <tr>
                                <td colspan="4">Aucun quart disponible pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (($moduleRows['shifts'] ?? []) as $shift): ?>
                            <tr>
                                <td><?php echo e($shift['work_date']); ?></td>
                                <td><?php echo e($shift['shift_name']); ?></td>
                                <td><?php echo e($shift['department_name']); ?></td>
                                <td><?php echo e($shift['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card">
            <h2>Mes demandes</h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Titre</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($moduleRows['requests'] ?? [])): ?>
                            <tr>
                                <td colspan="4">Aucune demande enregistrée.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (($moduleRows['requests'] ?? []) as $request): ?>
                            <tr>
                                <td><?php echo e($request['type']); ?></td>
                                <td><?php echo e($request['title'] ?? '-'); ?></td>
                                <td><?php echo e($request['status']); ?></td>
                                <td><?php echo e($request['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>
