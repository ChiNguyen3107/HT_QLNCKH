# Cập Nhật Hạn Chế Quyền Và Trạng Thái Đề Tài

## Ngày: 30/07/2025

### Mục tiêu
Giới hạn quyền cập nhật tiến độ và file chỉ cho:
1. **Chủ nhiệm đề tài** (không phải thành viên khác)
2. **Đề tài đang ở trạng thái "Đang thực hiện"** (không phải "Chờ duyệt" hay trạng thái khác)

### Files đã cập nhật

#### Frontend (view_project.php)
- ✅ Form cập nhật file thuyết minh: Chỉ chủ nhiệm + đang thực hiện
- ✅ Form cập nhật thông tin hợp đồng: Chỉ chủ nhiệm + đang thực hiện  
- ✅ Form cập nhật quyết định nghiệm thu: Chỉ chủ nhiệm + đang thực hiện
- ✅ Form cập nhật biên bản nghiệm thu: Chỉ chủ nhiệm + đang thực hiện
- ✅ Upload file đánh giá: Chỉ chủ nhiệm + đang thực hiện
- ✅ Xóa file đánh giá: Chỉ chủ nhiệm + đang thực hiện
- ✅ Modal cập nhật tiến độ: Chỉ chủ nhiệm + đang thực hiện
- ✅ Nút cập nhật tiến độ: Hiển thị trạng thái và quyền hạn

#### Backend Files
- ✅ `update_decision_info.php`: Kiểm tra vai trò + trạng thái
- ✅ `update_proposal_file.php`: Kiểm tra vai trò + trạng thái
- ✅ `update_contract_info.php`: Kiểm tra vai trò + trạng thái
- ✅ `update_report_info.php`: Kiểm tra vai trò + trạng thái
- ✅ `upload_evaluation_file.php`: Kiểm tra vai trò + trạng thái
- ✅ `update_project_progress.php`: Đã có sẵn, cập nhật thông báo lỗi

### Thông báo người dùng
- **Thành viên không phải chủ nhiệm**: Hiển thị cảnh báo về quyền hạn bị hạn chế
- **Đề tài không đang thực hiện**: Hiển thị thông tin về trạng thái hiện tại
- **Nút bị vô hiệu hóa**: Tooltip giải thích lý do không thể thực hiện

### Kiểm tra an toàn
- Backend validation đảm bảo không thể bypass từ frontend
- Error messages rõ ràng và thông tin đầy đủ
- Session và database checks đầy đủ

### Trạng thái được kiểm tra
- `"Đang thực hiện"`: Cho phép cập nhật (chỉ chủ nhiệm)
- `"Chờ duyệt"`: Không cho phép cập nhật
- Các trạng thái khác: Không cho phép cập nhật

### Vai trò được kiểm tra
- `"Chủ nhiệm"`: Có quyền cập nhật (nếu đề tài đang thực hiện)
- `"Thành viên"` hoặc vai trò khác: Không có quyền cập nhật
