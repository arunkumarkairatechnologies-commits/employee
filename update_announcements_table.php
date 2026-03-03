<?php
require 'config/db.php';
$conn->query("ALTER TABLE announcements ADD is_edited TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE announcements ADD is_deleted TINYINT(1) DEFAULT 0");
echo "Done.\n";
?>
