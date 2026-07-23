-- =============================================================================
-- Guard anti-dobel BUKA RETUR (tblbukaretur)
-- =============================================================================
-- Tujuan : 1 baris per (resi_buka, sku). Cegah dobel akibat double-submit /
--          scanner double-Enter. Arsipkan baris dobel LAMA (tidak dihapus),
--          lalu pasang UNIQUE index sebagai jaring pengaman DB.
--
-- Backup  : C:\xampp\backups\iresis-prod\retur_tables_*.sql (mysqldump 2 tabel)
-- DB      : iresis-prod  (MariaDB 10.4)
--
-- Keeper per (resi_buka, sku): utamakan baris ber-status KEPUTUSAN NYATA
-- (bukan KE_DISPLAY/kosong), lalu id_bukaretur terkecil (tertua). Sisanya
-- dipindah ke tblbukaretur_dup_arsip.
--
-- JALANKAN URUT. Bungkus dalam transaksi; commit hanya bila jumlah cocok.
-- =============================================================================

-- 1. Tabel arsip = salinan struktur persis (biar restore = INSERT balik saja).
CREATE TABLE IF NOT EXISTS `tblbukaretur_dup_arsip` LIKE `tblbukaretur`;

-- 2. Tangkap id baris EKSTRA (semua kecuali keeper) ke tabel sementara.
DROP TEMPORARY TABLE IF EXISTS `_dup_extra`;
CREATE TEMPORARY TABLE `_dup_extra` (
  `id_bukaretur` INT(11) NOT NULL,
  PRIMARY KEY (`id_bukaretur`)
);

INSERT INTO `_dup_extra` (`id_bukaretur`)
SELECT `id_bukaretur` FROM (
  SELECT `id_bukaretur`,
         ROW_NUMBER() OVER (
           PARTITION BY `resi_buka`, `sku`
           ORDER BY
             CASE WHEN `status_detail_buka` IS NULL
                    OR `status_detail_buka` IN ('', '-', 'KE_DISPLAY')
                  THEN 1 ELSE 0 END,          -- keputusan nyata didahulukan
             `id_bukaretur`                    -- lalu baris tertua
         ) AS rn
  FROM `tblbukaretur`
  WHERE `sku` IS NOT NULL AND `sku` <> ''      -- SKU NULL/'' tidak bentrok di UNIQUE
) t
WHERE t.rn > 1;

-- 3. Pindahkan baris ekstra ke arsip.
INSERT INTO `tblbukaretur_dup_arsip`
SELECT b.* FROM `tblbukaretur` b
JOIN `_dup_extra` e ON e.`id_bukaretur` = b.`id_bukaretur`;

-- 4. Hapus baris ekstra dari tabel live.
DELETE b FROM `tblbukaretur` b
JOIN `_dup_extra` e ON e.`id_bukaretur` = b.`id_bukaretur`;

-- 5. Ganti index (resi_buka, sku) non-unik menjadi UNIQUE.
ALTER TABLE `tblbukaretur` DROP INDEX `idx_bukaretur_resi_sku`;
ALTER TABLE `tblbukaretur` ADD UNIQUE INDEX `uq_bukaretur_resi_sku` (`resi_buka`, `sku`);

DROP TEMPORARY TABLE IF EXISTS `_dup_extra`;

-- =============================================================================
-- ROLLBACK (bila perlu kembalikan):
--   ALTER TABLE `tblbukaretur` DROP INDEX `uq_bukaretur_resi_sku`;
--   ALTER TABLE `tblbukaretur` ADD INDEX `idx_bukaretur_resi_sku` (`resi_buka`,`sku`);
--   INSERT INTO `tblbukaretur` SELECT * FROM `tblbukaretur_dup_arsip`;
--   -- (atau restore penuh dari mysqldump di C:\xampp\backups\iresis-prod\)
-- =============================================================================
