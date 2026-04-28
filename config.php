<?php
// Basic database configuration for NexusLeave.
// Adjust these values to match your local MySQL setup.

define('DB_HOST', 'localhost');
define('DB_NAME', 'nexusleave');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db_connection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

