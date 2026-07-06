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

function appSupportedLocales(): array
{
    return ['fr', 'en', 'it'];
}

function appDefaultLocale(): string
{
    return 'fr';
}

function appLocale(): string
{
    static $resolved = null;
    if (is_string($resolved) && $resolved !== '') {
        return $resolved;
    }

    startAppSession();

    $supported = appSupportedLocales();
    $requested = strtolower(trim((string) ($_GET['lang'] ?? '')));
    if ($requested !== '' && in_array($requested, $supported, true)) {
        $_SESSION['app_locale'] = $requested;
    }

    $sessionLocale = strtolower(trim((string) ($_SESSION['app_locale'] ?? '')));
    if ($sessionLocale !== '' && in_array($sessionLocale, $supported, true)) {
        $resolved = $sessionLocale;
        return $resolved;
    }

    $resolved = appDefaultLocale();
    $_SESSION['app_locale'] = $resolved;

    return $resolved;
}

function appCurrentUrl(array $overrides = []): string
{
    $query = $_GET;
    if (!isset($query['route'])) {
        $query['route'] = $_GET['route'] ?? 'home';
    }

    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($query[$key]);
            continue;
        }
        $query[$key] = $value;
    }

    $basePath = appBasePath();
    return ($basePath === '' ? '' : $basePath) . '/?' . http_build_query($query);
}

function appTranslations(string $locale): array
{
    static $cache = [];
    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    $file = __DIR__ . '/../config/lang/' . $locale . '.php';
    if (!is_file($file)) {
        $cache[$locale] = [];
        return $cache[$locale];
    }

    $loaded = require $file;
    $cache[$locale] = is_array($loaded) ? $loaded : [];
    return $cache[$locale];
}

function t(string $key, array $replace = [], ?string $locale = null): string
{
    $locale = $locale ?: appLocale();
    $fallbackLocale = appDefaultLocale();

    $resolve = static function (array $source, string $path): ?string {
        $node = $source;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return null;
            }
            $node = $node[$segment];
        }
        return is_string($node) ? $node : null;
    };

    $value = $resolve(appTranslations($locale), $key);
    if ($value === null && $locale !== $fallbackLocale) {
        $value = $resolve(appTranslations($fallbackLocale), $key);
    }
    if ($value === null) {
        return $key;
    }

    if (!empty($replace)) {
        $pairs = [];
        foreach ($replace as $replaceKey => $replaceValue) {
            $pairs['{' . $replaceKey . '}'] = (string) $replaceValue;
        }
        $value = strtr($value, $pairs);
    }

    return $value;
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
function requireRole(string $role, string $errorMessage = ''): void
{
    if ($errorMessage === '') {
        $errorMessage = t('common.access_restricted');
    }

    if (!isLoggedIn()) {
        setFlash('error', t('common.login_required'));
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
    requireRole('super_admin', t('common.super_admin_only'));
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

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS user_department_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                department_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_user_department (user_id, department_id),
                KEY idx_user_department_user (user_id),
                KEY idx_user_department_department (department_id),
                CONSTRAINT fk_user_department_links_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_user_department_links_department
                    FOREIGN KEY (department_id) REFERENCES departments(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $e) {
        // Table already exists or legacy schema issue.
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
            'icon' => 'popcorn.svg',
            'color' => '#9ca3af',
            'description' => 'System template for rest day assignment.',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
        ],
        'vacation' => [
            'name' => 'Vacation',
            'icon' => 'parasol.svg',
            'color' => '#9ca3af',
            'description' => 'System template for vacation assignment.',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
        ],
        'sick' => [
            'name' => 'Sick leave',
            'icon' => 'heart-pulse.svg',
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

    $refreshExisting = $pdo->prepare(
        'UPDATE shifts
         SET name = :name,
             icon = :icon,
             color = :color,
             description = :description,
             start_time = :start_time,
             end_time = :end_time
         WHERE id = :id'
    );

    foreach ($templates as $kind => $template) {
        $lookup->execute([
            'department_id' => $departmentId,
            'kind' => $kind,
        ]);

        $existingId = (int) ($lookup->fetchColumn() ?: 0);
        if ($existingId > 0) {
            $refreshExisting->execute([
                'name' => (string) ($template['name'] ?? ucfirst($kind)),
                'icon' => (string) ($template['icon'] ?? ''),
                'color' => (string) ($template['color'] ?? '#9ca3af'),
                'description' => (string) ($template['description'] ?? ''),
                'start_time' => (string) ($template['start_time'] ?? '00:00:00'),
                'end_time' => (string) ($template['end_time'] ?? '23:59:00'),
                'id' => $existingId,
            ]);
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

/**
 * Build a small preview thumbnail data URL for document cards.
 * Supports PDF (first page) and image mime types via Imagick.
 */
function documentThumbnailDataUrl(array $document, int $maxWidth = 220, int $maxHeight = 120): ?string
{
    if (!class_exists('Imagick')) {
        return null;
    }

    $maxWidth = max(60, $maxWidth);
    $maxHeight = max(40, $maxHeight);

    $documentId = (int) ($document['id'] ?? $document['document_id'] ?? 0);
    $fileName = (string) ($document['file_name'] ?? 'document');
    $uploadStamp = (string) ($document['upload_date'] ?? $document['created_at'] ?? '');
    $cacheKey = ($documentId > 0 ? (string) $documentId : ($fileName . '|' . $uploadStamp))
        . '|' . $maxWidth . 'x' . $maxHeight;

    static $cache = [];
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $mimeType = strtolower(trim((string) ($document['file_mime_type'] ?? '')));
    if ($mimeType === '') {
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension === 'pdf') {
            $mimeType = 'application/pdf';
        }
    }

    $isPdf = str_contains($mimeType, 'pdf');
    $isImage = str_starts_with($mimeType, 'image/');
    if (!$isPdf && !$isImage) {
        $cache[$cacheKey] = null;
        return null;
    }

    $blobContent = $document['file_blob'] ?? null;
    $filePath = trim((string) ($document['file_path'] ?? ''));
    $resolvedPath = null;

    if ($filePath !== '') {
        $candidates = [
            $filePath,
            __DIR__ . '/../' . ltrim($filePath, '/'),
            __DIR__ . '/../public/' . ltrim($filePath, '/'),
        ];
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
                $resolvedPath = $candidate;
                break;
            }
        }
    }

    try {
        $imagick = new Imagick();
        if ($isPdf) {
            $imagick->setResolution(120, 120);
        }

        if (is_string($blobContent) && $blobContent !== '') {
            $imagick->readImageBlob($blobContent);
            if ($isPdf && $imagick->getNumberImages() > 1) {
                $imagick->setIteratorIndex(0);
            }
        } elseif ($resolvedPath !== null) {
            if ($isPdf) {
                $imagick->readImage($resolvedPath . '[0]');
            } else {
                $imagick->readImage($resolvedPath);
            }
        } else {
            $cache[$cacheKey] = null;
            return null;
        }

        $imagick->setImageBackgroundColor('white');
        if (defined('Imagick::ALPHACHANNEL_REMOVE')) {
            $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        }

        $imagick->thumbnailImage($maxWidth, $maxHeight, true, true);
        $imagick->setImageFormat('jpeg');
        $blob = (string) $imagick->getImageBlob();
        $imagick->clear();
        $imagick->destroy();

        if ($blob === '') {
            $cache[$cacheKey] = null;
            return null;
        }

        $cache[$cacheKey] = 'data:image/jpeg;base64,' . base64_encode($blob);
        return $cache[$cacheKey];
    } catch (Throwable $e) {
        $cache[$cacheKey] = null;
        return null;
    }
}