<?php
require_once 'BaseModel.php';

/**
 * Model xử lý đề tài nghiên cứu khoa học
 */
class ProjectModel extends BaseModel {
    
    /**
     * Khởi tạo model
     */
    public function __construct() {
        parent::__construct();
        $this->table = 'de_tai_nghien_cuu';
        $this->primaryKey = 'DT_MADT';
    }
    
    /**
     * Lấy tất cả đề tài với thông tin liên quan
     * @param array $filters Các điều kiện lọc
     * @return array Danh sách đề tài
     */
    public function getAllProjects($filters = []) {
        $sql = "SELECT dt.*, gv.GV_HOGV, gv.GV_TENGV, ldt.LDT_TENLOAI, hd.HD_NGAYKT
                FROM de_tai_nghien_cuu dt
                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA";
                
        $params = [];
        $where = [];
        
        // Áp dụng các bộ lọc
        if (!empty($filters)) {
            if (isset($filters['status']) && $filters['status']) {
                $where[] = "dt.DT_TRANGTHAI = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['department']) && $filters['department']) {
                $where[] = "gv.DV_MADV = ?";
                $params[] = $filters['department'];
            }
            
            if (isset($filters['type']) && $filters['type']) {
                $where[] = "dt.LDT_MA = ?";
                $params[] = $filters['type'];
            }
            
            if (isset($filters['teacher']) && $filters['teacher']) {
                $where[] = "dt.GV_MAGV = ?";
                $params[] = $filters['teacher'];
            }
            
            if (isset($filters['search']) && $filters['search']) {
                $search = '%' . $filters['search'] . '%';
                $where[] = "(dt.DT_MADT LIKE ? OR dt.DT_TENDT LIKE ? OR dt.DT_MOTA LIKE ?)";
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }
        }
        
        // Thêm điều kiện WHERE
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        // Sắp xếp
        if (isset($filters['sort']) && isset($filters['direction'])) {
            $sql .= " ORDER BY " . $filters['sort'] . " " . $filters['direction'];
        } else {
            $sql .= " ORDER BY dt.DT_MADT DESC";
        }
        
        // Giới hạn và phân trang
        if (isset($filters['limit']) && isset($filters['offset'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int) $filters['limit'];
            $params[] = (int) $filters['offset'];
        }
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Lấy đề tài theo ID với thông tin đầy đủ
     * @param string $id Mã đề tài
     * @return array Thông tin đề tài
     */
    public function getProjectDetail($id) {
        $sql = "SELECT dt.*, gv.GV_HOGV, gv.GV_TENGV, gv.GV_EMAIL, ldt.LDT_TENLOAI, 
                lvnc.LVNC_TEN, lvut.LVUT_TEN, 
                hd.HD_NGAYBD, hd.HD_NGAYKT, hd.HD_TONGKINHPHI, hd.HD_FILEHD
                FROM de_tai_nghien_cuu dt
                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
                LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
                LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
                WHERE dt.DT_MADT = ?";
        
        return fetchRow($sql, [$id]);
    }
    
    /**
     * Lấy danh sách đề tài của sinh viên
     * @param string $student_id Mã sinh viên
     * @return array Danh sách đề tài
     */
    public function getStudentProjects($student_id) {
        $sql = "SELECT dt.*, ct.CTTG_VAITRO, gv.GV_HOGV, gv.GV_TENGV, hd.HD_NGAYKT
                FROM de_tai_nghien_cuu dt
                JOIN chi_tiet_tham_gia ct ON dt.DT_MADT = ct.DT_MADT
                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
                WHERE ct.SV_MASV = ?
                ORDER BY ct.CTTG_NGAYTHAMGIA DESC";
        
        return fetchAll($sql, [$student_id]);
    }
    
    /**
     * Lấy danh sách đề tài của giảng viên
     * @param string $teacher_id Mã giảng viên
     * @return array Danh sách đề tài
     */
    public function getTeacherProjects($teacher_id) {
        $sql = "SELECT dt.*, ldt.LDT_TENLOAI, hd.HD_NGAYKT,
                (SELECT COUNT(*) FROM chi_tiet_tham_gia WHERE DT_MADT = dt.DT_MADT) as total_members
                FROM de_tai_nghien_cuu dt
                LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
                WHERE dt.GV_MAGV = ?
                ORDER BY dt.DT_MADT DESC";
        
        return fetchAll($sql, [$teacher_id]);
    }
    
    /**
     * Lấy thành viên của đề tài
     * @param string $project_id Mã đề tài
     * @return array Danh sách thành viên
     */
    public function getProjectMembers($project_id) {
        $sql = "SELECT sv.*, ct.CTTG_VAITRO, ct.CTTG_NGAYTHAMGIA, lop.LOP_TEN, khoa.DV_TENDV
                FROM chi_tiet_tham_gia ct
                JOIN sinh_vien sv ON ct.SV_MASV = sv.SV_MASV
                LEFT JOIN lop ON sv.LOP_MA = lop.LOP_MA
                LEFT JOIN khoa ON lop.DV_MADV = khoa.DV_MADV
                WHERE ct.DT_MADT = ?
                ORDER BY ct.CTTG_VAITRO DESC, ct.CTTG_NGAYTHAMGIA ASC";
        
        return fetchAll($sql, [$project_id]);
    }
    
    /**
     * Lấy danh sách báo cáo của đề tài
     * @param string $project_id Mã đề tài
     * @return array Danh sách báo cáo
     */
    public function getProjectReports($project_id) {
        // Kiểm tra xem bảng bao_cao có tồn tại không
        $check_sql = "SHOW TABLES LIKE 'bao_cao'";
        $check_result = fetchRow($check_sql);
        
        if (!$check_result) {
            // Bảng bao_cao không tồn tại, trả về mảng rỗng
            error_log("Bảng bao_cao không tồn tại trong cơ sở dữ liệu");
            return [];
        }
        
        $sql = "SELECT bc.*, lbc.LBC_TENLOAI, sv.SV_MASV, sv.SV_HOSV, sv.SV_TENSV
                FROM bao_cao bc
                LEFT JOIN loai_bao_cao lbc ON bc.LBC_MALOAI = lbc.LBC_MALOAI
                LEFT JOIN sinh_vien sv ON bc.SV_MASV = sv.SV_MASV
                WHERE bc.DT_MADT = ?
                ORDER BY bc.BC_NGAYNOP DESC";
        
        return fetchAll($sql, [$project_id]);
    }
    
    /**
     * Tạo đề tài mới
     * @param array $data Thông tin đề tài
     * @return string|bool Mã đề tài mới hoặc false nếu lỗi
     */
    public function createProject($data) {
        // Sinh mã đề tài mới
        $nextID = $this->generateNextProjectID();
        $data['DT_MADT'] = $nextID;
        
        // Thêm đề tài
        if ($this->create($data)) {
            return $nextID;
        }
        
        return false;
    }
    
    /**
     * Thêm thành viên vào đề tài
     * @param string $project_id Mã đề tài
     * @param string $student_id Mã sinh viên
     * @param string $role Vai trò (Chủ nhiệm/Thành viên)
     * @param string $semester_id Mã học kỳ
     * @return bool Kết quả thêm
     */
    public function addProjectMember($project_id, $student_id, $role, $semester_id) {
        $data = [
            'DT_MADT' => $project_id,
            'SV_MASV' => $student_id,
            'CTTG_VAITRO' => $role,
            'HK_MA' => $semester_id,
            'CTTG_NGAYTHAMGIA' => date('Y-m-d')
        ];
        
        return insert('chi_tiet_tham_gia', $data);
    }
    
    /**
     * Sinh mã đề tài tiếp theo
     * @return string Mã đề tài mới
     */
    private function generateNextProjectID() {
        $sql = "SELECT MAX(DT_MADT) as max_id FROM de_tai_nghien_cuu";
        $result = fetchRow($sql);
        
        if ($result && $result['max_id']) {
            $current_id = (int) substr($result['max_id'], 2);
            $next_id = $current_id + 1;
        } else {
            $next_id = 1;
        }
        
        return 'DT' . str_pad($next_id, 7, '0', STR_PAD_LEFT);
    }
}
?>
