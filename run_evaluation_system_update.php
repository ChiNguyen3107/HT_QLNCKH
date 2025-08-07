<?php
// File: run_evaluation_system_update.php
// Script để chạy cập nhật hệ thống đánh giá chi tiết

include 'include/connect.php';

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

echo "<h2>Cập nhật hệ thống đánh giá chi tiết</h2>\n";
echo "<pre>\n";

try {
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    echo "1. Cập nhật bảng thanh_vien_hoi_dong...\n";
    
    // Kiểm tra cột đã tồn tại chưa
    $result = $conn->query("DESCRIBE thanh_vien_hoi_dong");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    // Thêm các cột mới nếu chưa có
    $new_columns = [
        'TV_DIEMCHITIET' => "ADD COLUMN `TV_DIEMCHITIET` JSON NULL COMMENT 'Điểm chi tiết theo từng tiêu chí (dạng JSON)' AFTER `TV_DIEM`",
        'TV_TRANGTHAI' => "ADD COLUMN `TV_TRANGTHAI` ENUM('Chưa đánh giá', 'Đang đánh giá', 'Đã hoàn thành') DEFAULT 'Chưa đánh giá' COMMENT 'Trạng thái đánh giá' AFTER `TV_DIEMCHITIET`",
        'TV_NGAYDANHGIA' => "ADD COLUMN `TV_NGAYDANHGIA` DATETIME NULL COMMENT 'Ngày cập nhật đánh giá cuối cùng' AFTER `TV_TRANGTHAI`",
        'TV_FILEDANHGIA' => "ADD COLUMN `TV_FILEDANHGIA` VARCHAR(255) NULL COMMENT 'File đánh giá của thành viên' AFTER `TV_NGAYDANHGIA`"
    ];
    
    foreach ($new_columns as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            $full_sql = "ALTER TABLE `thanh_vien_hoi_dong` " . $sql;
            if ($conn->query($full_sql)) {
                echo "   ✓ Đã thêm cột $column\n";
            } else {
                throw new Exception("Lỗi thêm cột $column: " . $conn->error);
            }
        } else {
            echo "   - Cột $column đã tồn tại\n";
        }
    }
    
    echo "2. Cập nhật bảng file_dinh_kem...\n";
    
    $result = $conn->query("DESCRIBE file_dinh_kem");
    $fdk_existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $fdk_existing_columns[] = $row['Field'];
    }
    
    $fdk_new_columns = [
        'GV_MAGV' => "ADD COLUMN `GV_MAGV` CHAR(8) NULL COMMENT 'Mã giảng viên (thành viên hội đồng)' AFTER `BB_SOBB`",
        'FDG_TENFILE' => "ADD COLUMN `FDG_TENFILE` VARCHAR(200) NULL COMMENT 'Tên hiển thị của file' AFTER `FDG_LOAI`",
        'FDG_NGAYTAO' => "ADD COLUMN `FDG_NGAYTAO` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày tạo file' AFTER `FDG_FILE`",
        'FDG_KICHTHUC' => "ADD COLUMN `FDG_KICHTHUC` BIGINT NULL COMMENT 'Kích thước file (bytes)' AFTER `FDG_NGAYTAO`",
        'FDG_MOTA' => "ADD COLUMN `FDG_MOTA` TEXT NULL COMMENT 'Mô tả file đánh giá' AFTER `FDG_KICHTHUC`"
    ];
    
    foreach ($fdk_new_columns as $column => $sql) {
        if (!in_array($column, $fdk_existing_columns)) {
            $full_sql = "ALTER TABLE `file_dinh_kem` " . $sql;
            if ($conn->query($full_sql)) {
                echo "   ✓ Đã thêm cột $column\n";
            } else {
                throw new Exception("Lỗi thêm cột $column: " . $conn->error);
            }
        } else {
            echo "   - Cột $column đã tồn tại\n";
        }
    }
    
    echo "3. Tạo bảng chi_tiet_diem_danh_gia...\n";
    
    // Kiểm tra bảng đã tồn tại chưa
    $result = $conn->query("SHOW TABLES LIKE 'chi_tiet_diem_danh_gia'");
    if ($result->num_rows == 0) {
        $create_table_sql = "
        CREATE TABLE `chi_tiet_diem_danh_gia` (
            `CTDDG_MA` CHAR(10) NOT NULL,
            `QD_SO` CHAR(5) NOT NULL,
            `GV_MAGV` CHAR(8) NOT NULL,
            `TC_MATC` CHAR(5) NOT NULL,
            `CTDDG_DIEM` DECIMAL(4,2) NOT NULL DEFAULT 0.00 COMMENT 'Điểm đánh giá cho tiêu chí này',
            `CTDDG_GHICHU` TEXT NULL COMMENT 'Ghi chú đánh giá',
            `CTDDG_NGAYCAPNHAT` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`CTDDG_MA`),
            UNIQUE KEY `unique_member_criteria` (`QD_SO`, `GV_MAGV`, `TC_MATC`),
            KEY `idx_qd_so` (`QD_SO`),
            KEY `idx_gv_magv` (`GV_MAGV`),
            KEY `idx_tc_matc` (`TC_MATC`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($conn->query($create_table_sql)) {
            echo "   ✓ Đã tạo bảng chi_tiet_diem_danh_gia\n";
        } else {
            throw new Exception("Lỗi tạo bảng chi_tiet_diem_danh_gia: " . $conn->error);
        }
    } else {
        echo "   - Bảng chi_tiet_diem_danh_gia đã tồn tại\n";
    }
    
    echo "4. Thêm ràng buộc khóa ngoại...\n";
    
    // Kiểm tra và thêm khóa ngoại
    $foreign_keys = [
        'fk_ctddg_quyet_dinh' => "ALTER TABLE `chi_tiet_diem_danh_gia` ADD CONSTRAINT `fk_ctddg_quyet_dinh` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`) ON DELETE CASCADE",
        'fk_ctddg_giang_vien' => "ALTER TABLE `chi_tiet_diem_danh_gia` ADD CONSTRAINT `fk_ctddg_giang_vien` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`) ON DELETE CASCADE",
        'fk_ctddg_tieu_chi' => "ALTER TABLE `chi_tiet_diem_danh_gia` ADD CONSTRAINT `fk_ctddg_tieu_chi` FOREIGN KEY (`TC_MATC`) REFERENCES `tieu_chi` (`TC_MATC`) ON DELETE CASCADE",
        'fk_file_dinh_kem_giang_vien' => "ALTER TABLE `file_dinh_kem` ADD CONSTRAINT `fk_file_dinh_kem_giang_vien` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien`(`GV_MAGV`) ON DELETE SET NULL ON UPDATE CASCADE"
    ];
    
    foreach ($foreign_keys as $fk_name => $sql) {
        // Kiểm tra khóa ngoại đã tồn tại chưa
        $check_fk = $conn->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND CONSTRAINT_NAME = '$fk_name'
        ");
        
        if ($check_fk->num_rows == 0) {
            if ($conn->query($sql)) {
                echo "   ✓ Đã thêm khóa ngoại $fk_name\n";
            } else {
                echo "   ! Cảnh báo: Không thể thêm khóa ngoại $fk_name: " . $conn->error . "\n";
            }
        } else {
            echo "   - Khóa ngoại $fk_name đã tồn tại\n";
        }
    }
    
    echo "5. Tạo view view_chi_tiet_danh_gia...\n";
    
    $create_view_sql = "
    CREATE OR REPLACE VIEW `view_chi_tiet_danh_gia` AS
    SELECT 
        ctddg.CTDDG_MA,
        ctddg.QD_SO,
        qd.QD_NGAY,
        bb.BB_SOBB,
        bb.BB_NGAYNGHIEMTHU,
        tv.GV_MAGV,
        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
        tv.TV_VAITRO,
        tv.TV_HOTEN as TV_HOTEN_HIENTHI,
        ctddg.TC_MATC,
        tc.TC_NDDANHGIA,
        tc.TC_DIEMTOIDA,
        ctddg.CTDDG_DIEM,
        ctddg.CTDDG_GHICHU,
        ctddg.CTDDG_NGAYCAPNHAT,
        tv.TV_FILEDANHGIA,
        tv.TV_TRANGTHAI,
        tv.TV_NGAYDANHGIA
    FROM chi_tiet_diem_danh_gia ctddg
    JOIN quyet_dinh_nghiem_thu qd ON ctddg.QD_SO = qd.QD_SO
    LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
    JOIN thanh_vien_hoi_dong tv ON ctddg.QD_SO = tv.QD_SO AND ctddg.GV_MAGV = tv.GV_MAGV
    JOIN giang_vien gv ON ctddg.GV_MAGV = gv.GV_MAGV
    JOIN tieu_chi tc ON ctddg.TC_MATC = tc.TC_MATC";
    
    if ($conn->query($create_view_sql)) {
        echo "   ✓ Đã tạo view view_chi_tiet_danh_gia\n";
    } else {
        echo "   ! Cảnh báo: Không thể tạo view: " . $conn->error . "\n";
    }
    
    echo "6. Thêm tiêu chí mẫu...\n";
    
    $sample_criteria = [
        ['TC001', 'Tính mới, tính khoa học và tính ứng dụng của đề tài', 20],
        ['TC002', 'Phương pháp nghiên cứu và tính khả thi', 15],
        ['TC003', 'Kết quả nghiên cứu và sản phẩm đạt được', 25],
        ['TC004', 'Khả năng ứng dụng và triển khai', 15],
        ['TC005', 'Chất lượng báo cáo và trình bày', 15],
        ['TC006', 'Khả năng trả lời và thảo luận', 10]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO `tieu_chi` (`TC_MATC`, `TC_NDDANHGIA`, `TC_DIEMTOIDA`) VALUES (?, ?, ?)");
    $added_count = 0;
    
    foreach ($sample_criteria as $criteria) {
        $stmt->bind_param("ssi", $criteria[0], $criteria[1], $criteria[2]);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $added_count++;
        }
    }
    
    echo "   ✓ Đã thêm $added_count tiêu chí mới\n";
    
    echo "7. Tạo stored procedure và trigger...\n";
    
    // Tạo stored procedure
    $drop_proc = "DROP PROCEDURE IF EXISTS CalculateMemberTotalScore";
    $conn->query($drop_proc);
    
    $create_proc_sql = "
    CREATE PROCEDURE `CalculateMemberTotalScore`(
        IN p_qd_so CHAR(5),
        IN p_gv_magv CHAR(8)
    )
    BEGIN
        DECLARE total_score DECIMAL(5,2) DEFAULT 0.00;
        
        -- Tính tổng điểm từ chi tiết đánh giá
        SELECT COALESCE(SUM(CTDDG_DIEM), 0) INTO total_score
        FROM chi_tiet_diem_danh_gia
        WHERE QD_SO = p_qd_so AND GV_MAGV = p_gv_magv;
        
        -- Cập nhật vào bảng thanh_vien_hoi_dong
        UPDATE thanh_vien_hoi_dong 
        SET TV_DIEM = total_score,
            TV_NGAYDANHGIA = CURRENT_TIMESTAMP,
            TV_TRANGTHAI = CASE 
                WHEN total_score > 0 THEN 'Đã hoàn thành'
                ELSE TV_TRANGTHAI
            END
        WHERE QD_SO = p_qd_so AND GV_MAGV = p_gv_magv;
        
        SELECT total_score as total_score;
    END";
    
    if ($conn->query($create_proc_sql)) {
        echo "   ✓ Đã tạo stored procedure CalculateMemberTotalScore\n";
    } else {
        echo "   ! Cảnh báo: Không thể tạo stored procedure: " . $conn->error . "\n";
    }
    
    echo "8. Thêm index để tối ưu hóa...\n";
    
    $indexes = [
        'idx_tv_trangthai' => "CREATE INDEX `idx_tv_trangthai` ON `thanh_vien_hoi_dong` (`TV_TRANGTHAI`)",
        'idx_tv_ngaydanhgia' => "CREATE INDEX `idx_tv_ngaydanhgia` ON `thanh_vien_hoi_dong` (`TV_NGAYDANHGIA`)",
        'idx_fdg_gv_magv' => "CREATE INDEX `idx_fdg_gv_magv` ON `file_dinh_kem` (`GV_MAGV`)",
        'idx_fdg_ngaytao' => "CREATE INDEX `idx_fdg_ngaytao` ON `file_dinh_kem` (`FDG_NGAYTAO`)"
    ];
    
    foreach ($indexes as $index_name => $sql) {
        // Kiểm tra index đã tồn tại chưa
        $table_name = strpos($sql, 'thanh_vien_hoi_dong') !== false ? 'thanh_vien_hoi_dong' : 'file_dinh_kem';
        $check_index = $conn->query("SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'");
        
        if ($check_index->num_rows == 0) {
            if ($conn->query($sql)) {
                echo "   ✓ Đã thêm index $index_name\n";
            } else {
                echo "   ! Cảnh báo: Không thể thêm index $index_name: " . $conn->error . "\n";
            }
        } else {
            echo "   - Index $index_name đã tồn tại\n";
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "\n✅ Cập nhật hệ thống đánh giá chi tiết hoàn thành thành công!\n";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    echo "\n❌ Lỗi trong quá trình cập nhật: " . $e->getMessage() . "\n";
}

echo "</pre>\n";

// Hiển thị thống kê sau khi cập nhật
echo "<h3>Thống kê sau khi cập nhật:</h3>\n";

// Số lượng tiêu chí
$result = $conn->query("SELECT COUNT(*) as count FROM tieu_chi");
$criteria_count = $result->fetch_assoc()['count'];
echo "<p>Số tiêu chí đánh giá: <strong>$criteria_count</strong></p>\n";

// Danh sách tiêu chí
echo "<h4>Danh sách tiêu chí đánh giá:</h4>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Mã TC</th><th>Nội dung đánh giá</th><th>Điểm tối đa</th></tr>\n";

$result = $conn->query("SELECT * FROM tieu_chi ORDER BY TC_MATC");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['TC_MATC']) . "</td>";
    echo "<td>" . htmlspecialchars($row['TC_NDDANHGIA']) . "</td>";
    echo "<td class='text-center'>" . htmlspecialchars($row['TC_DIEMTOIDA']) . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cập nhật hệ thống đánh giá chi tiết</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
        table { border-collapse: collapse; margin: 10px 0; width: 100%; }
        th { background: #007bff; color: white; padding: 8px; }
        td { padding: 8px; border: 1px solid #ddd; }
        .text-center { text-align: center; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
</body>
</html>
