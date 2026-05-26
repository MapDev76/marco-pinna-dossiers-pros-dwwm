<?php
if (!isLoggedIn()) {
    return;
}

$currentUser = currentUser();
$role = $currentUser['role'] ?? 'employee';
?>
<section class="dashboard-modal dashboard-settings-modal" id="modal-settings" hidden>
    <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
    <h2>Paramètres de l'entreprise</h2>
    <p>Gestion des paramètres globaux, des départements, des couleurs, des icônes et des missions attribuées.</p>

    <div class="settings-tabs">
        <button type="button" class="settings-tab is-active">Shift Times</button>
        <button type="button" class="settings-tab">Roles</button>
        <button type="button" class="settings-tab">Departments</button>
        <button type="button" class="settings-tab">Coverage</button>
        <button type="button" class="settings-tab">Occupancy</button>
        <button type="button" class="settings-tab">Absence Types</button>
    </div>

    <div class="settings-board">
        <div class="settings-card is-highlight">
            <div class="settings-card-head">
                <span class="settings-badge">Reception</span>
                <span class="settings-color">#b98b12</span>
            </div>
            <p>Rôles: receptionist, check-in, front desk</p>
            <div class="settings-meta">Icône: 🔑 | Départements autorisés: Front Office</div>
        </div>
        <div class="settings-card">
            <div class="settings-card-head">
                <span class="settings-badge">Housekeeping</span>
                <span class="settings-color">#6c7ae0</span>
            </div>
            <p>Rôles: housekeeping, room attendant, linen</p>
            <div class="settings-meta">Icône: 🧹 | Départements autorisés: Housekeeping</div>
        </div>
        <div class="settings-card">
            <div class="settings-card-head">
                <span class="settings-badge">Maintenance</span>
                <span class="settings-color">#df7b2b</span>
            </div>
            <p>Rôles: maintenance, technician, repair</p>
            <div class="settings-meta">Icône: 🛠 | Départements autorisés: Technical</div>
        </div>
        <div class="settings-card">
            <div class="settings-card-head">
                <span class="settings-badge">Night auditor</span>
                <span class="settings-color">#8e67d9</span>
            </div>
            <p>Rôles: night auditor, security, assistant</p>
            <div class="settings-meta">Icône: 🌙 | Départements autorisés: Night</div>
        </div>
    </div>

    <div class="settings-form-grid">
        <label class="settings-field">
            Nom de la mission
            <input type="text" value="Reception" readonly>
        </label>
        <label class="settings-field">
            Couleur du département
            <input type="text" value="#b98b12" readonly>
        </label>
        <label class="settings-field">
            Icône de la mission
            <input type="text" value="🔑" readonly>
        </label>
        <label class="settings-field">
            Département assigné
            <input type="text" value="Front Office" readonly>
        </label>
    </div>

    <div class="admin-actions settings-actions">
        <button type="button" class="admin-action-link">Créer</button>
        <button type="button" class="admin-action-link">Modifier</button>
        <button type="button" class="admin-action-link">Supprimer</button>
    </div>
</section>
