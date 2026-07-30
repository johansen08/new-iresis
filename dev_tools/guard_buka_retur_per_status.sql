-- =============================================================================
-- Longgarkan guard Buka Retur: 1 SKU boleh dipecah per STATUS
-- =============================================================================
-- Kasus nyata: 1 SKU qty 2 -> 1 pcs KE_DISPLAY, 1 pcs REJECT.
-- Kunci unik lama (resi_buka, sku, is_komplain) memblokir submit ke-2.
--
-- Kunci baru menyertakan status_detail_buka, jadi:
--   - SKU sama + status BEDA   -> BOLEH (kasus di atas)
--   - SKU sama + status SAMA   -> baris digabung (qty ditambahkan) oleh aplikasi
-- Anti-dobel dijaga oleh validasi qty di aplikasi: total qty yang diproses
-- untuk (resi, sku) tidak boleh melebihi qty pesanan (tbldetailprintresi).
--
-- Aman: status_detail_buka tidak pernah NULL/'' (dicek: 0 dari 31.265 baris),
-- dan kunci baru tidak melanggar data yang ada (0 pelanggaran).
--
-- Backup : C:\xampp\backups\iresis-prod\retur_*.sql
-- DB     : iresis-prod (MariaDB 10.4)
-- =============================================================================

ALTER TABLE `tblbukaretur` DROP INDEX `uq_bukaretur_resi_sku_kmp`;
ALTER TABLE `tblbukaretur`
  ADD UNIQUE INDEX `uq_bukaretur_resi_sku_kmp_st`
  (`resi_buka`, `sku`, `is_komplain`, `status_detail_buka`);

-- =============================================================================
-- ROLLBACK:
--   ALTER TABLE `tblbukaretur` DROP INDEX `uq_bukaretur_resi_sku_kmp_st`;
--   ALTER TABLE `tblbukaretur` ADD UNIQUE INDEX `uq_bukaretur_resi_sku_kmp`
--     (`resi_buka`, `sku`, `is_komplain`);
--   -- (perlu dedup dulu bila sudah ada baris SKU sama beda status)
-- =============================================================================
