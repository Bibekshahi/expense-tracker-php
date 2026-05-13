<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db/config.php';

// Check if user is admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION['admin_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get user details
if ($action === 'getUserDetails') {
    $user_id = intval($_GET['id']);
    
    $userResult = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($userResult);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }
    
    $transactionCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM transactions WHERE user_id = $user_id"))['count'];
    $totalIncome = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE user_id = $user_id AND type = 'income'"))['total'] ?? 0;
    $totalExpense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE user_id = $user_id AND type = 'expense'"))['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'transaction_count' => $transactionCount,
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense
    ]);
}

// Export users
else if ($action === 'exportUsers' && $_GET['format'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="all_users_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Full Name', 'Email', 'Registered Date', 'Last Login', 'Status', 'Transaction Count']);
    
    $result = mysqli_query($conn, "SELECT u.*, COUNT(t.id) as trans_count 
        FROM users u 
        LEFT JOIN transactions t ON u.id = t.user_id 
        WHERE u.is_admin = 0 
        GROUP BY u.id 
        ORDER BY u.created_at DESC");
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            $row['fullname'],
            $row['email'],
            $row['created_at'],
            $row['last_login'] ?? 'Never',
            $row['status'],
            $row['trans_count']
        ]);
    }
    fclose($output);
}

// Export all transactions
else if ($action === 'exportTransactions' && $_GET['format'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="all_transactions_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Transaction ID', 'User', 'Title', 'Category', 'Type', 'Amount', 'Date', 'Notes']);
    
    $result = mysqli_query($conn, "SELECT t.*, u.fullname as user_name, c.name as category_name 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        JOIN categories c ON t.category_id = c.id 
        ORDER BY t.created_at DESC");
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            $row['user_name'],
            $row['title'],
            $row['category_name'],
            $row['type'],
            $row['amount'],
            $row['transaction_date'],
            $row['notes']
        ]);
    }
    fclose($output);
}

// Bulk action on users
else if ($action === 'bulkAction') {
    $bulk_action = $_POST['bulk_action'];
    $user_ids = explode(',', $_POST['user_ids']);
    $count = 0;
    
    foreach ($user_ids as $user_id) {
        $user_id = intval($user_id);
        
        if ($bulk_action === 'activate') {
            mysqli_query($conn, "UPDATE users SET status = 'active' WHERE id = $user_id AND is_admin = 0");
            $count++;
        } 
        else if ($bulk_action === 'suspend') {
            mysqli_query($conn, "UPDATE users SET status = 'suspended' WHERE id = $user_id AND is_admin = 0");
            $count++;
        }
        else if ($bulk_action === 'delete') {
            mysqli_query($conn, "DELETE FROM transactions WHERE user_id = $user_id");
            mysqli_query($conn, "DELETE FROM budgets WHERE user_id = $user_id");
            mysqli_query($conn, "DELETE FROM alerts WHERE user_id = $user_id");
            mysqli_query($conn, "DELETE FROM categories WHERE user_id = $user_id");
            mysqli_query($conn, "DELETE FROM users WHERE id = $user_id AND is_admin = 0");
            $count++;
        }
    }
    
    // Log admin action
    mysqli_query($conn, "INSERT INTO admin_logs (admin_id, action, details) VALUES ($admin_id, 'Bulk $bulk_action', 'Affected $count users')");
    
    echo json_encode(['success' => true, 'count' => $count]);
}

// Get platform statistics
else if ($action === 'getPlatformStats') {
    $totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_admin = 0"))['count'];
    $totalAdmins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_admin = 1"))['count'];
    $totalTransactions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM transactions"))['count'];
    $totalIncome = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'income'"))['total'] ?? 0;
    $totalExpense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'expense'"))['total'] ?? 0;
    
    // Get monthly data for charts
    $monthlyData = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('M', strtotime("-$i months"));
        $monthNum = date('m', strtotime("-$i months"));
        $year = date('Y', strtotime("-$i months"));
        
        $signups = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = $monthNum AND YEAR(created_at) = $year"))['count'];
        $income = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'income' AND MONTH(transaction_date) = $monthNum AND YEAR(transaction_date) = $year"))['total'] ?? 0;
        $expense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'expense' AND MONTH(transaction_date) = $monthNum AND YEAR(transaction_date) = $year"))['total'] ?? 0;
        
        $monthlyData[] = [
            'month' => $month,
            'signups' => $signups,
            'income' => $income,
            'expense' => $expense
        ];
    }
    
    echo json_encode([
        'success' => true,
        'total_users' => $totalUsers,
        'total_admins' => $totalAdmins,
        'total_transactions' => $totalTransactions,
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'net_balance' => $totalIncome - $totalExpense,
        'monthly_data' => $monthlyData
    ]);
}

// Get system settings
else if ($action === 'getSettings') {
    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM system_settings");
    $settings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode(['success' => true, 'settings' => $settings]);
}

// Update system settings
else if ($action === 'updateSettings') {
    $settings = json_decode($_POST['settings'], true);
    
    foreach ($settings as $key => $value) {
        $value = mysqli_real_escape_string($conn, $value);
        mysqli_query($conn, "UPDATE system_settings SET setting_value = '$value' WHERE setting_key = '$key'");
    }
    
    // Log action
    mysqli_query($conn, "INSERT INTO admin_logs (admin_id, action, details) VALUES ($admin_id, 'Updated System Settings', 'Updated ' . count($settings) . ' settings')");
    
    echo json_encode(['success' => true]);
}

// Unknown action
else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>