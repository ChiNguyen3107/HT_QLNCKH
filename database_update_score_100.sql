-- ==================================================
-- SCRIPT CẬP NHẬT CƠ SỞ DỮ LIỆU CHO ĐIỂM SỐ 0-100
-- ==================================================
-- Ngày tạo: 2025-08-06
-- Mục đích: Cập nhật cấu trúc bảng để hỗ trợ điểm từ 0-100

-- 1. CẬP NHẬT BẢNG THÀNH VIÊN HỘI ĐỒNG
-- Thay đổi cột TV_DIEM để lưu điểm từ 0-100 với 1 chữ số thập phân
ALTER TABLE thanh_vien_hoi_dong 
MODIFY COLUMN TV_DIEM DECIMAL(5,2) DEFAULT NULL 
COMMENT 'Điểm đánh giá từ 0-100, với 2 chữ số thập phân';

-- Thêm constraint để đảm bảo điểm trong khoảng 0-100
ALTER TABLE thanh_vien_hoi_dong 
ADD CONSTRAINT chk_tv_diem_range 
CHECK (TV_DIEM IS NULL OR (TV_DIEM >= 0.0 AND TV_DIEM <= 100.0));

-- 2. CẬP NHẬT BẢNG BIÊN BẢN NGHIỆM THU
-- Thay đổi cột BB_TONGDIEM để lưu tổng điểm từ 0-100
ALTER TABLE bien_ban 
MODIFY COLUMN BB_TONGDIEM DECIMAL(5,2) DEFAULT NULL 
COMMENT 'Tổng điểm đánh giá từ 0-100, với 2 chữ số thập phân';

-- Thêm constraint để đảm bảo tổng điểm trong khoảng 0-100
ALTER TABLE bien_ban 
ADD CONSTRAINT chk_bb_tongdiem_range 
CHECK (BB_TONGDIEM IS NULL OR (BB_TONGDIEM >= 0.0 AND BB_TONGDIEM <= 100.0));

-- 3. KIỂM TRA VÀ CẬP NHẬT DỮ LIỆU CŨ
-- Tìm các bản ghi có điểm ngoài khoảng 0-100
SELECT 'Điểm thành viên ngoài khoảng 0-100:' as check_type, 
       TV_MATV, GV_MAGV, TV_DIEM, QD_SO
FROM thanh_vien_hoi_dong 
WHERE TV_DIEM IS NOT NULL 
  AND (TV_DIEM < 0 OR TV_DIEM > 100);

SELECT 'Tổng điểm biên bản ngoài khoảng 0-100:' as check_type,
       BB_SOBB, BB_TONGDIEM, QD_SO
FROM bien_ban 
WHERE BB_TONGDIEM IS NOT NULL 
  AND (BB_TONGDIEM < 0 OR BB_TONGDIEM > 100);

-- 4. CẬP NHẬT DỮ LIỆU SAI (NẾU CÓ)
-- Nếu có điểm từ thang 10, chuyển đổi sang thang 100
UPDATE thanh_vien_hoi_dong 
SET TV_DIEM = TV_DIEM * 10
WHERE TV_DIEM IS NOT NULL 
  AND TV_DIEM > 0 
  AND TV_DIEM <= 10;

UPDATE bien_ban 
SET BB_TONGDIEM = BB_TONGDIEM * 10
WHERE BB_TONGDIEM IS NOT NULL 
  AND BB_TONGDIEM > 0 
  AND BB_TONGDIEM <= 10;

-- 5. XÓA CÁC DỮ LIỆU KHÔNG HỢP LỆ (TÙY CHỌN)
-- Cẩn thận với lệnh này - chỉ chạy sau khi đã backup
-- SET @backup_done = 0;
-- 
-- UPDATE thanh_vien_hoi_dong 
-- SET TV_DIEM = NULL 
-- WHERE TV_DIEM IS NOT NULL 
--   AND (TV_DIEM < 0 OR TV_DIEM > 100)
--   AND @backup_done = 1;
-- 
-- UPDATE bien_ban 
-- SET BB_TONGDIEM = NULL 
-- WHERE BB_TONGDIEM IS NOT NULL 
--   AND (BB_TONGDIEM < 0 OR BB_TONGDIEM > 100)
--   AND @backup_done = 1;

-- 6. TẠO INDEX CHO HIỆU SUẤT (TÙY CHỌN)
-- Index cho việc tìm kiếm theo điểm
CREATE INDEX idx_thanh_vien_hoi_dong_diem 
ON thanh_vien_hoi_dong(TV_DIEM) 
WHERE TV_DIEM IS NOT NULL;

