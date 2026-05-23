# **StaffEase Pro - Guide de Développement pour l'Examen**
*Gestion des quarts de travail, des présences et des demandes pour les entreprises (hôtels, hôpitaux, etc.)*

---

## 📌 **Description du Projet**
**StaffEase Pro** est une application web simple et complète développée en **PHP natif**, **MySQL**, **HTML5**, **CSS3** et **JavaScript pur**. L'objectif est de fournir une solution fonctionnelle pour gérer :
- les **quarts de travail** (création, modification, assignation),
- les **présences** et signatures numériques locales,
- les **demandes** des employés (remplacement, congés, permissions),
- les **documents** (contrats, certificats, justificatifs).

Ce README est conçu pour guider Copilot (ou tout contributeur) pas à pas pendant le développement et pour préparer une démo/examen claire et reproductible.

---

## 🧩 **Structure du projet**
Le projet doit rester simple et organisé :

- **Backend (PHP + MySQL)** : dossier `backend/`. Contient la logique métier, l'accès à la base de données, les contrôleurs et les modèles MVC.
- **Frontend** : dossier `public/` contenant les assets publics et les pages visibles par l'utilisateur.
- **Config/DB** : `config/` pour les fichiers de configuration et `db/` pour le schéma de base de données.
- **Shared layout** : dossier `app/layout/` pour les partials communs comme l'en-tête.

Exemple de structure recommandée :

- `README.md` (ce fichier)
- `index.php` (routeur frontal au niveau racine)
- `backend/`
  - `db.php` (connexion PDO)
  - `bootstrap.php` (initialisation de session et helpers)
  - `helpers.php` (fonctions communes: flash, routes, auth)
  - `controllers/` (contrôleurs MVC)
  - `models/` (modèles MVC)
  - `api/` (endpoints JSON)
- `app/layout/` (partials partagés comme `header.php`)
- `public/`
  - `assets/css/` (styles)
  - `assets/js/` (scripts)
  - `views/` (pages publiques et pages d'administration)
- `db/` (script de création de la BDD)
- `config/` (paramètres, `.env.example`)

---

## 🤖 **Instructions pour Copilot**
Copilot doit suivre un flux itératif et explicite :

1. Lire `README.md` et la structure actuelle du répertoire.
2. Proposer un plan d'implémentation clair en étapes (MVC, DB, pages principales).
3. Générer les fichiers de base (bootstrap, connexion DB, routeur, modèles, contrôleurs).
4. Implémenter un cas d'utilisation complet (ex : connexion Super Admin, création et affichage d'un utilisateur).
5. Ajouter validation, sécurité basique (préparer les requêtes PDO), et messages d'erreur utiles.
6. Tester localement avec le serveur PHP intégré et documenter comment lancer l'application.

Consignes spécifiques :
- Utiliser PDO pour les requêtes SQL et préparer les statements.
- Séparer clairement logique et présentation (controllers vs views).
- Ne pas introduire de frameworks externes.

## 🔐 **Flux Super Admin**

- Le compte `super_admin` se connecte sur la page `?route=login`.
- La session stocke l'utilisateur authentifié dans `$_SESSION['auth_user']`.
- Les routes `dashboard`, `users`, `companies` et `departments` sont protégées par `requireSuperAdmin()`.
- Les pages d'administration permettent de créer, modifier et supprimer les utilisateurs, les companies et les départements.

---

## 🗂️ **Fichiers à créer (ordre logique)**
Créer les fichiers dans cet ordre pour un développement progressif et testable :

1. `sql/schema.sql` — script de création des tables essentielles (`users`, `shifts`, `attendance`, `requests`, `departments`).
2. `config/env.example` — variables de connexion à la BDD.
3. `backend/db.php` — wrapper PDO pour la connexion et helper `getPDO()`.
4. `index.php` — routeur frontal (voir section `Routing` ci-dessous).
5. `backend/models/User.php` — modèle minimal pour `users`.
6. `backend/controllers/ShiftController.php` — endpoints CRUD pour les quarts.
7. `public/views/shifts/list.php` — page d’affichage des quarts.
8. `public/assets/js/app.js` — interactions JS de base (fetch pour API).
9. `public/assets/css/styles.css` — styles minimaux.
10. `backend/api/shifts.php` — point d’accès JSON pour lister/créer/mettre à jour les shifts.

