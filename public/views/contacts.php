<?php $currentUser = currentUser(); ?>
<div class="legal-page">
    <h1><?php echo e(t('contacts.title', ['fallback' => 'Contacts'])); ?></h1>
    <p><?php echo e(t('contacts.subtitle', ['fallback' => 'For support and information, use the references below.'])); ?></p>

    <section class="commercial-card creator-contact-card">
        <h2><?php echo e(t('contacts.direct_title', ['fallback' => 'Contatto diretto'])); ?></h2>
        <p><?php echo e(t('contacts.direct_body', ['fallback' => 'Per assistenza tecnica, demo commerciale e attivazione account, usa i contatti ufficiali qui sotto.'])); ?></p>
        <p class="creator-email-line"><strong>Marco Antonio PINNA</strong></p>
        <p class="creator-email-line"><strong>Email:</strong> <a href="mailto:pinna.marcantonio@icloud.com">pinna.marcantonio@icloud.com</a></p>
        <p class="creator-email-line"><strong>Phone:</strong> <a href="tel:+33744907701">+33 744907701</a></p>
    </section>

    <h2><?php echo e(t('contacts.support_title', ['fallback' => 'Support'])); ?></h2>
    <p><?php echo e(t('contacts.support_body', ['fallback' => 'For technical support, contact the StaffEase Pro administration.'])); ?></p>

    <h2><?php echo e(t('contacts.sales_title', ['fallback' => 'Commerciale e partnership'])); ?></h2>
    <p><?php echo e(t('contacts.sales_body', ['fallback' => 'Per aziende ricettive, gruppi hospitality e attivazioni multi-sede, e disponibile una presentazione dedicata con roadmap StaffEase Pro, HotelEase Pro e GuestEase Pro.'])); ?></p>

    <h2><?php echo e(t('contacts.general_title', ['fallback' => 'General contacts'])); ?></h2>
    <p><?php echo e(t('contacts.general_body', ['fallback' => 'For business or legal requests, use your organization channels.'])); ?></p>

    <section class="commercial-media-placeholder">
        <h2><?php echo e(t('contacts.media_title', ['fallback' => 'Spazio per brochure e video'])); ?></h2>
        <p><?php echo e(t('contacts.media_body', ['fallback' => 'Area pronta per allegare immagini, brochure PDF e video dimostrativi in piu lingue.'])); ?></p>
        <div class="commercial-image-slot">Image / Document Slot</div>
    </section>

    <p style="margin-top: 2rem;">
        <a href="<?php echo e(appUrl('home')); ?>">&larr; <?php echo e(t('legal.back_home')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('legal')); ?>"><?php echo e(t('common.legal_mentions')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('creator')); ?>"><?php echo e(t('common.app_creator', ['fallback' => 'App creator'])); ?></a>
    </p>
</div>
