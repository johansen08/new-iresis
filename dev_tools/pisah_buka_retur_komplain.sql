-- =============================================================================
-- Pisahkan Buka Retur biasa vs komplain (tblbukaretur.is_komplain)
-- =============================================================================
-- Masalah : tblbukaretur tidak punya penanda komplain, sehingga 1 baris buka
--           menempel ke DUA baris tblresiretur (is_komplain 0 dan 1) → data
--           Update Retur Komplain ikut muncul di Laporan Verifikasi Retur
--           (dan sebaliknya).
--
-- Backup  : C:\xampp\backups\iresis-prod\retur_prekomplain_*.sql
-- DB      : iresis-prod (MariaDB 10.4)
--
-- Backfill: turunkan is_komplain dari tblresiretur berdasarkan noresi.
--           - resi hanya ada sbg komplain      -> 1   (365 baris)
--           - resi hanya ada sbg retur biasa   -> 0   (30.155 baris)
--           - resi ada dua-duanya (AMBIGU)     -> 0   (3 baris, lihat di bawah)
--           - resi tidak ada di tblresiretur   -> 0   (11 baris, default)
--
-- 3 baris AMBIGU (semua terbukti milik retur BIASA dari selisih waktu scan):
--   75814 SPXID066549912287 GL-FNE-1    KE_DISPLAY 2026-07-20 14:11:37 (scan biasa 14:11:22)
--   75815 SPXID068126654877 T01-PTAA-18 KE_DISPLAY 2026-07-20 14:13:10 (scan biasa 14:13:04)
--   77936 SPXID068652138067 C226-GTJ-1  REJECT     2026-07-27 15:50:44 (scan biasa 15:50:29)
--
-- JALANKAN URUT.
-- =============================================================================

-- 1. Tambah kolom penanda komplain (default 0 = retur biasa).
ALTER TABLE `tblbukaretur`
  ADD COLUMN `is_komplain` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '0=retur biasa, 1=retur komplain (mengikuti tblresiretur)'
  AFTER `sumber_input`;

-- 2. Backfill: tandai 1 HANYA untuk resi yang di tblresiretur ada sebagai
--    komplain DAN tidak ada sebagai retur biasa (yang ambigu tetap 0).
UPDATE `tblbukaretur` b
SET b.`is_komplain` = 1
WHERE EXISTS (
        SELECT 1 FROM `tblresiretur` r
        WHERE r.`noresi` = b.`resi_buka` AND r.`is_komplain` = 1
      )
  AND NOT EXISTS (
        SELECT 1 FROM `tblresiretur` r
        WHERE r.`noresi` = b.`resi_buka` AND r.`is_komplain` = 0
      );

-- 3. Index unik ikut memperhitungkan is_komplain: retur biasa & komplain untuk
--    (resi, SKU) yang sama boleh berdiri sendiri, tapi tetap anti-dobel.
ALTER TABLE `tblbukaretur` DROP INDEX `uq_bukaretur_resi_sku`;
ALTER TABLE `tblbukaretur`
  ADD UNIQUE INDEX `uq_bukaretur_resi_sku_kmp` (`resi_buka`, `sku`, `is_komplain`);

-- 4. Index bantu untuk join laporan (br.resi_buka + br.is_komplain).
ALTER TABLE `tblbukaretur` ADD INDEX `idx_buka_resi_kmp` (`resi_buka`, `is_komplain`);

-- =============================================================================
-- ROLLBACK:
--   ALTER TABLE `tblbukaretur` DROP INDEX `idx_buka_resi_kmp`;
--   ALTER TABLE `tblbukaretur` DROP INDEX `uq_bukaretur_resi_sku_kmp`;
--   ALTER TABLE `tblbukaretur` ADD UNIQUE INDEX `uq_bukaretur_resi_sku` (`resi_buka`,`sku`);
--   ALTER TABLE `tblbukaretur` DROP COLUMN `is_komplain`;
--   -- (atau restore penuh dari C:\xampp\backups\iresis-prod\retur_prekomplain_*.sql)
-- =============================================================================
