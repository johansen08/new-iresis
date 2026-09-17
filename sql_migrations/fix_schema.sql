-- Fix 1: Tambah kolom status_sortir ke tblpengembalian_qc (jika belum ada)
ALTER TABLE `tblpengembalian_qc` 
  ADD COLUMN IF NOT EXISTS `status_sortir` tinyint(1) DEFAULT 0 COMMENT '0=belum diproses, 1=sudah dipindah ke repair/reject';

-- Fix 2: Tambah kolom id_pengembalian ke purchasing_repair (sebagai FK reference)
ALTER TABLE `purchasing_repair`
  ADD COLUMN IF NOT EXISTS `id_pengembalian` int(11) DEFAULT NULL COMMENT 'FK ke tblpengembalian_qc';

-- Fix 3: Tambah kolom id_pengembalian ke purchasing_reject (sebagai FK reference)
ALTER TABLE `purchasing_reject`
  ADD COLUMN IF NOT EXISTS `id_pengembalian` int(11) DEFAULT NULL COMMENT 'FK ke tblpengembalian_qc';

-- Verifikasi
DESCRIBE `tblpengembalian_qc`;
DESCRIBE `purchasing_repair`;
DESCRIBE `purchasing_reject`;
