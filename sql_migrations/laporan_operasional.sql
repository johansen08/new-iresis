-- =============================================================
-- MIGRASI DATABASE: Modul Laporan Operasional
-- Jalankan script ini di phpMyAdmin atau HeidiSQL
-- =============================================================

-- 1. ALTER TABLE tblprintresi - tambah kolom untuk laporan
ALTER TABLE tblprintresi
  ADD COLUMN IF NOT EXISTS tipe_resi ENUM('satuan','campuran') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS trip TINYINT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS status_rts TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS rts_checked_at DATETIME DEFAULT NULL;

-- 2. CREATE TABLE tb_target_harian
CREATE TABLE IF NOT EXISTS tb_target_harian (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('picker','packer') NOT NULL,
  user_id INT DEFAULT NULL COMMENT 'NULL = target default role, INT = target spesifik pegawai',
  target INT NOT NULL DEFAULT 0,
  berlaku_dari DATE NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 3. CREATE TABLE tb_config_operasional
CREATE TABLE IF NOT EXISTS tb_config_operasional (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kunci VARCHAR(100) NOT NULL UNIQUE,
  nilai VARCHAR(255) NOT NULL,
  keterangan VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO tb_config_operasional (kunci, nilai, keterangan) VALUES
('batas_kirim_aman', '15:00', 'Jam batas cetak resi agar wajib kirim hari yang sama'),
('rts_trip1_jam', '16:00', 'Jadwal pemicu rekonsiliasi RTS Trip 1'),
('rts_trip2_jam', '19:00', 'Jadwal pemicu rekonsiliasi RTS Trip 2'),
('rts_interval_menit', '30', 'Selang waktu pengulangan audit RTS'),
('rts_batas_akhir', '23:30', 'Batas waktu akhir pengecekan RTS harian');

-- 4. CREATE TABLE tb_rts_log
CREATE TABLE IF NOT EXISTS tb_rts_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  trip TINYINT NOT NULL,
  siklus_jam DATETIME NOT NULL,
  jumlah_rts INT NOT NULL DEFAULT 0,
  detail TEXT DEFAULT NULL COMMENT 'Daftar nomor resi dalam format JSON',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 5. INSERT MENU: Laporan Operasional + Sub-menu
-- Parent menu
INSERT INTO menu (parentid, name, uri, icon, sortorder, isactive, createdby, created)
VALUES (0, 'Laporan Operasional', NULL, 'fa fa-bar-chart', 99, 1, 1, NOW());

SET @parent_id = LAST_INSERT_ID();

INSERT INTO menu (parentid, name, uri, icon, sortorder, isactive, createdby, created) VALUES
(@parent_id, 'Totalan Picker (Realtime)', 'laporan/totalan-picker', 'fa fa-caret-right', 1, 1, 1, NOW()),
(@parent_id, 'Totalan Packer (Realtime)', 'laporan/totalan-packer', 'fa fa-caret-right', 2, 1, 1, NOW()),
(@parent_id, 'Sisa Resi Belum Kirim',     'laporan/sisa-resi',      'fa fa-caret-right', 3, 1, 1, NOW()),
(@parent_id, 'Rekap Paket Keluar',         'laporan/paket-keluar',   'fa fa-caret-right', 4, 1, 1, NOW()),
(@parent_id, 'Rekap Pencapaian Staf',      'laporan/rekap-pencapaian','fa fa-caret-right', 5, 1, 1, NOW()),
(@parent_id, 'Pencapaian Target Harian',   'laporan/rekap-target',   'fa fa-caret-right', 6, 1, 1, NOW()),
(@parent_id, 'Kelola Target Pegawai',      'laporan/kelola-target',  'fa fa-caret-right', 7, 1, 1, NOW());

-- 6. GRANT ACCESS: Tambahkan semua sub-menu ke role Admin (roleid = 1)
-- Sesuaikan roleid jika admin menggunakan ID berbeda
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, 1, 1, NOW() FROM menu WHERE parentid = @parent_id;
