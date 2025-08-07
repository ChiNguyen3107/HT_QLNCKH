-- ==================================================
-- BƯỚC 2: CẬP NHẬT CẤU TRÚC BẢNG
-- ==================================================

SELECT 'BƯỚC 2: Bắt đầu cập nhật cấu trúc bảng' as step;

-- 2.1: Xóa constraint cũ trước khi sửa
ALTER TABLE bien_ban DROP CONSTRAINT chk_bien_ban_tongdiem;

-- 2.2: Cập nhật cấu trúc cột TV_DIEM (từ 0-10 lên 0-100)
ALTER TABLE thanh_vien_hoi_dong 
MODIFY COLUMN TV_DIEM DECIMAL(5,2) DEFAULT NULL 
COMMENT 'Điểm đánh giá từ 0-100, với 2 chữ số thập phân';

-- 2.3: Cập nhật cấu trúc cột BB_TONGDIEM (từ INT sang DECIMAL)
ALTER TABLE bien_ban 
MODIFY COLUMN BB_TONGDIEM DECIMAL(5,2) DEFAULT NULL 
COMMENT 'Tổng điểm đánh giá từ 0-100, với 2 chữ số thập phân';

-- 2.4: Thêm constraint mới cho TV_DIEM
ALTER TABLE thanh_vien_hoi_dong 
ADD CONSTRAINT chk_tv_diem_range 
CHECK (TV_DIEM IS NULL OR (TV_DIEM >= 0.0 AND TV_DIEM <= 100.0));

-- 2.5: Thêm constraint mới cho BB_TONGDIEM
ALTER TABLE bien_ban 
ADD CONSTRAINT chk_bb_tongdiem_range 
CHECK (BB_TONGDIEM IS NULL OR (BB_TONGDIEM >= 0.0 AND BB_TONGDIEM <= 100.0));

SELECT 'BƯỚC 2 HOÀN THÀNH: Cấu trúc đã được cập nhật' as result;

-- Kiểm tra cấu trúc mới
SHOW CREATE TABLE thanh_vien_hoi_dong;
SHOW CREATE TABLE bien_ban;

-- DỪNG TẠI ĐÂY - Kiểm tra kết quả trước khi tiếp tục
-- Sau khi xác nhận OK, chạy file step3_convert_data.sql
