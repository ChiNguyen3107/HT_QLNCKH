-- Add SV_AVATAR column to sinh_vien table
USE ql_nckh;
ALTER TABLE sinh_vien ADD COLUMN SV_AVATAR VARCHAR(255) NULL;
