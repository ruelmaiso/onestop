<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/logger.php';

// Require user login
requireRole('user');

$user = getCurrentUser();

// Handle booking cancellation
if (isset($_GET['cancel'])) {
    $bookingId = intval($_GET['cancel']);
    
    try {
        // Check if booking belongs to user and is pending
        $booking = fetchRow($conn, 
            "SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'pending'",
            [$bookingId, $_SESSION['user_id']]
        );
        
        if ($booking) {
            executeQuery($conn, 
                "UPDATE bookings SET status = 'cancelled' WHERE id = ?",
                [$bookingId]
            );
            
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'booking_cancelled', ['booking_id' => $bookingId]);
            $success = 'Booking cancelled successfully!';
        } else {
            $error = 'Booking not found or cannot be cancelled';
        }
    } catch (Exception $e) {
        $error = 'Failed to cancel booking';
    }
}

// Get all user's bookings
$bookings = fetchAll($conn, 
    "SELECT b.*, s.title as service_title, s.price, c.name as company_name 
     FROM bookings b 
     JOIN services s ON b.service_id = s.id 
     JOIN companies c ON s.company_id = c.id 
     WHERE b.user_id = ? 
     ORDER BY b.created_at DESC", 
    [$_SESSION['user_id']]
);

// Get booking statistics
$stats = fetchRow($conn, 
    "SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_bookings,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_bookings
     FROM bookings 
     WHERE user_id = ?", 
    [$_SESSION['user_id']]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - OneStop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .navbar-brand {
            font-weight: bold;
        }
        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .booking-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand text-primary" href="../index.php">
                <i class="fas fa-globe me-2"></i>OneStop
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_bookings.php">My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
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

    <div class="container my-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-calendar-check me-2"></i>My Bookings</h2>
                <p class="text-muted">View and manage all your bookings</p>
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

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-2">
                <div class="dashboard-card p-3 text-center">
                    <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                    <h3><?php echo $stats['total_bookings']; ?></h3>
                    <small class="text-muted">Total Bookings</small>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="dashboard-card p-3 text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h3><?php echo $stats['approved_bookings']; ?></h3>
                    <small class="text-muted">Approved</small>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="dashboard-card p-3 text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h3><?php echo $stats['pending_bookings']; ?></h3>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="dashboard-card p-3 text-center">
                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                    <h3><?php echo $stats['declined_bookings']; ?></h3>
                    <small class="text-muted">Declined</small>
                </div>
            </div>
        </div>

        <!-- Bookings List -->
        <?php if (empty($bookings)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No bookings yet</h5>
                <p class="text-muted">Start by exploring our services</p>
                <a href="../index.php" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Find Services
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5><?php echo htmlspecialchars($booking['service_title']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($booking['company_name']); ?>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-calendar me-2"></i>
                                <?php echo date('M j, Y', strtotime($booking['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-users me-2"></i><?php echo $booking['pax']; ?> Guest<?php echo $booking['pax'] > 1 ? 's' : ''; ?>
                            </p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="text-primary">$<?php echo number_format($booking['total_price'], 2); ?></h4>
                            <p class="text-muted small">Total Amount</p>
                            <p class="text-muted small mb-0">
                                <?php echo date('M j, Y', strtotime($booking['created_at'])); ?>
                            </p>
                        </div>
                        <div class="col-md-3 text-center">
                            <?php
                            $statusClass = '';
                            switch($booking['status']) {
                                case 'approved': $statusClass = 'success'; break;
                                case 'pending': $statusClass = 'warning'; break;
                                case 'declined': $statusClass = 'danger'; break;
                                case 'cancelled': $statusClass = 'secondary'; break;
                            }
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?> px-3 py-2 mb-2">
                                <i class="fas fa-circle me-1"></i><?php echo ucfirst($booking['status']); ?>
                            </span>
                            <br>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <a href="?cancel=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger mt-2" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($booking['notes']): ?>
                        <div class="mt-3 pt-3 border-top">
                            <strong>Special Requests:</strong>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($booking['notes']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-globe me-2"></i>OneStop</h5>
                    <p class="text-muted">Your one-stop destination for finding and booking services.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted">&copy; 2025 OneStop. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

