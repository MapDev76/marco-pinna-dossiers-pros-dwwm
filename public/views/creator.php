<?php $currentUser = currentUser(); ?>
<?php
$basePath = $basePath ?? (function () {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
})();
$creatorName = 'Marco Antonio PINNA';
$creatorEmail = 'pinna.marcantonio@icloud.com';
$creatorPhoneDisplay = '+33 744907701';
$creatorPhoneHref = '+33744907701';
?>
<article class="legal-page" aria-labelledby="creator-title">
    <section class="creator-hero">
        <img src="<?php echo e($basePath); ?>/assets/images/MapDev76.jpg" alt="<?php echo e(t('creator.title', ['fallback' => 'App creator'])); ?>" class="creator-photo" loading="lazy" decoding="async">
        <div>
            <p class="commercial-eyebrow"><?php echo e(t('common.app_creator')); ?></p>
            <p class="creator-email-line"><strong><?php echo e($creatorName); ?></strong></p>
        </div>
    </section>

    <h2><?php echo e(t('creator.vision_title', ['fallback' => 'Visione prodotto'])); ?></h2>
    <p><?php echo e(t('creator.vision_body', ['fallback' => 'L obiettivo e costruire una suite moderna e leggera che unisca gestione del personale, operations e comunicazione in un flusso semplice e scalabile per aziende piccole e grandi.'])); ?></p>

    <h2><?php echo e(t('creator.projects_title', ['fallback' => 'Prossimi progetti in corso'])); ?></h2>
    <p><?php echo e(t('creator.projects_body', ['fallback' => 'HotelEase Pro e GuestEase Pro estenderanno la suite con moduli dedicati alla gestione reception, servizi ospiti, coordinamento inter-reparto e controllo operativo delle strutture ricettive.'])); ?></p>

    <section class="commercial-card creator-contact-card">
        <h2><?php echo e(t('common.creator_contact_title', ['fallback' => 'Contact me for support or account creation'])); ?></h2>
        <p><?php echo e(t('common.creator_contact_body', ['fallback' => 'For technical support, a demo or to create a new business account, write directly to the email address below.'])); ?></p>
        <p class="creator-email-line">
            <strong>Creator:</strong>
            <span><?php echo e($creatorName); ?></span>
        </p>
        <p class="creator-email-line">
            <strong><?php echo e(t('common.creator_contact_email_label', ['fallback' => 'Contact email'])); ?>:</strong>
            <a href="mailto:<?php echo e($creatorEmail); ?>"><?php echo e($creatorEmail); ?></a>
        </p>
        <p class="creator-email-line">
            <strong>Phone:</strong>
            <a href="tel:<?php echo e($creatorPhoneHref); ?>"><?php echo e($creatorPhoneDisplay); ?></a>
        </p>
        <p class="creator-email-note"><?php echo e(t('common.creator_contact_email_note', ['fallback' => 'Support, demo, business account creation and commercial information.'])); ?></p>
    </section>

</article>
