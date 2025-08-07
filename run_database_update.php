<?php
// File: run_database_update.php
// Script để chạy các câu lệnh SQL cập nhật cơ sở dữ liệu

include 'include/connect.php';

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

echo "<h2>Cập nhật cấu trúc cơ sở dữ liệu cho việc lưu thành viên hội đồng</h2>\n";
echo "<pre>\n";

try {
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    echo "1. Kiểm tra cấu trúc bảng quyet_dinh_nghiem_thu hiện tại...\n";
    $result = $conn->query("DESCRIBE quyet_dinh_nghiem_thu");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    // Thêm cột QD_NOIDUNG nếu chưa có
    if (!in_array('QD_NOIDUNG', $existing_columns)) {
        echo "2. Thêm cột QD_NOIDUNG vào bảng quyet_dinh_nghiem_thu...\n";
        $sql = "ALTER TABLE `quyet_dinh_nghiem_thu` 
                ADD COLUMN `QD_NOIDUNG` TEXT NULL COMMENT 'Nội dung chi tiết của quyết định' AFTER `QD_FILE`";
        if ($conn->query($sql)) {
            echo "   ✓ Đã thêm cột QD_NOIDUNG\n";
        } else {
            throw new Exception("Lỗi thêm cột QD_NOIDUNG: " . $conn->error);
        }
    } else {
        echo "2. Cột QD_NOIDUNG đã tồn tại, bỏ qua...\n";
    }
    
    // Thêm cột HD_THANHVIEN nếu chưa có
    if (!in_array('HD_THANHVIEN', $existing_columns)) {
        echo "3. Thêm cột HD_THANHVIEN vào bảng quyet_dinh_nghiem_thu...\n";
        $sql = "ALTER TABLE `quyet_dinh_nghiem_thu` 
                ADD COLUMN `HD_THANHVIEN` TEXT NULL COMMENT 'Danh sách thành viên hội đồng (dạng JSON)' AFTER `QD_NOIDUNG`";
        if ($conn->query($sql)) {
            echo "   ✓ Đã thêm cột HD_THANHVIEN\n";
        } else {
            throw new Exception("Lỗi thêm cột HD_THANHVIEN: " . $conn->error);
        }
    } else {
        echo "3. Cột HD_THANHVIEN đã tồn tại, bỏ qua...\n";
    }
    
    echo "4. Kiểm tra cấu trúc bảng thanh_vien_hoi_dong hiện tại...\n";
    $result = $conn->query("DESCRIBE thanh_vien_hoi_dong");
    $tv_existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $tv_existing_columns[] = $row['Field'];
    }
    
    // Thêm cột TV_HOTEN nếu chưa có
    if (!in_array('TV_HOTEN', $tv_existing_columns)) {
        echo "5. Thêm cột TV_HOTEN vào bảng thanh_vien_hoi_dong...\n";
        $sql = "ALTER TABLE `thanh_vien_hoi_dong` 
                ADD COLUMN `TV_HOTEN` VARCHAR(100) NULL COMMENT 'Họ tên đầy đủ của thành viên' AFTER `GV_MAGV`";
        if ($conn->query($sql)) {
            echo "   ✓ Đã thêm cột TV_HOTEN\n";
        } else {
            throw new Exception("Lỗi thêm cột TV_HOTEN: " . $conn->error);
        }
    } else {
        echo "5. Cột TV_HOTEN đã tồn tại, bỏ qua...\n";
    }
    
    // Cập nhật kiểu dữ liệu cho TV_DIEM
    echo "6. Cập nhật kiểu dữ liệu cho cột TV_DIEM...\n";
    $sql = "ALTER TABLE `thanh_vien_hoi_dong` 
            MODIFY COLUMN `TV_DIEM` DECIMAL(4,2) NULL DEFAULT NULL COMMENT 'Điểm đánh giá của thành viên (0-10)'";
    if ($conn->query($sql)) {
        echo "   ✓ Đã cập nhật kiểu dữ liệu cho TV_DIEM\n";
    } else {
        echo "   ! Cảnh báo: Không thể cập nhật TV_DIEM: " . $conn->error . "\n";
    }
    
    // Cập nhật kiểu dữ liệu cho TV_DANHGIA
    echo "7. Cập nhật kiểu dữ liệu cho cột TV_DANHGIA...\n";
    $sql = "ALTER TABLE `thanh_vien_hoi_dong` 
            MODIFY COLUMN `TV_DANHGIA` TEXT NULL COMMENT 'Nhận xét đánh giá của thành viên'";
    if ($conn->query($sql)) {
        echo "   ✓ Đã cập nhật kiểu dữ liệu cho TV_DANHGIA\n";
    } else {
        echo "   ! Cảnh báo: Không thể cập nhật TV_DANHGIA: " . $conn->error . "\n";
    }
    
    // Thêm index
    echo "8. Thêm index để tối ưu hóa truy vấn...\n";
    
    // Kiểm tra index đã tồn tại chưa
    $result = $conn->query("SHOW INDEX FROM thanh_vien_hoi_dong WHERE Key_name = 'idx_qd_so'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE `thanh_vien_hoi_dong` ADD INDEX `idx_qd_so` (`QD_SO`)";
        if ($conn->query($sql)) {
            echo "   ✓ Đã thêm index idx_qd_so\n";
        } else {
            echo "   ! Cảnh báo: Không thể thêm index idx_qd_so: " . $conn->error . "\n";
        }
    } else {
        echo "   - Index idx_qd_so đã tồn tại\n";
    }
    
    $result = $conn->query("SHOW INDEX FROM thanh_vien_hoi_dong WHERE Key_name = 'idx_gv_magv'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE `thanh_vien_hoi_dong` ADD INDEX `idx_gv_magv` (`GV_MAGV`)";
        if ($conn->query($sql)) {
            echo "   ✓ Đã thêm index idx_gv_magv\n";
        } else {
            echo "   ! Cảnh báo: Không thể thêm index idx_gv_magv: " . $conn->error . "\n";
        }
    } else {
        echo "   - Index idx_gv_magv đã tồn tại\n";
    }
    
    // Tạo view
    echo "9. Tạo view để dễ dàng truy vấn thông tin hội đồng...\n";
    $sql = "CREATE OR REPLACE VIEW `view_council_members` AS
            SELECT 
                qd.QD_SO,
                qd.QD_NGAY,
                tv.GV_MAGV,
                tv.TV_HOTEN,
                gv.GV_HOTEN as GV_HOTEN_FULL,
                tv.TV_VAITRO,
                tv.TV_DIEM,
                tv.TV_DANHGIA,
                bb.BB_SOBB,
                bb.BB_NGAYNGHIEMTHU
            FROM quyet_dinh_nghiem_thu qd
            LEFT JOIN thanh_vien_hoi_dong tv ON qd.QD_SO = tv.QD_SO
            LEFT JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
            LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO";
    
    if ($conn->query($sql)) {
        echo "   ✓ Đã tạo view view_council_members\n";
    } else {
        echo "   ! Cảnh báo: Không thể tạo view: " . $conn->error . "\n";
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "\n✅ Cập nhật cấu trúc cơ sở dữ liệu hoàn thành thành công!\n";
    echo "\nCác thay đổi đã được thực hiện:\n";
    echo "- Thêm cột QD_NOIDUNG vào bảng quyet_dinh_nghiem_thu\n";
    echo "- Thêm cột HD_THANHVIEN vào bảng quyet_dinh_nghiem_thu\n";
    echo "- Thêm cột TV_HOTEN vào bảng thanh_vien_hoi_dong\n";
    echo "- Cập nhật kiểu dữ liệu cho TV_DIEM và TV_DANHGIA\n";
    echo "- Thêm index để tối ưu hóa truy vấn\n";
    echo "- Tạo view view_council_members\n";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    echo "\n❌ Lỗi trong quá trình cập nhật: " . $e->getMessage() . "\n";
}

echo "</pre>\n";

// Hiển thị cấu trúc bảng sau khi cập nhật
echo "<h3>Cấu trúc bảng sau khi cập nhật:</h3>\n";
echo "<h4>Bảng quyet_dinh_nghiem_thu:</h4>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";

$result = $conn->query("DESCRIBE quyet_dinh_nghiem_thu");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h4>Bảng thanh_vien_hoi_dong:</h4>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";

$result = $conn->query("DESCRIBE thanh_vien_hoi_dong");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>\n";
}
echo "</table>\n";

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cập nhật cơ sở dữ liệu</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
        table { border-collapse: collapse; margin: 10px 0; }
        th { background: #007bff; color: white; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
</body>
</html>
