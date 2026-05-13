<?php require_once 'db/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Smart Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 450px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 60px; margin-bottom: 10px;">🔐</div>
            <h1>Forgot Password?</h1>
            <p style="color: #94a3b8;">Enter your email to reset password</p>
        </div>
        
        <form id="forgotForm">
            <div style="margin-bottom: 20px;">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            <button type="submit" class="btn-primary" style="width: 100%;">Send Reset Link</button>
            <p style="text-align: center; margin-top: 20px;"><a href="login.php" style="color: #4f46e5;">Back to Login</a></p>
        </form>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="js/main.js"></script>
    <script>
        document.getElementById('forgotForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'forgot');
            
            const response = await fetch('api/auth.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                alert('Password reset link sent to your email!');
                window.location.href = 'login.php';
            } else {
                alert(data.error || 'Error sending reset link');
            }
        });
    </script>
</body>
</html>