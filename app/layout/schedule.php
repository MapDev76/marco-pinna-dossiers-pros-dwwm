<?php
if (!isLoggedIn()) {
    return;
}

$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';
?>
<section class="dashboard-modal dashboard-schedule-modal" id="modal-schedule" hidden>
    <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
    <h2>Planning interactif</h2>
    <p>Gestion des colonnes du planning, des shifts et des affectations par département.</p>

    <div class="schedule-toolbar">
        <button type="button" class="schedule-pill is-active">Shift Times</button>
        <button type="button" class="schedule-pill">Roles</button>
        <button type="button" class="schedule-pill">Departments</button>
        <button type="button" class="schedule-pill">Coverage</button>
        <button type="button" class="schedule-pill">Occupancy</button>
        <button type="button" class="schedule-pill">Absence Types</button>
    </div>

    <div class="schedule-board">
        <div class="schedule-card is-highlight">
            <div class="schedule-card-head">
                <span class="schedule-badge">Reception</span>
                <span class="schedule-color">#b98b12</span>
            </div>
            <div class="schedule-row"><span>Start</span><strong>06:00</strong></div>
            <div class="schedule-row"><span>End</span><strong>14:00</strong></div>
            <div class="schedule-row"><span>Icon</span><strong>🔑</strong></div>
        </div>
        <div class="schedule-card">
            <div class="schedule-card-head">
                <span class="schedule-badge">Housekeeping</span>
                <span class="schedule-color">#6c7ae0</span>
            </div>
            <div class="schedule-row"><span>Start</span><strong>08:00</strong></div>
            <div class="schedule-row"><span>End</span><strong>16:00</strong></div>
            <div class="schedule-row"><span>Icon</span><strong>🧹</strong></div>
        </div>
        <div class="schedule-card">
            <div class="schedule-card-head">
                <span class="schedule-badge">Maintenance</span>
                <span class="schedule-color">#df7b2b</span>
            </div>
            <div class="schedule-row"><span>Start</span><strong>09:00</strong></div>
            <div class="schedule-row"><span>End</span><strong>17:00</strong></div>
            <div class="schedule-row"><span>Icon</span><strong>🛠</strong></div>
        </div>
        <div class="schedule-card">
            <div class="schedule-card-head">
                <span class="schedule-badge">Night auditor</span>
                <span class="schedule-color">#8e67d9</span>
            </div>
            <div class="schedule-row"><span>Start</span><strong>22:00</strong></div>
            <div class="schedule-row"><span>End</span><strong>06:00</strong></div>
            <div class="schedule-row"><span>Icon</span><strong>🌙</strong></div>
        </div>
    </div>

    <div class="schedule-form-grid">
        <label class="schedule-field">
            Nom de la mission
            <input type="text" value="Reception" readonly>
        </label>
        <label class="schedule-field">
            Couleur
            <input type="text" value="#b98b12" readonly>
        </label>
        <label class="schedule-field">
            Icône
            <input type="text" value="🔑" readonly>
        </label>
        <label class="schedule-field">
            Département
            <input type="text" value="Front Office" readonly>
        </label>
    </div>

    <div class="admin-actions settings-actions">
        <button type="button" class="admin-action-link">Créer</button>
        <button type="button" class="admin-action-link">Modifier</button>
        <button type="button" class="admin-action-link">Supprimer</button>
    </div>
</section>
