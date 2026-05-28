<?php
if (!isLoggedIn()) {
    return;
}
?>
<div class="dashboard-overlay" id="dashboard-overlay" hidden></div>
<section class="crud-modal" id="crud-modal" hidden role="dialog" aria-modal="true" aria-labelledby="crud-modal-title">
    <div class="crud-modal-card">
        <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
        </div>
        <div class="crud-modal-body" id="crud-modal-body">
            <div class="crud-empty-state">Select an item from the sidebar to load its CRUD template.</div>
        </div>
    </div>
</section>

<template id="crud-template-placeholder">
    <div class="crud-panel">
        <h3>Common CRUD shell</h3>
        <p>This modal is shared by every dashboard action. We will adapt its content per entity in the next step.</p>
        <div class="crud-empty-state">No CRUD template has been connected for this element yet.</div>
    </div>
</template>

<template id="crud-template-companies">
    <div class="crud-panel-grid">
        <section class="crud-panel">
            <h3>Create company</h3>
            <form method="post" action="<?php echo appUrl('companies'); ?>" class="admin-form">
                <input type="hidden" name="action" value="create">
                <label>
                    Name
                    <input type="text" name="name" required>
                </label>
                <label>
                    Type
                    <select name="type">
                        <?php foreach (['hotel', 'hospital', 'clinic', 'elderly_center', 'restaurant', 'other'] as $companyType): ?>
                            <option value="<?php echo e($companyType); ?>"><?php echo e($companyType); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    City
                    <input type="text" name="city">
                </label>
                <label>
                    Address
                    <input type="text" name="address">
                </label>
                <label>
                    Zip code
                    <input type="text" name="zip_code">
                </label>
                <label>
                    Phone
                    <input type="text" name="phone">
                </label>
                <label>
                    Email
                    <input type="email" name="email">
                </label>
                <label>
                    Logo path
                    <input type="text" name="logo_path" placeholder="assets/images/...">
                </label>
                <label>
                    Signature IP
                    <input type="text" name="signature_ip" placeholder="Leave empty if not used">
                </label>
                <div class="form-actions">
                    <button type="submit">Create</button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3>Companies list</h3>
            <div class="company-grid">
                <?php if (empty($moduleRows['company_directory'] ?? [])): ?>
                    <div class="crud-empty-state">No companies to display.</div>
                <?php endif; ?>
                <?php foreach (($moduleRows['company_directory'] ?? []) as $company): ?>
                    <article class="company-card" data-company-id="<?php echo (int) $company['id']; ?>">
                        <header class="company-card-head">
                            <div class="company-card-title"><?php echo e($company['name']); ?></div>
                        </header>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</template>