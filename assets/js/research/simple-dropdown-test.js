/* Simple Sidebar Dropdown Test */
/* Phiên bản đơn giản để test dropdown */

$(document).ready(function() {
    console.log('Simple dropdown test loaded');
    
    // Wait for sidebar to load
    setTimeout(function() {
        initSimpleDropdown();
    }, 1000);
});

function initSimpleDropdown() {
    console.log('Initializing simple dropdown...');
    
    // Remove all existing click handlers
    $('.submenu-toggle').off('click.simple');
    
    // Add simple click handler
    $('.submenu-toggle').on('click.simple', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $clickedLink = $(this);
        const $navItem = $clickedLink.closest('.nav-item.has-submenu');
        const menuName = $navItem.find('.nav-text').text();
        
        console.log('=== CLICK EVENT ===');
        console.log('Clicked menu:', menuName);
        console.log('Nav item:', $navItem[0]);
        console.log('Has open class before:', $navItem.hasClass('open'));
        
        // Simple toggle - just add/remove class
        if ($navItem.hasClass('open')) {
            $navItem.removeClass('open');
            console.log('Removed open class');
        } else {
            // Close all others first
            $('.nav-item.has-submenu').removeClass('open');
            console.log('Closed all submenus');
            
            // Open this one
            $navItem.addClass('open');
            console.log('Added open class');
        }
        
        console.log('Has open class after:', $navItem.hasClass('open'));
        
        // Check submenu element
        const $submenu = $navItem.find('.submenu');
        console.log('Submenu element:', $submenu[0]);
        console.log('Submenu computed styles:');
        console.log('- max-height:', $submenu.css('max-height'));
        console.log('- opacity:', $submenu.css('opacity'));
        console.log('- display:', $submenu.css('display'));
        console.log('- visibility:', $submenu.css('visibility'));
        
        // Force refresh
        $navItem[0].offsetHeight;
        
        console.log('=== END CLICK EVENT ===');
    });
    
    console.log('Simple dropdown initialized');
    console.log('Found submenu toggles:', $('.submenu-toggle').length);
}

// Global debug function
window.testDropdownSimple = function() {
    console.log('=== MANUAL TEST ===');
    const $firstToggle = $('.submenu-toggle').first();
    if ($firstToggle.length) {
        console.log('Manually triggering first toggle...');
        $firstToggle.trigger('click.simple');
    } else {
        console.log('No toggles found!');
    }
};
