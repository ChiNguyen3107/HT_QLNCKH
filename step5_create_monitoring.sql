-- ==================================================
-- BƯỚC 5: TẠO VIEW MONITORING VÀ INDEX
-- ==================================================

SELECT 'BƯỚC 5: Tạo view monitoring và index' as step;

-- 5.1: Tạo view để theo dõi điểm số
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

-- 5.2: Tạo index cho hiệu suất (nếu chưa có)
CREATE INDEX IF NOT EXISTS idx_thanh_vien_hoi_dong_diem 
ON thanh_vien_hoi_dong(TV_DIEM);

CREATE INDEX IF NOT EXISTS idx_bien_ban_tongdiem_new 
ON bien_ban(BB_TONGDIEM);

SELECT 'BƯỚC 5 HOÀN THÀNH: View và index đã được tạo' as result;

-- 5.3: Hiển thị thống kê cuối cùng
SELECT 'THỐNG KÊ CUỐI CÙNG SAU CẬP NHẬT:' as final_summary;
SELECT * FROM v_score_summary;

-- 5.4: Test validation
SELECT 'TEST VALIDATION:' as test_info;
-- Test này sẽ thất bại (đó là điều mong muốn)
-- INSERT INTO thanh_vien_hoi_dong (QD_SO, GV_MAGV, TC_MATC, TV_VAITRO, TV_DIEM) 
-- VALUES ('TEST1', 'GV000001', 'TC001', 'Thành viên', 150.0);

SELECT 'CẬP NHẬT DATABASE HOÀN THÀNH!' as completion_status;
SELECT 'Hệ thống giờ đây hỗ trợ điểm từ 0-100 với validation tự động' as final_note;
