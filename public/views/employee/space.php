<!-- Espace employé : visualise les quarts, les présences et les demandes personnelles. -->
<?php
$shifts = $shifts ?? [];
$requests = $requests ?? [];
$attendances = $attendances ?? [];
$requestTypeLabels = [
    'shift_coverage' => 'Remplacement de quart',
    'leave' => 'Congé',
    'permission' => 'Autorisation',
    'document_signature' => 'Signature de document',
    'notification' => 'Notification',
];
$statusLabels = [
    'assigned' => 'Attribué',
    'completed' => 'Terminé',
    'cancelled' => 'Annulé',
    'in_progress' => 'En cours',
    'present' => 'Présent',
    'absent' => 'Absent',
    'late' => 'En retard',
    'early_departure' => 'Départ anticipé',
    'pending' => 'En attente',
    'approved' => 'Approuvé',
    'rejected' => 'Refusé',
    'read' => 'Lu',
    'unread' => 'Non lu',
];
?>
<div class="admin-shell">
    <div class="admin-hero">
        <h1>Mon espace employé</h1>
        <p>Retrouve ici tes quarts, la signature de présence et la création de demandes.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <h2>Signer une présence</h2>
        <form method="post" class="admin-form admin-form-grid">
            <input type="hidden" name="action" value="sign_attendance">
            <label>
                <span>Choisir un quart</span>
                <select name="user_shift_id" required>
                    <option value="">Sélectionner</option>
                    <?php foreach ($shifts as $shift): ?>
                        <option value="<?php echo (int) $shift['id']; ?>"><?php echo e($shift['work_date'] . ' - ' . $shift['shift_name'] . ' - ' . $shift['department_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="form-actions span-2">
                <button type="submit">Enregistrer la présence</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Créer une demande</h2>
        <form method="post" class="admin-form admin-form-grid">
            <input type="hidden" name="action" value="create_request">
            <label>
                <span>Type</span>
                <select name="type" required>
                    <option value="">Sélectionner</option>
                    <option value="shift_coverage">Remplacement de quart</option>
                    <option value="leave">Congé</option>
                    <option value="permission">Autorisation</option>
                    <option value="document_signature">Signature de document</option>
                    <option value="notification">Notification</option>
                </select>
            </label>
            <label>
                <span>Titre</span>
                <input type="text" name="title" placeholder="Résumé de la demande">
            </label>
            <label class="span-2">
                <span>Message</span>
                <textarea name="message" rows="4" required></textarea>
            </label>
            <div class="form-actions span-2">
                <button type="submit">Envoyer la demande</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Mes quarts</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Quart</th>
                        <th>Département</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shifts)): ?>
                        <tr><td colspan="4">Aucun quart disponible.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($shifts as $shift): ?>
                        <tr>
                            <td><?php echo e($shift['work_date']); ?></td>
                            <td><?php echo e($shift['shift_name']); ?></td>
                            <td><?php echo e($shift['department_name']); ?></td>
                            <td><?php echo e($statusLabels[$shift['status']] ?? $shift['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <h2>Mes présences</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Quart</th>
                        <th>Statut</th>
                        <th>Entrée</th>
                        <th>Sortie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendances)): ?>
                        <tr><td colspan="5">Aucune présence enregistrée.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($attendances as $attendance): ?>
                        <tr>
                            <td><?php echo e($attendance['work_date']); ?></td>
                            <td><?php echo e($attendance['shift_name'] ?? '-'); ?></td>
                            <td><?php echo e($statusLabels[$attendance['status']] ?? $attendance['status']); ?></td>
                            <td><?php echo e($attendance['check_in_time'] ?? '-'); ?></td>
                            <td><?php echo e($attendance['check_out_time'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <h2>Mes demandes</h2>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Titre</th>
                        <th>Message</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="5">Aucune demande créée.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo e($requestTypeLabels[$request['type']] ?? $request['type']); ?></td>
                            <td><?php echo e($request['title'] ?? '-'); ?></td>
                            <td><?php echo e($request['message']); ?></td>
                            <td><?php echo e($statusLabels[$request['status']] ?? $request['status']); ?></td>
                            <td><?php echo e($request['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>