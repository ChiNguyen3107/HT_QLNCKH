-- ==================================================
-- SCRIPT CẬP NHẬT STEP-BY-STEP CHO ĐIỂM SỐ 0-100
-- ==================================================
-- Chạy từng bước một và kiểm tra kết quả

-- BƯỚC 1: Kiểm tra dữ liệu trước khi cập nhật
SELECT 'BƯỚC 1: Kiểm tra dữ liệu hiện tại' as step;

SELECT 'Điểm thành viên hiện tại:' as info;
SELECT QD_SO, GV_MAGV, TV_DIEM 
FROM thanh_vien_hoi_dong 
WHERE TV_DIEM IS NOT NULL;

SELECT 'Tổng điểm biên bản hiện tại:' as info;
SELECT BB_SOBB, QD_SO, BB_TONGDIEM 
FROM bien_ban 
WHERE BB_TONGDIEM IS NOT NULL;

-- DỪNG TẠI ĐÂY - Kiểm tra kết quả trước khi tiếp tục
-- Sau khi xem kết quả, chạy file step2_update_structure.sql
