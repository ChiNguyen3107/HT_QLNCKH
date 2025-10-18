<?php
/**
 * Admin Controller
 */

class AdminController
{
    private $userService;
    private $projectService;
    
    public function __construct()
    {
        $this->userService = new UserService();
        $this->projectService = new ProjectService();
    }
    
    /**
     * Dashboard admin
     */
    public function dashboard()
    {
        $stats = [
            'total_projects' => $this->getTotalProjects(),
            'active_projects' => $this->getActiveProjects(),
            'total_students' => $this->getTotalStudents(),
            'total_teachers' => $this->getTotalTeachers(),
            'recent_projects' => $this->getRecentProjects(),
            'notifications' => $this->getNotifications()
        ];
        
        view('admin/dashboard', compact('stats'));
    }
    
    /**
     * Quản lý người dùng
     */
    public function users()
    {
        $users = $this->userService->getAllUsers();
        view('admin/users', compact('users'));
    }
    
    /**
     * Quản lý dự án
     */
    public function projects()
    {
        $projects = $this->projectService->getAllProjects();
        view('admin/projects', compact('projects'));
    }
    
    /**
     * API - Lấy danh sách dự án
     */
    public function apiProjects()
    {
        header('Content-Type: application/json');
        
        try {
            $projects = $this->projectService->getAllProjects();
            echo json_encode([
                'success' => true,
                'data' => $projects
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    private function getTotalProjects()
    {
        try {
            $result = db()->fetch("SELECT COUNT(*) as count FROM de_tai_nghien_cuu");
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getActiveProjects()
    {
        try {
            $result = db()->fetch("SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE trang_thai = 'active'");
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getTotalStudents()
    {
        try {
            $result = db()->fetch("SELECT COUNT(*) as count FROM sinh_vien");
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getTotalTeachers()
    {
        try {
            $result = db()->fetch("SELECT COUNT(*) as count FROM giang_vien");
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getRecentProjects()
    {
        try {
            return db()->fetchAll("
                SELECT dt.*, sv.ho_ten as student_name, gv.ho_ten as teacher_name
                FROM de_tai_nghien_cuu dt
                LEFT JOIN sinh_vien sv ON dt.ma_sinh_vien = sv.ma_sinh_vien
                LEFT JOIN giang_vien gv ON dt.ma_giang_vien = gv.ma_giang_vien
                ORDER BY dt.ngay_tao DESC
                LIMIT 5
            ");
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getNotifications()
    {
        try {
            return db()->fetchAll("
                SELECT * FROM thong_bao
                WHERE trang_thai = 'unread'
                ORDER BY ngay_tao DESC
                LIMIT 10
            ");
        } catch (Exception $e) {
            return [];
        }
    }
}

