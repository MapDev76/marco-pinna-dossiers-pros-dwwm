<?php

function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function appBasePath(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

    if ($scriptDir === '.' || $scriptDir === '/' || $scriptDir === '') {
        return '';
    }

    return rtrim($scriptDir, '/');
}

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

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

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

function currentUser(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function isSuperAdmin(): bool
{
    $user = currentUser();

    return $user !== null && ($user['role'] ?? null) === 'super_admin';
}

function requireSuperAdmin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Veuillez vous connecter pour continuer.');
        redirectTo('login');
    }

    if (!isSuperAdmin()) {
        setFlash('error', 'Accès réservé au Super Admin.');
        redirectTo('dashboard');
    }
}
