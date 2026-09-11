-- =============================================
-- MIGRATION: Purchasing Revisi 2026-04-27
-- Jalankan query ini di database iresis-prod
-- =============================================

-- 1. Tambah kolom no_penyesuaian dan foto_barang di tblpengembalian_qc (untuk giveaway)
ALTER TABLE `tblpengembalian_qc`
  ADD COLUMN IF NOT EXISTS `no_penyesuaian` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Nomor penyesuaian untuk Giveaway/Reject' AFTER `keterangan_reject`,
  ADD COLUMN IF NOT EXISTS `foto_barang` TEXT NULL DEFAULT NULL AFTER `no_penyesuaian`;

-- 2. Tambah kolom tanggal_proses di tblpengembalian_qc (memisahkan tgl masuk vs tgl proses)
ALTER TABLE `tblpengembalian_qc`
  ADD COLUMN IF NOT EXISTS `tanggal_proses` DATE NULL DEFAULT NULL COMMENT 'Tanggal saat barang diproses/disetujui' AFTER `foto_barang`;

-- 3. Tambah kolom no_penyesuaian dan foto_barang di purchasing_reject (jika belum ada)
ALTER TABLE `purchasing_reject`
  ADD COLUMN IF NOT EXISTS `no_penyesuaian` VARCHAR(100) NULL DEFAULT NULL AFTER `keterangan`,
  ADD COLUMN IF NOT EXISTS `foto_barang` TEXT NULL DEFAULT NULL AFTER `no_penyesuaian`,
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) NULL DEFAULT 'REJECTED' AFTER `foto_barang`;

-- 4. Update tanggal_proses untuk data yang sudah diproses
UPDATE `tblpengembalian_qc`
SET `tanggal_proses` = DATE(`acc_at`)
WHERE `acc_at` IS NOT NULL AND `tanggal_proses` IS NULL;
