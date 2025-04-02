/**
 * JavaScript cho trang quản lý người dùng
 */

$(document).ready(function () {
    // Khởi tạo DataTables cho bảng sinh viên và giảng viên
    initDataTables();
    
    // Gắn sự kiện cho các nút
    attachEventHandlers();
    
    // Lưu tab đang active vào localStorage để giữ nguyên khi tải lại trang
    preserveActiveTab();
    
    /**
     * Khởi tạo các bảng dữ liệu với DataTables
     */
    function initDataTables() {
        const dataTablesConfig = {
            "paging": true,
            "ordering": true,
            "info": true,
            "searching": true,
            "pageLength": 10,
            "lengthMenu": [5, 10, 25, 50, 100],
            "language": {
                "search": "Tìm kiếm:",
                "lengthMenu": "Hiển thị _MENU_ dòng",
                "info": "Hiển thị _START_ đến _END_ của _TOTAL_ người dùng",
                "infoEmpty": "Hiển thị 0 đến 0 của 0 người dùng",
                "infoFiltered": "(được lọc từ _MAX_ người dùng)",
                "paginate": {
                    "first": "Đầu",
                    "last": "Cuối",
                    "next": "Tiếp",
                    "previous": "Trước"
                },
                "emptyTable": "Không có dữ liệu",
                "zeroRecords": "Không tìm thấy kết quả phù hợp"
            },
            "drawCallback": function() {
                // Gắn lại các sự kiện sau khi DataTables cập nhật
                attachEventHandlers();
            }
        };
        
        // Khởi tạo bảng sinh viên
        $('#studentsTable').DataTable(dataTablesConfig);
        
        // Khởi tạo bảng giảng viên
        $('#teachersTable').DataTable(dataTablesConfig);
    }
    
    /**
     * Gắn sự kiện cho các nút chỉnh sửa và xóa
     */
    function attachEventHandlers() {
        // Nút chỉnh sửa
        $('.editBtn').off('click').on('click', function() {
            const id = $(this).data('id');
            const isStudent = $(this).closest('table').attr('id') === 'studentsTable';
            
            // Hiệu ứng loading
            $(this).html('<i class="fas fa-spinner fa-spin"></i>');
            $(this).prop('disabled', true);
            
            // Điều chỉnh modal dựa trên loại người dùng
            if (isStudent) {
                $('#editModalLabel').text('Chỉnh sửa thông tin sinh viên');
                $('.teacher-only').hide();
                $('.student-only').show();
            } else {
                $('#editModalLabel').text('Chỉnh sửa thông tin giảng viên');
                $('.student-only').hide();
                $('.teacher-only').show();
            }
            
            // Lấy thông tin người dùng qua AJAX
            $.ajax({
                url: 'get_user.php',
                type: 'GET',
                data: { id: id, type: isStudent ? 'student' : 'teacher' },
                dataType: 'json',
                success: function(response) {
                    // Khôi phục nút
                    $('.editBtn[data-id="' + id + '"]').html('Sửa').prop('disabled', false);
                    
                    if (response.error) {
                        showAlert('error', response.error);
                        return;
                    }
                    
                    // Điền thông tin vào form
                    fillEditForm(response, isStudent);
                    
                    // Hiển thị modal
                    $('#editModal').modal('show');
                    
                    // Focus vào trường đầu tiên
                    $('#editFirstName').focus();
                },
                error: function(xhr, status, error) {
                    // Khôi phục nút
                    $('.editBtn[data-id="' + id + '"]').html('Sửa').prop('disabled', false);
                    
                    console.error("AJAX error:", error);
                    console.error("Response:", xhr.responseText);
                    showAlert('error', 'Đã xảy ra lỗi khi tải thông tin người dùng.');
                }
            });
        });
        
        // Nút xóa
        $('.deleteBtn').off('click').on('click', function() {
            const id = $(this).data('id');
            const isStudent = $(this).closest('table').attr('id') === 'studentsTable';
            
            // Cập nhật URL xóa và thông báo dựa trên loại người dùng
            $('#confirmDelete').attr('href', "delete_user.php?id=" + id + "&type=" + (isStudent ? 'student' : 'teacher'));
            $('#deleteConfirmMessage').text(`Bạn có chắc chắn muốn xóa ${isStudent ? 'sinh viên' : 'giảng viên'} này không?`);
            
            // Hiển thị modal xác nhận
            $('#deleteModal').modal('show');
        });
        
        // Xử lý submit form chỉnh sửa
        $('#editForm').submit(function(e) {
            e.preventDefault();
            
            // Hiệu ứng loading
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Đang cập nhật').prop('disabled', true);
            
            // Dữ liệu form bao gồm cả loại người dùng
            const formData = $(this).serialize();
            
            $.ajax({
                url: 'update_user.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    // Khôi phục nút
                    submitBtn.html('Cập nhật').prop('disabled', false);
                    
                    if (response.success) {
                        $('#editModal').modal('hide');
                        showAlert('success', response.success);
                        
                        // Tải lại trang sau 1 giây
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else if (response.error) {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr, status, error) {
                    // Khôi phục nút
                    submitBtn.html('Cập nhật').prop('disabled', false);
                    
                    console.error("AJAX error:", error);
                    console.error("Response:", xhr.responseText);
                    showAlert('error', 'Đã xảy ra lỗi khi cập nhật thông tin người dùng.');
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
        $('#addForm').submit(function(e) {
            e.preventDefault();
            
            // Hiệu ứng loading
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Đang thêm').prop('disabled', true);
            
            $.ajax({
                url: 'add_user.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    // Khôi phục nút
                    submitBtn.html('Thêm mới').prop('disabled', false);
                    
                    if (response.success) {
                        $('#addModal').modal('hide');
                        showAlert('success', response.success);
                        
                        // Tải lại trang sau 1 giây
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else if (response.error) {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr, status, error) {
                    // Khôi phục nút
                    submitBtn.html('Thêm mới').prop('disabled', false);
                    
                    console.error("AJAX error:", error);
                    console.error("Response:", xhr.responseText);
                    showAlert('error', 'Đã xảy ra lỗi khi thêm người dùng mới.');
                }
            });
        });
    }
    
    /**
     * Điền thông tin vào form chỉnh sửa
     */
    function fillEditForm(user, isStudent) {
        if (isStudent) {
            // Trường hợp sinh viên
            $('#editId').val(user.SV_MASV);
            $('#editFirstName').val(user.SV_HOSV);
            $('#editLastName').val(user.SV_TENSV);
            $('#editEmail').val(user.SV_EMAIL);
            $('#editPhone').val(user.SV_SDT || '');
            $('#editAddress').val(user.SV_DIACHI || '');
            $('#editBirthDate').val(formatDate(user.SV_NGAYSINH) || '');
            $('#editGender').val(user.SV_GIOITINH === 0 ? 'Nam' : 'Nữ');
            $('#editClass').val(user.LOP_MA || '');
            $('#userType').val('student');
        } else {
            // Trường hợp giảng viên
            $('#editId').val(user.GV_MAGV);
            $('#editFirstName').val(user.GV_HOGV);
            $('#editLastName').val(user.GV_TENGV);
            $('#editEmail').val(user.GV_EMAIL);
            $('#editPhone').val(user.GV_SDT || '');
            $('#editAddress').val(user.GV_DIACHI || '');
            $('#editBirthDate').val(formatDate(user.GV_NGAYSINH) || '');
            $('#editGender').val(user.GV_GIOITINH === 0 ? 'Nam' : 'Nữ');
            $('#editDepartment').val(user.DV_MADV || '');
            $('#userType').val('teacher');
        }
    }
    
    /**
     * Reset form thêm mới
     */
    function resetAddForm() {
        $('#addForm')[0].reset();
        $('#addForm .is-invalid').removeClass('is-invalid');
        $('#addForm .invalid-feedback').html('');
    }
    
    /**
     * Định dạng ngày tháng từ MySQL sang Y-m-d cho input date
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        
        // Nếu đã đúng định dạng Y-m-d
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
            return dateString;
        }
        
        // Chuyển đổi từ định dạng MySQL
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        return `${year}-${month}-${day}`;
    }
    
    /**
     * Lưu và khôi phục tab đang active
     */
    function preserveActiveTab() {
        // Khôi phục tab đã chọn trước đó
        const activeTab = localStorage.getItem('userManagementActiveTab');
        if (activeTab) {
            $(`#userTabs a[href="${activeTab}"]`).tab('show');
        }
        
        // Lưu tab khi chuyển tab
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            localStorage.setItem('userManagementActiveTab', $(e.target).attr('href'));
        });
    }
    
    /**
     * Hiển thị thông báo
     */
    function showAlert(type, message) {
        if (type === 'success') {
            alert(message); // Thay bằng thư viện thông báo đẹp hơn như toastr nếu có
        } else {
            alert(message);
        }
    }
    
    // Khởi tạo validation cho các form
    function initFormValidation() {
        // Thêm validation cho các trường bắt buộc
        $('input[required], select[required]').on('input change', function() {
            if ($(this).val().trim() === '') {
                $(this).addClass('is-invalid');
                $(this).next('.invalid-feedback').html('Trường này là bắt buộc');
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').html('');
            }
        });
        
        // Validation email
        $('input[type="email"]').on('input', function() {
            const email = $(this).val().trim();
            const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            
            if (email !== '' && !emailRegex.test(email)) {
                $(this).addClass('is-invalid');
                $(this).next('.invalid-feedback').html('Email không hợp lệ');
            } else if (email === '') {
                $(this).addClass('is-invalid');
                $(this).next('.invalid-feedback').html('Email là bắt buộc');
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').html('');
            }
        });
        
        // Validation số điện thoại
        $('input#editPhone, input#addPhone').on('input', function() {
            const phone = $(this).val().trim();
            const phoneRegex = /^[0-9]{10,11}$/;
            
            if (phone !== '' && !phoneRegex.test(phone)) {
                $(this).addClass('is-invalid');
                $(this).next('.invalid-feedback').html('Số điện thoại phải có 10-11 chữ số');
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').html('');
            }
        });
    }
    
    // Khởi tạo validation
    initFormValidation();
    
    // Hiển thị animation khi tải trang
    function initAnimations() {
        $('.tab-content').css('opacity', 0).animate({
            opacity: 1
        }, 500);
    }
    
    // Khởi tạo animation
    initAnimations();
});