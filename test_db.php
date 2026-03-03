<?php
$conn = new mysqli('localhost', 'root', '', 'employee');
$res = $conn->query('SHOW TABLES');
while ($row = $res->fetch_array()) {
    echo "TABLE: " . $row[0] . "\n";
    $cols = $conn->query("DESCRIBE " . $row[0]);
    while ($col = $cols->fetch_assoc()) {
        echo "  " . $col['Field'] . " - " . $col['Type'] . "\n";
    }
}
?>
