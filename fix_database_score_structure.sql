-- ================================================================
-- SCRIPT SỬA LỖI CẤU TRÚC DATABASE CHO HỆ THỐNG ĐIỂM SỐ
-- Tạo ngày: 05/08/2025
-- Mục đích: Sửa lỗi hiển thị và lưu trữ điểm số sai trong hệ thống
-- ================================================================

-- Backup dữ liệu trước khi sửa đổi
CREATE TABLE IF NOT EXISTS backup_bien_ban_20250805 AS SELECT * FROM bien_ban;
CREATE TABLE IF NOT EXISTS backup_thanh_vien_hoi_dong_20250805 AS SELECT * FROM thanh_vien_hoi_dong;

-- ================================================================
-- 1. SỬA CẤU TRÚC BẢNG BIEN_BAN
-- ================================================================

-- Sửa cột BB_TONGDIEM từ decimal(4,3) thành decimal(5,2) để hỗ trợ thang điểm 100
-- Ví dụ: 95.75, 88.50, 100.00
ALTER TABLE bien_ban 
MODIFY COLUMN BB_TONGDIEM decimal(5,2) DEFAULT NULL 
COMMENT 'Tổng điểm đánh giá (thang điểm 100, VD: 85.50)';

-- Thêm index để tối ưu truy vấn
ALTER TABLE bien_ban 
ADD INDEX idx_bien_ban_tongdiem_optimized (BB_TONGDIEM);

-- ================================================================
-- 2. SỬA CẤU TRÚC BẢNG THANH_VIEN_HOI_DONG  
-- ================================================================

-- Cập nhật comment cho cột TV_DIEM để rõ ràng thang điểm
ALTER TABLE thanh_vien_hoi_dong 
MODIFY COLUMN TV_DIEM decimal(5,2) DEFAULT NULL 
COMMENT 'Điểm đánh giá của thành viên (thang điểm 100, VD: 85.50)';

-- ================================================================
-- 3. CHUYỂN ĐỔI DỮ LIỆU CŨ (NẾU CẦN)
-- ================================================================

-- Nếu có dữ liệu cũ theo thang điểm 10, chuyển đổi sang thang điểm 100
-- Kiểm tra và chuyển đổi dữ liệu trong bảng bien_ban
UPDATE bien_ban 
SET BB_TONGDIEM = BB_TONGDIEM * 10 
WHERE BB_TONGDIEM IS NOT NULL 
  AND BB_TONGDIEM <= 10 
  AND BB_TONGDIEM > 0;

-- Kiểm tra và chuyển đổi dữ liệu trong bảng thanh_vien_hoi_dong
UPDATE thanh_vien_hoi_dong 
SET TV_DIEM = TV_DIEM * 10 
WHERE TV_DIEM IS NOT NULL 
  AND TV_DIEM <= 10 
  AND TV_DIEM > 0;

-- ================================================================
-- 4. THÊM CONSTRAINT ĐỂ ĐẢM BẢO DỮ LIỆU HỢP LỆ
-- ================================================================

-- Constraint cho bảng bien_ban: điểm từ 0 đến 100
ALTER TABLE bien_ban 
ADD CONSTRAINT chk_bien_ban_tongdiem_range 
CHECK (BB_TONGDIEM IS NULL OR (BB_TONGDIEM >= 0 AND BB_TONGDIEM <= 100));

-- Constraint cho bảng thanh_vien_hoi_dong: điểm từ 0 đến 100  
ALTER TABLE thanh_vien_hoi_dong 
ADD CONSTRAINT chk_thanh_vien_diem_range 
CHECK (TV_DIEM IS NULL OR (TV_DIEM >= 0 AND TV_DIEM <= 100));

-- ================================================================
-- 5. TẠO TRIGGER TỰ ĐỘNG TÍNH TOÁN ĐIỂM TRUNG BÌNH
-- ================================================================

DELIMITER $$

