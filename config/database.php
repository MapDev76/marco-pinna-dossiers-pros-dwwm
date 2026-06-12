<?php

// PDO connection configuration for local development with MAMP.
return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 8889,
    'database' => 'staff_ease_pro',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8mb4',
];


// <?php
// PDO connection configuration for InfinityFree MySQL.
// return [
//     'driver'    => 'mysql',
//     'host'      => 'sql308.infinityfree.com',  // ✅ Host MySQL (non FTP!)
//     'port'      => 3306,                      // ✅ Porta MySQL standard
//     'database'  => 'if0_41728115_db_staffeasepro', // ✅ Nome database (dallo screenshot)
//     'username'  => 'if0_41728115',           // ✅ Username MySQL (stesso dell'FTP)
//     'password'  => 'oHvJj3rSdfw',            // ✅ Password MySQL (dallo screenshot)
//     'charset'   => 'utf8mb4',
//     'collation' => 'utf8mb4_unicode_ci',
//     'options'   => [
//         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Abilita errori PDO
//         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//         PDO::ATTR_EMULATE_PREPARES => false,
//     ],
// ];
// ?> 
