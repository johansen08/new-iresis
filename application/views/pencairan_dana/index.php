<?php
$BASE = rtrim(base_url(), '/') . '/';
$MP_JSON = json_encode(array_values($mp));
?>
<style>
/* Pencairan Dana (TIM FINANCE): ringkasan persentase + tab + filter + tabel bersih. Semua gaya di bawah #pd_wrap. */
#pd_wrap { --pd-hijau:#0b6b5e; --pd-teks:#1c2a33; --pd-redup:#4b5d68; --pd-garis:#e3e8ec;
           font-family:'Inter',system-ui,sans-serif; color:var(--pd-teks); max-width:1240px; margin:0 auto; padding:16px 28px 48px; }
#pd_wrap * { box-sizing:border-box; }
#pd_wrap button { font-family:inherit; }
/* Stylesheet aplikasi memaksa svg melebar penuh (atribut width/height diabaikan); ukuran ikon dikunci di sini. */
#pd_wrap svg { flex:none; width:18px; height:18px; max-width:none; }
#pd_wrap .pd-tip-btn svg { width:20px; height:20px; }
#pd_wrap .pd-mpbox svg { width:16px; height:16px; }
#pd_wrap .pd-kepala { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; margin-bottom:20px; }
#pd_wrap h1 { margin:0; font-size:24px; font-weight:700; color:#12212b; display:inline-block; vertical-align:middle; }
#pd_wrap .pd-aksi { display:flex; flex-wrap:wrap; gap:8px; }
#pd_wrap .pd-btn { min-height:44px; padding:0 18px; border:1px solid #c9d3d9; border-radius:10px; background:#fff; color:var(--pd-teks); font-size:14px; font-weight:600; cursor:pointer; }
#pd_wrap .pd-btn:hover { background:#f3f6f8; }
#pd_wrap .pd-btn.utama { border:0; background:var(--pd-hijau); color:#fff; }
#pd_wrap .pd-btn.utama:hover { background:#095a4f; }
#pd_wrap .pd-btn:disabled { opacity:.45; cursor:not-allowed; }
#pd_wrap .pd-kartu { background:#fff; border:1px solid var(--pd-garis); border-radius:14px; }

/* Ikon info + kotak penjelasan (hover / fokus / klik) */
#pd_wrap .pd-tip { position:relative; display:inline-flex; align-items:center; vertical-align:middle; }
#pd_wrap .pd-tip-btn { display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border:0; border-radius:22px; background:transparent; color:#6b7c87; cursor:help; padding:0; }
#pd_wrap .pd-tip-btn:hover, #pd_wrap .pd-tip-btn:focus { background:#e9eef1; color:var(--pd-hijau); outline:none; }
#pd_wrap .pd-tip-box { display:none; position:absolute; top:44px; left:0; z-index:40; width:280px; padding:14px 16px; border-radius:10px; background:#12212b; color:#fff; font-size:13px; line-height:1.5; font-weight:400; box-shadow:0 8px 24px rgba(18,33,43,.18); }
#pd_wrap .pd-tip.kanan .pd-tip-box { left:auto; right:0; }
#pd_wrap .pd-tip:hover .pd-tip-box, #pd_wrap .pd-tip:focus-within .pd-tip-box, #pd_wrap .pd-tip.buka .pd-tip-box { display:block; }
#pd_wrap .pd-dot { display:inline-block; width:10px; height:10px; border-radius:5px; margin-right:8px; }

/* Ringkasan */
#pd_wrap .pd-ring { padding:20px; display:flex; flex-direction:column; gap:16px; margin-bottom:24px; }
#pd_wrap .pd-ring-atas { display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:12px; }
#pd_wrap .pd-ring-label { font-size:13px; font-weight:600; color:var(--pd-redup); }
#pd_wrap .pd-ring-besar { font-size:32px; font-weight:700; color:#0b5a4d; line-height:1.2; }
#pd_wrap .pd-ring-besar small { font-size:15px; font-weight:500; color:var(--pd-redup); }
#pd_wrap .pd-ring-sub { font-size:14px; color:var(--pd-redup); font-variant-numeric:tabular-nums; }
#pd_wrap .pd-batang { display:flex; height:14px; border-radius:7px; overflow:hidden; background:#eef1f3; }
#pd_wrap .pd-batang > div { transition:width .25s; }
#pd_wrap .pd-kotak { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; }
#pd_wrap .pd-kotak > div { display:flex; flex-direction:column; gap:4px; padding:12px 14px; border-radius:10px; }
#pd_wrap .pd-kotak .t { font-size:13px; }
#pd_wrap .pd-kotak .a { font-size:24px; font-weight:700; font-variant-numeric:tabular-nums; }
#pd_wrap .pd-kotak .a small { font-size:14px; font-weight:600; }

/* Tab */
#pd_wrap .pd-tabbar { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:0 12px 0 20px; border-bottom:1px solid var(--pd-garis); }
#pd_wrap .pd-tabs { display:flex; flex-wrap:wrap; gap:4px; }
#pd_wrap .pd-tab { min-height:52px; padding:0 16px; margin-bottom:-1px; border:0; border-bottom:3px solid transparent; background:transparent; color:var(--pd-redup); font-size:15px; font-weight:500; cursor:pointer; }
#pd_wrap .pd-tab .n { color:#7a8a94; font-weight:500; }
#pd_wrap .pd-tab.on { border-bottom-color:var(--pd-hijau); color:#12212b; font-weight:700; }
#pd_wrap .pd-tab.on .n { color:var(--pd-hijau); font-weight:600; }

