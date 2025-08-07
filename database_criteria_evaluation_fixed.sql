-- Tạo bảng tiêu chí đánh giá
CREATE TABLE IF NOT EXISTS tieu_chi_danh_gia (
    ma_tieu_chi VARCHAR(10) PRIMARY KEY,
    ten_tieu_chi VARCHAR(200) NOT NULL,
    mo_ta TEXT,
    diem_toi_da DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    thu_tu INT DEFAULT 1,
    trang_thai ENUM('Hoạt động', 'Không hoạt động') DEFAULT 'Hoạt động',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Thêm dữ liệu mẫu tiêu chí đánh giá
INSERT INTO tieu_chi_danh_gia (ma_tieu_chi, ten_tieu_chi, mo_ta, diem_toi_da, thu_tu) VALUES
('TC001', 'Tính sáng tạo, mới lạ', 'Đánh giá mức độ sáng tạo và tính mới lạ của đề tài', 15.00, 1),
('TC002', 'Phương pháp nghiên cứu', 'Đánh giá tính khoa học và hiệu quả của phương pháp nghiên cứu', 15.00, 2),
('TC003', 'Kết quả nghiên cứu', 'Đánh giá chất lượng và tính khả thi của kết quả nghiên cứu', 25.00, 3),
('TC004', 'Khả năng ứng dụng thực tiễn', 'Đánh giá tính ứng dụng và giá trị thực tiễn của nghiên cứu', 20.00, 4),
('TC005', 'Chất lượng báo cáo', 'Đánh giá cách trình bày, báo cáo và tài liệu nghiên cứu', 15.00, 5),
('TC006', 'Thái độ và tinh thần', 'Đánh giá thái độ làm việc và tinh thần nghiên cứu của sinh viên', 10.00, 6)
ON DUPLICATE KEY UPDATE
ten_tieu_chi = VALUES(ten_tieu_chi),
mo_ta = VALUES(mo_ta),
diem_toi_da = VALUES(diem_toi_da),
thu_tu = VALUES(thu_tu);

-- Tạo bảng chi tiết đánh giá theo tiêu chí
CREATE TABLE IF NOT EXISTS chi_tiet_danh_gia_tieu_chi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qd_so VARCHAR(11) NOT NULL,
    gv_magv CHAR(8) NOT NULL,
    tc_matc CHAR(5) NOT NULL,
    ma_tieu_chi VARCHAR(10) NOT NULL,
    diem_so DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    nhan_xet TEXT,
    ngay_danh_gia DATETIME DEFAULT CURRENT_TIMESTAMP,
    hoan_thanh BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_evaluation (qd_so, gv_magv, tc_matc, ma_tieu_chi),
    INDEX idx_member (qd_so, gv_magv, tc_matc),
    INDEX idx_criteria (ma_tieu_chi),
    FOREIGN KEY (ma_tieu_chi) REFERENCES tieu_chi_danh_gia(ma_tieu_chi) ON DELETE CASCADE,
    FOREIGN KEY (qd_so, gv_magv, tc_matc) REFERENCES thanh_vien_hoi_dong(QD_SO, GV_MAGV, TC_MATC) ON DELETE CASCADE
);

-- Thêm cột chi tiết tiêu chí vào bảng thanh_vien_hoi_dong nếu chưa có
ALTER TABLE thanh_vien_hoi_dong 
ADD COLUMN IF NOT EXISTS TV_CHITIET_TIEUCHI JSON COMMENT 'Chi tiết điểm theo từng tiêu chí (JSON)',
ADD COLUMN IF NOT EXISTS TV_HOAN_THANH BOOLEAN DEFAULT FALSE COMMENT 'Trạng thái hoàn thành đánh giá';

-- Tạo view để xem tổng hợp điểm theo tiêu chí
CREATE OR REPLACE VIEW v_tong_hop_danh_gia_tieu_chi AS
SELECT 
    thd.QD_SO,
    thd.GV_MAGV,
    thd.TC_MATC,
    thd.TV_HOTEN,
    thd.TV_VAITRO,
    thd.TV_DIEM as diem_tong,
    COUNT(ct.id) as so_tieu_chi_da_danh_gia,
    SUM(ct.diem_so) as tong_diem_tieu_chi,
    thd.TV_HOAN_THANH as hoan_thanh,
    thd.TV_NGAYDANHGIA as ngay_cap_nhat
FROM thanh_vien_hoi_dong thd
LEFT JOIN chi_tiet_danh_gia_tieu_chi ct ON 
    thd.QD_SO = ct.qd_so AND 
    thd.GV_MAGV = ct.gv_magv AND 
    thd.TC_MATC = ct.tc_matc
GROUP BY thd.QD_SO, thd.GV_MAGV, thd.TC_MATC;

-- Tạo trigger để cập nhật điểm tổng khi có thay đổi chi tiết
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS tr_update_total_score_after_criteria_insert
AFTER INSERT ON chi_tiet_danh_gia_tieu_chi
FOR EACH ROW
BEGIN
    UPDATE thanh_vien_hoi_dong 
    SET 
        TV_DIEM = (
            SELECT COALESCE(SUM(diem_so), 0) 
            FROM chi_tiet_danh_gia_tieu_chi 
            WHERE qd_so = NEW.qd_so 
            AND gv_magv = NEW.gv_magv 
            AND tc_matc = NEW.tc_matc
        ),
        TV_NGAYDANHGIA = NOW()
    WHERE QD_SO = NEW.qd_so 
    AND GV_MAGV = NEW.gv_magv 
    AND TC_MATC = NEW.tc_matc;
END$$

CREATE TRIGGER IF NOT EXISTS tr_update_total_score_after_criteria_update
AFTER UPDATE ON chi_tiet_danh_gia_tieu_chi
FOR EACH ROW
BEGIN
    UPDATE thanh_vien_hoi_dong 
    SET 
        TV_DIEM = (
            SELECT COALESCE(SUM(diem_so), 0) 
            FROM chi_tiet_danh_gia_tieu_chi 
            WHERE qd_so = NEW.qd_so 
            AND gv_magv = NEW.gv_magv 
            AND tc_matc = NEW.tc_matc
        ),
        TV_NGAYDANHGIA = NOW()
    WHERE QD_SO = NEW.qd_so 
    AND GV_MAGV = NEW.gv_magv 
    AND TC_MATC = NEW.tc_matc;
END$$

DELIMITER ;
