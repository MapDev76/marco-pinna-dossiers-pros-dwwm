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
        <button type="button" class="dashboard-modal-close" data-modal-close aria-label="Close">&times;</button>

        <div class="crud-modal-head">
            <div>
                <h2 id="crud-modal-title">CRUD</h2>
                <p id="crud-modal-subtitle" class="crud-modal-subtitle"></p>
            </div>
        </div>

        <div class="crud-modal-body" id="crud-modal-body">
            <div class="crud-empty-state">Select an item from the sidebar to load its CRUD template.</div>
        </div>
    </div>
</section>

<template id="crud-template-placeholder">
    <div class="crud-panel">
        <h3>Common CRUD shell</h3>
        <p>This modal is shared by every dashboard action. We will adapt its content per entity in the next step.</p>
        <div class="crud-empty-state">No CRUD template has been connected for this element yet.</div>
    </div>
</template>

<template id="crud-template-companies">
    <div class="crud-panel-grid crud-panel-grid-wide" data-crud-entity="companies">
        <section class="crud-panel">
            <h3 id="crud-company-form-heading">Create company</h3>
            <form method="post" action="<?php echo appUrl('companies'); ?>" class="admin-form crud-form" id="crud-company-form">
                <input type="hidden" name="action" value="create" id="crud-company-action">
                <input type="hidden" name="id" value="" id="crud-company-id">
                <label>
                    Name
                    <input type="text" name="name" id="crud-company-name" required>
                </label>
                <label>
                    Type
                    <select name="type" id="crud-company-type">
                        <?php foreach (['hotel', 'hospital', 'clinic', 'elderly_center', 'restaurant', 'other'] as $companyType): ?>
                            <option value="<?php echo e($companyType); ?>"><?php echo e($companyType); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    City
                    <input type="text" name="city" id="crud-company-city">
                </label>
                <label>
                    Address
                    <input type="text" name="address" id="crud-company-address">
                </label>
                <label>
                    Zip code
                    <input type="text" name="zip_code" id="crud-company-zip-code">
                </label>
                <label>
                    Phone
                    <input type="text" name="phone" id="crud-company-phone">
                </label>
                <label>
                    Email
                    <input type="email" name="email" id="crud-company-email">
                </label>
                <label>
                    Logo path
                    <input type="text" name="logo_path" id="crud-company-logo-path" placeholder="assets/images/...">
                </label>
                <label>
                    Signature IP
                    <input type="text" name="signature_ip" id="crud-company-signature-ip" placeholder="Leave empty if not used">
                </label>
                <div class="form-actions">
                    <button type="submit" id="crud-company-submit">Create</button>
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-crud-reset-company>Reset</button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3>Companies list</h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalCompanies ?? [])): ?>
                    <div class="crud-empty-state">No companies to display.</div>
                <?php endif; ?>
                <?php foreach (($dashboardModalCompanies ?? []) as $company): ?>
                    <article class="company-card" data-company-id="<?php echo (int) $company['id']; ?>" data-company-name="<?php echo e($company['name']); ?>" data-company-type="<?php echo e($company['type'] ?? 'other'); ?>" data-company-city="<?php echo e($company['city'] ?? ''); ?>" data-company-address="<?php echo e($company['address'] ?? ''); ?>" data-company-zip-code="<?php echo e($company['zip_code'] ?? ''); ?>" data-company-phone="<?php echo e($company['phone'] ?? ''); ?>" data-company-email="<?php echo e($company['email'] ?? ''); ?>" data-company-logo-path="<?php echo e($company['logo_path'] ?? ''); ?>" data-company-signature-ip="<?php echo e($company['signature_ip'] ?? ''); ?>">
                        <header class="company-card-head">
                            <div class="company-card-title"><?php echo e($company['name']); ?></div>
                        </header>
                        <div class="company-card-actions company-card-actions--inline">
                            <button type="button" class="company-card-action" data-company-action="edit" aria-label="Edit company">
                                <span aria-hidden="true">✎</span>
                            </button>
                            <form method="post" action="<?php echo appUrl('companies'); ?>" class="company-card-delete-form" onsubmit="return confirm('Delete this company?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $company['id']; ?>">
                                <button type="submit" class="company-card-action is-danger" aria-label="Delete company">
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
            <h3 id="crud-user-form-heading">Create user</h3>
            <form method="post" action="<?php echo appUrl('users'); ?>" class="admin-form crud-form" id="crud-user-form">
                <input type="hidden" name="action" value="create" id="crud-user-action">
                <input type="hidden" name="id" value="" id="crud-user-id">

                <label>
                    First name
                    <input type="text" name="first_name" id="crud-user-first-name" required>
                </label>
                <label>
                    Last name
                    <input type="text" name="last_name" id="crud-user-last-name" required>
                </label>
                <label>
                    Email
                    <input type="email" name="email" id="crud-user-email" required>
                </label>
                <label>
                    Phone
                    <input type="text" name="phone" id="crud-user-phone">
                </label>
                <label>
                    Password
                    <input type="password" name="password" id="crud-user-password" placeholder="Leave blank to keep existing password">
                </label>
                <label>
                    Role
                    <select name="role" id="crud-user-role">
                        <?php if ($modalCurrentRole === 'super_admin'): ?>
                            <option value="employee">Employee</option>
                            <option value="department_manager">Department manager</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super admin</option>
                        <?php elseif ($modalCurrentRole === 'admin'): ?>
                            <option value="employee">Employee</option>
                            <option value="department_manager">Department manager</option>
                            <option value="admin">Admin</option>
                        <?php else: ?>
                            <option value="employee">Employee</option>
                        <?php endif; ?>
                    </select>
                </label>
                <label>
                    Status
                    <select name="status" id="crud-user-status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                <label>
                    Company
                    <select id="crud-user-company-filter">
                        <option value="">All companies</option>
                        <?php foreach (($dashboardModalCompanies ?? []) as $company): ?>
                            <option value="<?php echo (int) $company['id']; ?>"><?php echo e($company['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Department
                    <select name="department_id" id="crud-user-department-id">
                        <option value="">None</option>
                        <?php foreach (($dashboardModalDepartments ?? []) as $department): ?>
                            <option value="<?php echo (int) $department['id']; ?>" data-company-id="<?php echo (int) $department['company_id']; ?>">
                                <?php echo e(($department['company_name'] ?? '') . ' - ' . $department['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="form-actions span-2">
                    <button type="submit" id="crud-user-submit">Create user</button>
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-crud-reset-user>Reset</button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3>Users list</h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalUsers ?? [])): ?>
                    <div class="crud-empty-state">No users available.</div>
                <?php endif; ?>
                <?php foreach (($dashboardModalUsers ?? []) as $user): ?>
                    <article class="company-card company-card--stacked" data-user-id="<?php echo (int) $user['id']; ?>" data-user-first-name="<?php echo e($user['first_name']); ?>" data-user-last-name="<?php echo e($user['last_name']); ?>" data-user-email="<?php echo e($user['email']); ?>" data-user-phone="<?php echo e($user['phone'] ?? ''); ?>" data-user-role="<?php echo e($user['role']); ?>" data-user-status="<?php echo e($user['status'] ?? 'active'); ?>" data-user-department-id="<?php echo (int) ($user['department_id'] ?? 0); ?>">
                        <div class="company-card-head">
                            <div class="company-card-title"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></div>
                            <div class="company-card-meta"><?php echo e($user['department_name'] ?? 'No department'); ?></div>
                        </div>
                        <div class="company-card-actions company-card-actions--inline">
                            <span class="company-card-chip"><?php echo e($user['role']); ?></span>
                            <button type="button" class="company-card-action" data-user-action="edit" aria-label="Edit user">
                                <span aria-hidden="true">✎</span>
                            </button>
                            <form method="post" action="<?php echo appUrl('users'); ?>" class="company-card-delete-form" onsubmit="return confirm('Delete this user?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                <button type="submit" class="company-card-action is-danger" aria-label="Delete user">
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
            <h3 id="crud-department-form-heading">Create department</h3>
            <form method="post" action="<?php echo appUrl('departments'); ?>" class="admin-form crud-form" id="crud-department-edit-form">
                <input type="hidden" name="action" value="create" id="crud-department-action">
                <input type="hidden" name="id" value="" id="crud-department-id">

                <label>
                    Company
                    <select name="company_id" id="crud-department-company-select" required>
                        <option value="">Select a company</option>
                        <?php foreach (($dashboardModalCompanies ?? []) as $company): ?>
                            <option value="<?php echo (int) $company['id']; ?>"><?php echo e($company['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Department name
                    <input type="text" name="name" id="crud-department-name-input" required>
                </label>
                <label>
                    Department head
                    <select name="head_user_id" id="crud-department-head-user-select">
                        <option value="">None</option>
                        <?php foreach (($dashboardModalUsers ?? []) as $user): ?>
                            <option value="<?php echo (int) $user['id']; ?>" data-company-id="<?php echo (int) ($user['company_id'] ?? 0); ?>"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-2">
                    Description
                    <textarea name="description" id="crud-department-description-input" rows="4"></textarea>
                </label>
                <div class="form-actions span-2">
                    <button type="submit" id="crud-department-submit">Create department</button>
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-crud-reset-department>Reset</button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3>Departments list</h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalDepartments ?? [])): ?>
                    <div class="crud-empty-state">No departments available.</div>
                <?php endif; ?>
                <?php foreach (($dashboardModalDepartments ?? []) as $department): ?>
                    <article class="company-card company-card--stacked" data-department-id="<?php echo (int) $department['id']; ?>" data-department-company-id="<?php echo (int) $department['company_id']; ?>" data-department-company-name="<?php echo e($department['company_name'] ?? ''); ?>" data-department-name="<?php echo e($department['name']); ?>" data-department-description="<?php echo e($department['description'] ?? ''); ?>" data-department-head-user-id="<?php echo (int) ($department['head_user_id'] ?? 0); ?>">
                        <div class="company-card-head">
                            <div class="company-card-title"><?php echo e($department['name']); ?></div>
                            <div class="company-card-meta"><?php echo e($department['company_name'] ?? ''); ?></div>
                        </div>
                        <div class="company-card-actions company-card-actions--inline">
                            <span class="company-card-chip"><?php echo e($department['head_user_name'] ?? 'No head'); ?></span>
                            <button type="button" class="company-card-action" data-department-action="edit" aria-label="Edit department">
                                <span aria-hidden="true">✎</span>
                            </button>
                            <form method="post" action="<?php echo appUrl('departments'); ?>" class="company-card-delete-form" onsubmit="return confirm('Delete this department?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $department['id']; ?>">
                                <button type="submit" class="company-card-action is-danger" aria-label="Delete department">
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
            <h3>Documents overview</h3>
            <p class="crud-modal-subtitle">Select a document below to attach it to a message.</p>
            <div class="crud-empty-state">Document upload is handled in the documents library. This panel lists available files only.</div>
        </section>

        <section class="crud-panel">
            <h3>Documents list</h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalDocuments ?? [])): ?>
                    <div class="crud-empty-state">No documents available.</div>
                <?php endif; ?>
                <?php foreach (($dashboardModalDocuments ?? []) as $document): ?>
                    <article class="company-card company-card--stacked">
                        <div class="company-card-head">
                            <div class="company-card-title"><?php echo e($document['file_name'] ?? 'Document'); ?></div>
                            <div class="company-card-meta"><?php echo e(($document['first_name'] ?? '') . ' ' . ($document['last_name'] ?? '')); ?></div>
                        </div>
                        <div class="company-card-actions company-card-actions--inline">
                            <span class="company-card-chip"><?php echo e($document['document_type'] ?? 'other'); ?></span>
                            <span class="company-card-chip"><?php echo e($document['status'] ?? 'pending'); ?></span>
                            <?php if (!empty($document['file_path'])): ?>
                                <a class="company-card-action" href="<?php echo appUrl('document-download', ['id' => (int) $document['id']]); ?>" title="Download document">
                                    <span aria-hidden="true">⬇</span>
                                </a>
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
            <h3 id="crud-message-form-heading">Create message</h3>
            <form method="post" action="<?php echo appUrl('dashboard'); ?>" class="admin-form crud-form" id="crud-message-form">
                <input type="hidden" name="dashboard_action" value="create_message">
                <label>
                    Message kind
                    <select name="message_kind" id="crud-message-kind">
                        <option value="request">Request</option>
                        <option value="notification">Notification</option>
                    </select>
                </label>
                <label>
                    Request type
                    <select name="request_type" id="crud-message-request-type">
                        <option value="shift_coverage">Shift coverage</option>
                        <option value="leave">Leave</option>
                        <option value="permission">Permission</option>
                        <option value="document_signature">Document signature</option>
                    </select>
                </label>
                <label class="span-2">
                    Title
                    <input type="text" name="message_title" id="crud-message-title" required>
                </label>
                <label class="span-2">
                    Message
                    <textarea name="message_body" id="crud-message-body" rows="4" required></textarea>
                </label>
                <label>
                    Attach document
                    <select name="document_id" id="crud-message-document-id">
                        <option value="">None</option>
                        <?php foreach (($dashboardModalDocuments ?? []) as $document): ?>
                            <option value="<?php echo (int) $document['id']; ?>"><?php echo e($document['file_name'] ?? 'Document'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-2">
                    Recipients
                    <select name="recipient_ids[]" id="crud-message-recipient-ids" multiple size="6" required>
                        <?php foreach (($dashboardModalUsers ?? []) as $user): ?>
                            <option value="<?php echo (int) $user['id']; ?>">
                                <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-actions span-2">
                    <button type="submit" id="crud-message-submit">Send message</button>
                    <button type="button" class="admin-action-link admin-action-link-secondary" data-crud-reset-message>Reset</button>
                </div>
            </form>
        </section>

        <section class="crud-panel">
            <h3>Messages list</h3>
            <div class="company-grid">
                <?php if (empty($dashboardModalMessages ?? [])): ?>
                    <div class="crud-empty-state">No messages available.</div>
                <?php endif; ?>
                <?php foreach (($dashboardModalMessages ?? []) as $message): ?>
                    <article class="company-card company-card--stacked">
                        <div class="company-card-head">
                            <div class="company-card-title"><?php echo e($message['title'] ?? 'Message'); ?></div>
                            <div class="company-card-meta"><?php echo e($message['sender_name'] ?? ''); ?> <?php echo !empty($message['recipient_name']) ? '→ ' . e($message['recipient_name']) : ''; ?></div>
                        </div>
                        <div class="company-card-actions company-card-actions--inline">
                            <span class="company-card-chip"><?php echo e($message['type'] ?? 'request'); ?></span>
                            <span class="company-card-chip"><?php echo e($message['status'] ?? 'pending'); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</template>