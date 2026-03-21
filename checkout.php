<?php
// checkout.php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$store = getCurrentStore($pdo);
if (!$store) { require_once 'saas_landing.php'; exit; }

$cart_key = 'cart_' . $store['id'];
$cart = isset($_SESSION[$cart_key]) ? $_SESSION[$cart_key] : [];

if (empty($cart)) {
    header("Location: index.php?store=" . urlencode($store['subdomain']));
    exit;
}

// Calculate total cost and gather products
$cart_items = [];
$total_cost = 0;
$placeholders = implode(',', array_fill(0, count($cart), '?'));
$stmt = $pdo->prepare("SELECT id, price FROM products WHERE store_id = ? AND id IN ($placeholders)");
$params = [$store['id']];
foreach (array_keys($cart) as $id) { $params[] = $id; }
$stmt->execute($params);
$products = $stmt->fetchAll();

foreach ($products as $p) {
    $qty = $cart[$p['id']];
    $total_cost += ($p['price'] * $qty);
    $cart_items[$p['id']] = $p['price'];
}

$error = '';
$success = false;
$order_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    
    if(empty($name) || empty($email) || empty($address)) {
        $error = "Please fill in all shipping details.";
    } else {
        $pdo->beginTransaction();
        try {
            // Check if customer email exists in THIS store
            $stmtC = $pdo->prepare("SELECT id FROM users WHERE email = ? AND store_id = ?");
            $stmtC->execute([$email, $store['id']]);
            $customer = $stmtC->fetch();
            
            $customer_id = 0;
            if ($customer) {
                $customer_id = $customer['id'];
                // Update their address
                $stmtU = $pdo->prepare("UPDATE users SET address = ?, name = ? WHERE id = ?");
                $stmtU->execute([$address, $name, $customer_id]);
            } else {
                // Create new customer account
                $hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // dummy pass
                $stmtI = $pdo->prepare("INSERT INTO users (store_id, role, name, email, password_hash, address) VALUES (?, 'customer', ?, ?, ?, ?)");
                $stmtI->execute([$store['id'], $name, $email, $hash, $address]);
                $customer_id = $pdo->lastInsertId();
            }
            
            // Create Order
            $stmtO = $pdo->prepare("INSERT INTO orders (store_id, customer_id, total_amount, shipping_address, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmtO->execute([$store['id'], $customer_id, $total_cost, $address]);
            $order_id = $pdo->lastInsertId();
            
            // Insert Order Items and theoretically reduce stock
            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
            foreach ($cart as $pid => $qty) {
                $price_at_time = $cart_items[$pid];
                $stmtItem->execute([$order_id, $pid, $qty, $price_at_time]);
                
                // Reduce stock
                $stmtStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmtStock->execute([$qty, $pid]);
            }
            
            $pdo->commit();
            
            // Clear cart
            unset($_SESSION[$cart_key]);
            $success = true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Payment/Processing failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - <?= htmlspecialchars($store['store_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .hero { background: white; padding: 60px 0; border-bottom: 1px solid #e2e8f0; text-align: center; margin-bottom: 40px;}
        .glass-panel { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .form-control { padding: 12px 15px; border-radius: 10px; border-color: #cbd5e1; }
        .btn-pay { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px; border-radius: 12px; font-weight: 700; font-size: 1.1rem; width: 100%; border: none; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2); }
        .btn-pay:hover { background: linear-gradient(135deg, #059669, #047857); color: white; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="hero">
        <h1 class="fw-bold fs-2 mb-2"><?= htmlspecialchars($store['store_name']) ?> Checkout</h1>
        <p class="text-secondary mb-0">Secure, encrypted, single-page checkout.</p>
    </div>

    <div class="container pb-5" style="max-width: 600px;">
        <?php if($success): ?>
            <div class="glass-panel text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                <h2 class="fw-bold mt-4 mb-3">Order Confirmed!</h2>
                <p class="text-secondary mb-4">Thank you for shopping at <?= htmlspecialchars($store['store_name']) ?>.<br>Your order ID is <strong>#<?= $order_id ?></strong>. We have received your purchase and will start processing it immediately.</p>
                <a href="index.php?store=<?= urlencode($store['subdomain']) ?>" class="btn btn-outline-dark rounded-pill px-4">Return to Store</a>
            </div>
        <?php else: ?>
            <div class="glass-panel">
                <h4 class="fw-bold mb-4">Shipping & Details</h4>
                <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="Jane Doe">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="Receipt will be sent here">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Full Delivery Address</label>
                        <textarea name="address" class="form-control" required rows="3" placeholder="123 Main St, Apartment 4B, New York, NY 10001"></textarea>
                    </div>

                    <div class="p-3 bg-light rounded text-center mb-4 border" style="color:#64748b;">
                        <i class="bi bi-credit-card-fill fs-3 mb-2 d-block"></i>
                        <small><strong>Demo Mode Active.</strong><br>No actual payment is collected. Any data entered simulates a real transaction hitting the ERP.</small>
                    </div>

                    <button type="submit" class="btn-pay">Pay <?= formatPrice($total_cost) ?> & Place Order</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
