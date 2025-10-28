<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/logger.php';

// Require company role
requireRole('company');

$user = getCurrentUser();
$error = '';
$success = '';

// Get company information
$company = fetchRow($conn, "SELECT * FROM companies WHERE user_id = ?", [$_SESSION['user_id']]);

if (!$company) {
    header('Location: ../index.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    checkCSRF();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if email is already taken by another user
        $existingUser = fetchRow($conn, "SELECT id FROM users WHERE email = ? AND id != ?", [$email, $_SESSION['user_id']]);
        
        if ($existingUser) {
            $error = 'Email already exists';
        } else {
            $result = updateUserProfile($_SESSION['user_id'], $name, $email);
            if ($result['success']) {
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $success = 'Profile updated successfully!';
                // Refresh user data
                $user = getCurrentUser();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Handle company info update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_company') {
    checkCSRF();
    
    $companyName = trim($_POST['company_name'] ?? '');
    $info = trim($_POST['info'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($companyName)) {
        $error = 'Company name is required';
    } else {
        try {
            executeQuery($conn, 
                "UPDATE companies SET name = ?, info = ?, address = ?, phone = ? WHERE id = ?",
                [$companyName, $info, $address, $phone, $company['id']]
            );
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'company_info_updated', ['company_id' => $company['id']]);
            $success = 'Company information updated successfully!';
            
            // Refresh company data
            $company = fetchRow($conn, "SELECT * FROM companies WHERE user_id = ?", [$_SESSION['user_id']]);
        } catch (Exception $e) {
            $error = 'Failed to update company information';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    checkCSRF();
    
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All password fields are required';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $result = changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
        if ($result['success']) {
            $success = 'Password changed successfully!';
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - OneStop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../includes/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="dashboard-main">
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h2><i class="fas fa-user me-2"></i>Profile Settings</h2>
                    <p class="text-muted">Manage your account and company information</p>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- User Profile -->
                <div class="content-card mb-4">
                    <h5 class="mb-4"><i class="fas fa-user me-2"></i>Account Information</h5>
                    
                    <form method="POST" class="row">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Account
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Company Information -->
                <div class="content-card mb-4">
                    <h5 class="mb-4"><i class="fas fa-building me-2"></i>Company Information</h5>
                    
                    <form method="POST">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="update_company">
                        
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($company['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Company Information</label>
                            <textarea class="form-control" name="info" rows="4"><?php echo htmlspecialchars($company['info']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($company['address']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($company['phone']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Approval Status</label>
                            <input type="text" class="form-control" value="<?php echo $company['approved'] ? 'Approved' : 'Pending Approval'; ?>" disabled>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Company Info
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="content-card">
                    <h5 class="mb-4"><i class="fas fa-lock me-2"></i>Change Password</h5>
                    
                    <form method="POST" class="row">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

