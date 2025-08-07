-- SCRIPT KIỂM TRA CẤU TRÚC HIỆN TẠI
-- Chạy script này TRƯỚC KHI thực hiện cập nhật

-- 1. Kiểm tra cấu trúc cột điểm hiện tại
SHOW CREATE TABLE thanh_vien_hoi_dong;
SHOW CREATE TABLE bien_ban;

-- 2. Kiểm tra kiểu dữ liệu của cột điểm
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    NUMERIC_PRECISION, 
    NUMERIC_SCALE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'ql_nckh' 
  AND TABLE_NAME = 'thanh_vien_hoi_dong' 
  AND COLUMN_NAME = 'TV_DIEM';

SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    NUMERIC_PRECISION, 
    NUMERIC_SCALE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'ql_nckh' 
  AND TABLE_NAME = 'bien_ban' 
  AND COLUMN_NAME = 'BB_TONGDIEM';

-- 3. Kiểm tra dữ liệu điểm hiện có
SELECT 'Thống kê điểm thành viên hội đồng:' as info;
SELECT 
    COUNT(*) as total_records,
    COUNT(TV_DIEM) as has_score,
    MIN(TV_DIEM) as min_score,
    MAX(TV_DIEM) as max_score,
    AVG(TV_DIEM) as avg_score,
    COUNT(CASE WHEN TV_DIEM > 100 THEN 1 END) as scores_over_100,
    COUNT(CASE WHEN TV_DIEM < 0 THEN 1 END) as negative_scores
FROM thanh_vien_hoi_dong;

SELECT 'Thống kê tổng điểm biên bản:' as info;
SELECT 
    COUNT(*) as total_records,
    COUNT(BB_TONGDIEM) as has_score,
    MIN(BB_TONGDIEM) as min_score,
    MAX(BB_TONGDIEM) as max_score,
    AVG(BB_TONGDIEM) as avg_score,
    COUNT(CASE WHEN BB_TONGDIEM > 100 THEN 1 END) as scores_over_100,
    COUNT(CASE WHEN BB_TONGDIEM < 0 THEN 1 END) as negative_scores
FROM bien_ban;

-- 4. Hiển thị một số mẫu dữ liệu
SELECT 'Mẫu dữ liệu điểm thành viên (10 bản ghi đầu):' as info;
SELECT TV_MATV, GV_MAGV, TV_DIEM, QD_SO 
FROM thanh_vien_hoi_dong 
WHERE TV_DIEM IS NOT NULL 
LIMIT 10;

SELECT 'Mẫu dữ liệu tổng điểm biên bản (10 bản ghi đầu):' as info;
SELECT BB_SOBB, BB_TONGDIEM, QD_SO 
FROM bien_ban 
WHERE BB_TONGDIEM IS NOT NULL 
LIMIT 10;
