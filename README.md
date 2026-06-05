# StaffEase Pro

StaffEase Pro est une application web PHP/MySQL pour la gestion des shifts, des presences, des demandes internes, des documents et de la planification operationnelle.

## Vue D Ensemble

L application suit une architecture server-rendered simple et lisible, avec un front controller unique, un routage centralise, des controllers dedies et des vues partagees. Le systeme est concu pour fonctionner correctement sur desktop et mobile, avec un dashboard modulaire et un espace employee separe.

Objectifs fonctionnels principaux:

- gerer les company, departements, utilisateurs, shifts et documents;
- assigner les shifts avec des regles de disponibilite et de couverture;
- enregistrer les presences et les signatures numeriques;
- gerer les demandes internes et les notifications;
- maintenir une interface, des messages et des modales coherents;
- supporter plusieurs langues avec fallback centralise.

## Stack Technologique

- Backend: PHP 8+ avec rendu server-side
- Base de donnees: MySQL avec acces via PDO
- Frontend: Vanilla JavaScript, HTML et CSS modulaire
- API: endpoints JSON pour dashboard et CRUD
- Environnement local: MAMP ou `php -S localhost:8000`

## Architecture

### Flux Principal

1. `index.php` recoit la route courante.
2. `app/router.php` redirige la requete vers le controller ou la vue correcte.
3. Le controller prepare les donnees et configure le layout.
4. Les vues dans `public/views/` et les blocs partages dans `app/layout/` composent la page.

### Couches Applicatives

- `backend/bootstrap.php`: bootstrap de l application
- `backend/helpers.php`: helpers partages, traduction, utilitaires de session et URL
- `backend/controllers/`: logique de page, actions CRUD et endpoints JSON
- `backend/models/`: acces aux donnees via PDO
- `app/layout/`: header, sidebar, modales, panneaux partages
- `public/views/`: home, login, dashboard, espace employee et pages speciales
- `config/`: configuration applicative, base de donnees, langues
- `db/`: schema et migrations
- `assets/`: CSS, JS, icones et images

### Entry Point Et Routing

- `index.php`: front controller unique.
- `app/router.php`: mappe `route` vers controllers, vues et layout.
- `route=api-*`: requetes JSON pour dashboard et modules operationnels.

## Structure Du Projet

- `app/layout/`: composants UI partages
- `assets/css/`: style general et responsive
- `assets/js/`: logique interactive du dashboard, calendrier et espace employee
- `backend/controllers/`: controllers pour auth, dashboard, company, departments, users, shifts et API
- `backend/models/`: requetes et operations PDO
- `config/lang/`: dictionnaires de traduction
- `db/schema.sql`: schema complet de la base de donnees
- `db/migrations/`: evolutions incrementales de la base de donnees
- `public/views/`: pages rendues par route et role

## Roles Et Permissions

Roles supportes:

- `super_admin`
- `admin`
- `department_manager`
- `employee`

Scope des donnees:

- `super_admin`: vision multi-company et selection de la company active.
- `admin`: gestion de sa propre company.
- `department_manager`: gestion de son propre departement.
- `employee`: acces a son espace personnel avec shifts et presence.

## Fonctionnalites Principales

### Dashboard Unifie

- header contextuel avec actions rapides;
- sidebar avec sections operationnelles;
- modale CRUD commune pour company, utilisateurs, departements, documents et messages;
- panneau settings pour une gestion approfondie;
- feedback uniforme pour confirmations, erreurs et alertes.

### Company, Departements Et Utilisateurs

- annuaire company avec compteurs agreges;
- departements lies a la company et responsable de departement;
- utilisateurs avec role, statut, company et departement;
- contrainte IP de la company configurable pour la signature de presence.

### Shifts Et Planning

- catalogue de shifts avec icone, couleur, horaires et description;
- planner calendrier avec vues temporelles;
- affectations journalieres et details par departement;
- support de l auto-assign avec limites minimales et maximales par shift/jour.

### Disponibilite Et Regles

