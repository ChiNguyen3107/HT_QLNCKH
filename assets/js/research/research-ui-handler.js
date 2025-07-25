/**
 * research-ui-handler.js
 * Xử lý các hành vi giao diện thống nhất cho tất cả các trang research
 */

// Ensure jQuery is loaded before executing
(function() {
    function initializeUIHandler() {
        if (typeof $ === 'undefined') {
            setTimeout(initializeUIHandler, 50);
            return;
        }
        
        $(document).ready(function() {
            // Kiểm tra và thêm favicon nếu không tồn tại
            if (!$('link[rel="icon"]').length) {
                $('head').append('<link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">');
                $('head').append('<link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">');
            }
            
            // Thêm hiệu ứng mượt cho các thành phần
            $('.card').addClass('animate-on-scroll');
            
            // Kích hoạt animation khi cuộn
            function animateOnScroll() {
                $('.animate-on-scroll').each(function() {
                    const elementTop = $(this).offset().top;
                    const elementHeight = $(this).outerHeight();
            const windowHeight = $(window).height();
            const scrollY = window.scrollY;
            
            const delay = parseInt($(this).data('delay')) || 0;
            
            if (elementTop < (scrollY + windowHeight - elementHeight / 2)) {
                setTimeout(() => {
                    $(this).addClass('visible');
                }, delay);
            }
        });
    }
    
    // Chạy animation khi trang vừa tải
    setTimeout(function() {
        animateOnScroll();
    }, 100);
    
    // Chạy animation khi cuộn
    $(window).on('scroll', function() {
        animateOnScroll();
    });
    
    // Xử lý DataTables nếu có
    if ($.fn.DataTable) {
        // Cấu hình chung cho tất cả DataTables
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json",
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ mục",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
                infoEmpty: "Hiển thị 0 đến 0 của 0 mục",
                infoFiltered: "(lọc từ _MAX_ mục)",
                paginate: {
                    first: "Đầu tiên",
                    last: "Cuối cùng",
                    next: "Tiếp",
                    previous: "Trước"
                }
            },
            responsive: true,
            autoWidth: false
        });
        
        // Tự động áp dụng DataTables cho bảng có class datatable
        $('.datatable').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable();
            }
        });
    }
    
    // Xử lý đóng thông báo tự động sau 5 giây
    $('.alert:not(.alert-permanent)').delay(5000).fadeOut(500);
    
    // Đảm bảo các dropdown hoạt động tốt trên mobile
    $('.dropdown-toggle').dropdown();
    
    // Thêm hiệu ứng ripple cho các nút
    $('.btn').addClass('ripple');
    $('.ripple').on('click', function(event) {
        const $btn = $(this);
        const offset = $btn.offset();
        const x = event.pageX - offset.left;
        const y = event.pageY - offset.top;
        
        const $ripple = $('<span class="btn-ripple"></span>');
        $ripple.css({
            top: y + 'px',
            left: x + 'px'
        });
        
        $btn.append($ripple);
        
        setTimeout(function() {
            $ripple.remove();
        }, 700);
    });
    
    // Thêm CSS cho hiệu ứng ripple nếu chưa có
    if ($('#ripple-style').length === 0) {
        $('head').append(`
            <style id="ripple-style">
                .btn {
                    position: relative;
                    overflow: hidden;
                }
                .btn-ripple {
                    position: absolute;
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.7s linear;
                    background-color: rgba(255, 255, 255, 0.4);
                    width: 100px;
                    height: 100px;
                    pointer-events: none;
                }
                @keyframes ripple {
                    to {
                        transform: scale(2.5);
                        opacity: 0;
                    }
                }
            </style>
        `);
        });
    }
    
    // Start initialization
    initializeUIHandler();
})();
