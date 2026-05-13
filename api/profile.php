<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get profile data
if ($action === 'get') {
    $result = mysqli_query($conn, "SELECT fullname, email, currency, profile_image FROM users WHERE id = $user_id");
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'success' => true,
            'fullname' => $row['fullname'],
            'email' => $row['email'],
            'currency' => $row['currency'],
            'profile_image' => $row['profile_image']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
}

// Update profile
else if ($action === 'update') {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    
    $query = "UPDATE users SET fullname = '$fullname', email = '$email', currency = '$currency' WHERE id = $user_id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['user_name'] = $fullname;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_currency'] = $currency; // ADD THIS LINE
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
}

// Change password
else if ($action === 'changePassword') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    // Get current password from database
    $result = mysqli_query($conn, "SELECT password FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($result);
    
    if (!password_verify($current, $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    } 
    else if (strlen($new) < 6) {
        echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters']);
    } 
    else if ($new !== $confirm) {
        echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
    } 
    else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = '$hashed' WHERE id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update password']);
        }
    }
}

// Upload profile image
else if ($action === 'uploadImage') {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            // Create unique filename
            $filename = time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = __DIR__ . '/../uploads/' . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Delete old profile image if not default
                $result = mysqli_query($conn, "SELECT profile_image FROM users WHERE id = $user_id");
                $old_image = mysqli_fetch_assoc($result)['profile_image'];
                if ($old_image && $old_image != 'default.png' && file_exists(__DIR__ . '/../uploads/' . $old_image)) {
                    unlink(__DIR__ . '/../uploads/' . $old_image);
                }
                
                // Update database
                $query = "UPDATE users SET profile_image = '$filename' WHERE id = $user_id";
                if (mysqli_query($conn, $query)) {
                    echo json_encode(['success' => true, 'image' => $filename]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Database update failed']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: jpg, jpeg, png, gif']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    }
}

// Unknown action
else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>