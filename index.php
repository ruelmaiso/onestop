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
        LIMIT 12";

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
        :root {
            --booking-orange: #FF7000;
            --booking-dark: #1a237e;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navigation */
        .navbar {
            background: white !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 15px 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            color: var(--booking-orange) !important;
        }

        .nav-link {
            font-weight: 500;
            color: #333 !important;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background: #f8f9fa;
            color: var(--booking-orange) !important;
        }

        /* Hero Section */
        .hero-banner {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 50%, #5c6bc0 100%);
            position: relative;
            overflow: hidden;
            padding: 100px 0;
            color: white;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 112, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 112, 0, 0.1) 0%, transparent 50%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        /* Search Box */
        .search-container {
            position: relative;
            z-index: 10;
            margin-top: -60px;
        }

        .search-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            padding: 35px;
            margin: 0 auto;
            max-width: 1200px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        .form-group-modern {
            position: relative;
        }

        .form-group-modern label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: block;
        }

        .form-control-modern {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control-modern:focus {
            border-color: var(--booking-orange);
            box-shadow: 0 0 0 3px rgba(255, 112, 0, 0.1);
            outline: none;
        }

        .btn-search {
            background: var(--booking-orange);
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-search:hover {
            background: #e85f00;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 112, 0, 0.3);
        }

        /* Service Cards */
        .service-card-modern {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .service-card-modern:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }

        .service-image-modern {
            height: 220px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .service-image-modern i {
            font-size: 5rem;
            color: white;
            opacity: 0.3;
        }

        .service-image-modern::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.3) 100%);
        }

        .price-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--booking-orange);
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .service-card-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .service-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }

        .service-card-company {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 15px;
        }

        .service-card-description {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 15px;
            flex-grow: 1;
        }

        .service-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .service-location {
            color: #666;
            font-size: 0.9rem;
        }

        .service-capacity {
            color: #666;
            font-size: 0.9rem;
        }

        .btn-view-details {
            background: var(--booking-orange);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-view-details:hover {
            background: #e85f00;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 112, 0, 0.3);
        }

        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .feature-box {
            text-align: center;
            padding: 30px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
        }

        .feature-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--booking-orange), #ff9500);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }

        .feature-description {
            color: #666;
            font-size: 0.95rem;
        }

        /* Section Titles */
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 60px;
        }

        /* Stats Section */
        .stats-section {
            background: var(--booking-dark);
            color: white;
            padding: 60px 0;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--booking-orange);
        }

        .stat-label {
            font-size: 1.1rem;
            margin-top: 10px;
            opacity: 0.9;
        }

        /* Footer */
        .modern-footer {
            background: #1a1a1a;
            color: #ccc;
            padding: 60px 0 30px;
        }

        .footer-title {
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .footer-link {
            color: #999;
            text-decoration: none;
            display: block;
            padding: 8px 0;
            transition: color 0.3s;
        }

        .footer-link:hover {
            color: var(--booking-orange);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-globe me-2"></i>OneStop
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Browse Services</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
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

    <!-- Hero Banner -->
    <div class="hero-banner">
        <div class="hero-content container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Find your perfect service</h1>
                    <p class="hero-subtitle">Discover amazing services from verified companies. Book with confidence and enjoy great experiences.</p>
                    <div class="d-flex gap-3">
                        <a href="services.php" class="btn" style="background: white; color: var(--booking-orange); font-weight: 600; padding: 14px 30px; border-radius: 12px;">
                            <i class="fas fa-search me-2"></i>Browse All Services
                        </a>
                        <a href="auth/register.php" class="btn" style="background: var(--booking-orange); color: white; font-weight: 600; padding: 14px 30px; border-radius: 12px;" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fas fa-user-plus me-2"></i>Join Now
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-calendar-check" style="font-size: 12rem; opacity: 0.2;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-container">
        <div class="container">
            <div class="search-box">
                <h4 class="mb-4"><i class="fas fa-search me-2"></i>Search Services</h4>
                <form method="get" action="index.php">
                    <div class="search-form">
                        <div class="form-group-modern">
                            <label>What are you looking for?</label>
                            <input type="text" class="form-control-modern" name="search" placeholder="Search services..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group-modern">
                            <label>Guests</label>
                            <select class="form-control-modern" name="guests">
                                <?php for($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>><?php echo $i; ?> <?php echo $i == 1 ? 'Guest' : 'Guests'; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group-modern">
                            <label>Date</label>
                            <input type="date" class="form-control-modern" name="checkin" value="<?php echo htmlspecialchars($checkin); ?>">
                        </div>
                        <div class="form-group-modern">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-search">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Services Section -->
    <div class="container my-5" style="margin-top: 100px !important;">
        <h2 class="section-title">Featured Services</h2>
        <p class="section-subtitle">Discover the best services from trusted companies</p>
        
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
                        <div class="service-card-modern">
                            <div class="service-image-modern">
                                <i class="fas fa-building"></i>
                                <div class="price-badge">
                                    $<?php echo number_format($service['price'], 0); ?>/night
                                </div>
                            </div>
                            <div class="service-card-body">
                                <h5 class="service-card-title"><?php echo htmlspecialchars($service['title']); ?></h5>
                                <p class="service-card-company">
                                    <i class="fas fa-building text-muted me-1"></i><?php echo htmlspecialchars($service['company_name']); ?>
                                </p>
                                <p class="service-card-description"><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                                <div class="service-card-footer">
                                    <span class="service-location">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i><?php echo htmlspecialchars($service['location']); ?>
                                    </span>
                                    <span class="service-capacity">
                                        <i class="fas fa-users text-info me-1"></i><?php echo $service['capacity']; ?> max
                                    </span>
                                </div>
                            </div>
                            <div style="padding: 0 20px 20px;">
                                <a href="service/view.php?id=<?php echo $service['id']; ?>" class="btn-view-details">
                                    View Details & Book
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($services)): ?>
            <div class="text-center mt-5">
                <a href="services.php" class="btn" style="background: var(--booking-orange); color: white; padding: 14px 40px; font-weight: 600; border-radius: 12px;">
                    View All Services <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Features Section -->
    <div class="features-section">
        <div class="container">
            <h2 class="section-title">Why Choose OneStop?</h2>
            <p class="section-subtitle">Trusted by thousands of customers across the Philippines</p>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="feature-title">Verified Services</h4>
                        <p class="feature-description">All services are verified and approved by our team for quality assurance.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4 class="feature-title">Instant Booking</h4>
                        <p class="feature-description">Book your services instantly with real-time availability and confirmation.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4 class="feature-title">24/7 Support</h4>
                        <p class="feature-description">Get help anytime with our dedicated customer support team.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Verified Companies</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">1000+</div>
                        <div class="stat-label">Active Services</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">5000+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Satisfaction Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="modern-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5 class="footer-title"><i class="fas fa-globe me-2"></i>OneStop</h5>
                    <p>Your one-stop destination for finding and booking services worldwide.</p>
                    <div class="d-flex gap-2">
                        <a href="#" class="footer-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="footer-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="footer-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 class="footer-title">Company</h6>
                    <a href="#" class="footer-link">About us</a>
                    <a href="#" class="footer-link">Careers</a>
                    <a href="#" class="footer-link">Press</a>
                    <a href="#" class="footer-link">Blog</a>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 class="footer-title">Support</h6>
                    <a href="#" class="footer-link">Help Center</a>
                    <a href="#" class="footer-link">Contact us</a>
                    <a href="#" class="footer-link">Safety</a>
                    <a href="#" class="footer-link">FAQs</a>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 class="footer-title">Legal</h6>
                    <a href="#" class="footer-link">Terms & Conditions</a>
                    <a href="#" class="footer-link">Privacy Policy</a>
                    <a href="#" class="footer-link">Cookies</a>
                    <a href="#" class="footer-link">Disclaimer</a>
                </div>
            </div>
            <hr style="border-color: #333; margin: 40px 0 20px;">
            <div class="text-center text-muted">
                <p>&copy; 2025 OneStop. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Include Modals -->
            
   
</body>
</html>
