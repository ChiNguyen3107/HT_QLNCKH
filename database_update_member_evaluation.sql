-- Cập nhật bảng file_dinh_kem để hỗ trợ file đánh giá từ thành viên hội đồng
ALTER TABLE file_dinh_kem 
ADD COLUMN FDK_MEMBER_ID VARCHAR(20) NULL COMMENT 'Mã thành viên hội đồng (nếu là file đánh giá)' AFTER FDK_MADT;

-- Cập nhật bảng thanh_vien_hoi_dong để thêm thông tin đánh giá
ALTER TABLE thanh_vien_hoi_dong 
ADD COLUMN TV_NHANXET TEXT NULL COMMENT 'Nhận xét đánh giá' AFTER TV_DIEM,
ADD COLUMN TV_NGAYCAPDIEM DATETIME NULL COMMENT 'Ngày cập nhật điểm' AFTER TV_NHANXET;
