/**
 * fixed-sidebar-permanent.js
 * JavaScript để vô hiệu hóa hoàn toàn chức năng thu gọn sidebar
 */

(function() {
    'use strict';
    
    function initializePermanentSidebar() {
        // Đợi jQuery load
        if (typeof $ === 'undefined') {
            setTimeout(initializePermanentSidebar, 50);
            return;
        }
        
        $(document).ready(function() {
            // Xóa tất cả các class liên quan đến sidebar toggled
            $('body').removeClass('sidebar-toggled');
            
            // Đảm bảo sidebar luôn có class fixed-sidebar
            $('.modern-sidebar').addClass('fixed-sidebar');
            
            // Ẩn hoàn toàn tất cả các nút toggle
            $('.sidebar-collapse-toggle').hide();
            $('.toggle-sidebar-btn').hide();
            
            // Vô hiệu hóa tất cả các event listener trên nút toggle
            $('.sidebar-collapse-toggle').off('click');
            $('.toggle-sidebar-btn').off('click');
            
            // Ngăn chặn thêm class sidebar-toggled
            const originalAddClass = $.fn.addClass;
            $.fn.addClass = function(className) {
                if (typeof className === 'string' && className.includes('sidebar-toggled')) {
                    return this; // Không thêm class sidebar-toggled
                }
                return originalAddClass.call(this, className);
            };
            
            // Ngăn chặn toggle class sidebar-toggled
            const originalToggleClass = $.fn.toggleClass;
            $.fn.toggleClass = function(className) {
                if (typeof className === 'string' && className.includes('sidebar-toggled')) {
                    return this; // Không toggle class sidebar-toggled
                }
                return originalToggleClass.call(this, className);
            };
            
            // Ghi đè localStorage để không lưu trạng thái collapsed
            const originalSetItem = localStorage.setItem;
            localStorage.setItem = function(key, value) {
                if (key === 'sidebar-collapsed') {
                    return; // Không lưu trạng thái collapsed
                }
                return originalSetItem.call(this, key, value);
            };
            
            // Xóa trạng thái collapsed đã lưu
            localStorage.removeItem('sidebar-collapsed');
            
            // Đảm bảo content-wrapper luôn có margin đúng
            function ensureCorrectLayout() {
                $('#content-wrapper').css({
                    'margin-left': '260px',
                    'width': 'calc(100% - 260px)'
                });
                
                $('.modern-sidebar').css({
                    'width': '260px',
                    'transform': 'none',
                    'display': 'block'
                });
            }
            
            // Thực hiện ngay lập tức
            ensureCorrectLayout();
            
            // Theo dõi và sửa lỗi mỗi khi có thay đổi
            setInterval(ensureCorrectLayout, 100);
            
            // Ngăn chặn các event có thể gây ra thu gọn sidebar
            $(document).on('click', '.sidebar-collapse-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            $(document).on('click', '.toggle-sidebar-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            // Ngăn chặn keyboard shortcuts có thể ảnh hưởng đến sidebar
            $(document).on('keydown', function(e) {
                // Ngăn chặn Alt + S hoặc các phím tắt khác
                if ((e.altKey && e.key === 's') || (e.ctrlKey && e.key === 'b')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Theo dõi mutation để đảm bảo sidebar không bị thay đổi
            if (typeof MutationObserver !== 'undefined') {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            const target = $(mutation.target);
                            if (target.hasClass('sidebar-toggled')) {
                                target.removeClass('sidebar-toggled');
                            }
                        }
                    });
                });
                
                observer.observe(document.body, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
            
            // Đảm bảo sidebar luôn hiển thị đầy đủ khi window resize
            $(window).on('resize', function() {
                ensureCorrectLayout();
            });
            
            console.log('Permanent fixed sidebar initialized successfully');
        });
    }
    
    // Khởi tạo
    initializePermanentSidebar();
})();
