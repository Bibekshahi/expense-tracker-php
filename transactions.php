<?php
require_once 'db/config.php';
redirectIfNotLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Smart Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">💰 SmartTracker</div>
        <nav>
            <a href="dashboard.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="transactions.php" class="nav-item active"><span>💸</span> Transactions</a>
            <a href="budget.php" class="nav-item"><span>🎯</span> Budget</a>
            <a href="reports.php" class="nav-item"><span>📈</span> Reports</a>
            <a href="profile.php" class="nav-item"><span>👤</span> Profile</a>
            <a href="logout.php" class="nav-item"><span>🚪</span> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="flex justify-between align-center" style="margin-bottom: 30px;"><h1>Transactions</h1><button class="btn-primary" onclick="openModal('transactionModal')">+ Add Transaction</button></div>
        
        <div class="glass-card" style="margin-bottom: 24px;"><div class="flex gap-10"><input type="text" id="searchInput" placeholder="Search transactions..." style="flex: 1;"><button class="btn-primary" onclick="searchTransactions()">Search</button><button class="btn-primary" onclick="exportCSV()" style="background: #10b981;">Export CSV</button></div></div>
        
        <div class="glass-card" style="margin-bottom: 24px;"><div class="flex gap-10 flex-wrap"><select id="filterType" onchange="loadTransactions()"><option value="all">All Types</option><option value="income">Income</option><option value="expense">Expense</option></select><select id="filterCategory" onchange="loadTransactions()"><option value="">All Categories</option></select><input type="month" id="filterMonth" onchange="loadTransactions()"><button onclick="resetFilters()" class="btn-secondary">Reset</button></div></div>
        
        <div class="glass-card"><div class="table-container"><table><thead><tr><th>Date</th><th>Title</th><th>Category</th><th>Type</th><th>Amount</th><th>Notes</th><th>Actions</th></tr></thead><tbody id="transactionsList"><tr><td colspan="7" class="text-center">Loading...</td></tr></tbody></table></div><div id="pagination" class="pagination"></div></div>
    </div>
    
    <div id="transactionModal" class="modal"><div class="modal-content"><h2>Add Transaction</h2><form id="transactionForm"><div class="mb-20"><label>Title</label><input type="text" name="title" required></div><div class="mb-20"><label>Amount</label><input type="number" name="amount" step="0.01" required></div><div class="mb-20"><label>Type</label><select name="type" id="transType" required><option value="expense">Expense</option><option value="income">Income</option></select></div><div class="mb-20"><label>Category</label><select name="category_id" id="categorySelect" required></select></div><div class="mb-20"><label>Date</label><input type="date" name="transaction_date" required></div><div class="mb-20"><label>Notes</label><textarea name="notes" rows="3"></textarea></div><div class="flex gap-10"><button type="submit" class="btn-primary">Save</button><button type="button" onclick="closeModal('transactionModal')" class="btn-secondary">Cancel</button></div></form></div></div>
    
    <div id="editModal" class="modal"><div class="modal-content"><h2>Edit Transaction</h2><form id="editForm"><input type="hidden" name="id" id="editId"><div class="mb-20"><label>Title</label><input type="text" name="title" id="editTitle" required></div><div class="mb-20"><label>Amount</label><input type="number" name="amount" id="editAmount" step="0.01" required></div><div class="mb-20"><label>Type</label><select name="type" id="editType" required><option value="expense">Expense</option><option value="income">Income</option></select></div><div class="mb-20"><label>Category</label><select name="category_id" id="editCategory" required></select></div><div class="mb-20"><label>Date</label><input type="date" name="transaction_date" id="editDate" required></div><div class="mb-20"><label>Notes</label><textarea name="notes" id="editNotes" rows="3"></textarea></div><div class="flex gap-10"><button type="submit" class="btn-primary">Update</button><button type="button" onclick="closeModal('editModal')" class="btn-secondary">Cancel</button></div></form></div></div>
    
    <div class="dark-mode-toggle" id="darkModeToggle">🌙</div>
    
    <script src="js/main.js"></script>
</body>
</html>