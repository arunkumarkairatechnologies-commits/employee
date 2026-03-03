<?php
// scripts/add_designation_column.php
// Usage: run from CLI `php scripts/add_designation_column.php` or open in browser.
// IMPORTANT: Backup your database before running this migration.
require_once __DIR__ . '/../config/db.php';

// ALTER statement to add designation column to users table
$sql = "ALTER TABLE users ADD COLUMN designation VARCHAR(100) AFTER phone";

echo "Running migration: add designation column to users table...\n";

// Check if column already exists
$check_sql = "SHOW COLUMNS FROM users LIKE 'designation'";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows > 0) {
    echo "Designation column already exists. No action needed.\n";
} else {
    if ($conn->query($sql) === TRUE) {
        echo "Migration completed successfully. Designation column added.\n";
    } else {
        echo "Migration failed: " . $conn->error . "\n";
    }
}

// Close connection
$conn->close();

?>
