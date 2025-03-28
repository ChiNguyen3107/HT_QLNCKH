$(document).ready(function () {
    $('#studentsTable, #teachersTable').DataTable({
        "paging": true,  // Phân trang
        "ordering": true, // Cho phép sắp xếp
        "info": false,    // Ẩn thông tin tổng số dòng
        "searching": true, // Bật tìm kiếm
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50, 100],
        "language": {
            "search": "Tìm kiếm:",
            "lengthMenu": "Hiển thị _MENU_ dòng",
            "paginate": {
                "first": "Đầu",
                "last": "Cuối",
                "next": "Tiếp",
                "previous": "Trước"
            }
        }
    });

    $('.editBtn').click(function () {
        let id = $(this).data('id');
        $.ajax({
            url: 'get_user.php',
            type: 'GET',
            data: { id: id },
            success: function (response) {
                try {
                    let user = JSON.parse(response);
                    if (user.error) {
                        alert(user.error);
                    } else {
                        $('#editId').val(user.SV_MASV || user.GV_MAGV);
                        $('#editFirstName').val(user.SV_HOSV || user.GV_HOGV);
                        $('#editLastName').val(user.SV_TENSV || user.GV_TENGV);
                        $('#editEmail').val(user.SV_EMAIL || user.GV_EMAIL);
                        $('#editPhone').val(user.SV_SDT || user.GV_SDT);
                        $('#editAddress').val(user.SV_DIACHI || user.GV_DIACHI);
                        $('#editBirthDate').val(user.SV_NGAYSINH || user.GV_NGAYSINH);
                        $('#editGender').val(user.SV_GIOITINH || user.GV_GIOITINH);
                        $('#editClass').val(user.LOP_MA || '');
                        $('#editModal').modal('show');
                    }
                } catch (e) {
                    console.error("Lỗi phân tích JSON: ", e);
                    console.error("Phản hồi từ máy chủ: ", response);
                    alert("Đã xảy ra lỗi khi phân tích phản hồi từ máy chủ.");
                }
            },
            error: function (xhr, status, error) {
                alert("Đã xảy ra lỗi: " + error);
            }
        });
    });

    $('.deleteBtn').click(function () {
        let id = $(this).data('id');
        $('#confirmDelete').attr('href', "delete_user.php?id=" + id);
        $('#deleteModal').modal('show');
    });

    $('#editForm').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: 'update_user.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                location.reload();
            },
            error: function (xhr, status, error) {
                alert("Đã xảy ra lỗi: " + error);
            }
        });
    });
});