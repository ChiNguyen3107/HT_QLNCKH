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
    <title>Cập nhật thông tin đề tài</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../../../include/admin_sidebar.php'; ?>

    <div class="container-fluid" style="margin-left: 220px;">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Cập nhật thông tin đề tài</h1>
                <div class="card p-4">
                    <?php
                    // Kiểm tra xem ID đề tài có được truyền qua URL không
                    if (isset($_GET['id']) && !empty($_GET['id'])) {
                        $id = $_GET['id'];

                        // Truy vấn SQL để lấy thông tin chi tiết
                        $sql = "SELECT 
                                    dt.DT_MADT AS MaDeTai,
                                    dt.DT_TENDT AS TenDeTai,
                                    dt.DT_MOTA AS MoTa,
                                    dt.DT_TRANGTHAI AS TrangThai,
                                    gv.GV_MAGV AS MaGiangVien,
                                    ldt.LDT_MA AS MaLoaiDeTai,
                                    lvnc.LVNC_MA AS MaLinhVucNghienCuu,
                                    lvut.LVUT_MA AS MaLinhVucUuTien,
                                    qd.QD_SO AS SoQuyetDinh,
                                    qd.QD_NGAY AS NgayQuyetDinh,
                                    qd.QD_FILE AS FileQuyetDinh
                                FROM 
                                    de_tai_nghien_cuu dt
                                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                                LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                                LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
                                LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
                                LEFT JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                                WHERE 
                                    dt.DT_MADT = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            ?>

                            <form action="update_project.php" method="POST">
                                <div class="row">
                                    <!-- Cột bên trái -->
                                    <div class="col-md-6">
                                        <!-- Mã đề tài (không cho chỉnh sửa) -->
                                        <div class="form-group">
                                            <label for="DT_MADT">Mã đề tài</label>
                                            <input type="text" class="form-control" id="DT_MADT" name="DT_MADT" value="<?php echo htmlspecialchars($row['MaDeTai']); ?>" readonly>
                                        </div>

                                        <!-- Tên đề tài -->
                                        <div class="form-group">
                                            <label for="DT_TENDT">Tên đề tài</label>
                                            <input type="text" class="form-control" id="DT_TENDT" name="DT_TENDT" value="<?php echo htmlspecialchars($row['TenDeTai']); ?>" required>
                                        </div>

                                        <!-- Mô tả -->
                                        <div class="form-group">
                                            <label for="DT_MOTA">Mô tả</label>
                                            <textarea class="form-control" id="DT_MOTA" name="DT_MOTA" rows="5" required><?php echo htmlspecialchars($row['MoTa']); ?></textarea>
                                        </div>

                                        <!-- Loại đề tài -->
                                        <div class="form-group">
                                            <label for="LDT_MA">Loại đề tài</label>
                                            <select class="form-control" id="LDT_MA" name="LDT_MA" required>
                                                <?php
                                                $loai_de_tai = $conn->query("SELECT LDT_MA, LDT_TENLOAI FROM loai_de_tai");
                                                while ($ldt = $loai_de_tai->fetch_assoc()) {
                                                    echo '<option value="' . $ldt['LDT_MA'] . '"' . ($ldt['LDT_MA'] == $row['MaLoaiDeTai'] ? ' selected' : '') . '>' . htmlspecialchars($ldt['LDT_TENLOAI']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Cột bên phải -->
                                    <div class="col-md-6">
                                        <!-- Giảng viên hướng dẫn -->
                                        <div class="form-group">
                                            <label for="GV_MAGV">Giảng viên hướng dẫn</label>
                                            <select class="form-control" id="GV_MAGV" name="GV_MAGV" required>
                                                <?php
                                                $giang_vien = $conn->query("SELECT GV_MAGV, CONCAT(GV_HOGV, ' ', GV_TENGV) AS GV_HOTEN FROM giang_vien");
                                                while ($gv = $giang_vien->fetch_assoc()) {
                                                    echo '<option value="' . $gv['GV_MAGV'] . '"' . ($gv['GV_MAGV'] == $row['MaGiangVien'] ? ' selected' : '') . '>' . htmlspecialchars($gv['GV_HOTEN']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Lĩnh vực nghiên cứu -->
                                        <div class="form-group">
                                            <label for="LVNC_MA">Lĩnh vực nghiên cứu</label>
                                            <select class="form-control" id="LVNC_MA" name="LVNC_MA" required>
                                                <?php
                                                $linh_vuc_nc = $conn->query("SELECT LVNC_MA, LVNC_TEN FROM linh_vuc_nghien_cuu");
                                                while ($lvnc = $linh_vuc_nc->fetch_assoc()) {
                                                    echo '<option value="' . $lvnc['LVNC_MA'] . '"' . ($lvnc['LVNC_MA'] == $row['MaLinhVucNghienCuu'] ? ' selected' : '') . '>' . htmlspecialchars($lvnc['LVNC_TEN']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Lĩnh vực ưu tiên -->
                                        <div class="form-group">
                                            <label for="LVUT_MA">Lĩnh vực ưu tiên</label>
                                            <select class="form-control" id="LVUT_MA" name="LVUT_MA" required>
                                                <?php
                                                $linh_vuc_ut = $conn->query("SELECT LVUT_MA, LVUT_TEN FROM linh_vuc_uu_tien");
                                                while ($lvut = $linh_vuc_ut->fetch_assoc()) {
                                                    echo '<option value="' . $lvut['LVUT_MA'] . '"' . ($lvut['LVUT_MA'] == $row['MaLinhVucUuTien'] ? ' selected' : '') . '>' . htmlspecialchars($lvut['LVUT_TEN']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Trạng thái -->
                                        <div class="form-group">
                                            <label for="DT_TRANGTHAI">Trạng thái</label>
                                            <select class="form-control" id="DT_TRANGTHAI" name="DT_TRANGTHAI" required>
                                                <option value="Chờ duyệt" <?php if ($row['TrangThai'] == 'Chờ duyệt') echo 'selected'; ?>>Chờ duyệt</option>
                                                <option value="Đang thực hiện" <?php if ($row['TrangThai'] == 'Đang thực hiện') echo 'selected'; ?>>Đang thực hiện</option>
                                                <option value="Đã hoàn thành" <?php if ($row['TrangThai'] == 'Đã hoàn thành') echo 'selected'; ?>>Đã hoàn thành</option>
                                                <option value="Tạm dừng" <?php if ($row['TrangThai'] == 'Tạm dừng') echo 'selected'; ?>>Tạm dừng</option>
                                                <option value="Đã hủy" <?php if ($row['TrangThai'] == 'Đã hủy') echo 'selected'; ?>>Đã hủy</option>
                                            </select>
                                        </div>

                                        <!-- Số quyết định (không cho chỉnh sửa) -->
                                        <div class="form-group">
                                            <label for="QD_SO">Số quyết định</label>
                                            <input type="text" class="form-control" id="QD_SO" name="QD_SO" value="<?php echo htmlspecialchars($row['SoQuyetDinh']); ?>" readonly>
                                        </div>

                                        <!-- Ngày quyết định (không cho chỉnh sửa) -->
                                        <div class="form-group">
                                            <label for="QD_NGAY">Ngày quyết định</label>
                                            <input type="text" class="form-control" id="QD_NGAY" value="<?php echo htmlspecialchars($row['NgayQuyetDinh']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nút cập nhật -->
                                <div class="form-group text-center mt-4">
                                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                                </div>
                            </form>
                            <?php
                        } else {
                            echo "<p class='text-danger'>Không tìm thấy thông tin đề tài.</p>";
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