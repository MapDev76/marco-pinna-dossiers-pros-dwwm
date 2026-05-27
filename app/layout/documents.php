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
            <form id="documents-upload-form" method="post" action="<?php echo appUrl('documents_upload'); ?>" enctype="multipart/form-data" class="admin-form">
                <label>
                    Upload a document
                    <input type="file" name="document_file" required>
                </label>
                <label>
                    Type
                    <select name="document_type">
                        <option value="contract">Contract</option>
                        <option value="medical">Medical</option>
                        <option value="attestation">Certificate</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <div class="form-actions">
                    <button type="submit">Upload</button>
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
                                <td colspan="4">No documents available.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (($moduleRows['documents'] ?? []) as $doc): ?>
                            <tr>
                                <td><?php echo e($doc['type'] ?? '-'); ?></td>
                                <td><?php echo e($doc['name'] ?? 'File'); ?></td>
                                <td><?php echo e($doc['status'] ?? 'Active'); ?></td>
                                <td>
                                    <a class="admin-action-link" href="<?php echo e($doc['url'] ?? '#'); ?>" download>Download</a>
                                    <button type="button" class="admin-action-link">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<script>
(function(){
    const form = document.getElementById('documents-upload-form');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submit = form.querySelector('button[type=submit]');
        if (submit) submit.disabled = true;
        try {
            if (typeof AppAPI === 'undefined') {
                alert('Error: missing JS resources.');
                return;
            }
            const res = await AppAPI.uploadForm(form);
            if (res && res.success) {
                alert('Upload successful');
                location.reload();
            } else {
                alert('Error: ' + (res && res.error ? res.error : 'Server error'));
            }
        } catch (err) {
            alert('Network error: ' + err.message);
        } finally {
            if (submit) submit.disabled = false;
        }
    });
})();
</script>
