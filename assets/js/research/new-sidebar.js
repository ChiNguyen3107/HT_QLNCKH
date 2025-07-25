/**
 * New Research Sidebar JavaScript
 * File: assets/js/research/new-sidebar.js
 * Description: Enhanced functionality for the new research sidebar
 */

class NewResearchSidebar {
    constructor() {
        this.sidebar = document.getElementById('newResearchSidebar');
        this.overlay = document.getElementById('sidebarOverlay');
        this.isMobile = window.innerWidth <= 768;
        this.isOpen = false;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupSubmenuHandlers();
        this.setupActiveMenuDetection();
        this.setupMobileHandlers();
        this.setupNotificationUpdates();
        this.setupScrollHandlers();
        this.setupAnimations();
        
        // Initialize on load
        this.checkMobileView();
        this.updateActiveMenus();
        
        console.log('New Research Sidebar initialized successfully');
    }

    setupEventListeners() {
        // Responsive handling
        window.addEventListener('resize', () => {
            this.checkMobileView();
        });

        // Overlay click to close on mobile
        if (this.overlay) {
            this.overlay.addEventListener('click', () => {
                this.closeMobileSidebar();
            });
        }

        // Escape key to close sidebar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMobile && this.isOpen) {
                this.closeMobileSidebar();
            }
        });
    }

    setupSubmenuHandlers() {
        const submenuToggles = document.querySelectorAll('.submenu-toggle');
        
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSubmenu(toggle);
            });
        });
    }

    toggleSubmenu(toggle) {
        const parent = toggle.parentElement;
        const submenu = parent.querySelector('.submenu');
        const arrow = toggle.querySelector('.submenu-arrow i');
        
        // Close other submenus
        document.querySelectorAll('.has-submenu.submenu-open').forEach(openSubmenu => {
            if (openSubmenu !== parent) {
                openSubmenu.classList.remove('submenu-open');
                const otherArrow = openSubmenu.querySelector('.submenu-arrow i');
                if (otherArrow) {
                    otherArrow.style.transform = 'rotate(0deg)';
                }
            }
        });
        
        // Toggle current submenu
        const isOpen = parent.classList.contains('submenu-open');
        
        if (isOpen) {
            parent.classList.remove('submenu-open');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        } else {
            parent.classList.add('submenu-open');
            if (arrow) arrow.style.transform = 'rotate(90deg)';
        }

        // Add animation effect
        if (submenu) {
            submenu.style.transition = 'max-height 0.3s cubic-bezier(0.25, 0.8, 0.25, 1)';
        }
    }

    setupActiveMenuDetection() {
        const currentPath = window.location.pathname;
        const currentSearch = window.location.search;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && (currentPath.includes(href) || 
                        (href + currentSearch) === (currentPath + currentSearch))) {
                this.setActiveMenu(link);
            }
        });
    }

    setActiveMenu(activeLink) {
        // Remove active class from all links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Add active class to current link
        activeLink.classList.add('active');
        
        // Mark parent section as active
        const section = activeLink.closest('.nav-section');
        if (section) {
            section.classList.add('active-section');
        }
        
        // If it's in a submenu, open the submenu
        const parentSubmenu = activeLink.closest('.has-submenu');
        if (parentSubmenu) {
            parentSubmenu.classList.add('submenu-open');
            const arrow = parentSubmenu.querySelector('.submenu-arrow i');
            if (arrow) {
                arrow.style.transform = 'rotate(90deg)';
            }
        }
    }

    setupMobileHandlers() {
        // Create mobile toggle button if it doesn't exist
        if (!document.querySelector('.mobile-sidebar-toggle')) {
            this.createMobileToggle();
        }
    }

    createMobileToggle() {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-sidebar-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.style.cssText = `
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #667eea;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            display: none;
            cursor: pointer;
            transition: all 0.3s ease;
        `;
        
        toggleBtn.addEventListener('click', () => {
            this.toggleMobileSidebar();
        });
        
        document.body.appendChild(toggleBtn);
        this.mobileToggle = toggleBtn;
    }

    checkMobileView() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth <= 768;
        
        if (this.isMobile && !wasMobile) {
            // Switched to mobile
            this.sidebar.classList.add('mobile');
            if (this.mobileToggle) {
                this.mobileToggle.style.display = 'block';
            }
        } else if (!this.isMobile && wasMobile) {
            // Switched to desktop
            this.sidebar.classList.remove('mobile', 'mobile-open');
            this.overlay.classList.remove('active');
            if (this.mobileToggle) {
                this.mobileToggle.style.display = 'none';
            }
            this.isOpen = false;
        }
    }

    toggleMobileSidebar() {
        if (this.isOpen) {
            this.closeMobileSidebar();
        } else {
            this.openMobileSidebar();
        }
    }

    openMobileSidebar() {
        this.sidebar.classList.add('mobile-open');
        this.overlay.classList.add('active');
        this.isOpen = true;
        
        // Prevent body scrolling
        document.body.style.overflow = 'hidden';
    }

    closeMobileSidebar() {
        this.sidebar.classList.remove('mobile-open');
        this.overlay.classList.remove('active');
        this.isOpen = false;
        
        // Restore body scrolling
        document.body.style.overflow = '';
    }

    setupNotificationUpdates() {
        // Update notification count periodically
        this.updateNotificationCount();
        setInterval(() => {
            this.updateNotificationCount();
        }, 30000); // Update every 30 seconds
    }

    async updateNotificationCount() {
        try {
            const response = await fetch('/NLNganh/api/get_notifications_count.php');
            const data = await response.json();
            
            const badge = document.querySelector('.notification-badge');
            if (badge && data.count !== undefined) {
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.warn('Failed to update notification count:', error);
        }
    }

    setupScrollHandlers() {
        // Smooth scrolling for internal navigation
        const internalLinks = document.querySelectorAll('a[href^="#"]');
        internalLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll indicator for long navigation
        this.addScrollIndicator();
    }

    addScrollIndicator() {
        const navigation = document.querySelector('.sidebar-navigation');
        if (!navigation) return;

        navigation.addEventListener('scroll', () => {
            const { scrollTop, scrollHeight, clientHeight } = navigation;
            const scrollPercent = (scrollTop / (scrollHeight - clientHeight)) * 100;
            
            // You can add a scroll indicator here if needed
            this.updateScrollIndicator(scrollPercent);
        });
    }

    updateScrollIndicator(percent) {
        // Implementation for scroll indicator (optional)
        // Can be added if navigation becomes very long
    }

    setupAnimations() {
        // Add stagger animation to menu items
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.05}s`;
            item.style.animation = 'fadeInLeft 0.5s ease forwards';
        });

        // Add hover effects with better performance
        this.setupHoverEffects();
    }

    setupHoverEffects() {
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('mouseenter', () => {
                if (!link.classList.contains('active')) {
                    link.style.transform = 'translateX(5px)';
                }
            });
            
            link.addEventListener('mouseleave', () => {
                if (!link.classList.contains('active')) {
                    link.style.transform = 'translateX(0)';
                }
            });
        });
    }

    // Quick action methods
    createNewProject() {
        window.location.href = '/NLNganh/view/research/create_project.php';
    }

    generateReport() {
        window.location.href = '/NLNganh/view/research/research_reports.php';
    }

    // Theme switching (future feature)
    switchTheme(theme) {
        document.body.className = document.body.className.replace(/theme-\w+/g, '');
        document.body.classList.add(`theme-${theme}`);
        localStorage.setItem('sidebar-theme', theme);
    }

    // Export sidebar state (for debugging)
    getState() {
        return {
            isMobile: this.isMobile,
            isOpen: this.isOpen,
            activeMenus: Array.from(document.querySelectorAll('.nav-link.active')).map(link => link.href),
            openSubmenus: Array.from(document.querySelectorAll('.has-submenu.submenu-open')).length
        };
    }

    // Update active menu (can be called externally)
    updateActiveMenus() {
        this.setupActiveMenuDetection();
    }

    // Destroy sidebar (cleanup)
    destroy() {
        if (this.mobileToggle) {
            this.mobileToggle.remove();
        }
        
        // Remove event listeners
        window.removeEventListener('resize', this.checkMobileView);
        document.removeEventListener('keydown', this.escapeHandler);
        
        console.log('New Research Sidebar destroyed');
    }
}

// CSS animations
const sidebarAnimations = `
@keyframes fadeInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.mobile-sidebar-toggle:hover {
    transform: scale(1.05);
    background: #5a67d8 !important;
}

.nav-link {
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.nav-item {
    opacity: 0;
    animation-fill-mode: forwards;
}
`;

// Inject animations
const styleSheet = document.createElement('style');
styleSheet.textContent = sidebarAnimations;
document.head.appendChild(styleSheet);

// Global functions for quick actions
window.createNewProject = function() {
    if (window.sidebarInstance) {
        window.sidebarInstance.createNewProject();
    }
};

window.generateReport = function() {
    if (window.sidebarInstance) {
        window.sidebarInstance.generateReport();
    }
};

// Logout confirmation
document.addEventListener('DOMContentLoaded', function() {
    const logoutLink = document.querySelector('.logout-link');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                e.preventDefault();
            }
        });
    }
});

// Initialize sidebar when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.sidebarInstance = new NewResearchSidebar();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NewResearchSidebar;
}
