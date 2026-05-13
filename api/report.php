<?php
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['user_id'])) {
    if ($_GET['action'] !== 'exportPDF') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
}

$user_id = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? '';

// PDF Export
if ($action === 'exportPDF') {
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // Get data
    $incomeResult = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$user_id AND type='income' AND MONTH(transaction_date)=$month AND YEAR(transaction_date)=$year");
    $income = mysqli_fetch_assoc($incomeResult);
    $total_income = floatval($income['total']);
    
    $expenseResult = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$user_id AND type='expense' AND MONTH(transaction_date)=$month AND YEAR(transaction_date)=$year");
    $expense = mysqli_fetch_assoc($expenseResult);
    $total_expense = floatval($expense['total']);
    
    $net_savings = $total_income - $total_expense;
    $savings_percentage = $total_income > 0 ? round(($net_savings / $total_income) * 100) : 0;
    
    $transactions = mysqli_query($conn, "SELECT t.*, c.name as category_name 
        FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id=$user_id AND MONTH(t.transaction_date)=$month AND YEAR(t.transaction_date)=$year 
        ORDER BY t.transaction_date DESC");
    
    $categorySummary = mysqli_query($conn, "SELECT c.name, c.icon, SUM(t.amount) as total 
        FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id=$user_id AND t.type='expense' AND MONTH(t.transaction_date)=$month AND YEAR(t.transaction_date)=$year 
        GROUP BY t.category_id 
        ORDER BY total DESC");
    
    $monthName = date('F', mktime(0, 0, 0, $month, 1));
    $currency = $_SESSION['user_currency'] ?? 'Rs';
    
    $base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/expense-tracker/';
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Financial Report - <?php echo $monthName; ?> <?php echo $year; ?></title>
        <link rel="stylesheet" href="<?php echo $base_url; ?>css/pdf-export.css">
        <style>
            body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
            .report-container { max-width: 1200px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #4f46e5; }
            .header h1 { color: #4f46e5; margin: 0; }
            .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
            .summary-card { background: #f8f9fa; padding: 20px; border-radius: 12px; text-align: center; }
            .summary-card h3 { font-size: 14px; color: #666; margin-bottom: 10px; }
            .summary-card .amount { font-size: 28px; font-weight: bold; }
            .income .amount { color: #28a745; }
            .expense .amount { color: #dc3545; }
            .savings .amount { color: #4f46e5; }
            .section-title { font-size: 20px; margin: 30px 0 15px; padding-bottom: 10px; border-bottom: 2px solid #4f46e5; }
            .transaction-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .transaction-table th, .transaction-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            .transaction-table th { background: #4f46e5; color: white; }
            .income-text { color: #28a745; font-weight: bold; }
            .expense-text { color: #dc3545; font-weight: bold; }
            .category-item { display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid #eee; }
            .print-button { text-align: center; margin: 30px 0; }
            .print-button button { background: #4f46e5; color: white; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; }
            .footer { text-align: center; font-size: 12px; color: #999; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; }
            @media print { .print-button { display: none; } }
        </style>
    </head>
    <body>
        <div class="report-container">
            <div class="header">
                <h1>💰 Smart Expense Tracker</h1>
                <p>Financial Report - <?php echo $monthName; ?> <?php echo $year; ?></p>
                <p>Generated: <?php echo date('F j, Y g:i A'); ?></p>
            </div>
            
            <div class="summary-grid">
                <div class="summary-card income">
                    <h3>Total Income</h3>
                    <div class="amount"><?php echo $currency; ?> <?php echo number_format($total_income, 2); ?></div>
                </div>
                <div class="summary-card expense">
                    <h3>Total Expenses</h3>
                    <div class="amount"><?php echo $currency; ?> <?php echo number_format($total_expense, 2); ?></div>
                </div>
                <div class="summary-card savings">
                    <h3>Net Savings</h3>
                    <div class="amount"><?php echo $currency; ?> <?php echo number_format($net_savings, 2); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Savings Rate</h3>
                    <div class="amount"><?php echo $savings_percentage; ?>%</div>
                </div>
            </div>
            
            <h2 class="section-title">📋 Transaction History</h2>
            <table class="transaction-table">
                <thead><tr><th>Date</th><th>Title</th><th>Category</th><th>Type</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php if (mysqli_num_rows($transactions) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($transactions)): ?>
                        <tr>
                            <td><?php echo $row['transaction_date']; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td><?php echo ucfirst($row['type']); ?></td>
                            <td class="<?php echo $row['type'] == 'income' ? 'income-text' : 'expense-text'; ?>">
                                <?php echo $row['type'] == 'income' ? '+' : '-'; ?> <?php echo $currency; ?> <?php echo number_format($row['amount'], 2); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center;">No transactions found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <h2 class="section-title">📊 Spending by Category</h2>
            <div class="category-list">
                <?php if (mysqli_num_rows($categorySummary) > 0): ?>
                    <?php while ($cat = mysqli_fetch_assoc($categorySummary)): ?>
                        <?php $percentage = $total_expense > 0 ? round(($cat['total'] / $total_expense) * 100) : 0; ?>
                        <div class="category-item">
                            <span><?php echo $cat['icon']; ?> <?php echo htmlspecialchars($cat['name']); ?></span>
                            <span><?php echo $currency; ?> <?php echo number_format($cat['total'], 2); ?> (<?php echo $percentage; ?>%)</span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="category-item">No expense data available</div>
                <?php endif; ?>
            </div>
            
            <div class="print-button">
                <button onclick="window.print()">🖨️ Print / Save as PDF</button>
            </div>
            
            <div class="footer">
                <p>This is a system-generated report from Smart Expense Tracker.</p>
                <p>&copy; <?php echo date('Y'); ?> Smart Expense Tracker</p>
            </div>
        </div>
        <script>setTimeout(function(){ window.print(); }, 500);</script>
    </body>
    </html>
    <?php
    exit();
}

// CSV Export
else if ($action === 'export' && $_GET['type'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Title', 'Category', 'Type', 'Amount', 'Notes']);
    
    $result = mysqli_query($conn, "SELECT t.transaction_date, t.title, c.name as category, t.type, t.amount, t.notes 
        FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = $user_id 
        ORDER BY t.transaction_date DESC");
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['transaction_date'],
            $row['title'],
            $row['category'],
            $row['type'],
            $row['amount'],
            $row['notes']
        ]);
    }
    fclose($output);
    exit();
}

// Analytics (JSON) - FIXED: Removed number_format
else if ($action === 'getAnalytics') {
    header('Content-Type: application/json');
    
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    $incomeResult = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$user_id AND type='income' AND MONTH(transaction_date)=$month AND YEAR(transaction_date)=$year");
    $income = mysqli_fetch_assoc($incomeResult);
    $total_income = floatval($income['total']);
    
    $expenseResult = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$user_id AND type='expense' AND MONTH(transaction_date)=$month AND YEAR(transaction_date)=$year");
    $expense = mysqli_fetch_assoc($expenseResult);
    $total_expense = floatval($expense['total']);
    
    $net_savings = $total_income - $total_expense;
    $savings_percentage = $total_income > 0 ? round(($net_savings / $total_income) * 100) : 0;
    
    if ($savings_percentage >= 40) $health_text = 'Excellent 🎉';
    else if ($savings_percentage >= 20) $health_text = 'Good 👍';
    else $health_text = 'Risky ⚠️';
    
    $advice = '';
    if ($savings_percentage < 20) $advice = 'Consider reducing unnecessary expenses';
    else if ($savings_percentage < 40) $advice = 'Good job! Try to save more';
    else $advice = 'Excellent financial discipline!';
    
    $highestResult = mysqli_query($conn, "SELECT c.name, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id=$user_id AND t.type='expense' AND MONTH(t.transaction_date)=$month GROUP BY t.category_id ORDER BY total DESC LIMIT 1");
    $highest = mysqli_fetch_assoc($highestResult);
    
    $months = [];
    $monthly_incomes = [];
    $monthly_expenses = [];
    $savings_trend = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $m = date('m', strtotime("-$i months"));
        $y = date('Y', strtotime("-$i months"));
        $months[] = date('M', strtotime("-$i months"));
        
        $inc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$user_id AND type='income' AND MONTH(transaction_date)=$m AND YEAR(transaction_date)=$y"));
        $exp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$user_id AND type='expense' AND MONTH(transaction_date)=$m AND YEAR(transaction_date)=$y"));
        
        $monthly_incomes[] = floatval($inc['total']);
        $monthly_expenses[] = floatval($exp['total']);
        $savings_trend[] = floatval($inc['total']) - floatval($exp['total']);
    }
    
    $category_data = ['labels' => [], 'values' => []];
    $cats = mysqli_query($conn, "SELECT c.name, c.icon, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id=$user_id AND t.type='expense' AND MONTH(t.transaction_date)=$month GROUP BY t.category_id ORDER BY total DESC LIMIT 5");
    while ($cat = mysqli_fetch_assoc($cats)) {
        $category_data['labels'][] = $cat['icon'] . ' ' . $cat['name'];
        $category_data['values'][] = floatval($cat['total']);
    }
    
    // FIXED: Send raw numbers, NOT formatted with number_format
    echo json_encode([
        'success' => true,
        'total_income' => $total_income,
        'total_expense' => $total_expense,
        'net_savings' => $net_savings,
        'savings_percentage' => $savings_percentage,
        'health_text' => $health_text,
        'advice' => $advice,
        'highest_category' => $highest ? $highest['name'] : 'N/A',
        'highest_amount' => $highest ? $highest['total'] : 0,
        'months' => $months,
        'monthly_incomes' => $monthly_incomes,
        'monthly_expenses' => $monthly_expenses,
        'savings_trend' => $savings_trend,
        'category_data' => $category_data
    ]);
    exit();
}

// Default
else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>