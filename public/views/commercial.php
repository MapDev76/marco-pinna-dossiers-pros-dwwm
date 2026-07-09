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
			<?php echo e(t('commercial.lead', ['fallback' => 'Scopri una piattaforma progettata per semplificare pianificazione turni, presenze e gestione operativa.'])); ?>
		</p>
		<div class="commercial-actions">
			<a class="admin-action-link" href="<?php echo e(appUrl('login')); ?>"><?php echo e(t('common.login')); ?></a>
			<a class="admin-action-link-secondary" href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('common.contacts')); ?></a>
		</div>
	</section>

	<section class="commercial-project-note">
		<h2><?php echo e(t('commercial.projects_title', ['fallback' => 'Progetti in corso'])); ?></h2>
		<p><?php echo e(t('commercial.projects_body', ['fallback' => 'HotelEase Pro e GuestEase Pro estenderanno la base StaffEase Pro con strumenti verticali per strutture ricettive e team operativi.'])); ?></p>
		<p class="commercial-emphasis"><?php echo e(t('commercial.projects_sell', ['fallback' => 'Una suite semplice, connessa e scalabile per qualsiasi azienda del turismo e dell ospitalita.'])); ?></p>
	</section>

	<section class="commercial-feature-grid">
		<article class="commercial-card commercial-feature-card">
			<span class="commercial-step-number">1</span>
			<h2><?php echo e(t('commercial.feature_1_title', ['fallback' => 'Automatizzazione dei turni di lavoro'])); ?></h2>
			<p class="commercial-video-title"><?php echo e(t('commercial.feature_1_video_title', ['fallback' => 'StaffEase Pro: Come automatizzare i turni di lavoro in 3 passaggi'])); ?></p>
			<p><?php echo e(t('commercial.feature_1_intro')); ?></p>
			<h3><?php echo e(t('commercial.how_it_works_title', ['fallback' => 'Come funziona'])); ?></h3>
			<ul>
				<li><?php echo e(t('commercial.feature_1_step_1')); ?></li>
				<li><?php echo e(t('commercial.feature_1_step_2')); ?></li>
				<li><?php echo e(t('commercial.feature_1_step_3')); ?></li>
				<li><?php echo e(t('commercial.feature_1_step_4')); ?></li>
			</ul>
			<h3><?php echo e(t('commercial.benefits_title', ['fallback' => 'Vantaggi'])); ?></h3>
			<ul>
				<li><?php echo e(t('commercial.feature_1_benefit_1')); ?></li>
				<li><?php echo e(t('commercial.feature_1_benefit_2')); ?></li>
				<li><?php echo e(t('commercial.feature_1_benefit_3')); ?></li>
			</ul>
		</article>
		<article class="commercial-card commercial-feature-card">
			<span class="commercial-step-number">2</span>
			<h2><?php echo e(t('commercial.feature_2_title', ['fallback' => 'Sicurezza delle firme digitali (Wi-Fi/IP aziendale)'])); ?></h2>
			<p class="commercial-video-title"><?php echo e(t('commercial.feature_2_video_title', ['fallback' => 'Firme digitali sicure con StaffEase Pro: Autenticazione e tracciabilita'])); ?></p>
			<p><?php echo e(t('commercial.feature_2_intro')); ?></p>
			<h3><?php echo e(t('commercial.how_it_works_title', ['fallback' => 'Come funziona'])); ?></h3>
			<ul>
				<li><?php echo e(t('commercial.feature_2_step_1')); ?></li>
				<li><?php echo e(t('commercial.feature_2_step_2')); ?></li>
				<li><?php echo e(t('commercial.feature_2_step_3')); ?></li>
			</ul>
			<h3><?php echo e(t('commercial.benefits_title', ['fallback' => 'Vantaggi'])); ?></h3>
			<ul>
				<li><?php echo e(t('commercial.feature_2_benefit_1')); ?></li>
				<li><?php echo e(t('commercial.feature_2_benefit_2')); ?></li>
				<li><?php echo e(t('commercial.feature_2_benefit_3')); ?></li>
			</ul>
		</article>
		<article class="commercial-card commercial-feature-card">
			<span class="commercial-step-number">3</span>
			<h2><?php echo e(t('commercial.feature_3_title', ['fallback' => 'Centralizzazione della gestione dei dipendenti'])); ?></h2>
			<p class="commercial-video-title"><?php echo e(t('commercial.feature_3_video_title', ['fallback' => 'StaffEase Pro: Tutto il team in un unica piattaforma'])); ?></p>
			<p><?php echo e(t('commercial.feature_3_intro')); ?></p>
			<h3><?php echo e(t('commercial.how_it_works_title', ['fallback' => 'Come funziona'])); ?></h3>
			<ul>
				<li><?php echo e(t('commercial.feature_3_step_1')); ?></li>
				<li><?php echo e(t('commercial.feature_3_step_2')); ?></li>
				<li><?php echo e(t('commercial.feature_3_step_3')); ?></li>
				<li><?php echo e(t('commercial.feature_3_step_4')); ?></li>
			</ul>
			<h3><?php echo e(t('commercial.benefits_title', ['fallback' => 'Vantaggi'])); ?></h3>
			<ul>
				<li><?php echo e(t('commercial.feature_3_benefit_1')); ?></li>
				<li><?php echo e(t('commercial.feature_3_benefit_2')); ?></li>
				<li><?php echo e(t('commercial.feature_3_benefit_3')); ?></li>
			</ul>
		</article>
	</section>

	<section class="commercial-videos">
		<div class="commercial-video-card">
			<div class="commercial-video-placeholder" aria-label="<?php echo e(t('commercial.video_slot_1', ['fallback' => 'Spazio video 1'])); ?>">1</div>
			<h3><?php echo e(t('commercial.video_1_title', ['fallback' => 'Vue d ensemble du dashboard'])); ?></h3>
			<p><?php echo e(t('commercial.video_1_body', ['fallback' => 'Spazio riservato al primo video YouTube. Il link verra inserito successivamente.'])); ?></p>
		</div>
		<div class="commercial-video-card">
			<div class="commercial-video-placeholder" aria-label="<?php echo e(t('commercial.video_slot_2', ['fallback' => 'Spazio video 2'])); ?>">2</div>
			<h3><?php echo e(t('commercial.video_2_title', ['fallback' => 'Presences et signatures'])); ?></h3>
			<p><?php echo e(t('commercial.video_2_body', ['fallback' => 'Spazio riservato al secondo video YouTube. Il link verra inserito successivamente.'])); ?></p>
		</div>
		<div class="commercial-video-card">
			<div class="commercial-video-placeholder" aria-label="<?php echo e(t('commercial.video_slot_3', ['fallback' => 'Spazio video 3'])); ?>">3</div>
			<h3><?php echo e(t('commercial.video_3_title', ['fallback' => 'Documents et espace employe'])); ?></h3>
			<p><?php echo e(t('commercial.video_3_body', ['fallback' => 'Spazio riservato al terzo video YouTube. Il link verra inserito successivamente.'])); ?></p>
		</div>
	</section>

	<section class="commercial-media-placeholder">
		<h2><?php echo e(t('commercial.media_title', ['fallback' => 'Spazio immagini e demo future'])); ?></h2>
		<p><?php echo e(t('commercial.media_body', ['fallback' => 'Questa sezione resta disponibile per aggiungere nuove immagini, mockup o video di prodotto.'])); ?></p>
		<div class="commercial-image-slot">Image / Video Slot</div>
	</section>

	<section class="commercial-cta">
		<h2><?php echo e(t('commercial.cta_title', ['fallback' => 'Vous voulez voir une demo complete ?'])); ?></h2>
		<p><?php echo e(t('commercial.cta_body', ['fallback' => 'Demandez une presentation et obtenez un accompagnement pour l installation.'])); ?></p>
		<a class="admin-action-link" href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('commercial.cta_button', ['fallback' => 'Demander une demonstration'])); ?></a>
	</section>
</div>
