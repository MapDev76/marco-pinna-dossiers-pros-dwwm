<?php

// Charge la configuration de connexion à la base de données.
function getConfig(): array
{
    return require __DIR__ . '/../config/database.php';
}

// Retourne une instance PDO unique, partagée pendant la requête en cours.
function getPDO(): PDO
{
    static $pdo = null;
// Si une instance existe déjà, la retourner.
    if ($pdo instanceof PDO) {
        return $pdo;
    }
// Sinon, créer une nouvelle instance PDO avec la configuration et la retourner.
    $config = getConfig();
//Data Source Name (DSN) : chaîne de connexion formatée pour PDO.
    $dsn = sprintf(
        '%s:host=%s;port=%d;dbname=%s;charset=%s',
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );
//
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lancer des exceptions en cas d'erreur de base de données.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Retourner les résultats sous forme de tableaux associatifs par défaut.
        PDO::ATTR_EMULATE_PREPARES => false, // Utiliser les requêtes préparées natives du pilote pour une meilleure sécurité et performance.
    ];

    $pdo = new PDO($dsn, $config['username'], $config['password'], $options); // Créer une nouvelle instance PDO avec les paramètres de connexion et les options spécifiées.

    return $pdo;
}
