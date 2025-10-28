<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/logger.php';

// Require admin role
requireRole('admin');

$user = getCurrentUser();

// Get pending companies
$pendingCompanies = fetchAll($conn, 
    "SELECT c.*, u.name as user_name, u.email as user_email 
     FROM companies c 
     JOIN users u ON c.user_id = u.id 
     WHERE c.approved = 0 
     ORDER BY c.created_at DESC"
);

// Get pending services
$pendingServices = fetchAll($conn, 
    "SELECT s.*, c.name as company_name 
     FROM services s 
     JOIN companies c ON s.company_id = c.id 
     WHERE s.approved = 0 
     ORDER BY s.created_at DESC"
);

$pendingUser = fetchAll($conn, 
    "SELECT id, name as user_name, email as user_email 
     FROM users
     WHERE status = 0 
     ORDER BY created_at DESC"
);

// Handle approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = intval($_POST['id']);
    
    if ($action === 'approve_company') {
        try {
            executeQuery($conn, "UPDATE companies SET approved = 1 WHERE id = ?", [$id]);
            executeQuery($conn, "UPDATE users SET status = 1 WHERE id = (SELECT user_id FROM companies WHERE id = ?)", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'company_approved', ['company_id' => $id]);
            $success = 'Company approved successfully!';
            header('Location: pending_approvals.php');
            exit();
        } catch (Exception $e) {
            $error = 'Failed to approve company';
        }
    } elseif ($action === 'reject_company') {
        try {
            executeQuery($conn, "DELETE FROM companies WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'company_rejected', ['company_id' => $id]);
            $success = 'Company rejected successfully!';
            header('Location: pending_approvals.php');
            exit();
        } catch (Exception $e) {
            $error = 'Failed to reject company';
        }
    } elseif ($action === 'approve_service') {
        try {
            executeQuery($conn, "UPDATE services SET approved = 1 WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'service_approved', ['service_id' => $id]);
            $success = 'Service approved successfully!';
            header('Location: pending_approvals.php');
            exit();
        } catch (Exception $e) {
            $error = 'Failed to approve service';
        }
    } elseif ($action === 'reject_service') {
        try {
            executeQuery($conn, "DELETE FROM services WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'service_rejected', ['service_id' => $id]);
            $success = 'Service rejected successfully!';
            header('Location: pending_approvals.php');
            exit();
        } catch (Exception $e) {
            $error = 'Failed to reject service';
        }
    } elseif ($action === 'approve_user') {
        try {
            executeQuery($conn, "UPDATE users SET status = 1 WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'user_approved', ['user_id' => $id]);
            $success = 'User approved successfully!';
            header('Location: pending_approvals.php');
            exit();
        } catch (Exception $e) {
            $error = 'Failed to approve User';
        }
    } elseif ($action === 'reject_user') {
        try {
            executeQuery($conn, "DELETE FROM users WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'user_rejected', ['user_id' => $id]);
            $success = 'User rejected successfully!';
            header('Location: pending_approvals.php');
            exit();
        } catch (Exception $e) {
            $error = 'Failed to reject user';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - Admin - OneStop</title>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2><i class="fas fa-clock me-2"></i>Pending Approvals</h2>
                            <p class="text-muted">Review and approve pending requests</p>
                        </div>
                        <button class="btn btn-primary d-lg-none" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i> Menu
                        </button>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="pending-grid">
                    <!-- Pending Companies -->
                    <div class="content-card">
                        <h5 class="mb-3"><i class="fas fa-building me-2"></i>Pending Companies</h5>
                        <?php if (empty($pendingCompanies)): ?>
                            <p class="text-muted text-center py-3">No pending companies</p>
                        <?php else: ?>
                            <?php foreach ($pendingCompanies as $company): ?>
                                <div class="pending-item">
                                    <h6><?php echo htmlspecialchars($company['name']); ?></h6>
                                    <p class="mb-1"><strong>Owner:</strong> <?php echo htmlspecialchars($company['user_name']); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($company['user_email']); ?></p>
                                    <p class="mb-3 small"><?php echo htmlspecialchars(substr($company['info'], 0, 100)); ?>...</p>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                                        <button type="submit" name="action" value="approve_company" class="btn btn-sm btn-success me-2">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                        <button type="submit" name="action" value="reject_company" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Users -->
                    <div class="content-card">
                        <h5 class="mb-3"><i class="fas fa-user me-2"></i>Pending Users</h5>
                        <?php if (empty($pendingUser)): ?>
                            <p class="text-muted text-center py-3">No pending users</p>
                        <?php else: ?>
                            <?php foreach ($pendingUser as $u): ?>
                                <div class="pending-item">
                                    <h6>ID: <?php echo htmlspecialchars($u['id']); ?></h6>
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($u['user_name']); ?></p>
                                    <p class="mb-3"><strong>Email:</strong> <?php echo htmlspecialchars($u['user_email']); ?></p>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" name="action" value="approve_user" class="btn btn-sm btn-success me-2">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                        <button type="submit" name="action" value="reject_user" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Services -->
                    <div class="content-card">
                        <h5 class="mb-3"><i class="fas fa-list me-2"></i>Pending Services</h5>
                        <?php if (empty($pendingServices)): ?>
                            <p class="text-muted text-center py-3">No pending services</p>
                        <?php else: ?>
                            <?php foreach ($pendingServices as $service): ?>
                                <div class="pending-item">
                                    <h6><?php echo htmlspecialchars($service['title']); ?></h6>
                                    <p class="mb-1"><strong>Company:</strong> <?php echo htmlspecialchars($service['company_name']); ?></p>
                                    <p class="mb-1"><strong>Price:</strong> $<?php echo number_format($service['price'], 2); ?></p>
                                    <p class="mb-3 small"><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                        <button type="submit" name="action" value="approve_service" class="btn btn-sm btn-success me-2">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                        <button type="submit" name="action" value="reject_service" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>

