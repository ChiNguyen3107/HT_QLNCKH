<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\FacultyResource;
use App\Http\Requests\BaseRequest;

/**
 * Faculty API Controller
 */
class FacultyController extends BaseApiController
{
    /**
     * GET /api/v2/faculties
     * Lấy danh sách khoa/đơn vị
     */
    public function index($request)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'faculties.view')) {
                return $this->forbidden('Không có quyền xem danh sách khoa');
            }

            // Get pagination parameters
            $pagination = $this->getPaginationParams($request);
            
            // Get faculties from database
            $faculties = $this->getFaculties($pagination);
            $total = $this->getFacultiesCount();

            $this->logRequest('GET', '/api/v2/faculties', $user, 200);

            return $this->paginatedResponse($faculties, $total, $pagination['page'], $pagination['limit']);

        } catch (Exception $e) {
            $this->logRequest('GET', '/api/v2/faculties', null, 500);
            return $this->serverError('Có lỗi xảy ra khi lấy danh sách khoa');
        }
    }

    /**
     * GET /api/v2/faculties/{id}
     * Lấy thông tin chi tiết khoa
     */
    public function show($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'faculties.view')) {
                return $this->forbidden('Không có quyền xem thông tin khoa');
            }

            // Get faculty details
            $faculty = $this->getFacultyById($id);

            if (!$faculty) {
                return $this->notFound('Không tìm thấy khoa');
            }

            $this->logRequest('GET', "/api/v2/faculties/{$id}", $user, 200);

            return (new FacultyResource($faculty, 200, 'Lấy thông tin khoa thành công'))->send();

        } catch (Exception $e) {
            $this->logRequest('GET', "/api/v2/faculties/{$id}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi lấy thông tin khoa');
        }
    }

    /**
     * POST /api/v2/faculties
     * Tạo khoa mới
     */
    public function store($request)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'faculties.create')) {
                return $this->forbidden('Không có quyền tạo khoa');
            }

            // Validate input
            $validation = $this->validateFacultyRequest($request);
            if (!$validation['valid']) {
                return $this->validationError($validation['errors'], 'Dữ liệu khoa không hợp lệ');
            }

            // Create faculty
            $facultyId = $this->createFaculty($request);

            if (!$facultyId) {
                return $this->error('Không thể tạo khoa', 500);
            }

            // Get created faculty
            $faculty = $this->getFacultyById($facultyId);

            $this->logRequest('POST', '/api/v2/faculties', $user, 201);

            return (new FacultyResource($faculty, 201, 'Tạo khoa thành công'))->send();

        } catch (Exception $e) {
            $this->logRequest('POST', '/api/v2/faculties', null, 500);
            return $this->serverError('Có lỗi xảy ra khi tạo khoa');
        }
    }

    /**
     * PUT /api/v2/faculties/{id}
     * Cập nhật thông tin khoa
     */
    public function update($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'faculties.update')) {
                return $this->forbidden('Không có quyền cập nhật khoa');
            }

            // Check if faculty exists
            $existingFaculty = $this->getFacultyById($id);
            if (!$existingFaculty) {
                return $this->notFound('Không tìm thấy khoa');
            }

            // Validate input
            $validation = $this->validateFacultyRequest($request, false);
            if (!$validation['valid']) {
                return $this->validationError($validation['errors'], 'Dữ liệu cập nhật không hợp lệ');
            }

            // Update faculty
            $success = $this->updateFaculty($id, $request);

            if (!$success) {
                return $this->error('Không thể cập nhật khoa', 500);
            }

            // Get updated faculty
            $faculty = $this->getFacultyById($id);

            $this->logRequest('PUT', "/api/v2/faculties/{$id}", $user, 200);

            return (new FacultyResource($faculty, 200, 'Cập nhật khoa thành công'))->send();

        } catch (Exception $e) {
            $this->logRequest('PUT', "/api/v2/faculties/{$id}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi cập nhật khoa');
        }
    }

    /**
     * DELETE /api/v2/faculties/{id}
     * Xóa khoa
     */
    public function destroy($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'faculties.delete')) {
                return $this->forbidden('Không có quyền xóa khoa');
            }

            // Check if faculty exists
            $existingFaculty = $this->getFacultyById($id);
            if (!$existingFaculty) {
                return $this->notFound('Không tìm thấy khoa');
            }

            // Check if faculty has students or teachers
            if ($this->hasDependencies($id)) {
                return $this->error('Không thể xóa khoa vì còn có sinh viên hoặc giảng viên', 400);
            }

            // Delete faculty
            $success = $this->deleteFaculty($id);

            if (!$success) {
                return $this->error('Không thể xóa khoa', 500);
            }

            $this->logRequest('DELETE', "/api/v2/faculties/{$id}", $user, 200);

            return $this->success(null, 'Xóa khoa thành công');

        } catch (Exception $e) {
            $this->logRequest('DELETE', "/api/v2/faculties/{$id}", null, 500);
            return $this->serverError('Có lỗi xảy ra khi xóa khoa');
        }
    }

    /**
     * GET /api/v2/faculties/{id}/statistics
     * Lấy thống kê khoa
     */
    public function statistics($request, $id)
    {
        try {
            // Apply middleware
            $this->applyMiddleware($request, ['auth']);
            
            $user = $this->getCurrentUser($request);
            
            // Check permission
            if (!$this->hasPermission($user, 'faculties.view')) {
                return $this->forbidden('Không có quyền xem thống kê khoa');
            }

            // Check if faculty exists
            $faculty = $this->getFacultyById($id);
            if (!$faculty) {
                return $this->notFound('Không tìm thấy khoa');
            }

            // Get statistics
            $statistics = $this->getFacultyStatistics($id);

            $this->logRequest('GET', "/api/v2/faculties/{$id}/statistics", $user, 200);

            return $this->success($statistics, 'Lấy thống kê khoa thành công');

        } catch (Exception $e) {
            $this->logRequest('GET', "/api/v2/faculties/{$id}/statistics", null, 500);
            return $this->serverError('Có lỗi xảy ra khi lấy thống kê khoa');
        }
    }

    /**
     * Validate faculty request
     */
    private function validateFacultyRequest($request, $isCreate = true)
    {
        $errors = [];
        
        if ($isCreate) {
            if (empty($request['code'])) {
                $errors['code'] = ['Mã khoa là bắt buộc'];
            }
        }
        
        if (empty($request['name'])) {
            $errors['name'] = ['Tên khoa là bắt buộc'];
        }
        
        if (!empty($request['code']) && strlen($request['code']) > 20) {
            $errors['code'] = ['Mã khoa không được quá 20 ký tự'];
        }
        
        if (!empty($request['name']) && strlen($request['name']) > 255) {
            $errors['name'] = ['Tên khoa không được quá 255 ký tự'];
        }
        
        if (!empty($request['description']) && strlen($request['description']) > 1000) {
            $errors['description'] = ['Mô tả không được quá 1000 ký tự'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get faculties from database
     */
    private function getFaculties($pagination)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "SELECT 
                    k.DV_MADV,
                    k.DV_TENDV,
                    k.DV_MOTA,
                    COUNT(DISTINCT sv.SV_MASV) as student_count,
                    COUNT(DISTINCT gv.GV_MAGV) as teacher_count,
                    COUNT(DISTINCT dt.DT_MADT) as project_count
                FROM khoa k
                LEFT JOIN lop l ON k.DV_MADV = l.DV_MADV
                LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
                LEFT JOIN giang_vien gv ON k.DV_MADV = gv.DV_MADV
                LEFT JOIN de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV
                GROUP BY k.DV_MADV, k.DV_TENDV, k.DV_MOTA
                ORDER BY k.DV_TENDV ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $pagination['limit'], $pagination['offset']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $faculties = [];
        while ($row = $result->fetch_assoc()) {
            $faculties[] = $row;
        }
        
        return $faculties;
    }

    /**
     * Get faculties count
     */
    private function getFacultiesCount()
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "SELECT COUNT(*) as total FROM khoa";
        $result = $conn->query($sql);
        $total = $result->fetch_assoc()['total'];
        
        return (int)$total;
    }

    /**
     * Get faculty by ID
     */
    private function getFacultyById($id)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "SELECT 
                    k.DV_MADV,
                    k.DV_TENDV,
                    k.DV_MOTA,
                    COUNT(DISTINCT sv.SV_MASV) as student_count,
                    COUNT(DISTINCT gv.GV_MAGV) as teacher_count,
                    COUNT(DISTINCT dt.DT_MADT) as project_count
                FROM khoa k
                LEFT JOIN lop l ON k.DV_MADV = l.DV_MADV
                LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
                LEFT JOIN giang_vien gv ON k.DV_MADV = gv.DV_MADV
                LEFT JOIN de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV
                WHERE k.DV_MADV = ?
                GROUP BY k.DV_MADV, k.DV_TENDV, k.DV_MOTA";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    /**
     * Create faculty
     */
    private function createFaculty($data)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "INSERT INTO khoa (DV_MADV, DV_TENDV, DV_MOTA) VALUES (?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", 
            $data['code'],
            $data['name'],
            $data['description'] ?? ''
        );
        
        if ($stmt->execute()) {
            return $data['code'];
        }
        
        return false;
    }

    /**
     * Update faculty
     */
    private function updateFaculty($id, $data)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "UPDATE khoa SET DV_TENDV = ?, DV_MOTA = ? WHERE DV_MADV = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss",
            $data['name'] ?? '',
            $data['description'] ?? '',
            $id
        );
        
        return $stmt->execute();
    }

    /**
     * Delete faculty
     */
    private function deleteFaculty($id)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "DELETE FROM khoa WHERE DV_MADV = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        
        return $stmt->execute();
    }

    /**
     * Check if faculty has dependencies
     */
    private function hasDependencies($id)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        // Check if faculty has classes
        $sql = "SELECT COUNT(*) as count FROM lop WHERE DV_MADV = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $classCount = $result->fetch_assoc()['count'];
        
        // Check if faculty has teachers
        $sql = "SELECT COUNT(*) as count FROM giang_vien WHERE DV_MADV = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $teacherCount = $result->fetch_assoc()['count'];
        
        return ($classCount > 0 || $teacherCount > 0);
    }

    /**
     * Get faculty statistics
     */
    private function getFacultyStatistics($id)
    {
        include_once __DIR__ . '/../../../include/connect.php';
        
        $sql = "SELECT 
                    COUNT(DISTINCT sv.SV_MASV) as total_students,
                    COUNT(DISTINCT gv.GV_MAGV) as total_teachers,
                    COUNT(DISTINCT dt.DT_MADT) as total_projects,
                    COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN dt.DT_MADT END) as completed_projects,
                    COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI = 'Đang thực hiện' THEN dt.DT_MADT END) as active_projects,
                    AVG(dt.DT_KINHPHI) as average_budget
                FROM khoa k
                LEFT JOIN lop l ON k.DV_MADV = l.DV_MADV
                LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
                LEFT JOIN giang_vien gv ON k.DV_MADV = gv.DV_MADV
                LEFT JOIN de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV
                WHERE k.DV_MADV = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
}
