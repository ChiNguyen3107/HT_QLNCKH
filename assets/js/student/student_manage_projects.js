/**
 * JavaScript cho trang quản lý đề tài sinh viên
 */

$(document).ready(function() {
    // Khởi tạo tooltip Bootstrap
    $('[data-toggle="tooltip"]').tooltip();
    
    /**
     * Hiệu ứng loading cho bảng
     */
    function showTableLoading(tableId) {
        $(`#${tableId}`).html(`
            <tr>
                <td colspan="6" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                </td>
            </tr>
        `);
    }
    
    /**
     * Hiển thị thông báo lỗi trong bảng
     */
    function showTableError(tableId, message) {
        $(`#${tableId}`).html(`
            <tr>
                <td colspan="6" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle mr-1"></i> ${message}
                </td>
            </tr>
        `);
    }
    
    /**
     * Hiển thị trạng thái trống trong bảng
     */
    function showEmptyTable(tableId, message) {
        $(`#${tableId}`).html(`
            <tr>
                <td colspan="6" class="text-center">
                    ${message}
                </td>
            </tr>
        `);
    }
    
    /**
     * Hiệu ứng cho các hàng trong bảng
     */
    function setupTableRowEffects() {
        // Highlight hàng khi hover
        $('.table-hover tbody tr').hover(
            function() {
                $(this).addClass('bg-light');
            }, 
            function() {
                $(this).removeClass('bg-light');
            }
        );
        
        // Tooltip cho các nút hành động
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    /**
     * Tải danh sách đề tài gợi ý
     */
    function loadSuggestedProjects(search = '') {
        showTableLoading('suggestedProjects');
        
        $.ajax({
            url: 'get_suggested_projects.php',
            type: 'GET',
            data: { search: search },
            dataType: 'json',
            success: function(data) {
                var html = '';
                if (data.length > 0) {
                    $.each(data, function(index, project) {
                        var statusClass = getStatusBadgeClass(project.DT_TRANGTHAI);
                        
                        html += `
                            <tr>
                                <td>${project.DT_MADT}</td>
                                <td>${project.DT_TENDT}</td>
                                <td>${project.GV_HOTEN || 'Chưa có GVHD'}</td>
                                <td>${project.LDT_TENLOAI}</td>
                                <td><span class="badge ${statusClass}">${project.DT_TRANGTHAI}</span></td>
                                <td>
                                    <div class="btn-group-sm">
                                        <a href="view_project.php?id=${project.DT_MADT}" class="btn btn-sm btn-info mb-1" 
                                           data-toggle="tooltip" title="Xem chi tiết đề tài">
                                            <i class="fas fa-eye"></i> Xem
                                        </a> 
                                        <button class="btn btn-sm btn-success mb-1 btn-register-project"
                                               data-toggle="tooltip" title="Đăng ký tham gia đề tài"
                                               data-id="${project.DT_MADT}" 
                                               data-title="${project.DT_TENDT}">
                                            <i class="fas fa-check"></i> Đăng ký
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = `
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-search"></i>
                                    <p>Không có đề tài nào phù hợp với tìm kiếm</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }
                $('#suggestedProjects').html(html);
                
                // Thiết lập hiệu ứng sau khi tải và đăng ký sự kiện
                setupTableRowEffects();
                setupRegisterButtons();
            },
            error: function(xhr, status, error) {
                console.error('Error loading projects:', error);
                showTableError('suggestedProjects', 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.');
            }
        });
    }
    
    /**
     * Thiết lập các nút đăng ký đề tài
     */
    function setupRegisterButtons() {
        $('.btn-register-project').off('click').on('click', function() {
            var projectId = $(this).data('id');
            var projectTitle = $(this).data('title');
            
            // Hiển thị modal xác nhận
            $('#confirmProjectId').val(projectId);
            $('#confirmProjectTitle').text(projectTitle);
            $('#registerConfirmModal').modal('show');
        });
    }
    
    /**
     * Xác định class cho badge trạng thái
     */
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'Chờ duyệt': return 'badge-warning';
            case 'Đang thực hiện': return 'badge-primary';
            case 'Đã hoàn thành': return 'badge-success';
            case 'Tạm dừng': return 'badge-info';
            case 'Đã hủy': return 'badge-danger';
            default: return 'badge-secondary';
        }
    }
    
    /**
     * Tải các đề tài được đề xuất dựa trên lĩnh vực quan tâm
     */
    function loadRecommendedProjects() {
        $.ajax({
            url: 'get_recommended_projects.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.length > 0) {
                    var html = '';
                    $.each(data, function(index, project) {
                        if (index >= 3) return false; // Chỉ hiển thị 3 đề tài gợi ý đầu tiên
                        
                        html += `
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 project-card">
                                    <div class="card-body">
                                        <h5 class="card-title">${project.DT_TENDT}</h5>
                                        <p class="card-text text-muted small">${project.LDT_TENLOAI}</p>
                                        <p class="card-text">
                                            ${project.DT_MOTA ? (project.DT_MOTA.length > 100 ? project.DT_MOTA.substring(0, 100) + '...' : project.DT_MOTA) : 'Không có mô tả'}
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                                        <span class="badge ${getStatusBadgeClass(project.DT_TRANGTHAI)}">${project.DT_TRANGTHAI}</span>
                                        <a href="view_project.php?id=${project.DT_MADT}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-info-circle mr-1"></i> Chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    $('#recommendedProjects').html(html);
                    $('#recommendedProjectsSection').show();
                } else {
                    $('#recommendedProjectsSection').hide();
                }
            },
            error: function() {
                $('#recommendedProjectsSection').hide();
            }
        });
    }
    
    // Tải dữ liệu ban đầu
    loadSuggestedProjects();
    loadRecommendedProjects();
    
    // Xử lý tìm kiếm với debounce
    var searchTimeout;
    $('#searchProject').on('input', function() {
        clearTimeout(searchTimeout);
        var searchValue = $(this).val();
        
        // Hiển thị icon loading trong ô tìm kiếm
        if (searchValue.length > 0) {
            if (!$(this).next('.search-icon').length) {
                $(this).after('<span class="search-icon"><i class="fas fa-spinner fa-spin"></i></span>');
            }
        } else {
            $('.search-icon').remove();
        }
        
        searchTimeout = setTimeout(function() {
            loadSuggestedProjects(searchValue);
            // Xóa icon loading
            $('.search-icon').remove();
        }, 500);
    });
    
    // Xử lý form đăng ký khi submit
    $('#registerProjectForm').on('submit', function(e) {
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang đăng ký...');
        submitBtn.prop('disabled', true);
        
        // Lưu lại tham số tìm kiếm hiện tại để tải lại sau khi đăng ký
        var currentSearch = $('#searchProject').val();
        
        // Gửi form qua AJAX để không reload trang
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('#registerConfirmModal').modal('hide');
                
                if (response.success) {
                    // Hiển thị thông báo thành công
                    showNotification('success', response.message || 'Đăng ký đề tài thành công!');
                    
                    // Tải lại danh sách đề tài của tôi và danh sách đề tài gợi ý
                    loadMyProjects();
                    loadSuggestedProjects(currentSearch);
                } else {
                    // Hiển thị thông báo lỗi
                    showNotification('danger', response.message || 'Có lỗi xảy ra khi đăng ký đề tài.');
                }
                
                // Reset trạng thái nút submit
                submitBtn.html('Xác nhận đăng ký');
                submitBtn.prop('disabled', false);
            },
            error: function() {
                $('#registerConfirmModal').modal('hide');
                showNotification('danger', 'Có lỗi xảy ra khi xử lý yêu cầu.');
                
                // Reset trạng thái nút submit
                submitBtn.html('Xác nhận đăng ký');
                submitBtn.prop('disabled', false);
            }
        });
    });
    
    /**
     * Hiển thị thông báo
     */
    function showNotification(type, message) {
        // Xóa thông báo cũ
        $('.alert-notification').remove();
        
        // Tạo thông báo mới
        const notification = $(`
            <div class="alert alert-${type} alert-dismissible fade show alert-notification" role="alert">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-1"></i> 
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `);
        
        // Thêm vào đầu container
        $('.container-fluid').prepend(notification);
        
        // Tự động đóng thông báo sau 5 giây
        setTimeout(function() {
            notification.alert('close');
        }, 5000);
        
        // Cuộn lên đầu trang để hiển thị thông báo
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    }
    
    /**
     * Tải danh sách đề tài của tôi
     */
    function loadMyProjects() {
        $.ajax({
            url: 'get_my_projects.php',
            type: 'GET',
            success: function(data) {
                // Làm mới nội dung bảng đề tài của tôi
                // Nếu giá trị trả về là HTML thì thay thế trực tiếp
                $('#myProjectsTable').html(data);
                
                // Thiết lập lại các hiệu ứng
                setupTableRowEffects();
            },
            error: function() {
                console.error('Không thể tải danh sách đề tài của tôi');
            }
        });
    }
    
    // Hiệu ứng khi trang tải xong
    animatePageLoad();
    
    /**
     * Hiệu ứng khi tải trang
     */
    function animatePageLoad() {
        $('.page-header').css('opacity', 0).animate({
            opacity: 1
        }, 500);
        
        $('.card').each(function(index) {
            $(this).css('opacity', 0).delay(100 * index).animate({
                opacity: 1
            }, 500);
        });
    }
    
    // Khởi tạo tooltip
    $('[data-toggle="tooltip"]').tooltip();
    
    // Khởi tạo sự kiện cho các nút đăng ký có sẵn
    setupRegisterButtons();
});