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
        .kpi-card {
            background: rgba(30, 41, 59, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 24px;
            backdrop-filter: blur(12px);
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
<body class="pb-5">
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
                    
                    <div class="px-4 mt-5 pt-5 text-center" style="position: absolute; bottom: 20px; width: 100%; left: 0;">
                        <span class="small text-secondary" style="font-size: 0.7rem; opacity: 0.6;">Developed by</span>
                        <a href="https://hareramkushwaha.com.np" target="_blank" class="d-block small text-decoration-none fw-bold mt-1" style="background: linear-gradient(135deg, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 0.78rem;">Hareram Kushwaha</a>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-panel">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom border-secondary border-opacity-10">
                    <div>
                        <h1 class="h2 fw-bold mb-1">Reports & Analytics</h1>
                        <p class="text-secondary mb-0">Aggregate real-time metrics, MoM sales curves, and stock notifications.</p>
                    </div>
                </div>

                <!-- KPI metric cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="kpi-card d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle me-3 border border-success border-opacity-10">
                                <i class="bi bi-currency-dollar fs-3"></i>
                            </div>
                            <div>
                                <small class="text-secondary fw-semibold">Lifetime Revenue</small>
                                <h3 class="mb-0 fw-bold text-white"><?= formatPrice($total_revenue) ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="kpi-card d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle me-3 border border-primary border-opacity-10">
                                <i class="bi bi-calculator fs-3"></i>
                            </div>
                            <div>
                                <small class="text-secondary fw-semibold">Average Order Value (AOV)</small>
                                <h3 class="mb-0 fw-bold text-white"><?= formatPrice($aov) ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="kpi-card d-flex align-items-center">
                            <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle me-3 border border-danger border-opacity-10">
                                <i class="bi bi-exclamation-triangle fs-3"></i>
                            </div>
                            <div>
                                <small class="text-secondary fw-semibold">Low Stock Items</small>
                                <h3 class="mb-0 fw-bold text-white"><?= count($lowStockProducts) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphic charts -->
                <div class="row g-4 mb-4">
                    <!-- Revenue month over month -->
                    <div class="col-lg-8">
                        <div class="glass-card p-4">
                            <h5 class="fw-bold text-white mb-4"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Revenue Velocity (MoM)</h5>
                            <div style="height: 320px;">
                                <canvas id="momRevenueChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Category sales distribution -->
                    <div class="col-lg-4">
                        <div class="glass-card p-4">
                            <h5 class="fw-bold text-white mb-4"><i class="bi bi-pie-chart me-2 text-purple" style="color: #a855f7;"></i>Sales by Category</h5>
                            <div style="height: 320px; position: relative;">
                                <?php if (empty($category_labels)): ?>
                                    <div class="text-center py-5 text-secondary position-absolute top-50 start-50 translate-middle w-100">
                                        <i class="bi bi-pie-chart fs-1 mb-2 d-block text-secondary" style="opacity: 0.3;"></i>
                                        No sales registered yet.
                                    </div>
                                <?php else: ?>
                                    <canvas id="categorySalesChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low stock notification center -->
                <div class="glass-card">
                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-box-seam me-2 text-danger"></i>Inventory Alert Center</h5>
                    <?php if (count($lowStockProducts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-glass align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Current Stock</th>
                                        <th>Low Stock Safety Limit</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $prod): ?>
                                        <tr>
                                            <td class="fw-bold text-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($prod['name']) ?></td>
                                            <td>
                                                <span class="badge bg-danger bg-opacity-25 text-danger px-3 py-2 rounded-pill"><?= $prod['stock_quantity'] ?> left</span>
                                            </td>
                                            <td class="text-secondary"><?= $prod['low_stock_threshold'] ?> items</td>
                                            <td class="text-end">
                                                <a href="product_edit.php?id=<?= $prod['id'] ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-pencil me-1"></i> Edit Stock / Restock</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success bg-success bg-opacity-10 text-success border-0 d-flex align-items-center mb-0 mt-2" role="alert">
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
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderColor: 'rgba(99, 102, 241, 1)',
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
                plugins: {
                    legend: {
                        labels: { color: '#94a3b8', font: { family: 'Outfit' } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8' }
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
                        '#6366f1', // Indigo
                        '#a855f7', // Purple
                        '#ec4899', // Pink
                        '#10b981', // Emerald
                        '#3b82f6', // Blue
                    ],
                    borderWidth: 2,
                    borderColor: '#1e293b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: { color: '#94a3b8', font: { family: 'Outfit' } }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
