<?php
// Script để tạo bảng de_tai_gia_han nếu chưa tồn tại
include 'include/connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Tạo Bảng Extension System</h1>";

// Kiểm tra kết nối database
if ($conn->connect_error) {
    die("❌ Lỗi kết nối database: " . $conn->connect_error);
}
echo "✅ Kết nối database thành công<br>";

// Kiểm tra xem bảng de_tai_gia_han có tồn tại không
$table_check = $conn->query("SHOW TABLES LIKE 'de_tai_gia_han'");
if ($table_check && $table_check->num_rows > 0) {
    echo "✅ Bảng de_tai_gia_han đã tồn tại<br>";
} else {
    echo "⚠️ Bảng de_tai_gia_han chưa tồn tại. Đang tạo...<br>";
    
    // Tạo bảng de_tai_gia_han
    $create_table_sql = "
    CREATE TABLE `de_tai_gia_han` (
        `GH_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID gia hạn',
        `DT_MADT` char(10) NOT NULL COMMENT 'Mã đề tài',
        `SV_MASV` char(8) NOT NULL COMMENT 'Sinh viên yêu cầu gia hạn',
        `GH_LYDOYEUCAU` text NOT NULL COMMENT 'Lý do yêu cầu gia hạn',
        `GH_NGAYHETHAN_CU` date NOT NULL COMMENT 'Ngày hết hạn hiện tại',
        `GH_NGAYHETHAN_MOI` date NOT NULL COMMENT 'Ngày hết hạn mới đề xuất',
        `GH_SOTHANGGIAHAN` int(11) NOT NULL COMMENT 'Số tháng gia hạn',
        `GH_TRANGTHAI` enum('Chờ duyệt','Đã duyệt','Từ chối','Hủy') NOT NULL DEFAULT 'Chờ duyệt' COMMENT 'Trạng thái yêu cầu',
        `GH_NGAYYEUCAU` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày gửi yêu cầu',
        `GH_NGUOIDUYET` char(8) DEFAULT NULL COMMENT 'Người duyệt (Quản lý NCKH)',
        `GH_NGAYDUYET` datetime DEFAULT NULL COMMENT 'Ngày duyệt',
        `GH_LYDOTUCHO` text DEFAULT NULL COMMENT 'Lý do từ chối (nếu có)',
        `GH_GHICHU` text DEFAULT NULL COMMENT 'Ghi chú từ người duyệt',
        `GH_FILE_DINKEM` varchar(255) DEFAULT NULL COMMENT 'File đính kèm hỗ trợ yêu cầu',
        `GH_NGUOITAO` char(8) DEFAULT NULL COMMENT 'Người tạo yêu cầu',
        `GH_NGAYCAPNHAT` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Ngày cập nhật cuối',
        PRIMARY KEY (`GH_ID`),
        KEY `idx_de_tai_gia_han_dt` (`DT_MADT`),
        KEY `idx_de_tai_gia_han_sv` (`SV_MASV`),
        KEY `idx_de_tai_gia_han_trangthai` (`GH_TRANGTHAI`),
        KEY `idx_de_tai_gia_han_ngayyeucau` (`GH_NGAYYEUCAU`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng quản lý yêu cầu gia hạn đề tài'";
    
    if ($conn->query($create_table_sql)) {
        echo "✅ Đã tạo bảng de_tai_gia_han thành công<br>";
    } else {
        echo "❌ Lỗi khi tạo bảng de_tai_gia_han: " . $conn->error . "<br>";
    }
}

// Kiểm tra và thêm cột DT_TRE_TIENDO và DT_SO_LAN_GIA_HAN vào bảng de_tai_nghien_cuu
$check_columns = $conn->query("SHOW COLUMNS FROM de_tai_nghien_cuu LIKE 'DT_TRE_TIENDO'");
if ($check_columns && $check_columns->num_rows == 0) {
    echo "⚠️ Cột DT_TRE_TIENDO chưa tồn tại. Đang thêm...<br>";
    $add_column_sql = "ALTER TABLE `de_tai_nghien_cuu` 
                       ADD COLUMN `DT_TRE_TIENDO` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đề tài trễ tiến độ (1=có gia hạn, 0=bình thường)'";
    if ($conn->query($add_column_sql)) {
        echo "✅ Đã thêm cột DT_TRE_TIENDO<br>";
    } else {
        echo "❌ Lỗi khi thêm cột DT_TRE_TIENDO: " . $conn->error . "<br>";
    }
}

$check_columns2 = $conn->query("SHOW COLUMNS FROM de_tai_nghien_cuu LIKE 'DT_SO_LAN_GIA_HAN'");
if ($check_columns2 && $check_columns2->num_rows == 0) {
    echo "⚠️ Cột DT_SO_LAN_GIA_HAN chưa tồn tại. Đang thêm...<br>";
    $add_column_sql2 = "ALTER TABLE `de_tai_nghien_cuu` 
                        ADD COLUMN `DT_SO_LAN_GIA_HAN` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lần đã gia hạn'";
    if ($conn->query($add_column_sql2)) {
        echo "✅ Đã thêm cột DT_SO_LAN_GIA_HAN<br>";
    } else {
        echo "❌ Lỗi khi thêm cột DT_SO_LAN_GIA_HAN: " . $conn->error . "<br>";
    }
}

// Kiểm tra bảng thong_bao
$table_check2 = $conn->query("SHOW TABLES LIKE 'thong_bao'");
if ($table_check2 && $table_check2->num_rows == 0) {
    echo "⚠️ Bảng thong_bao chưa tồn tại. Đang tạo...<br>";
    
    $create_notification_table = "
    CREATE TABLE `thong_bao` (
        `TB_MA` INT AUTO_INCREMENT PRIMARY KEY,
        `TB_NOIDUNG` TEXT NOT NULL,
        `TB_NGAYTAO` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `TB_DANHDOC` TINYINT(1) NOT NULL DEFAULT 0,
        `TB_LOAI` VARCHAR(50) DEFAULT 'Thông báo',
        `DT_MADT` CHAR(10) NULL,
        `GV_MAGV` CHAR(8) NULL,
        `SV_MASV` CHAR(8) NULL,
        `QL_MA` CHAR(8) NULL,
        `TB_TRANGTHAI` VARCHAR(20) DEFAULT 'Chưa đọc',
        `TB_LINK` VARCHAR(255) NULL,
        `NGUOI_NHAN` VARCHAR(50) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($create_notification_table)) {
        echo "✅ Đã tạo bảng thong_bao thành công<br>";
    } else {
        echo "❌ Lỗi khi tạo bảng thong_bao: " . $conn->error . "<br>";
    }
} else {
    echo "✅ Bảng thong_bao đã tồn tại<br>";
}

echo "<br><h2>Hoàn thành!</h2>";
echo "<p><a href='view/student/debug_extension.php'>→ Kiểm tra Debug Extension</a></p>";
echo "<p><a href='view/student/manage_extensions.php'>→ Đi đến trang Gia hạn đề tài</a></p>";
?>

