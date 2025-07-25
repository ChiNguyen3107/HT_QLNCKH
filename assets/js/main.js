/**
 * JavaScript chung cho toàn bộ hệ thống
 */

// Hàm để đảm bảo favicon được thêm vào mọi trang
function ensureFavicon() {
    if (!document.querySelector('link[rel*="icon"]')) {
        const link = document.createElement('link');
        link.type = 'image/x-icon';
        link.rel = 'shortcut icon';
        link.href = '/NLNganh/favicon.ico';
        document.getElementsByTagName('head')[0].appendChild(link);
        
        const linkIcon = document.createElement('link');
        linkIcon.type = 'image/x-icon';
        linkIcon.rel = 'icon';
        linkIcon.href = '/NLNganh/favicon.ico';
        document.getElementsByTagName('head')[0].appendChild(linkIcon);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Đảm bảo favicon có trong trang
    ensureFavicon();
    // Tham chiếu đến các phần tử
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const content = document.querySelector('.content');
    const dropdownItems = document.querySelectorAll('.sidebar-dropdown');
    
    // Kiểm tra trạng thái sidebar từ localStorage
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        content.classList.add('expanded');
    }

    // Xử lý sự kiện click vào nút toggle sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
            // Lưu trạng thái vào localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }

    // Xử lý sự kiện click vào các mục dropdown
    dropdownItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('open');
            // Đóng các dropdown khác
            dropdownItems.forEach(function(otherItem) {
                if (otherItem !== item) {
                    otherItem.parentElement.classList.remove('open');
                }
            });
        });
    });

    // Xử lý nút active dựa trên URL hiện tại
    const currentUrl = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar-link, .sidebar-sublink');
    
    sidebarLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && currentUrl.includes(href)) {
            link.classList.add('active');
            // Nếu là submenu, mở menu cha
            const parent = link.closest('.sidebar-item');
            if (parent) {
                parent.classList.add('open');
            }
        }
    });

    // Hiệu ứng fade-in cho các phần tử
    const fadeElements = document.querySelectorAll('[style*="opacity: 0"]');
    fadeElements.forEach(function(element, index) {
        setTimeout(function() {
            element.style.transition = 'opacity 0.5s ease-in-out';
            element.style.opacity = '1';
        }, index * 150); // Thêm độ trễ cho mỗi phần tử
    });

    // Xử lý các modal
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('data-target'));
            if (target) {
                target.classList.add('show');
                document.body.style.overflow = 'hidden'; // Ngăn scroll của body
            }
        });
    });

    // Đóng modal khi click vào nút đóng hoặc bên ngoài
    const modalCloseButtons = document.querySelectorAll('.modal-close, [data-dismiss="modal"]');
    modalCloseButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = ''; // Khôi phục scroll của body
            }
        });
    });

    // Đóng modal khi click vào nền mờ
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) { // Chỉ khi click vào phần nền, không phải modal-dialog
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // Xử lý nút nhấn "Enter" trong form tìm kiếm
    const searchForms = document.querySelectorAll('.search-form');
    searchForms.forEach(function(form) {
        const searchInput = form.querySelector('input[type="search"]');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    form.submit();
                }
            });
        }
    });

    // Xử lý toast messages
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(function(toast) {
        // Hiển thị toast
        toast.classList.add('show');
        
        // Tự động ẩn sau 5 giây
        setTimeout(function() {
            toast.classList.remove('show');
            // Xóa khỏi DOM sau khi animation kết thúc
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 5000);
        
        // Xử lý nút đóng toast
        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                toast.classList.remove('show');
                setTimeout(function() {
                    toast.remove();
                }, 300);
            });
        }
    });

    // Xử lý custom dropdown
    const dropdowns = document.querySelectorAll('.custom-dropdown');
    dropdowns.forEach(function(dropdown) {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (trigger && menu) {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                menu.classList.toggle('show');
            });
            
            // Đóng dropdown khi click bên ngoài
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target)) {
                    menu.classList.remove('show');
                }
            });
        }
    });

    // Xử lý tabs
    const tabLinks = document.querySelectorAll('[data-toggle="tab"]');
    tabLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Xóa active của tất cả tab links
            tabLinks.forEach(function(tab) {
                tab.classList.remove('active');
            });
            
            // Thêm active cho tab hiện tại
            this.classList.add('active');
            
            // Lấy ID của nội dung tab
            const tabId = this.getAttribute('href');
            
            // Ẩn tất cả tab content
            const tabContents = document.querySelectorAll('.tab-content .tab-pane');
            tabContents.forEach(function(content) {
                content.classList.remove('active');
            });
            
            // Hiện tab content tương ứng
            const activeContent = document.querySelector(tabId);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });

    // Khởi tạo tooltips
    const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
    tooltips.forEach(function(tooltip) {
        tooltip.addEventListener('mouseenter', function() {
            const title = this.getAttribute('title') || this.getAttribute('data-title');
            if (!title) return;
            
            // Tạo tooltip element
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'tooltip';
            tooltipEl.textContent = title;
            document.body.appendChild(tooltipEl);
            
            // Định vị tooltip
            const rect = this.getBoundingClientRect();
            tooltipEl.style.top = (rect.top + window.scrollY - tooltipEl.offsetHeight - 10) + 'px';
            tooltipEl.style.left = (rect.left + window.scrollX + (rect.width / 2) - (tooltipEl.offsetWidth / 2)) + 'px';
            
            // Hiển thị tooltip
            setTimeout(() => {
                tooltipEl.classList.add('show');
            }, 10);
            
            // Lưu tham chiếu
            this._tooltip = tooltipEl;
            
            // Xóa title để tránh tooltip mặc định
            this.setAttribute('data-title', title);
            this.removeAttribute('title');
        });
        
        tooltip.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.classList.remove('show');
                setTimeout(() => {
                    if (this._tooltip) {
                        this._tooltip.remove();
                        this._tooltip = null;
                    }
                }, 300);
            }
        });
    });

    // Xử lý nút "Xem thêm" cho text truncate
    const showMoreButtons = document.querySelectorAll('.show-more');
    showMoreButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.truncate-text');
            if (parent) {
                const fullText = parent.getAttribute('data-full-text');
                const shortText = parent.getAttribute('data-short-text');
                
                if (this.textContent.includes('Xem thêm')) {
                    parent.innerHTML = fullText + ' <a href="#" class="show-more">Thu gọn</a>';
                } else {
                    parent.innerHTML = shortText + ' <a href="#" class="show-more">Xem thêm</a>';
                }
                
                // Gắn lại sự kiện cho nút mới
                const newButton = parent.querySelector('.show-more');
                if (newButton) {
                    newButton.addEventListener('click', arguments.callee);
                }
            }
        });
    });

    // Khởi tạo datepicker nếu có
    const datepickers = document.querySelectorAll('.datepicker');
    if (typeof flatpickr !== 'undefined') {
        flatpickr(datepickers, {
            dateFormat: "d/m/Y",
            locale: "vn",
            allowInput: true
        });
    }

    // Khởi tạo select2 nếu có
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Chọn...',
            allowClear: true
        });
    }
});

