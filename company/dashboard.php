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
    header('Location: ../index.php');
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
            // Refresh page to show updated data
            header('Location: dashboard.php');
            exit();
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
    <link href="../includes/dashboard.css" rel="stylesheet">
    <style>
        .company-info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .recent-bookings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="dashboard-main">
            <div class="dashboard-content">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>Welcome, <?php echo htmlspecialchars($company['name']); ?>!</h2>
                            <p class="text-muted">Manage your services and bookings</p>
                        </div>
                        <button class="btn btn-primary d-lg-none" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i> Menu
                        </button>
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
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <i class="fas fa-list stat-icon"></i>
                            <div class="stat-value"><?php echo $stats['total_services']; ?></div>
                            <div class="stat-label">Total Services</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card" style="border-left-color: #28a745;">
                            <i class="fas fa-check-circle stat-icon" style="color: #28a745;"></i>
                            <div class="stat-value"><?php echo $stats['approved_services']; ?></div>
                            <div class="stat-label">Approved Services</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card" style="border-left-color: #17a2b8;">
                            <i class="fas fa-calendar-check stat-icon" style="color: #17a2b8;"></i>
                            <div class="stat-value"><?php echo $stats['total_bookings']; ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card" style="border-left-color: #ffc107;">
                            <i class="fas fa-clock stat-icon" style="color: #ffc107;"></i>
                            <div class="stat-value"><?php echo $stats['pending_bookings']; ?></div>
                            <div class="stat-label">Pending Bookings</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="content-card">
                    <div class="recent-bookings-header">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Recent Bookings</h5>
                        <a href="services.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i>Manage Services
                        </a>
                    </div>
                    
                    <?php if (empty($bookings)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No bookings yet</h5>
                            <p class="text-muted">Bookings will appear here when customers make reservations</p>
                            <a href="services/add.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Your First Service
                            </a>
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
                                            <td><strong><?php echo htmlspecialchars($booking['service_title']); ?></strong></td>
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
                                                            <i class="fas fa-check me-1"></i>Approve
                                                        </button>
                                                        <button type="submit" name="action" value="declined" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times me-1"></i>Decline
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
   
</body>
</html>
