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
$plannerCompany = is_array($planner['company'] ?? null) ? $planner['company'] : [];
$scopeCompanyId = (int) ($plannerCompany['id'] ?? ($departments[0]['company_id'] ?? ($currentUser['company_id'] ?? 0)));
$scopeCompanyName = (string) ($plannerCompany['name'] ?? ($currentUser['company_name'] ?? 'StaffEase Pro'));
$scopeCompanies = is_array($planner['companies'] ?? null) ? $planner['companies'] : [];
$visibleUsers = $users;
$currentRole = $currentUser['role'] ?? '';
if ($currentRole === 'admin') {
    $visibleUsers = array_values(array_filter($users, static function($u) use ($scopeCompanyId) {
        $rowCompanyId = (int) ($u['company_id'] ?? 0);
        if ($scopeCompanyId > 0 && $rowCompanyId > 0 && $rowCompanyId !== $scopeCompanyId) {
            return false;
        }

        return (($u['role'] ?? '') !== 'super_admin');
    }));
}
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

$visibleDepartments = $departments;
if (($currentRole ?? '') === 'admin') {
    $visibleDepartments = array_values(array_filter($departments, static fn($d) => (int) ($d['company_id'] ?? 0) === $scopeCompanyId));
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
                    <strong><?php echo e($scopeCompanyName); ?></strong>
                </div>
                <div class="settings-summary-card">
                    <span class="settings-summary-label">Departments</span>
                    <strong><?php echo count($departments); ?></strong>
                </div>
                <div class="settings-summary-card">
                    <span class="settings-summary-label">Users</span>
                    <strong><?php echo count($visibleUsers); ?></strong>
                </div>
                <div class="settings-summary-card">
                    <span class="settings-summary-label">Shifts</span>
                    <strong><?php echo count($shifts); ?></strong>
                </div>
            </div>

            <?php if ($currentRole === 'super_admin' && !empty($scopeCompanies)): ?>
                <form method="get" class="settings-company-switcher">
                    <input type="hidden" name="route" value="dashboard">
                    <input type="hidden" name="modal" value="settings">
                    <input type="hidden" name="settings_tab" value="" data-settings-tab-input>
                    <label class="settings-field">
                        Company in settings
                        <select name="settings_company_id" data-settings-company-select>
                            <?php foreach ($scopeCompanies as $company): ?>
                                <option value="<?php echo (int) ($company['id'] ?? 0); ?>" <?php echo ((int) ($company['id'] ?? 0) === $scopeCompanyId) ? 'selected' : ''; ?>>
                                    <?php echo e($company['name'] ?? 'Company'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            <?php endif; ?>
        </div>

        <div class="settings-tabs" role="tablist" aria-label="Management rubrics">
            <button type="button" class="settings-tab" data-settings-tab="users">Users</button>
            <button type="button" class="settings-tab" data-settings-tab="departments">Departments</button>
            <button type="button" class="settings-tab" data-settings-tab="assignments">Assignments</button>
            <button type="button" class="settings-tab" data-settings-tab="roles">Roles</button>
            <button type="button" class="settings-tab" data-settings-tab="shifts">Shifts</button>
        </div>

        <div class="crud-modal-body settings-modal-body">
            <section class="crud-panel settings-panel" data-settings-panel="assignments" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Assignments</h3>
                        <p class="crud-modal-subtitle">Planner assignments in list mode, editable row by row.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill">Company: <?php echo e($scopeCompanyName); ?></span>
                        <span class="settings-pill">Assignments: <?php echo count($assignments); ?></span>
                    </div>
                </div>

                <div class="settings-list-wrap">
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-assignment">
                        <strong>Date</strong>
                        <span>Department</span>
                        <span>Shift</span>
                        <span>User</span>
                        <span>Status</span>
                        <span>Actions</span>
                    </div>

                    <?php if (empty($assignments)): ?>
                        <div class="crud-empty-state">No assignments available.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($assignments, 0, 250) as $assignment): ?>
                            <article class="settings-list-item-wrap" data-assignment-id="<?php echo (int) ($assignment['assignment_id'] ?? 0); ?>">
                                <div class="settings-list-row settings-list-cols settings-list-cols-assignment">
                                    <strong><?php echo e($assignment['work_date'] ?? ''); ?></strong>
                                    <span><?php echo e($assignment['department_name'] ?? '--'); ?></span>
                                    <span><?php echo e($assignment['shift_name'] ?? '--'); ?></span>
                                    <span><?php echo e($assignment['user_name'] ?? 'Unassigned'); ?></span>
                                    <span><?php echo e($assignment['status'] ?? 'assigned'); ?></span>
                                    <div class="settings-inline-actions">
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-assignment-edit" aria-label="Edit assignment" title="Edit assignment">✎</button>
                                    </div>
                                </div>
                                <div class="settings-edit-drawer" hidden>
                                    <div class="settings-list-cols settings-list-cols-assignment-edit">
                                        <label class="settings-field">Work date
                                            <input data-field="work_date" type="date" value="<?php echo e($assignment['work_date'] ?? ''); ?>">
                                        </label>
                                        <label class="settings-field">Shift
                                            <select data-field="shift_id">
                                                <?php foreach ($shifts as $shift): ?>
                                                    <option value="<?php echo (int) ($shift['id'] ?? 0); ?>" <?php echo ((int) ($assignment['shift_id'] ?? 0) === (int) ($shift['id'] ?? 0)) ? 'selected' : ''; ?>>
                                                        <?php echo e(($shift['name'] ?? 'Shift') . ' • ' . ($shift['department_name'] ?? '')); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="settings-field">Status
                                            <select data-field="status">
                                                <?php $assignmentStatus = (string) ($assignment['status'] ?? 'assigned'); ?>
                                                <option value="assigned" <?php echo $assignmentStatus === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                <option value="in_progress" <?php echo $assignmentStatus === 'in_progress' ? 'selected' : ''; ?>>In progress</option>
                                                <option value="completed" <?php echo $assignmentStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $assignmentStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </label>
                                        <div class="settings-inline-actions">
                                            <button type="button" class="admin-action-link settings-assignment-save">Save</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-assignment-cancel">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="crud-panel settings-panel" data-settings-panel="departments" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Departments</h3>
                        <p class="crud-modal-subtitle">List view with inline edit drawer for each department.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill">Catalog</span>
                        <span class="settings-pill"><?php echo count($visibleDepartments); ?> items</span>
                    </div>
                </div>

                <div class="settings-list-head settings-create-row" data-dept-create-row>
                    <div class="settings-list-cols settings-list-cols-dept">
                        <strong>New department</strong>
                        <label class="settings-field">Name<input data-field="name" type="text" value=""></label>
                        <label class="settings-field">Icon<input data-field="icon" type="text" value="🏷️"></label>
                        <label class="settings-field">Color<input data-field="color" type="text" value="#b98b12"></label>
                        <label class="settings-field">Company
                            <select data-field="company_id">
                                <?php foreach ($scopeCompanies as $c): ?>
                                    <?php if ($currentRole !== 'super_admin' && (int) ($c['id'] ?? 0) !== $scopeCompanyId) continue; ?>
                                    <option value="<?php echo (int) ($c['id'] ?? 0); ?>" <?php echo ((int) ($c['id'] ?? 0) === $scopeCompanyId) ? 'selected' : ''; ?>><?php echo e($c['name'] ?? 'Company'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="settings-inline-actions">
                            <button type="button" class="admin-action-link settings-dept-create">Create</button>
                            <button type="button" class="admin-action-link admin-action-link-secondary settings-dept-reset">Reset</button>
                        </div>
                    </div>
                </div>

                <div class="settings-list-wrap">
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-dept">
                        <strong>Name</strong>
                        <span>Company</span>
                        <span>Lead</span>
                        <span>Staff</span>
                        <span>Shifts</span>
                        <span>Actions</span>
                    </div>

                    <?php if (empty($visibleDepartments)): ?>
                        <div class="crud-empty-state">No departments available.</div>
                    <?php else: ?>
                        <?php foreach ($visibleDepartments as $department): ?>
                            <article class="settings-list-item-wrap" data-department-id="<?php echo (int) ($department['id'] ?? 0); ?>">
                                <div class="settings-list-row settings-list-cols settings-list-cols-dept">
                                    <strong><?php echo e($department['name'] ?? 'Department'); ?></strong>
                                    <span><?php echo e($department['company_name'] ?? $scopeCompanyName); ?></span>
                                    <span><?php echo e($department['head_user_name'] ?: 'Unassigned'); ?></span>
                                    <span><?php echo count($department['users'] ?? []); ?></span>
                                    <span><?php echo count($department['shifts'] ?? []); ?></span>
                                    <div class="settings-inline-actions">
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-dept-edit" aria-label="Edit department" title="Edit department">✎</button>
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-action-icon-danger settings-dept-delete" data-department-id="<?php echo (int) ($department['id'] ?? 0); ?>" aria-label="Delete department" title="Delete department">🗑</button>
                                    </div>
                                </div>
                                <div class="settings-edit-drawer" hidden>
                                    <div class="settings-list-cols settings-list-cols-dept-edit">
                                        <label class="settings-field">Name<input data-field="name" type="text" value="<?php echo e($department['name'] ?? 'Department'); ?>"></label>
                                        <label class="settings-field">Icon<input data-field="icon" type="text" value="<?php echo e($department['icon'] ?? '🏷️'); ?>" placeholder="🏷️"></label>
                                        <label class="settings-field">Color<input data-field="color" type="text" value="<?php echo e($department['color'] ?? '#b98b12'); ?>" placeholder="#b98b12"></label>
                                        <div class="settings-inline-actions">
                                            <button type="button" class="admin-action-link settings-dept-save" data-department-id="<?php echo (int) ($department['id'] ?? 0); ?>">Save</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-dept-cancel">Cancel</button>
                                        </div>
                                    </div>
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
                        <p class="crud-modal-subtitle">List view with edit action to open full editable fields.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($visibleUsers); ?> users</span>
                    </div>
                </div>
                <div class="settings-list-head settings-create-row" data-user-create-row>
                    <div class="settings-list-cols settings-list-cols-user-create">
                        <strong>New user</strong>
                        <label class="settings-field">First name<input data-field="first_name" type="text" value=""></label>
                        <label class="settings-field">Last name<input data-field="last_name" type="text" value=""></label>
                        <label class="settings-field">Email<input data-field="email" type="email" value=""></label>
                        <label class="settings-field">Role
                            <select data-field="role">
                                <?php foreach ($roleCatalog as $r): ?>
                                    <?php if ($currentRole === 'admin' && $r['key'] === 'super_admin') continue; ?>
                                    <option value="<?php echo e($r['key']); ?>"><?php echo e($r['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="settings-field">Department
                            <select data-field="department_id">
                                <option value="">-- none --</option>
                                <?php foreach ($visibleDepartments as $department): ?>
                                    <option value="<?php echo (int) ($department['id'] ?? 0); ?>"><?php echo e($department['name'] ?? 'Department'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="settings-field">Password<input data-field="password" type="text" value=""></label>
                        <div class="settings-inline-actions">
                            <button type="button" class="admin-action-link settings-user-create">Create user</button>
                            <button type="button" class="admin-action-link admin-action-link-secondary settings-user-reset">Reset</button>
                        </div>
                    </div>
                </div>

                <div class="settings-list-wrap">
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-user">
                        <strong>Name</strong>
                        <span>Email</span>
                        <span>Role</span>
                        <span>Department</span>
                        <span>Status</span>
                        <span>Actions</span>
                    </div>

                    <?php if (empty($visibleUsers)): ?>
                        <div class="crud-empty-state">No users assigned yet.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($visibleUsers, 0, 200) as $user): ?>
                            <article class="settings-list-item-wrap" data-user-id="<?php echo (int) ($user['id'] ?? 0); ?>">
                                <div class="settings-list-row settings-list-cols settings-list-cols-user">
                                    <strong><?php echo e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User'); ?></strong>
                                    <span><?php echo e($user['email'] ?? ''); ?></span>
                                    <span><?php echo e($roleLabels[$user['role'] ?? 'employee'] ?? ucfirst((string) ($user['role'] ?? 'employee'))); ?></span>
                                    <span><?php echo e($user['department_name'] ?? '--'); ?></span>
                                    <span><?php echo e($user['status'] ?? 'active'); ?></span>
                                    <div class="settings-inline-actions">
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-user-edit" aria-label="Edit user" title="Edit user">✎</button>
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-action-icon-danger settings-user-delete" data-user-id="<?php echo (int) ($user['id'] ?? 0); ?>" aria-label="Delete user" title="Delete user">🗑</button>
                                    </div>
                                </div>

                                <div class="settings-edit-drawer" hidden>
                                    <div class="settings-list-cols settings-list-cols-user-edit">
                                        <label class="settings-field">First name<input data-field="first_name" type="text" value="<?php echo e($user['first_name'] ?? ''); ?>"></label>
                                        <label class="settings-field">Last name<input data-field="last_name" type="text" value="<?php echo e($user['last_name'] ?? ''); ?>"></label>
                                        <label class="settings-field">Email<input data-field="email" type="email" value="<?php echo e($user['email'] ?? ''); ?>"></label>
                                        <label class="settings-field">Role
                                            <select data-field="role">
                                                <?php foreach ($roleCatalog as $r): ?>
                                                    <?php if ($currentRole === 'admin' && $r['key'] === 'super_admin') continue; ?>
                                                    <option value="<?php echo e($r['key']); ?>" <?php echo (($user['role'] ?? '') === $r['key']) ? 'selected' : ''; ?>><?php echo e($r['label']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="settings-field">Department
                                            <select data-field="department_id">
                                                <option value="">-- none --</option>
                                                <?php foreach ($visibleDepartments as $department): ?>
                                                    <option value="<?php echo (int) ($department['id'] ?? 0); ?>" <?php echo ((int) ($user['department_id'] ?? 0) === (int) ($department['id'] ?? 0)) ? 'selected' : ''; ?>><?php echo e($department['name'] ?? 'Department'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="settings-field">Status
                                            <select data-field="status">
                                                <option value="active" <?php echo (($user['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo (($user['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </label>
                                        <label class="settings-field">Password<input data-field="password" type="text" value="" placeholder="(leave blank to keep)"></label>
                                        <div class="settings-inline-actions">
                                            <button type="button" class="admin-action-link settings-user-save" data-user-id="<?php echo (int) ($user['id'] ?? 0); ?>">Save</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-user-cancel">Cancel</button>
                                        </div>
                                    </div>
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
                        <p class="crud-modal-subtitle">Role management list with Edit and Save for user role assignment.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($visibleUsers); ?> users</span>
                    </div>
                </div>

                <div class="settings-list-wrap">
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-role">
                        <strong>User</strong>
                        <span>Email</span>
                        <span>Current role</span>
                        <span>Department</span>
                        <span>Status</span>
                        <span>Actions</span>
                    </div>

                    <?php if (empty($visibleUsers)): ?>
                        <div class="crud-empty-state">No users available.</div>
                    <?php else: ?>
                        <?php foreach ($visibleUsers as $user): ?>
                            <article class="settings-list-item-wrap"
                                data-role-user-id="<?php echo (int) ($user['id'] ?? 0); ?>"
                                data-first-name="<?php echo e($user['first_name'] ?? ''); ?>"
                                data-last-name="<?php echo e($user['last_name'] ?? ''); ?>"
                                data-email="<?php echo e($user['email'] ?? ''); ?>"
                                data-status="<?php echo e($user['status'] ?? 'active'); ?>"
                                data-department-id="<?php echo (int) ($user['department_id'] ?? 0); ?>">
                                <div class="settings-list-row settings-list-cols settings-list-cols-role">
                                    <strong><?php echo e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User'); ?></strong>
                                    <span><?php echo e($user['email'] ?? ''); ?></span>
                                    <span><?php echo e($roleLabels[$user['role'] ?? 'employee'] ?? ucfirst((string) ($user['role'] ?? 'employee'))); ?></span>
                                    <span><?php echo e($user['department_name'] ?? '--'); ?></span>
                                    <span><?php echo e($user['status'] ?? 'active'); ?></span>
                                    <div class="settings-inline-actions">
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-role-edit" aria-label="Edit role assignment" title="Edit role assignment">✎</button>
                                    </div>
                                </div>

                                <div class="settings-edit-drawer" hidden>
                                    <div class="settings-list-cols settings-list-cols-role-edit">
                                        <label class="settings-field">Role
                                            <select data-field="role">
                                                <?php foreach ($roleCatalog as $r): ?>
                                                    <?php if ($currentRole === 'admin' && $r['key'] === 'super_admin') continue; ?>
                                                    <option value="<?php echo e($r['key']); ?>" <?php echo (($user['role'] ?? '') === $r['key']) ? 'selected' : ''; ?>><?php echo e($r['label']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="settings-field">Department
                                            <select data-field="department_id">
                                                <option value="">-- none --</option>
                                                <?php foreach ($visibleDepartments as $department): ?>
                                                    <option value="<?php echo (int) ($department['id'] ?? 0); ?>" <?php echo ((int) ($user['department_id'] ?? 0) === (int) ($department['id'] ?? 0)) ? 'selected' : ''; ?>><?php echo e($department['name'] ?? 'Department'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="settings-field">Status
                                            <select data-field="status">
                                                <option value="active" <?php echo (($user['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo (($user['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </label>
                                        <div class="settings-inline-actions">
                                            <button type="button" class="admin-action-link settings-role-save">Save</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-role-cancel">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="crud-panel settings-panel" data-settings-panel="shifts" hidden>
                <div class="settings-panel-head">
                    <div>
                        <h3>Shifts</h3>
                        <p class="crud-modal-subtitle">List view with edit action for each shift.</p>
                    </div>
                    <div class="settings-pill-row">
                        <span class="settings-pill"><?php echo count($shifts); ?> shifts</span>
                    </div>
                </div>

                <div class="settings-list-head settings-create-row" data-shift-create-row>
                    <div class="settings-list-cols settings-list-cols-shift-create">
                        <strong>New shift</strong>
                        <label class="settings-field">Department
                            <select data-field="department_id">
                                <?php foreach ($visibleDepartments as $department): ?>
                                    <option value="<?php echo (int) ($department['id'] ?? 0); ?>" <?php echo (int) ($department['id'] ?? 0) === (int) ($planner['active_department_id'] ?? 0) ? 'selected' : ''; ?>>
                                        <?php echo e($department['name'] ?? 'Department'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="settings-field">Name<input data-field="name" type="text" value="" placeholder="Morning shift"></label>
                        <label class="settings-field">Icon<input data-field="icon" type="text" value="🕒" placeholder="🕒"></label>
                        <label class="settings-field">Color<input data-field="color" type="text" value="#2f6fed" placeholder="#2f6fed"></label>
                        <label class="settings-field">Start<input data-field="start_time" type="time" value="09:00"></label>
                        <label class="settings-field">End<input data-field="end_time" type="time" value="17:00"></label>
                        <div class="settings-inline-actions">
                            <button type="button" class="admin-action-link settings-shift-create">Create shift</button>
                            <button type="button" class="admin-action-link admin-action-link-secondary settings-shift-reset">Reset</button>
                        </div>
                    </div>
                </div>

                <div class="settings-list-wrap">
                    <div class="settings-list-row settings-list-header settings-list-cols settings-list-cols-shift">
                        <strong>Name</strong>
                        <span>Department</span>
                        <span>Time</span>
                        <span>Icon</span>
                        <span>Color</span>
                        <span>Actions</span>
                    </div>

                    <?php if (empty($shifts)): ?>
                        <div class="crud-empty-state">No shifts available.</div>
                    <?php else: ?>
                        <?php foreach ($shifts as $shift): ?>
                            <article class="settings-list-item-wrap" data-shift-id="<?php echo (int) ($shift['id'] ?? 0); ?>">
                                <div class="settings-list-row settings-list-cols settings-list-cols-shift">
                                    <strong><?php echo e($shift['name'] ?? 'Shift'); ?></strong>
                                    <span><?php echo e($shift['department_name'] ?? ''); ?></span>
                                    <span><?php echo e(($shift['start_time'] ?? '--:--') . ' - ' . ($shift['end_time'] ?? '--:--')); ?></span>
                                    <span><?php echo e($shift['icon'] ?? '🕒'); ?></span>
                                    <span><?php echo e($shift['color'] ?? '#2f6fed'); ?></span>
                                    <div class="settings-inline-actions">
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-shift-edit" aria-label="Edit shift" title="Edit shift">✎</button>
                                        <button type="button" class="admin-action-link admin-action-link-secondary settings-action-icon settings-action-icon-danger settings-shift-delete" data-shift-id="<?php echo (int) ($shift['id'] ?? 0); ?>" aria-label="Delete shift" title="Delete shift">🗑</button>
                                    </div>
                                </div>
                                <div class="settings-edit-drawer" hidden>
                                    <div class="settings-list-cols settings-list-cols-shift-edit">
                                        <label class="settings-field">Name<input data-field="name" type="text" value="<?php echo e($shift['name'] ?? 'Shift'); ?>"></label>
                                        <label class="settings-field">Icon<input data-field="icon" type="text" value="<?php echo e($shift['icon'] ?? '🕒'); ?>" placeholder="🕒"></label>
                                        <label class="settings-field">Color<input data-field="color" type="text" value="<?php echo e($shift['color'] ?? '#2f6fed'); ?>" placeholder="#2f6fed"></label>
                                        <label class="settings-field">Start<input data-field="start_time" type="time" value="<?php echo e($shift['start_time'] ?? ''); ?>"></label>
                                        <label class="settings-field">End<input data-field="end_time" type="time" value="<?php echo e($shift['end_time'] ?? ''); ?>"></label>
                                        <div class="settings-inline-actions">
                                            <button type="button" class="admin-action-link settings-shift-save" data-shift-id="<?php echo (int) ($shift['id'] ?? 0); ?>">Save</button>
                                            <button type="button" class="admin-action-link admin-action-link-secondary settings-shift-cancel">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

    </div>
</section>
