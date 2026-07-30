-- =============================================================================
-- Arsipkan catatan retur BIASA yang salah menu (resi kembar biasa+komplain)
-- =============================================================================
-- Kasus : 3 resi ter-scan di DUA menu (Update Retur DAN Update Retur Komplain),
--         sehingga punya 2 baris tblresiretur dan muncul di kedua Laporan
--         Verifikasi. Keputusan user 2026-07-28: yang BENAR adalah catatan
--         KOMPLAIN; catatan retur biasa diarsipkan.
--
-- Detail SKU (tblbukaretur) tercatat di sisi biasa -> DIPINDAH ke komplain
-- (is_komplain 0 -> 1) agar tidak hilang, lalu status_detail catatan komplain
-- diselaraskan dengan status baris buka-nya.
--
--   SPXID066549912287  komplain KE_DISPLAY  | buka GL-FNE-1     KE_DISPLAY (cocok)
--   SPXID068126654877  komplain KE_DISPLAY  | buka T01-PTAA-18  KE_DISPLAY (cocok)
--   SPXID068652138067  komplain BUKAN_RETUR | buka C226-GTJ-1   REJECT     (diselaraskan -> REJECT)
--
-- Backup : C:\xampp\backups\iresis-prod\retur_prearsip_salahmenu_*.sql
-- DB     : iresis-prod (MariaDB 10.4)
-- =============================================================================

-- 0. Daftar resi yang ditangani (kembar: punya is_komplain 0 DAN 1).
DROP TEMPORARY TABLE IF EXISTS `_resi_kembar`;
CREATE TEMPORARY TABLE `_resi_kembar` (
  `noresi` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`noresi`)
);
INSERT INTO `_resi_kembar` (`noresi`)
SELECT r.`noresi` FROM `tblresiretur` r
WHERE r.`id_resi` > 0
GROUP BY r.`noresi`
HAVING COUNT(DISTINCT r.`is_komplain`) > 1;

-- 1. Tabel arsip untuk baris resiretur yang dibuang (struktur sama = mudah restore).
CREATE TABLE IF NOT EXISTS `tblresiretur_salahmenu_arsip` LIKE `tblresiretur`;

-- 2. Pindahkan baris Buka Retur dari sisi biasa -> komplain (detail SKU tidak hilang).
--    Aman terhadap UNIQUE (resi_buka, sku, is_komplain): sisi komplain belum punya
--    baris untuk resi-resi ini.
UPDATE `tblbukaretur` b
JOIN `_resi_kembar` k ON k.`noresi` = b.`resi_buka`
SET b.`is_komplain` = 1
WHERE b.`is_komplain` = 0;

-- 3. Selaraskan status_detail catatan komplain dengan status baris buka-nya
--    (mis. BUKAN_RETUR -> REJECT untuk SPXID068652138067).
UPDATE `tblresiretur` r
JOIN `_resi_kembar` k ON k.`noresi` = r.`noresi`
JOIN (
    SELECT `resi_buka`, MIN(`status_detail_buka`) AS `st`
    FROM `tblbukaretur` WHERE `is_komplain` = 1 GROUP BY `resi_buka`
) b ON b.`resi_buka` = r.`noresi`
SET r.`status_detail` = b.`st`
WHERE r.`is_komplain` = 1
  AND (r.`status_detail` IS NULL OR r.`status_detail` <> b.`st`);

-- 4. Salin baris retur BIASA ke arsip, lalu hapus dari tabel live.
INSERT INTO `tblresiretur_salahmenu_arsip`
SELECT r.* FROM `tblresiretur` r
JOIN `_resi_kembar` k ON k.`noresi` = r.`noresi`
WHERE r.`is_komplain` = 0;

DELETE r FROM `tblresiretur` r
JOIN `_resi_kembar` k ON k.`noresi` = r.`noresi`
WHERE r.`is_komplain` = 0;

DROP TEMPORARY TABLE IF EXISTS `_resi_kembar`;

-- =============================================================================
-- ROLLBACK:
--   INSERT INTO `tblresiretur` SELECT * FROM `tblresiretur_salahmenu_arsip`;
--   UPDATE `tblbukaretur` SET `is_komplain` = 0
--     WHERE `resi_buka` IN ('SPXID066549912287','SPXID068126654877','SPXID068652138067');
--   UPDATE `tblresiretur` SET `status_detail` = 'BUKAN_RETUR'
--     WHERE `noresi` = 'SPXID068652138067' AND `is_komplain` = 1;
--   -- (atau restore penuh dari backup retur_prearsip_salahmenu_*.sql)
-- =============================================================================
