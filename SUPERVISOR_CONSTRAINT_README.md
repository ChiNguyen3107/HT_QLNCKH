# Ràng Buộc Giảng Viên Hướng Dẫn - Hội Đồng Nghiệm Thu

## Tổng Quan

Đã thêm ràng buộc để đảm bảo giảng viên hướng dẫn không thể tham gia hội đồng nghiệm thu của đề tài mình hướng dẫn, nhằm đảm bảo tính khách quan trong quá trình đánh giá.

## Các Thay Đổi Đã Thực Hiện

### 1. API Mới
- **File**: `api/get_project_supervisor.php`
- **Chức năng**: Lấy thông tin giảng viên hướng dẫn của đề tài
- **Response**: JSON chứa mã và tên giảng viên hướng dẫn

### 2. Frontend (JavaScript)
- **File**: `assets/js/council_members.js`
- **Thay đổi chính**:
  - Thêm biến `projectSupervisor` để lưu thông tin giảng viên hướng dẫn
  - Thêm hàm `loadProjectSupervisor()` để tải thông tin giảng viên hướng dẫn
  - Cập nhật hàm `selectTeacher()` để kiểm tra ràng buộc
  - Cập nhật hàm `renderTeachersList()` để hiển thị giảng viên hướng dẫn với badge cảnh báo
  - Thêm thông báo giải thích trong modal chọn thành viên

### 3. Backend Validation
Cập nhật các file xử lý lưu thành viên hội đồng:

#### `view/student/save_council_members.php`
- Thêm kiểm tra giảng viên hướng dẫn trước khi lưu
- Ném exception nếu phát hiện giảng viên hướng dẫn trong danh sách

#### `view/student/update_council_members.php`
- Thêm validation tương tự
- Log thông tin giảng viên hướng dẫn để debug

#### `view/student/update_report_info.php`
- Thêm validation tương tự
- Đảm bảo tính nhất quán trong toàn bộ hệ thống

## Cách Hoạt Động

### 1. Khi Tải Trang
- Hệ thống tự động tải thông tin giảng viên hướng dẫn của đề tài
- Lưu thông tin vào biến `projectSupervisor`

### 2. Khi Chọn Thành Viên Hội Đồng
- Hiển thị danh sách giảng viên với badge đánh dấu giảng viên hướng dẫn
- Nút "Chọn" bị vô hiệu hóa cho giảng viên hướng dẫn
- Hiển thị thông báo cảnh báo cho giảng viên hướng dẫn

### 3. Khi Lưu Thành Viên Hội Đồng
- Backend kiểm tra lại ràng buộc trước khi lưu
- Nếu phát hiện giảng viên hướng dẫn, trả về lỗi
- Đảm bảo tính toàn vẹn dữ liệu

## Giao Diện Người Dùng

### Modal Chọn Thành Viên
- Thêm thông báo giải thích về ràng buộc
- Giảng viên hướng dẫn được đánh dấu với badge màu vàng
- Nút "Giảng viên hướng dẫn" bị vô hiệu hóa
- Hiển thị thông báo cảnh báo dưới tên giảng viên

### Thông Báo Lỗi
- Frontend: Hiển thị thông báo cảnh báo khi cố gắng chọn giảng viên hướng dẫn
- Backend: Trả về lỗi với thông điệp rõ ràng

## Lợi Ích

1. **Tính Khách Quan**: Đảm bảo giảng viên hướng dẫn không thể ảnh hưởng đến kết quả đánh giá
2. **Tuân Thủ Quy Định**: Phù hợp với quy định về hội đồng nghiệm thu
3. **Trải Nghiệm Người Dùng**: Thông báo rõ ràng và giao diện trực quan
4. **Bảo Mật Dữ Liệu**: Validation ở cả frontend và backend

## Kiểm Tra

### Test Cases
1. Tải trang với đề tài có giảng viên hướng dẫn
2. Thử chọn giảng viên hướng dẫn làm thành viên hội đồng
3. Kiểm tra thông báo lỗi
4. Kiểm tra giao diện hiển thị
5. Test validation backend

### Debug
- Kiểm tra console log để xem thông tin giảng viên hướng dẫn
- Kiểm tra network tab để xem API calls
- Kiểm tra error logs của backend

## Tương Lai

Có thể mở rộng tính năng này để:
- Thêm cấu hình cho phép admin bỏ qua ràng buộc trong trường hợp đặc biệt
- Thêm báo cáo về các trường hợp vi phạm ràng buộc
- Tích hợp với hệ thống thông báo để cảnh báo admin






