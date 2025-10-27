<?php
require_once 'db.php';

// Function to log activity
function logActivity($userId, $role, $action, $meta = null) {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $metaJson = $meta ? json_encode($meta) : null;
    
    try {
        executeQuery($conn, 
            "INSERT INTO activity_logs (user_id, role, action, meta, ip, ua) VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $role, $action, $metaJson, $ip, $ua]
        );
    } catch (Exception $e) {
        // Log error but don't break the application
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Function to get activity logs
function getActivityLogs($limit = 50, $offset = 0, $userId = null) {
    global $conn;
    
    $sql = "SELECT al.*, u.name as user_name 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id";
    $params = [];
    
    if ($userId) {
        $sql .= " WHERE al.user_id = ?";
        $params[] = $userId;
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    return fetchAll($conn, $sql, $params);
}

// Function to get activity logs count
function getActivityLogsCount($userId = null) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM activity_logs";
    $params = [];
    
    if ($userId) {
        $sql .= " WHERE user_id = ?";
        $params[] = $userId;
    }
    
    $result = fetchRow($conn, $sql, $params);
    return $result['count'];
}
?>