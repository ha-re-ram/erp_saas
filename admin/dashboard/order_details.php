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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .sidebar { height: 100vh; background-color: #343a40; color: white; padding-top: 20px;}
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .detail-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky">
                    <h5 class="px-3 mb-4 text-white">ERP system</h5>
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

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4 pb-5">
                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">Order Details #<?= $order['id'] ?></h1>
                    <a href="orders.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back to Orders</a>
                </div>

                <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <div class="row g-4">
                    <!-- Invoice Summary & Items -->
                    <div class="col-lg-8">
                        <div class="detail-card p-4 mb-4">
                            <h5 class="fw-bold mb-4 border-bottom pb-2">Order Line Items</h5>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Price</th>
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
                                                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="img" width="40" height="40" class="rounded me-3">
                                                        <?php else: ?>
                                                            <div class="bg-secondary text-white text-center rounded d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;"><i class="bi bi-image"></i></div>
                                                        <?php endif; ?>
                                                        <span class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></span>
                                                    </div>
                                                </td>
                                                <td><?= formatPrice($item['price_at_time']) ?></td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td class="text-end fw-bold"><?= formatPrice($item['price_at_time'] * $item['quantity']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="fs-5 fw-bold border-top">
                                            <td colspan="3" class="text-end">Grand Total:</td>
                                            <td class="text-end text-success"><?= formatPrice($order['total_amount']) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Customer & Shipping Info -->
                        <div class="detail-card p-4">
                            <h5 class="fw-bold mb-3 border-bottom pb-2">Delivery & Customer Profile</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="text-secondary fw-semibold">Customer Information</h6>
                                    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                                    <p class="mb-0"><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone'] ?? 'Not provided') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-secondary fw-semibold">Shipping Address</h6>
                                    <p class="mb-0 bg-light p-3 rounded border" style="white-space: pre-wrap;"><?= htmlspecialchars($order['shipping_address']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions & Status Updates -->
                    <div class="col-lg-4">
                        <div class="detail-card p-4 mb-4">
                            <h5 class="fw-bold mb-4 border-bottom pb-2">Fulfillment Control</h5>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Order Status</label>
                                    <select name="status" class="form-select">
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Payment Status</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="unpaid" <?= $order['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                        <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                    </select>
                                </div>

                                <button type="submit" name="update_status" class="btn btn-primary w-100 py-2 fw-semibold">
                                    <i class="bi bi-save me-2"></i>Update Order State
                                </button>
                            </form>
                        </div>

                        <div class="detail-card p-4 text-center border-start border-4 border-info">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <i class="bi bi-clock-history fs-3 text-info me-2"></i>
                                <span class="fw-bold text-dark">Order Chronology</span>
                            </div>
                            <small class="text-secondary d-block mb-1">Placed on: <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></small>
                            <span class="badge bg-secondary">System Auto-Generated Invoice</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
