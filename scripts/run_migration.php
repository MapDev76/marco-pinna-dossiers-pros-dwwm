<?php
/**
 * Simple migration runner: apply or rollback a named migration.
 * Usage: php scripts/run_migration.php up 20260601_add_icon_color
 *        php scripts/run_migration.php down 20260601_add_icon_color
 */
require_once __DIR__ . '/../backend/bootstrap.php';

$action = $argv[1] ?? null; // up or down
$name = $argv[2] ?? null; // migration base name

if (!in_array($action, ['up', 'down'], true) || !$name) {
    echo "Usage: php scripts/run_migration.php up|down migration_name\n";
    exit(1);
}

$dir = __DIR__ . '/../db/migrations';
$file = $dir . '/' . $name . '.' . ($action === 'up' ? 'up.sql' : 'down.sql');

if (!file_exists($file)) {
    echo "Migration file not found: {$file}\n";
    exit(1);
}

$sql = file_get_contents($file);
if ($sql === false) {
    echo "Failed to read migration file: {$file}\n";
    exit(1);
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();
    $pdo->exec($sql);
    $pdo->commit();
    echo "Migration {$action} applied: {$name}\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
