<?php
// Sidebar dédiée au dashboard, séparée du conteneur central principal.
if (!isset($dashboardSidebarSections) || !is_array($dashboardSidebarSections)) {
    return;
}

$sidebarRoleLabel = $dashboardSidebarRoleLabel ?? 'User';
?>
<aside class="app-sidebar" aria-label="Dashboard sidebar navigation">
    <div class="dashboard-sidebar-shell">
        <div class="dashboard-sidebar-brand">
            <!-- Brand/title intentionally empty to match design: no title above sidebar -->
        </div>

        <?php foreach ($dashboardSidebarSections as $section): ?>
            <section class="dashboard-sidebar-group">
                <p class="dashboard-sidebar-group-title"><span><?php echo e($section['icon'] ?? '•'); ?></span> <?php echo e($section['title'] ?? 'Section'); ?></p>
                <?php foreach (($section['buttons'] ?? []) as $button): ?>
                    <button type="button" class="dashboard-sidebar-link <?php echo !empty($button['variant']) ? 'is-' . e($button['variant']) : ''; ?>" data-modal-target="<?php echo e($button['target'] ?? ''); ?>" data-modal-entity="<?php echo e($button['entity'] ?? ''); ?>" data-modal-title="<?php echo e($button['title'] ?? ($button['label'] ?? '')); ?>">
                        <?php echo e($button['label'] ?? 'Action'); ?>
                    </button>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
</aside>
