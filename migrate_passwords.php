<?php
/**
 * Password Migration Script
 * Convert existing MD5 passwords to bcrypt/Argon2
 */

require_once 'include/connect.php';
require_once 'app/Services/PasswordPolicy.php';

class PasswordMigration
{
    private $conn;
    private $passwordPolicy;
    private $batchSize = 100;
    private $logFile;

    public function __construct()
    {
        $this->conn = $GLOBALS['conn'];
        $this->passwordPolicy = new PasswordPolicy();
        $this->logFile = 'logs/password_migration_' . date('Y-m-d_H-i-s') . '.log';
        
        // Tạo thư mục logs nếu chưa có
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
    }

    /**
     * Chạy migration cho tất cả bảng
     */
    public function runMigration()
    {
        $this->log("Bắt đầu migration passwords...");
        
        try {
            // Migration bảng user
            $this->migrateTable('user', 'USER_ID', 'USERNAME', 'PASSWORD', 'admin');
            $this->migrateTable('user', 'USER_ID', 'USERNAME', 'PASSWORD', 'research_manager');
            
            // Migration bảng sinh_vien
            $this->migrateTable('sinh_vien', 'SV_MASV', 'SV_EMAIL', 'SV_MATKHAU', 'student');
            
            // Migration bảng giang_vien
            $this->migrateTable('giang_vien', 'GV_MAGV', 'GV_EMAIL', 'GV_MATKHAU', 'teacher');
            
            $this->log("Migration hoàn thành thành công!");
            
        } catch (Exception $e) {
            $this->log("Lỗi trong quá trình migration: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Migration cho một bảng cụ thể
     */
    private function migrateTable($table, $idColumn, $usernameColumn, $passwordColumn, $userType)
    {
        $this->log("Bắt đầu migration bảng: {$table}");
        
        $offset = 0;
        $totalMigrated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        do {
            // Lấy batch users
            $users = $this->getUsersBatch($table, $idColumn, $usernameColumn, $passwordColumn, $offset);
            
            if (empty($users)) {
                break;
            }

            foreach ($users as $user) {
                try {
                    $result = $this->migrateUserPassword($user, $table, $idColumn, $passwordColumn, $userType);
                    
                    if ($result['migrated']) {
                        $totalMigrated++;
                        $this->log("Migrated: {$user[$usernameColumn]} ({$userType})");
                    } else {
                        $totalSkipped++;
                        $this->log("Skipped: {$user[$usernameColumn]} - {$result['reason']}");
                    }
                    
                } catch (Exception $e) {
                    $totalErrors++;
                    $this->log("Error migrating {$user[$usernameColumn]}: " . $e->getMessage());
                }
            }

            $offset += $this->batchSize;
            
        } while (count($users) === $this->batchSize);

        $this->log("Hoàn thành migration bảng {$table}: {$totalMigrated} migrated, {$totalSkipped} skipped, {$totalErrors} errors");
    }

    /**
     * Lấy batch users để migrate
     */
    private function getUsersBatch($table, $idColumn, $usernameColumn, $passwordColumn, $offset)
    {
        $sql = "SELECT {$idColumn}, {$usernameColumn}, {$passwordColumn} 
                FROM {$table} 
                WHERE {$passwordColumn} IS NOT NULL 
                AND {$passwordColumn} != '' 
                ORDER BY {$idColumn} 
                LIMIT {$offset}, {$this->batchSize}";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            throw new Exception("Lỗi query: " . $this->conn->error);
        }

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        return $users;
    }

    /**
     * Migrate password cho một user
     */
    private function migrateUserPassword($user, $table, $idColumn, $passwordColumn, $userType)
    {
        $storedPassword = $user[$passwordColumn];
        
        // Kiểm tra xem password đã được hash bằng bcrypt/argon2 chưa
        if ($this->isModernHash($storedPassword)) {
            return [
                'migrated' => false,
                'reason' => 'Already using modern hash'
            ];
        }

        // Nếu là MD5, cần user nhập password mới (không thể reverse MD5)
        if ($this->isMD5Hash($storedPassword)) {
            // Tạo password mặc định mạnh
            $newPassword = $this->generateDefaultPassword($userType);
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Cập nhật password
            $this->updateUserPassword($user[$idColumn], $hashedPassword, $table, $idColumn, $passwordColumn);
            
            // Gửi email thông báo password mới (nếu có email)
            $this->notifyNewPassword($user, $newPassword, $userType);
            
            return [
                'migrated' => true,
                'reason' => 'Generated new password (MD5 detected)',
                'newPassword' => $newPassword
            ];
        }

        // Nếu là plain text, hash bằng bcrypt
        if ($this->isPlainText($storedPassword)) {
            $hashedPassword = password_hash($storedPassword, PASSWORD_DEFAULT);
            $this->updateUserPassword($user[$idColumn], $hashedPassword, $table, $idColumn, $passwordColumn);
            
            return [
                'migrated' => true,
                'reason' => 'Hashed plain text password'
            ];
        }

        return [
            'migrated' => false,
            'reason' => 'Unknown password format'
        ];
    }

    /**
     * Kiểm tra có phải modern hash không
     */
    private function isModernHash($password)
    {
        return preg_match('/^(\$(2y|2b|2a)\$|\$argon2(id|i|d)\$)/', $password);
    }

    /**
     * Kiểm tra có phải MD5 hash không
     */
    private function isMD5Hash($password)
    {
        return preg_match('/^[0-9a-f]{32}$/i', $password);
    }

    /**
     * Kiểm tra có phải plain text không
     */
    private function isPlainText($password)
    {
        return !$this->isModernHash($password) && !$this->isMD5Hash($password) && !preg_match('/^[0-9a-f]{64}$/i', $password);
    }

    /**
     * Tạo password mặc định mạnh
     */
    private function generateDefaultPassword($userType)
    {
        $prefix = strtoupper(substr($userType, 0, 3));
        $random = bin2hex(random_bytes(4));
        return $prefix . $random . '!';
    }

    /**
     * Cập nhật password trong database
     */
    private function updateUserPassword($userId, $hashedPassword, $table, $idColumn, $passwordColumn)
    {
        $sql = "UPDATE {$table} SET {$passwordColumn} = ?, password_changed_at = NOW() WHERE {$idColumn} = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Lỗi prepare statement: " . $this->conn->error);
        }

        $stmt->bind_param("ss", $hashedPassword, $userId);
        $result = $stmt->execute();
        $stmt->close();

        if (!$result) {
            throw new Exception("Lỗi update password: " . $this->conn->error);
        }
    }

