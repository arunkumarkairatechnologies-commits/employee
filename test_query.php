<?php
require 'config/db.php';
$conn->query("CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($conn->error) {
    echo "Error creating table: " . $conn->error . "\n";
} else {
    echo "Table verified.\n";
}

$res = $conn->query('SELECT * FROM announcements ORDER BY created_at DESC');
if (!$res) {
    echo "Error selecting: " . $conn->error . "\n";
} else {
    echo "Query OK. Num rows: " . $res->num_rows . "\n";
}
?>