/**
 * Hiển thị thông báo toast
 * @param {string} message Nội dung thông báo
 * @param {string} type Loại thông báo: success, error, warning, info
 * @param {number} duration Thời gian hiển thị (ms)
 */
function showToast(message, type = 'info', duration = 5000) {
    // Tạo container nếu chưa có
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Tạo toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Nội dung toast
    toast.innerHTML = `
        <div class="toast-header">
            <i class="toast-icon fas ${getIconByType(type)}"></i>
            <div class="toast-title">${getTitleByType(type)}</div>
            <button class="toast-close">&times;</button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    
    // Thêm vào container
    container.appendChild(toast);
    
    // Hiển thị sau khi đã thêm vào DOM
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Tự động ẩn sau duration
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, duration);
    
    // Xử lý nút đóng
    const closeBtn = toast.querySelector('.toast-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        });
    }
    
    // Hàm lấy icon tương ứng
    function getIconByType(type) {
        switch (type) {
            case 'success': return 'fa-check-circle';
            case 'error': return 'fa-times-circle';
            case 'warning': return 'fa-exclamation-triangle';
            default: return 'fa-info-circle';
        }
    }
    
    // Hàm lấy title tương ứng
    function getTitleByType(type) {
        switch (type) {
            case 'success': return 'Thành công';
            case 'error': return 'Lỗi';
            case 'warning': return 'Cảnh báo';
            default: return 'Thông báo';
        }
    }
}

/**
 * Hiển thị dialog xác nhận
 * @param {string} message Nội dung xác nhận
 * @param {Function} callback Hàm callback khi người dùng xác nhận
 * @param {string} title Tiêu đề dialog
 */
function confirmDialog(message, callback, title = 'Xác nhận') {
    // Tạo backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade';
    document.body.appendChild(backdrop);
    
    // Tạo dialog
    const dialog = document.createElement('div');
    dialog.className = 'confirm-dialog';
    dialog.innerHTML = `
        <div class="confirm-dialog-content">
            <div class="confirm-dialog-header">
                <h5>${title}</h5>
                <button type="button" class="confirm-dialog-close">&times;</button>
            </div>
            <div class="confirm-dialog-body">
                ${message}
            </div>
            <div class="confirm-dialog-footer">
                <button type="button" class="btn btn-secondary confirm-dialog-cancel">Hủy</button>
                <button type="button" class="btn btn-primary confirm-dialog-confirm">Xác nhận</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(dialog);
    document.body.style.overflow = 'hidden';
    
    // Hiển thị animation
    setTimeout(() => {
        backdrop.classList.add('show');
        dialog.classList.add('show');
    }, 10);
    
    // Xử lý đóng dialog
    function closeDialog() {
        dialog.classList.remove('show');
        backdrop.classList.remove('show');
        
        setTimeout(() => {
            dialog.remove();
            backdrop.remove();
            document.body.style.overflow = '';
        }, 300);
    }
    
    // Xử lý các nút
    const closeBtn = dialog.querySelector('.confirm-dialog-close');
    const cancelBtn = dialog.querySelector('.confirm-dialog-cancel');
    const confirmBtn = dialog.querySelector('.confirm-dialog-confirm');
    
    closeBtn.addEventListener('click', closeDialog);
    cancelBtn.addEventListener('click', closeDialog);
    
    confirmBtn.addEventListener('click', function() {
        if (typeof callback === 'function') {
            callback();
        }
        closeDialog();
    });
    
    // Đóng khi click backdrop
    backdrop.addEventListener('click', closeDialog);
}

