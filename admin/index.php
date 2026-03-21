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
    <title>Admin Login - ERP Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-box { width: 100%; max-width: 400px; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="login-box">
        <h4 class="mb-4 text-center">Store Admin Login</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger p-2 text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Store Slug (e.g. electronics)</label>
                <input type="text" name="subdomain" class="form-control" required placeholder="mystore" value="<?= isset($_GET['store']) ? htmlspecialchars($_GET['store']) : '' ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" required placeholder="admin@electronics.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="password"> <!-- default demo pass: password -->
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>
</html>
