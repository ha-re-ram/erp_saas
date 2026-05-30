<?php
// admin/dashboard/product_edit.php
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

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['id']);

// Fetch active product detail and ensure it belongs to the active tenant
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
$stmt->execute([$product_id, $store_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}

// Fetch all categories from this store to populate the dropdown
$stmtCat = $pdo->prepare("SELECT id, name FROM categories WHERE store_id = :store_id");
$stmtCat->execute(['store_id' => $store_id]);
$categories = $stmtCat->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $low_stock_threshold = intval($_POST['low_stock_threshold']);
    $image_url = trim($_POST['image_url']);

    if (empty($name) || empty($slug) || empty($price)) {
        $error = "Name, slug, and price are required.";
    } else {
        // Ensure slug is unique within this store excluding current product
        $stmtSlug = $pdo->prepare("SELECT id FROM products WHERE store_id = ? AND slug = ? AND id != ?");
        $stmtSlug->execute([$store_id, $slug, $product_id]);
        if ($stmtSlug->fetch()) {
            $error = "A product with this URL slug already exists in your store.";
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, stock_quantity = ?, low_stock_threshold = ?, image_url = ? WHERE id = ? AND store_id = ?");
            if ($stmtUpdate->execute([$category_id, $name, $slug, $description, $price, $stock_quantity, $low_stock_threshold, $image_url, $product_id, $store_id])) {
                header("Location: products.php");
                exit;
            } else {
                $error = "Database error: Failed to update product.";
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
    <title>Edit Product - ERP Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light pb-5">
    <div class="container mt-5" style="max-width: 800px;">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Product</h5>
                <a href="products.php" class="btn btn-sm btn-outline-light">Cancel & Back</a>
            </div>
            
            <div class="card-body p-4 p-md-5">
                <?php if($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <form method="POST">
                    <h6 class="text-secondary fw-bold mb-3 border-bottom pb-2">Basic Info</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Product Name</label>
                            <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Wireless Headphones" value="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Product Slug</label>
                            <input type="text" name="slug" id="slug" class="form-control" required placeholder="e.g. wireless-headphones" value="<?= htmlspecialchars($product['slug']) ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">No Category</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $product['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Retail Price ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required min="0" placeholder="0.00" value="<?= htmlspecialchars($product['price']) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Product Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Enter full specifications and describing traits..."><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>

                    <h6 class="text-secondary fw-bold mb-3 border-bottom pb-2 mt-4">Inventory & Stock</h6>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Current Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" min="0" required value="<?= htmlspecialchars($product['stock_quantity']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Low Stock Warning Threshold</label>
                            <input type="number" name="low_stock_threshold" class="form-control" min="0" required value="<?= htmlspecialchars($product['low_stock_threshold']) ?>">
                        </div>
                    </div>

                    <h6 class="text-secondary fw-bold mb-3 border-bottom pb-2 mt-4">Media</h6>

                    <div class="mb-5">
                        <label class="form-label fw-bold">Image URL</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg" value="<?= htmlspecialchars($product['image_url']) ?>">
                        <div class="form-text">Paste a direct link to a hosted image.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-5">Save Product Changes</button>
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
