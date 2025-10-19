<?php
session_start();
require_once 'include/connect.php';
require_once 'app/Services/PasswordResetService.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $userType = $_POST['user_type'] ?? 'user';
    
    if (empty($email)) {
        $error = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        $passwordResetService = new PasswordResetService();
        $result = $passwordResetService->createResetToken($email, $userType);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - Hệ thống NCKH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .forgot-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .forgot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .forgot-body {
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
        .user-type-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .user-type-card:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .user-type-card.selected {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .user-type-card i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="forgot-container">
                    <div class="forgot-header">
                        <i class="fas fa-lock fa-3x mb-3"></i>
                        <h3>Quên mật khẩu?</h3>
                        <p class="mb-0">Nhập email để nhận link reset mật khẩu</p>
                    </div>
                    
                    <div class="forgot-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Quay lại đăng nhập
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" id="forgotForm">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Loại tài khoản</label>
                                    <div class="row g-3">
                                        <div class="col-4">
                                            <div class="user-type-card" data-type="user">
                                                <i class="fas fa-user-shield"></i>
                                                <div class="small">Admin/Manager</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="user-type-card" data-type="student">
                                                <i class="fas fa-user-graduate"></i>
                                                <div class="small">Sinh viên</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="user-type-card" data-type="teacher">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                                <div class="small">Giảng viên</div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="user_type" id="user_type" value="user">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>
                                        Email đăng ký
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           required
                                           placeholder="Nhập email của bạn">
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Gửi link reset
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
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeCards = document.querySelectorAll('.user-type-card');
            const userTypeInput = document.getElementById('user_type');
            
            // Select user type
            userTypeCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    userTypeCards.forEach(c => c.classList.remove('selected'));
                    
                    // Add selected class to clicked card
                    this.classList.add('selected');
                    
                    // Update hidden input
                    userTypeInput.value = this.dataset.type;
                });
            });
            
            // Select first card by default
            if (userTypeCards.length > 0) {
                userTypeCards[0].click();
            }
        });
    </script>
</body>
</html>
