# Hướng dẫn sử dụng tính năng đánh giá thành viên hội đồng

## Tổng quan
Hệ thống đã được bổ sung tính năng cho phép chủ nhiệm đề tài nhập điểm và upload file đánh giá cho từng thành viên hội đồng nghiệm thu.

## Các tính năng mới

### 1. Nhập điểm đánh giá
- **Vị trí**: Tab "Đánh giá" > Bảng "Thành viên hội đồng nghiệm thu" > Cột "Hành động" > Nút "Điểm"
- **Chức năng**: 
  - Nhập điểm từ 0 đến 100 (cho phép số thập phân)
  - Thêm nhận xét tùy chọn
  - Lưu ngày cập nhật điểm
- **Quyền hạn**: Chỉ chủ nhiệm đề tài và khi đề tài đang ở trạng thái cho phép chỉnh sửa

### 2. Upload file đánh giá
- **Vị trí**: Tab "Đánh giá" > Bảng "Thành viên hội đồng nghiệm thu" > Cột "Hành động" > Nút "File"
- **Chức năng**:
  - Upload file đánh giá cho từng thành viên cụ thể
  - Hỗ trợ định dạng: PDF, DOC, DOCX, TXT, XLS, XLSX
  - Kích thước tối đa: 10MB
  - Thêm mô tả cho file
- **Lưu trữ**: File được lưu vào bảng `file_dinh_kem` với loại `member_evaluation`

### 3. Hiển thị trạng thái file
- **Vị trí**: Cột "File đánh giá" trong bảng thành viên hội đồng
- **Chức năng**:
  - Hiển thị số lượng file đã upload
  - Dropdown menu để tải xuống từng file
  - Trạng thái "Chưa có" nếu chưa upload file nào

## Cấu trúc database mới

### Bảng `file_dinh_kem`
- Thêm cột `FDK_MEMBER_ID`: Mã thành viên hội đồng (cho file đánh giá)
- Loại file mới: `member_evaluation`

### Bảng `thanh_vien_hoi_dong`  
- Thêm cột `TV_NHANXET`: Nhận xét đánh giá
- Thêm cột `TV_NGAYCAPDIEM`: Ngày cập nhật điểm

## File xử lý backend

### `update_member_score.php`
- Xử lý cập nhật điểm và nhận xét cho thành viên hội đồng
- Validation điểm từ 0-100
- Kiểm tra quyền truy cập đề tài
- Trả về JSON response

### `upload_member_evaluation.php`
- Xử lý upload file đánh giá cho thành viên
- Validation loại file và kích thước
- Tạo tên file unique để tránh trùng lặp
- Lưu metadata vào database

## Cài đặt

1. **Chạy script cập nhật database**:
   ```sql
   -- Chạy file database_update_member_evaluation.sql
   ```

2. **Tạo thư mục upload**:
   ```
   mkdir /uploads/member_evaluations/
   chmod 755 /uploads/member_evaluations/
   ```

3. **Cấu hình quyền**:
   - Đảm bảo PHP có quyền ghi vào thư mục uploads
   - Kiểm tra cấu hình upload_max_filesize và post_max_size

## Lưu ý kỹ thuật

- File upload được lưu với tên unique để tránh conflict
- Sử dụng AJAX để cập nhật không cần reload trang
- Responsive design cho mobile
- Validation cả client-side và server-side
- Error handling và user feedback

## Bảo mật

- Kiểm tra quyền truy cập đề tài
- Validation loại file upload
- Sanitize input data
- Protected upload directory
- Session-based authentication
