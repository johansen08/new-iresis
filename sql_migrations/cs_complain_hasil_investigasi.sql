-- Migration: Hasil investigasi komplain + poin ke KPI packer
-- Database: iresis-prod
-- Tanggal  : 2026-07-28
--
-- Empat pilihan (QC Salah / QC Benar / Tidak Terlihat CCTV / CCTV Tidak Bisa Diakses)
-- adalah HASIL PENGECEKAN atas komplain, bukan jenis komplainnya. Karena itu
-- disimpan di kolom sendiri supaya kategori aslinya (Kurang Kirim, Reject, dst)
-- tidak hilang saat hasil cek CCTV diisi.
--
-- Bila hasil = 'QC Salah', satu baris tblmasalahpacker dibuat supaya komplain itu
-- ikut terhitung sebagai kesalahan packer di rekapan KPI (bobot 50 poin per
-- kesalahan, lihat Kpi_reports::... total_eror = total_kesalahan * 50).

ALTER TABLE `tblcs_complain`
  ADD COLUMN `hasil_investigasi` ENUM(
    'QC Salah',
    'QC Benar',
    'Tidak Terlihat CCTV',
    'CCTV Tidak Bisa Diakses'
  ) DEFAULT NULL AFTER `status_penanganan`,
  ADD COLUMN `id_masalahpacker` INT(11) DEFAULT NULL AFTER `hasil_investigasi`,
  ADD KEY `idx_cs_complain_hasil` (`hasil_investigasi`),
  ADD KEY `idx_cs_complain_masalahpacker` (`id_masalahpacker`);

-- Jenis masalah packer khusus untuk poin yang lahir dari komplain CS
INSERT INTO `tbltypemasalahpacker` (`type_masalah`)
SELECT 'QC Salah (Komplain CS)'
WHERE NOT EXISTS (
  SELECT 1 FROM `tbltypemasalahpacker` WHERE `type_masalah` = 'QC Salah (Komplain CS)'
);
