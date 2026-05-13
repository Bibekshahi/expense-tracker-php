// ==================== GLOBAL VARIABLES ====================
let charts = {};
let currentPage = 1;
let totalPages = 1;
let CURRENCY = 'Rs';  // Global currency variable

// ==================== LOAD CURRENCY FIRST ====================
async function loadCurrency() {
    try {
        const response = await fetch('api/get-currency.php');
        const data = await response.json();
        CURRENCY = data.currency;
        console.log('Currency loaded:', CURRENCY);
    } catch (error) {
        console.error('Error loading currency:', error);
        CURRENCY = 'Rs';
    }
}

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', async function () {
    await loadCurrency();  // Load currency before everything
    
    initDarkMode();
    initMobileMenu();

    if (document.getElementById('dashboardStats')) {
        initDashboard();
    }
    if (document.getElementById('transactionsList')) {
        initTransactions();
    }
    if (document.getElementById('budgetAmount')) {
        initBudget();
    }
    if (document.getElementById('reportsContainer')) {
        initReports();
    }
    if (document.getElementById('profileForm')) {
        initProfile();
    }
});

// ==================== DARK MODE ====================
function initDarkMode() {
    const toggle = document.getElementById('darkModeToggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });
    }

    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
}

// ==================== MOBILE MENU ====================
function initMobileMenu() {
    const menuToggle = document.createElement('div');
    menuToggle.className = 'menu-toggle';
    menuToggle.innerHTML = '☰';
    menuToggle.onclick = function () {
        document.querySelector('.sidebar').classList.toggle('active');
    };
    document.body.appendChild(menuToggle);
}

// ==================== NOTIFICATIONS ====================
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// ==================== MODAL FUNCTIONS ====================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

window.onclick = function (event) {
    if (event.target.classList && event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};

// ==================== DASHBOARD FUNCTIONS ====================
function initDashboard() {
    loadDashboardData();
    loadRecentTransactions();
}

function loadDashboardData() {
    fetch('api/dashboard.php?action=getStats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data);
                createCharts(data);
                showBudgetAlerts(data.alerts);
            }
        })
        .catch(error => console.error('Error:', error));
}

function loadRecentTransactions() {
    fetch('api/transaction.php?action=getRecent')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.transactions) {
                displayRecentTransactions(data.transactions);
            }
        });
}