/* Filter */
#pd_wrap .pd-filter { display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end; padding:16px 20px; border-bottom:1px solid var(--pd-garis); }
#pd_wrap .pd-fgrup { position:relative; display:flex; flex-direction:column; gap:6px; }
#pd_wrap .pd-flabel { font-size:12px; font-weight:600; color:var(--pd-redup); }
#pd_wrap label.pd-flabel { font-size:12px; font-weight:600; line-height:1.5; margin:0; }
#pd_wrap .pd-input { display:inline-flex; align-items:center; gap:10px; min-height:44px; padding:0 14px; border:1px solid #c9d3d9; border-radius:10px; background:#fff; color:var(--pd-teks); font-size:14px; font-weight:500; cursor:pointer; }
#pd_wrap .pd-mpbox { display:inline-flex; align-items:center; min-height:44px; border:1px solid #c9d3d9; border-radius:10px; background:#fff; }
#pd_wrap .pd-mpbox .utama { display:inline-flex; align-items:center; gap:10px; min-height:42px; min-width:170px; padding:0 12px 0 14px; border:0; border-radius:10px; background:transparent; font-size:14px; cursor:pointer; }
#pd_wrap .pd-mpbox .utama .isi { flex:1; text-align:left; color:#7a8a94; }
#pd_wrap .pd-mpbox .utama .isi.ada { font-weight:600; color:var(--pd-teks); }
#pd_wrap .pd-mpbox .hapus { display:inline-flex; align-items:center; justify-content:center; width:40px; height:42px; border:0; border-left:1px solid var(--pd-garis); border-radius:0 10px 10px 0; background:transparent; color:#6b7c87; cursor:pointer; padding:0; }
#pd_wrap .pd-lapis { position:fixed; top:0; left:0; right:0; bottom:0; z-index:29; border:0; background:transparent; cursor:default; padding:0; }
#pd_wrap .pd-pop { position:absolute; top:76px; left:0; z-index:30; background:#fff; border:1px solid #dce3e8; border-radius:14px; box-shadow:0 12px 32px rgba(18,33,43,.16); }
#pd_wrap .pd-mplist { width:260px; padding:8px; }
#pd_wrap .pd-mplist label { display:flex; align-items:center; gap:12px; min-height:44px; padding:0 10px; border-radius:8px; font-size:14px; cursor:pointer; margin:0; font-weight:400; }
#pd_wrap .pd-mplist label:hover { background:#f3f6f8; }
#pd_wrap .pd-mplist input { width:20px; height:20px; accent-color:var(--pd-hijau); flex:none; margin:0; }
#pd_wrap .pd-mplist .nama { flex:1; }
#pd_wrap .pd-mplist .jml { color:#7a8a94; font-variant-numeric:tabular-nums; }
#pd_wrap .pd-mpfoot { display:flex; align-items:center; justify-content:space-between; margin-top:4px; padding:6px 4px 2px; border-top:1px solid var(--pd-garis); }
#pd_wrap .pd-link { min-height:44px; padding:0 10px; border:0; background:transparent; color:var(--pd-hijau); font-size:14px; font-weight:600; cursor:pointer; }
#pd_wrap .pd-cari { position:relative; display:flex; align-items:center; }
#pd_wrap .pd-cari > svg { position:absolute; left:14px; color:#6b7c87; pointer-events:none; }
#pd_wrap .pd-cari input[type=search] { width:300px; max-width:100%; min-height:44px; padding:0 44px 0 42px; border:1px solid #c9d3d9; border-radius:10px; background:#fff; color:var(--pd-teks); font-family:inherit; font-size:14px; outline:none; box-shadow:none; -webkit-appearance:none; }
#pd_wrap .pd-cari input[type=search]::-webkit-search-cancel-button { -webkit-appearance:none; display:none; }
#pd_wrap .pd-cari input[type=search]:focus { border-color:var(--pd-hijau); box-shadow:0 0 0 3px rgba(11,107,94,.15); }
#pd_wrap .pd-cari .bersih { position:absolute; right:2px; display:none; align-items:center; justify-content:center; width:40px; height:40px; border:0; border-radius:20px; background:transparent; color:#6b7c87; cursor:pointer; padding:0; }
#pd_wrap .pd-cari .bersih:hover { background:#e9eef1; color:var(--pd-teks); }
#pd_wrap .pd-cari .bersih svg { width:16px; height:16px; }
#pd_wrap mark.pd-hl { background:#fff1b3; color:inherit; padding:0 1px; border-radius:2px; }
#pd_wrap .pd-selisih { margin-top:4px; font-size:12px; color:var(--pd-redup); }
#pd_wrap .pd-cek { display:flex; align-items:center; gap:10px; min-height:44px; font-size:14px; font-weight:500; cursor:pointer; margin:0 0 0 auto; }
#pd_wrap .pd-cek input { width:20px; height:20px; accent-color:var(--pd-hijau); margin:0; }

/* Pemilih tanggal: 2 kolom (pilihan cepat | kalender) */
#pd_wrap .pd-dp { width:580px; max-width:calc(100vw - 32px); display:flex; flex-wrap:wrap; }
#pd_wrap .pd-dp-kiri { flex:0 0 176px; display:flex; flex-direction:column; gap:4px; padding:16px 12px; border-right:1px solid var(--pd-garis); }
#pd_wrap .pd-dp-kiri button { min-height:44px; padding:0 12px; border:0; border-radius:8px; background:transparent; color:var(--pd-teks); font-size:14px; font-weight:500; text-align:left; cursor:pointer; }
#pd_wrap .pd-dp-kiri button:hover { background:#f3f6f8; }
#pd_wrap .pd-dp-kiri button.on { background:#e3f1ee; color:#0b4f45; font-weight:600; }
#pd_wrap .pd-dp-kanan { flex:1 1 340px; min-width:0; display:flex; flex-direction:column; gap:14px; padding:16px; }
#pd_wrap .pd-dp-isi { display:flex; align-items:center; gap:8px; }
#pd_wrap .pd-dp-isi .k { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; padding:8px 12px; border-radius:10px; border:2px solid #dce3e8; background:#fff; }
#pd_wrap .pd-dp-isi .k.aktif { border-color:var(--pd-hijau); }
#pd_wrap .pd-dp-isi .k span:first-child { font-size:11px; font-weight:600; color:#6b7c87; }
#pd_wrap .pd-dp-isi .k span:last-child { font-size:14px; font-weight:600; font-variant-numeric:tabular-nums; }
#pd_wrap .pd-kal-kepala { display:flex; align-items:center; justify-content:space-between; height:44px; font-size:14px; font-weight:700; }
#pd_wrap .pd-ikon-btn { display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border:0; border-radius:10px; background:#fff; color:var(--pd-teks); cursor:pointer; padding:0; }
#pd_wrap .pd-ikon-btn:hover:not(:disabled) { background:#e9eef1; }
#pd_wrap .pd-ikon-btn:disabled { opacity:.4; cursor:not-allowed; }
#pd_wrap .pd-hari7 { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); height:32px; align-items:center; text-align:center; font-size:12px; font-weight:600; color:#6b7c87; }
#pd_wrap .pd-minggu { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); }
#pd_wrap .pd-tgl { height:40px; width:100%; border:0; border-radius:20px; background:transparent; color:var(--pd-teks); font-size:13px; cursor:pointer; padding:0; }
#pd_wrap .pd-tgl.range { background:#e3f1ee; color:#0b4f45; border-radius:0; }
#pd_wrap .pd-tgl.ujung { background:var(--pd-hijau); color:#fff; font-weight:700; }
#pd_wrap .pd-tgl.mati { color:#b4c0c7; cursor:default; }
#pd_wrap .pd-tgl.kosong { cursor:default; }
#pd_wrap .pd-petunjuk { font-size:12px; color:#6b7c87; }

