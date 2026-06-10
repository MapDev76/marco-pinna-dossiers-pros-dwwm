<?php

// PDO connection configuration for the application database.
// Override these values on the target host if environment variables are available.
$dbPort = getenv('DB_PORT');

return [
    'driver' => getenv('DB_DRIVER') ?: 'mysql',
    'host' => getenv('DB_HOST') ?: 'sql308.infinityfree.com',
    'port' => $dbPort !== false && $dbPort !== '' ? (int) $dbPort : 3306,
    'database' => getenv('DB_NAME') ?: 'if0_41728115_db_staffeasepro',
    'username' => getenv('DB_USER') ?: 'if0_41728115',
    'password' => getenv('DB_PASS') ?: 'oHvJj3rSdfw',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];
