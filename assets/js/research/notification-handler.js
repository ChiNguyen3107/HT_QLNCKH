/**
 * notification-handler.js
 * Handles notification updates for the modern sidebar
 */

// Use both jQuery and vanilla JS for compatibility
(function() {
    function initializeNotificationHandler() {
        // Function to update notification count
        function updateNotificationCount() {
            const notificationBadge = document.getElementById('notification-count');
            if (!notificationBadge) return;
            
            // Get current notification count via AJAX
            fetch('/NLNganh/view/research/get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    // Update badge text
                    notificationBadge.textContent = data.count;
                    
                    // Add or remove visual indicator based on count
                    if (parseInt(data.count) > 0) {
                    notificationBadge.classList.add('has-new');
                } else {
                    notificationBadge.classList.remove('has-new');
                    notificationBadge.textContent = '0';
                }
            })
            .catch(error => {
                console.error('Error fetching notification count:', error);
                // Set default value on error
                notificationBadge.textContent = '0';
            });
    }
    
    // Update notification count when page loads
    updateNotificationCount();
    
    // Set interval to update notification count periodically (every 60 seconds)
    setInterval(updateNotificationCount, 60000);
    
    // Mark notifications as read when clicking on notification link
    const notificationLink = document.querySelector('.modern-sidebar a[href*="notifications.php"]');
    if (notificationLink) {
        notificationLink.addEventListener('click', function() {
            // Reset notification badge when visiting notifications page
            const notificationBadge = document.getElementById('notification-count');
            if (notificationBadge) {
                // We'll let the page handle the actual read status
                // This is just visual feedback immediately on click
                notificationBadge.classList.remove('has-new');
            }
        });
    }
});
