<?php
require_once 'db/config.php';
redirectIfNotLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar">
        <div class="logo">💰 SmartTracker</div>
        <nav>
            <a href="dashboard.php" class="nav-item active"><span>📊</span> Dashboard</a>
            <a href="transactions.php" class="nav-item"><span>💸</span> Transactions</a>
            <a href="budget.php" class="nav-item"><span>🎯</span> Budget</a>
            <a href="reports.php" class="nav-item"><span>📈</span> Reports</a>
            <a href="profile.php" class="nav-item"><span>👤</span> Profile</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1>Dashboard</h1>
            <button class="btn-primary" onclick="openModal('transactionModal')">+ Add Transaction</button>
        </div>
        
        <div class="stats-grid" id="dashboardStats">
            <div class="stat-card"><div class="stat-title">Total Income</div><div class="stat-value" id="totalIncome">Rs0</div></div>
<div class="stat-card"><div class="stat-title">Total Expenses</div><div class="stat-value" id="totalExpense">Rs0</div></div>
<div class="stat-card"><div class="stat-title">Remaining Balance</div><div class="stat-value" id="remainingBalance">Rs0</div></div>
<div class="stat-card"><div class="stat-title">Monthly Savings</div><div class="stat-value" id="monthlySavings">Rs0</div><div class="stat-change" id="savingsRate">0%</div></div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 30px;">
            <div class="chart-container"><h3>Expense Trend</h3><canvas id="expenseTrendChart"></canvas></div>
            <div class="chart-container"><h3>Income vs Expenses</h3><canvas id="incomeExpenseChart"></canvas></div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
            <div class="chart-container"><h3>Spending by Category</h3><canvas id="categoryChart"></canvas></div>
            <div class="glass-card"><h3>Financial Health Score</h3><div class="text-center" style="padding: 20px;"><div style="font-size: 48px; font-weight: 700; color: #4f46e5;" id="healthScore">0</div><div id="healthText"></div><div class="progress-bar mt-20"><div class="progress-fill" id="healthProgress" style="width: 0%"></div></div></div></div>
        </div>
        
        <div class="glass-card" style="margin-top: 30px;"><h3>Budget Alerts</h3><div id="budgetAlerts"></div></div>
        
        <div class="glass-card" style="margin-top: 30px;"><h3>Recent Transactions</h3><div class="table-container"><table><thead><tr><th>Title</th><th>Category</th><th>Amount</th><th>Date</th></tr></thead><tbody id="recentTransactions"></tbody></table></div></div>
    </div>
    
    <div id="transactionModal" class="modal"><div class="modal-content"><h2>Add Transaction</h2><form id="transactionForm"><div class="mb-20"><label>Title</label><input type="text" name="title" required></div><div class="mb-20"><label>Amount</label><input type="number" name="amount" step="0.01" required></div><div class="mb-20"><label>Type</label><select name="type" id="transType" required><option value="expense">Expense</option><option value="income">Income</option></select></div><div class="mb-20"><label>Category</label><select name="category_id" id="categorySelect" required></select></div><div class="mb-20"><label>Date</label><input type="date" name="transaction_date" required></div><div class="mb-20"><label>Notes</label><textarea name="notes" rows="3"></textarea></div><div class="flex gap-10"><button type="submit" class="btn-primary">Save</button><button type="button" onclick="closeModal('transactionModal')" class="btn-secondary">Cancel</button></div></form></div></div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="js/main.js"></script>
</body>
</html>
                    <