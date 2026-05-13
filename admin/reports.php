<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: login.php");
    exit();
}

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="admin_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'User', 'Type', 'Category', 'Amount', 'Notes']);
    
    $result = mysqli_query($conn, "SELECT t.transaction_date, u.fullname, t.type, c.name as category, t.amount, t.notes 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date'
        ORDER BY t.transaction_date DESC");
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['transaction_date'],
            $row['fullname'],
            $row['type'],
            $row['category'],
            $row['amount'],
            $row['notes']
        ]);
    }
    fclose($output);
    exit();
}

// Get summary statistics
$summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(DISTINCT u.id) as total_users,
    COUNT(t.id) as total_transactions,
    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
    SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expense
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
    WHERE u.is_admin = 0"));

// Get top users
$top_users = mysqli_query($conn, "SELECT u.fullname, COUNT(t.id) as trans_count, SUM(t.amount) as total_amount
    FROM users u
    JOIN transactions t ON u.id = t.user_id
    WHERE u.is_admin = 0 AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY u.id
    ORDER BY trans_count DESC
    LIMIT 10");

// Get category statistics
$category_stats = mysqli_query($conn, "SELECT c.name, c.icon, COUNT(t.id) as usage_count, SUM(t.amount) as total_amount
    FROM categories c
    JOIN transactions t ON c.id = t.category_id
    WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY c.id
    ORDER BY usage_count DESC
    LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - Smart Expense Tracker</title>
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
            <a href="categories.php" class="nav-item"><span>📁</span> Categories</a>
            <a href="reports.php" class="nav-item active"><span>📈</span> Reports</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="admin-header">
            <h1>Platform Reports</h1>
            <div>System-wide analytics</div>
        </div>
        
        <!-- Date Range Filter -->
        <div class="glass-card" style="margin: 20px 0;">
            <form method="GET" class="filter-bar">
                <label>From:</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                <label>To:</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                <button type="submit" class="btn-primary">Generate Report</button>
                <a href="reports.php" class="btn-secondary">Reset</a>
                <a href="?export=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn-primary" style="background: #10b981;">Export CSV</a>
            </form>
        </div>
        
        <!-- Summary Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="stat-card-large">
                <div style="font-size: 30px;">👥</div>
                <div class="stat-number"><?php echo $summary['total_users']; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card-large">
                <div style="font-size: 30px;">💸</div>
                <div class="stat-number"><?php echo number_format($summary['total_transactions']); ?></div>
                <div class="stat-label">Transactions</div>
            </div>
            <div class="stat-card-large">
                <div style="font-size: 30px;">💰</div>
                <div class="stat-number">Rs <?php echo number_format($summary['total_income'], 2); ?></div>
                <div class="stat-label">Total Income</div>
            </div>
            <div class="stat-card-large">
                <div style="font-size: 30px;">💳</div>
                <div class="stat-number">Rs <?php echo number_format($summary['total_expense'], 2); ?></div>
                <div class="stat-label">Total Expense</div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
            <!-- Top Users -->
            <div class="glass-card">
                <h3>Top Active Users (by transactions)</h3>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Transactions</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($top_users) > 0): ?>
                                <?php while ($user = mysqli_fetch_assoc($top_users)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                    <td><?php echo $user['trans_count']; ?></td>
                                    <td>Rs <?php echo number_format($user['total_amount'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align: center;">No data available</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Popular Categories -->
            <div class="glass-card">
                <h3>Most Used Categories</h3>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Usage Count</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($category_stats) > 0): ?>
                                <?php while ($cat = mysqli_fetch_assoc($category_stats)): ?>
                                <tr>
                                    <td><?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?></td>
                                    <td><?php echo $cat['usage_count']; ?> times</td>
                                    <td>Rs <?php echo number_format($cat['total_amount'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align: center;">No data available</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Net Profit/Loss Card -->
        <div class="glass-card" style="margin-top: 30px;">
            <h3>Financial Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; padding: 20px;">
                <div class="text-center">
                    <div style="font-size: 14px; color: #94a3b8;">Total Income</div>
                    <div class="text-success" style="font-size: 24px; font-weight: bold;">Rs <?php echo number_format($summary['total_income'], 2); ?></div>
                </div>
                <div class="text-center">
                    <div style="font-size: 14px; color: #94a3b8;">Total Expense</div>
                    <div class="text-danger" style="font-size: 24px; font-weight: bold;">Rs <?php echo number_format($summary['total_expense'], 2); ?></div>
                </div>
                <div class="text-center">
                    <div style="font-size: 14px; color: #94a3b8;">Net Profit/Loss</div>
                    <div class="<?php echo ($summary['total_income'] - $summary['total_expense']) >= 0 ? 'text-success' : 'text-danger'; ?>" style="font-size: 24px; font-weight: bold;">
                        Rs <?php echo number_format($summary['total_income'] - $summary['total_expense'], 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="../js/main.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>