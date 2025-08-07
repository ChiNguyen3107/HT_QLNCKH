<?php
// Test upload file với debug chi tiết
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

// Giả lập session
$_SESSION['user_id'] = 'SV001';
$_SESSION['role'] = 'student';

echo "<h2>Upload Test với Debug Chi Tiết</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>FILES Data:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    try {
        require_once 'include/connect.php';
        
        if (!$conn) {
            throw new Exception('Database connection failed: ' . mysqli_connect_error());
        }
        
        echo "<p style='color: green;'>✓ Database connected</p>";
        
        // Validate POST data
        $project_id = $_POST['project_id'] ?? '';
        $member_id = $_POST['member_id'] ?? '';
        $file_name = $_POST['evaluation_file_name'] ?? '';
        $file_description = $_POST['file_description'] ?? '';
        
        echo "<h4>Validated Data:</h4>";
        echo "<p>Project ID: '$project_id'</p>";
        echo "<p>Member ID: '$member_id'</p>";
        echo "<p>File Name: '$file_name'</p>";
        echo "<p>Description: '$file_description'</p>";
        
        if (empty($project_id) || empty($member_id) || empty($file_name)) {
            throw new Exception('Missing required fields');
        }
        
        // Check file upload
        if (!isset($_FILES['evaluation_file']) || $_FILES['evaluation_file']['error'] !== UPLOAD_ERR_OK) {
            $error = $_FILES['evaluation_file']['error'] ?? 'No file uploaded';
            throw new Exception('File upload error: ' . $error);
        }
        
        $file = $_FILES['evaluation_file'];
        echo "<h4>File Info:</h4>";
        echo "<p>Original name: " . $file['name'] . "</p>";
        echo "<p>Size: " . $file['size'] . " bytes</p>";
        echo "<p>Type: " . $file['type'] . "</p>";
        echo "<p>Tmp name: " . $file['tmp_name'] . "</p>";
        
        // Check project access via chi_tiet_tham_gia table
        $stmt = $conn->prepare("
            SELECT dt.*, cttg.SV_MASV 
            FROM de_tai_nghien_cuu dt 
            JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT 
            WHERE dt.DT_MADT = ? AND cttg.SV_MASV = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        
        $stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
        $stmt->execute();
        $project_result = $stmt->get_result();
        
        if ($project_result->num_rows === 0) {
            echo "<p style='color: red;'>✗ No project access for user {$_SESSION['user_id']} to project $project_id</p>";
            
            // Show available projects for debug
            $debug_stmt = $conn->prepare("
                SELECT dt.DT_MADT, dt.DT_TENDT, cttg.SV_MASV 
                FROM de_tai_nghien_cuu dt 
                JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT 
                WHERE cttg.SV_MASV = ?
            ");
            
            if ($debug_stmt) {
                $debug_stmt->bind_param("s", $_SESSION['user_id']);
                $debug_stmt->execute();
                $debug_result = $debug_stmt->get_result();
                
                echo "<h4>Available projects for user {$_SESSION['user_id']}:</h4>";
                if ($debug_result->num_rows > 0) {
                    while ($row = $debug_result->fetch_assoc()) {
                        echo "<p>- " . $row['DT_MADT'] . ": " . $row['DT_TENDT'] . "</p>";
                    }
                } else {
                    echo "<p>No projects found for this user</p>";
                }
            }
            
            throw new Exception('No project access');
        }
        
        echo "<p style='color: green;'>✓ Project access verified</p>";
        
        // Get BB_SOBB
        $bb_result = $conn->query("SELECT BB_SOBB FROM bien_ban ORDER BY BB_SOBB LIMIT 1");
        if (!$bb_result || $bb_result->num_rows === 0) {
            $bb_sobb = 'BBEVAL' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $create_bb = $conn->prepare("INSERT INTO bien_ban (BB_SOBB, BB_NGAYNGHIEMTHU, BB_XEPLOAI) VALUES (?, NOW(), 'Đánh giá')");
            if (!$create_bb) {
                throw new Exception('Prepare create bien_ban failed: ' . $conn->error);
            }
            $create_bb->bind_param("s", $bb_sobb);
            if (!$create_bb->execute()) {
                throw new Exception('Cannot create bien_ban: ' . $create_bb->error);
            }
            echo "<p style='color: blue;'>Created new BB_SOBB: $bb_sobb</p>";
        } else {
            $bb_row = $bb_result->fetch_assoc();
            $bb_sobb = $bb_row['BB_SOBB'];
            echo "<p style='color: green;'>Using existing BB_SOBB: $bb_sobb</p>";
        }
        
        // Check GV_MAGV
        $gv_magv = null;
        $gv_check = $conn->prepare("SELECT GV_MAGV FROM giang_vien WHERE GV_MAGV = ?");
        if (!$gv_check) {
            throw new Exception('Prepare GV check failed: ' . $conn->error);
        }
        $gv_check->bind_param("s", $member_id);
        $gv_check->execute();
        $gv_result = $gv_check->get_result();
        
        if ($gv_result->num_rows > 0) {
            $gv_magv = $member_id;
            echo "<p style='color: green;'>Valid GV_MAGV: $gv_magv</p>";
        } else {
            echo "<p style='color: orange;'>GV_MAGV not found, setting to NULL</p>";
            
            // Show available GV_MAGV for debug
            $gv_list = $conn->query("SELECT GV_MAGV, GV_TENGV FROM giang_vien LIMIT 5");
            echo "<h4>Available GV_MAGV:</h4>";
            while ($gv_row = $gv_list->fetch_assoc()) {
                echo "<p>- " . $gv_row['GV_MAGV'] . ": " . $gv_row['GV_TENGV'] . "</p>";
            }
        }
        
        // Create upload directory
        $upload_dir = 'uploads/member_evaluations/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
            echo "<p style='color: blue;'>Created upload directory: $upload_dir</p>";
        } else {
            echo "<p style='color: green;'>Upload directory exists: $upload_dir</p>";
        }
        
        // Generate file info
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_filename = 'eval_' . $member_id . '_' . $project_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;
        
        echo "<p>Generated filename: $unique_filename</p>";
        echo "<p>Upload path: $upload_path</p>";
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Cannot move uploaded file to: ' . $upload_path);
        }
        
        echo "<p style='color: green;'>✓ File moved successfully</p>";
        
        // Insert to database
        $file_id = 'FDG' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $file_type = 'member_evaluation';
        
        echo "<h4>Database Insert:</h4>";
        echo "<p>File ID: $file_id</p>";
        echo "<p>BB_SOBB: $bb_sobb</p>";
        echo "<p>GV_MAGV: " . ($gv_magv ?? 'NULL') . "</p>";
        echo "<p>File Type: $file_type</p>";
        echo "<p>Original Name: " . $file['name'] . "</p>";
        echo "<p>Stored Name: $unique_filename</p>";
        echo "<p>Size: " . $file['size'] . "</p>";
        echo "<p>Description: $file_description</p>";
        
        if ($gv_magv !== null) {
            $stmt = $conn->prepare("
                INSERT INTO file_dinh_kem (
                    FDG_MA, BB_SOBB, GV_MAGV, FDG_LOAI, 
                    FDG_TENFILE, FDG_FILE, FDG_NGAYTAO, FDG_KICHTHUC, FDG_MOTA
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
            ");
            if (!$stmt) {
                throw new Exception('Prepare insert with GV_MAGV failed: ' . $conn->error);
            }
            $stmt->bind_param("ssssssis", $file_id, $bb_sobb, $gv_magv, $file_type, $file['name'], $unique_filename, $file['size'], $file_description);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO file_dinh_kem (
                    FDG_MA, BB_SOBB, GV_MAGV, FDG_LOAI, 
                    FDG_TENFILE, FDG_FILE, FDG_NGAYTAO, FDG_KICHTHUC, FDG_MOTA
                ) VALUES (?, ?, NULL, ?, ?, ?, NOW(), ?, ?)
            ");
            if (!$stmt) {
                throw new Exception('Prepare insert without GV_MAGV failed: ' . $conn->error);
            }
            $stmt->bind_param("sssssis", $file_id, $bb_sobb, $file_type, $file['name'], $unique_filename, $file['size'], $file_description);
        }
        
        if ($stmt->execute()) {
            echo "<p style='color: green; font-weight: bold;'>✓ SUCCESS! File uploaded and saved to database</p>";
            echo "<p>Insert ID: " . $conn->insert_id . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Database insert failed: " . $stmt->error . "</p>";
            // Delete uploaded file if database insert failed
            unlink($upload_path);
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red; font-weight: bold;'>ERROR: " . $e->getMessage() . "</p>";
        echo "<p>Line: " . $e->getLine() . "</p>";
        echo "<p>File: " . $e->getFile() . "</p>";
    }
} else {
    // Show form
    echo "<form method='POST' enctype='multipart/form-data'>";
    echo "<table>";
    echo "<tr><td>Project ID:</td><td><input type='text' name='project_id' value='DT0000001' required></td></tr>";
    echo "<tr><td>Member ID:</td><td><input type='text' name='member_id' value='GV000002' required></td></tr>";
    echo "<tr><td>File Name:</td><td><input type='text' name='evaluation_file_name' value='Test Evaluation' required></td></tr>";
    echo "<tr><td>Description:</td><td><textarea name='file_description'>Test upload file</textarea></td></tr>";
    echo "<tr><td>File:</td><td><input type='file' name='evaluation_file' required></td></tr>";
    echo "<tr><td colspan='2'><button type='submit'>Upload Test</button></td></tr>";
    echo "</table>";
    echo "</form>";
}
?>
