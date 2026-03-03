<?php
require 'config/db.php';
$conn->query("ALTER TABLE tasks ADD COLUMN is_archived TINYINT(1) DEFAULT 0;");
$conn->query("ALTER TABLE leaves ADD COLUMN is_archived TINYINT(1) DEFAULT 0;");
echo "DB Updated: is_archived added.";
?>
