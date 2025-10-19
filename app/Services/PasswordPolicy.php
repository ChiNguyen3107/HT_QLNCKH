<?php

/**
 * Password Policy Service
 * Quản lý các quy tắc bảo mật password
 */
class PasswordPolicy
{
    private $minLength;
    private $requireUppercase;
    private $requireLowercase;
    private $requireNumbers;
    private $requireSpecialChars;
    private $maxLength;
    private $forbiddenPasswords;
    private $maxConsecutiveChars;
    private $maxRepeatingChars;

    public function __construct($config = [])
    {
        $this->minLength = $config['min_length'] ?? 8;
        $this->requireUppercase = $config['require_uppercase'] ?? true;
        $this->requireLowercase = $config['require_lowercase'] ?? true;
        $this->requireNumbers = $config['require_numbers'] ?? true;
        $this->requireSpecialChars = $config['require_special_chars'] ?? true;
        $this->maxLength = $config['max_length'] ?? 128;
        $this->maxConsecutiveChars = $config['max_consecutive_chars'] ?? 3;
        $this->maxRepeatingChars = $config['max_repeating_chars'] ?? 2;
        
        // Danh sách password yếu cần tránh
        $this->forbiddenPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'root', 'user', 'test',
            '12345678', 'welcome', 'monkey', 'dragon', 'master',
            'hello', 'login', 'pass', '1234', '12345',
            'matkhau', 'admin123', 'user123', 'test123'
        ];
    }

    /**
     * Kiểm tra password có đáp ứng policy không
     */
    public function validatePassword($password)
    {
        $errors = [];
        
        // Kiểm tra độ dài
        if (strlen($password) < $this->minLength) {
            $errors[] = "Mật khẩu phải có ít nhất {$this->minLength} ký tự";
        }
        
        if (strlen($password) > $this->maxLength) {
            $errors[] = "Mật khẩu không được vượt quá {$this->maxLength} ký tự";
        }

        // Kiểm tra chữ hoa
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Mật khẩu phải chứa ít nhất 1 chữ cái viết hoa";
        }

        // Kiểm tra chữ thường
        if ($this->requireLowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Mật khẩu phải chứa ít nhất 1 chữ cái viết thường";
        }

        // Kiểm tra số
        if ($this->requireNumbers && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Mật khẩu phải chứa ít nhất 1 chữ số";
        }

        // Kiểm tra ký tự đặc biệt
        if ($this->requireSpecialChars && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $errors[] = "Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt";
        }

        // Kiểm tra password yếu
        if (in_array(strtolower($password), $this->forbiddenPasswords)) {
            $errors[] = "Mật khẩu quá yếu, vui lòng chọn mật khẩu khác";
        }

        // Kiểm tra ký tự liên tiếp
        if ($this->hasConsecutiveChars($password, $this->maxConsecutiveChars)) {
            $errors[] = "Mật khẩu không được chứa quá {$this->maxConsecutiveChars} ký tự liên tiếp";
        }

        // Kiểm tra ký tự lặp lại
        if ($this->hasRepeatingChars($password, $this->maxRepeatingChars)) {
            $errors[] = "Mật khẩu không được chứa quá {$this->maxRepeatingChars} ký tự giống nhau liên tiếp";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Tính điểm mạnh của password (0-100)
     */
    public function calculateStrength($password)
    {
        $score = 0;
        $length = strlen($password);

        // Điểm cho độ dài
        if ($length >= 8) $score += 20;
        if ($length >= 12) $score += 10;
        if ($length >= 16) $score += 10;

        // Điểm cho các loại ký tự
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $score += 15;

        // Điểm cho độ phức tạp
        if ($this->hasMixedCase($password)) $score += 5;
        if ($this->hasNumbersAndLetters($password)) $score += 5;
        if ($this->hasSpecialChars($password)) $score += 5;

        // Trừ điểm cho các vấn đề
        if (in_array(strtolower($password), $this->forbiddenPasswords)) $score -= 30;
        if ($this->hasConsecutiveChars($password, 3)) $score -= 10;
        if ($this->hasRepeatingChars($password, 3)) $score -= 10;

        return min(100, max(0, $score));
    }

    /**
     * Lấy mức độ mạnh của password
     */
    public function getStrengthLevel($password)
    {
        $score = $this->calculateStrength($password);
        
        if ($score < 30) return 'very_weak';
        if ($score < 50) return 'weak';
        if ($score < 70) return 'medium';
        if ($score < 90) return 'strong';
        return 'very_strong';
    }

    /**
     * Lấy thông báo mức độ mạnh
     */
    public function getStrengthMessage($password)
    {
        $level = $this->getStrengthLevel($password);
        
        $messages = [
            'very_weak' => 'Rất yếu - Cần cải thiện ngay',
            'weak' => 'Yếu - Nên thêm ký tự đặc biệt và số',
            'medium' => 'Trung bình - Có thể cải thiện thêm',
            'strong' => 'Mạnh - Mật khẩu tốt',
            'very_strong' => 'Rất mạnh - Mật khẩu xuất sắc'
        ];

        return $messages[$level] ?? 'Không xác định';
    }

    /**
     * Kiểm tra có ký tự liên tiếp không
     */
    private function hasConsecutiveChars($password, $maxConsecutive)
    {
        $consecutive = 1;
        for ($i = 1; $i < strlen($password); $i++) {
            if (ord($password[$i]) === ord($password[$i-1]) + 1) {
                $consecutive++;
                if ($consecutive > $maxConsecutive) {
                    return true;
                }
            } else {
                $consecutive = 1;
            }
        }
        return false;
    }

    /**
     * Kiểm tra có ký tự lặp lại không
     */
    private function hasRepeatingChars($password, $maxRepeating)
    {
        $repeating = 1;
        for ($i = 1; $i < strlen($password); $i++) {
            if ($password[$i] === $password[$i-1]) {
                $repeating++;
                if ($repeating > $maxRepeating) {
                    return true;
                }
            } else {
                $repeating = 1;
            }
        }
        return false;
    }

    /**
     * Kiểm tra có cả chữ hoa và thường không
     */
    private function hasMixedCase($password)
    {
        return preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password);
    }

    /**
     * Kiểm tra có cả số và chữ không
     */
    private function hasNumbersAndLetters($password)
    {
        return preg_match('/[0-9]/', $password) && preg_match('/[a-zA-Z]/', $password);
    }

    /**
     * Kiểm tra có ký tự đặc biệt không
     */
    private function hasSpecialChars($password)
    {
        return preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
    }

    /**
     * Tạo password mạnh ngẫu nhiên
     */
    public function generateStrongPassword($length = 12)
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        $password = '';
        
        // Đảm bảo có ít nhất 1 ký tự từ mỗi loại
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $special[rand(0, strlen($special) - 1)];
        
        // Thêm các ký tự ngẫu nhiên
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[rand(0, strlen($allChars) - 1)];
        }
        
        // Trộn password
        return str_shuffle($password);
    }
}
