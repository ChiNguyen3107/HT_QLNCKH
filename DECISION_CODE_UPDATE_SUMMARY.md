# Cập nhật cơ sở dữ liệu - Mã quyết định 11 ký tự

## Tóm tắt thay đổi
**Ngày cập nhật**: 6/8/2025
**Thay đổi**: Mở rộng trường mã quyết định từ 5 ký tự lên 11 ký tự

## Chi tiết kỹ thuật

### Trước khi cập nhật:
- **Trường**: `QD_SO` 
- **Kiểu**: `CHAR(5)`
- **Giới hạn**: 5 ký tự
- **Ví dụ**: `QDDT0`, `123ab`

### Sau khi cập nhật:
- **Trường**: `QD_SO`
- **Kiểu**: `VARCHAR(11)`
- **Giới hạn**: 11 ký tự
- **Ví dụ**: `QD2024-0001`, `NGHIEM-001`, `DECISION01`

## Các bước thực hiện:

### 1. Xử lý Foreign Key Constraints:
- Phát hiện 4 foreign key constraints liên quan đến `QD_SO`
- Tạm thời xóa các constraints
- Cập nhật các bảng liên quan:
  - `bien_ban.QD_SO` → VARCHAR(11)
  - `chi_tiet_diem_danh_gia.QD_SO` → VARCHAR(11)
  - `de_tai_nghien_cuu.QD_SO` → VARCHAR(11)
  - `thanh_vien_hoi_dong.QD_SO` → VARCHAR(11)

### 2. Cập nhật bảng chính:
```sql
ALTER TABLE quyet_dinh_nghiem_thu MODIFY QD_SO VARCHAR(11) NOT NULL;
```

### 3. Khôi phục Foreign Key Constraints:
- Tạo lại tất cả 4 foreign key constraints với CASCADE

### 4. Cập nhật giao diện:
- Thêm `maxlength="11"` cho input số quyết định
- Cập nhật placeholder: `QD2024-0001`
- Thêm thông báo: "Số quyết định có độ dài tối đa 11 ký tự"

## Dữ liệu hiện tại:
- ✓ Dữ liệu cũ được bảo toàn: `QDDT0`, `123ab`
- ✓ Có thể nhập mã mới dài hơn (tối đa 11 ký tự)
- ✓ Tất cả foreign key constraints hoạt động bình thường

## Ví dụ mã quyết định hợp lệ:
- `QD2024-0001` (10 ký tự)
- `NGHIEM-001` (10 ký tự) 
- `DECISION01` (10 ký tự)
- `QD-2025-001` (10 ký tự)
- `NT12345678A` (11 ký tự)

## Ví dụ mã quyết định không hợp lệ:
- `QD2024-000001` (12 ký tự - quá dài)
- `VERY_LONG_DECISION_CODE` (sẽ bị cắt xuống 11 ký tự)

## Kết luận:
✅ **Hoàn thành**: Cơ sở dữ liệu đã được cập nhật thành công
✅ **Tương thích**: Dữ liệu cũ vẫn hoạt động  
✅ **Sẵn sàng**: Form có thể nhận mã quyết định 11 ký tự
✅ **An toàn**: Tất cả foreign key constraints được bảo toàn

**Lưu ý**: Cập nhật này tương tự như cập nhật mã hợp đồng đã thực hiện trước đó.
