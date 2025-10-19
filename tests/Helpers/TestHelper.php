<?php

namespace Tests\Helpers;

/**
 * Helper class cho testing utilities
 */
class TestHelper
{
    /**
     * Generate random string
     */
    public static function randomString(int $length = 10): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $string;
    }

    /**
     * Generate random email
     */
    public static function randomEmail(): string
    {
        return self::randomString(8) . '@' . self::randomString(5) . '.com';
    }

    /**
     * Generate random phone number
     */
    public static function randomPhone(): string
    {
        return '0' . rand(100000000, 999999999);
    }

    /**
     * Generate Vietnamese name
     */
    public static function randomVietnameseName(): string
    {
        $firstNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi'];
        $middleNames = ['Văn', 'Thị', 'Đức', 'Minh', 'Hữu', 'Quang', 'Thanh', 'Xuân'];
        $lastNames = ['Nam', 'Hùng', 'Minh', 'Linh', 'Hương', 'Lan', 'Mai', 'Anh', 'Tuấn', 'Huy'];
        
        $firstName = $firstNames[array_rand($firstNames)];
        $middleName = $middleNames[array_rand($middleNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        
        return $firstName . ' ' . $middleName . ' ' . $lastName;
    }

    /**
     * Generate student ID
     */
    public static function randomStudentId(): string
    {
        $years = ['B21', 'B22', 'B23', 'B24'];
        $year = $years[array_rand($years)];
        $number = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        return $year . $number;
    }

    /**
     * Create temporary file
     */
    public static function createTempFile(string $content = 'test content', string $extension = 'txt'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.' . $extension;
        file_put_contents($tempFile, $content);
        
        return $tempFile;
    }

    /**
     * Clean up temporary file
     */
    public static function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Generate random date between range
     */
    public static function randomDate(string $start = '-1 year', string $end = '+1 year'): string
    {
        $startTime = strtotime($start);
        $endTime = strtotime($end);
        
        $randomTime = rand($startTime, $endTime);
        
        return date('Y-m-d', $randomTime);
    }

    /**
     * Generate random datetime between range
     */
    public static function randomDateTime(string $start = '-1 year', string $end = '+1 year'): string
    {
        $startTime = strtotime($start);
        $endTime = strtotime($end);
        
        $randomTime = rand($startTime, $endTime);
        
        return date('Y-m-d H:i:s', $randomTime);
    }

    /**
     * Assert array has all required keys
     */
    public static function assertArrayHasKeys(array $array, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $array)) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    "Array does not contain required key: {$key}"
                );
            }
        }
    }

    /**
     * Assert array contains only allowed keys
     */
    public static function assertArrayHasOnlyKeys(array $array, array $allowedKeys): void
    {
        $actualKeys = array_keys($array);
        $extraKeys = array_diff($actualKeys, $allowedKeys);
        
        if (!empty($extraKeys)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Array contains unexpected keys: " . implode(', ', $extraKeys)
            );
        }
    }

    /**
     * Generate random Vietnamese address
     */
    public static function randomVietnameseAddress(): string
    {
        $streets = ['Đường Lê Lợi', 'Đường Nguyễn Huệ', 'Đường Trần Hưng Đạo', 'Đường Lý Tự Trọng'];
        $wards = ['Phường Bến Nghé', 'Phường Đa Kao', 'Phường Tân Định', 'Phường Cầu Kho'];
        $districts = ['Quận 1', 'Quận 3', 'Quận 5', 'Quận 10'];
        $city = 'TP. Hồ Chí Minh';
        
        $street = $streets[array_rand($streets)];
        $ward = $wards[array_rand($wards)];
        $district = $districts[array_rand($districts)];
        $number = rand(1, 999);
        
        return "{$number} {$street}, {$ward}, {$district}, {$city}";
    }

    /**
     * Generate random Vietnamese company name
     */
    public static function randomVietnameseCompanyName(): string
    {
        $prefixes = ['Công ty', 'Tập đoàn', 'Tổng công ty', 'Công ty TNHH', 'Công ty CP'];
        $names = ['Công nghệ', 'Tin học', 'Phần mềm', 'Viễn thông', 'Điện tử'];
        $suffixes = ['Việt Nam', 'Thành phố Hồ Chí Minh', 'Hà Nội', 'Miền Nam'];
        
        $prefix = $prefixes[array_rand($prefixes)];
        $name = $names[array_rand($names)];
        $suffix = $suffixes[array_rand($suffixes)];
        
        return "{$prefix} {$name} {$suffix}";
    }
}
