-- Migration: Manajemen Komplain CS
-- Database: iresis-prod

CREATE TABLE IF NOT EXISTS `tblcs_complain` (
  `no_resi` varchar(100) NOT NULL,
  `id_marketplace` int(11) NOT NULL,
  `toko` varchar(100) NOT NULL,
  `kategori_komplain` enum('Kurang Kirim','Reject','Paket Kosong','Salah Kirim','Tidak Sesuai Deskripsi','Lainnya') NOT NULL,
  `detail_lainnya` text DEFAULT NULL,
  `nama_qc` varchar(100) DEFAULT NULL,
  `nama_packer` varchar(100) DEFAULT NULL,
  `nominal_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  -- Proses Banding
  `tgl_banding_pengajuan` datetime DEFAULT NULL,
  `tgl_banding_tinjauan` datetime DEFAULT NULL,
  `tgl_claim_dana` date DEFAULT NULL,
  `keterangan_banding` text DEFAULT NULL,
  `nominal_claim_dana` decimal(15,2) DEFAULT 0.00,
  -- Metadata & Sync
  `created_at` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`no_resi`),
  KEY `idx_cs_complain_marketplace` (`id_marketplace`),
  KEY `idx_cs_complain_updated` (`updated_at`),
  KEY `idx_cs_complain_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblcs_complain_detail` (
  `id_complain_detail` int(11) NOT NULL AUTO_INCREMENT,
  `no_resi` varchar(100) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_complain_detail`),
  KEY `idx_cs_complain_detail_resi` (`no_resi`),
  KEY `idx_cs_complain_detail_deleted` (`is_deleted`),
  CONSTRAINT `fk_cs_complain_detail_resi` FOREIGN KEY (`no_resi`) REFERENCES `tblcs_complain` (`no_resi`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
