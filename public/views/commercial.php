<?php $currentUser = currentUser(); ?>
<?php
$basePath = $basePath ?? (function () {
	$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
	return $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
})();
?>
<div class="home-page commercial-page">
	<section class="commercial-hero">
		<p class="commercial-eyebrow"><?php echo e(t('common.app_name')); ?></p>
		<h1><?php echo e(t('commercial.title', ['fallback' => 'StaffEase Pro en action'])); ?></h1>
		<p class="commercial-lead">
			<?php echo e(t('commercial.lead', ['fallback' => 'Decouvrez comment l application simplifie les plannings, les presences, les documents et les messages.'])); ?>
		</p>
		<div class="commercial-suite-logo" aria-label="HotelEase Pro">
			<img src="<?php echo e($basePath); ?>/assets/images/LogoHotelEasePro.jpg" alt="HotelEase Pro" class="commercial-suite-logo-image">
			<div class="commercial-suite-logo-text">
				<span>HotelEase</span>
				<strong>Pro</strong>
			</div>
		</div>
		<div class="commercial-actions">
			<a class="admin-action-link" href="<?php echo e(appUrl('login')); ?>"><?php echo e(t('common.login')); ?></a>
			<a class="admin-action-link-secondary" href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('common.contacts')); ?></a>
		</div>
	</section>

	<section class="commercial-split">
		<article class="commercial-card commercial-sell-card">
			<h2><?php echo e(t('commercial.how_it_works_title', ['fallback' => 'Comment ca marche'])); ?></h2>
			<p><?php echo e(t('commercial.how_it_works_body', ['fallback' => 'Une interface unique pour gerer les shifts, les presences, les documents et les echanges internes.'])); ?></p>
		</article>
		<article class="commercial-card commercial-sell-card is-highlighted">
			<h2><?php echo e(t('commercial.commercial_suite_title', ['fallback' => 'StaffEase Pro + HotelEase Pro'])); ?></h2>
			<p><?php echo e(t('commercial.commercial_suite_body', ['fallback' => 'Two coordinated products...'])); ?></p>
			<p class="commercial-emphasis"><?php echo e(t('commercial.cta_body', ['fallback' => 'If you want to free your company from large and expensive software, contact me and let us build the solution that fits your organization.'])); ?></p>
		</article>
	</section>

	<section class="commercial-steps">
		<article class="commercial-step-card">
			<span class="commercial-step-number">1</span>
			<h3><?php echo e(t('commercial.step_1_title', ['fallback' => 'Discover the platform'])); ?></h3>
			<p><?php echo e(t('commercial.step_1_body', ['fallback' => 'Watch the first demo and see the main workflow in action.'])); ?></p>
		</article>
		<article class="commercial-step-card is-highlighted">
			<span class="commercial-step-number">2</span>
			<h3><?php echo e(t('commercial.step_2_title', ['fallback' => 'Choose HotelEase Pro'])); ?></h3>
			<p><?php echo e(t('commercial.step_2_body', ['fallback' => 'Aggiungi la suite hospitality quando gestisci hotel, residence o strutture multi-servizio.'])); ?></p>
		</article>
		<article class="commercial-step-card is-highlighted">
			<span class="commercial-step-number">3</span>
			<h3><?php echo e(t('commercial.step_3_title', ['fallback' => 'Request activation'])); ?></h3>
			<p><?php echo e(t('commercial.step_3_body', ['fallback' => 'Scrivi per ricevere assistenza, attivazione account e una proposta commerciale su misura.'])); ?></p>
		</article>
	</section>

	<section class="commercial-grid">
		<article class="commercial-card">
			<h2><?php echo e(t('commercial.how_to_get_it_title', ['fallback' => 'Comment l obtenir'])); ?></h2>
			<p><?php echo e(t('commercial.how_to_get_it_body', ['fallback' => 'Contactez-nous pour une demonstration, un devis et une configuration adaptee a votre organisation.'])); ?></p>
			<p class="commercial-email-callout"><a href="mailto:pinna.marcantonio@icloud.com">pinna.marcantonio@icloud.com</a></p>
		</article>
		<article class="commercial-card">
			<h2><?php echo e(t('commercial.commercial_suite_title', ['fallback' => 'StaffEase Pro + HotelEase Pro'])); ?></h2>
			<p><?php echo e(t('commercial.commercial_suite_body', ['fallback' => 'Two coordinated products: one to manage staff, the other designed to simplify processes for hospitality properties and multi-department organizations.'])); ?></p>
		</article>
		<article class="commercial-card">
			<h2><?php echo e(t('commercial.demo_videos_title', ['fallback' => 'Videos demonstratives'])); ?></h2>
			<p><?php echo e(t('commercial.demo_videos_body', ['fallback' => 'Des videos courtes pour voir le planning, les presences, les documents et l espace employe.'])); ?></p>
		</article>
	</section>

	<section class="commercial-videos">
		<div class="commercial-video-card">
			<a class="commercial-video-placeholder" href="https://www.youtube.com/watch?v=VIDEO_DEMO_1" target="_blank" rel="noopener noreferrer">1</a>
			<h3><?php echo e(t('commercial.video_1_title', ['fallback' => 'Vue d ensemble du dashboard'])); ?></h3>
			<p><?php echo e(t('commercial.video_1_body', ['fallback' => 'Navigation, calendrier, actions rapides et logique de gestion.'])); ?></p>
		</div>
		<div class="commercial-video-card">
			<a class="commercial-video-placeholder" href="https://www.youtube.com/watch?v=VIDEO_DEMO_2" target="_blank" rel="noopener noreferrer">2</a>
			<h3><?php echo e(t('commercial.video_2_title', ['fallback' => 'Presences et signatures'])); ?></h3>
			<p><?php echo e(t('commercial.video_2_body', ['fallback' => 'Signature tactile, horodatage et validation des presences.'])); ?></p>
		</div>
		<div class="commercial-video-card">
			<a class="commercial-video-placeholder" href="https://www.youtube.com/watch?v=VIDEO_DEMO_3" target="_blank" rel="noopener noreferrer">3</a>
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
