<?php
/**
 * Dashboard sidebar component.
 *
 * Renders the left navigation for dashboard actions. The variable
 * `$dashboardSidebarSections` must be an array of sections with `title` and
 * `buttons` entries. Each button may include `target`, `entity`, and `title`.
 */
if (!isset($dashboardSidebarSections) || !is_array($dashboardSidebarSections)) {
    return;
}

$sidebarRoleLabel = $dashboardSidebarRoleLabel ?? 'User';
$currentSidebarUser = currentUser();
$sidebarRole = $currentSidebarUser['role'] ?? 'employee';
?>
<aside id="dashboard-sidebar" class="app-sidebar" aria-label="Dashboard sidebar navigation">
    <div class="dashboard-sidebar-shell">
        <div class="dashboard-sidebar-brand">
            <!-- Brand/title intentionally empty to match design: no title above sidebar -->
        </div>

        <?php foreach ($dashboardSidebarSections as $section): ?>
            <section class="dashboard-sidebar-group">
                <p class="dashboard-sidebar-group-title"><span><?php echo e($section['icon'] ?? '•'); ?></span> <?php echo e($section['title'] ?? 'Section'); ?></p>
                <?php foreach (($section['buttons'] ?? []) as $button): ?>
                    <button type="button" class="dashboard-sidebar-link <?php echo !empty($button['variant']) ? 'is-' . e($button['variant']) : ''; ?>" data-modal-target="<?php echo e($button['target'] ?? ''); ?>" data-modal-entity="<?php echo e($button['entity'] ?? ''); ?>" data-modal-title="<?php echo e($button['title'] ?? ($button['label'] ?? '')); ?>">
                        <?php echo e($button['label'] ?? 'Action'); ?>
                    </button>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>

        <?php if (in_array($sidebarRole, ['admin', 'department_manager'], true)): ?>
            <section class="dashboard-sidebar-group dashboard-sidebar-calendar-panel">
                <p class="dashboard-sidebar-group-title"><span>🗓</span> Calendar</p>
                <div class="dashboard-sidebar-control-grid">
                    <button type="button" class="dashboard-sidebar-control-button" data-calendar-nav="prev">Prev</button>
                    <button type="button" class="dashboard-sidebar-control-button" data-calendar-nav="today">Today</button>
                    <button type="button" class="dashboard-sidebar-control-button" data-calendar-nav="next">Next</button>
                </div>
                <div class="dashboard-sidebar-control-grid dashboard-sidebar-control-grid--modes">
                    <button type="button" class="dashboard-sidebar-control-button is-active" data-calendar-mode="day">Day</button>
                    <button type="button" class="dashboard-sidebar-control-button" data-calendar-mode="week">Week</button>
                    <button type="button" class="dashboard-sidebar-control-button" data-calendar-mode="fortnight">Fortnight</button>
                    <button type="button" class="dashboard-sidebar-control-button" data-calendar-mode="month">Month</button>
                    <button type="button" class="dashboard-sidebar-control-button" data-calendar-mode="year">Year</button>
                </div>
            </section>
        <?php endif; ?>
    </div>
</aside>
