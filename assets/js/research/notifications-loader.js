/**
 * notifications-loader.js
 * Handles loading notifications for dropdown menu display
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if the notifications dropdown exists
    const notificationsDropdown = document.getElementById('alertsDropdown');
    const notificationsContainer = document.getElementById('notifications-container');
    
    if (!notificationsDropdown || !notificationsContainer) return;
    
    // Function to load notifications into the dropdown
    function loadNotifications() {
        // Show loading indicator
        notificationsContainer.innerHTML = `
            <div class="text-center p-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Đang tải thông báo...</p>
            </div>
        `;
        
        // Fetch notifications
        fetch('/NLNganh/view/research/notifications.php?format=dropdown&limit=5')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                notificationsContainer.innerHTML = data;
                
                // Add event listeners to mark-as-read buttons
                const markReadButtons = notificationsContainer.querySelectorAll('.mark-as-read');
                markReadButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const notificationId = this.dataset.id;
                        markAsRead(notificationId, this);
                    });
                });
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                notificationsContainer.innerHTML = `
                    <a class="dropdown-item d-flex align-items-center" href="#">
                        <div class="mr-3">
                            <div class="icon-circle bg-warning">
                                <i class="fas fa-exclamation-triangle text-white"></i>
                            </div>
                        </div>
                        <div>
                            <div class="small text-gray-500">Hôm nay</div>
                            Không thể tải thông báo. Hãy thử lại sau.
                        </div>
                    </a>
                `;
            });
    }
    
    // Function to mark a notification as read
    function markAsRead(id, buttonElement) {
        // Show loading state
        const originalHTML = buttonElement.innerHTML;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        buttonElement.disabled = true;
        
        // Send request to mark as read
        fetch(`/NLNganh/view/research/notifications.php?mark_read=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(() => {
                // Update UI to show notification is read
                const notificationItem = buttonElement.closest('.notification-item');
                notificationItem.classList.add('bg-gray-100');
                notificationItem.classList.add('text-gray-500');
                buttonElement.innerHTML = '<i class="fas fa-check"></i>';
                
                // Update notification count
                const notificationBadge = document.getElementById('notification-count');
                let currentCount = parseInt(notificationBadge.textContent);
                if (currentCount > 1) {
                    notificationBadge.textContent = currentCount - 1;
                } else {
                    notificationBadge.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
                buttonElement.innerHTML = originalHTML;
                buttonElement.disabled = false;
            });
    }
    
    // Load notifications when dropdown is opened
    notificationsDropdown.addEventListener('click', function() {
        // Only load if dropdown is not already open
        if (!notificationsDropdown.getAttribute('aria-expanded') || 
            notificationsDropdown.getAttribute('aria-expanded') === 'false') {
            loadNotifications();
        }
    });
});
