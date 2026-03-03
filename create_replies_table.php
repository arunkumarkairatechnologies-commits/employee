<?php
require 'c:/xampp/leo/htdocs/employee/config/db.php';
$conn->query("CREATE TABLE IF NOT EXISTS announcement_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_edited TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
if ($conn->error) {
    echo "Error: " . $conn->error;
} else {
    echo "Table created.";
}
?>
