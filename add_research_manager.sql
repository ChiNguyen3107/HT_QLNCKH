-- Tạo bảng nguoi_dung nếu chưa tồn tại
CREATE TABLE IF NOT EXISTS `nguoi_dung` (
  `ND_MA` VARCHAR(20) NOT NULL,
  `ND_MATKHAU` VARCHAR(255) NOT NULL,
  `ND_VAITRO` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`ND_MA`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Thêm vai trò research_manager vào bảng user
INSERT INTO `user` (`USERNAME`, `PASSWORD`, `ROLE`) 
VALUES ('research_admin', '$2y$10$3n1zxJGWPptH9qMnJvHl5uKzmVb2YKzgWsBu0AmCBNG.kclQOVbCe', 'research_manager')  -- Mật khẩu: Research@123
ON DUPLICATE KEY UPDATE `ROLE` = VALUES(`ROLE`);

-- Tạo bảng quản lý nghiên cứu nếu cần thiết
CREATE TABLE IF NOT EXISTS `quan_ly_nghien_cuu` (
  `QL_MA` char(8) NOT NULL,
  `DV_MADV` char(5) NOT NULL,
  `QL_HO` varchar(20) NOT NULL,
  `QL_TEN` varchar(50) NOT NULL,
  `QL_EMAIL` varchar(50) NOT NULL,
  `QL_MATKHAU` varchar(255) NOT NULL,
  `QL_SDT` varchar(15) DEFAULT NULL,
  `QL_GIOITINH` tinyint(1) DEFAULT 1,
  `QL_NGAYSINH` date DEFAULT NULL,
  `QL_DIACHI` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`QL_MA`),
  UNIQUE KEY `QL_EMAIL` (`QL_EMAIL`),
  KEY `FK_QLNC_THUOC_VE_KHOA` (`DV_MADV`),
  CONSTRAINT `FK_QLNC_THUOC_VE_KHOA` FOREIGN KEY (`DV_MADV`) REFERENCES `khoa` (`DV_MADV`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
