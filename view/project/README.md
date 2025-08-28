# Module Xem Đề Tài Nghiên Cứu

Module này cung cấp chức năng xem thông tin chi tiết đề tài nghiên cứu khoa học.

## Cấu trúc Files

```
view/project/
├── view_project.php    # Trang xem chi tiết đề tài
├── search.php         # Trang tìm kiếm đề tài
├── index.php          # Redirect đến search.php
├── demo.php           # Trang demo/test
└── README.md          # Tài liệu này
```

## URLs

### Xem chi tiết đề tài
```
http://localhost/NLNganh/view/project/view_project.php?dt_madt=[MÃ_ĐỀ_TÀI]
```

**Ví dụ:**
- `http://localhost/NLNganh/view/project/view_project.php?dt_madt=DT0000001`
- `http://localhost/NLNganh/view/project/view_project.php?dt_madt=DT0000002`

### Tìm kiếm đề tài
```
http://localhost/NLNganh/view/project/search.php
```

**Tham số tìm kiếm:**
- `search`: Từ khóa tìm kiếm (tên đề tài, mô tả, giảng viên)
- `status`: Trạng thái đề tài
- `department`: Mã khoa
- `year`: Năm tạo
- `page`: Trang hiện tại

**Ví dụ:**
- `http://localhost/NLNganh/view/project/search.php?search=machine%20learning`
- `http://localhost/NLNganh/view/project/search.php?status=Đang%20thực%20hiện`

### Demo/Test
```
http://localhost/NLNganh/view/project/demo.php
```

## Tính năng

### view_project.php
- Hiển thị thông tin chi tiết đề tài
- Thông tin giảng viên hướng dẫn
- Danh sách sinh viên tham gia
- Tiến độ thực hiện
- File đính kèm (thuyết minh, hợp đồng, quyết định)
- Biên bản nghiệm thu
- Hội đồng đánh giá
- Responsive design
- Print-friendly

### search.php
- Tìm kiếm đề tài theo từ khóa
- Lọc theo trạng thái, khoa, năm
- Phân trang
- Card view với thông tin tóm tắt
- Responsive design

## Database Tables Sử Dụng

- `de_tai_nghien_cuu`: Thông tin đề tài chính
- `giang_vien`: Thông tin giảng viên hướng dẫn
- `sinh_vien`: Thông tin sinh viên
- `chi_tiet_tham_gia`: Chi tiết tham gia đề tài
- `tien_do_de_tai`: Tiến độ thực hiện
- `hop_dong`: Hợp đồng
- `quyet_dinh_nghiem_thu`: Quyết định nghiệm thu
- `bien_ban`: Biên bản nghiệm thu
- `thanh_vien_hoi_dong`: Thành viên hội đồng
- `loai_de_tai`: Loại đề tài
- `linh_vuc_nghien_cuu`: Lĩnh vực nghiên cứu
- `linh_vuc_uu_tien`: Lĩnh vực ưu tiên
- `khoa`: Thông tin khoa
- `lop`: Thông tin lớp

## CSS Styles

Sử dụng file CSS riêng: `/assets/css/project/project-view.css`

## Xử Lý Lỗi

- Redirect đến `/NLNganh/404.php` nếu:
  - Không có tham số `dt_madt`
  - Đề tài không tồn tại
  - Lỗi database

## Security

- Sử dụng prepared statements
- Escape HTML output với `htmlspecialchars()`
- Validate input parameters

## Responsive Design

- Bootstrap 5
- Mobile-friendly
- Touch-optimized

## Browser Support

- Chrome/Edge/Firefox/Safari (modern versions)
- IE 11+ (limited support)

## Dependencies

- PHP 7.4+
- MySQL/MariaDB
- Bootstrap 5.3.0
- Font Awesome 6.4.0

## Installation

1. Copy files vào thư mục `view/project/`
2. Copy CSS file vào `assets/css/project/`
3. Đảm bảo database connection trong `include/config.php`
4. Test qua `demo.php`

## Troubleshooting

### Lỗi 404
- Kiểm tra đường dẫn file
- Kiểm tra tham số `dt_madt`
- Kiểm tra dữ liệu trong database

### Lỗi Database
- Kiểm tra connection trong `config.php`
- Kiểm tra các bảng có tồn tại không
- Kiểm tra quyền truy cập database

### CSS không load
- Kiểm tra đường dẫn CSS
- Kiểm tra file CSS có tồn tại không
- Clear browser cache

## Customization

### Thêm field mới
1. Cập nhật SQL query
2. Thêm hiển thị trong HTML
3. Cập nhật CSS nếu cần

### Thay đổi layout
1. Chỉnh sửa HTML structure
2. Cập nhật CSS classes
3. Test responsive

### Thêm tính năng
1. Thêm logic PHP
2. Thêm HTML/CSS
3. Thêm JavaScript nếu cần

## Future Enhancements

- Export PDF
- Print optimization
- Comments/Reviews
- Rating system
- Social sharing
- Advanced filters
- Bookmark/Favorites
- Recent views
- Related projects
