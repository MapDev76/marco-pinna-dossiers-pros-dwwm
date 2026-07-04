<?php $currentUser = currentUser(); ?>
<div class="home-page commercial-page">
	<section class="commercial-hero">
		<p class="commercial-eyebrow"><?php echo e(t('common.app_name')); ?></p>
		<h1><?php echo e(t('commercial.title', ['fallback' => 'StaffEase Pro en action'])); ?></h1>
		<p class="commercial-lead">
			<?php echo e(t('commercial.lead', ['fallback' => 'Decouvrez comment l application simplifie les plannings, les presences, les documents et les messages.'])); ?>
		</p>
		<div class="commercial-actions">
			<a class="admin-action-link" href="<?php echo e(appUrl('login')); ?>"><?php echo e(t('common.login')); ?></a>
			<a class="admin-action-link-secondary" href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('common.contacts')); ?></a>
		</div>
	</section>

	<section class="commercial-grid">
		<article class="commercial-card">
			<h2><?php echo e(t('commercial.how_it_works_title', ['fallback' => 'Comment ca marche'])); ?></h2>
			<p><?php echo e(t('commercial.how_it_works_body', ['fallback' => 'Une interface unique pour gerer les shifts, les presences, les documents et les echanges internes.'])); ?></p>
		</article>
		<article class="commercial-card">
			<h2><?php echo e(t('commercial.how_to_get_it_title', ['fallback' => 'Comment l obtenir'])); ?></h2>
			<p><?php echo e(t('commercial.how_to_get_it_body', ['fallback' => 'Contactez-nous pour une demonstration, un devis et une configuration adaptee a votre organisation.'])); ?></p>
		</article>
		<article class="commercial-card">
			<h2><?php echo e(t('commercial.demo_videos_title', ['fallback' => 'Videos demonstratives'])); ?></h2>
			<p><?php echo e(t('commercial.demo_videos_body', ['fallback' => 'Des videos courtes pour voir le planning, les presences, les documents et l espace employe.'])); ?></p>
		</article>
	</section>

	<section class="commercial-videos">
		<div class="commercial-video-card">
			<div class="commercial-video-placeholder">1</div>
			<h3><?php echo e(t('commercial.video_1_title', ['fallback' => 'Vue d ensemble du dashboard'])); ?></h3>
			<p><?php echo e(t('commercial.video_1_body', ['fallback' => 'Navigation, calendrier, actions rapides et logique de gestion.'])); ?></p>
		</div>
		<div class="commercial-video-card">
			<div class="commercial-video-placeholder">2</div>
			<h3><?php echo e(t('commercial.video_2_title', ['fallback' => 'Presences et signatures'])); ?></h3>
			<p><?php echo e(t('commercial.video_2_body', ['fallback' => 'Signature tactile, horodatage et validation des presences.'])); ?></p>
		</div>
		<div class="commercial-video-card">
			<div class="commercial-video-placeholder">3</div>
			<h3><?php echo e(t('commercial.video_3_title', ['fallback' => 'Documents et espace employe'])); ?></h3>
			<p><?php echo e(t('commercial.video_3_body', ['fallback' => 'Envoi de documents, inbox, messages et consultation mobile.'])); ?></p>
		</div>
	</section>

	<section class="commercial-cta">
		<h2><?php echo e(t('commercial.cta_title', ['fallback' => 'Vous voulez voir une demo complete ?'])); ?></h2>
		<p><?php echo e(t('commercial.cta_body', ['fallback' => 'Demandez une presentation et obtenez un accompagnement pour l installation.'])); ?></p>
		<a class="admin-action-link" href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('commercial.cta_button', ['fallback' => 'Demander une demonstration'])); ?></a>
	</section>
</div>
