<?php
// config/db.php
// Secure MySQLi connection for Employee Management System

$host = 'localhost';
$db   = 'employee';
$user = 'root';
$pass = '';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Set charset to utf8mb4 for security and compatibility
$conn->set_charset('utf8mb4');

// Use prepared statements for all queries in the application for security
?>
