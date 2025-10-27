<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/logger.php';

// Require company role
requireRole('company');

$user = getCurrentUser();

// Get company information
$company = fetchRow($conn, "SELECT * FROM companies WHERE user_id = ?", [$_SESSION['user_id']]);

if (!$company) {
    header('Location: /index.php');
    exit();
}

// Get company's services
$services = fetchAll($conn, 
    "SELECT * FROM services WHERE company_id = ? ORDER BY created_at DESC", 
    [$company['id']]
);

// Get company's bookings
$bookings = fetchAll($conn, 
    "SELECT b.*, s.title as service_title, u.name as customer_name, u.email as customer_email 
     FROM bookings b 
     JOIN services s ON b.service_id = s.id 
     JOIN users u ON b.user_id = u.id 
     WHERE s.company_id = ? 
     ORDER BY b.created_at DESC", 
    [$company['id']]
);

// Get statistics
$stats = fetchRow($conn, 
    "SELECT 
        COUNT(DISTINCT s.id) as total_services,
        SUM(CASE WHEN s.approved = 1 THEN 1 ELSE 0 END) as approved_services,
        COUNT(b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved_bookings,
        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_bookings
     FROM services s 
     LEFT JOIN bookings b ON s.id = b.service_id 
     WHERE s.company_id = ?", 
    [$company['id']]
);

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $bookingId = intval($_POST['booking_id']);
    $action = $_POST['action'];
    
    if (in_array($action, ['approved', 'declined'])) {
        try {
            executeQuery($conn, 
                "UPDATE bookings SET status = ? WHERE id = ? AND service_id IN (SELECT id FROM services WHERE company_id = ?)", 
                [$action, $bookingId, $company['id']]
            );
            
            // Log activity
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'booking_' . $action, ['booking_id' => $bookingId]);
            
            $success = 'Booking status updated successfully!';
        } catch (Exception $e) {
            $error = 'Failed to update booking status';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard - OneStop</title>
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
                    
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">My Services</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
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
                <h2>Welcome, <?php echo htmlspecialchars($company['name']); ?>!</h2>
                <p class="text-muted">Manage your services and bookings</p>
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
            <div class="col-md-3 mb-3">
                <div class="card stat-card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-list fa-2x mb-3"></i>
                        <h3><?php echo $stats['total_services']; ?></h3>
                        <p class="mb-0">Total Services</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card success dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-3"></i>
                        <h3><?php echo $stats['approved_services']; ?></h3>
                        <p class="mb-0">Approved Services</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x mb-3"></i>
                        <h3><?php echo $stats['total_bookings']; ?></h3>
                        <p class="mb-0">Total Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card warning dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-3"></i>
                        <h3><?php echo $stats['pending_bookings']; ?></h3>
                        <p class="mb-0">Pending Bookings</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <a href="services/add.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Add New Service
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="services.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-list me-2"></i>Manage Services
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="#bookings" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-calendar me-2"></i>View Bookings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="row" id="bookings">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Bookings</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No bookings yet</h5>
                                <p class="text-muted">Bookings will appear here when customers make reservations</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Service</th>
                                            <th>Customer</th>
                                            <th>Dates</th>
                                            <th>Guests</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['service_title']); ?></strong>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo date('M j', strtotime($booking['start_date'])); ?> - 
                                                    <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                                </td>
                                                <td><?php echo $booking['pax']; ?></td>
                                                <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch($booking['status']) {
                                                        case 'approved': $statusClass = 'success'; break;
                                                        case 'pending': $statusClass = 'warning'; break;
                                                        case 'declined': $statusClass = 'danger'; break;
                                                        case 'cancelled': $statusClass = 'secondary'; break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                            <button type="submit" name="action" value="approved" class="btn btn-sm btn-success me-1">
                                                                Approve
                                                            </button>
                                                            <button type="submit" name="action" value="declined" class="btn btn-sm btn-danger">
                                                                Decline
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">No actions</span>
                                                    <?php endif; ?>
                                                </td>
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