Commencer par un chemin critique minimal : DB → connexion → modèle → route → vue.

---

## ⚖️ **Comment séparer Frontend / Backend**
- Backend : toute la logique d'accès aux données et règles métier doit résider dans `backend/`.
- Frontend : fichiers statiques (HTML/CSS/JS) et vues doivent être dans `public/`.
- Communication : via requêtes internes (inclusions PHP pour rendu côté serveur) ou via appels AJAX `fetch()` vers `backend/api/*.php` qui retournent JSON.

Bonnes pratiques :
- Ne pas exécuter de logique SQL directement dans les fichiers `views`.
- Les controllers reçoivent les entrées (GET/POST), valident, appellent les modèles, puis renvoient une vue ou JSON.

---

## 🛣️ **Routing simple avec `index.php`**
Utiliser `index.php` comme point d’entrée pour simplifier l'examen :

1. Placer `index.php` dans la racine publique (`public/` ou racine du projet si vous servez depuis `/`).
2. Lire la variable `$_GET['route']` ou la `PATH_INFO` pour déterminer la route.
3. Exemple minimal :

```php
// index.php (extrait)
require __DIR__ . '/backend/db.php';
$route = $_GET['route'] ?? 'home';
switch ($route) {
    case 'shifts':
        require __DIR__ . '/public/views/shifts/list.php';
        break;
    case 'api_shifts':
        require __DIR__ . '/backend/api/shifts.php';
        break;
    default:
        require __DIR__ . '/public/views/home.php';
}
```

4. Lancer localement :

```bash
php -S localhost:8000
```

 et visiter `http://localhost:8000/index.php?route=shifts`

---

## ✅ **Conseils pour l'examen — expliquer la logique et le fonctionnement**
Lors de la présentation/examen, structurez clairement vos explications :

- **Contexte** : expliquer le besoin (gestion des quarts, contraintes d’accès local pour les signatures).
- **Architecture** : montrer la séparation `backend/` vs `public/` et comment les données circulent.
- **Flux principal** (ex : création d’un shift) :
  1. L’utilisateur soumet le formulaire (frontend JS ou POST simple).
 2. `index.php` ou controller route la requête vers `backend/controllers/ShiftController.php`.
 3. Le controller valide les données et appelle `backend/models/Shift.php`.
 4. Le modèle exécute la requête PDO préparée et retourne le résultat.
  5. Le controller renvoie une vue ou JSON, et le frontend met à jour l’interface.
- **Sécurité et validation** : montrer l’utilisation de requêtes préparées PDO, la validation côté serveur, et la gestion d’erreurs.
- **Démonstration** : préparer un scénario reproduit (ex : créer 2 employés, créer un shift, marquer la présence). Montrer les requêtes réseau (tab Réseau du navigateur) et la sortie SQL si besoin.

Points à expliciter lors de l’oral :
- Pourquoi PDO et statements préparés ? (sécurité contre injections SQL)
- Pourquoi séparer controllers/models/views ? (maintenabilité)
- Comment le routeur `index.php` simplifie le déploiement sur un hébergeur basique.

---

## 🔁 **Commandes utiles**

- Démarrer serveur de développement PHP :

```bash
php -S localhost:8000
```

- Importer la BDD (MySQL) :

```bash
mysql -u root -p < sql/schema.sql
```

---

## ✍️ **Remarques finales pour Copilot / Développeur**
- Travailler itérativement : implémenter un chemin critique et le démontrer avant d’ajouter des fonctionnalités secondaires.
- Documenter chaque endpoint minimalement (route, méthode, paramètres attendus, réponse JSON exemple).
- Fournir des scripts SQL simples et un `config/env.example` pour faciliter l’installation.

Bonne chance pour l’examen — si tu veux, je peux générer :
- le script `sql/schema.sql` initial,
- `backend/db.php` avec PDO,
- un `backend/api/shifts.php` minimal et une page `public/views/shifts/list.php`.
