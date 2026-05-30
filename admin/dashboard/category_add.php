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
        $error = "Category Name and URL Slug are required.";
    } else {
        // Ensure slug is unique per store
        $stmtSlug = $pdo->prepare("SELECT id FROM categories WHERE store_id = ? AND slug = ?");
        $stmtSlug->execute([$store_id, $slug]);
        if ($stmtSlug->fetch()) {
            $error = "A category with this URL slug already exists.";
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO categories (store_id, name, slug) VALUES (?, ?, ?)");
            if ($stmtInsert->execute([$store_id, $name, $slug])) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - ERP Dashboard</title>
    <!-- Outfit Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0b0f19;
            color: #f1f5f9;
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            backdrop-filter: blur(16px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        .form-control {
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #f8fafc;
            padding: 12px 15px;
            border-radius: 10px;
        }
        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.5);
            border-color: #a855f7;
            color: #f8fafc;
            box-shadow: 0 0 0 0.25rem rgba(168, 85, 247, 0.2);
        }
        .form-label {
            color: #cbd5e1;
            font-weight: 600;
        }
        .btn-premium {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            transition: all 0.2s ease;
        }
        .btn-premium:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5" style="max-width: 600px;">
        <div class="glass-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-10 pb-3">
                <h4 class="fw-bold mb-0" style="background: linear-gradient(135deg, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><i class="bi bi-tag-fill me-2"></i>Add Category</h4>
                <a href="categories.php" class="btn btn-sm btn-outline-light rounded-pill px-3">Cancel & Back</a>
            </div>

            <?php if($error): ?><div class="alert alert-danger" style="background:rgba(239,68,68,0.1); border-color:rgba(239,68,68,0.2); color:#fca5a5;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Footwear">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">URL Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" required placeholder="e.g. footwear">
                    <div class="form-text text-secondary">The URL-friendly identifier, consisting of letters, digits, and hyphens.</div>
                </div>

                <button type="submit" class="btn-premium w-100 fs-6">Create Collection Category</button>
            </form>
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
