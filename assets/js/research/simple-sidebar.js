// Simple Sidebar JavaScript
// filepath: d:\xampp\htdocs\NLNganh\assets\js\research\simple-sidebar.js

document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidebar functionality
    initSimpleSidebar();
    
    // Handle active menu highlighting
    highlightActiveMenu();
    
    // Handle mobile navigation
    handleMobileNavigation();
    
    // Load notification count
    loadNotificationCount();
});

function initSimpleSidebar() {
    const sidebar = document.getElementById('simpleSidebar');
    const toggleBtn = document.getElementById('sidebarToggleTop');
    
    if (!sidebar || !toggleBtn) return;
    
    // Mobile toggle functionality
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        toggleMobileSidebar();
    });
    
    // Handle nav link clicks with smooth transitions
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add loading state for better UX
            if (!this.classList.contains('active')) {
                this.style.opacity = '0.7';
                this.style.pointerEvents = 'none';
                
                // Restore after a short delay (simulates loading)
                setTimeout(() => {
                    this.style.opacity = '';
                    this.style.pointerEvents = '';
                }, 300);
            }
        });
    });
}

function highlightActiveMenu() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.split('/').pop())) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

function handleMobileNavigation() {
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('simpleSidebar');
        const toggleBtn = document.getElementById('sidebarToggleTop');
        
        if (window.innerWidth <= 768) {
            if (sidebar && !sidebar.contains(e.target) && 
                toggleBtn && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('simpleSidebar');
        if (sidebar && window.innerWidth > 768) {
            sidebar.classList.remove('show');
        }
    });
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const sidebar = document.getElementById('simpleSidebar');
            if (sidebar && window.innerWidth <= 768) {
                sidebar.classList.remove('show');
            }
        }
    });
}

function toggleMobileSidebar() {
    const sidebar = document.getElementById('simpleSidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
        
        // Add/remove body scroll lock on mobile
        if (sidebar.classList.contains('show')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}

function loadNotificationCount() {
    // Simulate loading notification count
    // In a real application, this would make an AJAX call
    const badge = document.querySelector('.nav-badge');
    if (badge) {
        // Example: Update badge count
        // fetch('/api/notifications/count')
        //     .then(response => response.json())
        //     .then(data => {
        //         if (data.count > 0) {
        //             badge.textContent = data.count;
        //             badge.style.display = 'inline-block';
        //         } else {
        //             badge.style.display = 'none';
        //         }
        //     });
    }
}

// Utility function to show toast notifications
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-info-circle"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add styles
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Export functions for use in other scripts
window.SimpleSidebar = {
    toggle: toggleMobileSidebar,
    showToast: showToast,
    highlightActive: highlightActiveMenu
};
