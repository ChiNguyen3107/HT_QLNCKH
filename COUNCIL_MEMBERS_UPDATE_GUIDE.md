# Hướng dẫn cập nhật hệ thống lưu thành viên hội đồng nghiệm thu

## Tổng quan
Hệ thống đã được cập nhật để có thể lưu trữ và quản lý thành viên hội đồng nghiệm thu một cách chính xác. Các thay đổi bao gồm:

## 1. Thay đổi cơ sở dữ liệu

### Bảng `quyet_dinh_nghiem_thu`
- **QD_NOIDUNG** (TEXT): Nội dung chi tiết của quyết định
- **HD_THANHVIEN** (TEXT): Danh sách thành viên hội đồng dạng JSON

### Bảng `thanh_vien_hoi_dong`
- **TV_HOTEN** (VARCHAR(100)): Họ tên đầy đủ của thành viên
- **TV_DIEM** (DECIMAL(4,2)): Điểm đánh giá (0-10)
- **TV_DANHGIA** (TEXT): Nhận xét đánh giá
- Thêm index: `idx_qd_so`, `idx_gv_magv`

### View mới
- **view_council_members**: Kết hợp thông tin từ các bảng liên quan

## 2. Files đã được tạo/cập nhật

### Scripts cơ sở dữ liệu
- `fix_council_members_structure.sql`: Script SQL cập nhật cấu trúc
- `run_database_update.php`: Script PHP chạy cập nhật tự động

### PHP Backend
- `save_council_members.php`: API xử lý lưu thành viên hội đồng
- `update_report_info.php`: Cập nhật để xử lý thành viên hội đồng

### JavaScript Frontend
- `assets/js/council_members.js`: UI quản lý thành viên hội đồng

### UI Updates
- `view_project.php`: Thêm JavaScript và form xử lý thành viên

## 3. Cách sử dụng

### Bước 1: Chạy cập nhật cơ sở dữ liệu
1. Truy cập: `http://localhost/NLNganh/run_database_update.php`
2. Kiểm tra kết quả cập nhật
3. Xác nhận tất cả các bảng đã được cập nhật thành công

### Bước 2: Sử dụng tính năng mới
1. Vào trang chi tiết đề tài
2. Chuyển đến tab "Biên bản nghiệm thu"
3. Điền thông tin nghiệm thu (ngày, xếp loại, điểm)
4. Nhấn "Thêm thành viên hội đồng"
5. Chọn giảng viên từ danh sách
6. Chỉ định vai trò (Chủ tịch, Thành viên, Thư ký)
7. Lưu thông tin

## 4. Tính năng chính

### Quản lý thành viên hội đồng
- ✅ Chọn giảng viên từ danh sách có sẵn
- ✅ Phân vai trò: Chủ tịch, Thành viên, Thư ký
- ✅ Kiểm tra trùng lặp vai trò (chỉ 1 chủ tịch, 1 thư ký)
- ✅ Tìm kiếm giảng viên theo tên/mã
- ✅ Xóa/thêm thành viên dễ dàng

### Lưu trữ dữ liệu
- ✅ Lưu dạng JSON trong trường HD_THANHVIEN
- ✅ Lưu chi tiết trong bảng thanh_vien_hoi_dong
- ✅ Đảm bảo tính nhất quán dữ liệu

### Hiển thị thông tin
- ✅ Hiển thị danh sách thành viên đã chọn
- ✅ Phân biệt vai trò bằng màu sắc
- ✅ Thông tin chi tiết của từng thành viên

## 5. API Endpoints

### GET `/api/get_teachers.php`
Lấy danh sách giảng viên để chọn làm thành viên hội đồng

### POST `/view/student/save_council_members.php`
Lưu danh sách thành viên hội đồng
- `project_id`: Mã đề tài
- `decision_id`: Số quyết định
- `council_members`: JSON danh sách thành viên

### POST `/view/student/update_report_info.php`
Cập nhật thông tin biên bản nghiệm thu (bao gồm thành viên hội đồng)

## 6. Kiểm tra và Debug

### Kiểm tra dữ liệu
```sql
-- Xem thông tin hội đồng cho một quyết định
SELECT * FROM view_council_members WHERE QD_SO = 'QD001';

-- Kiểm tra dữ liệu JSON
SELECT QD_SO, HD_THANHVIEN FROM quyet_dinh_nghiem_thu WHERE HD_THANHVIEN IS NOT NULL;
```

### Logs
Kiểm tra error logs trong:
- PHP error logs
- Browser console
- Database query logs

## 7. Troubleshooting

### Lỗi thường gặp:
1. **"Column 'HD_THANHVIEN' doesn't exist"**
   - Chạy lại `run_database_update.php`

2. **"Cannot load teachers list"**
   - Kiểm tra file `/api/get_teachers.php`
   - Kiểm tra quyền truy cập database

3. **"JSON parse error"**
   - Kiểm tra format dữ liệu được gửi từ form

4. **"Permission denied"**
   - Chỉ chủ nhiệm đề tài mới có quyền cập nhật

## 8. Backup và Recovery

### Trước khi cập nhật:
- Backup database: `mysqldump -u root -p ql_nckh > backup_before_update.sql`

### Khôi phục nếu cần:
- `mysql -u root -p ql_nckh < backup_before_update.sql`

## 9. Kiểm tra hoạt động

1. Tạo một quyết định nghiệm thu mới
2. Thêm thành viên hội đồng
3. Kiểm tra dữ liệu đã lưu đúng chưa
4. Test các chức năng CRUD

---

**Lưu ý:** Sau khi cập nhật thành công, bạn có thể xóa file `run_database_update.php` để đảm bảo bảo mật.
