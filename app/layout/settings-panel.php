<?php
/**
 * Settings modal module.
 *
 * This panel groups the management rubrics for departments, users, roles,
 * shifts and assignments inside one modal shell.
 */
$currentUser = currentUser();
$planner = $dashboardPlannerData ?? [];
$departments = is_array($planner['departments'] ?? null) ? $planner['departments'] : [];
$users = is_array($planner['users'] ?? null) ? $planner['users'] : [];
$shifts = is_array($planner['shifts'] ?? null) ? $planner['shifts'] : [];
$assignments = is_array($planner['assignments'] ?? null) ? $planner['assignments'] : [];
$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'department_manager' => 'Department Manager',
    'employee' => 'Employee',
];
$roleCatalog = [
    ['key' => 'super_admin', 'label' => 'Super Admin', 'color' => '#1f2937', 'icon' => '🛡️'],
    ['key' => 'admin', 'label' => 'Admin', 'color' => '#b98b12', 'icon' => '⚙'],
    ['key' => 'department_manager', 'label' => 'Department Manager', 'color' => '#2f6fed', 'icon' => '👔'],
    ['key' => 'employee', 'label' => 'Employee', 'color' => '#5b6472', 'icon' => '👤'],
];
$activeDepartment = null;
if (!empty($departments)) {
    foreach ($departments as $department) {
        if ((int) ($department['id'] ?? 0) === (int) ($planner['active_department_id'] ?? 0)) {
            $activeDepartment = $department;
            break;
        }
    }
    if (!$activeDepartment) {
        $activeDepartment = $departments[0];
    }
}
$activeDepartmentAssignments = [];
if ($activeDepartment) {
    $activeDepartmentAssignments = array_values(array_filter(
        $assignments,
        static fn (array $assignment): bool => (int) ($assignment['department_id'] ?? 0) === (int) ($activeDepartment['id'] ?? 0)
    ));
}
$assignmentCountByDepartment = [];
foreach ($assignments as $assignment) {
    $deptId = (int) ($assignment['department_id'] ?? 0);
    $assignmentCountByDepartment[$deptId] = ($assignmentCountByDepartment[$deptId] ?? 0) + 1;
}
?>
<section class="dashboard-modal dashboard-settings-modal" id="modal-settings" hidden>
    <div class="crud-modal-card">
        <button type="button" class="dashboard-modal-close" data-modal-close aria-label="Close settings">&times;</button>

        <div class="crud-modal-head settings-modal-head">
            <div>
                <h2 id="settings-modal-title">Management settings</h2>
                <p id="settings-modal-subtitle" class="crud-modal-subtitle">Open a rubric and edit departments, users, roles, shifts or assignments.</p>
            </div>
            <div class="settings-summary settings-summary--compact">
                <div class="settings-summary-card">
                    <span class="settings-summary-label">Company</span>
                    <strong><?php echo e($currentUser['company_name'] ?? 'StaffEase Pro'); ?></strong>
                </div>
                <div class="settings-summary-card">
                    <span class="settings-summary-label">Departments</span>
                    <strong><?php echo count($departments); ?></strong>
                </div>
                <div class="settings-summary-card">
                    <span class="settings-summary-label">Users</span>
                    <strong><?php echo count($users); ?></strong>
                </div>
                <div class="settings-summary-card">
                    <span class="settings-summary-label">Shifts</span>
                    <strong><?php echo count($shifts); ?></strong>
                </div>
            </div>
        </div>

        <div class="settings-tabs" role="tablist" aria-label="Management rubrics">
            <button type="button" class="settings-tab is-active" data-settings-tab="assignments">Assignments</button>
            <button type="button" class="settings-tab" data-settings-tab="departments">Departments</button>
            <button type="button" class="settings-tab" data-settings-tab="users">Users</button>
            <button type="button" class="settings-tab" data-settings-tab="roles">Roles</button>
            <button type="button" class="settings-tab" data-settings-tab="shifts">Shifts</button>
        </div>

        <div class="crud-modal-body settings-modal-body">
            <section class="crud-panel settings-panel" data-settings-panel="assignments">
                <div class="settings-panel-head">
                    <div>
                        <h3>Assignments</h3>
                        <p class="crud-modal-subtitle">Current planner overview for the selected department.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill">Department: <?php echo e($activeDepartment['name'] ?? 'None'); ?></span>
                        <span class="settings-pill">Assigned: <?php echo count($activeDepartmentAssignments); ?></span>
                    </div>
                </div>

                <?php if (!$activeDepartment): ?>
                    <div class="crud-empty-state">No department selected for the planner.</div>
                <?php else: ?>
                    <div class="settings-catalog-grid settings-catalog-grid--wide">
                        <article class="settings-card is-highlight">
                            <div class="settings-card-head">
                                <span class="settings-badge"><?php echo e($activeDepartment['name'] ?? 'Department'); ?></span>
                                <span class="settings-color"><?php echo e($activeDepartment['head_user_name'] ?: 'Unassigned'); ?></span>
                            </div>
                            <p><?php echo e($activeDepartment['description'] ?? ''); ?></p>
                            <div class="settings-meta">
                                Staff: <?php echo count($activeDepartment['users'] ?? []); ?> | Shifts: <?php echo count($activeDepartment['shifts'] ?? []); ?> | Planner assignments: <?php echo count($activeDepartmentAssignments); ?>
                            </div>
                        </article>
                        <article class="settings-card">
                            <div class="settings-card-head">
                                <span class="settings-badge">Visible assignments</span>
                                <span class="settings-color"><?php echo count($activeDepartmentAssignments); ?></span>
                            </div>
                            <div class="settings-list">
                                <?php if (empty($activeDepartmentAssignments)): ?>
                                    <div class="crud-empty-state">No assignments found for this department.</div>
                                <?php else: ?>
                                    <?php foreach (array_slice($activeDepartmentAssignments, 0, 8) as $assignment): ?>
                                        <div class="settings-list-item">
                                            <strong><?php echo e($assignment['work_date'] ?? ''); ?></strong>
                                            <span><?php echo e(($assignment['shift_name'] ?? 'Shift') . ' • ' . ($assignment['user_name'] ?? 'Unassigned')); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endif; ?>
            </section>

            <section class="crud-panel settings-panel" data-settings-panel="departments" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Departments</h3>
                        <p class="crud-modal-subtitle">Edit icon, color and name for each department.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill">Catalog</span>
                        <span class="settings-pill"><?php echo count($departments); ?> items</span>
                    </div>
                </div>

                <div class="settings-catalog-grid">
                    <?php if (empty($departments)): ?>
                        <div class="crud-empty-state">No departments available.</div>
                    <?php else: ?>
                        <?php foreach ($departments as $department): ?>
                            <article class="settings-card settings-catalog-card">
                                <div class="settings-card-head">
                                    <span class="settings-badge"><?php echo e($department['name'] ?? 'Department'); ?></span>
                                    <span class="settings-color">#b98b12</span>
                                </div>
                                <div class="settings-catalog-fields">
                                    <label class="settings-field">
                                        Icon
                                        <input type="text" value="<?php echo e($department['icon'] ?? '🏷️'); ?>" placeholder="🏷️">
                                    </label>
                                    <label class="settings-field">
                                        Color
                                        <input type="text" value="<?php echo e($department['color'] ?? '#b98b12'); ?>" placeholder="#b98b12">
                                    </label>
                                    <label class="settings-field">
                                        Name
                                        <input type="text" value="<?php echo e($department['name'] ?? 'Department'); ?>">
                                    </label>
                                </div>
                                <div class="settings-meta">
                                    Lead: <?php echo e($department['head_user_name'] ?: 'Unassigned'); ?> | Staff: <?php echo count($department['users'] ?? []); ?> | Shifts: <?php echo count($department['shifts'] ?? []); ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="crud-panel settings-panel" data-settings-panel="users" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Users</h3>
                        <p class="crud-modal-subtitle">Assign users to a department and set the role for the selected rubric.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($users); ?> users</span>
                    </div>
                </div>

                <div class="settings-catalog-grid">
                    <?php if (empty($users)): ?>
                        <div class="crud-empty-state">No users assigned yet.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($users, 0, 8) as $user): ?>
                            <article class="settings-card settings-catalog-card">
                                <div class="settings-card-head">
                                    <span class="settings-badge"><?php echo e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User'); ?></span>
                                    <span class="settings-color"><?php echo e($roleLabels[$user['role'] ?? 'employee'] ?? ucfirst((string) ($user['role'] ?? 'employee'))); ?></span>
                                </div>
                                <div class="settings-catalog-fields">
                                    <label class="settings-field">
                                        Role
                                        <input type="text" value="<?php echo e($roleLabels[$user['role'] ?? 'employee'] ?? ucfirst((string) ($user['role'] ?? 'employee'))); ?>">
                                    </label>
                                    <label class="settings-field">
                                        Department
                                        <input type="text" value="<?php echo e($user['department_name'] ?? 'Unassigned'); ?>">
                                    </label>
                                    <label class="settings-field">
                                        Color
                                        <input type="text" value="#b98b12">
                                    </label>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="crud-panel settings-panel" data-settings-panel="roles" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Roles</h3>
                        <p class="crud-modal-subtitle">Create role presets and personalize icon, color and name.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($roleCatalog); ?> presets</span>
                    </div>
                </div>

                <div class="settings-catalog-grid">
                    <?php foreach ($roleCatalog as $roleItem): ?>
                        <article class="settings-card settings-catalog-card">
                            <div class="settings-card-head">
                                <span class="settings-badge"><?php echo e($roleItem['label']); ?></span>
                                <span class="settings-color"><?php echo e($roleItem['color']); ?></span>
                            </div>
                            <div class="settings-catalog-fields">
                                <label class="settings-field">
                                    Icon
                                    <input type="text" value="<?php echo e($roleItem['icon']); ?>">
                                </label>
                                <label class="settings-field">
                                    Name
                                    <input type="text" value="<?php echo e($roleItem['label']); ?>">
                                </label>
                                <label class="settings-field">
                                    Color
                                    <input type="text" value="<?php echo e($roleItem['color']); ?>">
                                </label>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="crud-panel settings-panel" data-settings-panel="shifts" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Shifts</h3>
                        <p class="crud-modal-subtitle">Create working shifts and bind them to a department.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($shifts); ?> shifts</span>
                    </div>
                </div>

                <div class="settings-catalog-grid">
                    <?php if (empty($shifts)): ?>
                        <div class="crud-empty-state">No shifts available.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($shifts, 0, 8) as $shift): ?>
                            <article class="settings-card settings-catalog-card">
                                <div class="settings-card-head">
                                    <span class="settings-badge"><?php echo e($shift['name'] ?? 'Shift'); ?></span>
                                    <span class="settings-color"><?php echo e(($shift['start_time'] ?? '--:--') . ' - ' . ($shift['end_time'] ?? '--:--')); ?></span>
                                </div>
                                <div class="settings-catalog-fields">
                                    <label class="settings-field">
                                        Department
                                        <input type="text" value="<?php echo e($shift['department_name'] ?? ''); ?>">
                                    </label>
                                    <label class="settings-field">
                                        Start
                                        <input type="text" value="<?php echo e($shift['start_time'] ?? '--:--'); ?>">
                                    </label>
                                    <label class="settings-field">
                                        End
                                        <input type="text" value="<?php echo e($shift['end_time'] ?? '--:--'); ?>">
                                    </label>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="admin-actions settings-actions">
            <button type="button" class="admin-action-link">Create</button>
            <button type="button" class="admin-action-link">Edit</button>
            <button type="button" class="admin-action-link">Delete</button>
        </div>
    </div>
</section>
