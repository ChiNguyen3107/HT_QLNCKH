<?php
require_once 'BaseModel.php';

/**
 * Model xử lý sinh viên
 */
class StudentModel extends BaseModel {
    
    /**
     * Khởi tạo model
     */
    public function __construct() {
        parent::__construct();
        $this->table = 'sinh_vien';
        $this->primaryKey = 'SV_MASV';
    }
    
    /**
     * Lấy thông tin sinh viên
     * @param string $student_id Mã sinh viên
     * @return array Thông tin sinh viên
     */
    public function getStudent($student_id) {
        $sql = "SELECT sv.*, lop.LOP_TEN, lop.LOP_LOAICTDT, khoa.DV_TENDV, kh.KH_NAM 
                FROM sinh_vien sv
                LEFT JOIN lop ON sv.LOP_MA = lop.LOP_MA
                LEFT JOIN khoa ON lop.DV_MADV = khoa.DV_MADV
                LEFT JOIN khoa_hoc kh ON lop.KH_NAM = kh.KH_NAM
                WHERE sv.SV_MASV = ?";
        
        return fetchRow($sql, [$student_id]);
    }
    
    /**
     * Lấy danh sách sinh viên theo lớp
     * @param string $class_id Mã lớp
     * @return array Danh sách sinh viên
     */
    public function getStudentsByClass($class_id) {
        $sql = "SELECT sv.*, lop.LOP_TEN 
                FROM sinh_vien sv
                LEFT JOIN lop ON sv.LOP_MA = lop.LOP_MA
                WHERE sv.LOP_MA = ?
                ORDER BY sv.SV_HOSV, sv.SV_TENSV";
        
        return fetchAll($sql, [$class_id]);
    }
    
    /**
     * Lấy danh sách sinh viên theo khoa
     * @param string $department_id Mã khoa
     * @return array Danh sách sinh viên
     */
    public function getStudentsByDepartment($department_id) {
        $sql = "SELECT sv.*, lop.LOP_TEN 
                FROM sinh_vien sv
                JOIN lop ON sv.LOP_MA = lop.LOP_MA
                WHERE lop.DV_MADV = ?
                ORDER BY lop.LOP_MA, sv.SV_HOSV, sv.SV_TENSV";
        
        return fetchAll($sql, [$department_id]);
    }
    
    /**
     * Tìm kiếm sinh viên
     * @param string $keyword Từ khóa tìm kiếm
     * @return array Danh sách sinh viên
     */
    public function searchStudents($keyword) {
        $search = '%' . $keyword . '%';
        
        $sql = "SELECT sv.*, lop.LOP_TEN, khoa.DV_TENDV
                FROM sinh_vien sv
                LEFT JOIN lop ON sv.LOP_MA = lop.LOP_MA
                LEFT JOIN khoa ON lop.DV_MADV = khoa.DV_MADV
                WHERE sv.SV_MASV LIKE ? 
                   OR CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) LIKE ?
                   OR sv.SV_EMAIL LIKE ?
                   OR sv.SV_SDT LIKE ?
                   OR lop.LOP_TEN LIKE ?
                ORDER BY sv.SV_MASV";
        
        return fetchAll($sql, [$search, $search, $search, $search, $search]);
    }
    
    /**
     * Xác thực đăng nhập sinh viên
     * @param string $username Tên đăng nhập (mã SV)
     * @param string $password Mật khẩu
     * @return array|false Thông tin sinh viên hoặc false nếu không đúng
     */
    public function authenticate($username, $password) {
        $sql = "SELECT sv.*, lop.LOP_TEN, khoa.DV_TENDV 
                FROM sinh_vien sv
                LEFT JOIN lop ON sv.LOP_MA = lop.LOP_MA
                LEFT JOIN khoa ON lop.DV_MADV = khoa.DV_MADV
                WHERE sv.SV_MASV = ?";
        
        $student = fetchRow($sql, [$username]);
        
        if ($student && verifyPassword($password, $student['SV_MATKHAU'])) {
            return $student;
        }
        
        return false;
    }
    
    /**
     * Cập nhật thông tin sinh viên
     * @param string $student_id Mã sinh viên
     * @param array $data Thông tin cập nhật
     * @return bool Kết quả cập nhật
     */
    public function updateStudent($student_id, $data) {
        return $this->update($student_id, $data);
    }
    
    /**
     * Cập nhật mật khẩu sinh viên
     * @param string $student_id Mã sinh viên
     * @param string $new_password Mật khẩu mới
     * @return bool Kết quả cập nhật
     */
    public function updatePassword($student_id, $new_password) {
        $password_hash = hashPassword($new_password);
        return $this->update($student_id, ['SV_MATKHAU' => $password_hash]);
    }
    
    /**
     * Lấy số lượng đề tài của sinh viên
     * @param string $student_id Mã sinh viên
     * @return array Thông tin số lượng đề tài theo trạng thái
     */
    public function getStudentProjectStats($student_id) {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN dt.DT_TRANGTHAI = 'Đang thực hiện' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN dt.DT_TRANGTHAI = 'Chờ duyệt' THEN 1 ELSE 0 END) as waiting
                FROM chi_tiet_tham_gia ct
                JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                WHERE ct.SV_MASV = ?";
        
        return fetchRow($sql, [$student_id]);
    }
    
    /**
     * Tạo tài khoản sinh viên mới
     * @param array $data Thông tin sinh viên
     * @return string|bool Mã sinh viên mới hoặc false nếu lỗi
     */
    public function createStudent($data) {
        // Mã hóa mật khẩu nếu có
        if (isset($data['SV_MATKHAU'])) {
            $data['SV_MATKHAU'] = hashPassword($data['SV_MATKHAU']);
        }
        
        return $this->create($data);
    }
}
?>
