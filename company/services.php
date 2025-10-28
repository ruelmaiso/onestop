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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services - OneStop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../includes/dashboard.css" rel="stylesheet">
    <style>
        .service-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .service-card:hover {
            transform: translateY(-2px);
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body>
  
   
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="dashboard-main">
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h2><i class="fas fa-user me-2"></i>My Services</h2>
                    <p class="text-muted">Manage your Services Settings</p>
                </div>

                <div class="container my-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>My Services</h2>
                    <a href="services/add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Service
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <?php if (empty($services)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-list fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No services yet</h4>
                    <p class="text-muted">Start by adding your first service</p>
                    <a href="services/add.php" class="btn btn-primary">Add Service</a>
                </div>
            <?php else: ?>
                <?php foreach ($services as $service): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card service-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title"><?php echo htmlspecialchars($service['title']); ?></h5>
                                    <span class="badge bg-<?php echo $service['approved'] ? 'success' : 'warning'; ?>">
                                        <?php echo $service['approved'] ? 'Approved' : 'Pending'; ?>
                                    </span>
                                </div>
                                <p class="card-text text-muted"><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <strong class="text-primary">$<?php echo number_format($service['price'], 2); ?></strong>
                                        <br><small class="text-muted">Price</small>
                                    </div>
                                    <div class="col-4">
                                        <strong class="text-info"><?php echo $service['capacity']; ?></strong>
                                        <br><small class="text-muted">Capacity</small>
                                    </div>
                                    <div class="col-4">
                                        <strong class="text-success"><?php echo $service['location']; ?></strong>
                                        <br><small class="text-muted">Location</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex gap-2">
                                    <a href="services/edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <a href="services/delete.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>