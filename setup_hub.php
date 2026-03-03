<?php
require 'config/db.php';

// Add columns 'date_of_birth' and 'joining_date' to users table
$columns = [
    'date_of_birth' => 'DATE DEFAULT NULL',
    'joining_date' => 'DATE DEFAULT NULL'
];

foreach ($columns as $column => $type) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD $column $type");
        echo "Added $column to users table.<br>";
    }
}

// Create events table
$sql_events = "CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    event_type VARCHAR(50) DEFAULT 'event',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql_events)) echo "Table events created.<br>";

// Create event_rsvps table
$sql_rsvps = "CREATE TABLE IF NOT EXISTS event_rsvps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'going',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (event_id, user_id)
)";
if ($conn->query($sql_rsvps)) echo "Table event_rsvps created.<br>";

echo "Database updated for Hub and Events.";
?>
