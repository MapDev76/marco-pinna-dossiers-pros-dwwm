<?php
if (!isLoggedIn()) {
    return;
}

$currentUser = currentUser();
?>
<section class="dashboard-modal dashboard-settings-modal" id="modal-documents" hidden>
    <button type="button" class="dashboard-modal-close" data-modal-close>&times;</button>
    <div class="documents-grid">
        <div class="documents-actions">
            <form method="post" action="<?php echo appUrl('documents_upload'); ?>" enctype="multipart/form-data" class="admin-form">
                <label>
                    Charger un document
                    <input type="file" name="document_file" required>
                </label>
                <label>
                    Type
                    <select name="document_type">
                        <option value="contract">Contrat</option>
                        <option value="medical">Visite médicale</option>
                        <option value="attestation">Attestation</option>
                        <option value="other">Autre</option>
                    </select>
                </label>
                <div class="form-actions">
                    <button type="submit">Téléverser</button>
                </div>
            </form>
        </div>

        <div class="documents-list">
            <h3>Documents</h3>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Nom</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($moduleRows['documents'] ?? [])): ?>
                            <tr>
                                <td colspan="4">Aucun document disponible.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (($moduleRows['documents'] ?? []) as $doc): ?>
                            <tr>
                                <td><?php echo e($doc['type'] ?? '-'); ?></td>
                                <td><?php echo e($doc['name'] ?? 'Fichier'); ?></td>
                                <td><?php echo e($doc['status'] ?? 'Actif'); ?></td>
                                <td>
                                    <a class="admin-action-link" href="<?php echo e($doc['url'] ?? '#'); ?>" download>Télécharger</a>
                                    <button type="button" class="admin-action-link">Voir</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
