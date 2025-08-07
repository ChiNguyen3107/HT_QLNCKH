-- ==================================================
-- BƯỚC 4: THÊM VALIDATION VÀ TRIGGERS
-- ==================================================

SELECT 'BƯỚC 4: Thêm validation và triggers' as step;

-- 4.1: Tạo triggers validation cho TV_DIEM
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

-- 4.2: Tạo triggers validation cho BB_TONGDIEM
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

SELECT 'BƯỚC 4 HOÀN THÀNH: Triggers validation đã được tạo' as result;

-- 4.3: Kiểm tra triggers đã được tạo
SHOW TRIGGERS LIKE 'thanh_vien_hoi_dong';
SHOW TRIGGERS LIKE 'bien_ban';

-- DỪNG TẠI ĐÂY - Kiểm tra kết quả trước khi tiếp tục
-- Sau khi xác nhận OK, chạy file step5_create_monitoring.sql
