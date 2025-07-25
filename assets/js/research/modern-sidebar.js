/**
 * modern-sidebar.js - JavaScript functionality for the modern sidebar
 * Provides dropdown toggle, mobile responsiveness and sidebar state management
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const sidebar = document.querySelector('.modern-sidebar');
    const dropdownMenus = document.querySelectorAll('.modern-sidebar .has-submenu > a');
    const toggleButton = document.querySelector('.toggle-sidebar-btn');
    const body = document.body;
    
    // Initialize active submenu
    const activeSubmenuItem = document.querySelector('.modern-sidebar .submenu a.active');
    if (activeSubmenuItem) {
        const parentSubmenu = activeSubmenuItem.closest('.submenu');
        const parentMenuItem = parentSubmenu.parentElement;
        
        // Open the parent dropdown if a submenu item is active
        parentSubmenu.classList.add('active');
        parentMenuItem.classList.add('open');
    }
    
    // Dropdown menus toggle functionality
    dropdownMenus.forEach(function(menu) {
        menu.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parent = this.parentElement;
            const submenu = parent.querySelector('.submenu');
            
            // Toggle current dropdown
            parent.classList.toggle('open');
            
            // Toggle submenu visibility with animation
            if (submenu.classList.contains('active')) {
                submenu.classList.remove('active');
            } else {
                submenu.classList.add('active');
            }
        });
    });
    
    // Mobile sidebar toggle
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 991) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggleButton = toggleButton && toggleButton.contains(event.target);
            
            if (!isClickInsideSidebar && !isClickOnToggleButton && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        }
    });
    
    // Sidebar collapse toggle for desktop
    const sidebarCollapseToggle = document.querySelector('.sidebar-collapse-toggle');
    if (sidebarCollapseToggle) {
        // Check if sidebar state is stored in localStorage
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            body.classList.add('sidebar-toggled');
        }
        
        sidebarCollapseToggle.addEventListener('click', function() {
            body.classList.toggle('sidebar-toggled');
            // Store the state in localStorage
            localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-toggled'));
        });
    }
    
    // Notification count update
    function updateNotificationCount() {
        const badge = document.querySelector('.modern-sidebar .notification-badge');
        if (badge) {
            fetch('/NLNganh/api/get_notifications_count.php')
                .then(response => response.json())
                .then(data => {
                    const count = parseInt(data.unread_count); // Sửa lỗi: sử dụng 'unread_count' thay vì 'count'
                    badge.textContent = count;

                    if (count > 0) {
                        badge.classList.add('has-new');
                    } else {
                        badge.classList.remove('has-new');
                    }
                })
                .catch(error => {
                    console.error('Error fetching notification count:', error);
                });
        }
    }
    
    // Call once on page load and then periodically
    updateNotificationCount();
    setInterval(updateNotificationCount, 60000); // Update every minute
});
