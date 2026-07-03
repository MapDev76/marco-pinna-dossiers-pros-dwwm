<?php $currentUser = currentUser(); ?>
<div class="legal-page">
    <h1><?php echo e(t('contacts.title', ['fallback' => 'Contacts'])); ?></h1>
    <p><?php echo e(t('contacts.subtitle', ['fallback' => 'For support and information, use the references below.'])); ?></p>

    <h2><?php echo e(t('contacts.support_title', ['fallback' => 'Support'])); ?></h2>
    <p><?php echo e(t('contacts.support_body', ['fallback' => 'For technical support, contact the StaffEase Pro administration.'])); ?></p>

    <h2><?php echo e(t('contacts.general_title', ['fallback' => 'General contacts'])); ?></h2>
    <p><?php echo e(t('contacts.general_body', ['fallback' => 'For business or legal requests, use your organization channels.'])); ?></p>

    <p style="margin-top: 2rem;">
        <a href="<?php echo e(appUrl('home')); ?>">&larr; <?php echo e(t('legal.back_home')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('legal')); ?>"><?php echo e(t('common.legal_mentions')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('creator')); ?>"><?php echo e(t('common.app_creator', ['fallback' => 'App creator'])); ?></a>
    </p>
</div>
