/**
 * JavaScript cho trang quản lý khoa
 */

$(document).ready(function () {
    // Cấu hình toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };
    
    // Khởi tạo DataTables cho bảng chính
    var departmentsTable = $('#departmentsTable').DataTable({
        "paging": true,
        "ordering": true,
        "info": true,
        "searching": true,
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50, 100],
        "language": {
            "search": "Tìm kiếm:",
            "lengthMenu": "Hiển thị _MENU_ dòng",
            "info": "Hiển thị _START_ đến _END_ của _TOTAL_ khoa",
            "infoEmpty": "Hiển thị 0 đến 0 của 0 khoa",
            "infoFiltered": "(được lọc từ _MAX_ khoa)",
            "paginate": {
                "first": "Đầu",
                "last": "Cuối",
                "next": "<i class='fas fa-chevron-right'></i>",
                "previous": "<i class='fas fa-chevron-left'></i>"
            },
            "emptyTable": "Không có dữ liệu",
            "zeroRecords": "Không tìm thấy khoa phù hợp"
        },
        "drawCallback": function() {
            // Thêm các hiệu ứng sau khi vẽ bảng
            $('.dataTables_paginate .paginate_button.current').addClass('btn-primary');
        }
    });
    
    // Khởi tạo hiệu ứng
    function initAnimations() {
        // Hiệu ứng fade-in cho bảng
        $('.card').css('opacity', 0).animate({
            opacity: 1
        }, 500);
    }
    
    // Gọi hàm khởi tạo hiệu ứng
    initAnimations();
    
    // Gắn sự kiện cho các nút thêm mới
    $('#addForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'add_department.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.success, 'Thành công!');
                    $('#addModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.error, 'Lỗi!');
                }
            },
            error: function(xhr) {
                console.error("Lỗi AJAX:", xhr.responseText);
                toastr.error('Đã xảy ra lỗi khi thêm khoa.', 'Lỗi!');
            }
        });
    });

    // Tải thông tin khoa khi click nút sửa
    $(document).on('click', '.editBtn', function() {
        let id = $(this).data('id');
        
        // Hiệu ứng nút
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Đang tải');
        $(this).prop('disabled', true);
        
        $.ajax({
            url: 'get_department.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(department) {
                // Khôi phục nút
                $('.editBtn[data-id="'+id+'"]').html('<i class="fas fa-edit"></i> Sửa');
                $('.editBtn[data-id="'+id+'"]').prop('disabled', false);
                
                if (department.error) {
                    toastr.error(department.error, 'Lỗi!');
                } else {
                    $('#editId').val(department.DV_MADV);
                    $('#editName').val(department.DV_TENDV);
                    $('#editModal').modal('show');
                    
                    // Focus vào trường tên sau khi modal hiển thị
                    $('#editModal').on('shown.bs.modal', function() {
                        $('#editName').focus();
                    });
                }
            },
            error: function(xhr) {
                // Khôi phục nút
                $('.editBtn[data-id="'+id+'"]').html('<i class="fas fa-edit"></i> Sửa');
                $('.editBtn[data-id="'+id+'"]').prop('disabled', false);
                
                console.error("Lỗi AJAX:", xhr.responseText);
                toastr.error('Đã xảy ra lỗi khi tải thông tin khoa.', 'Lỗi!');
            }
        });
    });

    // Xử lý form cập nhật khoa
    $('#editForm').submit(function(e) {
        e.preventDefault();
        
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Đang cập nhật').prop('disabled', true);
        
        $.ajax({
            url: 'update_department.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                submitBtn.html('<i class="fas fa-save mr-1"></i> Cập nhật').prop('disabled', false);
                
                if (response.success) {
                    toastr.success(response.success, 'Thành công!');
                    $('#editModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.error, 'Lỗi!');
                }
            },
            error: function(xhr) {
                submitBtn.html('<i class="fas fa-save mr-1"></i> Cập nhật').prop('disabled', false);
                console.error("Lỗi AJAX:", xhr.responseText);
                toastr.error('Đã xảy ra lỗi khi cập nhật khoa.', 'Lỗi!');
            }
        });
    });

    // Xử lý nút xóa
    $(document).on('click', '.deleteBtn', function() {
        let id = $(this).data('id');
        $('#confirmDelete').attr('href', "delete_department.php?id=" + id);
        $('#deleteModal').modal('show');
    });

    // Xử lý nút xem lớp học
    $(document).on('click', '.viewClassesBtn', function() {
        let id = $(this).data('id');
        
        // Hiệu ứng nút
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Đang tải');
        $(this).prop('disabled', true);
        
        $('#viewClassesModal').data('id', id);
        
        // Tải danh sách khóa học
        $.ajax({
            url: 'get_courses.php',
            type: 'GET',
            dataType: 'json',
            success: function(courses) {
                // Khôi phục nút
                $('.viewClassesBtn[data-id="'+id+'"]').html('<i class="fas fa-users"></i> Xem lớp học');
                $('.viewClassesBtn[data-id="'+id+'"]').prop('disabled', false);
                
                $('#selectCourse').empty();
                $('#selectCourse').append('<option value="">Chọn khóa học</option>');
                
                if (courses.length > 0) {
                    $.each(courses, function(index, course) {
                        $('#selectCourse').append('<option value="' + course.KH_NAM + '">' + course.KH_NAM + '</option>');
                    });
                }
                
                // Hiển thị modal
                $('#viewClassesModal').modal('show');
                
                // Reset nội dung bảng lớp
                $('#classesTable tbody').html('<tr><td colspan="4" class="text-center">Vui lòng chọn khóa học</td></tr>');
                
                // Nếu đã khởi tạo DataTables trước đó, hủy bỏ
                if ($.fn.DataTable.isDataTable('#classesTable')) {
                    $('#classesTable').DataTable().destroy();
                }
            },
            error: function(xhr) {
                // Khôi phục nút
                $('.viewClassesBtn[data-id="'+id+'"]').html('<i class="fas fa-users"></i> Xem lớp học');
                $('.viewClassesBtn[data-id="'+id+'"]').prop('disabled', false);
                
                console.error("Lỗi AJAX:", xhr.responseText);
                toastr.error('Đã xảy ra lỗi khi tải danh sách khóa học.', 'Lỗi!');
            }
        });
    });

    // Xử lý khi chọn khóa học
    $('#selectCourse').change(function() {
        let departmentId = $('#viewClassesModal').data('id');
        let courseId = $(this).val();
        
        if (!courseId) {
            $('#classesTable tbody').html('<tr><td colspan="4" class="text-center">Vui lòng chọn khóa học</td></tr>');
            return;
        }
        
        // Hiển thị trạng thái đang tải
        $('#classesTable tbody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu...</td></tr>');
        
        // Tải danh sách lớp học
        $.ajax({
            url: 'get_classes.php',
            type: 'GET',
            data: { departmentId: departmentId, courseId: courseId },
            dataType: 'json',
            success: function(classes) {
                // Hủy bỏ DataTables nếu đã khởi tạo
                if ($.fn.DataTable.isDataTable('#classesTable')) {
                    $('#classesTable').DataTable().destroy();
                }
                
                // Cập nhật dữ liệu bảng
                let tbody = '';
                if (classes.length === 0) {
                    tbody = '<tr><td colspan="4" class="text-center">Không có lớp học nào cho khóa này</td></tr>';
                } else {
                    $.each(classes, function(index, classInfo) {
                        tbody += '<tr>' +
                                '<td>' + classInfo.LOP_MA + '</td>' +
                                '<td>' + classInfo.LOP_TEN + '</td>' +
                                '<td>' + classInfo.KH_NAM + '</td>' +
                                '<td>' +
                                    '<button class="btn btn-info btn-sm viewStudentsBtn" data-id="' + classInfo.LOP_MA + '">' +
                                        '<i class="fas fa-user-graduate mr-1"></i> Xem sinh viên' +
                                    '</button>' +
                                '</td>' +
                            '</tr>';
                    });
                }
                $('#classesTable tbody').html(tbody);
                
                // Khởi tạo DataTables cho bảng lớp học
                $('#classesTable').DataTable({
                    "paging": true,
                    "ordering": true,
                    "info": true,
                    "searching": true,
                    "pageLength": 5,
                    "lengthMenu": [5, 10, 25],
                    "language": {
                        "search": "Tìm kiếm:",
                        "lengthMenu": "Hiển thị _MENU_ dòng",
                        "info": "Hiển thị _START_ đến _END_ của _TOTAL_ lớp",
                        "infoEmpty": "Hiển thị 0 đến 0 của 0 lớp",
                        "infoFiltered": "(được lọc từ _MAX_ lớp)",
                        "paginate": {
                            "first": "Đầu",
                            "last": "Cuối",
                            "next": "<i class='fas fa-chevron-right'></i>",
                            "previous": "<i class='fas fa-chevron-left'></i>"
                        },
                        "emptyTable": "Không có dữ liệu",
                        "zeroRecords": "Không tìm thấy lớp học phù hợp"
                    }
                });
            },
            error: function(xhr) {
                console.error("Lỗi AJAX:", xhr.responseText);
                $('#classesTable tbody').html('<tr><td colspan="4" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>');
                toastr.error('Đã xảy ra lỗi khi tải danh sách lớp học.', 'Lỗi!');
            }
        });
    });

    // Xử lý nút xem sinh viên
    $(document).on('click', '.viewStudentsBtn', function() {
        let classId = $(this).data('id');
        
        // Hiệu ứng nút
        $(this).html('<i class="fas fa-spinner fa-spin"></i>');
        $(this).prop('disabled', true);
        
        $('#viewStudentsModal').data('id', classId);
        
        // Tải danh sách sinh viên
        $.ajax({
            url: 'get_students.php',
            type: 'GET',
            data: { classId: classId },
            dataType: 'json',
            success: function(students) {
                // Khôi phục nút
                $('.viewStudentsBtn[data-id="'+classId+'"]').html('<i class="fas fa-user-graduate mr-1"></i> Xem sinh viên');
                $('.viewStudentsBtn[data-id="'+classId+'"]').prop('disabled', false);
                
                // Hủy bỏ DataTables nếu đã khởi tạo
                if ($.fn.DataTable.isDataTable('#studentsTable')) {
                    $('#studentsTable').DataTable().destroy();
                }
                
                if (students.length === 0) {
                    $('#noStudentsMessage').show();
                    $('#studentsTable').hide();
                } else {
                    $('#noStudentsMessage').hide();
                    $('#studentsTable').show();
                    
                    // Cập nhật dữ liệu bảng
                    let tbody = '';
                    $.each(students, function(index, student) {
                        tbody += '<tr>' +
                                '<td>' + student.SV_MASV + '</td>' +
                                '<td>' + student.SV_HOTEN + '</td>' +
                                '<td>' + student.SV_EMAIL + '</td>' +
                                '<td>' + student.SV_SDT + '</td>' +
                            '</tr>';
                    });
                    $('#studentsTable tbody').html(tbody);
                    
                    // Khởi tạo DataTables cho bảng sinh viên
                    $('#studentsTable').DataTable({
                        "paging": true,
                        "ordering": true,
                        "info": true,
                        "searching": true,
                        "pageLength": 10,
                        "lengthMenu": [5, 10, 25, 50],
                        "language": {
                            "search": "Tìm kiếm:",
                            "lengthMenu": "Hiển thị _MENU_ dòng",
                            "info": "Hiển thị _START_ đến _END_ của _TOTAL_ sinh viên",
                            "infoEmpty": "Hiển thị 0 đến 0 của 0 sinh viên",
                            "infoFiltered": "(được lọc từ _MAX_ sinh viên)",
                            "paginate": {
                                "first": "Đầu",
                                "last": "Cuối",
                                "next": "<i class='fas fa-chevron-right'></i>",
                                "previous": "<i class='fas fa-chevron-left'></i>"
                            },
                            "emptyTable": "Không có dữ liệu",
                            "zeroRecords": "Không tìm thấy sinh viên phù hợp"
                        }
                    });
                }
                
                // Hiển thị modal
                $('#viewStudentsModal').modal('show');
            },
            error: function(xhr) {
                // Khôi phục nút
                $('.viewStudentsBtn[data-id="'+classId+'"]').html('<i class="fas fa-user-graduate mr-1"></i> Xem sinh viên');
                $('.viewStudentsBtn[data-id="'+classId+'"]').prop('disabled', false);
                
                console.error("Lỗi AJAX:", xhr.responseText);
                toastr.error('Đã xảy ra lỗi khi tải danh sách sinh viên.', 'Lỗi!');
            }
        });
    });

    // Reset modal
    $('#addModal').on('hidden.bs.modal', function() {
        $('#addForm')[0].reset();
    });

    $('#editModal').on('hidden.bs.modal', function() {
        $('#editForm')[0].reset();
    });

    $('#viewClassesModal').on('hidden.bs.modal', function() {
        $('#selectCourse').val('');
    });
});