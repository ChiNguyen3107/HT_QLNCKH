-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th4 14, 2025 lúc 05:51 PM
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
  `DT_FILEBTM` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `de_tai_nghien_cuu`
--

INSERT INTO `de_tai_nghien_cuu` (`DT_MADT`, `LDT_MA`, `GV_MAGV`, `LVNC_MA`, `QD_SO`, `LVUT_MA`, `HD_MA`, `DT_TENDT`, `DT_MOTA`, `DT_TRANGTHAI`, `DT_FILEBTM`) VALUES
('DT0000011', 'DT001', 'GV000001', 'L0002', 'QD011', 'LV004', 'HD011', 'Nghiên cứu phát triển hệ thống điều khiển tự động hóa trong sản xuất', 'Nghiên cứu và phát triển hệ thống điều khiển tự động hóa ứng dụng trong các nhà máy sản xuất.', 'Tạm dừng', 'file11.pdf'),
('DT0000012', 'DT002', 'GV000001', 'L0002', 'QD012', 'LV004', 'HD012', 'Ứng dụng AI trong nhận diện hình ảnh y tế', 'Nghiên cứu ứng dụng trí tuệ nhân tạo để nhận diện và chẩn đoán hình ảnh y tế.', 'Đã hoàn thành', 'file12.pdf'),
('DT0000013', 'DT003', 'GV000001', 'L0002', 'QD013', 'LV004', 'HD013', 'Phát triển hệ thống quản lý năng lượng thông minh', 'Nghiên cứu và phát triển hệ thống quản lý năng lượng thông minh cho các tòa nhà và khu công nghiệp.', 'Đang thực hiện', 'file13.pdf'),
('DT0000014', 'DT004', 'GV000001', 'L0002', 'QD014', 'LV004', 'HD014', 'Nghiên cứu công nghệ in 3D trong sản xuất công nghiệp', 'Nghiên cứu ứng dụng công nghệ in 3D để tối ưu hóa quy trình sản xuất công nghiệp.', 'Đã hoàn thành', 'file14.pdf'),
('DT0000015', 'DT005', 'GV000001', 'L0002', 'QD015', 'LV004', 'HD015', 'Phát triển hệ thống IoT cho nông nghiệp thông minh', 'Nghiên cứu và phát triển hệ thống IoT để quản lý và tối ưu hóa sản xuất nông nghiệp.', 'Đang thực hiện', 'file15.pdf'),
('DT0000016', 'DT006', 'GV000001', 'L0002', 'QD016', 'LV004', 'HD016', 'Nghiên cứu ứng dụng blockchain trong quản lý chuỗi cung ứng', 'Nghiên cứu ứng dụng công nghệ blockchain để quản lý chuỗi cung ứng hiệu quả hơn.', 'Đã hoàn thành', 'file16.pdf'),
('DT0000017', 'DT007', 'GV000001', 'L0002', 'QD017', 'LV004', 'HD017', 'Nghiên cứu phát triển robot tự hành trong công nghiệp', 'Nghiên cứu và phát triển robot tự hành ứng dụng trong các nhà máy sản xuất.', 'Đang thực hiện', 'file17.pdf'),
('DT0000018', 'DT008', 'GV000001', 'L0002', 'QD018', 'LV004', 'HD018', 'Nghiên cứu công nghệ xử lý nước thải bằng phương pháp sinh học', 'Nghiên cứu ứng dụng công nghệ sinh học để xử lý nước thải công nghiệp.', 'Đã hoàn thành', 'file18.pdf'),
('DT0000019', 'DT009', 'GV000001', 'L0002', 'QD019', 'LV004', 'HD019', 'Phát triển hệ thống quản lý giao thông thông minh', 'Nghiên cứu và phát triển hệ thống quản lý giao thông thông minh dựa trên AI và IoT.', 'Đang thực hiện', 'file19.pdf'),
('DT0000020', 'DT010', 'GV000001', 'L0002', 'QD020', 'LV004', 'HD020', 'Nghiên cứu công nghệ vật liệu mới trong sản xuất ô tô', 'Nghiên cứu và phát triển vật liệu mới ứng dụng trong sản xuất ô tô để giảm trọng lượng và tiết kiệm nhiên liệu.', 'Đã hoàn thành', 'file20.pdf');

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
  `GV_MATKHAU` varchar(255) NOT NULL,
  `GV_NGAYSINH` date DEFAULT NULL,
  `GV_DIACHI` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `giang_vien`
--

INSERT INTO `giang_vien` (`GV_MAGV`, `DV_MADV`, `GV_HOGV`, `GV_TENGV`, `GV_EMAIL`, `GV_MATKHAU`, `GV_NGAYSINH`, `GV_DIACHI`) VALUES
('GV000001', 'KH011', 'Nguyen', 'Van A', 'nguyenvana@example.com', '$2y$10$sJxxdAudHUMzhzKEjzLXm.2jvIsNfBU0wejyfHvIOBxgpJ/CGnIdS', NULL, NULL);

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

--
-- Đang đổ dữ liệu cho bảng `hoc_ki`
--

INSERT INTO `hoc_ki` (`HK_MA`, `NK_NAM`, `HK_TEN`, `HK_NGAYBD`, `HK_NGAYKT`) VALUES
('HK1-2020', '2020-2021', 'Học kỳ 1 năm học 2020-2021', NULL, NULL),
('HK1-2021', '2021-2022', 'Học kỳ 1 năm học 2021-2022', NULL, NULL),
('HK1-2022', '2022-2023', 'Học kỳ 1 năm học 2022-2023', NULL, NULL),
('HK1-2023', '2023-2024', 'Học kỳ 1 năm học 2023-2024', NULL, NULL),
('HK1-2024', '2024-2025', 'Học kỳ 1 năm học 2024-2025', NULL, NULL),
('HK2-2020', '2020-2021', 'Học kỳ 2 năm học 2020-2021', NULL, NULL),
('HK2-2021', '2021-2022', 'Học kỳ 2 năm học 2021-2022', NULL, NULL),
('HK2-2022', '2022-2023', 'Học kỳ 2 năm học 2022-2023', NULL, NULL),
('HK2-2023', '2023-2024', 'Học kỳ 2 năm học 2023-2024', NULL, NULL),
('HK2-2024', '2024-2025', 'Học kỳ 2 năm học 2024-2025', NULL, NULL),
('HK3-2020', '2020-2021', 'Học kỳ Hè năm học 2020-2021', NULL, NULL),
('HK3-2021', '2021-2022', 'Học kỳ Hè năm học 2021-2022', NULL, NULL),
('HK3-2022', '2022-2023', 'Học kỳ Hè năm học 2022-2023', NULL, NULL),
('HK3-2023', '2023-2024', 'Học kỳ Hè năm học 2023-2024', NULL, NULL),
('HK3-2024', '2024-2025', 'Học kỳ Hè năm học 2024-2025', NULL, NULL);

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

--
-- Đang đổ dữ liệu cho bảng `hop_dong`
--

INSERT INTO `hop_dong` (`HD_MA`, `DT_MADT`, `HD_NGAYTAO`, `HD_NGAYBD`, `HD_NGAYKT`, `HD_GHICHU`, `HD_TONGKINHPHI`, `HD_FILEHD`) VALUES
('HD011', 'DT0000011', '2023-10-01', '2023-10-01', '2024-10-01', 'Hợp đồng cho đề tài DT0000011', 1000000.00, 'hd011.pdf'),
('HD012', 'DT0000012', '2023-10-02', '2023-10-02', '2024-10-02', 'Hợp đồng cho đề tài DT0000012', 1500000.00, 'hd012.pdf'),
('HD013', 'DT0000013', '2023-10-03', '2023-10-03', '2024-10-03', 'Hợp đồng cho đề tài DT0000013', 2000000.00, 'hd013.pdf'),
('HD014', 'DT0000014', '2023-10-04', '2023-10-04', '2024-10-04', 'Hợp đồng cho đề tài DT0000014', 2500000.00, 'hd014.pdf'),
('HD015', 'DT0000015', '2023-10-05', '2023-10-05', '2024-10-05', 'Hợp đồng cho đề tài DT0000015', 3000000.00, 'hd015.pdf');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoa`
--

CREATE TABLE `khoa` (
  `DV_MADV` char(5) NOT NULL,
  `DV_TENDV` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khoa`
