<!-- Page de connexion commune : authentifie tous les rôles actifs. -->
<?php $loginError = $loginError ?? null; ?>
<div class="auth-card">
    <h1>Connexion</h1>
    <p>User registration is exclusively managed by your department manager or administration.    Please contact them to request an account.</p>

    <?php if ($loginError !== null): ?>
        <div id="flash-backdrop-login" class="flash-backdrop"></div>
        <div id="flash-login" class="flash flash-error" role="alert" aria-live="assertive" data-backdrop="flash-backdrop-login">
            <button class="flash-close" aria-label="Chiudi messaggio">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true"><path d="M18.3 5.71L12 12l6.3 6.29-1.41 1.42L10.59 13.41 4.29 19.71 2.88 18.29 9.18 12 2.88 5.71 4.29 4.29 10.59 10.59 17.89 4.29z"/></svg>
            </button>
            <span class="flash-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm3.54 13.54L13.41 12l2.13-3.54L15.13 7 12 10.13 8.87 7 7.46 8.46 9.59 12 7.46 15.54 8.87 17 12 13.87 15.13 17z" />
                </svg>
            </span>
            <div class="flash-body">
                <div class="flash-title">Oops!</div>
                <p><?php echo e($loginError); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <form action="<?php echo appUrl('login'); ?>" method="post" class="login-form">
        <div>
            <input type="email" id="email" placeholder="Email" name="email" value="<?php echo e($loginEmail ?? ''); ?>" required>
        </div>
        <div>
            <input type="password" id="password" placeholder="Mot de passe" name="password" required>
        </div>
        <div>
            <button type="submit">Se connecter</button>
        </div>
    </form>
</div>
