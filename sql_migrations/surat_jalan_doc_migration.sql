-- =====================================================================
-- Surat Jalan — dokumen header + link ke item
-- Menu Tim Accounting -> Surat Jalan (siklus: upload persediaan ->
-- filter display minus -> request qty). Aman: tblsurat_jalan_items 0 baris.
-- Jalankan pada database iresis-prod.
-- =====================================================================

-- 1) Tabel header dokumen Surat Jalan
CREATE TABLE IF NOT EXISTS `tblsurat_jalan_doc` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `no_sj`          VARCHAR(50)  DEFAULT NULL,
  `tgl`            DATE         DEFAULT NULL,
  `jenis_sj`       VARCHAR(100) DEFAULT NULL,
  `no_trf_jubelio` VARCHAR(100) DEFAULT NULL,
  `status`         VARCHAR(30)  DEFAULT 'DRAFT',
  `id_pegawai`     INT(11)      DEFAULT NULL,
  `created_at`     DATETIME     DEFAULT NULL,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sj_doc_no` (`no_sj`),
  KEY `idx_sj_doc_tgl` (`tgl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 2) Kolom id_doc pada item (link ke dokumen). Kolom denormalized lama tetap.
ALTER TABLE `tblsurat_jalan_items`
  ADD COLUMN `id_doc` INT(11) NULL AFTER `id`,
  ADD KEY `idx_sj_items_doc` (`id_doc`);
