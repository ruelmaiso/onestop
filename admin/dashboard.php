<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/logger.php';

// Require admin role
requireRole('admin');

$user = getCurrentUser();

// Get statistics
$stats = fetchRow($conn, 
    "SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM companies WHERE approved = 1) as approved_companies,
        (SELECT COUNT(*) FROM services WHERE approved = 1) as approved_services,
        (SELECT COUNT(*) FROM bookings) as total_bookings,
        (SELECT COUNT(*) FROM companies WHERE approved = 0) as pending_companies,
        (SELECT COUNT(*) FROM services WHERE approved = 0) as pending_services,
        (SELECT COUNT(*) FROM users WHERE status = 0) as pending_users
    "
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OneStop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../includes/dashboard.css" rel="stylesheet">
    <style>
        .stat-grid {
    display: flex;
    flex-wrap: wrap;            /* allow cards to wrap to next line */
    gap: .5rem;                  /* spacing between cards (preferred to margins) */
    align-items: stretch;       /* keep cards same height */
    margin-bottom: 1.5rem;
    /* optional max-width & centering:
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    */
  }
  .stat-card {
  flex: 1 1 220px;            /* grow, shrink, base 220px — adjust for desired columns */
  min-width: 200px;           /* prevent cards from getting too narrow */
  display: flex;
  flex-direction: column;     /* icon, value, label stacked inside card */
  justify-content: center;    /* vertical centering of content */
  gap: 0.4rem;
  padding: 22px;
}
/* If you want exact columns at certain breakpoints, you can override: */
@media (min-width: 1400px) {
  .stat-card { flex-basis: 15%; } /* example: 6 columns on very wide screens */
}
@media (max-width: 575px) {
  .stat-card { flex: 1 1 100%; }  /* on tiny screens each card is full width */
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="dashboard-main">
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>Admin Dashboard</h2>
                            <p class="text-muted">Manage the OneStop platform</p>
                        </div>
                        <button class="btn btn-primary d-lg-none" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i> Menu
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stat-grid mb-4">
                    <div class="stat-card">
                        <i class="fas fa-users stat-icon"></i>
                        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #28a745;">
                        <i class="fas fa-building stat-icon" style="color: #28a745;"></i>
                        <div class="stat-value"><?php echo $stats['approved_companies']; ?></div>
                        <div class="stat-label">Companies</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #17a2b8;">
                        <i class="fas fa-list stat-icon" style="color: #17a2b8;"></i>
                        <div class="stat-value"><?php echo $stats['approved_services']; ?></div>
                        <div class="stat-label">Services</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #28a745;">
                        <i class="fas fa-calendar-check stat-icon" style="color: #28a745;"></i>
                        <div class="stat-value"><?php echo $stats['total_bookings']; ?></div>
                        <div class="stat-label">Bookings</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #ffc107;">
                        <i class="fas fa-clock stat-icon" style="color: #ffc107;"></i>
                        <div class="stat-value"><?php echo $stats['pending_companies']; ?></div>
                        <div class="stat-label">Pending Companies</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #dc3545;">
                        <i class="fas fa-exclamation-triangle stat-icon" style="color: #dc3545;"></i>
                        <div class="stat-value"><?php echo $stats['pending_services']; ?></div>
                        <div class="stat-label">Pending Services</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #dc3545;">
                        <i class="fas fa-exclamation-triangle stat-icon" style="color: #dc3545;"></i>
                        <div class="stat-value"><?php echo $stats['pending_users']; ?></div>
                        <div class="stat-label">Pending Users</div>
                    </div>
                </div>

                <!-- Quick Actions -->
           <div class="row">
                    <div class="col-lg-4 mb-3">
                        <div class="content-card">
                            <div class="text-center">
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h5>Pending Approvals</h5>
                                <p class="text-muted">Review and approve pending requests</p>
                                <a href="pending_approvals.php" class="btn btn-primary w-100">
                                    <i class="fas fa-arrow-right me-2"></i>View Pending
                                </a>
                            </div>
                        </div>
                    </div>
                <div class="col-lg-4 mb-3">
                        <div class="content-card">
                            <div class="text-center">
                                <i class="fas fa-history fa-3x text-info mb-3"></i>
                                <h5>Activity Logs</h5>
                                <p class="text-muted">View platform activity logs</p>
                                <a href="activity_logs.php" class="btn btn-primary w-100">
                                    <i class="fas fa-arrow-right me-2"></i>View Logs
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div class="content-card">
                            <div class="text-center">
                                <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                                <h5>Booking History</h5>
                                <p class="text-muted">View all booking records</p>
                                <a href="booking_history.php" class="btn btn-primary w-100">
                                    <i class="fas fa-arrow-right me-2"></i>View Bookings
                                </a>
                            </div>
                        </div>
                    </div> 
                </div> 
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
