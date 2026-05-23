<!-- Page de connexion Super Admin : authentifie uniquement les comptes autorisés à gérer l'administration. -->
<?php $loginError = $loginError ?? null; ?>
<div class="auth-card">
    <h1>Connexion Super Admin</h1>
    <p>Cette interface est réservée aux comptes Super Admin pour gérer les utilisateurs, les entreprises et les départements.</p>

    <?php if ($loginError !== null): ?>
        <div class="flash flash-error"><?php echo e($loginError); ?></div>
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
