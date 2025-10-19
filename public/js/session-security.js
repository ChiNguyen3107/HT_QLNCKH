/**
 * Session Security Frontend Handler
 * Xử lý session warning và các tính năng bảo mật session ở frontend
 */

class SessionSecurity {
    constructor() {
        this.warningShown = false;
        this.warningInterval = null;
        this.checkInterval = null;
        this.warningThreshold = 300; // 5 phút trước khi hết hạn
        this.checkIntervalTime = 60000; // Kiểm tra mỗi phút
        
        this.init();
    }
    
    init() {
        // Kiểm tra session warning từ server
        this.checkServerWarning();
        
        // Bắt đầu kiểm tra định kỳ
        this.startPeriodicCheck();
        
        // Bind events
        this.bindEvents();
    }
    
    /**
     * Kiểm tra warning từ server
     */
    checkServerWarning() {
        // Kiểm tra nếu có warning trong session
        if (typeof window.sessionWarning !== 'undefined' && window.sessionWarning.show_warning) {
            this.showWarning(window.sessionWarning.time_remaining, window.sessionWarning.message);
        }
    }
    
    /**
     * Bắt đầu kiểm tra định kỳ
     */
    startPeriodicCheck() {
        this.checkInterval = setInterval(() => {
            this.checkSessionStatus();
        }, this.checkIntervalTime);
    }
    
    /**
     * Kiểm tra trạng thái session
     */
    async checkSessionStatus() {
        try {
            const response = await fetch('/api/v1/session/status', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.handleSessionStatus(data);
            }
        } catch (error) {
            console.warn('Session check failed:', error);
        }
    }
    
    /**
     * Xử lý trạng thái session từ server
     */
    handleSessionStatus(data) {
        if (data.time_remaining <= 0) {
            this.showExpiredMessage();
            return;
        }
        
        if (data.time_remaining <= this.warningThreshold && !this.warningShown) {
            this.showWarning(data.time_remaining, data.message);
        }
        
        // Cập nhật thời gian còn lại
        this.updateTimeRemaining(data.time_remaining);
    }
    
    /**
     * Hiển thị cảnh báo session
     */
    showWarning(timeRemaining, message) {
        if (this.warningShown) return;
        
        this.warningShown = true;
        
        // Tạo modal warning
        const modal = this.createWarningModal(timeRemaining, message);
        document.body.appendChild(modal);
        
        // Hiển thị modal
        modal.style.display = 'block';
        modal.classList.add('show');
        
        // Bắt đầu countdown
        this.startCountdown(timeRemaining);
    }
    
    /**
     * Tạo modal warning
     */
    createWarningModal(timeRemaining, message) {
        const modal = document.createElement('div');
        modal.className = 'session-warning-modal';
        modal.innerHTML = `
            <div class="session-warning-content">
                <div class="session-warning-header">
                    <h3>⚠️ Cảnh báo phiên làm việc</h3>
                </div>
                <div class="session-warning-body">
                    <p>${message}</p>
                    <div class="session-warning-timer">
                        <span class="timer-label">Thời gian còn lại:</span>
                        <span class="timer-value" id="session-timer">${this.formatTime(timeRemaining)}</span>
                    </div>
                    <div class="session-warning-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" id="session-progress" style="width: ${(timeRemaining / 3600) * 100}%"></div>
                        </div>
                    </div>
                </div>
                <div class="session-warning-footer">
                    <button type="button" class="btn btn-primary" id="extend-session">
                        <i class="fas fa-clock"></i> Gia hạn phiên
                    </button>
                    <button type="button" class="btn btn-secondary" id="dismiss-warning">
                        <i class="fas fa-times"></i> Bỏ qua
                    </button>
                    <button type="button" class="btn btn-danger" id="logout-now">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất ngay
                    </button>
                </div>
            </div>
        `;
        
        // Thêm CSS
        this.addWarningStyles();
        
        return modal;
    }
    
