<?php
/**
 * Swagger/OpenAPI Documentation for NCKH API v2
 */

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="NCKH Management System API",
 *     version="2.0.0",
 *     description="RESTful API cho hệ thống quản lý nghiên cứu khoa học",
 *     @OA\Contact(
 *         email="admin@nckh.ctu.edu.vn",
 *         name="NCKH Admin"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost/api/v2",
 *     description="Development server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for authentication"
 * )
 * 
 * @OA\Tag(
 *     name="Students",
 *     description="API endpoints for student management"
 * )
 * 
 * @OA\Tag(
 *     name="Projects",
 *     description="API endpoints for project management"
 * )
 * 
 * @OA\Tag(
 *     name="Faculties",
 *     description="API endpoints for faculty management"
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/login",
 *     tags={"Authentication"},
 *     summary="Đăng nhập",
 *     description="Đăng nhập vào hệ thống và nhận JWT token",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"username","password"},
 *             @OA\Property(property="username", type="string", example="admin"),
 *             @OA\Property(property="password", type="string", example="password123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Đăng nhập thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Đăng nhập thành công"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="user", type="object",
 *                     @OA\Property(property="id", type="string", example="admin"),
 *                     @OA\Property(property="username", type="string", example="admin"),
 *                     @OA\Property(property="role", type="string", example="admin"),
 *                     @OA\Property(property="name", type="string", example="Administrator")
 *                 ),
 *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
 *                 @OA\Property(property="token_type", type="string", example="Bearer"),
 *                 @OA\Property(property="expires_in", type="integer", example=86400)
 *             ),
 *             @OA\Property(property="timestamp", type="string", example="2024-01-01 12:00:00")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Đăng nhập thất bại",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="status", type="integer", example=401),
 *             @OA\Property(property="message", type="string", example="Tên đăng nhập hoặc mật khẩu không đúng"),
 *             @OA\Property(property="timestamp", type="string", example="2024-01-01 12:00:00")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/students",
 *     tags={"Students"},
 *     summary="Lấy danh sách sinh viên",
 *     description="Lấy danh sách sinh viên với phân trang và lọc",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="department",
 *         in="query",
 *         description="Mã khoa",
 *         required=false,
 *         @OA\Schema(type="string", example="CNTT")
 *     ),
 *     @OA\Parameter(
 *         name="school_year",
 *         in="query",
 *         description="Năm học",
 *         required=false,
 *         @OA\Schema(type="string", example="2024")
 *     ),
 *     @OA\Parameter(
 *         name="class",
 *         in="query",
 *         description="Mã lớp",
 *         required=false,
 *         @OA\Schema(type="string", example="CNTT01")
 *     ),
 *     @OA\Parameter(
 *         name="research_status",
 *         in="query",
 *         description="Trạng thái nghiên cứu",
 *         required=false,
 *         @OA\Schema(type="string", enum={"active", "completed", "none"}, example="active")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Trang hiện tại",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         description="Số lượng mỗi trang",
 *         required=false,
 *         @OA\Schema(type="integer", example=20)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Lấy danh sách sinh viên thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Thành công"),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="string", example="SV001"),
 *                     @OA\Property(property="student_code", type="string", example="SV001"),
 *                     @OA\Property(property="full_name", type="string", example="Nguyễn Văn A"),
 *                     @OA\Property(property="class_name", type="string", example="CNTT01"),
 *                     @OA\Property(property="department_name", type="string", example="Công nghệ thông tin"),
 *                     @OA\Property(property="project_count", type="integer", example=2),
 *                     @OA\Property(property="completed_project_count", type="integer", example=1),
 *                     @OA\Property(property="research_status", type="string", example="active")
 *                 )
 *             ),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="pagination", type="object",
 *                     @OA\Property(property="current_page", type="integer", example=1),
 *                     @OA\Property(property="per_page", type="integer", example=20),
 *                     @OA\Property(property="total", type="integer", example=100),
 *                     @OA\Property(property="total_pages", type="integer", example=5),
 *                     @OA\Property(property="has_next", type="boolean", example=true),
 *                     @OA\Property(property="has_prev", type="boolean", example=false)
 *                 )
 *             ),
 *             @OA\Property(property="timestamp", type="string", example="2024-01-01 12:00:00")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/projects",
 *     tags={"Projects"},
 *     summary="Lấy danh sách đề tài",
 *     description="Lấy danh sách đề tài nghiên cứu với phân trang và lọc",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Trạng thái đề tài",
 *         required=false,
 *         @OA\Schema(type="string", enum={"Đang thực hiện", "Đã hoàn thành", "Tạm dừng", "Hủy bỏ"}, example="Đang thực hiện")
 *     ),
 *     @OA\Parameter(
 *         name="supervisor_id",
 *         in="query",
 *         description="Mã giảng viên hướng dẫn",
 *         required=false,
 *         @OA\Schema(type="string", example="GV001")
 *     ),
 *     @OA\Parameter(
 *         name="department",
 *         in="query",
 *         description="Mã khoa",
 *         required=false,
 *         @OA\Schema(type="string", example="CNTT")
 *     ),
 *     @OA\Parameter(
 *         name="year",
 *         in="query",
 *         description="Năm bắt đầu",
 *         required=false,
 *         @OA\Schema(type="integer", example=2024)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Trang hiện tại",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         description="Số lượng mỗi trang",
 *         required=false,
 *         @OA\Schema(type="integer", example=20)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Lấy danh sách đề tài thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Thành công"),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="string", example="DT2024001"),
 *                     @OA\Property(property="title", type="string", example="Hệ thống quản lý sinh viên"),
 *                     @OA\Property(property="description", type="string", example="Xây dựng hệ thống quản lý sinh viên sử dụng PHP"),
 *                     @OA\Property(property="status", type="string", example="Đang thực hiện"),
 *                     @OA\Property(property="start_date", type="string", example="2024-01-01"),
 *                     @OA\Property(property="end_date", type="string", example="2024-12-31"),
 *                     @OA\Property(property="budget", type="number", example=5000000),
 *                     @OA\Property(property="supervisor", type="object",
 *                         @OA\Property(property="id", type="string", example="GV001"),
 *                         @OA\Property(property="name", type="string", example="TS. Nguyễn Văn B"),
 *                         @OA\Property(property="email", type="string", example="nguyenvanb@ctu.edu.vn")
 *                     )
 *                 )
 *             ),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="pagination", type="object",
 *                     @OA\Property(property="current_page", type="integer", example=1),
 *                     @OA\Property(property="per_page", type="integer", example=20),
 *                     @OA\Property(property="total", type="integer", example=50),
 *                     @OA\Property(property="total_pages", type="integer", example=3),
 *                     @OA\Property(property="has_next", type="boolean", example=true),
 *                     @OA\Property(property="has_prev", type="boolean", example=false)
 *                 )
 *             ),
 *             @OA\Property(property="timestamp", type="string", example="2024-01-01 12:00:00")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/faculties",
 *     tags={"Faculties"},
 *     summary="Lấy danh sách khoa",
 *     description="Lấy danh sách khoa/đơn vị với thống kê",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Trang hiện tại",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         description="Số lượng mỗi trang",
 *         required=false,
 *         @OA\Schema(type="integer", example=20)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Lấy danh sách khoa thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Thành công"),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="string", example="CNTT"),
 *                     @OA\Property(property="code", type="string", example="CNTT"),
 *                     @OA\Property(property="name", type="string", example="Công nghệ thông tin"),
 *                     @OA\Property(property="description", type="string", example="Khoa Công nghệ thông tin"),
 *                     @OA\Property(property="student_count", type="integer", example=500),
 *                     @OA\Property(property="teacher_count", type="integer", example=50),
 *                     @OA\Property(property="project_count", type="integer", example=100)
 *                 )
 *             ),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="pagination", type="object",
 *                     @OA\Property(property="current_page", type="integer", example=1),
 *                     @OA\Property(property="per_page", type="integer", example=20),
 *                     @OA\Property(property="total", type="integer", example=10),
 *                     @OA\Property(property="total_pages", type="integer", example=1),
 *                     @OA\Property(property="has_next", type="boolean", example=false),
 *                     @OA\Property(property="has_prev", type="boolean", example=false)
 *                 )
 *             ),
 *             @OA\Property(property="timestamp", type="string", example="2024-01-01 12:00:00")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/health",
 *     summary="Health Check",
 *     description="Kiểm tra trạng thái API",
 *     @OA\Response(
 *         response=200,
 *         description="API hoạt động bình thường",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="API hoạt động bình thường"),
 *             @OA\Property(property="timestamp", type="string", example="2024-01-01 12:00:00"),
 *             @OA\Property(property="version", type="string", example="2.0.0")
 *         )
 *     )
 * )
 */
