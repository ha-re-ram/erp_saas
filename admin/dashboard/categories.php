<?php
// admin/dashboard/categories.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

$store_id = $_SESSION['store_id'];

// Get all categories
$stmt = $pdo->prepare("SELECT * FROM categories WHERE store_id = :store_id");
$stmt->execute(['store_id' => $store_id]);
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Manager - ERP Dashboard</title>
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
        .sidebar {
            height: 100vh;
            background: rgba(30, 41, 59, 0.4);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding-top: 30px;
        }
        .sidebar-title {
            font-weight: 800;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.3rem;
            letter-spacing: -0.5px;
        }
        .sidebar a {
            color: #94a3b8;
            text-decoration: none;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin: 4px 12px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 255, 255, 0.03);
            color: #f8fafc;
            border-left: 3px solid #a855f7;
        }
        .sidebar a.active {
            background: linear-gradient(90deg, rgba(168, 85, 247, 0.1) 0%, rgba(168, 85, 247, 0) 100%);
        }
        .main-panel {
            padding: 30px 40px;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            backdrop-filter: blur(16px);
            padding: 25px;
        }
        .table-glass {
            background: transparent !important;
            color: #f1f5f9 !important;
        }
        .table-glass th {
            background: rgba(255, 255, 255, 0.02) !important;
            color: #94a3b8 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            font-weight: 600;
        }
        .table-glass td {
            background: transparent !important;
            color: #cbd5e1 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04) !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky">
                    <div class="px-4 mb-4">
                        <span class="sidebar-title"><i class="bi bi-cpu-fill me-2"></i>ERPSAAS ERP</span>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php"><i class="bi bi-box"></i> Products</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="categories.php"><i class="bi bi-tags"></i> Categories</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php"><i class="bi bi-cart"></i> Orders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customers.php"><i class="bi bi-people"></i> Customers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-panel">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom border-secondary border-opacity-10">
                    <div>
                        <h1 class="h2 fw-bold mb-1">Categories Manager</h1>
                        <p class="text-secondary mb-0">Organize store items into URL-slugified collections.</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                        <a href="category_add.php" class="btn btn-primary rounded-pill px-4 btn-sm d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #6366f1, #a855f7); border:none;">
                            <i class="bi bi-plus-lg"></i> Add New Category
                        </a>
                        <a href="logout.php" class="btn btn-outline-danger rounded-pill px-4 btn-sm d-flex align-items-center gap-2">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="table-responsive">
                        <table class="table table-glass align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Collection Name</th>
                                    <th>URL Slug</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($categories) > 0): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td>#<?= $cat['id'] ?></td>
                                            <td class="fw-bold text-white"><?= htmlspecialchars($cat['name']) ?></td>
                                            <td class="text-secondary">/<?= htmlspecialchars($cat['slug']) ?></td>
                                            <td class="text-end">
                                                <a href="category_edit.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-light me-1" title="Edit Category"><i class="bi bi-pencil"></i></a>
                                                <a href="category_delete.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category? Products in this category will become uncategorized.');" title="Delete Category"><i class="bi bi-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-secondary">
                                            <i class="bi bi-tags fs-1 mb-2 d-block text-secondary" style="opacity: 0.3;"></i>
                                            No categories exist yet. Get started by adding your first collection!
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
