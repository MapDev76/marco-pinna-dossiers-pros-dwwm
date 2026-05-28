<!-- Departments management: associate to company, edit and delete. -->
<?php
$editing = $editingDepartment ?? null;
$formData = $formData ?? [];
$companies = $companies ?? [];
$departments = $departments ?? [];
$users = $users ?? [];
?>
<div class="admin-shell">
    <div class="admin-hero">
        <h1>Departments</h1>
        <p>Manage departments and their association with companies.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div class="flash flash-success"><?php echo e($successMessage); ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <h2><?php echo $editing ? 'Edit department' : 'Create department'; ?></h2>
        <form method="post" class="admin-form admin-form-grid">
            <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>">
            <?php endif; ?>

            <?php
            $companyId = (string) ($formData['company_id'] ?? ($editing['company_id'] ?? ''));
            $name = $formData['name'] ?? ($editing['name'] ?? '');
            $description = $formData['description'] ?? ($editing['description'] ?? '');
            $headUserId = (string) ($formData['head_user_id'] ?? ($editing['head_user_id'] ?? ''));
            ?>

            <label>
                <span>Company</span>
                <select name="company_id" required>
                    <option value="">Select a company</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo (int) $company['id']; ?>" <?php echo $companyId === (string) $company['id'] ? 'selected' : ''; ?>>
                            <?php echo e($company['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?php echo e($name); ?>" required>
            </label>
            <label>
                <span>Department head</span>
                <select name="head_user_id">
                    <option value="">None</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int) $user['id']; ?>" <?php echo $headUserId === (string) $user['id'] ? 'selected' : ''; ?>>
                            <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="span-2">
                <span>Description</span>
                <textarea name="description" rows="4"><?php echo e($description); ?></textarea>
            </label>

            <div class="form-actions span-2">
                <button type="submit"><?php echo $editing ? 'Update' : 'Create'; ?></button>
                <?php if ($editing): ?>
                    <a href="<?php echo appUrl('departments'); ?>" class="admin-action-link admin-action-link-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Departments list</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Head</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($departments)): ?>
                        <tr><td colspan="5">No departments to display.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td><?php echo e($department['name']); ?></td>
                            <td><?php echo e($department['company_name'] ?? '-'); ?></td>
                            <td><?php echo e($department['head_user_name'] ?? '-'); ?></td>
                            <td><?php echo e($department['description'] ?? '-'); ?></td>
                            <td class="table-actions">
                                <a href="<?php echo appUrl('departments', ['action' => 'edit', 'id' => (int) $department['id']]); ?>">Edit</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('Delete this department?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $department['id']; ?>">
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
