<?php
// View menu TIM CS -> Kesalahan List/Input Top Level. Dimuat lewat AJAX (SPA).
// Daftar kejadian kesalahan petugas list / petugas input yang ditandai di Input Top Level (tbltoplevel_kesalahan),
// dihitung per kejadian (dua transfer dari satu kejadian = satu kesalahan). Bentuk halaman mengikuti Manajemen Komplain CS New:
// periode, kartu ringkasan, tab, chip filter multi-pilih, tabel server-side, dan drawer detail di sisi kanan.
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Kesalahan List/Input Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif; ?>
<div class="kt-root">
<style>
  .kt-root { --kt-primary: #4f46e5; --kt-primary-light: rgba(79, 70, 229, .1); --kt-bg: #f8fafc; --kt-border: #e2e8f0; --kt-text: #334155; --kt-muted: #64748b; font-family: 'Inter', 'Segoe UI', sans-serif; color: #1e293b; }
  .kt-root input, .kt-root select, .kt-root button { font-family: inherit; }
  .kt-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding: 4px 0 14px; }
  .kt-period { display: flex; align-items: center; gap: 10px; }
  .kt-period-label { font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--kt-muted); }
  .kt-period .kt-chip { height: 40px; }
  .kt-head-actions { display: flex; gap: 8px; flex: none; }
  .kt-btn { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 18px; border: 1px solid var(--kt-primary); border-radius: 8px; background: var(--kt-primary); color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all .2s ease; }
  .kt-btn:hover { background: #4338ca; border-color: #4338ca; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79, 70, 229, .2); }
  .kt-btn-2 { background: #fff; color: var(--kt-text); border-color: var(--kt-border); }
  .kt-btn-2:hover { background: var(--kt-bg); border-color: #cbd5e1; box-shadow: none; transform: none; }

  .kt-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
  .kt-kpi { border: 1px solid var(--kt-border); border-radius: 12px; padding: 14px 16px 12px; background: #fff; min-width: 0; }
  .kt-kpi-top { display: flex; align-items: center; gap: 8px; }
  .kt-kpi-ic { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; font-size: 14px; }
  .kt-ic-indigo { background: #eef2ff; color: #4338ca; } .kt-ic-red { background: #fef2f2; color: #b91c1c; }
  .kt-ic-amber { background: #fffbeb; color: #b45309; } .kt-ic-green { background: #ecfdf5; color: #047857; }
  .kt-kpi-label { font-size: 13px; font-weight: 600; color: var(--kt-text); }
  .kt-kpi-val { margin-top: 10px; font-size: 28px; line-height: 1.1; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
  .kt-kpi-val.kt-kpi-teks { font-size: 20px; line-height: 1.35; }
  .kt-kpi-note { margin-top: 4px; font-size: 12px; color: var(--kt-muted); }

  .kt-body { margin-top: 16px; padding: 16px 18px 12px; background: #fff; border: 1px solid var(--kt-border); border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
  .kt-tabs { display: flex; flex-wrap: wrap; gap: 4px; margin: 0 0 12px; border-bottom: 1px solid var(--kt-border); }
  .kt-tab { appearance: none; background: none; border: 0; border-bottom: 2px solid transparent; margin-bottom: -1px; padding: 8px 14px; font-size: 13.5px; font-weight: 600; color: #64748b; cursor: pointer; }
  .kt-tab:hover { color: #0f172a; }
  .kt-tab.aktif { color: var(--kt-primary); border-bottom-color: var(--kt-primary); }
  .kt-tab-n { display: inline-block; min-width: 20px; margin-left: 6px; padding: 1px 7px; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: 11.5px; font-weight: 700; text-align: center; }
  .kt-tab.aktif .kt-tab-n { background: var(--kt-primary-light); color: var(--kt-primary); }
  .kt-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 4px 0 14px; }
  .kt-search { position: relative; flex: 1 1 240px; max-width: 340px; }
  .kt-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
  .kt-search input { width: 100%; height: 38px; padding: 0 12px 0 34px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; background: #fff; color: #0f172a; box-sizing: border-box; }
  .kt-search input:focus { outline: none; border-color: var(--kt-primary); box-shadow: 0 0 0 3px var(--kt-primary-light); }
  .kt-filter { position: relative; }
  .kt-chip { display: inline-flex; align-items: center; gap: 6px; height: 38px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: var(--kt-text); font-size: 13.5px; font-weight: 500; cursor: pointer; margin: 0; white-space: nowrap; }
  .kt-chip:hover { background: var(--kt-bg); }
  .kt-chip .fa-angle-down { color: #94a3b8; }
  .kt-chip-count { display: none; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background: var(--kt-primary); color: #fff; font-size: 11px; font-weight: 700; line-height: 18px; text-align: center; }
  .kt-filter.aktif .kt-chip { border-color: #a5b4fc; background: #eef2ff; color: #3730a3; }
  .kt-filter.aktif .kt-chip-count { display: inline-block; }
  .kt-chip-date input { width: 232px; border: 0; background: transparent; font-size: 13.5px; color: var(--kt-text); cursor: pointer; padding: 0; }
  .kt-chip-date input:focus { outline: none; }
  .kt-chip-date .fa { color: #94a3b8; }
  .kt-pop { display: none; position: absolute; z-index: 1050; top: calc(100% + 6px); left: 0; min-width: 230px; padding: 6px; background: #fff; border: 1px solid var(--kt-border); border-radius: 10px; box-shadow: 0 12px 28px rgba(15, 23, 42, .14); }
  .kt-filter.open .kt-pop { display: block; }
  .kt-opt { display: flex; align-items: center; gap: 9px; margin: 0; padding: 8px 10px; border-radius: 6px; font-size: 13.5px; font-weight: 400; color: var(--kt-text); cursor: pointer; }
  .kt-opt:hover { background: var(--kt-bg); }
  .kt-opt input { margin: 0; accent-color: var(--kt-primary); width: 15px; height: 15px; }
  .kt-opt span { flex: 1; }
  .kt-pop-foot { margin-top: 4px; padding: 6px 10px 2px; border-top: 1px solid var(--kt-border); }
  .kt-pop-clear { border: 0; background: transparent; padding: 0; font-size: 12.5px; color: var(--kt-muted); cursor: pointer; }
  .kt-pop-clear:hover { color: var(--kt-primary); }
  .kt-reset { height: 38px; padding: 0 10px; border: 0; background: transparent; color: var(--kt-primary); font-size: 13.5px; font-weight: 600; cursor: pointer; border-radius: 8px; }
  .kt-reset:hover { background: var(--kt-primary-light); }

  .kt-table-wrap { border: 1px solid var(--kt-border); border-radius: 10px; overflow-x: auto; background: #fff; }
  .kt-table { width: 100% !important; margin: 0 !important; border-collapse: separate; border-spacing: 0; }
  .kt-table > thead > tr > th { padding: 10px 8px; background: var(--kt-bg); border-bottom: 1px solid var(--kt-border); border-top: 0; font-size: 11.5px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--kt-muted); white-space: nowrap; }
  .kt-table > thead > tr > th[class*="sorting"] { padding-right: 20px; }
  .kt-table > tbody > tr > td { padding: 10px 8px; border-top: 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; color: var(--kt-text); vertical-align: middle; }
  .kt-table > tbody > tr:nth-child(even) > td { background: #fafbfd; }
  .kt-table > tbody > tr:hover > td { background: #f1f5ff; cursor: pointer; }
  .kt-table > tbody > tr:last-child > td { border-bottom: 0; }
  .kt-table td:nth-child(2) { min-width: 130px; }
  .kt-id { display: block; font-weight: 600; color: var(--kt-primary); font-variant-numeric: tabular-nums; white-space: nowrap; }
  .kt-sub { display: block; margin-top: 1px; font-size: 12px; font-weight: 400; color: var(--kt-muted); white-space: nowrap; }
  .kt-muted { color: #94a3b8; }
  .kt-mono { font-family: Consolas, 'Courier New', monospace; font-size: 12.5px; font-weight: 600; color: #0f172a; white-space: nowrap; }
  .kt-ket { display: block; max-width: 280px; font-size: 13px; color: var(--kt-text); }
  .kt-pill { display: inline-block; padding: 3px 10px; margin: 1px 0; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
  .kt-pill-red { background: #fef2f2; color: #b91c1c; } .kt-pill-amber { background: #fffbeb; color: #b45309; }
  .kt-pill-green { background: #ecfdf5; color: #047857; } .kt-pill-indigo { background: #eef2ff; color: #4338ca; }
  .kt-pill-sky { background: #f0f9ff; color: #0369a1; } .kt-pill-slate { background: #f1f5f9; color: #475569; }
  .kt-duo { display: flex; flex-direction: column; gap: 4px; }
  .kt-person { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; font-size: 13px; }
  .kt-avatar { display: inline-flex; align-items: center; justify-content: center; flex: none; width: 20px; height: 20px; border-radius: 50%; background: #e0e7ff; color: #3730a3; font-size: 10px; font-weight: 700; }
  .kt-duo > span + span .kt-avatar { background: #fef3c7; color: #92400e; }
  .kt-trf { display: flex; align-items: center; gap: 6px; }
  .kt-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; flex: none; background: #94a3b8; }
  .kt-dot-in { background: #10b981; } .kt-dot-out { background: #ef4444; }
  .kt-copy { flex: none; width: 20px; height: 20px; padding: 0; border: 0; border-radius: 5px; background: transparent; color: #cbd5e1; font-size: 12px; cursor: pointer; line-height: 1; }
  .kt-table tbody tr:hover .kt-copy { color: #94a3b8; }
  .kt-copy:hover, .kt-table tbody tr:hover .kt-copy:hover { background: var(--kt-primary-light); color: var(--kt-primary); }
  .kt-copy.ok, .kt-copy.ok:hover { color: #059669; background: #ecfdf5; }
  .kt-actions { display: flex; gap: 2px; justify-content: flex-end; }
  .kt-icon-btn { width: 28px; height: 28px; border: 1px solid transparent; border-radius: 8px; background: transparent; color: #64748b; cursor: pointer; }
  .kt-icon-btn:hover { background: #fff; border-color: var(--kt-border); color: var(--kt-primary); }

  .kt-foot { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; padding: 12px 4px 0; font-size: 13px; color: var(--kt-muted); }
  .kt-foot .dataTables_info { padding: 0; }
  .kt-pager { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
  .kt-pager .dataTables_length label { margin: 0; font-weight: 400; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
  .kt-pager .dataTables_length select { height: 32px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 6px; background: #fff; color: var(--kt-text); }
  .kt-pager .dataTables_paginate { margin: 0; }
  .kt-pager .pagination { margin: 0; }
  .kt-pager .pagination > li > a { margin-left: 4px; border: 1px solid var(--kt-border); border-radius: 8px !important; color: var(--kt-text); padding: 5px 11px; font-size: 13px; }
  .kt-pager .pagination > li > a:hover { background: var(--kt-bg); }
  .kt-pager .pagination > .active > a, .kt-pager .pagination > .active > a:hover, .kt-pager .pagination > .active > a:focus { background: var(--kt-primary) !important; border-color: var(--kt-primary) !important; color: #fff !important; }
  .kt-pager .pagination > .disabled > a { color: #cbd5e1; background: #fff; }
  #kt-tabel_processing { background: rgba(255, 255, 255, .9); border: 1px solid var(--kt-border); border-radius: 8px; padding: 8px 14px; font-size: 13px; color: var(--kt-muted); box-shadow: 0 6px 16px rgba(15, 23, 42, .1); }

  /* Drawer sisi kanan (detail), sama dengan drawer Manajemen Komplain CS New */
  #ktDrawer { overflow: hidden !important; }
  .modal-dialog.kt-dr-dialog { position: fixed; top: 0; right: 0; bottom: 0; margin: 0 !important; height: 100vh; max-width: none; width: min(600px, 96vw); }
  .modal.fade .kt-dr-dialog { transform: translateX(100%); transition: transform .28s ease-out; }
  .modal.in .kt-dr-dialog { transform: none; }
  .kt-dr-content { height: 100%; display: flex; flex-direction: column; border-radius: 16px 0 0 16px; overflow: hidden; box-shadow: -16px 0 48px rgba(15, 23, 42, .22); font-family: 'Inter', 'Segoe UI', sans-serif; color: #1e293b; }
  .kt-dr-header { flex: none; padding: 18px 24px 14px; border-bottom: 1px solid var(--kt-border); position: relative; }
  .kt-dr-header .close { position: absolute; right: 18px; top: 16px; }
  .kt-dr-titlebox { display: flex; align-items: center; gap: 12px; padding-right: 30px; }
  .kt-dr-icon { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background: var(--kt-primary-light); color: var(--kt-primary); font-size: 17px; flex: none; }
  .kt-dr-titlebox .modal-title { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0; }
  .kt-dr-meta { margin-top: 3px; font-size: 12.5px; color: var(--kt-muted); }
  .kt-dr-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 18px 28px 8px; }
  .kt-dr-sec { margin-bottom: 20px; }
  .kt-dr-sec h5 { margin: 0 0 6px; font-size: 14px; font-weight: 700; color: #0f172a; }
  .kt-dr-row { display: flex; gap: 12px; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
  .kt-dr-row i.fa { flex: none; width: 18px; margin-top: 2px; text-align: center; color: #94a3b8; }
  .kt-dr-row dt { flex: 0 0 120px; margin: 0; font-weight: 400; color: var(--kt-muted); }
  .kt-dr-row dd { flex: 1; margin: 0; font-weight: 600; color: #0f172a; word-break: break-word; }
  .kt-dr-ket { margin: 4px 0 0; padding: 10px 12px; background: var(--kt-bg); border: 1px solid var(--kt-border); border-radius: 10px; font-size: 13.5px; line-height: 1.55; white-space: pre-line; }
  .kt-dr-table { width: 100%; margin-top: 6px; border: 1px solid var(--kt-border); border-radius: 10px; border-collapse: separate; border-spacing: 0; overflow: hidden; font-size: 13px; }
  .kt-dr-table th { padding: 8px 12px; background: var(--kt-bg); font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--kt-muted); }
  .kt-dr-table td { padding: 9px 12px; border-top: 1px solid #f1f5f9; }
  .kt-dr-table .num { text-align: right; font-variant-numeric: tabular-nums; }
  .kt-dr-trf { display: flex; align-items: center; gap: 8px; margin: 12px 0 2px; font-size: 13.5px; }
  .kt-dr-footer { flex: none; padding: 12px 24px; border-top: 1px solid var(--kt-border); display: flex; justify-content: flex-end; gap: 8px; background: #fff; }

  @media (max-width: 1199px) { .kt-kpis { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 767px) {
    .kt-head { padding: 0 0 12px; }
    .kt-kpis { gap: 10px; }
    .kt-body { padding: 12px 12px 8px; }
    .kt-search { max-width: none; flex-basis: 100%; }
    .kt-chip-date input { width: 190px; }
    .modal-dialog.kt-dr-dialog { width: 100vw; }
    .kt-dr-content { border-radius: 0; }
  }
</style>

<div class="kt-page">
  <div class="kt-head">
    <div class="kt-period">
      <span class="kt-period-label">Periode</span>
      <label class="kt-chip kt-chip-date" title="Rentang tanggal kejadian dicatat">
        <i class="fa fa-calendar"></i>
        <input type="text" id="kt-range" readonly value="" placeholder="Semua tanggal" />
      </label>
    </div>
    <div class="kt-head-actions">
      <button type="button" class="kt-btn kt-btn-2" id="kt-export" title="Unduh daftar sesuai filter yang aktif"><i class="fa fa-download"></i> Export</button>
      <button type="button" class="kt-btn" id="kt-catat" title="Kesalahan dicatat dari Input Top Level dengan tanda Tandai sebagai kesalahan"><i class="fa fa-plus"></i> Catat Kesalahan</button>
    </div>
  </div>

  <div class="kt-kpis">
    <div class="kt-kpi">
      <div class="kt-kpi-top"><span class="kt-kpi-ic kt-ic-indigo"><i class="fa fa-list-alt"></i></span><span class="kt-kpi-label">Total Kesalahan</span></div>
      <div class="kt-kpi-val" id="kt-k-total">&ndash;</div>
      <div class="kt-kpi-note">kejadian pada periode &amp; filter terpilih</div>
    </div>
    <div class="kt-kpi">
      <div class="kt-kpi-top"><span class="kt-kpi-ic kt-ic-amber"><i class="fa fa-pencil-square-o"></i></span><span class="kt-kpi-label">Salah Petugas List</span></div>
      <div class="kt-kpi-val" id="kt-k-list">&ndash;</div>
      <div class="kt-kpi-note" id="kt-k-list-note">&nbsp;</div>
    </div>
    <div class="kt-kpi">
      <div class="kt-kpi-top"><span class="kt-kpi-ic kt-ic-red"><i class="fa fa-keyboard-o"></i></span><span class="kt-kpi-label">Salah Petugas Input</span></div>
      <div class="kt-kpi-val" id="kt-k-input">&ndash;</div>
      <div class="kt-kpi-note" id="kt-k-input-note">&nbsp;</div>
    </div>
    <div class="kt-kpi">
      <div class="kt-kpi-top"><span class="kt-kpi-ic kt-ic-green"><i class="fa fa-bar-chart"></i></span><span class="kt-kpi-label">Jenis Terbanyak</span></div>
      <div class="kt-kpi-val kt-kpi-teks" id="kt-k-jenis">&ndash;</div>
      <div class="kt-kpi-note" id="kt-k-jenis-note">&nbsp;</div>
    </div>
  </div>

  <div class="kt-body">
    <div class="kt-tabs" id="kt-tabs">
      <button type="button" class="kt-tab aktif" data-tab="semua">Semua<span class="kt-tab-n">&ndash;</span></button>
      <button type="button" class="kt-tab" data-tab="LIST">Petugas List<span class="kt-tab-n">&ndash;</span></button>
      <button type="button" class="kt-tab" data-tab="INPUT">Petugas Input<span class="kt-tab-n">&ndash;</span></button>
    </div>
    <div class="kt-toolbar">
      <div class="kt-search">
        <i class="fa fa-search"></i>
        <input type="text" id="kt-cari" placeholder="Cari kode, petugas, transfer, SKU, keterangan..." autocomplete="off" />
      </div>
      <div class="kt-filter" data-key="jenis">
        <button type="button" class="kt-chip"><span>Jenis</span><span class="kt-chip-count"></span><i class="fa fa-angle-down"></i></button>
        <div class="kt-pop">
          <?php foreach ($jenis_list as $kode => $nama) : ?>
            <label class="kt-opt"><input type="checkbox" value="<?= htmlspecialchars($kode) ?>"><span><?= htmlspecialchars($nama) ?></span></label>
          <?php endforeach; ?>
          <div class="kt-pop-foot"><button type="button" class="kt-pop-clear">Hapus pilihan</button></div>
        </div>
      </div>
      <div class="kt-filter" data-key="arah">
        <button type="button" class="kt-chip"><span>Arah koreksi</span><span class="kt-chip-count"></span><i class="fa fa-angle-down"></i></button>
        <div class="kt-pop">
          <label class="kt-opt"><input type="checkbox" value="MASUK"><span>Ada transfer Masuk</span></label>
          <label class="kt-opt"><input type="checkbox" value="KELUAR"><span>Ada transfer Keluar</span></label>
          <div class="kt-pop-foot"><button type="button" class="kt-pop-clear">Hapus pilihan</button></div>
        </div>
      </div>
      <button type="button" class="kt-reset" id="kt-reset" style="display:none;"><i class="fa fa-times"></i> Reset filter</button>
    </div>

    <div class="kt-table-wrap">
      <table class="kt-table table" id="kt-tabel">
        <thead>
          <tr><th>Kejadian</th><th>Jenis</th><th>Pihak yang salah</th><th>Transfer</th><th>Transfer asal</th><th>Keterangan</th><th>Dicatat oleh</th><th></th></tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Drawer detail kejadian -->
<div class="modal fade" id="ktDrawer" role="dialog" aria-labelledby="ktDrawerLabel" aria-hidden="true">
  <div class="modal-dialog kt-dr-dialog" role="document">
    <div class="modal-content kt-dr-content">
      <div class="kt-dr-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        <div class="kt-dr-titlebox">
          <span class="kt-dr-icon"><i class="fa fa-exclamation-triangle"></i></span>
          <div>
            <h4 class="modal-title" id="ktDrawerLabel">Kesalahan</h4>
            <div class="kt-dr-meta" id="kt-dr-meta"></div>
          </div>
        </div>
      </div>
      <div class="kt-dr-body" id="kt-dr-body"></div>
      <div class="kt-dr-footer">
        <button type="button" class="kt-btn kt-btn-2" data-dismiss="modal">Tutup</button>
        <button type="button" class="kt-btn" id="kt-dr-transfer"><i class="fa fa-exchange"></i> Buka Daftar Transfer</button>
      </div>
    </div>
  </div>
</div>
</div>

<script>
$(document).ready(function () {
  var NAMA_JENIS = <?= json_encode($jenis_list) ?>, NADA = <?= json_encode(Top_level::NADA_JENIS) ?>;
  var BLN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  // Halaman ini dimuat ulang lewat AJAX (SPA): lepas handler lama di document supaya tidak menumpuk
  $(document).off('.ktnew');

  var tabAktif = 'semua', filterAktif = { jenis: [], arah: [] };
  function esc(v) { return $('<div>').text(v === undefined || v === null || v === '' ? '-' : v).html(); }
  function noty2(teks, tipe) { if (window.noty) noty({ text: teks, layout: 'topRight', type: tipe || 'information', timeout: 3000 }); }
  function tglJam(s) { var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(s || ''); return m ? (+m[3]) + ' ' + BLN[m[2] - 1] + ' ' + m[1] + ' ' + m[4] + ':' + m[5] : (s || '-'); }
  function tgl(s) { var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || ''); return m ? (+m[3]) + ' ' + BLN[m[2] - 1] + ' ' + m[1] : '-'; }

  function sertakan(d) {
    d.tab = tabAktif;
    d.reportrange = $('#kt-range').val();
    d.f_jenis = filterAktif.jenis;
    d.f_arah = filterAktif.arah;
    return d;
  }

  var table = $('#kt-tabel').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: 'cs/kesalahan-top-level/data', type: 'POST', data: function (d) { sertakan(d); } },
    columns: [{ data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }, { data: 7, orderable: false }],
    order: [[0, 'desc']],
    pageLength: 25,
    autoWidth: false,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    dom: 't<"kt-foot"<"kt-info"i><"kt-pager"lp>>',
    language: {
      emptyTable: 'Tidak ada kesalahan pada tab, periode & filter ini',
      zeroRecords: 'Tidak ada kesalahan pada tab, periode & filter ini',
      info: 'Menampilkan _START_&ndash;_END_ dari _TOTAL_ kesalahan',
      infoEmpty: 'Menampilkan 0 kesalahan',
      infoFiltered: '',
      lengthMenu: 'Baris per halaman _MENU_',
      processing: 'Memuat...',
      paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
    }
  });

  // Kartu ringkasan dan jumlah tab: dimuat ulang setiap tabel digambar ulang, dengan filter yang sama
  var xhrRingkasan = null;
  function muatRingkasan() {
    var d = sertakan({});
    d.search_text = $('#kt-cari').val();
    if (xhrRingkasan) xhrRingkasan.abort();
    xhrRingkasan = $.post('cs/kesalahan-top-level/ringkasan', d, function (r) {
      if (!r || typeof r.total === 'undefined') return;
      var pct = function (n) { return r.total > 0 ? Math.round(n / r.total * 100) : 0; };
      $('#kt-k-total').text(r.total.toLocaleString('id-ID'));
      $('#kt-k-list').text(r.list.toLocaleString('id-ID'));
      $('#kt-k-list-note').text(pct(r.list) + '% dari kejadian');
      $('#kt-k-input').text(r.input.toLocaleString('id-ID'));
      $('#kt-k-input-note').text(pct(r.input) + '% dari kejadian');
      if (r.jenis_top) {
        $('#kt-k-jenis').text(NAMA_JENIS[r.jenis_top.kode] || r.jenis_top.kode);
        $('#kt-k-jenis-note').text(r.jenis_top.n.toLocaleString('id-ID') + ' kejadian');
      } else { $('#kt-k-jenis').html('&ndash;'); $('#kt-k-jenis-note').html('&nbsp;'); }
      $.each(r.tab, function (k, n) { $('.kt-tab[data-tab="' + k + '"] .kt-tab-n').text(n.toLocaleString('id-ID')); });
    }, 'json').fail(function (x, st) {
      if (st !== 'abort' && window.console) console.error('Ringkasan kesalahan gagal dimuat:', st, (x.responseText || '').slice(0, 500));
    });
  }
  table.on('draw.dt', muatRingkasan);

  var timerCari = null;
  $('#kt-cari').on('input', function () {
    var v = this.value;
    clearTimeout(timerCari);
    timerCari = setTimeout(function () { table.search(v).draw(); }, 300);
  });

  // Chip filter multi-pilih
  function perbaruiChip($f) {
    var n = filterAktif[$f.data('key')].length, ada = false;
    $f.toggleClass('aktif', n > 0);
    $f.find('.kt-chip-count').text(n);
    $.each(filterAktif, function (k, v) { if (v.length) ada = true; });
    $('#kt-reset').toggle(ada);
  }
  $(document).on('click.ktnew', '.kt-filter > .kt-chip', function (e) {
    e.stopPropagation();
    var $f = $(this).closest('.kt-filter');
    $('.kt-filter').not($f).removeClass('open');
    $f.toggleClass('open');
  });
  $(document).on('click.ktnew', function (e) { if (!$(e.target).closest('.kt-filter').length) $('.kt-filter').removeClass('open'); });
  $(document).on('change.ktnew', '.kt-opt input', function () {
    var $f = $(this).closest('.kt-filter');
    filterAktif[$f.data('key')] = $f.find('.kt-opt input:checked').map(function () { return this.value; }).get();
    perbaruiChip($f);
    table.ajax.reload();
  });
  $(document).on('click.ktnew', '.kt-pop-clear', function () {
    var $f = $(this).closest('.kt-filter');
    $f.find('.kt-opt input').prop('checked', false);
    filterAktif[$f.data('key')] = [];
    perbaruiChip($f);
    table.ajax.reload();
  });
  $('#kt-reset').on('click', function () {
    $('.kt-opt input').prop('checked', false);
    $.each(filterAktif, function (k) { filterAktif[k] = []; });
    $('.kt-filter').each(function () { perbaruiChip($(this)); });
    table.ajax.reload();
  });

  // Tab pihak yang salah
  $(document).on('click.ktnew', '.kt-tab', function () {
    tabAktif = $(this).data('tab');
    $('.kt-tab').removeClass('aktif');
    $(this).addClass('aktif');
    table.ajax.reload();
  });

  // Periode: kosong = semua tanggal; terisi hanya setelah pengguna memilih
  $('#kt-range').daterangepicker({
    locale: { format: 'YYYY-MM-DD' },
    autoUpdateInput: false,
    startDate: moment().startOf('month'),
    endDate: moment().endOf('month'),
    ranges: {
      'Semua Tanggal': [moment('2000-01-01'), moment()],
      'Hari Ini': [moment(), moment()],
      'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
      '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
      '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
      'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
      'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    }
  });
  $('#kt-range').on('apply.daterangepicker', function (ev, picker) {
    $(this).val(picker.chosenLabel === 'Semua Tanggal' ? '' : picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    table.ajax.reload();
  });

  // Export CSV sesuai filter aktif (form POST ke iframe tersembunyi)
  $('#kt-export').on('click', function () {
    var d = sertakan({});
    d.search_text = $('#kt-cari').val();
    var $f = $('<form method="post" class="nojs" action="cs/kesalahan-top-level/export" target="kt-unduh" style="display:none"></form>');
    $.each(d, function (k, v) {
      if ($.isArray(v)) $.each(v, function (i, x) { $f.append($('<input type="hidden">').attr('name', k + '[]').val(x)); });
      else $f.append($('<input type="hidden">').attr('name', k).val(v));
    });
    if (!$('#kt-unduh').length) $('body').append('<iframe name="kt-unduh" id="kt-unduh" style="display:none"></iframe>');
    $f.appendTo('body').submit();
    setTimeout(function () { $f.remove(); }, 2000);
    noty2('Menyiapkan file sesuai filter yang aktif...', 'success');
  });

  // Catat kesalahan = buka Input Top Level (tanda Tandai sebagai kesalahan ada di sana)
  $('#kt-catat').on('click', function () { $('a[href="cs/input-top-level"]').first().trigger('click'); });
  $('#kt-dr-transfer').on('click', function () { $('#ktDrawer').one('hidden.bs.modal', function () { $('a[href="cs/daftar-transfer-top-level"]').first().trigger('click'); }).modal('hide'); });

  // Salin nomor transfer. Aplikasi dibuka lewat http di LAN (bukan konteks aman): cadangannya textarea + execCommand('copy').
  function salinTeks(teks) {
    var cadangan = function () {
      return new Promise(function (resolve, reject) {
        var ta = document.createElement('textarea');
        ta.value = teks; ta.setAttribute('readonly', ''); ta.style.cssText = 'position:fixed;top:-1000px;opacity:0';
        document.body.appendChild(ta); ta.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        document.body.removeChild(ta);
        ok ? resolve() : reject();
      });
    };
    if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(teks).catch(cadangan);
    return cadangan();
  }
  $(document).on('click.ktnew', '.kt-copy', function (e) {
    e.stopPropagation(); e.preventDefault();
    var $b = $(this), teks = String($b.attr('data-copy') || '');
    if (!teks) return;
    salinTeks(teks).then(function () {
      $b.addClass('ok').find('i').removeClass('fa-files-o').addClass('fa-check');
      setTimeout(function () { $b.removeClass('ok').find('i').removeClass('fa-check').addClass('fa-files-o'); }, 1300);
    }, function () { noty2('Gagal menyalin. Blok teks lalu salin manual.', 'error'); });
  });

  // ---------------- Drawer detail ----------------
  function baris(ikon, label, isi) { return '<div class="kt-dr-row"><i class="fa ' + ikon + '"></i><dt>' + label + '</dt><dd>' + isi + '</dd></div>'; }
  function bukaDrawer(id) {
    $.post('cs/kesalahan-top-level/detail', { id: id }, function (r) {
      if (!r || r.code !== 200) { noty2((r && r.message) || 'Gagal mengambil detail kesalahan.', 'error'); return; }
      var k = r.data.d, t0 = k.transfer[0] || {};
      var pil = $.map(String(k.jenis).split(','), function (j) { return NAMA_JENIS[j] ? '<span class="kt-pill kt-pill-' + (NADA[j] || 'slate') + '">' + esc(NAMA_JENIS[j]) + '</span>' : null; }).join(' ');
      $('#ktDrawerLabel').html('Kesalahan ' + esc(k.kode));
      $('#kt-dr-meta').text('Dicatat ' + tglJam(k.dibuat_at) + ' oleh ' + (k.dibuat_by || '-'));
      var pihak = $.map(String(k.pihak).split(','), function (p) {
        return '<div>' + (p === 'LIST' ? 'Petugas list: ' + esc(t0.nama_tim) : 'Petugas input: ' + esc(t0.nama_input)) + '</div>';
      }).join('');
      var h = '<div class="kt-dr-sec"><h5>Ringkasan</h5>' +
        baris('fa-tag', 'Jenis', pil) + baris('fa-user', 'Pihak yang salah', pihak) +
        baris('fa-exchange', 'Transfer asal', k.asal ? '<span class="kt-mono">' + esc(k.asal.nomor) + '</span> <span class="kt-muted">(' + (k.asal.arah === 'MASUK' ? 'Masuk' : 'Keluar') + ', ' + tgl(k.asal.tgl_input) + ')</span>' : (k.ref_nomor ? '<span class="kt-mono">' + esc(k.ref_nomor) + '</span>' : '<span class="kt-muted">Tidak ada (lupa tulis / lupa input)</span>')) +
        '</div>';
      h += '<div class="kt-dr-sec"><h5>Keterangan</h5>' + (k.keterangan ? '<div class="kt-dr-ket">' + esc(k.keterangan) + '</div>' : '<span class="kt-muted">Tidak ada keterangan.</span>') + '</div>';
      h += '<div class="kt-dr-sec"><h5>Transfer koreksi (' + k.transfer.length + ')</h5>';
      $.each(k.transfer, function (i, t) {
        var masuk = t.arah === 'MASUK';
        h += '<div class="kt-dr-trf"><i class="kt-dot ' + (masuk ? 'kt-dot-in' : 'kt-dot-out') + '"></i><span class="kt-mono">' + esc(t.nomor) + '</span><span class="kt-pill kt-pill-' + (masuk ? 'green' : 'red') + '">' + (masuk ? 'Masuk' : 'Keluar') + '</span>' +
          '<span class="kt-muted">' + tgl(t.tgl_input) + '</span></div>' +
          '<table class="kt-dr-table"><thead><tr><th>SKU</th><th>No rak</th><th class="num">Qty</th><th class="num">Stok sebelum</th><th class="num">Stok sesudah</th></tr></thead><tbody>' +
          (t.baris.length ? $.map(t.baris, function (b) { return '<tr><td>' + esc(b.sku) + '</td><td>' + esc(b.rak) + '</td><td class="num"><b>' + (masuk ? '+' : '−') + b.qty + '</b></td><td class="num">' + b.sebelum + '</td><td class="num">' + b.sesudah + '</td></tr>'; }).join('') : '<tr><td colspan="5" class="kt-muted">Tidak ada baris.</td></tr>') +
          '</tbody></table>';
      });
      h += '</div>';
      $('#kt-dr-body').html(h);
      $('#ktDrawer').modal('show');
    }, 'json').fail(function () { noty2('Terjadi kesalahan koneksi server.', 'error'); });
  }
  $(document).on('click.ktnew', '#kt-tabel tbody tr', function (e) {
    if ($(e.target).closest('.kt-copy').length) return;
    var id = $(this).find('[data-detail]').data('detail');
    if (id) bukaDrawer(id);
  });
});
</script>
