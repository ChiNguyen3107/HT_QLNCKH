<?php
include '../include/connect.php';

echo "<h2>Debug Update Report Info</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>Council Members JSON:</h3>";
    $council_members = $_POST['council_members_json'] ?? '';
    echo "Raw data: " . htmlspecialchars($council_members) . "<br>";
    
    if (!empty($council_members)) {
        $members_data = json_decode($council_members, true);
        echo "<h4>Parsed data:</h4>";
        echo "<pre>";
        print_r($members_data);
        echo "</pre>";
        
        if ($members_data && is_array($members_data)) {
            echo "<h4>Members to insert:</h4>";
            foreach ($members_data as $member) {
                $gv_magv = $member['id'] ?? '';
                $vaitro = $member['role'] ?? '';
                $hoten = $member['name'] ?? '';
                $tc_matc = 'TC001';
                
                echo "GV_MAGV: $gv_magv, TV_VAITRO: $vaitro, TV_HOTEN: $hoten, TC_MATC: $tc_matc<br>";
            }
        }
    }
} else {
    echo "<p>No POST data received. Use this for testing the form submission.</p>";
}

echo "<h3>Test Query Structure:</h3>";
echo "INSERT INTO thanh_vien_hoi_dong (QD_SO, GV_MAGV, TC_MATC, TV_VAITRO, TV_DIEM, TV_DANHGIA) VALUES (?, ?, ?, ?, 0, 'Chưa đánh giá')";
?>
