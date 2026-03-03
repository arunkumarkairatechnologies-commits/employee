<?php
require 'c:/xampp/leo/htdocs/employee/config/db.php';
$res = $conn->query('DESCRIBE tasks');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
