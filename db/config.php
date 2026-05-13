<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expense_tracker');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

define('CURRENCY', 'Rs');
define('SITE_NAME', 'Smart Expense Tracker');
define('SESSION_TIMEOUT', 1800);

// Check session timeout for regular users
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) && !isset($_SESSION['is_admin'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header("Location: ../login.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Check session timeout for admin
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_last_activity'])) {
    if (time() - $_SESSION['admin_last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header("Location: ../admin/login.php?timeout=1");
        exit();
    }
    $_SESSION['admin_last_activity'] = time();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header("Location: ../admin/login.php");
        exit();
    }
}

function sanitizeInput($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}
?>