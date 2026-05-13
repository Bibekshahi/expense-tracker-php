<?php require_once 'db/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 450px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 60px; margin-bottom: 10px;">💰</div>
            <h1 style="font-size: 28px; background: linear-gradient(135deg, #4f46e5, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Smart Expense Tracker</h1>
            <p style="color: #94a3b8; margin-top: 10px;">Login to your account</p>
        </div>
        
        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert-warning" style="margin-bottom: 20px;">Session expired. Please login again.</div>
        <?php endif; ?>
        
        <form id="loginForm">
            <div style="margin-bottom: 20px;">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%;">Login</button>
            
            <p style="text-align: center; margin-top: 20px; color: #94a3b8;">
                Don't have an account? <a href="register.php" style="color: #4f46e5; text-decoration: none;">Register here</a>
            </p>
            <p style="text-align: center; margin-top: 10px;">
                <a href="forgot-password.php" style="color: #4f46e5; text-decoration: none;">Forgot Password?</a>
            </p>
        </form>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="js/main.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'login');
            
            const response = await fetch('api/auth.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                alert(data.errors ? data.errors.join('\n') : 'Login failed');
            }
        });
    </script>
</body>
</html>