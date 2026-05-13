<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$type_filter = $_GET['type'] ?? '';
$user_filter = $_GET['user'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = "1=1";
if ($type_filter) {
    $where .= " AND t.type = '$type_filter'";
}
if ($user_filter) {
    $where .= " AND u.id = " . intval($user_filter);
}
if ($search) {
    $search = mysqli_real_escape_string($conn, $search);
    $where .= " AND (t.title LIKE '%$search%' OR u.fullname LIKE '%$search%')";
}

// Get total count
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions t JOIN users u ON t.user_id = u.id WHERE $where");
$total_transactions = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_transactions / $limit);

// Get transactions
$transactions_query = "SELECT t.*, u.fullname as user_name, c.name as category_name, c.icon 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    JOIN categories c ON t.category_id = c.id 
    WHERE $where 
    ORDER BY t.created_at DESC 
    LIMIT $offset, $limit";
$transactions = mysqli_query($conn, $transactions_query);

// Get users for filter dropdown
$users = mysqli_query($conn, "SELECT id, fullname FROM users WHERE is_admin = 0 ORDER BY fullname");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Transactions - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="sidebar admin-sidebar">
        <div class="logo">🛡️ Admin Panel</div>
        <nav>
            <a href="index.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="users.php" class="nav-item"><span>👥</span> Users</a>
            <a href="transactions.php" class="nav-item active"><span>💰</span> All Transactions</a>
            <a href="categories.php" class="nav-item"><span>📁</span> Categories</a>
            <a href="reports.php" class="nav-item"><span>📈</span> Reports</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="admin-header">
            <h1>All Transactions</h1>
            <div>Total: <?php echo $total_transactions; ?> transactions</div>
        </div>
        
        <!-- Filters -->
        <div class="glass-card" style="margin: 20px 0;">
            <form method="GET" class="filter-bar">
                <input type="text" name="search" placeholder="Search by title or user..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                <select name="type">
                    <option value="">All Types</option>
                    <option value="income" <?php echo $type_filter == 'income' ? 'selected' : ''; ?>>Income</option>
                    <option value="expense" <?php echo $type_filter == 'expense' ? 'selected' : ''; ?>>Expense</option>
                </select>
                <select name="user">
                    <option value="">All Users</option>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['fullname']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn-primary">Filter</button>
                <a href="transactions.php" class="btn-secondary">Reset</a>
                <button type="button" class="btn-primary" onclick="exportAdminReport('transactions')" style="background: #10b981;">Export CSV</button>
            </form>
        </div>
        
        <!-- Transactions Table -->
        <div class="glass-card">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($trans = mysqli_fetch_assoc($transactions)): ?>
                        <tr>
                            <td><?php echo $trans['id']; ?></td>
                            <td><?php echo htmlspecialchars($trans['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($trans['title']); ?></td>
                            <td><?php echo $trans['icon'] . ' ' . htmlspecialchars($trans['category_name']); ?></td>
                            <td>
                                <span class="badge <?php echo $trans['type'] == 'income' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($trans['type']); ?>
                                </span>
                            </td>
                            <td class="<?php echo $trans['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $trans['type'] == 'income' ? '+' : '-'; ?> Rs <?php echo number_format($trans['amount'], 2); ?>
                            </td>
                            <td><?php echo $trans['transaction_date']; ?></td>
                            <td><?php echo htmlspecialchars(substr($trans['notes'] ?? '', 0, 50)); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($transactions) == 0): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No transactions found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&type=<?php echo urlencode($type_filter); ?>&user=<?php echo urlencode($user_filter); ?>&search=<?php echo urlencode($search); ?>" 
                       class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="../js/main.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>