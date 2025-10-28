<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/logger.php';

// Require user login
requireRole('user');

$user = getCurrentUser();

// Get user's bookings
$bookings = fetchAll($conn, 
    "SELECT b.*, s.title as service_title, s.price, c.name as company_name 
     FROM bookings b 
     JOIN services s ON b.service_id = s.id 
     JOIN companies c ON s.company_id = c.id 
     WHERE b.user_id = ? 
     ORDER BY b.created_at DESC 
     LIMIT 10", 
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
    <title>Dashboard - OneStop</title>
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
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
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
        .service-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .service-card:hover {
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../services.php">Services</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="my_bookings.php">My Bookings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <h2>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                <p class="text-muted">Manage your bookings and discover new services</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="stat-card dashboard-card">
                    <i class="fas fa-calendar-check fa-2x mb-3"></i>
                    <h3><?php echo $stats['total_bookings']; ?></h3>
                    <p class="mb-0">Total Bookings</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card success dashboard-card">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h3><?php echo $stats['approved_bookings']; ?></h3>
                    <p class="mb-0">Approved</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card warning dashboard-card">
                    <i class="fas fa-clock fa-2x mb-3"></i>
                    <h3><?php echo $stats['pending_bookings']; ?></h3>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card danger dashboard-card">
                    <i class="fas fa-times-circle fa-2x mb-3"></i>
                    <h3><?php echo $stats['declined_bookings']; ?></h3>
                    <p class="mb-0">Declined</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-5">
            <div class="col-md-4 mb-2">
                <div class="dashboard-card h-100 text-center p-4">
                    <i class="fas fa-search fa-3x text-primary mb-3"></i>
                    <h5>Find Services</h5>
                    <p class="text-muted">Discover amazing services</p>
                    <a href="../index.php" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Explore
                    </a>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="dashboard-card h-100 text-center p-4">
                    <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                    <h5>My Bookings</h5>
                    <p class="text-muted">View all your bookings</p>
                    <a href="my_bookings.php" class="btn btn-primary w-100">
                        <i class="fas fa-list me-2"></i>View Bookings
                    </a>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="dashboard-card h-100 text-center p-4">
                    <i class="fas fa-user fa-3x text-info mb-3"></i>
                    <h5>Profile</h5>
                    <p class="text-muted">Manage your account</p>
                    <a href="profile.php" class="btn btn-primary w-100">
                        <i class="fas fa-user me-2"></i>Update Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Recent Bookings</h5>
                            <a href="my_bookings.php" class="btn btn-outline-primary btn-sm">View All</a>
                        </div>
                        
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
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Service</th>
                                            <th>Company</th>
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
                                                <td><?php echo htmlspecialchars($booking['company_name']); ?></td>
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
                                                <td>
                                                    <a href="my_bookings.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <a href="my_bookings.php?cancel=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                            <i class="fas fa-times me-1"></i>Cancel
                                                        </a>
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
