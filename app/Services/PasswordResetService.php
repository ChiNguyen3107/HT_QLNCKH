<?php

/**
 * Password Reset Service
 * Quản lý việc reset password với secure tokens
 */
class PasswordResetService
{
    private $db;
    private $tokenExpiry = 3600; // 1 giờ
    private $maxAttempts = 3; // Tối đa 3 lần reset trong 1 giờ

    public function __construct($database = null)
    {
        $this->db = $database ?? db();
    }

    /**
     * Tạo token reset password
     */
    public function createResetToken($email, $userType = 'user')
    {
        try {
            // Kiểm tra email có tồn tại không
            $user = $this->findUserByEmail($email, $userType);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Email không tồn tại trong hệ thống'
                ];
            }

            // Kiểm tra số lần reset trong 1 giờ
            $recentAttempts = $this->getRecentResetAttempts($email, $userType);
            if ($recentAttempts >= $this->maxAttempts) {
                return [
                    'success' => false,
                    'message' => 'Bạn đã thực hiện quá nhiều yêu cầu reset password. Vui lòng thử lại sau 1 giờ.'
                ];
            }

            // Tạo token bảo mật
            $token = $this->generateSecureToken();
            $expiresAt = date('Y-m-d H:i:s', time() + $this->tokenExpiry);

            // Lưu token vào database
            $this->saveResetToken($email, $token, $expiresAt, $userType);

            // Gửi email reset
            $this->sendResetEmail($email, $token, $user['name'] ?? $user['username']);

