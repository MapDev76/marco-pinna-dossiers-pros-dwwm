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
		<ul>
			<li>✅ <?php echo e(t('home.audience_1')); ?></li>
			<li>✅ <?php echo e(t('home.audience_2')); ?></li>
			<li>✅ <?php echo e(t('home.audience_3')); ?></li>
			<li>✅ <?php echo e(t('home.audience_4')); ?></li>
			<li>✅ <?php echo e(t('home.audience_5')); ?></li>
		</ul>


		<!-- Feature panels: read-only summaries for each area -->
		<section class="editable-features">
			<div class="editable-grid">
				<div class="feature-box">
					<label for="f_reporting"><strong><?php echo e(t('home.feature_reporting_title')); ?></strong></label>
					<div id="f_reporting" class="feature-input" aria-readonly="true" role="textbox"><?php echo e(t('home.feature_reporting_body')); ?></div>
				</div>

				<div class="feature-box">
					<label for="f_attendance"><strong><?php echo e(t('home.feature_attendance_title')); ?></strong></label>
					<div id="f_attendance" class="feature-input" aria-readonly="true" role="textbox"><?php echo e(t('home.feature_attendance_body')); ?></div>
				</div>

				<div class="feature-box">
					<label for="f_documents"><strong><?php echo e(t('home.feature_documents_title')); ?></strong></label>
					<div id="f_documents" class="feature-input" aria-readonly="true" role="textbox"><?php echo e(t('home.feature_documents_body')); ?></div>
				</div>

				<div class="feature-box">
					<label for="f_roles"><strong><?php echo e(t('home.feature_roles_title')); ?></strong></label>
					<div id="f_roles" class="feature-input" aria-readonly="true" role="textbox"><?php echo e(t('home.feature_roles_body')); ?></div>
				</div>

				<div class="feature-box">
					<label for="f_security"><strong><?php echo e(t('home.feature_security_title')); ?></strong></label>
					<div id="f_security" class="feature-input" aria-readonly="true" role="textbox"><?php echo e(t('home.feature_security_body')); ?></div>
				</div>

				<div class="feature-box">
					<label for="f_shifts"><strong><?php echo e(t('home.feature_shifts_title')); ?></strong></label>
					<div id="f_shifts" class="feature-input" aria-readonly="true" role="textbox"><?php echo e(t('home.feature_shifts_body')); ?></div>
				</div>
			</div>
		</section>

	</section>

	
</div>
