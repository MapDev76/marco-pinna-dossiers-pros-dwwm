<?php
if (!isLoggedIn()) {
    return;
}
?>
<section class="dashboard-modal" id="modal-documents" hidden>
    <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
    <h2>Gestion des documents</h2>
    <p>Contrats, visites médicales, attestations et autres documents de l'entreprise.</p>
    <div class="admin-actions">
        <button type="button" class="admin-action-link">Ajouter un contrat</button>
        <button type="button" class="admin-action-link">Ajouter une visite médicale</button>
        <button type="button" class="admin-action-link">Ajouter une attestation</button>
    </div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Nom</th>
                    <th>État</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Contrat</td>
                    <td>Contrat standard</td>
                    <td>Actif</td>
                    <td><button type="button" class="admin-action-link">Ouvrir</button></td>
                </tr>
                <tr>
                    <td>Visite médicale</td>
                    <td>Contrôle annuel</td>
                    <td>En attente</td>
                    <td><button type="button" class="admin-action-link">Ouvrir</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
