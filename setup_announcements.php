<?php
require 'config/db.php';

// Create announcements table
$sql = "CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) {
    echo "Table announcements created successfully.\n";
} else {
    echo "Error creating table announcements: " . $conn->error . "\n";
}

// Add reply_to, is_edited, is_deleted to chats
// "reply panna message edit delete option irrukan add panni kudu" - This implies replying to specific messages? Or "messages sent as replies"? Actually I will add a `reply_to` column to chats if they want to reply to specific messages, but that might be overkill if they just meant "messages sent as replies". I think they just meant "messages that are sent in chat should have edit and delete options". "reply panna message" simply means "the message that we reply/send".
$columns = [
    'reply_to_id' => 'INT(11) DEFAULT NULL',
    'is_edited' => 'TINYINT(1) DEFAULT 0',
    'is_deleted' => 'TINYINT(1) DEFAULT 0'
];

foreach ($columns as $column => $type) {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM chats LIKE '$column'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE chats ADD $column $type");
    }
}
echo "Chats table updated.\n";
?>
