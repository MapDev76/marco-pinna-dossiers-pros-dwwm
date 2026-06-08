<?php
/**
 * Shared CRUD modal shell and embedded templates.
 *
 * This file includes a single modal card (`#crud-modal`) used as a shell for
 * multiple entity templates. Each `<template id="crud-template-...">`
 * contains the form and listing for an entity (companies, users, departments,
 * documents, messages). The dashboard JavaScript copies the selected template
 * into `#crud-modal-body` and wires interactive behavior.
 *
 * Required runtime data provided by the dashboard controller:
 * - `$dashboardModalCompanies` : array of companies for selects/lists
 * - `$dashboardModalDepartments` : array of departments
 * - `$dashboardModalUsers` : array of users
 * - `$dashboardModalDocuments` : array of documents
 */
if (!isLoggedIn()) {
    return;
}

$modalCurrentRole = currentUser()['role'] ?? 'employee';
?>
<div class="dashboard-overlay" id="dashboard-overlay" hidden></div>
<section class="crud-modal" id="crud-modal" hidden role="dialog" aria-modal="true" aria-labelledby="crud-modal-title">
    <div class="crud-modal-card">
        <button type="button" class="dashboard-modal-close" data-modal-close aria-label="<?php echo e(t('common.close')); ?>">&times;</button>

        <div class="crud-modal-head">
            <div>
                <h2 id="crud-modal-title"><?php echo e(t('crud.title')); ?></h2>
                <p id="crud-modal-subtitle" class="crud-modal-subtitle"></p>
            </div>
        </div>

        <div class="crud-modal-body" id="crud-modal-body">
            <div class="crud-empty-state"><?php echo e(t('crud.select_sidebar_item')); ?></div>
        </div>
    </div>
</section>

<template id="crud-template-placeholder">
    <div class="crud-panel">
        <h3><?php echo e(t('crud.common_shell')); ?></h3>
        <p><?php echo e(t('crud.shared_modal')); ?></p>
        <div class="crud-empty-state"><?php echo e(t('crud.no_template')); ?></div>
    </div>
</template>

