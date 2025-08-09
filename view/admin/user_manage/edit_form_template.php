<input type="hidden" id="userType" name="userType" value="">
<input type="hidden" id="editId" name="editId">

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="editFirstName">Họ</label>
        <input type="text" class="form-control" id="editFirstName" name="editFirstName" required>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="form-group col-md-6">
        <label for="editLastName">Tên</label>
        <input type="text" class="form-control" id="editLastName" name="editLastName" required>
        <div class="invalid-feedback"></div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="editEmail">Email</label>
        <input type="email" class="form-control" id="editEmail" name="editEmail" required>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="form-group col-md-6">
        <label for="editPhone">Số điện thoại</label>
        <input type="text" class="form-control" id="editPhone" name="editPhone">
        <div class="invalid-feedback"></div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="editGender">Giới tính</label>
        <select class="form-control" id="editGender" name="editGender" required>
            <option value="Nam">Nam</option>
            <option value="Nữ">Nữ</option>
        </select>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="form-group col-md-6">
        <label for="editBirthDate">Ngày sinh</label>
        <input type="date" class="form-control" id="editBirthDate" name="editBirthDate">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="editAddress">Địa chỉ</label>
        <input type="text" class="form-control" id="editAddress" name="editAddress">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12 student-only">
        <label for="editClass">Mã lớp</label>
        <select class="form-control" id="editClass" name="editClass">
            <option value="">-- Chọn lớp --</option>
            <?php
            include_once '../../../include/connect.php';
            $sql = "SELECT LOP_MA, LOP_TEN FROM lop";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['LOP_MA']}'>{$row['LOP_MA']} - {$row['LOP_TEN']}</option>";
                }
            }
            ?>
        </select>
    </div>
    
    <div class="form-group col-md-12 teacher-only">
        <label for="editDepartment">Khoa</label>
        <select class="form-control" id="editDepartment" name="editDepartment">
            <option value="">-- Chọn khoa --</option>
            <?php
            // Lấy danh sách khoa từ bảng khoa
            $sql = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($row['DV_MADV']) . "'>" . 
                         htmlspecialchars($row['DV_TENDV']) . "</option>";
                }
            }
            ?>
        </select>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">
        <i class="fas fa-times mr-1"></i> Hủy
    </button>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Cập nhật
    </button>
</div>

<script>
// Trước khi submit form, thêm đoạn code này để debug
$(document).on('submit', '#editForm', function(e) {
    e.preventDefault();
    
    // Debug - kiểm tra giá trị userType trước khi gửi
    console.log('userType value:', $('#userType').val());
    
    // Các phần xử lý khác giữ nguyên
    // ...
});
</script>