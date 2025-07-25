<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\user_manage\update_user.php
// Tắt hiển thị lỗi để tránh ảnh hưởng đến phản hồi JSON
error_reporting(0);
ini_set('display_errors', 0);

include '../../../include/session.php';
checkAdminRole();
include '../../../include/connect.php';

// Thiết lập header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // In debug để kiểm tra
    error_log("POST data: " . print_r($_POST, true));
    
    // Lấy thông tin cơ bản từ form
    $userType = $_POST['userType'] ?? '';
    $id = $_POST['editId'] ?? '';
    
    // Thêm thông báo debug cụ thể
    if (empty($userType)) {
        echo json_encode(['success' => false, 'message' => 'Loại người dùng không được cung cấp']);
        exit;
    }
    
    if ($userType !== 'student' && $userType !== 'teacher') {
        echo json_encode([
            'success' => false, 
            'message' => 'Loại người dùng không hợp lệ: "' . htmlspecialchars($userType) . '"'
        ]);
        exit;
    }
    
    $firstName = $_POST['editFirstName'] ?? '';
    $lastName = $_POST['editLastName'] ?? '';
    $email = $_POST['editEmail'] ?? '';
    $phone = $_POST['editPhone'] ?? '';
    $address = $_POST['editAddress'] ?? '';
    $birthDate = empty($_POST['editBirthDate']) ? null : $_POST['editBirthDate'];
    $gender = $_POST['editGender'] ?? 'Nam'; // Giá trị là "Nam" hoặc "Nữ"
    
    // Chuyển đổi giới tính từ "Nam"/"Nữ" thành 0/1 theo đúng định dạng cơ sở dữ liệu
    $genderValue = ($gender == 'Nam') ? 1 : 0;  // 1 = Nam, 0 = Nữ (theo CSDL)
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($id) || empty($firstName) || empty($lastName) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
        exit;
    }

    // Xử lý cập nhật dựa vào loại người dùng
    if ($userType == 'student') {
        $class = $_POST['editClass'] ?? '';
        
        // Cập nhật thông tin sinh viên
        $sql = "UPDATE sinh_vien SET 
                SV_HOSV = ?, 
                SV_TENSV = ?, 
                SV_EMAIL = ?, 
                SV_SDT = ?, 
                SV_DIACHI = ?, 
                SV_NGAYSINH = ?, 
                SV_GIOITINH = ?, 
                LOP_MA = ? 
                WHERE SV_MASV = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
            exit;
        }
        
        // Bind các tham số với các giá trị
        $stmt->bind_param("ssssssiss", $firstName, $lastName, $email, $phone, $address, $birthDate, $genderValue, $class, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin sinh viên thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $stmt->error]);
        }
        
    } elseif ($userType == 'teacher') {
        $department = $_POST['editDepartment'] ?? '';
        
        // Debug thông tin về khoa được gửi từ form
        error_log("Department value: " . $department);
        
        // Cập nhật thông tin giảng viên (kiểm tra xem có cột GV_SDT và GV_GIOITINH không)
        $checkColumnsSQL = "SHOW COLUMNS FROM giang_vien LIKE 'GV_SDT'";
        $result = $conn->query($checkColumnsSQL);
        $hasSdtColumn = ($result && $result->num_rows > 0);
        
        $checkColumnsSQL = "SHOW COLUMNS FROM giang_vien LIKE 'GV_GIOITINH'";
        $result = $conn->query($checkColumnsSQL);
        $hasGioiTinhColumn = ($result && $result->num_rows > 0);
        
        // Xây dựng câu lệnh SQL dựa trên các cột hiện có
        $sql = "UPDATE giang_vien SET 
                GV_HOGV = ?, 
                GV_TENGV = ?, 
                GV_EMAIL = ?, ";
                
        // Thêm cột SDT nếu tồn tại
        if ($hasSdtColumn) {
            $sql .= "GV_SDT = ?, ";
        }
                
        $sql .= "GV_DIACHI = ?, 
                GV_NGAYSINH = ?";
        
        // Thêm cột GIOITINH nếu tồn tại
        if ($hasGioiTinhColumn) {
            $sql .= ", GV_GIOITINH = ?";
        }
        
        // Thêm cập nhật DV_MADV nếu được cung cấp
        if (!empty($department)) {
            $sql .= ", DV_MADV = ?";
        }
        
        $sql .= " WHERE GV_MAGV = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error . ' - ' . $sql]);
            exit;
        }
        
        // Xây dựng các tham số cần bind
        $types = "sss"; // Các tham số cơ bản: họ, tên, email
        $params = array($firstName, $lastName, $email);
        
        if ($hasSdtColumn) {
            $types .= "s";
            $params[] = $phone;
        }
        
        $types .= "ss"; // địa chỉ, ngày sinh
        $params[] = $address;
        $params[] = $birthDate;
        
        if ($hasGioiTinhColumn) {
            $types .= "i";
            $params[] = $genderValue;
        }
        
        if (!empty($department)) {
            $types .= "s";
            $params[] = $department;
        }
        
        // Thêm ID vào cuối để WHERE
        $types .= "s";
        $params[] = $id;
        
        // Bind parameters
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin giảng viên thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $stmt->error]);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Loại người dùng không hợp lệ']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
}
?>