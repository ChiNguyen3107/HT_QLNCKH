<?php
/**
 * Script tự động cập nhật hệ thống đánh giá chi tiết
 * Chạy file này để cập nhật database và kiểm tra tính năng mới
 */

require_once 'include/connect.php';

echo "<h2>🔄 CẬP NHẬT HỆ THỐNG ĐÁNH GIÁ CHI TIẾT</h2>\n";
echo "<pre>\n";

try {
    // Bước 1: Kiểm tra kết nối database
    echo "✅ Bước 1: Kiểm tra kết nối database...\n";
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối database: " . $conn->connect_error);
    }
    echo "   ✓ Kết nối database thành công\n\n";

    // Bước 2: Backup dữ liệu hiện tại
    echo "📦 Bước 2: Tạo backup dữ liệu...\n";
    $backup_file = "backup_" . date('Y-m-d_H-i-s') . ".sql";
    echo "   ⚠️  Lưu ý: Nên tạo backup database thủ công trước khi chạy script này\n";
    echo "   💡 Lệnh backup: mysqldump -u username -p database_name > $backup_file\n\n";

    // Bước 3: Kiểm tra và cập nhật bảng tieu_chi
    echo "🔧 Bước 3: Cập nhật bảng tiêu chí đánh giá...\n";
    
    // Kiểm tra bảng tieu_chi tồn tại
    $check_table = $conn->query("SHOW TABLES LIKE 'tieu_chi'");
    if ($check_table->num_rows == 0) {
        echo "   ❌ Bảng tieu_chi không tồn tại. Tạo bảng mới...\n";
        $create_table_sql = "
        CREATE TABLE `tieu_chi` (
          `TC_MATC` char(5) NOT NULL PRIMARY KEY,
          `TC_TEN` VARCHAR(255) NULL,
          `TC_NDDANHGIA` text NOT NULL,
          `TC_MOTA` TEXT NULL,
          `TC_DIEMTOIDA` decimal(3,0) NOT NULL,
          `TC_TRONGSO` DECIMAL(5,2) DEFAULT 20.00,
          `TC_THUTU` INT DEFAULT 1,
          `TC_TRANGTHAI` ENUM('Hoạt động', 'Tạm dừng') DEFAULT 'Hoạt động'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        $conn->query($create_table_sql);
        echo "   ✓ Tạo bảng tieu_chi thành công\n";
    } else {
        echo "   ✓ Bảng tieu_chi đã tồn tại\n";
        
        // Kiểm tra và thêm cột mới nếu cần
        $columns = ['TC_TEN', 'TC_MOTA', 'TC_TRONGSO', 'TC_THUTU', 'TC_TRANGTHAI'];
        foreach ($columns as $column) {
            $check_column = $conn->query("SHOW COLUMNS FROM tieu_chi LIKE '$column'");
            if ($check_column->num_rows == 0) {
                echo "   🔄 Thêm cột $column...\n";
                switch ($column) {
                    case 'TC_TEN':
                        $conn->query("ALTER TABLE `tieu_chi` ADD COLUMN `TC_TEN` VARCHAR(255) NULL AFTER `TC_MATC`");
                        break;
                    case 'TC_MOTA':
                        $conn->query("ALTER TABLE `tieu_chi` ADD COLUMN `TC_MOTA` TEXT NULL AFTER `TC_TEN`");
                        break;
                    case 'TC_TRONGSO':
                        $conn->query("ALTER TABLE `tieu_chi` ADD COLUMN `TC_TRONGSO` DECIMAL(5,2) DEFAULT 20.00 AFTER `TC_DIEMTOIDA`");
                        break;
                    case 'TC_THUTU':
                        $conn->query("ALTER TABLE `tieu_chi` ADD COLUMN `TC_THUTU` INT DEFAULT 1 AFTER `TC_TRONGSO`");
                        break;
                    case 'TC_TRANGTHAI':
                        $conn->query("ALTER TABLE `tieu_chi` ADD COLUMN `TC_TRANGTHAI` ENUM('Hoạt động', 'Tạm dừng') DEFAULT 'Hoạt động' AFTER `TC_THUTU`");
                        break;
                }
                echo "     ✓ Thêm cột $column thành công\n";
            }
        }
    }

    // Thêm dữ liệu mẫu nếu chưa có
    $count_criteria = $conn->query("SELECT COUNT(*) as count FROM tieu_chi")->fetch_assoc()['count'];
    if ($count_criteria == 0) {
        echo "   📝 Thêm dữ liệu mẫu cho tiêu chí đánh giá...\n";
        
        $sample_criteria = [
            ['TC001', 'Tính khoa học của đề tài', 'Đánh giá tính khoa học, tính mới và ý nghĩa khoa học của đề tài nghiên cứu', 'Xem xét mức độ sáng tạo, tính khoa học và giá trị học thuật của nghiên cứu', 10, 25.00, 1],
            ['TC002', 'Phương pháp nghiên cứu', 'Đánh giá sự phù hợp và tính khả thi của phương pháp nghiên cứu được sử dụng', 'Xem xét tính logic, hợp lý của phương pháp và khả năng thực hiện', 10, 20.00, 2],
            ['TC003', 'Kết quả nghiên cứu', 'Đánh giá chất lượng, tính đầy đủ và độ tin cậy của kết quả nghiên cứu đạt được', 'Xem xét mức độ hoàn thành mục tiêu và chất lượng kết quả', 10, 25.00, 3],
            ['TC004', 'Ứng dụng thực tiễn', 'Đánh giá khả năng ứng dụng và tác động của kết quả nghiên cứu trong thực tiễn', 'Xem xét giá trị thực tiễn và khả năng chuyển giao kết quả nghiên cứu', 10, 15.00, 4],
            ['TC005', 'Báo cáo và trình bày', 'Đánh giá chất lượng báo cáo nghiên cứu và khả năng trình bày của tác giả', 'Xem xét tính rõ ràng, logic trong trình bày và chất lượng báo cáo', 10, 15.00, 5]
        ];
        
        $stmt = $conn->prepare("INSERT INTO `tieu_chi` (`TC_MATC`, `TC_TEN`, `TC_NDDANHGIA`, `TC_MOTA`, `TC_DIEMTOIDA`, `TC_TRONGSO`, `TC_THUTU`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sample_criteria as $criteria) {
            $stmt->bind_param("ssssddi", $criteria[0], $criteria[1], $criteria[2], $criteria[3], $criteria[4], $criteria[5], $criteria[6]);
            $stmt->execute();
        }
        
        echo "   ✓ Thêm " . count($sample_criteria) . " tiêu chí đánh giá mẫu\n";
    } else {
        echo "   ✓ Dữ liệu tiêu chí đã có sẵn ($count_criteria tiêu chí)\n";
    }

    // Bước 4: Tạo/kiểm tra bảng chi_tiet_diem_danh_gia
    echo "\n🔧 Bước 4: Cập nhật bảng chi tiết điểm đánh giá...\n";
    
    $check_detail_table = $conn->query("SHOW TABLES LIKE 'chi_tiet_diem_danh_gia'");
    if ($check_detail_table->num_rows == 0) {
        echo "   🔄 Tạo bảng chi_tiet_diem_danh_gia...\n";
        $create_detail_table = "
        CREATE TABLE `chi_tiet_diem_danh_gia` (
          `CTDD_ID` INT AUTO_INCREMENT PRIMARY KEY,
          `QD_SO` VARCHAR(20) NOT NULL,
          `GV_MAGV` VARCHAR(10) NOT NULL,
          `TC_MATC` CHAR(5) NOT NULL,
          `CTDD_DIEM` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
          `CTDD_NHANXET` TEXT NULL,
          `CTDD_NGAYTAO` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `CTDD_NGAYCAPNHAT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX `idx_qd_gv` (`QD_SO`, `GV_MAGV`),
          INDEX `idx_tieu_chi` (`TC_MATC`),
          UNIQUE KEY `unique_evaluation` (`QD_SO`, `GV_MAGV`, `TC_MATC`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $conn->query($create_detail_table);
        echo "   ✓ Tạo bảng chi_tiet_diem_danh_gia thành công\n";
    } else {
        echo "   ✓ Bảng chi_tiet_diem_danh_gia đã tồn tại\n";
    }

    // Bước 5: Cập nhật bảng thanh_vien_hoi_dong
    echo "\n🔧 Bước 5: Cập nhật bảng thành viên hội đồng...\n";
    
    $detail_columns = ['TV_DIEMCHITIET', 'TV_NGAYDANHGIA', 'TV_TRANGTHAI'];
    foreach ($detail_columns as $column) {
        $check_column = $conn->query("SHOW COLUMNS FROM thanh_vien_hoi_dong LIKE '$column'");
        if ($check_column->num_rows == 0) {
            echo "   🔄 Thêm cột $column vào thanh_vien_hoi_dong...\n";
            switch ($column) {
                case 'TV_DIEMCHITIET':
                    $conn->query("ALTER TABLE `thanh_vien_hoi_dong` ADD COLUMN `TV_DIEMCHITIET` ENUM('Có', 'Không', 'Đang đánh giá') DEFAULT 'Không' AFTER `TV_DANHGIA`");
                    break;
                case 'TV_NGAYDANHGIA':
                    $conn->query("ALTER TABLE `thanh_vien_hoi_dong` ADD COLUMN `TV_NGAYDANHGIA` TIMESTAMP NULL AFTER `TV_DIEMCHITIET`");
                    break;
                case 'TV_TRANGTHAI':
                    $conn->query("ALTER TABLE `thanh_vien_hoi_dong` ADD COLUMN `TV_TRANGTHAI` ENUM('Chưa đánh giá', 'Đang đánh giá', 'Đã hoàn thành') DEFAULT 'Chưa đánh giá' AFTER `TV_NGAYDANHGIA`");
                    break;
            }
            echo "     ✓ Thêm cột $column thành công\n";
        }
    }

    // Bước 6: Tạo thư mục uploads
    echo "\n📁 Bước 6: Kiểm tra thư mục uploads...\n";
    
    $upload_dirs = [
        'uploads/member_evaluation_files',
        'uploads/evaluation_files'
    ];
    
    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) {
            echo "   🔄 Tạo thư mục $dir...\n";
            if (mkdir($dir, 0755, true)) {
                echo "     ✓ Tạo thư mục thành công\n";
            } else {
                echo "     ❌ Không thể tạo thư mục $dir\n";
            }
        } else {
            echo "   ✓ Thư mục $dir đã tồn tại\n";
        }
        
        // Kiểm tra quyền ghi
        if (is_writable($dir)) {
            echo "     ✓ Thư mục $dir có quyền ghi\n";
        } else {
            echo "     ⚠️  Thư mục $dir không có quyền ghi - cần chmod 755\n";
        }
    }

    // Bước 7: Test API
    echo "\n🧪 Bước 7: Kiểm tra API...\n";
    
    $api_files = [
        'api/get_evaluation_criteria.php' => 'API tiêu chí đánh giá',
        'api/get_member_detailed_scores.php' => 'API điểm chi tiết thành viên',
        'api/get_member_files_new.php' => 'API file thành viên'
    ];
    
    foreach ($api_files as $file => $name) {
        if (file_exists($file)) {
            echo "   ✓ $name: $file tồn tại\n";
        } else {
            echo "   ❌ $name: $file không tồn tại\n";
        }
    }

    // Bước 8: Hiển thị thống kê
    echo "\n📊 Bước 8: Thống kê hệ thống...\n";
    
    $stats = [];
    
    // Đếm tiêu chí
    $criteria_count = $conn->query("SELECT COUNT(*) as count FROM tieu_chi WHERE COALESCE(TC_TRANGTHAI, 'Hoạt động') = 'Hoạt động'")->fetch_assoc()['count'];
    echo "   📋 Tiêu chí đánh giá: $criteria_count tiêu chí\n";
    
    // Đếm đề tài
    $project_count = $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu")->fetch_assoc()['count'];
    echo "   📚 Tổng đề tài: $project_count đề tài\n";
    
    // Đếm biên bản có thành viên hội đồng
    $council_count = $conn->query("SELECT COUNT(DISTINCT QD_SO) as count FROM thanh_vien_hoi_dong")->fetch_assoc()['count'];
    echo "   👥 Biên bản có hội đồng: $council_count biên bản\n";
    
    // Đếm đánh giá chi tiết đã có
    $detail_eval_count = $conn->query("SELECT COUNT(DISTINCT CONCAT(QD_SO, '-', GV_MAGV)) as count FROM chi_tiet_diem_danh_gia")->fetch_assoc()['count'];
    echo "   ⭐ Đánh giá chi tiết: $detail_eval_count lượt đánh giá\n";

    echo "\n✅ CẬP NHẬT HOÀN TẤT!\n\n";
    
    echo "🎯 HƯỚNG DẪN SỬ DỤNG:\n";
    echo "1. Truy cập trang chi tiết đề tài có biên bản nghiệm thu\n";
    echo "2. Vào tab 'Đánh giá' để xem danh sách thành viên hội đồng\n";
    echo "3. Nhấn 'Đánh giá' để nhập điểm chi tiết theo tiêu chí\n";
    echo "4. Nhấn 'Upload file' để tải lên file đánh giá của thành viên\n";
    echo "5. Khi đủ điều kiện, đề tài sẽ tự động chuyển sang 'Đã hoàn thành'\n\n";
    
    echo "🔧 CHỨC NĂNG MỚI:\n";
    echo "✅ Đánh giá thành viên theo 5 tiêu chí với trọng số\n";
    echo "✅ Upload file đánh giá cho từng thành viên\n";
    echo "✅ Tự động tính điểm tổng kết theo trọng số\n";
    echo "✅ Tự động hoàn thành đề tài khi đủ điều kiện\n";
    echo "✅ Hiển thị chi tiết trạng thái đánh giá\n\n";
    
    echo "⚠️  LƯU Ý:\n";
    echo "- Chỉ chủ nhiệm đề tài mới có thể đánh giá thành viên\n";
    echo "- Cần có biên bản nghiệm thu trước khi đánh giá\n";
    echo "- File upload tối đa 10MB, định dạng: PDF, DOC, DOCX, TXT, XLS, XLSX\n";
    echo "- Hệ thống tự động backup điểm khi có thay đổi\n\n";

} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    echo "\n🔧 KHẮC PHỤC:\n";
    echo "1. Kiểm tra kết nối database\n";
    echo "2. Kiểm tra quyền truy cập file và thư mục\n";
    echo "3. Xem log lỗi PHP: /var/log/apache2/error.log\n";
    echo "4. Liên hệ admin để được hỗ trợ\n";
}

echo "</pre>\n";
echo "<p><strong>Cập nhật hoàn tất lúc:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "<p><a href='index.php' class='btn btn-primary'>← Quay lại trang chủ</a></p>\n";
?>
