# Xử lý đề tài trùng lặp - Hướng dẫn chi tiết

## 🎯 Vấn đề
Khi sinh viên đăng ký đề tài, có thể xảy ra tình trạng trùng lặp thông tin:
- **Tên đề tài trùng lặp**: Nhiều đề tài có cùng tên
- **Mô tả trùng lặp**: Nhiều đề tài có mô tả giống nhau
- **Sinh viên tham gia nhiều đề tài**: Một sinh viên đăng ký nhiều đề tài

## 🔍 Phân tích hiện tại

### 1. **Cấu trúc database**
- Bảng `de_tai_nghien_cuu` không có ràng buộc UNIQUE cho `DT_TENDT`
- Không có validation trong code để kiểm tra trùng lặp
- Sinh viên có thể tham gia nhiều đề tài

### 2. **Các loại trùng lặp có thể xảy ra**
- ✅ **Tên đề tài trùng lặp**: `DT_TENDT` giống nhau
- ✅ **Mô tả trùng lặp**: `DT_MOTA` giống nhau  
- ✅ **Sinh viên đăng ký trùng**: Cùng sinh viên đăng ký đề tài có tên tương tự
- ✅ **Giảng viên trùng lặp**: Cùng giảng viên hướng dẫn nhiều đề tài

## 🛠️ Giải pháp đã triển khai

### 1. **Script kiểm tra trùng lặp**
File: `check_duplicate_projects.php`
- Kiểm tra đề tài trùng tên
- Kiểm tra đề tài trùng mô tả
- Kiểm tra sinh viên tham gia nhiều đề tài
- Đề xuất giải pháp xử lý

### 2. **Cập nhật logic đăng ký**
File: `view/student/register_project_process.php`

#### Thêm function kiểm tra trùng lặp:
```php
function checkDuplicateProject($conn, $project_title, $project_description)
{
    // Kiểm tra tên đề tài trùng lặp
    $title_query = "SELECT DT_MADT, DT_TENDT, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_TENDT = ?";
    $title_stmt = $conn->prepare($title_query);
    $title_stmt->bind_param("s", $project_title);
    $title_stmt->execute();
    $title_result = $title_stmt->get_result();
    
    if ($title_result->num_rows > 0) {
        $existing_project = $title_result->fetch_assoc();
        return [
            'duplicate' => true,
            'type' => 'title',
            'message' => 'Đã tồn tại đề tài với tên "' . $project_title . '". Vui lòng đặt tên khác hoặc kiểm tra lại.',
            'existing_project' => $existing_project
        ];
    }
    
    // Kiểm tra mô tả trùng lặp
    if (strlen($project_description) > 100) {
        $desc_query = "SELECT DT_MADT, DT_TENDT, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MOTA = ?";
        $desc_stmt = $conn->prepare($desc_query);
        $desc_stmt->bind_param("s", $project_description);
        $desc_stmt->execute();
        $desc_result = $desc_stmt->get_result();
        
        if ($desc_result->num_rows > 0) {
            $existing_project = $desc_result->fetch_assoc();
            return [
                'duplicate' => true,
                'type' => 'description',
                'message' => 'Đã tồn tại đề tài với mô tả tương tự. Vui lòng kiểm tra lại.',
                'existing_project' => $existing_project
            ];
        }
    }
    
    // Kiểm tra sinh viên đã đăng ký đề tài với tên tương tự chưa (tránh đăng ký trùng)
    $student_similar_query = "SELECT COUNT(*) as project_count 
                             FROM chi_tiet_tham_gia ct
                             JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                             WHERE ct.SV_MASV = ? AND dt.DT_TENDT = ?";
    $student_similar_stmt = $conn->prepare($student_similar_query);
    $student_similar_stmt->bind_param("ss", $_POST['leader_student_id'], $project_title);
    $student_similar_stmt->execute();
    $student_similar_result = $student_similar_stmt->get_result();
    $student_similar_count = $student_similar_result->fetch_assoc()['project_count'];
    
    if ($student_similar_count > 0) {
        return [
            'duplicate' => true,
            'type' => 'student_similar',
            'message' => 'Sinh viên này đã đăng ký một đề tài có tên tương tự. Vui lòng kiểm tra lại.',
            'project_count' => $student_similar_count
        ];
    }
    
    return ['duplicate' => false];
}
```

#### Thêm validation trong quá trình xử lý:
```php
// Kiểm tra đề tài trùng lặp
$duplicate_check = checkDuplicateProject($conn, $project_title, $project_description);
if ($duplicate_check['duplicate']) {
    throw new Exception($duplicate_check['message']);
}
```

### 3. **Ràng buộc database**
File: `add_unique_constraints.sql`

