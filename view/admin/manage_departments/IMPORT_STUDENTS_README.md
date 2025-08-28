# Hướng dẫn Import Sinh viên

## Tổng quan
Tài liệu này mô tả cấu trúc để phát triển chức năng import danh sách sinh viên từ file Excel/CSV vào lớp học.

## Cấu trúc Database
Bảng `sinh_vien` có các trường sau:
- `SV_MASV` (char(8)) - Mã sinh viên (Primary Key)
- `LOP_MA` (char(8)) - Mã lớp (Foreign Key)
- `SV_HOSV` (varchar(50)) - Họ sinh viên
- `SV_TENSV` (varchar(50)) - Tên sinh viên
- `SV_GIOITINH` (tinyint(4)) - Giới tính (1=Nam, 0=Nữ)
- `SV_SDT` (varchar(15)) - Số điện thoại
- `SV_EMAIL` (varchar(35)) - Email
- `SV_MATKHAU` (varchar(255)) - Mật khẩu (mã hóa)
- `SV_NGAYSINH` (date) - Ngày sinh
- `SV_DIACHI` (varchar(255)) - Địa chỉ
- `SV_AVATAR` (varchar(255)) - Avatar (nullable)

## Định dạng file Import (Đã triển khai)
File Excel/CSV phải có đúng 6 cột theo thứ tự:
1. **Mã sinh viên** (cột A) - ví dụ: B2103452
2. **Họ sinh viên** (cột B) - ví dụ: Trần  
3. **Tên sinh viên** (cột C) - ví dụ: Bảo Anh
4. **Ngày sinh** (cột D) - định dạng dd/mm/yyyy - ví dụ: 16/09/2003
5. **Email** (cột E) - ví dụ: anhb2103452@student.ctu.edu.vn
6. **Số điện thoại** (cột F) - ví dụ: 0919825472

**Lưu ý**: File KHÔNG cần dòng tiêu đề, chỉ cần nội dung sinh viên.

## Files đã tạo (Hoàn thành)
1. ✅ `import_students.php` - Xử lý upload và import file
2. ✅ Modal import trong `manage_departments.php`
3. ✅ JavaScript functions trong `manage_departments.js`
4. ✅ `EXCEL_TO_CSV_GUIDE.md` - Hướng dẫn chuyển đổi file

## Chức năng đã hoàn thành
- ✅ Upload file Excel/CSV (tối đa 5MB)
- ✅ Validate định dạng file (.xlsx, .xls, .csv)
- ✅ Xử lý file không có dòng tiêu đề
- ✅ Kiểm tra trùng lặp mã sinh viên
- ✅ Tạo mật khẩu mặc định cho sinh viên mới
- ✅ Validate đầy đủ (email, SĐT, ngày sinh...)
- ✅ Báo cáo kết quả import chi tiết
- ✅ Transaction database với rollback nếu có lỗi
- ✅ Giao diện thân thiện với modal và progress

## Ghi chú
- Mật khẩu mặc định có thể là mã sinh viên hoặc ngày sinh
- Cần hash mật khẩu trước khi lưu vào database
- Kiểm tra email không trùng lặp
- Validate format email và số điện thoại
