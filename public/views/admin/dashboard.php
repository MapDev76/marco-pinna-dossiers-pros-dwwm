<!-- Shared dashboard: sidebar navigation and centered modals by logged-in role. -->
<?php
$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';
$profile = $profile ?? [];
$moduleRows = $moduleRows ?? [];
$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'department_manager' => 'Department Manager',
    'employee' => 'Employee',
];
$requestStatusLabels = [
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
];
$requestTypeLabels = [
    'notification' => 'Notification',
    'leave' => 'Leave',
    'shift_change' => 'Shift change',
    'other' => 'Other',
    'admin_note' => 'Admin note',
];

$weekDays = [
    ['label' => 'MONDAY', 'day' => 'XX'],
    ['label' => 'TUESDAY', 'day' => '24'],
    ['label' => 'WEDNESDAY', 'day' => '25'],
    ['label' => 'THURSDAY', 'day' => '26'],
    ['label' => 'FRIDAY', 'day' => '27'],
    ['label' => 'SATURDAY', 'day' => '28', 'highlight' => true],
    ['label' => 'SUNDAY', 'day' => '29'],
];

$calendarTemplates = [
    ['time' => '06:00 - 14:00', 'title' => 'Reception', 'meta' => 'Assigned to department'],
    ['time' => '14:00 - 22:00', 'title' => 'Housekeeping', 'meta' => 'Day shift'],
    ['time' => '22:00 - 06:00', 'title' => 'Night auditor', 'meta' => 'Night shift'],
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
                        <span class="admin-stat-label">Users</span>
                    </section>
                    <section class="admin-card admin-stat">
                        <span class="admin-stat-value"><?php echo e($stats['companies'] ?? 0); ?></span>
                        <span class="admin-stat-label">Companies</span>
                    </section>
                    <section class="admin-card admin-stat">
                        <span class="admin-stat-value"><?php echo e($stats['departments'] ?? 0); ?></span>
                        <span class="admin-stat-label">Departments</span>
                    </section>
                </div>

                <section class="admin-card company-directory-section">
                    <div class="dashboard-calendar-headline">
                        <div>
                            <p>Companies</p>
                            <h2>Company overview</h2>
                        </div>
                        <button type="button" class="admin-action-link" data-modal-target="modal-super-directory">Open CRUD</button>
                    </div>

                    <div class="dashboard-company-grid">
                        <?php if (empty($moduleRows['company_directory'] ?? [])): ?>
                            <p>No companies to display.</p>
                        <?php endif; ?>

                        <?php foreach (($moduleRows['company_directory'] ?? []) as $company): ?>
                            <article class="dashboard-company-card">
                                <div class="dashboard-company-card-head">
                                    <div>
                                        <h3><?php echo e($company['name']); ?></h3>
                                        <p><?php echo e($company['city'] ?? '-'); ?></p>
                                    </div>
                                    <?php if (!empty($company['logo_path'])): ?>
                                        <img src="<?php echo e($company['logo_path']); ?>" alt="<?php echo e($company['name']); ?>" class="dashboard-company-logo">
                                    <?php endif; ?>
                                </div>

                                <div class="dashboard-company-metrics">
                                    <div><span>Users</span><strong><?php echo e($company['users_count'] ?? 0); ?></strong></div>
                                    <div><span>Departments</span><strong><?php echo e($company['departments_count'] ?? 0); ?></strong></div>
                                    <div><span>Signature IP</span><strong><?php echo e($company['signature_ip'] ?: '-'); ?></strong></div>
                                </div>

                                <p><strong>Admins:</strong> <?php echo e(empty($company['admins']) ? 'None' : implode(', ', $company['admins'])); ?></p>
                                <p><strong>Department heads:</strong> <?php echo e(empty($company['heads']) ? 'None' : implode(', ', $company['heads'])); ?></p>
                                <p><strong>Departments:</strong> <?php echo e(empty($company['departments']) ? 'None' : implode(', ', $company['departments'])); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php elseif ($role === 'admin' || $role === 'department_manager'): ?>
                <section class="admin-card dashboard-calendar-shell">
                    <div class="dashboard-calendar-headline">
                        <div>
                            <p><?php echo $role === 'admin' ? 'Company planning' : 'Department planning'; ?></p>
                        </div>
                        <button type="button" class="admin-action-link" data-modal-target="modal-schedule">Schedule</button>
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
                                        + Add
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php else: ?>
                <section class="admin-card">
                    <h2>My shifts</h2>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Shift</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($moduleRows['shifts'] ?? [])): ?>
                                    <tr>
                                        <td colspan="4">No shifts available right now.</td>
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
        <h2>Super Admin Actions</h2>
        <div class="admin-actions">
            <a class="admin-action-link" href="<?php echo appUrl('users'); ?>">Manage users</a>
            <a class="admin-action-link" href="<?php echo appUrl('companies'); ?>">Manage companies</a>
            <a class="admin-action-link" href="<?php echo appUrl('departments'); ?>">Manage departments</a>
        </div>
    </section>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
    <section class="dashboard-modal" id="modal-admin-departments" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        <h2>Departments</h2>
        <p>Manage the departments for your company.</p>
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
                            <td colspan="2">No departments found.</td>
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
        <h2>Employees</h2>
        <p>Manage the employees in your company.</p>
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
                            <td colspan="5">No users are linked to this company.</td>
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
        <h2>Company requests</h2>

        <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form">
            <input type="hidden" name="dashboard_action" value="create_request">
            <input type="hidden" name="request_type" value="admin_note">
            <label>
                Title
                <input type="text" name="request_title" required>
            </label>
            <label>
                Message
                <textarea name="request_message" required></textarea>
            </label>
            <div class="form-actions">
                <button type="submit">Create request</button>
            </div>
        </form>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($moduleRows['company_requests'] ?? [])): ?>
                        <tr>
                            <td colspan="6">No recent requests.</td>
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
                Title
                <input type="text" name="notification_title" required>
            </label>
            <label>
                Message
                <textarea name="notification_message" required></textarea>
            </label>
            <div class="form-actions">
                <button type="submit">Create notification</button>
            </div>
        </form>

        <?php if (empty($moduleRows['notifications'] ?? [])): ?>
            <p>No notifications at the moment.</p>
        <?php endif; ?>

        <?php foreach (($moduleRows['notifications'] ?? []) as $notification): ?>
            <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form dashboard-note-form">
                <input type="hidden" name="dashboard_action" value="update_notification">
                <input type="hidden" name="notification_id" value="<?php echo e($notification['id']); ?>">
                <label>
                    Title
                    <input type="text" name="notification_title" value="<?php echo e($notification['title'] ?? ''); ?>" required>
                </label>
                <label>
                    Message
                    <textarea name="notification_message" required><?php echo e($notification['message'] ?? ''); ?></textarea>
                </label>
                <div class="form-actions">
                    <button type="submit">Update</button>
                </div>
            </form>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if ($role === 'department_manager'): ?>
    <section class="dashboard-modal" id="modal-manager-team" hidden>
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        <h2>Department team</h2>
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
                            <td colspan="4">No colleagues found in this department.</td>
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
    <h2>My requests</h2>

    <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form">
        <input type="hidden" name="dashboard_action" value="create_request">
        <label>
            Type
            <select name="request_type" required>
                <option value="leave">Leave</option>
                <option value="shift_change">Shift change</option>
                <option value="other">Other</option>
            </select>
        </label>
        <label>
            Title
            <input type="text" name="request_title" required>
        </label>
        <label>
            Message
            <textarea name="request_message" required></textarea>
        </label>
        <div class="form-actions">
            <button type="submit">Create request</button>
        </div>
    </form>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($moduleRows['requests'] ?? [])): ?>
                    <tr>
                        <td colspan="4">No requests recorded.</td>
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
    <h2>My notifications</h2>

    <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form">
        <input type="hidden" name="dashboard_action" value="create_notification">
        <label>
            Title
            <input type="text" name="notification_title" required>
        </label>
        <label>
            Message
            <textarea name="notification_message" required></textarea>
        </label>
        <div class="form-actions">
            <button type="submit">Create notification</button>
        </div>
    </form>

    <?php if (empty($moduleRows['notifications'] ?? [])): ?>
        <p>No notifications at the moment.</p>
    <?php endif; ?>

    <?php foreach (($moduleRows['notifications'] ?? []) as $notification): ?>
        <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form dashboard-note-form">
            <input type="hidden" name="dashboard_action" value="update_notification">
            <input type="hidden" name="notification_id" value="<?php echo e($notification['id']); ?>">
            <label>
                Title
                <input type="text" name="notification_title" value="<?php echo e($notification['title'] ?? ''); ?>" required>
            </label>
            <label>
                Message
                <textarea name="notification_message" required><?php echo e($notification['message'] ?? ''); ?></textarea>
            </label>
            <div class="form-actions">
                <button type="submit">Update</button>
            </div>
        </form>
    <?php endforeach; ?>
</section>

<script src="assets/js/api.js"></script>
<script>
  window.DashboardConfig = {
    apiCompanies: '<?php echo appUrl('api-companies'); ?>',
    apiDepartments: '<?php echo appUrl('api-departments'); ?>',
    apiUsers: '<?php echo appUrl('api-users'); ?>'
  };
</script>
<script src="assets/js/dashboard.js"></script>
