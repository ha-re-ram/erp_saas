<?php
// admin/dashboard/category_delete.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $store_id = $_SESSION['store_id'];

    // Ensure the category belongs to this store before deleting
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id AND store_id = :store_id");
    $stmt->execute(['id' => $id, 'store_id' => $store_id]);
}

header('Location: categories.php');
exit;
