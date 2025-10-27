<?php
require_once 'db.php';
require_once 'session.php';

// Function to register a new user
function registerUser($name, $email, $password, $role = 'user') {
    global $conn;
    
    // Check if email already exists
    $existingUser = fetchRow($conn, "SELECT id FROM users WHERE email = ?", [$email]);
    if ($existingUser) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = executeQuery($conn, 
            "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)", 
            [$name, $email, $hashedPassword, $role, 0]
        );
        
        $userId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'user_id' => $userId];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

// Function to login user
function loginUser($email, $password) {
    global $conn;
    
    $user = fetchRow($conn, "SELECT * FROM users WHERE email = ? AND status = 1", [$email]);
    
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    
    return ['success' => true, 'user' => $user];
}

// Function to logout user
function logoutUser() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

// Function to update user profile
function updateUserProfile($userId, $name, $email) {
    global $conn;
    
    try {
        executeQuery($conn, 
            "UPDATE users SET name = ?, email = ? WHERE id = ?", 
            [$name, $email, $userId]
        );
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
    }
}

// Function to change password
function changePassword($userId, $currentPassword, $newPassword) {
    global $conn;
    
    // Get current password hash
    $user = fetchRow($conn, "SELECT password FROM users WHERE id = ?", [$userId]);
    
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    try {
        executeQuery($conn, 
            "UPDATE users SET password = ? WHERE id = ?", 
            [$hashedPassword, $userId]
        );
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Password change failed: ' . $e->getMessage()];
    }
}
?>