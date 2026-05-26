<!-- Tableau de bord commun : navigation latérale et modales centrées selon le rôle connecté. -->
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
$requestStatusLabels = [
    'pending' => 'En attente',
    'approved' => 'Approuvée',
    'rejected' => 'Refusée',
];
$requestTypeLabels = [
    'notification' => 'Notification',
    'leave' => 'Congé',
    'shift_change' => 'Changement de quart',
    'other' => 'Autre',
    'admin_note' => 'Note admin',
];
?>

<div class="admin-shell dashboard-shell">
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

    <div class="dashboard-main">
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
            <?php elseif ($role === 'admin'): ?>
                <div class="admin-grid">
                    <section class="admin-card admin-stat">
                        <span class="admin-stat-value"><?php echo e(count($moduleRows['company_users'] ?? [])); ?></span>
                        <span class="admin-stat-label">Employés de l'entreprise</span>
                    </section>
                    <section class="admin-card admin-stat">
                        <span class="admin-stat-value"><?php echo e($stats['departments'] ?? 0); ?></span>
                        <span class="admin-stat-label">Départements</span>
                    </section>
                    <section class="admin-card admin-stat">
                        <span class="admin-stat-value"><?php echo e(count($moduleRows['company_requests'] ?? [])); ?></span>
                        <span class="admin-stat-label">Requests récentes</span>
                    </section>
                </div>
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
            <?php else: ?>
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
            <?php endif; ?>
    </div>
</div>

<div class="dashboard-overlay" id="dashboard-overlay" hidden></div>

