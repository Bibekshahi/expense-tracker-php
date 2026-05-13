<?php
require_once 'db/config.php';
redirectIfNotLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget - Smart Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">💰 SmartTracker</div>
        <nav>
            <a href="dashboard.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="transactions.php" class="nav-item"><span>💸</span> Transactions</a>
            <a href="budget.php" class="nav-item active"><span>🎯</span> Budget</a>
            <a href="reports.php" class="nav-item"><span>📈</span> Reports</a>
            <a href="profile.php" class="nav-item"><span>👤</span> Profile</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <h1 style="margin-bottom: 30px;">Budget Planning</h1>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Monthly Budget</div>
                <div class="stat-value" id="currentBudget">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Spent</div>
                <div class="stat-value" id="totalSpent">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Remaining Budget</div>
                <div class="stat-value" id="remainingBudget">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Budget Status</div>
                <div class="stat-value" id="budgetStatus">Not Set</div>
            </div>
        </div>

        <!-- Budget Progress -->
        <div class="glass-card" style="margin-bottom: 24px;">
            <h3>Budget Progress</h3>
            <div class="progress-bar">
                <div class="progress-fill" id="budgetProgress" style="width: 0%"></div>
            </div>
            <div class="text-center mt-20"><span id="budgetPercentage">0%</span> of budget used</div>
        </div>

        <!-- View Budget Section -->
        <div class="glass-card" style="margin-bottom: 24px;">
            <h3>View Budget</h3>
            <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1;">
                    <label>Select Month/Year to View</label>
                    <select id="viewMonth" style="width: 100%;">
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5" selected>May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label>Year</label>
                    <input type="number" id="viewYear" value="<?php echo date('Y'); ?>" style="width: 100%;">
                </div>
                <div>
                    <button type="button" class="btn-primary" onclick="viewBudget()">View Budget</button>
                </div>
            </div>
        </div>

        <!-- Set Monthly Budget Section -->
        <div class="glass-card">
            <h3>Set Monthly Budget</h3>
            <form id="budgetForm">
                <div class="mb-20">
                    <label>Monthly Budget Amount</label>
                    <input type="number" name="budget" id="budgetAmount" step="0.01" required placeholder="Enter budget amount">
                </div>
                <div class="mb-20">
                    <label>Month</label>
                    <select name="month" id="budgetMonth">
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5" selected>May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>
                <div class="mb-20">
                    <label>Year</label>
                    <input type="number" name="year" id="budgetYear" value="<?php echo date('Y'); ?>">
                </div>
                <button type="submit" class="btn-primary">Save Budget</button>
            </form>
        </div>
    </div>

    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>

    
   <script src="js/main.js"></script>
<script>
    // Get currency from session
    let CURRENCY = '<?php echo $_SESSION['user_currency'] ?? 'Rs'; ?>';
    
    // Override the loadBudgetData function to use dynamic currency
    function loadBudgetData() {
        let month = document.getElementById('budgetMonth')?.value || new Date().getMonth() + 1;
        let year = document.getElementById('budgetYear')?.value || new Date().getFullYear();
        
        fetch(`api/budget.php?action=get&month=${month}&year=${year}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let budgetAmount = 0;
                    if (data.budget && data.budget.monthly_budget) {
                        budgetAmount = parseFloat(data.budget.monthly_budget);
                    }
                    
                    // Use CURRENCY from session
                    document.getElementById('currentBudget').innerHTML = CURRENCY + budgetAmount.toFixed(2);
                    document.getElementById('totalSpent').innerHTML = CURRENCY + data.spent.toFixed(2);
                    document.getElementById('remainingBudget').innerHTML = CURRENCY + data.remaining.toFixed(2);
                    
                    const percentage = data.percentage || 0;
                    document.getElementById('budgetProgress').style.width = `${Math.min(percentage, 100)}%`;
                    document.getElementById('budgetPercentage').innerHTML = `${percentage.toFixed(1)}%`;
                    
                    if (percentage >= 100) {
                        document.getElementById('budgetStatus').innerHTML = '<span class="badge badge-danger">Exceeded!</span>';
                    } else if (percentage >= 80) {
                        document.getElementById('budgetStatus').innerHTML = '<span class="badge badge-warning">Near Limit!</span>';
                    } else if (budgetAmount > 0) {
                        document.getElementById('budgetStatus').innerHTML = '<span class="badge badge-success">On Track</span>';
                    } else {
                        document.getElementById('budgetStatus').innerHTML = 'Not Set';
                    }
                    
                    document.getElementById('budgetAmount').value = budgetAmount > 0 ? budgetAmount : '';
                }
            });
    }
    
    // Re-run loadBudgetData on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadBudgetData();
    });
</script>
</body>
</html>