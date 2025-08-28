# Hướng dẫn chuyển đổi file Excel sang CSV

## Nếu gặp lỗi "Vui lòng chuyển đổi file Excel sang định dạng CSV"

### Cách 1: Sử dụng Microsoft Excel
1. Mở file Excel của bạn
2. Click **File** → **Save As**
3. Chọn định dạng **CSV (Comma delimited) (*.csv)**
4. Click **Save**
5. Upload file CSV vào hệ thống

### Cách 2: Sử dụng Google Sheets
1. Upload file Excel lên Google Drive
2. Mở bằng Google Sheets
3. Click **File** → **Download** → **Comma Separated Values (.csv)**
4. Upload file CSV vào hệ thống

### Cách 3: Sử dụng LibreOffice Calc (Miễn phí)
1. Mở file Excel bằng LibreOffice Calc
2. Click **File** → **Save As**
3. Chọn định dạng **Text CSV (.csv)**
4. Click **Save**
5. Upload file CSV vào hệ thống

## Lưu ý quan trọng
- Đảm bảo file CSV có đúng 6 cột: Mã SV, Họ, Tên, Ngày sinh, Email, SĐT
- **KHÔNG cần dòng tiêu đề** - chỉ cần nội dung sinh viên
- Định dạng ngày sinh phải là dd/mm/yyyy (ví dụ: 16/09/2003)
- Không được có dòng trống giữa các dữ liệu
- Encoding nên là UTF-8 để hiển thị đúng tiếng Việt

## Ví dụ file CSV đúng định dạng:
```
B2103452,Trần,Bảo Anh,16/09/2003,anhb2103452@student.ctu.edu.vn,0919825472
B2103453,Võ,Doãn Ngọc Châu,03/02/2003,chaub2103453@student.ctu.edu.vn,0939064919
B2103455,Nguyễn,Thành Đạt,24/05/2003,datb2103455@student.ctu.edu.vn,0373167868
```
