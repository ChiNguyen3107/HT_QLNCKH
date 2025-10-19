<?php
/**
 * Enhanced Authentication Service with Security Features
 */

class AuthService
{
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 phút
    private $passwordPolicy;

    public function __construct()
    {
        $this->passwordPolicy = new PasswordPolicy();
    }

    /**
     * Xác thực người dùng với enhanced security
     */
    public function authenticate($username, $password, $ipAddress = null)
    {
        try {
            // Kiểm tra account lockout
            if ($this->isAccountLocked($username, $ipAddress)) {
                $this->logLoginAttempt($username, $ipAddress, 'locked', 'Account is locked due to too many failed attempts');
                return [
                    'success' => false,
                    'message' => 'Tài khoản đã bị khóa do quá nhiều lần đăng nhập sai. Vui lòng thử lại sau 15 phút.',
                    'locked' => true
                ];
            }

            // Tìm kiếm trong bảng user
            $user = $this->findUser($username);
            if ($user && $this->verifyPassword($password, $user['password'])) {
                // Reset failed attempts
                $this->resetFailedAttempts($username, $ipAddress);
                
                // Log successful login
                $this->logLoginAttempt($username, $ipAddress, 'success');
                
                // Upgrade password if needed
                $this->maybeUpgradePassword($user, $password);
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'name' => $user['name']
                    ]
                ];
            }

            // Log failed attempt
            $this->logLoginAttempt($username, $ipAddress, 'failed', 'Invalid credentials');
            $this->recordFailedAttempt($username, $ipAddress);

