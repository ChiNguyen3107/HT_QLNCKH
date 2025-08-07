-- Cập nhật bảng tiêu chí đánh giá để hỗ trợ hệ thống đánh giá chi tiết
-- File: update_evaluation_criteria.sql

-- Bước 1: Thêm các cột cần thiết vào bảng tieu_chi (chỉ thêm nếu chưa có)
-- Kiểm tra và thêm cột TC_TEN
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tieu_chi' AND COLUMN_NAME = 'TC_TEN');
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `tieu_chi` ADD COLUMN `TC_TEN` VARCHAR(255) NULL AFTER `TC_MATC`', 'SELECT "Cột TC_TEN đã tồn tại" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm cột TC_MOTA
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tieu_chi' AND COLUMN_NAME = 'TC_MOTA');
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `tieu_chi` ADD COLUMN `TC_MOTA` TEXT NULL AFTER `TC_NDDANHGIA`', 'SELECT "Cột TC_MOTA đã tồn tại" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm cột TC_TRONGSO
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tieu_chi' AND COLUMN_NAME = 'TC_TRONGSO');
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `tieu_chi` ADD COLUMN `TC_TRONGSO` DECIMAL(5,2) DEFAULT 20.00 AFTER `TC_DIEMTOIDA`', 'SELECT "Cột TC_TRONGSO đã tồn tại" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm cột TC_THUTU
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tieu_chi' AND COLUMN_NAME = 'TC_THUTU');
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `tieu_chi` ADD COLUMN `TC_THUTU` INT DEFAULT 1 AFTER `TC_TRONGSO`', 'SELECT "Cột TC_THUTU đã tồn tại" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm cột TC_TRANGTHAI
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tieu_chi' AND COLUMN_NAME = 'TC_TRANGTHAI');
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `tieu_chi` ADD COLUMN `TC_TRANGTHAI` ENUM(\'Hoạt động\', \'Tạm dừng\') DEFAULT \'Hoạt động\' AFTER `TC_THUTU`', 'SELECT "Cột TC_TRANGTHAI đã tồn tại" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Bước 2: Thêm dữ liệu mẫu CHỈ KHI CHƯA CÓ DỮ LIỆU (GIỮ NGUYÊN DỮ LIỆU CŨ)
-- Kiểm tra nếu bảng tieu_chi chưa có dữ liệu thì mới thêm mẫu
SET @data_count = (SELECT COUNT(*) FROM `tieu_chi`);

-- Chỉ thêm dữ liệu mẫu nếu bảng rỗng
INSERT IGNORE INTO `tieu_chi` (`TC_MATC`, `TC_TEN`, `TC_NDDANHGIA`, `TC_MOTA`, `TC_DIEMTOIDA`, `TC_TRONGSO`, `TC_THUTU`) 
SELECT * FROM (
    SELECT 'TC001' as TC_MATC, 'Tính khoa học của đề tài' as TC_TEN, 'Đánh giá tính khoa học, tính mới và ý nghĩa khoa học của đề tài nghiên cứu' as TC_NDDANHGIA, 'Xem xét mức độ sáng tạo, tính khoa học và giá trị học thuật của nghiên cứu' as TC_MOTA, 10 as TC_DIEMTOIDA, 25.00 as TC_TRONGSO, 1 as TC_THUTU
    UNION ALL SELECT 'TC002', 'Phương pháp nghiên cứu', 'Đánh giá sự phù hợp và tính khả thi của phương pháp nghiên cứu được sử dụng', 'Xem xét tính logic, hợp lý của phương pháp và khả năng thực hiện', 10, 20.00, 2
    UNION ALL SELECT 'TC003', 'Kết quả nghiên cứu', 'Đánh giá chất lượng, tính đầy đủ và độ tin cậy của kết quả nghiên cứu đạt được', 'Xem xét mức độ hoàn thành mục tiêu và chất lượng kết quả', 10, 25.00, 3
    UNION ALL SELECT 'TC004', 'Ứng dụng thực tiễn', 'Đánh giá khả năng ứng dụng và tác động của kết quả nghiên cứu trong thực tiễn', 'Xem xét giá trị thực tiễn và khả năng chuyển giao kết quả nghiên cứu', 10, 15.00, 4
    UNION ALL SELECT 'TC005', 'Báo cáo và trình bày', 'Đánh giá chất lượng báo cáo nghiên cứu và khả năng trình bày của tác giả', 'Xem xét tính rõ ràng, logic trong trình bày và chất lượng báo cáo', 10, 15.00, 5
) AS sample_data
WHERE @data_count = 0;

-- Bước 3: Kiểm tra và tạo bảng chi_tiet_diem_danh_gia nếu chưa có
CREATE TABLE IF NOT EXISTS `chi_tiet_diem_danh_gia` (
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
  CONSTRAINT `fk_ctddg_quyet_dinh` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`) ON DELETE CASCADE,
  CONSTRAINT `fk_ctddg_giang_vien` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`) ON DELETE CASCADE,
  CONSTRAINT `fk_ctddg_tieu_chi` FOREIGN KEY (`TC_MATC`) REFERENCES `tieu_chi` (`TC_MATC`) ON DELETE CASCADE,
  UNIQUE KEY `unique_evaluation` (`QD_SO`, `GV_MAGV`, `TC_MATC`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bước 4: Cập nhật bảng thanh_vien_hoi_dong để hỗ trợ đánh giá chi tiết (chỉ thêm nếu chưa có)
-- Kiểm tra và thêm cột TV_DIEMCHITIET
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'thanh_vien_hoi_dong' AND COLUMN_NAME = 'TV_DIEMCHITIET');
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `thanh_vien_hoi_dong` ADD COLUMN `TV_DIEMCHITIET` ENUM(\'Có\', \'Không\', \'Đang đánh giá\') DEFAULT \'Không\' AFTER `TV_DANHGIA`', 'SELECT "Cột TV_DIEMCHITIET đã tồn tại" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm cột TV_NGAYDANHGIA
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'thanh_vien_hoi_dong' AND COLUMN_NAME = 'TV_NGAYDANHGIA');
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `thanh_vien_hoi_dong` ADD COLUMN `TV_NGAYDANHGIA` TIMESTAMP NULL AFTER `TV_DIEMCHITIET`', 'SELECT "Cột TV_NGAYDANHGIA đã tồn tại" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm cột TV_TRANGTHAI
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'thanh_vien_hoi_dong' AND COLUMN_NAME = 'TV_TRANGTHAI');
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `thanh_vien_hoi_dong` ADD COLUMN `TV_TRANGTHAI` ENUM(\'Chưa đánh giá\', \'Đang đánh giá\', \'Đã hoàn thành\') DEFAULT \'Chưa đánh giá\' AFTER `TV_NGAYDANHGIA`', 'SELECT "Cột TV_TRANGTHAI đã tồn tại" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Bước 5: Tạo view để dễ dàng truy vấn điểm đánh giá chi tiết
CREATE OR REPLACE VIEW `view_danh_gia_chi_tiet` AS
SELECT 
    ctddg.QD_SO,
    ctddg.GV_MAGV,
    gv.GV_HOTEN as TEN_GIANG_VIEN,
    tvhd.TV_VAITRO as VAI_TRO,
    ctddg.TC_MATC,
    tc.TC_TEN as TEN_TIEU_CHI,
    tc.TC_DIEMTOIDA as DIEM_TOI_DA,
    tc.TC_TRONGSO as TRONG_SO,
    ctddg.CTDD_DIEM as DIEM_DAT,
    ctddg.CTDD_NHANXET as NHAN_XET,
    ROUND((ctddg.CTDD_DIEM / tc.TC_DIEMTOIDA) * tc.TC_TRONGSO, 2) as DIEM_TRONG_SO,
    ctddg.CTDD_NGAYTAO as NGAY_DANH_GIA
FROM chi_tiet_diem_danh_gia ctddg
JOIN tieu_chi tc ON ctddg.TC_MATC = tc.TC_MATC
JOIN giang_vien gv ON ctddg.GV_MAGV = gv.GV_MAGV
JOIN thanh_vien_hoi_dong tvhd ON ctddg.QD_SO = tvhd.QD_SO AND ctddg.GV_MAGV = tvhd.GV_MAGV
ORDER BY ctddg.QD_SO, ctddg.GV_MAGV, tc.TC_THUTU;

-- Bước 6: Tạo stored procedure để tính điểm tổng kết
DELIMITER //

CREATE OR REPLACE PROCEDURE `CalculateTotalScore`(
    IN p_qd_so VARCHAR(20),
    IN p_gv_magv VARCHAR(10)
)
BEGIN
    DECLARE total_score DECIMAL(5,2) DEFAULT 0;
    
    -- Tính tổng điểm theo trọng số
    SELECT SUM(ROUND((ctddg.CTDD_DIEM / tc.TC_DIEMTOIDA) * tc.TC_TRONGSO, 2))
    INTO total_score
    FROM chi_tiet_diem_danh_gia ctddg
    JOIN tieu_chi tc ON ctddg.TC_MATC = tc.TC_MATC
    WHERE ctddg.QD_SO = p_qd_so AND ctddg.GV_MAGV = p_gv_magv;
    
    -- Cập nhật điểm vào bảng thanh_vien_hoi_dong
    UPDATE thanh_vien_hoi_dong 
    SET TV_DIEM = total_score,
        TV_DIEMCHITIET = 'Có',
        TV_TRANGTHAI = 'Đã hoàn thành',
        TV_NGAYDANHGIA = NOW()
    WHERE QD_SO = p_qd_so AND GV_MAGV = p_gv_magv;
    
    SELECT total_score as TONG_DIEM;
END //

DELIMITER ;

-- Bước 7: Tạo trigger để tự động cập nhật điểm khi có thay đổi
DELIMITER //

CREATE OR REPLACE TRIGGER `update_total_score_after_detail_change`
AFTER INSERT ON `chi_tiet_diem_danh_gia`
FOR EACH ROW
BEGIN
    CALL CalculateTotalScore(NEW.QD_SO, NEW.GV_MAGV);
END //

CREATE OR REPLACE TRIGGER `update_total_score_after_detail_update`
AFTER UPDATE ON `chi_tiet_diem_danh_gia`
FOR EACH ROW
BEGIN
    CALL CalculateTotalScore(NEW.QD_SO, NEW.GV_MAGV);
END //

DELIMITER ;

-- Bước 8: Kiểm tra dữ liệu sau khi cập nhật
SELECT 'Danh sách tiêu chí đánh giá:' as INFO;
SELECT TC_MATC, TC_TEN, TC_DIEMTOIDA, TC_TRONGSO, TC_TRANGTHAI 
FROM tieu_chi 
ORDER BY TC_THUTU;

SELECT 'Tổng trọng số các tiêu chí:' as INFO;
SELECT SUM(TC_TRONGSO) as TONG_TRONG_SO FROM tieu_chi WHERE TC_TRANGTHAI = 'Hoạt động';
