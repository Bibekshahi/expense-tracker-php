<?php
require_once __DIR__ . '/../db/config.php';

// Redirect if already logged in as admin
if (isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Check if user exists and is admin
    $result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email' AND is_admin = 1 AND status = 'active'");
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            // Set admin session
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_name'] = $row['fullname'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_last_activity'] = time();
            
            // Update last login
            mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE id = " . $row['id']);
            
            // Log admin login
            mysqli_query($conn, "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES ({$row['id']}, 'Admin Login', '{$_SERVER['REMOTE_ADDR']}')");
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "No admin account found with this email";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Smart Expense Tracker</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="glass-card">
                <div class="admin-badge">
                    <span>🔐 ADMIN PANEL</span>
                </div>
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="font-size: 50px; margin-bottom: 10px;">👨‍💼</div>
                    <h2>Admin Login</h2>
                    <p style="color: #94a3b8;">Secure access to admin dashboard</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert-danger" style="margin-bottom: 20px; padding: 12px; border-radius: 8px;">
                        ⚠️ <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert-warning" style="margin-bottom: 20px; padding: 12px; border-radius: 8px;">
                        ⏰ Session expired. Please login again.
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="admin@example.com" style="width: 100%;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Enter your password" style="width: 100%;">
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%;">Login to Admin Panel</button>
                </form>
                
                <hr style="margin: 25px 0; border-color: rgba(255,255,255,0.1);">
                
                <p style="text-align: center; margin: 0;">
                    <a href="../index.php" style="color: #4f46e5; text-decoration: none;">← Back to User Portal</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>