<?php if ($role === 'super_admin'): ?>
    <section class="dashboard-modal" id="modal-super-directory" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        <h2>Entreprises, directeurs assignés et départements</h2>
        <div class="dashboard-directory-grid">
            <?php if (empty($moduleRows['company_directory'] ?? [])): ?>
                <p>Aucune entreprise trouvée.</p>
            <?php endif; ?>
            <?php foreach (($moduleRows['company_directory'] ?? []) as $company): ?>
                <article class="dashboard-directory-card">
                    <h3><?php echo e($company['name']); ?></h3>
                    <p>Ville: <?php echo e($company['city'] ?? '-'); ?></p>
                    <p><strong>Directeurs:</strong> <?php echo e(empty($company['admins']) ? 'Aucun assigné' : implode(', ', $company['admins'])); ?></p>
                    <p><strong>Départements:</strong> <?php echo e(empty($company['departments']) ? 'Aucun département' : implode(', ', $company['departments'])); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="dashboard-modal" id="modal-super-actions" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        <h2>Actions Super Admin</h2>
        <div class="admin-actions">
            <a class="admin-action-link" href="<?php echo appUrl('users'); ?>">Gérer les utilisateurs</a>
            <a class="admin-action-link" href="<?php echo appUrl('companies'); ?>">Gérer les entreprises</a>
            <a class="admin-action-link" href="<?php echo appUrl('departments'); ?>">Gérer les départements</a>
        </div>
    </section>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
    <section class="dashboard-modal" id="modal-admin-departments" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        <h2>Départements</h2>
        <p>Gestion des départements de votre entreprise.</p>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($moduleRows['company_departments'] ?? [])): ?>
                        <tr>
                            <td colspan="2">Aucun département trouvé.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach (($moduleRows['company_departments'] ?? []) as $department): ?>
                        <tr>
                            <td><?php echo e($department['name']); ?></td>
                            <td><?php echo e($department['description'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-actions">
            <a class="admin-action-link" href="<?php echo appUrl('departments'); ?>">Ouvrir la gestion des départements</a>
        </div>
    </section>

    <section class="dashboard-modal" id="modal-admin-employees" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        <h2>Employés</h2>
        <p>Gestion des employés de votre entreprise.</p>
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
        <div class="admin-actions">
            <a class="admin-action-link" href="<?php echo appUrl('users'); ?>">Ouvrir la gestion des utilisateurs</a>
        </div>
    </section>

    <section class="dashboard-modal" id="modal-admin-requests" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        <h2>Requests de l'entreprise</h2>

        <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form">
            <input type="hidden" name="dashboard_action" value="create_request">
            <input type="hidden" name="request_type" value="admin_note">
            <label>
                Titre
                <input type="text" name="request_title" required>
            </label>
            <label>
                Message
                <textarea name="request_message" required></textarea>
            </label>
            <div class="form-actions">
                <button type="submit">Créer une request</button>
            </div>
        </form>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Employé</th>
                        <th>Département</th>
                        <th>Type</th>
                        <th>Titre</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($moduleRows['company_requests'] ?? [])): ?>
                        <tr>
                            <td colspan="6">Aucune request récente.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach (($moduleRows['company_requests'] ?? []) as $request): ?>
                        <tr>
                            <td><?php echo e(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></td>
                            <td><?php echo e($request['department_name'] ?? '-'); ?></td>
                            <td><?php echo e($requestTypeLabels[$request['type']] ?? $request['type']); ?></td>
                            <td><?php echo e($request['title'] ?? '-'); ?></td>
                            <td><?php echo e($requestStatusLabels[$request['status']] ?? $request['status']); ?></td>
                            <td><?php echo e($request['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="dashboard-modal" id="modal-admin-notifications" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        <h2>Notifications</h2>

        <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form">
            <input type="hidden" name="dashboard_action" value="create_notification">
            <label>
                Titre
                <input type="text" name="notification_title" required>
            </label>
            <label>
                Message
                <textarea name="notification_message" required></textarea>
            </label>
            <div class="form-actions">
                <button type="submit">Créer une notification</button>
            </div>
        </form>

        <?php if (empty($moduleRows['notifications'] ?? [])): ?>
            <p>Aucune notification pour le moment.</p>
        <?php endif; ?>

        <?php foreach (($moduleRows['notifications'] ?? []) as $notification): ?>
            <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form dashboard-note-form">
                <input type="hidden" name="dashboard_action" value="update_notification">
                <input type="hidden" name="notification_id" value="<?php echo e($notification['id']); ?>">
                <label>
                    Titre
                    <input type="text" name="notification_title" value="<?php echo e($notification['title'] ?? ''); ?>" required>
                </label>
                <label>
                    Message
                    <textarea name="notification_message" required><?php echo e($notification['message'] ?? ''); ?></textarea>
                </label>
                <div class="form-actions">
                    <button type="submit">Mettre à jour</button>
                </div>
            </form>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if ($role === 'department_manager'): ?>
    <section class="dashboard-modal" id="modal-manager-team" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
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
<?php endif; ?>

<section class="dashboard-modal" id="modal-global-requests" hidden>
    <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
    <h2>Mes requests</h2>

    <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form">
        <input type="hidden" name="dashboard_action" value="create_request">
        <label>
            Type
            <select name="request_type" required>
                <option value="leave">Congé</option>
                <option value="shift_change">Changement de quart</option>
                <option value="other">Autre</option>
            </select>
        </label>
        <label>
            Titre
            <input type="text" name="request_title" required>
        </label>
        <label>
            Message
            <textarea name="request_message" required></textarea>
        </label>
        <div class="form-actions">
            <button type="submit">Créer une request</button>
        </div>
    </form>

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
                        <td colspan="4">Aucune request enregistrée.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach (($moduleRows['requests'] ?? []) as $request): ?>
                    <tr>
                        <td><?php echo e($requestTypeLabels[$request['type']] ?? $request['type']); ?></td>
                        <td><?php echo e($request['title'] ?? '-'); ?></td>
                        <td><?php echo e($requestStatusLabels[$request['status']] ?? $request['status']); ?></td>
                        <td><?php echo e($request['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="dashboard-modal" id="modal-global-notifications" hidden>
    <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
    <h2>Mes notifications</h2>

    <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form">
        <input type="hidden" name="dashboard_action" value="create_notification">
        <label>
            Titre
            <input type="text" name="notification_title" required>
        </label>
        <label>
            Message
            <textarea name="notification_message" required></textarea>
        </label>
        <div class="form-actions">
            <button type="submit">Créer une notification</button>
        </div>
    </form>

    <?php if (empty($moduleRows['notifications'] ?? [])): ?>
        <p>Aucune notification pour le moment.</p>
    <?php endif; ?>

    <?php foreach (($moduleRows['notifications'] ?? []) as $notification): ?>
        <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form dashboard-note-form">
            <input type="hidden" name="dashboard_action" value="update_notification">
            <input type="hidden" name="notification_id" value="<?php echo e($notification['id']); ?>">
            <label>
                Titre
                <input type="text" name="notification_title" value="<?php echo e($notification['title'] ?? ''); ?>" required>
            </label>
            <label>
                Message
                <textarea name="notification_message" required><?php echo e($notification['message'] ?? ''); ?></textarea>
            </label>
            <div class="form-actions">
                <button type="submit">Mettre à jour</button>
            </div>
        </form>
    <?php endforeach; ?>
</section>

<script>
(() => {
    const overlay = document.getElementById('dashboard-overlay');
    const modals = document.querySelectorAll('.dashboard-modal');
    const openButtons = document.querySelectorAll('.dashboard-sidebar-link[data-modal-target]');
    const closeButtons = document.querySelectorAll('[data-modal-close]');

    const closeAll = () => {
        modals.forEach((modal) => {
            modal.hidden = true;
            modal.classList.remove('is-open');
        });
        if (overlay) {
            overlay.hidden = true;
            overlay.classList.remove('is-open');
        }
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-modal-target');
            const targetModal = document.getElementById(targetId);

            if (!targetModal) {
                return;
            }

            closeAll();
            openButtons.forEach((item) => item.classList.remove('is-active'));
            button.classList.add('is-active');
            targetModal.hidden = false;
            targetModal.classList.add('is-open');
            if (overlay) {
                overlay.hidden = false;
                overlay.classList.add('is-open');
            }
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeAll);
    });

    if (overlay) {
        overlay.addEventListener('click', closeAll);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAll();
        }
    });
})();
</script>
