<!-- Gestion des départements : association à une entreprise, édition et suppression. -->
<?php
$editing = $editingDepartment ?? null;
$formData = $formData ?? [];
$companies = $companies ?? [];
$departments = $departments ?? [];
?>
<div class="admin-shell">
    <div class="admin-hero">
        <h1>Départements</h1>
        <p>Gérer les départements et leur liaison avec les entreprises.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <h2><?php echo $editing ? 'Modifier un département' : 'Créer un département'; ?></h2>
        <form method="post" class="admin-form admin-form-grid">
            <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>">
            <?php endif; ?>

            <?php
            $companyId = (string) ($formData['company_id'] ?? ($editing['company_id'] ?? ''));
            $name = $formData['name'] ?? ($editing['name'] ?? '');
            $description = $formData['description'] ?? ($editing['description'] ?? '');
            ?>

            <label>
                <span>Entreprise</span>
                <select name="company_id" required>
                    <option value="">Choisir une entreprise</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo (int) $company['id']; ?>" <?php echo $companyId === (string) $company['id'] ? 'selected' : ''; ?>>
                            <?php echo e($company['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Nom</span>
                <input type="text" name="name" value="<?php echo e($name); ?>" required>
            </label>
            <label class="span-2">
                <span>Description</span>
                <textarea name="description" rows="4"><?php echo e($description); ?></textarea>
            </label>

            <div class="form-actions span-2">
                <button type="submit"><?php echo $editing ? 'Mettre à jour' : 'Créer'; ?></button>
                <?php if ($editing): ?>
                    <a href="<?php echo appUrl('departments'); ?>" class="admin-action-link admin-action-link-secondary">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Liste des départements</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Entreprise</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td><?php echo e($department['name']); ?></td>
                            <td><?php echo e($department['company_name'] ?? '-'); ?></td>
                            <td><?php echo e($department['description'] ?? '-'); ?></td>
                            <td class="table-actions">
                                <a href="<?php echo appUrl('departments', ['action' => 'edit', 'id' => (int) $department['id']]); ?>">Modifier</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('Supprimer ce département ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $department['id']; ?>">
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