            return [
                'success' => false,
                'message' => 'Tên đăng nhập hoặc mật khẩu không đúng',
                'locked' => false
            ];
            
        } catch (Exception $e) {
            Logger::error('Authentication error: ' . $e->getMessage());
            $this->logLoginAttempt($username, $ipAddress, 'error', $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra trong quá trình đăng nhập',
                'locked' => false
            ];
        }
    }

    /**
     * Tìm user trong tất cả các bảng
     */
    private function findUser($username)
    {
        // Tìm trong bảng user
        $user = db()->fetch(
            "SELECT USER_ID as id, USERNAME as username, PASSWORD as password, ROLE as role, NAME as name FROM user WHERE USERNAME = ? OR EMAIL = ?",
            [$username, $username]
        );
        
        if ($user) {
            return $user;
        }

        // Tìm trong bảng sinh_vien
        $student = db()->fetch(
            "SELECT SV_MASV as id, SV_EMAIL as username, SV_MATKHAU as password, 'student' as role, CONCAT(SV_HOSV, ' ', SV_TENSV) as name FROM sinh_vien WHERE SV_MASV = ? OR SV_EMAIL = ?",
            [$username, $username]
        );
        
        if ($student) {
            return $student;
        }

        // Tìm trong bảng giang_vien
        $teacher = db()->fetch(
            "SELECT GV_MAGV as id, GV_EMAIL as username, GV_MATKHAU as password, 'teacher' as role, CONCAT(GV_HOGV, ' ', GV_TENGV) as name FROM giang_vien WHERE GV_MAGV = ? OR GV_EMAIL = ?",
            [$username, $username]
        );
        
        if ($teacher) {
            return $teacher;
        }

        return null;
    }

    /**
     * Verify password với mixed format support
     */
    private function verifyPassword($rawPassword, $storedPassword)
    {
        if ($storedPassword === null) return false;
        $storedPassword = trim((string)$storedPassword);

        // bcrypt / argon2
        if (preg_match('/^(\$(2y|2b|2a)\$|\$argon2(id|i|d)\$)/', $storedPassword)) {
            return password_verify($rawPassword, $storedPassword);
        }
        
        // sha-256 hex
        if (preg_match('/^[0-9a-f]{64}$/i', $storedPassword)) {
            return hash('sha256', $rawPassword) === strtolower($storedPassword);
        }
        
        // md5 hex (legacy support)
        if (preg_match('/^[0-9a-f]{32}$/i', $storedPassword)) {
            return md5($rawPassword) === strtolower($storedPassword);
        }
        
        // plain text (not recommended)
        return hash_equals($storedPassword, $rawPassword);
    }

    /**
     * Upgrade password to bcrypt if needed
     */
    private function maybeUpgradePassword($user, $rawPassword)
    {
        $storedPassword = $user['password'];
        
        // Nếu không phải bcrypt/argon2 thì upgrade
        if (!preg_match('/^\$2[aby]\$|\$argon2(id|i|d)\$/', $storedPassword)) {
            $newHash = password_hash($rawPassword, PASSWORD_DEFAULT);
            $this->updateUserPassword($user['id'], $newHash, $user['role']);
        }
    }

    /**
     * Update user password
     */
    private function updateUserPassword($userId, $hashedPassword, $role)
    {
        switch ($role) {
            case 'admin':
            case 'research_manager':
                return db()->execute(
                    "UPDATE user SET PASSWORD = ? WHERE USER_ID = ?",
                    [$hashedPassword, $userId]
                );
            case 'student':
                return db()->execute(
                    "UPDATE sinh_vien SET SV_MATKHAU = ? WHERE SV_MASV = ?",
                    [$hashedPassword, $userId]
                );
            case 'teacher':
                return db()->execute(
                    "UPDATE giang_vien SET GV_MATKHAU = ? WHERE GV_MAGV = ?",
                    [$hashedPassword, $userId]
                );
        }
        return false;
    }

    /**
     * Kiểm tra account có bị lock không
     */
    private function isAccountLocked($username, $ipAddress)
    {
        $lockoutTime = time() - $this->lockoutDuration;
        
        $result = db()->fetch(
            "SELECT COUNT(*) as count FROM login_attempts 
             WHERE (username = ? OR ip_address = ?) 
             AND attempt_time > ? AND status = 'failed'",
            [$username, $ipAddress, date('Y-m-d H:i:s', $lockoutTime)]
        );
        
        return ($result['count'] ?? 0) >= $this->maxLoginAttempts;
    }

    /**
     * Ghi nhận lần đăng nhập sai
     */
    private function recordFailedAttempt($username, $ipAddress)
    {
        db()->execute(
            "INSERT INTO login_attempts (username, ip_address, status, attempt_time) VALUES (?, ?, 'failed', NOW())",
            [$username, $ipAddress]
        );
    }

    /**
     * Reset failed attempts
     */
    private function resetFailedAttempts($username, $ipAddress)
    {
        db()->execute(
            "DELETE FROM login_attempts WHERE username = ? OR ip_address = ?",
            [$username, $ipAddress]
        );
    }

    /**
     * Log login attempt
     */
    private function logLoginAttempt($username, $ipAddress, $status, $message = '')
    {
        db()->execute(
            "INSERT INTO login_attempts (username, ip_address, status, message, attempt_time) VALUES (?, ?, ?, ?, NOW())",
            [$username, $ipAddress, $status, $message]
        );
    }

    /**
     * Change password với policy validation
     */
    public function changePassword($userId, $currentPassword, $newPassword, $role)
    {
        try {
            // Lấy thông tin user
            $user = $this->findUserById($userId, $role);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy user'
                ];
            }

            // Verify current password
            if (!$this->verifyPassword($currentPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Mật khẩu hiện tại không đúng'
                ];
            }

            // Validate new password
            $validation = $this->passwordPolicy->validatePassword($newPassword);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Mật khẩu mới không đáp ứng yêu cầu: ' . implode(', ', $validation['errors'])
                ];
            }

            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password
            if ($this->updateUserPassword($userId, $hashedPassword, $role)) {
                Logger::info("Password changed for user: {$userId}");
                return [
                    'success' => true,
                    'message' => 'Mật khẩu đã được thay đổi thành công'
                ];
            }

            return [
                'success' => false,
                'message' => 'Không thể cập nhật mật khẩu'
            ];

        } catch (Exception $e) {
            Logger::error('Change password error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thay đổi mật khẩu'
            ];
        }
    }

    /**
     * Find user by ID
     */
    private function findUserById($userId, $role)
    {
        switch ($role) {
            case 'admin':
            case 'research_manager':
                return db()->fetch(
                    "SELECT USER_ID as id, PASSWORD as password FROM user WHERE USER_ID = ?",
                    [$userId]
                );
            case 'student':
                return db()->fetch(
                    "SELECT SV_MASV as id, SV_MATKHAU as password FROM sinh_vien WHERE SV_MASV = ?",
                    [$userId]
                );
            case 'teacher':
                return db()->fetch(
                    "SELECT GV_MAGV as id, GV_MATKHAU as password FROM giang_vien WHERE GV_MAGV = ?",
                    [$userId]
                );
        }
        return null;
    }
    
    /**
     * Kiểm tra quyền truy cập
     */
    public function hasPermission($role, $permission)
    {
        $permissions = [
            'admin' => ['*'], // Admin có tất cả quyền
            'research_manager' => ['projects.*', 'evaluations.*', 'reports.*'],
            'teacher' => ['projects.view', 'projects.update', 'students.view'],
            'student' => ['projects.view', 'projects.create', 'profile.update']
        ];
        
        if (!isset($permissions[$role])) {
            return false;
        }
        
        $userPermissions = $permissions[$role];
        
        // Kiểm tra wildcard permission
        if (in_array('*', $userPermissions)) {
            return true;
        }
        
        // Kiểm tra permission cụ thể
        return in_array($permission, $userPermissions);
    }
    
    /**
     * Lấy thông tin user hiện tại
     */
    public function getCurrentUser()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['name']
        ];
    }
    
    /**
     * Kiểm tra đăng nhập
     */
    public function check()
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Kiểm tra role
     */
    public function hasRole($role)
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    /**
     * Lấy thông tin password policy
     */
    public function getPasswordPolicy()
    {
        return $this->passwordPolicy;
    }

    /**
     * Kiểm tra password strength
     */
    public function checkPasswordStrength($password)
    {
        return [
            'score' => $this->passwordPolicy->calculateStrength($password),
            'level' => $this->passwordPolicy->getStrengthLevel($password),
            'message' => $this->passwordPolicy->getStrengthMessage($password)
        ];
    }

    /**
     * Lấy lịch sử đăng nhập
     */
    public function getLoginHistory($username, $limit = 10)
    {
        return db()->fetchAll(
            "SELECT * FROM login_attempts WHERE username = ? ORDER BY attempt_time DESC LIMIT ?",
            [$username, $limit]
        );
    }

    /**
     * Unlock account (admin only)
     */
    public function unlockAccount($username)
    {
        try {
            db()->execute(
                "DELETE FROM login_attempts WHERE username = ?",
                [$username]
            );
            
            Logger::info("Account unlocked for user: {$username}");
            return [
                'success' => true,
                'message' => 'Tài khoản đã được mở khóa thành công'
            ];
        } catch (Exception $e) {
            Logger::error('Unlock account error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Không thể mở khóa tài khoản'
            ];
        }
    }
}

