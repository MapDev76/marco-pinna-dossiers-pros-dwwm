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
        <button type="button" class="dashboard-modal-close" data-modal-close aria-label="Close">&times;</button>
        <header class="dashboard-print-head">
            <div>
                <h2 id="print-modal-title">Planning print and CSV export</h2>
                <p class="dashboard-print-subtitle">Department planning grid by employee and date for the month currently selected in calendar navigator.</p>
            </div>
            <div class="dashboard-print-actions">
                <button type="button" class="dashboard-sidebar-control-button" data-print-refresh>Refresh</button>
                <button type="button" class="dashboard-sidebar-control-button" data-print-preview>Preview A4</button>
                <button type="button" class="dashboard-sidebar-control-button" data-print-download-csv>Save CSV</button>
                <button type="button" class="dashboard-sidebar-control-button" data-print-save-document>Save to Documents</button>
                <button type="button" class="dashboard-sidebar-control-button is-active" data-print-trigger>Print</button>
            </div>
        </header>

        <div class="dashboard-print-meta" data-print-meta></div>
        <div class="dashboard-print-feedback" data-print-feedback aria-live="polite"></div>

        <div class="dashboard-print-content" data-print-content>
            <div class="dashboard-sidebar-planner-placeholder">Open this modal from dashboard after selecting a department in the sidebar.</div>
        </div>
    </div>
</section>
