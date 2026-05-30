<?php
// includes/config.php

// Define environment// Database configuration
define('DB_HOST', 'bfovm7vgdwvajydo3fti-mysql.services.clever-cloud.com');
define('DB_NAME', 'bfovm7vgdwvajydo3fti');
define('DB_USER', 'ummjclzu0fe8ya63');
define('DB_PASS', 'CxlBtMkU87ZvmTlru5La');

// Base URL configuration (without trailing slash)
define('BASE_URL', 'http://hareramkushwah.infinityfree.io'); // Base platform URL
define('MAIN_DOMAIN', 'localhost'); // e.g., 'mysite.com' for production

// Setup error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
