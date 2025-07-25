/*
 * Enhanced Profile Page JavaScript
 * Advanced functionality for research manager profile page
 */

$(document).ready(function() {
    
    // Initialize enhanced profile functionality
    initializeProfileEnhancements();
    
    function initializeProfileEnhancements() {
        // Animation on scroll
        initScrollAnimations();
        
        // Password visibility toggle
        initPasswordToggle();
        
        // Form validation
        initFormValidation();
        
        // Auto-save functionality
        initAutoSave();
        
        // Loading states
        initLoadingStates();
        
        // Tooltip initialization
        initTooltips();
        
        // Enhanced interactions
        initEnhancedInteractions();
        
        console.log('Profile page enhancements initialized');
    }
    
    // Scroll animations
    function initScrollAnimations() {
        function animateOnScroll() {
            $('.animate-on-scroll').each(function() {
                const elementTop = $(this).offset().top;
                const elementHeight = $(this).outerHeight();
                const windowHeight = $(window).height();
                const scrollY = window.scrollY;
                
                const delay = parseInt($(this).data('delay')) || 0;
                const animation = $(this).data('animation') || 'fadeInUp';
                
                if (elementTop < (scrollY + windowHeight - elementHeight / 2)) {
                    setTimeout(() => {
                        $(this).addClass('visible').addClass(animation);
                    }, delay);
                }
            });
        }
        
        // Initial animation check
        setTimeout(animateOnScroll, 100);
        
        // Animation on scroll
        $(window).on('scroll', debounce(animateOnScroll, 100));
    }
    
    // Password visibility toggle
    function initPasswordToggle() {
        $('.toggle-password').on('click', function() {
            const targetId = $(this).data('target');
            const targetInput = $('#' + targetId);
            const icon = $(this).find('i');
            
            if (targetInput.attr('type') === 'password') {
                targetInput.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
                $(this).attr('title', 'Ẩn mật khẩu');
            } else {
                targetInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
                $(this).attr('title', 'Hiện mật khẩu');
            }
            
            // Add visual feedback
            $(this).addClass('clicked');
            setTimeout(() => {
                $(this).removeClass('clicked');
            }, 200);
        });
    }
    
    // Enhanced form validation
    function initFormValidation() {
        // Real-time validation for password change form
        $('#changePasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = $('#current_password').val();
            const newPassword = $('#new_password').val();
            const confirmPassword = $('#confirm_password').val();
            
            // Reset previous error states
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            
            let isValid = true;
            
            // Validate current password
            if (!currentPassword.trim()) {
                showFieldError('#current_password', 'Vui lòng nhập mật khẩu hiện tại');
                isValid = false;
            }
            
            // Validate new password
            if (!validatePassword(newPassword)) {
                showFieldError('#new_password', 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số');
                isValid = false;
            }
            
            // Validate confirm password
            if (newPassword !== confirmPassword) {
                showFieldError('#confirm_password', 'Mật khẩu xác nhận không khớp');
                isValid = false;
            }
            
            if (isValid) {
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                setButtonLoading(submitBtn, true);
                
                // Submit form after validation
                this.submit();
            }
        });
        
        // Real-time validation for profile update form
        $('#updateProfileForm').on('submit', function(e) {
            e.preventDefault();
            
            const email = $('#email').val();
            const phone = $('#phone').val();
            
            // Reset previous error states
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            
            let isValid = true;
            
            // Validate email
            if (!validateEmail(email)) {
                showFieldError('#email', 'Vui lòng nhập địa chỉ email hợp lệ');
                isValid = false;
            }
            
            // Validate phone (optional but must be valid if provided)
            if (phone && !validatePhone(phone)) {
                showFieldError('#phone', 'Số điện thoại không hợp lệ (10-11 số)');
                isValid = false;
            }
            
            if (isValid) {
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                setButtonLoading(submitBtn, true);
                
                // Submit form after validation
                this.submit();
            }
        });
        
        // Real-time validation on input
        $('#email').on('blur', function() {
            validateEmailField(this);
        });
        
        $('#phone').on('blur', function() {
            validatePhoneField(this);
        });
        
        $('#new_password').on('input', function() {
            validatePasswordField(this);
        });
        
        $('#confirm_password').on('input', function() {
            validateConfirmPasswordField(this);
        });
    }
    
    // Auto-save functionality (for future implementation)
    function initAutoSave() {
        let autoSaveTimer;
        const autoSaveDelay = 30000; // 30 seconds
        
        $('#updateProfileForm input, #updateProfileForm textarea, #updateProfileForm select').on('change', function() {
            clearTimeout(autoSaveTimer);
            
            // Show auto-save indicator
            showAutoSaveIndicator();
            
            autoSaveTimer = setTimeout(() => {
                // Auto-save logic would go here
                console.log('Auto-save triggered');
                hideAutoSaveIndicator();
            }, autoSaveDelay);
        });
    }
    
    // Loading states for buttons
    function initLoadingStates() {
        $('form').on('submit', function() {
            const submitBtn = $(this).find('button[type="submit"]');
            setButtonLoading(submitBtn, true);
        });
    }
    
    // Initialize tooltips
    function initTooltips() {
        $('[data-toggle="tooltip"]').tooltip();
        
        // Add helpful tooltips
        $('#current_password').attr('title', 'Nhập mật khẩu hiện tại của bạn');
        $('#new_password').attr('title', 'Mật khẩu mới phải có ít nhất 8 ký tự');
        $('#email').attr('title', 'Email sẽ được sử dụng để đăng nhập và nhận thông báo');
        
        // Initialize new tooltips
        $('[title]').tooltip();
    }
    
    // Enhanced interactions
    function initEnhancedInteractions() {
        // Hover effects for profile cards
        $('.profile-card').hover(
            function() {
                $(this).addClass('hovered');
            },
            function() {
                $(this).removeClass('hovered');
            }
        );
        
        // Click effect for buttons
        $('.btn').on('click', function() {
            $(this).addClass('clicked');
            setTimeout(() => {
                $(this).removeClass('clicked');
            }, 200);
        });
        
        // Focus management for modals
        $('#editProfileModal').on('shown.bs.modal', function() {
            $('#email').focus();
        });
        
        // Escape key to close modals
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.modal').modal('hide');
            }
        });
    }
    
    // Helper functions
    function validatePassword(password) {
        // At least 8 characters, one uppercase, one lowercase, one number
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
        return passwordRegex.test(password);
    }
    
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function validatePhone(phone) {
        const phoneRegex = /^[0-9]{10,11}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }
    
    function showFieldError(fieldSelector, message) {
        const field = $(fieldSelector);
        field.addClass('is-invalid');
        
        // Remove existing error message
        field.siblings('.invalid-feedback').remove();
        
        // Add error message
        field.after(`<div class="invalid-feedback">${message}</div>`);
        
        // Focus on first error field
        if ($('.is-invalid').length === 1) {
            field.focus();
        }
    }
    
    function validateEmailField(field) {
        const email = $(field).val();
        if (email && !validateEmail(email)) {
            showFieldError(field, 'Địa chỉ email không hợp lệ');
            return false;
        } else {
            $(field).removeClass('is-invalid').siblings('.invalid-feedback').remove();
            return true;
        }
    }
    
    function validatePhoneField(field) {
        const phone = $(field).val();
        if (phone && !validatePhone(phone)) {
            showFieldError(field, 'Số điện thoại không hợp lệ');
            return false;
        } else {
            $(field).removeClass('is-invalid').siblings('.invalid-feedback').remove();
            return true;
        }
    }
    
    function validatePasswordField(field) {
        const password = $(field).val();
        if (password && !validatePassword(password)) {
            showFieldError(field, 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số');
            return false;
        } else {
            $(field).removeClass('is-invalid').siblings('.invalid-feedback').remove();
            return true;
        }
    }
    
    function validateConfirmPasswordField(field) {
        const confirmPassword = $(field).val();
        const newPassword = $('#new_password').val();
        
        if (confirmPassword && confirmPassword !== newPassword) {
            showFieldError(field, 'Mật khẩu xác nhận không khớp');
            return false;
        } else {
            $(field).removeClass('is-invalid').siblings('.invalid-feedback').remove();
            return true;
        }
    }
    
    function setButtonLoading(button, loading) {
        if (loading) {
            button.addClass('loading').prop('disabled', true);
            const originalText = button.html();
            button.data('original-text', originalText);
            button.html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...');
        } else {
            button.removeClass('loading').prop('disabled', false);
            const originalText = button.data('original-text');
            if (originalText) {
                button.html(originalText);
            }
        }
    }
    
    function showAutoSaveIndicator() {
        if ($('.auto-save-indicator').length === 0) {
            $('body').append(`
                <div class="auto-save-indicator position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
                    <div class="alert alert-info alert-sm">
                        <i class="fas fa-save mr-2"></i>Tự động lưu sau 30 giây...
                    </div>
                </div>
            `);
        }
    }
    
    function hideAutoSaveIndicator() {
        $('.auto-save-indicator').fadeOut(function() {
            $(this).remove();
        });
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Auto dismiss alerts after 5 seconds
    $('.alert').delay(5000).fadeOut('slow');
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save profile
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            if ($('#editProfileModal').hasClass('show')) {
                $('#updateProfileForm').submit();
            }
        }
        
        // Ctrl/Cmd + E to edit profile
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            $('#editProfileModal').modal('show');
        }
    });
    
    // Print functionality
    window.printProfile = function() {
        window.print();
    };
    
    // Export profile data (for future implementation)
    window.exportProfile = function() {
        const profileData = {
            name: $('.profile-body h4').text(),
            email: $('#email').val(),
            phone: $('#phone').val(),
            department: $('#department').val(),
            exportDate: new Date().toISOString()
        };
        
        console.log('Profile data export:', profileData);
        // Implementation for actual export would go here
    };
    
});

// Additional utility functions available globally
window.ProfileUtils = {
    validateEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    validatePhone: function(phone) {
        const phoneRegex = /^[0-9]{10,11}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    },
    
    validatePassword: function(password) {
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
        return passwordRegex.test(password);
    }
};
