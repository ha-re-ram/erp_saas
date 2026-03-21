<?php
// index.php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$store = getCurrentStore($pdo);

if (!$store) {
    require_once 'saas_landing.php';
    exit;
}

// Fetch categories for filter menu
$stmtCat = $pdo->prepare("SELECT * FROM categories WHERE store_id = :store_id");
$stmtCat->execute(['store_id' => $store['id']]);
$categories = $stmtCat->fetchAll();

// Fetch products for the store
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.store_id = :store_id ORDER BY p.created_at DESC LIMIT 12");
$stmt->execute(['store_id' => $store['id']]);
$products = $stmt->fetchAll();

// Handle add to cart POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    // Initialize cart for this specific store tenant if it doesn't exist
    if (!isset($_SESSION['cart_' . $store['id']])) {
        $_SESSION['cart_' . $store['id']] = [];
    }
    
    // Increment quantity or add new
    if (isset($_SESSION['cart_' . $store['id']][$product_id])) {
        $_SESSION['cart_' . $store['id']][$product_id]++;
    } else {
        $_SESSION['cart_' . $store['id']][$product_id] = 1;
    }
    
    // Refresh to update cart counter without resubmitting form
    header("Location: ?store=" . urlencode($store['subdomain']));
    exit;
}

$cart_count = 0;
if (isset($_SESSION['cart_' . $store['id']])) {
    $cart_count = array_sum($_SESSION['cart_' . $store['id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($store['store_name']) ?> - Official Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 0;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #0f172a !important;
            letter-spacing: -0.5px;
        }
        .nav-link {
            font-weight: 500;
            color: #64748b !important;
            transition: color 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: #0f172a !important;
        }
        .cart-badge {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(50%, -50%);
            background: linear-gradient(135deg, #ef4444, #f97316);
            border-radius: 50%;
            padding: 4px 6px;
            font-size: 0.65rem;
            font-weight: bold;
            color: white;
        }
        .hero-section {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-radius: 20px;
            padding: 80px 40px;
            margin-top: 40px;
            text-align: center;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99,102,241,0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }
        .hero-title {
            font-weight: 800;
            font-size: 3.5rem;
            letter-spacing: -1px;
            margin-bottom: 20px;
            color: #0f172a;
        }
        .product-card {
            background: white;
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        .product-img-wrap {
            height: 220px;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .product-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .product-card:hover .product-img-wrap img {
            transform: scale(1.05);
        }
        .category-badge {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 8px;
            display: inline-block;
        }
        .product-title {
            font-weight: 600;
            font-size: 1.15rem;
            color: #1e293b;
            margin-bottom: 12px;
        }
        .product-price {
            font-weight: 700;
            font-size: 1.3rem;
            color: #0f172a;
        }
        .btn-add-cart {
            background: #0f172a;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            padding: 10px 0;
            border: none;
            transition: background 0.2s;
        }
        .btn-add-cart:hover {
            background: #334155;
            color: white;
        }
        footer {
            margin-top: 100px;
            border-top: 1px solid #e2e8f0;
            padding: 40px 0;
            color: #64748b;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="?store=<?= urlencode($store['subdomain']) ?>"><?= htmlspecialchars($store['store_name']) ?></a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <i class="bi bi-list fs-2 text-dark"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="?store=<?= urlencode($store['subdomain']) ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#shop">Shop All</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">About Us</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="cart.php?store=<?= urlencode($store['subdomain']) ?>" class="btn btn-link text-dark position-relative me-3 text-decoration-none p-0">
                        <i class="bi bi-bag fs-5"></i>
                        <?php if($cart_count > 0): ?>
                            <span class="cart-badge"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="admin/index.php?store=<?= urlencode($store['subdomain']) ?>" class="btn btn-outline-dark rounded-pill px-4 text-decoration-none" style="font-weight: 600; font-size: 0.9rem;">Admin Login</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Hero -->
        <div class="hero-section mb-5">
            <h1 class="hero-title">Elevate Your Everyday.</h1>
            <p class="lead text-secondary mx-auto mb-4" style="max-width: 600px;">Discover our exclusive collection of premium products, carefully curated and designed just for you.</p>
            <a href="#shop" class="btn btn-dark rounded-pill px-5 py-3 fw-bold shadow">Shop the Collection</a>
        </div>

        <!-- Filters (Placeholder visuals) -->
        <div class="d-flex justify-content-between align-items-center mb-4 mt-5 pt-3" id="shop">
            <h3 class="fw-bold mb-0">New Arrivals</h3>
            <div class="dropdown">
                <button class="btn btn-light rounded-pill px-4 dropdown-toggle border" type="button" data-bs-toggle="dropdown">
                    Filter Category
                </button>
                <ul class="dropdown-menu shadow-sm border-0">
                    <li><a class="dropdown-item" href="#">All Categories</a></li>
                    <?php foreach($categories as $cat): ?>
                        <li><a class="dropdown-item" href="#"><?= htmlspecialchars($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="row g-4">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="product-card d-flex flex-column">
                            <div class="product-img-wrap">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <i class="bi bi-image text-muted" style="font-size: 3rem; opacity: 0.2;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                <span class="category-badge"><?= htmlspecialchars($product['category_name'] ?? 'Essentials') ?></span>
                                <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <div class="mt-auto d-flex justify-content-between align-items-center pt-3">
                                    <span class="product-price"><?= formatPrice($product['price']) ?></span>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-add-cart px-3" title="Add to Cart">
                                            <i class="bi bi-bag-plus"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 py-5 text-center">
                    <div class="p-5 bg-white rounded-4 shadow-sm border">
                        <i class="bi bi-emoji-frown display-1 text-muted mb-3 d-block"></i>
                        <h4 class="fw-bold">No Products Yet</h4>
                        <p class="text-secondary">This store hasn't added any inventory to their catalog.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <h4 class="fw-bold text-dark mb-3"><?= htmlspecialchars($store['store_name']) ?></h4>
            <p class="mb-4">Delivering quality and excellence straight to your door.</p>
            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="#" class="text-secondary fs-4"><i class="bi bi-instagram"></i></a>
                <a href="#" class="text-secondary fs-4"><i class="bi bi-twitter"></i></a>
                <a href="#" class="text-secondary fs-4"><i class="bi bi-facebook"></i></a>
            </div>
            <p class="mb-0 small">&copy; <?= date('Y') ?> <?= htmlspecialchars($store['store_name']) ?>. Powered by <strong>ERPSAAS</strong>.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
