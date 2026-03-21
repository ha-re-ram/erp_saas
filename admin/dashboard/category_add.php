<?php
// admin/dashboard/category_add.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));

    if (empty($name) || empty($slug)) {
        $error = "Name and Slug are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE store_id = ? AND slug = ?");
        $stmt->execute([$store_id, $slug]);
        if ($stmt->fetch()) {
            $error = "A category with this slug already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (store_id, name, slug) VALUES (?, ?, ?)");
            if ($stmt->execute([$store_id, $name, $slug])) {
                header("Location: categories.php");
                exit;
            } else {
                $error = "Failed to add category.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Category - ERP Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0">Add New Category</h5>
                <a href="categories.php" class="btn btn-sm btn-outline-light">Back to Categories</a>
            </div>
            <div class="card-body p-4">
                <?php if($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Shoes">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">URL Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control" required placeholder="e.g. shoes">
                        <div class="form-text">The URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Save Category</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-generate slug from name as user types
        document.getElementById('name').addEventListener('input', function() {
            var slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
            document.getElementById('slug').value = slug;
        });
    </script>
</body>
</html>
