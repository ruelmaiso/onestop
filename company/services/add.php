<?php
require_once '../../includes/db.php';
require_once '../../includes/session.php';
require_once '../../includes/logger.php';
require_once '../../includes/csrf.php';

// Require company role
requireRole('company');

$user = getCurrentUser();

// Get company information
$company = fetchRow($conn, "SELECT * FROM companies WHERE user_id = ?", [$_SESSION['user_id']]);

if (!$company) {
    header('Location: /index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $capacity = intval($_POST['capacity'] ?? 1);
    $location = trim($_POST['location'] ?? '');
    $availableFrom = $_POST['available_from'] ?? '';
    $availableTo = $_POST['available_to'] ?? '';
    
    // Validation
    if (empty($title) || empty($description) || empty($location)) {
        $error = 'Title, description, and location are required';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } elseif ($capacity < 1) {
        $error = 'Capacity must be at least 1';
    } elseif (empty($availableFrom) || empty($availableTo)) {
        $error = 'Available dates are required';
    } elseif (strtotime($availableTo) <= strtotime($availableFrom)) {
        $error = 'End date must be after start date';
    } else {
        try {
            executeQuery($conn, 
                "INSERT INTO services (company_id, title, description, price, capacity, location, available_from, available_to) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                [$company['id'], $title, $description, $price, $capacity, $location, $availableFrom, $availableTo]
            );
            
            $serviceId = $conn->insert_id;
            
            // Log activity
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'service_created', [
                'service_id' => $serviceId,
                'title' => $title
            ]);
            
            $success = 'Service added successfully! It will be reviewed by admin before going live.';
        } catch (Exception $e) {
            $error = 'Failed to add service. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - OneStop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            <a class="navbar-brand text-primary" href="../dashboard.php">
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
                            <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card form-card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Service</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php echo csrfInput(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Service Title *</label>
                                    <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Location *</label>
                                    <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($location ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Price per night *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="price" step="0.01" min="0" value="<?php echo $price ?? ''; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Capacity *</label>
                                    <input type="number" class="form-control" name="capacity" min="1" value="<?php echo $capacity ?? 1; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Available From *</label>
                                    <input type="date" class="form-control" name="available_from" value="<?php echo $availableFrom ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Available To *</label>
                                <input type="date" class="form-control" name="available_to" value="<?php echo $availableTo ?? ''; ?>" required>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Add Service
                                </button>
                                <a href="services.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="available_from"]').setAttribute('min', today);
        document.querySelector('input[name="available_to"]').setAttribute('min', today);
        
        // Update end date minimum when start date changes
        document.querySelector('input[name="available_from"]').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.querySelector('input[name="available_to"]');
            endDateInput.setAttribute('min', startDate);
        });
    </script>
</body>
</html>