-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 27, 2025 lúc 07:44 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `ql_nckh`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `advisor_class`
--

CREATE TABLE `advisor_class` (
  `AC_ID` int(11) NOT NULL,
  `GV_MAGV` char(8) NOT NULL COMMENT 'M?? gi???ng vi??n c??? v???n',
  `LOP_MA` char(8) NOT NULL COMMENT 'M?? l???p ???????c c??? v???n',
  `AC_NGAYBATDAU` date NOT NULL COMMENT 'Ng??y b???t ?????u c??? v???n',
  `AC_NGAYKETTHUC` date DEFAULT NULL COMMENT 'Ng??y k???t th??c c??? v???n (NULL = ??ang hi???u l???c)',
  `AC_COHIEULUC` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'C?? hi???u l???c (1=yes, 0=no)',
  `AC_GHICHU` text DEFAULT NULL COMMENT 'Ghi ch?? b??? sung',
  `AC_NGAYTAO` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Ng??y t???o b???n ghi',
  `AC_NGUOICAPNHAT` varchar(20) DEFAULT NULL COMMENT 'Ng?????i c???p nh???t cu???i'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='B???ng li??n k???t C??? v???n h???c t???p - L???p';

--
-- Bẫy `advisor_class`
--
DELIMITER $$
CREATE TRIGGER `tr_advisor_class_after_insert` AFTER INSERT ON `advisor_class` FOR EACH ROW BEGIN
    INSERT INTO advisor_class_audit_log (AC_ID, GV_MAGV, LOP_MA, ACAL_HANHDONG, ACAL_NOIDUNG, ACAL_NGUOITHUCHIEN)
    VALUES (NEW.AC_ID, NEW.GV_MAGV, NEW.LOP_MA, 'T???o', 
            CONCAT('G??n CVHT ', NEW.GV_MAGV, ' cho l???p ', NEW.LOP_MA, ' t??? ', NEW.AC_NGAYBATDAU),
            COALESCE(NEW.AC_NGUOICAPNHAT, 'SYSTEM'));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_advisor_class_after_update` AFTER UPDATE ON `advisor_class` FOR EACH ROW BEGIN
    DECLARE audit_message TEXT;
    
    IF NEW.AC_COHIEULUC != OLD.AC_COHIEULUC THEN
        IF NEW.AC_COHIEULUC = 0 THEN
            SET audit_message = CONCAT('Hu??? g??n CVHT ', NEW.GV_MAGV, ' kh???i l???p ', NEW.LOP_MA);
        ELSE
            SET audit_message = CONCAT('K??ch ho???t l???i CVHT ', NEW.GV_MAGV, ' cho l???p ', NEW.LOP_MA);
        END IF;
    ELSE
        SET audit_message = CONCAT('C???p nh???t th??ng tin CVHT ', NEW.GV_MAGV, ' cho l???p ', NEW.LOP_MA);
    END IF;
    
    INSERT INTO advisor_class_audit_log (AC_ID, GV_MAGV, LOP_MA, ACAL_HANHDONG, ACAL_NOIDUNG, ACAL_NGUOITHUCHIEN)
    VALUES (NEW.AC_ID, NEW.GV_MAGV, NEW.LOP_MA, 'C???p nh???t', audit_message,
            COALESCE(NEW.AC_NGUOICAPNHAT, 'SYSTEM'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `advisor_class_audit_log`
--

CREATE TABLE `advisor_class_audit_log` (
  `ACAL_ID` int(11) NOT NULL,
  `AC_ID` int(11) DEFAULT NULL COMMENT 'ID b???n ghi advisor_class',
  `GV_MAGV` char(8) NOT NULL COMMENT 'M?? gi???ng vi??n',
  `LOP_MA` char(8) NOT NULL COMMENT 'M?? l???p',
  `ACAL_HANHDONG` enum('T???o','C???p nh???t','X??a','G??n','Hu??? g??n') NOT NULL COMMENT 'H??nh ?????ng th???c hi???n',
  `ACAL_NOIDUNG` text DEFAULT NULL COMMENT 'N???i dung thay ?????i',
  `ACAL_NGUOITHUCHIEN` varchar(20) NOT NULL COMMENT 'Ng?????i th???c hi???n',
  `ACAL_NGAYTHUCHIEN` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Log audit cho thao t??c c??? v???n h???c t???p';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bien_ban`
--

CREATE TABLE `bien_ban` (
  `BB_SOBB` char(11) NOT NULL,
  `QD_SO` varchar(11) DEFAULT NULL,
  `BB_NGAYNGHIEMTHU` date NOT NULL,
  `BB_XEPLOAI` varchar(255) NOT NULL,
  `BB_TONGDIEM` decimal(5,2) DEFAULT NULL COMMENT 'T???ng ??i???m ????nh gi?? t??? 0-100, v???i 2 ch??? s??? th???p ph??n'
) ;

--
-- Bẫy `bien_ban`
--
DELIMITER $$
CREATE TRIGGER `tr_validate_bb_tongdiem_before_insert` BEFORE INSERT ON `bien_ban` FOR EACH ROW BEGIN
    IF NEW.BB_TONGDIEM IS NOT NULL AND (NEW.BB_TONGDIEM < 0 OR NEW.BB_TONGDIEM > 100) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'T???ng ??i???m bi??n b???n ph???i t??? 0 ?????n 100';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_validate_bb_tongdiem_before_update` BEFORE UPDATE ON `bien_ban` FOR EACH ROW BEGIN
    IF NEW.BB_TONGDIEM IS NOT NULL AND (NEW.BB_TONGDIEM < 0 OR NEW.BB_TONGDIEM > 100) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'T???ng ??i???m bi??n b???n ph???i t??? 0 ?????n 100';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_tham_gia`
--

CREATE TABLE `chi_tiet_tham_gia` (
  `SV_MASV` char(8) NOT NULL,
  `DT_MADT` char(10) NOT NULL,
  `HK_MA` char(8) NOT NULL,
  `CTTG_VAITRO` varchar(20) NOT NULL,
  `CTTG_NGAYTHAMGIA` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `co_van_hoc_tap`
--

CREATE TABLE `co_van_hoc_tap` (
  `CVHT_MA` int(11) NOT NULL,
  `GV_MAGV` char(8) NOT NULL,
  `LOP_MA` char(8) NOT NULL,
  `CVHT_NGAYBATDAU` date NOT NULL,
  `CVHT_NGAYKETTHUC` date DEFAULT NULL,
  `CVHT_TRANGTHAI` enum('Đang hoạt động','Đã kết thúc') DEFAULT 'Đang hoạt động',
  `CVHT_GHICHU` text DEFAULT NULL,
  `CVHT_NGAYTAO` datetime DEFAULT current_timestamp(),
  `CVHT_NGUOICAPNHAT` varchar(20) DEFAULT NULL,
  `CVHT_NGAYCAPNHAT` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `de_tai_nghien_cuu`
--

CREATE TABLE `de_tai_nghien_cuu` (
  `DT_MADT` char(10) NOT NULL,
  `LDT_MA` char(5) NOT NULL,
  `GV_MAGV` char(8) NOT NULL,
  `LVNC_MA` char(5) NOT NULL,
  `QD_SO` varchar(11) DEFAULT NULL,
  `LVUT_MA` char(5) NOT NULL,
  `HD_MA` char(5) NOT NULL,
  `DT_TENDT` varchar(200) NOT NULL,
  `DT_MOTA` text NOT NULL,
  `DT_TRANGTHAI` enum('Chờ duyệt','Đang thực hiện','Đã hoàn thành','Tạm dừng','Đã hủy','Đang xử lý') NOT NULL DEFAULT 'Chờ duyệt',
  `DT_FILEBTM` varchar(255) DEFAULT NULL,
  `DT_NGAYTAO` datetime NOT NULL DEFAULT current_timestamp(),
  `DT_SLSV` int(11) NOT NULL DEFAULT 3 COMMENT 'Số lượng sinh viên tham gia đề tài',
  `DT_GHICHU` text DEFAULT NULL,
  `DT_NGUOICAPNHAT` varchar(20) DEFAULT NULL,
  `DT_NGAYCAPNHAT` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Bẫy `de_tai_nghien_cuu`
--
DELIMITER $$
CREATE TRIGGER `tg_auto_generate_dt_madt` BEFORE INSERT ON `de_tai_nghien_cuu` FOR EACH ROW BEGIN
  DECLARE next_id INT;
  
  -- Lấy ID tiếp theo
  SELECT IFNULL(MAX(SUBSTRING(DT_MADT, 3)), 0) + 1 INTO next_id FROM `de_tai_nghien_cuu`;
  
  -- Tạo mã đề tài mới
  SET NEW.DT_MADT = CONCAT('DT', LPAD(next_id, 7, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `file_dinh_kem`
--

CREATE TABLE `file_dinh_kem` (
  `FDG_MA` char(10) NOT NULL,
  `BB_SOBB` char(11) NOT NULL,
  `GV_MAGV` char(8) DEFAULT NULL COMMENT 'Mã giảng viên (thành viên hội đồng)',
  `FDG_LOAI` varchar(50) NOT NULL COMMENT 'Loại file đánh giá',
  `FDG_TENFILE` varchar(200) DEFAULT NULL COMMENT 'Tên hiển thị của file',
  `FDG_FILE` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn file đánh giá',
  `FDG_NGAYTAO` datetime DEFAULT current_timestamp() COMMENT 'Ngày tạo file',
  `FDG_KICHTHUC` bigint(20) DEFAULT NULL COMMENT 'Kích thước file (bytes)',
  `FDG_MOTA` text DEFAULT NULL COMMENT 'Mô tả file đánh giá'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giang_vien`
--

CREATE TABLE `giang_vien` (
  `GV_MAGV` char(8) NOT NULL,
  `DV_MADV` char(5) NOT NULL,
  `GV_HOGV` varchar(50) NOT NULL,
  `GV_TENGV` varchar(50) NOT NULL,
  `GV_EMAIL` varchar(35) NOT NULL,
  `GV_CHUYENMON` text DEFAULT NULL,
  `GV_GIOITINH` tinyint(4) NOT NULL DEFAULT 1,
  `GV_SDT` varchar(15) DEFAULT NULL,
  `GV_MATKHAU` varchar(255) NOT NULL,
  `GV_NGAYSINH` date DEFAULT NULL,
  `GV_DIACHI` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoc_ki`
--

CREATE TABLE `hoc_ki` (
  `HK_MA` char(8) NOT NULL,
  `NK_NAM` varchar(9) NOT NULL,
  `HK_TEN` varchar(100) NOT NULL,
  `HK_NGAYBD` date DEFAULT NULL,
  `HK_NGAYKT` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hop_dong`
--

CREATE TABLE `hop_dong` (
  `HD_MA` varchar(11) NOT NULL,
  `DT_MADT` char(10) NOT NULL,
  `HD_NGAYTAO` date NOT NULL,
  `HD_NGAYBD` date NOT NULL,
  `HD_NGAYKT` date NOT NULL,
  `HD_GHICHU` text DEFAULT NULL,
  `HD_TONGKINHPHI` decimal(10,2) NOT NULL,
  `HD_FILEHD` varchar(255) DEFAULT NULL,
  `HD_NGUOIKY` varchar(100) DEFAULT NULL COMMENT 'Người ký hợp đồng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoa`
--

CREATE TABLE `khoa` (
  `DV_MADV` char(5) NOT NULL,
  `DV_TENDV` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoa_hoc`
--

CREATE TABLE `khoa_hoc` (
  `KH_NAM` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich_su_thuyet_minh`
--

CREATE TABLE `lich_su_thuyet_minh` (
  `ID` int(11) NOT NULL,
  `DT_MADT` char(10) NOT NULL,
  `FILE_TEN` varchar(255) NOT NULL,
  `FILE_KICHTHUOC` bigint(20) DEFAULT NULL,
  `FILE_LOAI` varchar(100) DEFAULT NULL,
  `LY_DO` text DEFAULT NULL,
  `NGUOI_TAI` varchar(20) DEFAULT NULL,
  `NGAY_TAI` datetime NOT NULL DEFAULT current_timestamp(),
  `LA_HIEN_TAI` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `linh_vuc_nghien_cuu`
--

CREATE TABLE `linh_vuc_nghien_cuu` (
  `LVNC_MA` char(5) NOT NULL,
  `LVNC_TEN` varchar(50) NOT NULL,
  `LVNC_MOTA` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `linh_vuc_uu_tien`
--

CREATE TABLE `linh_vuc_uu_tien` (
  `LVUT_MA` char(5) NOT NULL,
  `LVUT_TEN` varchar(255) NOT NULL,
  `LVUT_MOTA` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_bao_cao`
--

CREATE TABLE `loai_bao_cao` (
  `LBC_MALOAI` char(5) NOT NULL,
  `LBC_TENLOAI` varchar(50) NOT NULL,
  `LBC_MOTA` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_de_tai`
--

CREATE TABLE `loai_de_tai` (
  `LDT_MA` char(5) NOT NULL,
  `LDT_TENLOAI` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lop`
--

CREATE TABLE `lop` (
  `LOP_MA` char(8) NOT NULL,
  `DV_MADV` char(5) NOT NULL,
  `KH_NAM` varchar(9) NOT NULL,
  `LOP_TEN` varchar(50) NOT NULL,
  `LOP_LOAICTDT` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `ND_MA` varchar(20) NOT NULL,
  `ND_MATKHAU` varchar(255) NOT NULL,
  `ND_VAITRO` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nien_khoa`
--

CREATE TABLE `nien_khoa` (
  `NK_NAM` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quan_ly_nghien_cuu`
--

CREATE TABLE `quan_ly_nghien_cuu` (
  `QL_MA` char(8) NOT NULL,
  `DV_MADV` char(5) NOT NULL,
  `QL_HO` varchar(20) NOT NULL,
  `QL_TEN` varchar(50) NOT NULL,
  `QL_EMAIL` varchar(50) NOT NULL,
  `QL_MATKHAU` varchar(255) NOT NULL,
  `QL_SDT` varchar(15) DEFAULT NULL,
  `QL_GIOITINH` tinyint(1) DEFAULT 1,
  `QL_NGAYSINH` date DEFAULT NULL,
  `QL_DIACHI` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quyet_dinh_nghiem_thu`
--

CREATE TABLE `quyet_dinh_nghiem_thu` (
  `QD_SO` varchar(11) NOT NULL,
  `BB_SOBB` char(11) NOT NULL,
  `QD_NGAY` date NOT NULL,
  `QD_FILE` varchar(255) NOT NULL,
  `QD_NOIDUNG` text DEFAULT NULL COMMENT 'Nội dung chi tiết của quyết định',
  `HD_THANHVIEN` text DEFAULT NULL COMMENT 'Danh sách thành viên hội đồng (dạng JSON)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sinh_vien`
--

CREATE TABLE `sinh_vien` (
  `SV_MASV` char(8) NOT NULL,
  `LOP_MA` char(8) NOT NULL,
  `SV_HOSV` varchar(50) NOT NULL,
  `SV_TENSV` varchar(50) NOT NULL,
  `SV_GIOITINH` tinyint(4) NOT NULL,
  `SV_SDT` varchar(15) NOT NULL,
  `SV_EMAIL` varchar(35) NOT NULL,
  `SV_MATKHAU` varchar(255) NOT NULL,
  `SV_NGAYSINH` date DEFAULT NULL,
  `SV_DIACHI` varchar(255) DEFAULT NULL,
  `SV_AVATAR` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thanh_vien_hoi_dong`
--

CREATE TABLE `thanh_vien_hoi_dong` (
  `QD_SO` varchar(11) NOT NULL,
  `GV_MAGV` char(8) NOT NULL,
  `TV_HOTEN` varchar(100) DEFAULT NULL COMMENT 'Họ tên đầy đủ của thành viên',
  `TC_MATC` char(5) NOT NULL,
  `TV_VAITRO` varchar(30) NOT NULL,
  `TV_DIEM` decimal(5,2) DEFAULT NULL COMMENT '??i???m ????nh gi?? t??? 0-100, v???i 2 ch??? s??? th???p ph??n',
  `TV_DIEMCHITIET` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Điểm chi tiết theo từng tiêu chí (dạng JSON)' CHECK (json_valid(`TV_DIEMCHITIET`)),
  `TV_TRANGTHAI` enum('Chưa đánh giá','Đang đánh giá','Đã hoàn thành') DEFAULT 'Chưa đánh giá' COMMENT 'Trạng thái đánh giá',
  `TV_NGAYDANHGIA` datetime DEFAULT NULL COMMENT 'Ngày cập nhật đánh giá cuối cùng',
  `TV_FILEDANHGIA` varchar(255) DEFAULT NULL COMMENT 'File đánh giá của thành viên',
  `TV_DANHGIA` text DEFAULT NULL COMMENT 'Nhận xét đánh giá của thành viên',
  `TV_CHITIET_TIEUCHI` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Chi tiß║┐t ─æiß╗âm theo tß╗½ng ti├¬u ch├¡ (JSON)' CHECK (json_valid(`TV_CHITIET_TIEUCHI`)),
  `TV_HOAN_THANH` tinyint(1) DEFAULT 0 COMMENT 'Trß║íng th├íi ho├án th├ánh ─æ├ính gi├í'
) ;

--
-- Bẫy `thanh_vien_hoi_dong`
--
DELIMITER $$
CREATE TRIGGER `tr_validate_tv_diem_before_insert` BEFORE INSERT ON `thanh_vien_hoi_dong` FOR EACH ROW BEGIN
    IF NEW.TV_DIEM IS NOT NULL AND (NEW.TV_DIEM < 0 OR NEW.TV_DIEM > 100) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = '??i???m th??nh vi??n h???i ?????ng ph???i t??? 0 ?????n 100';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_validate_tv_diem_before_update` BEFORE UPDATE ON `thanh_vien_hoi_dong` FOR EACH ROW BEGIN
    IF NEW.TV_DIEM IS NOT NULL AND (NEW.TV_DIEM < 0 OR NEW.TV_DIEM > 100) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = '??i???m th??nh vi??n h???i ?????ng ph???i t??? 0 ?????n 100';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thong_bao`
--

CREATE TABLE `thong_bao` (
  `TB_MA` int(11) NOT NULL,
  `TB_NOIDUNG` text NOT NULL,
  `TB_NGAYTAO` datetime NOT NULL DEFAULT current_timestamp(),
  `TB_DANHDOC` tinyint(1) NOT NULL DEFAULT 0,
  `TB_LOAI` varchar(50) DEFAULT 'Thông báo',
  `DT_MADT` char(10) DEFAULT NULL,
  `GV_MAGV` char(8) DEFAULT NULL,
  `SV_MASV` char(8) DEFAULT NULL,
  `QL_MA` char(8) DEFAULT NULL,
  `NGUOI_NHAN` varchar(20) DEFAULT NULL,
  `TB_LINK` varchar(255) DEFAULT NULL,
  `TB_TRANGTHAI` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tien_do_de_tai`
--

CREATE TABLE `tien_do_de_tai` (
  `TDDT_MA` char(10) NOT NULL,
  `DT_MADT` char(10) NOT NULL,
  `SV_MASV` char(8) NOT NULL,
  `TDDT_TIEUDE` varchar(200) NOT NULL,
  `TDDT_NOIDUNG` text NOT NULL,
  `TDDT_PHANTRAMHOANTHANH` int(11) DEFAULT 0,
  `TDDT_FILE` varchar(255) DEFAULT NULL,
  `TDDT_NGAYCAPNHAT` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tieu_chi`
--

CREATE TABLE `tieu_chi` (
  `TC_MATC` char(5) NOT NULL,
  `TC_TEN` varchar(255) DEFAULT NULL,
  `TC_MOTA` text DEFAULT NULL,
  `TC_NDDANHGIA` text NOT NULL,
  `TC_DIEMTOIDA` decimal(3,0) NOT NULL,
  `TC_TRONGSO` decimal(5,2) DEFAULT 20.00,
  `TC_THUTU` int(11) DEFAULT 1,
  `TC_TRANGTHAI` enum('Hoạt động','Tạm dừng') DEFAULT 'Hoạt động'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tieu_chi_danh_gia`
--

CREATE TABLE `tieu_chi_danh_gia` (
  `TC_MA` varchar(10) NOT NULL,
  `TC_TEN` varchar(255) NOT NULL,
  `TC_MOTA` text DEFAULT NULL,
  `TC_DIEM_TOIDAI` decimal(5,2) DEFAULT 10.00,
  `TC_THUTU` int(11) DEFAULT 0,
  `TC_TRANGTHAI` enum('Hoß║ít ─æß╗Öng','Kh├┤ng hoß║ít ─æß╗Öng') DEFAULT 'Hoß║ít ─æß╗Öng',
  `TC_NGAYTAO` datetime DEFAULT current_timestamp(),
  `TC_NGAYCAPNHAT` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user`
--

CREATE TABLE `user` (
  `USER_ID` int(11) NOT NULL,
  `USERNAME` varchar(50) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `ROLE` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `view_chi_tiet_danh_gia`
-- (See below for the actual view)
--
CREATE TABLE `view_chi_tiet_danh_gia` (
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_class_overview`
-- (See below for the actual view)
--
CREATE TABLE `v_class_overview` (
`LOP_MA` char(8)
,`LOP_TEN` varchar(50)
,`KH_NAM` varchar(9)
,`DV_TENDV` varchar(50)
,`TONG_SV` bigint(21)
,`SV_CO_DETAI` bigint(21)
,`SV_CHUA_CO_DETAI` bigint(21)
,`DETAI_CHO_DUYET` bigint(21)
,`DETAI_DANG_THUCHIEN` bigint(21)
,`DETAI_HOAN_THANH` bigint(21)
,`DETAI_TAM_DUNG` bigint(21)
,`TY_LE_THAM_GIA_PHANTRAM` decimal(26,2)
,`CVHT_MAGV` char(8)
,`CVHT_HOTEN` varchar(101)
,`AC_NGAYBATDAU` date
,`AC_COHIEULUC` tinyint(1)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_score_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_score_summary` (
`table_name` varchar(19)
,`total_records` bigint(21)
,`scored_records` bigint(21)
,`min_score` decimal(5,2)
,`max_score` decimal(5,2)
,`avg_score` decimal(9,6)
,`invalid_scores` bigint(21)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_student_project_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_student_project_summary` (
`SV_MASV` char(8)
,`SV_HOSV` varchar(50)
,`SV_TENSV` varchar(50)
,`LOP_MA` char(8)
,`LOP_TEN` varchar(50)
,`KH_NAM` varchar(9)
,`DV_TENDV` varchar(50)
,`DT_MADT` char(10)
,`DT_TENDT` varchar(200)
,`DT_TRANGTHAI` enum('Chờ duyệt','Đang thực hiện','Đã hoàn thành','Tạm dừng','Đã hủy','Đang xử lý')
,`DT_NGAYTAO` datetime
,`GV_MAGV` char(8)
,`GV_HOTEN` varchar(101)
,`CTTG_VAITRO` varchar(20)
,`CTTG_NGAYTHAMGIA` date
,`TRANGTHAI_PHANLOAI` varchar(29)
,`TIENDO_PHANTRAM` int(3)
);

-- --------------------------------------------------------

--
-- Cấu trúc cho view `view_chi_tiet_danh_gia`
--
DROP TABLE IF EXISTS `view_chi_tiet_danh_gia`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_chi_tiet_danh_gia`  AS SELECT `ctddg`.`CTDDG_MA` AS `CTDDG_MA`, `ctddg`.`QD_SO` AS `QD_SO`, `qd`.`QD_NGAY` AS `QD_NGAY`, `bb`.`BB_SOBB` AS `BB_SOBB`, `bb`.`BB_NGAYNGHIEMTHU` AS `BB_NGAYNGHIEMTHU`, `tv`.`GV_MAGV` AS `GV_MAGV`, concat(`gv`.`GV_HOGV`,' ',`gv`.`GV_TENGV`) AS `GV_HOTEN`, `tv`.`TV_VAITRO` AS `TV_VAITRO`, `tv`.`TV_HOTEN` AS `TV_HOTEN_HIENTHI`, `ctddg`.`TC_MATC` AS `TC_MATC`, `tc`.`TC_NDDANHGIA` AS `TC_NDDANHGIA`, `tc`.`TC_DIEMTOIDA` AS `TC_DIEMTOIDA`, `ctddg`.`CTDDG_DIEM` AS `CTDDG_DIEM`, `ctddg`.`CTDDG_GHICHU` AS `CTDDG_GHICHU`, `ctddg`.`CTDDG_NGAYCAPNHAT` AS `CTDDG_NGAYCAPNHAT`, `tv`.`TV_FILEDANHGIA` AS `TV_FILEDANHGIA`, `tv`.`TV_TRANGTHAI` AS `TV_TRANGTHAI`, `tv`.`TV_NGAYDANHGIA` AS `TV_NGAYDANHGIA` FROM (((((`chi_tiet_diem_danh_gia` `ctddg` join `quyet_dinh_nghiem_thu` `qd` on(`ctddg`.`QD_SO` = `qd`.`QD_SO`)) left join `bien_ban` `bb` on(`qd`.`QD_SO` = `bb`.`QD_SO`)) join `thanh_vien_hoi_dong` `tv` on(`ctddg`.`QD_SO` = `tv`.`QD_SO` and `ctddg`.`GV_MAGV` = `tv`.`GV_MAGV`)) join `giang_vien` `gv` on(`ctddg`.`GV_MAGV` = `gv`.`GV_MAGV`)) join `tieu_chi` `tc` on(`ctddg`.`TC_MATC` = `tc`.`TC_MATC`)) ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_class_overview`
--
DROP TABLE IF EXISTS `v_class_overview`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_class_overview`  AS SELECT `lop`.`LOP_MA` AS `LOP_MA`, `lop`.`LOP_TEN` AS `LOP_TEN`, `lop`.`KH_NAM` AS `KH_NAM`, `dv`.`DV_TENDV` AS `DV_TENDV`, count(`sv`.`SV_MASV`) AS `TONG_SV`, count(`dt`.`DT_MADT`) AS `SV_CO_DETAI`, count(case when `dt`.`DT_MADT` is null then 1 end) AS `SV_CHUA_CO_DETAI`, count(case when `dt`.`DT_TRANGTHAI` = 'Ch??? duy???t' then 1 end) AS `DETAI_CHO_DUYET`, count(case when `dt`.`DT_TRANGTHAI` = '??ang th???c hi???n' then 1 end) AS `DETAI_DANG_THUCHIEN`, count(case when `dt`.`DT_TRANGTHAI` = '???? ho??n th??nh' then 1 end) AS `DETAI_HOAN_THANH`, count(case when `dt`.`DT_TRANGTHAI` in ('T???m d???ng','???? h???y') then 1 end) AS `DETAI_TAM_DUNG`, round(count(`dt`.`DT_MADT`) * 100.0 / count(`sv`.`SV_MASV`),2) AS `TY_LE_THAM_GIA_PHANTRAM`, `ac`.`GV_MAGV` AS `CVHT_MAGV`, concat(`gv`.`GV_HOGV`,' ',`gv`.`GV_TENGV`) AS `CVHT_HOTEN`, `ac`.`AC_NGAYBATDAU` AS `AC_NGAYBATDAU`, `ac`.`AC_COHIEULUC` AS `AC_COHIEULUC` FROM ((((((`lop` left join `khoa` `dv` on(`lop`.`DV_MADV` = `dv`.`DV_MADV`)) left join `sinh_vien` `sv` on(`lop`.`LOP_MA` = `sv`.`LOP_MA`)) left join `chi_tiet_tham_gia` `cttg` on(`sv`.`SV_MASV` = `cttg`.`SV_MASV`)) left join `de_tai_nghien_cuu` `dt` on(`cttg`.`DT_MADT` = `dt`.`DT_MADT`)) left join `advisor_class` `ac` on(`lop`.`LOP_MA` = `ac`.`LOP_MA` and `ac`.`AC_COHIEULUC` = 1)) left join `giang_vien` `gv` on(`ac`.`GV_MAGV` = `gv`.`GV_MAGV`)) GROUP BY `lop`.`LOP_MA`, `lop`.`LOP_TEN`, `lop`.`KH_NAM`, `dv`.`DV_TENDV`, `ac`.`GV_MAGV`, `gv`.`GV_HOGV`, `gv`.`GV_TENGV`, `ac`.`AC_NGAYBATDAU`, `ac`.`AC_COHIEULUC` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_score_summary`
--
DROP TABLE IF EXISTS `v_score_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_score_summary`  AS SELECT 'thanh_vien_hoi_dong' AS `table_name`, count(0) AS `total_records`, count(`thanh_vien_hoi_dong`.`TV_DIEM`) AS `scored_records`, min(`thanh_vien_hoi_dong`.`TV_DIEM`) AS `min_score`, max(`thanh_vien_hoi_dong`.`TV_DIEM`) AS `max_score`, avg(`thanh_vien_hoi_dong`.`TV_DIEM`) AS `avg_score`, count(case when `thanh_vien_hoi_dong`.`TV_DIEM` < 0 or `thanh_vien_hoi_dong`.`TV_DIEM` > 100 then 1 end) AS `invalid_scores` FROM `thanh_vien_hoi_dong`union all select 'bien_ban' AS `table_name`,count(0) AS `total_records`,count(`bien_ban`.`BB_TONGDIEM`) AS `scored_records`,min(`bien_ban`.`BB_TONGDIEM`) AS `min_score`,max(`bien_ban`.`BB_TONGDIEM`) AS `max_score`,avg(`bien_ban`.`BB_TONGDIEM`) AS `avg_score`,count(case when `bien_ban`.`BB_TONGDIEM` < 0 or `bien_ban`.`BB_TONGDIEM` > 100 then 1 end) AS `invalid_scores` from `bien_ban`  ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_student_project_summary`
--
DROP TABLE IF EXISTS `v_student_project_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_student_project_summary`  AS SELECT `sv`.`SV_MASV` AS `SV_MASV`, `sv`.`SV_HOSV` AS `SV_HOSV`, `sv`.`SV_TENSV` AS `SV_TENSV`, `sv`.`LOP_MA` AS `LOP_MA`, `lop`.`LOP_TEN` AS `LOP_TEN`, `lop`.`KH_NAM` AS `KH_NAM`, `dv`.`DV_TENDV` AS `DV_TENDV`, `dt`.`DT_MADT` AS `DT_MADT`, `dt`.`DT_TENDT` AS `DT_TENDT`, `dt`.`DT_TRANGTHAI` AS `DT_TRANGTHAI`, `dt`.`DT_NGAYTAO` AS `DT_NGAYTAO`, `gv`.`GV_MAGV` AS `GV_MAGV`, concat(`gv`.`GV_HOGV`,' ',`gv`.`GV_TENGV`) AS `GV_HOTEN`, `cttg`.`CTTG_VAITRO` AS `CTTG_VAITRO`, `cttg`.`CTTG_NGAYTHAMGIA` AS `CTTG_NGAYTHAMGIA`, CASE WHEN `dt`.`DT_MADT` is null THEN 'Ch??a tham gia' WHEN `dt`.`DT_TRANGTHAI` = '??ang th???c hi???n' THEN '??ang tham gia' WHEN `dt`.`DT_TRANGTHAI` = '???? ho??n th??nh' THEN '???? ho??n th??nh' WHEN `dt`.`DT_TRANGTHAI` in ('T???m d???ng','???? h???y') THEN 'B??? t??? ch???i/T???m d???ng' ELSE `dt`.`DT_TRANGTHAI` END AS `TRANGTHAI_PHANLOAI`, CASE WHEN `dt`.`DT_MADT` is null THEN 0 WHEN `dt`.`DT_TRANGTHAI` = 'Ch??? duy???t' THEN 10 WHEN `dt`.`DT_TRANGTHAI` = '??ang th???c hi???n' THEN 50 WHEN `dt`.`DT_TRANGTHAI` = '???? ho??n th??nh' THEN 100 ELSE 25 END AS `TIENDO_PHANTRAM` FROM (((((`sinh_vien` `sv` left join `lop` on(`sv`.`LOP_MA` = `lop`.`LOP_MA`)) left join `khoa` `dv` on(`lop`.`DV_MADV` = `dv`.`DV_MADV`)) left join `chi_tiet_tham_gia` `cttg` on(`sv`.`SV_MASV` = `cttg`.`SV_MASV`)) left join `de_tai_nghien_cuu` `dt` on(`cttg`.`DT_MADT` = `dt`.`DT_MADT`)) left join `giang_vien` `gv` on(`dt`.`GV_MAGV` = `gv`.`GV_MAGV`)) ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `advisor_class`
--
ALTER TABLE `advisor_class`
  ADD PRIMARY KEY (`AC_ID`),
  ADD UNIQUE KEY `uk_advisor_class_active` (`LOP_MA`,`AC_COHIEULUC`) COMMENT 'M???t l???p ch??? c?? 1 CVHT hi???u l???c',
  ADD KEY `idx_advisor_class_gv` (`GV_MAGV`),
  ADD KEY `idx_advisor_class_lop` (`LOP_MA`),
  ADD KEY `idx_advisor_class_active` (`AC_COHIEULUC`),
  ADD KEY `idx_advisor_class_date` (`AC_NGAYBATDAU`,`AC_NGAYKETTHUC`);

--
-- Chỉ mục cho bảng `advisor_class_audit_log`
--
ALTER TABLE `advisor_class_audit_log`
  ADD PRIMARY KEY (`ACAL_ID`),
  ADD KEY `idx_advisor_class_audit_lop` (`LOP_MA`),
  ADD KEY `idx_advisor_class_audit_gv` (`GV_MAGV`),
  ADD KEY `idx_advisor_class_audit_date` (`ACAL_NGAYTHUCHIEN`);

--
-- Chỉ mục cho bảng `bien_ban`
--
ALTER TABLE `bien_ban`
  ADD PRIMARY KEY (`BB_SOBB`),
  ADD KEY `idx_bien_ban_tongdiem` (`BB_TONGDIEM`),
  ADD KEY `idx_bien_ban_tongdiem_new` (`BB_TONGDIEM`),
  ADD KEY `FK_BIEN_BAN_CO_BIEN_B_QUYET_DI` (`QD_SO`) USING BTREE;

--
-- Chỉ mục cho bảng `chi_tiet_tham_gia`
--
ALTER TABLE `chi_tiet_tham_gia`
  ADD PRIMARY KEY (`SV_MASV`,`DT_MADT`,`HK_MA`),
  ADD KEY `FK_CHI_TIET_RELATIONS_HOC_KI` (`HK_MA`),
  ADD KEY `FK_CHI_TIET_RELATIONS_DE_TAI_N` (`DT_MADT`);

--
-- Chỉ mục cho bảng `co_van_hoc_tap`
--
ALTER TABLE `co_van_hoc_tap`
  ADD PRIMARY KEY (`CVHT_MA`),
  ADD UNIQUE KEY `unique_advisor_class` (`GV_MAGV`,`LOP_MA`),
  ADD KEY `LOP_MA` (`LOP_MA`);

--
-- Chỉ mục cho bảng `de_tai_nghien_cuu`
--
ALTER TABLE `de_tai_nghien_cuu`
  ADD PRIMARY KEY (`DT_MADT`),
  ADD KEY `FK_DE_TAI_N_CO_LINH_VUC` (`LVNC_MA`),
  ADD KEY `FK_DE_TAI_N_CO__1__LINH_VUC` (`LVUT_MA`),
  ADD KEY `FK_DE_TAI_N_RELATIONS_GIANG_VI` (`GV_MAGV`),
  ADD KEY `FK_DE_TAI_N_THUOC_LOAI_DE_` (`LDT_MA`),
  ADD KEY `FK_DE_TAI_N_RELATIONS_QUYET_DI` (`QD_SO`);

--
-- Chỉ mục cho bảng `file_dinh_kem`
--
ALTER TABLE `file_dinh_kem`
  ADD PRIMARY KEY (`FDG_MA`),
  ADD KEY `idx_fdg_loai` (`FDG_LOAI`),
  ADD KEY `idx_fdg_gv_magv` (`GV_MAGV`),
  ADD KEY `idx_fdg_ngaytao` (`FDG_NGAYTAO`),
  ADD KEY `FK_FILE_DAN_CUA_BIEN_BAN` (`BB_SOBB`);

--
-- Chỉ mục cho bảng `giang_vien`
--
ALTER TABLE `giang_vien`
  ADD PRIMARY KEY (`GV_MAGV`);

--
-- Chỉ mục cho bảng `hoc_ki`
--
ALTER TABLE `hoc_ki`
  ADD PRIMARY KEY (`HK_MA`),
  ADD KEY `FK_HOC_KI_RELATIONS_NIEN_KHO` (`NK_NAM`);

--
-- Chỉ mục cho bảng `hop_dong`
--
ALTER TABLE `hop_dong`
  ADD PRIMARY KEY (`HD_MA`),
  ADD KEY `FK_HOP_DONG_CO_1_2_DE_TAI_N` (`DT_MADT`);

--
-- Chỉ mục cho bảng `khoa`
--
ALTER TABLE `khoa`
  ADD PRIMARY KEY (`DV_MADV`);

--
-- Chỉ mục cho bảng `khoa_hoc`
--
ALTER TABLE `khoa_hoc`
  ADD PRIMARY KEY (`KH_NAM`);

--
-- Chỉ mục cho bảng `lich_su_thuyet_minh`
--
ALTER TABLE `lich_su_thuyet_minh`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `IDX_LSTM_DTMADT` (`DT_MADT`);

--
-- Chỉ mục cho bảng `linh_vuc_nghien_cuu`
--
ALTER TABLE `linh_vuc_nghien_cuu`
  ADD PRIMARY KEY (`LVNC_MA`);

--
-- Chỉ mục cho bảng `linh_vuc_uu_tien`
--
ALTER TABLE `linh_vuc_uu_tien`
  ADD PRIMARY KEY (`LVUT_MA`);

--
-- Chỉ mục cho bảng `loai_bao_cao`
--
ALTER TABLE `loai_bao_cao`
  ADD PRIMARY KEY (`LBC_MALOAI`);

--
-- Chỉ mục cho bảng `loai_de_tai`
--
ALTER TABLE `loai_de_tai`
  ADD PRIMARY KEY (`LDT_MA`);

--
-- Chỉ mục cho bảng `lop`
--
ALTER TABLE `lop`
  ADD PRIMARY KEY (`LOP_MA`),
  ADD KEY `FK_LOP_THUOC_VE_KHOA` (`DV_MADV`),
  ADD KEY `FK_LOP_THUOC_VE__KHOA_HOC` (`KH_NAM`);

--
-- Chỉ mục cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`ND_MA`);

--
-- Chỉ mục cho bảng `nien_khoa`
--
ALTER TABLE `nien_khoa`
  ADD PRIMARY KEY (`NK_NAM`);

--
-- Chỉ mục cho bảng `quan_ly_nghien_cuu`
--
ALTER TABLE `quan_ly_nghien_cuu`
  ADD PRIMARY KEY (`QL_MA`),
  ADD UNIQUE KEY `QL_EMAIL` (`QL_EMAIL`),
  ADD KEY `FK_QLNC_THUOC_VE_KHOA` (`DV_MADV`);

--
-- Chỉ mục cho bảng `quyet_dinh_nghiem_thu`
--
ALTER TABLE `quyet_dinh_nghiem_thu`
  ADD PRIMARY KEY (`QD_SO`);

--
-- Chỉ mục cho bảng `sinh_vien`
--
ALTER TABLE `sinh_vien`
  ADD PRIMARY KEY (`SV_MASV`),
  ADD KEY `FK_SINH_VIEN_THUOC_VE_LOP` (`LOP_MA`);

--
-- Chỉ mục cho bảng `thanh_vien_hoi_dong`
--
ALTER TABLE `thanh_vien_hoi_dong`
  ADD PRIMARY KEY (`QD_SO`,`GV_MAGV`,`TC_MATC`),
  ADD KEY `FK_THANH_VI_DANH_GIA__TIEU_CHI` (`TC_MATC`),
  ADD KEY `idx_qd_so` (`QD_SO`),
  ADD KEY `idx_gv_magv` (`GV_MAGV`),
  ADD KEY `idx_tv_trangthai` (`TV_TRANGTHAI`),
  ADD KEY `idx_tv_ngaydanhgia` (`TV_NGAYDANHGIA`),
  ADD KEY `idx_thanh_vien_hoi_dong_diem` (`TV_DIEM`);

--
-- Chỉ mục cho bảng `thong_bao`
--
ALTER TABLE `thong_bao`
  ADD PRIMARY KEY (`TB_MA`);

--
-- Chỉ mục cho bảng `tien_do_de_tai`
--
ALTER TABLE `tien_do_de_tai`
  ADD PRIMARY KEY (`TDDT_MA`),
  ADD KEY `FK_TIEN_DO_DETAI` (`DT_MADT`),
  ADD KEY `FK_TIEN_DO_SINHVIEN` (`SV_MASV`);

--
-- Chỉ mục cho bảng `tieu_chi`
--
ALTER TABLE `tieu_chi`
  ADD PRIMARY KEY (`TC_MATC`);

--
-- Chỉ mục cho bảng `tieu_chi_danh_gia`
--
ALTER TABLE `tieu_chi_danh_gia`
  ADD PRIMARY KEY (`TC_MA`),
  ADD KEY `idx_tieu_chi_thutu` (`TC_THUTU`);

--
-- Chỉ mục cho bảng `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`USER_ID`),
  ADD UNIQUE KEY `USERNAME` (`USERNAME`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `advisor_class`
--
ALTER TABLE `advisor_class`
  MODIFY `AC_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `advisor_class_audit_log`
--
ALTER TABLE `advisor_class_audit_log`
  MODIFY `ACAL_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `co_van_hoc_tap`
--
ALTER TABLE `co_van_hoc_tap`
  MODIFY `CVHT_MA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lich_su_thuyet_minh`
--
ALTER TABLE `lich_su_thuyet_minh`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `thong_bao`
--
ALTER TABLE `thong_bao`
  MODIFY `TB_MA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `user`
--
ALTER TABLE `user`
  MODIFY `USER_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `advisor_class`
--
ALTER TABLE `advisor_class`
  ADD CONSTRAINT `fk_advisor_class_giang_vien` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_advisor_class_lop` FOREIGN KEY (`LOP_MA`) REFERENCES `lop` (`LOP_MA`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `bien_ban`
--
ALTER TABLE `bien_ban`
  ADD CONSTRAINT `FK_BIEN_BAN_CO_BIEN_B_QUYET_DI` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_tham_gia`
--
ALTER TABLE `chi_tiet_tham_gia`
  ADD CONSTRAINT `FK_CHI_TIET_RELATIONS_DE_TAI_N` FOREIGN KEY (`DT_MADT`) REFERENCES `de_tai_nghien_cuu` (`DT_MADT`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_CHI_TIET_RELATIONS_HOC_KI` FOREIGN KEY (`HK_MA`) REFERENCES `hoc_ki` (`HK_MA`),
  ADD CONSTRAINT `FK_CHI_TIET_RELATIONS_SINH_VIE` FOREIGN KEY (`SV_MASV`) REFERENCES `sinh_vien` (`SV_MASV`);

--
-- Các ràng buộc cho bảng `co_van_hoc_tap`
--
ALTER TABLE `co_van_hoc_tap`
  ADD CONSTRAINT `co_van_hoc_tap_ibfk_1` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `co_van_hoc_tap_ibfk_2` FOREIGN KEY (`LOP_MA`) REFERENCES `lop` (`LOP_MA`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `de_tai_nghien_cuu`
--
ALTER TABLE `de_tai_nghien_cuu`
  ADD CONSTRAINT `FK_DE_TAI_N_CO_LINH_VUC` FOREIGN KEY (`LVNC_MA`) REFERENCES `linh_vuc_nghien_cuu` (`LVNC_MA`),
  ADD CONSTRAINT `FK_DE_TAI_N_CO__1__LINH_VUC` FOREIGN KEY (`LVUT_MA`) REFERENCES `linh_vuc_uu_tien` (`LVUT_MA`),
  ADD CONSTRAINT `FK_DE_TAI_N_RELATIONS_GIANG_VI` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`),
  ADD CONSTRAINT `FK_DE_TAI_N_RELATIONS_QUYET_DI` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_DE_TAI_N_THUOC_LOAI_DE_` FOREIGN KEY (`LDT_MA`) REFERENCES `loai_de_tai` (`LDT_MA`);

--
-- Các ràng buộc cho bảng `file_dinh_kem`
--
ALTER TABLE `file_dinh_kem`
  ADD CONSTRAINT `FK_FILE_DAN_CUA_BIEN_BAN` FOREIGN KEY (`BB_SOBB`) REFERENCES `bien_ban` (`BB_SOBB`),
  ADD CONSTRAINT `fk_file_dinh_kem_giang_vien` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `hoc_ki`
--
ALTER TABLE `hoc_ki`
  ADD CONSTRAINT `FK_HOC_KI_RELATIONS_NIEN_KHO` FOREIGN KEY (`NK_NAM`) REFERENCES `nien_khoa` (`NK_NAM`);

--
-- Các ràng buộc cho bảng `hop_dong`
--
ALTER TABLE `hop_dong`
  ADD CONSTRAINT `FK_HOP_DONG_CO_1_2_DE_TAI_N` FOREIGN KEY (`DT_MADT`) REFERENCES `de_tai_nghien_cuu` (`DT_MADT`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `lop`
--
ALTER TABLE `lop`
  ADD CONSTRAINT `FK_LOP_THUOC_VE_KHOA` FOREIGN KEY (`DV_MADV`) REFERENCES `khoa` (`DV_MADV`),
  ADD CONSTRAINT `FK_LOP_THUOC_VE__KHOA_HOC` FOREIGN KEY (`KH_NAM`) REFERENCES `khoa_hoc` (`KH_NAM`);

--
-- Các ràng buộc cho bảng `quan_ly_nghien_cuu`
--
ALTER TABLE `quan_ly_nghien_cuu`
  ADD CONSTRAINT `FK_QLNC_THUOC_VE_KHOA` FOREIGN KEY (`DV_MADV`) REFERENCES `khoa` (`DV_MADV`);

--
-- Các ràng buộc cho bảng `sinh_vien`
--
ALTER TABLE `sinh_vien`
  ADD CONSTRAINT `FK_SINH_VIEN_THUOC_VE_LOP` FOREIGN KEY (`LOP_MA`) REFERENCES `lop` (`LOP_MA`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `thanh_vien_hoi_dong`
--
ALTER TABLE `thanh_vien_hoi_dong`
  ADD CONSTRAINT `FK_THANH_VI_CO_VAI_TR_QUYET_DI` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_THANH_VI_DANH_GIA__TIEU_CHI` FOREIGN KEY (`TC_MATC`) REFERENCES `tieu_chi` (`TC_MATC`),
  ADD CONSTRAINT `FK_THANH_VI_LA_GIANG_VI` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`);

--
-- Các ràng buộc cho bảng `tien_do_de_tai`
--
ALTER TABLE `tien_do_de_tai`
  ADD CONSTRAINT `FK_TIEN_DO_DETAI` FOREIGN KEY (`DT_MADT`) REFERENCES `de_tai_nghien_cuu` (`DT_MADT`),
  ADD CONSTRAINT `FK_TIEN_DO_SINHVIEN` FOREIGN KEY (`SV_MASV`) REFERENCES `sinh_vien` (`SV_MASV`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
