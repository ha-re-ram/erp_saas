<?php
// admin/dashboard/order_details.php
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

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);

// Fetch order and customer details
$stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.id = ? AND o.store_id = ?");
$stmt->execute([$order_id, $store_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Fetch ordered items
$stmtItems = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmtItems->execute([$order_id]);
$items = $stmtItems->fetchAll();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = trim($_POST['status']);
    $payment_status = trim($_POST['payment_status']);
    
    $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $allowed_payment = ['unpaid', 'paid', 'refunded'];
    
    if (in_array($status, $allowed_statuses) && in_array($payment_status, $allowed_payment)) {
        $stmtUpdate = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ? AND store_id = ?");
        if ($stmtUpdate->execute([$status, $payment_status, $order_id, $store_id])) {
            $success = "Order status updated successfully!";
            // Refresh order data
            $order['status'] = $status;
            $order['payment_status'] = $payment_status;
        } else {
            $error = "Failed to update order status.";
        }
    } else {
        $error = "Invalid status selected.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?= $order['id'] ?> - ERP Dashboard</title>
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
        .btn-premium {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
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
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky">
                    <div class="px-4 mb-4">
                        <span class="sidebar-title"><i class="bi bi-cpu-fill me-2"></i>ERPSAAS ERP</span>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-box"></i> Products</a></li>
                        <li class="nav-item"><a class="nav-link" href="categories.php"><i class="bi bi-tags"></i> Categories</a></li>
                        <li class="nav-item"><a class="nav-link active" href="orders.php"><i class="bi bi-cart"></i> Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="customers.php"><i class="bi bi-people"></i> Customers</a></li>
                        <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a></li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-panel">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom border-secondary border-opacity-10">
                    <div>
                        <h1 class="h2 fw-bold mb-1">Order Details #<?= $order['id'] ?></h1>
                        <p class="text-secondary mb-0">Review ordered line items and update shipping/billing workflows.</p>
                    </div>
                    <a href="orders.php" class="btn btn-outline-light rounded-pill px-4 btn-sm"><i class="bi bi-arrow-left me-2"></i>Back to Orders</a>
                </div>

                <?php if($success): ?><div class="alert alert-success bg-success bg-opacity-10 text-success border-0 py-2 mb-4"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if($error): ?><div class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 py-2 mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <div class="row g-4">
                    <!-- Invoice Summary & Items -->
                    <div class="col-lg-8">
                        <div class="glass-card p-4 mb-4">
                            <h5 class="fw-bold mb-4 border-bottom border-secondary border-opacity-10 pb-2 text-white"><i class="bi bi-receipt me-2 text-primary"></i>Order Line Items</h5>
                            <div class="table-responsive">
                                <table class="table table-glass align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Item Details</th>
                                            <th>Unit Price</th>
                                            <th>Quantity</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($item['image_url']): ?>
                                                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="img" width="40" height="40" class="rounded me-3 border border-secondary border-opacity-20" style="object-fit:cover;">
                                                        <?php else: ?>
                                                            <div class="bg-secondary text-white text-center rounded d-flex align-items-center justify-content-center me-3 border border-secondary border-opacity-20" style="width: 40px; height: 40px;"><i class="bi bi-image"></i></div>
                                                        <?php endif; ?>
                                                        <span class="fw-bold text-white"><?= htmlspecialchars($item['product_name']) ?></span>
                                                    </div>
                                                </td>
                                                <td><?= formatPrice($item['price_at_time']) ?></td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td class="text-end fw-bold text-white"><?= formatPrice($item['price_at_time'] * $item['quantity']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="fs-5 fw-bold border-top border-secondary border-opacity-20">
                                            <td colspan="3" class="text-end text-secondary">Grand Total:</td>
                                            <td class="text-end text-success"><?= formatPrice($order['total_amount']) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Customer & Shipping Info -->
                        <div class="glass-card p-4">
                            <h5 class="fw-bold mb-3 border-bottom border-secondary border-opacity-10 pb-2 text-white"><i class="bi bi-truck me-2 text-purple" style="color:#a855f7;"></i>Delivery & Customer Profile</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="text-secondary fw-semibold mb-2">Customer Profile</h6>
                                    <p class="mb-1 text-white"><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                                    <p class="mb-1 text-secondary"><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                                    <p class="mb-0 text-secondary"><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone'] ?? 'Not provided') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-secondary fw-semibold mb-2">Fulfillment Address</h6>
                                    <div class="p-3 rounded border border-secondary border-opacity-10 text-white" style="white-space: pre-wrap; background-color: rgba(15, 23, 42, 0.3);"><?= htmlspecialchars($order['shipping_address']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions & Status Updates -->
                    <div class="col-lg-4">
                        <div class="glass-card p-4 mb-4">
                            <h5 class="fw-bold mb-4 border-bottom border-secondary border-opacity-10 pb-2 text-white"><i class="bi bi-sliders me-2 text-info"></i>Fulfillment Controls</h5>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-secondary">Shipping & Order Status</label>
                                    <select name="status" class="form-select">
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-secondary">Transaction Payment Status</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="unpaid" <?= $order['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                        <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                    </select>
                                </div>

                                <button type="submit" name="update_status" class="btn-premium w-100 py-3 d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-save"></i> Save Fulfillment State
                                </button>
                            </form>
                        </div>

                        <div class="glass-card p-4 text-center border-start border-4 border-info">
                            <div class="d-flex align-items-center justify-content-center mb-2 text-info gap-2">
                                <i class="bi bi-clock-history fs-4"></i>
                                <span class="fw-bold">Order Chronology</span>
                            </div>
                            <small class="text-secondary d-block mb-1">Placed on: <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></small>
                            <span class="badge bg-secondary bg-opacity-20 text-secondary border border-secondary border-opacity-10 py-2 px-3 mt-2 rounded-pill">System Auto-Generated Invoice</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
