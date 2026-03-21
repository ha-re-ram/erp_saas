<?php
// register.php
session_start();
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = trim($_POST['store_name']);
    $subdomain = strtolower(trim($_POST['subdomain']));
    $owner_name = trim($_POST['owner_name']);
    $owner_email = trim($_POST['owner_email']);
    $password = $_POST['password'];

    // Validation
    if (!preg_match('/^[a-z0-9]+$/', $subdomain)) {
        $error = "Store slug can only contain lowercase letters and numbers.";
    } elseif (strlen($subdomain) < 3) {
        $error = "Store slug must be at least 3 characters long.";
    } else {
        // Check uniqueness of subdomain
        $stmt = $pdo->prepare("SELECT id FROM stores WHERE subdomain = ?");
        $stmt->execute([$subdomain]);
        if ($stmt->fetch()) {
            $error = "This store slug is already taken. Please choose another one.";
        } else {
            // Check if email already exists across users? We allow the same email across different stores,
            // but for simplicity, we insert into the new store. 

            $pdo->beginTransaction();
            try {
                // Insert Store
                $stmt = $pdo->prepare("INSERT INTO stores (store_name, subdomain, owner_email) VALUES (?, ?, ?)");
                $stmt->execute([$store_name, $subdomain, $owner_email]);
                $store_id = $pdo->lastInsertId();

                // Insert User (Owner)
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $pdo->prepare("INSERT INTO users (store_id, role, name, email, password_hash) VALUES (?, 'owner', ?, ?, ?)");
                $stmt2->execute([$store_id, $owner_name, $owner_email, $hash]);

                $pdo->commit();
                $success = "Store created successfully!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "An unexpected error occurred: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Business - ERPSAAS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0b0f19;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .container-box {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 550px;
            width: 100%;
            margin: 0 auto;
        }
        h2 {
            font-weight: 800;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
        }
        .form-control {
            background-color: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
            padding: 12px 15px;
            border-radius: 10px;
        }
        .form-control:focus {
            background-color: #0f172a;
            border-color: #a855f7;
            color: #f8fafc;
            box-shadow: 0 0 0 0.25rem rgba(168, 85, 247, 0.25);
        }
        .form-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .input-group-text {
            background-color: #334155;
            border: 1px solid #334155;
            color: #94a3b8;
        }
        .btn-premium {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: #fff;
            border: none;
            padding: 14px 20px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.6);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <a href="index.php" class="btn btn-link text-decoration-none text-secondary mb-3 d-inline-block">&larr; Back to Home</a>
        <div class="container-box">
            <h2>Create Your Store</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="background:#450a0a; color:#fca5a5; border-color:#7f1d1d;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="background:#064e3b; color:#6ee7b7; border-color:#065f46;">
                    <?= htmlspecialchars($success) ?><br><br>
                    <a href="admin/index.php" class="btn btn-success btn-sm w-100">Proceed to Admin Login</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Store Name</label>
                        <input type="text" name="store_name" class="form-control" placeholder="My Awesome Shop" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Store Slug (URL)</label>
                        <div class="input-group">
                            <input type="text" name="subdomain" class="form-control" placeholder="myshop" required>
                            <span class="input-group-text">.erpsaas.com</span>
                        </div>
                        <small class="text-secondary mt-1 d-block">Only lowercase letters and numbers, no spaces.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Your Name</label>
                            <input type="text" name="owner_name" class="form-control" placeholder="John Doe" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="owner_email" class="form-control" placeholder="john@example.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Create a strong password" required minlength="6">
                    </div>

                    <button type="submit" class="btn-premium">Register My Business</button>
                    <div class="text-center mt-4">
                        <p class="text-secondary" style="font-size: 0.9rem;">Already have a store? <a href="admin/index.php" style="color:#a855f7;">Log in here</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