<template id="crud-template-companies">
    <div class="crud-panel-grid crud-panel-grid-wide" data-crud-entity="companies">
        <section class="crud-panel">
            <h3 id="crud-company-form-heading"><?php echo e(t('crud.create_company')); ?></h3>
            <form method="post" action="<?php echo appUrl('companies'); ?>" class="admin-form crud-form" id="crud-company-form">
                <input type="hidden" name="action" value="create" id="crud-company-action">
                <input type="hidden" name="id" value="" id="crud-company-id">
                <label>
                    <?php echo e(t('crud.company_name')); ?>
                    <input type="text" name="name" id="crud-company-name" required>
                </label>
                <label>
                    <?php echo e(t('crud.type')); ?>
                    <select name="type" id="crud-company-type">
                        <?php foreach (['hotel', 'hospital', 'clinic', 'elderly_center', 'restaurant', 'other'] as $companyType): ?>
                            <option value="<?php echo e($companyType); ?>"><?php echo e($companyType); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <?php echo e(t('crud.city')); ?>
                    <input type="text" name="city" id="crud-company-city">
                </label>
                <label>
                    <?php echo e(t('crud.address')); ?>
                    <input type="text" name="address" id="crud-company-address">
                </label>
                <label>
                    <?php echo e(t('crud.zip_code')); ?>
                    <input type="text" name="zip_code" id="crud-company-zip-code">
                </label>
                <label>
                    <?php echo e(t('crud.phone')); ?>
                    <input type="text" name="phone" id="crud-company-phone">
                </label>
                <label>
                    <?php echo e(t('crud.email')); ?>
                    <input type="email" name="email" id="crud-company-email">
                </label>
                <label>
                    <?php echo e(t('crud.logo_path')); ?>
                    <input type="text" name="logo_path" id="crud-company-logo-path" placeholder="assets/images/...">
                </label>
                <label>
                    <?php echo e(t('crud.signature_ip')); ?>
                    <input type="text" name="signature_ip" id="crud-company-signature-ip" placeholder="<?php echo e(t('crud.leave_empty_if_not_used')); ?>">
                </label>
                <div class="form-actions">
                    <button type="submit" id="crud-company-submit"><?php echo e(t('crud.create')); ?></button>
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-crud-reset-company><?php echo e(t('crud.reset')); ?></button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3><?php echo e(t('crud.companies_list')); ?></h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalCompanies ?? [])): ?>
                    <div class="crud-empty-state"><?php echo e(t('crud.no_companies')); ?></div>
                <?php endif; ?>
                <?php foreach (($dashboardModalCompanies ?? []) as $company): ?>
                    <article class="company-card" data-company-id="<?php echo (int) $company['id']; ?>" data-company-name="<?php echo e($company['name']); ?>" data-company-type="<?php echo e($company['type'] ?? 'other'); ?>" data-company-city="<?php echo e($company['city'] ?? ''); ?>" data-company-address="<?php echo e($company['address'] ?? ''); ?>" data-company-zip-code="<?php echo e($company['zip_code'] ?? ''); ?>" data-company-phone="<?php echo e($company['phone'] ?? ''); ?>" data-company-email="<?php echo e($company['email'] ?? ''); ?>" data-company-logo-path="<?php echo e($company['logo_path'] ?? ''); ?>" data-company-signature-ip="<?php echo e($company['signature_ip'] ?? ''); ?>">
                        <header class="company-card-head">
                            <div class="company-card-title"><?php echo e($company['name']); ?></div>
                        </header>
                        <div class="company-card-actions company-card-actions--inline">
                            <button type="button" class="company-card-action" data-company-action="edit" aria-label="<?php echo e(t('crud.edit_company')); ?>">
                                <span aria-hidden="true">✎</span>
                            </button>
                            <form method="post" action="<?php echo appUrl('companies'); ?>" class="company-card-delete-form" onsubmit="return confirm('<?php echo e(t('crud.company_card_delete')); ?>');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $company['id']; ?>">
                                <button type="submit" class="company-card-action is-danger" aria-label="<?php echo e(t('crud.delete_company')); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</template>

