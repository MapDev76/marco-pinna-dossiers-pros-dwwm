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

$weekDays = [
    ['label' => 'DAY WEEK', 'day' => 'XX'],
    ['label' => 'TUESDAY', 'day' => '24'],
    ['label' => 'WEDNESDAY', 'day' => '25'],
    ['label' => 'THURSDAY', 'day' => '26'],
    ['label' => 'FRIDAY', 'day' => '27'],
    ['label' => 'SATURDAY', 'day' => '28', 'highlight' => true],
    ['label' => 'SUNDAY', 'day' => '29'],
];

$calendarTemplates = [
    ['time' => '06:00 - 14:00', 'title' => 'Reception', 'meta' => 'Assigné au département'],
    ['time' => '14:00 - 22:00', 'title' => 'Housekeeping', 'meta' => 'Shift de jour'],
    ['time' => '22:00 - 06:00', 'title' => 'Night auditor', 'meta' => 'Shift de nuit'],
];
?>

<div class="admin-shell dashboard-shell">
    <div class="admin-hero">
        <?php if (($currentUser['role'] ?? '') === 'super_admin'): ?>
            <h1><?php echo e($pageTitle ?? 'Tableau de bord'); ?></h1>
        <?php endif; ?>
        <!-- No welcome message or titles for non-super users per design -->
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
            <?php elseif ($role === 'admin' || $role === 'department_manager'): ?>
                <section class="admin-card dashboard-calendar-shell">
                    <div class="dashboard-calendar-headline">
                        <div>
                            <p><?php echo $role === 'admin' ? 'Planification de l’entreprise' : 'Planification du département'; ?></p>
                        </div>
                        <button type="button" class="admin-action-link" data-modal-target="modal-schedule">Planning</button>
                    </div>

                    <div class="dashboard-calendar">
                        <?php foreach ($weekDays as $index => $dayInfo): ?>
                            <article class="dashboard-calendar-column <?php echo !empty($dayInfo['highlight']) ? 'is-highlight' : ''; ?>">
                                <header class="dashboard-calendar-head">
                                    <span class="dashboard-calendar-weekday"><?php echo e($dayInfo['label']); ?></span>
                                    <span class="dashboard-calendar-day"><?php echo e($dayInfo['day']); ?></span>
                                </header>
                                <div class="dashboard-calendar-body">
                                    <?php foreach ($calendarTemplates as $template): ?>
                                        <button type="button" class="dashboard-calendar-card" data-modal-target="modal-schedule">
                                            <span class="dashboard-calendar-time"><?php echo e($template['time']); ?></span>
                                            <span class="dashboard-calendar-title"><?php echo e($template['title']); ?></span>
                                            <span class="dashboard-calendar-meta"><?php echo e($template['meta']); ?></span>
                                        </button>
                                    <?php endforeach; ?>

                                    <button type="button" class="dashboard-calendar-card is-add" data-modal-target="modal-schedule">
                                        + Ajouter
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
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
    <section class="dashboard-modal dashboard-settings-modal" id="modal-super-directory" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        <div class="admin-actions">
            <button type="button" class="admin-action-link">Ajouter une entreprise</button>
        </div>
        <div class="dashboard-directory-grid">
            <?php if (empty($moduleRows['company_directory'] ?? [])): ?>
                <p>Aucune entreprise trouvée.</p>
            <?php endif; ?>
            <?php foreach (($moduleRows['company_directory'] ?? []) as $company): ?>
                    <article class="dashboard-directory-card" data-company-id="<?php echo e($company['id']); ?>">
                    <h3><?php echo e($company['name']); ?></h3>
                    <p>Ville: <?php echo e($company['city'] ?? '-'); ?></p>
                    <p><strong>Directeurs:</strong> <?php echo e(empty($company['admins']) ? 'Aucun assigné' : implode(', ', $company['admins'])); ?></p>
                    <p><strong>Départements:</strong> <?php echo e(empty($company['departments']) ? 'Aucun département' : implode(', ', $company['departments'])); ?></p>
                    <div class="company-actions">
                        <button type="button" class="admin-action-link" data-action="manage-departments">Gérer les départements</button>
                        <button type="button" class="admin-action-link" data-action="manage-employees">Gérer les employés</button>
                        <button type="button" class="admin-action-link" data-action="assign-head">Assigner un chef de département</button>
                        <button type="button" class="admin-action-link" data-action="set-ip">Définir IP signature</button>
                        <button type="button" class="admin-action-link" data-action="edit">Modifier</button>
                        <button type="button" class="admin-action-link" data-action="delete">Supprimer</button>
                    </div>
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

    <section class="dashboard-modal dashboard-settings-modal" id="modal-admin-requests" hidden>
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

    <section class="dashboard-modal dashboard-settings-modal" id="modal-admin-notifications" hidden>
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

