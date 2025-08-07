/**
 * Unified Tab System for Student Project View
 * Handles tab functionality uniformly across all project statuses
 */

class UnifiedTabSystem {
    constructor() {
        this.activeTab = 'proposal'; // Default tab
        this.tabContainer = '#documentTabs';
        this.contentContainer = '#documentTabsContent';
        this.initialized = false;
        
        this.init();
    }
    
    init() {
        console.log('=== Unified Tab System Initialization ===');
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        console.log('Setting up unified tab system...');
        
        // Remove any existing event listeners
        this.removeExistingListeners();
        
        // Setup new event listeners
        this.setupEventListeners();
        
        // Initialize tab state
        this.initializeTabState();
        
        // Mark as initialized
        this.initialized = true;
        
        console.log('✓ Unified tab system initialized');
    }
    
    removeExistingListeners() {
        // Remove all existing click handlers on tabs
        $(this.tabContainer + ' a[data-toggle="tab"]').off('click shown.bs.tab');
    }
    
    setupEventListeners() {
        const self = this;
        
        // Handle tab clicks
        $(this.tabContainer + ' a[data-toggle="tab"]').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetTab = $(this).attr('href').replace('#', '');
            console.log('Tab clicked:', targetTab);
            
            self.switchToTab(targetTab);
        });
        
        // Handle browser back/forward
        window.addEventListener('popstate', function(event) {
            const urlParams = new URLSearchParams(window.location.search);
            const urlTab = urlParams.get('tab');
            if (urlTab) {
                self.switchToTab(urlTab);
            }
        });
    }
    
    initializeTabState() {
        // Try to get initial tab from URL, localStorage, or default
        const urlParams = new URLSearchParams(window.location.search);
        const urlTab = urlParams.get('tab');
        const savedTab = localStorage.getItem('lastActiveTab');
        
        let initialTab = urlTab || savedTab || 'proposal';
        
        // Ensure the tab exists
        if (!$('#' + initialTab).length) {
            initialTab = 'proposal';
        }
        
        console.log('Initial tab:', initialTab);
        this.switchToTab(initialTab);
    }
    
    switchToTab(tabName) {
        console.log('Switching to tab:', tabName);
        
        // Validate tab exists
        if (!$('#' + tabName).length) {
            console.warn('Tab not found:', tabName);
            return false;
        }
        
        // Update active tab
        this.activeTab = tabName;
        
        // Remove all active states
        $(this.tabContainer + ' .nav-link').removeClass('active').attr('aria-selected', 'false');
        $(this.contentContainer + ' .tab-pane').removeClass('show active').hide();
        
        // Activate target tab and content
        $(this.tabContainer + ' a[href="#' + tabName + '"]').addClass('active').attr('aria-selected', 'true');
        $('#' + tabName).addClass('show active').show().css({
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1'
        });
        
        // Force layout recalculation
        $('#' + tabName)[0].offsetHeight;
        
        // Save to localStorage
        localStorage.setItem('lastActiveTab', tabName);
        
        // Update URL without page reload
        this.updateURL(tabName);
        
        console.log('✓ Tab switched to:', tabName);
        return true;
    }
    
    updateURL(tabName) {
        try {
            if (history.pushState) {
                // Lấy URL hiện tại và giữ lại tất cả parameters
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('tab', tabName);
                
                // Đảm bảo ID đề tài luôn có trong URL
                if (!urlParams.has('id')) {
                    console.warn('Missing project ID in URL, may cause redirect issues');
                    // Có thể lấy ID từ form hoặc data attribute nếu cần
                }
                
                const newUrl = window.location.pathname + '?' + urlParams.toString();
                history.pushState(null, null, newUrl);
                console.log('URL updated to:', newUrl);
            }
        } catch (e) {
            console.warn('URL update failed:', e);
        }
    }
    
    // Public methods for external use
    getCurrentTab() {
        return this.activeTab;
    }
    
    isInitialized() {
        return this.initialized;
    }
    
    // Debug functions
    debugState() {
        console.log('=== Tab System Debug ===');
        console.log('Current tab:', this.activeTab);
        console.log('Initialized:', this.initialized);
        console.log('Active tab links:', $(this.tabContainer + ' .nav-link.active').length);
        console.log('Visible tab panes:', $(this.contentContainer + ' .tab-pane.show.active:visible').length);
        console.log('All tabs:', $(this.tabContainer + ' .nav-link').map(function() { 
            return $(this).attr('href'); 
        }).get());
    }
    
    // Force recovery if tabs stop working
    forceRecovery() {
        console.log('=== Force Tab Recovery ===');
        this.setup(); // Re-initialize everything
        this.switchToTab(this.activeTab); // Switch back to current tab
    }
}

// Global instance
window.unifiedTabSystem = null;

// Initialize when DOM is ready
$(document).ready(function() {
    console.log('Initializing Unified Tab System...');
    window.unifiedTabSystem = new UnifiedTabSystem();
    
    // Add global helper functions
    window.switchToTab = function(tabName) {
        if (window.unifiedTabSystem) {
            return window.unifiedTabSystem.switchToTab(tabName);
        }
        return false;
    };
    
    window.debugTabs = function() {
        if (window.unifiedTabSystem) {
            window.unifiedTabSystem.debugState();
        }
    };
    
    window.recoverTabs = function() {
        if (window.unifiedTabSystem) {
            window.unifiedTabSystem.forceRecovery();
        }
    };
    
    // Set up periodic health check
    setInterval(function() {
        if (window.unifiedTabSystem && window.unifiedTabSystem.isInitialized()) {
            const visibleTabs = $('.tab-pane.show.active:visible').length;
            if (visibleTabs === 0) {
                console.warn('No visible tabs detected, running recovery...');
                window.unifiedTabSystem.forceRecovery();
            }
        }
    }, 5000); // Check every 5 seconds
});
