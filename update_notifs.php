<?php
require 'c:/xampp/leo/htdocs/employee/config/db.php';
$conn->query("ALTER TABLE notifications ADD link VARCHAR(255) DEFAULT NULL");
echo 'Done';
?>
