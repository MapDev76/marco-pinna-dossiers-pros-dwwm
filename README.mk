# **StaffEase Pro**
*Gestion des quarts de travail, des présences et des demandes pour les entreprises (hôtels, hôpitaux, cliniques, etc.)*

---

## 📌 **Description du Projet**
**StaffEase Pro** est une **application web** conçue pour **simplifier la gestion des quarts de travail, des présences et des demandes** (congés, permissions, couvertures de quarts) dans les entreprises.
Ce projet a été développé dans le cadre d'un **examen de développement web et mobile**, en utilisant **uniquement PHP natif, JavaScript pur, HTML5 et CSS3** pour garantir une **compatibilité maximale** avec les hébergements gratuits comme **InfinityFree**.

---

---

## 👥 **Rôles et Permissions**
*(Nouvelle section ajoutée pour clarifier les différences entre les rôles)*
   **Rôle**               | **Description**                                                                                     | **Permissions**                                                                                     |
 |------------------------|-----------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------|
 | **Super Admin**       | **Rôle le plus élevé** : Gère **toutes les entreprises** et les **utilisateurs globaux**.          | ✅ Créer/modifier/supprimer des **entreprises**. <br> ✅ Assigner le rôle **Admin** aux utilisateurs pour une entreprise. <br> ✅ Configurer l’**`allowed_ip_range`** pour chaque entreprise. <br> ❌ **Ne peut pas** gérer les employés ou les départements d’une entreprise spécifique (c’est le rôle de l’Admin). |
 | **Admin**             | Gère **une seule entreprise** : départements, employés, quarts, présences.                         | ✅ **CRUD** sur les départements de son entreprise. <br> ✅ **CRUD** sur les employés de son entreprise. <br> ✅ Gérer les **quarts de travail** (création, assignation). <br> ✅ **Valider les présences** (uniquement si l’employé est connecté au réseau local). <br> ✅ **Approuver/Refuser** les demandes (couverture de quart, congé). <br> ✅ **Visualiser les rapports** (heures travaillées, retards). <br> ❌ **Ne peut pas** créer/supprimer des entreprises. |
 | **Chef de Département** | Gère **un département spécifique** : quarts et employés de son département.                       | ✅ Gérer les **quarts de travail** pour son département. <br> ✅ **Assigner des quarts** aux employés de son département. <br> ✅ **Approuver/Refuser** les demandes de son département. <br> ❌ **Ne peut pas** gérer les entreprises ou les départements hors de son scope. |
 | **Employé**           | Utilisateur standard : visualise ses quarts et fait des demandes.                                | ✅ **Visualiser ses quarts** assignés. <br> ✅ **Signer sa présence** (uniquement depuis le réseau local). <br> ✅ **Faire des demandes** (couverture de quart, congé, permission). <br> ❌ **Ne peut pas** modifier les quarts ou les données d’autres employés. |

---

---

## 🛠 **Technologies Utilisées**
 | **Catégorie**       | **Technologies**                                                                                     | **Justification**                                                                                     |
 |----------------------|-----------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------|
 | **Langages**         | PHP (natif), JavaScript (pur), HTML5, CSS3                                                          | **PHP** pour la logique côté serveur, **JavaScript** pour l'interactivité côté client.              |
 | **Base de Données**  | MySQL                                                                                              | Base de données relationnelle **gratuite et largement supportée**.                                |
 | **Hébergement**      | InfinityFree (ou tout hébergement supportant PHP/MySQL)                                           | **Gratuit**, compatible avec PHP natif et MySQL.                                                   |
 | **Outils**           | VS Code, FileZilla (pour le déploiement FTP), MySQL Workbench (pour la base de données)           | **VS Code** pour le développement, **FileZilla** pour le déploiement.                              |
 | **Design**           | CSS3 (avec Flexbox/Grid), pas de framework (Bootstrap, Tailwind, etc.)                              | **Apprentissage des fondamentaux** du CSS.                                                          |

---

---

## 🚀 **Comment Accéder au Projet ?**

### **1. Prérequis**
- Un **hébergement web** supportant **PHP 7.4+** et **MySQL** (ex. InfinityFree, 000WebHost, XAMPP/MAMP pour le développement local).
- Un **navigateur web moderne** (Chrome, Firefox, Edge).
- Un **client FTP** (ex. FileZilla) pour déployer les fichiers.

---

### **2. Installation et Déploiement**
#### **📥 Déploiement sur InfinityFree**
1. **Télécharger les fichiers** du projet depuis ce dépôt.
2. **Créer une base de données MySQL** :
   - Allez dans **cPanel > MySQL Databases**.
   - Créez une nouvelle base de données (ex. `staff_ease_pro`).
   - Créez un utilisateur MySQL et associez-le à la base de données.
3. **Importer le schéma de la base de données** :
   - Utilisez **phpMyAdmin** (disponible dans cPanel) pour importer le fichier `database/staff_ease_pro.sql`.
4. **Configurer les informations de la base de données** :
   - Modifiez le fichier `includes/config.php` avec les **identifiants de votre base de données** :
     ```php
     define('DB_HOST', 'sqlXXX.epizy.com'); // Hôte InfinityFree
     define('DB_USER', 'epiz_XXXXXX');      // Utilisateur MySQL
     define('DB_PASS', 'votre_mot_de_passe'); // Mot de passe MySQL
     define('DB_NAME', 'epiz_XXXXXX_staff_ease_pro'); // Nom de la base de données
     ```
5. **Télécharger les fichiers via FTP** :
   - Utilisez **FileZilla** pour télécharger tous les fichiers dans le dossier `public_html` de votre hébergement.
6. **Accéder à l'application** :
   - Visitez votre domaine (ex. `https://votre-site.epizy.com`).

#### **💻 Développement Local (XAMPP/MAMP)**
1. **Installer XAMPP/MAMP** pour avoir un serveur local (Apache + MySQL).
2. **Cloner le projet** dans le dossier `htdocs` (XAMPP) ou `www` (MAMP).
3. **Créer la base de données** via phpMyAdmin (`http://localhost/phpmyadmin`).
4. **Importer le schéma** `database/staff_ease_pro.sql`.
5. **Configurer `includes/config.php`** :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'staff_ease_pro');