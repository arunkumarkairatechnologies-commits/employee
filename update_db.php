<?php
require 'config/db.php';
$conn->query("ALTER TABLE task_comments ADD COLUMN is_read TINYINT(1) DEFAULT 0");
echo "Table updated";
?>
