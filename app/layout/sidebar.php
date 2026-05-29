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
        <button type="button" class="dashboard-sidebar-handle" data-sidebar-hover-handle aria-label="Open sidebar">
            <span aria-hidden="true">›</span>
        </button>
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
            <section class="dashboard-sidebar-group dashboard-sidebar-departments-panel">
                <p class="dashboard-sidebar-group-title"><span>📁</span> Departments</p>
                <div class="dashboard-sidebar-department-list">
                    <?php foreach (($dashboardPlannerData['departments'] ?? []) as $department): ?>
                        <button type="button"
                            class="dashboard-sidebar-department-button <?php echo ((int) ($dashboardPlannerData['active_department_id'] ?? 0) === (int) $department['id']) ? 'is-active' : ''; ?>"
                            data-planner-department-id="<?php echo (int) $department['id']; ?>"
                            data-planner-department-name="<?php echo e($department['name'] ?? ''); ?>"
                            data-planner-department-description="<?php echo e($department['description'] ?? ''); ?>">
                            <?php echo e($department['name'] ?? 'Department'); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="dashboard-sidebar-planner-detail" data-sidebar-planner-detail>
                    <div class="dashboard-sidebar-planner-placeholder">Select a department to show employees and shifts.</div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</aside>
