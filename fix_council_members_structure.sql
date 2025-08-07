-- Script để sửa cấu trúc cơ sở dữ liệu cho việc lưu thành viên hội đồng nghiệm thu
-- Ngày tạo: 2025-08-03

-- 1. Thêm các trường cần thiết vào bảng quyet_dinh_nghiem_thu
ALTER TABLE `quyet_dinh_nghiem_thu` 
ADD COLUMN `QD_NOIDUNG` TEXT NULL COMMENT 'Nội dung chi tiết của quyết định' AFTER `QD_FILE`,
ADD COLUMN `HD_THANHVIEN` TEXT NULL COMMENT 'Danh sách thành viên hội đồng (dạng JSON hoặc text)' AFTER `QD_NOIDUNG`;

-- 2. Cập nhật cấu trúc bảng thanh_vien_hoi_dong để phù hợp hơn
-- Thêm trường để lưu tên đầy đủ của thành viên (nếu cần)
ALTER TABLE `thanh_vien_hoi_dong` 
ADD COLUMN `TV_HOTEN` VARCHAR(100) NULL COMMENT 'Họ tên đầy đủ của thành viên' AFTER `GV_MAGV`,
MODIFY COLUMN `TV_DIEM` DECIMAL(4,2) NULL DEFAULT NULL COMMENT 'Điểm đánh giá của thành viên (0-10)',
MODIFY COLUMN `TV_DANHGIA` TEXT NULL COMMENT 'Nhận xét đánh giá của thành viên';

-- 3. Tạo bảng backup để lưu dữ liệu cũ (nếu có)
CREATE TABLE `thanh_vien_hoi_dong_backup` AS SELECT * FROM `thanh_vien_hoi_dong`;

-- 4. Thêm index để tối ưu hóa truy vấn
ALTER TABLE `thanh_vien_hoi_dong` 
ADD INDEX `idx_qd_so` (`QD_SO`),
ADD INDEX `idx_gv_magv` (`GV_MAGV`);

-- 5. Tạo stored procedure để lưu thành viên hội đồng
DELIMITER $$

CREATE PROCEDURE `SaveCouncilMembers`(
    IN p_qd_so CHAR(5),
    IN p_members_json TEXT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_gv_magv CHAR(8);
    DECLARE v_vaitro VARCHAR(30);
    DECLARE v_hoten VARCHAR(100);
    
    -- Xóa dữ liệu cũ
    DELETE FROM thanh_vien_hoi_dong WHERE QD_SO = p_qd_so;
    
    -- Cập nhật trường HD_THANHVIEN trong bảng quyet_dinh_nghiem_thu
    UPDATE quyet_dinh_nghiem_thu 
    SET HD_THANHVIEN = p_members_json 
    WHERE QD_SO = p_qd_so;
    
END$$

DELIMITER ;

-- 6. Tạo view để dễ dàng truy vấn thông tin hội đồng
CREATE VIEW `view_council_members` AS
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
LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO;

-- 7. Tạo function để parse JSON thành viên hội đồng (nếu MySQL hỗ trợ JSON)
DELIMITER $$

CREATE FUNCTION `ParseCouncilMembersFromJSON`(members_json TEXT)
RETURNS TEXT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE result TEXT DEFAULT '';
    
    -- Nếu không có JSON functions, return nguyên text
    IF members_json IS NULL OR members_json = '' THEN
        RETURN '';
    END IF;
    
    RETURN members_json;
END$$

DELIMITER ;

-- 8. Thêm dữ liệu mẫu cho testing (optional)
-- INSERT INTO thanh_vien_hoi_dong (QD_SO, GV_MAGV, TC_MATC, TV_VAITRO, TV_HOTEN) VALUES
-- ('QD001', 'GV001', 'TC001', 'Chủ tịch', 'PGS.TS. Nguyễn Văn A'),
-- ('QD001', 'GV002', 'TC002', 'Thành viên', 'TS. Trần Thị B'),
-- ('QD001', 'GV003', 'TC003', 'Thư ký', 'ThS. Lê Văn C');

-- Thông báo hoàn thành
SELECT 'Cập nhật cấu trúc cơ sở dữ liệu cho thành viên hội đồng hoàn thành!' as message;