function displayRecentTransactions(transactions) {
    const tbody = document.getElementById('recentTransactions');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No transactions found</td></tr>';
        return;
    }

    transactions.slice(0, 5).forEach(trans => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${escapeHtml(trans.title)}</td>
            <td>${trans.icon || '📁'} ${escapeHtml(trans.category_name)}</td>
            <td class="${trans.type === 'income' ? 'text-success' : 'text-danger'}">
                ${trans.type === 'income' ? '+' : '-'} ${CURRENCY}${parseFloat(trans.amount).toFixed(2)}
            </td>
            <td>${trans.transaction_date}</td>
        `;
    });
}

function updateDashboardStats(data) {
    const totalIncome = Number(data.total_income).toFixed(2);
    const totalExpense = Number(data.total_expense).toFixed(2);
    const balance = Number(data.balance).toFixed(2);
    const savings = Number(data.savings).toFixed(2);

    const elements = {
        'totalIncome': CURRENCY + totalIncome,
        'totalExpense': CURRENCY + totalExpense,
        'remainingBalance': CURRENCY + balance,
        'monthlySavings': CURRENCY + savings,
        'savingsRate': data.savings_rate + '%',
        'healthScore': data.health_score
    };

    for (const [id, value] of Object.entries(elements)) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = value;
    }

    const healthText = document.getElementById('healthText');
    const healthProgress = document.getElementById('healthProgress');
    const score = Number(data.health_score);

    if (healthText) {
        let status = '';
        if (score >= 40) status = 'Excellent 🎉';
        else if (score >= 20) status = 'Good 👍';
        else status = 'Risky ⚠️';
        healthText.innerHTML = status;
    }

    if (healthProgress) {
        healthProgress.style.width = Math.min(score, 100) + '%';
    }
}

function createCharts(data) {
    const ctx1 = document.getElementById('expenseTrendChart');
    if (ctx1) {
        if (charts.expenseTrend) charts.expenseTrend.destroy();
        charts.expenseTrend = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: data.months,
                datasets: [{
                    label: 'Expenses',
                    data: data.monthly_expenses,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: '#fff' } } },
                scales: { y: { ticks: { color: '#fff' } }, x: { ticks: { color: '#fff' } } }
            }
        });
    }

    const ctx2 = document.getElementById('incomeExpenseChart');
    if (ctx2) {
        if (charts.incomeExpense) charts.incomeExpense.destroy();
        charts.incomeExpense = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: data.months,
                datasets: [
                    { label: 'Income', data: data.monthly_incomes, backgroundColor: '#22c55e' },
                    { label: 'Expense', data: data.monthly_expenses, backgroundColor: '#ef4444' }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: '#fff' } } },
                scales: { y: { ticks: { color: '#fff' } }, x: { ticks: { color: '#fff' } } }
            }
        });
    }

    const ctx3 = document.getElementById('categoryChart');
    if (ctx3 && data.category_data && data.category_data.labels.length > 0) {
        if (charts.category) charts.category.destroy();
        charts.category = new Chart(ctx3, {
            type: 'pie',
            data: {
                labels: data.category_data.labels,
                datasets: [{
                    data: data.category_data.values,
                    backgroundColor: ['#4f46e5', '#06b6d4', '#22c55e', '#f59e0b', '#ef4444', '#ec4899', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: '#fff', position: 'bottom' } } }
            }
        });
    }
}

function showBudgetAlerts(alerts) {
    const container = document.getElementById('budgetAlerts');
    if (!container) return;

    container.innerHTML = '';
    if (alerts && alerts.length > 0) {
        alerts.forEach(alert => {
            const div = document.createElement('div');
            div.className = alert.type === 'exceeded' ? 'alert-danger' : 'alert-warning';
            div.innerHTML = `
                <strong>⚠️ ${escapeHtml(alert.message)}</strong>
                <p>Spent: ${CURRENCY}${Number(alert.spent).toFixed(2)} / Budget: ${CURRENCY}${Number(alert.budget).toFixed(2)}</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${alert.percentage}%"></div>
                </div>
            `;
            container.appendChild(div);
        });
    } else {
        container.innerHTML = '<div class="alert-success">🎉 All budgets are on track!</div>';
    }
}

// ==================== TRANSACTIONS FUNCTIONS ====================
function initTransactions() {
    loadCategories();
    loadTransactions();

    const dateInput = document.querySelector('input[name="transaction_date"]');
    if (dateInput) {
        dateInput.valueAsDate = new Date();
    }

    const typeSelect = document.getElementById('transType');
    if (typeSelect) {
        typeSelect.addEventListener('change', function () {
            loadCategories(this.value);
        });
    }

    const transactionForm = document.getElementById('transactionForm');
    if (transactionForm) {
        transactionForm.addEventListener('submit', addTransaction);
    }

    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', updateTransaction);
    }
}

function loadCategories(type = 'all') {
    let url = 'api/transaction.php?action=getCategories';
    if (type !== 'all') {
        url += `&type=${type}`;
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.categories) {
                updateCategoryDropdowns(data.categories);
            }
        });
}

function updateCategoryDropdowns(categories) {
    const selects = ['categorySelect', 'editCategory', 'filterCategory'];
    selects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (select) {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Category</option>';
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.innerHTML = `${cat.icon} ${cat.name}`;
                if (currentValue == cat.id) option.selected = true;
                select.appendChild(option);
            });
        }
    });
}

function loadTransactions() {
    const type = document.getElementById('filterType')?.value || 'all';
    const category = document.getElementById('filterCategory')?.value || '';
    const month = document.getElementById('filterMonth')?.value || '';
    const search = document.getElementById('searchInput')?.value || '';

    fetch(`api/transaction.php?action=getAll&page=${currentPage}&type=${type}&category=${category}&month=${month}&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTransactions(data.transactions);
                totalPages = data.total_pages || 1;
                updatePagination();
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayTransactions(transactions) {
    const tbody = document.getElementById('transactionsList');
    if (!tbody) return;

    if (!transactions || transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No transactions found</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    transactions.forEach(trans => {
        const row = tbody.insertRow();
        const amountNum = parseFloat(trans.amount);
        const formattedAmount = amountNum.toFixed(2);

        row.innerHTML = `
            <td>${trans.transaction_date}</td>
            <td>${escapeHtml(trans.title)}</td>
            <td>${trans.icon || '📁'} ${escapeHtml(trans.category_name)}</td>
            <td><span class="badge ${trans.type === 'income' ? 'badge-success' : 'badge-danger'}">${trans.type}</span></td>
            <td class="${trans.type === 'income' ? 'text-success' : 'text-danger'}">${trans.type === 'income' ? '+' : '-'} ${CURRENCY}${formattedAmount}</td>
            <td>${escapeHtml(trans.notes || '-')}</td>
            <td>
                <button class="action-btn edit-btn" onclick="editTransaction(${trans.id})">✏️</button>
                <button class="action-btn delete-btn" onclick="deleteTransaction(${trans.id})">🗑️</button>
            </td>
        `;
    });
}

function updatePagination() {
    const container = document.getElementById('pagination');
    if (!container) return;

    container.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.innerHTML = i;
        btn.className = i === currentPage ? 'active' : '';
        btn.onclick = () => {
            currentPage = i;
            loadTransactions();
        };
        container.appendChild(btn);
    }
}

function addTransaction(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'add');

    fetch('api/transaction.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Transaction added successfully!', 'success');
                closeModal('transactionModal');
                event.target.reset();
                loadTransactions();
                if (typeof loadDashboardData === 'function') loadDashboardData();
            } else {
                showNotification(data.error || 'Error adding transaction', 'error');
            }
        });
}

