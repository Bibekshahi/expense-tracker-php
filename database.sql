-- ============================================
-- SMART EXPENSE TRACKER - DATABASE SCHEMA
-- For BCA 6th Semester Project
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS expense_tracker;
USE expense_tracker;

-- ============================================
-- 1. USERS TABLE
-- Stores all user information (regular users and admins)
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default.png',
    currency VARCHAR(5) DEFAULT 'Rs',
    is_admin TINYINT DEFAULT 0,
    status ENUM('active', 'suspended') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. CATEGORIES TABLE
-- Stores transaction categories (Food, Shopping, Salary, etc.)
-- ============================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50) DEFAULT '📁',
    color VARCHAR(7) DEFAULT '#4f46e5',
    type ENUM('income', 'expense') DEFAULT 'expense',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 3. TRANSACTIONS TABLE
-- Stores all income and expense records
-- ============================================
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    notes TEXT,
    transaction_date DATE NOT NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_period VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ============================================
-- 4. BUDGETS TABLE
-- Stores monthly budget limits per user
-- ============================================
CREATE TABLE budgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    monthly_budget DECIMAL(12,2) NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 5. ALERTS TABLE
-- Stores budget alert notifications for users
-- ============================================
CREATE TABLE alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 6. ADMIN_LOGS TABLE
-- Stores all admin activities for security auditing
-- ============================================
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 7. SYSTEM_SETTINGS TABLE
-- Stores global system configuration
-- ============================================
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 8. SAVINGS_GOALS TABLE (Future Enhancement)
-- Stores user savings goals
-- ============================================
CREATE TABLE savings_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    goal_name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    current_amount DECIMAL(12,2) DEFAULT 0,
    target_date DATE,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 9. RECURRING_TRANSACTIONS TABLE (Future Enhancement)
-- Stores recurring bills and income
-- ============================================
CREATE TABLE recurring_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL,
    next_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'paused', 'ended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ============================================
-- INSERT DEFAULT CATEGORIES (Global categories for all users)
-- ============================================
INSERT INTO categories (user_id, name, icon, type) VALUES
(NULL, 'Food', '🍔', 'expense'),
(NULL, 'Transport', '🚗', 'expense'),
(NULL, 'Shopping', '🛍️', 'expense'),
(NULL, 'Bills', '💡', 'expense'),
(NULL, 'Health', '🏥', 'expense'),
(NULL, 'Education', '📚', 'expense'),
(NULL, 'Entertainment', '🎬', 'expense'),
(NULL, 'Salary', '💰', 'income'),
(NULL, 'Investment', '📈', 'income'),
(NULL, 'Miscellaneous', '📌', 'expense');

-- ============================================
-- INSERT DEFAULT SYSTEM SETTINGS
-- ============================================
INSERT INTO system_settings (setting_key, setting_value) VALUES
('site_name', 'Smart Expense Tracker'),
('site_description', 'Personal Finance Management System'),
('maintenance_mode', '0'),
('default_currency', 'Rs'),
('budget_warning_threshold', '80'),
('budget_danger_threshold', '100'),
('session_timeout', '1800');

