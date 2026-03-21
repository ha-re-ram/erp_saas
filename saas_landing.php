<?php
// saas_landing.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS ERP - Build Your Online Store</title>
    <!-- Use premium fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0b0f19;
            color: #f1f5f9;
            overflow-x: hidden;
        }
        /* Custom Premium CSS */
        .navbar-brand {
            font-weight: 800;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero {
            position: relative;
            padding: 120px 0 80px;
            text-align: center;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.15) 0%, rgba(11, 15, 25, 0) 70%);
            z-index: -1;
        }
        .hero h1 {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -1px;
        }
        .hero h1 span {
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: 1.25rem;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto 40px;
        }
        .btn-premium {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: #fff;
            border: none;
            padding: 16px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.6);
            color: #fff;
        }
        .feature-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 40px 30px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            border-color: rgba(168, 85, 247, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .feature-card p {
            color: #94a3b8;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent py-4">
        <div class="container">
            <a class="navbar-brand fs-2" href="#">ERPSAAS</a>
            <div class="d-flex ms-auto">
                <a href="admin/index.php" class="btn btn-outline-light rounded-pill px-4 me-3">Login</a>
                <a href="register.php" class="btn btn-premium px-4 py-2">Get Started</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <h1>Create your online store in <span>seconds.</span></h1>
            <p>The powerful multi-tenant SaaS platform that unifies your E-Commerce storefront and back-office ERP into one seamless experience.</p>
            <a href="register.php" class="btn-premium">Create Your Store For Free</a>
        </div>
    </section>

    <section class="container py-5 mb-5">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">✨</div>
                    <h3>Beautiful Storefronts</h3>
                    <p>Launch an optimized e-commerce site on your own subdomain effortlessly to start selling immediately.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Powerful ERP Features</h3>
                    <p>Track your inventory, process customer orders, and manage users within an integrated admin dashboard.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">🚀</div>
                    <h3>Multi-Tenant Scalability</h3>
                    <p>Robust database architecture ensures that your business data is secure, fast, and totally isolated.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="text-center py-4" style="border-top: 1px solid #1e293b; color: #64748b;">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> ERPSAAS. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
