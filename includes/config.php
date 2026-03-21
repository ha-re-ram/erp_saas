<?php
// includes/config.php

// Define environment parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change on production
define('DB_PASS', '');     // Change on production
define('DB_NAME', 'erp_saas');

define('APP_NAME', 'SaaS ERP & E-Commerce');
define('APP_URL', 'http://localhost/erp_saas'); // Base platform URL
define('MAIN_DOMAIN', 'localhost'); // e.g., 'mysite.com' for production

// Setup error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
