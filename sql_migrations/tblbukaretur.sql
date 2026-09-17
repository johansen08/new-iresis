-- Diterapkan Mar 2026 (asal: create_tblbukaretur.php di root, PDO ke DB lama `iresis`).
-- Skema tblbukaretur sudah dibangun ulang kanonik 8 Jul 2026 (docs/arsip/HANDOFF_RETUR_VERIFIKASI.md).
CREATE TABLE IF NOT EXISTS `tblbukaretur` (
  `id_bukaretur` int(11) NOT NULL AUTO_INCREMENT,
  `status_buka` varchar(50) DEFAULT NULL,
  `status_detail_buka` varchar(100) DEFAULT NULL,
  `resi_buka` varchar(150) DEFAULT NULL,
  `hasil_scan_buka` text DEFAULT NULL,
  `sku` varchar(150) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `tanggal_buka_retur` datetime DEFAULT NULL,
  `id_pegawai` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_bukaretur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
