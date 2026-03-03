<?php
// scripts/migrate_role_to_varchar.php
// Usage: run from CLI `php scripts/migrate_role_to_varchar.php` or open in browser.
// IMPORTANT: Backup your database before running this migration.
require_once __DIR__ . '/../config/db.php';

// ALTER statement to convert ENUM role column to VARCHAR
$sql = "ALTER TABLE users MODIFY role VARCHAR(100) NOT NULL";

echo "Running migration: convert users.role to VARCHAR(100)...\n";

if ($conn->query($sql) === TRUE) {
    echo "Migration completed successfully.\n";
} else {
    echo "Migration failed: " . $conn->error . "\n";
}

// Close connection
$conn->close();

?>
