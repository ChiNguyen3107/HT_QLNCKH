<?php
// Tạo dữ liệu test cho hệ thống CVHT
include '../../include/connect.php';

echo "<h2>Tạo dữ liệu test cho hệ thống CVHT</h2>";

// 1. Kiểm tra dữ liệu hiện có
echo "<h3>1. Kiểm tra dữ liệu hiện có:</h3>";

// Kiểm tra lớp DI2195A2
$result = $conn->query("SELECT COUNT(*) as total FROM lop WHERE LOP_MA = 'DI2195A2'");
$lop_count = $result->fetch_assoc()['total'];
echo "<p>Lớp DI2195A2: $lop_count bản ghi</p>";

// Kiểm tra sinh viên trong lớp
$result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE LOP_MA = 'DI2195A2'");
$sv_count = $result->fetch_assoc()['total'];
echo "<p>Sinh viên trong lớp DI2195A2: $sv_count bản ghi</p>";

// Kiểm tra giảng viên
$result = $conn->query("SELECT COUNT(*) as total FROM giang_vien");
$gv_count = $result->fetch_assoc()['total'];
echo "<p>Tổng giảng viên: $gv_count bản ghi</p>";

// Kiểm tra đề tài
$result = $conn->query("SELECT COUNT(*) as total FROM de_tai_nghien_cuu");
$dt_count = $result->fetch_assoc()['total'];
echo "<p>Tổng đề tài: $dt_count bản ghi</p>";

// 2. Tạo dữ liệu test nếu cần
echo "<h3>2. Tạo dữ liệu test:</h3>";

// Tạo lớp nếu chưa có
if ($lop_count == 0) {
    echo "<p>Tạo lớp DI2195A2...</p>";
    $conn->query("INSERT INTO lop (LOP_MA, DV_MADV, KH_NAM, LOP_TEN) VALUES ('DI2195A2', 'DV001', '2022-2026', 'Hệ thống thông tin')");
    echo "<p>✓ Đã tạo lớp DI2195A2</p>";
}

