<!-- Shared dashboard: sidebar navigation and centered modals by logged-in role. -->
<?php
$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';
$profile = $profile ?? [];
$moduleRows = $moduleRows ?? [];
$roleLabels = [
    'super_admin' => t('roles.super_admin'),
    'admin' => t('roles.admin'),
    'department_manager' => t('roles.department_manager'),
    'employee' => t('roles.employee'),
];
$requestStatusLabels = [
    'pending' => t('employee.status_pending', ['fallback' => 'Pending']),
    'approved' => t('employee.status_approved', ['fallback' => 'Approved']),
    'rejected' => t('employee.status_rejected', ['fallback' => 'Rejected']),
];
$requestTypeLabels = [
    'notification' => t('common.notification', ['fallback' => 'Notification']),
    'leave' => t('common.leave', ['fallback' => 'Leave']),
    'shift_change' => t('common.shift_change', ['fallback' => 'Shift change']),
    'other' => t('common.other', ['fallback' => 'Other']),
    'admin_note' => t('common.admin_note', ['fallback' => 'Admin note']),
];

?>

<section class="admin-shell dashboard-shell" aria-label="<?php echo e(t('common.dashboard')); ?>">
    <header class="admin-hero">
            <?php if (($currentUser['role'] ?? '') === 'super_admin'): ?>
            <h1><?php echo e($pageTitle ?? t('common.dashboard')); ?></h1>
        <?php endif; ?>
        <!-- No welcome message or titles for non-super users per design -->
    </header>

        <section class="dashboard-main" aria-label="<?php echo e(t('common.quick_actions')); ?>">
            <?php if ($role === 'super_admin'): ?>
                <div class="admin-grid">
                    <section class="admin-card admin-stat">
                        <span class="admin-stat-value"><?php echo e($stats['users'] ?? 0); ?></span>
                        <span class="admin-stat-label"><?php echo e(t('common.users')); ?></span>
                    </section>
                    <section class="admin-card admin-stat">
                        <span class="admin-stat-value"><?php echo e($stats['companies'] ?? 0); ?></span>
                        <span class="admin-stat-label"><?php echo e(t('common.companies')); ?></span>
                    </section>
                    <section class="admin-card admin-stat">
                        <span class="admin-stat-value"><?php echo e($stats['departments'] ?? 0); ?></span>
                        <span class="admin-stat-label"><?php echo e(t('common.departments')); ?></span>
                    </section>
                </div>

                <section class="admin-card company-directory-section">

                    <div class="dashboard-company-grid">
                        <?php if (empty($moduleRows['company_directory'] ?? [])): ?>
                            <p><?php echo e(t('common.no_companies_to_display')); ?></p>
                        <?php endif; ?>

                        <?php foreach (($moduleRows['company_directory'] ?? []) as $company): ?>
                            <article class="dashboard-company-card">
                                <div class="dashboard-company-card-head">
                                    <div>
                                        <h3><?php echo e($company['name']); ?></h3>
                                        <p><?php echo e($company['city'] ?? '-'); ?></p>
                                    </div>
                                    <?php if (!empty($company['logo_path'])): ?>
                                        <img src="<?php echo e($company['logo_path']); ?>" alt="<?php echo e($company['name']); ?> logo" class="dashboard-company-logo" loading="lazy" decoding="async">
                                    <?php endif; ?>
                                </div>

                                <div class="dashboard-company-metrics">
                                    <div><span><?php echo e(t('common.users')); ?></span><strong><?php echo e($company['users_count'] ?? 0); ?></strong></div>
                                    <div><span><?php echo e(t('common.departments')); ?></span><strong><?php echo e($company['departments_count'] ?? 0); ?></strong></div>
                                    <div><span><?php echo e(t('common.signature_ip')); ?></span><strong><?php echo e($company['signature_ip'] ?: '-'); ?></strong></div>
                                </div>

                                <p><strong><?php echo e(t('common.admins')); ?>:</strong> <?php echo e(empty($company['admins']) ? t('common.none') : implode(', ', $company['admins'])); ?></p>
                                <p><strong><?php echo e(t('common.department_heads')); ?>:</strong> <?php echo e(empty($company['heads']) ? t('common.none') : implode(', ', $company['heads'])); ?></p>
                                <p><strong><?php echo e(t('common.departments')); ?>:</strong> <?php echo e(empty($company['departments']) ? t('common.none') : implode(', ', $company['departments'])); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php elseif ($role === 'admin' || $role === 'department_manager'): ?>
                <section class="admin-card dashboard-calendar-shell">
                    <div class="dashboard-calendar-headline">
                        <div class="dashboard-calendar-headline-main">
                            <div class="dashboard-calendar-top-row">
                                <div class="dashboard-calendar-range-actions">
                                    <input type="text" class="dashboard-calendar-navigator-range" value="<?php echo e(date('d/m/y')); ?> - <?php echo e(date('d/m/y')); ?>" readonly data-calendar-range-display>
                                    <button type="button" class="dashboard-calendar-navigator-action is-square" data-calendar-nav="today"><?php echo e(date('d/m/Y', strtotime((string) ($dashboardCalendarToday ?? date('Y-m-d'))))); ?></button>
                                </div>
                                <div class="dashboard-calendar-title-nav">
                                    <button type="button" class="dashboard-calendar-navigator-action" data-calendar-nav="prev" aria-label="Prev">‹</button>
                                    <h2 class="dashboard-calendar-title" data-calendar-title><?php echo e($dashboardCalendarScopeLabel ?? t('common.calendar')); ?></h2>
                                    <button type="button" class="dashboard-calendar-navigator-action" data-calendar-nav="next" aria-label="Next">›</button>
                                </div>
                                <p class="dashboard-calendar-title-meta" data-calendar-stats><?php echo e(date('d M Y')); ?></p>
                                <div class="dashboard-calendar-navigator-actions is-inline">
                                    <button type="button" class="dashboard-calendar-navigator-pill" data-calendar-mode="day"><?php echo e(t('common.one_day', ['fallback' => '1 day'])); ?></button>
                                    <button type="button" class="dashboard-calendar-navigator-pill" data-calendar-mode="week"><?php echo e(t('common.seven_days')); ?></button>
                                    <button type="button" class="dashboard-calendar-navigator-pill is-active" data-calendar-mode="fortnight"><?php echo e(t('common.fifteen_days')); ?></button>
                                    <button type="button" class="dashboard-calendar-navigator-pill" data-calendar-mode="month"><?php echo e(t('common.one_month')); ?></button>
                                </div>
                            </div>
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
                    <h2><?php echo e(t('common.my_shifts')); ?></h2>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th><?php echo e(t('common.date', ['fallback' => 'Date'])); ?></th>
                                    <th><?php echo e(t('common.shift')); ?></th>
                                    <th><?php echo e(t('common.department')); ?></th>
                                    <th><?php echo e(t('common.status')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($moduleRows['shifts'] ?? [])): ?>
                                    <tr>
                                        <td colspan="4"><?php echo e(t('common.no_shifts_available')); ?></td>
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
    </section>
</section>

