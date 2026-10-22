<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<style>
/* Daftar Retur Penjualan: tab status + filter ringkas + tabel bersih (acuan: UI daftar pesanan seller marketplace) */
#dr_wrap { background:#fff; border-radius:6px; padding:0 18px 18px; }
#dr_wrap .dr-judul { padding:16px 0 4px; }
#dr_wrap .dr-judul h3 { margin:0; font-size:18px; font-weight:700; }
/* Ikon info: penjelasan & legenda disembunyikan di balik ikon (muncul saat diarahkan/diklik) */
.dr-bantu { position:relative; display:inline-block; margin-left:6px; vertical-align:middle; }
.dr-bantu > i { color:#9ca3af; cursor:pointer; font-size:16px; }
.dr-bantu:hover > i, .dr-bantu.buka > i { color:#0f766e; }
.dr-pop { display:none; position:absolute; left:0; top:26px; z-index:50; width:340px; padding:12px 14px;
          background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12);
          font-size:13px; font-weight:400; color:#374151; line-height:1.5; }
.dr-bantu:hover .dr-pop, .dr-bantu.buka .dr-pop { display:block; }
.dr-pop p { margin:0 0 8px; }
.dr-pop .dr-lg { margin-top:6px; }

/* Tab status */
/* flex-wrap (bukan overflow:auto): overflow membuat batang gulir kecil muncul di sisi kanan tab */
.dr-tabs { display:flex; flex-wrap:wrap; gap:0 28px; border-bottom:1px solid #e5e7eb; margin-top:10px; }
.dr-tab { padding:12px 2px; cursor:pointer; font-weight:600; color:#111827; white-space:nowrap;
          border-bottom:2px solid transparent; margin-bottom:-1px; user-select:none; }
.dr-tab:hover { color:#0f766e; }
.dr-tab.on { border-bottom-color:#0f766e; }
.dr-tab .n { margin-left:5px; font-weight:500; color:#9ca3af; }
.dr-tab .n.hot { color:#0f766e; }

/* Bar filter */
.dr-filter { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:14px 0 10px; }
.dr-chip { display:inline-flex; align-items:center; gap:8px; border:1px solid #d1d5db; border-radius:6px;
           padding:0 12px; height:38px; background:#fff; }
.dr-chip i { color:#6b7280; }
.dr-chip input { border:0; outline:0; background:transparent; width:210px; cursor:pointer; color:#111827; }
.dr-reset { width:38px; height:38px; border:0; border-radius:6px; background:#f3f4f6; cursor:pointer; color:#374151; }
.dr-reset:hover { background:#e5e7eb; }

/* Tabel */
#dr_wrap table.dataTable { border:0; }
/* Tanpa gulir samping: tata letak tetap (table-layout:fixed) dengan lebar kolom persen,
   teks panjang dibungkus / dipotong titik-titik, bukan melebar. */
#dr_wrap table.dataTable { table-layout:fixed; width:100% !important; font-size:12.5px; }
#dr_wrap table.dataTable thead th { background:#f9fafb; color:#6b7280; font-weight:600; border:0; padding:10px 6px; font-size:12px; }
#dr_wrap table.dataTable tbody td { border:0; border-bottom:1px solid #f1f5f9; padding:8px 6px; vertical-align:middle; }
/* hanya nomor pesanan & resi yang boleh dipotong di tengah kata; kolom lain dibungkus di spasi */
#dr_wrap table.dataTable tbody td:nth-child(3), #dr_wrap table.dataTable tbody td:nth-child(4) { overflow-wrap:anywhere; }
#dr_wrap table.dataTable tbody td:nth-child(1), #dr_wrap table.dataTable tbody td:nth-child(2) { white-space:nowrap; }
#dr_wrap table.dataTable tbody td:nth-child(8) { text-align:right; font-variant-numeric:tabular-nums; }
#dr_wrap table.dataTable thead th:nth-child(8) { text-align:left; }
/* teks + ikon salin: ikon tetap di samping baris pertama, tidak jatuh ke baris baru */
.dr-no { display:flex; align-items:flex-start; gap:4px; }
.dr-nt { min-width:0; overflow-wrap:anywhere; }
.dr-salin { flex:none; margin-top:2px; color:#9ca3af; cursor:pointer; font-size:12px; }
.dr-salin:hover { color:#0f766e; }
.dr-salin.sudah { color:#16a34a; }
.dr-toko { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
/* layar sempit: kolom Toko disembunyikan (masih bisa dicari lewat kotak Search) */
@media (max-width: 1200px) {
  #dr_wrap table.dataTable th:nth-child(7), #dr_wrap table.dataTable td:nth-child(7) { display:none; }
}
/* jendela sangat sempit: tabel tidak diperas lagi, baru boleh digeser */
@media (max-width: 899px) {
  #dr_tablewrap { overflow-x:auto; }
  #dr_wrap table.dataTable { min-width:820px; }
}
#dr_wrap table.dataTable tbody tr:hover td { background:#f8fafc; }
.dr-t1 { font-weight:600; color:#111827; }
.dr-t2 { color:#9ca3af; font-size:12px; }

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

/* Umur */
.dr-umur { display:inline-block; padding:2px 8px; border-radius:12px; font-weight:700; white-space:nowrap; }
.dr-umur-aman { background:#dcfce7; color:#166534; }
.dr-umur-oranye { background:#f59e0b; color:#fff; }
.dr-umur-merah { background:#dc2626; color:#fff; }
.dr-umur-selesai { background:#e5e7eb; color:#6b7280; }
.dr-pill { display:inline-block; margin-top:4px; padding:2px 6px; border-radius:4px; font-size:11px; font-weight:600; white-space:nowrap; }
.dr-pill-merah { background:#fee2e2; color:#b91c1c; }
.dr-pill-oranye { background:#fef3c7; color:#b45309; }

/* Status retur */
.dr-st { display:inline-block; padding:3px 8px; border-radius:4px; font-weight:600; font-size:11.5px; }
.dr-st-abu { background:#f3f4f6; color:#374151; }
.dr-st-biru { background:#dbeafe; color:#1d4ed8; }
.dr-st-hijau { background:#dcfce7; color:#166534; }

/* Sumber tanggal */
.dr-sb { font-size:11px; line-height:1.3; margin-top:2px; }
.dr-sb-ok { color:#166534; }
.dr-sb-kira { color:#b45309; }
.dr-sb-kasar { color:#b91c1c; }
</style>

<div id="dr_wrap">

  <div class="dr-judul">
    <h3>Daftar Retur Penjualan
      <span class="dr-bantu" id="dr_bantu" tabindex="0" aria-label="Penjelasan">
        <i class="fa fa-info-circle"></i>
        <div class="dr-pop">
          <p>Retur ekspedisi: paket yang sudah diambil kurir tetapi dikembalikan ke toko oleh kurir. Umur dihitung dari tanggal retur.</p>
          <div class="dr-lg"><span class="dr-umur dr-umur-oranye" style="padding:0 8px;">&ge; <?= (int) $umur_oranye ?> hari</span> peringatan</div>
          <div class="dr-lg"><span class="dr-umur dr-umur-merah" style="padding:0 8px;">&ge; <?= (int) $umur_merah ?> hari</span> wajib klaim</div>
          <div class="dr-lg text-muted">Paket yang sudah dibuka tidak diberi warna.</div>
        </div>
      </span>
    </h3>
  </div>

  <!-- Tab status retur -->
  <div class="dr-tabs" id="dr_tabs">
    <div class="dr-tab on" data-status="">Semua <span class="n" id="n_semua">-</span></div>
    <div class="dr-tab" data-status="BELUM_DITERIMA">Belum diterima <span class="n hot" id="n_BELUM_DITERIMA">-</span></div>
    <div class="dr-tab" data-status="BELUM_DIBUKA">Diterima, belum dibuka <span class="n hot" id="n_BELUM_DIBUKA">-</span></div>
    <div class="dr-tab" data-status="SUDAH_DIBUKA">Sudah dibuka <span class="n" id="n_SUDAH_DIBUKA">-</span></div>
  </div>

  <!-- Filter -->
  <div class="dr-filter">
    <span class="dr-chip"><i class="fa fa-calendar"></i>
      <span class="text-muted" style="font-size:12px;">Tanggal pesan</span>
      <input type="text" id="dr_rentang" readonly />
    </span>
    <button type="button" class="dr-reset" id="dr_reset" title="Atur ulang filter"><i class="fa fa-undo"></i></button>
  </div>

  <div id="dr_tablewrap">
  <div class="dr-loading">
    <div class="dr-bar"></div>
    <div class="dr-memuat-teks"><span><i class="fa fa-spinner fa-spin"></i><b id="dr_memuat_teks">Memuat data...</b><small id="dr_memuat_detik"></small></span></div>
  </div>
  <table class="table table-hover" id="dt_daftar_retur" style="width:100%;">
    <thead>
      <tr>
        <th style="width:8%;">Tanggal Pesan</th>
        <th style="width:10%;">Tanggal Retur</th>
        <th style="width:15%;">No. Pesanan</th>
        <th style="width:13%;">No. Resi</th>
        <th style="width:10%;">Kurir</th>
        <th style="width:7%;">MP</th>
        <th style="width:11%;">Toko</th>
        <th style="width:8%;">Nilai (Rp)</th>
        <th style="width:9%;">Umur</th>
        <th style="width:9%;">Status Retur</th>
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
    var segarkanBerikut = false;   // true = lewati cache server (tombol atur ulang)
    var timerMuat = null, mulaiMuat = 0;

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

    var tabel = $('#dt_daftar_retur').DataTable({
        processing: false,
        serverSide: true,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[8, 'desc']],          // umur terbesar di atas
        ajax: {
            url: BASE + 'retur/get-daftar-retur-penjualan',
            type: 'POST',
            data: function (d) {
                var v = $('#dr_rentang').val().split(' s/d ');
                d.start_date = v[0] || '';
                d.end_date = v[1] || '';
                d.status = statusAktif;
                d.segarkan = segarkanBerikut ? '1' : '0';
            }
        },
        language: {
            processing: 'Memuat data...',
            emptyTable: 'Tidak ada retur pada rentang tanggal pesan ini',
            zeroRecords: 'Tidak ada data yang cocok'
        }
    });

    // Jumlah per tab + baris "Ada N retur" dari respons server.
    tabel.on('xhr.dt', function (e, settings, json) {
        if (!json) return;
        var c = json.tab_counts || {};
        var semua = 0;
        ['BELUM_DITERIMA', 'BELUM_DIBUKA', 'SUDAH_DIBUKA'].forEach(function (k) {
            var n = parseInt(c[k] || 0, 10);
            semua += n;
            $('#n_' + k).text(n.toLocaleString('id-ID'));
        });
        $('#n_semua').text(semua.toLocaleString('id-ID'));
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

    $('#dr_tabs').on('click', '.dr-tab', function () {
        $('#dr_tabs .dr-tab').removeClass('on');
        $(this).addClass('on');
        statusAktif = $(this).data('status') || '';
        tabel.ajax.reload();
    });

    $('#dr_rentang').on('apply.daterangepicker', function () { tabel.ajax.reload(); });

    $('#dr_reset').on('click', function () {
        var r = rentangBawaan();
        $('#dr_rentang').data('daterangepicker').setStartDate(r[0]);
        $('#dr_rentang').data('daterangepicker').setEndDate(r[1]);
        tulisRentang(r[0], r[1]);
        statusAktif = '';
        $('#dr_tabs .dr-tab').removeClass('on').first().addClass('on');
        segarkanBerikut = true;            // atur ulang sekaligus ambil data terbaru
        tabel.search('').order([8, 'desc']).ajax.reload();
    });
});
</script>
