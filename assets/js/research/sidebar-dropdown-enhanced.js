/* Sidebar Dropdown JavaScript Enhancement */
/* Đảm bảo dropdown sidebar hoạt động đúng cách */

// Khởi tạo sidebar dropdown functionality
function initSidebarDropdown() {
    console.log('Initializing sidebar dropdown...');
    
    // Đảm bảo jQuery đã load
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }
    
    // Kiểm tra xem đã được khởi tạo chưa
    if ($('.submenu-toggle').attr('data-dropdown-initialized') === 'true') {
        console.log('Sidebar dropdown already initialized, skipping...');
        return;
    }
    
    // Xóa tất cả event handlers cũ để tránh duplicate
    $('.submenu-toggle').off('click.sidebar');
    
    // Thêm event handler mới với namespace
    $('.submenu-toggle').on('click.sidebar', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Submenu toggle clicked:', this);
        
        const $clickedItem = $(this).closest('.nav-item');
        const wasOpen = $clickedItem.hasClass('open');
        
        // Đóng tất cả submenu khác
        $('.nav-item.has-submenu').removeClass('open');
        $('.nav-item.has-submenu .nav-arrow').css('transform', 'rotate(0deg)');
        
        // Toggle submenu hiện tại
        if (!wasOpen) {
            $clickedItem.addClass('open');
            $clickedItem.find('.nav-arrow').css('transform', 'rotate(90deg)');
            console.log('Submenu opened');
        } else {
            console.log('Submenu closed');
        }
    });
    
    // Đảm bảo active submenu được mở
    $('.nav-item.active.has-submenu').addClass('open');
    $('.nav-item.active.has-submenu .nav-arrow').css('transform', 'rotate(90deg)');
    
    // Thêm cursor pointer cho submenu toggles
    $('.submenu-toggle').css('cursor', 'pointer');
    
    // Đánh dấu đã được khởi tạo
    $('.submenu-toggle').attr('data-dropdown-initialized', 'true');
    
    console.log('Sidebar dropdown initialized successfully');
}

// Khởi tạo khi document ready
$(document).ready(function() {
    // Chỉ khởi tạo một lần khi DOM ready
    if (!window.sidebarDropdownInitialized) {
        setTimeout(function() {
            initSidebarDropdown();
            window.sidebarDropdownInitialized = true;
        }, 200);
    }
});

// Re-initialize nếu có AJAX reload (nhưng check trước)
$(document).ajaxComplete(function() {
    if ($('.submenu-toggle').attr('data-dropdown-initialized') !== 'true') {
        setTimeout(function() {
            initSidebarDropdown();
        }, 100);
    }
});

// Debug function - có thể gọi từ console
window.debugSidebar = function() {
    console.log('=== SIDEBAR DEBUG INFO ===');
    console.log('Submenu toggles found:', $('.submenu-toggle').length);
    console.log('Has-submenu items:', $('.nav-item.has-submenu').length);
    console.log('Open submenus:', $('.nav-item.has-submenu.open').length);
    console.log('jQuery version:', $.fn.jquery);
    
    $('.submenu-toggle').each(function(i) {
        console.log(`Toggle ${i}:`, this, 'Events:', $._data(this, "events"));
    });
};

// Fallback initialization nếu document ready không chạy
window.addEventListener('load', function() {
    setTimeout(function() {
        // Chỉ chạy nếu chưa được khởi tạo
        if ($('.submenu-toggle').length > 0 && 
            $('.submenu-toggle').attr('data-dropdown-initialized') !== 'true' && 
            !window.sidebarDropdownInitialized) {
            console.log('Fallback initialization...');
            initSidebarDropdown();
            window.sidebarDropdownInitialized = true;
        }
    }, 500);
});
