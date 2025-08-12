# Hướng dẫn Upload Code lên GitHub

## Bước 1: Cài đặt Git

### Windows:
1. Truy cập: https://git-scm.com/download/win
2. Tải và cài đặt Git for Windows
3. Khởi động lại PowerShell sau khi cài đặt

### Kiểm tra cài đặt:
```powershell
git --version
```

## Bước 2: Tạo Repository trên GitHub

1. Truy cập: https://github.com
2. Đăng nhập hoặc tạo tài khoản mới
3. Click "New repository" (nút màu xanh)
4. Điền thông tin:
   - **Repository name**: `NLNganh` (hoặc tên bạn muốn)
   - **Description**: `Hệ thống quản lý nghiên cứu khoa học`
   - **Public** hoặc **Private** (tùy chọn)
   - **Không check** "Add a README file" (vì đã có sẵn)
5. Click "Create repository"

## Bước 3: Cấu hình Git (Lần đầu sử dụng)

```powershell
# Cấu hình tên người dùng
git config --global user.name "Tên của bạn"

# Cấu hình email
git config --global user.email "email@example.com"
```

## Bước 4: Upload Code lên GitHub

### Cách 1: Sử dụng Script tự động (Khuyến nghị)

1. Chạy script PowerShell:
```powershell
.\commit_to_github.ps1
```

2. Làm theo hướng dẫn trong script:
   - Nhập `y` để xác nhận commit
   - Nhập URL repository GitHub (ví dụ: `https://github.com/username/NLNganh.git`)

### Cách 2: Thực hiện thủ công

1. **Khởi tạo Git repository**:
```powershell
git init
```

2. **Thêm file vào staging area**:
```powershell
git add .
```

3. **Commit với message tiếng Việt**:
```powershell
git commit -m "✨ Cập nhật hệ thống quản lý nghiên cứu khoa học

🔧 Sửa lỗi:
- Sửa lỗi dropdown lớp không populate
- Sửa lỗi filter trạng thái nghiên cứu 'Chưa tham gia nghiên cứu'
- Cải thiện logic SQL query

✨ Tính năng mới:
- Thêm API lấy danh sách khoa động
- Thêm API lấy khóa học từ bảng lớp
- Tự động cập nhật danh sách khi thay đổi bộ lọc
- Hiển thị trạng thái nghiên cứu với badge màu sắc
- Thêm chức năng xuất Excel"
```

4. **Thêm remote origin**:
```powershell
git remote add origin https://github.com/username/NLNganh.git
```

5. **Push lên GitHub**:
```powershell
git push -u origin main
```

## Bước 5: Kiểm tra kết quả

1. Truy cập repository trên GitHub
2. Kiểm tra xem code đã được upload chưa
3. Kiểm tra README.md có hiển thị đúng không

## Các lệnh Git hữu ích

### Kiểm tra trạng thái:
```powershell
git status
```

### Xem lịch sử commit:
```powershell
git log --oneline
```

### Xem remote repositories:
```powershell
git remote -v
```

### Tạo branch mới:
```powershell
git checkout -b feature/tinh-nang-moi
```

### Chuyển branch:
```powershell
git checkout main
```

### Merge branch:
```powershell
git merge feature/tinh-nang-moi
```

## Troubleshooting

### Lỗi "git is not recognized":
- Cài đặt lại Git và khởi động lại PowerShell
- Kiểm tra PATH environment variable

### Lỗi authentication:
```powershell
# Sử dụng Personal Access Token
git remote set-url origin https://username:token@github.com/username/repo.git
```

### Lỗi "main branch does not exist":
```powershell
# Tạo branch main
git branch -M main
git push -u origin main
```

### Lỗi "remote origin already exists":
```powershell
# Xóa remote cũ và thêm lại
git remote remove origin
git remote add origin https://github.com/username/repo.git
```

## Commit Message Convention

Sử dụng emoji và format rõ ràng:

```
✨ Tính năng mới
🔧 Sửa lỗi
📝 Cập nhật tài liệu
🚀 Cải thiện hiệu suất
🐛 Fix bug
📁 Thêm file mới
🗑️ Xóa file
```

## Lưu ý quan trọng

1. **Không commit file nhạy cảm**: Kiểm tra `.gitignore` đã loại trừ file config chứa thông tin database
2. **Commit thường xuyên**: Commit mỗi khi hoàn thành một tính năng
3. **Message rõ ràng**: Viết commit message mô tả rõ thay đổi
4. **Backup**: Luôn có backup trước khi push

## Liên kết hữu ích

- [Git Documentation](https://git-scm.com/doc)
- [GitHub Guides](https://guides.github.com/)
- [Git Cheat Sheet](https://education.github.com/git-cheat-sheet-education.pdf)
