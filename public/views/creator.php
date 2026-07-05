<?php $currentUser = currentUser(); ?>
<?php
$basePath = $basePath ?? (function () {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
})();
?>
<div class="legal-page">
    <section class="creator-hero">
        <img src="<?php echo e($basePath); ?>/assets/images/MapDev76.jpg" alt="<?php echo e(t('creator.title', ['fallback' => 'App creator'])); ?>" class="creator-photo">
        <div>
            <p class="commercial-eyebrow"><?php echo e(t('common.app_creator')); ?></p>
            <h1><?php echo e(t('creator.title', ['fallback' => 'App creator'])); ?></h1>
            <p><?php echo e(t('creator.subtitle', ['fallback' => 'This page describes the creator of StaffEase Pro.'])); ?></p>
        </div>
    </section>

    <h2><?php echo e(t('creator.section_title', ['fallback' => 'About the creator'])); ?></h2>
    <p><?php echo e(t('creator.section_body', ['fallback' => 'StaffEase Pro was ideated and developed by the creator of this application.'])); ?></p>

    <section class="commercial-card creator-contact-card">
        <h2><?php echo e(t('creator_contact_title', ['fallback' => 'Contact me for support or account creation'])); ?></h2>
        <p><?php echo e(t('creator_contact_body', ['fallback' => 'For technical support, a demo or to create a new business account, write directly to the email address below.'])); ?></p>
        <p class="creator-email-line">
            <strong><?php echo e(t('creator_contact_email_label', ['fallback' => 'Contact email'])); ?>:</strong>
            <a href="mailto:pinna.marcantonio@icloud.com">pinna.marcantonio@icloud.com</a>
        </p>
        <p class="creator-email-note"><?php echo e(t('creator_contact_email_note', ['fallback' => 'Support, demo, business account creation and commercial information.'])); ?></p>
    </section>

    <p class="creator-cta-note">
        <?php echo e(t('commercial.cta_body', ['fallback' => 'If you want to free your company from large and expensive software, contact me and let us build the solution that fits your organization.'])); ?>
    </p>

    <p style="margin-top: 2rem;">
        <a href="<?php echo e(appUrl('home')); ?>">&larr; <?php echo e(t('legal.back_home')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('legal')); ?>"><?php echo e(t('common.legal_mentions')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('common.contacts', ['fallback' => 'Contacts'])); ?></a>
    </p>
</div>
