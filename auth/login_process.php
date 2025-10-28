<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/captcha.php';
require_once '../includes/logger.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['captcha'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } elseif (!verifyCaptcha($captcha)) {
        $error = 'Incorrect security answer';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            // Log successful login
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'user_login', ['email' => $email]);
            
            // Redirect based on role
            if ($_SESSION['role'] === 'admin') {
                header('Location: /one/admin/dashboard.php');
            } elseif ($_SESSION['role'] === 'company') {
                header('Location: /one/company/dashboard.php');
            } else {
                header('Location: /one/user/dashboard.php');
            }
            exit();
        } else {
            // Log failed login attempt
            logActivity(null, 'guest', 'failed_login_attempt', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']]);
            $error = $result['message'];
        }
    }
}
if (isset($error)) {
    $_SESSION['login_error'] = $error;
}

// Redirect back to referrer or index
$referrer = $_SERVER['HTTP_REFERER'] ?? '/one/index.php';
header('Location: ' . $referrer);
exit();


