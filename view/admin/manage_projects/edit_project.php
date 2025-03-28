<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_projects\edit_project.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa đề tài</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../../../include/admin_sidebar.php'; ?>

    <div class="container-fluid" style="margin-left: 220px;">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Chỉnh sửa đề tài</h1>
                <div class="card p-4">
                    <?php
                    // Kiểm tra xem ID đề tài có được truyền qua URL không
                    if (isset($_GET['id']) && !empty($_GET['id'])) {
                        $id = $_GET['id'];

                        // Chuẩn bị câu lệnh SQL để lấy thông tin đề tài
                        $sql = "SELECT * FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();

                            // Kiểm tra khóa ngoại QD_SO
                            if (!empty($row['QD_SO'])) {
                                $sql_check_qd = "SELECT QD_SO FROM quyet_dinh_nghiem_thu WHERE QD_SO = ?";
                                $stmt_check_qd = $conn->prepare($sql_check_qd);
                                $stmt_check_qd->bind_param("s", $row['QD_SO']);
                                $stmt_check_qd->execute();
                                $result_check_qd = $stmt_check_qd->get_result();

                                if ($result_check_qd->num_rows == 0) {
                                    echo "<p class='text-danger'>Lỗi: Số quyết định không tồn tại trong cơ sở dữ liệu!</p>";
                                    exit();
                                }
                            }

                            // Lấy dữ liệu từ các bảng liên quan
                            $sql_loai_de_tai = "SELECT * FROM loai_de_tai";
                            $loai_de_tai = $conn->query($sql_loai_de_tai);

                            $sql_giang_vien = "SELECT GV_MAGV, CONCAT(GV_HOGV, ' ', GV_TENGV) AS GV_HOTEN FROM giang_vien";
                            $giang_vien = $conn->query($sql_giang_vien);

                            $sql_lvnc = "SELECT * FROM linh_vuc_nghien_cuu";
                            $lvnc = $conn->query($sql_lvnc);

                            $sql_lvut = "SELECT * FROM linh_vuc_uu_tien";
                            $lvut = $conn->query($sql_lvut);
                            ?>

                            <form action="update_project.php" method="POST">
                                <input type="hidden" name="DT_MADT" value="<?php echo htmlspecialchars($row['DT_MADT']); ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="DT_TENDT">Tên đề tài</label>
                                            <input type="text" class="form-control" id="DT_TENDT" name="DT_TENDT"
                                                value="<?php echo htmlspecialchars($row['DT_TENDT']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="DT_MOTA">Mô tả</label>
                                            <textarea class="form-control" id="DT_MOTA" name="DT_MOTA" rows="5"
                                                required><?php echo htmlspecialchars($row['DT_MOTA']); ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="LDT_MA">Loại đề tài</label>
                                            <select class="form-control" id="LDT_MA" name="LDT_MA">
                                                <?php while ($ldt = $loai_de_tai->fetch_assoc()) { ?>
                                                    <option value="<?php echo $ldt['LDT_MA']; ?>" <?php if ($ldt['LDT_MA'] == $row['LDT_MA'])
                                                           echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($ldt['LDT_TENLOAI']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="LVNC_MA">Lĩnh vực nghiên cứu</label>
                                            <select class="form-control" id="LVNC_MA" name="LVNC_MA">
                                                <?php while ($lv = $lvnc->fetch_assoc()) { ?>
                                                    <option value="<?php echo $lv['LVNC_MA']; ?>" <?php if ($lv['LVNC_MA'] == $row['LVNC_MA'])
                                                           echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($lv['LVNC_TEN']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="GV_MAGV">Giảng viên hướng dẫn</label>
                                            <select class="form-control" id="GV_MAGV" name="GV_MAGV">
                                                <?php while ($gv = $giang_vien->fetch_assoc()) { ?>
                                                    <option value="<?php echo $gv['GV_MAGV']; ?>" <?php if ($gv['GV_MAGV'] == $row['GV_MAGV'])
                                                           echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($gv['GV_HOTEN']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="LVUT_MA">Lĩnh vực ưu tiên</label>
                                            <select class="form-control" id="LVUT_MA" name="LVUT_MA">
                                                <?php while ($lv = $lvut->fetch_assoc()) { ?>
                                                    <option value="<?php echo $lv['LVUT_MA']; ?>" <?php if ($lv['LVUT_MA'] == $row['LVUT_MA'])
                                                           echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($lv['LVUT_TEN']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="DT_TRANGTHAI">Trạng thái</label>
                                            <select class="form-control" id="DT_TRANGTHAI" name="DT_TRANGTHAI" required>
                                                <option value="Chờ duyệt" <?php if ($row['DT_TRANGTHAI'] == 'Chờ duyệt')
                                                    echo 'selected'; ?>>Chờ duyệt</option>
                                                <option value="Đang thực hiện" <?php if ($row['DT_TRANGTHAI'] == 'Đang thực hiện')
                                                    echo 'selected'; ?>>Đang thực hiện</option>
                                                <option value="Đã hoàn thành" <?php if ($row['DT_TRANGTHAI'] == 'Đã hoàn thành')
                                                    echo 'selected'; ?>>Đã hoàn thành</option>
                                                <option value="Tạm dừng" <?php if ($row['DT_TRANGTHAI'] == 'Tạm dừng')
                                                    echo 'selected'; ?>>Tạm dừng</option>
                                                <option value="Đã hủy" <?php if ($row['DT_TRANGTHAI'] == 'Đã hủy')
                                                    echo 'selected'; ?>>Đã hủy</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Cập nhật</button>
                            </form>
                        <?php
                        } else {
                            echo "<p class='text-danger'>Không tìm thấy đề tài.</p>";
                        }
                        $stmt->close();
                    } else {
                        echo "<p class='text-danger'>ID đề tài không hợp lệ.</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>