# Simple Research Sidebar

## Giới thiệu

Simple Research Sidebar là một sidebar mới được thiết kế đơn giản, gọn gàng và dễ sử dụng cho hệ thống quản lý nghiên cứu khoa học. Sidebar này thay thế cho sidebar phức tạp trước đó với giao diện thân thiện và hiệu suất tốt hơn.

## Tính năng chính

### 1. Thiết kế đơn giản và sạch sẽ
- Giao diện tối giản, dễ nhìn
- Màu sắc hài hòa và chuyên nghiệp
- Icons rõ ràng, dễ hiểu

### 2. Responsive Design
- Tương thích với tất cả các thiết bị
- Sidebar thu gọn trên mobile
- Touch-friendly cho tablet và smartphone

### 3. Tổ chức menu logic
- Phân nhóm chức năng rõ ràng:
  - **Dashboard**: Bảng điều khiển tổng quan
  - **Quản lý đề tài**: Các chức năng về đề tài nghiên cứu
  - **Quản lý người dùng**: Quản lý nhà nghiên cứu, sinh viên, giảng viên
  - **Báo cáo & Thống kê**: Các chức năng báo cáo
  - **Cài đặt**: Cấu hình hệ thống và hồ sơ cá nhân

### 4. Thông báo trực quan
- Badge hiển thị số đề tài chờ duyệt
- Highlight menu đang hoạt động
- Animation mượt mà khi chuyển trang

### 5. Accessibility
- Hỗ trợ keyboard navigation
- High contrast mode
- Screen reader friendly

## Cấu trúc Files

```
include/
├── simple_research_sidebar.php     # File sidebar chính
└── research_header.php            # Header đã cập nhật

assets/css/research/
├── simple-sidebar.css             # CSS chính cho sidebar
└── simple-sidebar-enhanced.css    # CSS nâng cao (animations, effects)

assets/js/research/
└── simple-sidebar.js              # JavaScript xử lý tương tác
```

## Cách sử dụng

### 1. Cài đặt
Sidebar đã được tích hợp sẵn vào hệ thống. Chỉ cần include `research_header.php` là sidebar sẽ hoạt động.

```php
<?php include '../../include/research_header.php'; ?>
```

### 2. Tùy chỉnh menu
Để thêm menu mới, chỉnh sửa file `simple_research_sidebar.php`:

```php
<li class="nav-item">
    <a href="/path/to/page.php" 
       class="nav-link <?php echo ($current_page == 'page.php') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-icon-name"></i>
        <span class="nav-text">Tên menu</span>
    </a>
</li>
```

### 3. Thêm badge thông báo
```php
<span class="nav-badge">5</span>
```

## Cấu hình

### 1. Màu sắc
Chỉnh sửa trong `simple-sidebar.css`:

```css
.simple-sidebar {
    background: #2c3e50;  /* Màu nền chính */
    color: #ecf0f1;       /* Màu text */
}

.nav-link.active {
    background: #3498db;  /* Màu khi active */
}
```

### 2. Responsive breakpoints
```css
@media (max-width: 768px) {
    /* Mobile styles */
}
```

### 3. Animation timing
```css
.nav-link {
    transition: all 0.3s ease;  /* Tốc độ animation */
}
```

## API JavaScript

### 1. Toggle sidebar trên mobile
```javascript
SimpleSidebar.toggle();
```

### 2. Highlight menu active
```javascript
SimpleSidebar.highlightActive();
```

### 3. Hiển thị thông báo toast
```javascript
SimpleSidebar.showToast('Thông báo thành công', 'success');
```

## Troubleshooting

### 1. Sidebar không hiển thị
- Kiểm tra file CSS đã được load chưa
- Đảm bảo jQuery đã được include trước
- Kiểm tra quyền truy cập file

### 2. Menu không highlight đúng
- Kiểm tra biến `$current_page` đã được set chưa
- Đảm bảo đường dẫn href chính xác

### 3. Responsive không hoạt động
- Kiểm tra meta viewport trong header
- Đảm bảo CSS responsive đã được load

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers

## Performance

- CSS: ~15KB (minified)
- JavaScript: ~8KB (minified)
- Load time: <100ms
- Mobile friendly: Touch optimized

## Migration từ sidebar cũ

1. Backup sidebar cũ
2. Update `research_header.php` để include sidebar mới
3. Test tất cả các trang
4. Cập nhật custom CSS nếu có

## Todo / Roadmap

- [ ] Dark mode toggle
- [ ] Customizable themes
- [ ] More animation options
- [ ] Collapsible sub-menus
- [ ] Drag & drop menu reordering

## Support

Nếu gặp vấn đề, hãy kiểm tra:
1. Console log trong browser
2. Network tab để xem file CSS/JS đã load chưa
3. Responsive design mode để test mobile

---

**Sidebar mới này được thiết kế để dễ sử dụng, hiệu suất cao và thân thiện với người dùng. Hy vọng sẽ mang lại trải nghiệm tốt hơn cho hệ thống quản lý nghiên cứu!**
