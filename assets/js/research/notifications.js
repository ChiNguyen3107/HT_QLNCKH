/**
 * notifications.js
 * Handles functionality related to notifications in the research management system
 */

document.addEventListener('DOMContentLoaded', function() {
    // Update notification count
    function updateNotificationCount() {
        fetch('/NLNganh/view/research/get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                const notificationCountElement = document.getElementById('notification-count');
                if (notificationCountElement) {
                    if (data.count > 0) {
                        notificationCountElement.textContent = data.count;
                        notificationCountElement.style.display = 'inline-block';
                    } else {
                        notificationCountElement.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error fetching notification count:', error));
    }

    // Mark notification as read
    function markAsRead(notificationId) {
        fetch(`/NLNganh/view/research/notifications.php?mark_read=${notificationId}`)
            .then(response => {
                if (response.ok) {
                    // Update UI
                    const notification = document.getElementById(`notification-${notificationId}`);
                    if (notification) {
                        notification.classList.remove('unread');
                        notification.classList.add('read');
                    }
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
    }

    // Mark all notifications as read
    function markAllAsRead() {
        fetch('/NLNganh/view/research/notifications.php?mark_all_read=1')
            .then(response => {
                if (response.ok) {
                    // Update UI
                    const unreadNotifications = document.querySelectorAll('.notification-item.unread');
                    unreadNotifications.forEach(notification => {
                        notification.classList.remove('unread');
                        notification.classList.add('read');
                    });
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Error marking all notifications as read:', error));
    }

    // Delete notification
    function deleteNotification(notificationId) {
        if (confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
            fetch(`/NLNganh/view/research/notifications.php?delete=${notificationId}`)
                .then(response => {
                    if (response.ok) {
                        // Remove notification from UI
                        const notification = document.getElementById(`notification-${notificationId}`);
                        if (notification) {
                            notification.remove();
                        }
                        updateNotificationCount();
                    }
                })
                .catch(error => console.error('Error deleting notification:', error));
        }
    }

    // Initialize notifications
    if (document.getElementById('notification-count')) {
        updateNotificationCount();
        
        // Set up event listeners for notification actions
        document.querySelectorAll('.mark-read-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                markAsRead(this.getAttribute('data-id'));
            });
        });
        
        document.querySelectorAll('.delete-notification-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                deleteNotification(this.getAttribute('data-id'));
            });
        });
        
        const markAllReadBtn = document.getElementById('mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                markAllAsRead();
            });
        }
        
        // Refresh notification count every 60 seconds
        setInterval(updateNotificationCount, 60000);
    }
});