/**
 * Khởi tạo biểu đồ
 * @param {string} elementId ID của canvas
 * @param {string} type Loại biểu đồ: bar, line, pie, doughnut
 * @param {object} data Dữ liệu biểu đồ
 * @param {object} options Tùy chọn biểu đồ
 * @return {Chart} Đối tượng biểu đồ
 */
function initChart(elementId, type, data, options = {}) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return null;
    }
    
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // Merge với options mặc định
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    return new Chart(ctx, {
        type: type,
        data: data,
        options: mergedOptions
    });
}

/**
 * Mã hóa HTML để tránh XSS
 * @param {string} html Chuỗi HTML cần mã hóa
 * @return {string} Chuỗi đã mã hóa
 */
function escapeHtml(html) {
    const div = document.createElement('div');
    div.textContent = html;
    return div.innerHTML;
}

/**
 * Tạo ID ngẫu nhiên
 * @param {string} prefix Tiền tố cho ID
 * @return {string} ID ngẫu nhiên
 */
function generateRandomId(prefix = 'id_') {
    return prefix + Math.random().toString(36).substr(2, 9);
}

/**
 * Format số theo định dạng tiền tệ
 * @param {number} number Số cần format
 * @param {number} decimals Số chữ số thập phân
 * @param {string} decimalSeparator Ký hiệu phân cách phần thập phân
 * @param {string} thousandSeparator Ký hiệu phân cách hàng nghìn
 * @return {string} Chuỗi đã format
 */
function formatNumber(number, decimals = 0, decimalSeparator = ',', thousandSeparator = '.') {
    const fixed = parseFloat(number).toFixed(decimals);
    const [intPart, decPart] = fixed.split('.');
    const intFormatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
    
    return decPart ? intFormatted + decimalSeparator + decPart : intFormatted;
}

/**
 * Format ngày tháng
 * @param {Date|string} date Ngày cần format
 * @param {string} format Định dạng ngày tháng
 * @return {string} Chuỗi ngày đã format
 */
function formatDate(date, format = 'dd/MM/yyyy') {
    const d = new Date(date);
    
    if (isNaN(d.getTime())) {
        return '';
    }
    
    const day = d.getDate().toString().padStart(2, '0');
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const year = d.getFullYear();
    const hours = d.getHours().toString().padStart(2, '0');
    const minutes = d.getMinutes().toString().padStart(2, '0');
    const seconds = d.getSeconds().toString().padStart(2, '0');
    
    return format
        .replace('dd', day)
        .replace('MM', month)
        .replace('yyyy', year)
        .replace('HH', hours)
        .replace('mm', minutes)
        .replace('ss', seconds);
}
