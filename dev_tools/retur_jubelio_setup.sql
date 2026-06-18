-- =====================================================================
-- Setup tabel validasi / rekonsiliasi retur dari Jubelio
-- Jalankan sekali di database iresis.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `tblreturjubelio` (
  `id_jubelio`      INT(11) NOT NULL AUTO_INCREMENT,
  `batch_id`        VARCHAR(40)  DEFAULT NULL COMMENT '1 upload = 1 batch',
  `no_resi`         VARCHAR(100) DEFAULT NULL COMMENT 'kolom E tracking_number',
  `no_pesanan`      VARCHAR(100) DEFAULT NULL COMMENT 'kolom U salesorder_no',
  `sku`             VARCHAR(150) DEFAULT NULL COMMENT 'kolom J SKU',
  `nama_barang`     VARCHAR(255) DEFAULT NULL COMMENT 'kolom K Nama Barang',
  `qty`             INT(11)      DEFAULT NULL COMMENT 'kolom I QTY',
  `amount`          DECIMAL(15,2) DEFAULT NULL COMMENT 'kolom N amount',
  `marketplace`     VARCHAR(100) DEFAULT NULL COMMENT 'kolom Y Sumber',
  `nama_toko`       VARCHAR(150) DEFAULT NULL COMMENT 'kolom Z store',
  `kurir`           VARCHAR(100) DEFAULT NULL COMMENT 'kolom AD Kurir',
  `status_jubelio`  VARCHAR(100) DEFAULT NULL COMMENT 'kolom AB Status',
  `tanggal_retur`   DATETIME     DEFAULT NULL COMMENT 'kolom V Tanggal',
  `match_resi`      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'resi ada di tblresiretur',
  `match_pesanan`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'no_pesanan ada di tbldetailprintresi',
  `found_in_iresis` TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'resi sudah discan retur di iresis',
  `uploaded_by`     INT(11)      DEFAULT NULL,
  `uploaded_at`     DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id_jubelio`),
  KEY `idx_resi` (`no_resi`),
  KEY `idx_pesanan` (`no_pesanan`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_tanggal` (`tanggal_retur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
