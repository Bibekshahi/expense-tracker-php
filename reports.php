<?php
require_once 'db/config.php';
redirectIfNotLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Smart Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar">
        <div class="logo">💰 SmartTracker</div>
        <nav>
            <a href="dashboard.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="transactions.php" class="nav-item"><span>💸</span> Transactions</a>
            <a href="budget.php" class="nav-item"><span>🎯</span> Budget</a>
            <a href="reports.php" class="nav-item active"><span>📈</span> Reports</a>
            <a href="profile.php" class="nav-item"><span>👤</span> Profile</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="flex justify-between align-center" style="margin-bottom: 30px;"><h1>Financial Reports</h1><div><button class="btn-primary" onclick="exportCSV()">Export CSV</button><button class="btn-primary" onclick="exportPDF()" style="margin-left: 10px; background: #ef4444;">Export PDF</button></div></div>
        
        <div class="glass-card" style="margin-bottom: 24px;"><div class="flex gap-10 align-center"><label>Select Month:</label><input type="month" id="reportMonth"></div></div>
        
        <div class="stats-grid" id="reportsContainer">
          <div class="stat-card"><div class="stat-title">Total Income</div><div class="stat-value" id="totalIncome">Rs0</div></div>
<div class="stat-card"><div class="stat-title">Total Expenses</div><div class="stat-value" id="totalExpense">Rs0</div></div>
<div class="stat-card"><div class="stat-title">Net Savings</div><div class="stat-value" id="netSavings">Rs0</div></div>
<div class="stat-card"><div class="stat-title">Savings Rate</div><div class="stat-value" id="savingsPercentage">0%</div></div>
        </div>
        
        <div class="stats-grid" style="margin-bottom: 24px;">
            <div class="stat-card"><div class="stat-title">Highest Spending Category</div><div class="stat-value" id="highestCategory">-</div><div class="stat-change" id="highestAmount">Rs0</div></div>
            <div class="stat-card"><div class="stat-title">Financial Health</div><div class="stat-value" id="financialHealth">-</div><div class="stat-change" id="financialAdvice"></div></div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
            <div class="chart-container"><h3>Monthly Comparison</h3><canvas id="monthlyComparisonChart"></canvas></div>
            <div class="chart-container"><h3>Category Breakdown</h3><canvas id="categoryBreakdownChart"></canvas></div>
            <div class="chart-container"><h3>Savings Trend</h3><canvas id="savingsTrendChart"></canvas></div>
        </div>
    </div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="js/main.js"></script>
</body>
</html>