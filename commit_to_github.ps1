# Script để commit và push code lên GitHub
# Chạy script này sau khi đã cài đặt Git và tạo repository trên GitHub

Write-Host "=== Script Commit và Push lên GitHub ===" -ForegroundColor Green
Write-Host ""

# Kiểm tra Git đã được cài đặt chưa
try {
    $gitVersion = git --version
    Write-Host "✓ Git đã được cài đặt: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ Git chưa được cài đặt. Vui lòng cài đặt Git trước!" -ForegroundColor Red
    Write-Host "Tải Git tại: https://git-scm.com/download/win" -ForegroundColor Yellow
    exit 1
}

# Kiểm tra xem đã có repository Git chưa
if (Test-Path ".git") {
    Write-Host "✓ Đã tìm thấy repository Git" -ForegroundColor Green
} else {
    Write-Host "! Chưa có repository Git. Đang khởi tạo..." -ForegroundColor Yellow
    git init
    Write-Host "✓ Đã khởi tạo repository Git" -ForegroundColor Green
}

# Thêm tất cả file vào staging area
Write-Host ""
Write-Host "Đang thêm file vào staging area..." -ForegroundColor Cyan
git add .

# Kiểm tra trạng thái
Write-Host ""
Write-Host "Trạng thái hiện tại:" -ForegroundColor Cyan
git status

# Hỏi người dùng có muốn commit không
Write-Host ""
$confirm = Read-Host "Bạn có muốn commit và push lên GitHub không? (y/n)"

if ($confirm -eq "y" -or $confirm -eq "Y") {
    # Commit với message tiếng Việt
    $commitMessage = "✨ Cập nhật hệ thống quản lý nghiên cứu khoa học

🔧 Sửa lỗi:
- Sửa lỗi dropdown lớp không populate
- Sửa lỗi filter trạng thái nghiên cứu 'Chưa tham gia nghiên cứu'
- Cải thiện logic SQL query

✨ Tính năng mới:
- Thêm API lấy danh sách khoa động
- Thêm API lấy khóa học từ bảng lớp
- Tự động cập nhật danh sách khi thay đổi bộ lọc
- Hiển thị trạng thái nghiên cứu với badge màu sắc
- Thêm chức năng xuất Excel

📁 File đã thêm:
- api/get_distinct_lop_years.php
- api/get_faculties.php
- test_research_status_debug.html
- test_direct_sql.php
- HUONG_DAN_TEST.md
- README.md
- .gitignore"

    Write-Host ""
    Write-Host "Đang commit với message:" -ForegroundColor Cyan
    Write-Host $commitMessage -ForegroundColor Gray
    
    git commit -m $commitMessage
    
    # Hỏi URL repository GitHub
    Write-Host ""
    $repoUrl = Read-Host "Nhập URL repository GitHub (ví dụ: https://github.com/username/repo.git)"
    
    if ($repoUrl) {
        # Thêm remote origin nếu chưa có
        $remotes = git remote -v
        if ($remotes -notlike "*origin*") {
            Write-Host "Đang thêm remote origin..." -ForegroundColor Cyan
            git remote add origin $repoUrl
        }
        
        # Push lên GitHub
        Write-Host ""
        Write-Host "Đang push lên GitHub..." -ForegroundColor Cyan
        git push -u origin main
        
        # Nếu branch main không tồn tại, thử master
        if ($LASTEXITCODE -ne 0) {
            Write-Host "Thử push với branch master..." -ForegroundColor Yellow
            git push -u origin master
        }
        
        Write-Host ""
        Write-Host "✓ Hoàn thành! Code đã được push lên GitHub" -ForegroundColor Green
        Write-Host "Repository URL: $repoUrl" -ForegroundColor Cyan
    } else {
        Write-Host "! Chưa nhập URL repository. Bạn có thể push thủ công sau." -ForegroundColor Yellow
        Write-Host "Lệnh push: git push -u origin main" -ForegroundColor Gray
    }
} else {
    Write-Host "! Đã hủy commit. Bạn có thể commit thủ công sau." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Kết thúc script ===" -ForegroundColor Green
