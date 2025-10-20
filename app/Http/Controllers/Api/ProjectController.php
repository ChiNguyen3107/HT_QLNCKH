<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProjectResource;
use App\Http\Requests\ProjectRequest;

/**
 * Project API Controller
 */
class ProjectController extends BaseApiController
{
    /**
     * GET /api/v2/projects
     * Lấy danh sách đề tài
     */
    public function index($request)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'projects.view')) {
                return $this->forbidden('Không có quyền xem danh sách đề tài');
            }

            // Validate filter parameters
            $projectRequest = new ProjectRequest($request);
            if (!$projectRequest->validateForFilter()) {
                return $this->validationError($projectRequest->errors(), 'Tham số lọc không hợp lệ');
            }

            // Get pagination parameters
            $pagination = $this->getPaginationParams($request);
            
            // Get filter parameters
            $filters = [
                'status' => $request['status'] ?? '',
                'supervisor_id' => $request['supervisor_id'] ?? '',
                'student_id' => $request['student_id'] ?? '',
                'department' => $request['department'] ?? '',
                'year' => $request['year'] ?? ''
            ];

            // Get projects from database
            $projects = $this->getProjects($filters, $pagination);
            $total = $this->getProjectsCount($filters);

            $this->logRequest('GET', '/api/v2/projects', $user, 200);

            return $this->paginatedResponse($projects, $total, $pagination['page'], $pagination['limit']);

        } catch (Exception $e) {
            $this->logRequest('GET', '/api/v2/projects', null, 500);
            return $this->serverError('Có lỗi xảy ra khi lấy danh sách đề tài');
        }
    }

    /**
     * GET /api/v2/projects/{id}
     * Lấy thông tin chi tiết đề tài
     */
    public function show($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'projects.view')) {
                return $this->forbidden('Không có quyền xem thông tin đề tài');
            }

            // Get project details
            $project = $this->getProjectById($id);

            if (!$project) {
                return $this->notFound('Không tìm thấy đề tài');
            }

            $this->logRequest('GET', "/api/v2/projects/{$id}", $user, 200);

            return (new ProjectResource($project, 200, 'Lấy thông tin đề tài thành công'))->send();

        } catch (Exception $e) {
            $this->logRequest('GET', "/api/v2/projects/{$id}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi lấy thông tin đề tài');
        }
    }

    /**
     * POST /api/v2/projects
     * Tạo đề tài mới
     */
    public function store($request)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'projects.create')) {
                return $this->forbidden('Không có quyền tạo đề tài');
            }

            // Validate input
            $projectRequest = new ProjectRequest($request);
            if (!$projectRequest->validateForCreate()) {
                return $this->validationError($projectRequest->errors(), 'Dữ liệu đề tài không hợp lệ');
            }

            // Create project
            $projectId = $this->createProject($request);

            if (!$projectId) {
                return $this->error('Không thể tạo đề tài', 500);
            }

            // Get created project
            $project = $this->getProjectById($projectId);

            $this->logRequest('POST', '/api/v2/projects', $user, 201);

            return (new ProjectResource($project, 201, 'Tạo đề tài thành công'))->send();

        } catch (Exception $e) {
            $this->logRequest('POST', '/api/v2/projects', null, 500);
            return $this->serverError('Có lỗi xảy ra khi tạo đề tài');
        }
    }

    /**
     * PUT /api/v2/projects/{id}
     * Cập nhật thông tin đề tài
     */
    public function update($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'projects.update')) {
                return $this->forbidden('Không có quyền cập nhật đề tài');
            }

            // Check if project exists
            $existingProject = $this->getProjectById($id);
            if (!$existingProject) {
                return $this->notFound('Không tìm thấy đề tài');
            }

            // Validate input
            $projectRequest = new ProjectRequest($request);
            if (!$projectRequest->validateForUpdate()) {
                return $this->validationError($projectRequest->errors(), 'Dữ liệu cập nhật không hợp lệ');
            }

            // Update project
            $success = $this->updateProject($id, $request);

            if (!$success) {
                return $this->error('Không thể cập nhật đề tài', 500);
            }

            // Get updated project
            $project = $this->getProjectById($id);

            $this->logRequest('PUT', "/api/v2/projects/{$id}", $user, 200);

            return (new ProjectResource($project, 200, 'Cập nhật đề tài thành công'))->send();

        } catch (Exception $e) {
            $this->logRequest('PUT', "/api/v2/projects/{$id}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi cập nhật đề tài');
        }
    }

    /**
     * DELETE /api/v2/projects/{id}
     * Xóa đề tài
     */
    public function destroy($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'projects.delete')) {
                return $this->forbidden('Không có quyền xóa đề tài');
            }

            // Check if project exists
            $existingProject = $this->getProjectById($id);
            if (!$existingProject) {
                return $this->notFound('Không tìm thấy đề tài');
            }

            // Delete project
            $success = $this->deleteProject($id);

            if (!$success) {
                return $this->error('Không thể xóa đề tài', 500);
            }

            $this->logRequest('DELETE', "/api/v2/projects/{$id}", $user, 200);

            return $this->success(null, 'Xóa đề tài thành công');

        } catch (Exception $e) {
            $this->logRequest('DELETE', "/api/v2/projects/{$id}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi xóa đề tài');
        }
    }

    /**
     * POST /api/v2/projects/{id}/members
     * Thêm thành viên vào đề tài
     */
    public function addMember($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'projects.update')) {
                return $this->forbidden('Không có quyền thêm thành viên');
            }

            // Check if project exists
            $project = $this->getProjectById($id);
            if (!$project) {
                return $this->notFound('Không tìm thấy đề tài');
            }

            // Validate input
            $projectRequest = new ProjectRequest($request);
            if (!$projectRequest->validateForAddMember()) {
                return $this->validationError($projectRequest->errors(), 'Dữ liệu thành viên không hợp lệ');
            }

            // Add member
            $success = $this->addProjectMember($id, $request['student_id'], $request['role'] ?? 'member');

            if (!$success) {
                return $this->error('Không thể thêm thành viên', 500);
            }

            $this->logRequest('POST', "/api/v2/projects/{$id}/members", $user, 200);

            return $this->success(null, 'Thêm thành viên thành công');

        } catch (Exception $e) {
            $this->logRequest('POST', "/api/v2/projects/{$id}/members", null, 500);
            return $this->serverError('Có lỗi xảy ra khi thêm thành viên');
        }
    }

    /**
     * DELETE /api/v2/projects/{id}/members/{student_id}
     * Xóa thành viên khỏi đề tài
     */
    public function removeMember($request, $id, $studentId)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'projects.update')) {
                return $this->forbidden('Không có quyền xóa thành viên');
            }

            // Check if project exists
            $project = $this->getProjectById($id);
            if (!$project) {
                return $this->notFound('Không tìm thấy đề tài');
            }

            // Remove member
            $success = $this->removeProjectMember($id, $studentId);

            if (!$success) {
                return $this->error('Không thể xóa thành viên', 500);
            }

            $this->logRequest('DELETE', "/api/v2/projects/{$id}/members/{$studentId}", $user, 200);

            return $this->success(null, 'Xóa thành viên thành công');

        } catch (Exception $e) {
            $this->logRequest('DELETE', "/api/v2/projects/{$id}/members/{$studentId}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi xóa thành viên');
        }
    }

    /**
     * POST /api/v2/projects/{id}/evaluation
     * Đánh giá đề tài
     */
    public function evaluate($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'evaluations.create')) {
                return $this->forbidden('Không có quyền đánh giá đề tài');
            }

            // Check if project exists
            $project = $this->getProjectById($id);
            if (!$project) {
                return $this->notFound('Không tìm thấy đề tài');
            }

            // Validate input
            $projectRequest = new ProjectRequest($request);
            if (!$projectRequest->validateForEvaluation()) {
                return $this->validationError($projectRequest->errors(), 'Dữ liệu đánh giá không hợp lệ');
            }

            // Add evaluation
            $success = $this->addProjectEvaluation($id, $request, $user);

            if (!$success) {
                return $this->error('Không thể đánh giá đề tài', 500);
            }

            $this->logRequest('POST', "/api/v2/projects/{$id}/evaluation", $user, 200);

            return $this->success(null, 'Đánh giá đề tài thành công');

        } catch (Exception $e) {
            $this->logRequest('POST', "/api/v2/projects/{$id}/evaluation", null, 500);
            return $this->serverError('Có lỗi xảy ra khi đánh giá đề tài');
        }
    }

    /**
     * Get projects from database
     */
    private function getProjects($filters, $pagination)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $where_conditions = [];
        $params = [];
        $param_types = '';
        
        // Build WHERE conditions
        if (!empty($filters['status'])) {
            $where_conditions[] = "dt.DT_TRANGTHAI = ?";
            $params[] = $filters['status'];
            $param_types .= 's';
        }
        
        if (!empty($filters['supervisor_id'])) {
            $where_conditions[] = "dt.GV_MAGV = ?";
            $params[] = $filters['supervisor_id'];
            $param_types .= 's';
        }
        
        if (!empty($filters['student_id'])) {
            $where_conditions[] = "cttg.SV_MASV = ?";
            $param_types .= 's';
        }
        
        if (!empty($filters['department'])) {
            $where_conditions[] = "k.DV_MADV = ?";
            $params[] = $filters['department'];
            $param_types .= 's';
        }
        
        if (!empty($filters['year'])) {
            $where_conditions[] = "YEAR(dt.DT_NGAYBD) = ?";
            $params[] = $filters['year'];
            $param_types .= 'i';
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Query projects
        $sql = "SELECT 
                    dt.DT_MADT,
                    dt.DT_TEN,
                    dt.DT_MOTA,
                    dt.DT_TRANGTHAI,
                    dt.DT_NGAYBD,
                    dt.DT_NGAYKT,
                    dt.DT_KINHPHI,
                    dt.GV_MAGV,
                    CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
                    gv.GV_EMAIL
                FROM de_tai_nghien_cuu dt
                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                LEFT JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
                LEFT JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA
                LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV
                $where_clause
                GROUP BY dt.DT_MADT
                ORDER BY dt.DT_NGAYBD DESC
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
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        return $projects;
    }

    /**
     * Get projects count
     */
    private function getProjectsCount($filters)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $where_conditions = [];
        $params = [];
        $param_types = '';
        
        // Build WHERE conditions (same as getProjects)
        if (!empty($filters['status'])) {
            $where_conditions[] = "dt.DT_TRANGTHAI = ?";
            $params[] = $filters['status'];
            $param_types .= 's';
        }
        
        if (!empty($filters['supervisor_id'])) {
            $where_conditions[] = "dt.GV_MAGV = ?";
            $params[] = $filters['supervisor_id'];
            $param_types .= 's';
        }
        
        if (!empty($filters['student_id'])) {
            $where_conditions[] = "cttg.SV_MASV = ?";
            $param_types .= 's';
        }
        
        if (!empty($filters['department'])) {
            $where_conditions[] = "k.DV_MADV = ?";
            $params[] = $filters['department'];
            $param_types .= 's';
        }
        
        if (!empty($filters['year'])) {
            $where_conditions[] = "YEAR(dt.DT_NGAYBD) = ?";
            $params[] = $filters['year'];
            $param_types .= 'i';
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $count_sql = "SELECT COUNT(DISTINCT dt.DT_MADT) as total
                      FROM de_tai_nghien_cuu dt
                      LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                      LEFT JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
                      LEFT JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                      LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA
                      LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV
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
     * Get project by ID
     */
    private function getProjectById($id)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "SELECT 
                    dt.DT_MADT,
                    dt.DT_TEN,
                    dt.DT_MOTA,
                    dt.DT_TRANGTHAI,
                    dt.DT_NGAYBD,
                    dt.DT_NGAYKT,
                    dt.DT_KINHPHI,
                    dt.GV_MAGV,
                    CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
                    gv.GV_EMAIL
                FROM de_tai_nghien_cuu dt
                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                WHERE dt.DT_MADT = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    /**
     * Create project
     */
    private function createProject($data)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $projectId = 'DT' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO de_tai_nghien_cuu (DT_MADT, DT_TEN, DT_MOTA, DT_TRANGTHAI, DT_NGAYBD, DT_NGAYKT, DT_KINHPHI, GV_MAGV) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssds", 
            $projectId,
            $data['title'],
            $data['description'] ?? '',
            $data['status'] ?? 'Đang thực hiện',
            $data['start_date'],
            $data['end_date'],
            $data['budget'] ?? 0,
            $data['supervisor_id']
        );
        
        if ($stmt->execute()) {
            return $projectId;
        }
        
        return false;
    }

    /**
     * Update project
     */
    private function updateProject($id, $data)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "UPDATE de_tai_nghien_cuu SET DT_TEN = ?, DT_MOTA = ?, DT_TRANGTHAI = ?, DT_NGAYBD = ?, DT_NGAYKT = ?, DT_KINHPHI = ?, GV_MAGV = ? WHERE DT_MADT = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssds",
            $data['title'] ?? '',
            $data['description'] ?? '',
            $data['status'] ?? '',
            $data['start_date'] ?? '',
            $data['end_date'] ?? '',
            $data['budget'] ?? 0,
            $data['supervisor_id'] ?? '',
            $id
        );
        
        return $stmt->execute();
    }

    /**
     * Delete project
     */
    private function deleteProject($id)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "DELETE FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        
        return $stmt->execute();
    }

    /**
     * Add project member
     */
    private function addProjectMember($projectId, $studentId, $role = 'member')
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "INSERT INTO chi_tiet_tham_gia (DT_MADT, SV_MASV, VAI_TRO) VALUES (?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $projectId, $studentId, $role);
        
        return $stmt->execute();
    }

    /**
     * Remove project member
     */
    private function removeProjectMember($projectId, $studentId)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "DELETE FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $projectId, $studentId);
        
        return $stmt->execute();
    }

    /**
     * Add project evaluation
     */
    private function addProjectEvaluation($projectId, $data, $user)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "INSERT INTO danh_gia_de_tai (DT_MADT, NGUOI_DANH_GIA, DIEM_SO, NOI_DUNG_DANH_GIA, NGAY_DANH_GIA) VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssds", 
            $projectId,
            $user['user_id'],
            $data['score'],
            $data['comment'] ?? ''
        );
        
        return $stmt->execute();
    }
}
