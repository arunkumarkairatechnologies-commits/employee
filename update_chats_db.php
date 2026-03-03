<?php
require 'config/db.php';
$conn->query("ALTER TABLE chats ADD COLUMN is_read TINYINT(1) DEFAULT 0");
echo "Chats table updated";
?>
