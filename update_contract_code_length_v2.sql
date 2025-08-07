-- Bước 1: Gỡ bỏ các ràng buộc khóa ngoại liên quan đến hop_dong.HD_MA
ALTER TABLE `nguon_kinh_phi` DROP FOREIGN KEY `FK_NGUON_KI_CO_TU_HOP_DONG`;
ALTER TABLE `de_tai_nghien_cuu` DROP FOREIGN KEY `FK_DE_TAI_CO_HOP_DONG`;

-- Bước 2: Thay đổi kiểu dữ liệu của các cột liên quan
-- Thay đổi bảng hop_dong (bảng chính)
ALTER TABLE `hop_dong` MODIFY COLUMN `HD_MA` VARCHAR(11) NOT NULL;

-- Thay đổi bảng nguon_kinh_phi (bảng có khóa ngoại)
ALTER TABLE `nguon_kinh_phi` MODIFY COLUMN `HD_MA` VARCHAR(11) NOT NULL;

-- Thay đổi bảng de_tai_nghien_cuu (bảng có khóa ngoại)
ALTER TABLE `de_tai_nghien_cuu` MODIFY COLUMN `HD_MA` VARCHAR(11) DEFAULT NULL;

-- Bước 3: Thêm lại các ràng buộc khóa ngoại với cấu trúc mới
ALTER TABLE `nguon_kinh_phi` ADD CONSTRAINT `FK_NGUON_KI_CO_TU_HOP_DONG` FOREIGN KEY (`HD_MA`) REFERENCES `hop_dong` (`HD_MA`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `de_tai_nghien_cuu` ADD CONSTRAINT `FK_DE_TAI_CO_HOP_DONG` FOREIGN KEY (`HD_MA`) REFERENCES `hop_dong` (`HD_MA`) ON DELETE SET NULL ON UPDATE CASCADE;