-- ============================================
-- CREATE DEFAULT ADMIN USER
-- Email: admin@expense.com
-- Password: Admin@123
-- ============================================
INSERT INTO users (fullname, email, password, is_admin, status) 
VALUES ('Super Admin', 'admin@expense.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'active');

-- ============================================
-- CREATE SAMPLE REGULAR USER
-- Email: user@expense.com
-- Password: User@123
-- ============================================
INSERT INTO users (fullname, email, password, is_admin, status) 
VALUES ('Demo User', 'user@expense.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'active');

-- ============================================
-- INSERT SAMPLE CATEGORIES FOR DEMO USER (user_id = 2)
-- ============================================
INSERT INTO categories (user_id, name, icon, type) VALUES
(2, 'Freelancing', '💻', 'income'),
(2, 'Rent', '🏠', 'expense'),
(2, 'Gym', '💪', 'expense'),
(2, 'Coffee', '☕', 'expense');

-- ============================================
-- INSERT SAMPLE TRANSACTIONS FOR DEMO USER (user_id = 2)
-- ============================================

-- Income Transactions
INSERT INTO transactions (user_id, category_id, title, amount, type, transaction_date, notes) VALUES
(2, (SELECT id FROM categories WHERE user_id = 2 AND name = 'Freelancing'), 'Website Project', 25000, 'income', '2026-05-01', 'Completed client website'),
(2, (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Salary'), 'Monthly Salary', 50000, 'income', '2026-05-05', 'May salary from company'),
(2, (SELECT id FROM categories WHERE user_id = 2 AND name = 'Freelancing'), 'App Development', 15000, 'income', '2026-05-15', 'Mobile app project');

-- Expense Transactions
INSERT INTO transactions (user_id, category_id, title, amount, type, transaction_date, notes) VALUES
(2, (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Food'), 'Groceries', 5000, 'expense', '2026-05-02', 'Weekly groceries'),
(2, (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Transport'), 'Fuel', 3000, 'expense', '2026-05-03', 'Petrol for car'),
(2, (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Shopping'), 'New Shoes', 4500, 'expense', '2026-05-07', 'Nike shoes'),
(2, (SELECT id FROM categories WHERE user_id = 2 AND name = 'Rent'), 'Apartment Rent', 15000, 'expense', '2026-05-10', 'Monthly rent'),
(2, (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Bills'), 'Electricity Bill', 2000, 'expense', '2026-05-12', 'NEA bill'),
(2, (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Bills'), 'Internet Bill', 1500, 'expense', '2026-05-12', 'WiFi bill'),
(2, (SELECT id FROM categories WHERE user_id = 2 AND name = 'Gym'), 'Gym Membership', 3000, 'expense', '2026-05-14', 'Monthly gym fee'),
(2, (SELECT id FROM categories WHERE user_id = 2 AND name = 'Coffee'), 'Coffee Shops', 1000, 'expense', '2026-05-16', 'Weekend coffee'),
(2, (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Health'), 'Medicine', 800, 'expense', '2026-05-18', 'Pharmacy'),
(2, (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Entertainment'), 'Movie', 1200, 'expense', '2026-05-20', 'Cinema tickets');

-- ============================================
-- INSERT SAMPLE BUDGET FOR DEMO USER (May 2026)
-- ============================================
INSERT INTO budgets (user_id, monthly_budget, month, year) VALUES
(2, 50000, 5, 2026),
(2, 50000, 6, 2026);

-- ============================================
-- INSERT SAMPLE ALERTS FOR DEMO USER
-- ============================================
INSERT INTO alerts (user_id, message, status) VALUES
(2, 'You have reached 80% of your monthly budget!', 'unread'),
(2, 'Budget limit exceeded! Please review your expenses.', 'unread');

-- ============================================
-- INSERT SAMPLE SAVINGS GOAL FOR DEMO USER
-- ============================================
INSERT INTO savings_goals (user_id, goal_name, target_amount, current_amount, target_date, status) VALUES
(2, 'New Laptop', 100000, 45000, '2026-12-31', 'active'),
(2, 'Vacation Trip', 50000, 15000, '2026-08-31', 'active');

-- ============================================
-- VIEW ALL DATA (For verification)
-- ============================================
SELECT '=== USERS ===' as '';
SELECT id, fullname, email, is_admin, status FROM users;

SELECT '=== CATEGORIES ===' as '';
SELECT id, user_id, name, icon, type FROM categories;

SELECT '=== TRANSACTIONS ===' as '';
SELECT id, user_id, title, amount, type, transaction_date FROM transactions;

SELECT '=== BUDGETS ===' as '';
SELECT id, user_id, monthly_budget, month, year FROM budgets;

SELECT '=== ALERTS ===' as '';
SELECT id, user_id, message, status FROM alerts;

SELECT '=== SAVINGS GOALS ===' as '';
SELECT id, user_id, goal_name, target_amount, current_amount, status FROM savings_goals;

-- ============================================
-- INDEXES FOR BETTER PERFORMANCE
-- ============================================
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);
CREATE INDEX idx_budgets_user_month ON budgets(user_id, month, year);
CREATE INDEX idx_alerts_user_id ON alerts(user_id);
CREATE INDEX idx_categories_user_id ON categories(user_id);

-- ============================================
-- END OF DATABASE SCHEMA
-- ============================================
