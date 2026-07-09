<?php $currentUser = currentUser(); ?>
<div class="legal-page">
    <h1><?php echo e(t('legal.title')); ?></h1>
    <p><strong><?php echo e(t('common.app_name')); ?></strong> © <?php echo date('Y'); ?> - <?php echo e(t('legal.rights_reserved')); ?></p>

    <h2><?php echo e(t('legal.publisher_title', ['fallback' => 'Editore e responsabile servizio'])); ?></h2>
    <p><?php echo e(t('legal.publisher_body', ['fallback' => 'StaffEase Pro e sviluppato e pubblicato da Marco Antonio PINNA. Per richieste legali o amministrative e disponibile il canale email ufficiale.'])); ?></p>

    <h2><?php echo e(t('legal.intellectual_property_title')); ?></h2>
    <p><?php echo e(t('legal.intellectual_property_body')); ?></p>

    <h2><?php echo e(t('legal.data_processing_title')); ?></h2>
    <p><?php echo e(t('legal.data_processing_body')); ?></p>

    <h2><?php echo e(t('legal.security_title', ['fallback' => 'Sicurezza e tracciabilita'])); ?></h2>
    <p><?php echo e(t('legal.security_body', ['fallback' => 'Le firme digitali e i log operativi sono tracciati con riferimenti temporali e controlli di rete aziendale, nel rispetto delle policy interne del cliente.'])); ?></p>

    <h2><?php echo e(t('legal.terms_title', ['fallback' => 'Termini di utilizzo'])); ?></h2>
    <p><?php echo e(t('legal.terms_body', ['fallback' => 'L accesso e consentito solo a utenti autorizzati. Ogni organizzazione cliente e responsabile della configurazione ruoli, permessi e conformita alle normative locali.'])); ?></p>

    <h2><?php echo e(t('legal.liability_title')); ?></h2>
    <p><?php echo e(t('legal.liability_body')); ?></p>

    <section class="commercial-media-placeholder">
        <h2><?php echo e(t('legal.media_title', ['fallback' => 'Spazio per policy e allegati'])); ?></h2>
        <p><?php echo e(t('legal.media_body', ['fallback' => 'Questa area puo ospitare PDF di policy aziendali, informative privacy multilingua e documentazione compliance.'])); ?></p>
        <div class="commercial-image-slot">Policy / PDF Slot</div>
    </section>

    <p style="margin-top: 2rem;">
        <a href="<?php echo e(appUrl('home')); ?>">&larr; <?php echo e(t('legal.back_home')); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('contacts')); ?>"><?php echo e(t('common.contacts', ['fallback' => 'Contacts'])); ?></a>
        &nbsp;|&nbsp;
        <a href="<?php echo e(appUrl('creator')); ?>"><?php echo e(t('common.app_creator', ['fallback' => 'App creator'])); ?></a>
    </p>
</div>