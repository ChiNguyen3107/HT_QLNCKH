/**
 * dashboard-enhanced.js
 * JavaScript cải tiến cho trang dashboard của research manager
 */

// Ensure jQuery is loaded before executing
(function() {
    'use strict';
    
    function initDashboard() {
        if (typeof $ === 'undefined') {
            setTimeout(initDashboard, 50);
            return;
        }
        
        $(document).ready(function() {
            // Enhanced counter animation với hiệu ứng đặc biệt
            function animateCounters() {
                $('.count-numbers').each(function () {
                    const $this = $(this);
                    const countTo = parseInt($this.text());
                    
                    // Thêm class để có hiệu ứng pulse
                    $this.addClass('counting');
                    
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 2000,
                        easing: 'swing',
                step: function() {
                    $this.text(Math.floor(this.countNum));
                },
                complete: function() {
                    $this.text(this.countNum);
                    $this.removeClass('counting');
                    
                    // Thêm hiệu ứng flash khi hoàn thành
                    $this.fadeOut(100).fadeIn(200);
                }
            });
        });
    }

    // Enhanced scroll animation
    function enhancedScrollAnimation() {
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

    // Cải thiện DataTables với hiệu ứng
    if ($.fn.DataTable) {
        $('#recentProjectsTable').DataTable({
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
            pageLength: 5,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            ordering: true,
            searching: true,
            paging: true,
            info: true,
            autoWidth: false,
            drawCallback: function() {
                // Thêm hiệu ứng fade in cho các row mới
                $(this.api().table().container()).find('tbody tr').hide().fadeIn(300);
            }
        });
    }

    // Thêm hiệu ứng hover cho quick access cards
    $('.quick-access-card').hover(
        function() {
            $(this).find('i').addClass('fa-pulse');
        },
        function() {
            $(this).find('i').removeClass('fa-pulse');
        }
    );

    // Thêm hiệu ứng loading cho charts
    function showChartLoading(chartId) {
        const chartContainer = document.getElementById(chartId).parentElement;
        chartContainer.style.position = 'relative';
        
        const loader = document.createElement('div');
        loader.className = 'chart-loader';
        loader.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>';
        loader.style.position = 'absolute';
        loader.style.top = '50%';
        loader.style.left = '50%';
        loader.style.transform = 'translate(-50%, -50%)';
        loader.style.zIndex = '1000';
        
        chartContainer.appendChild(loader);
        
        setTimeout(() => {
            loader.remove();
        }, 1500);
    }

    // Hiệu ứng parallax nhẹ cho header
    $(window).scroll(function() {
        const scrolled = $(this).scrollTop();
        $('.card-counter').css('transform', 'translateY(' + (scrolled * 0.1) + 'px)');
        
        enhancedScrollAnimation();
    });

    // Khởi tạo animations
    setTimeout(() => {
        enhancedScrollAnimation();
        animateCounters();
    }, 300);

    // Thêm hiệu ứng ripple cho buttons
    $('.btn').on('click', function(e) {
        const button = $(this);
        const ripple = $('<span class="ripple"></span>');
        
        button.append(ripple);
        
        const x = e.pageX - button.offset().left;
        const y = e.pageY - button.offset().top;
        
        ripple.css({
            left: x,
            top: y
        }).addClass('ripple-animate');
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });

    // Progressive enhancement cho các thông báo
    function enhanceNotifications() {
        $('.dropdown-item').hover(
            function() {
                $(this).find('.icon-circle').addClass('animated pulse');
            },
            function() {
                $(this).find('.icon-circle').removeClass('animated pulse');
            }
        );
    }

    enhanceNotifications();

    // Smooth scroll cho các internal links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 70
            }, 800, 'easeInOutQuart');
        }
    });

    // Auto-refresh cho real-time data (mỗi 5 phút)
    setInterval(function() {
        // Có thể thêm AJAX call để cập nhật data
        console.log('Checking for data updates...');
    }, 300000);
            
            // Initialize everything
            animateCounters();
            enhancedScrollAnimation();
            
            // Execute scroll animation on scroll
            $(window).on('scroll', function() {
                enhancedScrollAnimation();
            });
        });
    }
    
    // Start initialization
    initDashboard();
})();

// CSS cho ripple effect
$('<style>')
    .prop('type', 'text/css')
    .html(`
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .chart-loader {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 2rem;
        }
    `)
    .appendTo('head');