function editTransaction(id) {
    fetch(`api/transaction.php?action=getOne&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.transaction) {
                const trans = data.transaction;
                document.getElementById('editId').value = trans.id;
                document.getElementById('editTitle').value = trans.title;
                document.getElementById('editAmount').value = trans.amount;
                document.getElementById('editType').value = trans.type;
                document.getElementById('editDate').value = trans.transaction_date;
                document.getElementById('editNotes').value = trans.notes || '';

                loadCategories(trans.type);
                setTimeout(() => {
                    document.getElementById('editCategory').value = trans.category_id;
                }, 100);

                openModal('editModal');
            }
        });
}

function updateTransaction(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'update');

    fetch('api/transaction.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Transaction updated successfully!', 'success');
                closeModal('editModal');
                loadTransactions();
                if (typeof loadDashboardData === 'function') loadDashboardData();
            } else {
                showNotification(data.error || 'Error updating transaction', 'error');
            }
        });
}

function deleteTransaction(id) {
    if (confirm('Are you sure you want to delete this transaction?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch('api/transaction.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Transaction deleted successfully!', 'success');
                    loadTransactions();
                    if (typeof loadDashboardData === 'function') loadDashboardData();
                } else {
                    showNotification('Error deleting transaction', 'error');
                }
            });
    }
}

function searchTransactions() {
    currentPage = 1;
    loadTransactions();
}

function resetFilters() {
    const filterType = document.getElementById('filterType');
    const filterCategory = document.getElementById('filterCategory');
    const filterMonth = document.getElementById('filterMonth');
    const searchInput = document.getElementById('searchInput');

    if (filterType) filterType.value = 'all';
    if (filterCategory) filterCategory.value = '';
    if (filterMonth) filterMonth.value = '';
    if (searchInput) searchInput.value = '';

    currentPage = 1;
    loadTransactions();
}

function exportCSV() {
    window.location.href = 'api/report.php?action=export&type=csv';
}

// ==================== BUDGET FUNCTIONS ====================
function initBudget() {
    console.log('initBudget called');
    loadBudgetData();

    const budgetForm = document.getElementById('budgetForm');
    if (budgetForm) {
        budgetForm.addEventListener('submit', saveBudget);
    }
}

function loadBudgetData() {
    let month = document.getElementById('budgetMonth')?.value || new Date().getMonth() + 1;
    let year = document.getElementById('budgetYear')?.value || new Date().getFullYear();
    
    const viewSelect = document.getElementById('viewBudgetMonth');
    if (viewSelect && viewSelect.value) {
        const viewValue = viewSelect.value;
        if (viewValue.includes('-')) {
            month = viewValue.split('-')[0];
            year = viewValue.split('-')[1];
        }
    }
    
    fetch(`api/budget.php?action=get&month=${month}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let budgetAmount = 0;
                if (data.budget && data.budget.monthly_budget) {
                    budgetAmount = parseFloat(data.budget.monthly_budget);
                }
                
                // Use CURRENCY variable instead of hardcoded Rs
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

function saveBudget(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'save');
    
    fetch('api/budget.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Budget saved successfully!', 'success');
            loadBudgetData();
        } else {
            showNotification(data.error || 'Error saving budget', 'error');
        }
    });
}