<template id="crud-template-users">
    <div class="crud-panel-grid crud-panel-grid-wide" data-crud-entity="users">
        <section class="crud-panel">
            <h3 id="crud-user-form-heading"><?php echo e(t('crud.create_user')); ?></h3>
            <form method="post" action="<?php echo appUrl('users'); ?>" class="admin-form crud-form" id="crud-user-form">
                <input type="hidden" name="action" value="create" id="crud-user-action">
                <input type="hidden" name="id" value="" id="crud-user-id">

                <label>
                    <?php echo e(t('crud.first_name')); ?>
                    <input type="text" name="first_name" id="crud-user-first-name" required>
                </label>
                <label>
                    <?php echo e(t('crud.last_name')); ?>
                    <input type="text" name="last_name" id="crud-user-last-name" required>
                </label>
                <label>
                    <?php echo e(t('crud.email')); ?>
                    <input type="email" name="email" id="crud-user-email" required>
                </label>
                <label>
                    <?php echo e(t('crud.phone')); ?>
                    <input type="text" name="phone" id="crud-user-phone">
                </label>
                <label>
                    <?php echo e(t('crud.password')); ?>
                    <input type="password" name="password" id="crud-user-password" placeholder="<?php echo e(t('crud.leave_blank_password')); ?>">
                </label>
                <label>
                    <?php echo e(t('crud.role')); ?>
                    <select name="role" id="crud-user-role">
                        <?php if ($modalCurrentRole === 'super_admin'): ?>
                            <option value="employee"><?php echo e(t('crud.role_employee')); ?></option>
                            <option value="department_manager"><?php echo e(t('crud.role_department_manager')); ?></option>
                            <option value="admin"><?php echo e(t('crud.role_admin')); ?></option>
                            <option value="super_admin"><?php echo e(t('crud.role_super_admin')); ?></option>
                        <?php elseif ($modalCurrentRole === 'admin'): ?>
                            <option value="employee"><?php echo e(t('crud.role_employee')); ?></option>
                            <option value="department_manager"><?php echo e(t('crud.role_department_manager')); ?></option>
                            <option value="admin"><?php echo e(t('crud.role_admin')); ?></option>
                        <?php else: ?>
                            <option value="employee"><?php echo e(t('crud.role_employee')); ?></option>
                        <?php endif; ?>
                    </select>
                </label>
                <label>
                    <?php echo e(t('crud.status')); ?>
                    <select name="status" id="crud-user-status">
                        <option value="active"><?php echo e(t('crud.active')); ?></option>
                        <option value="inactive"><?php echo e(t('crud.inactive')); ?></option>
                    </select>
                </label>
                <label>
                    <?php echo e(t('crud.company')); ?>
                    <select id="crud-user-company-filter">
                        <option value=""><?php echo e(t('crud.all_companies')); ?></option>
                        <?php foreach (($dashboardModalCompanies ?? []) as $company): ?>
                            <option value="<?php echo (int) $company['id']; ?>"><?php echo e($company['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <?php echo e(t('crud.department')); ?>
                    <select name="department_id" id="crud-user-department-id">
                        <option value=""><?php echo e(t('crud.none')); ?></option>
                        <?php foreach (($dashboardModalDepartments ?? []) as $department): ?>
                            <option value="<?php echo (int) $department['id']; ?>" data-company-id="<?php echo (int) $department['company_id']; ?>">
                                <?php echo e(($department['company_name'] ?? '') . ' - ' . $department['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="form-actions span-2">
                    <button type="submit" id="crud-user-submit"><?php echo e(t('crud.create')); ?></button>
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-crud-reset-user><?php echo e(t('crud.reset')); ?></button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3><?php echo e(t('crud.users_list')); ?></h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalUsers ?? [])): ?>
                    <div class="crud-empty-state"><?php echo e(t('crud.no_users')); ?></div>
                <?php endif; ?>
                <?php foreach (($dashboardModalUsers ?? []) as $user): ?>
                    <article class="company-card company-card--stacked" data-user-id="<?php echo (int) $user['id']; ?>" data-user-first-name="<?php echo e($user['first_name']); ?>" data-user-last-name="<?php echo e($user['last_name']); ?>" data-user-email="<?php echo e($user['email']); ?>" data-user-phone="<?php echo e($user['phone'] ?? ''); ?>" data-user-role="<?php echo e($user['role']); ?>" data-user-status="<?php echo e($user['status'] ?? 'active'); ?>" data-user-department-id="<?php echo (int) ($user['department_id'] ?? 0); ?>">
                        <div class="company-card-head">
                            <div class="company-card-title"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></div>
                            <div class="company-card-meta"><?php echo e($user['department_name'] ?? t('crud.no_department')); ?></div>
                        </div>
                        <div class="company-card-actions company-card-actions--inline">
                            <span class="company-card-chip"><?php echo e($user['role']); ?></span>
                            <button type="button" class="company-card-action" data-user-action="edit" aria-label="<?php echo e(t('crud.edit_user')); ?>">
                                <span aria-hidden="true">✎</span>
                            </button>
                            <form method="post" action="<?php echo appUrl('users'); ?>" class="company-card-delete-form" onsubmit="return confirm('<?php echo e(t('crud.user_card_delete')); ?>');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                <button type="submit" class="company-card-action is-danger" aria-label="<?php echo e(t('crud.delete_user')); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</template>

<template id="crud-template-departments">
    <div class="crud-panel-grid crud-panel-grid-wide" data-crud-entity="departments">
        <section class="crud-panel">
            <h3 id="crud-department-form-heading"><?php echo e(t('crud.create_department')); ?></h3>
            <form method="post" action="<?php echo appUrl('departments'); ?>" class="admin-form crud-form" id="crud-department-edit-form">
                <input type="hidden" name="action" value="create" id="crud-department-action">
                <input type="hidden" name="id" value="" id="crud-department-id">

                <label>
                    <?php echo e(t('crud.company')); ?>
                    <select name="company_id" id="crud-department-company-select" required>
                        <option value=""><?php echo e(t('crud.select_company')); ?></option>
                        <?php foreach (($dashboardModalCompanies ?? []) as $company): ?>
                            <option value="<?php echo (int) $company['id']; ?>"><?php echo e($company['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <?php echo e(t('crud.department_name')); ?>
                    <input type="text" name="name" id="crud-department-name-input" required>
                </label>
                <label>
                    <?php echo e(t('crud.department_head')); ?>
                    <select name="head_user_id" id="crud-department-head-user-select">
                        <option value=""><?php echo e(t('crud.none')); ?></option>
                        <?php foreach (($dashboardModalUsers ?? []) as $user): ?>
                            <option value="<?php echo (int) $user['id']; ?>" data-company-id="<?php echo (int) ($user['company_id'] ?? 0); ?>"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-2">
                    <?php echo e(t('crud.description')); ?>
                    <textarea name="description" id="crud-department-description-input" rows="4"></textarea>
                </label>
                <div class="form-actions span-2">
                    <button type="submit" id="crud-department-submit"><?php echo e(t('crud.create')); ?></button>
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-crud-reset-department><?php echo e(t('crud.reset')); ?></button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3><?php echo e(t('crud.departments_list')); ?></h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalDepartments ?? [])): ?>
                    <div class="crud-empty-state"><?php echo e(t('crud.no_departments')); ?></div>
                <?php endif; ?>
                <?php foreach (($dashboardModalDepartments ?? []) as $department): ?>
                    <article class="company-card company-card--stacked" data-department-id="<?php echo (int) $department['id']; ?>" data-department-company-id="<?php echo (int) $department['company_id']; ?>" data-department-company-name="<?php echo e($department['company_name'] ?? ''); ?>" data-department-name="<?php echo e($department['name']); ?>" data-department-description="<?php echo e($department['description'] ?? ''); ?>" data-department-head-user-id="<?php echo (int) ($department['head_user_id'] ?? 0); ?>">
                        <div class="company-card-head">
                            <div class="company-card-title"><?php echo e($department['name']); ?></div>
                            <div class="company-card-meta"><?php echo e($department['company_name'] ?? ''); ?></div>
                        </div>
                        <div class="company-card-actions company-card-actions--inline">
                            <span class="company-card-chip"><?php echo e($department['head_user_name'] ?? t('crud.no_department')); ?></span>
                            <button type="button" class="company-card-action" data-department-action="edit" aria-label="<?php echo e(t('crud.edit_department')); ?>">
                                <span aria-hidden="true">✎</span>
                            </button>
                            <form method="post" action="<?php echo appUrl('departments'); ?>" class="company-card-delete-form" onsubmit="return confirm('<?php echo e(t('crud.department_card_delete')); ?>');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $department['id']; ?>">
                                <button type="submit" class="company-card-action is-danger" aria-label="<?php echo e(t('crud.delete_department')); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</template>

<template id="crud-template-documents">
    <div class="crud-panel-grid crud-panel-grid-wide" data-crud-entity="documents">
        <section class="crud-panel">
            <h3><?php echo e(t('crud.documents_overview')); ?></h3>
            <p class="crud-modal-subtitle"><?php echo e(t('crud.documents_hint')); ?></p>
            <form class="admin-form crud-form" id="crud-document-share-form" enctype="multipart/form-data">
                <label class="span-2">
                    <?php echo e(t('crud.document')); ?>
                    <input type="file" id="crud-document-file" required>
                </label>
                <label>
                    <?php echo e(t('crud.recipients')); ?>
                    <select id="crud-document-recipient-scope">
                        <option value="selected">Employes selectionnes</option>
                        <option value="all">Tous les employes disponibles</option>
                    </select>
                </label>
                <label class="span-2">
                    <?php echo e(t('crud.title')); ?>
                    <input type="text" id="crud-document-title" maxlength="255" placeholder="<?php echo e(t('crud.message_title_default')); ?>">
                </label>
                <label class="span-2">
                    <?php echo e(t('crud.message')); ?>
                    <textarea id="crud-document-message" rows="3" placeholder="Ajoutez un court message pour les destinataires du document."></textarea>
                </label>
                <label class="span-2" id="crud-document-recipient-label">
                    <?php echo e(t('crud.recipients')); ?>
                    <select id="crud-document-recipient-ids" multiple size="6">
                        <?php foreach (($dashboardModalUsers ?? []) as $user): ?>
                            <?php if (($user['role'] ?? '') === 'employee'): ?>
                                <option value="<?php echo (int) $user['id']; ?>"
                                        data-role="<?php echo e($user['role'] ?? ''); ?>"
                                        data-department-id="<?php echo (int) ($user['department_id'] ?? 0); ?>">
                                    <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-2 crud-inline-choice" for="crud-document-require-signature">
                    <input type="checkbox" id="crud-document-require-signature" value="1">
                    <span class="company-card-chip">Demander une signature numerique</span>
                </label>
                <div class="form-actions span-2">
                    <button type="submit" id="crud-document-share-submit" class="admin-action-link">Partager le document</button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3><?php echo e(t('crud.documents_list')); ?></h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalDocuments ?? [])): ?>
                    <div class="crud-empty-state"><?php echo e(t('crud.no_documents')); ?></div>
                <?php endif; ?>
                <?php foreach (($dashboardModalDocuments ?? []) as $document): ?>
                    <article class="company-card company-card--stacked">
                        <div class="company-card-head">
                            <div class="company-card-title"><?php echo e($document['file_name'] ?? t('crud.document')); ?></div>
                            <div class="company-card-meta"><?php echo e(($document['first_name'] ?? '') . ' ' . ($document['last_name'] ?? '')); ?> • <?php echo e($document['upload_date'] ?? ''); ?></div>
                        </div>
                        <div class="company-card-actions company-card-actions--inline">
                            <span class="company-card-chip"><?php echo e($document['document_type'] ?? t('crud.other')); ?></span>
                            <span class="company-card-chip"><?php echo e($document['status'] ?? t('crud.pending')); ?></span>
                            <button type="button"
                                    class="company-card-action"
                                    title="<?php echo e(t('crud.attach_send_employees')); ?>"
                                    data-document-send-id="<?php echo (int) ($document['id'] ?? 0); ?>"
                                    data-document-send-name="<?php echo e($document['file_name'] ?? 'Document'); ?>">
                                <img src="<?php echo appUrl('assets/icons/mail-open.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                            </button>
                            <button type="button"
                                    class="company-card-action"
                                    title="<?php echo e(t('crud.attach_send_department')); ?>"
                                    data-document-send-all-id="<?php echo (int) ($document['id'] ?? 0); ?>"
                                    data-document-send-all-name="<?php echo e($document['file_name'] ?? 'Document'); ?>">
                                <img src="<?php echo appUrl('assets/icons/mails.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                            </button>
                            <a class="company-card-action" href="<?php echo appUrl('document-download', ['id' => (int) $document['id']]); ?>" title="<?php echo e(t('crud.download_document')); ?>">
                                <img src="<?php echo appUrl('assets/icons/circle-arrow-out-up-left.svg'); ?>" alt="" aria-hidden="true" class="company-card-action-icon">
                            </a>
                            <?php if (in_array($modalCurrentRole, ['super_admin', 'admin', 'department_manager'], true)): ?>
                                <button type="button"
                                        class="company-card-action is-danger"
                                        title="<?php echo e(t('crud.delete_document')); ?>"
                                        data-document-delete-id="<?php echo (int) ($document['id'] ?? 0); ?>"
                                        data-document-delete-name="<?php echo e($document['file_name'] ?? 'Document'); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</template>

