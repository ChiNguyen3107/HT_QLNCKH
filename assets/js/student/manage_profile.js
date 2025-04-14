/**
 * JavaScript cho trang quản lý hồ sơ sinh viên
 */

$(document).ready(function() {
    // Khởi tạo tooltip Bootstrap
    $('[data-toggle="tooltip"]').tooltip();
    
    // Kích hoạt modal khi nhấn vào trường thông tin lớp học chỉ đọc
    $('#LOP_MA, #LOP_TEN').click(function() {
        $('#classInfoModal').modal('show');
    });
    
    // Hiệu ứng khi di chuột qua trường chỉ đọc
    $('.form-control[readonly]').hover(
        function() {
            $(this).css('cursor', 'pointer');
            $(this).css('background-color', '#e9ecef');
        }, 
        function() {
            $(this).css('background-color', '#f8f9fa');
        }
    );
    
    // Xử lý form submit
    $('form').submit(function(e) {
        // Xác thực form trước khi submit
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // Hiệu ứng loading khi submit form
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang cập nhật...');
        submitBtn.prop('disabled', true);
        
        // Form vẫn được submit bình thường (không sử dụng AJAX)
        return true;
    });
    
    // Kiểm tra email hợp lệ khi thay đổi
    $('#SV_EMAIL').on('input', function() {
        validateEmail($(this).val());
    });
    
    // Kiểm tra số điện thoại hợp lệ khi thay đổi
    $('#SV_SDT').on('input', function() {
        validatePhone($(this).val());
    });
    
    // Kiểm tra họ và tên khi thay đổi
    $('#SV_HOSV, #SV_TENSV').on('input', function() {
        validateName($(this).attr('id'), $(this).val());
    });
    
    /**
     * Hàm xác thực form trước khi submit
     */
    function validateForm() {
        let isValid = true;
        
        // Kiểm tra email
        if (!validateEmail($('#SV_EMAIL').val())) {
            isValid = false;
        }
        
        // Kiểm tra số điện thoại
        if (!validatePhone($('#SV_SDT').val())) {
            isValid = false;
        }
        
        // Kiểm tra họ và tên
        if (!validateName('SV_HOSV', $('#SV_HOSV').val()) || 
            !validateName('SV_TENSV', $('#SV_TENSV').val())) {
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Kiểm tra email hợp lệ
     */
    function validateEmail(email) {
        // Regex cho email
        const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        const isValid = emailRegex.test(email);
        
        const emailField = $('#SV_EMAIL');
        if (!isValid) {
            emailField.addClass('is-invalid');
            
            // Thêm feedback nếu chưa có
            if (emailField.next('.invalid-feedback').length === 0) {
                emailField.after('<div class="invalid-feedback">Email không hợp lệ</div>');
            }
        } else {
            emailField.removeClass('is-invalid');
            emailField.next('.invalid-feedback').remove();
        }
        
        return isValid;
    }
    
    /**
     * Kiểm tra số điện thoại hợp lệ
     */
    function validatePhone(phone) {
        // Regex cho số điện thoại (10-11 số)
        const phoneRegex = /^[0-9]{10,11}$/;
        const isValid = phoneRegex.test(phone);
        
        const phoneField = $('#SV_SDT');
        if (!isValid) {
            phoneField.addClass('is-invalid');
            
            // Thêm feedback nếu chưa có
            if (phoneField.next('.invalid-feedback').length === 0) {
                phoneField.after('<div class="invalid-feedback">Số điện thoại phải có 10-11 chữ số</div>');
            }
        } else {
            phoneField.removeClass('is-invalid');
            phoneField.next('.invalid-feedback').remove();
        }
        
        return isValid;
    }
    
    /**
     * Kiểm tra họ và tên hợp lệ
     */
    function validateName(fieldId, name) {
        // Kiểm tra tên không được để trống và không chứa ký tự đặc biệt
        const nameRegex = /^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]+$/;
        const isValid = nameRegex.test(name) && name.trim() !== '';
        
        const nameField = $('#' + fieldId);
        if (!isValid) {
            nameField.addClass('is-invalid');
            
            // Thêm feedback nếu chưa có
            if (nameField.next('.invalid-feedback').length === 0) {
                nameField.after('<div class="invalid-feedback">Tên không hợp lệ</div>');
            }
        } else {
            nameField.removeClass('is-invalid');
            nameField.next('.invalid-feedback').remove();
        }
        
        return isValid;
    }
    
    // Hiệu ứng hiển thị trang
    animatePageLoad();
    
    /**
     * Hiệu ứng khi tải trang
     */
    function animatePageLoad() {
        // Hiệu ứng fade-in cho content
        $('.page-title').css('opacity', 0).animate({
            opacity: 1
        }, 500);
        
        // Hiệu ứng slide-down cho form
        $('.profile-card').css('opacity', 0).animate({
            opacity: 1
        }, 700);
    }
});