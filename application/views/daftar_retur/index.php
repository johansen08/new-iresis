<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<style>
/* Daftar Retur Penjualan: tab status + filter ringkas + tabel bersih (acuan: UI daftar pesanan seller marketplace).
   Prinsip: satu baris per retur (tinggi seragam), identitas (resi/pesanan) tidak pernah terpotong di tengah,
   warna peringatan hanya satu lapis (lencana umur), kontrol sekunder muncul saat diarahkan. */
/* Rapat (21 Nov 2026): satu halaman 10 baris + pagination muat di layar laptop (viewport ~650 px) tanpa gulir. Tinggi baris 37 px = tinggi lencana Progress dua baris + napas. */
#dr_wrap { background:#fff; border-radius:8px; padding:0 20px 10px; font-size:13px; color:#111827; }
#dr_wrap .dr-tabs { margin-top:0; }

/* Ikon info (legenda umur): menempel di judul kolom Umur, isi muncul saat diarahkan/diklik */
.dr-bantu { position:relative; display:inline-block; margin-left:4px; vertical-align:middle; }
.dr-bantu > i { color:#9ca3af; cursor:pointer; font-size:14px; }
.dr-bantu:hover > i, .dr-bantu.buka > i { color:#0f766e; }
.dr-pop { display:none; position:absolute; right:-8px; top:24px; z-index:50; width:330px; padding:12px 14px; text-align:left;
          background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12);
          font-size:12.5px; font-weight:400; color:#374151; line-height:1.55; white-space:normal; }
.dr-bantu:hover .dr-pop, .dr-bantu.buka .dr-pop { display:block; }
.dr-pop p { margin:0 0 8px; }
.dr-pop .dr-lg { margin-top:6px; display:flex; align-items:center; gap:8px; }
.dr-pop .dr-lg2 { margin-top:6px; }
.dr-pop .dr-lg .dr-umur { min-width:78px; text-align:center; }

/* Tab status */
/* flex-wrap (bukan overflow:auto): overflow membuat batang gulir kecil muncul di sisi kanan tab */
.dr-tabs { display:flex; flex-wrap:wrap; gap:0 14px; border-bottom:1px solid #e5e7eb; margin-top:10px; }
.dr-tab { padding:11px 2px 9px; cursor:pointer; font-size:13px; font-weight:600; color:#4b5563; white-space:nowrap;
          border-bottom:2px solid transparent; margin-bottom:-1px; user-select:none; }
.dr-tab:hover { color:#0f766e; }
.dr-tab.on { border-bottom-color:#0f766e; color:#111827; }
.dr-tab .n { margin-left:5px; padding:1px 7px; border-radius:999px; background:#f3f4f6; font-size:11.5px; font-weight:600; color:#6b7280; }
.dr-tab .n.hot { background:#ccfbf1; color:#0f766e; }
.dr-tab.on .n { background:#0f766e; color:#fff; }

/* Bar filter: filter di kiri (tanggal, jenis, status, atur ulang), kotak cari menyusul, aksi (Export/Cetak) rata kanan.
   Satu spesifikasi kontrol: tinggi 38, sudut 6, garis #d1d5db, teks 13, jarak antar kontrol 10 (antar baris saat membungkus 8),
   ikon 14 px selebar sama berwarna abu, jarak ikon-teks 8. Hover = garis teal, fokus papan ketik = cincin teal 3 px. */
.dr-filter { display:flex; align-items:center; gap:8px 10px; flex-wrap:wrap; padding:10px 0; }
.dr-chip { box-sizing:border-box; display:inline-flex; align-items:center; gap:8px; border:1px solid #d1d5db; border-radius:6px;
           padding:0 12px; height:38px; background:#fff; }
.dr-chip:hover { border-color:#0f766e; }
.dr-chip:focus-within { border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.15); }
.dr-chip input { border:0; outline:0; background:transparent; width:176px; height:36px; padding:0; margin:0; cursor:pointer; color:#111827; font-weight:600; font-size:13px; line-height:36px; }
.dr-chip .dr-chip-lbl { font-size:12px; color:#6b7280; white-space:nowrap; }
/* ikon kontrol: kotak 14 px, rata tengah, warna sama di semua tombol */
.dr-chip > i, .dr-multi-btn > i:first-child, .dr-reset i, .dr-export i { flex:none; width:14px; text-align:center; font-size:13px; line-height:1; color:#6b7280; }
.dr-export i { color:inherit; }
.dr-filter .dataTables_filter { height:38px; margin:0; padding:0; float:none; width:auto; text-align:left; flex:1 1 120px; min-width:120px; max-width:340px; }
.dr-filter .dataTables_filter label { margin:0; padding:0; display:block; width:100%; height:38px; line-height:0; overflow:visible; }
.dr-filter .dataTables_filter input { box-sizing:border-box; display:block; width:100%; height:38px; margin:0; padding:0 12px 0 34px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; line-height:36px; color:#111827;
  background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5' stroke-linecap='round'><circle cx='11' cy='11' r='7'/><path d='M20 20l-3.5-3.5'/></svg>") no-repeat 12px center; }
.dr-filter .dataTables_filter input:hover { border-color:#0f766e; }
.dr-filter .dataTables_filter input:focus { outline:0; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.15); }
.dr-acts { display:flex; align-items:center; gap:10px; margin-left:auto; }
.dr-reset { box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px; padding:0; margin:0; border:1px solid #d1d5db; border-radius:6px; background:#fff; cursor:pointer; color:#6b7280; }
.dr-reset:hover { border-color:#0f766e; color:#0f766e; }
.dr-reset:hover i { color:#0f766e; }
/* Export = aksi sekunder (garis), Cetak = aksi utama konteks tab (isi) */
.dr-export { box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:8px; height:38px; margin:0; padding:0 14px; border:1px solid #0f766e; border-radius:6px; background:#fff; color:#0f766e; font-size:13px; font-weight:600; line-height:1; cursor:pointer; white-space:nowrap; }
.dr-export:hover { background:#f0fdfa; }
.dr-cetak { background:#0f766e; color:#fff; }
.dr-cetak:hover { background:#115e59; }
.dr-cetak[disabled] { background:#e5e7eb; border-color:#e5e7eb; color:#9ca3af; cursor:not-allowed; }
.dr-reset:focus-visible, .dr-export:focus-visible, .dr-multi-btn:focus-visible { outline:0; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.25); }

/* Filter jenis retur: tombol + panel centang multi (dropdown berisi checkbox, tombol menampilkan ringkasan pilihan, ada Pilih semua / Hapus) */
.dr-multi { position:relative; }
.dr-multi.nonaktif { opacity:.45; pointer-events:none; }
.dr-multi-btn { box-sizing:border-box; display:inline-flex; align-items:center; gap:8px; height:38px; padding:0 12px; background:#fff;
                border:1px solid #d1d5db; border-radius:6px; cursor:pointer; color:#111827; }
.dr-multi-btn:hover, .dr-multi.buka .dr-multi-btn { border-color:#0f766e; }
.dr-multi-btn .lbl { color:#6b7280; font-size:12px; }
.dr-multi-btn .nilai { font-weight:600; }
.dr-multi-btn .cnt { background:#0f766e; color:#fff; border-radius:999px; font-size:11px; font-weight:700; padding:0 7px; line-height:18px; }
.dr-multi-btn .cnt.semua { display:none; }   /* semua jenis terpilih = keadaan normal, tidak perlu angka */
.dr-multi-btn .fa-angle-down { flex:none; width:10px; text-align:center; color:#9ca3af; transition:transform .15s; }
.dr-multi.buka .fa-angle-down { transform:rotate(180deg); }
.dr-multi-pop { display:none; position:absolute; left:0; top:44px; z-index:60; min-width:260px; padding:6px;
                background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12); }
.dr-multi.buka .dr-multi-pop { display:block; }
.dr-opsi { display:flex; align-items:center; gap:10px; padding:9px 10px; border-radius:6px; cursor:pointer; margin:0; font-weight:400; }
.dr-opsi:hover { background:#f3f4f6; }
.dr-opsi input { position:absolute; opacity:0; pointer-events:none; }
.dr-kotak { flex:none; width:18px; height:18px; border:1.5px solid #9ca3af; border-radius:4px; background:#fff;
            display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; transition:all .12s; }
.dr-kotak i { opacity:0; }
.dr-opsi input:checked + .dr-kotak { background:#0f766e; border-color:#0f766e; }
.dr-opsi input:checked + .dr-kotak i { opacity:1; }
.dr-opsi input:focus-visible + .dr-kotak { box-shadow:0 0 0 3px rgba(15,118,110,.25); }
.dr-opsi .teks { flex:1; color:#111827; }
.dr-opsi .ket { display:block; color:#9ca3af; font-size:11.5px; }
.dr-multi-kaki { display:flex; justify-content:space-between; padding:6px 10px 4px; margin-top:4px; border-top:1px solid #f1f5f9; font-size:12px; }
.dr-multi-kaki a { cursor:pointer; color:#0f766e; font-weight:600; }
.dr-multi-pop .dr-cari-opsi { box-sizing:border-box; width:100%; height:34px; margin:2px 0 4px; padding:0 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; }
.dr-multi-pop .dr-cari-opsi:focus { outline:0; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.15); }
.dr-multi-pop .dr-daftar { max-height:260px; overflow-y:auto; }
.dr-multi-pop .dr-kosong { padding:10px; color:#9ca3af; font-size:12.5px; }
.dr-dyn .dr-multi-pop { min-width:240px; }
.dr-dyn .dr-multi-btn .nilai { max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dr-pill-komplain { background:#ede9fe; color:#6d28d9; }

/* DataTables: baris per halaman, info, dan paging di bawah */
/* tema memberi float:left pada tabel; tanpa ini blok bawah terdorong ke samping tabel */
#dr_wrap table.dataTable { float:none; clear:both; }
#dr_wrap .dr-bawah { clear:both; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px 18px; margin-top:8px; padding-top:8px; border-top:1px solid #f1f5f9; }
#dr_wrap .dr-bawah .dataTables_length, #dr_wrap .dr-bawah .dataTables_info, #dr_wrap .dr-bawah .dataTables_paginate { float:none; margin:0; padding:0; width:auto; min-width:0; }
#dr_wrap .dr-bawah .dataTables_info { width:auto; text-align:left; color:#6b7280; white-space:nowrap; font-size:12.5px; }
#dr_wrap .dr-kanan { display:flex; align-items:center; flex-wrap:nowrap; gap:8px 18px; margin-left:auto; }
#dr_wrap .dr-bawah .dataTables_length label { margin:0; font-weight:400; color:#6b7280; white-space:nowrap; font-size:12.5px; display:inline-flex; align-items:center; gap:6px; }
#dr_wrap .dr-bawah .dataTables_length select { width:auto; display:inline-block; margin:0; height:32px; padding:0 8px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827; font-weight:600; font-size:13px; }
#dr_wrap .dr-bawah .dataTables_length select:focus { outline:0; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.15); }
/* Nomor halaman: tombol persegi bersudut halus; halaman aktif terisi hijau, tombol mati redup, titik-titik sebagai pemisah */
#dr_wrap .dataTables_paginate { display:flex; align-items:center; gap:4px; }
#dr_wrap .dataTables_paginate > span { display:flex; align-items:center; gap:4px; }
#dr_wrap .dataTables_paginate > span > span { color:#9ca3af; padding:0 4px; user-select:none; }
#dr_wrap .dataTables_paginate .paginate_button { box-sizing:border-box; min-width:32px; height:32px; margin:0; padding:0 9px; display:inline-flex; align-items:center; justify-content:center;
    border:1px solid #e5e7eb; border-radius:6px; background:#fff; color:#374151; font-size:13px; font-weight:600; line-height:1; cursor:pointer; text-decoration:none; transition:background .12s, border-color .12s, color .12s; }
#dr_wrap .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) { border-color:#0f766e; background:#f0fdfa; color:#0f766e; }
#dr_wrap .dataTables_paginate .paginate_button.current, #dr_wrap .dataTables_paginate .paginate_button.current:hover { background:#0f766e; border-color:#0f766e; color:#fff; cursor:default; }
#dr_wrap .dataTables_paginate .paginate_button.disabled, #dr_wrap .dataTables_paginate .paginate_button.disabled:hover { background:#f9fafb; border-color:#f1f5f9; color:#cbd5e1; cursor:not-allowed; }
#dr_wrap .dataTables_paginate .paginate_button:focus-visible { outline:0; box-shadow:0 0 0 3px rgba(15,118,110,.25); }

/* Tabel: tata letak tetap, lebar kolom piksel (kolom Toko = sisa lebar) supaya baris selalu satu baris
   dan identitas (pesanan, resi) tidak pecah di tengah. Teks panjang dipotong titik-titik + tooltip. */
#dr_wrap table.dataTable { border:0; table-layout:fixed; width:100% !important; font-size:13px; }
#dr_wrap table.dataTable thead th { position:sticky; top:50px; z-index:5; background:#f9fafb; color:#6b7280; font-weight:600; border:0; padding:9px 22px 9px 8px; font-size:12px;
                                    vertical-align:middle; white-space:nowrap; box-shadow:0 1px 0 #e5e7eb; }
#dr_wrap table.dataTable thead th:nth-child(9) { z-index:7; }
#dr_wrap table.dataTable tbody td { height:37px; border:0; border-bottom:1px solid #f1f5f9; padding:0 8px; vertical-align:middle; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
#dr_wrap table.dataTable thead th:first-child { border-radius:6px 0 0 6px; }
#dr_wrap table.dataTable thead th:last-child { border-radius:0 6px 6px 0; }
/* Tgl Pesan (kolom 2) & Tgl Retur (kolom 3) disembunyikan: sudah ada di kartu Progress (Dipesan, Diretur). Kolom tetap ada agar urutan indeks tidak bergeser. */
#dr_wrap table.dataTable th:nth-child(2), #dr_wrap table.dataTable td:nth-child(2),
#dr_wrap table.dataTable th:nth-child(3), #dr_wrap table.dataTable td:nth-child(3) { display:none; }
/* lebar kolom: 1 centang, 2 pesan, 3 retur, 4 tgl tahap, 5 pesanan, 6 resi, 7 toko (sisa lebar), 8 nilai, 9 umur, 10 progress, 11 status Jubelio.
   Kurir dan MP bukan kolom lagi (jadi filter multi-pilih); MP tampil sebagai awalan di sel Toko.
   Lebar dasar = isi nyata (p95 data produksi, diukur di browser) + padding sel 16px: no pesanan <=134 (+ikon salin 18), resi <=127 (+ikon 18),
   nilai <=59, lencana umur 68, lencana progress 99-111 (Sudah Serah Terima 134), tanggal+jam 92 (+lencana Lama 44 / Ulang 66). */
#dr_wrap table.dataTable thead th:nth-child(1)  { width:40px; }
#dr_wrap table.dataTable thead th:nth-child(4)  { width:122px; }
#dr_wrap[data-tab="SUDAH_DIBUKA"] table.dataTable thead th:nth-child(4) { width:170px; }
#dr_wrap[data-tab="SUDAH_SERAH"] table.dataTable thead th:nth-child(4) { width:176px; }
#dr_wrap table.dataTable thead th:nth-child(5)  { width:168px; }
#dr_wrap table.dataTable thead th:nth-child(6)  { width:162px; }
#dr_wrap table.dataTable thead th:nth-child(8)  { width:90px; }
#dr_wrap table.dataTable thead th:nth-child(9)  { width:84px; }
#dr_wrap table.dataTable thead th:nth-child(10) { width:128px; }
#dr_wrap[data-tab="SUDAH_SERAH"] table.dataTable thead th:nth-child(10) { width:152px; }
#dr_wrap[data-tab="BELUM_DIBUKA"] table.dataTable thead th:nth-child(10) { width:116px; }
#dr_wrap[data-tab="SUDAH_DIBUKA"] table.dataTable thead th:nth-child(10) { width:108px; }
#dr_wrap table.dataTable thead th:nth-child(11) { width:150px; }
@media (min-width: 1680px) {
  #dr_wrap table.dataTable thead th:nth-child(5)  { width:190px; }
  #dr_wrap table.dataTable thead th:nth-child(6)  { width:185px; }
  #dr_wrap table.dataTable thead th:nth-child(8)  { width:100px; }
  #dr_wrap table.dataTable thead th:nth-child(9)  { width:95px; }
  #dr_wrap table.dataTable thead th:nth-child(10) { width:150px; }
  #dr_wrap table.dataTable thead th:nth-child(11) { width:180px; }
}
@media (min-width: 1900px) {
  #dr_wrap table.dataTable thead th:nth-child(5)  { width:220px; }
  #dr_wrap table.dataTable thead th:nth-child(6)  { width:215px; }
  #dr_wrap table.dataTable thead th:nth-child(8)  { width:110px; }
  #dr_wrap table.dataTable thead th:nth-child(9)  { width:105px; }
  #dr_wrap table.dataTable thead th:nth-child(10) { width:170px; }
  #dr_wrap table.dataTable thead th:nth-child(11) { width:200px; }
}
/* Nilai (kolom 8): angka rata kanan, judul kolom dengan padding sama supaya sejajar */
#dr_wrap table.dataTable tbody td:nth-child(8), #dr_wrap table.dataTable thead th:nth-child(8) { text-align:right; }
/* tema menambah ~10px ruang panah urut di dalam judul kolom: padding judul dikurangi supaya teks sejajar dengan angka */
#dr_wrap table.dataTable thead th:nth-child(8) { padding-right:12px; }
#dr_wrap table.dataTable tbody td:nth-child(8) { padding-right:22px; }
#dr_wrap table.dataTable tbody td:nth-child(8) { font-variant-numeric:tabular-nums; }
/* Umur (kolom 9) rata tengah, sejajar dengan lencananya; tampil di semua tab (dari tanggal retur, bila tidak ada dari tanggal pesan) */
#dr_wrap table.dataTable tbody td:nth-child(9), #dr_wrap table.dataTable thead th:nth-child(9) { text-align:center; }
/* Kolom tanggal tahap (kolom 4: Tgl Terima / Buka / Serah Terima / Verifikasi menurut tab): tampil di semua tab kecuali Semua & Belum Diterima (kelas mode-buka) */
#dr_wrap:not(.mode-buka) table.dataTable th:nth-child(4), #dr_wrap:not(.mode-buka) table.dataTable td:nth-child(4) { display:none; }
/* Kolom centang (kolom 1): hanya tampil di tab Sudah dibuka / Sudah Serah Terima. display:none (kolom tetap ada) supaya urutan kolom tidak bergeser */
#dr_wrap table.dataTable th:first-child, #dr_wrap table.dataTable td:first-child { text-align:center; padding-left:0; padding-right:0; }
#dr_wrap:not(.mode-pilih) table.dataTable th:first-child, #dr_wrap:not(.mode-pilih) table.dataTable td:first-child { display:none; }
/* Progress (kolom 10) dan Status Jubelio (kolom 11) tampil di semua tab; Status Jubelio paling akhir. */
#dr_wrap table.dataTable tbody td:nth-child(11) { overflow:visible; }
.dr-sr { display:inline-block; margin-right:4px; padding:0 8px; border-radius:999px; font-size:11px; font-weight:700; line-height:20px; white-space:nowrap; }
.dr-sr-selesai { background:#dcfce7; color:#166534; }
.dr-sr-tolak { background:#fee2e2; color:#b91c1c; }
.dr-sr-barang { background:#e0f2fe; color:#0369a1; }
/* Filter Status Retur hanya tampil di tab Semua */
#dr_wrap:not([data-tab=""]):not([data-tab="SELESAI"]) #dr_status { display:none; }
.dr-sr-warn { background:#fef3c7; color:#92400e; padding:0 7px; }
.dr-pilih-info { color:#134e4a; font-size:12.5px; background:#f0fdfa; border:1px solid #99f6e4; border-radius:6px; padding:5px 12px; margin:0 0 6px; }
.dr-pilih-info a { color:#0f766e; font-weight:600; cursor:pointer; margin-left:10px; }
/* teks + ikon salin: ikon muncul saat baris diarahkan (tidak mengotori 25 baris sekaligus) */
.dr-no { display:flex; align-items:center; gap:6px; }
.dr-nt { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-variant-numeric:tabular-nums; }
.dr-salin { flex:none; color:#9ca3af; cursor:pointer; font-size:12px; opacity:0; transition:opacity .12s; }
#dr_wrap tbody tr:hover .dr-salin, .dr-salin:focus, .dr-salin.sudah { opacity:1; }
.dr-salin:hover { color:#0f766e; }
.dr-salin.sudah { color:#16a34a; }
.dr-toko, .dr-potong { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
/* awalan marketplace di sel Toko (kolom MP sudah tidak ada) */
.dr-mpx { display:inline; color:#9ca3af; font-size:11.5px; font-weight:600; margin-right:5px; }
#dr_wrap table.dataTable tbody tr:hover td { background:#f8fafc; }
/* tanggal + jam sebaris (satu baris, bukan dua) supaya baris tabel lebih pendek */
.dr-t1 { display:inline; font-weight:600; color:#111827; font-variant-numeric:tabular-nums; }
.dr-t2 { display:inline; margin-left:5px; color:#9ca3af; font-size:12px; font-variant-numeric:tabular-nums; }

/* Di bawah 1400 (laptop 1280/1366): kolom dirapatkan dan awalan MP di sel Toko dibuang (MP tetap di tooltip) supaya nama toko dapat ruang */
@media (max-width: 1399px) {
  .dr-mpx { display:none; }
  #dr_wrap table.dataTable thead th:nth-child(4)  { width:112px; }
  #dr_wrap[data-tab="SUDAH_DIBUKA"] table.dataTable thead th:nth-child(4) { width:150px; }
  #dr_wrap[data-tab="SUDAH_SERAH"] table.dataTable thead th:nth-child(4) { width:160px; }
  #dr_wrap table.dataTable thead th:nth-child(5)  { width:150px; }
  #dr_wrap table.dataTable thead th:nth-child(6)  { width:148px; }
  #dr_wrap table.dataTable thead th:nth-child(8)  { width:78px; }
  #dr_wrap table.dataTable thead th:nth-child(9)  { width:74px; }
  #dr_wrap table.dataTable thead th:nth-child(10) { width:116px; }
  #dr_wrap[data-tab="SUDAH_SERAH"] table.dataTable thead th:nth-child(10) { width:140px; }
  #dr_wrap table.dataTable thead th:nth-child(11) { width:118px; }
}
/* Layar sempit: tabel boleh digeser bila tetap tidak muat */
@media (max-width: 1200px) {
  #dr_tablewrap { overflow-x:auto; }
  #dr_wrap table.dataTable { min-width:900px; }
  #dr_wrap table.dataTable thead th { position:static; }
}

/* Indikator memuat: bar bergerak + keterangan, supaya jelas data sedang dimuat (bukan macet) */
#dr_tablewrap { position:relative; min-height:140px; }
#dr_tablewrap.memuat table { opacity:.35; pointer-events:none; transition:opacity .15s; }
.dr-loading { display:none; position:absolute; left:0; right:0; top:0; z-index:20; }
#dr_tablewrap.memuat .dr-loading { display:block; }
.dr-bar { height:4px; background:#e5e7eb; overflow:hidden; border-radius:2px; }
.dr-bar:before { content:''; display:block; height:100%; width:35%; background:#0f766e; border-radius:2px;
                 animation:dr-geser 1.1s ease-in-out infinite; }
@keyframes dr-geser { 0% { margin-left:-35%; } 100% { margin-left:100%; } }
.dr-memuat-teks { display:flex; justify-content:center; margin-top:46px; }
.dr-memuat-teks span { background:#fff; border:1px solid #e5e7eb; border-radius:20px; padding:8px 18px;
                        box-shadow:0 4px 14px rgba(0,0,0,.10); color:#374151; font-weight:600; }
.dr-memuat-teks i { color:#0f766e; margin-right:6px; }
.dr-memuat-teks small { color:#9ca3af; font-weight:400; margin-left:6px; }
.dr-tabs.sibuk, .dr-filter.sibuk { pointer-events:none; opacity:.7; }

/* Umur: lencana lembut (isi pucat + teks tegas), satu lapis peringatan; arti warna ada di tooltip & legenda */
.dr-umur { display:inline-block; min-width:68px; padding:0 10px; border-radius:999px; font-size:12px; font-weight:700; line-height:22px; white-space:nowrap; text-align:center; font-variant-numeric:tabular-nums; }
.dr-umur-aman { background:#dcfce7; color:#166534; }
.dr-umur-oranye { background:#fef3c7; color:#b45309; }
.dr-umur-merah { background:#fee2e2; color:#b91c1c; }
.dr-umur-selesai { background:#f3f4f6; color:#6b7280; font-weight:600; }
.dr-pill { display:inline-block; margin-top:2px; padding:0 8px; border-radius:999px; font-size:11px; font-weight:600; line-height:18px; white-space:nowrap; }

.dr-lama { display:inline-block; margin-left:6px; padding:0 7px; border-radius:999px; background:#f3f4f6; color:#6b7280; font-size:11px; font-weight:600; line-height:18px; cursor:help; }
.dr-ulang { display:inline-block; margin-left:6px; padding:0 7px; border-radius:999px; background:#fef3c7; color:#b45309; font-size:11px; font-weight:700; line-height:18px; cursor:help; }
/* Progress retur: lencana tahap (warna naik: abu -> biru -> teal -> ungu -> hijau) + 4 ruas penanda di bawahnya */
.dr-prog { display:flex; flex-direction:column; justify-content:center; gap:4px; cursor:default; }
.dr-st { display:inline-block; align-self:flex-start; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; padding:0 9px; border-radius:999px; font-weight:600; font-size:12px; line-height:22px; }
.dr-st-t0 { background:#f3f4f6; color:#4b5563; }
.dr-st-hangus { background:#fee2e2; color:#b91c1c; }
.dr-st-t1 { background:#dbeafe; color:#1d4ed8; }
.dr-st-t2 { background:#ccfbf1; color:#0f766e; }
.dr-st-t3 { background:#ede9fe; color:#6d28d9; }
.dr-st-t4 { background:#dcfce7; color:#166534; }
.dr-baris2 { display:flex; align-items:center; gap:6px; height:3px; }
.dr-seg { display:flex; gap:2px; width:100px; }
.dr-k { flex:none; margin:-8px 0; width:16px; height:16px; border-radius:50%; background:#ede9fe; color:#6d28d9; font-size:10px; font-weight:700; line-height:16px; text-align:center; cursor:help; }
.dr-seg i { flex:1; height:3px; border-radius:2px; background:#e5e7eb; }
.dr-seg i.on { background:#0f766e; }
/* Kartu rincian tahap (muncul saat Progress diarahkan; digambar JS di luar tabel supaya tidak terpotong sel) */
#dr_kartu { display:none; position:fixed; z-index:200; width:320px; padding:12px 14px; background:#fff; border:1px solid #e5e7eb; border-radius:8px;
            box-shadow:0 8px 24px rgba(0,0,0,.14); font-size:12.5px; color:#374151; pointer-events:none; }
#dr_kartu .ks { display:flex; gap:10px; padding:6px 0; }
#dr_kartu .ks + .ks { border-top:1px solid #f1f5f9; }
#dr_kartu .ki { flex:none; width:18px; height:18px; margin-top:1px; border-radius:50%; background:#e5e7eb; color:#fff; font-size:10px; display:flex; align-items:center; justify-content:center; }
#dr_kartu .ks.ok .ki { background:#0f766e; }
#dr_kartu .kn { font-weight:600; color:#111827; }
#dr_kartu .kw { color:#6b7280; font-variant-numeric:tabular-nums; }
#dr_kartu .kx { color:#b45309; font-size:11.5px; }
#dr_kartu .ks.info .ki { background:#94a3b8; }
#dr_kartu .ks.info .kn { color:#111827; font-weight:600; }
#dr_kartu .kp { color:#374151; }
#dr_kartu .ks.tahap:not(.ok) .kn, #dr_kartu .ks.tahap:not(.ok) .kw { color:#9ca3af; font-weight:400; }

/* Tanda tanggal perkiraan: satu glyph ≈ sebaris dengan tanggal (arti di tooltip & legenda) */
.dr-sb { display:inline-block; margin-left:6px; font-size:12px; font-weight:700; cursor:help; }
.dr-sb-ok { color:#166534; }
.dr-sb-kira { color:#b45309; }
.dr-sb-kasar { color:#b91c1c; }
</style>

<div id="dr_wrap" data-tab="">

  <!-- Tab status retur -->
  <div class="dr-tabs" id="dr_tabs">
    <div class="dr-tab on" data-status="" title="Semua retur Jubelio (ekspedisi + SELESAI / DITOLAK, bisa difilter Status retur); tanggal disaring menurut tanggal tahap masing-masing">Semua <span class="n" id="n_semua">-</span></div>
    <div class="dr-tab" data-status="BELUM_DITERIMA" title="Belum ada scan terima, umur 30 hari atau kurang">Belum Terima <span class="n hot" id="n_BELUM_DITERIMA">-</span></div>
    <div class="dr-tab" data-status="BELUM_HANGUS" title="Belum ada scan terima dan umur lebih dari 30 hari (dianggap hangus)">Belum Terima (Hangus) <span class="n" id="n_BELUM_HANGUS">-</span></div>
    <div class="dr-tab" data-status="BELUM_DIBUKA" title="Sudah scan terima, belum dibuka">Sudah Terima <span class="n hot" id="n_BELUM_DIBUKA">-</span></div>
    <div class="dr-tab" data-status="SUDAH_DIBUKA" title="Sudah scan buka, belum dicetak serah terima">Sudah Buka <span class="n" id="n_SUDAH_DIBUKA">-</span></div>
    <div class="dr-tab" data-status="SUDAH_SERAH" title="Sudah dicetak serah terima, belum diverifikasi accounting (bisa Cetak Ulang)">Sudah Serah Terima <span class="n" id="n_SUDAH_SERAH">-</span></div>
    <div class="dr-tab" data-status="SUDAH_VERIF" title="Sudah diverifikasi accounting, tetapi status di Jubelio masih BARANG RETUR (accounting perlu menuntaskannya di Jubelio)">Sudah Verifikasi <span class="n" id="n_SUDAH_VERIF">-</span></div>
    <div class="dr-tab" data-status="SELESAI" title="Status di Jubelio sudah SELESAI atau DITOLAK; bisa difilter Status retur">Selesai <span class="n" id="n_SELESAI">-</span></div>
  </div>

  <!-- Filter -->
  <div class="dr-filter">
    <span class="dr-chip"><i class="fa fa-calendar"></i>
      <span class="dr-chip-lbl" id="dr_rentang_lbl" title="Filter tanggal mengikuti tab: Belum Diterima = tanggal pesan, Sudah Terima = tanggal terima, Sudah Buka = tanggal buka, Sudah Serah Terima = tanggal serah terima, Sudah Verifikasi = tanggal verifikasi.">Tgl progress</span>
      <input type="text" id="dr_rentang" readonly />
    </span>
    <div class="dr-multi" id="dr_jenis">
      <div class="dr-multi-btn" id="dr_jenis_btn" tabindex="0" role="button" aria-haspopup="true">
        <i class="fa fa-filter"></i>
        <span class="lbl">Jenis retur</span>
        <span class="nilai" id="dr_jenis_nilai">Semua</span>
        <span class="cnt semua" id="dr_jenis_cnt">2</span>
        <i class="fa fa-angle-down"></i>
      </div>
      <div class="dr-multi-pop" id="dr_jenis_pop">
        <label class="dr-opsi">
          <input type="checkbox" value="B" checked><span class="dr-kotak"><i class="fa fa-check"></i></span>
          <span class="teks">Ekspedisi<span class="ket">Retur ekspedisi (gagal kirim)</span></span>
        </label>
        <label class="dr-opsi">
          <input type="checkbox" value="K" checked><span class="dr-kotak"><i class="fa fa-check"></i></span>
          <span class="teks">Komplain<span class="ket">Pengembalian barang dari customer</span></span>
        </label>
        <div class="dr-multi-kaki"><a id="dr_jenis_semua">Pilih semua</a><a id="dr_jenis_hapus">Hapus</a></div>
      </div>
    </div>
    <!-- Status retur Jubelio (hanya tab Semua): SELESAI / DITOLAK / BARANG RETUR -->
    <div class="dr-multi" id="dr_status">
      <div class="dr-multi-btn" id="dr_status_btn" tabindex="0" role="button" aria-haspopup="true">
        <i class="fa fa-tag"></i>
        <span class="lbl">Status retur</span>
        <span class="nilai" id="dr_status_nilai">Semua</span>
        <span class="cnt semua" id="dr_status_cnt">3</span>
        <i class="fa fa-angle-down"></i>
      </div>
      <div class="dr-multi-pop" id="dr_status_pop">
        <label class="dr-opsi">
          <input type="checkbox" value="SELESAI" checked><span class="dr-kotak"><i class="fa fa-check"></i></span>
          <span class="teks">SELESAI<span class="ket">Retur selesai diproses di Jubelio</span></span>
        </label>
        <label class="dr-opsi">
          <input type="checkbox" value="DITOLAK" checked><span class="dr-kotak"><i class="fa fa-check"></i></span>
          <span class="teks">DITOLAK<span class="ket">Pengajuan retur ditolak</span></span>
        </label>
        <label class="dr-opsi">
          <input type="checkbox" value="BARANG RETUR" checked><span class="dr-kotak"><i class="fa fa-check"></i></span>
          <span class="teks">BARANG RETUR<span class="ket">Retur ekspedisi (paket kembali ke toko)</span></span>
        </label>
        <div class="dr-multi-kaki"><a id="dr_status_semua">Pilih semua</a><a id="dr_status_hapus">Hapus</a></div>
      </div>
    </div>
    <!-- Filter multi-pilih Kurir / MP / Toko: pilihan diisi JS dari data (kosong = semua) -->
    <div class="dr-multi dr-dyn" id="dr_f_kurir" data-kunci="kurir">
      <div class="dr-multi-btn" tabindex="0" role="button" aria-haspopup="true"><i class="fa fa-truck"></i><span class="lbl">Kurir</span><span class="nilai">Semua</span><span class="cnt semua">0</span><i class="fa fa-angle-down"></i></div>
      <div class="dr-multi-pop"><input type="text" class="dr-cari-opsi" placeholder="Cari kurir..." autocomplete="off"><div class="dr-daftar"></div>
        <div class="dr-multi-kaki"><span class="text-muted">Kosong = semua</span><a class="dr-f-hapus">Hapus pilihan</a></div></div>
    </div>
    <div class="dr-multi dr-dyn" id="dr_f_mp" data-kunci="mp">
      <div class="dr-multi-btn" tabindex="0" role="button" aria-haspopup="true"><i class="fa fa-shopping-bag"></i><span class="lbl">MP</span><span class="nilai">Semua</span><span class="cnt semua">0</span><i class="fa fa-angle-down"></i></div>
      <div class="dr-multi-pop"><input type="text" class="dr-cari-opsi" placeholder="Cari marketplace..." autocomplete="off"><div class="dr-daftar"></div>
        <div class="dr-multi-kaki"><span class="text-muted">Kosong = semua</span><a class="dr-f-hapus">Hapus pilihan</a></div></div>
    </div>
    <div class="dr-multi dr-dyn" id="dr_f_toko" data-kunci="toko">
      <div class="dr-multi-btn" tabindex="0" role="button" aria-haspopup="true"><i class="fa fa-home"></i><span class="lbl">Toko</span><span class="nilai">Semua</span><span class="cnt semua">0</span><i class="fa fa-angle-down"></i></div>
      <div class="dr-multi-pop"><input type="text" class="dr-cari-opsi" placeholder="Cari toko..." autocomplete="off"><div class="dr-daftar"></div>
        <div class="dr-multi-kaki"><span class="text-muted">Kosong = semua</span><a class="dr-f-hapus">Hapus pilihan</a></div></div>
    </div>
    <button type="button" class="dr-reset" id="dr_reset" title="Atur ulang filter"><i class="fa fa-undo"></i></button>
    <!-- kotak cari DataTables disisipkan JS di sini -->
    <div class="dr-acts">
      <button type="button" class="dr-export" id="dr_export" title="Unduh Excel sesuai filter, tab, dan pencarian saat ini"><i class="fa fa-file-excel-o"></i> Export</button>
      <button type="button" class="dr-export dr-cetak" id="dr_cetak" style="display:none;" disabled title="Centang resi yang mau dicetak dulu"><i class="fa fa-print"></i> Cetak Serah Terima</button>
    </div>
  </div>
  <!-- Bar pilihan (tab Sudah dibuka): jumlah resi dicentang + Pilih semua hasil / Hapus pilihan -->
  <div class="dr-pilih-info" id="dr_pilih_info" style="display:none;"></div>

  <div id="dr_tablewrap">
  <div class="dr-loading">
    <div class="dr-bar"></div>
    <div class="dr-memuat-teks"><span><i class="fa fa-spinner fa-spin"></i><b id="dr_memuat_teks">Memuat data...</b><small id="dr_memuat_detik"></small></span></div>
  </div>
  <table class="table table-hover" id="dt_daftar_retur" style="width:100%;">
    <thead>
      <tr>
        <th><input type="checkbox" id="dr_pilih_hal" title="Centang semua resi di halaman ini"></th>
        <th>Tgl Pesan</th>
        <th>Tgl Retur</th>
        <th id="dr_th_tahap">Tgl Buka</th>
        <th>No. Pesanan</th>
        <th>No. Resi</th>
        <th>Toko</th>
        <th>Nilai (Rp)</th>
        <th>Umur<span class="dr-bantu" id="dr_bantu" tabindex="0" aria-label="Arti warna umur">
          <i class="fa fa-info-circle"></i>
          <div class="dr-pop">
            <p>Umur dihitung dari tanggal retur; bila tanggal retur tidak ada, dari tanggal pesan.</p>
            <div class="dr-lg"><span class="dr-umur dr-umur-oranye">&ge; <?= (int) $umur_oranye ?> hari</span> peringatan</div>
            <div class="dr-lg"><span class="dr-umur dr-umur-merah">&ge; <?= (int) $umur_merah ?> hari</span> wajib klaim</div>
            <div class="dr-lg2 text-muted">Paket yang sudah dibuka tidak diberi warna.</div>
            <div class="dr-lg2 text-muted"><b style="color:#b91c1c;">&asymp;</b> di tanggal retur = perkiraan (tanggal retur tidak tersedia, dipakai tanggal order/kirim).</div>
          </div>
        </span></th>
        <th>Progress</th>
        <th title="Status retur di Jubelio. Ikon segitiga kuning = sudah diverifikasi di iresis, tetapi di Jubelio masih BARANG RETUR (belum final).">Status Jubelio</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  </div>

</div>

<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';
    var statusAktif = '';
    var jenisAktif = 'B,K';        // centang filter jenis retur; '-' = tidak ada
    var STATUS_SEMUA = 'SELESAI,DITOLAK,BARANG RETUR';
    var statusRetAktif = STATUS_SEMUA;   // centang filter status retur (tab Semua); '-' = tidak ada
    var segarkanBerikut = false;   // true = lewati cache server (tombol atur ulang)
    var timerMuat = null, mulaiMuat = 0;
    var tabel;
    var sel = {};                  // resi yang dicentang (bertahan antar halaman) untuk cetak serah terima
    // filter multi-pilih Kurir / MP / Toko (lihat modul di bawah); harus ada sebelum DataTable dibuat karena ajax.data memakainya
    var FIL = { kurir: { pilih: {}, opsi: [], terapan: '' }, mp: { pilih: {}, opsi: [], terapan: '' }, toko: { pilih: {}, opsi: [], terapan: '' } };

    // Tema (actions.js page_content_onresize) menyetel tinggi .page-content = tinggi daftar menu samping: saat halaman dimuat menu itu
    // belum diberi scrollbar sendiri (tinggi alami ~1000 px), dan sesudahnya tingginya setinggi jendela tanpa memperhitungkan padding
    // atas 50 px bilah menu. Akibatnya halaman selalu bisa digulir (hingga ratusan px) walau isinya muat. Setel ulang: tinggi alami isi,
    // dinaikkan hanya sampai memenuhi jendela (jendela - padding atas).
    function rapikanTinggi() {
        var $c = $('.page-content');
        if (!$c.length) return;
        $c.css('height', '');
        var minimal = $(window).height() - ($c.outerHeight() - $c.height());
        if ($c.height() < minimal) $c.height(minimal);
    }
    function jmlPilih() { return Object.keys(sel).length; }
    function ubahInfoPilih() {
        if (!tabel) return;
        var n = jmlPilih(), total = tabel.page.info().recordsDisplay;
        $('#dr_cetak').prop('disabled', n === 0)
            .attr('title', n ? (statusAktif === 'SUDAH_SERAH' ? 'Cetak ulang serah terima ' : 'Cetak serah terima ') + n + ' resi yang dicentang' : 'Centang resi yang mau dicetak dulu');
        var h = n ? '<b>' + n.toLocaleString('id-ID') + ' resi dipilih</b> <a id="dr_pilih_hapus">Hapus pilihan</a>' : 'Centang resi untuk ' + (statusAktif === 'SUDAH_SERAH' ? 'Cetak Ulang Serah Terima.' : 'Cetak Serah Terima.');
        if (total > 0 && n < total) h += '<a id="dr_pilih_semua">Pilih semua ' + total.toLocaleString('id-ID') + ' hasil</a>';
        $('#dr_pilih_info').html(h);
        var $hal = $('#dt_daftar_retur .dr-pilih');
        $('#dr_pilih_hal').prop('checked', $hal.length > 0 && $hal.filter(':checked').length === $hal.length);
    }
    function aturModePilih(aktif) {
        $('#dr_wrap').toggleClass('mode-pilih', aktif);
        $('#dr_cetak, #dr_pilih_info').toggle(aktif);
        var ulang = (statusAktif === 'SUDAH_SERAH');
        $('#dr_cetak').html('<i class="fa fa-print"></i> ' + (ulang ? 'Cetak Ulang Serah Terima' : 'Cetak Serah Terima'));
    }
    // Tab -> nama kolom tanggal tahap (judul kolom) dan label filter tanggal.
    var NAMA_TAHAP = {
        'BELUM_DIBUKA': ['Tgl Terima', 'Tgl terima'], 'SUDAH_DIBUKA': ['Tgl Buka', 'Tgl buka'],
        'SUDAH_SERAH': ['Tgl Serah Terima', 'Tgl serah terima'], 'SUDAH_VERIF': ['Tgl Verifikasi', 'Tgl verifikasi'],
        'SELESAI': ['Tgl Verifikasi', 'Tgl verifikasi']
    };
    function aturModeTanggal(status) {
        var n = NAMA_TAHAP[status];
        $('#dr_wrap').toggleClass('mode-buka', !!n).attr('data-tab', status);
        // Jenis retur baru diketahui setelah scan terima: filter jenis tidak berlaku di tab Belum Terima / Hangus.
        var belum = (status === 'BELUM_DITERIMA' || status === 'BELUM_HANGUS');
        $('#dr_jenis').toggleClass('nonaktif', belum).attr('title', belum ? 'Jenis retur baru diketahui setelah scan terima, jadi filter ini tidak berlaku di tab Belum Terima' : '');
        $('#dr_rentang_lbl').text(n ? n[1] : ((status === 'BELUM_DITERIMA' || status === 'BELUM_HANGUS') ? 'Tgl pesan' : 'Tgl progress'));
        if (n && tabel) $(tabel.column(3).header()).text(n[0]);
    }

    function mulaiMemuat() {
        mulaiMuat = Date.now();
        $('#dr_tablewrap').addClass('memuat');
        $('#dr_tabs, .dr-filter').addClass('sibuk');
        $('#dr_memuat_teks').text(segarkanBerikut ? 'Menghitung ulang data terbaru...' : 'Memuat data...');
        $('#dr_memuat_detik').text('');
        clearInterval(timerMuat);
        timerMuat = setInterval(function () {
            var d = Math.floor((Date.now() - mulaiMuat) / 1000);
            $('#dr_memuat_detik').text(d >= 1 ? d + ' dtk' : '');
            if (d >= 4) $('#dr_memuat_teks').text('Masih memuat, mohon tunggu...');
        }, 500);
    }
    function selesaiMemuat() {
        clearInterval(timerMuat);
        $('#dr_tablewrap').removeClass('memuat');
        $('#dr_tabs, .dr-filter').removeClass('sibuk');
    }

    function rentangBawaan() {
        return [moment().startOf('year'), moment()];
    }
    function tulisRentang(a, b) {
        $('#dr_rentang').val(a.format('YYYY-MM-DD') + ' s/d ' + b.format('YYYY-MM-DD'));
    }

    var awal = rentangBawaan();
    $('#dr_rentang').daterangepicker({
        startDate: awal[0],
        endDate: awal[1],
        ranges: {
            'Hari Ini'        : [moment(), moment()],
            '7 Hari Terakhir' : [moment().subtract(6, 'days'), moment()],
            '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
            'Bulan Ini'       : [moment().startOf('month'), moment().endOf('month')],
            'Tahun Ini'       : [moment().startOf('year'), moment()]
        },
        locale: {
            format: 'YYYY-MM-DD', separator: ' s/d ',
            applyLabel: 'Terapkan', cancelLabel: 'Batal',
            fromLabel: 'Dari', toLabel: 'Sampai', customRangeLabel: 'Custom',
            daysOfWeek: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
            monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
            firstDay: 1
        }
    });
    tulisRentang(awal[0], awal[1]);

    tabel = $('#dt_daftar_retur').DataTable({
        processing: false,
        serverSide: true,
        autoWidth: false,
        // Pencarian di atas tabel; jumlah baris per halaman + info + paging di bawah.
        dom: "<'dr-atas'f>rt<'dr-bawah'<'dr-kiri'i><'dr-kanan'lp>>",   // kotak cari dipindah ke bar filter (lihat bawah)
        pageLength: 15,   // 15 baris: halaman + navigasi muat tanpa gulir bila tinggi jendela >= ~840 px
        pagingType: 'full_numbers',   // tombol « awal, ‹ sebelumnya, nomor, › berikutnya, » akhir
        lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
        order: [[8, 'desc']],          // umur terbesar di atas
        columnDefs: [{ targets: 0, orderable: false, searchable: false }],
        drawCallback: function () {
            // pulihkan centang resi yang sudah dipilih (juga setelah ganti halaman / urutan)
            $('#dt_daftar_retur .dr-pilih').each(function () { this.checked = !!sel[$(this).attr('data-resi')]; });
            ubahInfoPilih();
            rapikanTinggi();
        },
        ajax: {
            url: BASE + 'retur/get-daftar-retur-penjualan',
            type: 'POST',
            data: function (d) {
                var v = $('#dr_rentang').val().split(' s/d ');
                d.start_date = v[0] || '';
                d.end_date = v[1] || '';
                d.status = statusAktif;
                d.jenis = jenisAktif;
                d.status_retur = statusRetAktif;
                d.f_kurir = pilihJson('kurir'); d.f_mp = pilihJson('mp'); d.f_toko = pilihJson('toko');
                d.segarkan = segarkanBerikut ? '1' : '0';
            }
        },
        language: {
            processing: 'Memuat data...',
            emptyTable: 'Tidak ada retur pada rentang tanggal ini',
            zeroRecords: 'Tidak ada data yang cocok',
            search: '',
            searchPlaceholder: 'Cari resi, pesanan, kurir, toko...',
            lengthMenu: 'Baris per halaman _MENU_',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '',
            thousands: '.',
            paginate: { first: '«', previous: '‹', next: '›', last: '»' }
        }
    });

    // Kotak cari: pindah ke bar filter (setelah tombol atur ulang) supaya tidak makan satu baris sendiri di atas tabel.
    $('#dt_daftar_retur_filter').insertAfter('#dr_reset').find('input').attr('autocomplete', 'off');
    $('.dr-atas').remove();

    // Jumlah per tab + baris "Ada N retur" dari respons server.
    tabel.on('xhr.dt', function (e, settings, json) {
        if (!json) return;
        gabungOpsi(json.opsi);
        var c = json.tab_counts || {};
        var semua = 0;
        ['BELUM_DITERIMA', 'BELUM_HANGUS', 'BELUM_DIBUKA', 'SUDAH_DIBUKA', 'SUDAH_SERAH', 'SUDAH_VERIF'].forEach(function (k) {
            var n = parseInt(c[k] || 0, 10);
            semua += n;
            $('#n_' + k).text(n.toLocaleString('id-ID'));
        });
        // Angka Selesai (final di Jubelio) hanya dihitung server saat tab Semua / Selesai aktif (baris tambahannya berat);
        // di tab lain angka terakhir dibiarkan.
        if (json.jumlah_selesai !== null && json.jumlah_selesai !== undefined) {
            $('#n_SELESAI').text(parseInt(json.jumlah_selesai, 10).toLocaleString('id-ID')).data('terisi', true);
        }
        // Tab Semua = retur ekspedisi + SELESAI/DITOLAK menurut filter Status retur; angkanya hanya dihitung server saat tab Semua
        // aktif (baris tambahannya berat), jadi di tab lain angka terakhir dibiarkan.
        if (json.jumlah_semua !== null && json.jumlah_semua !== undefined) semua = parseInt(json.jumlah_semua, 10);
        if (json.jumlah_semua !== null && json.jumlah_semua !== undefined || !$('#n_semua').data('terisi')) {
            $('#n_semua').text(semua.toLocaleString('id-ID')).data('terisi', json.jumlah_semua !== null && json.jumlah_semua !== undefined);
        }
        // Waktu hitung data hanya di tooltip tombol atur ulang (tidak perlu teks tambahan di layar).
        $('#dr_reset').attr('title', 'Atur ulang filter & ambil data terbaru' + (json.dihitung_pada ? ' (data per ' + json.dihitung_pada + ')' : ''));
        segarkanBerikut = false;
        selesaiMemuat();
    });
    tabel.on('preXhr.dt', mulaiMemuat);
    // Gagal memuat: hentikan indikator supaya tidak terlihat macet selamanya.
    $('#dt_daftar_retur').on('error.dt', function () { segarkanBerikut = false; selesaiMemuat(); });

    // Ikon info: klik untuk membuka/menutup (selain hover), klik di luar menutup.
    $('#dr_bantu').on('click', function (e) { e.stopPropagation(); $(this).toggleClass('buka'); });
    $(document).on('click.drbantu', function () { $('#dr_bantu').removeClass('buka'); });

    // Salin nomor pesanan / resi dengan satu klik. clipboard API hanya jalan di HTTPS/localhost dan
    // bisa ditolak (mis. jendela tidak fokus); klien LAN memakai HTTP -> cadangan textarea + execCommand.
    function salinCadangan(teks) {
        var ta = document.createElement('textarea');
        ta.value = teks;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        var berhasil = false;
        try { berhasil = document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
        return berhasil;
    }
    function salinTeks(teks) {
        return new Promise(function (ok, gagal) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(teks).then(ok, function () {
                    salinCadangan(teks) ? ok() : gagal();
                });
            } else {
                salinCadangan(teks) ? ok() : gagal();
            }
        });
    }
    $('#dt_daftar_retur').on('click', '.dr-salin', function () {
        var $i = $(this), teks = String($i.attr('data-salin') || '');
        salinTeks(teks).then(function () {
            $i.removeClass('fa-clipboard').addClass('fa-check sudah').attr('title', 'Tersalin');
            setTimeout(function () { $i.removeClass('fa-check sudah').addClass('fa-clipboard').attr('title', 'Salin'); }, 1200);
        }, function () {
            window.prompt('Salin manual (Ctrl+C):', teks);
        });
    });

    // Kartu rincian progres: Dipesan, Diretur, lalu tanggal, jam, dan petugas tiap tahap (data-detail dari server).
    var $kartu = $('<div id="dr_kartu"></div>').appendTo('body');
    function esc(t) { return $('<div>').text(t == null ? '' : t).html(); }
    $('#dt_daftar_retur').on('mouseenter', '.dr-prog', function () {
        var d;
        try { d = JSON.parse($(this).attr('data-detail') || '[]'); } catch (e) { return; }
        var h = '';
        d.forEach(function (x) {
            var ada = !!x.t || !!x.jub, info = (x.k === 'info');
            var judul = (info || ada) ? x.n : 'Belum ' + x.n.replace(/^Sudah /, '');
            h += '<div class="ks ' + (info ? 'info' : 'tahap') + (ada ? ' ok' : '') + '"><span class="ki">' + (ada && !info ? '<i class="fa fa-check"></i>' : '') + '</span><div>' +
                '<div class="kn">' + esc(judul) + '</div>' +
                (x.t ? '<div class="kw">' + esc(x.t) + ' · ' + esc(x.w) + '</div>' : (x.jub ? '<div class="kw">Tanggal tidak tersedia</div>' : '<div class="kw">' + (x.lama ? 'Tidak tercatat (data lama)' : 'Belum ada catatan') + '</div>')) +
                (x.t && x.p ? '<div class="kp">' + esc(x.p) + '</div>' : (x.t && !info ? '<div class="kp">Petugas tidak tercatat</div>' : '')) +
                (x.x ? '<div class="kx">' + esc(x.x) + '</div>' : '') +
                '</div></div>';
        });
        $kartu.html(h).show();
        var r = this.getBoundingClientRect(), k = $kartu[0].getBoundingClientRect();
        var left = Math.max(8, Math.min(r.left, window.innerWidth - k.width - 8));
        var top = r.bottom + 6;
        if (top + k.height > window.innerHeight - 8) top = Math.max(8, r.top - k.height - 6);
        $kartu.css({ left: left, top: top });
    }).on('mouseleave', '.dr-prog', function () { $kartu.hide(); });
    $(window).on('scroll.drkartu', function () { $kartu.hide(); });
    tabel.on('preXhr.dt', function () { $kartu.hide(); });

    $('#dr_tabs').on('click', '.dr-tab', function () {
        $('#dr_tabs .dr-tab').removeClass('on');
        $(this).addClass('on');
        statusAktif = $(this).data('status') || '';
        sel = {};   // pilihan tidak dibawa antar tab
        aturModePilih(statusAktif === 'SUDAH_DIBUKA' || statusAktif === 'SUDAH_SERAH');   // centang + cetak (Sudah Buka) / cetak ulang (Sudah Serah Terima)
        aturModeTanggal(statusAktif);
        // Tab ber-kolom tanggal tahap: urut default tanggal tahap (terbaru di atas); tab lain: umur terbesar di atas
        tabel.order(NAMA_TAHAP[statusAktif] ? [[3, 'desc']] : [[8, 'desc']]);
        tabel.ajax.reload();
    });


    // Filter jenis retur (centang multi). Data dimuat ulang saat panel ditutup / tombol aksi ditekan,
    // bukan di tiap centang, supaya memilih dua opsi tidak memicu dua kali muat.
    var jenisBerubah = false;
    function bacaJenis() {
        var v = $('#dr_jenis_pop input:checked').map(function () { return this.value; }).get();
        return v.length ? v.join(',') : '-';
    }
    function tulisTombolJenis() {
        var v = $('#dr_jenis_pop input:checked').map(function () { return this.value; }).get();
        var teks = v.length === 2 ? 'Semua' : (v.length === 0 ? 'Tidak ada' : (v[0] === 'K' ? 'Komplain' : 'Ekspedisi'));
        $('#dr_jenis_nilai').text(teks);
        $('#dr_jenis_cnt').text(v.length).toggleClass('semua', v.length === 2);
    }
    function tutupJenis() {
        if (!$('#dr_jenis').hasClass('buka')) return;
        $('#dr_jenis').removeClass('buka');
        var baru = bacaJenis();
        if (baru !== jenisAktif) { jenisAktif = baru; tabel.ajax.reload(); }
    }
    $('#dr_jenis_btn').on('click', function (e) {
        e.stopPropagation();
        if ($('#dr_jenis').hasClass('buka')) tutupJenis(); else $('#dr_jenis').addClass('buka');
    }).on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
        if (e.key === 'Escape') tutupJenis();
    });
    $('#dr_jenis_pop').on('click', function (e) { e.stopPropagation(); });
    $('#dr_jenis_pop').on('change', 'input', tulisTombolJenis);
    $('#dr_jenis_semua').on('click', function () { $('#dr_jenis_pop input').prop('checked', true); tulisTombolJenis(); });
    $('#dr_jenis_hapus').on('click', function () { $('#dr_jenis_pop input').prop('checked', false); tulisTombolJenis(); });
    $(document).on('click.drjenis', tutupJenis);

    // Filter status retur Jubelio (tab Semua): pola sama dengan filter jenis; muat ulang saat panel ditutup.
    function bacaStatusRet() {
        var v = $('#dr_status_pop input:checked').map(function () { return this.value; }).get();
        return v.length ? v.join(',') : '-';
    }
    function tulisTombolStatus() {
        var v = $('#dr_status_pop input:checked').map(function () { return this.value; }).get();
        var teks = v.length === 3 ? 'Semua' : (v.length === 0 ? 'Tidak ada' : v.join(', '));
        $('#dr_status_nilai').text(teks);
        $('#dr_status_cnt').text(v.length).toggleClass('semua', v.length === 3);
    }
    function tutupStatus() {
        if (!$('#dr_status').hasClass('buka')) return;
        $('#dr_status').removeClass('buka');
        var baru = bacaStatusRet();
        if (baru !== statusRetAktif) { statusRetAktif = baru; tabel.ajax.reload(); }
    }
    $('#dr_status_btn').on('click', function (e) {
        e.stopPropagation();
        tutupJenis();
        if ($('#dr_status').hasClass('buka')) tutupStatus(); else $('#dr_status').addClass('buka');
    }).on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
        if (e.key === 'Escape') tutupStatus();
    });
    $('#dr_jenis_btn').on('click.drstatus', tutupStatus);
    $('#dr_status_pop').on('click', function (e) { e.stopPropagation(); });
    $('#dr_status_pop').on('change', 'input', tulisTombolStatus);
    $('#dr_status_semua').on('click', function () { $('#dr_status_pop input').prop('checked', true); tulisTombolStatus(); });
    $('#dr_status_hapus').on('click', function () { $('#dr_status_pop input').prop('checked', false); tulisTombolStatus(); });
    $(document).on('click.drstatus', tutupStatus);

    // Filter multi-pilih Kurir / MP / Toko. Pilihan diambil dari data (respons server: json.opsi) dan digabung, tidak pernah dibuang,
    // supaya pilihan yang sudah dicentang tidak hilang saat tab/filter lain berganti. Tidak ada yang dicentang = semua.
    function pilihJson(k) {
        var a = Object.keys(FIL[k].pilih).sort();
        return a.length ? JSON.stringify(a) : '';
    }
    function tulisTombolF(k) {
        var a = Object.keys(FIL[k].pilih), $f = $('#dr_f_' + k);
        $f.find('.nilai').text(a.length === 0 ? 'Semua' : (a.length === 1 ? (a[0] || '(kosong)') : a.length + ' dipilih')).attr('title', a.join(', '));
        $f.find('.cnt').text(a.length).toggleClass('semua', a.length === 0);
        gambarOpsi(k);
    }
    function gambarOpsi(k) {
        var $f = $('#dr_f_' + k), q = $.trim($f.find('.dr-cari-opsi').val()).toLowerCase(), h = '';
        FIL[k].opsi.forEach(function (v) {
            var label = v === '' ? '(kosong)' : v;
            if (q && label.toLowerCase().indexOf(q) < 0) return;
            h += '<label class="dr-opsi"><input type="checkbox" value="' + esc(v).replace(/"/g, '&quot;') + '"' + (FIL[k].pilih[v] ? ' checked' : '') +
                '><span class="dr-kotak"><i class="fa fa-check"></i></span><span class="teks">' + esc(label) + '</span></label>';
        });
        $f.find('.dr-daftar').html(h || '<div class="dr-kosong">' + (FIL[k].opsi.length ? 'Tidak ada yang cocok' : 'Belum ada data') + '</div>');
    }
    function gabungOpsi(o) {
        if (!o) return;
        Object.keys(FIL).forEach(function (k) {
            var m = {};
            FIL[k].opsi.concat(o[k] || []).forEach(function (v) { m[v] = true; });
            var baru = Object.keys(m).sort(function (a, b) { return a.toLowerCase() < b.toLowerCase() ? -1 : (a.toLowerCase() > b.toLowerCase() ? 1 : 0); });
            if (baru.length !== FIL[k].opsi.length) { FIL[k].opsi = baru; gambarOpsi(k); }
        });
    }
    function tutupF(k) {
        var $f = $('#dr_f_' + k);
        if (!$f.hasClass('buka')) return;
        $f.removeClass('buka');
        var baru = pilihJson(k);
        if (baru !== FIL[k].terapan) { FIL[k].terapan = baru; sel = {}; tabel.ajax.reload(); }
    }
    function tutupSemuaF(kecuali) { Object.keys(FIL).forEach(function (k) { if (k !== kecuali) tutupF(k); }); }
    $('.dr-dyn').each(function () {
        var $f = $(this), k = $f.attr('data-kunci');
        $f.find('.dr-multi-btn').on('click', function (e) {
            e.stopPropagation();
            tutupJenis(); tutupStatus(); tutupSemuaF(k);
            if ($f.hasClass('buka')) tutupF(k); else { $f.addClass('buka'); $f.find('.dr-cari-opsi').focus(); }
        }).on('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
            if (e.key === 'Escape') tutupF(k);
        });
        $f.find('.dr-multi-pop').on('click', function (e) { e.stopPropagation(); });
        $f.on('change', '.dr-daftar input', function () {
            if (this.checked) FIL[k].pilih[this.value] = true; else delete FIL[k].pilih[this.value];
            tulisTombolF(k);
        });
        $f.find('.dr-cari-opsi').on('input', function () { gambarOpsi(k); });
        $f.find('.dr-f-hapus').on('click', function () { FIL[k].pilih = {}; tulisTombolF(k); });
    });
    $(document).on('click.drdyn', function () { tutupSemuaF(); });
    $('#dr_jenis_btn, #dr_status_btn').on('click.drdyn', function () { tutupSemuaF(); });

    $('#dr_rentang').on('apply.daterangepicker', function () { tabel.ajax.reload(); });

    // Export xlsx: pakai filter yang sedang aktif (rentang, tab, jenis, kotak cari).
    // Centang resi: per baris, per halaman (header), atau seluruh hasil filter (daftar resi diminta ke server).
    $('#dt_daftar_retur').on('change', '.dr-pilih', function () {
        var r = $(this).attr('data-resi');
        if (this.checked) sel[r] = true; else delete sel[r];
        ubahInfoPilih();
    });
    $('#dr_pilih_hal').on('change', function () {
        var on = this.checked;
        $('#dt_daftar_retur .dr-pilih').each(function () {
            this.checked = on;
            var r = $(this).attr('data-resi');
            if (on) sel[r] = true; else delete sel[r];
        });
        ubahInfoPilih();
    });
    $('#dr_pilih_info').on('click', '#dr_pilih_hapus', function () {
        sel = {};
        $('#dt_daftar_retur .dr-pilih').prop('checked', false);
        ubahInfoPilih();
    });
    $('#dr_pilih_info').on('click', '#dr_pilih_semua', function () {
        var v = $('#dr_rentang').val().split(' s/d ');
        $.post(BASE + 'retur/get-daftar-retur-penjualan', {
            ids_saja: '1', start_date: v[0] || '', end_date: v[1] || '', status: statusAktif,
            jenis: jenisAktif, f_kurir: pilihJson('kurir'), f_mp: pilihJson('mp'), f_toko: pilihJson('toko'), search: { value: tabel.search() }
        }, function (res) {
            (res.resi || []).forEach(function (r) { sel[r] = true; });
            $('#dt_daftar_retur .dr-pilih').each(function () { this.checked = !!sel[$(this).attr('data-resi')]; });
            ubahInfoPilih();
        }, 'json');
    });

    // Cetak: POST lewat form ke tab baru (daftar resi bisa panjang, tidak muat di URL).
    $('#dr_cetak').on('click', function () {
        if (!jmlPilih()) return;
        var ulang = (statusAktif === 'SUDAH_SERAH');
        if (ulang && !window.confirm('Cetak ulang serah terima ' + jmlPilih() + ' resi? Ini akan tercatat sebagai cetak ulang.')) return;
        var v = $('#dr_rentang').val().split(' s/d ');
        // Kelas nojs wajib: tanpa itu plugins.js membajak submit jadi AJAX ke halaman SPA ("Invalid response format").
        var $f = $('<form method="post" target="_blank" class="nojs" style="display:none;"></form>')
            .attr('action', BASE + 'retur/serah-cetak-daftar-retur-penjualan');
        var kirim = { start_date: v[0] || '', end_date: v[1] || '', jenis: jenisAktif, q: tabel.search(), resi: Object.keys(sel).join(','), ulang: ulang ? '1' : '0' };
        $.each(kirim, function (k, val) { $('<input type="hidden">').attr('name', k).val(val).appendTo($f); });
        $f.appendTo('body').submit().remove();
        // Serah terima tercatat di server saat kertas dibuat: muat ulang sebentar lagi supaya resi yang dicetak
        // pindah ke tab Sudah Serah Terima dan centang dibersihkan.
        setTimeout(function () { sel = {}; tabel.ajax.reload(); }, 2000);
    });

    $('#dr_export').on('click', function () {
        var v = $('#dr_rentang').val().split(' s/d ');
        var q = $.param({ start_date: v[0] || '', end_date: v[1] || '', status: statusAktif, jenis: jenisAktif, status_retur: statusRetAktif, f_kurir: pilihJson('kurir'), f_mp: pilihJson('mp'), f_toko: pilihJson('toko'), q: tabel.search() });
        window.location.href = BASE + 'retur/export-daftar-retur-penjualan?' + q;
    });

    $('#dr_reset').on('click', function () {
        var r = rentangBawaan();
        $('#dr_rentang').data('daterangepicker').setStartDate(r[0]);
        $('#dr_rentang').data('daterangepicker').setEndDate(r[1]);
        tulisRentang(r[0], r[1]);
        statusAktif = '';
        aturModePilih(false);
        aturModeTanggal('');
        sel = {};
        jenisAktif = 'B,K';
        $('#dr_jenis_pop input').prop('checked', true); tulisTombolJenis(); $('#dr_jenis').removeClass('buka');
        statusRetAktif = STATUS_SEMUA;
        $('#dr_status_pop input').prop('checked', true); tulisTombolStatus(); $('#dr_status').removeClass('buka');
        $('#dr_tabs .dr-tab').removeClass('on').first().addClass('on');
        Object.keys(FIL).forEach(function (k) { FIL[k].pilih = {}; FIL[k].terapan = ''; tulisTombolF(k); });
        segarkanBerikut = true;            // atur ulang sekaligus ambil data terbaru
        tabel.search('').order([8, 'desc']).ajax.reload();
    });
});
</script>
