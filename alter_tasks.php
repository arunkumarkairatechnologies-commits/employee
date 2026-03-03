<?php
require 'c:/xampp/leo/htdocs/employee/config/db.php';
$conn->query("ALTER TABLE tasks ADD observer_id INT(11) DEFAULT NULL");
echo "DB Updated: " . $conn->error;
?>
