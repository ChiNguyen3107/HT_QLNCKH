-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 08, 2025 lúc 08:44 AM
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

DELIMITER $$
--
-- Thủ tục
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CalculateMemberTotalScore` (IN `p_qd_so` CHAR(5), IN `p_gv_magv` CHAR(8))   BEGIN
        DECLARE total_score DECIMAL(5,2) DEFAULT 0.00;
        
        -- Tính tổng điểm từ chi tiết đánh giá
        SELECT COALESCE(SUM(CTDDG_DIEM), 0) INTO total_score
        FROM chi_tiet_diem_danh_gia
        WHERE QD_SO = p_qd_so AND GV_MAGV = p_gv_magv;
        
        -- Cập nhật vào bảng thanh_vien_hoi_dong
        UPDATE thanh_vien_hoi_dong 
        SET TV_DIEM = total_score,
            TV_NGAYDANHGIA = CURRENT_TIMESTAMP,
            TV_TRANGTHAI = CASE 
                WHEN total_score > 0 THEN 'Đã hoàn thành'
                ELSE TV_TRANGTHAI
            END
        WHERE QD_SO = p_qd_so AND GV_MAGV = p_gv_magv;
        
        SELECT total_score as total_score;
    END$$

DELIMITER ;

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
  `BB_SOBB` char(11) NOT NULL,
  `QD_SO` varchar(11) DEFAULT NULL,
  `BB_NGAYNGHIEMTHU` date NOT NULL,
  `BB_XEPLOAI` varchar(255) NOT NULL,
  `BB_TONGDIEM` decimal(5,2) DEFAULT NULL COMMENT 'T???ng ??i???m ????nh gi?? t??? 0-100, v???i 2 ch??? s??? th???p ph??n'
) ;

--
-- Đang đổ dữ liệu cho bảng `bien_ban`
--

INSERT INTO `bien_ban` (`BB_SOBB`, `QD_SO`, `BB_NGAYNGHIEMTHU`, `BB_XEPLOAI`, `BB_TONGDIEM`) VALUES
('BB00000004', 'QDDT0', '2024-12-15', 'Xuất sắc', 100.00),
('BB00000005', '123ab', '2026-01-04', 'Xuất sắc', 100.00),
('BBDT0000003', 'QDDT0000003', '2026-01-30', 'Xuất sắc', 100.00);

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
-- Cấu trúc bảng cho bảng `chi_tiet_danh_gia_tieu_chi`
--

CREATE TABLE `chi_tiet_danh_gia_tieu_chi` (
  `CDGTC_MA` int(11) NOT NULL,
  `CDGTC_MAGV` varchar(20) NOT NULL,
  `CDGTC_MADT` varchar(20) NOT NULL,
  `CDGTC_MATC` varchar(10) NOT NULL,
  `CDGTC_DIEM` decimal(5,2) DEFAULT NULL,
  `CDGTC_NHANXET` text DEFAULT NULL,
  `CDGTC_NGAYTAO` datetime DEFAULT current_timestamp(),
  `CDGTC_NGAYCAPNHAT` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_diem_danh_gia`
--

