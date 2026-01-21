<?php
// db.php
declare(strict_types=1);

function getPDO(): PDO {
    // Ruta para la base de datos
    $dbPath = __DIR__ . '/storage/db/EcoHome.db';

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("PRAGMA foreign_keys = ON;");
    return $pdo;
}

