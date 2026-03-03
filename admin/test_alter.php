<?php
require '../config/db.php';
$conn->query('ALTER TABLE tasks ADD COLUMN attachment VARCHAR(255) NULL');
if ($conn->error) {
    echo "SQL ERR: " . $conn->error;
} else {
    echo "SUCCESS";
}
?>
