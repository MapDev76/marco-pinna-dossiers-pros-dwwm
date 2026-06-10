<?php

/**
 * Loads the database configuration array from the application config file.
 */
function getConfig(): array
{
    return require __DIR__ . '/../config/database.php';
}

/**
 * Returns a shared PDO instance for the current request.
 * The static cache avoids opening multiple connections in the same request.
 */
function getPDO(): PDO
{
    static $pdo = null;

    // Reuse the existing connection when possible.
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Otherwise build a new PDO connection from the configuration values.
    $config = getConfig();

    // Build the Data Source Name (DSN) used by PDO.
    $dsnParts = [
        sprintf('%s:host=%s', $config['driver'], $config['host']),
    ];

    $port = (int) ($config['port'] ?? 0);
    if ($port > 0) {
        $dsnParts[] = 'port=' . $port;
    }

    $dsnParts[] = 'dbname=' . (string) $config['database'];
    $dsnParts[] = 'charset=' . (string) $config['charset'];
    $dsn = implode(';', $dsnParts);

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);

    return $pdo;
}
