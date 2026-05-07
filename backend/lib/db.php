<?php
/**
 * Database Connection
 */

function getDb(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        // Get from environment or use defaults
        $host = getenv('DB_HOST') ?: 'db';
        $dbname = getenv('DB_NAME') ?: 'tamalbank-db';
        $user = getenv('DB_USER') ?: 'tamalbank-user';
        $pass = getenv('DB_PASS') ?: 'tamalbank-password';
        
        $dsn = "pgsql:host=$host;dbname=$dbname";
        
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    
    return $pdo;
}