-- Trigger tự động cập nhật BB_TONGDIEM khi TV_DIEM thay đổi
CREATE TRIGGER tr_auto_update_bien_ban_tongdiem
AFTER UPDATE ON thanh_vien_hoi_dong
FOR EACH ROW
BEGIN
    DECLARE avg_score DECIMAL(5,2);
    DECLARE valid_count INT;
    
    -- Tính điểm trung bình từ các thành viên hội đồng có điểm hợp lệ
    SELECT 
        AVG(TV_DIEM),
        COUNT(*)
    INTO avg_score, valid_count
    FROM thanh_vien_hoi_dong 
    WHERE QD_SO = NEW.QD_SO 
      AND TV_DIEM IS NOT NULL 
      AND TV_DIEM >= 0 
      AND TV_DIEM <= 100;
    
    -- Chỉ cập nhật nếu có ít nhất 2 thành viên đã chấm điểm
    IF valid_count >= 2 THEN
        UPDATE bien_ban 
        SET BB_TONGDIEM = avg_score 
        WHERE QD_SO = NEW.QD_SO;
    END IF;
END$$

-- Trigger tương tự cho INSERT
CREATE TRIGGER tr_auto_insert_bien_ban_tongdiem
AFTER INSERT ON thanh_vien_hoi_dong
FOR EACH ROW
BEGIN
    DECLARE avg_score DECIMAL(5,2);
    DECLARE valid_count INT;
    
    -- Tính điểm trung bình từ các thành viên hội đồng có điểm hợp lệ
    SELECT 
        AVG(TV_DIEM),
        COUNT(*)
    INTO avg_score, valid_count
    FROM thanh_vien_hoi_dong 
    WHERE QD_SO = NEW.QD_SO 
      AND TV_DIEM IS NOT NULL 
      AND TV_DIEM >= 0 
      AND TV_DIEM <= 100;
    
    -- Chỉ cập nhật nếu có ít nhất 2 thành viên đã chấm điểm
    IF valid_count >= 2 THEN
        UPDATE bien_ban 
        SET BB_TONGDIEM = avg_score 
        WHERE QD_SO = NEW.QD_SO;
    END IF;
END$$

DELIMITER ;

-- ================================================================
-- 6. THÊM VIEW ĐỂ DỄ DÀNG TRUY VẤN THỐNG KÊ ĐIỂM
-- ================================================================

CREATE OR REPLACE VIEW view_diem_danh_gia_chi_tiet AS
SELECT 
    bb.BB_SOBB,
    bb.QD_SO,
    bb.BB_NGAYNGHIEMTHU,
    bb.BB_XEPLOAI,
    bb.BB_TONGDIEM,
    tv.GV_MAGV,
    CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN,
    tv.TV_VAITRO,
    tv.TV_DIEM,
    tv.TV_TRANGTHAI,
    tv.TV_NGAYDANHGIA,
    -- Thống kê điểm
    (SELECT COUNT(*) FROM thanh_vien_hoi_dong WHERE QD_SO = bb.QD_SO AND TV_DIEM IS NOT NULL) AS SO_THANH_VIEN_DA_CHAM,
    (SELECT COUNT(*) FROM thanh_vien_hoi_dong WHERE QD_SO = bb.QD_SO) AS TONG_SO_THANH_VIEN,
    (SELECT AVG(TV_DIEM) FROM thanh_vien_hoi_dong WHERE QD_SO = bb.QD_SO AND TV_DIEM IS NOT NULL) AS DIEM_TRUNG_BINH_THUC_TE,
    (SELECT MIN(TV_DIEM) FROM thanh_vien_hoi_dong WHERE QD_SO = bb.QD_SO AND TV_DIEM IS NOT NULL) AS DIEM_THAP_NHAT,
    (SELECT MAX(TV_DIEM) FROM thanh_vien_hoi_dong WHERE QD_SO = bb.QD_SO AND TV_DIEM IS NOT NULL) AS DIEM_CAO_NHAT
FROM bien_ban bb
LEFT JOIN thanh_vien_hoi_dong tv ON bb.QD_SO = tv.QD_SO
LEFT JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
ORDER BY bb.BB_SOBB, tv.TV_VAITRO;

