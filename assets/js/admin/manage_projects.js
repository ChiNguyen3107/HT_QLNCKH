/**
 * JavaScript cho trang quản lý đề tài
 */

$(document).ready(function () {
    // Biến lưu trạng thái hiện tại
    var currentPage = 1; // Mặc định trang 1
    var searchValue = '';
    var status = '';
    var type = '';
    var department = '';

    // Khởi tạo thông tin phân trang từ dữ liệu HTML
    initPagination();

    // Khởi tạo các bộ lọc từ tham số URL (nếu có)
    initFiltersFromUrl();

    // Hàm khởi tạo phân trang
    function initPagination() {
        // Lấy số trang hiện tại từ phân tử active
        var activePage = $('.pagination .page-item.active .page-link').data('page');
        if (activePage) {
            currentPage = activePage;
        }

        // Gắn sự kiện cho các nút phân trang
        $('.page-link').click(function (e) {
            e.preventDefault();
            var page = $(this).data('page');
            if (page && !$(this).parent().hasClass('disabled')) {
                currentPage = page;
                loadData(page);
            }
        });
    }

    // Khởi tạo bộ lọc từ URL
    function initFiltersFromUrl() {
        var urlParams = new URLSearchParams(window.location.search);
        
        // Lấy tham số trạng thái từ URL
        var statusParam = urlParams.get('status');
        if (statusParam) {
            $('#filterStatus').val(decodeURIComponent(statusParam));
            status = decodeURIComponent(statusParam);
        }
        
        // Lấy tham số trang từ URL
        var pageParam = urlParams.get('page');
        if (pageParam) {
            currentPage = parseInt(pageParam);
        }
        
        // Lấy tham số loại đề tài từ URL
        var typeParam = urlParams.get('type');
        if (typeParam) {
            $('#filterType').val(decodeURIComponent(typeParam));
            type = decodeURIComponent(typeParam);
        }
        
        // Lấy tham số khoa từ URL
        var deptParam = urlParams.get('department');
        if (deptParam) {
            $('#filterDepartment').val(decodeURIComponent(deptParam));
            department = decodeURIComponent(deptParam);
        }
        
        // Lấy tham số tìm kiếm từ URL
        var searchParam = urlParams.get('search');
        if (searchParam) {
            $('#searchProject').val(decodeURIComponent(searchParam));
            searchValue = decodeURIComponent(searchParam);
        }
    }

    // Hàm tải dữ liệu với AJAX
    function loadData(page) {
        // Hiển thị indicator loading
        $('tbody').html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</td></tr>');

        $.ajax({
            url: 'filter_projects.php',
            type: 'POST',
            data: {
                search: searchValue,
                status: status,
                type: type,
                department: department,
                page: page
            },
            success: function (response) {
                $('tbody').html(response);

                // Lấy thông tin phân trang từ phản hồi
                var paginationInfo = $('.pagination-info');
                if (paginationInfo.length > 0) {
                    var totalItems = paginationInfo.find('td').data('total');
                    var totalPages = paginationInfo.find('td').data('pages');
                    var currentPage = paginationInfo.find('td').data('current');

                    console.log("Thông tin phân trang:", { total: totalItems, pages: totalPages, current: currentPage });

                    // Cập nhật thông tin hiển thị
                    updatePaginationInfo(totalItems, currentPage);

                    // Cập nhật các nút phân trang
                    updatePaginationButtons(totalPages, currentPage);
                    
                    // Cập nhật URL để có thể bookmark hoặc chia sẻ kết quả tìm kiếm
                    updateUrl(currentPage);
                } else {
                    console.log("Không tìm thấy thông tin phân trang trong phản hồi");
                }

                // Cập nhật lại sự kiện xóa
                bindDeleteButtons();
            },
            error: function (xhr, status, error) {
                console.error("AJAX error:", error);
                $('tbody').html('<tr><td colspan="6" class="text-center text-danger">Có lỗi xảy ra khi tải dữ liệu: ' + error + '</td></tr>');
            }
        });
    }

    // Cập nhật thông tin hiển thị phân trang (số hiển thị/tổng số)
    function updatePaginationInfo(totalItems, page) {
        var itemsPerPage = 10;
        var offset = (page - 1) * itemsPerPage;
        var showing = Math.min(offset + itemsPerPage, totalItems);
        if (totalItems > 0) {
            $('.pagination-display').html('Hiển thị ' + (offset + 1) + '-' + showing + ' của ' + totalItems + ' đề tài');
        } else {
            $('.pagination-display').html('Không có đề tài nào');
        }
    }

    // Cập nhật các nút phân trang
    function updatePaginationButtons(totalPages, currentPage) {
        console.log("Cập nhật nút phân trang:", { pages: totalPages, current: currentPage });

        var paginationEl = $('.pagination');
        paginationEl.empty();

        // Nút Previous
        paginationEl.append('<li class="page-item ' + (currentPage <= 1 ? 'disabled' : '') + '">' +
            '<a class="page-link page-nav" href="#" data-page="' + (currentPage - 1) + '">Trước</a></li>');

        // Tính toán phạm vi các trang hiển thị
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);

        // Hiển thị trang đầu
        if (startPage > 1) {
            paginationEl.append('<li class="page-item"><a class="page-link page-number" href="#" data-page="1">1</a></li>');
            if (startPage > 2) {
                paginationEl.append('<li class="page-item disabled"><a class="page-link" href="#">...</a></li>');
            }
        }

        // Hiển thị các trang ở giữa
        for (var i = startPage; i <= endPage; i++) {
            paginationEl.append('<li class="page-item ' + (i == currentPage ? 'active' : '') + '">' +
                '<a class="page-link page-number" href="#" data-page="' + i + '">' + i + '</a></li>');
        }

        // Hiển thị trang cuối
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationEl.append('<li class="page-item disabled"><a class="page-link" href="#">...</a></li>');
            }
            paginationEl.append('<li class="page-item"><a class="page-link page-number" href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>');
        }

        // Nút Next
        paginationEl.append('<li class="page-item ' + (currentPage >= totalPages ? 'disabled' : '') + '">' +
            '<a class="page-link page-nav" href="#" data-page="' + (currentPage + 1) + '">Sau</a></li>');

        // Gán sự kiện click cho các nút phân trang
        $('.page-link').click(function (e) {
            e.preventDefault();
            var page = $(this).data('page');
            if (page && !$(this).parent().hasClass('disabled')) {
                currentPage = page;
                loadData(page);
            }
        });
    }

    // Cập nhật URL với tham số hiện tại
    function updateUrl(page) {
        var url = new URL(window.location.href);
        var params = new URLSearchParams(url.search);
        
        // Cập nhật tham số
        if (page) params.set('page', page);
        if (searchValue) params.set('search', searchValue);
        else params.delete('search');
        
        if (status) params.set('status', status);
        else params.delete('status');
        
        if (type) params.set('type', type);
        else params.delete('type');
        
        if (department) params.set('department', department);
        else params.delete('department');
        
        // Cập nhật URL mà không làm mới trang
        window.history.replaceState({}, '', url.pathname + '?' + params.toString());
    }

    // Gán sự kiện cho nút Delete
    function bindDeleteButtons() {
        $('.btn-delete').click(function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            $('#confirmDelete').attr('href', 'delete_project.php?id=' + id);
            $('#deleteModal').modal('show');
        });
    }

    // Khởi tạo: gán sự kiện xóa cho các nút
    bindDeleteButtons();

    // Xử lý khi thay đổi các trường tìm kiếm và lọc
    $('#searchProject').on('keyup', function(e) {
        if (e.key === 'Enter' || searchValue !== $(this).val()) {
            searchValue = $(this).val();
            currentPage = 1;
            loadData(currentPage);
        }
    });
    
    // Xử lý nút tìm kiếm
    $('.input-group-append button').click(function() {
        searchValue = $('#searchProject').val();
        currentPage = 1;
        loadData(currentPage);
    });

    // Xử lý khi thay đổi các bộ lọc
    $('#filterStatus, #filterType, #filterDepartment').change(function() {
        status = $('#filterStatus').val();
        type = $('#filterType').val();
        department = $('#filterDepartment').val();

        // Reset về trang 1 khi lọc
        currentPage = 1;
        loadData(currentPage);
    });

    // Reset bộ lọc
    $('#resetFilters').click(function() {
        $('#searchProject').val('');
        $('#filterStatus, #filterType, #filterDepartment').val('');

        searchValue = '';
        status = '';
        type = '';
        department = '';

        // Reset về trang 1
        currentPage = 1;
        loadData(currentPage);
    });
    
    // Hiệu ứng animation cho page load
    function initAnimations() {
        $('.card').css('opacity', 0).animate({
            opacity: 1
        }, 500);
    }
    
    // Gọi hàm khởi tạo hiệu ứng
    initAnimations();
});