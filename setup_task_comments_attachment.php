<?php
require 'config/db.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add attachment column to task_comments
$sql = "ALTER TABLE task_comments ADD COLUMN attachment VARCHAR(255) NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column attachment added to task_comments table successfully.\n";
} else {
    echo "Error altering table: " . $conn->error . "\n";
}

$conn->close();
?>
