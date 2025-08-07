-- Tạo cấu trúc database cho hệ thống đánh giá theo tiêu chí

-- Bảng tiêu chí đánh giá
CREATE TABLE IF NOT EXISTS tieu_chi_danh_gia (
    TC_MA VARCHAR(10) PRIMARY KEY,
    TC_TEN VARCHAR(255) NOT NULL,
    TC_MOTA TEXT,
    TC_DIEM_TOIDAI DECIMAL(5,2) DEFAULT 10.00,
    TC_THUTU INT DEFAULT 0,
    TC_TRANGTHAI ENUM('Hoạt động', 'Không hoạt động') DEFAULT 'Hoạt động',
    TC_NGAYTAO DATETIME DEFAULT CURRENT_TIMESTAMP,
    TC_NGAYCAPNHAT DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng chi tiết đánh giá theo tiêu chí của từng thành viên
CREATE TABLE IF NOT EXISTS chi_tiet_danh_gia_tieu_chi (
    CDGTC_MA INT AUTO_INCREMENT PRIMARY KEY,
    CDGTC_MAGV VARCHAR(20) NOT NULL, -- Mã giảng viên đánh giá
    CDGTC_MADT VARCHAR(20) NOT NULL, -- Mã đề tài
    CDGTC_MATC VARCHAR(10) NOT NULL, -- Mã tiêu chí
    CDGTC_DIEM DECIMAL(5,2), -- Điểm cho tiêu chí này
    CDGTC_NHANXET TEXT, -- Nhận xét cho tiêu chí
    CDGTC_NGAYTAO DATETIME DEFAULT CURRENT_TIMESTAMP,
    CDGTC_NGAYCAPNHAT DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (CDGTC_MATC) REFERENCES tieu_chi_danh_gia(TC_MA) ON DELETE CASCADE,
    UNIQUE KEY unique_evaluation (CDGTC_MAGV, CDGTC_MADT, CDGTC_MATC)
);

-- Insert dữ liệu mẫu cho tiêu chí đánh giá
INSERT INTO tieu_chi_danh_gia (TC_MA, TC_TEN, TC_MOTA, TC_DIEM_TOIDAI, TC_THUTU) VALUES 
('TC001', 'Tính mới và tính sáng tạo của đề tài', 'Đánh giá mức độ mới mẻ, sáng tạo và tính khả thi của đề tài nghiên cứu', 15.00, 1),
('TC002', 'Phương pháp nghiên cứu', 'Đánh giá tính phù hợp và hiệu quả của phương pháp nghiên cứu được áp dụng', 15.00, 2),
('TC003', 'Kết quả nghiên cứu', 'Đánh giá chất lượng và tính đầy đủ của kết quả nghiên cứu đạt được', 25.00, 3),
('TC004', 'Tính ứng dụng thực tiễn', 'Đánh giá khả năng ứng dụng thực tiễn và tác động của kết quả nghiên cứu', 20.00, 4),
('TC005', 'Chất lượng báo cáo và thuyết trình', 'Đánh giá chất lượng của báo cáo nghiên cứu và kỹ năng thuyết trình', 15.00, 5),
('TC006', 'Thái độ và tinh thần nghiên cứu', 'Đánh giá thái độ nghiên cứu khoa học và tinh thần học tập của sinh viên', 10.00, 6);

-- Cập nhật bảng thanh_vien_hoi_dong để lưu tổng điểm từ các tiêu chí
ALTER TABLE thanh_vien_hoi_dong 
ADD COLUMN TV_DIEM_CHITIET JSON NULL COMMENT 'Chi tiết điểm các tiêu chí (JSON format)' AFTER TV_DIEM,
ADD COLUMN TV_DANHGIA_HOANTAT BOOLEAN DEFAULT FALSE COMMENT 'Đã hoàn tất đánh giá tất cả tiêu chí' AFTER TV_NHANXET;

-- Index để tăng hiệu suất
CREATE INDEX idx_chi_tiet_danh_gia_member ON chi_tiet_danh_gia_tieu_chi(CDGTC_MAGV, CDGTC_MADT);
CREATE INDEX idx_chi_tiet_danh_gia_project ON chi_tiet_danh_gia_tieu_chi(CDGTC_MADT);
CREATE INDEX idx_tieu_chi_thutu ON tieu_chi_danh_gia(TC_THUTU);
