<?php
include 'include/connect.php';

echo "=== COMPARISON FOR DT0000001 vs DT0000003 ===\n";

// Basic project data
$sql = "SELECT dt.*, 
        ldt.LDT_TENLOAI,
        lvnc.LVNC_TEN, 
        lvut.LVUT_TEN,
        gv.GV_HOGV, gv.GV_TENGV
FROM de_tai_nghien_cuu dt
LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA  
LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
WHERE dt.DT_MADT IN ('DT0000001', 'DT0000003')
ORDER BY dt.DT_MADT";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\nProject: " . $row['DT_MADT'] . "\n";
        echo "Status: " . ($row['DT_TRANGTHAI'] ?? 'NULL') . "\n";
        echo "QD_SO: " . ($row['QD_SO'] ?? 'NULL') . "\n";
        echo "Proposal File: " . ($row['DT_FILEBTM'] ?? 'NULL') . "\n";
    }
}

// Check contracts
echo "\n=== CONTRACTS ===\n";
$sql = "SELECT * FROM hop_dong WHERE DT_MADT IN ('DT0000001', 'DT0000003')";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Project: " . $row['DT_MADT'] . " - Contract: " . $row['HD_MA'] . " - File: " . ($row['HD_FILEHD'] ?? 'NULL') . "\n";
    }
}

// Check decisions
echo "\n=== DECISIONS ===\n";
$sql = "SELECT qd.*, dt.DT_MADT FROM quyet_dinh_nghiem_thu qd 
        JOIN de_tai_nghien_cuu dt ON qd.QD_SO = dt.QD_SO 
        WHERE dt.DT_MADT IN ('DT0000001', 'DT0000003')";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Project: " . $row['DT_MADT'] . " - Decision: " . $row['QD_SO'] . " - File: " . ($row['QD_FILE'] ?? 'NULL') . "\n";
    }
} else {
    echo "No decisions found\n";
}

// Check bien_ban
echo "\n=== BIEN BAN ===\n";
$sql = "SELECT bb.*, dt.DT_MADT FROM bien_ban bb 
        JOIN de_tai_nghien_cuu dt ON bb.QD_SO = dt.QD_SO 
        WHERE dt.DT_MADT IN ('DT0000001', 'DT0000003')";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Project: " . $row['DT_MADT'] . " - BienBan: " . $row['BB_SOBB'] . " - Score: " . ($row['BB_TONGDIEM'] ?? 'NULL') . "\n";
    }
} else {
    echo "No bien_ban found\n";
}

// Check project participation
echo "\n=== PROJECT PARTICIPATION ===\n";
$sql = "SELECT cttg.*, sv.SV_HOSV, sv.SV_TENSV 
        FROM chi_tiet_tham_gia cttg
        JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
        WHERE cttg.DT_MADT IN ('DT0000001', 'DT0000003')
        ORDER BY cttg.DT_MADT, cttg.CTTG_VAITRO";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Project: " . $row['DT_MADT'] . " - Student: " . $row['SV_MASV'] . " (" . $row['SV_HOSV'] . " " . $row['SV_TENSV'] . ") - Role: " . $row['CTTG_VAITRO'] . "\n";
    }
} else {
    echo "No participation found\n";
}
?>
