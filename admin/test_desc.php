<?php
require '../config/db.php';
$res = $conn->query('DESCRIBE tasks');
if (!$res) {
    echo "SQL ERR: " . $conn->error;
} else {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
?>
