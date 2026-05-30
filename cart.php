<?php
// cart.php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$store = getCurrentStore($pdo);
if (!$store) { require_once 'saas_landing.php'; exit; }

$cart_key = 'cart_' . $store['id'];
$cart = isset($_SESSION[$cart_key]) ? $_SESSION[$cart_key] : [];

// Handle remove item
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    if (isset($_SESSION[$cart_key][$remove_id])) {
        unset($_SESSION[$cart_key][$remove_id]);
    }
    header("Location: cart.php?store=" . urlencode($store['subdomain']));
    exit;
}

// Fetch product details for cart items
$cart_items = [];
$total_cost = 0;

if (!empty($cart)) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    // We must also bind the store_id to ensure products actually belong to this store
    $stmt = $pdo->prepare("SELECT id, name, price, image_url FROM products WHERE store_id = ? AND id IN ($placeholders)");
    
    $params = [$store['id']];
    foreach (array_keys($cart) as $id) {
        $params[] = $id;
    }
    
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    foreach ($products as $p) {
        $qty = $cart[$p['id']];
        $subtotal = $p['price'] * $qty;
        $total_cost += $subtotal;
        
        $p['qty'] = $qty;
        $p['subtotal'] = $subtotal;
        $cart_items[] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Bag - <?= htmlspecialchars($store['store_name']) ?></title>
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
        .navbar {
            background: rgba(15, 23, 42, 0.7);
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
        }
        .cart-wrapper {
            background: rgba(30, 41, 59, 0.4);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .cart-header {
            font-weight: 700;
            color: #f8fafc;
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin: 0;
        }
        .cart-item {
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            display: flex;
            align-items: center;
        }
        .cart-img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 16px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .cart-details {
            flex-grow: 1;
            margin-left: 20px;
        }
        .cart-title {
            font-weight: 700;
            font-size: 1.2rem;
            color: #f8fafc;
            margin-bottom: 4px;
        }
        .cart-price {
            font-weight: 800;
            color: #f8fafc;
        }
        .cart-qty {
            background: rgba(255, 255, 255, 0.05);
            padding: 5px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }
        .summary-box {
            background: rgba(30, 41, 59, 0.4);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn-checkout {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            padding: 15px;
            border-radius: 14px;
            font-weight: 700;
            width: 100%;
            display: block;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            border: none;
        }
        .btn-checkout:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar mb-5">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php?store=<?= urlencode($store['subdomain']) ?>" class="text-white fw-bold text-decoration-none fs-5 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Keep Shopping
            </a>
            <h5 class="mb-0 fw-bold" style="background: linear-gradient(135deg, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?= htmlspecialchars($store['store_name']) ?> Checkout</h5>
        </div>
    </nav>

    <div class="container pb-5">
        <h2 class="fw-bold mb-4">Your Shopping Cart</h2>
        
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="cart-wrapper">
                    <h5 class="cart-header">Items (<?= count($cart_items) ?>)</h5>
                    
                    <?php if(empty($cart_items)): ?>
                        <div class="p-5 text-center text-secondary">
                            <i class="bi bi-cart-x fs-1 mb-3 text-secondary" style="opacity: 0.4;"></i>
                            <h4 class="fw-bold text-white">Your cart is empty.</h4>
                            <p>Discover our exclusive products and add some items to your bag.</p>
                            <a href="index.php?store=<?= urlencode($store['subdomain']) ?>" class="btn btn-outline-light rounded-pill px-4 mt-3">Return to Store</a>
                        </div>
                    <?php else: ?>
                        <?php foreach($cart_items as $item): ?>
                            <div class="cart-item">
                                <?php if($item['image_url']): ?>
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" class="cart-img" alt="product">
                                <?php else: ?>
                                    <div class="cart-img d-flex align-items-center justify-content-center text-secondary fs-3"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                                
                                <div class="cart-details">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="cart-title"><?= htmlspecialchars($item['name']) ?></h5>
                                        <h5 class="cart-price"><?= formatPrice($item['subtotal']) ?></h5>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="text-secondary small">Unit Price: <?= formatPrice($item['price']) ?></div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="cart-qty">Qty: <?= $item['qty'] ?></span>
                                            <a href="?store=<?= urlencode($store['subdomain']) ?>&remove=<?= $item['id'] ?>" class="text-danger small text-decoration-none fw-bold">Remove</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="summary-box text-light">
                    <h4 class="fw-bold mb-4">Order Summary</h4>
                    <div class="d-flex justify-content-between mb-3 text-secondary">
                        <span>Subtotal</span>
                        <span><?= formatPrice($total_cost) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 text-secondary">
                        <span>Shipping</span>
                        <span class="badge bg-success bg-opacity-20 text-success">FREE</span>
                    </div>
                    <hr class="my-4 rgba-white-border" style="border-color: rgba(255,255,255,0.05);">
                    <div class="d-flex justify-content-between mb-4 fs-4 fw-bold text-white">
                        <span>Total</span>
                        <span><?= formatPrice($total_cost) ?></span>
                    </div>
                    
                    <?php if(!empty($cart_items)): ?>
                        <a href="checkout.php?store=<?= urlencode($store['subdomain']) ?>" class="btn-checkout">Secure Checkout <i class="bi bi-lock-fill ms-1"></i></a>
                    <?php else: ?>
                        <button class="btn-checkout opacity-50 pe-none" disabled>Checkout Unavailable</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
