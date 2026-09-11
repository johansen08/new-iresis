-- =============================================================
-- DATABASE MIGRATION: Return to Display Batching System
-- Run this script in phpMyAdmin or HeidiSQL
-- =============================================================

-- 1. Create Table for Display Return Batch Headers
CREATE TABLE IF NOT EXISTS `tblretur_display_batch` (
  `id_batch` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_batch` VARCHAR(50) NOT NULL,
  `status` ENUM('DIKIRIM','DITERIMA') NOT NULL DEFAULT 'DIKIRIM',
  `total_qty` INT(11) NOT NULL DEFAULT 0,
  `created_by` INT(11) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `received_by` INT(11) DEFAULT NULL,
  `received_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_batch`),
  UNIQUE KEY `idx_kode_batch` (`kode_batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create Table for Display Return Batch Details
CREATE TABLE IF NOT EXISTS `tblretur_display_batch_detail` (
  `id_detail` INT(11) NOT NULL AUTO_INCREMENT,
  `batch_id` INT(11) NOT NULL,
  `id_bukaretur` INT(11) NOT NULL,
  `qty` INT(11) NOT NULL DEFAULT 1,
  `keterangan` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_id_bukaretur` (`id_bukaretur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Register Menu: Kirim ke Display under Tim Retur (parentid = 30)
INSERT INTO menu (parentid, name, uri, icon, sortorder, isactive, createdby, created)
SELECT 30, 'Kirim ke Display', 'retur/kirim-display', 'fa fa-send', 18, 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM menu WHERE uri = 'retur/kirim-display');

SET @menu_kirim_id = (SELECT id FROM menu WHERE uri = 'retur/kirim-display' LIMIT 1);

-- Grant access to Kirim ke Display (roles: 1, 2, 3, 6)
INSERT IGNORE INTO roleaccess (roleid, menuid, createdby, created) VALUES
(1, @menu_kirim_id, 1, NOW()),
(2, @menu_kirim_id, 1, NOW()),
(3, @menu_kirim_id, 1, NOW()),
(6, @menu_kirim_id, 1, NOW());

-- 4. Register Menu: Laporan Retur Display under Tim Restock (parentid = 77)
INSERT INTO menu (parentid, name, uri, icon, sortorder, isactive, createdby, created)
SELECT 77, 'Laporan Retur Display', 'restock/laporan-retur-display', 'fa fa-bar-chart', 40, 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM menu WHERE uri = 'restock/laporan-retur-display');

SET @menu_laporan_id = (SELECT id FROM menu WHERE uri = 'restock/laporan-retur-display' LIMIT 1);

-- Grant access to Laporan Retur Display (roles: 1, 2, 4, 6, 11)
INSERT IGNORE INTO roleaccess (roleid, menuid, createdby, created) VALUES
(1, @menu_laporan_id, 1, NOW()),
(2, @menu_laporan_id, 1, NOW()),
(4, @menu_laporan_id, 1, NOW()),
(6, @menu_laporan_id, 1, NOW()),
(11, @menu_laporan_id, 1, NOW());
