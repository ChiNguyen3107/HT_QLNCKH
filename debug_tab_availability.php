<?php
include 'include/connect.php';

// Get project ID from URL
$project_id = $_GET['id'] ?? 'DT0000003';

echo "=== DEBUGGING TAB ISSUE FOR $project_id ===\n";

// Check if project exists and get its details
$sql = "SELECT * FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $project = $result->fetch_assoc();
    echo "Project found: " . $project['DT_TENDT'] . "\n";
    echo "Status: " . $project['DT_TRANGTHAI'] . "\n";
    echo "QD_SO: " . ($project['QD_SO'] ?? 'NULL') . "\n";
} else {
    echo "Project not found!\n";
    exit;
}

// Check what tabs should be available
echo "\n=== TAB AVAILABILITY CHECK ===\n";

// 1. Proposal tab (always available)
$has_proposal = !empty($project['DT_FILEBTM']);
echo "Proposal tab: " . ($has_proposal ? "Available" : "Not available") . "\n";

// 2. Contract tab
$contract_sql = "SELECT HD_FILEHD FROM hop_dong WHERE DT_MADT = ? AND HD_FILEHD IS NOT NULL AND HD_FILEHD != ''";
$stmt = $conn->prepare($contract_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$has_contract = ($result->num_rows > 0);
echo "Contract tab: " . ($has_contract ? "Available" : "Not available") . "\n";

// 3. Decision tab
$decision_sql = "SELECT qd.QD_FILE FROM quyet_dinh_nghiem_thu qd 
                JOIN de_tai_nghien_cuu dt ON qd.QD_SO = dt.QD_SO 
                WHERE dt.DT_MADT = ? AND qd.QD_FILE IS NOT NULL AND qd.QD_FILE != ''";
$stmt = $conn->prepare($decision_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$has_decision = ($result->num_rows > 0);
echo "Decision tab: " . ($has_decision ? "Available" : "Not available") . "\n";

// 4. Evaluation tab
$eval_sql = "SELECT COUNT(*) as file_count FROM file_danh_gia fg
            INNER JOIN bien_ban bb ON fg.BB_SOBB = bb.BB_SOBB
            INNER JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO
            INNER JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
            WHERE dt.DT_MADT = ?";
$stmt = $conn->prepare($eval_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$has_evaluation = ($row['file_count'] > 0);
echo "Evaluation tab: " . ($has_evaluation ? "Available" : "Not available") . " (Files: " . $row['file_count'] . ")\n";

// 5. Budget tab - check if contract has budget info
$budget_sql = "SELECT HD_TONGKINHPHI FROM hop_dong WHERE DT_MADT = ? AND HD_TONGKINHPHI > 0";
$stmt = $conn->prepare($budget_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$has_budget = ($result->num_rows > 0);
echo "Budget tab: " . ($has_budget ? "Available" : "Not available") . "\n";

// 6. Progress tab - check if there are reports
$progress_sql = "SELECT COUNT(*) as report_count FROM bao_cao bc
                INNER JOIN chi_tiet_tham_gia cttg ON bc.DT_MADT = cttg.DT_MADT
                WHERE cttg.DT_MADT = ?";
$stmt = $conn->prepare($progress_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$has_progress = ($row['report_count'] > 0);
echo "Progress tab: " . ($has_progress ? "Available" : "Not available") . " (Reports: " . $row['report_count'] . ")\n";

echo "\n=== EXPECTED TAB COUNT ===\n";
$expected_tabs = 0;
if ($has_proposal) $expected_tabs++;
if ($has_contract) $expected_tabs++;
if ($has_decision) $expected_tabs++;
if ($has_evaluation) $expected_tabs++;
if ($has_budget) $expected_tabs++;
if ($has_progress) $expected_tabs++;

echo "Expected number of tabs: $expected_tabs\n";

// Check if user has permission to view this project
$user_id = $_SESSION['user_id'] ?? 'B2110051'; // Default for testing
echo "\n=== USER PERMISSION CHECK ===\n";
echo "User ID: $user_id\n";

$permission_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE SV_MASV = ? AND DT_MADT = ?";
$stmt = $conn->prepare($permission_sql);
$stmt->bind_param("ss", $user_id, $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $role = $result->fetch_assoc()['CTTG_VAITRO'];
    echo "User role in project: $role\n";
    echo "Has permission: Yes\n";
} else {
    echo "User role in project: None\n";
    echo "Has permission: No\n";
}
?>
