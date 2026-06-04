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

?>

<div class="admin-shell dashboard-shell">
    <div class="admin-hero">
            <?php if (($currentUser['role'] ?? '') === 'super_admin'): ?>
            <h1><?php echo e($pageTitle ?? 'Dashboard'); ?></h1>
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
                    <div class="dashboard-calendar-navigator overlay" id="dashboard-calendar-navigator" aria-hidden="true">
                        <div class="dashboard-calendar-navigator-top">
                            <button type="button" class="dashboard-calendar-navigator-close" data-calendar-navigator-toggle aria-label="Toggle calendar navigator">⌃</button>
                            <input type="text" class="dashboard-calendar-navigator-range" value="<?php echo e(date('d/m/y')); ?> - <?php echo e(date('d/m/y')); ?>" readonly data-calendar-range-display>
                        </div>
                        <div class="dashboard-calendar-navigator-modes">
                            <button type="button" class="dashboard-calendar-navigator-pill" data-calendar-mode="week">7 days</button>
                            <button type="button" class="dashboard-calendar-navigator-pill is-active" data-calendar-mode="fortnight">15 days</button>
                            <button type="button" class="dashboard-calendar-navigator-pill" data-calendar-mode="month">1 month</button>
                        </div>
                        <div class="dashboard-calendar-navigator-actions">
                            <button type="button" class="dashboard-calendar-navigator-action" data-calendar-nav="prev">‹</button>
                            <button type="button" class="dashboard-calendar-navigator-action is-square" data-calendar-nav="today" aria-label="Go to today">today</button>
                            <button type="button" class="dashboard-calendar-navigator-action" data-calendar-nav="next">›</button>
                        </div>
                    </div>

                    <div class="dashboard-calendar-headline">
                        <div>
                            <h2 class="dashboard-calendar-title" data-calendar-title><?php echo e($dashboardCalendarScopeLabel ?? 'Calendar'); ?></h2>
                            <p class="dashboard-calendar-title-meta" data-calendar-stats><?php echo e(date('d M Y')); ?></p>
                        </div>
                    </div>

                    <div class="dashboard-calendar-frame">
                        <div
                            class="dashboard-calendar-grid"
                            data-dashboard-calendar-shell
                            data-calendar-mode="<?php echo e($dashboardCalendarMode ?? 'week'); ?>"
                            data-calendar-today="<?php echo e($dashboardCalendarToday ?? date('Y-m-d')); ?>"
                            data-calendar-events="<?php echo e(json_encode($dashboardCalendarEvents ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"
                        ></div>
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

