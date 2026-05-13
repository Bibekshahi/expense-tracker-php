<?php
require_once __DIR__ . '/../db/config.php';

// Check admin access
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: login.php");
    exit();
}

// Get platform statistics
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_admin = 0"))['count'];
$totalAdmins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_admin = 1"))['count'];
$totalTransactions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM transactions"))['count'];
$totalIncome = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'income'"))['total'] ?? 0;
$totalExpense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'expense'"))['total'] ?? 0;
$totalBalance = $totalIncome - $totalExpense;

// Get recent users
$recentUsers = mysqli_query($conn, "SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 5");

// Get recent transactions
$recentTransactions = mysqli_query($conn, "SELECT t.*, u.fullname as user_name, c.name as category_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    JOIN categories c ON t.category_id = c.id 
    ORDER BY t.created_at DESC 
    LIMIT 10");

// Get monthly signups for chart
$monthlySignups = [];
$monthlyIncome = [];
$monthlyExpense = [];

for ($i = 5; $i >= 0; $i--) {
    $monthName = date('M', strtotime("-$i months"));
    $monthNum = date('m', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    
    $signupCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = $monthNum AND YEAR(created_at) = $year AND is_admin = 0"))['count'];
    $monthlySignups[] = $signupCount;
    
    $incAmount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'income' AND MONTH(transaction_date) = $monthNum AND YEAR(transaction_date) = $year"))['total'] ?? 0;
    $expAmount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'expense' AND MONTH(transaction_date) = $monthNum AND YEAR(transaction_date) = $year"))['total'] ?? 0;
    
    $monthlyIncome[] = $incAmount;
    $monthlyExpense[] = $expAmount;
}

$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];

// Get recent admin logs
$adminLogs = mysqli_query($conn, "SELECT l.*, u.fullname as admin_name 
    FROM admin_logs l 
    JOIN users u ON l.admin_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Expense Tracker</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar admin-sidebar">
        <div class="logo">🛡️ Admin Panel</div>
        <nav>
            <a href="index.php" class="nav-item active"><span>📊</span> Dashboard</a>
            <a href="users.php" class="nav-item"><span>👥</span> Users</a>
            <a href="transactions.php" class="nav-item"><span>💰</span> All Transactions</a>
            <a href="categories.php" class="nav-item"><span>📁</span> Categories</a>
            <a href="reports.php" class="nav-item"><span>📈</span> Reports</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <div>
                <span style="margin-right: 15px;">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="../dashboard.php" target="_blank" class="btn-secondary" style="padding: 8px 16px; text-decoration: none;">View User Site</a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="stat-card-large">
                <div style="font-size: 35px; margin-bottom: 10px;">👥</div>
                <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card-large">
                <div style="font-size: 35px; margin-bottom: 10px;">🛡️</div>
                <div class="stat-number"><?php echo $totalAdmins; ?></div>
                <div class="stat-label">Administrators</div>
            </div>
            <div class="stat-card-large">
                <div style="font-size: 35px; margin-bottom: 10px;">💸</div>
                <div class="stat-number"><?php echo number_format($totalTransactions); ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
            <div class="stat-card-large">
                <div style="font-size: 35px; margin-bottom: 10px;">💰</div>
                <div class="stat-number">Rs <?php echo number_format($totalBalance, 2); ?></div>
                <div class="stat-label">Platform Balance</div>
            </div>
        </div>
        
        <!-- Charts -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px; margin-bottom: 30px;">
            <div class="glass-card">
                <h3>User Signups (Last 6 Months)</h3>
                <canvas id="signupsChart" style="max-height: 300px;"></canvas>
            </div>
            <div class="glass-card">
                <h3>Income vs Expense Trend</h3>
                <canvas id="financeTrendChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
            <div class="glass-card">
                <h3>Platform Financial Summary</h3>
                <canvas id="financeChart" style="max-height: 250px;"></canvas>
            </div>
            <div class="glass-card">
                <h3>Quick Stats</h3>
                <div style="padding: 20px;">
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Total Income</span>
                            <span class="text-success">Rs <?php echo number_format($totalIncome, 2); ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $totalIncome > 0 ? min(100, ($totalIncome / ($totalIncome + $totalExpense)) * 100) : 0; ?>%; background: #22c55e;"></div>
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Total Expense</span>
                            <span class="text-danger">Rs <?php echo number_format($totalExpense, 2); ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $totalExpense > 0 ? min(100, ($totalExpense / ($totalIncome + $totalExpense)) * 100) : 0; ?>%; background: #ef4444;"></div>
                        </div>
                    </div>
                    <hr style="margin: 15px 0; border-color: rgba(255,255,255,0.1);">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Savings Rate</span>
                        <span class="text-success"><?php echo $totalIncome > 0 ? round(($totalBalance / $totalIncome) * 100) : 0; ?>%</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="glass-card" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Recent Users</h3>
                <a href="users.php" class="btn-secondary" style="padding: 5px 15px;">View All →</a>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Avatar</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($recentUsers)): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <img src="../uploads/<?php echo $user['profile_image'] ?? 'default.png'; ?>" 
                                     style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                            </td>
                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn-icon btn-edit" style="text-decoration: none;">Edit</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="glass-card" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Recent Transactions (All Users)</h3>
                <a href="transactions.php" class="btn-secondary" style="padding: 5px 15px;">View All →</a>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($trans = mysqli_fetch_assoc($recentTransactions)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trans['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($trans['title']); ?></td>
                            <td><?php echo htmlspecialchars($trans['category_name']); ?></td>
                            <td>
                                <span class="badge <?php echo $trans['type'] == 'income' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($trans['type']); ?>
                                </span>
                            </td>
                            <td class="<?php echo $trans['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $trans['type'] == 'income' ? '+' : '-'; ?> Rs <?php echo number_format($trans['amount'], 2); ?>
                            </td>
                            <td><?php echo $trans['transaction_date']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Admin Activity Log -->
        <div class="glass-card">
            <h3>Recent Admin Activity</h3>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr><th>Admin</th><th>Action</th><th>Details</th><th>Time</th><th>IP Address</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($log = mysqli_fetch_assoc($adminLogs)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['admin_name']); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
                            <td><?php echo $log['ip_address'] ?? '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="../js/main.js"></script>
    <script src="../js/admin.js"></script>
    <script>
        // Signups Chart
        const signupsCtx = document.getElementById('signupsChart').getContext('2d');
        new Chart(signupsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode($monthlySignups); ?>,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { 
                    legend: { labels: { color: '#fff' } }
                },
                scales: { 
                    y: { ticks: { color: '#fff', stepSize: 1 } }, 
                    x: { ticks: { color: '#fff' } } 
                }
            }
        });
        
        // Finance Trend Chart
        const trendCtx = document.getElementById('financeTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [
                    {
                        label: 'Income',
                        data: <?php echo json_encode($monthlyIncome); ?>,
                        backgroundColor: '#22c55e',
                        borderRadius: 8
                    },
                    {
                        label: 'Expense',
                        data: <?php echo json_encode($monthlyExpense); ?>,
                        backgroundColor: '#ef4444',
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { 
                    legend: { labels: { color: '#fff' } }
                },
                scales: { 
                    y: { ticks: { color: '#fff' } }, 
                    x: { ticks: { color: '#fff' } } 
                }
            }
        });
        
        // Finance Pie Chart
        const financeCtx = document.getElementById('financeChart').getContext('2d');
        new Chart(financeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Total Income', 'Total Expense'],
                datasets: [{
                    data: [<?php echo $totalIncome; ?>, <?php echo $totalExpense; ?>],
                    backgroundColor: ['#22c55e', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { 
                    legend: { labels: { color: '#fff', position: 'bottom' } }
                }
            }
        });
    </script>
</body>
</html>