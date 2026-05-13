<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get budget for specific month/year
if ($action === 'get') {
    // Get month and year from request, or use current
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // Get budget for specified month/year
    $budgetResult = mysqli_query($conn, "SELECT monthly_budget FROM budgets WHERE user_id=$user_id AND month=$month AND year=$year");
    $budget = mysqli_fetch_assoc($budgetResult);
    
    // Get spent amount for specified month/year
    $spentResult = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$user_id AND type='expense' AND MONTH(transaction_date)=$month AND YEAR(transaction_date)=$year");
    $spent = mysqli_fetch_assoc($spentResult);
    $total_spent = floatval($spent['total']);
    
    $budget_amount = $budget ? floatval($budget['monthly_budget']) : 0;
    $remaining = $budget_amount - $total_spent;
    $percentage = $budget_amount > 0 ? ($total_spent / $budget_amount) * 100 : 0;
    
    echo json_encode([
        'success' => true, 
        'budget' => $budget, 
        'spent' => $total_spent, 
        'remaining' => $remaining, 
        'percentage' => $percentage,
        'month' => $month,
        'year' => $year
    ]);
}

// Save budget
else if ($action === 'save') {
    $budget_amount = floatval($_POST['budget']);
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    
    // Check if budget already exists for this month/year
    $checkResult = mysqli_query($conn, "SELECT id FROM budgets WHERE user_id=$user_id AND month=$month AND year=$year");
    
    if (mysqli_num_rows($checkResult) > 0) {
        // Update existing budget
        $query = "UPDATE budgets SET monthly_budget = $budget_amount WHERE user_id=$user_id AND month=$month AND year=$year";
    } else {
        // Insert new budget
        $query = "INSERT INTO budgets (user_id, monthly_budget, month, year) VALUES ($user_id, $budget_amount, $month, $year)";
    }
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Budget saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
}

// Get all budgets for user (for dropdown)
else if ($action === 'getAllBudgets') {
    $result = mysqli_query($conn, "SELECT DISTINCT month, year, monthly_budget FROM budgets WHERE user_id=$user_id ORDER BY year DESC, month DESC");
    $budgets = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $budgets[] = $row;
    }
    echo json_encode(['success' => true, 'budgets' => $budgets]);
}

// Delete budget
else if ($action === 'delete') {
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    
    $query = "DELETE FROM budgets WHERE user_id=$user_id AND month=$month AND year=$year";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Budget deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
}
?>