-- =====================================================================
-- Riwayat pengeditan item Surat Jalan (worksheet) + dukungan "kembalikan".
-- Setiap perubahan sel (item/dokumen) dicatat: field, nilai lama -> baru.
-- Jalankan pada database iresis-prod.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `tblsj_item_history` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `id_item`    INT(11)     DEFAULT NULL,   -- tblsurat_jalan_items.id (NULL utk field level dokumen)
  `id_doc`     INT(11)     DEFAULT NULL,   -- tblsurat_jalan_doc.id
  `field`      VARCHAR(40) DEFAULT NULL,
  `nilai_lama` TEXT        DEFAULT NULL,
  `nilai_baru` TEXT        DEFAULT NULL,
  `sumber`     VARCHAR(20) DEFAULT 'EDIT', -- EDIT | REVERT
  `id_pegawai` INT(11)     DEFAULT NULL,
  `created_at` DATETIME    DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sjih_item` (`id_item`),
  KEY `idx_sjih_doc` (`id_doc`),
  KEY `idx_sjih_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
