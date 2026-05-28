<?php

/**
 * Fichier de fonctions utilitaires
 * Contient des fonctions globales pour la gestion des sessions, des URL, des redirections, des messages flash et de l'authentification.
 * Ces fonctions sont utilisées dans les contrôleurs et les vues pour simplifier le code et éviter les répétitions.
 */

function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Fonctions de gestion des URL et des redirections
 * Permettent de construire des URL basées sur la route et les paramètres.
 */
function appBasePath(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

    if ($scriptDir === '.' || $scriptDir === '/' || $scriptDir === '') {
        return '';
    }

    return rtrim($scriptDir, '/');
}

/**
 * appUrl
 * Retourne l'URL complète pour une route donnée.
 * Utilisé par les vues et contrôleurs pour construire des liens.
 * Rôle: utilisé par toutes les vues et contrôleurs (global).
 */

function appUrl(string $route, array $params = []): string
{
    $query = array_merge(['route' => $route], $params);
    $basePath = appBasePath();

    return ($basePath === '' ? '' : $basePath) . '/?' . http_build_query($query);
}

function redirectTo(string $route, array $params = []): never
{
    header('Location: ' . appUrl($route, $params));
    exit;
}

/**
 * e
 * Échappe une valeur pour affichage HTML (prévention XSS).
 * Utilisé par les vues quand elles affichent des données utilisateur.
 * Rôle: sécurité côté affichage (toutes les vues).
 */

/**
 * Fonction d'échappement pour la sécurité
 * Permet d'échapper les données avant de les afficher dans les vues pour éviter les attaques XSS.
 */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Fonction de réponse JSON
 * Permet de retourner une réponse JSON avec un code de statut HTTP approprié.
 */

function jsonResponse(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * setFlash / getFlash
 * Stocke et récupère des messages flash dans la session.
 * Utilisé par les contrôleurs pour informer l'utilisateur après redirection.
 * Rôle: notifications temporanee per l'utente.
 */

/**
 * Fonctions de gestion des messages flash
 * Permettent de stocker des messages temporaires dans la session pour les afficher après une redirection.
 */

function setFlash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function getFlash(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

/**
 * currentUser / isLoggedIn / role helpers
 * Fournissent l'information sur l'utilisateur connecté et ses rôles.
 * Utilisés par les contrôleurs pour vérifier l'état de connexion et les permissions.
 * Rôle: contrôle d'accès côté serveur (tous les contrôleurs backend).
 */

/**
 * Fonctions d'authentification et de gestion des utilisateurs
 * Permettent de vérifier l'état de connexion et les rôles des utilisateurs.
 */

function currentUser(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

/**
 * Middlewares d'accès
 * Vérifie que l'utilisateur est connecté et possède un rôle spécifique.
 * Redirige vers la page de connexion ou le tableau de bord si les conditions ne sont pas remplies.
 */

function hasRole(string $role): bool
{
    $user = currentUser();

    return $user !== null && ($user['role'] ?? null) === $role;
}

function isSuperAdmin(): bool
{
    return hasRole('super_admin');
}


function requireRole(string $role, string $errorMessage = 'Access restricted.'): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirectTo('login');
    }

    if (!hasRole($role)) {
        setFlash('error', $errorMessage);
        redirectTo('dashboard');
    }
}

function requireSuperAdmin(): void
{
    requireRole('super_admin', 'Access restricted to Super Admin.');
}