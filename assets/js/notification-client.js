/**
 * Notification Client
 * Client-side JavaScript để tương tác với hệ thống thông báo
 */

class NotificationClient {
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || '/NLNganh/api/notification_manager.php';
        this.simpleCountUrl = options.simpleCountUrl || '/NLNganh/api/simple_notification_count.php';
        this.updateInterval = options.updateInterval || 30000; // 30 giây
        this.maxRetries = options.maxRetries || 3;
        this.retryDelay = options.retryDelay || 1000;
        
        this.isInitialized = false;
        this.updateTimer = null;
        this.retryCount = 0;
        
        // Event listeners
        this.onNotificationReceived = options.onNotificationReceived || null;
        this.onCountUpdated = options.onCountUpdated || null;
        this.onError = options.onError || null;
        
        this.init();
    }
    
    /**
     * Khởi tạo notification client
     */
    init() {
        if (this.isInitialized) return;
        
        this.updateNotificationCount();
        this.startPeriodicUpdate();
        this.bindEvents();
        
        this.isInitialized = true;
        console.log('NotificationClient initialized successfully');
    }
    
    /**
     * Bind các sự kiện DOM
     */
    bindEvents() {
        // Auto-refresh khi tab trở lại focus
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateNotificationCount();
            }
        });
        
        // Bind click events cho notification links
        document.addEventListener('click', (e) => {
            const notificationLink = e.target.closest('[data-notification-action]');
            if (notificationLink) {
                e.preventDefault();
                const action = notificationLink.dataset.notificationAction;
                const notificationId = notificationLink.dataset.notificationId;
                
                this.handleNotificationAction(action, notificationId);
            }
        });
    }
    
    /**
     * Xử lý các action trên thông báo
     */
    async handleNotificationAction(action, notificationId) {
        try {
            switch (action) {
                case 'mark-read':
                    await this.markAsRead(notificationId);
                    break;
                case 'mark-all-read':
                    await this.markAllAsRead();
                    break;
                case 'delete':
                    if (confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
                        await this.deleteNotification(notificationId);
                    }
                    break;
            }
        } catch (error) {
            this.handleError(error);
        }
    }
    
    /**
     * Bắt đầu cập nhật định kỳ
     */
    startPeriodicUpdate() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        
        this.updateTimer = setInterval(() => {
            this.updateNotificationCount();
        }, this.updateInterval);
    }
    
    /**
     * Dừng cập nhật định kỳ
     */
    stopPeriodicUpdate() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }
    
    /**
     * Cập nhật số lượng thông báo chưa đọc
     */
    async updateNotificationCount() {
        try {
            // Thử API đơn giản trước
            const response = await fetch(this.simpleCountUrl);
            const data = await response.json();
            
            if (data.success) {
                const count = data.data.count;
                this.updateCountDisplay(count);
                
                if (this.onCountUpdated) {
                    this.onCountUpdated(count);
                }
                
                this.retryCount = 0; // Reset retry count on success
            } else {
                throw new Error(data.message || 'API call failed');
            }
        } catch (error) {
            console.warn('Simple API failed, trying complex API:', error);
            
            // Fallback to complex API
            try {
                const response = await this.apiCall('get_count');
                
                if (response.success) {
                    const count = response.data.count;
                    this.updateCountDisplay(count);
                    
                    if (this.onCountUpdated) {
                        this.onCountUpdated(count);
                    }
                    
                    this.retryCount = 0;
                }
            } catch (fallbackError) {
                this.handleError(fallbackError);
            }
        }
    }
    
    /**
     * Cập nhật hiển thị số lượng thông báo
     */
    updateCountDisplay(count) {
        const badges = document.querySelectorAll('.notification-badge, .nav-badge, #notification-count');
        
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
                badge.classList.add('has-notifications');
                badge.classList.remove('no-notifications');
            } else {
                badge.textContent = '0';
                badge.style.display = count > 0 ? 'inline-block' : 'none';
                badge.classList.add('no-notifications');
                badge.classList.remove('has-notifications');
            }
        });
        
        // Cập nhật title của trang nếu có thông báo mới
        this.updatePageTitle(count);
    }
    
    /**
     * Cập nhật title trang với số thông báo
     */
    updatePageTitle(count) {
        const originalTitle = document.title.replace(/^\(\d+\)\s*/, '');
        
        if (count > 0) {
            document.title = `(${count}) ${originalTitle}`;
        } else {
            document.title = originalTitle;
        }
    }
    
    /**
     * Lấy danh sách thông báo
     */
    async getNotifications(options = {}) {
        const params = new URLSearchParams({
            action: 'get_notifications',
            page: options.page || 1,
            limit: options.limit || 20,
            status: options.status || 'all'
        });
        
        try {
            const response = await this.apiCall(`get_notifications?${params.toString()}`);
            return response;
        } catch (error) {
            this.handleError(error);
            throw error;
        }
    }
    
    /**
     * Đánh dấu thông báo đã đọc
     */
    async markAsRead(notificationId) {
        try {
            const response = await this.apiCall('mark_read', 'POST', {
                notification_id: notificationId
            });
            
            if (response.success) {
                // Cập nhật UI
                const notificationElement = document.getElementById(`notification-${notificationId}`);
                if (notificationElement) {
                    notificationElement.classList.remove('unread');
                    notificationElement.classList.add('read');
                }
                
                // Cập nhật count
                this.updateNotificationCount();
                
                this.showToast('Đã đánh dấu thông báo là đã đọc', 'success');
            }
            
            return response;
        } catch (error) {
            this.handleError(error);
            throw error;
        }
    }
    
    /**
     * Đánh dấu tất cả thông báo đã đọc
     */
    async markAllAsRead() {
        try {
            const response = await this.apiCall('mark_all_read', 'POST');
            
            if (response.success) {
                // Cập nhật UI
                const unreadElements = document.querySelectorAll('.notification-item.unread');
                unreadElements.forEach(element => {
                    element.classList.remove('unread');
                    element.classList.add('read');
                });
                
                // Cập nhật count
                this.updateNotificationCount();
                
                this.showToast('Đã đánh dấu tất cả thông báo là đã đọc', 'success');
            }
            
            return response;
        } catch (error) {
            this.handleError(error);
            throw error;
        }
    }
    
    /**
     * Xóa thông báo
     */
    async deleteNotification(notificationId) {
        try {
            const response = await this.apiCall('delete', 'POST', {
                notification_id: notificationId
            });
            
            if (response.success) {
                // Xóa khỏi UI
                const notificationElement = document.getElementById(`notification-${notificationId}`);
                if (notificationElement) {
                    notificationElement.remove();
                }
                
                // Cập nhật count
                this.updateNotificationCount();
                
                this.showToast('Đã xóa thông báo', 'success');
            }
            
            return response;
        } catch (error) {
            this.handleError(error);
            throw error;
        }
    }
    
    /**
     * Tạo thông báo mới
     */
    async createNotification(data) {
        try {
            const response = await this.apiCall('create', 'POST', data);
            
            if (response.success) {
                this.showToast('Tạo thông báo thành công', 'success');
                this.updateNotificationCount();
            }
            
            return response;
        } catch (error) {
            this.handleError(error);
            throw error;
        }
    }
    
    /**
     * Gọi API
     */
    async apiCall(action, method = 'GET', data = null) {
        const url = action.includes('?') ? 
            `${this.apiUrl}?${action}` : 
            `${this.apiUrl}?action=${action}`;
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'API call failed');
        }
        
        return result;
    }
    
    /**
     * Xử lý lỗi
     */
    handleError(error) {
        console.error('NotificationClient Error:', error);
        
        if (this.onError) {
            this.onError(error);
        }
        
        // Retry logic
        if (this.retryCount < this.maxRetries) {
            this.retryCount++;
            setTimeout(() => {
                this.updateNotificationCount();
            }, this.retryDelay * this.retryCount);
        }
        
        this.showToast(error.message || 'Có lỗi xảy ra với hệ thống thông báo', 'error');
    }
    
    /**
     * Hiển thị toast notification
     */
    showToast(message, type = 'info') {
        // Tạo toast element nếu chưa có
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        // Tạo toast
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Show toast
        if (typeof bootstrap !== 'undefined') {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Auto remove after hide
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        } else {
            // Fallback nếu không có Bootstrap
            toast.style.display = 'block';
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    }
    
    /**
     * Render danh sách thông báo
     */
    renderNotifications(notifications, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có thông báo</h5>
                    <p class="text-muted">Bạn không có thông báo nào</p>
                </div>
            `;
            return;
        }
        
        const notificationsList = notifications.map(notification => {
            const isUnread = !notification.TB_DANHDOC;
            const priorityClass = this.getPriorityClass(notification.TB_MUCDO);
            const iconClass = this.getNotificationIcon(notification.TB_LOAI);
            
            return `
                <div id="notification-${notification.TB_MA}" 
                     class="notification-item ${isUnread ? 'unread' : 'read'} ${priorityClass} border-bottom p-3">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <i class="${iconClass} fa-lg text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-1 ${isUnread ? 'fw-bold' : ''}">${notification.TB_LOAI}</h6>
                                <small class="text-muted">${notification.formatted_date}</small>
                            </div>
                            <p class="mb-2 ${isUnread ? 'fw-semibold' : 'text-muted'}">${notification.TB_NOIDUNG}</p>
                            <div class="d-flex gap-2">
                                ${isUnread ? `
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-notification-action="mark-read" 
                                            data-notification-id="${notification.TB_MA}">
                                        <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                                    </button>
                                ` : ''}
                                <button class="btn btn-sm btn-outline-danger" 
                                        data-notification-action="delete" 
                                        data-notification-id="${notification.TB_MA}">
                                    <i class="fas fa-trash me-1"></i>Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = notificationsList;
    }
    
    /**
     * Lấy class CSS cho mức độ ưu tiên
     */
    getPriorityClass(mucdo) {
        switch (mucdo) {
            case 'khan_cap': return 'priority-urgent';
            case 'cao': return 'priority-high';
            case 'trung_binh': return 'priority-medium';
            case 'thap': return 'priority-low';
            default: return 'priority-medium';
        }
    }
    
    /**
     * Lấy icon cho loại thông báo
     */
    getNotificationIcon(loai) {
        const iconMap = {
            'de_tai_moi': 'fas fa-file-plus',
            'de_tai_duyet': 'fas fa-check-circle',
            'de_tai_tu_choi': 'fas fa-times-circle',
            'gia_han_yeu_cau': 'fas fa-clock',
            'gia_han_duyet': 'fas fa-calendar-check',
            'cvht_gan_moi': 'fas fa-user-tie',
            'he_thong': 'fas fa-cog',
            'default': 'fas fa-bell'
        };
        
        return iconMap[loai] || iconMap.default;
    }
    
    /**
     * Destroy instance
     */
    destroy() {
        this.stopPeriodicUpdate();
        this.isInitialized = false;
    }
}

// Auto-initialize nếu có element notification
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.notification-badge, .nav-badge, #notification-count')) {
        window.notificationClient = new NotificationClient({
            onError: (error) => {
                console.warn('Notification system error:', error.message);
            }
        });
    }
});

// Export cho sử dụng module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationClient;
}