-- ================================================================
-- 7. TẠO STORED PROCEDURE ĐỂ TÍNH LẠI TẤT CẢ ĐIỂM
-- ================================================================

DELIMITER $$

CREATE PROCEDURE sp_recalculate_all_scores()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE qd_so_var CHAR(5);
    DECLARE avg_score DECIMAL(5,2);
    DECLARE valid_count INT;
    
    -- Cursor để duyệt qua tất cả quyết định
    DECLARE qd_cursor CURSOR FOR 
        SELECT DISTINCT QD_SO FROM thanh_vien_hoi_dong;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN qd_cursor;
    
    recalc_loop: LOOP
        FETCH qd_cursor INTO qd_so_var;
        IF done THEN
            LEAVE recalc_loop;
        END IF;
        
        -- Tính điểm trung bình cho quyết định này
        SELECT 
            AVG(TV_DIEM),
            COUNT(*)
        INTO avg_score, valid_count
        FROM thanh_vien_hoi_dong 
        WHERE QD_SO = qd_so_var 
          AND TV_DIEM IS NOT NULL 
          AND TV_DIEM >= 0 
          AND TV_DIEM <= 100;
        
        -- Cập nhật điểm vào bảng bien_ban
        IF valid_count > 0 THEN
            UPDATE bien_ban 
            SET BB_TONGDIEM = avg_score 
            WHERE QD_SO = qd_so_var;
        END IF;
        
    END LOOP;
    
    CLOSE qd_cursor;
    
    -- Thông báo kết quả
    SELECT 
        'Đã tính lại điểm cho tất cả biên bản' AS message,
        COUNT(*) AS so_bien_ban_da_cap_nhat
    FROM bien_ban 
    WHERE BB_TONGDIEM IS NOT NULL;
    
END$$

DELIMITER ;

-- ================================================================
-- 8. CHẠY SCRIPT TÍNH LẠI ĐIỂM CHO DỮ LIỆU HIỆN TẠI
-- ================================================================

-- Gọi stored procedure để tính lại tất cả điểm
CALL sp_recalculate_all_scores();

-- ================================================================
-- 9. KẾT QUẢ VÀ KIỂM TRA
-- ================================================================

-- Kiểm tra kết quả sau khi sửa đổi
SELECT 
    'KIỂM TRA KẾT QUẢ SAU KHI SỬA ĐỔI' AS status;

-- Hiển thị thống kê điểm hiện tại
SELECT 
    bb.BB_SOBB,
    bb.BB_TONGDIEM AS 'Điểm trong DB',
    COUNT(tv.TV_DIEM) AS 'Số TV đã chấm',
    AVG(tv.TV_DIEM) AS 'Điểm TB thực tế',
    MIN(tv.TV_DIEM) AS 'Điểm thấp nhất',
    MAX(tv.TV_DIEM) AS 'Điểm cao nhất',
    CASE 
        WHEN ABS(bb.BB_TONGDIEM - AVG(tv.TV_DIEM)) < 0.01 THEN 'ĐÚNG'
        ELSE 'SAI'
    END AS 'Trạng thái'
FROM bien_ban bb
LEFT JOIN thanh_vien_hoi_dong tv ON bb.QD_SO = tv.QD_SO AND tv.TV_DIEM IS NOT NULL
GROUP BY bb.BB_SOBB, bb.BB_TONGDIEM
ORDER BY bb.BB_SOBB;

-- Kiểm tra cấu trúc bảng đã được sửa
DESCRIBE bien_ban;
DESCRIBE thanh_vien_hoi_dong;

-- ================================================================
-- HOÀN THÀNH!
-- Script này đã:
-- 1. Sửa cấu trúc database để hỗ trợ thang điểm 100
-- 2. Chuyển đổi dữ liệu cũ (nếu có)
-- 3. Thêm constraint bảo đảm dữ liệu hợp lệ
-- 4. Tạo trigger tự động tính toán điểm
-- 5. Tạo view và stored procedure hỗ trợ
-- 6. Tính lại tất cả điểm hiện tại
-- ================================================================
