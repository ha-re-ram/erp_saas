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
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    
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
                $stmtU = $pdo->prepare("UPDATE users SET address = ?, name = ?, phone = ? WHERE id = ?");
                $stmtU->execute([$address, $name, $phone, $customer_id]);
            } else {
                // Create new customer account
                $hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // dummy pass
                $stmtI = $pdo->prepare("INSERT INTO users (store_id, role, name, email, password_hash, address, phone) VALUES (?, 'customer', ?, ?, ?, ?, ?)");
                $stmtI->execute([$store['id'], $name, $email, $hash, $address, $phone]);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout - <?= htmlspecialchars($store['store_name']) ?></title>
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
        .hero {
            background: rgba(15, 23, 42, 0.5);
            padding: 60px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
            margin-bottom: 40px;
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(16px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        .form-control {
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #f8fafc;
            padding: 12px 15px;
            border-radius: 10px;
        }
        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.5);
            border-color: #a855f7;
            color: #f8fafc;
            box-shadow: 0 0 0 0.25rem rgba(168, 85, 247, 0.2);
        }
        .btn-pay {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            border: none;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            transition: all 0.2s ease;
        }
        .btn-pay:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
            color: white;
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1 class="fw-bold fs-2 mb-2" style="background: linear-gradient(135deg, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?= htmlspecialchars($store['store_name']) ?> Checkout</h1>
        <p class="text-secondary mb-0">Secure, encrypted, single-page guest checkout.</p>
    </div>

    <div class="container pb-5" style="max-width: 600px;">
        <?php if($success): ?>
            <div class="glass-panel text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem; filter: drop-shadow(0 0 15px rgba(16,185,129,0.3));"></i>
                <h2 class="fw-bold mt-4 mb-3">Order Confirmed!</h2>
                <p class="text-secondary mb-4">Thank you for shopping at <?= htmlspecialchars($store['store_name']) ?>.<br>Your order ID is <strong class="text-white">#<?= $order_id ?></strong>. We have received your purchase and will start processing it immediately.</p>
                <a href="index.php?store=<?= urlencode($store['subdomain']) ?>" class="btn btn-outline-light rounded-pill px-5">Return to Store</a>
            </div>
        <?php else: ?>
            <div class="glass-panel">
                <h4 class="fw-bold mb-4">Shipping & Details</h4>
                <?php if($error): ?><div class="alert alert-danger" style="background:rgba(239,68,68,0.1); border-color:rgba(239,68,68,0.2); color:#fca5a5;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required placeholder="Jane Doe">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1 (555) 019-2834">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="Receipt will be sent here">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">Full Delivery Address</label>
                        <textarea name="address" class="form-control" required rows="3" placeholder="123 Main St, Seattle, WA 98101"></textarea>
                    </div>

                    <div class="p-3 bg-dark bg-opacity-30 rounded text-center mb-4 border border-secondary border-opacity-10" style="color:#94a3b8;">
                        <i class="bi bi-shield-check-fill fs-3 mb-2 d-block text-success"></i>
                        <small><strong>Demo Transaction Mode.</strong><br>No real financial details will be requested. Submitting simulates secure credit processing in the Store ERP.</small>
                    </div>

                    <button type="submit" class="btn-pay">Pay <?= formatPrice($total_cost) ?> & Place Order</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
