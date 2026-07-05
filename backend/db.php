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
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch results as associative arrays by default
        PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements when possible
    ];

    $attempts = []; //
    $baseCandidates = [];

    $configuredHost = trim((string) ($config['host'] ?? '127.0.0.1'));
    $configuredPort = (int) ($config['port'] ?? 0);
    $database = (string) ($config['database'] ?? '');
    $charset = (string) ($config['charset'] ?? 'utf8mb4');

    if ($configuredHost !== '') {
        $baseCandidates[] = ['host' => $configuredHost, 'port' => $configuredPort];
        if ($configuredHost === '127.0.0.1') {
            $baseCandidates[] = ['host' => 'localhost', 'port' => $configuredPort];
        }
        if ($configuredHost === 'localhost') {
            $baseCandidates[] = ['host' => '127.0.0.1', 'port' => $configuredPort];
        }
    }

    if ($configuredPort === 8889) {
        $baseCandidates[] = ['host' => $configuredHost ?: '127.0.0.1', 'port' => 3306];
        $baseCandidates[] = ['host' => 'localhost', 'port' => 3306];
        $baseCandidates[] = ['host' => '127.0.0.1', 'port' => 3306];
    } elseif ($configuredPort === 3306) {
        $baseCandidates[] = ['host' => $configuredHost ?: 'localhost', 'port' => 8889];
        $baseCandidates[] = ['host' => 'localhost', 'port' => 8889];
        $baseCandidates[] = ['host' => '127.0.0.1', 'port' => 8889];
    }

    $baseCandidates[] = ['host' => $configuredHost ?: 'localhost', 'port' => 0];
    $baseCandidates[] = ['host' => 'localhost', 'port' => 0];

    $uniqueCandidates = [];
    foreach ($baseCandidates as $candidate) {
        $key = $candidate['host'] . ':' . (string) $candidate['port'];
        $uniqueCandidates[$key] = $candidate;
    }

    foreach ($uniqueCandidates as $candidate) {
        $dsnParts = [
            sprintf('%s:host=%s', $config['driver'], $candidate['host']),
        ];

        if ((int) $candidate['port'] > 0) {
            $dsnParts[] = 'port=' . (int) $candidate['port'];
        }

        $dsnParts[] = 'dbname=' . $database;
        $dsnParts[] = 'charset=' . $charset;
        $dsn = implode(';', $dsnParts);

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
            return $pdo;
        } catch (Throwable $connectionError) {
            $attempts[] = $candidate['host'] . ':' . ((int) $candidate['port'] > 0 ? (string) $candidate['port'] : 'default');
        }
    }

    throw new RuntimeException('Unable to connect to the database. Tried: ' . implode(', ', $attempts));
}
