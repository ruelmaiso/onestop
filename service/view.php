<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/csrf.php';
require_once '../includes/logger.php';

$serviceId = $_GET['id'] ?? 0;

// Get service details
$service = fetchRow($conn, 
    "SELECT s.*, c.name as company_name, c.address as company_address, c.phone as company_phone 
     FROM services s 
     JOIN companies c ON s.company_id = c.id 
     WHERE s.id = ? AND s.approved = 1", 
    [$serviceId]
);

if (!$service) {
    header('Location: /index.php');
    exit();
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    checkCSRF();
    
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $pax = intval($_POST['pax'] ?? 1);
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($startDate) || empty($endDate)) {
        $error = 'Please select check-in and check-out dates';
    } elseif (strtotime($startDate) < strtotime('today')) {
        $error = 'Check-in date cannot be in the past';
    } elseif (strtotime($endDate) <= strtotime($startDate)) {
        $error = 'Check-out date must be after check-in date';
    } elseif ($pax < 1 || $pax > $service['capacity']) {
        $error = 'Number of guests must be between 1 and ' . $service['capacity'];
    } else {
        // Check for overlapping bookings
        $overlappingBookings = fetchRow($conn, 
            "SELECT COUNT(*) as count FROM bookings b 
             WHERE b.service_id = ? AND b.status = 'approved' 
             AND NOT (b.end_date <= ? OR b.start_date >= ?)", 
            [$serviceId, $startDate, $endDate]
        );
        
        if ($overlappingBookings['count'] > 0) {
            $error = 'This service is not available for the selected dates';
        } else {
            // Calculate total price
            $days = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
            $totalPrice = $service['price'] * $days * $pax;
            
            // Create booking
            try {
                executeQuery($conn, 
                    "INSERT INTO bookings (user_id, service_id, start_date, end_date, pax, total_price, notes) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)", 
                    [$_SESSION['user_id'], $serviceId, $startDate, $endDate, $pax, $totalPrice, $notes]
                );
                
                $bookingId = $conn->insert_id;
                
                // Log activity
                logActivity($_SESSION['user_id'], $_SESSION['role'], 'booking_created', [
                    'booking_id' => $bookingId,
                    'service_id' => $serviceId,
                    'total_price' => $totalPrice
                ]);
                
                $success = 'Booking request submitted successfully!';
            } catch (Exception $e) {
                $error = 'Failed to create booking. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['title']); ?> - OneStop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .service-image {
            height: 400px;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #999;
            border-radius: 15px;
        }
        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            position: sticky;
            top: 20px;
        }
        .price-display {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
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
            <a class="navbar-brand text-primary" href="../index.php">
                <i class="fas fa-globe me-2"></i>OneStop
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                   
                    <li class="nav-item">
                        <a class="nav-link" href="../services.php">Services</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../user/dashboard.php">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary ms-2" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <!-- Service Image -->
                <div class="service-image mb-4">
                    <i class="fas fa-building"></i>
                </div>

                <!-- Service Details -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h1 class="mb-3"><?php echo htmlspecialchars($service['title']); ?></h1>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-map-marker-alt text-muted me-2"></i>
                            <span class="text-muted"><?php echo htmlspecialchars($service['location']); ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-building text-muted me-2"></i>
                            <span class="text-muted"><?php echo htmlspecialchars($service['company_name']); ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-users text-muted me-2"></i>
                            <span class="text-muted">Capacity: <?php echo $service['capacity']; ?> guests</span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4>Description</h4>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                    </div>
                </div>

                <!-- Company Information -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Company Information</h5>
                            </div>
                            <div class="card-body">
                                <h6><?php echo htmlspecialchars($service['company_name']); ?></h6>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($service['company_address']); ?></p>
                                <?php if ($service['company_phone']): ?>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($service['company_phone']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="booking-card">
                    <div class="text-center mb-4">
                        <div class="price-display">$<?php echo number_format($service['price'], 2); ?></div>
                        <small class="text-muted">per night</small>
                    </div>

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

                    <?php if (isLoggedIn()): ?>
                        <form method="POST">
                            <?php echo csrfInput(); ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Check-in Date</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Check-out Date</label>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Number of Guests</label>
                                <select class="form-select" name="pax" required>
                                    <?php for($i = 1; $i <= $service['capacity']; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i == 1 ? 'Guest' : 'Guests'; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Special Requests (Optional)</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="Any special requirements..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-calendar-check me-2"></i>Book Now
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <p class="text-muted mb-3">Please login to book this service</p>
                            <a href="../auth/login.php" class="btn btn-primary w-100 mb-2">Login</a>
                            <a href="../auth/register.php" class="btn btn-outline-primary w-100">Sign Up</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    <?php include '../includes/modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="start_date"]').setAttribute('min', today);
        document.querySelector('input[name="end_date"]').setAttribute('min', today);
        
        // Update end date minimum when start date changes
        document.querySelector('input[name="start_date"]').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.querySelector('input[name="end_date"]');
            endDateInput.setAttribute('min', startDate);
            
            // If end date is before start date, clear it
            if (endDateInput.value && endDateInput.value <= startDate) {
                endDateInput.value = '';
            }
        });
    </script>
</body>
</html>