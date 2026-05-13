<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'register') {
    $fullname = sanitizeInput($_POST['fullname']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    $errors = [];
    if (empty($fullname)) $errors[] = "Full name required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email";
    if (strlen($password) < 6) $errors[] = "Password must be 6+ characters";
    if ($password !== $confirm) $errors[] = "Passwords don't match";
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) $errors[] = "Email already exists";
    
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (fullname, email, password) VALUES ('$fullname', '$email', '$hashed')";
        
        if (mysqli_query($conn, $query)) {
            $user_id = mysqli_insert_id($conn);
            $defaults = mysqli_query($conn, "SELECT * FROM categories WHERE user_id IS NULL");
            while ($cat = mysqli_fetch_assoc($defaults)) {
                mysqli_query($conn, "INSERT INTO categories (user_id, name, icon, type) VALUES ($user_id, '{$cat['name']}', '{$cat['icon']}', '{$cat['type']}')");
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'errors' => ['Registration failed']]);
        }
    } else {
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
}

else if ($action === 'login') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_currency'] = $row['currency']; // ADD THIS LINE
            $_SESSION['last_activity'] = time();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'errors' => ['Invalid password']]);
        }
    } else {
        echo json_encode(['success' => false, 'errors' => ['User not found']]);
    }
}

else if ($action === 'forgot') {
    $email = sanitizeInput($_POST['email']);
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    mysqli_query($conn, "DELETE FROM password_resets WHERE email='$email'");
    mysqli_query($conn, "INSERT INTO password_resets (email, token, expires_at) VALUES ('$email', '$token', '$expires')");
    
    echo json_encode(['success' => true, 'message' => 'Reset link sent (simulated)']);
}
?>