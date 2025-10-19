<?php
/**
 * Security Upgrade Runner
 * Script để chạy nâng cấp bảo mật một cách an toàn
 */

require_once 'include/connect.php';
require_once 'core/Logger.php';

class SecurityUpgradeRunner
{
    private $conn;
    private $logFile;

    public function __construct()
    {
        $this->conn = $GLOBALS['conn'];
        $this->logFile = 'logs/security_upgrade_' . date('Y-m-d_H-i-s') . '.log';
        
        // Tạo thư mục logs nếu chưa có
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
    }

    public function run()
    {
        $this->log("=== BẮT ĐẦU NÂNG CẤP BẢO MẬT ===");
        
        try {
            // 1. Kiểm tra kết nối database
            $this->checkDatabaseConnection();
            
            // 2. Chạy migration database
            $this->runDatabaseMigration();
            
            // 3. Backup existing passwords
            $this->backupExistingPasswords();
            
            // 4. Chạy password migration
            $this->runPasswordMigration();
            
            // 5. Kiểm tra kết quả
            $this->verifyUpgrade();
            
            $this->log("=== NÂNG CẤP HOÀN THÀNH THÀNH CÔNG ===");
            echo "\n✅ Nâng cấp bảo mật hoàn thành thành công!\n";
            echo "📋 Xem chi tiết trong file: {$this->logFile}\n";
            
        } catch (Exception $e) {
            $this->log("❌ LỖI: " . $e->getMessage());
            echo "\n❌ Có lỗi xảy ra: " . $e->getMessage() . "\n";
            echo "📋 Xem chi tiết trong file: {$this->logFile}\n";
            exit(1);
        }
    }

    private function checkDatabaseConnection()
    {
        $this->log("🔍 Kiểm tra kết nối database...");
        
        if (!$this->conn) {
            throw new Exception("Không thể kết nối database");
        }
        
        $result = $this->conn->query("SELECT 1");
        if (!$result) {
            throw new Exception("Database connection test failed");
        }
        
        $this->log("✅ Kết nối database thành công");
    }

    private function runDatabaseMigration()
    {
        $this->log("🗄️ Chạy migration database...");
        
        $migrationFile = 'migrations/001_security_upgrade.sql';
        if (!file_exists($migrationFile)) {
            throw new Exception("Migration file không tồn tại: {$migrationFile}");
        }
        
        $sql = file_get_contents($migrationFile);
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $this->conn->query($statement);
                $this->log("✅ Executed: " . substr($statement, 0, 50) . "...");
            } catch (Exception $e) {
                // Một số statements có thể fail nếu table đã tồn tại
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $this->log("⚠️ Warning: " . $e->getMessage());
                }
            }
        }
        
        $this->log("✅ Migration database hoàn thành");
    }

    private function backupExistingPasswords()
    {
        $this->log("💾 Backup existing passwords...");
        
        $tables = [
            'user' => ['USER_ID', 'USERNAME', 'PASSWORD'],
            'sinh_vien' => ['SV_MASV', 'SV_EMAIL', 'SV_MATKHAU'],
            'giang_vien' => ['GV_MAGV', 'GV_EMAIL', 'GV_MATKHAU']
        ];
        
        $backupFile = 'backups/passwords_backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Tạo thư mục backup nếu chưa có
        if (!is_dir('backups')) {
            mkdir('backups', 0755, true);
        }
        
        $backupContent = "-- Password Backup - " . date('Y-m-d H:i:s') . "\n";
        
        foreach ($tables as $table => $columns) {
            $idCol = $columns[0];
            $userCol = $columns[1];
            $passCol = $columns[2];
            
            $result = $this->conn->query("SELECT {$idCol}, {$userCol}, {$passCol} FROM {$table}");
            
            $backupContent .= "\n-- Table: {$table}\n";
            while ($row = $result->fetch_assoc()) {
                $backupContent .= "UPDATE {$table} SET {$passCol} = '{$row[$passCol]}' WHERE {$idCol} = '{$row[$idCol]}';\n";
            }
        }
        
        file_put_contents($backupFile, $backupContent);
        $this->log("✅ Backup saved to: {$backupFile}");
    }

    private function runPasswordMigration()
    {
        $this->log("🔄 Chạy password migration...");
        
        if (!file_exists('migrate_passwords.php')) {
            throw new Exception("Migration script không tồn tại");
        }
        
        // Capture output
        ob_start();
        include 'migrate_passwords.php';
        $output = ob_get_clean();
        
        $this->log("Migration output: " . $output);
        $this->log("✅ Password migration hoàn thành");
    }

    private function verifyUpgrade()
    {
        $this->log("🔍 Kiểm tra kết quả nâng cấp...");
        
        // Kiểm tra các bảng mới
        $newTables = ['login_attempts', 'password_reset_tokens', 'security_audit_log', 'user_sessions'];
        
        foreach ($newTables as $table) {
            $result = $this->conn->query("SHOW TABLES LIKE '{$table}'");
            if ($result->num_rows === 0) {
                throw new Exception("Bảng {$table} chưa được tạo");
            }
            $this->log("✅ Bảng {$table} đã được tạo");
        }
        
        // Kiểm tra password migration
        $tables = ['user', 'sinh_vien', 'giang_vien'];
        foreach ($tables as $table) {
            $passCol = $this->getPasswordColumn($table);
            
            $result = $this->conn->query("
                SELECT COUNT(*) as count FROM {$table} 
                WHERE {$passCol} LIKE '\$2y%' 
                OR {$passCol} LIKE '\$2b%' 
                OR {$passCol} LIKE '\$2a%' 
                OR {$passCol} LIKE '\$argon2%'
            ");
            
            $modernCount = $result->fetch_assoc()['count'];
            $this->log("✅ Bảng {$table}: {$modernCount} passwords đã được nâng cấp");
        }
        
        $this->log("✅ Kiểm tra hoàn thành");
    }

    private function getPasswordColumn($table)
    {
        switch ($table) {
            case 'user': return 'PASSWORD';
            case 'sinh_vien': return 'SV_MATKHAU';
            case 'giang_vien': return 'GV_MATKHAU';
        }
        return 'PASSWORD';
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Chạy upgrade nếu được gọi trực tiếp
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🚀 Bắt đầu nâng cấp bảo mật...\n\n";
    
    $runner = new SecurityUpgradeRunner();
    $runner->run();
}
