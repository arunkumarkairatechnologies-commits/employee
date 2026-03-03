<?php
require 'config/db.php';
$res = $conn->query("SHOW COLUMNS FROM users");
while($row = $res->fetch_assoc()) print_r($row);
?>
