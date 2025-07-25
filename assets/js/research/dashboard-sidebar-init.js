/**
 * dashboard-sidebar-init.js
 * JavaScript đặc biệt để khởi tạo sidebar cố định cho trang dashboard
 */

// Check if jQuery is loaded before executing
(function() {
    function initializeSidebar() {
        if (typeof $ === 'undefined') {
            // If jQuery is not loaded, try again after a short delay
            setTimeout(initializeSidebar, 100);
            return;
        }
        
        $(document).ready(function() {
            // Đảm bảo sidebar luôn ở trạng thái cố định cho dashboard
            $('.modern-sidebar').addClass('fixed-sidebar');
            
            // Đảm bảo nút toggle được hiển thị đúng
            $('.sidebar-collapse-toggle').addClass('visible-toggle');
            
            // Khởi tạo lại trạng thái của sidebar
            const sidebarState = localStorage.getItem('sidebar-collapsed');
            if (sidebarState === 'true') {
                $('body').addClass('sidebar-toggled');
            }
            
            // Ghi đè hành vi của nút toggle để đảm bảo hoạt động chính xác
            $('.sidebar-collapse-toggle').off('click').on('click', function() {
                $('body').toggleClass('sidebar-toggled');
                const isCollapsed = $('body').hasClass('sidebar-toggled');
                localStorage.setItem('sidebar-collapsed', isCollapsed);
            });
            
            // Kiểm tra và sửa các vấn đề về margin
            checkContentMargin();
            
            function checkContentMargin() {
                if ($('body').hasClass('sidebar-toggled')) {
                    $('#content-wrapper').css('margin-left', '60px');
                    $('#content-wrapper').css('width', 'calc(100% - 60px)');
                } else {
                    $('#content-wrapper').css('margin-left', '260px');
                    $('#content-wrapper').css('width', 'calc(100% - 260px)');
                }
                
                // Kiểm tra trên mobile
                if ($(window).width() < 992) {
                    $('#content-wrapper').css('margin-left', '0');
                    $('#content-wrapper').css('width', '100%');
                }
            }
            
            // Kiểm tra lại khi resize
            $(window).resize(function() {
                checkContentMargin();
            });
        });
    }
    
    // Start the initialization process
    initializeSidebar();
})();
