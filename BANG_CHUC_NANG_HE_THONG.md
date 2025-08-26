# BẢNG CHỨC NĂNG HỆ THỐNG QUẢN LÝ ĐỀ TÀI NGHIÊN CỨU KHOA HỌC

| STT | Mã chức năng | Tên chức năng |
|-----|--------------|---------------|
| 1 | TC01 | Đăng ký tài khoản |
| 2 | TC02 | Đăng nhập tài khoản |
| 3 | TC03 | Đăng xuất tài khoản |
| 4 | TC04 | Quản lý thông tin cá nhân sinh viên |
| 5 | TC05 | Quản lý thông tin cá nhân giảng viên |
| 6 | TC06 | Quản lý thông tin cá nhân quản trị viên |
| 7 | TC07 | Đăng ký đề tài nghiên cứu khoa học |
| 8 | TC08 | Xem danh sách đề tài nghiên cứu |
| 9 | TC09 | Tìm kiếm đề tài nghiên cứu |
| 10 | TC10 | Xem chi tiết đề tài nghiên cứu |
| 11 | TC11 | Cập nhật thông tin đề tài nghiên cứu |
| 12 | TC12 | Phê duyệt đề tài nghiên cứu |
| 13 | TC13 | Quản lý thành viên tham gia đề tài |
| 14 | TC14 | Cập nhật tiến độ đề tài |
| 15 | TC15 | Upload file đề tài |
| 16 | TC16 | Quản lý báo cáo tiến độ |
| 17 | TC17 | Đánh giá thành viên nhóm |
| 18 | TC18 | Quản lý hội đồng đánh giá |
| 19 | TC19 | Tạo quyết định nghiệm thu |
| 20 | TC20 | Tạo biên bản nghiệm thu |
| 21 | TC21 | Quản lý hợp đồng nghiên cứu |
| 22 | TC22 | Quản lý ngân sách đề tài |
| 23 | TC23 | Xem báo cáo thống kê |
| 24 | TC24 | Xuất báo cáo Excel |
| 25 | TC25 | Quản lý thông báo hệ thống |
| 26 | TC26 | Quản lý người dùng hệ thống |
| 27 | TC27 | Quản lý khoa/đơn vị |
| 28 | TC28 | Quản lý lớp học |
| 29 | TC29 | Quản lý loại đề tài |
| 30 | TC30 | Quản lý lĩnh vực nghiên cứu |
| 31 | TC31 | Quản lý lĩnh vực ưu tiên |
| 32 | TC32 | Quản lý tiêu chí đánh giá |
| 33 | TC33 | Quản lý học kỳ |
| 34 | TC34 | Quản lý niên khóa |
| 35 | TC35 | Đổi mật khẩu |
| 36 | TC36 | Khôi phục mật khẩu |
| 37 | TC37 | Xem lịch sử hoạt động |
| 38 | TC38 | Sao lưu dữ liệu |
| 39 | TC39 | Khôi phục dữ liệu |
| 40 | TC40 | Cấu hình hệ thống |

## Mô tả chi tiết các chức năng chính:

### **Nhóm chức năng xác thực và phân quyền (TC01-TC03)**
- **TC01**: Đăng ký tài khoản cho sinh viên, giảng viên, quản trị viên
- **TC02**: Đăng nhập vào hệ thống với vai trò tương ứng
- **TC03**: Đăng xuất khỏi hệ thống

### **Nhóm chức năng quản lý thông tin cá nhân (TC04-TC06)**
- **TC04**: Sinh viên xem và cập nhật thông tin cá nhân, hồ sơ nghiên cứu
- **TC05**: Giảng viên quản lý thông tin chuyên môn, lĩnh vực nghiên cứu
- **TC06**: Quản trị viên quản lý thông tin cá nhân và cấu hình hệ thống

### **Nhóm chức năng quản lý đề tài nghiên cứu (TC07-TC15)**
- **TC07**: Sinh viên đăng ký đề tài nghiên cứu mới
- **TC08**: Xem danh sách đề tài với bộ lọc theo trạng thái, loại, khoa
- **TC09**: Tìm kiếm đề tài theo từ khóa, mã đề tài, tên đề tài
- **TC10**: Xem chi tiết thông tin đề tài, thành viên, tiến độ
- **TC11**: Cập nhật thông tin đề tài (chủ nhiệm, giảng viên hướng dẫn)
- **TC12**: Quản lý nghiên cứu phê duyệt đề tài mới
- **TC13**: Quản lý thành viên tham gia đề tài (thêm, xóa, phân quyền)
- **TC14**: Cập nhật tiến độ thực hiện đề tài
- **TC15**: Upload file đề tài (đề xuất, báo cáo, tài liệu)

### **Nhóm chức năng đánh giá và nghiệm thu (TC16-TC20)**
- **TC16**: Quản lý báo cáo tiến độ định kỳ
- **TC17**: Đánh giá thành viên nhóm theo tiêu chí
- **TC18**: Quản lý hội đồng đánh giá (thành viên, vai trò)
- **TC19**: Tạo quyết định nghiệm thu đề tài
- **TC20**: Tạo biên bản nghiệm thu chi tiết

### **Nhóm chức năng quản lý tài chính (TC21-TC22)**
- **TC21**: Quản lý hợp đồng nghiên cứu và kinh phí
- **TC22**: Quản lý ngân sách đề tài, chi tiêu

### **Nhóm chức năng báo cáo và thống kê (TC23-TC24)**
- **TC23**: Xem báo cáo thống kê theo khoa, trạng thái, thời gian
- **TC24**: Xuất báo cáo ra file Excel

### **Nhóm chức năng quản trị hệ thống (TC25-TC40)**
- **TC25**: Quản lý thông báo hệ thống
- **TC26**: Quản lý người dùng (thêm, sửa, xóa, phân quyền)
- **TC27**: Quản lý khoa/đơn vị
- **TC28**: Quản lý lớp học
- **TC29**: Quản lý loại đề tài
- **TC30**: Quản lý lĩnh vực nghiên cứu
- **TC31**: Quản lý lĩnh vực ưu tiên
- **TC32**: Quản lý tiêu chí đánh giá
- **TC33**: Quản lý học kỳ
- **TC34**: Quản lý niên khóa
- **TC35**: Đổi mật khẩu
- **TC36**: Khôi phục mật khẩu
- **TC37**: Xem lịch sử hoạt động
- **TC38**: Sao lưu dữ liệu
- **TC39**: Khôi phục dữ liệu
- **TC40**: Cấu hình hệ thống