/* Tabel */
#pd_tablewrap { position:relative; min-height:160px; overflow-x:auto; }
#pd_wrap table.pd-tabel { width:100%; min-width:1500px; border-collapse:collapse; }
#pd_wrap .pd-tabel th { padding:12px 14px; background:#f6f8fa; border-bottom:1px solid var(--pd-garis); font-size:12px; font-weight:600; color:#6b7c87; text-align:left; }
#pd_wrap .pd-tabel td { padding:12px 14px; border-bottom:1px solid #eef1f3; font-size:14px; vertical-align:middle; }
#pd_wrap .pd-tabel tbody tr:hover td { background:#fafbfc; }
#pd_wrap .pd-id { display:flex; align-items:center; gap:2px; margin:-8px 0; }
#pd_wrap .pd-id b { font-weight:600; font-variant-numeric:tabular-nums; white-space:nowrap; }
#pd_wrap .pd-salin { flex:none; display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border:0; border-radius:22px; background:transparent; color:#6b7c87; cursor:pointer; padding:0; }
#pd_wrap .pd-salin:hover { background:#e9eef1; color:var(--pd-hijau); }
#pd_wrap .pd-salin.sudah { background:#dff2ec; color:#0b5a4d; }
#pd_wrap .pd-lencana { display:inline-block; padding:4px 12px; border-radius:14px; font-size:12px; font-weight:600; white-space:nowrap; }
#pd_wrap .pd-lencana.sudah { background:#dff2ec; color:#0b5a4d; }
#pd_wrap .pd-lencana.kuning { background:#fdf0c2; color:#6b4a00; }
#pd_wrap .pd-lencana.merah { background:#fde4e0; color:#8a2a1e; }
#pd_wrap .pd-kosong { padding:48px 20px; text-align:center; color:#6b7c87; font-size:14px; }
#pd_wrap .pd-memuat { display:none; position:absolute; left:0; right:0; top:0; z-index:5; }
#pd_tablewrap.memuat .pd-memuat { display:block; }
#pd_tablewrap.memuat table { opacity:.35; pointer-events:none; }
#pd_wrap .pd-memuat .bar { height:4px; background:#e5e7eb; overflow:hidden; }
#pd_wrap .pd-memuat .bar:before { content:''; display:block; height:100%; width:35%; background:var(--pd-hijau); animation:pd-geser 1.1s ease-in-out infinite; }
@keyframes pd-geser { 0% { margin-left:-35%; } 100% { margin-left:100%; } }
#pd_wrap .pd-pager { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding:12px 20px; font-size:13px; color:var(--pd-redup); }
#pd_wrap .pd-pager .kanan { display:flex; flex-wrap:wrap; align-items:center; gap:12px; }
#pd_wrap .pd-pager select { min-height:44px; padding:0 10px; border:1px solid #c9d3d9; border-radius:10px; background:#fff; font-size:14px; color:var(--pd-teks); }
#pd_wrap .pd-pager .hal { min-width:96px; text-align:center; font-weight:600; color:var(--pd-teks); }
#pd_wrap .pd-pager .pd-ikon-btn { border:1px solid #c9d3d9; }

/* Dialog upload */
#pd_upload { display:none; position:fixed; top:0; left:0; right:0; bottom:0; z-index:2000; background:rgba(18,33,43,.45); align-items:center; justify-content:center; padding:16px; }
#pd_upload.buka { display:flex; }
#pd_upload .kotak { width:560px; max-width:100%; max-height:90vh; overflow:auto; background:#fff; border-radius:14px; padding:24px; font-family:'Inter',system-ui,sans-serif; color:#1c2a33; }
#pd_upload h2 { margin:0 0 4px; font-size:18px; font-weight:700; }
#pd_upload p { margin:0 0 12px; font-size:14px; color:#4b5d68; line-height:1.5; }
#pd_upload .kolom { display:grid; grid-template-columns:auto 1fr; gap:6px 12px; margin:12px 0; padding:12px 14px; border-radius:10px; background:#f6f8fa; font-size:14px; }
#pd_upload .kolom b { color:#0b6b5e; }
#pd_upload .hasil { margin-top:14px; padding:12px 14px; border-radius:10px; background:#f1f8f6; font-size:14px; line-height:1.7; }
#pd_upload .hasil.galat { background:#fdf0ee; color:#8a2a1e; }
#pd_upload .hasil .daftar { margin-top:6px; font-size:12px; color:#4b5d68; word-break:break-all; }
#pd_upload .bawah { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; margin-top:18px; }
#pd_upload .pd-btn { min-height:44px; padding:0 18px; border:1px solid #c9d3d9; border-radius:10px; background:#fff; color:#1c2a33; font-size:14px; font-weight:600; cursor:pointer; font-family:inherit; }
#pd_upload .pd-btn.utama { border:0; background:#0b6b5e; color:#fff; }
#pd_upload .pd-btn:disabled { opacity:.45; cursor:not-allowed; }
</style>

