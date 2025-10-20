<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\StudentResource;
use App\Http\Requests\StudentRequest;

/**
 * Student API Controller
 */
class StudentController extends BaseApiController
{
    /**
     * GET /api/v2/students
     * Lấy danh sách sinh viên
     */
    public function index($request)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'students.view')) {
                return $this->forbidden('Không có quyền xem danh sách sinh viên');
            }

            // Validate filter parameters
            $studentRequest = new StudentRequest($request);
            if (!$studentRequest->validateForFilter()) {
                return $this->validationError($studentRequest->errors(), 'Tham số lọc không hợp lệ');
            }

            // Get pagination parameters
            $pagination = $this->getPaginationParams($request);
            
            // Get filter parameters
            $filters = [
                'department' => $request['department'] ?? '',
                'school_year' => $request['school_year'] ?? '',
                'class' => $request['class'] ?? '',
                'research_status' => $request['research_status'] ?? ''
            ];

            // Get students from database
            $students = $this->getStudents($filters, $pagination);
            $total = $this->getStudentsCount($filters);

            $this->logRequest('GET', '/api/v2/students', $user, 200);

            return $this->paginatedResponse($students, $total, $pagination['page'], $pagination['limit']);

        } catch (Exception $e) {
            $this->logRequest('GET', '/api/v2/students', null, 500);
            return $this->serverError('Có lỗi xảy ra khi lấy danh sách sinh viên');
        }
    }

    /**
     * GET /api/v2/students/{id}
     * Lấy thông tin chi tiết sinh viên
     */
    public function show($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'students.view')) {
                return $this->forbidden('Không có quyền xem thông tin sinh viên');
            }

            // Get student details
            $student = $this->getStudentById($id);

            if (!$student) {
                return $this->notFound('Không tìm thấy sinh viên');
            }

            $this->logRequest('GET', "/api/v2/students/{$id}", $user, 200);

            return (new StudentResource($student, 200, 'Lấy thông tin sinh viên thành công'))->send();

        } catch (Exception $e) {
            $this->logRequest('GET', "/api/v2/students/{$id}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi lấy thông tin sinh viên');
        }
    }

    /**
     * POST /api/v2/students
     * Tạo sinh viên mới
     */
    public function store($request)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'students.create')) {
                return $this->forbidden('Không có quyền tạo sinh viên');
            }

            // Validate input
            $studentRequest = new StudentRequest($request);
            if (!$studentRequest->validateForCreate()) {
                return $this->validationError($studentRequest->errors(), 'Dữ liệu sinh viên không hợp lệ');
            }

            // Create student
            $studentId = $this->createStudent($request);

            if (!$studentId) {
                return $this->error('Không thể tạo sinh viên', 500);
            }

            // Get created student
            $student = $this->getStudentById($studentId);

            $this->logRequest('POST', '/api/v2/students', $user, 201);

            return (new StudentResource($student, 201, 'Tạo sinh viên thành công'))->send();

        } catch (Exception $e) {
            $this->logRequest('POST', '/api/v2/students', null, 500);
            return $this->serverError('Có lỗi xảy ra khi tạo sinh viên');
        }
    }

    /**
     * PUT /api/v2/students/{id}
     * Cập nhật thông tin sinh viên
     */
    public function update($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'students.update')) {
                return $this->forbidden('Không có quyền cập nhật sinh viên');
            }

            // Check if student exists
            $existingStudent = $this->getStudentById($id);
            if (!$existingStudent) {
                return $this->notFound('Không tìm thấy sinh viên');
            }

            // Validate input
            $studentRequest = new StudentRequest($request);
            if (!$studentRequest->validateForUpdate()) {
                return $this->validationError($studentRequest->errors(), 'Dữ liệu cập nhật không hợp lệ');
            }

            // Update student
            $success = $this->updateStudent($id, $request);

            if (!$success) {
                return $this->error('Không thể cập nhật sinh viên', 500);
            }

            // Get updated student
            $student = $this->getStudentById($id);

            $this->logRequest('PUT', "/api/v2/students/{$id}", $user, 200);

            return (new StudentResource($student, 200, 'Cập nhật sinh viên thành công'))->send();

        } catch (Exception $e) {
            $this->logRequest('PUT', "/api/v2/students/{$id}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi cập nhật sinh viên');
        }
    }

    /**
     * DELETE /api/v2/students/{id}
     * Xóa sinh viên
     */
    public function destroy($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'students.delete')) {
                return $this->forbidden('Không có quyền xóa sinh viên');
            }

            // Check if student exists
            $existingStudent = $this->getStudentById($id);
            if (!$existingStudent) {
                return $this->notFound('Không tìm thấy sinh viên');
            }

            // Delete student
            $success = $this->deleteStudent($id);

            if (!$success) {
                return $this->error('Không thể xóa sinh viên', 500);
            }

            $this->logRequest('DELETE', "/api/v2/students/{$id}", $user, 200);

            return $this->success(null, 'Xóa sinh viên thành công');

        } catch (Exception $e) {
            $this->logRequest('DELETE', "/api/v2/students/{$id}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi xóa sinh viên');
        }
    }

    /**
     * Get students from database
     */
    private function getStudents($filters, $pagination)
    {
        // Include database connection
        include_once __DIR__ . '/../../../include/connect.php';
        
        $where_conditions = [];
        $params = [];
        $param_types = '';
        
        // Build WHERE conditions
        if (!empty($filters['department'])) {
            $where_conditions[] = "l.DV_MADV = ?";
            $params[] = $filters['department'];
            $param_types .= 's';
        }
        
        if (!empty($filters['school_year'])) {
            $where_conditions[] = "l.KH_NAM = ?";
            $params[] = $filters['school_year'];
            $param_types .= 's';
        }
        
        if (!empty($filters['class'])) {
            $where_conditions[] = "sv.LOP_MA = ?";
            $params[] = $filters['class'];
            $param_types .= 's';
        }
        
        if (!empty($filters['research_status'])) {
            switch ($filters['research_status']) {
                case 'active':
                    $where_conditions[] = "COALESCE(project_stats.project_count, 0) > 0";
                    break;
                case 'completed':
                    $where_conditions[] = "COALESCE(project_stats.completed_project_count, 0) > 0";
                    break;
                case 'none':
                    $where_conditions[] = "COALESCE(project_stats.project_count, 0) = 0";
                    break;
            }
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Query students
        $sql = "SELECT 
                    sv.SV_MASV,
                    CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as SV_HOTEN,
                    sv.SV_HOSV,
                    sv.SV_TENSV,
                    sv.SV_EMAIL,
                    sv.SV_SDT,
                    l.LOP_TEN,
                    l.LOP_MA,
                    k.DV_TENDV,
                    k.DV_MADV,
                    COALESCE(project_stats.project_count, 0) as project_count,
                    COALESCE(project_stats.completed_project_count, 0) as completed_project_count
                FROM sinh_vien sv
                JOIN lop l ON sv.LOP_MA = l.LOP_MA
                JOIN khoa k ON l.DV_MADV = k.DV_MADV
                LEFT JOIN (
                    SELECT 
                        cttg.SV_MASV,
                        COUNT(DISTINCT cttg.DT_MADT) as project_count,
                        COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN cttg.DT_MADT END) as completed_project_count
                    FROM chi_tiet_tham_gia cttg
                    JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                    GROUP BY cttg.SV_MASV
                ) project_stats ON sv.SV_MASV = project_stats.SV_MASV
                $where_clause
                ORDER BY sv.SV_HOSV, sv.SV_TENSV
                LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        
        // Add pagination parameters
        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];
        $param_types .= 'ii';
        
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        return $students;
    }

    /**
     * Get students count
     */
    private function getStudentsCount($filters)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $where_conditions = [];
        $params = [];
        $param_types = '';
        
        // Build WHERE conditions (same as getStudents)
        if (!empty($filters['department'])) {
            $where_conditions[] = "l.DV_MADV = ?";
            $params[] = $filters['department'];
            $param_types .= 's';
        }
        
        if (!empty($filters['school_year'])) {
            $where_conditions[] = "l.KH_NAM = ?";
            $params[] = $filters['school_year'];
            $param_types .= 's';
        }
        
        if (!empty($filters['class'])) {
            $where_conditions[] = "sv.LOP_MA = ?";
            $params[] = $filters['class'];
            $param_types .= 's';
        }
        
        if (!empty($filters['research_status'])) {
            switch ($filters['research_status']) {
                case 'active':
                    $where_conditions[] = "COALESCE(project_stats.project_count, 0) > 0";
                    break;
                case 'completed':
                    $where_conditions[] = "COALESCE(project_stats.completed_project_count, 0) > 0";
                    break;
                case 'none':
                    $where_conditions[] = "COALESCE(project_stats.project_count, 0) = 0";
                    break;
            }
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $count_sql = "SELECT COUNT(DISTINCT sv.SV_MASV) as total
                      FROM sinh_vien sv
                      JOIN lop l ON sv.LOP_MA = l.LOP_MA
                      JOIN khoa k ON l.DV_MADV = k.DV_MADV
                      LEFT JOIN (
                          SELECT 
                              cttg.SV_MASV,
                              COUNT(DISTINCT cttg.DT_MADT) as project_count,
                              COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN cttg.DT_MADT END) as completed_project_count
                          FROM chi_tiet_tham_gia cttg
                          JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                          GROUP BY cttg.SV_MASV
                      ) project_stats ON sv.SV_MASV = project_stats.SV_MASV
                      $where_clause";
        
        $count_stmt = $conn->prepare($count_sql);
        if (!empty($params)) {
            $count_stmt->bind_param($param_types, ...$params);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total = $count_result->fetch_assoc()['total'];
        
        return (int)$total;
    }

    /**
     * Get student by ID
     */
    private function getStudentById($id)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "SELECT 
                    sv.SV_MASV,
                    CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as SV_HOTEN,
                    sv.SV_HOSV,
                    sv.SV_TENSV,
                    sv.SV_EMAIL,
                    sv.SV_SDT,
                    l.LOP_TEN,
                    l.LOP_MA,
                    k.DV_TENDV,
                    k.DV_MADV,
                    COALESCE(project_stats.project_count, 0) as project_count,
                    COALESCE(project_stats.completed_project_count, 0) as completed_project_count
                FROM sinh_vien sv
                JOIN lop l ON sv.LOP_MA = l.LOP_MA
                JOIN khoa k ON l.DV_MADV = k.DV_MADV
                LEFT JOIN (
                    SELECT 
                        cttg.SV_MASV,
                        COUNT(DISTINCT cttg.DT_MADT) as project_count,
                        COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN cttg.DT_MADT END) as completed_project_count
                    FROM chi_tiet_tham_gia cttg
                    JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                    GROUP BY cttg.SV_MASV
                ) project_stats ON sv.SV_MASV = project_stats.SV_MASV
                WHERE sv.SV_MASV = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    /**
     * Create student
     */
    private function createStudent($data)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "INSERT INTO sinh_vien (SV_MASV, SV_HOSV, SV_TENSV, SV_EMAIL, SV_SDT, LOP_MA) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", 
            $data['student_code'],
            $data['last_name'],
            $data['first_name'],
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $data['class_code']
        );
        
        if ($stmt->execute()) {
            return $data['student_code'];
        }
        
        return false;
    }

    /**
     * Update student
     */
    private function updateStudent($id, $data)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "UPDATE sinh_vien SET SV_HOSV = ?, SV_TENSV = ?, SV_EMAIL = ?, SV_SDT = ?, LOP_MA = ? WHERE SV_MASV = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss",
            $data['last_name'] ?? '',
            $data['first_name'] ?? '',
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $data['class_code'] ?? '',
            $id
        );
        
        return $stmt->execute();
    }

    /**
     * Delete student
     */
    private function deleteStudent($id)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "DELETE FROM sinh_vien WHERE SV_MASV = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        
        return $stmt->execute();
    }
}
