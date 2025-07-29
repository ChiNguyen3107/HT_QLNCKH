<?php
include 'include/connect.php';

echo "Thêm giảng viên mẫu vào các khoa khác nhau...\n";

$sampleTeachers = [
    ['GV000003', 'KH001', 'Trần', 'Văn Bình', 'tranvanbinh@example.com', 'Lý luận chính trị, Triết học Mác-Lenin'],
    ['GV000004', 'KH012', 'Nguyễn', 'Thị Hoa', 'nguyenthihoa@example.com', 'Kinh tế học, Quản trị kinh doanh'],
    ['GV000005', 'KH009', 'Lê', 'Minh Tuấn', 'leminhtuan@example.com', 'Sư phạm toán học, Phương pháp giảng dạy'],
    ['GV000006', 'KH007', 'Phạm', 'Thị Lan', 'phamthilan@example.com', 'Ngôn ngữ Anh, Văn học nước ngoài'],
    ['GV000007', 'KH013', 'Võ', 'Văn Nam', 'vovannam@example.com', 'Nông học, Chăn nuôi thú y'],
];

foreach ($sampleTeachers as $teacher) {
    $sql = "INSERT IGNORE INTO giang_vien (GV_MAGV, DV_MADV, GV_HOGV, GV_TENGV, GV_EMAIL, GV_CHUYENMON, GV_GIOITINH, GV_SDT, GV_MATKHAU) 
            VALUES (?, ?, ?, ?, ?, ?, 1, '0900000000', ?)";
    
    $stmt = $conn->prepare($sql);
    $password = password_hash('123456', PASSWORD_DEFAULT);
    $stmt->bind_param("sssssss", $teacher[0], $teacher[1], $teacher[2], $teacher[3], $teacher[4], $teacher[5], $password);
    
    if ($stmt->execute()) {
        echo "✓ Thêm thành công: " . $teacher[2] . " " . $teacher[3] . "\n";
    } else {
        echo "✗ Lỗi thêm: " . $teacher[2] . " " . $teacher[3] . " - " . $stmt->error . "\n";
    }
}

echo "\nKiểm tra lại phân bố giảng viên:\n";
$sql = "SELECT k.DV_TENDV, COUNT(*) as count
        FROM giang_vien gv 
        LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
        GROUP BY k.DV_TENDV
        ORDER BY count DESC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    echo "- " . ($row['DV_TENDV'] ?? 'Không xác định') . ": " . $row['count'] . " giảng viên\n";
}

$conn->close();
?>
