<?php
// admin/dashboard/reports.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

$store_id = $_SESSION['store_id'];

// --- METRIC 1: Lifetime Revenue & Total Orders
$stmt = $pdo->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE store_id = :store_id AND status != 'cancelled'");
$stmt->execute(['store_id' => $store_id]);
$salesData = $stmt->fetch();
$total_revenue = $salesData['total_revenue'];
$total_orders = $salesData['total_orders'];
$aov = $total_orders > 0 ? ($total_revenue / $total_orders) : 0;

// --- METRIC 2: Month-over-Month Revenue Charting (Aggregated from DB)
$stmtMonthly = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month_label, SUM(total_amount) as monthly_revenue 
    FROM orders 
    WHERE store_id = :store_id AND status != 'cancelled'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
    LIMIT 6
");
$stmtMonthly->execute(['store_id' => $store_id]);
$monthlyData = $stmtMonthly->fetchAll();

$month_labels = [];
$monthly_revenues = [];
foreach ($monthlyData as $row) {
    $month_labels[] = $row['month_label'];
    $monthly_revenues[] = floatval($row['monthly_revenue']);
}

// Fallback if no sales data exists
if (empty($month_labels)) {
    $month_labels = ['No Data'];
    $monthly_revenues = [0];
}

// --- METRIC 3: Sales by Category (Aggregated from DB)
$stmtCatSales = $pdo->prepare("
    SELECT c.name as category_name, SUM(oi.quantity * oi.price_at_time) as category_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.store_id = :store_id AND o.status != 'cancelled'
    GROUP BY c.id
    ORDER BY category_revenue DESC
");
$stmtCatSales->execute(['store_id' => $store_id]);
$categorySales = $stmtCatSales->fetchAll();

$category_labels = [];
$category_revenues = [];
foreach ($categorySales as $row) {
    $category_labels[] = $row['category_name'];
    $category_revenues[] = floatval($row['category_revenue']);
}

// --- METRIC 4: Low Stock Inventory List
$stmtLowStock = $pdo->prepare("
    SELECT id, name, stock_quantity, low_stock_threshold 
    FROM products 
    WHERE store_id = :store_id AND stock_quantity <= low_stock_threshold
    ORDER BY stock_quantity ASC
");
$stmtLowStock->execute(['store_id' => $store_id]);
$lowStockProducts = $stmtLowStock->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Business Analytics - ERP Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .sidebar { height: 100vh; background-color: #343a40; color: white; padding-top: 20px;}
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .kpi-card { border: none; border-radius: 16px; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .chart-card { border: none; border-radius: 16px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky">
                    <h5 class="px-3 mb-4 text-white">ERP system</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
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
                            <a class="nav-link active" href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4 pb-5">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4 border-bottom">
                    <h1 class="h2">Reports & Business Analytics</h1>
                </div>

                <!-- KPI metric cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="kpi-card p-4 d-flex align-items-center border-start border-4 border-success">
                            <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle me-3">
                                <i class="bi bi-currency-dollar fs-3"></i>
                            </div>
                            <div>
                                <small class="text-secondary fw-semibold">Lifetime Revenue</small>
                                <h3 class="mb-0 fw-bold"><?= formatPrice($total_revenue) ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="kpi-card p-4 d-flex align-items-center border-start border-4 border-primary">
                            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle me-3">
                                <i class="bi bi-calculator fs-3"></i>
                            </div>
                            <div>
                                <small class="text-secondary fw-semibold">Average Order Value (AOV)</small>
                                <h3 class="mb-0 fw-bold"><?= formatPrice($aov) ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="kpi-card p-4 d-flex align-items-center border-start border-4 border-danger">
                            <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle me-3">
                                <i class="bi bi-exclamation-triangle fs-3"></i>
                            </div>
                            <div>
                                <small class="text-secondary fw-semibold">Low Stock Products</small>
                                <h3 class="mb-0 fw-bold"><?= count($lowStockProducts) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphic charts -->
                <div class="row g-4 mb-4">
                    <!-- Revenue month over month -->
                    <div class="col-lg-8">
                        <div class="chart-card p-4">
                            <h5 class="fw-bold text-dark mb-4">Revenue Velocity (MoM)</h5>
                            <canvas id="momRevenueChart" style="height: 300px;"></canvas>
                        </div>
                    </div>

                    <!-- Category sales distribution -->
                    <div class="col-lg-4">
                        <div class="chart-card p-4">
                            <h5 class="fw-bold text-dark mb-4">Sales by Category</h5>
                            <?php if (empty($category_labels)): ?>
                                <div class="text-center py-5 text-secondary">
                                    <i class="bi bi-pie-chart fs-1 mb-2 d-block"></i>
                                    No sales registered yet.
                                </div>
                            <?php else: ?>
                                <canvas id="categorySalesChart" style="height: 300px;"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Low stock notification center -->
                <div class="card chart-card p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam me-2 text-danger"></i>Inventory Alert Center</h5>
                    <?php if (count($lowStockProducts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock Quantity</th>
                                        <th>Low Stock Limit</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $prod): ?>
                                        <tr>
                                            <td class="fw-bold text-danger"><?= htmlspecialchars($prod['name']) ?></td>
                                            <td>
                                                <span class="badge bg-danger fs-6"><?= $prod['stock_quantity'] ?></span>
                                            </td>
                                            <td class="text-secondary"><?= $prod['low_stock_threshold'] ?></td>
                                            <td>
                                                <a href="product_edit.php?id=<?= $prod['id'] ?>" class="btn btn-sm btn-outline-dark">Restock / Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success d-flex align-items-center mb-0 mt-2" role="alert">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                            <div>All products are safely above their low-stock safety thresholds. Perfect inventory state!</div>
                        </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Month over Month Revenue line chart
        const ctxMom = document.getElementById('momRevenueChart').getContext('2d');
        const momChart = new Chart(ctxMom, {
            type: 'line',
            data: {
                labels: <?= json_encode($month_labels) ?>,
                datasets: [{
                    label: 'Monthly Revenue ($)',
                    data: <?= json_encode($monthly_revenues) ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        <?php if (!empty($category_labels)): ?>
        // Category Sales Doughnut Chart
        const ctxCat = document.getElementById('categorySalesChart').getContext('2d');
        const catChart = new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($category_labels) ?>,
                datasets: [{
                    data: <?= json_encode($category_revenues) ?>,
                    backgroundColor: [
                        '#8b5cf6', // Violet
                        '#ec4899', // Pink
                        '#10b981', // Emerald
                        '#3b82f6', // Blue
                        '#f59e0b', // Amber
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
