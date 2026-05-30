<?php
// includes/functions.php
require_once 'db.php';

/**
 * Determine the current store based on the subdomain.
 */
function getCurrentStore($pdo) {
    if (php_sapi_name() === 'cli') return null;

    $host = $_SERVER['HTTP_HOST']; // e.g., store1.mysite.com
    $subdomain = explode('.', $host)[0];
    
    // Fallback for local testing and platform landing pages (e.g., hareramkushwah.infinityfree.io/?store=electronics)
    if (($host === 'localhost' || strpos($host, 'hareramkushwah') !== false) && isset($_GET['store'])) {
        $subdomain = $_GET['store'];
    }

    $stmt = $pdo->prepare("SELECT * FROM stores WHERE subdomain = :subdomain AND status = 'active' LIMIT 1");
    $stmt->execute(['subdomain' => $subdomain]);
    $store = $stmt->fetch();

    return $store ?: false;
}

/**
 * Require admin authentication
 */
function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Format currency
 */
function formatPrice($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Clean input data
 */
function cleanInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
