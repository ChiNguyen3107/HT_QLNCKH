<?php
// File debug để kiểm tra dữ liệu POST được gửi
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG POST DATA ===\n\n";

echo "1. SERVER INFO:\n";
echo "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "\n";
echo "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "\n";
echo "Content Length: " . ($_SERVER['CONTENT_LENGTH'] ?? 'NOT SET') . "\n\n";

echo "2. POST DATA:\n";
if (empty($_POST)) {
    echo "No POST data received\n";
} else {
    foreach ($_POST as $key => $value) {
        echo "- $key: " . (is_array($value) ? print_r($value, true) : $value) . "\n";
    }
}

echo "\n3. FILES DATA:\n";
if (empty($_FILES)) {
    echo "No FILES data received\n";
} else {
    foreach ($_FILES as $key => $file) {
        echo "- $key:\n";
        echo "  - name: " . ($file['name'] ?? 'NOT SET') . "\n";
        echo "  - type: " . ($file['type'] ?? 'NOT SET') . "\n";
        echo "  - size: " . ($file['size'] ?? 'NOT SET') . "\n";
        echo "  - error: " . ($file['error'] ?? 'NOT SET') . "\n";
        echo "  - tmp_name: " . ($file['tmp_name'] ?? 'NOT SET') . "\n";
    }
}

echo "\n4. SESSION DATA:\n";
session_start();
if (empty($_SESSION)) {
    echo "No SESSION data\n";
} else {
    foreach ($_SESSION as $key => $value) {
        if ($key === 'user_id' || $key === 'role' || $key === 'error_message' || $key === 'success_message') {
            echo "- $key: " . (is_array($value) ? print_r($value, true) : $value) . "\n";
        }
    }
}

echo "\n5. VALIDATION TEST:\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = trim($_POST['project_id'] ?? '');
    $decision_number = trim($_POST['decision_number'] ?? '');
    $decision_date = trim($_POST['decision_date'] ?? '');
    $update_reason = trim($_POST['update_reason'] ?? '');
    
    echo "After trim:\n";
    echo "- project_id: '$project_id'\n";
    echo "- decision_number: '$decision_number'\n";
    echo "- decision_date: '$decision_date'\n";
    echo "- update_reason: '$update_reason'\n";
    
    // Check validation conditions
    echo "\nValidation checks:\n";
    echo "- project_id empty: " . (empty($project_id) ? 'YES' : 'NO') . "\n";
    echo "- decision_number empty: " . (empty($decision_number) ? 'YES' : 'NO') . "\n";
    echo "- decision_date empty: " . (empty($decision_date) ? 'YES' : 'NO') . "\n";
    echo "- update_reason empty: " . (empty($update_reason) ? 'YES' : 'NO') . "\n";
    
    // Check date validation
    if (!empty($decision_date)) {
        $date_valid = strtotime($decision_date);
        echo "- decision_date valid: " . ($date_valid ? 'YES (' . date('Y-m-d', $date_valid) . ')' : 'NO') . "\n";
    }
    
    // File check
    if (isset($_FILES['decision_file']) && $_FILES['decision_file']['error'] === UPLOAD_ERR_OK) {
        echo "- File uploaded successfully\n";
    } elseif (empty($_POST['decision_id'])) {
        echo "- No file uploaded and no decision_id (new decision)\n";
    } else {
        echo "- No file uploaded but decision_id exists (update decision)\n";
    }
} else {
    echo "Not a POST request\n";
}

echo "\n=== END DEBUG ===\n";
?>
