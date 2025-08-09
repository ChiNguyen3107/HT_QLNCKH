<?php
require_once 'config/connection.php';

$project_id = $_GET['project_id'] ?? 'DT001';

echo "<h2>Kiểm tra từng bước logic của checkProjectCompleteness</h2>";
echo "<p><strong>Project ID:</strong> " . htmlspecialchars($project_id) . "</p>";

// Step 1: Check proposal file
echo "<h3>1. Kiểm tra file thuyết minh:</h3>";
$proposal_sql = "SELECT DT_FILEBTM FROM de_tai_nghien_cuu WHERE DT_MADT = ? AND DT_FILEBTM IS NOT NULL AND DT_FILEBTM != ''";
$stmt = $conn->prepare($proposal_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$proposal_exists = ($result->num_rows > 0);
echo "<p>SQL: " . $proposal_sql . "</p>";
echo "<p>Rows found: " . $result->num_rows . "</p>";
echo "<p>Proposal exists: " . ($proposal_exists ? "✅ TRUE" : "❌ FALSE") . "</p>";

if ($row = $result->fetch_assoc()) {
    echo "<p>File name: " . htmlspecialchars($row['DT_FILEBTM']) . "</p>";
}

// Step 2: Check contract files
echo "<h3>2. Kiểm tra file hợp đồng:</h3>";
$contract_sql = "SELECT HD_FILEHD, HD_FILE FROM hop_dong WHERE DT_MADT = ?";
$stmt = $conn->prepare($contract_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
echo "<p>SQL: " . $contract_sql . "</p>";
echo "<p>Rows found: " . $result->num_rows . "</p>";

$contract_exists = false;
if ($row = $result->fetch_assoc()) {
    $has_filehd = !empty($row['HD_FILEHD']) && $row['HD_FILEHD'] != '';
    $has_file = !empty($row['HD_FILE']) && $row['HD_FILE'] != '';
    $contract_exists = ($has_filehd || $has_file);
    
    echo "<p>HD_FILEHD: " . htmlspecialchars($row['HD_FILEHD'] ?? 'NULL') . "</p>";
    echo "<p>HD_FILE: " . htmlspecialchars($row['HD_FILE'] ?? 'NULL') . "</p>";
    echo "<p>Has HD_FILEHD: " . ($has_filehd ? "✅ TRUE" : "❌ FALSE") . "</p>";
    echo "<p>Has HD_FILE: " . ($has_file ? "✅ TRUE" : "❌ FALSE") . "</p>";
    echo "<p>Contract exists: " . ($contract_exists ? "✅ TRUE" : "❌ FALSE") . "</p>";
} else {
    echo "<p>❌ No contract record found</p>";
}

// Step 3: Check decision
echo "<h3>3. Kiểm tra file quyết định:</h3>";
$decision_sql = "SELECT qd.QD_FILE, bb.BB_SOBB 
                FROM de_tai_nghien_cuu dt
                INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                WHERE dt.DT_MADT = ?
                AND qd.QD_FILE IS NOT NULL AND qd.QD_FILE != ''
                AND bb.BB_SOBB IS NOT NULL";
$stmt = $conn->prepare($decision_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$decision_exists = ($result->num_rows > 0);

echo "<p>SQL: " . $decision_sql . "</p>";
echo "<p>Rows found: " . $result->num_rows . "</p>";
echo "<p>Decision exists: " . ($decision_exists ? "✅ TRUE" : "❌ FALSE") . "</p>";

if ($row = $result->fetch_assoc()) {
    echo "<p>QD_FILE: " . htmlspecialchars($row['QD_FILE']) . "</p>";
    echo "<p>BB_SOBB: " . htmlspecialchars($row['BB_SOBB']) . "</p>";
}

// Step 4: Check evaluation
echo "<h3>4. Kiểm tra file đánh giá:</h3>";
$evaluation_exists = false;
if ($decision_exists) {
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
    $evaluation_exists = ($row['file_count'] > 0);
    
    echo "<p>SQL: " . $eval_sql . "</p>";
    echo "<p>File count: " . $row['file_count'] . "</p>";
    echo "<p>Evaluation exists: " . ($evaluation_exists ? "✅ TRUE" : "❌ FALSE") . "</p>";
} else {
    echo "<p>❌ Skipped evaluation check because decision doesn't exist</p>";
}

// Summary
echo "<h3>5. Tổng kết:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>File Type</th><th>Status</th></tr>";
echo "<tr><td>Proposal</td><td>" . ($proposal_exists ? "✅ TRUE" : "❌ FALSE") . "</td></tr>";
echo "<tr><td>Contract</td><td>" . ($contract_exists ? "✅ TRUE" : "❌ FALSE") . "</td></tr>";
echo "<tr><td>Decision</td><td>" . ($decision_exists ? "✅ TRUE" : "❌ FALSE") . "</td></tr>";
echo "<tr><td>Evaluation</td><td>" . ($evaluation_exists ? "✅ TRUE" : "❌ FALSE") . "</td></tr>";
echo "</table>";

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    h3 { color: #007bff; }
    p { margin: 5px 0; }
</style>
