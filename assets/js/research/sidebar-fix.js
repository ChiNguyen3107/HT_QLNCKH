/**
 * sidebar-fix.js
 * JavaScript để khắc phục và cải thiện hành vi sidebar cố định
 */

$(document).ready(function() {
    // Đảm bảo sidebar luôn được hiển thị trên desktop
    const windowWidth = $(window).width();
    if (windowWidth >= 992) {
        $('.modern-sidebar').addClass('fixed-sidebar');
        
        // Lưu trạng thái sidebar trong localStorage
        const sidebarState = localStorage.getItem('sidebar-collapsed');
        if (sidebarState === 'true') {
            $('body').addClass('sidebar-toggled');
        }
    }
    
    // Ghi đè hành vi của nút toggle sidebar
    $('.sidebar-collapse-toggle').off('click').on('click', function() {
        $('body').toggleClass('sidebar-toggled');
        
        // Lưu trạng thái vào localStorage
        const isCollapsed = $('body').hasClass('sidebar-toggled');
        localStorage.setItem('sidebar-collapsed', isCollapsed);
    });
    
    // Xử lý nút hiện/ẩn sidebar trên mobile
    $('.toggle-sidebar-btn').off('click').on('click', function() {
        $('.modern-sidebar').toggleClass('show');
    });
    
    // Đóng sidebar khi click ra ngoài trên mobile
    $(document).on('click', function(e) {
        const windowWidth = $(window).width();
        const sidebar = $('.modern-sidebar');
        const toggleBtn = $('.toggle-sidebar-btn');
        
        // Chỉ áp dụng cho mobile
        if (windowWidth < 992 && 
            !sidebar.is(e.target) && 
            sidebar.has(e.target).length === 0 && 
            !toggleBtn.is(e.target)) {
            sidebar.removeClass('show');
        }
    });
    
    // Xử lý khi thay đổi kích thước màn hình
    $(window).resize(function() {
        const windowWidth = $(window).width();
        if (windowWidth >= 992) {
            // Trên desktop, đảm bảo sidebar được cố định và hiển thị
            $('.modern-sidebar').removeClass('show').addClass('fixed-sidebar');
        } else {
            // Trên mobile, tùy thuộc vào việc có đang hiển thị hay không
            $('.modern-sidebar').removeClass('fixed-sidebar');
            if (!$('.modern-sidebar').hasClass('show')) {
                $('.modern-sidebar').css('transform', 'translateX(-100%)');
            }
        }
    });
});
