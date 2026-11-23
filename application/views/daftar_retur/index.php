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
.dr-chip > i, .dr-reset i, .dr-export i { flex:none; width:14px; text-align:center; font-size:13px; line-height:1; color:#6b7280; }
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
.dr-reset:focus-visible, .dr-export:focus-visible { outline:0; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.25); }

/* Filter: satu tombol "Filter" membuka panel dua kolom (kategori di kiri, pilihan di kanan). Filter baru cukup ditambah ke daftar KAT di JS;
   tampilan panel, hitungan, dan chip filter aktif mengikuti otomatis. Pilihan baru berlaku setelah "Terapkan" (satu kali muat data). */
#dr_fp { position:relative; }
.dr-fbtn { box-sizing:border-box; display:inline-flex; align-items:center; gap:8px; height:38px; margin:0; padding:0 14px; background:#fff; border:1px solid #d1d5db; border-radius:6px;
           color:#111827; font-size:13px; font-weight:600; line-height:1; cursor:pointer; }
.dr-fbtn i { flex:none; width:14px; text-align:center; font-size:13px; color:#6b7280; }
.dr-fbtn:hover, #dr_fp.buka .dr-fbtn { border-color:#0f766e; }
.dr-fbtn:hover i, #dr_fp.buka .dr-fbtn i { color:#0f766e; }
.dr-fbtn:focus-visible { outline:0; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.25); }
.dr-fbtn .cnt { background:#0f766e; color:#fff; border-radius:999px; font-size:11px; font-weight:700; padding:0 7px; line-height:18px; }
.dr-fbtn .cnt.nol { display:none; }
.dr-fpanel { display:none; position:absolute; left:0; top:44px; z-index:60; width:580px; max-width:calc(100vw - 32px); background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 12px 32px rgba(0,0,0,.14); overflow:hidden; }
#dr_fp.buka .dr-fpanel { display:block; }
.dr-fp-body { display:flex; height:320px; }
.dr-fp-nav { flex:none; width:176px; padding:8px; background:#f8fafc; border-right:1px solid #e5e7eb; overflow-y:auto; }
.dr-fp-item { display:flex; align-items:center; gap:9px; padding:9px 10px; margin-bottom:2px; border-radius:6px; cursor:pointer; color:#374151; font-size:13px; font-weight:600; user-select:none; }
.dr-fp-item:hover { background:#eef2f7; }
.dr-fp-item.on { background:#fff; color:#0f766e; box-shadow:0 0 0 1px #ccfbf1; }
.dr-fp-item i.fa { flex:none; width:14px; text-align:center; color:#9ca3af; }
.dr-fp-item.on i.fa { color:#0f766e; }
.dr-fp-item .n { margin-left:auto; background:#0f766e; color:#fff; border-radius:999px; font-size:11px; font-weight:700; padding:0 7px; line-height:18px; }
.dr-fp-pane { flex:1; min-width:0; display:flex; flex-direction:column; padding:12px 14px 8px; }
.dr-fp-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; font-size:13.5px; font-weight:700; color:#111827; }
.dr-fp-head a, .dr-fp-kaki a { cursor:pointer; color:#0f766e; font-size:12px; font-weight:600; }
.dr-fp-ket { margin:-2px 0 4px; color:#9ca3af; font-size:11.5px; }
.dr-fp-list { flex:1; min-height:0; overflow-y:auto; }
.dr-fp-info { margin-bottom:6px; padding:8px 10px; background:#fffbeb; color:#92400e; border-radius:6px; font-size:12px; line-height:1.45; }
.dr-fp-kaki { display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border-top:1px solid #e5e7eb; background:#fff; }
.dr-fok { height:34px; padding:0 18px; border:0; border-radius:6px; background:#0f766e; color:#fff; font-size:13px; font-weight:600; cursor:pointer; }
.dr-fok:hover { background:#115e59; }
.dr-fpanel .dr-cari-opsi { box-sizing:border-box; width:100%; height:34px; margin:0 0 4px; padding:0 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; }
.dr-fpanel .dr-cari-opsi:focus { outline:0; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.15); }
.dr-fpanel .dr-kosong { padding:10px; color:#9ca3af; font-size:12.5px; }
.dr-opsi { display:flex; align-items:center; gap:10px; padding:9px 10px; border-radius:6px; cursor:pointer; margin:0; font-weight:400; }
.dr-opsi:hover { background:#f3f4f6; }
.dr-opsi input { position:absolute; opacity:0; pointer-events:none; }
.dr-kotak { flex:none; width:18px; height:18px; border:1.5px solid #9ca3af; border-radius:4px; background:#fff;
            display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; transition:all .12s; }
.dr-kotak i { opacity:0; }
.dr-opsi input:checked + .dr-kotak { background:#0f766e; border-color:#0f766e; }
.dr-opsi input:checked + .dr-kotak i { opacity:1; }
.dr-opsi input:disabled + .dr-kotak { opacity:.45; }
.dr-opsi input:focus-visible + .dr-kotak { box-shadow:0 0 0 3px rgba(15,118,110,.25); }
.dr-opsi .teks { flex:1; min-width:0; color:#111827; overflow-wrap:anywhere; }
.dr-opsi .ket { display:block; color:#9ca3af; font-size:11.5px; }
/* Chip filter yang sedang berlaku (satu baris di bawah bar; hilang bila tidak ada filter) */
.dr-aktif { display:none; flex-wrap:wrap; gap:6px 8px; padding:0 0 8px; }
.dr-aktif.ada { display:flex; }
.dr-achip { display:inline-flex; align-items:center; gap:6px; height:26px; padding:0 4px 0 10px; border-radius:999px; background:#f0fdfa; border:1px solid #99f6e4; color:#134e4a; font-size:12px; }
.dr-achip .v { max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dr-achip button { width:18px; height:18px; padding:0; border:0; border-radius:50%; background:none; color:#0f766e; font-size:11px; line-height:18px; cursor:pointer; }
.dr-achip button:hover { background:#ccfbf1; }
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
#dr_wrap table.dataTable thead th:nth-child(9)  { width:100px; }
#dr_wrap table.dataTable thead th:nth-child(10) { width:128px; }
#dr_wrap[data-tab="SUDAH_SERAH"] table.dataTable thead th:nth-child(10) { width:152px; }
#dr_wrap[data-tab="BELUM_DIBUKA"] table.dataTable thead th:nth-child(10) { width:116px; }
#dr_wrap[data-tab="SUDAH_DIBUKA"] table.dataTable thead th:nth-child(10) { width:108px; }
#dr_wrap table.dataTable thead th:nth-child(11) { width:160px; }
@media (min-width: 1680px) {
  #dr_wrap table.dataTable thead th:nth-child(5)  { width:190px; }
  #dr_wrap table.dataTable thead th:nth-child(6)  { width:185px; }
  #dr_wrap table.dataTable thead th:nth-child(8)  { width:100px; }
  #dr_wrap table.dataTable thead th:nth-child(9)  { width:104px; }
  #dr_wrap table.dataTable thead th:nth-child(10) { width:150px; }
  #dr_wrap table.dataTable thead th:nth-child(11) { width:180px; }
}
@media (min-width: 1900px) {
  #dr_wrap table.dataTable thead th:nth-child(5)  { width:220px; }
  #dr_wrap table.dataTable thead th:nth-child(6)  { width:215px; }
  #dr_wrap table.dataTable thead th:nth-child(8)  { width:110px; }
  #dr_wrap table.dataTable thead th:nth-child(9)  { width:110px; }
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
  #dr_wrap[data-tab="SUDAH_SERAH"] table.dataTable thead th:nth-child(4) { width:150px; }
  #dr_wrap table.dataTable thead th:nth-child(5)  { width:160px; }
  #dr_wrap table.dataTable thead th:nth-child(6)  { width:164px; }
  #dr_wrap table.dataTable thead th:nth-child(8)  { width:78px; }
  #dr_wrap table.dataTable thead th:nth-child(9)  { width:98px; }
  #dr_wrap table.dataTable thead th:nth-child(11) { width:150px; }
}
/* Layar sempit (<= 1300: laptop kecil, tablet): tabel boleh digeser mendatar bila tidak muat; lebar minimal menjamin kolom Toko tetap terbaca.
   Judul kolom tidak lagi menempel (sticky tidak bekerja di dalam wadah yang bisa digeser). */
@media (max-width: 1300px) {
  #dr_tablewrap { overflow-x:auto; }
  #dr_wrap table.dataTable { min-width:980px; }
  #dr_wrap[data-tab="SUDAH_DIBUKA"] table.dataTable { min-width:1080px; }
  #dr_wrap[data-tab="SUDAH_SERAH"] table.dataTable { min-width:1130px; }
  #dr_wrap table.dataTable thead th { position:static; }
}
/* Tablet / ponsel: filter memenuhi lebar, panel pilihan tidak keluar layar, paging menumpuk */
@media (max-width: 768px) {
  #dr_wrap { padding:0 10px 10px; }
  .dr-tabs { gap:0 10px; }
  .dr-filter .dataTables_filter { flex:1 1 100%; max-width:none; order:20; }
  .dr-acts { margin-left:0; flex:1 1 100%; order:21; }
  .dr-fpanel { position:fixed; left:16px; right:16px; top:15%; width:auto; max-width:none; }
  .dr-fp-body { flex-direction:column; height:auto; max-height:56vh; }
  .dr-fp-nav { display:flex; width:auto; overflow-x:auto; overflow-y:hidden; border-right:0; border-bottom:1px solid #e5e7eb; }
  .dr-fp-item { flex:none; margin:0 4px 0 0; }
  .dr-fp-pane { min-height:0; overflow:hidden; }
  #dr_wrap .dr-bawah, #dr_wrap .dr-kanan { flex-wrap:wrap; justify-content:flex-start; margin-left:0; }
  #dr_wrap .dataTables_paginate { flex-wrap:wrap; }
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

/* ===== Drawer detail resi (klik baris). Pola UI sama dengan drawer Manajemen Komplain CS New ===== */
#dt_daftar_retur tbody tr { cursor:pointer; }
#dr_drawer { overflow:hidden !important; }
.modal-dialog.dd-dialog { position:fixed; top:0; right:0; bottom:0; margin:0 !important; height:100vh; width:min(760px, 96vw); max-width:none; }
.modal.fade .dd-dialog { transform:translateX(100%); transition:transform .28s ease-out; }
.modal.in .dd-dialog { transform:none; }
.dd-content { height:100%; display:flex; flex-direction:column; border-radius:16px 0 0 16px; overflow:hidden; font-family:'Inter','Segoe UI',sans-serif; color:#1e293b; box-shadow:-16px 0 48px rgba(15,23,42,.22); }
.dd-header { flex:none; padding:16px 24px 12px; border-bottom:1px solid #e5e7eb; position:relative; }
.dd-header .close { position:absolute; right:18px; top:14px; }
.dd-titlebox { display:flex; align-items:center; gap:12px; padding-right:30px; }
.dd-icon { display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px; border-radius:10px; background:#ccfbf1; color:#0f766e; font-size:17px; flex:none; }
.dd-titlebox h4 { margin:0; font-size:16px; font-weight:700; color:#0f172a; line-height:1.4; }
.dd-meta { margin-top:3px; font-size:12.5px; color:#6b7280; }
.dd-body { flex:1 1 auto; min-height:0; overflow-y:auto; padding:18px 24px 8px; }
.dd-sec { margin-bottom:22px; }
.dd-sec h5 { margin:0 0 6px; font-size:14px; font-weight:700; color:#0f172a; }
.dd-sec h5 small { font-weight:400; color:#6b7280; margin-left:6px; }
.dd-row { display:flex; gap:12px; align-items:flex-start; padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:13.5px; }
.dd-row > i.fa { flex:none; width:18px; margin-top:2px; text-align:center; color:#94a3b8; }
.dd-row dt { flex:0 0 130px; margin:0; font-weight:400; color:#6b7280; }
.dd-row dd { flex:1; margin:0; font-weight:600; color:#0f172a; word-break:break-word; }
.dd-row dd small { font-weight:400; color:#6b7280; }
.dd-kosong { color:#9ca3af; font-weight:400; }
.dd-copy { margin-left:8px; border:0; background:none; color:#94a3b8; cursor:pointer; padding:0 2px; }
.dd-copy:hover { color:#0f766e; }
.dd-copy.ok { color:#16a34a; }
.dd-pill { display:inline-block; padding:0 9px; border-radius:999px; font-size:11.5px; font-weight:700; line-height:20px; white-space:nowrap; background:#f3f4f6; color:#4b5563; }
.dd-p-hijau { background:#dcfce7; color:#166534; }
.dd-p-merah { background:#fee2e2; color:#b91c1c; }
.dd-p-kuning { background:#fef3c7; color:#b45309; }
.dd-p-biru { background:#dbeafe; color:#1d4ed8; }
.dd-p-ungu { background:#ede9fe; color:#6d28d9; }
.dd-p-teal { background:#ccfbf1; color:#0f766e; }
.dd-p-abu { background:#f3f4f6; color:#4b5563; }
/* Ringkasan hasil buka retur: kartu kecil per status */
.dd-rekap { display:flex; flex-wrap:wrap; gap:8px; margin:4px 0 2px; }
.dd-rekap > div { flex:1 1 130px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; background:#f8fafc; }
.dd-rekap b { display:block; font-size:20px; line-height:1.2; color:#0f172a; font-variant-numeric:tabular-nums; }
.dd-rekap span { font-size:11.5px; }
.dd-table { width:100%; margin-top:6px; border:1px solid #e5e7eb; border-radius:10px; border-collapse:separate; border-spacing:0; overflow:hidden; font-size:13px; }
.dd-table th { padding:8px 12px; background:#f8fafc; font-size:11px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:#6b7280; text-align:left; }
.dd-table td { padding:9px 12px; border-top:1px solid #f1f5f9; vertical-align:top; }
.dd-table .num { text-align:right; font-variant-numeric:tabular-nums; }
.dd-table tr.dd-total td { font-weight:700; background:#f8fafc; }
.dd-sku { display:flex; gap:10px; align-items:flex-start; }
.dd-sku .dd-foto { flex:none; width:40px; height:40px; object-fit:cover; border-radius:6px; border:1px solid #e5e7eb; background:#f8fafc; }
.dd-sku b { display:block; color:#0f172a; }
.dd-sku small { display:block; color:#6b7280; line-height:1.35; }
.dd-ket { display:block; margin-top:3px; font-size:11.5px; color:#6b7280; line-height:1.4; }
.dd-ket em { color:#b45309; font-style:normal; }
/* Alur tahap: garis vertikal dengan titik */
.dd-alur { list-style:none; margin:6px 0 0; padding:0; }
.dd-alur li { position:relative; padding:0 0 14px 28px; }
.dd-alur li:before { content:''; position:absolute; left:8px; top:18px; bottom:-2px; width:2px; background:#e5e7eb; }
.dd-alur li:last-child { padding-bottom:0; }
.dd-alur li:last-child:before { display:none; }
.dd-alur .t { position:absolute; left:0; top:1px; width:18px; height:18px; border-radius:50%; background:#e5e7eb; color:#fff; font-size:10px; display:flex; align-items:center; justify-content:center; }
.dd-alur li.ok .t { background:#0f766e; }
.dd-alur li.info .t { background:#94a3b8; }
.dd-alur .n { font-size:13.5px; font-weight:600; color:#0f172a; }
.dd-alur li.belum .n { color:#9ca3af; font-weight:400; }
.dd-alur .w { font-size:12.5px; color:#4b5563; font-variant-numeric:tabular-nums; }
.dd-alur .w small { color:#6b7280; }
.dd-alur .x { font-size:11.5px; color:#b45309; }
.dd-footer { flex:none; padding:12px 24px; border-top:1px solid #e5e7eb; text-align:right; background:#fff; }
.dd-muat { padding:40px 0; text-align:center; color:#6b7280; }
@media (max-width:767px) { .modal-dialog.dd-dialog { width:100vw; } .dd-content { border-radius:0; } .dd-header, .dd-body, .dd-footer { padding-left:16px; padding-right:16px; } .dd-row dt { flex-basis:104px; } }
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
    <!-- Satu tombol Filter: panel dua kolom (kategori | pilihan) digambar JS dari daftar KAT -->
    <div class="dr-fp" id="dr_fp">
      <button type="button" class="dr-fbtn" id="dr_fbtn" aria-haspopup="true" title="Filter jenis retur, status Jubelio, kurir, marketplace, toko"><i class="fa fa-sliders"></i> Filter <span class="cnt nol">0</span></button>
      <div class="dr-fpanel">
        <div class="dr-fp-body"><div class="dr-fp-nav" id="dr_fp_nav"></div><div class="dr-fp-pane" id="dr_fp_pane"></div></div>
        <div class="dr-fp-kaki"><a id="dr_fp_reset">Atur ulang pilihan</a><button type="button" class="dr-fok" id="dr_fp_ok">Terapkan</button></div>
      </div>
    </div>
    <button type="button" class="dr-reset" id="dr_reset" title="Atur ulang filter"><i class="fa fa-undo"></i></button>
    <!-- kotak cari DataTables disisipkan JS di sini -->
    <div class="dr-acts">
      <button type="button" class="dr-export" id="dr_export" title="Unduh Excel sesuai filter, tab, dan pencarian saat ini"><i class="fa fa-file-excel-o"></i> Export</button>
      <button type="button" class="dr-export dr-cetak" id="dr_cetak" style="display:none;" disabled title="Centang resi yang mau dicetak dulu"><i class="fa fa-print"></i> Cetak Serah Terima</button>
    </div>
  </div>
  <!-- Chip filter yang sedang berlaku (diisi JS; tiap chip bisa dihapus dengan x) -->
  <div class="dr-aktif" id="dr_aktif"></div>
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

<!-- Drawer detail resi: terbuka saat baris tabel diklik (semua tab). Hanya lihat. -->
<div class="modal fade" id="dr_drawer" role="dialog" aria-labelledby="dr_drawer_judul" aria-hidden="true">
  <div class="modal-dialog dd-dialog" role="document">
    <div class="modal-content dd-content">
      <div class="dd-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        <div class="dd-titlebox">
          <span class="dd-icon"><i class="fa fa-undo"></i></span>
          <div>
            <h4 id="dr_drawer_judul">Detail Retur</h4>
            <div class="dd-meta" id="dr_drawer_meta"></div>
          </div>
        </div>
      </div>
      <div class="dd-body" id="dr_drawer_isi"></div>
      <div class="dd-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
    </div>
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
        tulisUIFilter();   // kategori Jenis / Status Jubelio mengikuti tab (lihat katTampil / katNonaktif)
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


    // ---------------- Filter: satu tombol + panel dua kolom ----------------
    // Menambah filter baru = tambah satu entri di KAT (tipe 'semua' = daftar tetap, semua tercentang secara bawaan; tipe 'dinamis' = pilihan
    // diambil dari data server (json.opsi), kosong = semua). Untuk filter dinamis juga daftarkan kuncinya di FIL dan di ajax.data + export.
    var KAT = [
        { k: 'jenis', t: 'Jenis retur', i: 'fa-exchange', tipe: 'semua', opsi: [['B', 'Ekspedisi', 'Retur ekspedisi (gagal kirim)'], ['K', 'Komplain', 'Pengembalian barang dari customer']] },
        { k: 'status', t: 'Status Jubelio', i: 'fa-tag', tipe: 'semua', opsi: [['SELESAI', 'SELESAI', 'Retur selesai diproses di Jubelio'], ['DITOLAK', 'DITOLAK', 'Pengajuan retur ditolak'], ['BARANG RETUR', 'BARANG RETUR', 'Retur ekspedisi (paket kembali ke toko)']] },
        { k: 'kurir', t: 'Kurir', i: 'fa-truck', tipe: 'dinamis', cari: 'Cari kurir...' },
        { k: 'mp', t: 'Marketplace', i: 'fa-shopping-bag', tipe: 'dinamis', cari: 'Cari marketplace...' },
        { k: 'toko', t: 'Toko', i: 'fa-home', tipe: 'dinamis', cari: 'Cari toko...' }
    ];
    var draf = {}, katAktif = 'jenis', panelBuka = false, cariOpsi = '';
    function attr(t) { return esc(t).replace(/"/g, '&quot;'); }
    function katCari(k) { return KAT.filter(function (x) { return x.k === k; })[0]; }
    function katTampil(x) { return x.k !== 'status' || statusAktif === '' || statusAktif === 'SELESAI'; }   // status Jubelio hanya relevan di tab Semua / Selesai
    function katNonaktif(x) { return x.k === 'jenis' && (statusAktif === 'BELUM_DITERIMA' || statusAktif === 'BELUM_HANGUS'); }   // jenis baru diketahui setelah scan terima
    function jumlah(m) { return Object.keys(m).length; }
    function setDari(str) { var m = {}; (str === '-' ? [] : str.split(',')).forEach(function (v) { if (v) m[v] = true; }); return m; }
    function terapanSet(x) { return x.k === 'jenis' ? setDari(jenisAktif) : (x.k === 'status' ? setDari(statusRetAktif) : $.extend({}, FIL[x.k].pilih)); }
    function bawaan(x) { var m = {}; if (x.tipe === 'semua') x.opsi.forEach(function (o) { m[o[0]] = true; }); return m; }
    function nondefault(x, m) { return x.tipe === 'semua' ? jumlah(m) !== x.opsi.length : jumlah(m) > 0; }
    function ringkasSet(x, m) {
        var a = Object.keys(m);
        if (x.tipe === 'semua') return x.opsi.filter(function (o) { return m[o[0]]; }).map(function (o) { return o[1]; }).join(', ') || 'Tidak ada';
        return a.length > 2 ? a.slice(0, 2).join(', ') + ' +' + (a.length - 2) : a.join(', ');
    }
    // Tombol Filter (angka) + chip filter yang SUDAH diterapkan.
    function tulisUIFilter() {
        if (!KAT) return;
        var n = 0, h = '';
        KAT.forEach(function (x) {
            if (!katTampil(x) || katNonaktif(x)) return;
            var m = terapanSet(x);
            if (!nondefault(x, m)) return;
            n++;
            h += '<span class="dr-achip"><b>' + esc(x.t) + ':</b><span class="v" title="' + attr(Object.keys(m).join(', ')) + '">' + esc(ringkasSet(x, m)) + '</span>' +
                '<button type="button" data-k="' + x.k + '" title="Hapus filter ini"><i class="fa fa-times"></i></button></span>';
        });
        $('#dr_fbtn .cnt').text(n).toggleClass('nol', n === 0);
        $('#dr_aktif').html(h).toggleClass('ada', n > 0);
    }
    function gambarNav() {
        var h = '';
        KAT.forEach(function (x) {
            if (!katTampil(x)) return;
            var m = draf[x.k];
            h += '<div class="dr-fp-item' + (x.k === katAktif ? ' on' : '') + '" data-k="' + x.k + '"><i class="fa ' + x.i + '"></i>' + esc(x.t) +
                (nondefault(x, m) ? '<span class="n">' + jumlah(m) + '</span>' : '') + '</div>';
        });
        $('#dr_fp_nav').html(h);
    }
    function gambarPane() {
        var x = katCari(katAktif), h = '';
        h += '<div class="dr-fp-head"><span>' + esc(x.t) + '</span><a id="dr_fp_reset_kat">' + (x.tipe === 'semua' ? 'Pilih semua' : 'Hapus pilihan') + '</a></div>';
        if (katNonaktif(x)) h += '<div class="dr-fp-info">Jenis retur baru diketahui setelah scan terima, jadi filter ini tidak berlaku di tab Belum Terima.</div>';
        if (x.tipe === 'dinamis') h += '<input type="text" class="dr-cari-opsi" placeholder="' + attr(x.cari) + '" autocomplete="off" value="' + attr(cariOpsi) + '"><div class="dr-fp-ket">Kosong = semua</div>';
        h += '<div class="dr-fp-list"></div>';
        $('#dr_fp_pane').html(h);
        gambarDaftar();
    }
    function gambarDaftar() {
        var x = katCari(katAktif), m = draf[x.k], q = cariOpsi.toLowerCase(), h = '';
        var opsi = x.tipe === 'semua' ? x.opsi : FIL[x.k].opsi.map(function (v) { return [v, v === '' ? '(kosong)' : v, '']; });
        opsi.forEach(function (o) {
            if (q && o[1].toLowerCase().indexOf(q) < 0) return;
            h += '<label class="dr-opsi"><input type="checkbox" value="' + attr(o[0]) + '"' + (m[o[0]] ? ' checked' : '') + (katNonaktif(x) ? ' disabled' : '') +
                '><span class="dr-kotak"><i class="fa fa-check"></i></span><span class="teks">' + esc(o[1]) + (o[2] ? '<span class="ket">' + esc(o[2]) + '</span>' : '') + '</span></label>';
        });
        $('#dr_fp_pane .dr-fp-list').html(h || '<div class="dr-kosong">' + (opsi.length ? 'Tidak ada yang cocok' : 'Belum ada data') + '</div>');
    }
    function bukaPanel() {
        KAT.forEach(function (x) { draf[x.k] = terapanSet(x); });
        cariOpsi = '';
        if (!katTampil(katCari(katAktif))) katAktif = 'jenis';
        panelBuka = true;
        $('#dr_fp').addClass('buka');
        gambarNav(); gambarPane();
    }
    function tutupPanel() {
        if (!panelBuka) return;
        panelBuka = false;
        $('#dr_fp').removeClass('buka');
    }
    // Terapkan draf ke filter berlaku; muat ulang data sekali saja, hanya bila ada yang berubah.
    function terapkan() {
        var urut = function (x) { return x.opsi.map(function (o) { return o[0]; }).filter(function (v) { return draf[x.k][v]; }).join(',') || '-'; };
        var j = urut(katCari('jenis')), s = urut(katCari('status')), muat = false, ganti = false;
        if (j !== jenisAktif) { jenisAktif = j; muat = true; }
        if (s !== statusRetAktif) { statusRetAktif = s; muat = true; }
        Object.keys(FIL).forEach(function (k) {
            FIL[k].pilih = $.extend({}, draf[k]);
            var b = pilihJson(k);
            if (b !== FIL[k].terapan) { FIL[k].terapan = b; muat = true; ganti = true; }
        });
        tutupPanel();
        tulisUIFilter();
        if (ganti) sel = {};
        if (muat) tabel.ajax.reload();
    }

    $('#dr_fp').on('click', function (e) { e.stopPropagation(); });
    $('#dr_fbtn').on('click', function () { if (panelBuka) tutupPanel(); else bukaPanel(); });
    $(document).on('click.drfp', tutupPanel).on('keydown.drfp', function (e) { if (e.key === 'Escape') tutupPanel(); });
    $('#dr_fp_nav').on('click', '.dr-fp-item', function () { katAktif = $(this).attr('data-k'); cariOpsi = ''; gambarNav(); gambarPane(); });
    $('#dr_fp_pane').on('change', 'input[type=checkbox]', function () {
        if (this.checked) draf[katAktif][this.value] = true; else delete draf[katAktif][this.value];
        gambarNav();
    });
    $('#dr_fp_pane').on('input', '.dr-cari-opsi', function () { cariOpsi = $.trim($(this).val()); gambarDaftar(); });
    $('#dr_fp_pane').on('click', '#dr_fp_reset_kat', function () { draf[katAktif] = bawaan(katCari(katAktif)); gambarNav(); gambarDaftar(); });
    $('#dr_fp_reset').on('click', function () { KAT.forEach(function (x) { draf[x.k] = bawaan(x); }); gambarNav(); gambarDaftar(); });
    $('#dr_fp_ok').on('click', terapkan);
    $('#dr_aktif').on('click', 'button', function () {   // hapus satu filter dari chip, langsung diterapkan
        var k = $(this).attr('data-k');
        KAT.forEach(function (x) { draf[x.k] = terapanSet(x); });
        draf[k] = bawaan(katCari(k));
        terapkan();
    });

    // Pilihan Kurir / MP / Toko diambil dari data (respons server: json.opsi) dan digabung, tidak pernah dibuang,
    // supaya pilihan yang sudah dicentang tidak hilang saat tab/filter lain berganti. Tidak ada yang dicentang = semua.
    function pilihJson(k) {
        var a = Object.keys(FIL[k].pilih).sort();
        return a.length ? JSON.stringify(a) : '';
    }
    function gambarOpsi(k) { if (panelBuka && katAktif === k) gambarDaftar(); }
    function gabungOpsi(o) {
        if (!o) return;
        Object.keys(FIL).forEach(function (k) {
            var m = {};
            FIL[k].opsi.concat(o[k] || []).forEach(function (v) { m[v] = true; });
            var baru = Object.keys(m).sort(function (a, b) { return a.toLowerCase() < b.toLowerCase() ? -1 : (a.toLowerCase() > b.toLowerCase() ? 1 : 0); });
            if (baru.length !== FIL[k].opsi.length) { FIL[k].opsi = baru; gambarOpsi(k); }
        });
    }

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
        statusRetAktif = STATUS_SEMUA;
        Object.keys(FIL).forEach(function (k) { FIL[k].pilih = {}; FIL[k].terapan = ''; });
        tutupPanel(); tulisUIFilter();
        segarkanBerikut = true;            // atur ulang sekaligus ambil data terbaru
        tabel.search('').order([8, 'desc']).ajax.reload();
    });

    // ---------------- Drawer detail resi (klik baris) ----------------
    var LABEL_HASIL = {
        KE_DISPLAY: 'KE DISPLAY', REJECT: 'REJECT', REFUND: 'REFUND', PENUKARAN_BERES: 'PENDINGAN BERES (PB)',
        REQUEST_DARI_PEMBELI: 'REQUEST DARI PEMBELI', KURANG: 'KURANG KIRIM', BUKAN_BARANG_KITA: 'BUKAN BARANG KITA',
        DIPAKAI_ADMIN: 'DIPAKAI ADMIN', BARANG_TIDAK_ADA: 'BARANG TIDAK ADA', BUKAN_RETUR: 'BUKAN RETUR',
        ALASAN_LAINNYA: 'ALASAN LAINNYA', PAKET_HILANG: 'PAKET HILANG'
    };
    var NADA_HASIL = {
        KE_DISPLAY: 'hijau', BUKAN_RETUR: 'biru', REJECT: 'merah', BARANG_TIDAK_ADA: 'merah', PAKET_HILANG: 'merah',
        BUKAN_BARANG_KITA: 'kuning', KURANG: 'kuning', DIPAKAI_ADMIN: 'ungu', PENUKARAN_BERES: 'teal', REFUND: 'teal'
    };
    var NADA_TAHAP = ['abu', 'biru', 'teal', 'ungu', 'hijau'];
    var NADA_JUB = { 'SELESAI': 'hijau', 'DITOLAK': 'merah', 'BARANG RETUR': 'kuning' };
    var NAMA_JENIS = { B: 'Retur ekspedisi', K: 'Retur komplain' };
    var reqDetail = null;

    function ddWaktu(t) { return t ? moment(t).format('DD MMM YYYY HH:mm:ss') : null; }
    function ddRp(n) { return 'Rp ' + (parseFloat(n) || 0).toLocaleString('id-ID'); }
    function ddPil(teks, nada) { return '<span class="dd-pill dd-p-' + (nada || 'abu') + '">' + esc(teks) + '</span>'; }
    function ddHasil(st) { return ddPil(LABEL_HASIL[st] || st || '-', NADA_HASIL[st] || 'abu'); }
    function ddRow(ikon, label, nilai, salin) {
        var kosong = (nilai === null || nilai === undefined || nilai === '');
        return '<div class="dd-row"><i class="fa ' + ikon + '"></i><dt>' + esc(label) + '</dt><dd>' +
            (kosong ? '<span class="dd-kosong">-</span>' : esc(nilai) + (salin ? '<button type="button" class="dd-copy" data-copy="' + esc(nilai) + '" title="Salin"><i class="fa fa-files-o"></i></button>' : '')) +
            '</dd></div>';
    }
    function ddSku(sku, nama, rak, foto) {
        return '<div class="dd-sku">' + (foto || '') + '<div><b>' + esc(sku || '-') + '</b>' +
            (nama ? '<small>' + esc(nama) + '</small>' : '') + (rak ? '<small>Rak: ' + esc(rak) + '</small>' : '') + '</div></div>';
    }
    // satu langkah alur: ok = ada catatan, isi = baris waktu/petugas (HTML)
    function ddLangkah(nama, ok, isi, kelas) {
        return '<li class="' + (kelas || (ok ? 'ok' : 'belum')) + '"><span class="t">' + (ok && kelas !== 'info' ? '<i class="fa fa-check"></i>' : '') + '</span><div class="n">' + esc(nama) + '</div>' + (isi || '') + '</li>';
    }
    function ddWP(waktu, petugas, ekstra) {
        return '<div class="w">' + esc(ddWaktu(waktu) || '-') + (petugas ? ' · ' + esc(petugas) : ' · <small>petugas tidak tercatat</small>') + (ekstra ? ' ' + ekstra : '') + '</div>';
    }

    function bangunDetail(d) {
        var h = '', tg = d.tanggal || {}, buka = d.hasil_buka || [], jub = d.barang_jubelio || [];

        // --- Ringkasan hasil buka retur (qty per status)
        var qtyBuka = 0;
        buka.forEach(function (x) { qtyBuka += x.qty; });
        h += '<div class="dd-sec"><h5>Ringkasan barang <small>' + (buka.length ? qtyBuka.toLocaleString('id-ID') + ' pcs sudah dibuka' : 'belum dibuka') + '</small></h5>';
        if (buka.length) {
            h += '<div class="dd-rekap">';
            Object.keys(d.rekap_status).forEach(function (st) {
                h += '<div>' + ddHasil(st) + '<b>' + parseInt(d.rekap_status[st], 10).toLocaleString('id-ID') + ' pcs</b></div>';
            });
            h += '</div>';
            if (d.qty_total > 0 && qtyBuka !== d.qty_total) {
                h += '<div class="dd-ket"><em>Qty dibuka (' + qtyBuka + ' pcs) berbeda dengan qty di Jubelio (' + d.qty_total + ' pcs).</em></div>';
            }
        } else {
            h += '<div class="dd-ket">Belum ada hasil Buka Retur untuk resi ini. Tujuan tiap SKU (KE DISPLAY, BUKAN RETUR, REJECT, dll) baru tercatat setelah paket dibuka.</div>';
        }
        h += '</div>';

        // --- Pesanan
        var stJ = (d.status_jubelio || []).map(function (s) { return ddPil(s, NADA_JUB[s]); }).join(' ');
        h += '<div class="dd-sec"><h5>Pesanan</h5>' +
            ddRow('fa-barcode', 'No. resi', d.resi, true) +
            ddRow('fa-hashtag', 'No. pesanan', d.no_pesanan, true) +
            ddRow('fa-shopping-bag', 'Marketplace', d.marketplace) +
            ddRow('fa-building-o', 'Toko', d.toko) +
            ddRow('fa-truck', 'Kurir', d.kurir) +
            '<div class="dd-row"><i class="fa fa-flag"></i><dt>Status Jubelio</dt><dd>' + (stJ || '<span class="dd-kosong">-</span>') + '</dd></div>' +
            ddRow('fa-info-circle', 'Status pesanan', d.status_pesanan) +
            ddRow('fa-calendar', 'Tanggal pesan', ddWaktu(tg.pesan)) +
            ddRow('fa-calendar-o', 'Tanggal kirim', ddWaktu(tg.kirim)) +
            ddRow('fa-calendar-check-o', 'Tanggal retur', ddWaktu(tg.retur || tg.retur_jubelio)) +
            ddRow('fa-calendar-check-o', 'Tanggal selesai', ddWaktu(tg.selesai)) +
            ddRow('fa-money', 'Tanggal pencairan', ddWaktu(tg.cair)) +
            ddRow('fa-cubes', 'Total barang', d.qty_total + ' pcs') +
            ddRow('fa-money', 'Nilai', ddRp(d.nilai_total)) + '</div>';

        // --- Alur progress
        var p = d.progress || {}, a = '<ul class="dd-alur">';
        a += ddLangkah('Diretur', !!(tg.retur || tg.retur_jubelio), ddWP(tg.retur || tg.retur_jubelio, null).replace(' · <small>petugas tidak tercatat</small>', ''), 'info');
        if ((d.scan_terima || []).length) {
            var it = '';
            d.scan_terima.forEach(function (x) { it += ddWP(x.waktu, x.petugas, ddPil(NAMA_JENIS[x.jenis] || x.jenis, x.jenis === 'K' ? 'ungu' : 'abu')); });
            a += ddLangkah('Sudah Terima', true, it);
        } else a += ddLangkah('Belum Terima', false, '<div class="w">Belum ada scan Terima Retur</div>');
        if (buka.length) {
            var bk = p.buka ? ddWP(p.buka.t, p.buka.p) : '';
            a += ddLangkah('Sudah Buka', true, bk + '<div class="w"><small>' + buka.length + ' baris SKU</small></div>');
        } else a += ddLangkah('Belum Buka', false, '<div class="w">Belum ada scan Buka Retur</div>');
        if ((d.serah || []).length) {
            var sr = '';
            d.serah.forEach(function (x) { sr += ddWP(x.waktu, x.petugas, ddPil(x.ulang ? 'Cetak ulang ke-' + x.cetak_ke : 'Cetak pertama', x.ulang ? 'kuning' : 'abu')); });
            a += ddLangkah('Sudah Serah Terima', true, sr);
        } else a += ddLangkah('Belum Serah Terima', false, '<div class="w">' + (buka.length ? 'Belum dicetak serah terima' : 'Menunggu Buka Retur') + '</div>');
        if ((d.verifikasi || []).length) {
            var vf = '';
            d.verifikasi.forEach(function (x) { vf += ddWP(x.waktu, x.petugas) + (x.catatan ? '<div class="x">' + esc(x.catatan) + '</div>' : ''); });
            a += ddLangkah('Sudah Verifikasi', true, vf);
        } else if (p.verif) {
            a += ddLangkah('Sudah Verifikasi', true, ddWP(p.verif.t, p.verif.p) + '<div class="x">Dari ACC Returan Buka (accounting)</div>');
        } else a += ddLangkah('Belum Verifikasi', false, '<div class="w">Belum diverifikasi accounting</div>');
        a += '</ul>';
        h += '<div class="dd-sec"><h5>Progress <small>' + esc(d.tahap_nama) + '</small></h5>' + a + '</div>';

        // --- Hasil buka retur per SKU
        h += '<div class="dd-sec"><h5>Hasil Buka Retur per SKU <small>' + buka.length + ' baris</small></h5>';
        if (buka.length) {
            h += '<table class="dd-table"><thead><tr><th>SKU</th><th class="num">Qty</th><th>Hasil</th><th>Keterangan</th></tr></thead><tbody>';
            buka.forEach(function (x) {
                var ket = [];
                if (x.pergantian) ket.push('Pergantian: <b>' + esc(x.pergantian) + '</b>' + (x.pergantian_nama ? ' (' + esc(x.pergantian_nama) + ')' : ''));
                if (x.alasan) ket.push('<em>Alasan: ' + esc(x.alasan) + '</em>');
                ket.push(esc(NAMA_JENIS[x.jenis] || x.jenis) + (x.sumber ? ' · ' + esc(x.sumber) : ''));
                ket.push('Dibuka ' + esc(ddWaktu(x.waktu) || '-') + (x.petugas ? ' oleh ' + esc(x.petugas) : ''));
                if (x.harga !== null) ket.push('Harga ' + ddRp(x.harga));
                if (x.status === 'KE_DISPLAY') ket.push(x.acc ? 'Stok sudah di-ACC accounting' + (x.acc_oleh ? ' (' + esc(x.acc_oleh) + ')' : '') : 'Belum di-ACC accounting');
                h += '<tr><td>' + ddSku(x.sku, x.nama, x.rak, x.foto) + '</td><td class="num"><b>' + x.qty + '</b></td><td>' + ddHasil(x.status) +
                    '</td><td>' + ket.map(function (k) { return '<span class="dd-ket">' + k + '</span>'; }).join('') + '</td></tr>';
            });
            h += '<tr class="dd-total"><td class="num">Total</td><td class="num">' + qtyBuka + '</td><td colspan="2"></td></tr></tbody></table>';
        } else {
            h += '<div class="dd-ket">Belum ada data.</div>';
        }
        h += '</div>';

        // --- Barang menurut Jubelio
        h += '<div class="dd-sec"><h5>Barang menurut Jubelio <small>' + jub.length + ' baris</small></h5>';
        if (jub.length) {
            h += '<table class="dd-table"><thead><tr><th>SKU</th><th class="num">Qty</th><th class="num">Nilai</th><th>Status</th></tr></thead><tbody>';
            jub.forEach(function (x) {
                h += '<tr><td>' + ddSku(x.sku, x.nama) + '</td><td class="num">' + x.qty + '</td><td class="num">' + ddRp(x.nilai) + '</td><td>' +
                    ddPil(x.status || '-', NADA_JUB[x.status]) + (x.status_jubelio ? '<span class="dd-ket">' + esc(x.status_jubelio) + '</span>' : '') + '</td></tr>';
            });
            h += '<tr class="dd-total"><td class="num">Total</td><td class="num">' + d.qty_total + '</td><td class="num">' + ddRp(d.nilai_total) + '</td><td></td></tr></tbody></table>';
        } else {
            h += '<div class="dd-ket">Tidak ada baris di data retur Jubelio.</div>';
        }
        h += '</div>';

        // --- Komplain CS
        if ((d.komplain || []).length) {
            h += '<div class="dd-sec"><h5>Komplain CS terkait <small>' + d.komplain.length + '</small></h5>';
            d.komplain.forEach(function (c) {
                h += ddRow('fa-comments-o', 'K-' + ('00000' + c.id_complain).slice(-5),
                    (c.kategori_komplain || '-') + ' · ' + (c.status_komplain || 'belum ada status') + (c.hasil_investigasi ? ' · CCTV: ' + c.hasil_investigasi : '') + (c.tgl_komplain ? ' · ' + c.tgl_komplain : ''));
            });
            h += '</div>';
        }
        return h;
    }

    function bukaDetail(resi) {
        if (reqDetail) reqDetail.abort();
        $('#dr_drawer_judul').text('Retur ' + resi);
        $('#dr_drawer_meta').text('Memuat detail...');
        $('#dr_drawer_isi').html('<div class="dd-muat"><i class="fa fa-spinner fa-spin"></i> Memuat detail...</div>').scrollTop(0);
        $('#dr_drawer').modal('show');
        reqDetail = $.ajax({ url: BASE + 'retur/detail-daftar-retur-penjualan', type: 'POST', dataType: 'json', data: { resi: resi }, timeout: 60000 })
            .done(function (resp) {
                if (!resp || !resp.success) {
                    $('#dr_drawer_meta').text('');
                    $('#dr_drawer_isi').html('<div class="dd-muat">' + esc((resp && resp.message) || 'Gagal mengambil detail.') + '</div>');
                    return;
                }
                var d = resp.data;
                $('#dr_drawer_judul').html('Retur ' + esc(d.resi) + ' ' + ddPil(d.tahap_nama, NADA_TAHAP[d.tahap]));
                $('#dr_drawer_meta').text([d.toko, d.marketplace, d.kurir].filter(Boolean).join(' · '));
                $('#dr_drawer_isi').html(bangunDetail(d)).scrollTop(0);
            })
            .fail(function (xhr, st) {
                if (st === 'abort') return;
                $('#dr_drawer_meta').text('');
                $('#dr_drawer_isi').html('<div class="dd-muat">Gagal memuat detail. Coba klik baris lagi.</div>');
            });
    }

    // Klik baris (semua tab). Abaikan klik pada kontrol di dalam baris, ikon salin, dan saat user sedang memblok teks.
    $('#dt_daftar_retur tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('a, button, input, label, .dr-salin').length) return;
        var s = window.getSelection ? String(window.getSelection()) : '';
        if (s && s.length > 0) return;
        var resi = $(this).find('.dr-pilih').attr('data-resi');
        if (resi) bukaDetail(resi);
    });
    $('#dr_drawer').on('click', '.dd-copy', function () {
        var $b = $(this), teks = String($b.attr('data-copy') || '');
        salinTeks(teks).then(function () {
            $b.addClass('ok').find('i').removeClass('fa-files-o').addClass('fa-check');
            setTimeout(function () { $b.removeClass('ok').find('i').removeClass('fa-check').addClass('fa-files-o'); }, 1200);
        }, function () { window.prompt('Salin manual (Ctrl+C):', teks); });
    });
});
</script>
