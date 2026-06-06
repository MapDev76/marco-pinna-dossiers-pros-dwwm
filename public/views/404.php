<!-- 404 error page: shown when the requested route does not exist. -->
<div class="content">
    <h1><?php echo e(t('common.page_not_found')); ?></h1>
    <p><?php echo e(t('common.route_not_available')); ?></p>
    <p><a class="admin-action-link" href="<?php echo appUrl('home'); ?>"><?php echo e(t('common.back_to_home')); ?></a></p>
</div>