--

INSERT INTO `khoa` (`DV_MADV`, `DV_TENDV`) VALUES
('KH001', 'Khoa Khoa học Chính trị'),
('KH002', 'Khoa Giáo dục thể chất'),
('KH003', 'Khoa Khoa học Tự nhiên'),
('KH004', 'Khoa Khoa học Xã hội và Nhân văn'),
('KH005', 'Khoa Luật'),
('KH006', 'Khoa Môi trường và Tài nguyên thiên nhiên'),
('KH007', 'Khoa Ngoại ngữ'),
('KH008', 'Khoa Phát triển Nông thôn'),
('KH009', 'Khoa Sư phạm'),
('KH010', 'Trường Bách khoa'),
('KH011', 'Trường Công nghệ thông tin và truyền thông'),
('KH012', 'Trường Kinh tế'),
('KH013', 'Trường Nông nghiệp'),
('KH014', 'Trường Thủy sản'),
('KH015', 'Viện Công nghệ sinh học và thực phẩm');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoa_hoc`
--

CREATE TABLE `khoa_hoc` (
  `KH_NAM` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khoa_hoc`
--

INSERT INTO `khoa_hoc` (`KH_NAM`) VALUES
('Khóa 39'),
('Khóa 40'),
('Khóa 41'),
('Khóa 42'),
('Khóa 43'),
('Khóa 44'),
('Khóa 45'),
('Khóa 46'),
('Khóa 47'),
('Khóa 48'),
('Khóa 49'),
('Khóa 50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `linh_vuc_nghien_cuu`
--

CREATE TABLE `linh_vuc_nghien_cuu` (
  `LVNC_MA` char(5) NOT NULL,
  `LVNC_TEN` varchar(50) NOT NULL,
  `LVNC_MOTA` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `linh_vuc_nghien_cuu`
--

INSERT INTO `linh_vuc_nghien_cuu` (`LVNC_MA`, `LVNC_TEN`, `LVNC_MOTA`) VALUES
('L0001', 'Khoa học Tự nhiên', 'Nghiên cứu về các lĩnh vực khoa học tự nhiên như vật lý, hóa học, sinh học'),
('L0002', 'Khoa học Kỹ thuật và Công nghệ', 'Nghiên cứu và phát triển các công nghệ và kỹ thuật mới'),
('L0003', 'Khoa học Y, dược', 'Nghiên cứu về y học, dược phẩm và các vấn đề sức khỏe'),
('L0004', 'Khoa học Nông nghiệp', 'Nghiên cứu về nông nghiệp, thủy sản và phát triển nông thôn'),
('L0005', 'Khoa học Xã hội', 'Nghiên cứu về các vấn đề xã hội, kinh tế và chính trị'),
('L0006', 'Khoa học Nhân văn', 'Nghiên cứu về văn hóa, lịch sử, ngôn ngữ và các vấn đề nhân văn khác');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `linh_vuc_uu_tien`
--

CREATE TABLE `linh_vuc_uu_tien` (
  `LVUT_MA` char(5) NOT NULL,
  `LVUT_TEN` varchar(255) NOT NULL,
  `LVUT_MOTA` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `linh_vuc_uu_tien`
--

INSERT INTO `linh_vuc_uu_tien` (`LVUT_MA`, `LVUT_TEN`, `LVUT_MOTA`) VALUES
('LV001', 'Khoa học cơ bản', 'Các nghiên cứu cơ bản trong khoa học tự nhiên và xã hội'),
('LV002', 'Công nghệ cao trong nông nghiệp, thủy sản và phát triển bền vững', 'Ứng dụng công nghệ cao trong nông nghiệp và thủy sản để phát triển bền vững'),
('LV003', 'Môi trường, tài nguyên thiên nhiên và biến đổi khí hậu', 'Nghiên cứu về môi trường, quản lý tài nguyên thiên nhiên và ứng phó với biến đổi khí hậu'),
('LV004', 'Công nghệ, công nghệ thông tin và chuyển đổi số', 'Phát triển và ứng dụng công nghệ thông tin và chuyển đổi số trong các lĩnh vực khác nhau'),
('LV005', 'Khoa học giáo dục, luật và xã hội nhân văn', 'Nghiên cứu về giáo dục, luật pháp và các vấn đề xã hội nhân văn'),
('LV006', 'Phát triển kinh tế, thị trường và nông thôn', 'Nghiên cứu về phát triển kinh tế, thị trường và các vấn đề nông thôn'),
('LV007', 'Công nghệ sinh học và thực phẩm', 'Nghiên cứu và ứng dụng công nghệ sinh học trong sản xuất thực phẩm'),
('LV008', 'Không thuộc 7 Lĩnh vực ưu tiên', 'Các nghiên cứu không thuộc 7 lĩnh vực ưu tiên kể trên');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_bao_cao`
--

CREATE TABLE `loai_bao_cao` (
  `LBC_MALOAI` char(5) NOT NULL,
  `LBC_TENLOAI` varchar(50) NOT NULL,
  `LBC_MOTA` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `loai_bao_cao`
--

INSERT INTO `loai_bao_cao` (`LBC_MALOAI`, `LBC_TENLOAI`, `LBC_MOTA`) VALUES
('LBC01', 'Báo cáo tiến độ', 'Báo cáo tiến độ định kỳ'),
('LBC02', 'Báo cáo cuối kỳ', 'Báo cáo tổng kết cuối kỳ'),
('LBC03', 'Báo cáo đột xuất', 'Báo cáo theo yêu cầu của GVHD');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_de_tai`
--

CREATE TABLE `loai_de_tai` (
  `LDT_MA` char(5) NOT NULL,
  `LDT_TENLOAI` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `loai_de_tai`
--

INSERT INTO `loai_de_tai` (`LDT_MA`, `LDT_TENLOAI`) VALUES
('DT001', 'Đề tài cấp nhà nước'),
('DT002', 'Đề tài cấp bộ'),
('DT003', 'Đề tài cấp cơ sở'),
('DT004', 'Đề tài hợp tác địa phương/ doanh nghiệp'),
('DT005', 'Đề tài hợp tác doanh nghiệp'),
('DT006', 'Đề tài sinh viên'),
('DT007', 'Đề tài nghị định thư'),
('DT008', 'Đề tài cấp huyện'),
('DT009', 'Đề tài hợp tác quốc tế'),
('DT010', 'Đề tài hợp tác địa phương'),
('DT011', 'Đề tài nghiên cứu sinh'),
('DT012', 'Chương trình cấp cơ sở');

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

--
-- Đang đổ dữ liệu cho bảng `lop`
--

INSERT INTO `lop` (`LOP_MA`, `DV_MADV`, `KH_NAM`, `LOP_TEN`, `LOP_LOAICTDT`) VALUES
('ATI46001', 'KH011', 'Khóa 46', 'An toàn thông tin', 'Đại học chính quy'),
('ATI47001', 'KH011', 'Khóa 47', 'An toàn thông tin', 'Đại học chính quy'),
('ATI48001', 'KH011', 'Khóa 48', 'An toàn thông tin', 'Đại học chính quy'),
('BAO46001', 'KH004', 'Khóa 46', 'Báo chí', 'Đại học chính quy'),
('BAO47001', 'KH004', 'Khóa 47', 'Báo chí', 'Đại học chính quy'),
('BAO48001', 'KH004', 'Khóa 48', 'Báo chí', 'Đại học chính quy'),
('BHT46010', 'KH014', 'Khóa 46', 'Bệnh học thủy sản', 'Đại học chính quy'),
('BHT47010', 'KH014', 'Khóa 47', 'Bệnh học thủy sản', 'Đại học chính quy'),
('BHT48010', 'KH014', 'Khóa 48', 'Bệnh học thủy sản', 'Đại học chính quy'),
('BVT46001', 'KH013', 'Khóa 46', 'Bảo vệ thực vật', 'Đại học chính quy'),
('BVT47001', 'KH013', 'Khóa 47', 'Bảo vệ thực vật', 'Đại học chính quy'),
('BVT48001', 'KH013', 'Khóa 48', 'Bảo vệ thực vật', 'Đại học chính quy'),
('CBT46011', 'KH014', 'Khóa 46', 'Công nghệ chế biến thủy sản', 'Đại học chính quy'),
('CBT47011', 'KH014', 'Khóa 47', 'Công nghệ chế biến thủy sản', 'Đại học chính quy'),
('CBT48011', 'KH014', 'Khóa 48', 'Công nghệ chế biến thủy sản', 'Đại học chính quy'),
('CDT46005', 'KH010', 'Khóa 46', 'Kỹ thuật cơ điện tử', 'Đại học chính quy'),
('CDT47005', 'KH010', 'Khóa 47', 'Kỹ thuật cơ điện tử', 'Đại học chính quy'),
('CDT48005', 'KH010', 'Khóa 48', 'Kỹ thuật cơ điện tử', 'Đại học chính quy'),
('CKC46002', 'KH010', 'Khóa 46', 'Công nghệ kỹ thuật hóa học (chương trình chất lượn', 'Đại học chất lượng cao'),
('CKC47002', 'KH010', 'Khóa 47', 'Công nghệ kỹ thuật hóa học (chương trình chất lượn', 'Đại học chất lượng cao'),
('CKC48002', 'KH010', 'Khóa 48', 'Công nghệ kỹ thuật hóa học (chương trình chất lượn', 'Đại học chất lượng cao'),
('CKH46001', 'KH010', 'Khóa 46', 'Công nghệ kỹ thuật hóa học', 'Đại học chính quy'),
('CKH47001', 'KH010', 'Khóa 47', 'Công nghệ kỹ thuật hóa học', 'Đại học chính quy'),
('CKH48001', 'KH010', 'Khóa 48', 'Công nghệ kỹ thuật hóa học', 'Đại học chính quy'),
('CKM46004', 'KH010', 'Khóa 46', 'Kỹ thuật cơ khí - Cơ khí chế tạo máy', 'Đại học chính quy'),
('CKM47004', 'KH010', 'Khóa 47', 'Kỹ thuật cơ khí - Cơ khí chế tạo máy', 'Đại học chính quy'),
('CKM48004', 'KH010', 'Khóa 48', 'Kỹ thuật cơ khí - Cơ khí chế tạo máy', 'Đại học chính quy'),
('CNC46003', 'KH011', 'Khóa 46', 'Công nghệ thông tin (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('CNC47003', 'KH011', 'Khóa 47', 'Công nghệ thông tin (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('CNC48003', 'KH011', 'Khóa 48', 'Công nghệ thông tin (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('CNT46002', 'KH011', 'Khóa 46', 'Công nghệ thông tin', 'Đại học chính quy'),
('CNT47002', 'KH011', 'Khóa 47', 'Công nghệ thông tin', 'Đại học chính quy'),
('CNT48002', 'KH011', 'Khóa 48', 'Công nghệ thông tin', 'Đại học chính quy'),
('CNU46002', 'KH013', 'Khóa 46', 'Chăn nuôi', 'Đại học chính quy'),
('CNU47002', 'KH013', 'Khóa 47', 'Chăn nuôi', 'Đại học chính quy'),
('CNU48002', 'KH013', 'Khóa 48', 'Chăn nuôi', 'Đại học chính quy'),
('CRQ46003', 'KH013', 'Khóa 46', 'Công nghệ rau hoa quả và cảnh quan', 'Đại học chính quy'),
('CRQ47003', 'KH013', 'Khóa 47', 'Công nghệ rau hoa quả và cảnh quan', 'Đại học chính quy'),
('CRQ48003', 'KH013', 'Khóa 48', 'Công nghệ rau hoa quả và cảnh quan', 'Đại học chính quy'),
('CSH46016', 'KH015', 'Khóa 46', 'Công nghệ sinh học', 'Đại học chính quy'),
('CSH47016', 'KH015', 'Khóa 47', 'Công nghệ sinh học', 'Đại học chính quy'),
('CSH48016', 'KH015', 'Khóa 48', 'Công nghệ sinh học', 'Đại học chính quy'),
('CST46015', 'KH015', 'Khóa 46', 'Công nghệ sau thu hoạch', 'Đại học chính quy'),
('CST46017', 'KH015', 'Khóa 46', 'Công nghệ sinh học (chương trình tiên tiến)', 'Đại học tiên tiến'),
('CST47015', 'KH015', 'Khóa 47', 'Công nghệ sau thu hoạch', 'Đại học chính quy'),
('CST47017', 'KH015', 'Khóa 47', 'Công nghệ sinh học (chương trình tiên tiến)', 'Đại học tiên tiến'),
('CST48015', 'KH015', 'Khóa 48', 'Công nghệ sau thu hoạch', 'Đại học chính quy'),
('CST48017', 'KH015', 'Khóa 48', 'Công nghệ sinh học (chương trình tiên tiến)', 'Đại học tiên tiến'),
('CTC46019', 'KH015', 'Khóa 46', 'Công nghệ thực phẩm (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('CTC47019', 'KH015', 'Khóa 47', 'Công nghệ thực phẩm (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('CTC48019', 'KH015', 'Khóa 48', 'Công nghệ thực phẩm (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('CTH46001', 'KH001', 'Khóa 46', 'Chính trị học', 'Đại học chính quy'),
('CTH47001', 'KH001', 'Khóa 47', 'Chính trị học', 'Đại học chính quy'),
('CTH48001', 'KH001', 'Khóa 48', 'Chính trị học', 'Đại học chính quy'),
('CTP46018', 'KH015', 'Khóa 46', 'Công nghệ thực phẩm', 'Đại học chính quy'),
('CTP47018', 'KH015', 'Khóa 47', 'Công nghệ thực phẩm', 'Đại học chính quy'),
('CTP48018', 'KH015', 'Khóa 48', 'Công nghệ thực phẩm', 'Đại học chính quy'),
('DLH46002', 'KH004', 'Khóa 46', 'Du lịch', 'Đại học chính quy'),
('DLH47002', 'KH004', 'Khóa 47', 'Du lịch', 'Đại học chính quy'),
('DLH48002', 'KH004', 'Khóa 48', 'Du lịch', 'Đại học chính quy'),
('GDC46002', 'KH001', 'Khóa 46', 'Giáo dục Công dân', 'Đại học chính quy'),
('GDC47002', 'KH001', 'Khóa 47', 'Giáo dục Công dân', 'Đại học chính quy'),
('GDC48002', 'KH001', 'Khóa 48', 'Giáo dục Công dân', 'Đại học chính quy'),
('GDM46001', 'KH009', 'Khóa 46', 'Giáo dục Mầm non', 'Đại học chính quy'),
('GDM47001', 'KH009', 'Khóa 47', 'Giáo dục Mầm non', 'Đại học chính quy'),
('GDM48001', 'KH009', 'Khóa 48', 'Giáo dục Mầm non', 'Đại học chính quy'),
('GDT46002', 'KH009', 'Khóa 46', 'Giáo dục Tiểu học', 'Đại học chính quy'),
('GDT47002', 'KH009', 'Khóa 47', 'Giáo dục Tiểu học', 'Đại học chính quy'),
('GDT48002', 'KH009', 'Khóa 48', 'Giáo dục Tiểu học', 'Đại học chính quy'),
('HDH46001', 'KH003', 'Khóa 46', 'Hóa dược', 'Đại học chính quy'),
('HDH47001', 'KH003', 'Khóa 47', 'Hóa dược', 'Đại học chính quy'),
('HDH48001', 'KH003', 'Khóa 48', 'Hóa dược', 'Đại học chính quy'),
('HOC46002', 'KH003', 'Khóa 46', 'Hóa học', 'Đại học chính quy'),
('HOC47002', 'KH003', 'Khóa 47', 'Hóa học', 'Đại học chính quy'),
('HOC48002', 'KH003', 'Khóa 48', 'Hóa học', 'Đại học chính quy'),
('HTC46005', 'KH011', 'Khóa 46', 'Hệ thống thông tin (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('HTC47005', 'KH011', 'Khóa 47', 'Hệ thống thông tin (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('HTC48005', 'KH011', 'Khóa 48', 'Hệ thống thông tin (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('HTT46004', 'KH011', 'Khóa 46', 'Hệ thống thông tin', 'Đại học chính quy'),
('HTT47004', 'KH011', 'Khóa 47', 'Hệ thống thông tin', 'Đại học chính quy'),
('HTT48004', 'KH011', 'Khóa 48', 'Hệ thống thông tin', 'Đại học chính quy'),
('KCT46004', 'KH013', 'Khóa 46', 'Khoa học cây trồng', 'Đại học chính quy'),
('KCT47004', 'KH013', 'Khóa 47', 'Khoa học cây trồng', 'Đại học chính quy'),
('KCT48004', 'KH013', 'Khóa 48', 'Khoa học cây trồng', 'Đại học chính quy'),
('KDC46004', 'KH012', 'Khóa 46', 'Kinh doanh quốc tế (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KDC46015', 'KH010', 'Khóa 46', 'Kỹ thuật điện (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KDC46018', 'KH010', 'Khóa 46', 'Kỹ thuật điều khiển và tự động hóa (chương trình c', 'Đại học chất lượng cao'),
('KDC47004', 'KH012', 'Khóa 47', 'Kinh doanh quốc tế (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KDC47015', 'KH010', 'Khóa 47', 'Kỹ thuật điện (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KDC47018', 'KH010', 'Khóa 47', 'Kỹ thuật điều khiển và tự động hóa (chương trình c', 'Đại học chất lượng cao'),
('KDC48004', 'KH012', 'Khóa 48', 'Kinh doanh quốc tế (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KDC48015', 'KH010', 'Khóa 48', 'Kỹ thuật điện (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KDC48018', 'KH010', 'Khóa 48', 'Kỹ thuật điều khiển và tự động hóa (chương trình c', 'Đại học chất lượng cao'),
('KDN46001', 'KH008', 'Khóa 46', 'Kinh doanh nông nghiệp', 'Đại học chính quy'),
('KDN47001', 'KH008', 'Khóa 47', 'Kinh doanh nông nghiệp', 'Đại học chính quy'),
('KDN48001', 'KH008', 'Khóa 48', 'Kinh doanh nông nghiệp', 'Đại học chính quy'),
('KDQ46003', 'KH012', 'Khóa 46', 'Kinh doanh quốc tế', 'Đại học chính quy'),
('KDQ47003', 'KH012', 'Khóa 47', 'Kinh doanh quốc tế', 'Đại học chính quy'),
('KDQ48003', 'KH012', 'Khóa 48', 'Kinh doanh quốc tế', 'Đại học chính quy'),
('KDT46005', 'KH012', 'Khóa 46', 'Kinh doanh thương mại', 'Đại học chính quy'),
('KDT46006', 'KH013', 'Khóa 46', 'Khoa học đất - Quản lý đất và công nghệ phân bón', 'Đại học chính quy'),
('KDT46014', 'KH010', 'Khóa 46', 'Kỹ thuật điện', 'Đại học chính quy'),
('KDT46017', 'KH010', 'Khóa 46', 'Kỹ thuật điều khiển và tự động hóa', 'Đại học chính quy'),
('KDT47005', 'KH012', 'Khóa 47', 'Kinh doanh thương mại', 'Đại học chính quy'),
('KDT47006', 'KH013', 'Khóa 47', 'Khoa học đất - Quản lý đất và công nghệ phân bón', 'Đại học chính quy'),
('KDT47014', 'KH010', 'Khóa 47', 'Kỹ thuật điện', 'Đại học chính quy'),
('KDT47017', 'KH010', 'Khóa 47', 'Kỹ thuật điều khiển và tự động hóa', 'Đại học chính quy'),
('KDT48005', 'KH012', 'Khóa 48', 'Kinh doanh thương mại', 'Đại học chính quy'),
('KDT48006', 'KH013', 'Khóa 48', 'Khoa học đất - Quản lý đất và công nghệ phân bón', 'Đại học chính quy'),
('KDT48014', 'KH010', 'Khóa 48', 'Kỹ thuật điện', 'Đại học chính quy'),
('KDT48017', 'KH010', 'Khóa 48', 'Kỹ thuật điều khiển và tự động hóa', 'Đại học chính quy'),
('KGT46011', 'KH010', 'Khóa 46', 'Kỹ thuật xây dựng công trình giao thông', 'Đại học chính quy'),
('KGT47011', 'KH010', 'Khóa 47', 'Kỹ thuật xây dựng công trình giao thông', 'Đại học chính quy'),
('KGT48011', 'KH010', 'Khóa 48', 'Kỹ thuật xây dựng công trình giao thông', 'Đại học chính quy'),
('KHM46001', 'KH006', 'Khóa 46', 'Khoa học môi trường', 'Đại học chính quy'),
('KHM46006', 'KH011', 'Khóa 46', 'Khoa học máy tính', 'Đại học chính quy'),
('KHM47001', 'KH006', 'Khóa 47', 'Khoa học môi trường', 'Đại học chính quy'),
('KHM47006', 'KH011', 'Khóa 47', 'Khoa học máy tính', 'Đại học chính quy'),
('KHM48001', 'KH006', 'Khóa 48', 'Khoa học môi trường', 'Đại học chính quy'),
('KHM48006', 'KH011', 'Khóa 48', 'Khoa học máy tính', 'Đại học chính quy'),
('KNA46007', 'KH012', 'Khóa 46', 'Kinh tế nông nghiệp', 'Đại học chính quy'),
('KNA47007', 'KH012', 'Khóa 47', 'Kinh tế nông nghiệp', 'Đại học chính quy'),
('KNA48007', 'KH012', 'Khóa 48', 'Kinh tế nông nghiệp', 'Đại học chính quy'),
('KNC46005', 'KH013', 'Khóa 46', 'Khoa học cây trồng - Nông nghiệp công nghệ cao', 'Đại học chính quy'),
('KNC47005', 'KH013', 'Khóa 47', 'Khoa học cây trồng - Nông nghiệp công nghệ cao', 'Đại học chính quy'),
('KNC48005', 'KH013', 'Khóa 48', 'Khoa học cây trồng - Nông nghiệp công nghệ cao', 'Đại học chính quy'),
('KPC46008', 'KH011', 'Khóa 46', 'Kỹ thuật phần mềm (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KPC47008', 'KH011', 'Khóa 47', 'Kỹ thuật phần mềm (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KPC48008', 'KH011', 'Khóa 48', 'Kỹ thuật phần mềm (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KPM46007', 'KH011', 'Khóa 46', 'Kỹ thuật phần mềm', 'Đại học chính quy'),
('KPM47007', 'KH011', 'Khóa 47', 'Kỹ thuật phần mềm', 'Đại học chính quy'),
('KPM48007', 'KH011', 'Khóa 48', 'Kỹ thuật phần mềm', 'Đại học chính quy'),
('KTA46002', 'KH012', 'Khóa 46', 'Kiểm toán', 'Đại học chính quy'),
('KTA47002', 'KH012', 'Khóa 47', 'Kiểm toán', 'Đại học chính quy'),
('KTA48002', 'KH012', 'Khóa 48', 'Kiểm toán', 'Đại học chính quy'),
('KTE46006', 'KH012', 'Khóa 46', 'Kinh tế', 'Đại học chính quy'),
('KTE47006', 'KH012', 'Khóa 47', 'Kinh tế', 'Đại học chính quy'),
('KTE48006', 'KH012', 'Khóa 48', 'Kinh tế', 'Đại học chính quy'),
('KTM46003', 'KH006', 'Khóa 46', 'Kỹ thuật môi trường', 'Đại học chính quy'),
('KTM46006', 'KH010', 'Khóa 46', 'Kỹ thuật máy tính - Thiết kế vi mạch bán dẫn', 'Đại học chính quy'),
('KTM47003', 'KH006', 'Khóa 47', 'Kỹ thuật môi trường', 'Đại học chính quy'),
('KTM47006', 'KH010', 'Khóa 47', 'Kỹ thuật máy tính - Thiết kế vi mạch bán dẫn', 'Đại học chính quy'),
('KTM48003', 'KH006', 'Khóa 48', 'Kỹ thuật môi trường', 'Đại học chính quy'),
('KTM48006', 'KH010', 'Khóa 48', 'Kỹ thuật máy tính - Thiết kế vi mạch bán dẫn', 'Đại học chính quy'),
('KTN46002', 'KH006', 'Khóa 46', 'Kỹ thuật cấp thoát nước', 'Đại học chính quy'),
('KTN46008', 'KH012', 'Khóa 46', 'Kinh tế tài nguyên thiên nhiên', 'Đại học chính quy'),
('KTN47002', 'KH006', 'Khóa 47', 'Kỹ thuật cấp thoát nước', 'Đại học chính quy'),
('KTN47008', 'KH012', 'Khóa 47', 'Kinh tế tài nguyên thiên nhiên', 'Đại học chính quy'),
('KTN48002', 'KH006', 'Khóa 48', 'Kỹ thuật cấp thoát nước', 'Đại học chính quy'),
('KTN48008', 'KH012', 'Khóa 48', 'Kinh tế tài nguyên thiên nhiên', 'Đại học chính quy'),
('KTO46001', 'KH012', 'Khóa 46', 'Kế toán', 'Đại học chính quy'),
('KTO47001', 'KH012', 'Khóa 47', 'Kế toán', 'Đại học chính quy'),
('KTO48001', 'KH012', 'Khóa 48', 'Kế toán', 'Đại học chính quy'),
('KTR46003', 'KH010', 'Khóa 46', 'Kiến trúc', 'Đại học chính quy'),
('KTR47003', 'KH010', 'Khóa 47', 'Kiến trúc', 'Đại học chính quy'),
('KTR48003', 'KH010', 'Khóa 48', 'Kiến trúc', 'Đại học chính quy'),
('KTT46012', 'KH010', 'Khóa 46', 'Kỹ thuật xây dựng công trình thủy', 'Đại học chính quy'),
('KTT47012', 'KH010', 'Khóa 47', 'Kỹ thuật xây dựng công trình thủy', 'Đại học chính quy'),
('KTT48012', 'KH010', 'Khóa 48', 'Kỹ thuật xây dựng công trình thủy', 'Đại học chính quy'),
('KVL46008', 'KH010', 'Khóa 46', 'Kỹ thuật vật liệu', 'Đại học chính quy'),
('KVL47008', 'KH010', 'Khóa 47', 'Kỹ thuật vật liệu', 'Đại học chính quy'),
('KVL48008', 'KH010', 'Khóa 48', 'Kỹ thuật vật liệu', 'Đại học chính quy'),
('KVT46016', 'KH010', 'Khóa 46', 'Kỹ thuật điện tử - viễn thông', 'Đại học chính quy'),
('KVT47016', 'KH010', 'Khóa 47', 'Kỹ thuật điện tử - viễn thông', 'Đại học chính quy'),
('KVT48016', 'KH010', 'Khóa 48', 'Kỹ thuật điện tử - viễn thông', 'Đại học chính quy'),
('KXC46010', 'KH010', 'Khóa 46', 'Kỹ thuật xây dựng (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KXC47010', 'KH010', 'Khóa 47', 'Kỹ thuật xây dựng (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KXC48010', 'KH010', 'Khóa 48', 'Kỹ thuật xây dựng (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('KXD46009', 'KH010', 'Khóa 46', 'Kỹ thuật xây dựng', 'Đại học chính quy'),
('KXD47009', 'KH010', 'Khóa 47', 'Kỹ thuật xây dựng', 'Đại học chính quy'),
('KXD48009', 'KH010', 'Khóa 48', 'Kỹ thuật xây dựng', 'Đại học chính quy'),
('KYS46013', 'KH010', 'Khóa 46', 'Kỹ thuật y sinh', 'Đại học chính quy'),
('KYS47013', 'KH010', 'Khóa 47', 'Kỹ thuật y sinh', 'Đại học chính quy'),
('KYS48013', 'KH010', 'Khóa 48', 'Kỹ thuật y sinh', 'Đại học chính quy'),
('LHC46001', 'KH005', 'Khóa 46', 'Luật - Luật hành chính', 'Đại học chính quy'),
('LHC47001', 'KH005', 'Khóa 47', 'Luật - Luật hành chính', 'Đại học chính quy'),
('LHC48001', 'KH005', 'Khóa 48', 'Luật - Luật hành chính', 'Đại học chính quy'),
('LKT46003', 'KH005', 'Khóa 46', 'Luật kinh tế', 'Đại học chính quy'),
('LKT47003', 'KH005', 'Khóa 47', 'Luật kinh tế', 'Đại học chính quy'),
('LKT48003', 'KH005', 'Khóa 48', 'Luật kinh tế', 'Đại học chính quy'),
('LOG46019', 'KH010', 'Khóa 46', 'Logistics và Quản lý chuỗi cung ứng', 'Đại học chính quy'),
('LOG47019', 'KH010', 'Khóa 47', 'Logistics và Quản lý chuỗi cung ứng', 'Đại học chính quy'),
('LOG48019', 'KH010', 'Khóa 48', 'Logistics và Quản lý chuỗi cung ứng', 'Đại học chính quy'),
('LOP12345', 'KH011', 'Khóa 47', 'Lớp Kỹ thuật phần mềm', 'Đại học chính quy'),
('LTP46002', 'KH005', 'Khóa 46', 'Luật - Luật tư pháp', 'Đại học chính quy'),
('LTP47002', 'KH005', 'Khóa 47', 'Luật - Luật tư pháp', 'Đại học chính quy'),
('LTP48002', 'KH005', 'Khóa 48', 'Luật - Luật tư pháp', 'Đại học chính quy'),
('MAR46009', 'KH012', 'Khóa 46', 'Marketing', 'Đại học chính quy'),
('MAR47009', 'KH012', 'Khóa 47', 'Marketing', 'Đại học chính quy'),
('MAR48009', 'KH012', 'Khóa 48', 'Marketing', 'Đại học chính quy'),
('MMT46009', 'KH011', 'Khóa 46', 'Mạng máy tính và truyền thông dữ liệu', 'Đại học chính quy'),
('MMT47009', 'KH011', 'Khóa 47', 'Mạng máy tính và truyền thông dữ liệu', 'Đại học chính quy'),
('MMT48009', 'KH011', 'Khóa 48', 'Mạng máy tính và truyền thông dữ liệu', 'Đại học chính quy'),
('NAC46002', 'KH007', 'Khóa 46', 'Ngôn ngữ Anh (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('NAC47002', 'KH007', 'Khóa 47', 'Ngôn ngữ Anh (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('NAC48002', 'KH007', 'Khóa 48', 'Ngôn ngữ Anh (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('NHO46007', 'KH013', 'Khóa 46', 'Nông học', 'Đại học chính quy'),
('NHO47007', 'KH013', 'Khóa 47', 'Nông học', 'Đại học chính quy'),
('NHO48007', 'KH013', 'Khóa 48', 'Nông học', 'Đại học chính quy'),
('NNA46001', 'KH007', 'Khóa 46', 'Ngôn ngữ Anh', 'Đại học chính quy'),
('NNA47001', 'KH007', 'Khóa 47', 'Ngôn ngữ Anh', 'Đại học chính quy'),
('NNA48001', 'KH007', 'Khóa 48', 'Ngôn ngữ Anh', 'Đại học chính quy'),
('NNP46004', 'KH007', 'Khóa 46', 'Ngôn ngữ Pháp', 'Đại học chính quy'),
('NNP47004', 'KH007', 'Khóa 47', 'Ngôn ngữ Pháp', 'Đại học chính quy'),
('NNP48004', 'KH007', 'Khóa 48', 'Ngôn ngữ Pháp', 'Đại học chính quy'),
('NTS46012', 'KH014', 'Khóa 46', 'Nuôi trồng thủy sản', 'Đại học chính quy'),
('NTS47012', 'KH014', 'Khóa 47', 'Nuôi trồng thủy sản', 'Đại học chính quy'),
('NTS48012', 'KH014', 'Khóa 48', 'Nuôi trồng thủy sản', 'Đại học chính quy'),
('NTT46013', 'KH014', 'Khóa 46', 'Nuôi trồng thủy sản (chương trình tiên tiến)', 'Đại học tiên tiến'),
('NTT47013', 'KH014', 'Khóa 47', 'Nuôi trồng thủy sản (chương trình tiên tiến)', 'Đại học tiên tiến'),
('NTT48013', 'KH014', 'Khóa 48', 'Nuôi trồng thủy sản (chương trình tiên tiến)', 'Đại học tiên tiến'),
('OTO46007', 'KH010', 'Khóa 46', 'Kỹ thuật ô tô', 'Đại học chính quy'),
('OTO47007', 'KH010', 'Khóa 47', 'Kỹ thuật ô tô', 'Đại học chính quy'),
('OTO48007', 'KH010', 'Khóa 48', 'Kỹ thuật ô tô', 'Đại học chính quy'),
('PDB46003', 'KH007', 'Khóa 46', 'Ngôn ngữ Anh - Phiên dịch - Biên dịch tiếng Anh', 'Đại học chính quy'),
('PDB47003', 'KH007', 'Khóa 47', 'Ngôn ngữ Anh - Phiên dịch - Biên dịch tiếng Anh', 'Đại học chính quy'),
('PDB48003', 'KH007', 'Khóa 48', 'Ngôn ngữ Anh - Phiên dịch - Biên dịch tiếng Anh', 'Đại học chính quy'),
('QCN46020', 'KH010', 'Khóa 46', 'Quản lý công nghiệp', 'Đại học chính quy'),
('QCN47020', 'KH010', 'Khóa 47', 'Quản lý công nghiệp', 'Đại học chính quy'),
('QCN48020', 'KH010', 'Khóa 48', 'Quản lý công nghiệp', 'Đại học chính quy'),
('QDC46011', 'KH012', 'Khóa 46', 'Quản trị dịch vụ du lịch và lữ hành (chương trình ', 'Đại học chất lượng cao'),
('QDC47011', 'KH012', 'Khóa 47', 'Quản trị dịch vụ du lịch và lữ hành (chương trình ', 'Đại học chất lượng cao'),
('QDC48011', 'KH012', 'Khóa 48', 'Quản trị dịch vụ du lịch và lữ hành (chương trình ', 'Đại học chất lượng cao'),
('QDD46005', 'KH006', 'Khóa 46', 'Quản lý đất đai', 'Đại học chính quy'),
('QDD47005', 'KH006', 'Khóa 47', 'Quản lý đất đai', 'Đại học chính quy'),
('QDD48005', 'KH006', 'Khóa 48', 'Quản lý đất đai', 'Đại học chính quy'),
('QDL46010', 'KH012', 'Khóa 46', 'Quản trị dịch vụ du lịch và lữ hành', 'Đại học chính quy'),
('QDL47010', 'KH012', 'Khóa 47', 'Quản trị dịch vụ du lịch và lữ hành', 'Đại học chính quy'),
('QDL48010', 'KH012', 'Khóa 48', 'Quản trị dịch vụ du lịch và lữ hành', 'Đại học chính quy'),
('QHD46006', 'KH006', 'Khóa 46', 'Quy hoạch vùng và đô thị', 'Đại học chính quy'),
('QHD47006', 'KH006', 'Khóa 47', 'Quy hoạch vùng và đô thị', 'Đại học chính quy'),
('QHD48006', 'KH006', 'Khóa 48', 'Quy hoạch vùng và đô thị', 'Đại học chính quy'),
('QKC46013', 'KH012', 'Khóa 46', 'Quản trị kinh doanh (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('QKC47013', 'KH012', 'Khóa 47', 'Quản trị kinh doanh (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('QKC48013', 'KH012', 'Khóa 48', 'Quản trị kinh doanh (chương trình chất lượng cao)', 'Đại học chất lượng cao'),
('QKD46012', 'KH012', 'Khóa 46', 'Quản trị kinh doanh', 'Đại học chính quy'),
('QKD47012', 'KH012', 'Khóa 47', 'Quản trị kinh doanh', 'Đại học chính quy'),
('QKD48012', 'KH012', 'Khóa 48', 'Quản trị kinh doanh', 'Đại học chính quy'),
('QLT46014', 'KH014', 'Khóa 46', 'Quản lý thủy sản', 'Đại học chính quy'),
('QLT47014', 'KH014', 'Khóa 47', 'Quản lý thủy sản', 'Đại học chính quy'),
('QLT48014', 'KH014', 'Khóa 48', 'Quản lý thủy sản', 'Đại học chính quy'),
('QTN46004', 'KH006', 'Khóa 46', 'Quản lý tài nguyên và môi trường', 'Đại học chính quy'),
('QTN47004', 'KH006', 'Khóa 47', 'Quản lý tài nguyên và môi trường', 'Đại học chính quy'),
('QTN48004', 'KH006', 'Khóa 48', 'Quản lý tài nguyên và môi trường', 'Đại học chính quy'),
('SHU46008', 'KH013', 'Khóa 46', 'Sinh học ứng dụng', 'Đại học chính quy'),
('SHU47008', 'KH013', 'Khóa 47', 'Sinh học ứng dụng', 'Đại học chính quy'),
('SHU48008', 'KH013', 'Khóa 48', 'Sinh học ứng dụng', 'Đại học chính quy'),
('SIN46003', 'KH003', 'Khóa 46', 'Sinh học', 'Đại học chính quy'),
('SIN47003', 'KH003', 'Khóa 47', 'Sinh học', 'Đại học chính quy'),
('SIN48003', 'KH003', 'Khóa 48', 'Sinh học', 'Đại học chính quy'),
('SPA46005', 'KH007', 'Khóa 46', 'Sư phạm Tiếng Anh', 'Đại học chính quy'),
('SPA47005', 'KH007', 'Khóa 47', 'Sư phạm Tiếng Anh', 'Đại học chính quy'),
('SPA48005', 'KH007', 'Khóa 48', 'Sư phạm Tiếng Anh', 'Đại học chính quy'),
('SPD46011', 'KH009', 'Khóa 46', 'Sư phạm Địa lý', 'Đại học chính quy'),
('SPD47011', 'KH009', 'Khóa 47', 'Sư phạm Địa lý', 'Đại học chính quy'),
('SPD48011', 'KH009', 'Khóa 48', 'Sư phạm Địa lý', 'Đại học chính quy'),
('SPH46003', 'KH009', 'Khóa 46', 'Sư phạm Hóa học', 'Đại học chính quy'),
('SPH47003', 'KH009', 'Khóa 47', 'Sư phạm Hóa học', 'Đại học chính quy'),
('SPH48003', 'KH009', 'Khóa 48', 'Sư phạm Hóa học', 'Đại học chính quy'),
('SPK46004', 'KH009', 'Khóa 46', 'Sư phạm Khoa học tự nhiên', 'Đại học chính quy'),
('SPK47004', 'KH009', 'Khóa 47', 'Sư phạm Khoa học tự nhiên', 'Đại học chính quy'),
('SPK48004', 'KH009', 'Khóa 48', 'Sư phạm Khoa học tự nhiên', 'Đại học chính quy'),
('SPL46005', 'KH009', 'Khóa 46', 'Sư phạm Lịch sử', 'Đại học chính quy'),
('SPL47005', 'KH009', 'Khóa 47', 'Sư phạm Lịch sử', 'Đại học chính quy'),
('SPL48005', 'KH009', 'Khóa 48', 'Sư phạm Lịch sử', 'Đại học chính quy'),
('SPM46009', 'KH009', 'Khóa 46', 'Sư phạm Toán học', 'Đại học chính quy'),
('SPM47009', 'KH009', 'Khóa 47', 'Sư phạm Toán học', 'Đại học chính quy'),
('SPM48009', 'KH009', 'Khóa 48', 'Sư phạm Toán học', 'Đại học chính quy'),
('SPN46006', 'KH009', 'Khóa 46', 'Sư phạm Ngữ văn', 'Đại học chính quy'),
('SPN47006', 'KH009', 'Khóa 47', 'Sư phạm Ngữ văn', 'Đại học chính quy'),
('SPN48006', 'KH009', 'Khóa 48', 'Sư phạm Ngữ văn', 'Đại học chính quy'),
('SPP46006', 'KH007', 'Khóa 46', 'Sư phạm Tiếng Pháp', 'Đại học chính quy'),
('SPP47006', 'KH007', 'Khóa 47', 'Sư phạm Tiếng Pháp', 'Đại học chính quy'),
('SPP48006', 'KH007', 'Khóa 48', 'Sư phạm Tiếng Pháp', 'Đại học chính quy'),
('SPS46007', 'KH009', 'Khóa 46', 'Sư phạm Sinh học', 'Đại học chính quy'),
('SPS47007', 'KH009', 'Khóa 47', 'Sư phạm Sinh học', 'Đại học chính quy'),
('SPS48007', 'KH009', 'Khóa 48', 'Sư phạm Sinh học', 'Đại học chính quy'),
('SPT46008', 'KH009', 'Khóa 46', 'Sư phạm Tin học', 'Đại học chính quy'),
('SPT47008', 'KH009', 'Khóa 47', 'Sư phạm Tin học', 'Đại học chính quy'),
('SPT48008', 'KH009', 'Khóa 48', 'Sư phạm Tin học', 'Đại học chính quy'),
('SPV46010', 'KH009', 'Khóa 46', 'Sư phạm Vật lý', 'Đại học chính quy'),
('SPV47010', 'KH009', 'Khóa 47', 'Sư phạm Vật lý', 'Đại học chính quy'),
('SPV48010', 'KH009', 'Khóa 48', 'Sư phạm Vật lý', 'Đại học chính quy'),
('TCC46015', 'KH012', 'Khóa 46', 'Tài chính - Ngân hàng (chương trình chất lượng cao', 'Đại học chất lượng cao'),
('TCC47015', 'KH012', 'Khóa 47', 'Tài chính - Ngân hàng (chương trình chất lượng cao', 'Đại học chất lượng cao'),
('TCC48015', 'KH012', 'Khóa 48', 'Tài chính - Ngân hàng (chương trình chất lượng cao', 'Đại học chất lượng cao'),
('TCN46014', 'KH012', 'Khóa 46', 'Tài chính - Ngân hàng', 'Đại học chính quy'),
('TCN47014', 'KH012', 'Khóa 47', 'Tài chính - Ngân hàng', 'Đại học chính quy'),
('TCN48014', 'KH012', 'Khóa 48', 'Tài chính - Ngân hàng', 'Đại học chính quy'),
('THK46004', 'KH003', 'Khóa 46', 'Thống kê', 'Đại học chính quy'),
('THK47004', 'KH003', 'Khóa 47', 'Thống kê', 'Đại học chính quy'),
('THK48004', 'KH003', 'Khóa 48', 'Thống kê', 'Đại học chính quy'),
('TRI46003', 'KH001', 'Khóa 46', 'Triết học', 'Đại học chính quy'),
('TRI47003', 'KH001', 'Khóa 47', 'Triết học', 'Đại học chính quy'),
('TRI48003', 'KH001', 'Khóa 48', 'Triết học', 'Đại học chính quy'),
('TTM46010', 'KH011', 'Khóa 46', 'Truyền thông đa phương tiện', 'Đại học chính quy'),
('TTM47010', 'KH011', 'Khóa 47', 'Truyền thông đa phương tiện', 'Đại học chính quy'),
('TTM48010', 'KH011', 'Khóa 48', 'Truyền thông đa phương tiện', 'Đại học chính quy'),
('TTV46003', 'KH004', 'Khóa 46', 'Thông tin - Thư viện', 'Đại học chính quy'),
('TTV47003', 'KH004', 'Khóa 47', 'Thông tin - Thư viện', 'Đại học chính quy'),
('TTV48003', 'KH004', 'Khóa 48', 'Thông tin - Thư viện', 'Đại học chính quy'),
('TUD46005', 'KH003', 'Khóa 46', 'Toán ứng dụng', 'Đại học chính quy'),
('TUD47005', 'KH003', 'Khóa 47', 'Toán ứng dụng', 'Đại học chính quy'),
('TUD48005', 'KH003', 'Khóa 48', 'Toán ứng dụng', 'Đại học chính quy'),
('TYH46009', 'KH013', 'Khóa 46', 'Thú y', 'Đại học chính quy'),
('TYH47009', 'KH013', 'Khóa 47', 'Thú y', 'Đại học chính quy'),
('TYH48009', 'KH013', 'Khóa 48', 'Thú y', 'Đại học chính quy'),
('VAN46004', 'KH004', 'Khóa 46', 'Văn học', 'Đại học chính quy'),
('VAN47004', 'KH004', 'Khóa 47', 'Văn học', 'Đại học chính quy'),
('VAN48004', 'KH004', 'Khóa 48', 'Văn học', 'Đại học chính quy'),
('VLK46006', 'KH003', 'Khóa 46', 'Vật lý kỹ thuật', 'Đại học chính quy'),
('VLK47006', 'KH003', 'Khóa 47', 'Vật lý kỹ thuật', 'Đại học chính quy'),
('VLK48006', 'KH003', 'Khóa 48', 'Vật lý kỹ thuật', 'Đại học chính quy'),
('XHH46005', 'KH004', 'Khóa 46', 'Xã hội học', 'Đại học chính quy'),
('XHH47005', 'KH004', 'Khóa 47', 'Xã hội học', 'Đại học chính quy'),
('XHH48005', 'KH004', 'Khóa 48', 'Xã hội học', 'Đại học chính quy');

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

--
-- Đang đổ dữ liệu cho bảng `nien_khoa`
--

INSERT INTO `nien_khoa` (`NK_NAM`) VALUES
('2020-2021'),
('2021-2022'),
('2022-2023'),
('2023-2024'),
('2024-2025');

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

--
-- Đang đổ dữ liệu cho bảng `quyet_dinh_nghiem_thu`
--

INSERT INTO `quyet_dinh_nghiem_thu` (`QD_SO`, `BB_SOBB`, `QD_NGAY`, `QD_FILE`) VALUES
('QD011', 'BB011', '2023-10-01', 'qd011.pdf'),
('QD012', 'BB012', '2023-10-02', 'qd012.pdf'),
('QD013', 'BB013', '2023-10-03', 'qd013.pdf'),
('QD014', 'BB014', '2023-10-04', 'qd014.pdf'),
('QD015', 'BB015', '2023-10-05', 'qd015.pdf'),
('QD016', 'BB016', '2023-10-06', 'qd016.pdf'),
('QD017', 'BB017', '2023-10-07', 'qd017.pdf'),
('QD018', 'BB018', '2023-10-08', 'qd018.pdf'),
('QD019', 'BB019', '2023-10-09', 'qd019.pdf'),
('QD020', 'BB020', '2023-10-10', 'qd020.pdf');

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
  `SV_DIACHI` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sinh_vien`
--

INSERT INTO `sinh_vien` (`SV_MASV`, `LOP_MA`, `SV_HOSV`, `SV_TENSV`, `SV_GIOITINH`, `SV_SDT`, `SV_EMAIL`, `SV_MATKHAU`, `SV_NGAYSINH`, `SV_DIACHI`) VALUES
('B2110051', 'LOP12345', 'Doan', 'Chi Nguyen', 0, '0835886837', 'nguyenb2110051@student.ctu.edu.vn', '$2y$10$KauR1hx2VUTwxJZWkHZmouuXpIAtkmjfaW7aPuLpxI6hj7DXfjKVi', '2003-07-31', 'Hậu Giang'),
('B4600201', 'CNT46002', 'Nguyen', 'Van A', 1, '0123456789', 'B4600201@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-01-01', 'Ha Noi'),
('B4600202', 'CNT46002', 'Tran', 'Thi B', 0, '0123456788', 'B4600202@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-02-02', 'Ho Chi Minh'),
('B4600203', 'CNT46002', 'Le', 'Van C', 1, '0123456787', 'B4600203@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-03-03', 'Da Nang'),
('B4600204', 'CNT46002', 'Pham', 'Thi D', 0, '0123456786', 'B4600204@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-04-04', 'Hai Phong'),
('B4600205', 'CNT46002', 'Hoang', 'Van E', 1, '0123456785', 'B4600205@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-05-05', 'Can Tho'),
('B4600206', 'CNT46002', 'Vu', 'Thi F', 0, '0123456784', 'B4600206@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-06-06', 'Nha Trang'),
('B4600207', 'CNT46002', 'Dang', 'Van G', 1, '0123456783', 'B4600207@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-07-07', 'Vung Tau'),
('B4600208', 'CNT46002', 'Bui', 'Thi H', 0, '0123456782', 'B4600208@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-08-08', 'Quang Ninh'),
('B4600209', 'CNT46002', 'Do', 'Van I', 1, '0123456781', 'B4600209@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-09-09', 'Hue'),
('B4600210', 'CNT46002', 'Nguyen', 'Thi K', 0, '0123456780', 'B4600210@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-10-10', 'Binh Duong'),
('B4600211', 'CNT46002', 'Tran', 'Van L', 1, '0123456779', 'B4600211@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-11-11', 'Ha Noi'),
('B4600212', 'CNT46002', 'Le', 'Thi M', 0, '0123456778', 'B4600212@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-12-12', 'Ho Chi Minh'),
('B4600213', 'CNT46002', 'Pham', 'Van N', 1, '0123456777', 'B4600213@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-01-13', 'Da Nang'),
('B4600214', 'CNT46002', 'Hoang', 'Thi O', 0, '0123456776', 'B4600214@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-02-14', 'Hai Phong'),
('B4600215', 'CNT46002', 'Vu', 'Van P', 1, '0123456775', 'B4600215@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-03-15', 'Can Tho'),
('B4600216', 'CNT46002', 'Dang', 'Thi Q', 0, '0123456774', 'B4600216@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-04-16', 'Nha Trang'),
('B4600217', 'CNT46002', 'Bui', 'Van R', 1, '0123456773', 'B4600217@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-05-17', 'Vung Tau'),
('B4600218', 'CNT46002', 'Do', 'Thi S', 0, '0123456772', 'B4600218@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-06-18', 'Quang Ninh'),
('B4600219', 'CNT46002', 'Nguyen', 'Van T', 1, '0123456771', 'B4600219@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-07-19', 'Hue'),
('B4600220', 'CNT46002', 'Tran', 'Thi U', 0, '0123456770', 'B4600220@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-08-20', 'Binh Duong'),
('B4600221', 'CNT46002', 'Le', 'Van V', 1, '0123456769', 'B4600221@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-09-21', 'Ha Noi'),
('B4600222', 'CNT46002', 'Pham', 'Thi W', 0, '0123456768', 'B4600222@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-10-22', 'Ho Chi Minh'),
('B4600223', 'CNT46002', 'Hoang', 'Van X', 1, '0123456767', 'B4600223@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-11-23', 'Da Nang'),
('B4600224', 'CNT46002', 'Vu', 'Thi Y', 0, '0123456766', 'B4600224@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-12-24', 'Hai Phong'),
('B4600225', 'CNT46002', 'Dang', 'Van Z', 1, '0123456765', 'B4600225@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-01-25', 'Can Tho'),
('B4600226', 'CNT46002', 'Bui', 'Thi AA', 0, '0123456764', 'B4600226@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-02-26', 'Nha Trang'),
('B4600227', 'CNT46002', 'Do', 'Van BB', 1, '0123456763', 'B4600227@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-03-27', 'Vung Tau'),
('B4600228', 'CNT46002', 'Nguyen', 'Thi CC', 0, '0123456762', 'B4600228@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-04-28', 'Quang Ninh'),
('B4600229', 'CNT46002', 'Tran', 'Van DD', 1, '0123456761', 'B4600229@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-05-29', 'Hue'),
('B4600230', 'CNT46002', 'Le', 'Thi EE', 0, '0123456760', 'B4600230@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-06-30', 'Binh Duong');

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

--
-- Đang đổ dữ liệu cho bảng `tieu_chi`
--

INSERT INTO `tieu_chi` (`TC_MATC`, `TC_NDDANHGIA`, `TC_DIEMTOIDA`) VALUES
('TC001', 'Tổng quan tình hình nghiên cứu, lý do chọn đề tài', 10),
('TC002', 'Mục tiêu đề tài', 15),
('TC003', 'Phương pháp nghiên cứu', 15),
('TC004', 'Nội dung khoa học', 30),
('TC005', 'Đóng góp về mặt kinh tế - xã hội, giáo dục và đào tạo, an ninh, quốc phòng', 15),
('TC006', 'Hình thức trình bày báo cáo tổng kết đề tài', 5),
('TC007', 'Thời gian và tiến độ thực hiện đề tài (cho điểm 0 trong trường hợp đề tài nghiệm thu trễ hạn so với thuyết minh kể cả đề tài được duyệt gia hạn)', 5),
('TC008', 'Điểm thưởng: có bài báo đăng trên tạp chí khoa học có mã số ISSN, hoặc bài kỷ yếu Hội nghị/Hội thảo có Nhà Xuất Bản. Bài báo được xác nhận sẽ được đăng trên Tạp chí của Hội đồng biên tập cũng được tính điểm, phải có minh chứng bài báo hoặc giấy xác nhận cho Hội đồng (cho điểm 0 trong trường hợp không có bài báo).', 5);

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

--
-- Đang đổ dữ liệu cho bảng `user`
--

INSERT INTO `user` (`USER_ID`, `USERNAME`, `PASSWORD`, `ROLE`) VALUES
(3, 'admin', '$2y$10$M.thWqWP.HjNKDnkdVW6D.JNyULT2RgqjpbVV3/G7oE78tK.MW42m', 'admin'),
(4, 'nguyenvana@example.com', '$2y$10$sJxxdAudHUMzhzKEjzLXm.2jvIsNfBU0wejyfHvIOBxgpJ/CGnIdS', 'giang_vien');

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
  ADD KEY `FK_CHI_TIET_RELATIONS_DE_TAI_N` (`DT_MADT`),
  ADD KEY `FK_CHI_TIET_RELATIONS_HOC_KI` (`HK_MA`);

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
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `user`
--
ALTER TABLE `user`
  MODIFY `USER_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  ADD CONSTRAINT `FK_CHI_TIET_RELATIONS_DE_TAI_N` FOREIGN KEY (`DT_MADT`) REFERENCES `de_tai_nghien_cuu` (`DT_MADT`),
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
  ADD CONSTRAINT `FK_HOP_DONG_CO_1_2_DE_TAI_N` FOREIGN KEY (`DT_MADT`) REFERENCES `de_tai_nghien_cuu` (`DT_MADT`);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
