/**
 * JavaScript cho trang quản lý người dùng
 */

$(document).ready(function () {
    // Khởi tạo DataTables cho bảng sinh viên và giảng viên
    $('#studentsTable, #teachersTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        },
        "pageLength": 10
    });
    
    // Gắn sự kiện cho các nút
    attachEventHandlers();
    
    // Lưu tab đang active vào localStorage để giữ nguyên khi tải lại trang
    preserveActiveTab();
    
    /**
     * Gắn sự kiện cho các nút chỉnh sửa, xóa, thêm mới
     */
    function attachEventHandlers() {
        // Nút chỉnh sửa
        $(document).on('click', '.editBtn', function() {
            const userId = $(this).data('id');
            let userType = 'student';
            if ($('#teachers-tab').hasClass('active') || $(this).closest('#teachers').length > 0) {
                userType = 'teacher';
            }
            
            // Lưu userType cho việc sử dụng trong callback
            const currentUserType = userType;
            
            if (userType === 'student') {
                $('.teacher-only').hide();
                $('.student-only').show();
            } else {
                $('.student-only').hide();
                $('.teacher-only').show();
            }
            
            // Khi mở modal sửa giảng viên
            if (userType === 'teacher') {
                $('#editModal').addClass('teacher-mode').removeClass('student-mode');
            } else {
                $('#editModal').addClass('student-mode').removeClass('teacher-mode');
            }
            
            $('#editModalLabel').text(userType === 'student' ? 'Chỉnh sửa thông tin sinh viên' : 'Chỉnh sửa thông tin giảng viên');
            $('#editModal').modal('show');
            $('#editForm').html('<div class="text-center my-5"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-3">Đang tải dữ liệu...</p></div>');
            
            $.ajax({
                url: 'get_user.php',
                type: 'GET',
                data: {
                    userId: userId,
                    userType: userType
                },
                dataType: 'json',
                success: function(response) {
                    console.log("Dữ liệu trả về:", response);
                    $('#editForm').load('edit_form_template.php', function() {
                        // Đảm bảo gán giá trị userType sau khi form đã được tải
                        $('#userType').val(currentUserType);
                        
                        if (userType === 'student') {
                            $('#editId').val(response.SV_MASV);
                            $('#editFirstName').val(response.SV_HOSV);
                            $('#editLastName').val(response.SV_TENSV);
                            $('#editEmail').val(response.SV_EMAIL);
                            $('#editPhone').val(response.SV_SDT);
                            $('#editGender').val(response.SV_GIOITINH === 1 ? 'Nam' : 'Nữ');
                            $('#editBirthDate').val(response.SV_NGAYSINH);
                            $('#editAddress').val(response.SV_DIACHI);
                            $('#editClass').val(response.LOP_MA);
                            $('.teacher-only').hide();
                            $('.student-only').show();
                        } else {
                            $('#editId').val(response.GV_MAGV);
                            $('#editFirstName').val(response.GV_HOGV);
                            $('#editLastName').val(response.GV_TENGV);
                            $('#editEmail').val(response.GV_EMAIL);
                            if (response.GV_SDT) $('#editPhone').val(response.GV_SDT);
                            if (response.GV_GIOITINH !== undefined) {
                                $('#editGender').val(response.GV_GIOITINH === 1 ? 'Nam' : 'Nữ');
                            }
                            $('#editBirthDate').val(response.GV_NGAYSINH);
                            $('#editAddress').val(response.GV_DIACHI);
                            
                            // Đảm bảo hiển thị và đặt giá trị cho dropdown khoa
                            $('#editDepartment').val(response.DV_MADV);
                            
                            // Thêm debug để kiểm tra giá trị
                            console.log("Mã khoa giảng viên:", response.DV_MADV);
                            console.log("Các option có sẵn:", $('#editDepartment option').map(function() {
                                return $(this).val();
                            }).get());
                            
                            // Hiển thị phần dành cho giảng viên và ẩn phần dành cho sinh viên
                            $('.student-only').hide();
                            $('.teacher-only').show();
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", error);
                    console.log("Response:", xhr.responseText);
                    $('#editForm').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i> 
                            Có lỗi xảy ra khi tải thông tin người dùng. Vui lòng thử lại.
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i> Đóng
                            </button>
                        </div>
                    `);
                }
            });
        });
        
        // Nút xóa
        $(document).on('click', '.deleteBtn', function() {
            const userId = $(this).data('id');
            let userType = 'student';
            if ($('#teachers-tab').hasClass('active') || $(this).closest('#teachers').length > 0) {
                userType = 'teacher';
            }
            
            $('#deleteConfirmMessage').text(`Bạn có chắc chắn muốn xóa ${userType === 'student' ? 'sinh viên' : 'giảng viên'} này không?`);
            $('#confirmDelete').data('id', userId).data('type', userType);
            $('#deleteModal').modal('show');
        });
        
        // Xử lý nút xác nhận xóa
        $('#confirmDelete').click(function() {
            const userId = $(this).data('id');
            const userType = $(this).data('type');
            const deleteBtn = $(this);
            const originalBtnText = deleteBtn.html();
            deleteBtn.html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
            deleteBtn.prop('disabled', true);
            
            $.ajax({
                url: 'delete_user.php',
                type: 'POST',
                data: {
                    userId: userId,
                    userType: userType
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        $('#deleteModal').modal('hide');
                        location.reload();
                    } else {
                        alert(response.message);
                        deleteBtn.html(originalBtnText);
                        deleteBtn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", error);
                    console.log("Response:", xhr.responseText);
                    alert('Có lỗi xảy ra khi xóa người dùng. Vui lòng thử lại.');
                    deleteBtn.html(originalBtnText);
                    deleteBtn.prop('disabled', false);
                }
            });
        });
        
        // Xử lý nút thêm mới
        $('#addStudentBtn').click(function() {
            resetAddForm();
            $('.teacher-only').hide();
            $('.student-only').show();
            $('#addUserType').val('student');
            $('#addModalLabel').text('Thêm sinh viên mới');
            $('#addModal').modal('show');
        });
        
        $('#addTeacherBtn').click(function() {
            resetAddForm();
            $('.student-only').hide();
            $('.teacher-only').show();
            $('#addUserType').val('teacher');
            $('#addModalLabel').text('Thêm giảng viên mới');
            $('#addModal').modal('show');
        });
        
        // Xử lý submit form thêm mới
        $('#addForm').on('submit', function(e) {
            e.preventDefault();
            $('.is-invalid').removeClass('is-invalid');
            const formData = new FormData(this);
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
            submitBtn.prop('disabled', true);
            
            $.ajax({
                url: 'add_user.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        $('#addModal').modal('hide');
                        $('#addForm')[0].reset();
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", error);
                    console.log("Response:", xhr.responseText);
                    alert('Có lỗi xảy ra khi thêm người dùng. Vui lòng thử lại.');
                },
                complete: function() {
                    submitBtn.html(originalBtnText);
                    submitBtn.prop('disabled', false);
                }
            });
        });
        
        // Xử lý submit form sửa
        $(document).on('submit', '#editForm', function(e) {
            e.preventDefault();
            $('.is-invalid').removeClass('is-invalid');
            const formData = new FormData(this);
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
            submitBtn.prop('disabled', true);
            
            $.ajax({
                url: 'update_user.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        $('#editModal').modal('hide');
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", error);
                    console.log("Response:", xhr.responseText);
                    alert('Có lỗi xảy ra khi cập nhật người dùng. Vui lòng thử lại.');
                },
                complete: function() {
                    submitBtn.html(originalBtnText);
                    submitBtn.prop('disabled', false);
                }
            });
        });
    }
    
    /**
     * Reset form thêm mới
     */
    function resetAddForm() {
        $('#addForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
    }
    
    /**
     * Lưu và khôi phục tab đang active
     */
    function preserveActiveTab() {
        const activeTab = localStorage.getItem('userManagementActiveTab');
        if (activeTab) {
            $(`#userTabs a[href="${activeTab}"]`).tab('show');
        }
        
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            localStorage.setItem('userManagementActiveTab', $(e.target).attr('href'));
        });
    }
});