    /**
     * Thông báo password mới cho user
     */
    private function notifyNewPassword($user, $newPassword, $userType)
    {
        // Lấy email của user
        $email = $this->getUserEmail($user, $userType);
        
        if (!$email) {
            $this->log("Không có email cho user: " . ($user['USERNAME'] ?? $user['SV_EMAIL'] ?? $user['GV_EMAIL']));
            return;
        }

        $subject = "Mật khẩu mới - Hệ thống NCKH";
        $message = "
        <html>
        <head><title>Mật khẩu mới</title></head>
        <body>
            <h2>Mật khẩu mới của bạn</h2>
            <p>Hệ thống đã được nâng cấp bảo mật. Mật khẩu mới của bạn là:</p>
            <p><strong>{$newPassword}</strong></p>
            <p>Vui lòng đăng nhập và thay đổi mật khẩu này ngay lập tức.</p>
            <p>Trân trọng,<br>Hệ thống NCKH</p>
        </body>
        </html>
        ";

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: noreply@nckh.edu.vn'
        ];

        mail($email, $subject, $message, implode("\r\n", $headers));
        $this->log("Đã gửi email thông báo password mới cho: {$email}");
    }

    /**
     * Lấy email của user
     */
    private function getUserEmail($user, $userType)
    {
        switch ($userType) {
            case 'admin':
            case 'research_manager':
                return $user['EMAIL'] ?? null;
            case 'student':
                return $user['SV_EMAIL'] ?? null;
            case 'teacher':
                return $user['GV_EMAIL'] ?? null;
        }
        return null;
    }

    /**
     * Ghi log
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Tạo báo cáo migration
     */
    public function generateReport()
    {
        $this->log("Tạo báo cáo migration...");
        
        $tables = ['user', 'sinh_vien', 'giang_vien'];
        $report = [];
        
        foreach ($tables as $table) {
            $stats = $this->getTableStats($table);
            $report[$table] = $stats;
            $this->log("Bảng {$table}: {$stats['total']} users, {$stats['modern']} modern, {$stats['legacy']} legacy");
        }
        
        return $report;
    }

    /**
     * Lấy thống kê bảng
     */
    private function getTableStats($table)
    {
        $passwordColumn = $this->getPasswordColumn($table);
        
        $total = $this->conn->query("SELECT COUNT(*) as count FROM {$table} WHERE {$passwordColumn} IS NOT NULL")->fetch_assoc()['count'];
        
        $modern = $this->conn->query("
            SELECT COUNT(*) as count FROM {$table} 
            WHERE {$passwordColumn} LIKE '\$2y%' 
            OR {$passwordColumn} LIKE '\$2b%' 
            OR {$passwordColumn} LIKE '\$2a%' 
            OR {$passwordColumn} LIKE '\$argon2%'
        ")->fetch_assoc()['count'];
        
        return [
            'total' => $total,
            'modern' => $modern,
            'legacy' => $total - $modern
        ];
    }

    /**
     * Lấy tên cột password
     */
    private function getPasswordColumn($table)
    {
        switch ($table) {
            case 'user': return 'PASSWORD';
            case 'sinh_vien': return 'SV_MATKHAU';
            case 'giang_vien': return 'GV_MATKHAU';
        }
        return 'PASSWORD';
    }
}

// Chạy migration nếu được gọi trực tiếp
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $migration = new PasswordMigration();
        $migration->runMigration();
        $report = $migration->generateReport();
        
        echo "\n=== BÁO CÁO MIGRATION ===\n";
        foreach ($report as $table => $stats) {
            echo "Bảng {$table}: {$stats['total']} users, {$stats['modern']} modern, {$stats['legacy']} legacy\n";
        }
        
    } catch (Exception $e) {
        echo "Lỗi: " . $e->getMessage() . "\n";
        exit(1);
    }
}
