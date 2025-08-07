-- ==================================================
-- BƯỚC 3: CHUYỂN ĐỔI DỮ LIỆU TỪ THANG 10 SANG THANG 100
-- ==================================================

SELECT 'BƯỚC 3: Chuyển đổi dữ liệu từ thang 10 sang thang 100' as step;

-- 3.1: Hiển thị dữ liệu trước khi chuyển đổi
SELECT 'Dữ liệu TRƯỚC khi chuyển đổi:' as info;
SELECT 'TV_DIEM (thành viên):' as table_name;
SELECT QD_SO, GV_MAGV, TV_DIEM 
FROM thanh_vien_hoi_dong 
WHERE TV_DIEM IS NOT NULL;

SELECT 'BB_TONGDIEM (biên bản):' as table_name;
SELECT BB_SOBB, QD_SO, BB_TONGDIEM 
FROM bien_ban 
WHERE BB_TONGDIEM IS NOT NULL;

-- 3.2: Chuyển đổi điểm thành viên từ thang 10 sang thang 100
UPDATE thanh_vien_hoi_dong 
SET TV_DIEM = TV_DIEM * 10
WHERE TV_DIEM IS NOT NULL 
  AND TV_DIEM <= 10;

-- 3.3: Chuyển đổi tổng điểm biên bản từ thang 10 sang thang 100
UPDATE bien_ban 
SET BB_TONGDIEM = BB_TONGDIEM * 10
WHERE BB_TONGDIEM IS NOT NULL 
  AND BB_TONGDIEM <= 10;

-- 3.4: Hiển thị dữ liệu sau khi chuyển đổi
SELECT 'Dữ liệu SAU khi chuyển đổi:' as info;
SELECT 'TV_DIEM (thành viên):' as table_name;
SELECT QD_SO, GV_MAGV, TV_DIEM 
FROM thanh_vien_hoi_dong 
WHERE TV_DIEM IS NOT NULL;

SELECT 'BB_TONGDIEM (biên bản):' as table_name;
SELECT BB_SOBB, QD_SO, BB_TONGDIEM 
FROM bien_ban 
WHERE BB_TONGDIEM IS NOT NULL;

SELECT 'BƯỚC 3 HOÀN THÀNH: Dữ liệu đã được chuyển đổi sang thang 100' as result;

-- DỪNG TẠI ĐÂY - Kiểm tra kết quả trước khi tiếp tục
-- Sau khi xác nhận OK, chạy file step4_add_validation.sql
