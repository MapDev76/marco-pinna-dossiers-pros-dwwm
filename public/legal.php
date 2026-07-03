<?php $currentUser = currentUser(); ?>
<div class="legal-page">
    <h1><?php echo e(t('legal.title')); ?></h1>
    <p><strong><?php echo e(t('common.app_name')); ?></strong> © <?php echo date('Y'); ?> - <?php echo e(t('legal.rights_reserved')); ?></p>

    <h2><?php echo e(t('legal.intellectual_property_title')); ?></h2>
    <p><?php echo e(t('legal.intellectual_property_body')); ?></p>

    <h2><?php echo e(t('legal.data_processing_title')); ?></h2>
    <p><?php echo e(t('legal.data_processing_body')); ?></p>

    <h2><?php echo e(t('legal.liability_title')); ?></h2>
    <p><?php echo e(t('legal.liability_body')); ?></p>

    <p style="margin-top: 2rem;">
        <a href="<?php echo e(appUrl('home')); ?>">&larr; <?php echo e(t('legal.back_home')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('common.contacts', ['fallback' => 'Contacts'])); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('creator')); ?>"><?php echo e(t('common.app_creator', ['fallback' => 'App creator'])); ?></a>
    </p>
</div>