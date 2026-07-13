<?php $currentUser = currentUser(); ?>
<article class="legal-page" aria-labelledby="contacts-title">
    <h1 id="contacts-title"><?php echo e(t('contacts.title', ['fallback' => 'Contacts'])); ?></h1>
    <p><?php echo e(t('contacts.subtitle', ['fallback' => 'For support and information, use the references below.'])); ?></p>

    <section class="commercial-card creator-contact-card">
        <h2><?php echo e(t('contacts.direct_title', ['fallback' => 'Contatto diretto'])); ?></h2>
        <p><?php echo e(t('contacts.direct_body', ['fallback' => 'Per assistenza tecnica, demo commerciale e attivazione account, usa i contatti ufficiali qui sotto.'])); ?></p>
        <p class="creator-email-line"><strong>Marco Antonio PINNA</strong></p>
        <p class="creator-email-line"><strong>Email:</strong> <a href="mailto:pinna.marcantonio@icloud.com">pinna.marcantonio@icloud.com</a></p>
        <p class="creator-email-line"><strong>Phone:</strong> <a href="tel:+33744907701">+33 744907701</a></p>
    </section>

    <section aria-label="<?php echo e(t('contacts.sales_title', ['fallback' => 'Commerciale e partnership'])); ?>">
        <h2><?php echo e(t('contacts.sales_title', ['fallback' => 'Commerciale e partnership'])); ?></h2>
        <p><?php echo e(t('contacts.sales_body', ['fallback' => 'Per aziende ricettive, gruppi hospitality e attivazioni multi-sede, e disponibile una presentazione dedicata con roadmap StaffEase Pro, HotelEase Pro e GuestEase Pro.'])); ?></p>
    </section>

</article>
