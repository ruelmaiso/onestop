<?php
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/logger.php';

// Log logout activity
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], $_SESSION['role'], 'user_logout');
}

// Logout user
logoutUser();

// Redirect to home page
header('Location: /one/index.php');
exit();
?>