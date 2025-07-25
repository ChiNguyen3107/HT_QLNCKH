/**
 * Font Awesome Icon Fix
 * Handles fallback for Font Awesome icons if they fail to load from CDN
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to check if Font Awesome is loaded
    function isFontAwesomeLoaded() {
        const testIcon = document.createElement('i');
        testIcon.className = 'fas fa-check';
        document.body.appendChild(testIcon);
        
        // Get the computed style
        const computedStyle = window.getComputedStyle(testIcon);
        const fontFamily = computedStyle.getPropertyValue('font-family');
        
        // Remove the test element
        document.body.removeChild(testIcon);
        
        // If Font Awesome is loaded, the font-family should contain "Font Awesome"
        return fontFamily.toLowerCase().includes('awesome');
    }

    // If Font Awesome is not loaded from CDN, load it locally
    if (!isFontAwesomeLoaded()) {
        console.log('Font Awesome not loaded from CDN, attempting local fallback...');
        
        // Create link to local Font Awesome CSS (if available)
        const fallbackLink = document.createElement('link');
        fallbackLink.rel = 'stylesheet';
        fallbackLink.href = '/NLNganh/assets/vendor/fontawesome-free/css/all.min.css';
        document.head.appendChild(fallbackLink);
        
        // If still not loaded, add a fontawesome link from another CDN as backup
        fallbackLink.onerror = function() {
            console.log('Local Font Awesome not available, using alternative CDN...');
            const alternativeCDN = document.createElement('link');
            alternativeCDN.rel = 'stylesheet';
            alternativeCDN.href = 'https://use.fontawesome.com/releases/v5.15.4/css/all.css';
            document.head.appendChild(alternativeCDN);
        };
    }

    // Replace any broken icons with default text backup
    function fixBrokenIcons() {
        // All icon elements
        const icons = document.querySelectorAll('.fa, .fas, .fab, .far, .fal');
        
        icons.forEach(function(icon) {
            // Check if icon has 0 width (likely not loaded)
            if (icon.offsetWidth === 0 && icon.offsetHeight === 0) {
                // Add a text fallback based on the icon's classes
                if (icon.classList.contains('fa-eye')) {
                    icon.textContent = '[View]';
                } else if (icon.classList.contains('fa-edit')) {
                    icon.textContent = '[Edit]';
                } else if (icon.classList.contains('fa-check')) {
                    icon.textContent = '[Approve]';
                } else if (icon.classList.contains('fa-file')) {
                    icon.textContent = '[File]';
                } else if (icon.classList.contains('fa-search')) {
                    icon.textContent = '[Search]';
                } else if (icon.classList.contains('fa-trash')) {
                    icon.textContent = '[Delete]';
                } else if (icon.classList.contains('fa-download')) {
                    icon.textContent = '[Download]';
                } else if (icon.classList.contains('fa-upload')) {
                    icon.textContent = '[Upload]';
                } else if (icon.classList.contains('fa-plus')) {
                    icon.textContent = '[Add]';
                } else if (icon.classList.contains('fa-minus')) {
                    icon.textContent = '[Remove]';
                } else if (icon.classList.contains('fa-times')) {
                    icon.textContent = '[Close]';
                } else if (icon.classList.contains('fa-user')) {
                    icon.textContent = '[User]';
                } else if (icon.classList.contains('fa-bell')) {
                    icon.textContent = '[Notifications]';
                } else if (icon.classList.contains('fa-chart')) {
                    icon.textContent = '[Chart]';
                } else if (icon.classList.contains('fa-file-excel')) {
                    icon.textContent = '[Excel]';
                } else {
                    icon.textContent = '[Icon]';
                }
                
                // Add style to make it look like a button or icon replacement
                icon.style.padding = '3px 5px';
                icon.style.border = '1px solid #ccc';
                icon.style.borderRadius = '3px';
                icon.style.fontSize = '0.8rem';
                icon.style.fontWeight = 'normal';
                icon.style.color = '#555';
            }
        });
    }

    // Check for broken icons after a delay to ensure everything is rendered
    setTimeout(fixBrokenIcons, 2000);
});
