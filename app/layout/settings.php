<?php
/**
 * Settings modal used as the central management hub.
 *
 * The panel exposes departments, users, roles and shifts in a single place so
 * dashboard management can live outside the sidebar.
 */
if (!isLoggedIn()) {
    return;
}

$currentUser = currentUser();
$planner = $dashboardPlannerData ?? [];
$departments = is_array($planner['departments'] ?? null) ? $planner['departments'] : [];
$users = is_array($planner['users'] ?? null) ? $planner['users'] : [];
$shifts = is_array($planner['shifts'] ?? null) ? $planner['shifts'] : [];
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
?>
<section class="dashboard-modal dashboard-settings-modal" id="modal-settings" hidden>
    <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
    <h2>Management settings</h2>
    <p>Central place for departments, users, roles and shift setup. Choose icon, color and name for each management item.</p>

    <div class="settings-summary">
        <div class="settings-summary-card">
            <span class="settings-summary-label">Active company</span>
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
    </div>

    <div class="settings-tabs" role="tablist" aria-label="Management sections">
        <button type="button" class="settings-tab is-active">Departments</button>
        <button type="button" class="settings-tab">Users</button>
        <button type="button" class="settings-tab">Roles</button>
        <button type="button" class="settings-tab">Shifts</button>
    </div>

    <div class="settings-board">
        <div class="settings-card is-highlight">
            <div class="settings-card-head">
                <span class="settings-badge">Departments</span>
                <span class="settings-color">Manage icon, color, name</span>
            </div>
            <p>Define the department label, choose a visual icon and color, then assign a lead and workload structure.</p>
            <div class="settings-meta">Editable catalog for company organization and calendar grouping.</div>
        </div>
        <div class="settings-card">
            <div class="settings-card-head">
                <span class="settings-badge">Users</span>
                <span class="settings-color"><?php echo count($users); ?> people</span>
            </div>
            <p>Assign users to departments, update roles, and keep the team list aligned with the calendar.</p>
            <div class="settings-meta">Quick access to department membership and profile updates.</div>
        </div>
        <div class="settings-card">
            <div class="settings-card-head">
                <span class="settings-badge">Roles</span>
                <span class="settings-color"><?php echo count($roleCatalog); ?> presets</span>
            </div>
            <p>Create role presets for each department and control the operations available to that role.</p>
            <div class="settings-meta">Use this area for role permissions and per-department policies.</div>
        </div>
        <div class="settings-card">
            <div class="settings-card-head">
                <span class="settings-badge">Shifts</span>
                <span class="settings-color"><?php echo count($shifts); ?> shifts</span>
            </div>
            <p>Create working shifts with start and end times and bind them to a specific department.</p>
            <div class="settings-meta">The shift catalog feeds the planner and calendar assignments.</div>
        </div>
    </div>

    <div class="settings-form-grid">
        <div class="settings-field span-2">
            Department catalog preview
            <div class="settings-catalog-grid">
                <?php if (empty($departments)): ?>
                    <div class="settings-card">
                        <p>No departments available.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($departments as $department): ?>
                        <article class="settings-card settings-catalog-card">
                            <div class="settings-card-head">
                                <span class="settings-badge"><?php echo e($department['name'] ?? 'Department'); ?></span>
                                <span class="settings-color"><?php echo e((string) ($department['id'] ?? '')); ?></span>
                            </div>
                            <div class="settings-catalog-fields">
                                <label class="settings-field">
                                    Icon
                                    <input type="text" value="<?php echo e($department['icon'] ?? '🏷️'); ?>" readonly>
                                </label>
                                <label class="settings-field">
                                    Color
                                    <input type="text" value="<?php echo e($department['color'] ?? '#b98b12'); ?>" readonly>
                                </label>
                                <label class="settings-field">
                                    Name
                                    <input type="text" value="<?php echo e($department['name'] ?? 'Department'); ?>" readonly>
                                </label>
                            </div>
                            <div class="settings-meta">
                                Lead: <?php echo e($department['head_user_name'] ?: 'Unassigned'); ?> | Staff: <?php echo count($department['users'] ?? []); ?> | Shifts: <?php echo count($department['shifts'] ?? []); ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="settings-field">
            Users and roles
            <div class="settings-catalog-grid">
                <?php if (empty($users)): ?>
                    <div class="settings-card"><p>No users assigned yet.</p></div>
                <?php else: ?>
                    <?php foreach (array_slice($users, 0, 6) as $user): ?>
                        <article class="settings-card settings-catalog-card">
                            <div class="settings-card-head">
                                <span class="settings-badge"><?php echo e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User'); ?></span>
                                <span class="settings-color"><?php echo e($roleLabels[$user['role'] ?? 'employee'] ?? ucfirst((string) ($user['role'] ?? 'employee'))); ?></span>
                            </div>
                            <div class="settings-catalog-fields">
                                <label class="settings-field">
                                    Role
                                    <input type="text" value="<?php echo e($roleLabels[$user['role'] ?? 'employee'] ?? ucfirst((string) ($user['role'] ?? 'employee'))); ?>" readonly>
                                </label>
                                <label class="settings-field">
                                    Department
                                    <input type="text" value="<?php echo e($user['department_name'] ?? 'Unassigned'); ?>" readonly>
                                </label>
                                <label class="settings-field">
                                    Color
                                    <input type="text" value="#b98b12" readonly>
                                </label>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="settings-field">
            Roles catalog
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
                                <input type="text" value="<?php echo e($roleItem['icon']); ?>" readonly>
                            </label>
                            <label class="settings-field">
                                Name
                                <input type="text" value="<?php echo e($roleItem['label']); ?>" readonly>
                            </label>
                            <label class="settings-field">
                                Color
                                <input type="text" value="<?php echo e($roleItem['color']); ?>" readonly>
                            </label>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="settings-field">
            Shift planner
            <div class="settings-catalog-grid">
                <?php if (empty($shifts)): ?>
                    <div class="settings-card"><p>No shifts available.</p></div>
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
                                    <input type="text" value="<?php echo e($shift['department_name'] ?? ''); ?>" readonly>
                                </label>
                                <label class="settings-field">
                                    Start
                                    <input type="text" value="<?php echo e($shift['start_time'] ?? '--:--'); ?>" readonly>
                                </label>
                                <label class="settings-field">
                                    End
                                    <input type="text" value="<?php echo e($shift['end_time'] ?? '--:--'); ?>" readonly>
                                </label>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="admin-actions settings-actions">
        <button type="button" class="admin-action-link">Create</button>
        <button type="button" class="admin-action-link">Edit</button>
        <button type="button" class="admin-action-link">Delete</button>
    </div>
</section>
