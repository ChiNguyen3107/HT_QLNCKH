/**
 * research-responsive.js
 * Add responsive features to all research manager pages
 */

document.addEventListener('DOMContentLoaded', function() {
    // Apply responsive CSS
    applyResponsiveCSS();
    
    // Add mobile toggle button
    addMobileMenuToggle();
    
    // Initialize responsive tables
    makeTablesResponsive();
    
    // Fix notification badge on mobile
    fixNotificationBadge();
});

function applyResponsiveCSS() {
    // Check if responsive CSS is already included
    const existingLink = document.querySelector('link[href*="responsive.css"]');
    if (!existingLink) {
        // Create link tag for responsive CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = '/NLNganh/assets/css/research/responsive.css';
        document.head.appendChild(link);
    }
}

function addMobileMenuToggle() {
    // Check if mobile toggle button already exists
    if (!document.querySelector('.mobile-toggle-btn')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-primary rounded-circle shadow mobile-toggle-btn';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.setAttribute('aria-label', 'Toggle Sidebar');
        document.body.appendChild(toggleBtn);
        
        // Add click event
        toggleBtn.addEventListener('click', function() {
            const sidebar = document.querySelector('.research-sidebar');
            if (sidebar) {
                sidebar.classList.toggle('show');
                
                // Toggle button icon
                const icon = this.querySelector('i');
                if (icon.classList.contains('fa-bars')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.research-sidebar');
            const toggleBtn = document.querySelector('.mobile-toggle-btn');
            
            if (window.innerWidth <= 768 && 
                sidebar && sidebar.classList.contains('show') && 
                toggleBtn && !sidebar.contains(event.target) && 
                !toggleBtn.contains(event.target)) {
                sidebar.classList.remove('show');
                
                // Reset button icon
                const icon = toggleBtn.querySelector('i');
                if (icon.classList.contains('fa-times')) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
}

function makeTablesResponsive() {
    // Find all tables that aren't already wrapped
    const tables = document.querySelectorAll('table:not(.dataTable)');
    tables.forEach(table => {
        // Check if the table is already in a responsive wrapper
        if (!table.parentElement.classList.contains('table-responsive')) {
            // Create a wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            // Wrap the table
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
}

function fixNotificationBadge() {
    // Make sure notification badges are properly sized on mobile
    const notificationBadges = document.querySelectorAll('.badge-counter');
    notificationBadges.forEach(badge => {
        // Ensure small size on mobile
        if (window.innerWidth <= 576) {
            badge.style.fontSize = '10px';
            badge.style.padding = '2px 4px';
        }
    });
}
