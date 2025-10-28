<?php
// Sidebar navigation component for dashboards
// Include this file in your dashboard pages
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h5><i class="fas fa-globe me-2"></i>OneStop</h5>
        <button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo getDashboardUrl(); ?>">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </li>
            
            <?php if (hasRole('company')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../company/services.php">
                        <i class="fas fa-list me-2"></i>My Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../company/services/add.php">
                        <i class="fas fa-plus me-2"></i>Add Service
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if (hasRole('admin')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../admin/pending_approvals.php">
                        <i class="fas fa-clock me-2"></i>Pending Approvals
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../admin/activity_logs.php">
                        <i class="fas fa-history me-2"></i>Activity Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../admin/booking_history.php">
                        <i class="fas fa-calendar-check me-2"></i>Booking History
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo getProfileUrl(); ?>">
                    <i class="fas fa-user me-2"></i>Profile
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-globe me-2"></i>View Site
                </a>
            </li>
            
            <li class="nav-item">
                <hr class="dropdown-divider">
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-danger" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>

<?php
function getDashboardUrl() {
    if (hasRole('admin')) {
        return '../admin/dashboard.php';
    } elseif (hasRole('company')) {
        return '../company/dashboard.php';
    } else {
        return '../user/dashboard.php';
    }
}

function getProfileUrl() {
    if (hasRole('admin')) {
        return '../admin/profile.php';
    } elseif (hasRole('company')) {
        return '../company/profile.php';
    } else {
        return '../user/profile.php';
    }
}
?>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

</script>

