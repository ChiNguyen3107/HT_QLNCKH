<?php
if (empty($_POST['QD_SO'])) {
    echo "<script>alert('Lỗi: Số quyết định không được để trống!'); window.history.back();</script>";
    exit();
}
echo "<script>console.log('QD_SO: " . $_POST['QD_SO'] . "');</script>";
include '../../../include/session.php';
checkAdminRole();
include '../../../include/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $DT_MADT = $_POST['DT_MADT'] ?? '';
    $DT_TENDT = $_POST['DT_TENDT'] ?? '';
    $DT_MOTA = $_POST['DT_MOTA'] ?? '';
    $LDT_MA = $_POST['LDT_MA'] ?? '';
    $GV_MAGV = $_POST['GV_MAGV'] ?? '';
    $LVNC_MA = $_POST['LVNC_MA'] ?? '';
    $QD_SO = $_POST['QD_SO'] ?? '';
    $LVUT_MA = $_POST['LVUT_MA'] ?? '';
    $DT_TRANGTHAI = $_POST['DT_TRANGTHAI'] ?? '';

    // Kiểm tra dữ liệu đầu vào
    if (empty($DT_MADT) || empty($DT_TENDT) || empty($DT_MOTA) || empty($LDT_MA) || empty($GV_MAGV) || empty($LVNC_MA) || empty($QD_SO)) {
        echo "<script>alert('Lỗi: Một số trường quan trọng bị bỏ trống!'); window.history.back();</script>";
        exit();
    }

    // Kiểm tra mã đề tài có tồn tại không
    $sql_check_dt = "SELECT 1 FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $stmt_check_dt = $conn->prepare($sql_check_dt);
    $stmt_check_dt->bind_param("s", $DT_MADT);
    $stmt_check_dt->execute();
    $result_check_dt = $stmt_check_dt->get_result();

    if ($result_check_dt->num_rows == 0) {
        echo "<script>alert('Lỗi: Đề tài không tồn tại!'); window.history.back();</script>";
        exit();
    }
    $stmt_check_dt->close();

    // Kiểm tra khóa ngoại QD_SO nếu có giá trị
    if (!empty($QD_SO)) {
        $sql_check_qd = "SELECT 1 FROM quyet_dinh_nghiem_thu WHERE QD_SO = ?";
        $stmt_check_qd = $conn->prepare($sql_check_qd);
        $stmt_check_qd->bind_param("s", $QD_SO);
        $stmt_check_qd->execute();
        $result_check_qd = $stmt_check_qd->get_result();

        if ($result_check_qd->num_rows == 0) {
            echo "<script>alert('Lỗi: Số quyết định không tồn tại!'); window.history.back();</script>";
            exit();
        }
        $stmt_check_qd->close();
    }

    // Nếu không cập nhật file bài toán mẫu, giữ nguyên giá trị cũ
    if (empty($DT_FILEBTM)) {
        $sql_get_file = "SELECT DT_FILEBTM FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
        $stmt_get_file = $conn->prepare($sql_get_file);
        $stmt_get_file->bind_param("s", $DT_MADT);
        $stmt_get_file->execute();
        $result_get_file = $stmt_get_file->get_result();
        if ($row = $result_get_file->fetch_assoc()) {
            $DT_FILEBTM = $row['DT_FILEBTM'];
        }
        $stmt_get_file->close();
    }

    // Cập nhật thông tin đề tài
    $sql_update = "UPDATE de_tai_nghien_cuu 
                   SET DT_TENDT = ?, DT_MOTA = ?, LDT_MA = ?, GV_MAGV = ?, LVNC_MA = ?, QD_SO = ?, LVUT_MA = ?, DT_TRANGTHAI = ? 
                   WHERE DT_MADT = ?";
    $stmt = $conn->prepare($sql_update);

    if (!$stmt) {
        echo "<script>alert('Lỗi chuẩn bị truy vấn: " . $conn->error . "'); window.history.back();</script>";
        exit();
    }

    $stmt->bind_param("sssssssss", $DT_TENDT, $DT_MOTA, $LDT_MA, $GV_MAGV, $LVNC_MA, $QD_SO, $LVUT_MA, $DT_TRANGTHAI, $DT_MADT);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Cập nhật đề tài thành công!'); window.location.href='manage_projects.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi cập nhật: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('🚫 Yêu cầu không hợp lệ!'); window.location.href='manage_projects.php';</script>";
}
?>