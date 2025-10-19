<?php
session_start();
require_once 'include/connect.php';
require_once 'app/Services/PasswordResetService.php';
require_once 'app/Services/PasswordPolicy.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($token)) {
        $error = 'Token không hợp lệ';
    } elseif (empty($newPassword)) {
        $error = 'Vui lòng nhập mật khẩu mới';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        $passwordResetService = new PasswordResetService();
        $result = $passwordResetService->resetPassword($token, $newPassword);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Kiểm tra token có hợp lệ không
$passwordResetService = new PasswordResetService();
$isValidToken = $passwordResetService->validateToken($token);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset mật khẩu - Hệ thống NCKH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="public/js/password-strength.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .reset-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .reset-body {
            padding: 2rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="reset-container">
                    <div class="reset-header">
                        <i class="fas fa-key fa-3x mb-3"></i>
                        <h3>Reset mật khẩu</h3>
                        <p class="mb-0">Nhập mật khẩu mới cho tài khoản của bạn</p>
                    </div>
                    
                    <div class="reset-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Đăng nhập ngay
                                </a>
                            </div>
                        <?php elseif (!$isValidToken): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Token không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu reset password mới.
                            </div>
                            <div class="text-center">
                                <a href="forgot_password.php" class="btn btn-primary">
                                    <i class="fas fa-redo me-2"></i>
                                    Yêu cầu reset mới
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" id="resetForm">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>
                                        Mật khẩu mới
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="new_password" 
                                           required
                                           minlength="8"
                                           placeholder="Nhập mật khẩu mới">
                                    <div id="password-strength-container"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>
                                        Xác nhận mật khẩu
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           required
                                           placeholder="Nhập lại mật khẩu mới">
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>
                                        Đặt lại mật khẩu
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-3">
                                <a href="login.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Quay lại đăng nhập
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize password strength indicator
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const form = document.getElementById('resetForm');
            
            if (passwordInput && confirmInput && form) {
                // Initialize password strength
                const strengthIndicator = new PasswordStrengthIndicator({
                    container: document.getElementById('password-strength-container'),
                    passwordInput: passwordInput
                });
                
                // Validate password confirmation
                confirmInput.addEventListener('input', function() {
                    if (this.value && this.value !== passwordInput.value) {
                        this.setCustomValidity('Mật khẩu xác nhận không khớp');
                    } else {
                        this.setCustomValidity('');
                    }
                });
                
                // Form validation
                form.addEventListener('submit', function(e) {
                    const password = passwordInput.value;
                    const confirm = confirmInput.value;
                    
                    if (password !== confirm) {
                        e.preventDefault();
                        alert('Mật khẩu xác nhận không khớp');
                        return false;
                    }
                    
                    // Check password strength
                    const validation = strengthIndicator.validatePassword(password);
                    if (!validation.valid) {
                        e.preventDefault();
                        alert('Mật khẩu không đáp ứng yêu cầu bảo mật. Vui lòng kiểm tra các yêu cầu bên dưới.');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
