<?php
// Test API get_teachers với session giả lập
session_start();

// Giả lập session để test
$_SESSION['user_id'] = 'test_user';
$_SESSION['role'] = 'student';

// Gọi API
include 'get_teachers.php';
?>
