-- Script cập nhật cấu trúc cơ sở dữ liệu cho tính năng đánh giá chi tiết
-- Ngày tạo: 2025-08-03

-- 1. Cập nhật bảng thanh_vien_hoi_dong để lưu điểm chi tiết theo tiêu chí
ALTER TABLE `thanh_vien_hoi_dong` 
ADD COLUMN `TV_DIEMCHITIET` JSON NULL COMMENT 'Điểm chi tiết theo từng tiêu chí (dạng JSON)' AFTER `TV_DIEM`,
ADD COLUMN `TV_TRANGTHAI` ENUM('Chưa đánh giá', 'Đang đánh giá', 'Đã hoàn thành') DEFAULT 'Chưa đánh giá' COMMENT 'Trạng thái đánh giá' AFTER `TV_DIEMCHITIET`,
ADD COLUMN `TV_NGAYDANHGIA` DATETIME NULL COMMENT 'Ngày cập nhật đánh giá cuối cùng' AFTER `TV_TRANGTHAI`,
ADD COLUMN `TV_FILEDANHGIA` VARCHAR(255) NULL COMMENT 'File đánh giá của thành viên' AFTER `TV_NGAYDANHGIA`;

-- 2. Cập nhật bảng file_dinh_kem để liên kết với thành viên hội đồng
ALTER TABLE `file_dinh_kem`
ADD COLUMN `GV_MAGV` CHAR(8) NULL COMMENT 'Mã giảng viên (thành viên hội đồng)' AFTER `BB_SOBB`,
ADD COLUMN `FDG_TENFILE` VARCHAR(200) NULL COMMENT 'Tên hiển thị của file' AFTER `FDG_LOAI`,
ADD COLUMN `FDG_NGAYTAO` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày tạo file' AFTER `FDG_FILE`,
ADD COLUMN `FDG_KICHTHUC` BIGINT NULL COMMENT 'Kích thước file (bytes)' AFTER `FDG_NGAYTAO`,
ADD COLUMN `FDG_MOTA` TEXT NULL COMMENT 'Mô tả file đánh giá' AFTER `FDG_KICHTHUC`;

-- 3. Thêm ràng buộc khóa ngoại cho bảng file_dinh_kem
ALTER TABLE `file_dinh_kem`
ADD CONSTRAINT `fk_file_dinh_kem_giang_vien` 
FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien`(`GV_MAGV`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 4. Tạo bảng chi_tiet_diem_danh_gia để lưu điểm chi tiết theo tiêu chí
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
    KEY `idx_tc_matc` (`TC_MATC`),
    CONSTRAINT `fk_ctddg_quyet_dinh` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`) ON DELETE CASCADE,
    CONSTRAINT `fk_ctddg_giang_vien` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`) ON DELETE CASCADE,
    CONSTRAINT `fk_ctddg_tieu_chi` FOREIGN KEY (`TC_MATC`) REFERENCES `tieu_chi` (`TC_MATC`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Tạo view để dễ dàng truy vấn thông tin đánh giá chi tiết
CREATE OR REPLACE VIEW `view_chi_tiet_danh_gia` AS
SELECT 
    ctddg.CTDDG_MA,
    ctddg.QD_SO,
    qd.QD_NGAY,
    bb.BB_SOBB,
    bb.BB_NGAYNGHIEMTHU,
    tv.GV_MAGV,
    gv.GV_HOTEN,
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
JOIN tieu_chi tc ON ctddg.TC_MATC = tc.TC_MATC;

-- 6. Tạo stored procedure để tính tổng điểm của một thành viên hội đồng
DELIMITER $$

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
END$$

DELIMITER ;

-- 7. Tạo trigger để tự động cập nhật tổng điểm khi có thay đổi điểm chi tiết
DELIMITER $$

CREATE TRIGGER `tr_update_total_score_after_insert` 
AFTER INSERT ON `chi_tiet_diem_danh_gia`
FOR EACH ROW
BEGIN
    CALL CalculateMemberTotalScore(NEW.QD_SO, NEW.GV_MAGV);
END$$

CREATE TRIGGER `tr_update_total_score_after_update` 
AFTER UPDATE ON `chi_tiet_diem_danh_gia`
FOR EACH ROW
BEGIN
    CALL CalculateMemberTotalScore(NEW.QD_SO, NEW.GV_MAGV);
END$$

CREATE TRIGGER `tr_update_total_score_after_delete` 
AFTER DELETE ON `chi_tiet_diem_danh_gia`
FOR EACH ROW
BEGIN
    CALL CalculateMemberTotalScore(OLD.QD_SO, OLD.GV_MAGV);
END$$

DELIMITER ;

-- 8. Thêm một số tiêu chí mẫu nếu bảng tieu_chi trống
INSERT IGNORE INTO `tieu_chi` (`TC_MATC`, `TC_NDDANHGIA`, `TC_DIEMTOIDA`) VALUES
('TC001', 'Tính mới, tính khoa học và tính ứng dụng của đề tài', 20),
('TC002', 'Phương pháp nghiên cứu và tính khả thi', 15),
('TC003', 'Kết quả nghiên cứu và sản phẩm đạt được', 25),
('TC004', 'Khả năng ứng dụng và triển khai', 15),
('TC005', 'Chất lượng báo cáo và trình bày', 15),
('TC006', 'Khả năng trả lời và thảo luận', 10);

-- 9. Tạo index để tối ưu hóa performance
CREATE INDEX `idx_tv_trangthai` ON `thanh_vien_hoi_dong` (`TV_TRANGTHAI`);
CREATE INDEX `idx_tv_ngaydanhgia` ON `thanh_vien_hoi_dong` (`TV_NGAYDANHGIA`);
CREATE INDEX `idx_fdg_gv_magv` ON `file_dinh_kem` (`GV_MAGV`);
CREATE INDEX `idx_fdg_ngaytao` ON `file_dinh_kem` (`FDG_NGAYTAO`);

-- Thông báo hoàn thành
SELECT 'Cập nhật cấu trúc cơ sở dữ liệu cho tính năng đánh giá chi tiết hoàn thành!' as message;
