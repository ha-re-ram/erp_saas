<?php
// includes/config.php

// Database configuration
define('DB_HOST', 'sql204.infinityfree.com');
define('DB_NAME', 'if0_42056750_XXXX'); // Replace XXXX with your database suffix
define('DB_USER', 'if0_42056750');
define('DB_PASS', 'YOUR_INFINITYFREE_PASSWORD_HERE'); // Enter your direct password here (do not commit to GitHub!)

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