<template id="crud-template-messages">
    <div class="crud-panel-grid crud-panel-grid-wide" data-crud-entity="messages">
        <section class="crud-panel">
            <h3 id="crud-message-form-heading"><?php echo e(t('crud.create_message')); ?></h3>
            <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form crud-form" id="crud-message-form">
                <input type="hidden" name="dashboard_action" value="create_message">
                <label>
                    <?php echo e(t('crud.message_kind')); ?>
                    <select name="message_kind" id="crud-message-kind">
                        <option value="request"><?php echo e(t('crud.message_request')); ?></option>
                        <option value="notification"><?php echo e(t('crud.message_notification')); ?></option>
                    </select>
                </label>
                <label>
                    <?php echo e(t('crud.request_type')); ?>
                    <select name="request_type" id="crud-message-request-type">
                        <option value="shift_coverage"><?php echo e(t('crud.request_shift_coverage')); ?></option>
                        <option value="leave"><?php echo e(t('crud.request_leave')); ?></option>
                        <option value="permission"><?php echo e(t('crud.request_permission')); ?></option>
                        <option value="document_signature"><?php echo e(t('crud.request_document_signature')); ?></option>
                    </select>
                </label>
                <label class="span-2">
                    <?php echo e(t('crud.title')); ?>
                    <input type="text" name="message_title" id="crud-message-title" required>
                </label>
                <label class="span-2">
                    <?php echo e(t('crud.message')); ?>
                    <textarea name="message_body" id="crud-message-body" rows="4" required></textarea>
                </label>
                <label>
                    <?php echo e(t('crud.attach_document')); ?>
                    <select name="document_id" id="crud-message-document-id">
                        <option value=""><?php echo e(t('crud.none')); ?></option>
                        <?php foreach (($dashboardModalDocuments ?? []) as $document): ?>
                            <option value="<?php echo (int) $document['id']; ?>"><?php echo e($document['file_name'] ?? 'Document'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-2">
                    <?php echo e(t('crud.recipients')); ?>
                    <select name="recipient_ids[]" id="crud-message-recipient-ids" multiple size="6" required>
                        <?php foreach (($dashboardModalUsers ?? []) as $user): ?>
                            <option value="<?php echo (int) $user['id']; ?>"
                                    data-role="<?php echo e($user['role'] ?? ''); ?>"
                                    data-department-id="<?php echo (int) ($user['department_id'] ?? 0); ?>">
                                <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-actions span-2">
                    <button type="submit" id="crud-message-submit"><?php echo e(t('crud.send_message')); ?></button>
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-crud-reset-message><?php echo e(t('crud.reset')); ?></button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3><?php echo e(t('crud.messages_list')); ?></h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalMessages ?? [])): ?>
                    <div class="crud-empty-state"><?php echo e(t('crud.no_messages')); ?></div>
                <?php endif; ?>
                <?php foreach (($dashboardModalMessages ?? []) as $message): ?>
                    <article class="company-card company-card--stacked">
                        <div class="company-card-head">
                            <div class="company-card-title"><?php echo e($message['title'] ?? t('crud.message_title_default')); ?></div>
                            <div class="company-card-meta"><?php echo e($message['sender_name'] ?? ''); ?> <?php echo !empty($message['recipient_name']) ? '→ ' . e($message['recipient_name']) : ''; ?></div>
                        </div>
                        <div class="company-card-actions company-card-actions--inline">
                            <span class="company-card-chip"><?php echo e($message['type'] ?? t('crud.message_request')); ?></span>
                            <span class="company-card-chip"><?php echo e($message['status'] ?? t('crud.pending')); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</template>