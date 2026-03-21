<?php
// admin/dashboard/index.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

$store_id = $_SESSION['store_id'];

// Get dashboard statistics
$stats = [
    'total_sales' => 0,
    'total_orders' => 0,
    'total_products' => 0,
    'low_stock' => 0
];

// Orders count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE store_id = :store_id");
$stmt->execute(['store_id' => $store_id]);
$orderStats = $stmt->fetch();
$stats['total_orders'] = $orderStats['cnt'];
$stats['total_sales'] = $orderStats['total'];

// Products count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM products WHERE store_id = :store_id");
$stmt->execute(['store_id' => $store_id]);
$stats['total_products'] = $stmt->fetchColumn();

// Low stock count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM products WHERE store_id = :store_id AND stock_quantity <= low_stock_threshold");
$stmt->execute(['store_id' => $store_id]);
$stats['low_stock'] = $stmt->fetchColumn();

// Recent orders
$stmt = $pdo->prepare("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.store_id = :store_id ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute(['store_id' => $store_id]);
$recentOrders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Dashboard overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .sidebar { height: 100vh; background-color: #343a40; color: white; padding-top: 20px;}
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky">
                    <h5 class="px-3 mb-4 text-white">ERP system</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php"><i class="bi bi-box"></i> Products</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php"><i class="bi bi-tags"></i> Categories</a>
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
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Overview</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php
                        $stmtStore = $pdo->prepare("SELECT subdomain FROM stores WHERE id = ?");
                        $stmtStore->execute([$_SESSION['store_id']]);
                        $current_store = $stmtStore->fetch();
                        ?>
                        <a href="../../?store=<?= htmlspecialchars($current_store['subdomain']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                            <i class="bi bi-shop"></i> View Live Store
                        </a>
                        <a href="logout.php" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Total Sales</h5>
                                <h3 class="card-text"><?= formatPrice($stats['total_sales']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Total Orders</h5>
                                <h3 class="card-text"><?= $stats['total_orders'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Products</h5>
                                <h3 class="card-text"><?= $stats['total_products'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger mb-3 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Low Stock</h5>
                                <h3 class="card-text"><?= $stats['low_stock'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <h4>Recent Orders</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentOrders) > 0): ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= formatPrice($order['total_amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'primary') ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Chart placeholder -->
                <h4 class="mt-5">Sales Analytics</h4>
                <div class="card shadow-sm mb-5">
                    <div class="card-body">
                        <canvas id="salesChart" width="400" height="150"></canvas>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [1200, 1900, 3000, 5000, 2000, 3000],
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
    </script>
</body>
</html>
