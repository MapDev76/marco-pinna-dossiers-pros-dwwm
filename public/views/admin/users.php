<!-- Gestion des utilisateurs : liste, création, modification et suppression. -->
<?php
$editing = $editingUser ?? null;
$formData = $formData ?? [];
$users = $users ?? [];
$departments = $departments ?? [];
?>
<div class="admin-shell">
    <div class="admin-hero">
        <h1>Utilisateurs</h1>
        <p>Créer, modifier ou supprimer les comptes de l'application.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <h2><?php echo $editing ? 'Modifier un utilisateur' : 'Créer un utilisateur'; ?></h2>
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
                <span>Prénom</span>
                <input type="text" name="first_name" value="<?php echo e($firstName); ?>" required>
            </label>
            <label>
                <span>Nom</span>
                <input type="text" name="last_name" value="<?php echo e($lastName); ?>" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?php echo e($email); ?>" required>
            </label>
            <label>
                <span>Téléphone</span>
                <input type="text" name="phone" value="<?php echo e($phone); ?>">
            </label>
            <label>
                <span>Mot de passe <?php echo $editing ? '(laisser vide pour conserver)' : ''; ?></span>
                <input type="password" name="password" value="<?php echo e($formData['password'] ?? ''); ?>" <?php echo $editing ? '' : 'required'; ?>>
            </label>
            <label>
                <span>Rôle</span>
                <select name="role">
                    <?php foreach (['super_admin', 'admin', 'department_manager', 'employee'] as $optionRole): ?>
                        <option value="<?php echo e($optionRole); ?>" <?php echo $role === $optionRole ? 'selected' : ''; ?>><?php echo e($optionRole); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Statut</span>
                <select name="status">
                    <?php foreach (['active', 'inactive'] as $optionStatus): ?>
                        <option value="<?php echo e($optionStatus); ?>" <?php echo $status === $optionStatus ? 'selected' : ''; ?>><?php echo e($optionStatus); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Département</span>
                <select name="department_id">
                    <option value="">Aucun</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo (int) $department['id']; ?>" <?php echo $departmentId === (string) $department['id'] ? 'selected' : ''; ?>>
                            <?php echo e($department['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="form-actions">
                <button type="submit"><?php echo $editing ? 'Mettre à jour' : 'Créer'; ?></button>
                <?php if ($editing): ?>
                    <a href="<?php echo appUrl('users'); ?>" class="admin-action-link admin-action-link-secondary">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Liste des utilisateurs</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Département</th>
                        <th>Entreprise</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo e($user['email']); ?></td>
                            <td><span class="pill"><?php echo e($user['role']); ?></span></td>
                            <td><?php echo e($user['department_name'] ?? '-'); ?></td>
                            <td><?php echo e($user['company_name'] ?? '-'); ?></td>
                            <td><?php echo e($user['status']); ?></td>
                            <td class="table-actions">
                                <a href="<?php echo appUrl('users', ['action' => 'edit', 'id' => (int) $user['id']]); ?>">Modifier</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                    <button type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
