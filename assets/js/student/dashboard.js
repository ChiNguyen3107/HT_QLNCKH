/**
 * JavaScript cho trang Dashboard sinh viên
 */

$(document).ready(function() {
    // Khởi tạo tooltip Bootstrap
    $('[data-toggle="tooltip"]').tooltip();
    
    // Hiệu ứng khi trang tải xong
    animateDashboard();
    
    // Khởi tạo đồng hồ đếm ngược
    initCountdown();
    
    /**
     * Hiệu ứng cho dashboard
     */
    function animateDashboard() {
        // Hiệu ứng fade-in cho các thẻ thống kê
        $('.stats-card').each(function(index) {
            $(this).delay(100 * index).animate({
                opacity: 1
            }, 500);
        });
        
        // Hiệu ứng cho bảng đề tài
        $('.projects-card').css('opacity', 0).delay(300).animate({
            opacity: 1
        }, 500);
        
        // Hiệu ứng cho phần thông báo
        $('.announcement-card').each(function(index) {
            $(this).delay(200 * index).animate({
                opacity: 1
            }, 500);
        });
    }
    
    /**
     * Khởi tạo đồng hồ đếm ngược
     */
    function initCountdown() {
        // Kiểm tra xem có phần tử đồng hồ đếm ngược hay không
        if ($('#countdown').length === 0) return;
        
        // Lấy thời gian đích từ dữ liệu
        const targetDate = new Date($('#countdown').data('target'));
        
        // Cập nhật đồng hồ đếm ngược mỗi giây
        setInterval(function() {
            updateCountdown(targetDate);
        }, 1000);
        
        // Cập nhật ban đầu
        updateCountdown(targetDate);
    }
    
    /**
     * Cập nhật đồng hồ đếm ngược
     */
    function updateCountdown(targetDate) {
        const now = new Date();
        const diff = targetDate - now;
        
        // Nếu đã qua thời gian đích
        if (diff <= 0) {
            $('#days').text('0');
            $('#hours').text('0');
            $('#minutes').text('0');
            $('#seconds').text('0');
            return;
        }
        
        // Tính toán thời gian còn lại
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        // Cập nhật giao diện
        $('#days').text(days);
        $('#hours').text(hours);
        $('#minutes').text(minutes);
        $('#seconds').text(seconds);
    }
    
    /**
     * Xử lý sự kiện click vào nút xem chi tiết đề tài
     */
    $('.view-project-btn').click(function() {
        const projectId = $(this).data('id');
        window.location.href = `view_project.php?id=${projectId}`;
    });

    /**
     * Xử lý hiển thị toàn bộ mô tả khi click
     */
    $('.show-more').click(function(e) {
        e.preventDefault();
        
        const descCell = $(this).closest('td');
        const fullText = descCell.data('full-text');
        const shortText = descCell.data('short-text');
        
        if ($(this).hasClass('expanded')) {
            // Thu gọn
            descCell.html(shortText + ' <a href="#" class="show-more">Xem thêm</a>');
            $(this).removeClass('expanded');
        } else {
            // Mở rộng
            descCell.html(fullText + ' <a href="#" class="show-more expanded">Thu gọn</a>');
            $(this).addClass('expanded');
        }
    });
    
    /**
     * Chart.js - Biểu đồ tiến độ đề tài (nếu có)
     */
    if ($('#projectProgressChart').length > 0) {
        const ctx = document.getElementById('projectProgressChart').getContext('2d');
        
        // Dữ liệu từ PHP được truyền qua data attribute
        const data = {
            labels: ['Hoàn thành', 'Đang làm', 'Chưa bắt đầu'],
            datasets: [{
                data: [
                    parseInt($('#projectProgressChart').data('completed')),
                    parseInt($('#projectProgressChart').data('in-progress')),
                    parseInt($('#projectProgressChart').data('not-started'))
                ],
                backgroundColor: ['#1cc88a', '#4e73df', '#858796'],
                hoverBackgroundColor: ['#17a673', '#2e59d9', '#717384'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        };
        
        const options = {
            responsive: true,
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: true,
                position: 'bottom'
            },
            cutoutPercentage: 70,
        };
        
        // Tạo biểu đồ tròn
        new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: options
        });
    }
});