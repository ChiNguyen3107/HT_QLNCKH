/**
 * sidebar-checker.js 
 * Kiểm tra xem sidebar mới có hoạt động không
 */

document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra xem sidebar mới đã được kích hoạt chưa
    const modernSidebar = document.querySelector('.modern-sidebar');
    
    if (modernSidebar) {
        console.log('✓ Modern Sidebar được tìm thấy và đang hoạt động!');
        
        // Thêm class vào body để hiển thị thông báo
        setTimeout(function() {
            const notification = document.createElement('div');
            notification.className = 'sidebar-notification';
            notification.innerHTML = `
                <div class="sidebar-notification-content">
                    <i class="fas fa-check-circle"></i>
                    <span>Modern Sidebar đã được kích hoạt!</span>
                </div>
                <button class="sidebar-notification-close">&times;</button>
            `;
            
            document.body.appendChild(notification);
            
            // Hiệu ứng hiển thị
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            // Tự động ẩn sau 5 giây
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
            
            // Xử lý nút đóng
            const closeButton = notification.querySelector('.sidebar-notification-close');
            closeButton.addEventListener('click', () => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            });
        }, 2000);
        
        // Thêm CSS cho thông báo
        const style = document.createElement('style');
        style.textContent = `
            .sidebar-notification {
                position: fixed;
                bottom: 20px;
                right: -300px;
                background: #fff;
                box-shadow: 0 0 10px rgba(0,0,0,0.2);
                border-left: 4px solid #3a66db;
                padding: 12px 15px;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                z-index: 1060;
                transition: right 0.3s ease;
                max-width: 300px;
            }
            
            .sidebar-notification.show {
                right: 20px;
            }
            
            .sidebar-notification-content {
                display: flex;
                align-items: center;
            }
            
            .sidebar-notification i {
                color: #3a66db;
                font-size: 18px;
                margin-right: 10px;
            }
            
            .sidebar-notification-close {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #666;
                margin-left: 15px;
            }
        `;
        document.head.appendChild(style);
    } else {
        console.error('✗ Modern Sidebar không được tìm thấy! Hãy kiểm tra các file CSS và HTML.');
    }
});