<div id="pd_wrap">

  <div class="pd-kepala">
    <div>
      <h1>Pencairan Dana</h1>
      <span class="pd-tip">
        <button type="button" class="pd-tip-btn" aria-label="Info halaman">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="11" x2="12" y2="16.5"></line><circle cx="12" cy="7.8" r=".6" fill="currentColor"></circle></svg>
        </button>
        <span class="pd-tip-box">Pantau pesanan Completed: dananya sudah cair atau belum, berdasarkan laporan keuangan marketplace. Tanggal pencairan diisi lewat tombol Upload laporan MP.</span>
      </span>
    </div>
    <div class="pd-aksi">
      <button type="button" class="pd-btn" id="pd_btn_upload">Upload laporan MP</button>
      <button type="button" class="pd-btn utama" id="pd_btn_ekspor">Ekspor Excel</button>
    </div>
  </div>

  <!-- Ringkasan -->
  <div class="pd-kartu pd-ring">
    <div class="pd-ring-atas">
      <div>
        <div class="pd-ring-label">Status pencairan</div>
        <div class="pd-ring-besar"><span id="pd_pct_sudah">0,0</span>% <small>sudah cair</small></div>
      </div>
      <div class="pd-ring-sub"><span id="pd_n_sudah">0</span> dari <span id="pd_n_total">0</span> pesanan Completed</div>
    </div>
    <div class="pd-batang" id="pd_batang" role="img" aria-label="Komposisi status pencairan">
      <div id="pd_w_sudah" style="width:0;background:#1f9d7f"></div>
      <div id="pd_w_kuning" style="width:0;background:#e0a800"></div>
      <div id="pd_w_merah" style="width:0;background:#d6392b"></div>
    </div>
    <div class="pd-kotak">
      <div style="background:#f1f8f6;color:#0b5a4d"><span class="t"><span class="pd-dot" style="background:#1f9d7f"></span>Sudah cair, Full</span><span class="a"><span id="pd_k_full">0</span> <small id="pd_kp_full">0,0%</small></span></div>
      <div style="background:#fff1e6;color:#8a3b00"><span class="t"><span class="pd-dot" style="background:#e8710a"></span>Sudah cair, Ada selisih</span><span class="a"><span id="pd_k_selisih">0</span> <small id="pd_kp_selisih">0,0%</small></span></div>
      <div style="background:#fff8e1;color:#6b4a00"><span class="t"><span class="pd-dot" style="background:#e0a800"></span>Belum cair, dalam 7 hari</span><span class="a"><span id="pd_k_kuning">0</span> <small id="pd_kp_kuning">0,0%</small></span></div>
      <div style="background:#fdf0ee;color:#8a2a1e"><span class="t"><span class="pd-dot" style="background:#d6392b"></span>Belum cair, lewat 7 hari</span><span class="a"><span id="pd_k_merah">0</span> <small id="pd_kp_merah">0,0%</small></span></div>
    </div>
  </div>

  <div class="pd-kartu">
    <!-- Tab -->
    <div class="pd-tabbar">
      <div class="pd-tabs" id="pd_tabs">
        <button type="button" class="pd-tab" data-tab="semua">Semua <span class="n" id="pd_t_semua">0</span></button>
        <button type="button" class="pd-tab on" data-tab="belum">Belum Cair <span class="n" id="pd_t_belum">0</span></button>
        <button type="button" class="pd-tab" data-tab="selisih">Sudah Cair, Ada Selisih <span class="n" id="pd_t_selisih">0</span></button>
        <button type="button" class="pd-tab" data-tab="full">Sudah Cair, Full <span class="n" id="pd_t_full">0</span></button>
      </div>
      <span class="pd-tip kanan">
        <button type="button" class="pd-tip-btn" aria-label="Arti warna status">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="11" x2="12" y2="16.5"></line><circle cx="12" cy="7.8" r=".6" fill="currentColor"></circle></svg>
        </button>
        <span class="pd-tip-box"><b style="display:block;margin-bottom:6px">Arti warna status</b>
          <span style="display:block"><span class="pd-dot" style="background:#e0a800"></span>Kuning: belum cair, +1 sampai +<?= (int) $batas_kuning ?> hari dari tanggal Completed.</span>
          <span style="display:block;margin-top:4px"><span class="pd-dot" style="background:#ef5b4d"></span>Merah: belum cair, +<?= (int) $batas_kuning + 1 ?> hari atau lebih.</span>
          <span style="display:block;margin-top:4px"><span class="pd-dot" style="background:#3fc2a3"></span>Hijau: sudah ada tanggal pencairan.</span>
          <span style="display:block;margin-top:8px">Tab <b>Ada Selisih</b>: harga final menurut Jubelio berbeda dari laporan marketplace yang diunggah. Tab <b>Full</b>: sama. Pesanan sudah cair yang salah satu nominalnya belum ada hanya tampil di tab Semua.</span>
        </span>
      </span>
    </div>

    <!-- Filter -->
    <div class="pd-filter">
      <div class="pd-fgrup">
        <label class="pd-flabel" for="pd_cari" style="margin:0">Cari ID Pesanan</label>
        <div class="pd-cari">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="20" y1="20" x2="16.5" y2="16.5"></line></svg>
          <input type="search" id="pd_cari" placeholder="Ketik sebagian atau seluruh ID" autocomplete="off" spellcheck="false" maxlength="40">
          <button type="button" class="bersih" id="pd_cari_bersih" aria-label="Hapus pencarian"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg></button>
        </div>
      </div>
      <div class="pd-fgrup" id="pd_g_tgl">
        <span class="pd-flabel">Tanggal Completed</span>
        <button type="button" class="pd-input" id="pd_tgl_btn" aria-label="Pilih rentang tanggal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7c87" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line><line x1="8" y1="3" x2="8" y2="7"></line><line x1="16" y1="3" x2="16" y2="7"></line></svg>
          <span id="pd_tgl_label" style="font-variant-numeric:tabular-nums"></span>
        </button>
        <div id="pd_tgl_pop"></div>
      </div>

      <div class="pd-fgrup" id="pd_g_mp">
        <span class="pd-flabel">Marketplace</span>
        <div class="pd-mpbox">
          <button type="button" class="utama" id="pd_mp_btn" aria-label="Pilih marketplace">
            <span class="isi" id="pd_mp_label">Semua marketplace</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7c87" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </button>
          <button type="button" class="hapus" id="pd_mp_hapus" aria-label="Hapus pilihan marketplace" style="display:none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
          </button>
        </div>
        <div id="pd_mp_pop"></div>
      </div>

      <label class="pd-cek" id="pd_cek_wrap"><input type="checkbox" id="pd_cek_merah"> Hanya lewat <?= (int) $batas_kuning ?> hari</label>
    </div>

    <!-- Tabel -->
    <div id="pd_tablewrap">
      <div class="pd-memuat"><div class="bar"></div></div>
      <table class="pd-tabel">
        <thead>
          <tr>
            <th style="width:15%">ID Pesanan</th>
            <th style="width:7%">Tgl Shipped</th>
            <th style="width:7%">Tgl Delivered</th>
            <th style="width:7%">Tgl Completed</th>
            <th style="width:8%">Marketplace</th>
            <th style="width:10%">Toko</th>
            <th style="width:7%">Tanggal Cair</th>
            <th style="width:9%;text-align:right">Harga Final Jubelio</th>
            <th style="width:9%;text-align:right">Harga Final Laporan MP</th>
            <th style="width:15%">Status</th>
          </tr>
        </thead>
        <tbody id="pd_tbody"></tbody>
      </table>
      <div class="pd-kosong" id="pd_kosong" style="display:none">Tidak ada pesanan pada tab dan filter ini.</div>
    </div>

    <div class="pd-pager">
      <span id="pd_rentang_teks"></span>
      <div class="kanan">
        <label style="display:flex;align-items:center;gap:8px;margin:0;font-weight:400">Baris per halaman
          <select id="pd_size"><option value="10">10</option><option value="50">50</option><option value="100">100</option></select>
        </label>
        <div style="display:flex;align-items:center;gap:4px">
          <button type="button" class="pd-ikon-btn" id="pd_prev" aria-label="Halaman sebelumnya"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 5 8 12 15 19"></polyline></svg></button>
          <span class="hal">Hal <span id="pd_hal">1</span> dari <span id="pd_hal_max">1</span></span>
          <button type="button" class="pd-ikon-btn" id="pd_next" aria-label="Halaman berikutnya"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 5 16 12 9 19"></polyline></svg></button>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Dialog upload tanggal pencairan -->
