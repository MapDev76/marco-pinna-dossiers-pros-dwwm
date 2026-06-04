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

function appTimezoneName(): string
{
    return 'Europe/Rome';
}

function appTimezone(): DateTimeZone
{
    static $timezone = null;

    if (!$timezone instanceof DateTimeZone) {
        $timezone = new DateTimeZone(appTimezoneName());
    }

    return $timezone;
}

function appNow(): DateTimeImmutable
{
    return new DateTimeImmutable('now', appTimezone());
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
 * Access middleware helpers.
 * Ensure the user is authenticated and has the expected role.
 * Redirect to login or dashboard when requirements are not met.
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

function ensureDocumentStorageSchema(PDO $pdo): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    try {
        $pdo->exec('ALTER TABLE documents ADD COLUMN file_blob LONGBLOB NULL AFTER file_path');
    } catch (Throwable $e) {
        // Column already exists.
    }

    try {
        $pdo->exec('ALTER TABLE documents ADD COLUMN file_mime_type VARCHAR(100) NULL AFTER file_blob');
    } catch (Throwable $e) {
        // Column already exists.
    }

    $initialized = true;
}

function absenceShiftTemplateDefinitions(): array
{
    return [
        'rest' => [
            'name' => 'Rest day',
            'icon' => '💤',
            'color' => '#9ca3af',
            'description' => 'System template for rest day assignment.',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
        ],
        'vacation' => [
            'name' => 'Vacation',
            'icon' => '🏖',
            'color' => '#9ca3af',
            'description' => 'System template for vacation assignment.',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
        ],
        'sick' => [
            'name' => 'Sick leave',
            'icon' => '🤒',
            'color' => '#9ca3af',
            'description' => 'System template for sick leave assignment.',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
        ],
    ];
}

function ensureDepartmentAbsenceShiftTemplates(PDO $pdo, int $departmentId): array
{
    if ($departmentId <= 0) {
        return [];
    }

    $templates = absenceShiftTemplateDefinitions();
    $idsByKind = [];

    $lookup = $pdo->prepare(
        'SELECT id
         FROM shifts
         WHERE department_id = :department_id
           AND kind = :kind
         ORDER BY id ASC
         LIMIT 1'
    );

    $insert = $pdo->prepare(
        'INSERT INTO shifts (department_id, name, icon, color, description, kind, start_time, end_time)
         VALUES (:department_id, :name, :icon, :color, :description, :kind, :start_time, :end_time)'
    );

    foreach ($templates as $kind => $template) {
        $lookup->execute([
            'department_id' => $departmentId,
            'kind' => $kind,
        ]);

        $existingId = (int) ($lookup->fetchColumn() ?: 0);
        if ($existingId > 0) {
            $idsByKind[$kind] = $existingId;
            continue;
        }

        $insert->execute([
            'department_id' => $departmentId,
            'name' => (string) ($template['name'] ?? ucfirst($kind)),
            'icon' => (string) ($template['icon'] ?? ''),
            'color' => (string) ($template['color'] ?? '#9ca3af'),
            'description' => (string) ($template['description'] ?? ''),
            'kind' => $kind,
            'start_time' => (string) ($template['start_time'] ?? '00:00:00'),
            'end_time' => (string) ($template['end_time'] ?? '23:59:00'),
        ]);
        $idsByKind[$kind] = (int) $pdo->lastInsertId();
    }

    return $idsByKind;
}

function ensureAbsenceShiftTemplatesForDepartments(PDO $pdo, array $departmentIds): void
{
    foreach ($departmentIds as $departmentId) {
        ensureDepartmentAbsenceShiftTemplates($pdo, (int) $departmentId);
    }
}