-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 08, 2025 lúc 05:41 PM
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
-- Cấu trúc bảng cho bảng `bao_cao`
--

CREATE TABLE `bao_cao` (
  `BC_MABC` char(10) NOT NULL,
  `BC_TENBC` varchar(255) NOT NULL,
  `BC_DUONGDAN` varchar(255) DEFAULT NULL,
  `BC_MOTA` text DEFAULT NULL,
  `BC_NGAYNOP` datetime NOT NULL,
  `BC_TRANGTHAI` varchar(20) NOT NULL DEFAULT 'Chờ duyệt',
  `BC_GHICHU` text DEFAULT NULL,
  `BC_DIEMSO` decimal(3,1) DEFAULT NULL,
  `DT_MADT` char(10) NOT NULL,
  `SV_MASV` char(8) NOT NULL,
  `LBC_MALOAI` char(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bien_ban`
--

CREATE TABLE `bien_ban` (
  `BB_SOBB` char(10) NOT NULL,
  `QD_SO` char(5) NOT NULL,
  `BB_NGAYNGHIEMTHU` date NOT NULL,
  `BB_XEPLOAI` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Cấu trúc bảng cho bảng `de_tai_nghien_cuu`
--

CREATE TABLE `de_tai_nghien_cuu` (
  `DT_MADT` char(10) NOT NULL,
  `LDT_MA` char(5) NOT NULL,
  `GV_MAGV` char(8) NOT NULL,
  `LVNC_MA` char(5) NOT NULL,
  `QD_SO` char(5) NOT NULL,
  `LVUT_MA` char(5) NOT NULL,
  `HD_MA` char(5) NOT NULL,
  `DT_TENDT` varchar(200) NOT NULL,
  `DT_MOTA` text NOT NULL,
  `DT_TRANGTHAI` enum('Chờ duyệt','Đang thực hiện','Đã hoàn thành','Tạm dừng','Đã hủy','Đang xử lý') NOT NULL DEFAULT 'Chờ duyệt',
  `DT_FILEBTM` varchar(255) DEFAULT NULL,
  `DT_NGAYTAO` datetime NOT NULL DEFAULT current_timestamp(),
  `DT_SLSV` int(11) NOT NULL DEFAULT 3 COMMENT 'Số lượng sinh viên tham gia đề tài'
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
-- Cấu trúc bảng cho bảng `file_danh_gia`
--

CREATE TABLE `file_danh_gia` (
  `FDG_MA` char(10) NOT NULL,
  `BB_SOBB` char(10) NOT NULL,
  `FDG_TEN` varchar(255) NOT NULL,
  `FDG_NGAYCAP` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giang_vien`
--

CREATE TABLE `giang_vien` (
  `GV_MAGV` char(8) NOT NULL,
  `DV_MADV` char(5) NOT NULL,
  `GV_HOGV` varchar(8) NOT NULL,
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
  `HD_MA` char(5) NOT NULL,
  `DT_MADT` char(10) NOT NULL,
  `HD_NGAYTAO` date NOT NULL,
  `HD_NGAYBD` date NOT NULL,
  `HD_NGAYKT` date NOT NULL,
  `HD_GHICHU` text DEFAULT NULL,
  `HD_TONGKINHPHI` decimal(10,2) NOT NULL,
  `HD_FILEHD` varchar(255) DEFAULT NULL
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
-- Cấu trúc bảng cho bảng `nguon_kinh_phi`
--

CREATE TABLE `nguon_kinh_phi` (
  `NKP_MA` char(5) NOT NULL,
  `HD_MA` char(5) NOT NULL,
  `NKP_TENNGUON` varchar(50) NOT NULL,
  `NKP_SOTIEN` decimal(10,2) NOT NULL
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
  `QD_SO` char(5) NOT NULL,
  `BB_SOBB` char(10) NOT NULL,
  `QD_NGAY` date NOT NULL,
  `QD_FILE` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sinh_vien`
--

CREATE TABLE `sinh_vien` (
  `SV_MASV` char(8) NOT NULL,
  `LOP_MA` char(8) NOT NULL,
  `SV_HOSV` varchar(8) NOT NULL,
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
  `QD_SO` char(5) NOT NULL,
  `GV_MAGV` char(8) NOT NULL,
  `TC_MATC` char(5) NOT NULL,
  `TV_VAITRO` varchar(30) NOT NULL,
  `TV_DIEM` int(11) NOT NULL,
  `TV_DANHGIA` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `QL_MA` char(8) DEFAULT NULL
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
  `TC_NDDANHGIA` text NOT NULL,
  `TC_DIEMTOIDA` decimal(3,0) NOT NULL
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
-- Cấu trúc bảng cho bảng `yeu_cau_dang_ky`
--

CREATE TABLE `yeu_cau_dang_ky` (
  `YC_MA` int(11) NOT NULL,
  `DT_MADT` char(10) NOT NULL,
  `SV_MASV` char(8) NOT NULL,
  `GV_MAGV` char(8) NOT NULL,
  `YC_NOIDUNG` text DEFAULT NULL,
  `YC_NGAYYEUCAU` datetime NOT NULL,
  `YC_TRANGTHAI` varchar(50) NOT NULL DEFAULT 'Đang chờ duyệt',
  `YC_GHICHU` text DEFAULT NULL,
  `YC_NGAYDUYET` datetime DEFAULT NULL,
  `HK_MA` char(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bao_cao`
--
ALTER TABLE `bao_cao`
  ADD PRIMARY KEY (`BC_MABC`),
  ADD KEY `FK_BAO_CAO_CUA_DE_TAI` (`DT_MADT`),
  ADD KEY `FK_BAO_CAO_CUA_SINH_VIEN` (`SV_MASV`),
  ADD KEY `FK_BAO_CAO_LOAI_BAO_CAO` (`LBC_MALOAI`);

--
-- Chỉ mục cho bảng `bien_ban`
--
ALTER TABLE `bien_ban`
  ADD PRIMARY KEY (`BB_SOBB`),
  ADD KEY `FK_BIEN_BAN_CO_BIEN_B_QUYET_DI` (`QD_SO`);

--
-- Chỉ mục cho bảng `chi_tiet_tham_gia`
--
ALTER TABLE `chi_tiet_tham_gia`
  ADD PRIMARY KEY (`SV_MASV`,`DT_MADT`,`HK_MA`),
  ADD KEY `FK_CHI_TIET_RELATIONS_HOC_KI` (`HK_MA`),
  ADD KEY `FK_CHI_TIET_RELATIONS_DE_TAI_N` (`DT_MADT`);

--
-- Chỉ mục cho bảng `de_tai_nghien_cuu`
--
ALTER TABLE `de_tai_nghien_cuu`
  ADD PRIMARY KEY (`DT_MADT`),
  ADD KEY `FK_DE_TAI_N_CO_LINH_VUC` (`LVNC_MA`),
  ADD KEY `FK_DE_TAI_N_CO__1__LINH_VUC` (`LVUT_MA`),
  ADD KEY `FK_DE_TAI_N_RELATIONS_GIANG_VI` (`GV_MAGV`),
  ADD KEY `FK_DE_TAI_N_RELATIONS_QUYET_DI` (`QD_SO`),
  ADD KEY `FK_DE_TAI_N_THUOC_LOAI_DE_` (`LDT_MA`);

--
-- Chỉ mục cho bảng `file_danh_gia`
--
ALTER TABLE `file_danh_gia`
  ADD PRIMARY KEY (`FDG_MA`),
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
-- Chỉ mục cho bảng `nguon_kinh_phi`
--
ALTER TABLE `nguon_kinh_phi`
  ADD PRIMARY KEY (`NKP_MA`),
  ADD KEY `FK_NGUON_KI_CO_TU_HOP_DONG` (`HD_MA`);

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
  ADD KEY `FK_THANH_VI_LA_GIANG_VI` (`GV_MAGV`);

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
-- Chỉ mục cho bảng `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`USER_ID`),
  ADD UNIQUE KEY `USERNAME` (`USERNAME`);

--
-- Chỉ mục cho bảng `yeu_cau_dang_ky`
--
ALTER TABLE `yeu_cau_dang_ky`
  ADD PRIMARY KEY (`YC_MA`),
  ADD KEY `DT_MADT` (`DT_MADT`),
  ADD KEY `SV_MASV` (`SV_MASV`),
  ADD KEY `GV_MAGV` (`GV_MAGV`),
  ADD KEY `FK_YEU_CAU_HOCKI` (`HK_MA`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

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
-- AUTO_INCREMENT cho bảng `yeu_cau_dang_ky`
--
ALTER TABLE `yeu_cau_dang_ky`
  MODIFY `YC_MA` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bao_cao`
--
ALTER TABLE `bao_cao`
  ADD CONSTRAINT `FK_BAO_CAO_CUA_DE_TAI` FOREIGN KEY (`DT_MADT`) REFERENCES `de_tai_nghien_cuu` (`DT_MADT`),
  ADD CONSTRAINT `FK_BAO_CAO_CUA_SINH_VIEN` FOREIGN KEY (`SV_MASV`) REFERENCES `sinh_vien` (`SV_MASV`),
  ADD CONSTRAINT `FK_BAO_CAO_LOAI_BAO_CAO` FOREIGN KEY (`LBC_MALOAI`) REFERENCES `loai_bao_cao` (`LBC_MALOAI`);

--
-- Các ràng buộc cho bảng `bien_ban`
--
ALTER TABLE `bien_ban`
  ADD CONSTRAINT `FK_BIEN_BAN_CO_BIEN_B_QUYET_DI` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`);

--
-- Các ràng buộc cho bảng `chi_tiet_tham_gia`
--
ALTER TABLE `chi_tiet_tham_gia`
  ADD CONSTRAINT `FK_CHI_TIET_RELATIONS_DE_TAI_N` FOREIGN KEY (`DT_MADT`) REFERENCES `de_tai_nghien_cuu` (`DT_MADT`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_CHI_TIET_RELATIONS_HOC_KI` FOREIGN KEY (`HK_MA`) REFERENCES `hoc_ki` (`HK_MA`),
  ADD CONSTRAINT `FK_CHI_TIET_RELATIONS_SINH_VIE` FOREIGN KEY (`SV_MASV`) REFERENCES `sinh_vien` (`SV_MASV`);

--
-- Các ràng buộc cho bảng `de_tai_nghien_cuu`
--
ALTER TABLE `de_tai_nghien_cuu`
  ADD CONSTRAINT `FK_DE_TAI_N_CO_LINH_VUC` FOREIGN KEY (`LVNC_MA`) REFERENCES `linh_vuc_nghien_cuu` (`LVNC_MA`),
  ADD CONSTRAINT `FK_DE_TAI_N_CO__1__LINH_VUC` FOREIGN KEY (`LVUT_MA`) REFERENCES `linh_vuc_uu_tien` (`LVUT_MA`),
  ADD CONSTRAINT `FK_DE_TAI_N_RELATIONS_GIANG_VI` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`),
  ADD CONSTRAINT `FK_DE_TAI_N_RELATIONS_QUYET_DI` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`),
  ADD CONSTRAINT `FK_DE_TAI_N_THUOC_LOAI_DE_` FOREIGN KEY (`LDT_MA`) REFERENCES `loai_de_tai` (`LDT_MA`);

--
-- Các ràng buộc cho bảng `file_danh_gia`
--
ALTER TABLE `file_danh_gia`
  ADD CONSTRAINT `FK_FILE_DAN_CUA_BIEN_BAN` FOREIGN KEY (`BB_SOBB`) REFERENCES `bien_ban` (`BB_SOBB`);

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
-- Các ràng buộc cho bảng `nguon_kinh_phi`
--
ALTER TABLE `nguon_kinh_phi`
  ADD CONSTRAINT `FK_NGUON_KI_CO_TU_HOP_DONG` FOREIGN KEY (`HD_MA`) REFERENCES `hop_dong` (`HD_MA`);

--
-- Các ràng buộc cho bảng `quan_ly_nghien_cuu`
--
ALTER TABLE `quan_ly_nghien_cuu`
  ADD CONSTRAINT `FK_QLNC_THUOC_VE_KHOA` FOREIGN KEY (`DV_MADV`) REFERENCES `khoa` (`DV_MADV`);

--
-- Các ràng buộc cho bảng `sinh_vien`
--
ALTER TABLE `sinh_vien`
  ADD CONSTRAINT `FK_SINH_VIEN_THUOC_VE_LOP` FOREIGN KEY (`LOP_MA`) REFERENCES `lop` (`LOP_MA`);

--
-- Các ràng buộc cho bảng `thanh_vien_hoi_dong`
--
ALTER TABLE `thanh_vien_hoi_dong`
  ADD CONSTRAINT `FK_THANH_VI_CO_VAI_TR_QUYET_DI` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`),
  ADD CONSTRAINT `FK_THANH_VI_DANH_GIA__TIEU_CHI` FOREIGN KEY (`TC_MATC`) REFERENCES `tieu_chi` (`TC_MATC`),
  ADD CONSTRAINT `FK_THANH_VI_LA_GIANG_VI` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`);

--
-- Các ràng buộc cho bảng `tien_do_de_tai`
--
ALTER TABLE `tien_do_de_tai`
  ADD CONSTRAINT `FK_TIEN_DO_DETAI` FOREIGN KEY (`DT_MADT`) REFERENCES `de_tai_nghien_cuu` (`DT_MADT`),
  ADD CONSTRAINT `FK_TIEN_DO_SINHVIEN` FOREIGN KEY (`SV_MASV`) REFERENCES `sinh_vien` (`SV_MASV`);

--
-- Các ràng buộc cho bảng `yeu_cau_dang_ky`
--
ALTER TABLE `yeu_cau_dang_ky`
  ADD CONSTRAINT `FK_YEU_CAU_DETAI` FOREIGN KEY (`DT_MADT`) REFERENCES `de_tai_nghien_cuu` (`DT_MADT`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_YEU_CAU_GIANGVIEN` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`),
  ADD CONSTRAINT `FK_YEU_CAU_HOCKI` FOREIGN KEY (`HK_MA`) REFERENCES `hoc_ki` (`HK_MA`),
  ADD CONSTRAINT `FK_YEU_CAU_SINHVIEN` FOREIGN KEY (`SV_MASV`) REFERENCES `sinh_vien` (`SV_MASV`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
