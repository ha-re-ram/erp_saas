<?php
// admin/dashboard/product_add.php
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

// Fetch all categories for the store
$stmtCat = $pdo->prepare("SELECT id, name FROM categories WHERE store_id = :store_id");
$stmtCat->execute(['store_id' => $store_id]);
$categories = $stmtCat->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $low_stock_threshold = intval($_POST['low_stock_threshold']);
    $image_url = trim($_POST['image_url']);

    if (empty($name) || empty($slug) || empty($price)) {
        $error = "Name, slug, and price are required.";
    } else {
        // Ensure slug is unique per store
        $stmtSlug = $pdo->prepare("SELECT id FROM products WHERE store_id = ? AND slug = ?");
        $stmtSlug->execute([$store_id, $slug]);
        if ($stmtSlug->fetch()) {
            $error = "A product with this URL slug already exists in your store.";
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO products (store_id, category_id, name, slug, description, price, stock_quantity, low_stock_threshold, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmtInsert->execute([$store_id, $category_id, $name, $slug, $description, $price, $stock_quantity, $low_stock_threshold, $image_url])) {
                header("Location: products.php");
                exit;
            } else {
                $error = "Failed to add product to catalog.";
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
    <title>Add Product - ERP Dashboard</title>
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
        .form-control, .form-select {
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #f8fafc;
            padding: 12px 15px;
            border-radius: 10px;
        }
        .form-control:focus, .form-select:focus {
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
<body class="pb-5">
    <div class="container mt-5" style="max-width: 800px;">
        <div class="glass-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-10 pb-3">
                <h4 class="fw-bold mb-0" style="background: linear-gradient(135deg, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><i class="bi bi-box-seam me-2"></i>Add New Product</h4>
                <a href="products.php" class="btn btn-sm btn-outline-light rounded-pill px-3">Cancel & Back</a>
            </div>

            <?php if($error): ?><div class="alert alert-danger" style="background:rgba(239,68,68,0.1); border-color:rgba(239,68,68,0.2); color:#fca5a5;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="POST">
                <h6 class="text-secondary fw-bold mb-3 border-bottom border-secondary border-opacity-10 pb-2">Basic Specifications</h6>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Wireless Headset">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">URL Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control" required placeholder="e.g. wireless-headset">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">No Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Retail Price ($)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required min="0" placeholder="0.00">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Product Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Describe key highlights, capabilities, and features..."></textarea>
                </div>

                <h6 class="text-secondary fw-bold mb-3 border-bottom border-secondary border-opacity-10 pb-2 mt-4">Fulfillment & Warehousing</h6>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Initial Stock Level</label>
                        <input type="number" name="stock_quantity" class="form-control" min="0" required value="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Low Stock Threshold Limit</label>
                        <input type="number" name="low_stock_threshold" class="form-control" min="0" required value="5">
                    </div>
                </div>

                <h6 class="text-secondary fw-bold mb-3 border-bottom border-secondary border-opacity-10 pb-2 mt-4">Media Assets</h6>

                <div class="mb-5">
                    <label class="form-label">Product Image URL</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/photo-...">
                    <div class="form-text text-secondary">Paste a direct image asset URL.</div>
                </div>

                <button type="submit" class="btn-premium w-100 fs-5">Publish Product to Storefront</button>
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
