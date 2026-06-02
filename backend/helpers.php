<?php

/**
 * Shared helper functions used by controllers and views.
 * They centralize URL building, redirects, flash messages, auth checks, and HTML escaping.
 */

function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Builds application URLs from a route name and optional query parameters.
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
 * Returns a full application URL for the given route and query string.
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
 * Escapes a value for safe HTML output.
 */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Sends a JSON response and stops execution.
 */

function jsonResponse(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Stores and retrieves one-time flash messages from the session.
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
 * Authentication helpers used to inspect the current session user and roles.
 */

function currentUser(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function currentUserCompanyId(?array $user = null): ?int
{
    $user = $user ?? currentUser();

    if ($user === null || empty($user['department_id'])) {
        return null;
    }

    try {
        $pdo = getPDO();
        $statement = $pdo->prepare(
            'SELECT company_id
             FROM departments
             WHERE id = :department_id
             LIMIT 1'
        );
        $statement->execute(['department_id' => (int) $user['department_id']]);

        $companyId = $statement->fetchColumn();

        return $companyId !== false ? (int) $companyId : null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Checks whether the current user is logged in.
 */
function isLoggedIn(): bool
{
    return currentUser() !== null;
}

/**
 * Middlewares d'accès
 * Vérifie que l'utilisateur est connecté et possède un rôle spécifique.
 * Redirige vers la page de connexion ou le tableau de bord si les conditions ne sont pas remplies.
 */

/**
 * Checks whether the current user has the requested role.
 */
function hasRole(string $role): bool
{
    $user = currentUser();

    return $user !== null && ($user['role'] ?? null) === $role;
}

/**
 * Shortcut for checking the super admin role.
 */
function isSuperAdmin(): bool
{
    return hasRole('super_admin');
}


/**
 * Ensures the current user has the requested role or stops the request.
 */
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

/**
 * Ensures the current user is a super admin or stops the request.
 */
function requireSuperAdmin(): void
{
    requireRole('super_admin', 'Access restricted to Super Admin.');
}

/**
 * Ensures scheduler-related columns support shift kinds and open assignments
 * without introducing extra tables.
 */
function ensureSchedulerSchema(PDO $pdo): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    try {
        $pdo->exec("ALTER TABLE shifts ADD COLUMN kind ENUM('work', 'rest', 'vacation', 'sick', 'overtime') NOT NULL DEFAULT 'work' AFTER description");
    } catch (Throwable $e) {
        // Column already exists.
    }

    try {
        $pdo->exec("ALTER TABLE shifts MODIFY kind ENUM('work', 'rest', 'vacation', 'sick', 'overtime') NOT NULL DEFAULT 'work'");
    } catch (Throwable $e) {
        // Ignore if enum already matches.
    }

    try {
        $pdo->exec('ALTER TABLE user_shifts MODIFY user_id INT NULL');
    } catch (Throwable $e) {
        // Ignore if already nullable.
    }

    try {
        $pdo->exec('ALTER TABLE user_shifts MODIFY status ENUM("open", "assigned", "completed", "cancelled", "in_progress") NOT NULL DEFAULT "assigned"');
    } catch (Throwable $e) {
        // Ignore if enum already matches.
    }

    $initialized = true;
}