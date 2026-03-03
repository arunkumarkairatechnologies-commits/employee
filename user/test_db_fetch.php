<?php
require '../config/db.php';
$res = $conn->query('SELECT * FROM chats ORDER BY id DESC LIMIT 5');
if (!$res) echo "SQL ERR: " . $conn->error;
else while ($row = $res->fetch_assoc()) print_r($row);
?>
