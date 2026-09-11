-- =====================================================
-- PURCHASING & ACCOUNTING FEATURES MIGRATION SCRIPT
-- =====================================================
-- Run this script on your 'iresis' database in phpMyAdmin

-- 1. NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 2. TBLPENGEMBALIAN_QC (QC Returns from Restock team)
CREATE TABLE IF NOT EXISTS `tblpengembalian_qc` (
    `id_pengembalian` INT(11) NOT NULL AUTO_INCREMENT,
    `tanggal` DATE NOT NULL,
    `sku` VARCHAR(100) NOT NULL,
    `qty` INT(11) NOT NULL,
    `no_rak` VARCHAR(50) NOT NULL,
    `kondisi` ENUM('LEBIH AMBIL', 'REJECT') NOT NULL,
    `submit_by` INT(11) UNSIGNED NOT NULL,
    `acc_by` INT(11) UNSIGNED DEFAULT NULL,
    `acc_at` DATETIME DEFAULT NULL,
    `status` ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_pengembalian`),
    KEY `fk_qc_submit_by` (`submit_by`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 3. PURCHASING_REPAIR (Items sent for repair)
CREATE TABLE IF NOT EXISTS `purchasing_repair` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `no_rak` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `acc_by` int(11) DEFAULT NULL,
  `status_acc` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 4. PURCHASING_REJECT (Rejected items awaiting adjustment number)
CREATE TABLE IF NOT EXISTS `purchasing_reject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `no_rak` varchar(50) DEFAULT NULL,
  `no_penyesuaian` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 5. SURAT_JALAN (Delivery documents for Accounting / Inbound)
CREATE TABLE IF NOT EXISTS `surat_jalan` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nama_file` VARCHAR(255) NOT NULL,
    `jenis` VARCHAR(50) NOT NULL,
    `link_dokumen` TEXT NOT NULL,
    `status_inbound` VARCHAR(50) DEFAULT 'PENDING',
    `status_restock` VARCHAR(50) DEFAULT 'PENDING',
    `id_pegawai` INT(11) UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 6. SURAT_JALAN_TP (Third-party delivery documents)
CREATE TABLE IF NOT EXISTS `surat_jalan_tp` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nama_file` VARCHAR(255) NOT NULL,
    `link_dokumen` TEXT NOT NULL,
    `status_inbound` VARCHAR(50) DEFAULT 'PENDING',
    `status_restock` VARCHAR(50) DEFAULT 'PENDING',
    `id_pegawai` INT(11) UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 7. DENDA (Fines / penalty table for Finance)
CREATE TABLE IF NOT EXISTS `denda` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_pegawai` varchar(100) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text,
  `status` enum('PENDING','DONE') DEFAULT 'PENDING',
  `acc_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 8. PERGANTIAN_BARANG (Item replacement tracking for CS / Finance)
CREATE TABLE IF NOT EXISTS `pergantian_barang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_resi` varchar(50) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `keterangan` text,
  `status_acc` tinyint(1) DEFAULT '0',
  `acc_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- =====================================================
-- SELESAI - Semua tabel berhasil dibuat
-- =====================================================