// Tạo sinh viên test nếu chưa có
if ($sv_count == 0) {
    echo "<p>Tạo sinh viên test...</p>";
    for ($i = 1; $i <= 40; $i++) {
        $mssv = 'SV' . str_pad($i, 6, '0', STR_PAD_LEFT);
        $conn->query("INSERT INTO sinh_vien (SV_MASV, LOP_MA, SV_HOSV, SV_TENSV, SV_GIOITINH, SV_SDT, SV_EMAIL, SV_MATKHAU) 
                     VALUES ('$mssv', 'DI2195A2', 'Nguyễn Văn', 'Sinh viên $i', 1, '0123456789', 'sv$i@test.com', 'password')");
    }
    echo "<p>✓ Đã tạo 40 sinh viên test</p>";
}

// Tạo giảng viên test nếu chưa có
if ($gv_count == 0) {
    echo "<p>Tạo giảng viên test...</p>";
    $conn->query("INSERT INTO giang_vien (GV_MAGV, DV_MADV, GV_HOGV, GV_TENGV, GV_EMAIL, GV_MATKHAU) 
                 VALUES ('GV0001', 'DV001', 'Trần Văn', 'Giảng viên 1', 'gv1@test.com', 'password')");
    $conn->query("INSERT INTO giang_vien (GV_MAGV, DV_MADV, GV_HOGV, GV_TENGV, GV_EMAIL, GV_MATKHAU) 
                 VALUES ('GV0002', 'DV001', 'Lê Thị', 'Giảng viên 2', 'gv2@test.com', 'password')");
    echo "<p>✓ Đã tạo 2 giảng viên test</p>";
}

// Tạo đề tài test nếu chưa có
if ($dt_count == 0) {
    echo "<p>Tạo đề tài test...</p>";
    
    // Tạo loại đề tài
    $conn->query("INSERT IGNORE INTO loai_de_tai (LDT_MA, LDT_TENLOAI) VALUES ('LDT01', 'Đề tài nghiên cứu')");
    
    // Tạo lĩnh vực nghiên cứu
    $conn->query("INSERT IGNORE INTO linh_vuc_nghien_cuu (LVNC_MA, LVNC_TEN, LVNC_MOTA) VALUES ('LV001', 'Công nghệ thông tin', 'Lĩnh vực CNTT')");
    
    // Tạo lĩnh vực ưu tiên
    $conn->query("INSERT IGNORE INTO linh_vuc_uu_tien (LVUT_MA, LVUT_TEN, LVUT_MOTA) VALUES ('LVU01', 'Ứng dụng thực tế', 'Lĩnh vực ưu tiên')");
    
    // Tạo đề tài
    $conn->query("INSERT INTO de_tai_nghien_cuu (DT_MADT, LDT_MA, GV_MAGV, LVNC_MA, LVUT_MA, HD_MA, DT_TENDT, DT_MOTA, DT_TRANGTHAI) 
                 VALUES ('DT0000001', 'LDT01', 'GV0001', 'LV001', 'LVU01', 'HD001', 'Hệ thống quản lý sinh viên', 'Mô tả đề tài 1', 'Đang thực hiện')");
    
    $conn->query("INSERT INTO de_tai_nghien_cuu (DT_MADT, LDT_MA, GV_MAGV, LVNC_MA, LVUT_MA, HD_MA, DT_TENDT, DT_MOTA, DT_TRANGTHAI) 
                 VALUES ('DT0000002', 'LDT01', 'GV0002', 'LV001', 'LVU01', 'HD002', 'Website bán hàng online', 'Mô tả đề tài 2', 'Đã hoàn thành')");
    
    echo "<p>✓ Đã tạo 2 đề tài test</p>";
    
    // Tạo chi tiết tham gia
    $conn->query("INSERT INTO chi_tiet_tham_gia (SV_MASV, DT_MADT, HK_MA, CTTG_VAITRO, CTTG_NGAYTHAMGIA) 
                 VALUES ('SV000001', 'DT0000001', 'HK2023-1', 'Thành viên', '2023-09-01')");
    $conn->query("INSERT INTO chi_tiet_tham_gia (SV_MASV, DT_MADT, HK_MA, CTTG_VAITRO, CTTG_NGAYTHAMGIA) 
                 VALUES ('SV000002', 'DT0000001', 'HK2023-1', 'Thành viên', '2023-09-01')");
    $conn->query("INSERT INTO chi_tiet_tham_gia (SV_MASV, DT_MADT, HK_MA, CTTG_VAITRO, CTTG_NGAYTHAMGIA) 
                 VALUES ('SV000003', 'DT0000002', 'HK2023-1', 'Thành viên', '2023-09-01')");
    
    echo "<p>✓ Đã tạo chi tiết tham gia</p>";
}

// 3. Tạo gán CVHT
echo "<h3>3. Tạo gán CVHT:</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM advisor_class WHERE LOP_MA = 'DI2195A2'");
$ac_count = $result->fetch_assoc()['total'];

if ($ac_count == 0) {
    echo "<p>Tạo gán CVHT cho lớp DI2195A2...</p>";
    $conn->query("INSERT INTO advisor_class (GV_MAGV, LOP_MA, AC_NGAYBATDAU, AC_COHIEULUC, AC_GHICHU, AC_NGUOICAPNHAT) 
                 VALUES ('GV0001', 'DI2195A2', '2023-09-01', 1, 'Gán CVHT test', 'ADMIN')");
    echo "<p>✓ Đã tạo gán CVHT</p>";
}

// 4. Hiển thị kết quả cuối cùng
echo "<h3>4. Kết quả cuối cùng:</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE LOP_MA = 'DI2195A2'");
$final_sv = $result->fetch_assoc()['total'];

$result = $conn->query("
    SELECT COUNT(DISTINCT sv.SV_MASV) as total 
    FROM sinh_vien sv 
    JOIN chi_tiet_tham_gia cttg ON sv.SV_MASV = cttg.SV_MASV 
    WHERE sv.LOP_MA = 'DI2195A2'
");
$final_with_projects = $result->fetch_assoc()['total'];

echo "<p>✓ Tổng sinh viên lớp DI2195A2: $final_sv</p>";
echo "<p>✓ Sinh viên có đề tài: $final_with_projects</p>";

echo "<p><a href='test_fixed_api.php?lop_ma=DI2195A2' class='btn btn-primary'>Test API Thống kê</a></p>";
echo "<p><a href='manage_advisor.php' class='btn btn-success'>Quay lại Quản lý CVHT</a></p>";
?>
