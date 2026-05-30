<?php
// includes/db.php
require_once 'config.php';

// Detect environment variables (Clever Cloud, Railway, Render, or InfinityFree)
$db_host = getenv('MYSQL_ADDON_HOST') ?: (getenv('DB_HOST') ?: DB_HOST);
$db_name = getenv('MYSQL_ADDON_DB') ?: (getenv('DB_NAME') ?: DB_NAME);
$db_user = getenv('MYSQL_ADDON_USER') ?: (getenv('DB_USER') ?: DB_USER);
$db_pass = getenv('MYSQL_ADDON_PASSWORD') ?: (getenv('DB_PASS') ?: DB_PASS);
$db_port = getenv('MYSQL_ADDON_PORT') ?: (getenv('DB_PORT') ?: '3306');

try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
