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
        <button type="button" class="dashboard-sidebar-handle" data-sidebar-hover-handle aria-label="Apri sidebar">
            <span aria-hidden="true">›</span>
        </button>
        <div class="dashboard-sidebar-scroll">
        <div class="dashboard-sidebar-brand">
            <!-- Brand/title intentionally empty to match design: no title above sidebar -->
        </div>

        <?php foreach ($dashboardSidebarSections as $section): ?>
            <section class="dashboard-sidebar-group">
                <?php if (isset($section['title']) && trim($section['title']) === 'Management'): ?>
                    <div class="dashboard-sidebar-group-title"><span><?php echo e($section['icon'] ?? '•'); ?></span> <?php echo e($section['title']); ?></div>
                    <div>
                        <button type="button" class="dashboard-sidebar-link management-toggle" aria-expanded="false">Management</button>
                        <div class="dashboard-management-list" hidden>
                            <?php foreach (($section['buttons'] ?? []) as $button): ?>
                                <button type="button" class="dashboard-sidebar-link <?php echo !empty($button['variant']) ? 'is-' . e($button['variant']) : ''; ?>" data-modal-target="<?php echo e($button['target'] ?? ''); ?>" data-modal-entity="<?php echo e($button['entity'] ?? ''); ?>" data-modal-title="<?php echo e($button['title'] ?? ($button['label'] ?? '')); ?>">
                                    <?php echo e($button['label'] ?? 'Action'); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="dashboard-sidebar-group-title"><span><?php echo e($section['icon'] ?? '•'); ?></span> <?php echo e($section['title'] ?? 'Section'); ?></p>
                    <?php foreach (($section['buttons'] ?? []) as $button): ?>
                        <button type="button" class="dashboard-sidebar-link <?php echo !empty($button['variant']) ? 'is-' . e($button['variant']) : ''; ?>" data-modal-target="<?php echo e($button['target'] ?? ''); ?>" data-modal-entity="<?php echo e($button['entity'] ?? ''); ?>" data-modal-title="<?php echo e($button['title'] ?? ($button['label'] ?? '')); ?>">
                            <?php echo e($button['label'] ?? 'Action'); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <?php if (in_array($sidebarRole, ['admin', 'department_manager'], true)): ?>
            <section class="dashboard-sidebar-group dashboard-sidebar-departments-panel">
                <button type="button" class="dashboard-sidebar-group-title dashboard-sidebar-department-toggle" data-sidebar-department-toggle aria-expanded="false">
                    <span>📁</span> Departments
                </button>
                <div class="dashboard-sidebar-current-department" data-sidebar-current-department></div>
                <div>
                    <div class="dashboard-management-list dashboard-sidebar-department-list">
                        <?php foreach (($dashboardPlannerData['departments'] ?? []) as $department): ?>
                            <?php $deptIcon = $department['icon'] ?? '🏷️'; ?>
                            <?php $deptColor = $department['color'] ?? '#b98b12'; ?>
                            <button type="button"
                                class="dashboard-sidebar-department-button <?php echo ((int) ($dashboardPlannerData['active_department_id'] ?? 0) === (int) $department['id']) ? 'is-active' : ''; ?>"
                                data-planner-department-id="<?php echo (int) $department['id']; ?>"
                                data-planner-department-name="<?php echo e($department['name'] ?? ''); ?>"
                                data-planner-department-description="<?php echo e($department['description'] ?? ''); ?>"
                                data-planner-department-icon="<?php echo e($deptIcon); ?>"
                                data-planner-department-color="<?php echo e($deptColor); ?>">
                                <span style="color: <?php echo e($deptColor); ?>;"><?php echo e($deptIcon); ?></span>
                                <span><?php echo e($department['name'] ?? 'Department'); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="dashboard-sidebar-planner-detail" data-sidebar-planner-detail>
                    <?php
                    $activeDeptId = (int) ($dashboardPlannerData['active_department_id'] ?? 0);
                    $activeDept = null;
                    foreach (($dashboardPlannerData['departments'] ?? []) as $d) {
                        if ((int) ($d['id'] ?? 0) === $activeDeptId) { $activeDept = $d; break; }
                    }
                    if (!$activeDept) {
                        echo '<div class="dashboard-sidebar-planner-placeholder">Select a department to show employees and shifts.</div>';
                    } else {
                        $users = $activeDept['users'] ?? [];
                        $shifts = $activeDept['shifts'] ?? [];
                        $activeDeptIcon = $activeDept['icon'] ?? '🏷️';
                        $activeDeptColor = $activeDept['color'] ?? '#b98b12';
                        ?>
                        <div class="dashboard-sidebar-planner-title">
                            <span style="color: <?php echo e($activeDeptColor); ?>;"><?php echo e($activeDeptIcon); ?> <?php echo e($activeDept['name'] ?? 'Department'); ?></span>
                            <span><?php echo count($users); ?> staff</span>
                        </div>
                        <div class="dashboard-sidebar-planner-description"><?php echo e($activeDept['description'] ?? ''); ?></div>
                        <div>
                            <div class="dashboard-sidebar-group-title"><span>👤</span> Employees</div>
                            <div class="dashboard-sidebar-chip-group">
                                <?php if (!empty($users)): foreach ($users as $user): ?>
                                    <button type="button" class="dashboard-sidebar-user-chip" draggable="true" data-user-id="<?php echo (int) ($user['id'] ?? 0); ?>" data-user-name="<?php echo e(trim((($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))); ?>" title="Trascina sul calendario">
                                        <?php echo e(trim((($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))); ?>
                                    </button>
                                <?php endforeach; else: ?>
                                    <div class="dashboard-sidebar-planner-placeholder">No employees in this department.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div class="dashboard-sidebar-group-title"><span>⏱</span> Shifts</div>
                            <div class="dashboard-sidebar-chip-group">
                                <?php if (!empty($shifts)): foreach ($shifts as $shift): ?>
                                    <?php $shiftIcon = $shift['icon'] ?? '🕒'; ?>
                                    <?php $shiftColor = $shift['color'] ?? '#2f6fed'; ?>
                                    <button type="button" class="dashboard-sidebar-shift-chip" data-shift-id="<?php echo (int) ($shift['id'] ?? 0); ?>" style="--shift-chip-color: <?php echo e($shiftColor); ?>;">
                                        <span class="dashboard-sidebar-shift-icon"><?php echo e($shiftIcon); ?></span>
                                        <span><?php echo e($shift['name'] ?? 'Shift'); ?></span>
                                    </button>
                                <?php endforeach; else: ?>
                                    <div class="dashboard-sidebar-planner-placeholder">No shifts configured.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </section>
        <?php endif; ?>
        </div>
    </div>
</aside>
