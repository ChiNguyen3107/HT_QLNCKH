/**
 * URL Debug Script for Project View
 * Monitors URL changes and ensures project ID is preserved
 */

$(document).ready(function() {
    console.log('=== URL Debug Script Loaded ===');
    
    // Debug initial URL state
    console.log('Initial URL:', window.location.href);
    console.log('Initial pathname:', window.location.pathname);
    console.log('Initial search:', window.location.search);
    
    const initialParams = new URLSearchParams(window.location.search);
    console.log('Project ID from URL:', initialParams.get('id'));
    console.log('Tab from URL:', initialParams.get('tab'));
    
    // Store project ID in localStorage for recovery
    const projectId = initialParams.get('id');
    if (projectId) {
        localStorage.setItem('current_project_id', projectId);
        console.log('Stored project ID in localStorage:', projectId);
    } else {
        // Try to recover from localStorage
        const storedId = localStorage.getItem('current_project_id');
        if (storedId && window.location.pathname.includes('view_project.php')) {
            console.log('Recovering project ID from localStorage:', storedId);
            const tab = initialParams.get('tab') || 'proposal';
            const newUrl = window.location.pathname + '?id=' + storedId + '&tab=' + tab;
            console.log('Redirecting to:', newUrl);
            window.location.href = newUrl;
            return;
        }
        console.error('⚠️ No project ID found in URL! This may cause redirect issues.');
    }
    
    // Monitor URL changes
    let lastUrl = window.location.href;
    setInterval(function() {
        if (window.location.href !== lastUrl) {
            console.log('URL changed from:', lastUrl);
            console.log('URL changed to:', window.location.href);
            lastUrl = window.location.href;
            
            // Check if ID is missing after change
            const currentParams = new URLSearchParams(window.location.search);
            const currentId = currentParams.get('id');
            if (!currentId) {
                console.error('⚠️ Project ID missing from URL after change!');
                console.log('Available params:', Object.fromEntries(currentParams));
            }
        }
    }, 1000);
    
    // Handle page refresh/reload
    window.addEventListener('beforeunload', function() {
        const currentParams = new URLSearchParams(window.location.search);
        const currentId = currentParams.get('id');
        if (currentId) {
            localStorage.setItem('current_project_id', currentId);
        }
    });
    
    // Override history.pushState to track changes
    const originalPushState = history.pushState;
    history.pushState = function(state, title, url) {
        console.log('History pushState called with URL:', url);
        return originalPushState.apply(history, arguments);
    };
    
    // Override history.replaceState to track changes
    const originalReplaceState = history.replaceState;
    history.replaceState = function(state, title, url) {
        console.log('History replaceState called with URL:', url);
        return originalReplaceState.apply(history, arguments);
    };
});
