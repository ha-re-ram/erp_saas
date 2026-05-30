<?php
// admin/index.php
session_start();
require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Allow explicit store slug via POST, else GET, else subdomain
    $subdomain = isset($_POST['subdomain']) && !empty($_POST['subdomain']) ? trim($_POST['subdomain']) : (isset($_GET['store']) ? $_GET['store'] : explode('.', $_SERVER['HTTP_HOST'])[0]);

    // Find the store
    $stmtStore = $pdo->prepare("SELECT id FROM stores WHERE subdomain = :subdomain AND status = 'active'");
    $stmtStore->execute(['subdomain' => $subdomain]);
    $store = $stmtStore->fetch();

    if ($store) {
        // Authenticate User
        $stmt = $pdo->prepare("SELECT id, role, password_hash, name FROM users WHERE email = :email AND store_id = :store_id");
        $stmt->execute(['email' => $email, 'store_id' => $store['id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Check admin role
            if (in_array($user['role'], ['owner', 'admin'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['store_id'] = $store['id'];
                $_SESSION['user_name'] = $user['name'];
                
                header('Location: dashboard/index.php');
                exit;
            } else {
                $error = "Access denied. Only owners/admins can login here.";
            }
        } else {
            $error = "Invalid credentials";
        }
    } else {
        $error = "Store not found or suspended.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Admin Login - ERP Dashboard</title>
    <!-- Outfit Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0b0f19;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            width: 100%;
            max-width: 440px;
            padding: 40px;
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
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
        .form-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .btn-premium {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: #fff;
            border: none;
            padding: 14px 20px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
        }
        .btn-premium:hover {
            transform: translateY(-1px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.5);
            color: #fff;
        }
        .login-title {
            font-weight: 800;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.8rem;
            margin-bottom: 8px;
            text-align: center;
        }
        .login-subtitle {
            color: #64748b;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h4 class="login-title">Merchant Login</h4>
        <p class="login-subtitle">Access your store's back-office ERP dashboard</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger p-2 text-center" style="background:rgba(239,68,68,0.1); border-color:rgba(239,68,68,0.2); color:#fca5a5;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Store Domain Slug</label>
                <input type="text" name="subdomain" class="form-control" required placeholder="electronics" value="<?= isset($_GET['store']) ? htmlspecialchars($_GET['store']) : '' ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Admin Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="admin@electronics.com">
            </div>
            <div class="mb-4">
                <label class="form-label">Secure Password</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-premium">Sign In to Dashboard</button>
        </form>
    </div>
</body>
</html>
