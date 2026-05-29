# StaffEase Pro

Application PHP/MySQL simple pour gérer les shifts, la présence, les demandes et les documents.

## Objectif
Le but du projet est de rester lisible, simple et facile à présenter à l’examen. L’architecture repose sur un front controller, des controllers PHP, des models PDO et des vues rendues côté serveur.

## Progression du jour
- Unification des modales CRUD dans un shell commun.
- Ajout du flux `users`, `companies`, `departments`, `documents` et `messages` dans la même modale.
- Correction de l’affichage des compteurs entreprise.
- Gestion du chef de département avec `head_user_id` et rôle `department_manager`.
- Remplacement de l’auto-correction SQL par une mini-migration explicite.

## Mini-migration SQL
Fichier: [db/migrations/20260528_add_departments_head_user_id.sql](db/migrations/20260528_add_departments_head_user_id.sql)

```sql
-- Migration minimale pour les installations anciennes.
-- Pourquoi: certaines bases de données historiques n'avaient pas encore la colonne `head_user_id`.
-- Cette migration rend le schéma cohérent avec `db/schema.sql` sans logique de correction au runtime.

SET @column_exists := (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'departments'
	  AND COLUMN_NAME = 'head_user_id'
);

SET @sql := IF(
	@column_exists = 0,
	'ALTER TABLE departments ADD COLUMN head_user_id INT NULL AFTER company_id',
	'SELECT 1'
);
```

Cette migration explique à l’examen pourquoi le schéma est stabilisé au niveau base de données et non par du code caché dans le model.

## Modale CRUD commune
Fichier: [app/layout/crud-modal.php](app/layout/crud-modal.php)

```php
<form method="post" action="<?php echo appUrl('departments'); ?>">
	<input type="hidden" name="action" value="create">
	<input type="text" name="name" placeholder="Department name">
</form>
```

La même logique sert pour plusieurs entités: on garde une seule interface, plus facile à maintenir et plus propre à montrer en soutenance.

## Chef de département
Fichier: [backend/controllers/DepartmentsController.php](backend/controllers/DepartmentsController.php)

```php
if (!empty($payload['head_user_id'])) {
	$currentHead = $userModel->findById((int) $payload['head_user_id']);
	if ($currentHead) {
		$userModel->update((int) $payload['head_user_id'], [
			'department_id' => $departmentId,
			'first_name' => $currentHead['first_name'],
			'last_name' => $currentHead['last_name'],
			'email' => $currentHead['email'],
			'phone' => $currentHead['phone'] ?? null,
			'role' => 'department_manager',
			'status' => $currentHead['status'] ?? 'active',
		]);
	}
}
```

Ce bloc montre la règle métier: le chef de département doit être relié au service et recevoir le bon rôle applicatif.

## Model départements
Fichier: [backend/models/DepartmentModel.php](backend/models/DepartmentModel.php)

```php
public function create(array $data): int
{
	$columns = ['company_id', 'name', 'description'];
	if ($this->hasDepartmentColumn('head_user_id')) {
		$columns[] = 'head_user_id';
	}
```

Le model reste compatible avec le schéma actuel et n’ajoute plus de modification automatique à chaque exécution.

## Route JSON
Fichier: [backend/controllers/ApiDashboardController.php](backend/controllers/ApiDashboardController.php)

```php
jsonResponse([
	'ok' => true,
	'data' => $dashboardData,
]);
```

Cette partie sert à expliquer l’usage AJAX/JSON sans casser le rendu HTML principal.

## Structure
- `index.php`: point d’entrée unique.
- `backend/`: bootstrap, helpers, controllers, models et endpoints JSON.
- `app/layout/`: composants d’interface partagés.
- `public/views/`: pages par rôle.
- `public/assets/`: CSS, icônes et images.
- `config/`: paramètres de connexion.
- `db/`: schéma SQL et migrations.

## Déploiement
- Le projet fonctionne sur un hébergement PHP/MySQL standard.
- Les chemins restent relatifs pour marcher à la racine ou dans un sous-dossier.

## Notes
- Les fichiers de test temporaires ont été retirés.
- Le code reste volontairement léger, sans logique cachée dans les vues.
