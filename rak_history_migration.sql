-- =====================================================================
-- History perubahan Nomor Rak (Gudang & Display) untuk master SKU.
-- Dipakai oleh: upload Rak Gudang (append), sync Rak Display dari
-- persediaan bundling (kolom J), dan edit inline di halaman Nomor Rak.
-- Jalankan pada database iresis-prod.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `tblrak_history` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `id_sku`     VARCHAR(100) DEFAULT NULL,
  `jenis`      VARCHAR(20)  DEFAULT NULL,   -- GUDANG | DISPLAY
  `rak_lama`   TEXT         DEFAULT NULL,
  `rak_baru`   TEXT         DEFAULT NULL,
  `sumber`     VARCHAR(50)  DEFAULT NULL,   -- UPLOAD | PERSEDIAAN | INLINE
  `id_pegawai` INT(11)      DEFAULT NULL,
  `created_at` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rakhist_sku` (`id_sku`),
  KEY `idx_rakhist_created` (`created_at`),
  KEY `idx_rakhist_jenis` (`jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
