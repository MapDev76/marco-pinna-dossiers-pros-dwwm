<!-- Gestion des entreprises : liste et formulaire de création/modification. -->
<?php
$editing = $editingCompany ?? null;
$formData = $formData ?? [];
$companies = $companies ?? [];
$typeLabels = [
    'hotel' => 'Hôtel',
    'hospital' => 'Hôpital',
    'clinic' => 'Clinique',
    'elderly_center' => 'Maison de retraite',
    'restaurant' => 'Restaurant',
    'other' => 'Autre',
];
?>
<div class="admin-shell">
    <div class="admin-hero">
        <h1>Entreprises</h1>
        <p>Gérer les entreprises rattachées à l'application.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <h2><?php echo $editing ? 'Modifier une entreprise' : 'Créer une entreprise'; ?></h2>
        <form method="post" class="admin-form admin-form-grid">
            <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>">
            <?php endif; ?>

            <?php
            $name = $formData['name'] ?? ($editing['name'] ?? '');
            $type = $formData['type'] ?? ($editing['type'] ?? 'other');
            $address = $formData['address'] ?? ($editing['address'] ?? '');
            $city = $formData['city'] ?? ($editing['city'] ?? '');
            $zipCode = $formData['zip_code'] ?? ($editing['zip_code'] ?? '');
            $phone = $formData['phone'] ?? ($editing['phone'] ?? '');
            $email = $formData['email'] ?? ($editing['email'] ?? '');
            $logoPath = $formData['logo_path'] ?? ($editing['logo_path'] ?? '');
            $signatureIp = $formData['signature_ip'] ?? ($editing['signature_ip'] ?? '');
            ?>

            <label>
                <span>Nom</span>
                <input type="text" name="name" value="<?php echo e($name); ?>" required>
            </label>
            <label>
                <span>Type</span>
                <select name="type">
                    <?php foreach (['hotel', 'hospital', 'clinic', 'elderly_center', 'restaurant', 'other'] as $optionType): ?>
                        <option value="<?php echo e($optionType); ?>" <?php echo $type === $optionType ? 'selected' : ''; ?>><?php echo e($optionType); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Adresse</span>
                <input type="text" name="address" value="<?php echo e($address); ?>">
            </label>
            <label>
                <span>Ville</span>
                <input type="text" name="city" value="<?php echo e($city); ?>">
            </label>
            <label>
                <span>Code postal</span>
                <input type="text" name="zip_code" value="<?php echo e($zipCode); ?>">
            </label>
            <label>
                <span>Téléphone</span>
                <input type="text" name="phone" value="<?php echo e($phone); ?>">
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?php echo e($email); ?>">
            </label>
            <label>
                <span>Logo (chemin ou URL)</span>
                <input type="text" name="logo_path" value="<?php echo e($logoPath); ?>" placeholder="assets/images/...">
            </label>
            <label>
                <span>Signature IP</span>
                <input type="text" name="signature_ip" value="<?php echo e($signatureIp); ?>" placeholder="Laisser vide si non utilisé">
            </label>

            <div class="form-actions">
                <button type="submit"><?php echo $editing ? 'Mettre à jour' : 'Créer'; ?></button>
                <?php if ($editing): ?>
                    <a href="<?php echo appUrl('companies'); ?>" class="admin-action-link admin-action-link-secondary">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Liste des entreprises</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Ville</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($companies)): ?>
                        <tr><td colspan="6">Aucune entreprise à afficher.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?php echo e($company['name']); ?></td>
                            <td><?php echo e($typeLabels[$company['type']] ?? $company['type']); ?></td>
                            <td><?php echo e($company['city'] ?? '-'); ?></td>
                            <td><?php echo e($company['phone'] ?? '-'); ?></td>
                            <td><?php echo e($company['email'] ?? '-'); ?></td>
                            <td class="table-actions">
                                <a href="<?php echo appUrl('companies', ['action' => 'edit', 'id' => (int) $company['id']]); ?>">Modifier</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette entreprise ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $company['id']; ?>">
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