CREATE INDEX idx_bien_ban_tongdiem 
ON bien_ban(BB_TONGDIEM) 
WHERE BB_TONGDIEM IS NOT NULL;

-- 7. TẠO VIEW ĐỂ KIỂM TRA DỮ LIỆU
CREATE OR REPLACE VIEW v_score_summary AS
SELECT 
    'thanh_vien_hoi_dong' as table_name,
    COUNT(*) as total_records,
    COUNT(TV_DIEM) as scored_records,
    MIN(TV_DIEM) as min_score,
    MAX(TV_DIEM) as max_score,
    AVG(TV_DIEM) as avg_score,
    COUNT(CASE WHEN TV_DIEM < 0 OR TV_DIEM > 100 THEN 1 END) as invalid_scores
FROM thanh_vien_hoi_dong

UNION ALL

SELECT 
    'bien_ban' as table_name,
    COUNT(*) as total_records,
    COUNT(BB_TONGDIEM) as scored_records,
    MIN(BB_TONGDIEM) as min_score,
    MAX(BB_TONGDIEM) as max_score,
    AVG(BB_TONGDIEM) as avg_score,
    COUNT(CASE WHEN BB_TONGDIEM < 0 OR BB_TONGDIEM > 100 THEN 1 END) as invalid_scores
FROM bien_ban;

-- 8. TRIGGER ĐỂ VALIDATION TỰ ĐỘNG (TÙY CHỌN)
DELIMITER //

CREATE TRIGGER tr_validate_tv_diem_before_insert
BEFORE INSERT ON thanh_vien_hoi_dong
FOR EACH ROW
BEGIN
    IF NEW.TV_DIEM IS NOT NULL AND (NEW.TV_DIEM < 0 OR NEW.TV_DIEM > 100) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Điểm thành viên hội đồng phải từ 0 đến 100';
    END IF;
END//

CREATE TRIGGER tr_validate_tv_diem_before_update
BEFORE UPDATE ON thanh_vien_hoi_dong
FOR EACH ROW
BEGIN
    IF NEW.TV_DIEM IS NOT NULL AND (NEW.TV_DIEM < 0 OR NEW.TV_DIEM > 100) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Điểm thành viên hội đồng phải từ 0 đến 100';
    END IF;
END//

CREATE TRIGGER tr_validate_bb_tongdiem_before_insert
BEFORE INSERT ON bien_ban
FOR EACH ROW
BEGIN
    IF NEW.BB_TONGDIEM IS NOT NULL AND (NEW.BB_TONGDIEM < 0 OR NEW.BB_TONGDIEM > 100) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Tổng điểm biên bản phải từ 0 đến 100';
    END IF;
END//

CREATE TRIGGER tr_validate_bb_tongdiem_before_update
BEFORE UPDATE ON bien_ban
FOR EACH ROW
BEGIN
    IF NEW.BB_TONGDIEM IS NOT NULL AND (NEW.BB_TONGDIEM < 0 OR NEW.BB_TONGDIEM > 100) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Tổng điểm biên bản phải từ 0 đến 100';
    END IF;
END//

DELIMITER ;

-- 9. KIỂM TRA KẾT QUẢ SAU KHI CẬP NHẬT
SELECT * FROM v_score_summary;

-- 10. GHI CHÚ VÀ HƯỚNG DẪN
/*
HƯỚNG DẪN SỬ DỤNG:

1. BACKUP DỮ LIỆU TRƯỚC KHI CHẠY:
   mysqldump -u username -p database_name > backup_before_score_update.sql

2. CHẠY SCRIPT THEO THỨ TỰ:
   - Chạy từng phần một để kiểm tra
   - Kiểm tra kết quả sau mỗi bước
   - Đặc biệt cẩn thận với phần cập nhật dữ liệu cũ

3. KIỂM TRA SAU KHI CẬP NHẬT:
   - Chạy SELECT * FROM v_score_summary;
   - Kiểm tra ứng dụng web có hoạt động bình thường
   - Test việc nhập/sửa điểm mới

4. ROLLBACK NẾU CẦN:
   - Nếu có vấn đề, restore từ backup
   - Hoặc chạy ALTER TABLE để đổi lại cấu trúc cũ

5. CẬP NHẬT PHP CODE:
   - Đảm bảo validation trong PHP cũng check 0-100
   - Update UI để hiển thị đúng thang điểm
   - Test tất cả chức năng liên quan đến điểm

NOTES:
- DECIMAL(5,2) cho phép: 999.99 (đủ cho điểm 0-100 với 2 chữ số thập phân)
- Constraints đảm bảo data integrity ở database level
- Triggers cung cấp validation realtime
- View giúp monitoring dữ liệu dễ dàng
*/
