<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/logger.php';

// Require admin role
requireRole('admin');

$user = getCurrentUser();

// Get booking history
$bookingHistory = fetchAll($conn,
    "SELECT b.*, s.title as service_title, c.name as company_name, u.name as customer_name, u.email as customer_email
     FROM bookings b
     JOIN services s ON b.service_id = s.id
     JOIN companies c ON s.company_id = c.id
     JOIN users u ON b.user_id = u.id
     ORDER BY b.created_at DESC
     LIMIT 100"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - Admin - OneStop</title>
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
                            <h2><i class="fas fa-calendar-check me-2"></i>Booking History</h2>
                            <p class="text-muted">All booking records across the platform</p>
                        </div>
                        <button class="btn btn-primary d-lg-none" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i> Menu
                        </button>
                    </div>
                </div>

                <div class="content-card">
                    <?php if (empty($bookingHistory)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No bookings yet</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Service</th>
                                        <th>Company</th>
                                        <th>Customer</th>
                                        <th>Dates</th>
                                        <th>Guests</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookingHistory as $booking): ?>
                                        <tr>
                                            <td><strong>#<?php echo $booking['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['company_name']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('M j', strtotime($booking['start_date'])); ?> - 
                                                <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                            </td>
                                            <td><?php echo $booking['pax']; ?></td>
                                            <td><strong>$<?php echo number_format($booking['total_price'], 2); ?></strong></td>
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
                                            <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
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
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>

