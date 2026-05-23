<!-- Page d'accueil : présente les fonctionnalités principales du projet. -->
<?php $currentUser = currentUser(); ?>
<div>
	<h1>Welcome to StaffEase Pro</h1>
	<p>This home page contains the main information about the project, dashboards, and quick links.</p>

	<p>
		<?php if ($currentUser !== null): ?>
			<a class="admin-action-link" href="<?php echo appUrl('dashboard'); ?>">Accéder au dashboard</a>
		<?php else: ?>
			<a class="admin-action-link" href="<?php echo appUrl('login'); ?>">Connexion Super Admin</a>
		<?php endif; ?>
	</p>

	<section>
		<h2>Fonctionnalités principales</h2>
		<ul>
			<li>Gestion des quarts</li>
			<li>Suivi des présences</li>
			<li>Demandes et remplacements</li>
		</ul>
	</section>
</div>
