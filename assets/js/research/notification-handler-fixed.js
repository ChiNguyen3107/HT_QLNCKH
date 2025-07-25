/**
 * notification-handler.js
 * Handles notification updates for the modern sidebar
 */

// Initialize notification handler when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
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
                    notificationBadge.classList.add('badge-danger');
                    notificationBadge.classList.remove('badge-secondary');
                } else {
                    notificationBadge.classList.add('badge-secondary');
                    notificationBadge.classList.remove('badge-danger');
                }
            })
            .catch(error => {
                console.error('Error updating notification count:', error);
                notificationBadge.textContent = '0';
                notificationBadge.classList.add('badge-secondary');
                notificationBadge.classList.remove('badge-danger');
            });
    }
    
    // Initial load
    updateNotificationCount();
    
    // Update every 30 seconds
    setInterval(updateNotificationCount, 30000);
    
    // Update when page becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateNotificationCount();
        }
    });
    
    console.log('Notification handler initialized successfully');
});
