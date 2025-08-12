# Sửa lỗi hiển thị lưu vết file thuyết minh trong research admin

## Vấn đề
Trong trang view_project.php của research admin, bảng "Tất cả file thuyết minh" không hiển thị đúng lưu vết file thuyết minh của đề tài đó.

## Nguyên nhân
- Phần code chỉ tìm file trong thư mục `uploads/project_files/` dựa trên tên file
- Không lấy thông tin từ bảng `lich_su_thuyet_minh` để hiển thị đầy đủ lưu vết
- Thiếu thông tin chi tiết như lý do cập nhật, người tải, thời gian, trạng thái

## Giải pháp
Cập nhật logic hiển thị để:
1. Ưu tiên lấy dữ liệu từ bảng `lich_su_thuyet_minh`
2. Hiển thị đầy đủ thông tin lưu vết
3. Fallback về tìm file trong thư mục nếu chưa có lịch sử

## Các thay đổi đã thực hiện

### File: view/research/view_project.php

#### 1. Cải thiện phần "File thuyết minh hiện tại"
- Thêm thông tin ngày tạo đề tài
- Cải thiện xử lý đường dẫn file
- Thêm thuộc tính `download` cho link tải xuống

#### 2. Cải thiện phần "Tất cả file thuyết minh"
```php
// Trước: Chỉ tìm file trong thư mục uploads
$proposal_files = [];
$upload_dir = '../../uploads/project_files/';
// ... logic tìm file đơn giản

// Sau: Lấy lịch sử từ database + fallback
$proposal_history = [];
$hist_sql = "SELECT * FROM lich_su_thuyet_minh WHERE DT_MADT = ? ORDER BY NGAY_TAI DESC, ID DESC";
// ... logic lấy lịch sử đầy đủ
```

#### 3. Thông tin hiển thị được cải thiện
- **Tên file**: Hiển thị tên file chính xác
- **Lý do cập nhật**: Hiển thị lý do từ database
- **Thời gian**: Ngày giờ tải lên chính xác
- **Người tải**: Thông tin người thực hiện
- **Trạng thái**: Badge "Hiện tại" cho file đang sử dụng
- **Kích thước**: Thông tin kích thước file (nếu có)

#### 4. Logic fallback
- Nếu chưa có lịch sử nhưng có file hiện tại: Tạo bản ghi lịch sử từ thông tin đề tài
- Nếu vẫn chưa có lịch sử: Tìm file trong thư mục uploads
- Sắp xếp theo thời gian (mới nhất trước)

## Kết quả mong đợi
- Bảng "Tất cả file thuyết minh" hiển thị đầy đủ lưu vết
- Thông tin chi tiết về từng file (lý do, thời gian, người tải)
- Phân biệt rõ file hiện tại và file lịch sử
- Link tải xuống hoạt động chính xác
- Hiển thị thông tin kích thước file

## Lưu ý
- Cần đảm bảo bảng `lich_su_thuyet_minh` có dữ liệu khi sinh viên cập nhật file
- File trong thư mục uploads phải có quyền truy cập phù hợp
- Đường dẫn file phải được xử lý đúng cho cả đường dẫn tuyệt đối và tương đối




