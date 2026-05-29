<?php
/**
 * Settings modal used to showcase configuration sections (read-only demo).
 *
 * The modal `#modal-settings` is a presentation view for schedules, roles and
 * department configuration. It reads `currentUser()` for contextual labels.
 */
if (!isLoggedIn()) {
    return;
}

$currentUser = currentUser();
?>
<section class="dashboard-modal dashboard-settings-modal" id="modal-settings" hidden>
    <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
    <h2>Company settings</h2>
    <p>Manage global settings, departments, colors, icons, and assigned tasks.</p>

    <div class="settings-summary">
        <div class="settings-summary-card">
            <span class="settings-summary-label">Active company</span>
            <strong><?php echo e($currentUser['company_name'] ?? 'StaffEase Pro'); ?></strong>
        </div>
        <div class="settings-summary-card">
            <span class="settings-summary-label">Management</span>
            <strong>Departments + tasks</strong>
        </div>
        <div class="settings-summary-card">
            <span class="settings-summary-label">Mode</span>
            <strong>Centralized editing</strong>
        </div>
    </div>

    <div class="settings-tabs">
        <button type="button" class="settings-tab is-active">Schedules</button>
        <button type="button" class="settings-tab">Roles</button>
        <button type="button" class="settings-tab">Departments</button>
        <button type="button" class="settings-tab">Coverage</button>
        <button type="button" class="settings-tab">Occupancy</button>
        <button type="button" class="settings-tab">Absences</button>
    </div>

    <div class="settings-board">
        <div class="settings-card is-highlight">
            <div class="settings-card-head">
                <span class="settings-badge">Reception</span>
                <span class="settings-color">#b98b12</span>
            </div>
            <p>Reception, check-in, front desk, and arrivals management.</p>
            <div class="settings-meta">Icon: 🔑 | Allowed departments: Front Office</div>
        </div>
        <div class="settings-card">
            <div class="settings-card-head">
                <span class="settings-badge">Housekeeping</span>
                <span class="settings-color">#6c7ae0</span>
            </div>
            <p>Room cleaning, linen, and quality checks.</p>
            <div class="settings-meta">Icon: 🧹 | Allowed departments: Housekeeping</div>
        </div>
        <div class="settings-card">
            <div class="settings-card-head">
                <span class="settings-badge">Maintenance</span>
                <span class="settings-color">#df7b2b</span>
            </div>
            <p>Technical maintenance, interventions, and repairs.</p>
            <div class="settings-meta">Icon: 🛠 | Allowed departments: Technical</div>
        </div>
        <div class="settings-card">
            <div class="settings-card-head">
                <span class="settings-badge">Night auditor</span>
                <span class="settings-color">#8e67d9</span>
            </div>
            <p>Monitoring, end-of-day closing, and night control.</p>
            <div class="settings-meta">Icon: 🌙 | Allowed departments: Night</div>
        </div>
    </div>

    <div class="settings-form-grid">
        <label class="settings-field">
            Task name
            <input type="text" value="Reception" readonly>
        </label>
        <label class="settings-field">
            Department color
            <input type="text" value="#b98b12" readonly>
        </label>
        <label class="settings-field">
            Task icon
            <input type="text" value="🔑" readonly>
        </label>
        <label class="settings-field">
            Assigned department
            <input type="text" value="Front Office" readonly>
        </label>
    </div>

    <div class="admin-actions settings-actions">
        <button type="button" class="admin-action-link">Create</button>
        <button type="button" class="admin-action-link">Edit</button>
        <button type="button" class="admin-action-link">Delete</button>
    </div>
</section>