<section class="dashboard-modal dashboard-settings-modal" id="modal-global-requests" hidden>
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

<section class="dashboard-modal dashboard-settings-modal" id="modal-global-notifications" hidden>
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

<script>
(() => {
    const apiCompanies = '<?php echo appUrl('api-companies'); ?>';
    const apiDepartments = '<?php echo appUrl('api-departments'); ?>';
    const apiUsers = '<?php echo appUrl('api-users'); ?>';

    document.querySelectorAll('.dashboard-directory-card').forEach(card => {
        const companyId = card.getAttribute('data-company-id');
        if (!companyId) return;

        card.querySelectorAll('.company-actions [data-action]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const action = btn.getAttribute('data-action');

                try {
                    if (action === 'set-ip') {
                        const ip = prompt('Adresse IP pour la signature (laisser vide per rimuovere):');
                        if (ip === null) return;
                        const res = await fetch(apiCompanies, { method: 'POST', body: JSON.stringify({ action: 'set_signature_ip', company_id: companyId, ip }) });
                        const j = await res.json();
                        if (!j.ok) alert('Erreur: ' + (j.error || 'unknown')); else alert('IP mise à jour');
                        return;
                    }

                    if (action === 'delete') {
                        if (!confirm('Confirmer la suppression de cette entreprise ?')) return;
                        const res = await fetch(apiCompanies, { method: 'POST', body: JSON.stringify({ action: 'delete', id: companyId }) });
                        const j = await res.json();
                        if (!j.ok) alert('Erreur: ' + (j.error || 'unknown')); else location.reload();
                        return;
                    }

                    if (action === 'manage-departments') {
                        const res = await fetch(apiDepartments, { method: 'POST', body: JSON.stringify({ action: 'list', company_id: companyId }) });
                        const j = await res.json();
                        if (!j.ok) { alert('Erreur: ' + (j.error || 'unknown')); return; }
                        const list = j.departments.map(d => `${d.id}: ${d.name}`).join('\n') || 'Aucun département';
                        const cmd = prompt('Départements:\n' + list + '\n\nPour créer: saisir un nouveau nom. Pour supprimer: del:<id>');
                        if (!cmd) return;
                        if (cmd.startsWith('del:')) {
                            const id = cmd.split(':')[1];
                            const r = await fetch(apiDepartments, { method: 'POST', body: JSON.stringify({ action: 'delete', id }) });
                            const jr = await r.json(); if (!jr.ok) alert('Erreur: ' + (jr.error || 'unknown')); else location.reload();
                        } else {
                            const r = await fetch(apiDepartments, { method: 'POST', body: JSON.stringify({ action: 'create', company_id: companyId, name: cmd }) });
                            const jr = await r.json(); if (!jr.ok) alert('Erreur: ' + (jr.error || 'unknown')); else location.reload();
                        }
                        return;
                    }

                    if (action === 'manage-employees') {
                        const res = await fetch(apiUsers, { method: 'POST', body: JSON.stringify({ action: 'list_by_company', company_id: companyId }) });
                        const j = await res.json();
                        if (!j.ok) { alert('Erreur: ' + (j.error || 'unknown')); return; }
                        const list = j.users.map(u => `${u.id}: ${u.first_name} ${u.last_name} (${u.role})`).join('\n') || 'Aucun employé';
                        const cmd = prompt('Employés:\n' + list + '\n\nPour créer: new:First Last,email,role. Pour supprimer: del:<id>');
                        if (!cmd) return;
                        if (cmd.startsWith('del:')) {
                            const id = cmd.split(':')[1];
                            const r = await fetch(apiUsers, { method: 'POST', body: JSON.stringify({ action: 'delete', id }) });
                            const jr = await r.json(); if (!jr.ok) alert('Erreur: ' + (jr.error || 'unknown')); else location.reload();
                        } else if (cmd.startsWith('new:')) {
                            const payload = cmd.substring(4).split(',');
                            const name = payload[0] || ''; const email = payload[1] || ''; const role = payload[2] || 'employee';
                            const names = name.split(' '); const first = names.shift(); const last = names.join(' ') || '';
                            const r = await fetch(apiUsers, { method: 'POST', body: JSON.stringify({ action: 'create', department_id: null, first_name: first, last_name: last, email, role }) });
                            const jr = await r.json(); if (!jr.ok) alert('Erreur: ' + (jr.error || 'unknown')); else location.reload();
                        }
                        return;
                    }

                    if (action === 'assign-head') {
                        alert('Utilisez le flux Gérer les employés puis assign-head via UI futura.');
                        return;
                    }

                    if (action === 'edit') {
                        alert('Edit company — UI non implementata ancora.');
                        return;
                    }

                } catch (err) {
                    alert('Erreur réseau: ' + err.message);
                }
            });
        });
    });
})();
</script>
