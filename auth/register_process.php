<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/captcha.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $captcha = $_POST['captcha'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['register_error'] = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $_SESSION['register_error'] = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $_SESSION['register_error'] = 'Passwords do not match';
    } elseif (!verifyCaptcha($captcha)) {
        $_SESSION['register_error'] = 'Incorrect security answer';
    } else {
        $result = registerUser($name, $email, $password, $role);
        if ($result['success']) {
            $_SESSION['register_success'] = 'Registration successful! Please wait for admin approval.';
            // Log activity
            if (isset($result['user_id'])) {
                require_once '../includes/logger.php';
                logActivity($result['user_id'], $role, 'user_registered', ['email' => $email]);
            }
        } else {
            $_SESSION['register_error'] = $result['message'];
        }
    }
}

// Redirect back to referrer or index
$referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $referrer);
exit();

