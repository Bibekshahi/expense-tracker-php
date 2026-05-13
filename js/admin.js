// ==================== ADMIN GLOBAL VARIABLES ====================
let adminCurrentPage = 1;
let adminTotalPages = 1;

// ==================== ADMIN INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    initAdminDarkMode();
    initAdminEvents();
});

// ==================== ADMIN DARK MODE ====================
function initAdminDarkMode() {
    const toggle = document.getElementById('darkModeToggle');
    if (toggle) {
        toggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('adminDarkMode', document.body.classList.contains('dark-mode'));
        });
    }
    
    if (localStorage.getItem('adminDarkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
}

// ==================== ADMIN EVENT HANDLERS ====================
function initAdminEvents() {
    // Confirm delete
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this? This action cannot be undone!')) {
                e.preventDefault();
            }
        });
    });
    
    // Confirm suspend
    const suspendButtons = document.querySelectorAll('.btn-suspend');
    suspendButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to suspend this user?')) {
                e.preventDefault();
            }
        });
    });
}

// ==================== ADMIN MODAL FUNCTIONS ====================
function openAdminModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeAdminModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal on outside click
window.onclick = function(event) {
    if (event.target.classList && event.target.classList.contains('admin-modal')) {
        event.target.style.display = 'none';
    }
};

// ==================== ADMIN USER MANAGEMENT ====================
function viewUserDetails(userId) {
    fetch(`../api/admin.php?action=getUserDetails&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('userDetailsModal');
                if (modal) {
                    document.getElementById('modalUserName').innerHTML = data.user.fullname;
                    document.getElementById('modalUserEmail').innerHTML = data.user.email;
                    document.getElementById('modalUserRegistered').innerHTML = data.user.created_at;
                    document.getElementById('modalUserLastLogin').innerHTML = data.user.last_login || 'Never';
                    document.getElementById('modalUserStatus').innerHTML = data.user.status;
                    document.getElementById('modalTransactionCount').innerHTML = data.transaction_count;
                    document.getElementById('modalTotalIncome').innerHTML = 'Rs' + Number(data.total_income).toFixed(2);
                    document.getElementById('modalTotalExpense').innerHTML = 'Rs' + Number(data.total_expense).toFixed(2);
                    openAdminModal('userDetailsModal');
                }
            }
        });
}

// ==================== ADMIN FILTERS ====================
function applyFilters() {
    const type = document.getElementById('filterType')?.value || '';
    const status = document.getElementById('filterStatus')?.value || '';
    const search = document.getElementById('searchInput')?.value || '';
    
    let url = window.location.pathname;
    let params = [];
    
    if (type) params.push(`type=${type}`);
    if (status) params.push(`status=${status}`);
    if (search) params.push(`search=${encodeURIComponent(search)}`);
    if (adminCurrentPage > 1) params.push(`page=${adminCurrentPage}`);
    
    if (params.length > 0) {
        window.location.href = url + '?' + params.join('&');
    } else {
        window.location.href = url;
    }
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

// ==================== ADMIN EXPORT ====================
function exportAdminReport(type) {
    if (type === 'users') {
        window.location.href = '../api/admin.php?action=exportUsers&format=csv';
    } else if (type === 'transactions') {
        window.location.href = '../api/admin.php?action=exportTransactions&format=csv';
    }
}

// ==================== ADMIN BULK ACTIONS ====================
function selectAllUsers() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const selectAll = document.getElementById('selectAll');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function bulkAction() {
    const action = document.getElementById('bulkAction').value;
    if (!action) {
        alert('Please select an action');
        return;
    }
    
    const selectedUsers = [];
    document.querySelectorAll('.user-checkbox:checked').forEach(checkbox => {
        selectedUsers.push(checkbox.value);
    });
    
    if (selectedUsers.length === 0) {
        alert('Please select at least one user');
        return;
    }
    
    if (confirm(`Are you sure you want to ${action} ${selectedUsers.length} user(s)?`)) {
        fetch('../api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=bulkAction&bulk_action=${action}&user_ids=${selectedUsers.join(',')}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully performed ${action} on ${data.count} user(s)`);
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}

// ==================== ADMIN NOTIFICATION ====================
function showAdminNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 24px;
        background: ${type === 'success' ? '#22c55e' : '#ef4444'};
        color: white;
        border-radius: 12px;
        z-index: 9999;
        animation: fadeInUp 0.3s ease;
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}