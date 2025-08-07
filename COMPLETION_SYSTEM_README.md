# Hệ thống Hoàn thành Đề tài Tự động

## Tổng quan
Hệ thống tự động kiểm tra và cập nhật trạng thái hoàn thành của đề tài dựa trên 4 yêu cầu chính:

1. **Quyết định nghiệm thu** - Phải có quyết định nghiệm thu cho đề tài
2. **Điểm đánh giá thành viên** - Tất cả thành viên hội đồng phải được đánh giá và có điểm
3. **File đánh giá thành viên** - Tất cả thành viên phải có file đánh giá
4. **Báo cáo tổng kết** - Đề tài phải có báo cáo tổng kết

## Cách thức hoạt động

### 1. Kiểm tra Tự động
- Hệ thống tự động kiểm tra khi:
  - Lưu điểm đánh giá chi tiết cho thành viên
  - Upload file đánh giá cho thành viên  
  - Tạo/cập nhật báo cáo tổng kết

### 2. Hiển thị Trạng thái
- Trong tab "Đánh giá" của trang xem đề tài
- Hiển thị progress bar và trạng thái từng yêu cầu
- Có nút "Kiểm tra" để refresh trạng thái

### 3. Thông báo Tự động
- Hiển thị thông báo toast khi đề tài hoàn thành
- Tự động refresh trang để cập nhật giao diện
- Hiển thị trạng thái completion trong response API

## Files Liên quan

### Backend Files
- `check_project_completion.php` - Logic chính kiểm tra hoàn thành
- `save_detailed_evaluation.php` - Lưu điểm + kiểm tra hoàn thành  
- `upload_member_evaluation_file.php` - Upload file + kiểm tra hoàn thành
- `api/check_completion_status.php` - API endpoint cho AJAX

### Frontend Files  
- `view/student/view_project.php` - Giao diện hiển thị trạng thái
- CSS cho completion status section
- JavaScript xử lý AJAX và thông báo

### Test Files
- `test_completion_system.php` - Test và debug hệ thống

## API Endpoints

### POST /api/check_completion_status.php
**Parameters:**
- `project_id` (string) - Mã đề tài cần kiểm tra

**Response:**
```json
{
  "success": true,
  "data": {
    "is_complete": false,
    "completion_percentage": 75,
    "requirements": {
      "has_decision": true,
      "has_member_scores": true, 
      "has_evaluation_files": false,
      "has_final_report": true
    },
    "auto_completed": false
  }
}
```

## Database Schema

### Bảng liên quan:
- `de_tai` - Lưu trạng thái đề tài (DT_TRANGTHAI)
- `quyet_dinh_nghiem_thu` - Quyết định nghiệm thu
- `thanh_vien_hoi_dong` - Điểm đánh giá thành viên (TV_DIEM)
- `member_evaluation_files` - File đánh giá thành viên
- `bao_cao_tong_ket` - Báo cáo tổng kết

## Cách sử dụng

### 1. Cho Admin/Giảng viên:
1. Tạo quyết định nghiệm thu cho đề tài
2. Thêm thành viên hội đồng
3. Hướng dẫn thành viên đánh giá đề tài
4. Theo dõi tiến độ hoàn thành trong tab "Đánh giá"

### 2. Cho Thành viên hội đồng:
1. Truy cập trang xem đề tài được phân công
2. Vào tab "Đánh giá" 
3. Nhập điểm cho từng tiêu chí đánh giá
4. Upload file đánh giá của mình
5. Hệ thống sẽ tự động kiểm tra và thông báo khi đề tài hoàn thành

### 3. Cho Sinh viên:
1. Theo dõi tiến độ đánh giá trong tab "Đánh giá"
2. Xem trạng thái hoàn thành real-time
3. Nhận thông báo khi đề tài hoàn thành

## Troubleshooting

### Lỗi thường gặp:
1. **"Chưa có quyết định nghiệm thu"** - Cần tạo quyết định trước
2. **"Thiếu điểm thành viên"** - Chưa đủ thành viên đánh giá
3. **"Thiếu file đánh giá"** - Chưa đủ thành viên upload file

### Debug:
- Sử dụng `test_completion_system.php` để kiểm tra chi tiết
- Check log trong browser console
- Kiểm tra response của API calls

## Cấu hình

### Trạng thái đề tài được cập nhật:
- Từ trạng thái hiện tại → "Đã hoàn thành"
- Chỉ cập nhật khi đủ 4/4 yêu cầu
- Sử dụng database transaction để đảm bảo tính nhất quán

### Thông báo:
- Toast notification với thời gian hiển thị 10 giây
- Auto refresh completion status
- Reload trang sau 2 giây nếu có thay đổi

## Security Notes
- Kiểm tra session và quyền truy cập
- Validate input parameters
- Sử dụng prepared statements
- Error handling và logging
