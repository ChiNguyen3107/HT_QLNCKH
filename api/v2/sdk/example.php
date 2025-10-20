<?php
/**
 * NCKH API Client SDK - Usage Examples
 */

require_once 'NckhApiClient.php';

// Initialize API client
$api = new NckhApiClient('http://localhost/api/v2');

try {
    // ==================== AUTHENTICATION EXAMPLES ====================
    
    echo "=== AUTHENTICATION EXAMPLES ===\n";
    
    // Login
    $loginResult = $api->login('admin', 'password123');
    if ($loginResult['success']) {
        echo "Login successful!\n";
        echo "User: " . $loginResult['data']['user']['name'] . "\n";
        echo "Role: " . $loginResult['data']['user']['role'] . "\n";
    }
    
    // Get current user info
    $userInfo = $api->getCurrentUser();
    echo "Current user: " . json_encode($userInfo['data'], JSON_PRETTY_PRINT) . "\n";
    
    // ==================== STUDENT EXAMPLES ====================
    
    echo "\n=== STUDENT EXAMPLES ===\n";
    
    // Get all students with pagination
    $students = $api->getStudents(['page' => 1, 'limit' => 10]);
    echo "Total students: " . $students['meta']['pagination']['total'] . "\n";
    echo "Students on page 1:\n";
    foreach ($students['data'] as $student) {
        echo "- " . $student['full_name'] . " (" . $student['student_code'] . ")\n";
    }
    
    // Get students by department
    $cnStudents = $api->getStudents(['department' => 'CNTT', 'limit' => 5]);
    echo "\nCNTT students:\n";
    foreach ($cnStudents['data'] as $student) {
        echo "- " . $student['full_name'] . " - " . $student['class_name'] . "\n";
    }
    
    // Get student by ID
    if (!empty($students['data'])) {
        $firstStudent = $students['data'][0];
        $studentDetail = $api->getStudent($firstStudent['id']);
        echo "\nStudent detail:\n";
        echo json_encode($studentDetail['data'], JSON_PRETTY_PRINT) . "\n";
    }
    
    // Create new student
    $newStudent = $api->createStudent([
        'student_code' => 'SV999',
        'first_name' => 'Nguyễn',
        'last_name' => 'Văn Test',
        'email' => 'test@example.com',
        'phone' => '0123456789',
        'class_code' => 'CNTT01'
    ]);
    echo "\nCreated student: " . $newStudent['message'] . "\n";
    
    // ==================== PROJECT EXAMPLES ====================
    
    echo "\n=== PROJECT EXAMPLES ===\n";
    
    // Get all projects
    $projects = $api->getProjects(['page' => 1, 'limit' => 5]);
    echo "Total projects: " . $projects['meta']['pagination']['total'] . "\n";
    echo "Projects:\n";
    foreach ($projects['data'] as $project) {
        echo "- " . $project['title'] . " (" . $project['status'] . ")\n";
    }
    
    // Get projects by status
    $activeProjects = $api->getProjects(['status' => 'Đang thực hiện', 'limit' => 3]);
    echo "\nActive projects:\n";
    foreach ($activeProjects['data'] as $project) {
        echo "- " . $project['title'] . " - Supervisor: " . $project['supervisor']['name'] . "\n";
    }
    
    // Create new project
    $newProject = $api->createProject([
        'title' => 'Hệ thống quản lý API',
        'description' => 'Xây dựng hệ thống quản lý API RESTful',
        'supervisor_id' => 'GV001',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'budget' => 10000000,
        'status' => 'Đang thực hiện'
    ]);
    echo "\nCreated project: " . $newProject['message'] . "\n";
    
    // Add member to project
    if (!empty($students['data'])) {
        $projectId = 'DT2024001'; // Assuming project ID
        $studentId = $students['data'][0]['id'];
        $addMemberResult = $api->addProjectMember($projectId, $studentId, 'leader');
        echo "Add member result: " . $addMemberResult['message'] . "\n";
    }
    
    // ==================== FACULTY EXAMPLES ====================
    
    echo "\n=== FACULTY EXAMPLES ===\n";
    
    // Get all faculties
    $faculties = $api->getFaculties();
    echo "Faculties:\n";
    foreach ($faculties['data'] as $faculty) {
        echo "- " . $faculty['name'] . " (" . $faculty['code'] . ")\n";
        echo "  Students: " . $faculty['student_count'] . "\n";
        echo "  Teachers: " . $faculty['teacher_count'] . "\n";
        echo "  Projects: " . $faculty['project_count'] . "\n\n";
    }
    
    // Get faculty statistics
    if (!empty($faculties['data'])) {
        $firstFaculty = $faculties['data'][0];
        $stats = $api->getFacultyStatistics($firstFaculty['id']);
        echo "Statistics for " . $firstFaculty['name'] . ":\n";
        echo json_encode($stats['data'], JSON_PRETTY_PRINT) . "\n";
    }
    
    // ==================== HEALTH CHECK ====================
    
    echo "\n=== HEALTH CHECK ===\n";
    $health = $api->healthCheck();
    echo "API Status: " . $health['message'] . "\n";
    echo "Version: " . $health['version'] . "\n";
    
    // ==================== LOGOUT ====================
    
    echo "\n=== LOGOUT ===\n";
    $logoutResult = $api->logout();
    echo "Logout: " . $logoutResult['message'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
