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

// Fetch products for the store (with filtering & search)
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.store_id = :store_id";

$params = ['store_id' => $store['id']];

if (!empty($category_filter)) {
    $query .= " AND c.slug = :category_slug";
    $params['category_slug'] = $category_filter;
}

if (!empty($search_query)) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params['search'] = '%' . $search_query . '%';
}

$query .= " ORDER BY p.created_at DESC LIMIT 12";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
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
    
    // Maintain query params for seamless reload
    $redirect_url = "?store=" . urlencode($store['subdomain']);
    if (!empty($category_filter)) $redirect_url .= "&category=" . urlencode($category_filter);
    if (!empty($search_query)) $redirect_url .= "&search=" . urlencode($search_query);
    
    header("Location: " . $redirect_url . "#shop");
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
    <title><?= htmlspecialchars($store['store_name']) ?> - Official Premium Store</title>
    <!-- Premium Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0b0f19;
            color: #f1f5f9;
        }
        .navbar {
            background: rgba(15, 23, 42, 0.7) !important;
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 20px 0;
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.6rem;
            color: #f8fafc !important;
            letter-spacing: -0.5px;
        }
        .nav-link {
            font-weight: 500;
            color: #94a3b8 !important;
            transition: color 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: #f8fafc !important;
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            border-radius: 50%;
            padding: 3px 6px;
            font-size: 0.7rem;
            font-weight: bold;
            color: white;
            box-shadow: 0 0 10px rgba(168, 85, 247, 0.4);
        }
        .hero-section {
            background: radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.15) 0%, rgba(11, 15, 25, 0) 60%),
                        linear-gradient(135deg, rgba(30, 41, 59, 0.5) 0%, rgba(15, 23, 42, 0.8) 100%);
            border-radius: 24px;
            padding: 90px 40px;
            margin-top: 40px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.03);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }
        .hero-title {
            font-weight: 800;
            font-size: 4rem;
            letter-spacing: -1.5px;
            margin-bottom: 20px;
            background: linear-gradient(to right, #ffffff 30%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .product-card {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-8px);
            border-color: rgba(168, 85, 247, 0.3);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.3);
        }
        .product-img-wrap {
            height: 240px;
            background-color: rgba(15, 23, 42, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .product-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .product-card:hover .product-img-wrap img {
            transform: scale(1.06);
        }
        .category-badge {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #a855f7;
            margin-bottom: 8px;
            display: inline-block;
        }
        .product-title {
            font-weight: 700;
            font-size: 1.2rem;
            color: #f8fafc;
            margin-bottom: 12px;
        }
        .product-price {
            font-weight: 800;
            font-size: 1.4rem;
            color: #f8fafc;
        }
        .btn-add-cart {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 14px;
            border: none;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            transition: all 0.2s;
        }
        .btn-add-cart:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
            color: white;
        }
        .badge-low-stock {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(239, 68, 68, 0.9);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 5px 10px;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
        }
        footer {
            margin-top: 100px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 60px 0;
            color: #94a3b8;
            background: rgba(15, 23, 42, 0.5);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="?store=<?= urlencode($store['subdomain']) ?>">
                <i class="bi bi-gem text-purple" style="background: linear-gradient(135deg, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                <?= htmlspecialchars($store['store_name']) ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <i class="bi bi-list fs-2 text-white"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="?store=<?= urlencode($store['subdomain']) ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#shop">Collection</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <a href="cart.php?store=<?= urlencode($store['subdomain']) ?>" class="btn btn-link text-white position-relative text-decoration-none p-0 me-2">
                        <i class="bi bi-bag fs-4"></i>
                        <?php if($cart_count > 0): ?>
                            <span class="cart-badge"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="admin/index.php?store=<?= urlencode($store['subdomain']) ?>" class="btn btn-outline-light rounded-pill px-4" style="font-weight: 600; font-size: 0.9rem;">Merchant Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Hero -->
        <div class="hero-section mb-5">
            <h1 class="hero-title">Elevate Your Lifestyle.</h1>
            <p class="lead text-secondary mx-auto mb-4" style="max-width: 600px;">Indulge in a curated selection of state-of-the-art products. Designed with attention to detail and a vision for future-focused style.</p>
            <a href="#shop" class="btn btn-light rounded-pill px-5 py-3 fw-bold shadow-lg">Shop Entire Collection</a>
        </div>

        <!-- Filters & Search -->
        <div class="row align-items-center mb-5 mt-5 pt-3 g-3" id="shop">
            <div class="col-md-4">
                <h3 class="fw-bold mb-0">Featured Products</h3>
                <?php if(!empty($category_filter) || !empty($search_query)): ?>
                    <small class="text-secondary">
                        Showing results for: 
                        <?php if(!empty($category_filter)) echo 'Category "'.htmlspecialchars($category_filter).'"'; ?>
                        <?php if(!empty($category_filter) && !empty($search_query)) echo ' & '; ?>
                        <?php if(!empty($search_query)) echo 'Search "'.htmlspecialchars($search_query).'"'; ?>
                        <a href="?store=<?= urlencode($store['subdomain']) ?>#shop" class="text-decoration-none ms-2" style="color:#a855f7;">Clear filters</a>
                    </small>
                <?php endif; ?>
            </div>
            
            <div class="col-md-8 d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                <!-- Search bar -->
                <form method="GET" class="d-flex" style="max-width: 320px; width: 100%;">
                    <input type="hidden" name="store" value="<?= htmlspecialchars($store['subdomain']) ?>">
                    <?php if(!empty($category_filter)): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control bg-dark border-secondary border-opacity-20 text-white rounded-start" placeholder="Search products..." value="<?= htmlspecialchars($search_query) ?>">
                        <button class="btn btn-purple bg-purple text-white border-0 px-3" type="submit" style="background: linear-gradient(135deg, #6366f1, #a855f7);"><i class="bi bi-search"></i></button>
                    </div>
                </form>

                <!-- Category Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-dark rounded-pill px-4 dropdown-toggle border border-secondary border-opacity-20" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-funnel me-1"></i>Filter Categories
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark shadow-lg border-secondary border-opacity-20">
                        <li><a class="dropdown-item" href="?store=<?= urlencode($store['subdomain']) ?>#shop">All Categories</a></li>
                        <?php foreach($categories as $cat): ?>
                            <li><a class="dropdown-item" href="?store=<?= urlencode($store['subdomain']) ?>&category=<?= urlencode($cat['slug']) ?>#shop"><?= htmlspecialchars($cat['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="row g-4">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="product-card d-flex flex-column">
                            <div class="product-img-wrap">
                                <?php if ($product['stock_quantity'] <= $product['low_stock_threshold']): ?>
                                    <span class="badge-low-stock">Only <?= $product['stock_quantity'] ?> left!</span>
                                <?php endif; ?>
                                
                                <?php if ($product['image_url']): ?>
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <i class="bi bi-image text-muted" style="font-size: 3rem; opacity: 0.2;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                <span class="category-badge"><?= htmlspecialchars($product['category_name'] ?? 'Essentials') ?></span>
                                <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="text-secondary small mb-4" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($product['description'] ?? 'No description available.') ?></p>
                                <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-10">
                                    <span class="product-price"><?= formatPrice($product['price']) ?></span>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-add-cart d-flex align-items-center justify-content-center" title="Add to Cart">
                                            <i class="bi bi-cart-plus me-1"></i> Add
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 py-5 text-center">
                    <div class="p-5 bg-dark rounded-4 shadow-sm border border-secondary border-opacity-10">
                        <i class="bi bi-emoji-frown display-1 text-secondary mb-3 d-block"></i>
                        <h4 class="fw-bold">No Products Found</h4>
                        <p class="text-secondary">We couldn't find any products in this collection matching your criteria.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <h4 class="fw-bold text-white mb-3"><?= htmlspecialchars($store['store_name']) ?></h4>
            <p class="mb-4">Delivering premium quality and design straight to your door.</p>
            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="#" class="text-secondary fs-4"><i class="bi bi-instagram"></i></a>
                <a href="#" class="text-secondary fs-4"><i class="bi bi-twitter"></i></a>
                <a href="#" class="text-secondary fs-4"><i class="bi bi-facebook"></i></a>
            </div>
            <p class="mb-0 small text-secondary">&copy; <?= date('Y') ?> <?= htmlspecialchars($store['store_name']) ?>. Powered by <strong>ERPSAAS</strong>.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