            return [
                'success' => true,
                'message' => 'Email reset password đã được gửi. Vui lòng kiểm tra hộp thư.'
            ];

        } catch (Exception $e) {
            Logger::error('Password reset error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
            ];
        }
    }

    /**
     * Xác thực token và reset password
     */
    public function resetPassword($token, $newPassword, $userType = 'user')
    {
        try {
            // Kiểm tra token có hợp lệ không
            $resetData = $this->getResetToken($token, $userType);
            if (!$resetData) {
                return [
                    'success' => false,
                    'message' => 'Token không hợp lệ hoặc đã hết hạn'
                ];
            }

            // Kiểm tra token có hết hạn không
            if (strtotime($resetData['expires_at']) < time()) {
                $this->deleteResetToken($token, $userType);
                return [
                    'success' => false,
                    'message' => 'Token đã hết hạn. Vui lòng yêu cầu reset password mới.'
                ];
            }

            // Validate password mới
            $policy = new PasswordPolicy();
            $validation = $policy->validatePassword($newPassword);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Mật khẩu mới không đáp ứng yêu cầu bảo mật: ' . implode(', ', $validation['errors'])
                ];
            }

            // Hash password mới
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Cập nhật password
            $updated = $this->updateUserPassword($resetData['email'], $hashedPassword, $userType);
            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'Không thể cập nhật mật khẩu. Vui lòng thử lại.'
                ];
            }

            // Xóa token đã sử dụng
            $this->deleteResetToken($token, $userType);

            // Log hoạt động
            Logger::info("Password reset successful for email: {$resetData['email']}");

            return [
                'success' => true,
                'message' => 'Mật khẩu đã được cập nhật thành công'
            ];

        } catch (Exception $e) {
            Logger::error('Password reset error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
            ];
        }
    }

    /**
     * Kiểm tra token có hợp lệ không
     */
    public function validateToken($token, $userType = 'user')
    {
        $resetData = $this->getResetToken($token, $userType);
        if (!$resetData) {
            return false;
        }

        // Kiểm tra hết hạn
        if (strtotime($resetData['expires_at']) < time()) {
            $this->deleteResetToken($token, $userType);
            return false;
        }

        return true;
    }

    /**
     * Tìm user theo email
     */
    private function findUserByEmail($email, $userType)
    {
        switch ($userType) {
            case 'user':
                return $this->db->fetch(
                    "SELECT USER_ID as id, USERNAME as username, NAME as name, EMAIL as email FROM user WHERE EMAIL = ?",
                    [$email]
                );
            case 'student':
                return $this->db->fetch(
                    "SELECT SV_MASV as id, SV_EMAIL as email, CONCAT(SV_HOSV, ' ', SV_TENSV) as name FROM sinh_vien WHERE SV_EMAIL = ?",
                    [$email]
                );
            case 'teacher':
                return $this->db->fetch(
                    "SELECT GV_MAGV as id, GV_EMAIL as email, CONCAT(GV_HOGV, ' ', GV_TENGV) as name FROM giang_vien WHERE GV_EMAIL = ?",
                    [$email]
                );
            default:
                return null;
        }
    }

    /**
     * Tạo token bảo mật
     */
    private function generateSecureToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Lưu token reset vào database
     */
    private function saveResetToken($email, $token, $expiresAt, $userType)
    {
        // Xóa token cũ nếu có
        $this->db->execute(
            "DELETE FROM password_reset_tokens WHERE email = ? AND user_type = ?",
            [$email, $userType]
        );

        // Lưu token mới
        $this->db->execute(
            "INSERT INTO password_reset_tokens (email, token, expires_at, user_type, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$email, $token, $expiresAt, $userType]
        );
    }

    /**
     * Lấy thông tin token
     */
    private function getResetToken($token, $userType)
    {
        return $this->db->fetch(
            "SELECT * FROM password_reset_tokens WHERE token = ? AND user_type = ?",
            [$token, $userType]
        );
    }

    /**
     * Xóa token
     */
    private function deleteResetToken($token, $userType)
    {
        $this->db->execute(
            "DELETE FROM password_reset_tokens WHERE token = ? AND user_type = ?",
            [$token, $userType]
        );
    }

    /**
     * Cập nhật password user
     */
    private function updateUserPassword($email, $hashedPassword, $userType)
    {
        switch ($userType) {
            case 'user':
                return $this->db->execute(
                    "UPDATE user SET PASSWORD = ? WHERE EMAIL = ?",
                    [$hashedPassword, $email]
                );
            case 'student':
                return $this->db->execute(
                    "UPDATE sinh_vien SET SV_MATKHAU = ? WHERE SV_EMAIL = ?",
                    [$hashedPassword, $email]
                );
            case 'teacher':
                return $this->db->execute(
                    "UPDATE giang_vien SET GV_MATKHAU = ? WHERE GV_EMAIL = ?",
                    [$hashedPassword, $email]
                );
            default:
                return false;
        }
    }

    /**
     * Lấy số lần reset gần đây
     */
    private function getRecentResetAttempts($email, $userType)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM password_reset_tokens WHERE email = ? AND user_type = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$email, $userType]
        );
        
        return $result['count'] ?? 0;
    }

    /**
     * Gửi email reset password
     */
    private function sendResetEmail($email, $token, $name)
    {
        $resetUrl = "https://" . $_SERVER['HTTP_HOST'] . "/NLNganh/reset_password.php?token=" . $token;
        
        $subject = "Yêu cầu reset mật khẩu - Hệ thống NCKH";
        $message = "
        <html>
        <head>
            <title>Reset mật khẩu</title>
        </head>
        <body>
            <h2>Xin chào {$name},</h2>
            <p>Bạn đã yêu cầu reset mật khẩu cho tài khoản của mình.</p>
            <p>Vui lòng click vào link bên dưới để đặt lại mật khẩu:</p>
            <p><a href='{$resetUrl}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset mật khẩu</a></p>
            <p>Link này sẽ hết hạn sau 1 giờ.</p>
            <p>Nếu bạn không yêu cầu reset mật khẩu, vui lòng bỏ qua email này.</p>
            <br>
            <p>Trân trọng,<br>Hệ thống NCKH</p>
        </body>
        </html>
        ";

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: noreply@nckh.edu.vn',
            'Reply-To: support@nckh.edu.vn'
        ];

        return mail($email, $subject, $message, implode("\r\n", $headers));
    }

    /**
     * Dọn dẹp token hết hạn
     */
    public function cleanupExpiredTokens()
    {
        $this->db->execute(
            "DELETE FROM password_reset_tokens WHERE expires_at < NOW()"
        );
    }
}
