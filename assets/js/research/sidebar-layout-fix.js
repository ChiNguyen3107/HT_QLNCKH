/**
 * sidebar-layout-fix.js
 * JavaScript để đảm bảo sidebar hoạt động đúng và không đè lên nội dung
 */

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo các phần tử
    const body = document.body;
    const sidebar = document.querySelector('.modern-sidebar');
    const sidebarCollapseToggle = document.querySelector('.sidebar-collapse-toggle');
    const toggleSidebarBtn = document.querySelector('.toggle-sidebar-btn');
    const contentWrapper = document.getElementById('content-wrapper');
    
    // Khởi tạo trạng thái sidebar từ localStorage
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed) {
        body.classList.add('sidebar-toggled');
    }
    
    // Xử lý nút toggle sidebar trên desktop
    if (sidebarCollapseToggle) {
        sidebarCollapseToggle.addEventListener('click', function() {
            body.classList.toggle('sidebar-toggled');
            const isCollapsed = body.classList.contains('sidebar-toggled');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            // Trigger resize event để các chart hoặc component khác cập nhật
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 300);
        });
    }
    
    // Xử lý nút toggle sidebar trên mobile
    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', function() {
            if (sidebar) {
                sidebar.classList.toggle('show');
                body.classList.toggle('sidebar-open');
                
                // Thêm/xóa overlay cho mobile
                toggleMobileOverlay();
            }
        });
    }
    
    // Xử lý submenu dropdown
    const submenuToggle = document.querySelectorAll('.has-submenu > a');
    submenuToggle.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('open');
            
            // Đóng các submenu khác
            const otherSubmenus = document.querySelectorAll('.has-submenu.open');
            otherSubmenus.forEach(submenu => {
                if (submenu !== parent) {
                    submenu.classList.remove('open');
                }
            });
        });
    });
    
    // Xử lý click bên ngoài sidebar trên mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 991.98) {
            const isClickInsideSidebar = sidebar && sidebar.contains(e.target);
            const isClickOnToggleButton = toggleSidebarBtn && toggleSidebarBtn.contains(e.target);
            
            if (!isClickInsideSidebar && !isClickOnToggleButton && sidebar && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                body.classList.remove('sidebar-open');
                removeMobileOverlay();
            }
        }
    });
    
    // Xử lý thay đổi kích thước cửa sổ
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991.98) {
            // Trên desktop: xóa class mobile
            if (sidebar) {
                sidebar.classList.remove('show');
                body.classList.remove('sidebar-open');
            }
            removeMobileOverlay();
        } else {
            // Trên mobile: đảm bảo sidebar ẩn
            if (sidebar && !sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                body.classList.remove('sidebar-open');
            }
            removeMobileOverlay();
        }
    });
    
    // Thêm overlay cho mobile
    function toggleMobileOverlay() {
        let overlay = document.querySelector('.sidebar-overlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }
        
        if (sidebar && sidebar.classList.contains('show')) {
            overlay.classList.add('show');
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                body.classList.remove('sidebar-open');
                removeMobileOverlay();
            });
        } else {
            overlay.classList.remove('show');
        }
    }
    
    // Xóa overlay
    function removeMobileOverlay() {
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }
    
    // Đảm bảo layout đúng sau khi load
    setTimeout(function() {
        if (contentWrapper) {
            contentWrapper.style.opacity = '1';
        }
        
        // Trigger resize event
        window.dispatchEvent(new Event('resize'));
    }, 100);
    
    // Debug function - xóa sau khi testing
    function debugLayout() {
        console.log('Sidebar width:', sidebar ? sidebar.offsetWidth : 'not found');
        console.log('Content wrapper margin-left:', contentWrapper ? getComputedStyle(contentWrapper).marginLeft : 'not found');
        console.log('Body classes:', body.className);
        console.log('Sidebar classes:', sidebar ? sidebar.className : 'not found');
    }
    
    // Uncomment để debug
    // debugLayout();
    
    // Kiểm tra và sửa lỗi layout nếu cần
    function checkAndFixLayout() {
        if (sidebar && contentWrapper) {
            const sidebarWidth = sidebar.offsetWidth;
            const contentMarginLeft = parseInt(getComputedStyle(contentWrapper).marginLeft);
            
            // Nếu margin-left không đúng, force lại
            if (Math.abs(contentMarginLeft - sidebarWidth) > 5) {
                console.warn('Layout mismatch detected, fixing...');
                if (body.classList.contains('sidebar-toggled')) {
                    contentWrapper.style.marginLeft = '60px';
                    contentWrapper.style.width = 'calc(100% - 60px)';
                } else {
                    contentWrapper.style.marginLeft = '260px';
                    contentWrapper.style.width = 'calc(100% - 260px)';
                }
            }
        }
    }
    
    // Chạy check layout sau 1 giây
    setTimeout(checkAndFixLayout, 1000);
});
