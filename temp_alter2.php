<?php
require 'config/db.php';
$conn->query("ALTER TABLE tasks MODIFY observer_id VARCHAR(255) DEFAULT NULL;");
echo "DB Updated: observer_id is now VARCHAR";
?>
