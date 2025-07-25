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
            
            // Enhanced table interactions
            $('.table tbody tr').hover(
                function() {
                    $(this).addClass('table-active');
                },
                function() {
                    $(this).removeClass('table-active');
                }
            );
            
            // Smooth scrolling for anchor links
            $('a[href^="#"]').on('click', function(event) {
                var href = this.getAttribute('href');
                
                // Skip empty or just '#' hrefs (like dropdown toggles)
                if (!href || href === '#' || href.trim() === '') {
                    return; // Don't prevent default, let normal handling occur
                }
                
                var target = $(href);
                if (target.length) {
                    event.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 100
                    }, 1000);
                }
            });
            
            // Auto-hide alerts after 5 seconds
            $('.alert').each(function() {
                var alert = $(this);
                setTimeout(function() {
                    alert.fadeOut();
                }, 5000);
            });
            
            // Ripple effect for buttons
            $('.btn').on('click', function(e) {
                var button = $(this);
                var ripple = $('<span class="ripple"></span>');
                
                var diameter = Math.max(button.width(), button.height());
                var radius = diameter / 2;
                
                ripple.css({
                    width: diameter,
                    height: diameter,
                    left: e.pageX - button.offset().left - radius,
                    top: e.pageY - button.offset().top - radius
                }).appendTo(button);
                
                setTimeout(function() {
                    ripple.remove();
                }, 700);
            });
            
            console.log('Research UI Handler initialized successfully');
        });
    }
    
    // Start initialization
    initializeUIHandler();
})();