#### Thêm UNIQUE constraint:
```sql
-- Thêm UNIQUE constraint cho tên đề tài
ALTER TABLE de_tai_nghien_cuu ADD UNIQUE KEY unique_project_title (DT_TENDT);

-- Thêm index cho tìm kiếm nhanh
CREATE INDEX idx_project_title ON de_tai_nghien_cuu(DT_TENDT);
CREATE INDEX idx_project_description ON de_tai_nghien_cuu(DT_MOTA(100));
CREATE INDEX idx_project_status ON de_tai_nghien_cuu(DT_TRANGTHAI);
```

## 📋 Các bước triển khai

### 1. **Kiểm tra dữ liệu hiện tại**
```bash
# Chạy script kiểm tra
php check_duplicate_projects.php
```

### 2. **Xử lý dữ liệu trùng lặp (nếu có)**
- Xem xét các đề tài trùng lặp
- Quyết định giữ lại đề tài nào
- Xóa hoặc cập nhật đề tài trùng lặp

### 3. **Thêm ràng buộc database**
```bash
# Chạy script SQL
mysql -u username -p database_name < add_unique_constraints.sql
```

### 4. **Test hệ thống**
- Test đăng ký đề tài trùng tên
- Test đăng ký đề tài trùng mô tả
- Test sinh viên đăng ký nhiều đề tài

## 🎨 Cải tiến giao diện

### 1. **Thông báo lỗi rõ ràng**
- Hiển thị thông báo cụ thể về loại trùng lặp
- Gợi ý tên đề tài thay thế
- Link đến đề tài đã tồn tại

### 2. **Validation real-time**
- Kiểm tra tên đề tài khi người dùng nhập
- Hiển thị cảnh báo ngay lập tức
- Gợi ý tên đề tài tương tự

### 3. **Trang quản lý trùng lặp**
- Danh sách đề tài trùng lặp
- Chức năng merge đề tài
- Thống kê trùng lặp

## 🔧 Cấu hình tùy chọn

### 1. **Giới hạn sinh viên (KHÔNG KHUYẾN NGHỊ)**
```sql
-- Sinh viên có thể tham gia nhiều đề tài khác nhau, đây là điều bình thường
-- Chỉ sử dụng nếu có yêu cầu đặc biệt từ nhà trường
-- ALTER TABLE chi_tiet_tham_gia ADD UNIQUE KEY unique_student_project (SV_MASV);
```

### 2. **Giới hạn giảng viên**
```sql
-- UNCOMMENT nếu muốn giới hạn giảng viên chỉ hướng dẫn 1 đề tài
-- ALTER TABLE de_tai_nghien_cuu ADD UNIQUE KEY unique_advisor_project (GV_MAGV);
```

### 3. **So sánh nội dung nâng cao**
- Sử dụng thuật toán so sánh văn bản
- Phát hiện trùng lặp tương đối
- Đề xuất merge đề tài

## 📊 Monitoring và báo cáo

### 1. **Log trùng lặp**
- Ghi log khi phát hiện trùng lặp
- Thống kê tần suất trùng lặp
- Báo cáo định kỳ

### 2. **Dashboard quản lý**
- Hiển thị số lượng trùng lặp
- Biểu đồ xu hướng
- Cảnh báo trùng lặp

## 🚀 Kết quả mong đợi

### ✅ **Trước khi triển khai**
- Có thể đăng ký đề tài trùng tên
- Không có thông báo cảnh báo
- Dữ liệu không nhất quán

### ✅ **Sau khi triển khai**
- Ngăn chặn đăng ký trùng lặp
- Thông báo rõ ràng cho người dùng
- Dữ liệu sạch và nhất quán
- Performance tốt hơn với index

## 🔍 Troubleshooting

### Nếu gặp lỗi UNIQUE constraint:
1. Kiểm tra dữ liệu trùng lặp hiện tại
2. Xử lý dữ liệu trùng lặp trước
3. Thêm ràng buộc sau

### Nếu validation quá nghiêm ngặt:
1. Điều chỉnh logic kiểm tra
2. Thêm tùy chọn bỏ qua cảnh báo
3. Cấu hình mức độ nghiêm ngặt

## 📞 Hướng dẫn sử dụng

### 1. **Chạy kiểm tra**
```bash
php check_duplicate_projects.php
```

### 2. **Thêm ràng buộc**
```bash
mysql -u username -p database_name < add_unique_constraints.sql
```

### 3. **Test hệ thống**
- Đăng ký đề tài với tên đã tồn tại
- Kiểm tra thông báo lỗi
- Xác nhận validation hoạt động

## 🎉 Kết luận

Việc xử lý đề tài trùng lặp đã được triển khai đầy đủ với:
- ✅ **Validation chặt chẽ** trong code
- ✅ **Ràng buộc database** để đảm bảo tính toàn vẹn
- ✅ **Thông báo rõ ràng** cho người dùng
- ✅ **Script kiểm tra** để monitoring
- ✅ **Tài liệu hướng dẫn** chi tiết

Hệ thống giờ đây sẽ ngăn chặn hiệu quả việc đăng ký đề tài trùng lặp và đảm bảo chất lượng dữ liệu.
