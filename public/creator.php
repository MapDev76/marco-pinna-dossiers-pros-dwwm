<?php $currentUser = currentUser(); ?>
<div class="legal-page">
    <h1><?php echo e(t('creator.title', ['fallback' => 'App creator'])); ?></h1>
    <p><?php echo e(t('creator.subtitle', ['fallback' => 'This page describes the creator of StaffEase Pro.'])); ?></p>

    <h2><?php echo e(t('creator.section_title', ['fallback' => 'About the creator'])); ?></h2>
    <p><?php echo e(t('creator.section_body', ['fallback' => 'StaffEase Pro was ideated and developed by the creator of this application.'])); ?></p>

    <p style="margin-top: 2rem;">
        <a href="<?php echo e(appUrl('home')); ?>">&larr; <?php echo e(t('legal.back_home')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('legal')); ?>"><?php echo e(t('common.legal_mentions')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('common.contacts', ['fallback' => 'Contacts'])); ?></a>
    </p>
</div>
