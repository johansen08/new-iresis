-- Migration: Perluasan cakupan Manajemen Komplain CS
-- Database: iresis-prod
-- Tanggal  : 2026-07-28
--
-- Tujuan: modul bisa menampung SEGALA komplain atas produk yang sudah dikirim.
--   1. Multi-komplain per resi   -> PK pindah dari no_resi ke id_complain
--   2. Kategori diperluas + kolom sumber/channel, status penanganan, tgl komplain
--   3. Kaitan ke retur fisik     -> id_resiretur
--   4. Lampiran bukti            -> tblcs_complain_lampiran
--
-- Data lama (203 baris, Juni 2026) dimigrasi apa adanya; kolom baru dibiarkan
-- NULL supaya jelas mana yang belum dilengkapi CS.

-- ---------------------------------------------------------------------------
-- 0. Cadangan
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblcs_complain_backup_20260728` AS SELECT * FROM `tblcs_complain`;
CREATE TABLE IF NOT EXISTS `tblcs_complain_detail_backup_20260728` AS SELECT * FROM `tblcs_complain_detail`;

-- ---------------------------------------------------------------------------
-- 1. Lepas FK lama supaya PK master bisa diubah
-- ---------------------------------------------------------------------------
ALTER TABLE `tblcs_complain_detail` DROP FOREIGN KEY `fk_cs_complain_detail_resi`;

-- ---------------------------------------------------------------------------
-- 2. tblcs_complain: PK no_resi -> id_complain AUTO_INCREMENT
--    no_resi turun jadi index biasa (boleh berulang = multi komplain per resi)
-- ---------------------------------------------------------------------------
ALTER TABLE `tblcs_complain`
  DROP PRIMARY KEY,
  ADD COLUMN `id_complain` INT(11) NOT NULL AUTO_INCREMENT FIRST,
  ADD PRIMARY KEY (`id_complain`),
  ADD KEY `idx_cs_complain_resi` (`no_resi`);

-- ---------------------------------------------------------------------------
-- 3. Kolom baru + perluasan kategori
-- ---------------------------------------------------------------------------
ALTER TABLE `tblcs_complain`
  MODIFY COLUMN `kategori_komplain` ENUM(
    'Kurang Kirim',
    'Reject',
    'Paket Kosong',
    'Salah Kirim',
    'Tidak Sesuai Deskripsi',
    'Barang Rusak',
    'Paket Hilang',
    'Telat Kirim',
    'Salah Alamat',
    'Barang Tidak Original',
    'Lainnya'
  ) NOT NULL,
  ADD COLUMN `tgl_komplain` DATE DEFAULT NULL AFTER `no_resi`,
  ADD COLUMN `sumber_komplain` ENUM(
    'Chat Marketplace',
    'WhatsApp',
    'Telepon',
    'Review/Rating',
    'Email',
    'Retur Fisik',
    'Lainnya'
  ) DEFAULT NULL AFTER `detail_lainnya`,
  ADD COLUMN `status_penanganan` ENUM('Baru','Proses','Selesai','Ditolak') DEFAULT NULL AFTER `sumber_komplain`,
  ADD COLUMN `catatan_penanganan` TEXT DEFAULT NULL AFTER `status_penanganan`,
  ADD COLUMN `id_resiretur` INT(10) UNSIGNED DEFAULT NULL AFTER `catatan_penanganan`,
  ADD KEY `idx_cs_complain_tgl` (`tgl_komplain`),
  ADD KEY `idx_cs_complain_sumber` (`sumber_komplain`),
  ADD KEY `idx_cs_complain_status` (`status_penanganan`),
  ADD KEY `idx_cs_complain_resiretur` (`id_resiretur`);

-- ---------------------------------------------------------------------------
-- 4. tblcs_complain_detail: pindah relasi dari no_resi ke id_complain
--    Data lama 1 resi = 1 komplain, jadi pemetaan tidak ambigu.
-- ---------------------------------------------------------------------------
ALTER TABLE `tblcs_complain_detail`
  ADD COLUMN `id_complain` INT(11) DEFAULT NULL AFTER `id_complain_detail`,
  ADD KEY `idx_cs_complain_detail_master` (`id_complain`);

UPDATE `tblcs_complain_detail` d
  JOIN `tblcs_complain` c ON c.`no_resi` = d.`no_resi`
  SET d.`id_complain` = c.`id_complain`
  WHERE d.`id_complain` IS NULL;

-- Baris detail yatim (tidak punya master) diarsipkan lalu dibuang dari tabel aktif
CREATE TABLE IF NOT EXISTS `tblcs_complain_detail_orphan_20260728` AS
  SELECT * FROM `tblcs_complain_detail` WHERE `id_complain` IS NULL;
DELETE FROM `tblcs_complain_detail` WHERE `id_complain` IS NULL;

ALTER TABLE `tblcs_complain_detail`
  MODIFY COLUMN `id_complain` INT(11) NOT NULL,
  ADD CONSTRAINT `fk_cs_complain_detail_master`
    FOREIGN KEY (`id_complain`) REFERENCES `tblcs_complain` (`id_complain`)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- ---------------------------------------------------------------------------
-- 5. Lampiran bukti komplain
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblcs_complain_lampiran` (
  `id_lampiran` INT(11) NOT NULL AUTO_INCREMENT,
  `id_complain` INT(11) NOT NULL,
  `nama_file` VARCHAR(255) NOT NULL,
  `nama_asli` VARCHAR(255) DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `ukuran` INT(11) NOT NULL DEFAULT 0,
  `uploaded_by` INT(11) NOT NULL,
  `uploaded_at` DATETIME NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_lampiran`),
  KEY `idx_cs_lampiran_complain` (`id_complain`),
  KEY `idx_cs_lampiran_deleted` (`is_deleted`),
  CONSTRAINT `fk_cs_lampiran_complain`
    FOREIGN KEY (`id_complain`) REFERENCES `tblcs_complain` (`id_complain`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