<div id="pd_upload" role="dialog" aria-modal="true" aria-label="Upload laporan MP">
  <div class="kotak">
    <h2>Upload laporan MP</h2>
    <p>Mengisi tanggal pencairan dan harga final dari file laporan marketplace. Hanya kolom yang masih kosong yang diisi; yang sudah terisi tidak diubah.</p>
    <div class="kolom">
      <b>Laporan MP</b><span>Judul kolom dikenali otomatis: <u>ID Pesanan</u>, <u>Tanggal pencairan</u>, <u>Harga final (setelah diskon)</u> (mis. Rp35.900)</span>
      <b>Format lama</b><span>Tanpa judul: kolom A nomor pesanan (<u>Teks</u>), B tanggal pencairan, C harga final (opsional)</span>
    </div>
    <p style="font-size:13px">File .xlsx, .xls, atau .csv, maksimal 20.000 baris. Nomor pesanan berformat angka merusak 18 digit, jadi pastikan kolomnya Teks.</p>
    <input type="file" id="pd_file" accept=".xlsx,.xls,.csv">
    <div id="pd_upload_hasil"></div>
    <div class="bawah">
      <button type="button" class="pd-btn" id="pd_up_tutup">Tutup</button>
      <button type="button" class="pd-btn" id="pd_up_periksa">Periksa file</button>
      <button type="button" class="pd-btn utama" id="pd_up_simpan" disabled>Simpan</button>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';
    var HARI_INI = '<?= $hari_ini ?>';
    var NAMA_MP = <?= $MP_JSON ?>;
    var BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var BULAN_S = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    var DAY = 86400000;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function utc(s) { var p = s.split('-'); return Date.UTC(+p[0], +p[1] - 1, +p[2]); }
    function iso(ms) { var d = new Date(ms); return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate()); }
    function fmt(s) { if (!s) return '-'; var p = s.split('-'); return p[2] + ' ' + BULAN_S[+p[1] - 1] + ' ' + p[0]; }
    function esc(t) { return String(t).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function angka(n) { return Number(n).toLocaleString('id-ID'); }
    // Nominal rupiah; null = '-'. tanda=true menampilkan +/- di depan (untuk selisih).
    function rupiah(n, tanda) {
        if (n === null || n === undefined) return '-';
        var teks = 'Rp' + Math.abs(n).toLocaleString('id-ID', { maximumFractionDigits: 2 });
        return (n < 0 ? '-' : (tanda && n > 0 ? '+' : '')) + teks;
    }

    var T = utc(HARI_INI);
    var PD = {
        from: iso(T - 29 * DAY), to: HARI_INI, mps: [], q: '', tab: 'belum', merah: false,
        size: 10, page: 1, ring: null, ringKey: '', ringT: 0, xhr: null, req: 0,
        dp: { open: false, dFrom: '', dTo: '', hover: '', vy: 0, vm: 0 }, mpOpen: false
    };

    var ICON_PREV = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 5 8 12 15 19"></polyline></svg>';
    var ICON_NEXT = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 5 16 12 9 19"></polyline></svg>';
    var ICON_SALIN = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"></rect><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"></path></svg>';
    var ICON_CEK = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="5 12.5 10 17.5 19 7.5"></polyline></svg>';

    // ------------------------------------------------------------ muat data
    function parameter() {
        return { start_date: PD.from, end_date: PD.to, mps: PD.mps, q: PD.q, tab: PD.tab, hanya_merah: PD.merah ? '1' : '0', size: PD.size, page: PD.page };
    }
    // Ringkasan per marketplace disimpan di PD.ring (kunci: rentang tanggal + kotak cari). Pindah tab,
    // halaman, ukuran halaman, marketplace, atau centang hanya mengambil baris (ring=0), sedangkan
    // angka kartu/tab/halaman dihitung di sini dari PD.ring. Ringkasan diambil ulang bila kunci berubah,
    // sudah lebih dari 60 detik, atau setelah upload.
    function kunciRing() { return PD.from + '|' + PD.to + '|' + PD.q; }
    function ringBasi() { return !PD.ring || PD.ringKey !== kunciRing() || (Date.now() - PD.ringT) > 60000; }

    function muat() {
        var id = ++PD.req;
        var butuhRing = ringBasi();
        if (!butuhRing) {
            // angka sudah diketahui: perbarui kartu, tab, dan jumlah halaman seketika
            var h0 = hitung();
            if (PD.page > h0.pages) PD.page = h0.pages;
            renderRingkas();
        }
        $('#pd_tablewrap').addClass('memuat');
        var data = parameter();
        data.ring = butuhRing ? '1' : '0';
        if (PD.xhr) PD.xhr.abort();   // ketikan/klik baru membatalkan permintaan yang masih berjalan
        PD.xhr = $.ajax({ url: BASE + 'finance/get-pencairan-dana', type: 'POST', data: data, dataType: 'text', timeout: 120000 })
            .done(function (teks) {
                if (id !== PD.req) return;
                var r;
                try { r = JSON.parse(teks); } catch (e) { return galat('Respons server bukan JSON: ' + teks.substring(0, 300)); }
                if (r.code !== 200) return galat(r.message || 'Gagal memuat data.');
                if (r.data.ring) { PD.ring = r.data.ring; PD.ringKey = kunciRing(); PD.ringT = Date.now(); }
                PD.page = r.data.page;
                var h = hitung();
                if (PD.page > h.pages && h.pages >= 1) { PD.page = h.pages; return muat(); }   // ringkasan baru lebih kecil dari yang lama
                renderRingkas();
                renderBaris(r.data.rows);
            })
            .fail(function (xhr, status) {
                if (id === PD.req && status !== 'abort') galat('Gagal memuat data (' + (xhr.status || 'tanpa respons') + ').');
            })
            .always(function () { if (id === PD.req) $('#pd_tablewrap').removeClass('memuat'); });
    }
    function galat(pesan) {
        $('#pd_tbody').empty();
        $('#pd_kosong').text(pesan).show();
    }

    // Jumlah dari PD.ring menurut marketplace terpilih (kosong = semua), tab, dan centang.
    function hitung() {
        var h = { n: 0, sudah: 0, kuning: 0, merah: 0, selisih: 0, full: 0 };
        var daftar = PD.mps.length ? PD.mps : NAMA_MP;
        if (PD.ring) daftar.forEach(function (nama) {
            var x = PD.ring[nama];
            if (x) { h.n += x.n; h.sudah += x.sudah; h.kuning += x.kuning; h.merah += x.merah; h.selisih += x.selisih; h.full += x.full; }
        });
        h.belum = h.kuning + h.merah;
        h.total_tab = PD.tab === 'selisih' ? h.selisih : (PD.tab === 'full' ? h.full : (PD.tab === 'sudah' ? h.sudah : (PD.tab === 'belum' ? (PD.merah ? h.merah : h.belum) : h.n)));
        h.pages = Math.max(1, Math.ceil(h.total_tab / PD.size));
        return h;
    }

    // ------------------------------------------------------------ tampilan
    // Keterangan selisih tanggal cair terhadap tanggal Completed (negatif = cair sebelum Completed).
    function selisihTeks(n) {
        if (n === null || n === undefined) return '';
        if (n === 0) return 'Cair di hari Completed';
        return Math.abs(n) + ' hari ' + (n < 0 ? 'sebelum' : 'sesudah') + ' Completed';
    }
    // Sorot bagian ID yang cocok dengan kotak cari (hanya huruf/angka, tidak peka huruf besar-kecil).
    function tandai(no) {
        var q = PD.q;
        if (!q) return esc(no);
        var i = String(no).toLowerCase().indexOf(q.toLowerCase());
        if (i < 0) return esc(no);
        return esc(no.substring(0, i)) + '<mark class="pd-hl">' + esc(no.substring(i, i + q.length)) + '</mark>' + esc(no.substring(i + q.length));
    }
    function persen(n, total) { return total ? (n * 100 / total).toFixed(1).replace('.', ',') : '0,0'; }

    function renderRingkas() {
        var r = hitung();
        r.total = r.n;
        $('#pd_pct_sudah').text(persen(r.sudah, r.total));
        $('#pd_n_sudah').text(angka(r.sudah));
        $('#pd_n_total').text(angka(r.total));
        $('#pd_w_sudah').css('width', (r.total ? r.sudah * 100 / r.total : 0) + '%');
        $('#pd_w_kuning').css('width', (r.total ? r.kuning * 100 / r.total : 0) + '%');
        $('#pd_w_merah').css('width', (r.total ? r.merah * 100 / r.total : 0) + '%');
        $('#pd_batang').attr('aria-label', 'Sudah cair ' + persen(r.sudah, r.total) + ' persen, belum cair dalam 7 hari ' + persen(r.kuning, r.total) + ' persen, lewat 7 hari ' + persen(r.merah, r.total) + ' persen');
        $('#pd_k_full').text(angka(r.full)); $('#pd_kp_full').text(persen(r.full, r.total) + '%');
        $('#pd_k_selisih').text(angka(r.selisih)); $('#pd_kp_selisih').text(persen(r.selisih, r.total) + '%');
        $('#pd_k_kuning').text(angka(r.kuning)); $('#pd_kp_kuning').text(persen(r.kuning, r.total) + '%');
        $('#pd_k_merah').text(angka(r.merah)); $('#pd_kp_merah').text(persen(r.merah, r.total) + '%');

        $('#pd_t_semua').text(angka(r.total));
        $('#pd_t_belum').text(angka(r.belum));
        $('#pd_t_selisih').text(angka(r.selisih));
        $('#pd_t_full').text(angka(r.full));
        $('#pd_tabs .pd-tab').removeClass('on').filter('[data-tab="' + PD.tab + '"]').addClass('on');
        $('#pd_cek_wrap').toggle(PD.tab === 'belum');

        var awal = r.total_tab ? (PD.page - 1) * PD.size + 1 : 0;
        var akhir = Math.min(r.total_tab, PD.page * PD.size);
        $('#pd_rentang_teks').text('Menampilkan ' + angka(awal) + '–' + angka(akhir) + ' dari ' + angka(r.total_tab) + ' pesanan');
        $('#pd_hal').text(PD.page);
        $('#pd_hal_max').text(r.pages);
        $('#pd_prev').prop('disabled', PD.page <= 1);
        $('#pd_next').prop('disabled', PD.page >= r.pages);

        renderMp();
        renderTglLabel();
    }

    function renderBaris(rows) {
        var h = '';
        rows.forEach(function (x) {
            var badge;
            if (x.status === 'sudah') {
                badge = '<span class="pd-lencana sudah">Sudah cair</span>';
                if (x.cek === 'selisih') badge += ' <span class="pd-lencana merah">Selisih ' + rupiah(x.rp_selisih, true) + '</span>';
                else if (x.cek === 'penuh') badge += ' <span class="pd-lencana sudah">Full</span>';
                else badge += '<div class="pd-selisih">Nominal belum dicek</div>';
                badge += '<div class="pd-selisih">' + selisihTeks(x.selisih) + '</div>';
            } else {
                badge = '<span class="pd-lencana ' + x.status + '">+' + x.umur + ' hari</span>';
            }
            h += '<tr><td><span class="pd-id"><b>' + tandai(x.no) + '</b>'
                + '<button type="button" class="pd-salin" data-salin="' + esc(x.no) + '" aria-label="Salin ID pesanan" title="Salin ID pesanan">' + ICON_SALIN + '</button></span></td>'
                + '<td style="font-variant-numeric:tabular-nums;color:#4b5d68">' + fmt(x.kirim) + '</td>'
                + '<td style="font-variant-numeric:tabular-nums;color:#4b5d68">' + fmt(x.deliver) + '</td>'
                + '<td style="font-variant-numeric:tabular-nums">' + fmt(x.selesai) + '</td>'
                + '<td style="color:#4b5d68">' + esc(x.mp) + '</td>'
                + '<td style="color:#4b5d68">' + (x.toko ? esc(x.toko) : '-') + '</td>'
                + '<td style="font-variant-numeric:tabular-nums;color:#4b5d68">' + fmt(x.cair) + '</td>'
                + '<td style="text-align:right;font-variant-numeric:tabular-nums">' + rupiah(x.dana_mp) + '</td>'
                + '<td style="text-align:right;font-variant-numeric:tabular-nums">' + rupiah(x.dana_file) + '</td>'
                + '<td>' + badge + '</td></tr>';
        });
        $('#pd_tbody').html(h);
        $('#pd_kosong').text(PD.q ? 'Tidak ada pesanan dengan ID yang memuat "' + PD.q + '" pada tab dan filter ini.' : 'Tidak ada pesanan pada tab dan filter ini.').toggle(rows.length === 0);
    }

    // ------------------------------------------------------------ marketplace (multi pilih)
    function renderMp() {
        var n = PD.mps.length;
        var urut = NAMA_MP.filter(function (x) { return PD.mps.indexOf(x) >= 0; });
        $('#pd_mp_label').text(n === 0 ? 'Semua marketplace' : (n <= 2 ? urut.join(', ') : n + ' marketplace')).toggleClass('ada', n > 0);
        $('#pd_mp_hapus').toggle(n > 0);
        if (!PD.mpOpen) { $('#pd_mp_pop').empty(); return; }
        var jumlah = {};
        if (PD.ring) NAMA_MP.forEach(function (nama) { jumlah[nama] = PD.ring[nama] ? PD.ring[nama].n : 0; });
        var h = '<button type="button" class="pd-lapis" data-act="mp-tutup" aria-label="Tutup pilihan marketplace"></button>'
            + '<div class="pd-pop pd-mplist">';
        NAMA_MP.forEach(function (nama) {
            h += '<label><input type="checkbox" data-mp="' + esc(nama) + '"' + (PD.mps.indexOf(nama) >= 0 ? ' checked' : '') + '>'
                + '<span class="nama">' + esc(nama) + '</span><span class="jml">' + angka(jumlah[nama] || 0) + '</span></label>';
        });
        h += '<div class="pd-mpfoot"><button type="button" class="pd-link" data-act="mp-reset">Reset</button>'
            + '<button type="button" class="pd-btn utama" data-act="mp-tutup" style="min-height:44px;padding:0 16px">Selesai</button></div></div>';
        $('#pd_mp_pop').html(h);
    }

    // ------------------------------------------------------------ pemilih tanggal
    function lbl(s) { return fmt(s); }
    function rentangTeks(a, b) { return !a ? 'Pilih tanggal' : (!b || a === b ? lbl(a) : lbl(a) + ' – ' + lbl(b)); }
    function renderTglLabel() { $('#pd_tgl_label').text(rentangTeks(PD.from, PD.to)); }

    function presetList() {
        var ty = new Date(T).getUTCFullYear(), tm = new Date(T).getUTCMonth();
        return [
            ['Hari ini', iso(T), iso(T)],
            ['Kemarin', iso(T - DAY), iso(T - DAY)],
            ['7 hari terakhir', iso(T - 6 * DAY), iso(T)],
            ['30 hari terakhir', iso(T - 29 * DAY), iso(T)],
            ['Bulan ini', iso(Date.UTC(ty, tm, 1)), iso(T)],
            ['Bulan lalu', iso(Date.UTC(ty, tm - 1, 1)), iso(Date.UTC(ty, tm, 0))]
        ];
    }

    function renderTglPop() {
        var dp = PD.dp;
        if (!dp.open) { $('#pd_tgl_pop').empty(); return; }
        var ty = new Date(T).getUTCFullYear(), tm = new Date(T).getUTCMonth();
        var memilihAkhir = !!dp.dFrom && !dp.dTo;
        var a = dp.dFrom || '', b = dp.dTo || (dp.dFrom && dp.hover ? dp.hover : '');
        if (b && b < a) { var t = a; a = b; b = t; }

        var h = '<button type="button" class="pd-lapis" data-act="tgl-tutup" aria-label="Tutup pemilih tanggal"></button>'
            + '<div class="pd-pop pd-dp"><div class="pd-dp-kiri">';
        presetList().forEach(function (p, i) {
            h += '<button type="button" data-preset="' + i + '"' + (dp.dFrom === p[1] && dp.dTo === p[2] ? ' class="on"' : '') + '>' + p[0] + '</button>';
        });
        h += '</div><div class="pd-dp-kanan"><div class="pd-dp-isi">'
            + '<div class="k' + (memilihAkhir ? '' : ' aktif') + '"><span>Tanggal awal</span><span>' + (dp.dFrom ? lbl(dp.dFrom) : '—') + '</span></div><span style="color:#6b7c87">–</span>'
            + '<div class="k' + (memilihAkhir ? ' aktif' : '') + '"><span>Tanggal akhir</span><span>' + (dp.dTo ? lbl(dp.dTo) : (memilihAkhir && dp.hover ? lbl(b) : '—')) + '</span></div></div>';

        // satu bulan kalender, minggu mulai Senin
        var y = dp.vy, m = dp.vm;
        var lead = (new Date(Date.UTC(y, m, 1)).getUTCDay() + 6) % 7;
        var jumlah = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
        var nextOff = (y * 12 + m) >= (ty * 12 + tm);
        h += '<div data-act="tgl-keluar"><div class="pd-kal-kepala">'
            + '<button type="button" class="pd-ikon-btn" data-act="bln-prev" aria-label="Bulan sebelumnya">' + ICON_PREV + '</button>'
            + '<span>' + BULAN[m] + ' ' + y + '</span>'
            + '<button type="button" class="pd-ikon-btn" data-act="bln-next" aria-label="Bulan berikutnya"' + (nextOff ? ' disabled' : '') + '>' + ICON_NEXT + '</button></div>'
            + '<div class="pd-hari7"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div>';
        var sel = [], i;
        for (i = 0; i < lead; i++) sel.push('<span style="height:40px"></span>');
        for (var d = 1; d <= jumlah; d++) {
            var s = y + '-' + pad(m + 1) + '-' + pad(d);
            if (s > HARI_INI) { sel.push('<span class="pd-tgl mati" style="display:flex;align-items:center;justify-content:center">' + d + '</span>'); continue; }
            var kelas = 'pd-tgl';
            if (s === a || s === b) kelas += ' ujung';
            else if (a && b && s > a && s < b) kelas += ' range';
            sel.push('<button type="button" class="' + kelas + '" data-tgl="' + s + '">' + d + '</button>');
        }
        while (sel.length % 7 !== 0) sel.push('<span style="height:40px"></span>');
        for (i = 0; i < sel.length; i += 7) h += '<div class="pd-minggu">' + sel.slice(i, i + 7).join('') + '</div>';
        h += '</div><span class="pd-petunjuk">' + (memilihAkhir ? 'Pilih tanggal akhir. Rentang langsung berlaku.' : 'Pilih tanggal awal, lalu tanggal akhir.') + '</span></div></div>';
        $('#pd_tgl_pop').html(h);
    }

    function bukaTgl() {
        var td = new Date(utc(PD.to));
        PD.mpOpen = false; renderMp();
        PD.dp = { open: true, dFrom: PD.from, dTo: PD.to, hover: '', vy: td.getUTCFullYear(), vm: td.getUTCMonth() };
        renderTglPop();
    }
    function tutupTgl() { PD.dp.open = false; PD.dp.hover = ''; renderTglPop(); }
    function terapkanRentang(a, b) {
        PD.from = a; PD.to = b; PD.page = 1;
        PD.dp.open = false; PD.dp.hover = '';
        renderTglPop(); renderTglLabel(); muat();
    }

    $('#pd_tgl_btn').on('click', function () { PD.dp.open ? tutupTgl() : bukaTgl(); });
    $('#pd_tgl_pop').on('click', '[data-act="tgl-tutup"]', function () { PD.dp.dFrom = PD.from; PD.dp.dTo = PD.to; tutupTgl(); });
    $('#pd_tgl_pop').on('click', '[data-preset]', function () {
        var p = presetList()[+$(this).attr('data-preset')];
        terapkanRentang(p[1], p[2]);
    });
    $('#pd_tgl_pop').on('click', '[data-act="bln-prev"]', function () {
        var dp = PD.dp; if (dp.vm === 0) { dp.vy--; dp.vm = 11; } else dp.vm--; renderTglPop();
    });
    $('#pd_tgl_pop').on('click', '[data-act="bln-next"]', function () {
        var dp = PD.dp; if (dp.vm === 11) { dp.vy++; dp.vm = 0; } else dp.vm++; renderTglPop();
    });
    $('#pd_tgl_pop').on('click', '[data-tgl]', function () {
        var s = $(this).attr('data-tgl'), dp = PD.dp;
        if (!dp.dFrom || dp.dTo) { dp.dFrom = s; dp.dTo = ''; dp.hover = ''; renderTglPop(); return; }
        // tanggal akhir dipilih: rentang langsung berlaku dan popup menutup
        var lo = s < dp.dFrom ? s : dp.dFrom, hi = s < dp.dFrom ? dp.dFrom : s;
        dp.dFrom = lo; dp.dTo = hi;
        terapkanRentang(lo, hi);
    });
    // pratinjau rentang mengikuti kursor setelah tanggal awal dipilih
    $('#pd_tgl_pop').on('mouseover', '[data-tgl]', function () {
        var dp = PD.dp, s = $(this).attr('data-tgl');
        if (dp.dFrom && !dp.dTo && dp.hover !== s) { dp.hover = s; renderTglPop(); }
    });
    $('#pd_tgl_pop').on('mouseleave', '[data-act="tgl-keluar"]', function () {
        if (PD.dp.hover) { PD.dp.hover = ''; renderTglPop(); }
    });

    // marketplace
    $('#pd_mp_btn').on('click', function () {
        PD.mpOpen = !PD.mpOpen;
        if (PD.mpOpen && PD.dp.open) { PD.dp.open = false; renderTglPop(); }
        renderMp();
    });
    $('#pd_mp_hapus').on('click', function () { PD.mps = []; PD.page = 1; renderMp(); muat(); });
    $('#pd_mp_pop').on('click', '[data-act="mp-tutup"]', function () { PD.mpOpen = false; renderMp(); });
    $('#pd_mp_pop').on('click', '[data-act="mp-reset"]', function () { PD.mps = []; PD.page = 1; renderMp(); muat(); });
    $('#pd_mp_pop').on('change', 'input[data-mp]', function () {
        var nama = $(this).attr('data-mp'), i = PD.mps.indexOf(nama);
        if (this.checked && i < 0) PD.mps.push(nama);
        if (!this.checked && i >= 0) PD.mps.splice(i, 1);
        PD.page = 1; renderMp(); muat();
    });

    // Pencarian ID pesanan: tabel, tab, dan ringkasan mengikuti isi kotak sambil mengetik.
    // Ditunda 250 ms setelah ketikan terakhir supaya server tidak ditembak tiap huruf; Enter langsung mencari.
    var timerCari = null;
    function terapkanCari() {
        clearTimeout(timerCari);
        var q = $('#pd_cari').val().replace(/[^A-Za-z0-9]/g, '');
        $('#pd_cari_bersih').css('display', $('#pd_cari').val() !== '' ? 'inline-flex' : 'none');
        if (q === PD.q) return;
        PD.q = q; PD.page = 1; muat();
    }
    $('#pd_cari').on('input', function () {
        $('#pd_cari_bersih').css('display', this.value !== '' ? 'inline-flex' : 'none');
        clearTimeout(timerCari);
        timerCari = setTimeout(terapkanCari, 250);
    }).on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); terapkanCari(); }
        if (e.key === 'Escape' && this.value !== '') { this.value = ''; terapkanCari(); }
    });
    $('#pd_cari_bersih').on('click', function () { $('#pd_cari').val('').focus(); terapkanCari(); });

    // tab, centang, pagination
    $('#pd_tabs').on('click', '.pd-tab', function () { PD.tab = $(this).attr('data-tab'); PD.page = 1; muat(); });
    $('#pd_cek_merah').on('change', function () { PD.merah = this.checked; PD.page = 1; muat(); });
    $('#pd_size').on('change', function () { PD.size = parseInt(this.value, 10); PD.page = 1; muat(); });
    $('#pd_prev').on('click', function () { if (PD.page > 1) { PD.page--; muat(); } });
    $('#pd_next').on('click', function () { if (PD.page < hitung().pages) { PD.page++; muat(); } });

    // Ikon info: klik untuk membuka/menutup (selain hover), klik di luar menutup.
    $('#pd_wrap').on('click', '.pd-tip-btn', function (e) { e.stopPropagation(); $(this).parent().toggleClass('buka'); });
    $(document).on('click.pdtip', function () { $('#pd_wrap .pd-tip').removeClass('buka'); });

    // ------------------------------------------------------------ salin ID pesanan
    // clipboard API hanya jalan di HTTPS/localhost; klien LAN memakai HTTP -> cadangan textarea + execCommand.
    function salinCadangan(teks) {
        var ta = document.createElement('textarea');
        ta.value = teks; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.focus(); ta.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
        return ok;
    }
    function salinTeks(teks) {
        return new Promise(function (ok, gagal) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(teks).then(ok, function () { salinCadangan(teks) ? ok() : gagal(); });
            } else {
                salinCadangan(teks) ? ok() : gagal();
            }
        });
    }
    $('#pd_tbody').on('click', '.pd-salin', function () {
        var $b = $(this), teks = String($b.attr('data-salin') || '');
        salinTeks(teks).then(function () {
            $b.addClass('sudah').html(ICON_CEK).attr('title', 'Tersalin');
            setTimeout(function () { $b.removeClass('sudah').html(ICON_SALIN).attr('title', 'Salin ID pesanan'); }, 1500);
        }, function () { window.prompt('Salin manual (Ctrl+C):', teks); });
    });

    // ------------------------------------------------------------ ekspor
    $('#pd_btn_ekspor').on('click', function () {
        var q = $.param(parameter());
        window.location.href = BASE + 'finance/export-pencairan-dana?' + q;
    });

    // ------------------------------------------------------------ upload tanggal pencairan
    var fileSiap = false;
    function hasilUpload(html, galatnya) {
        $('#pd_upload_hasil').html('<div class="hasil' + (galatnya ? ' galat' : '') + '">' + html + '</div>');
    }
    function kirimUpload(dry) {
        var f = $('#pd_file')[0].files[0];
        if (!f) { hasilUpload('Pilih file Excel dulu.', true); return; }
        var fd = new FormData();
        fd.append('berkas', f);
        fd.append('dry', dry ? '1' : '0');
        $('#pd_up_periksa, #pd_up_simpan').prop('disabled', true);
        hasilUpload('Memproses file...', false);
        $.ajax({ url: BASE + 'finance/upload-pencairan-dana', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'text', timeout: 600000 })
            .done(function (teks) {
                var r;
                try { r = JSON.parse(teks); } catch (e) { hasilUpload('Respons server bukan JSON: ' + esc(teks.substring(0, 300)), true); return; }
                if (r.code !== 200) { hasilUpload(esc(r.message || 'Gagal.'), true); return; }
                tampilLaporan(r.data, dry);
            })
            .fail(function (xhr) { hasilUpload('Gagal mengunggah (' + (xhr.status || 'tanpa respons') + ').', true); })
            .always(function () {
                $('#pd_up_periksa').prop('disabled', false);
                $('#pd_up_simpan').prop('disabled', !(fileSiap));
            });
    }
    function tampilLaporan(d, dry) {
        var h = '<b>' + (dry ? 'Hasil pemeriksaan' : 'Tersimpan') + '</b> — ' + angka(d.total) + ' baris dibaca<br>'
            + '<b>' + angka(d.diisi) + '</b> ' + (dry ? 'pesanan akan diisi' : 'pesanan diisi') + ' (' + angka(d.nominal_diisi) + ' di antaranya dengan harga final)<br>'
            + angka(d.sudah_terisi) + ' sudah terisi sebelumnya (tidak diubah)<br>'
            + angka(d.tidak_ditemukan) + ' nomor pesanan tidak ditemukan<br>'
            + angka(d.tanggal_salah + d.nomor_salah) + ' baris tidak valid<br>'
            + angka(d.duplikat) + ' nomor ganda di file (dilewati)';
        if (d.belum_completed > 0) h += '<br>' + angka(d.belum_completed) + ' pesanan belum berstatus Completed di Iresis (tetap diisi)';
        if (d.contoh_tidak_ditemukan.length) h += '<div class="daftar">Contoh tidak ditemukan: ' + esc(d.contoh_tidak_ditemukan.join(', ')) + '</div>';
        if (d.contoh_salah.length) h += '<div class="daftar">' + d.contoh_salah.map(esc).join('<br>') + '</div>';
        hasilUpload(h, false);
        if (dry) {
            fileSiap = d.diisi > 0;
            $('#pd_up_simpan').prop('disabled', !fileSiap).text('Simpan ' + angka(d.diisi) + ' tanggal');
        } else {
            fileSiap = false;
            $('#pd_up_simpan').prop('disabled', true).text('Simpan');
            PD.ring = null;   // tanggal pencairan berubah: ringkasan wajib dihitung ulang
            muat();
        }
    }
    $('#pd_btn_upload').on('click', function () {
        fileSiap = false;
        $('#pd_file').val('');
        $('#pd_upload_hasil').empty();
        $('#pd_up_simpan').prop('disabled', true).text('Simpan');
        $('#pd_upload').addClass('buka');
    });
    $('#pd_up_tutup').on('click', function () { $('#pd_upload').removeClass('buka'); });
    $('#pd_file').on('change', function () { fileSiap = false; $('#pd_up_simpan').prop('disabled', true).text('Simpan'); $('#pd_upload_hasil').empty(); });
    $('#pd_up_periksa').on('click', function () { kirimUpload(true); });
    $('#pd_up_simpan').on('click', function () {
        if (!fileSiap) return;
        if (window.confirm('Simpan tanggal pencairan dari file ini? Hanya yang masih kosong yang diisi.')) kirimUpload(false);
    });

    // Dialog dipindah ke body agar tidak terpotong kontainer halaman SPA. Salinan dari kunjungan
    // sebelumnya (halaman SPA diganti tanpa reload) dibuang dulu supaya id tidak ganda.
    var $dialogBaru = $('#pd_wrap').next('#pd_upload');
    $('#pd_upload').not($dialogBaru).remove();
    $dialogBaru.appendTo('body');

    renderTglLabel();
    renderMp();
    muat();
});
</script>
