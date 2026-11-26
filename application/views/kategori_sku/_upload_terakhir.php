<?php
// Info "Upload kategori terakhir" (gaya sama dengan "Update otomatis terakhir" di Retur -> Daftar Retur Penjualan).
// Dipakai menu Update Kategori SKU dan Kategori SKU. $upload_terakhir dari Kategori_sku_fcd::upload_terakhir().
$u = isset($upload_terakhir) ? $upload_terakhir : null;
?>
<style>
  .ks-upd { display:flex; flex-wrap:wrap; align-items:center; gap:8px; padding:8px 0 0; font-size:12px; color:#6b7280; }
  .ks-upd .ku-judul { font-weight:600; color:#4b5563; }
  .ks-upd .ku-item { display:inline-flex; align-items:center; gap:6px; max-width:100%; padding:3px 10px; border:1px solid #e5e7eb; border-radius:999px; background:#f9fafb; }
  .ks-upd .ku-item i { color:#0f766e; }
  .ks-upd .ku-item b { color:#111827; font-weight:600; }
  .ks-upd .ku-item.kosong i, .ks-upd .ku-item.kosong b { color:#9ca3af; font-weight:400; }
</style>
<div class="ks-upd">
  <span class="ku-judul"><i class="fa fa-refresh"></i> Upload kategori terakhir</span>
  <?php if (!$u) : ?>
  <span class="ku-item kosong" title="Belum ada upload yang tercatat"><i class="fa fa-circle-o"></i> <b>belum ada catatan</b></span>
  <?php else : ?>
  <span class="ku-item" title="<?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($u['waktu'])) . ($u['nama_file'] ? ' - ' . $u['nama_file'] : '')) ?>"><i class="fa fa-check-circle"></i> Waktu: <b><?= date('d/m/Y H:i', strtotime($u['waktu'])) ?></b></span>
  <?php if ($u['oleh'] !== '') : ?>
  <span class="ku-item"><i class="fa fa-user"></i> Oleh: <b><?= htmlspecialchars($u['oleh']) ?></b></span>
  <?php endif; ?>
  <?php endif; ?>
</div>
