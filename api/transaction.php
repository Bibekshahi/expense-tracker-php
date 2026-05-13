<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get categories
if ($action === 'getCategories') {
    $type = $_GET['type'] ?? '';
    $where = "user_id = $user_id";
    if ($type && $type !== 'all' && $type !== '') {
        $where .= " AND type = '$type'";
    }
    $result = mysqli_query($conn, "SELECT id, name, icon, type FROM categories WHERE $where");
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    echo json_encode(['success' => true, 'categories' => $categories]);
}

// Add transaction
else if ($action === 'add') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amount = floatval(str_replace(',', '', $_POST['amount']));  // Remove commas and convert
    $amount = $amount;  // Keep as is - 15000 stays 15000
    $type = $_POST['type'];
    $category_id = intval($_POST['category_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $date = $_POST['transaction_date'];
    
    $query = "INSERT INTO transactions (user_id, category_id, title, amount, type, notes, transaction_date) 
              VALUES ($user_id, $category_id, '$title', $amount, '$type', '$notes', '$date')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
}

// Get recent transactions
else if ($action === 'getRecent') {
    $result = mysqli_query($conn, "SELECT t.*, c.name as category_name, c.icon 
        FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = $user_id 
        ORDER BY t.transaction_date DESC 
        LIMIT 10");
    
    $transactions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    echo json_encode(['success' => true, 'transactions' => $transactions]);
}

// Get all transactions with pagination
else if ($action === 'getAll') {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $type = $_GET['type'] ?? 'all';
    $category = $_GET['category'] ?? '';
    $month = $_GET['month'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $where = "t.user_id = $user_id";
    if ($type !== 'all') {
        $where .= " AND t.type = '$type'";
    }
    if ($category && $category !== '') {
        $where .= " AND t.category_id = " . intval($category);
    }
    if ($month) {
        $parts = explode('-', $month);
        $where .= " AND MONTH(t.transaction_date) = " . intval($parts[1]) . " AND YEAR(t.transaction_date) = " . intval($parts[0]);
    }
    if ($search) {
        $search = mysqli_real_escape_string($conn, $search);
        $where .= " AND (t.title LIKE '%$search%' OR t.notes LIKE '%$search%')";
    }
    
    $countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions t WHERE $where");
    $total = mysqli_fetch_assoc($countResult)['total'];
    $total_pages = ceil($total / $limit);
    
    $query = "SELECT t.*, c.name as category_name, c.icon 
              FROM transactions t 
              JOIN categories c ON t.category_id = c.id 
              WHERE $where 
              ORDER BY t.transaction_date DESC 
              LIMIT $offset, $limit";
    
    $result = mysqli_query($conn, $query);
    $transactions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'transactions' => $transactions, 
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total' => $total
    ]);
}

// Get single transaction
else if ($action === 'getOne') {
    $id = intval($_GET['id']);
    $result = mysqli_query($conn, "SELECT * FROM transactions WHERE id = $id AND user_id = $user_id");
    $transaction = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'transaction' => $transaction]);
}

// Update transaction
else if ($action === 'update') {
    $id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amount = floatval($_POST['amount']);
    $type = $_POST['type'];
    $category_id = intval($_POST['category_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $date = $_POST['transaction_date'];
    
    $query = "UPDATE transactions 
              SET title='$title', amount=$amount, type='$type', category_id=$category_id, notes='$notes', transaction_date='$date' 
              WHERE id=$id AND user_id=$user_id";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
}

// Delete transaction
else if ($action === 'delete') {
    $id = intval($_POST['id']);
    if (mysqli_query($conn, "DELETE FROM transactions WHERE id = $id AND user_id = $user_id")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delete failed']);
    }
}

// Search transactions
else if ($action === 'search') {
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    $result = mysqli_query($conn, "SELECT t.*, c.name as category_name, c.icon 
        FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = $user_id 
        AND (t.title LIKE '%$keyword%' OR t.notes LIKE '%$keyword%') 
        ORDER BY t.transaction_date DESC");
    
    $transactions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    echo json_encode(['success' => true, 'transactions' => $transactions]);
}
?>