<?php
require_once 'includes/db.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';

// Get search parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = $_GET['guests'] ?? 1;

// Build search query
$whereConditions = ["s.approved = 1"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(s.title LIKE ? OR s.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($location)) {
    $whereConditions[] = "s.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($checkin)) {
    $whereConditions[] = "s.available_from <= ?";
    $params[] = $checkin;
}

if (!empty($checkout)) {
    $whereConditions[] = "s.available_to >= ?";
    $params[] = $checkout;
}

$whereClause = implode(' AND ', $whereConditions);

// Get services
$sql = "SELECT s.*, c.name as company_name, c.address as company_address 
        FROM services s 
        JOIN companies c ON s.company_id = c.id 
        WHERE $whereClause 
        ORDER BY s.created_at DESC 
        LIMIT 20";

$services = fetchAll($conn, $sql, $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OneStop - Find Your Perfect Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        .search-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        .service-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .service-image {
            height: 200px;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #999;
        }
        .price-tag {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 10px 30px;
        }
        .btn-outline-primary {
            border-radius: 25px;
            padding: 10px 30px;
        } 

       

    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary" href="index.php">
                <i class="fas fa-globe me-2"></i>OneStop
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                   
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Services</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="user/dashboard.php">Dashboard</a></li>
                                <?php if (hasRole('company')): ?>
                                    <li><a class="dropdown-item" href="company/dashboard.php">Company Panel</a></li>
                                <?php endif; ?>
                                <?php if (hasRole('admin')): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary ms-2" href="auth/register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <!-- <div class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4">Find Your Perfect Service</h1>
                    <p class="lead mb-5">Discover and book amazing services from trusted companies worldwide</p>
                </div>
            </div>
        </div>
    </div> -->
    

    <!-- Hero Section -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold booking-text-orange mb-3">Find your perfect service</h1>
                    <p class="lead text-muted mb-4">Discover amazing services from verified companies across the Philippines. Book with confidence and enjoy great experiences.</p>
                    <div class="d-flex gap-3">
                        <a href="?action=services" class="btn booking-btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Browse Services
                        </a>
                   
                        <a href="?action=signup" class="btn booking-btn-secondary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Join Now
                        </a>
                   
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="booking-search-box">
                        <h4 class="mb-3">Search Services</h4>
                        <form method="get" action="index.php">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">What are you looking for?</label>
                                    <!-- <input type="text" class="form-control form-control-lg" name="q" placeholder="e.g., Photography, Catering, Cleaning"> -->
                                    <input type="text" class="form-control form-control-lg" name="search" placeholder="Service name..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Guests</label>
                                    <select class="form-select form-select-lg" name="guest">
                                       <?php  for($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>><?php echo $i; ?> <?php echo $i == 1 ? 'Guest' : 'Guests'; ?></option>
                                    <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Date</label>
                                    
                                    <input type="date" class="form-control form-control-lg" name="checkin" value="<?php echo htmlspecialchars($checkin); ?>">
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn booking-btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>Search Services
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <!-- <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="search-card">
                    <form method="GET" action="index.php">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">What are you looking for?</label>
                                <input type="text" class="form-control" name="search" placeholder="Service name..." value="<?php //echo htmlspecialchars($search); ?>"> -->
                            <!-- </div> -->
                            <!-- <div class="col-md-3">
                                <label class="form-label">Where?</label>
                                <input type="text" class="form-control" name="location" placeholder="Location..." value="<?php //echo htmlspecialchars($location); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Check-in</label>
                                <input type="date" class="form-control" name="checkin" value="<?php //echo htmlspecialchars($checkin); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Check-out</label>
                                <input type="date" class="form-control" name="checkout" value="<?php //echo htmlspecialchars($checkout); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Guests</label>
                                <select class="form-select" name="guests">
                                 <?php /* for($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>><?php echo $i; ?> <?php echo $i == 1 ? 'Guest' : 'Guests'; ?></option>
                                    <?php endfor;*/ ?>
                                </select>
                            </div> -->
                        <!-- </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> -->
        <!-- Services Section -->
        <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Available Services</h2>
            </div>
        </div>
        <div class="row">
            <?php if (empty($services)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No services found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                </div>
            <?php else: ?>
                <?php foreach ($services as $service): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card service-card h-100">
                            <div class="service-image">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title"><?php echo htmlspecialchars($service['title']); ?></h5>
                                    <span class="price-tag">$<?php echo number_format($service['price'], 2); ?></span>
                                </div>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($service['company_name']); ?></p>
                                <p class="card-text"><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($service['location']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i><?php echo $service['capacity']; ?> max
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="service/view.php?id=<?php echo $service['id']; ?>" class="btn btn-primary w-100">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>


    
    <!-- Features Section -->
    <div class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="h3 fw-bold booking-text-orange">Why choose OneStop?</h2>
                    <p class="text-muted">Trusted by thousands of customers across the locally</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-shield-alt fa-2x booking-text-orange"></i>
                        </div>
                        <h5 class="fw-semibold">Verified Services</h5>
                        <p class="text-muted">All services are verified and approved by our team for quality assurance.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-clock fa-2x booking-text-orange"></i>
                        </div>
                        <h5 class="fw-semibold">Instant Booking</h5>
                        <p class="text-muted">Book your services instantly with real-time availability and confirmation.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-headset fa-2x booking-text-orange"></i>
                        </div>
                        <h5 class="fw-semibold">24/7 Support</h5>
                        <p class="text-muted">Get help anytime with our dedicated customer support team.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>


 <!-- Footer -->
 <footer class="booking-footer bg-body-secondary">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mt-3">
                    <h6 class="booking-text-orange">Company</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-muted">About us</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Careers</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Press</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mt-3">
                    <h6 class="booking-text-orange">Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-muted">Help Center</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Contact us</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Safety</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mt-3">
                    <h6 class="booking-text-orange">Legal</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-muted">Terms & Conditions</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Privacy Policy</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Cookies</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mt-3">
                    <h6 class="booking-text-orange">Follow us</h6>
                    <div class="d-flex gap-2">
                        <a href="#" class="text-muted"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <small>&copy; 2025 OneStop.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>