    /**
     * Thêm CSS cho warning modal
     */
    addWarningStyles() {
        if (document.getElementById('session-warning-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'session-warning-styles';
        style.textContent = `
            .session-warning-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10000;
                display: none;
                align-items: center;
                justify-content: center;
            }
            
            .session-warning-modal.show {
                display: flex;
            }
            
            .session-warning-content {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                max-width: 500px;
                width: 90%;
                animation: slideIn 0.3s ease-out;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateY(-50px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            .session-warning-header {
                background: #ff9800;
                color: white;
                padding: 20px;
                border-radius: 10px 10px 0 0;
                text-align: center;
            }
            
            .session-warning-header h3 {
                margin: 0;
                font-size: 1.5em;
            }
            
            .session-warning-body {
                padding: 30px;
                text-align: center;
            }
            
            .session-warning-timer {
                margin: 20px 0;
                font-size: 1.2em;
            }
            
            .timer-value {
                font-weight: bold;
                color: #ff5722;
                font-size: 1.5em;
            }
            
            .session-warning-progress {
                margin: 20px 0;
            }
            
            .progress-bar {
                width: 100%;
                height: 10px;
                background: #e0e0e0;
                border-radius: 5px;
                overflow: hidden;
            }
            
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #ff5722, #ff9800);
                transition: width 1s ease;
            }
            
            .session-warning-footer {
                padding: 20px;
                text-align: center;
                border-top: 1px solid #e0e0e0;
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .session-warning-footer .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            
            .session-warning-footer .btn-primary {
                background: #2196f3;
                color: white;
            }
            
            .session-warning-footer .btn-primary:hover {
                background: #1976d2;
            }
            
            .session-warning-footer .btn-secondary {
                background: #757575;
                color: white;
            }
            
            .session-warning-footer .btn-secondary:hover {
                background: #616161;
            }
            
            .session-warning-footer .btn-danger {
                background: #f44336;
                color: white;
            }
            
            .session-warning-footer .btn-danger:hover {
                background: #d32f2f;
            }
        `;
        
        document.head.appendChild(style);
    }
    
    /**
     * Bắt đầu countdown
     */
    startCountdown(timeRemaining) {
        let timeLeft = timeRemaining;
        
        this.warningInterval = setInterval(() => {
            timeLeft--;
            
            if (timeLeft <= 0) {
                this.showExpiredMessage();
                return;
            }
            
            this.updateCountdown(timeLeft);
        }, 1000);
    }
    
    /**
     * Cập nhật countdown
     */
    updateCountdown(timeLeft) {
        const timerElement = document.getElementById('session-timer');
        const progressElement = document.getElementById('session-progress');
        
        if (timerElement) {
            timerElement.textContent = this.formatTime(timeLeft);
        }
        
        if (progressElement) {
            const percentage = (timeLeft / 3600) * 100;
            progressElement.style.width = Math.max(0, percentage) + '%';
        }
    }
    
    /**
     * Cập nhật thời gian còn lại
     */
    updateTimeRemaining(timeRemaining) {
        // Có thể cập nhật UI khác ở đây
        console.log('Session time remaining:', timeRemaining);
    }
    
    /**
     * Hiển thị thông báo hết hạn
     */
    showExpiredMessage() {
        this.clearIntervals();
        
        alert('Phiên làm việc của bạn đã hết hạn. Bạn sẽ được chuyển đến trang đăng nhập.');
        window.location.href = '/login?timeout=1';
    }
    
    /**
     * Bind events
     */
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.id === 'extend-session') {
                this.extendSession();
            } else if (e.target.id === 'dismiss-warning') {
                this.dismissWarning();
            } else if (e.target.id === 'logout-now') {
                this.logoutNow();
            }
        });
        
        // Extend session on user activity
        ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
            document.addEventListener(event, () => {
                this.onUserActivity();
            }, { passive: true });
        });
    }
    
    /**
     * Xử lý hoạt động của user
     */
    onUserActivity() {
        // Gia hạn session khi user hoạt động
        this.extendSession();
    }
    
    /**
     * Gia hạn session
     */
    async extendSession() {
        try {
            const response = await fetch('/api/v1/session/extend', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.hideWarning();
                    this.showSuccessMessage('Phiên làm việc đã được gia hạn thành công!');
                }
            }
        } catch (error) {
            console.error('Extend session failed:', error);
        }
    }
    
    /**
     * Bỏ qua warning
     */
    async dismissWarning() {
        try {
            const response = await fetch('/api/v1/session/dismiss-warning', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                this.hideWarning();
            }
        } catch (error) {
            console.error('Dismiss warning failed:', error);
        }
    }
    
    /**
     * Đăng xuất ngay
     */
    logoutNow() {
        window.location.href = '/logout';
    }
    
    /**
     * Ẩn warning
     */
    hideWarning() {
        const modal = document.querySelector('.session-warning-modal');
        if (modal) {
            modal.remove();
        }
        this.warningShown = false;
        this.clearIntervals();
    }
    
    /**
     * Xóa intervals
     */
    clearIntervals() {
        if (this.warningInterval) {
            clearInterval(this.warningInterval);
            this.warningInterval = null;
        }
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }
    
    /**
     * Hiển thị thông báo thành công
     */
    showSuccessMessage(message) {
        // Tạo toast notification
        const toast = document.createElement('div');
        toast.className = 'session-toast success';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4caf50;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10001;
            animation: slideInRight 0.3s ease-out;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    /**
     * Format thời gian
     */
    formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        } else {
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }
    }
}

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', () => {
    new SessionSecurity();
});
