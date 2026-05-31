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
$error = '';
$success = '';

// Handle manual customer registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } else {
        // Check for duplicates within this store
        $stmtC = $pdo->prepare("SELECT id FROM users WHERE email = ? AND store_id = ?");
        $stmtC->execute([$email, $store_id]);
        if ($stmtC->fetch()) {
            $error = "A customer with this email address already exists in your store.";
        } else {
            // Generate a secure dummy password for back-office registration
            $hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $stmtI = $pdo->prepare("INSERT INTO users (store_id, role, name, email, password_hash, phone, address) VALUES (?, 'customer', ?, ?, ?, ?, ?)");
            if ($stmtI->execute([$store_id, $name, $email, $hash, $phone, $address])) {
                $success = "Customer registered successfully!";
            } else {
                $error = "Failed to add customer to database.";
            }
        }
    }
}

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
    <!-- Outfit Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.ajax.ajax.libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
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
        .form-control, .form-select {
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #f8fafc;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(15, 23, 42, 0.5);
            border-color: #a855f7;
            color: #f8fafc;
            box-shadow: 0 0 0 0.25rem rgba(168, 85, 247, 0.2);
        }
        .modal-content-glass {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
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
                        <h1 class="h2 fw-bold mb-1">Customer Directory</h1>
                        <p class="text-secondary mb-0">Manage customer files and view lifetime purchase stats.</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                        <button class="btn btn-primary rounded-pill px-4 btn-sm d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #6366f1, #a855f7); border:none;" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                            <i class="bi bi-person-plus"></i> Add New Customer
                        </button>
                        <form method="GET" class="d-flex" style="max-width: 250px;">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control" placeholder="Search customer..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-dark border border-secondary border-opacity-20" type="submit"><i class="bi bi-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if($success): ?><div class="alert alert-success bg-success bg-opacity-10 text-success border-0 py-2 mb-4"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if($error): ?><div class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 py-2 mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <div class="glass-card">
                    <div class="table-responsive">
                        <table class="table table-glass align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Email Address</th>
                                    <th>Phone Number</th>
                                    <th>Delivery Address</th>
                                    <th class="text-center">Orders Placed</th>
                                    <th>Lifetime Spend</th>
                                    <th>Date Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($customers) > 0): ?>
                                    <?php foreach ($customers as $c): ?>
                                        <tr>
                                            <td class="fw-bold text-white"><?= htmlspecialchars($c['name']) ?></td>
                                            <td><?= htmlspecialchars($c['email']) ?></td>
                                            <td><?= htmlspecialchars($c['phone'] ?? 'N/A') ?></td>
                                            <td class="small" style="max-width: 240px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?= htmlspecialchars($c['address'] ?? 'No address') ?>">
                                                <?= htmlspecialchars($c['address'] ?? 'No address listed') ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary bg-opacity-30 text-light px-2 py-1 rounded"><?= $c['total_orders'] ?></span>
                                            </td>
                                            <td class="fw-bold text-success"><?= formatPrice($c['total_spend']) ?></td>
                                            <td class="small text-secondary"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-secondary">
                                            <i class="bi bi-people fs-1 mb-2 d-block text-secondary" style="opacity: 0.3;"></i>
                                            No customers registered yet. Customers appear here once they register or place orders.
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

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-glass text-white">
                <div class="modal-header border-secondary border-opacity-10">
                    <h5 class="modal-title fw-bold" id="exampleModalLabel"><i class="bi bi-person-plus me-2 text-primary"></i>Add Customer Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Full Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Jane Doe">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Email Address</label>
                            <input type="email" name="email" class="form-control" required placeholder="e.g. jane@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. +1 (555) 012-3456">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Delivery Address</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="e.g. 123 Pine St, Seattle, WA 98101"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary border-opacity-10">
                        <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_customer" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, #6366f1, #a855f7); border:none;">Register Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