CREATE TABLE `chi_tiet_diem_danh_gia` (
  `CTDDG_MA` char(10) NOT NULL,
  `QD_SO` varchar(11) DEFAULT NULL,
  `GV_MAGV` char(8) NOT NULL,
  `TC_MATC` char(5) NOT NULL,
  `CTDDG_DIEM` decimal(4,2) NOT NULL DEFAULT 0.00 COMMENT 'Điểm đánh giá cho tiêu chí này',
  `CTDDG_GHICHU` text DEFAULT NULL COMMENT 'Ghi chú đánh giá',
  `CTDDG_NGAYCAPNHAT` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_tham_gia`
--

INSERT INTO `chi_tiet_tham_gia` (`SV_MASV`, `DT_MADT`, `HK_MA`, `CTTG_VAITRO`, `CTTG_NGAYTHAMGIA`) VALUES
('B2110051', 'DT0000001', 'HK3-2024', 'Chủ nhiệm', '2025-08-04'),
('B2110051', 'DT0000002', 'HK3-2024', 'Chủ nhiệm', '2025-08-04'),
('B2110051', 'DT0000003', 'HK3-2024', 'Chủ nhiệm', '2025-08-05'),
('b2110056', 'DT0000001', 'HK3-2024', 'Thành viên', '2025-08-04'),
('b2110056', 'DT0000002', 'HK3-2024', 'Thành viên', '2025-08-04'),
('b2110056', 'DT0000003', 'HK3-2024', 'Thành viên', '2025-08-05');

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
-- Đang đổ dữ liệu cho bảng `de_tai_nghien_cuu`
--

INSERT INTO `de_tai_nghien_cuu` (`DT_MADT`, `LDT_MA`, `GV_MAGV`, `LVNC_MA`, `QD_SO`, `LVUT_MA`, `HD_MA`, `DT_TENDT`, `DT_MOTA`, `DT_TRANGTHAI`, `DT_FILEBTM`, `DT_NGAYTAO`, `DT_SLSV`, `DT_GHICHU`, `DT_NGUOICAPNHAT`, `DT_NGAYCAPNHAT`) VALUES
('DT0000001', 'DT004', 'GV000002', 'L0002', 'QDDT0', 'LV004', 'HDDT0', 'Xây dựng hệ thống giám sát chất lượng nước ao nuôi tôm bằng IoT và học máy', 'Thiết kế hệ thống cảm biến đo pH, nhiệt độ, độ mặn,... kết hợp thuật toán dự đoán cảnh báo sớm sự cố trong ao nuôi tôm.', 'Đã hoàn thành', 'proposal_DT0000001_1754301592.docx', '2025-08-04 16:58:49', 2, '\n', 'B2110051', '2025-08-05 23:31:47'),
('DT0000002', 'DT006', 'GV000002', 'L0002', '123ab', 'LV004', '123ab', 'Xây dựng hệ thống quản lý học tập trực tuyến tích hợp AI chấm điểm tự động', 'Phát triển hệ thống LMS (Learning Management System) tích hợp AI để tự động chấm bài trắc nghiệm và đưa ra phản hồi cá nhân hóa cho người học.', 'Đã hoàn thành', 'proposal_DT0000002_1754324302.docx', '2025-08-04 23:17:34', 2, '\n', 'B2110051', '2025-08-05 23:33:08'),
('DT0000003', 'DT011', 'GV000003', 'L0002', 'QDDT0000003', 'LV004', 'HDDT0', 'Ứng dụng Blockchain trong truy xuất nguồn gốc thực phẩm', 'Thiết kế mô hình ứng dụng công nghệ Blockchain để đảm bảo tính minh bạch, không thể sửa đổi của dữ liệu trong chuỗi cung ứng nông sản.', 'Đang thực hiện', 'proposal_DT0000003_1754486646.docx', '2025-08-05 20:31:22', 2, '\n', '0', '2025-08-06 13:32:41');

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
  `FDG_MA` varchar(10) NOT NULL,
  `BB_SOBB` varchar(11) NOT NULL,
  `FDG_TEN` varchar(255) NOT NULL,
  `FDG_DUONGDAN` varchar(500) DEFAULT NULL,
  `FDG_NGAYCAP` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Đang đổ dữ liệu cho bảng `file_dinh_kem`
--

INSERT INTO `file_dinh_kem` (`FDG_MA`, `BB_SOBB`, `GV_MAGV`, `FDG_LOAI`, `FDG_TENFILE`, `FDG_FILE`, `FDG_NGAYTAO`, `FDG_KICHTHUC`, `FDG_MOTA`) VALUES
('FDG038148', 'BB00000004', 'GV000002', 'member_evaluation', 'Test Upload Debug', 'eval_GV000002_DT0000001_1754579178.docx', '2025-08-07 22:06:18', 13389, 'File test upload khÃ´ng cáº§n session'),
('FDG143310', 'BB00000004', 'GV000003', 'member_evaluation', 'File đánh giá thành viên 3.docx', 'eval_GV000003_DT0000003_1754582303.docx', '2025-08-07 22:58:23', 13389, ''),
('FDG795009', 'BB00000004', 'GV000002', 'member_evaluation', 'File đánh giá thành viên 1.docx', 'eval_GV000002_DT0000003_1754621320.docx', '2025-08-08 09:48:40', 13345, ''),
('FDG947868', 'BB00000004', 'GV000004', 'member_evaluation', 'File đánh giá thành viên 3.docx', 'eval_GV000004_DT0000003_1754621338.docx', '2025-08-08 09:48:58', 13389, ''),
('FDG970866', 'BB00000004', 'GV000005', 'member_evaluation', 'File đánh giá thành viên 1.docx', 'eval_GV000005_DT0000003_1754581147.docx', '2025-08-07 22:39:07', 13345, '');

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

--
-- Đang đổ dữ liệu cho bảng `giang_vien`
--

INSERT INTO `giang_vien` (`GV_MAGV`, `DV_MADV`, `GV_HOGV`, `GV_TENGV`, `GV_EMAIL`, `GV_CHUYENMON`, `GV_GIOITINH`, `GV_SDT`, `GV_MATKHAU`, `GV_NGAYSINH`, `GV_DIACHI`) VALUES
('GV000001', 'KH011', 'Nguyen', 'Van A', 'nguyenvana@example.com', 'Trí tuệ nhân tạo, Học máy', 1, '0987654321', '$2y$10$M.thWqWP.HjNKDnkdVW6D.JNyULT2RgqjpbVV3/G7oE78tK.MW42m', NULL, ''),
('GV000002', 'KH011', 'Huỳnh', 'Thanh Phong', 'phong@gmail.com', 'Công nghệ phần mềm, An toàn thông tin', 1, '0909009009', '$2y$10$MOxPOQuVesOsBRmhbCD/5OswxOu1B/vjvFZrqP3Csy00oDL5pt1u2', NULL, 'Sóc Trăng'),
('GV000003', 'KH001', 'Trần', 'Văn Bình', 'tranvanbinh@example.com', 'Lý luận chính trị, Triết học Mác-Lenin', 1, '0900000000', '$2y$10$lIGYwDbGChhSDEPq4aNHnednv4zGB0E49UPMFaTBX8EFDVI/f9qkq', NULL, NULL),
('GV000004', 'KH012', 'Nguyễn', 'Thị Hoa', 'nguyenthihoa@example.com', 'Kinh tế học, Quản trị kinh doanh', 1, '0900000000', '$2y$10$3Y7H8b.rAL.yr5C.Lc5qpugj.KA0vEUnxhicHpXqdp7RNH5hkBO6O', NULL, NULL),
('GV000005', 'KH009', 'Lê', 'Minh Tuấn', 'leminhtuan@example.com', 'Sư phạm toán học, Phương pháp giảng dạy', 1, '0900000000', '$2y$10$mSkv61FZ7SAGBfqfHZktQ.4tHYRaHfRh64F3OSWFOWWYOPR7z8q0e', NULL, NULL),
('GV000006', 'KH007', 'Phạm', 'Thị Lan', 'phamthilan@example.com', 'Ngôn ngữ Anh, Văn học nước ngoài', 1, '0900000000', '$2y$10$cgQfjPxgDE6FfzbUMF9X8ezxBfawJZrV6VpmVtt42oljJp4aEIxyC', NULL, NULL),
('GV000007', 'KH013', 'Võ', 'Văn Nam', 'vovannam@example.com', 'Nông học, Chăn nuôi thú y', 1, '0900000000', '$2y$10$d7B1M1TW1bSdLfyB/BSzcOlzi3JEbotzg.RsWQBSO4ZDquvQnH6gi', NULL, NULL),
('GV_TEST0', '', 'Nguyễn V', 'Test', 'test@teacher.edu.vn', NULL, 1, NULL, 'test123', NULL, NULL);

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

--
-- Đang đổ dữ liệu cho bảng `hop_dong`
--

INSERT INTO `hop_dong` (`HD_MA`, `DT_MADT`, `HD_NGAYTAO`, `HD_NGAYBD`, `HD_NGAYKT`, `HD_GHICHU`, `HD_TONGKINHPHI`, `HD_FILEHD`, `HD_NGUOIKY`) VALUES
('123ab', 'DT0000002', '2025-08-04', '2025-08-04', '2026-02-04', 'âzza', 25000000.00, 'contract_123abc_1754324552.docx', NULL),
('HDDT0', 'DT0000001', '2025-08-04', '2025-08-04', '2026-02-04', 'abc', 35000000.00, 'contract_HDDT0000001_1754301639.docx', NULL),
('HDDT0000003', 'DT0000003', '2025-08-06', '2025-08-06', '2026-02-06', 'Hợp đồng cho đề tài \"Ứng dụng Blockchain trong truy xuất nguồn gốc thực phẩm\r\n\"', 20000000.00, 'contract_HDDT0000003_1754489621.docx', NULL);

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
('47'),
('48'),
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

--
-- Đang đổ dữ liệu cho bảng `quan_ly_nghien_cuu`
--

INSERT INTO `quan_ly_nghien_cuu` (`QL_MA`, `DV_MADV`, `QL_HO`, `QL_TEN`, `QL_EMAIL`, `QL_MATKHAU`, `QL_SDT`, `QL_GIOITINH`, `QL_NGAYSINH`, `QL_DIACHI`) VALUES
('QLR001', 'KH001', 'Quản lý', 'Nghiên cứu', 'research.admin@ctu.edu.vn', '$2y$10$3n1zxJGWPptH9qMnJvHl5uKzmVb2YKzgWsBu0AmCBNG.kclQOVbCe', '0987654321', 1, '1994-03-02', 'Cần Thơ\r\n');

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

--
-- Đang đổ dữ liệu cho bảng `quyet_dinh_nghiem_thu`
--

INSERT INTO `quyet_dinh_nghiem_thu` (`QD_SO`, `BB_SOBB`, `QD_NGAY`, `QD_FILE`, `QD_NOIDUNG`, `HD_THANHVIEN`) VALUES
('123ab', 'BB00000005', '2026-01-04', 'decision_123abc_1754324602.docx', NULL, 'Huỳnh Thanh Phong (Chủ tịch)'),
('QDDT0', 'BB00000004', '2026-01-04', 'decision_QDDT0000001_1754301665.docx', NULL, 'Huỳnh Thanh Phong (Chủ tịch)'),
('QDDT0000003', 'BBDT0000003', '2026-01-06', 'decision_QDDT0000003_1754493397.docx', NULL, 'Huỳnh Thanh Phong (Chủ tịch)\nLê Minh Tuấn (Phó chủ tịch)\nNguyễn Thị Hoa (Thư ký)\nTrần Văn Bình (Thành viên)');

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

--
-- Đang đổ dữ liệu cho bảng `sinh_vien`
--

INSERT INTO `sinh_vien` (`SV_MASV`, `LOP_MA`, `SV_HOSV`, `SV_TENSV`, `SV_GIOITINH`, `SV_SDT`, `SV_EMAIL`, `SV_MATKHAU`, `SV_NGAYSINH`, `SV_DIACHI`, `SV_AVATAR`) VALUES
('B2110051', 'LOP12345', 'Doan', 'Chi Nguyen', 0, '0835886837', 'nguyenb2110051@student.ctu.edu.vn', '$2y$10$KauR1hx2VUTwxJZWkHZmouuXpIAtkmjfaW7aPuLpxI6hj7DXfjKVi', '2003-07-31', 'Vị Trung, Vị Thủy, Hậu Giang', 'uploads/avatars/B2110051_1748864147.jpg'),
('b2110056', 'LOP12345', 'Cù', 'Minh Sang', 1, '0909090909', 'sangb2110056@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '2003-07-07', NULL, NULL),
('B4600201', 'CNT46002', 'Nguyen', 'Van A', 1, '0123456789', 'B4600201@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-01-01', 'Ha Noi', NULL),
('B4600202', 'CNT46002', 'Tran', 'Thi B', 0, '0123456788', 'B4600202@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-02-02', 'Ho Chi Minh', NULL),
('B4600203', 'CNT46002', 'Le', 'Van C', 1, '0123456787', 'B4600203@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-03-03', 'Da Nang', NULL),
('B4600204', 'CNT46002', 'Pham', 'Thi D', 0, '0123456786', 'B4600204@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-04-04', 'Hai Phong', NULL),
('B4600205', 'CNT46002', 'Hoang', 'Van E', 1, '0123456785', 'B4600205@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-05-05', 'Can Tho', NULL),
('B4600206', 'CNT46002', 'Vu', 'Thi F', 0, '0123456784', 'B4600206@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-06-06', 'Nha Trang', NULL),
('B4600207', 'CNT46002', 'Dang', 'Van G', 1, '0123456783', 'B4600207@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-07-07', 'Vung Tau', NULL),
('B4600208', 'CNT46002', 'Bui', 'Thi H', 0, '0123456782', 'B4600208@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-08-08', 'Quang Ninh', NULL),
('B4600209', 'CNT46002', 'Do', 'Van I', 1, '0123456781', 'B4600209@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-09-09', 'Hue', NULL),
('B4600210', 'CNT46002', 'Nguyen', 'Thi K', 0, '0123456780', 'B4600210@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-10-10', 'Binh Duong', NULL),
('B4600211', 'CNT46002', 'Tran', 'Van L', 1, '0123456779', 'B4600211@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-11-11', 'Ha Noi', NULL),
('B4600212', 'CNT46002', 'Le', 'Thi M', 0, '0123456778', 'B4600212@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-12-12', 'Ho Chi Minh', NULL),
('B4600213', 'CNT46002', 'Pham', 'Van N', 1, '0123456777', 'B4600213@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-01-13', 'Da Nang', NULL),
('B4600214', 'CNT46002', 'Hoang', 'Thi O', 0, '0123456776', 'B4600214@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-02-14', 'Hai Phong', NULL),
('B4600215', 'CNT46002', 'Vu', 'Van P', 1, '0123456775', 'B4600215@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-03-15', 'Can Tho', NULL),
('B4600216', 'CNT46002', 'Dang', 'Thi Q', 0, '0123456774', 'B4600216@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-04-16', 'Nha Trang', NULL),
('B4600217', 'CNT46002', 'Bui', 'Van R', 1, '0123456773', 'B4600217@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-05-17', 'Vung Tau', NULL),
('B4600218', 'CNT46002', 'Do', 'Thi S', 0, '0123456772', 'B4600218@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-06-18', 'Quang Ninh', NULL),
('B4600219', 'CNT46002', 'Nguyen', 'Van T', 1, '0123456771', 'B4600219@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-07-19', 'Hue', NULL),
('B4600220', 'CNT46002', 'Tran', 'Thi U', 0, '0123456770', 'B4600220@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-08-20', 'Binh Duong', NULL),
('B4600221', 'CNT46002', 'Le', 'Van V', 1, '0123456769', 'B4600221@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-09-21', 'Ha Noi', NULL),
('B4600222', 'CNT46002', 'Pham', 'Thi W', 0, '0123456768', 'B4600222@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-10-22', 'Ho Chi Minh', NULL),
('B4600223', 'CNT46002', 'Hoang', 'Van X', 1, '0123456767', 'B4600223@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-11-23', 'Da Nang', NULL),
('B4600224', 'CNT46002', 'Vu', 'Thi Y', 0, '0123456766', 'B4600224@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-12-24', 'Hai Phong', NULL),
('B4600225', 'CNT46002', 'Dang', 'Van Z', 1, '0123456765', 'B4600225@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-01-25', 'Can Tho', NULL),
('B4600226', 'CNT46002', 'Bui', 'Thi AA', 0, '0123456764', 'B4600226@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-02-26', 'Nha Trang', NULL),
('B4600227', 'CNT46002', 'Do', 'Van BB', 1, '0123456763', 'B4600227@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-03-27', 'Vung Tau', NULL),
('B4600228', 'CNT46002', 'Nguyen', 'Thi CC', 0, '0123456762', 'B4600228@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-04-28', 'Quang Ninh', NULL),
('B4600229', 'CNT46002', 'Tran', 'Van DD', 1, '0123456761', 'B4600229@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-05-29', 'Hue', NULL),
('B4600230', 'CNT46002', 'Le', 'Thi EE', 0, '0123456760', 'B4600230@student.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2003-06-30', 'Binh Duong', NULL);

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
-- Đang đổ dữ liệu cho bảng `thanh_vien_hoi_dong`
--

INSERT INTO `thanh_vien_hoi_dong` (`QD_SO`, `GV_MAGV`, `TV_HOTEN`, `TC_MATC`, `TV_VAITRO`, `TV_DIEM`, `TV_DIEMCHITIET`, `TV_TRANGTHAI`, `TV_NGAYDANHGIA`, `TV_FILEDANHGIA`, `TV_DANHGIA`, `TV_CHITIET_TIEUCHI`, `TV_HOAN_THANH`) VALUES
('123ab', 'GV000002', NULL, 'TC001', 'Chủ tịch', 0.00, NULL, 'Chưa đánh giá', NULL, NULL, 'Chưa đánh giá', NULL, 0),
('QDDT0', 'GV000002', 'Huỳnh Thanh Phong', 'TC001', 'Chủ tịch', NULL, NULL, 'Chưa đánh giá', NULL, NULL, NULL, NULL, 0),
('QDDT0000003', 'GV000002', NULL, 'TC001', 'Chủ tịch', 100.00, '{\"TC001\":{\"score\":10,\"comment\":\"\"},\"TC002\":{\"score\":15,\"comment\":\"\"},\"TC003\":{\"score\":15,\"comment\":\"\"},\"TC004\":{\"score\":30,\"comment\":\"\"},\"TC005\":{\"score\":15,\"comment\":\"\"},\"TC006\":{\"score\":5,\"comment\":\"\"},\"TC007\":{\"score\":5,\"comment\":\"\"},\"TC008\":{\"score\":5,\"comment\":\"\"}}', 'Đã hoàn thành', '2025-08-07 21:39:47', NULL, 'Chưa đánh giá', NULL, 0),
('QDDT0000003', 'GV000003', NULL, 'TC001', 'Thành viên', 90.00, '{\"TC001\":{\"score\":10,\"comment\":\"\"},\"TC002\":{\"score\":15,\"comment\":\"\"},\"TC003\":{\"score\":15,\"comment\":\"\"},\"TC004\":{\"score\":25,\"comment\":\"\"},\"TC005\":{\"score\":10,\"comment\":\"\"},\"TC006\":{\"score\":5,\"comment\":\"\"},\"TC007\":{\"score\":5,\"comment\":\"\"},\"TC008\":{\"score\":5,\"comment\":\"\"}}', 'Đã hoàn thành', '2025-08-07 21:42:06', NULL, 'Chưa đánh giá', NULL, 0),
('QDDT0000003', 'GV000004', NULL, 'TC001', 'Thư ký', 100.00, '{\"TC001\":{\"score\":10,\"comment\":\"\"},\"TC002\":{\"score\":15,\"comment\":\"\"},\"TC003\":{\"score\":15,\"comment\":\"\"},\"TC004\":{\"score\":30,\"comment\":\"\"},\"TC005\":{\"score\":15,\"comment\":\"\"},\"TC006\":{\"score\":5,\"comment\":\"\"},\"TC007\":{\"score\":5,\"comment\":\"\"},\"TC008\":{\"score\":5,\"comment\":\"\"}}', 'Đã hoàn thành', '2025-08-07 21:42:30', NULL, 'Chưa đánh giá', NULL, 0),
('QDDT0000003', 'GV000005', NULL, 'TC001', 'Phó chủ tịch', 95.00, '{\"TC001\":{\"score\":10,\"comment\":\"\"},\"TC002\":{\"score\":12,\"comment\":\"\"},\"TC003\":{\"score\":13,\"comment\":\"\"},\"TC004\":{\"score\":30,\"comment\":\"\"},\"TC005\":{\"score\":15,\"comment\":\"\"},\"TC006\":{\"score\":5,\"comment\":\"\"},\"TC007\":{\"score\":5,\"comment\":\"\"},\"TC008\":{\"score\":5,\"comment\":\"\"}}', 'Đã hoàn thành', '2025-08-07 22:23:45', NULL, 'Chưa đánh giá', NULL, 0);

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

--
-- Đang đổ dữ liệu cho bảng `thong_bao`
--

INSERT INTO `thong_bao` (`TB_MA`, `TB_NOIDUNG`, `TB_NGAYTAO`, `TB_DANHDOC`, `TB_LOAI`, `DT_MADT`, `GV_MAGV`, `SV_MASV`, `QL_MA`, `NGUOI_NHAN`, `TB_LINK`, `TB_TRANGTHAI`) VALUES
(1, 'Chào mừng bạn đến với hệ thống quản lý nghiên cứu! Đây là thông báo hệ thống để giới thiệu các tính năng mới.', '2025-06-02 11:14:14', 1, 'Hệ thống', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Có một đề tài nghiên cứu mới \"Ứng dụng AI trong giáo dục\" đang chờ được phê duyệt.', '2025-06-03 11:14:14', 0, 'Đề tài', 'DT001', NULL, NULL, '5', NULL, NULL, NULL),
(3, 'Giảng viên Nguyễn Văn A đã gửi báo cáo tiến độ cho đề tài \"Phát triển ứng dụng di động cho sinh viên\".', '2025-06-03 23:14:14', 0, 'Báo cáo', 'DT002', 'GV001', NULL, '5', NULL, NULL, NULL),
(4, 'Sinh viên Trần Thị B đã đăng ký tham gia đề tài \"Nghiên cứu về IoT và ứng dụng\".', '2025-06-04 06:14:14', 0, 'Đăng ký', 'DT003', NULL, 'SV001', '5', NULL, NULL, NULL),
(5, 'Đã có bản cập nhật mới cho hệ thống quản lý nghiên cứu. Vui lòng kiểm tra các tính năng mới được thêm vào.', '2025-06-04 09:14:14', 1, 'Hệ thống', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Thông báo test: Đề tài của bạn đã được phê duyệt', '2025-08-02 21:11:42', 0, 'thành công', NULL, NULL, NULL, NULL, 'GV001', '/NLNganh/view/teacher/view_project.php?id=TEST001', 'chưa đọc'),
(7, 'Đề tài của bạn (Ứng dụng trí tuệ nhân tạo trong nhận diện khuôn mặt thời gian thực) đã được phê duyệt', '2025-08-02 21:13:39', 0, 'success', NULL, NULL, NULL, NULL, 'GV000002', '/NLNganh/view/teacher/view_project.php?id=DT0000032', 'chưa đọc'),
(8, 'Đề tài của bạn (sÀGFS) đã được phê duyệt', '2025-08-03 21:48:34', 0, 'success', NULL, NULL, NULL, NULL, 'GV000002', '/NLNganh/view/teacher/view_project.php?id=DT0000033', 'chưa đọc'),
(9, 'Đề tài của bạn (ưetrhetrh) đã được phê duyệt', '2025-08-04 15:33:38', 0, 'success', NULL, NULL, NULL, NULL, 'GV000002', '/NLNganh/view/teacher/view_project.php?id=DT0000031', 'chưa đọc'),
(10, 'Đề tài của bạn (Xây dựng hệ thống giám sát chất lượng nước ao nuôi tôm bằng IoT và học máy) đã được phê duyệt', '2025-08-04 16:59:11', 0, 'success', NULL, NULL, NULL, NULL, 'GV000002', '/NLNganh/view/teacher/view_project.php?id=DT0000001', 'chưa đọc'),
(11, 'Đề tài của bạn (Xây dựng hệ thống quản lý học tập trực tuyến tích hợp AI chấm điểm tự động) đã được phê duyệt', '2025-08-04 23:17:49', 0, 'success', NULL, NULL, NULL, NULL, 'GV000002', '/NLNganh/view/teacher/view_project.php?id=DT0000002', 'chưa đọc'),
(12, 'Đề tài của bạn (Ứng dụng Blockchain trong truy xuất nguồn gốc thực phẩm) đã được phê duyệt', '2025-08-06 13:32:41', 0, 'success', NULL, NULL, NULL, NULL, 'GV000003', '/NLNganh/view/teacher/view_project.php?id=DT0000003', 'chưa đọc');

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

--
-- Đang đổ dữ liệu cho bảng `tien_do_de_tai`
--

INSERT INTO `tien_do_de_tai` (`TDDT_MA`, `DT_MADT`, `SV_MASV`, `TDDT_TIEUDE`, `TDDT_NOIDUNG`, `TDDT_PHANTRAMHOANTHANH`, `TDDT_FILE`, `TDDT_NGAYCAPNHAT`) VALUES
('TD04081158', 'DT0000001', 'B2110051', 'Khởi động đề tài', 'Đã đăng ký đề tài, đang chờ phê duyệt.', 0, NULL, '2025-08-04 11:58:49'),
('TD04081817', 'DT0000002', 'B2110051', 'Khởi động đề tài', 'Đã đăng ký đề tài, đang chờ phê duyệt.', 0, NULL, '2025-08-04 18:17:34'),
('TD05081531', 'DT0000003', 'B2110051', 'Khởi động đề tài', 'Đã đăng ký đề tài, đang chờ phê duyệt.', 0, NULL, '2025-08-05 15:31:22'),
('TD250804.6', 'DT0000001', 'B2110051', 'Cập nhật thông tin biên bản nghiệm thu', 'Thông tin biên bản nghiệm thu đã được cập nhật.\n\nLý do: ưdd\n\nChi tiết biên bản:\n- Ngày nghiệm thu: 04/01/2026\n- Xếp loại: Xuất sắc\n- Tổng điểm: 100.00/100\n', 100, NULL, '2025-08-04 20:32:30'),
('TD25080411', 'DT0000001', 'B2110051', 'Tạo hợp đồng mới', 'Thông tin hợp đồng đã được tạo mới.\n\nLý do: xyz\n\nChi tiết hợp đồng:\n- Mã hợp đồng: HDDT0000001\n- Ngày tạo: 04/08/2025\n- Thời gian thực hiện: 04/08/2025 - 04/02/2026\n- Tổng kinh phí: 35,000,000 VNĐ\n- File hợp đồng: contract_HDDT0000001_1754301639.docx\n- Mô tả: abc\n', 0, NULL, '2025-08-04 17:00:39'),
('TD25080417', 'DT0000002', 'B2110051', 'Tạo quyết định nghiệm thu', 'Thông tin quyết định nghiệm thu đã được tạo mới.\n\nLý do: ưdwdwd\n\nChi tiết quyết định:\n- Số quyết định: 123abc\n- Ngày ra quyết định: 04/01/2026\n- File quyết định: decision_123abc_1754324602.docx\n- Nội dung: sqsq\n', 100, NULL, '2025-08-04 23:23:22'),
('TD25080418', 'DT0000002', 'B2110051', 'Cập nhật thành viên hội đồng nghiệm thu', 'Thành viên hội đồng nghiệm thu đã được cập nhật.\n\nDanh sách thành viên:\n- Huỳnh Thanh Phong (Chủ tịch)\n', 100, NULL, '2025-08-04 23:59:03'),
('TD25080422', 'DT0000002', 'B2110051', 'Cập nhật file thuyết minh', 'File thuyết minh đã được cập nhật.\n\nLý do cập nhật: adaesd\nFile mới: proposal_DT0000002_1754324302.docx', 0, NULL, '2025-08-04 23:18:22'),
('TD25080446', 'DT0000001', 'B2110051', 'Tạo quyết định nghiệm thu', 'Thông tin quyết định nghiệm thu đã được tạo mới.\n\nLý do: xyz\n\nChi tiết quyết định:\n- Số quyết định: QDDT0000001\n- Ngày ra quyết định: 04/01/2026\n- File quyết định: decision_QDDT0000001_1754301665.docx\n- Nội dung: abc\n', 100, NULL, '2025-08-04 17:01:05'),
('TD25080466', 'DT0000001', 'B2110051', 'Cập nhật file thuyết minh', 'File thuyết minh đã được cập nhật.\n\nLý do cập nhật: chỉnh sửa, bổ sung\nFile mới: proposal_DT0000001_1754301592.docx', 0, NULL, '2025-08-04 16:59:52'),
('TD25080491', 'DT0000002', 'B2110051', 'Tạo hợp đồng mới', 'Thông tin hợp đồng đã được tạo mới.\n\nLý do: zazasdwd\n\nChi tiết hợp đồng:\n- Mã hợp đồng: 123abc\n- Ngày tạo: 04/08/2025\n- Thời gian thực hiện: 04/08/2025 - 04/02/2026\n- Tổng kinh phí: 25,000,000 VNĐ\n- File hợp đồng: contract_123abc_1754324552.docx\n- Mô tả: âzza\n', 0, NULL, '2025-08-04 23:22:32'),
('TD25080614', 'DT0000003', 'B2110051', 'Cập nhật file thuyết minh', 'File thuyết minh đã được cập nhật.\n\nLý do cập nhật: Bổ sung thông tin yêu cầu\nFile mới: proposal_DT0000003_1754486646.docx', 0, NULL, '2025-08-06 20:24:06'),
('TD25080617', 'DT0000003', 'B2110051', 'Cập nhật thành viên hội đồng nghiệm thu', 'Thành viên hội đồng nghiệm thu đã được cập nhật.\n\nDanh sách thành viên:\n- Huỳnh Thanh Phong (Chủ tịch)\n- Lê Minh Tuấn (Phó chủ tịch)\n- Nguyễn Thị Hoa (Thư ký)\n- Trần Văn Bình (Thành viên)\n', 100, NULL, '2025-08-06 22:39:37'),
('TD25080666', 'DT0000003', 'B2110051', 'Tạo hợp đồng mới', 'Thông tin hợp đồng đã được tạo mới.\n\nLý do: Cập nhật lần đầu\n\nChi tiết hợp đồng:\n- Mã hợp đồng: HDDT0000003\n- Ngày tạo: 06/08/2025\n- Thời gian thực hiện: 06/08/2025 - 06/02/2026\n- Tổng kinh phí: 20,000,000 VNĐ\n- File hợp đồng: contract_HDDT0000003_1754489621.docx\n- Mô tả: Hợp đồng cho đề tài \"Ứng dụng Blockchain trong truy xuất nguồn gốc thực phẩm\r\n\"\n', 0, NULL, '2025-08-06 21:13:41'),
('TD25080671', 'DT0000003', 'B2110051', 'Tạo quyết định nghiệm thu', 'Thông tin quyết định nghiệm thu đã được tạo mới.\n\nLý do: xyz\n\nChi tiết quyết định:\n- Số quyết định: QDDT0000003\n- Ngày ra quyết định: 06/01/2026\n- File quyết định: decision_QDDT0000003_1754493397.docx\n- Nội dung: abc\n', 100, NULL, '2025-08-06 22:16:37'),
('TDDT081818', 'DT0000001', 'B2110051', 'Cập nhật biên bản nghiệm thu', 'Đã tạo mới thông tin biên bản nghiệm thu:\n- Số biên bản: BB00000004\n- Ngày nghiệm thu: 04/01/2026\n- Xếp loại: Xuất sắc\n- Tổng điểm: 100/100\n- Phương pháp tính điểm: Nhập thủ công\n', 100, NULL, '2025-08-05 23:31:47'),
('TDDT081819', 'DT0000002', 'B2110051', 'Cập nhật biên bản nghiệm thu', 'Đã tạo mới thông tin biên bản nghiệm thu:\n- Số biên bản: BB00000005\n- Ngày nghiệm thu: 04/01/2026\n- Xếp loại: Xuất sắc\n- Tổng điểm: 100/100\n- Phương pháp tính điểm: Nhập thủ công\n', 100, NULL, '2025-08-05 23:33:08');

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

--
-- Đang đổ dữ liệu cho bảng `tieu_chi`
--

INSERT INTO `tieu_chi` (`TC_MATC`, `TC_TEN`, `TC_MOTA`, `TC_NDDANHGIA`, `TC_DIEMTOIDA`, `TC_TRONGSO`, `TC_THUTU`, `TC_TRANGTHAI`) VALUES
('TC001', NULL, NULL, 'Tổng quan tình hình nghiên cứu, lý do chọn đề tài', 10, 20.00, 1, 'Hoạt động'),
('TC002', NULL, NULL, 'Mục tiêu đề tài', 15, 20.00, 1, 'Hoạt động'),
('TC003', NULL, NULL, 'Phương pháp nghiên cứu', 15, 20.00, 1, 'Hoạt động'),
('TC004', NULL, NULL, 'Nội dung khoa học', 30, 20.00, 1, 'Hoạt động'),
('TC005', NULL, NULL, 'Đóng góp về mặt kinh tế - xã hội, giáo dục và đào tạo, an ninh, quốc phòng', 15, 20.00, 1, 'Hoạt động'),
('TC006', NULL, NULL, 'Hình thức trình bày báo cáo tổng kết đề tài', 5, 20.00, 1, 'Hoạt động'),
('TC007', NULL, NULL, 'Thời gian và tiến độ thực hiện đề tài (cho điểm 0 trong trường hợp đề tài nghiệm thu trễ hạn so với thuyết minh kể cả đề tài được duyệt gia hạn)', 5, 20.00, 1, 'Hoạt động'),
('TC008', NULL, NULL, 'Điểm thưởng: có bài báo đăng trên tạp chí khoa học có mã số ISSN, hoặc bài kỷ yếu Hội nghị/Hội thảo có Nhà Xuất Bản. Bài báo được xác nhận sẽ được đăng trên Tạp chí của Hội đồng biên tập cũng được tính điểm, phải có minh chứng bài báo hoặc giấy xác nhận cho Hội đồng (cho điểm 0 trong trường hợp không có bài báo).', 5, 20.00, 1, 'Hoạt động');

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

--
-- Đang đổ dữ liệu cho bảng `tieu_chi_danh_gia`
--

INSERT INTO `tieu_chi_danh_gia` (`TC_MA`, `TC_TEN`, `TC_MOTA`, `TC_DIEM_TOIDAI`, `TC_THUTU`, `TC_TRANGTHAI`, `TC_NGAYTAO`, `TC_NGAYCAPNHAT`) VALUES
('TC001', 'T├¡nh mß╗øi v├á t├¡nh s├íng tß║ío cß╗ºa ─æß╗ü t├ái', '─É├ính gi├í mß╗®c ─æß╗Ö mß╗øi mß║╗, s├íng tß║ío v├á t├¡nh khß║ú thi cß╗ºa ─æß╗ü t├ái nghi├¬n cß╗®u', 15.00, 1, 'Hoß║ít ─æß╗Öng', '2025-08-07 15:56:24', '2025-08-07 15:56:24'),
('TC002', 'Phã░ãíng ph├íp nghi├¬n cß╗®u', '─É├ính gi├í t├¡nh ph├╣ hß╗úp v├á hiß╗çu quß║ú cß╗ºa phã░ãíng ph├íp nghi├¬n cß╗®u ─æã░ß╗úc ├íp dß╗Ñng', 15.00, 2, 'Hoß║ít ─æß╗Öng', '2025-08-07 15:56:24', '2025-08-07 15:56:24'),
('TC003', 'Kß║┐t quß║ú nghi├¬n cß╗®u', '─É├ính gi├í chß║Ñt lã░ß╗úng v├á t├¡nh ─æß║ºy ─æß╗º cß╗ºa kß║┐t quß║ú nghi├¬n cß╗®u ─æß║ít ─æã░ß╗úc', 25.00, 3, 'Hoß║ít ─æß╗Öng', '2025-08-07 15:56:24', '2025-08-07 15:56:24'),
('TC004', 'T├¡nh ß╗®ng dß╗Ñng thß╗▒c tiß╗àn', '─É├ính gi├í khß║ú n─âng ß╗®ng dß╗Ñng thß╗▒c tiß╗àn v├á t├íc ─æß╗Öng cß╗ºa kß║┐t quß║ú nghi├¬n cß╗®u', 20.00, 4, 'Hoß║ít ─æß╗Öng', '2025-08-07 15:56:24', '2025-08-07 15:56:24'),
('TC005', 'Chß║Ñt lã░ß╗úng b├ío c├ío v├á thuyß║┐t tr├¼nh', '─É├ính gi├í chß║Ñt lã░ß╗úng cß╗ºa b├ío c├ío nghi├¬n cß╗®u v├á kß╗╣ n─âng thuyß║┐t tr├¼nh', 15.00, 5, 'Hoß║ít ─æß╗Öng', '2025-08-07 15:56:24', '2025-08-07 15:56:24'),
('TC006', 'Th├íi ─æß╗Ö v├á tinh thß║ºn nghi├¬n cß╗®u', '─É├ính gi├í th├íi ─æß╗Ö nghi├¬n cß╗®u khoa hß╗ìc v├á tinh thß║ºn hß╗ìc tß║¡p cß╗ºa sinh vi├¬n', 10.00, 6, 'Hoß║ít ─æß╗Öng', '2025-08-07 15:56:24', '2025-08-07 15:56:24');

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
(0, 'research_admin', '$2y$10$M.thWqWP.HjNKDnkdVW6D.JNyULT2RgqjpbVV3/G7oE78tK.MW42m', 'research_manager'),
(3, 'admin', '$2y$10$M.thWqWP.HjNKDnkdVW6D.JNyULT2RgqjpbVV3/G7oE78tK.MW42m', 'admin');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `view_chi_tiet_danh_gia`
-- (See below for the actual view)
--
CREATE TABLE `view_chi_tiet_danh_gia` (
`CTDDG_MA` char(10)
,`QD_SO` varchar(11)
,`QD_NGAY` date
,`BB_SOBB` char(11)
,`BB_NGAYNGHIEMTHU` date
,`GV_MAGV` char(8)
,`GV_HOTEN` varchar(59)
,`TV_VAITRO` varchar(30)
,`TV_HOTEN_HIENTHI` varchar(100)
,`TC_MATC` char(5)
,`TC_NDDANHGIA` text
,`TC_DIEMTOIDA` decimal(3,0)
,`CTDDG_DIEM` decimal(4,2)
,`CTDDG_GHICHU` text
,`CTDDG_NGAYCAPNHAT` datetime
,`TV_FILEDANHGIA` varchar(255)
,`TV_TRANGTHAI` enum('Chưa đánh giá','Đang đánh giá','Đã hoàn thành')
,`TV_NGAYDANHGIA` datetime
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

-- --------------------------------------------------------

--
-- Cấu trúc cho view `view_chi_tiet_danh_gia`
--
DROP TABLE IF EXISTS `view_chi_tiet_danh_gia`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_chi_tiet_danh_gia`  AS SELECT `ctddg`.`CTDDG_MA` AS `CTDDG_MA`, `ctddg`.`QD_SO` AS `QD_SO`, `qd`.`QD_NGAY` AS `QD_NGAY`, `bb`.`BB_SOBB` AS `BB_SOBB`, `bb`.`BB_NGAYNGHIEMTHU` AS `BB_NGAYNGHIEMTHU`, `tv`.`GV_MAGV` AS `GV_MAGV`, concat(`gv`.`GV_HOGV`,' ',`gv`.`GV_TENGV`) AS `GV_HOTEN`, `tv`.`TV_VAITRO` AS `TV_VAITRO`, `tv`.`TV_HOTEN` AS `TV_HOTEN_HIENTHI`, `ctddg`.`TC_MATC` AS `TC_MATC`, `tc`.`TC_NDDANHGIA` AS `TC_NDDANHGIA`, `tc`.`TC_DIEMTOIDA` AS `TC_DIEMTOIDA`, `ctddg`.`CTDDG_DIEM` AS `CTDDG_DIEM`, `ctddg`.`CTDDG_GHICHU` AS `CTDDG_GHICHU`, `ctddg`.`CTDDG_NGAYCAPNHAT` AS `CTDDG_NGAYCAPNHAT`, `tv`.`TV_FILEDANHGIA` AS `TV_FILEDANHGIA`, `tv`.`TV_TRANGTHAI` AS `TV_TRANGTHAI`, `tv`.`TV_NGAYDANHGIA` AS `TV_NGAYDANHGIA` FROM (((((`chi_tiet_diem_danh_gia` `ctddg` join `quyet_dinh_nghiem_thu` `qd` on(`ctddg`.`QD_SO` = `qd`.`QD_SO`)) left join `bien_ban` `bb` on(`qd`.`QD_SO` = `bb`.`QD_SO`)) join `thanh_vien_hoi_dong` `tv` on(`ctddg`.`QD_SO` = `tv`.`QD_SO` and `ctddg`.`GV_MAGV` = `tv`.`GV_MAGV`)) join `giang_vien` `gv` on(`ctddg`.`GV_MAGV` = `gv`.`GV_MAGV`)) join `tieu_chi` `tc` on(`ctddg`.`TC_MATC` = `tc`.`TC_MATC`)) ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_score_summary`
--
DROP TABLE IF EXISTS `v_score_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_score_summary`  AS SELECT 'thanh_vien_hoi_dong' AS `table_name`, count(0) AS `total_records`, count(`thanh_vien_hoi_dong`.`TV_DIEM`) AS `scored_records`, min(`thanh_vien_hoi_dong`.`TV_DIEM`) AS `min_score`, max(`thanh_vien_hoi_dong`.`TV_DIEM`) AS `max_score`, avg(`thanh_vien_hoi_dong`.`TV_DIEM`) AS `avg_score`, count(case when `thanh_vien_hoi_dong`.`TV_DIEM` < 0 or `thanh_vien_hoi_dong`.`TV_DIEM` > 100 then 1 end) AS `invalid_scores` FROM `thanh_vien_hoi_dong`union all select 'bien_ban' AS `table_name`,count(0) AS `total_records`,count(`bien_ban`.`BB_TONGDIEM`) AS `scored_records`,min(`bien_ban`.`BB_TONGDIEM`) AS `min_score`,max(`bien_ban`.`BB_TONGDIEM`) AS `max_score`,avg(`bien_ban`.`BB_TONGDIEM`) AS `avg_score`,count(case when `bien_ban`.`BB_TONGDIEM` < 0 or `bien_ban`.`BB_TONGDIEM` > 100 then 1 end) AS `invalid_scores` from `bien_ban`  ;

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
  ADD KEY `idx_bien_ban_tongdiem` (`BB_TONGDIEM`),
  ADD KEY `idx_bien_ban_tongdiem_new` (`BB_TONGDIEM`),
  ADD KEY `FK_BIEN_BAN_CO_BIEN_B_QUYET_DI` (`QD_SO`) USING BTREE;

--
-- Chỉ mục cho bảng `chi_tiet_danh_gia_tieu_chi`
--
ALTER TABLE `chi_tiet_danh_gia_tieu_chi`
  ADD PRIMARY KEY (`CDGTC_MA`),
  ADD UNIQUE KEY `unique_evaluation` (`CDGTC_MAGV`,`CDGTC_MADT`,`CDGTC_MATC`),
  ADD KEY `CDGTC_MATC` (`CDGTC_MATC`),
  ADD KEY `idx_chi_tiet_danh_gia_member` (`CDGTC_MAGV`,`CDGTC_MADT`),
  ADD KEY `idx_chi_tiet_danh_gia_project` (`CDGTC_MADT`);

--
-- Chỉ mục cho bảng `chi_tiet_diem_danh_gia`
--
ALTER TABLE `chi_tiet_diem_danh_gia`
  ADD PRIMARY KEY (`CTDDG_MA`),
  ADD UNIQUE KEY `unique_member_criteria` (`QD_SO`,`GV_MAGV`,`TC_MATC`),
  ADD KEY `idx_qd_so` (`QD_SO`),
  ADD KEY `idx_gv_magv` (`GV_MAGV`),
  ADD KEY `idx_tc_matc` (`TC_MATC`);

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
  ADD KEY `FK_DE_TAI_N_THUOC_LOAI_DE_` (`LDT_MA`),
  ADD KEY `FK_DE_TAI_N_RELATIONS_QUYET_DI` (`QD_SO`);

--
-- Chỉ mục cho bảng `file_danh_gia`
--
ALTER TABLE `file_danh_gia`
  ADD PRIMARY KEY (`FDG_MA`),
  ADD KEY `file_danh_gia_ibfk_1` (`BB_SOBB`);

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
-- AUTO_INCREMENT cho bảng `chi_tiet_danh_gia_tieu_chi`
--
ALTER TABLE `chi_tiet_danh_gia_tieu_chi`
  MODIFY `CDGTC_MA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `thong_bao`
--
ALTER TABLE `thong_bao`
  MODIFY `TB_MA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `user`
--
ALTER TABLE `user`
  MODIFY `USER_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  ADD CONSTRAINT `FK_BIEN_BAN_CO_BIEN_B_QUYET_DI` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_danh_gia_tieu_chi`
--
ALTER TABLE `chi_tiet_danh_gia_tieu_chi`
  ADD CONSTRAINT `chi_tiet_danh_gia_tieu_chi_ibfk_1` FOREIGN KEY (`CDGTC_MATC`) REFERENCES `tieu_chi_danh_gia` (`TC_MA`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_diem_danh_gia`
--
ALTER TABLE `chi_tiet_diem_danh_gia`
  ADD CONSTRAINT `fk_ctddg_giang_vien` FOREIGN KEY (`GV_MAGV`) REFERENCES `giang_vien` (`GV_MAGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ctddg_quyet_dinh` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctddg_tieu_chi` FOREIGN KEY (`TC_MATC`) REFERENCES `tieu_chi` (`TC_MATC`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `FK_DE_TAI_N_RELATIONS_QUYET_DI` FOREIGN KEY (`QD_SO`) REFERENCES `quyet_dinh_nghiem_thu` (`QD_SO`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_DE_TAI_N_THUOC_LOAI_DE_` FOREIGN KEY (`LDT_MA`) REFERENCES `loai_de_tai` (`LDT_MA`);

--
-- Các ràng buộc cho bảng `file_danh_gia`
--
ALTER TABLE `file_danh_gia`
  ADD CONSTRAINT `file_danh_gia_ibfk_1` FOREIGN KEY (`BB_SOBB`) REFERENCES `bien_ban` (`BB_SOBB`);

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
  ADD CONSTRAINT `FK_SINH_VIEN_THUOC_VE_LOP` FOREIGN KEY (`LOP_MA`) REFERENCES `lop` (`LOP_MA`);

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
