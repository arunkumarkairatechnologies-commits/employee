<?php
require 'config/db.php';

// Add new columns for targeting
$columns = [
    'target_type' => "VARCHAR(50) DEFAULT 'all'",
    'target_value' => "VARCHAR(255) DEFAULT ''", // 'employee', 'admin', 'manager' etc. 
    'is_critical' => "TINYINT(1) DEFAULT 0", // For mandatory acknowledgment
    'allow_reactions' => "TINYINT(1) DEFAULT 1",
    'allow_comments' => "TINYINT(1) DEFAULT 1"
];

foreach ($columns as $column => $type) {
    $check = $conn->query("SHOW COLUMNS FROM announcements LIKE '$column'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE announcements ADD $column $type");
        echo "Added $column to announcements.<br>";
    }
}

// Table for read receipts
$sql_reads = "CREATE TABLE IF NOT EXISTS announcement_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (announcement_id, user_id)
)";
$conn->query($sql_reads);
echo "Table announcement_reads created/exists.<br>";

// Table for reactions
$sql_reactions = "CREATE TABLE IF NOT EXISTS announcement_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (announcement_id, user_id)
)";
$conn->query($sql_reactions);
echo "Table announcement_reactions created/exists.<br>";

echo "Database successfully updated for new announcements features.";
?>
