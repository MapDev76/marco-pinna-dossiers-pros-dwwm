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

<template id="crud-template-placeholder">
    <div class="crud-panel">
        <h3>Common CRUD shell</h3>
        <p>This modal is shared by every dashboard action. We will adapt its content per entity in the next step.</p>
        <div class="crud-empty-state">No CRUD template has been connected for this element yet.</div>
    </div>
</template>

