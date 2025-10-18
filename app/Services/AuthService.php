<?php
/**
 * Authentication Service
 */

class AuthService
{
    /**
     * Xác thực người dùng
     */
    public function authenticate($username, $password)
    {
        try {
            // Tìm kiếm trong bảng admin
            $admin = db()->fetch(
                "SELECT * FROM admin WHERE username = ? AND password = ?",
                [$username, md5($password)]
            );
            
            if ($admin) {
                return [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'role' => 'admin',
                    'name' => $admin['name'] ?? 'Administrator'
                ];
            }
            
            // Tìm kiếm trong bảng sinh viên
            $student = db()->fetch(
                "SELECT * FROM sinh_vien WHERE ma_sinh_vien = ? AND mat_khau = ?",
                [$username, md5($password)]
            );
            
            if ($student) {
                return [
                    'id' => $student['ma_sinh_vien'],
                    'username' => $student['ma_sinh_vien'],
                    'role' => 'student',
                    'name' => $student['ho_ten']
                ];
            }
            
            // Tìm kiếm trong bảng giảng viên
            $teacher = db()->fetch(
                "SELECT * FROM giang_vien WHERE ma_giang_vien = ? AND mat_khau = ?",
                [$username, md5($password)]
            );
            
            if ($teacher) {
                return [
                    'id' => $teacher['ma_giang_vien'],
                    'username' => $teacher['ma_giang_vien'],
                    'role' => 'teacher',
                    'name' => $teacher['ho_ten']
                ];
            }
            
            // Tìm kiếm trong bảng research_manager
            $researchManager = db()->fetch(
                "SELECT * FROM research_manager WHERE username = ? AND password = ?",
                [$username, md5($password)]
            );
            
            if ($researchManager) {
                return [
                    'id' => $researchManager['id'],
                    'username' => $researchManager['username'],
                    'role' => 'research_manager',
                    'name' => $researchManager['name'] ?? 'Research Manager'
                ];
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::error('Authentication error: ' . $e->getMessage());
            return false;
        }
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
}

