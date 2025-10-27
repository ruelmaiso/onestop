<!-- the layout dashboard for company and admin should be in a dashboard style right the company still has no profile function also the admin dashboard activity log is incomplete it should be detailed there must be activity log 1 for overall activity booking not included and 2 is for the overall booking history. also the user dashboard still has no profile function too.. also modify or check this code in line 43:     logActivity($result['user_id'], $role, 'user_registered', ['email' => $email]);
} else {: i got error hhere but its working when registering a new user it the registration is working but this line code has some bug i, overall all the system works perfectly keep it up good job wait i also modify the admin to have a approve a pending user because you didnt create i created check it out the code i created  -->

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

// Get recent activity logs
$activityLogs = getActivityLogs(20, 0);

// Get statistics
$stats = fetchRow($conn, 
    "SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM companies WHERE approved = 1) as approved_companies,
        (SELECT COUNT(*) FROM services WHERE approved = 1) as approved_services,
        (SELECT COUNT(*) FROM bookings) as total_bookings,
        (SELECT COUNT(*) FROM companies WHERE approved = 0) as pending_companies,
        (SELECT COUNT(*) FROM services WHERE approved = 0) as pending_services
    "
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
        } catch (Exception $e) {
            $error = 'Failed to approve company';
        }
    } elseif ($action === 'reject_company') {
        try {
            executeQuery($conn, "DELETE FROM companies WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'company_rejected', ['company_id' => $id]);
            $success = 'Company rejected successfully!';
        } catch (Exception $e) {
            $error = 'Failed to reject company';
        }
    } elseif ($action === 'approve_service') {
        try {
            executeQuery($conn, "UPDATE services SET approved = 1 WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'service_approved', ['service_id' => $id]);
            $success = 'Service approved successfully!';
        } catch (Exception $e) {
            $error = 'Failed to approve service';
        }
    } elseif ($action === 'reject_service') {
        try {
            executeQuery($conn, "DELETE FROM services WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'service_rejected', ['service_id' => $id]);
            $success = 'Service rejected successfully!';
        } catch (Exception $e) {
            $error = 'Failed to reject service';
        }
    }
    elseif ($action === 'approve_user') {
        try {
            executeQuery($conn, "UPDATE users SET status = 1 WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'user_approve', ['user_id' => $id]);
            $success = 'User approved successfully!';
        } catch (Exception $e) {
            $error = 'Failed to approve User';
        }
    } elseif ($action === 'reject_user') {
        try {
            executeQuery($conn, "DELETE FROM users WHERE id = ?", [$id]);
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'user_rejected', ['user_id' => $id]);
            $success = 'user rejected successfully!';
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
    <title>Admin Dashboard - OneStop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        .stat-card.danger {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary" href="dashboard.php">
                <i class="fas fa-globe me-2"></i>OneStop
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">

                  
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                          
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <h2>Admin Dashboard</h2>
                <p class="text-muted">Manage the OneStop platform</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-2 mb-3">
                <div class="card stat-card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-3"></i>
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p class="mb-0">Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stat-card success dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-building fa-2x mb-3"></i>
                        <h3><?php echo $stats['approved_companies']; ?></h3>
                        <p class="mb-0">Companies</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stat-card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-list fa-2x mb-3"></i>
                        <h3><?php echo $stats['approved_services']; ?></h3>
                        <p class="mb-0">Services</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stat-card success dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x mb-3"></i>
                        <h3><?php echo $stats['total_bookings']; ?></h3>
                        <p class="mb-0">Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stat-card warning dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-3"></i>
                        <h3><?php echo $stats['pending_companies']; ?></h3>
                        <p class="mb-0">Pending Companies</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stat-card danger dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h3><?php echo $stats['pending_services']; ?></h3>
                        <p class="mb-0">Pending Services</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="row mb-5">
            <!-- Pending Companies -->
            <div class="col-lg-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Pending Companies</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingCompanies)): ?>
                            <p class="text-muted text-center py-3">No pending companies</p>
                        <?php else: ?>
                            <?php foreach ($pendingCompanies as $company): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <h6><?php echo htmlspecialchars($company['name']); ?></h6>
                                    <p class="text-muted small mb-2">Owner: <?php echo htmlspecialchars($company['user_name']); ?></p>
                                    <p class="text-muted small mb-2">Email: <?php echo htmlspecialchars($company['user_email']); ?></p>
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars(substr($company['info'], 0, 100)); ?>...</p>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                                        <button type="submit" name="action" value="approve_company" class="btn btn-sm btn-success me-2">
                                            Approve
                                        </button>
                                        <button type="submit" name="action" value="reject_company" class="btn btn-sm btn-danger">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

             <!-- Pending Users -->
             <div class="col-lg-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Pending User</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingUser)): ?>
                            <p class="text-muted text-center py-3">No pending User</p>
                        <?php else: ?>
                            <?php foreach ($pendingUser as $user): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <h6>ID: <?php echo htmlspecialchars($user['id']); ?></h6>
                                    <p class="text-muted small mb-2">name: <?php echo htmlspecialchars($user['user_name']); ?></p>
                                    <p class="text-muted small mb-2">Email: <?php echo htmlspecialchars($user['user_email']); ?></p>
                                   
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="action" value="approve_user" class="btn btn-sm btn-success me-2">
                                            Approve
                                        </button>
                                        <button type="submit" name="action" value="reject_user" class="btn btn-sm btn-danger">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pending Services -->
            <div class="col-lg-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Pending Services</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingServices)): ?>
                            <p class="text-muted text-center py-3">No pending services</p>
                        <?php else: ?>
                            <?php foreach ($pendingServices as $service): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <h6><?php echo htmlspecialchars($service['title']); ?></h6>
                                    <p class="text-muted small mb-2">Company: <?php echo htmlspecialchars($service['company_name']); ?></p>
                                    <p class="text-muted small mb-2">Price: $<?php echo number_format($service['price'], 2); ?></p>
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                        <button type="submit" name="action" value="approve_service" class="btn btn-sm btn-success me-2">
                                            Approve
                                        </button>
                                        <button type="submit" name="action" value="reject_service" class="btn btn-sm btn-danger">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activityLogs)): ?>
                            <p class="text-muted text-center py-3">No recent activity</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activityLogs as $log): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y H:i', strtotime($log['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td><?php echo htmlspecialchars($log['ip']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>