<!-- User management: list, create, update, and delete. -->
<?php
$editing = $editingUser ?? null;
$formData = $formData ?? [];
$users = $users ?? [];
$departments = $departments ?? [];
$companies = $companies ?? [];
$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'department_manager' => 'Department Manager',
    'employee' => 'Employee',
];
$statusLabels = [
    'active' => 'Active',
    'inactive' => 'Inactive',
];
$currentRole = currentUser()['role'] ?? 'employee';
$availableRoles = $currentRole === 'super_admin'
    ? ['super_admin', 'admin', 'department_manager', 'employee']
    : ($currentRole === 'admin'
        ? ['admin', 'department_manager', 'employee']
        : ['employee']);
?>
<div class="admin-shell">
    <div class="admin-hero">
        <h1>Users</h1>
        <p>Create, edit, or delete application accounts.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div class="flash flash-success"><?php echo e($successMessage); ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <h2><?php echo $editing ? 'Edit user' : 'Create user'; ?></h2>
        <form method="post" class="admin-form admin-form-grid">
            <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>">
            <?php endif; ?>

            <?php
            $firstName = $formData['first_name'] ?? ($editing['first_name'] ?? '');
            $lastName = $formData['last_name'] ?? ($editing['last_name'] ?? '');
            $email = $formData['email'] ?? ($editing['email'] ?? '');
            $phone = $formData['phone'] ?? ($editing['phone'] ?? '');
            $role = $formData['role'] ?? ($editing['role'] ?? 'employee');
            $status = $formData['status'] ?? ($editing['status'] ?? 'active');
            $departmentId = (string) ($formData['department_id'] ?? ($editing['department_id'] ?? ''));
            ?>

            <label>
                <span>First name</span>
                <input type="text" name="first_name" value="<?php echo e($firstName); ?>" required>
            </label>
            <label>
                <span>Last name</span>
                <input type="text" name="last_name" value="<?php echo e($lastName); ?>" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?php echo e($email); ?>" required>
            </label>
            <label>
                <span>Phone</span>
                <input type="text" name="phone" value="<?php echo e($phone); ?>">
            </label>
            <label>
                <span>Password <?php echo $editing ? '(leave blank to keep)' : ''; ?></span>
                <input type="password" name="password" value="<?php echo e($formData['password'] ?? ''); ?>" <?php echo $editing ? '' : 'required'; ?>>
            </label>
            <label>
                <span>Role</span>
                <select name="role">
                    <?php foreach ($availableRoles as $optionRole): ?>
                        <option value="<?php echo e($optionRole); ?>" <?php echo $role === $optionRole ? 'selected' : ''; ?>><?php echo e($roleLabels[$optionRole] ?? $optionRole); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <?php foreach (['active', 'inactive'] as $optionStatus): ?>
                        <option value="<?php echo e($optionStatus); ?>" <?php echo $status === $optionStatus ? 'selected' : ''; ?>><?php echo e($optionStatus); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Department</span>
                <select name="department_id">
                    <option value="">None</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo (int) $department['id']; ?>" <?php echo $departmentId === (string) $department['id'] ? 'selected' : ''; ?>>
                            <?php echo e($department['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="form-actions">
                <button type="submit"><?php echo $editing ? 'Update' : 'Create'; ?></button>
                <?php if ($editing): ?>
                    <a href="<?php echo appUrl('users'); ?>" class="admin-action-link admin-action-link-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>User list</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7">No users to display.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo e($user['email']); ?></td>
                            <td><span class="pill"><?php echo e($roleLabels[$user['role']] ?? $user['role']); ?></span></td>
                            <td><?php echo e($user['department_name'] ?? '-'); ?></td>
                            <td><?php echo e($user['company_name'] ?? '-'); ?></td>
                            <td><?php echo e($statusLabels[$user['status']] ?? $user['status']); ?></td>
                            <td class="table-actions">
                                <a href="<?php echo appUrl('users', ['action' => 'edit', 'id' => (int) $user['id']]); ?>">Edit</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
