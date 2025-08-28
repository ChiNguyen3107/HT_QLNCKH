<?php
// Debug version of process_extension_request.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Process Extension Request</h1>";

// Kiểm tra method
echo "<h2>1. Request Method Check:</h2>";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<p style='color: red;'>❌ Method không phải POST</p>";
    exit;
}
echo "<p style='color: green;'>✅ Method là POST</p>";

// Kiểm tra session
echo "<h2>2. Session Check:</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ Chưa đăng nhập</p>";
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo "<p style='color: red;'>❌ Không phải sinh viên. Role: " . ($_SESSION['role'] ?? 'null') . "</p>";
    exit;
}

echo "<p style='color: green;'>✅ Session hợp lệ - Student ID: " . $_SESSION['user_id'] . "</p>";

// Kiểm tra database connection
echo "<h2>3. Database Connection:</h2>";
try {
    include '../../include/connect.php';
    if ($conn->connect_error) {
        echo "<p style='color: red;'>❌ Lỗi kết nối: " . $conn->connect_error . "</p>";
        exit;
    }
    echo "<p style='color: green;'>✅ Kết nối database thành công</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    exit;
}

// Kiểm tra POST data
echo "<h2>4. POST Data:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Kiểm tra FILES data
echo "<h2>5. FILES Data:</h2>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

// Validate required fields
$student_id = $_SESSION['user_id'];
$project_id = trim($_POST['project_id'] ?? '');
$current_deadline = trim($_POST['current_deadline'] ?? '');
$extension_months = intval($_POST['extension_months'] ?? 0);
$new_deadline = trim($_POST['new_deadline'] ?? '');
$extension_reason = trim($_POST['extension_reason'] ?? '');

echo "<h2>6. Validation:</h2>";
echo "Project ID: " . ($project_id ? "✅ " . $project_id : "❌ Empty") . "<br>";
echo "Current Deadline: " . ($current_deadline ? "✅ " . $current_deadline : "❌ Empty") . "<br>";
echo "Extension Months: " . ($extension_months > 0 ? "✅ " . $extension_months : "❌ " . $extension_months) . "<br>";
echo "New Deadline: " . ($new_deadline ? "✅ " . $new_deadline : "❌ Empty") . "<br>";
echo "Extension Reason: " . (strlen($extension_reason) >= 20 ? "✅ " . strlen($extension_reason) . " chars" : "❌ " . strlen($extension_reason) . " chars (need 20+)") . "<br>";

if (empty($project_id) || empty($current_deadline) || $extension_months <= 0 || empty($extension_reason)) {
    echo "<p style='color: red;'>❌ Validation failed - missing required fields</p>";
    exit;
}

if (strlen($extension_reason) < 20) {
    echo "<p style='color: red;'>❌ Extension reason too short</p>";
    exit;
}

if ($extension_months > 6) {
    echo "<p style='color: red;'>❌ Extension months too many</p>";
    exit;
}

echo "<p style='color: green;'>✅ All validations passed</p>";

// Kiểm tra project permission
echo "<h2>7. Project Permission Check:</h2>";
$check_sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, dt.DT_SO_LAN_GIA_HAN,
                     hd.HD_NGAYKT,
                     COUNT(gh.GH_ID) as SO_YEU_CAU_CHO_DUYET
              FROM de_tai_nghien_cuu dt
              INNER JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
              LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
              LEFT JOIN de_tai_gia_han gh ON dt.DT_MADT = gh.DT_MADT AND gh.GH_TRANGTHAI = 'Chờ duyệt'
              WHERE cttg.SV_MASV = ? AND dt.DT_MADT = ?
              GROUP BY dt.DT_MADT";

$stmt = $conn->prepare($check_sql);
if (!$stmt) {
    echo "<p style='color: red;'>❌ Prepare failed: " . $conn->error . "</p>";
    exit;
}

$stmt->bind_param("ss", $student_id, $project_id);
if (!$stmt->execute()) {
    echo "<p style='color: red;'>❌ Execute failed: " . $stmt->error . "</p>";
    exit;
}

$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    echo "<p style='color: red;'>❌ Project not found or no permission</p>";
    exit;
}