- jours hebdomadaires non disponibles par utilisateur;
- dates speciales avec motif;
- contraintes respectees dans auto-assign, drag and drop et settings;
- statuts d absence: leave, vacation, sick et rest.

### Espace Employee

- visualisation des shifts personnels;
- signature de presence sur mobile;
- signature numerique avec canvas touchscreen;
- historisation dans `digital_signatures` et liaison a `attendances`.

### Documents Et Messages

- bibliotheque de documents avec liste des fichiers disponibles;
- envoi de documents aux employee ou a un departement entier;
- messages de demande ou de notification;
- selection de destinataires multiples;
- telechargement et suppression de documents avec confirmation.

### Langues Et Localisation

- langue par defaut: francais;
- anglais comme fallback;
- helper `t()` pour recuperer les traductions;
- traduction de header, dashboard, sidebar, modales, home, employee space et print modal.

## Modules UI Principaux

### `app/layout/header.php`

- barre superieure contextuelle;
- quick actions pour home, login, dashboard, documents, print et settings;
- adaptation selon les routes publiques et internes.

### `app/layout/sidebar.php`

- navigation dashboard;
- panneau planner et sections pour company, departements et utilisateurs;
- visibilite conditionnee selon le role.

### `app/layout/crud-modal.php`

- shell commun du CRUD;
- templates pour company, users, departments, documents et messages;
- actions de creation, modification, suppression et envoi.

### `app/layout/settings-panel.php`

- gestion de la company active;
- onglets pour users, departments, shifts, assignments et attendances;
- affectations automatiques;
- detail employee avec regles de disponibilite.

### `app/layout/schedule.php`

- modale demo pour le planning des shifts;
- catalogue visuel des shifts, roles, departements, couverture et absences.

### `app/layout/print-modal.php`

- export et impression du planning;
- selection de la vue et de la plage temporelle;
- layout compatible impression.

## API JSON Principales

Dispatcher principal:

- `backend/controllers/ApiDispatcher.php`

Routes principales:

- `route=api-dashboard`
- `route=api-companies`
- `route=api-departments`
- `route=api-users`
- `route=api-shifts`

Actions typiques:

- `auto_assign_open`
- `clear_assignments_scope`
- `set_signature_ip`
- operations CRUD pour company, departements, utilisateurs, shifts et documents

## Base De Donnees Et Migrations

Fichiers cles:

- [db/schema.sql](db/schema.sql)
- [db/migrations/](db/migrations)

Tables principales:

- `companies`
- `departments`
- `users`
- `shifts`
- `user_shifts`
- `attendances`
- `digital_signatures`
- `requests`
- `documents`

## Configuration

### Base De Donnees

- configurer la connexion dans `config/database.php`;
- importer `db/schema.sql`;
- appliquer les migrations presentes dans `db/migrations/` si necessaire.

### Langues

- dictionnaires dans `config/lang/fr.php` et `config/lang/en.php`;
- les textes communs passent par le helper `t()`;
- le fallback evite les chaines manquantes dans l interface.

## Flux Operationnels

### Super Admin

1. Selectionne la company active.
2. Configure departements, utilisateurs, shifts et policy IP.
3. Verifie le planning et la couverture globale.

### Admin

1. Gere sa propre company.
2. Maintient les donnees et les shifts.
3. Lance l auto-assign et corrige les exceptions.

### Department Manager

1. Travaille sur son propre departement.
2. Controle les affectations et la couverture.
3. Surveille les demandes et disponibilites.

### Employee

1. Entre dans son espace personnel.
2. Consulte uniquement ses propres shifts.
3. Signe la presence quand prevu.
4. Envoie des demandes operationnelles.

## Demarrage Rapide

1. Configure la base de donnees.
2. Importe le schema.
3. Demarre le serveur local avec `php -S localhost:8000`.
4. Ouvre `http://localhost:8000/?route=login`.

## Notes De Maintenance

- maintenir UI et textes coherents avec le systeme de traduction;
- eviter les duplications de templates ou de modales;
- conserver le front controller et le routing centralise comme point d entree unique;
- mettre a jour README, layout et dictionnaires lors de l ajout de nouvelles fonctionnalites.
