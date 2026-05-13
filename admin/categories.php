<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: login.php");
    exit();
}

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $icon = mysqli_real_escape_string($conn, $_POST['icon']);
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        
        mysqli_query($conn, "INSERT INTO categories (user_id, name, icon, type) VALUES (NULL, '$name', '$icon', '$type')");
        mysqli_query($conn, "INSERT INTO admin_logs (admin_id, action, details) VALUES ({$_SESSION['admin_id']}, 'Added Category', 'Category: $name')");
        header("Location: categories.php?msg=Category added");
        exit();
    }
    
    if (isset($_POST['delete_category'])) {
        $id = intval($_POST['category_id']);
        mysqli_query($conn, "DELETE FROM categories WHERE id = $id AND user_id IS NULL");
        mysqli_query($conn, "INSERT INTO admin_logs (admin_id, action, details) VALUES ({$_SESSION['admin_id']}, 'Deleted Category', 'Category ID: $id')");
        header("Location: categories.php?msg=Category deleted");
        exit();
    }
}

// Get all default categories
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id IS NULL ORDER BY type, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="sidebar admin-sidebar">
        <div class="logo">🛡️ Admin Panel</div>
        <nav>
            <a href="index.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="users.php" class="nav-item"><span>👥</span> Users</a>
            <a href="transactions.php" class="nav-item"><span>💰</span> All Transactions</a>
            <a href="categories.php" class="nav-item active"><span>📁</span> Categories</a>
            <a href="reports.php" class="nav-item"><span>📈</span> Reports</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="admin-header">
            <h1>Manage Default Categories</h1>
            <div>Categories available to all users</div>
        </div>
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-success" style="margin: 20px 0; padding: 12px;">✓ <?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
            <!-- Add Category Form -->
            <div class="glass-card">
                <h3>Add New Category</h3>
                <form method="POST" style="margin-top: 20px;">
                    <div class="mb-20">
                        <label>Category Name</label>
                        <input type="text" name="name" required placeholder="e.g., Groceries">
                    </div>
                    <div class="mb-20">
                        <label>Icon (Emoji)</label>
                        <input type="text" name="icon" required placeholder="e.g., 🛒" maxlength="2">
                    </div>
                    <div class="mb-20">
                        <label>Type</label>
                        <select name="type" required>
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>
                    <button type="submit" name="add_category" class="btn-primary">Add Category</button>
                </form>
            </div>
            
            <!-- Categories List -->
            <div class="glass-card">
                <h3>Default Categories</h3>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Icon</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                            <tr>
                                <td style="font-size: 24px;"><?php echo $cat['icon']; ?></td>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $cat['type'] == 'income' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($cat['type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this category?')">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                        <button type="submit" name="delete_category" class="btn-icon btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="../js/main.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>