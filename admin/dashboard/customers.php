<?php
// admin/dashboard/customers.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

$store_id = $_SESSION['store_id'];

// Get search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query to fetch customers and their lifetime spend
$query = "
    SELECT u.id, u.name, u.email, u.phone, u.address, u.created_at,
           COALESCE(SUM(o.total_amount), 0) as total_spend,
           COUNT(o.id) as total_orders
    FROM users u
    LEFT JOIN orders o ON u.id = o.customer_id AND o.store_id = :store_id_1
    WHERE u.store_id = :store_id_2 AND u.role = 'customer'
";

if (!empty($search)) {
    $query .= " AND (u.name LIKE :search OR u.email LIKE :search)";
}

$query .= " GROUP BY u.id ORDER BY total_spend DESC";

$stmt = $pdo->prepare($query);
$params = [
    'store_id_1' => $store_id,
    'store_id_2' => $store_id
];
if (!empty($search)) {
    $params['search'] = '%' . $search . '%';
}
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Directory - ERP Dashboard</title>
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
                            <a class="nav-link active" href="customers.php"><i class="bi bi-people"></i> Customers</a>
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
                    <h1 class="h2">Customer Directory</h1>
                    
                    <form method="GET" class="d-flex" style="max-width: 300px;">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name/email..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-sm btn-dark" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Delivery Address</th>
                                <th class="text-center">Orders Count</th>
                                <th>Total Spend</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($customers) > 0): ?>
                                <?php foreach ($customers as $c): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                                        <td><?= htmlspecialchars($c['email']) ?></td>
                                        <td><?= htmlspecialchars($c['phone'] ?? 'N/A') ?></td>
                                        <td class="small" style="max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                            <?= htmlspecialchars($c['address'] ?? 'No address listed') ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= $c['total_orders'] ?></span>
                                        </td>
                                        <td class="fw-bold text-success"><?= formatPrice($c['total_spend']) ?></td>
                                        <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No customers found. Customers appear here once they check out.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
