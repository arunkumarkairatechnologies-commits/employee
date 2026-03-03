<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'employee';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['react_ann'] = 'like';
$_POST['ann_id'] = 1;

require 'user/announcements.php';
?>
