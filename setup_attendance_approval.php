<?php
require_once 'config/db.php';

// Add approval columns to attendance table
$sql = "ALTER TABLE attendance 
        ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS approved_by INT NULL,
        ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL";

if ($conn->query($sql)) {
    echo "Attendance approval columns added successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
