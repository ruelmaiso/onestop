<?php
// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "booking_app";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Function to execute prepared statements
function executeQuery($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    if (!$result) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    return $stmt;
}

// Function to fetch single row
function fetchRow($conn, $sql, $params = []) {
    $stmt = executeQuery($conn, $sql, $params);
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

// Function to fetch all rows
function fetchAll($conn, $sql, $params = []) {
    $stmt = executeQuery($conn, $sql, $params);
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}
?>