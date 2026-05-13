<?php
session_start();
require_once __DIR__ . '/../db/config.php';

// Check admin access
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: login.php");
    exit();
}

// Handle single user actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    if ($_GET['action'] === 'suspend') {
        mysqli_query($conn, "UPDATE users SET status = 'suspended' WHERE id = $user_id AND is_admin = 0");
        mysqli_query($conn, "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES ({$_SESSION['admin_id']}, 'Suspended User', 'User ID: $user_id', '{$_SERVER['REMOTE_ADDR']}')");
        header("Location: users.php?msg=User suspended successfully");
        exit();
    }
    
    if ($_GET['action'] === 'activate') {
        mysqli_query($conn, "UPDATE users SET status = 'active' WHERE id = $user_id");
        mysqli_query($conn, "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES ({$_SESSION['admin_id']}, 'Activated User', 'User ID: $user_id', '{$_SERVER['REMOTE_ADDR']}')");
        header("Location: users.php?msg=User activated successfully");
        exit();
    }
    
    if ($_GET['action'] === 'delete') {
        // Delete all user data
        mysqli_query($conn, "DELETE FROM transactions WHERE user_id = $user_id");
        mysqli_query($conn, "DELETE FROM budgets WHERE user_id = $user_id");
        mysqli_query($conn, "DELETE FROM alerts WHERE user_id = $user_id");
        mysqli_query($conn, "DELETE FROM categories WHERE user_id = $user_id");
        mysqli_query($conn, "DELETE FROM users WHERE id = $user_id AND is_admin = 0");
        mysqli_query($conn, "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES ({$_SESSION['admin_id']}, 'Deleted User', 'User ID: $user_id', '{$_SERVER['REMOTE_ADDR']}')");
        header("Location: users.php?msg=User deleted successfully");
        exit();
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $bulk_action = $_POST['bulk_action'];
    $user_ids = $_POST['user_ids'] ?? [];
    
    if (!empty($user_ids)) {
        foreach ($user_ids as $user_id) {
            $user_id = intval($user_id);
            
            if ($bulk_action === 'activate') {
                mysqli_query($conn, "UPDATE users SET status = 'active' WHERE id = $user_id AND is_admin = 0");
            } elseif ($bulk_action === 'suspend') {
                mysqli_query($conn, "UPDATE users SET status = 'suspended' WHERE id = $user_id AND is_admin = 0");
            } elseif ($bulk_action === 'delete') {
                mysqli_query($conn, "DELETE FROM transactions WHERE user_id = $user_id");
                mysqli_query($conn, "DELETE FROM budgets WHERE user_id = $user_id");
                mysqli_query($conn, "DELETE FROM alerts WHERE user_id = $user_id");
                mysqli_query($conn, "DELETE FROM categories WHERE user_id = $user_id");
                mysqli_query($conn, "DELETE FROM users WHERE id = $user_id AND is_admin = 0");
            }
        }
        
        mysqli_query($conn, "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES ({$_SESSION['admin_id']}, 'Bulk $bulk_action', 'Affected ' . count($user_ids) . ' users', '{$_SERVER['REMOTE_ADDR']}')");
        header("Location: users.php?msg=Bulk action completed successfully");
        exit();
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = "is_admin = 0";
if ($search) {
    $search = mysqli_real_escape_string($conn, $search);
    $where .= " AND (fullname LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($status_filter) {
    $status_filter = mysqli_real_escape_string($conn, $status_filter);
    $where .= " AND status = '$status_filter'";
}

// Get total count
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE $where");
$total_users = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_users / $limit);

// Get users
$users_query = "SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT $offset, $limit";
$users = mysqli_query($conn, $users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="sidebar admin-sidebar">
        <div class="logo">🛡️ Admin Panel</div>
        <nav>
            <a href="index.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="users.php" class="nav-item active"><span>👥</span> Users</a>
            <a href="transactions.php" class="nav-item"><span>💰</span> All Transactions</a>
            <a href="categories.php" class="nav-item"><span>📁</span> Categories</a>
            <a href="reports.php" class="nav-item"><span>📈</span> Reports</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="admin-header">
            <h1>Manage Users</h1>
            <div>Total Users: <?php echo $total_users; ?></div>
        </div>
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-success" style="margin: 20px 0; padding: 12px; border-radius: 8px;">
                ✓ <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="glass-card" style="margin: 20px 0;">
            <form method="GET" class="filter-bar">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
                <button type="submit" class="btn-primary">Filter</button>
                <a href="users.php" class="btn-secondary">Reset</a>
            </form>
        </div>
        
        <!-- Bulk Actions -->
        <div class="glass-card" style="margin: 20px 0;">
            <form method="POST" class="filter-bar">
                <select name="bulk_action" id="bulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate Selected</option>
                    <option value="suspend">Suspend Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn-primary" onclick="return confirm('Are you sure?')">Apply</button>
                <button type="button" class="btn-primary" onclick="exportAdminReport('users')" style="background: #10b981;">Export CSV</button>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="glass-card">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" onclick="selectAllUsers()"></th>
                            <th>ID</th>
                            <th>Avatar</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Last Login</th>
                            <th>Transactions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($users)): 
                            $trans_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM transactions WHERE user_id = {$user['id']}"))['count'];
                        ?>
                        <tr>
                            <td><input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" class="user-checkbox"></td>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <img src="../uploads/<?php echo $user['profile_image'] ?? 'default.png'; ?>" 
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            </td>
                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                            <td><?php echo $trans_count; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button class="btn-icon btn-view" onclick="viewUserDetails(<?php echo $user['id']; ?>)">View</button>
                                <?php if ($user['status'] == 'active'): ?>
                                    <a href="?action=suspend&id=<?php echo $user['id']; ?>" class="btn-icon btn-suspend" onclick="return confirm('Suspend this user?')">Suspend</a>
                                <?php else: ?>
                                    <a href="?action=activate&id=<?php echo $user['id']; ?>" class="btn-icon btn-activate" onclick="return confirm('Activate this user?')">Activate</a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Delete this user? All their data will be lost!')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($users) == 0): ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">No users found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                       class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- User Details Modal -->
    <div id="userDetailsModal" class="admin-modal">
        <div class="admin-modal-content">
            <h2>User Details</h2>
            <div style="margin: 20px 0;">
                <p><strong>Name:</strong> <span id="modalUserName"></span></p>
                <p><strong>Email:</strong> <span id="modalUserEmail"></span></p>
                <p><strong>Registered:</strong> <span id="modalUserRegistered"></span></p>
                <p><strong>Last Login:</strong> <span id="modalUserLastLogin"></span></p>
                <p><strong>Status:</strong> <span id="modalUserStatus"></span></p>
                <hr style="margin: 15px 0; border-color: rgba(255,255,255,0.1);">
                <p><strong>Total Transactions:</strong> <span id="modalTransactionCount"></span></p>
                <p><strong>Total Income:</strong> <span id="modalTotalIncome"></span></p>
                <p><strong>Total Expense:</strong> <span id="modalTotalExpense"></span></p>
            </div>
            <button class="btn-primary" onclick="closeAdminModal('userDetailsModal')">Close</button>
        </div>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="../js/main.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>