// ==================== REPORTS FUNCTIONS ====================
function initReports() {
    loadReportsData();

    const reportMonth = document.getElementById('reportMonth');
    if (reportMonth) {
        reportMonth.valueAsDate = new Date();
        reportMonth.addEventListener('change', loadReportsData);
    }
}

function loadReportsData() {
    const month = document.getElementById('reportMonth')?.value || '';
    const year = month ? month.split('-')[0] : new Date().getFullYear();
    const monthNum = month ? month.split('-')[1] : new Date().getMonth() + 1;

    fetch(`api/report.php?action=getAnalytics&month=${monthNum}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReports(data);
                createReportCharts(data);
            }
        });
}

function displayReports(data) {
    const elements = {
        'totalIncome': CURRENCY + Number(data.total_income).toFixed(2),
        'totalExpense': CURRENCY + Number(data.total_expense).toFixed(2),
        'netSavings': CURRENCY + Number(data.net_savings).toFixed(2),
        'savingsPercentage': `${data.savings_percentage}%`,
        'highestCategory': `${data.highest_category || 'N/A'}`,
        'highestAmount': CURRENCY + Number(data.highest_amount || 0).toFixed(2),
        'financialHealth': data.health_text,
        'financialAdvice': data.advice
    };

    for (const [id, value] of Object.entries(elements)) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = value;
    }
}

function createReportCharts(data) {
    const ctx1 = document.getElementById('monthlyComparisonChart');
    if (ctx1 && data.months) {
        if (charts.monthlyComparison) charts.monthlyComparison.destroy();
        charts.monthlyComparison = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: data.months,
                datasets: [
                    { label: 'Income', data: data.monthly_incomes, backgroundColor: '#22c55e' },
                    { label: 'Expense', data: data.monthly_expenses, backgroundColor: '#ef4444' }
                ]
            },
            options: { responsive: true, plugins: { legend: { labels: { color: '#fff' } } } }
        });
    }

    const ctx2 = document.getElementById('categoryBreakdownChart');
    if (ctx2 && data.category_data) {
        if (charts.categoryBreakdown) charts.categoryBreakdown.destroy();
        charts.categoryBreakdown = new Chart(ctx2, {
            type: 'pie',
            data: {
                labels: data.category_data.labels,
                datasets: [{ data: data.category_data.values, backgroundColor: ['#4f46e5', '#06b6d4', '#22c55e', '#f59e0b', '#ef4444'] }]
            },
            options: { responsive: true, plugins: { legend: { labels: { color: '#fff', position: 'bottom' } } } }
        });
    }

    const ctx3 = document.getElementById('savingsTrendChart');
    if (ctx3 && data.savings_trend) {
        if (charts.savingsTrend) charts.savingsTrend.destroy();
        charts.savingsTrend = new Chart(ctx3, {
            type: 'line',
            data: {
                labels: data.months,
                datasets: [{ label: 'Savings', data: data.savings_trend, borderColor: '#06b6d4', tension: 0.4, fill: true }]
            },
            options: { responsive: true, plugins: { legend: { labels: { color: '#fff' } } } }
        });
    }
}

function exportPDF() {
    // Get current month and year from the selector
    const monthInput = document.getElementById('reportMonth');
    let month = new Date().getMonth() + 1;
    let year = new Date().getFullYear();
    
    if (monthInput && monthInput.value) {
        const parts = monthInput.value.split('-');
        year = parts[0];
        month = parts[1];
    }
    
    // Open a new window with the PDF report
    const pdfWindow = window.open('', '_blank');
    pdfWindow.document.write('<html><head><title>Loading Report...</title></head><body>Generating report, please wait...</body></html>');
    
    fetch(`api/report.php?action=exportPDF&month=${month}&year=${year}`)
        .then(response => response.text())
        .then(html => {
            pdfWindow.document.write(html);
            pdfWindow.document.close();
        })
        .catch(error => {
            pdfWindow.document.write('<body>Error loading report. Please try again.</body>');
            console.error('Error:', error);
        });
}

// ==================== PROFILE FUNCTIONS ====================
function initProfile() {
    loadProfileData();

    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', updateProfile);
    }

    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', updatePassword);
    }

    const imageInput = document.getElementById('profileImage');
    if (imageInput) {
        imageInput.addEventListener('change', uploadProfileImage);
    }
}

function loadProfileData() {
    fetch('api/profile.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('fullname').value = data.fullname;
                document.getElementById('email').value = data.email;
                document.getElementById('currency').value = data.currency;
                const profileImg = document.getElementById('profileImagePreview');
                if (profileImg && data.profile_image) {
                    profileImg.src = `uploads/${data.profile_image}`;
                }
            }
        });
}

function updateProfile(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'update');

    fetch('api/profile.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Profile updated successfully!', 'success');
                // Reload currency after profile update
                loadCurrency().then(() => {
                    location.reload(); // Refresh to update all displays
                });
            } else {
                showNotification(data.error || 'Error updating profile', 'error');
            }
        });
}

function updatePassword(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'changePassword');

    fetch('api/profile.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Password changed successfully!', 'success');
                event.target.reset();
            } else {
                showNotification(data.error || 'Error changing password', 'error');
            }
        });
}

function uploadProfileImage(event) {
    const formData = new FormData();
    formData.append('action', 'uploadImage');
    formData.append('profile_image', event.target.files[0]);

    fetch('api/profile.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.image) {
                document.getElementById('profileImagePreview').src = `uploads/${data.image}`;
                showNotification('Profile image updated!', 'success');
            } else {
                showNotification(data.error || 'Error uploading image', 'error');
            }
        });
}

// ==================== HELPER FUNCTIONS ====================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function loadCategoriesForSelect(selectId, type = 'all') {
    let url = 'api/transaction.php?action=getCategories';
    if (type !== 'all') {
        url += `&type=${type}`;
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.categories) {
                const select = document.getElementById(selectId);
                if (select) {
                    select.innerHTML = '<option value="">Select Category</option>';
                    data.categories.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.innerHTML = `${cat.icon} ${cat.name}`;
                        select.appendChild(option);
                    });
                }
            }
        });
}