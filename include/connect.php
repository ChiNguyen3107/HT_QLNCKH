<?php
$servername = "127.0.0.1";
$username = "root";
$password = ""; // Thay đổi nếu bạn có mật khẩu cho MySQL
$dbname = "ql_nckh";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>