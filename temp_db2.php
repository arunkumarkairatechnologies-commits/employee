<?php
require 'config/db.php';
$res1 = $conn->query("SHOW CREATE TABLE announcement_reactions");
if($res1) {
    print_r($res1->fetch_assoc());
} else {
    echo "Reactions table error: " . $conn->error . "\n";
}

$res2 = $conn->query("SHOW CREATE TABLE announcement_replies");
if($res2) {
    print_r($res2->fetch_assoc());
} else {
    echo "Replies table error: " . $conn->error . "\n";
}
?>