echo "<p style='color: green;'>✅ Project found: " . $project['DT_TENDT'] . "</p>";
echo "Status: " . $project['DT_TRANGTHAI'] . "<br>";
echo "Current extensions: " . $project['DT_SO_LAN_GIA_HAN'] . "<br>";
echo "Pending requests: " . $project['SO_YEU_CAU_CHO_DUYET'] . "<br>";

// Kiểm tra business rules
if (!in_array($project['DT_TRANGTHAI'], ['Đang thực hiện', 'Chờ duyệt'])) {
    echo "<p style='color: red;'>❌ Project status not allowed for extension</p>";
    exit;
}

if ($project['SO_YEU_CAU_CHO_DUYET'] > 0) {
    echo "<p style='color: red;'>❌ Already has pending extension request</p>";
    exit;
}

if ($project['DT_SO_LAN_GIA_HAN'] >= 3) {
    echo "<p style='color: red;'>❌ Maximum extensions reached</p>";
    exit;
}

echo "<p style='color: green;'>✅ All business rules passed</p>";

// Kiểm tra file upload
echo "<h2>8. File Upload Check:</h2>";
$attachment_file = null;
if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
    echo "File uploaded: " . $_FILES['attachment_file']['name'] . "<br>";
    echo "Size: " . $_FILES['attachment_file']['size'] . " bytes<br>";
    echo "Type: " . $_FILES['attachment_file']['type'] . "<br>";
    
    $upload_dir = '../../uploads/extensions/';
    if (!file_exists($upload_dir)) {
        if (mkdir($upload_dir, 0777, true)) {
            echo "✅ Created upload directory<br>";
        } else {
            echo "❌ Failed to create upload directory<br>";
        }
    }
    
    $file_info = pathinfo($_FILES['attachment_file']['name']);
    $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    
    if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
        echo "<p style='color: red;'>❌ File extension not allowed</p>";
        exit;
    }
    
    if ($_FILES['attachment_file']['size'] > 5 * 1024 * 1024) {
        echo "<p style='color: red;'>❌ File too large</p>";
        exit;
    }
    
    $new_filename = $project_id . '_' . $student_id . '_' . time() . '.' . $file_info['extension'];
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($_FILES['attachment_file']['tmp_name'], $upload_path)) {
        $attachment_file = 'uploads/extensions/' . $new_filename;
        echo "<p style='color: green;'>✅ File uploaded successfully: " . $attachment_file . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to move uploaded file</p>";
        exit;
    }
} else {
    echo "<p style='color: orange;'>⚠️ No file uploaded or upload error</p>";
}

echo "<h2>9. Ready for Database Insert</h2>";
echo "<p style='color: green;'>✅ All checks passed! Ready to insert into database.</p>";

// Test the actual insert (commented out for safety)
echo "<p><strong>Database insert would happen here...</strong></p>";

/*
// Begin transaction
$conn->begin_transaction();

try {
    // Insert extension request
    $insert_sql = "INSERT INTO de_tai_gia_han (
                      DT_MADT, SV_MASV, GH_LYDOYEUCAU, GH_NGAYHETHAN_CU, 
                      GH_NGAYHETHAN_MOI, GH_SOTHANGGIAHAN, GH_FILE_DINKEM, GH_NGUOITAO
                   ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sssssis", 
        $project_id, $student_id, $extension_reason, $current_deadline,
        $new_deadline, $extension_months, $attachment_file, $student_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Insert failed: ' . $stmt->error);
    }
    
    $extension_id = $conn->insert_id;
    $stmt->close();
    
    // Insert notification
    $notification_sql = "INSERT INTO thong_bao (
                           TB_NOIDUNG, TB_LOAI, DT_MADT, SV_MASV, TB_NGAYTAO
                         ) VALUES (?, 'Yêu cầu gia hạn', ?, ?, NOW())";
    
    $notification_content = "Sinh viên {$student_id} yêu cầu gia hạn {$extension_months} tháng cho đề tài \"{$project['DT_TENDT']}\"";
    
    $stmt = $conn->prepare($notification_sql);
    $stmt->bind_param("sss", $notification_content, $project_id, $student_id);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    
    echo "<p style='color: green;'>✅ Successfully inserted extension request with ID: " . $extension_id . "</p>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
*/

echo "<p><a href='test_extension_request.php'>← Back to Test Form</a></p>";
?>

