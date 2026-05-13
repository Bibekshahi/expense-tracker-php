<?php require_once 'db/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 500px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 60px; margin-bottom: 10px;">📝</div>
            <h1 style="font-size: 28px; background: linear-gradient(135deg, #4f46e5, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Create Account</h1>
            <p style="color: #94a3b8;">Start managing your finances today</p>
        </div>
        
        <form id="registerForm">
            <div style="margin-bottom: 15px;">
                <label>Full Name</label>
                <input type="text" name="fullname" required placeholder="Enter your full name">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters">
                <small style="color: #94a3b8;">Password must be at least 6 characters</small>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Confirm your password">
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%;">Register</button>
            
            <p style="text-align: center; margin-top: 20px; color: #94a3b8;">
                Already have an account? <a href="login.php" style="color: #4f46e5; text-decoration: none;">Login here</a>
            </p>
        </form>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="js/main.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'register');
            
            const response = await fetch('api/auth.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                alert('Registration successful! Please login.');
                window.location.href = 'login.php';
            } else {
                alert(data.errors ? data.errors.join('\n') : 'Registration failed');
            }
        });
    </script>
</body>
</html>