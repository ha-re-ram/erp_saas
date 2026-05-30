<?php
// admin/dashboard/category_edit.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

$store_id = $_SESSION['store_id'];
$error = '';
$success = '';

if (!isset($_GET['id'])) {
    header('Location: categories.php');
    exit;
}

$category_id = intval($_GET['id']);

// Fetch existing category detail and ensure it belongs to the active tenant
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND store_id = ?");
$stmt->execute([$category_id, $store_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: categories.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));

    if (empty($name) || empty($slug)) {
        $error = "Name and Slug are required.";
    } else {
        // Ensure slug is unique for this store, excluding this category itself
        $stmtSlug = $pdo->prepare("SELECT id FROM categories WHERE store_id = ? AND slug = ? AND id != ?");
        $stmtSlug->execute([$store_id, $slug, $category_id]);
        
        if ($stmtSlug->fetch()) {
            $error = "A category with this URL slug already exists.";
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ? AND store_id = ?");
            if ($stmtUpdate->execute([$name, $slug, $category_id, $store_id])) {
                header("Location: categories.php");
                exit;
            } else {
                $error = "Failed to update category.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - ERP Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .card { border-radius: 16px; border: none; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Category</h5>
                <a href="categories.php" class="btn btn-sm btn-outline-light">Back to Categories</a>
            </div>
            <div class="card-body p-4">
                <?php if($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Shoes" value="<?= htmlspecialchars($category['name']) ?>">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">URL Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control" required placeholder="e.g. shoes" value="<?= htmlspecialchars($category['slug']) ?>">
                        <div class="form-text">The URL-friendly version of the name. Must contain only lowercase letters, numbers, and hyphens.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-generate slug from name as user types (only if modified)
        document.getElementById('name').addEventListener('input', function() {
            var slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
            document.getElementById('slug').value = slug;
        });
    </script>
</body>
</html>
