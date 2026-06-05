<?php
/**
 * Planning print/export modal.
 *
 * Renders a printable planning grid for the selected calendar department,
 * with current month and next month sections.
 */
if (!isLoggedIn()) {
    return;
}
?>
<section class="dashboard-modal dashboard-print-modal" id="modal-print" hidden role="dialog" aria-modal="true" aria-labelledby="print-modal-title">
    <div class="dashboard-print-shell" data-print-shell>
        <button type="button" class="dashboard-modal-close" data-modal-close aria-label="<?php echo e(t('print.close')); ?>">&times;</button>
        <header class="dashboard-print-head">
            <div>
                <h2 id="print-modal-title"><?php echo e(t('print.title')); ?></h2>
                <p class="dashboard-print-subtitle"><?php echo e(t('print.subtitle')); ?></p>
            </div>
            <div class="dashboard-print-actions">
                <button type="button" class="dashboard-sidebar-control-button" data-print-refresh><?php echo e(t('print.refresh')); ?></button>
                <select class="dashboard-sidebar-control-button" data-print-document-type>
                    <option value="planning"><?php echo e(t('print.planning')); ?></option>
                    <option value="attendance"><?php echo e(t('print.attendance')); ?></option>
                </select>
                <select class="dashboard-sidebar-control-button" data-print-layout>
                    <option value="a4-single"><?php echo e(t('print.a4_single')); ?></option>
                </select>
                <button type="button" class="dashboard-sidebar-control-button" data-print-preview><?php echo e(t('print.preview')); ?></button>
                <button type="button" class="dashboard-sidebar-control-button" data-print-export-excel><?php echo e(t('print.export_excel')); ?></button>
                <button type="button" class="dashboard-sidebar-control-button" data-print-export-pdf><?php echo e(t('print.export_pdf')); ?></button>
                <button type="button" class="dashboard-sidebar-control-button" data-print-download-csv><?php echo e(t('print.save_csv')); ?></button>
                <button type="button" class="dashboard-sidebar-control-button" data-print-save-document><?php echo e(t('print.save_documents')); ?></button>
                <button type="button" class="dashboard-sidebar-control-button is-active" data-print-trigger><?php echo e(t('print.print')); ?></button>
            </div>
        </header>

        <div class="dashboard-print-meta" data-print-meta></div>
        <div class="dashboard-print-feedback" data-print-feedback aria-live="polite"></div>

        <div class="dashboard-print-content" data-print-content>
            <div class="dashboard-sidebar-planner-placeholder"><?php echo e(t('print.placeholder')); ?></div>
        </div>
    </div>
</section>
