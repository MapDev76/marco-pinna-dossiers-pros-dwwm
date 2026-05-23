# StaffEase Pro

Application PHP/MySQL simple pour la gestion des quarts, présences, demandes et documents.

## Objectif
Le projet doit rester lisible, simple et facile à présenter à l'examen. La structure actuelle s'appuie sur un routeur frontal, des contrôleurs PHP, des modèles PDO et des vues serveur.

## Structure
- `index.php` : point d'entrée unique.
- `backend/` : bootstrap, helpers, contrôleurs, modèles, endpoints JSON.
- `app/layout/` : éléments partagés de l'interface.
- `public/views/` : pages visibles selon le rôle.
- `public/assets/` : CSS, icônes et images.
- `config/` : paramètres de connexion.
- `db/` : schéma SQL.

## Règles de base
- Tout le texte visible doit rester en français.
- Le formulaire de connexion est unique pour tous les rôles.
- La page d'accueil reste publique.
- Le tableau de bord change selon le rôle connecté.
- Les endpoints JSON sont utilisés pour les besoins REST ou AJAX et retournent du JSON via le routeur.

## Point JSON existant
- `?route=api-dashboard` renvoie une réponse JSON.
- Il sert à exposer les données du tableau de bord sans HTML.

## Organisation conseillée pour l'examen
1. Montrer le routeur et l'authentification.
2. Expliquer la séparation entre contrôleurs, modèles et vues.
3. Montrer une action simple de CRUD.
4. Montrer l'endpoint JSON.
5. Expliquer la configuration pour l'hébergement.

## Déploiement
- Le projet est prévu pour fonctionner sur un hébergement PHP/MySQL standard comme InfinityFree.
- Les chemins sont construits de manière relative pour rester valides en racine ou en sous-dossier.

## Remarques
- Les fichiers de test temporaires ont été supprimés.
- Le code doit rester sobre, sans couches inutiles ni logique cachée dans les vues.
