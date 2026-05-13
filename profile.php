<?php
require_once 'db/config.php';
redirectIfNotLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Smart Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">💰 SmartTracker</div>
        <nav>
            <a href="dashboard.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="transactions.php" class="nav-item"><span>💸</span> Transactions</a>
            <a href="budget.php" class="nav-item"><span>🎯</span> Budget</a>
            <a href="reports.php" class="nav-item"><span>📈</span> Reports</a>
            <a href="profile.php" class="nav-item active"><span>👤</span> Profile</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <h1 style="margin-bottom: 30px;">Profile Settings</h1>
        
        <div class="stats-grid">
            <div class="glass-card">
                <h3>Profile Picture</h3>
                <div class="text-center">
                    <img id="profileImagePreview" class="profile-image" src="uploads/default.png" alt="Profile">
                    <div class="mt-20">
                        <input type="file" id="profileImage" accept="image/*" style="display: none;">
                        <button class="btn-primary" onclick="document.getElementById('profileImage').click()">Upload Photo</button>
                    </div>
                </div>
            </div>
            
            <div class="glass-card">
                <h3>Account Information</h3>
                <form id="profileForm">
                    <div class="mb-20">
                        <label>Full Name</label>
                        <input type="text" name="fullname" id="fullname" required>
                    </div>
                    <div class="mb-20">
                        <label>Email</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    <div class="mb-20">
                        <label>Currency</label>
                        <select name="currency" id="currency">
                            <option value="Rs">Rs NPR (Nepalese Rupee)</option>
                            <option value="₹">₹ INR (Indian Rupee)</option>
                            <option value="$">$ USD (US Dollar)</option>
                            <option value="€">€ EUR (Euro)</option>
                            <option value="£">£ GBP (British Pound)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Update Profile</button>
                </form>
            </div>
            
            <div class="glass-card">
                <h3>Change Password</h3>
                <form id="passwordForm">
                    <div class="mb-20">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="mb-20">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="mb-20">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="js/main.js"></script>
</body>
</html>