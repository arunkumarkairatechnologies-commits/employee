<?php
require 'c:/xampp/leo/htdocs/employee/config/db.php';
$res = $conn->query('SELECT id, role FROM users LIMIT 5');
while($row=$res->fetch_assoc()) { print_r($row); }
?>
