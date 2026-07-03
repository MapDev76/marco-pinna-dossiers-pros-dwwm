<?php $currentUser = currentUser(); ?>
<div class="home-page">
	<h1><?php echo e(t('home.welcome_title')); ?></h1>
	<p><?php echo e(t('home.welcome_subtitle')); ?></p>
	
	<section class="home-intro">
		<h2><?php echo e(t('home.intro_title')); ?></h2>

		<p>
			<?php echo e(t('home.lead_1')); ?>
			<?php echo e(t('home.lead_2')); ?>
		</p>
		<p>
			<?php echo e(t('home.lead_3')); ?>
		</p>

		<h3><?php echo e(t('home.audience_title')); ?></h3>
		<ul style="list-style: none; padding: 0;">
			<li style="display: flex; align-items: center; margin-bottom: 0.5rem; padding: 0.5rem; background-color: var(--color-badge-bg); border-radius: var(--border-radius-sm); color: var(--color-badge-text);">
				<img src="/assets/icons/alert-circle.svg" alt="" style="width: 16px; height: 16px; margin-right: 0.5rem;" />
				<?php echo e(t('home.audience_1')); ?>
			</li>
			<li style="display: flex; align-items: center; margin-bottom: 0.5rem; padding: 0.5rem; background-color: var(--color-badge-bg); border-radius: var(--border-radius-sm); color: var(--color-badge-text);">
				<img src="/assets/icons/alert-circle.svg" alt="" style="width: 16px; height: 16px; margin-right: 0.5rem;" />
				<?php echo e(t('home.audience_2')); ?>
			</li>
			<li style="display: flex; align-items: center; margin-bottom: 0.5rem; padding: 0.5rem; background-color: var(--color-badge-bg); border-radius: var(--border-radius-sm); color: var(--color-badge-text);">
				<img src="/assets/icons/alert-circle.svg" alt="" style="width: 16px; height: 16px; margin-right: 0.5rem;" />
				<?php echo e(t('home.audience_3')); ?>
			</li>
			<li style="display: flex; align-items: center; margin-bottom: 0.5rem; padding: 0.5rem; background-color: var(--color-badge-bg); border-radius: var(--border-radius-sm); color: var(--color-badge-text);">
				<img src="/assets/icons/alert-circle.svg" alt="" style="width: 16px; height: 16px; margin-right: 0.5rem;" />
				<?php echo e(t('home.audience_4')); ?>
			</li>
			<li style="display: flex; align-items: center; margin-bottom: 0.5rem; padding: 0.5rem; background-color: var(--color-badge-bg); border-radius: var(--border-radius-sm); color: var(--color-badge-text);">
				<img src="/assets/icons/alert-circle.svg" alt="" style="width: 16px; height: 16px; margin-right: 0.5rem;" />
				<?php echo e(t('home.audience_5')); ?>
			</li>
		</ul>

		<!-- Feature panels -->
		<div class="simple-div-container">
			<div class="simple-div">
				<p class="simple-div-title"><?php echo e(t('home.feature_shifts_title')); ?></p>
				<p class="simple-div-content"><?php echo e(t('home.feature_shifts_body')); ?></p>
			</div>
			<div class="simple-div">
				<p class="simple-div-title"><?php echo e(t('home.feature_attendance_title')); ?></p>
				<p class="simple-div-content"><?php echo e(t('home.feature_attendance_body')); ?></p>
			</div>
			<div class="simple-div">
				<p class="simple-div-title"><?php echo e(t('home.feature_documents_title')); ?></p>
				<p class="simple-div-content"><?php echo e(t('home.feature_documents_body')); ?></p>
			</div>
			<div class="simple-div">
				<p class="simple-div-title"><?php echo e(t('home.feature_reporting_title')); ?></p>
				<p class="simple-div-content"><?php echo e(t('home.feature_reporting_body')); ?></p>
			</div>
			<div class="simple-div">
				<p class="simple-div-title"><?php echo e(t('home.feature_requests_title')); ?></p>
				<p class="simple-div-content"><?php echo e(t('home.feature_requests_body')); ?></p>
			</div>
		</div>

		<p style="margin-top: 1.5rem;">
			<a href="<?php echo e(appUrl('legal')); ?>" style="color: var(--color-accent); text-decoration: underline;"><?php echo e(t('common.legal_mentions')); ?></a>
			&nbsp;|&nbsp;
			<a href="<?php echo e(appUrl('contacts')); ?>" style="color: var(--color-accent); text-decoration: underline;"><?php echo e(t('common.contacts', ['fallback' => 'Contacts'])); ?></a>
			&nbsp;|&nbsp;
			<a href="<?php echo e(appUrl('creator')); ?>" style="color: var(--color-accent); text-decoration: underline;"><?php echo e(t('common.app_creator', ['fallback' => 'App creator'])); ?></a>
		</p>
	</section>
</div>