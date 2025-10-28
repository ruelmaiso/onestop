<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/logger.php';

// Require admin role
requireRole('admin');

$user = getCurrentUser();

// Get activity logs (excluding booking-related actions)
$activityLogs = fetchAll($conn, 
    "SELECT al.*, u.name as user_name, u.email as user_email
     FROM activity_logs al 
     LEFT JOIN users u ON al.user_id = u.id
     WHERE al.action NOT LIKE '%booking%'
     ORDER BY al.created_at DESC 
     LIMIT 100"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin - OneStop</title>
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
                            <h2><i class="fas fa-history me-2"></i>Activity Logs</h2>
                            <p class="text-muted">Platform activities excluding booking records</p>
                        </div>
                        <button class="btn btn-primary d-lg-none" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i> Menu
                        </button>
                    </div>
                </div>

                <div class="content-card">
                    <?php if (empty($activityLogs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No activity logs available</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                        <th>IP Address</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activityLogs as $log): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['role'] ?? 'System'); ?></span></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                            <td><small><?php echo htmlspecialchars($log['ip']); ?></small></td>
                                            <td>
                                                <?php if ($log['meta']): ?>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="showDetails(<?php echo htmlspecialchars($log['id']); ?>)">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if ($log['meta']): ?>
                                            <tr id="details-<?php echo htmlspecialchars($log['id']); ?>" style="display:none;">
                                                <td colspan="6">
                                                    <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars(json_encode(json_decode($log['meta']), JSON_PRETTY_PRINT)); ?></pre>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
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
        
        function showDetails(id) {
            const row = document.getElementById('details-' + id);
            if (row) {
                row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
            }
        }
    </script>
</body>
</html>

