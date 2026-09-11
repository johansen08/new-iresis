-- Migration: Perluasan data auto-fill komplain dari nomor resi
-- Database: iresis-prod
-- Tanggal  : 2026-07-28
--
-- Saat CS memasukkan/scan nomor resi, sistem menarik sekaligus: nomor pesanan,
-- tanggal pesanan, SKU + qty, marketplace, toko, picker, dan packer.
-- Tanggal input dan siapa yang menginput sudah ditangani kolom created_at /
-- created_by yang lama, jadi tidak ditambah kolom baru untuk itu.

ALTER TABLE `tblcs_complain`
  ADD COLUMN `no_pesanan` VARCHAR(100) DEFAULT NULL AFTER `no_resi`,
  ADD COLUMN `tgl_pesanan` DATETIME DEFAULT NULL AFTER `tgl_komplain`,
  ADD COLUMN `nama_picker` VARCHAR(100) DEFAULT NULL AFTER `nama_packer`,
  ADD COLUMN `nilai_pesanan` DECIMAL(15,2) DEFAULT NULL AFTER `nominal_total`,
  ADD KEY `idx_cs_complain_pesanan` (`no_pesanan`);
