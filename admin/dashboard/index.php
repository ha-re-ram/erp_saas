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
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE store_id = :store_id AND status != 'cancelled'");
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

// Monthly sales trend for the chart
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

if (empty($month_labels)) {
    $month_labels = ['No Data'];
    $monthly_revenues = [0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Dashboard - Home</title>
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
            transition: transform 0.2s ease, border-color 0.2s ease;
        }
        .glass-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .card-stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-top: 10px;
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
        .badge-pending { background-color: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .badge-delivered { background-color: rgba(16, 185, 129, 0.15); color: #34d399; }
        .badge-processing { background-color: rgba(59, 130, 246, 0.15); color: #60a5fa; }
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
                        <h1 class="h2 fw-bold mb-1">Dashboard</h1>
                        <p class="text-secondary mb-0">Overview of store analytics and sales metrics.</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                        <?php
                        $stmtStore = $pdo->prepare("SELECT subdomain FROM stores WHERE id = ?");
                        $stmtStore->execute([$_SESSION['store_id']]);
                        $current_store = $stmtStore->fetch();
                        ?>
                        <a href="../../?store=<?= htmlspecialchars($current_store['subdomain']) ?>" target="_blank" class="btn btn-outline-light rounded-pill px-4 btn-sm d-flex align-items-center gap-2">
                            <i class="bi bi-shop"></i> View Live Store
                        </a>
                        <a href="logout.php" class="btn btn-outline-danger rounded-pill px-4 btn-sm d-flex align-items-center gap-2">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="glass-card p-4 border-start border-4 border-success">
                            <small class="text-secondary fw-semibold">Total Revenue</small>
                            <div class="card-stat-value text-success"><?= formatPrice($stats['total_sales']) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card p-4 border-start border-4 border-primary">
                            <small class="text-secondary fw-semibold">Total Orders</small>
                            <div class="card-stat-value text-primary"><?= $stats['total_orders'] ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card p-4 border-start border-4 border-info">
                            <small class="text-secondary fw-semibold">Active Products</small>
                            <div class="card-stat-value text-info"><?= $stats['total_products'] ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card p-4 border-start border-4 border-danger">
                            <small class="text-secondary fw-semibold">Low Stock Warnings</small>
                            <div class="card-stat-value text-danger"><?= $stats['low_stock'] ?></div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Recent Orders -->
                    <div class="col-lg-6">
                        <div class="glass-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold mb-0">Recent Orders</h5>
                                <a href="orders.php" class="text-decoration-none small" style="color:#a855f7;">View All <i class="bi bi-arrow-right"></i></a>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-glass align-middle">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($recentOrders) > 0): ?>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td><a href="order_details.php?id=<?= $order['id'] ?>" class="text-decoration-none fw-semibold" style="color:#cbd5e1;">#<?= $order['id'] ?></a></td>
                                                    <td class="fw-medium"><?= htmlspecialchars($order['customer_name']) ?></td>
                                                    <td class="fw-bold"><?= formatPrice($order['total_amount']) ?></td>
                                                    <td>
                                                        <span class="badge rounded-pill badge-<?= $order['status'] === 'delivered' ? 'delivered' : ($order['status'] === 'pending' ? 'pending' : 'processing') ?>">
                                                            <?= ucfirst($order['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-secondary">No orders found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Trend Chart -->
                    <div class="col-lg-6">
                        <div class="glass-card p-4 h-100">
                            <h5 class="fw-bold mb-4">Revenue Trend (Dynamic)</h5>
                            <div style="height: 250px;">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
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
                labels: <?= json_encode($month_labels) ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?= json_encode($monthly_revenues) ?>,
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderColor: 'rgba(168, 85, 247, 1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
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
    </script>
</body>
</html>
