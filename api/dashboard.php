<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'getStats') {
    $month = date('m');
    $year = date('Y');
    
    // Get income - FIXED: Don't modify the amount
    $incomeResult = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE user_id=$user_id AND type='income' AND MONTH(transaction_date)=$month AND YEAR(transaction_date)=$year");
    $income = mysqli_fetch_assoc($incomeResult);
    $total_income = $income['total'] ? floatval($income['total']) : 0;
    
    // Get expense - FIXED: Don't modify the amount
    $expenseResult = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE user_id=$user_id AND type='expense' AND MONTH(transaction_date)=$month AND YEAR(transaction_date)=$year");
    $expense = mysqli_fetch_assoc($expenseResult);
    $total_expense = $expense['total'] ? floatval($expense['total']) : 0;
    
    $balance = $total_income - $total_expense;
    $savings = $balance;
    $savings_rate = $total_income > 0 ? round(($balance / $total_income) * 100) : 0;
    $health_score = $savings_rate;
    
    // Get monthly data for charts
    $months = [];
    $monthly_incomes = [];
    $monthly_expenses = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $m = date('m', strtotime("-$i months"));
        $y = date('Y', strtotime("-$i months"));
        $months[] = date('M', strtotime("-$i months"));
        
        $incResult = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$user_id AND type='income' AND MONTH(transaction_date)=$m AND YEAR(transaction_date)=$y");
        $inc = mysqli_fetch_assoc($incResult);
        $monthly_incomes[] = floatval($inc['total']);
        
        $expResult = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$user_id AND type='expense' AND MONTH(transaction_date)=$m AND YEAR(transaction_date)=$y");
        $exp = mysqli_fetch_assoc($expResult);
        $monthly_expenses[] = floatval($exp['total']);
    }
    
    // Category data for pie chart
    $category_data = ['labels' => [], 'values' => []];
    $cats = mysqli_query($conn, "SELECT c.name, c.icon, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id=$user_id AND t.type='expense' AND MONTH(t.transaction_date)=$month GROUP BY t.category_id ORDER BY total DESC LIMIT 5");
    while ($cat = mysqli_fetch_assoc($cats)) { 
        $category_data['labels'][] = $cat['icon'] . ' ' . $cat['name']; 
        $category_data['values'][] = floatval($cat['total']); 
    }
    
    // Budget alerts
    $budgetResult = mysqli_query($conn, "SELECT monthly_budget FROM budgets WHERE user_id=$user_id AND month=$month AND year=$year");
    $budget = mysqli_fetch_assoc($budgetResult);
    $alerts = [];
    
    if ($budget && $budget['monthly_budget'] > 0) {
        $percentage = ($total_expense / $budget['monthly_budget']) * 100;
        if ($percentage >= 100) {
            $alerts[] = [
                'message' => 'Budget Limit Exceeded!', 
                'type' => 'exceeded', 
                'spent' => $total_expense, 
                'budget' => $budget['monthly_budget'], 
                'percentage' => 100
            ];
        } else if ($percentage >= 80) {
            $alerts[] = [
                'message' => 'Budget Almost Reached (80%)', 
                'type' => 'warning', 
                'spent' => $total_expense, 
                'budget' => $budget['monthly_budget'], 
                'percentage' => round($percentage)
            ];
        }
    }
    
   echo json_encode([
    'success' => true, 
    'total_income' => $total_income,  // REMOVED number_format
    'total_expense' => $total_expense,  // REMOVED number_format
    'balance' => $balance,  // REMOVED number_format
    'savings' => $savings,  // REMOVED number_format
    'savings_rate' => $savings_rate, 
    'health_score' => $health_score, 
    'months' => $months, 
    'monthly_incomes' => $monthly_incomes, 
    'monthly_expenses' => $monthly_expenses, 
    'category_data' => $category_data, 
    'alerts' => $alerts
]);
}
?>