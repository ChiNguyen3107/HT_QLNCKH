/**
 * CSRF Protection JavaScript
 * 
 * Cung cấp các chức năng JavaScript để xử lý CSRF protection
 * cho AJAX requests và form submissions
 */

class CSRFProtection {
    constructor() {
        this.tokenName = null;
        this.tokenValue = null;
        this.initialized = false;
        
        this.init();
    }
    
    /**
     * Khởi tạo CSRF protection
     */
    init() {
        // Lấy CSRF token từ meta tag
        const metaTag = document.querySelector('meta[name="_csrf_token"]');
        if (metaTag) {
            this.tokenName = metaTag.getAttribute('name');
            this.tokenValue = metaTag.getAttribute('content');
        }
        
        // Lấy CSRF token từ global variable
        if (window.csrfToken) {
            this.tokenName = window.csrfToken.name;
            this.tokenValue = window.csrfToken.value;
        }
        
        if (this.tokenName && this.tokenValue) {
            this.initialized = true;
            this.setupAjaxProtection();
            this.setupFormProtection();
        } else {
            console.warn('CSRF Protection: Không thể tìm thấy CSRF token');
        }
    }
    
    /**
     * Thiết lập bảo vệ cho AJAX requests
     */
    setupAjaxProtection() {
        // Override jQuery AJAX nếu có
        if (typeof $ !== 'undefined') {
            this.setupJQueryAjax();
        }
        
        // Override fetch API
        this.setupFetchAPI();
        
        // Override XMLHttpRequest
        this.setupXMLHttpRequest();
    }
    
    /**
     * Thiết lập bảo vệ cho jQuery AJAX
     */
    setupJQueryAjax() {
        const self = this;
        
        $(document).ajaxSend(function(event, xhr, settings) {
            if (self.shouldAddCSRFToken(settings)) {
                xhr.setRequestHeader('X-CSRF-Token', self.tokenValue);
            }
        });
        
        // Xử lý lỗi CSRF
        $(document).ajaxError(function(event, xhr, settings, error) {
            if (xhr.status === 403 && xhr.responseText.includes('CSRF_TOKEN_INVALID')) {
                self.handleCSRFError();
            }
        });
    }
    
    /**
     * Thiết lập bảo vệ cho Fetch API
     */
    setupFetchAPI() {
        const self = this;
        const originalFetch = window.fetch;
        
        window.fetch = function(url, options = {}) {
            if (self.shouldAddCSRFToken(options)) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-Token'] = self.tokenValue;
            }
            
            return originalFetch(url, options).catch(error => {
                if (error.status === 403) {
                    self.handleCSRFError();
                }
                throw error;
            });
        };
    }
    
    /**
     * Thiết lập bảo vệ cho XMLHttpRequest
     */
    setupXMLHttpRequest() {
        const self = this;
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
            this._method = method;
            this._url = url;
            return originalOpen.call(this, method, url, async, user, password);
        };
        
        XMLHttpRequest.prototype.send = function(data) {
            if (self.shouldAddCSRFToken({ method: this._method, url: this._url })) {
                this.setRequestHeader('X-CSRF-Token', self.tokenValue);
            }
            
            // Xử lý lỗi CSRF
            this.addEventListener('error', function() {
                if (this.status === 403) {
                    self.handleCSRFError();
                }
            });
            
            return originalSend.call(this, data);
        };
    }
    
    /**
     * Thiết lập bảo vệ cho forms
     */
    setupFormProtection() {
        const self = this;
        
        // Thêm CSRF token vào tất cả forms
        document.addEventListener('DOMContentLoaded', function() {
            self.addCSRFTokenToForms();
        });
        
        // Xử lý form submission
        document.addEventListener('submit', function(e) {
            if (!self.validateFormCSRF(e.target)) {
                e.preventDefault();
                self.showCSRFError('Lỗi bảo mật: CSRF token không hợp lệ. Vui lòng tải lại trang và thử lại.');
                return false;
            }
        });
    }
    
    /**
     * Thêm CSRF token vào tất cả forms
     */
    addCSRFTokenToForms() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (!form.querySelector(`input[name="${this.tokenName}"]`)) {
                const csrfField = document.createElement('input');
                csrfField.type = 'hidden';
                csrfField.name = this.tokenName;
                csrfField.value = this.tokenValue;
                form.appendChild(csrfField);
            }
        });
    }
    
    /**
     * Kiểm tra xem có nên thêm CSRF token không
     * 
     * @param {Object} settings AJAX settings hoặc options
     * @returns {boolean} True nếu nên thêm CSRF token
     */
    shouldAddCSRFToken(settings) {
        if (!this.initialized) return false;
        
        const method = (settings.method || settings.type || 'GET').toUpperCase();
        const url = settings.url || '';
        
        // Không thêm CSRF token cho GET requests
        if (method === 'GET' || method === 'HEAD' || method === 'OPTIONS') {
            return false;
        }
        
        // Không thêm CSRF token cho các URL được loại trừ
        const excludedUrls = [
            '/api/v1/auth/login',
            '/api/v1/auth/logout',
            '/api/v1/auth/refresh',
            '/login',
            '/logout'
        ];
        
        for (const excludedUrl of excludedUrls) {
            if (url.includes(excludedUrl)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate CSRF token cho form
     * 
     * @param {HTMLFormElement} form Form element
     * @returns {boolean} True nếu token hợp lệ
     */
    validateFormCSRF(form) {
        if (!this.initialized) return true;
        
        const csrfField = form.querySelector(`input[name="${this.tokenName}"]`);
        return csrfField && csrfField.value === this.tokenValue;
    }
    
    /**
     * Xử lý lỗi CSRF
     */
    handleCSRFError() {
        this.showCSRFError('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.');
        
        // Tự động reload trang sau 3 giây
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }
    
    /**
     * Hiển thị thông báo lỗi CSRF
     * 
     * @param {string} message Thông báo lỗi
     */
    showCSRFError(message) {
        // Tạo thông báo lỗi
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger csrf-error';
        errorDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        `;
        errorDiv.innerHTML = `
            <strong>Lỗi Bảo Mật:</strong><br>
            ${message}
            <button type="button" class="close" onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        `;
        
        // Thêm vào DOM
        document.body.appendChild(errorDiv);
        
        // Tự động ẩn sau 10 giây
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 10000);
    }
    
    /**
     * Lấy CSRF token hiện tại
     * 
     * @returns {Object} Object chứa token name và value
     */
    getToken() {
        return {
            name: this.tokenName,
            value: this.tokenValue
        };
    }
    
    /**
     * Cập nhật CSRF token
     * 
     * @param {string} newToken Token mới
     */
    updateToken(newToken) {
        this.tokenValue = newToken;
        
        // Cập nhật meta tag
        const metaTag = document.querySelector('meta[name="_csrf_token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }
        
        // Cập nhật global variable
        if (window.csrfToken) {
            window.csrfToken.value = newToken;
        }
        
        // Cập nhật tất cả forms
        this.addCSRFTokenToForms();
    }
    
    /**
     * Refresh CSRF token
     */
    async refreshToken() {
        try {
            const response = await fetch('/api/v1/csrf/refresh', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.tokenValue,
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.token) {
                    this.updateToken(data.token);
                }
            }
        } catch (error) {
            console.error('Lỗi khi refresh CSRF token:', error);
        }
    }
}

// Khởi tạo CSRF protection khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    window.csrfProtection = new CSRFProtection();
});

// Export cho module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CSRFProtection;
}
