<?php
/**
 * Authentication Controller
 */

class AuthController
{
    private $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Trang chủ - kiểm tra đăng nhập và chuyển hướng
     */
    public function index()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirectToRole();
            return;
        }
        
        // Hiển thị trang chủ
        $this->showHome();
    }
    
    /**
     * Hiển thị trang đăng nhập
     */
    public function showLogin()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirectToRole();
            return;
        }
        
        view('auth/login');
    }
    
    /**
     * Xử lý đăng nhập
     */
    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin';
            redirect('/login');
            return;
        }
        
        $user = $this->authService->authenticate($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            
            $this->redirectToRole();
        } else {
            $_SESSION['error'] = 'Tên đăng nhập hoặc mật khẩu không đúng';
            redirect('/login');
        }
    }
    
    /**
     * Đăng xuất
     */
    public function logout()
    {
        session_destroy();
        redirect('/');
    }
    
    /**
     * Chuyển hướng theo role
     */
    private function redirectToRole()
    {
        $role = $_SESSION['role'] ?? '';
        
        switch ($role) {
            case 'admin':
                redirect('/admin/dashboard');
                break;
            case 'student':
                redirect('/student/dashboard');
                break;
            case 'teacher':
                redirect('/teacher/dashboard');
                break;
            case 'research_manager':
                redirect('/research/dashboard');
                break;
            default:
                redirect('/login?error=role');
        }
    }
    
    /**
     * Hiển thị trang chủ
     */
    private function showHome()
    {
        // Load thống kê cơ bản
        $stats = [
            'projects' => $this->getProjectCount(),
            'students' => $this->getStudentCount(),
            'teachers' => $this->getTeacherCount(),
            'departments' => $this->getDepartmentCount()
        ];
        
        view('home', compact('stats'));
    }
    
    private function getProjectCount()
    {
        try {
            $result = db()->fetch("SELECT COUNT(*) as count FROM de_tai_nghien_cuu");
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getStudentCount()
    {
        try {
            $result = db()->fetch("SELECT COUNT(*) as count FROM sinh_vien");
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getTeacherCount()
    {
        try {
            $result = db()->fetch("SELECT COUNT(*) as count FROM giang_vien");
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getDepartmentCount()
    {
        try {
            $result = db()->fetch("SELECT COUNT(*) as count FROM khoa");
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}

