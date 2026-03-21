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
    <title>Shopping Cart - <?= htmlspecialchars($store['store_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .navbar { background: white; padding: 20px 0; border-bottom: 1px solid #e2e8f0; }
        .cart-wrapper { background: white; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        .cart-header { font-weight: 700; color: #0f172a; padding: 25px; border-bottom: 1px solid #e2e8f0; margin:0;}
        .cart-item { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display:flex; align-items:center; }
        .cart-img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; background: #f8fafc; }
        .cart-details { flex-grow: 1; margin-left: 20px; }
        .cart-title { font-weight: 600; font-size: 1.1rem; color: #1e293b; margin-bottom: 4px; }
        .cart-price { font-weight: 700; color: #0f172a; }
        .cart-qty { background:#f1f5f9; padding: 5px 15px; border-radius: 50px; font-weight:600; font-size:0.9rem;}
        .summary-box { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .btn-checkout { background: #0f172a; color: white; padding: 15px; border-radius: 12px; font-weight: 600; width: 100%; display:block; text-align:center; text-decoration:none; transition:0.2s; }
        .btn-checkout:hover { background: #334155; color: white; }
    </style>
</head>
<body>
    <nav class="navbar mb-5">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php?store=<?= urlencode($store['subdomain']) ?>" class="text-dark fw-bold text-decoration-none fs-4">
                <i class="bi bi-arrow-left me-2"></i> Continue Shopping
            </a>
            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($store['store_name']) ?> Checkout</h5>
        </div>
    </nav>

    <div class="container pb-5">
        <h2 class="fw-bold mb-4">Your Shopping Cart</h2>
        
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="cart-wrapper">
                    <h5 class="cart-header">Items (<?= count($cart_items) ?>)</h5>
                    
                    <?php if(empty($cart_items)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-cart-x fs-1 mb-3"></i>
                            <h4>Your cart is empty.</h4>
                            <p>Looks like you haven't added anything to your cart yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($cart_items as $item): ?>
                            <div class="cart-item">
                                <?php if($item['image_url']): ?>
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" class="cart-img" alt="product">
                                <?php else: ?>
                                    <div class="cart-img d-flex align-items-center justify-content-center text-muted fs-3"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                                
                                <div class="cart-details">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="cart-title"><?= htmlspecialchars($item['name']) ?></h5>
                                        <h5 class="cart-price"><?= formatPrice($item['subtotal']) ?></h5>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="text-secondary small">Price: <?= formatPrice($item['price']) ?></div>
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
                <div class="summary-box">
                    <h4 class="fw-bold mb-4">Order Summary</h4>
                    <div class="d-flex justify-content-between mb-3 text-secondary">
                        <span>Subtotal</span>
                        <span><?= formatPrice($total_cost) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 text-secondary">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between mb-4 fs-4 fw-bold">
                        <span>Total</span>
                        <span><?= formatPrice($total_cost) ?></span>
                    </div>
                    
                    <?php if(!empty($cart_items)): ?>
                        <a href="checkout.php?store=<?= urlencode($store['subdomain']) ?>" class="btn-checkout">Secure Checkout <i class="bi bi-lock-fill ms-2"></i></a>
                    <?php else: ?>
                        <button class="btn-checkout opacity-50 pe-none">Checkout Unavailable</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
