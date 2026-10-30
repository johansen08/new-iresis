<?php
// View menu TIM PURCHASING -> Stok AKS27-RDM. Dimuat lewat AJAX (SPA) oleh
// plugins.js: semua handler diikat ke #sa-root supaya ikut hilang saat user
// pindah menu. Tanpa <form> sengaja -- submit form dicegat handler global.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Stok AKS27-RDM</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<div id="sa-root" data-hari-ini="<?= htmlspecialchars($hari_ini, ENT_QUOTES) ?>" data-mulai="<?= htmlspecialchars(Stok_aks27_fcd::TANGGAL_MULAI_POTONG, ENT_QUOTES) ?>">
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Stok AKS27-RDM</strong>
            <button type="button" id="sa-ic" class="sa-ic" aria-label="Info halaman" aria-expanded="false">i</button>
          </h3>
        </div>
        <div class="panel-body">
          <div id="sa-info" class="alert alert-info hidden">
            <p>Stok pinset di lokasi khusus area packer. Baris hanya bertambah; koreksi lewat PENYESUAIAN.</p>
            <p>Status dan tanggal HO diambil dari data resi, bukan disimpan di stok.</p>
            <p style="margin-bottom:0;">Pemotongan penjualan dimulai dari resi yang scan packer (atau scan HO bila tidak ada catatan packer) pada atau setelah <b id="sa-mulai"></b>. Resi yang discan sebelumnya tidak memotong dan tidak ikut cancel atau retur. AKS27-RDM yang dibeli di sebuah resi diambil dari rak biasa dan tidak memotong stok ini.</p>
          </div>

          <div id="sa-pesan" class="alert alert-danger hidden" role="status" style="margin-bottom:12px;"></div>

          <div class="sa-kartu">
            <div class="sa-kpi"><small>Sisa Stok</small><b id="sa-k-saldo">-</b></div>
            <div class="sa-kpi"><small>Penjualan</small><b id="sa-k-jual">-</b></div>
            <div class="sa-kpi"><small>Dalam perjalanan</small><b id="sa-k-jalan">-</b></div>
          </div>

          <div class="panel panel-default sa-grafik">
            <div class="panel-body">
              <div class="sa-judul">Saldo per transaksi</div>
              <div id="sa-grafik"></div>
            </div>
          </div>

          <div class="sa-filter">
            <input type="text" id="sa-cari" class="form-control sa-cari" placeholder="Cari no, ref, ket, tanggal" autocomplete="off" />
            <div id="sa-dp" class="sa-dp">
              <button type="button" id="sa-dp-btn" class="sa-dp-btn" aria-haspopup="dialog" aria-expanded="false">
                <i class="fa fa-calendar"></i> <span id="sa-dp-label">Semua tanggal</span> <i class="fa fa-caret-down"></i>
              </button>
              <div id="sa-dp-pop" class="sa-dp-pop hidden" role="dialog" aria-label="Pilih rentang tanggal"></div>
            </div>
            <div id="sa-ref" class="sa-ref">
              <button type="button" id="sa-ref-btn" class="sa-dp-btn" aria-haspopup="true" aria-expanded="false">
                Ref: <span id="sa-ref-label">Semua ref</span> <i class="fa fa-caret-down"></i>
              </button>
              <div id="sa-ref-pop" class="sa-ref-pop hidden" role="group" aria-label="Filter ref"></div>
            </div>
            <button type="button" id="sa-reset" class="btn btn-default">Reset filter</button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-hover" id="sa-tabel" style="margin-bottom:10px;">
              <thead>
                <tr>
                  <th>Tgl</th><th>Ref</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Sisa</th>
                  <th class="text-right">Qty jual</th><th>No pesanan / transfer</th><th>Tgl QC</th><th>Tgl HO</th><th>Status</th><th>Ket</th>
                </tr>
              </thead>
              <tbody><tr><td colspan="11" class="text-muted text-center">Memuat…</td></tr></tbody>
            </table>
          </div>

          <div class="row">
            <div class="col-md-4 col-xs-12 text-muted" id="sa-info-tabel" style="padding-top:7px;"></div>
            <div class="col-md-8 col-xs-12 text-right sa-pager">
              <label for="sa-per" class="text-muted">Per halaman</label>
              <select id="sa-per" class="form-control">
                <option>10</option><option>20</option><option>50</option>
              </select>
              <ul class="pagination" id="sa-halaman" style="margin:0;"></ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var $root = $('#sa-root');
  var HARI_INI = String($root.data('hariIni'));
  var MULAI = String($root.data('mulai'));
  var URL = { ringkas: 'purchasing/stok-aks27/ringkas', data: 'purchasing/stok-aks27/data' };
  var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  var BULAN_PENUH = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  var PRESET = [['semua', 'Semua tanggal'], ['hari', 'Hari ini'], ['kemarin', 'Kemarin'], ['7', '7 hari terakhir'],
                ['30', '30 hari terakhir'], ['bulan', 'Bulan ini'], ['bulanlalu', 'Bulan lalu']];
  var REFS = ['INPUT', 'PENYESUAIAN', 'RETURAN', 'CANCEL', 'PENJUALAN'];
  var REF_KELAS = { INPUT: 'success', PENYESUAIAN: 'warning', RETURAN: 'info', CANCEL: 'danger', PENJUALAN: 'default' };
  var F = { q: '', dari: '', sampai: '', pre: 'semua', ref: [], per: 10, halaman: 1 };
  var perRef = {}, REFBUKA = false, reqData = 0, timerCari = null;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function pad2(n) { return (n < 10 ? '0' : '') + n; }
  function pesan(teks) { $('#sa-pesan').removeClass('hidden').text(teks); }
  function jsonGagal(xhr) {
    // Bukan JSON = ada output nyasar (warning PHP / halaman error CI).
    pesan('Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200));
  }
  function iso(ms) { return new Date(ms).toISOString().slice(0, 10); }
  function addD(s, n) { return iso(Date.parse(s) + n * 864e5); }
  function fmtD(s) { return Number(s.slice(8)) + ' ' + BULAN[Number(s.slice(5, 7)) - 1] + ' ' + s.slice(0, 4); }
  function fmtPendek(s) { return Number(s.slice(8)) + ' ' + BULAN[Number(s.slice(5, 7)) - 1]; }
  // 'YYYY-MM-DD HH:MM:SS' -> '28 Okt 19:00'
  function fmtWaktu(s) { return s ? Number(s.slice(8, 10)) + ' ' + BULAN[Number(s.slice(5, 7)) - 1] + ' ' + s.slice(11, 16) : ''; }

  $('#sa-mulai').text(fmtD(MULAI));
  $root.on('click', '#sa-ic', function () {
    var $i = $('#sa-info').toggleClass('hidden');
    $(this).attr('aria-expanded', !$i.hasClass('hidden'));
  });

  // ---------- kartu + grafik ----------
  function gambarGrafik(pts, total) {
    if (!pts.length) { $('#sa-grafik').html('<div class="text-muted">Belum ada transaksi.</div>'); return; }
    var W = 640, H = 90, mx = Math.max.apply(null, pts.concat([1])), mn = Math.min.apply(null, pts.concat([0]));
    var X = function (i) { return pts.length < 2 ? 10 : 10 + i * (W - 20) / (pts.length - 1); };
    var Y = function (v) { return 8 + (mx - v) * (H - 16) / ((mx - mn) || 1); };
    var d = $.map(pts, function (v, i) { return X(i) + ',' + Y(v); }).join(' ');
    $('#sa-grafik').html('<svg viewBox="0 0 ' + W + ' ' + (H + 14) + '" style="width:100%;height:auto" role="img" aria-label="Grafik saldo stok per transaksi">' +
      '<polyline fill="none" stroke="#337ab7" stroke-width="2" points="' + d + '"/>' +
      '<circle cx="' + X(pts.length - 1) + '" cy="' + Y(pts[pts.length - 1]) + '" r="4" fill="#337ab7"/>' +
      '<text x="10" y="' + (H + 10) + '" font-size="11" fill="#888">transaksi ' + (total - pts.length + 1) + '</text>' +
      '<text x="' + (W - 10) + '" y="' + (H + 10) + '" text-anchor="end" font-size="11" fill="#888">transaksi ' + total + '</text></svg>');
  }
  function muatRingkas() {
    $.getJSON(URL.ringkas).done(function (res) {
      if (Number(res.code) !== 200 || !res.data) { pesan(res.message || 'Gagal memuat ringkasan.'); return; }
      var d = res.data;
      $('#sa-k-saldo').text(d.saldo).toggleClass('text-danger', Number(d.saldo) < 0);
      $('#sa-k-jual').text(d.penjualan);
      $('#sa-k-jalan').text(d.perjalanan);
      perRef = d.per_ref || {};
      gambarGrafik(d.grafik || [], Number(d.total_baris));
    }).fail(jsonGagal);
  }

  // ---------- tabel + pagination ----------
  function muatData() {
    var no = ++reqData;
    $.getJSON(URL.data, { q: F.q, ref: F.ref.join(','), dari: F.dari, sampai: F.sampai, per: F.per, halaman: F.halaman }).done(function (res) {
      if (no !== reqData) { return; } // balasan lama, abaikan
      if (Number(res.code) !== 200 || !res.data) { pesan(res.message || 'Gagal memuat buku stok.'); return; }
      var d = res.data;
      F.halaman = d.halaman;
      var $tb = $('#sa-tabel tbody').empty();
      if (!d.rows.length) {
        $tb.append('<tr><td colspan="11" class="text-muted text-center">' + (F.q || F.dari || F.sampai || F.ref.length ? 'Tidak ada baris yang cocok dengan filter.' : 'Belum ada transaksi stok.') + '</td></tr>');
      }
      $.each(d.rows, function (i, r) {
        var tgl = fmtD(r.tanggal) + (r.jam ? ' ' + r.jam.slice(0, 5) : '');
        $tb.append('<tr><td>' + esc(tgl) + '</td>' +
          '<td><span class="label label-' + (REF_KELAS[r.ref] || 'default') + '">' + esc(r.ref) + '</span></td>' +
          '<td class="text-right">' + (Number(r.masuk) ? esc(r.masuk) : '') + '</td>' +
          '<td class="text-right">' + (Number(r.keluar) ? esc(r.keluar) : '') + '</td>' +
          '<td class="text-right"><b' + (Number(r.sisa) < 0 ? ' class="text-danger"' : '') + '>' + esc(r.sisa) + '</b></td>' +
          '<td class="text-right">' + esc(r.qty_jual === null ? '' : r.qty_jual) + '</td>' +
          '<td><code>' + esc(r.no_ref) + '</code></td>' +
          '<td>' + esc(fmtWaktu(r.tgl_qc)) + '</td><td>' + esc(fmtWaktu(r.tgl_ho)) + '</td>' +
          '<td>' + (r.status ? '<span class="label label-default">' + esc(r.status) + '</span>' : '') + '</td>' +
          '<td>' + esc(r.keterangan) + (r.flag ? ' <span class="label label-' + (r.flag === 'besar' ? 'danger' : 'warning') + '">' + esc(r.flag) + '</span>' : '') + '</td></tr>');
      });
      $('#sa-info-tabel').text(d.total ? 'Menampilkan ' + d.dari + '-' + d.sampai + ' dari ' + d.total : '0 baris');
      gambarHalaman(d.halaman, d.jml_hal);
    }).fail(jsonGagal);
  }
  function gambarHalaman(sekarang, jumlah) {
    var $p = $('#sa-halaman').empty();
    function item(label, hal, nonaktif, aktif, aria) {
      $p.append('<li class="' + (nonaktif ? 'disabled ' : '') + (aktif ? 'active' : '') + '"><a href="javascript:void(0)" class="nojs sa-hal" data-hal="' + hal + '"' +
        (aria ? ' aria-label="' + aria + '"' : '') + '>' + label + '</a></li>');
    }
    item('Sebelumnya', sekarang - 1, sekarang <= 1, false);
    var terakhir = 0;
    for (var i = 1; i <= jumlah; i++) {
      if (i === 1 || i === jumlah || Math.abs(i - sekarang) <= 1) {
        if (terakhir && i - terakhir > 1) { $p.append('<li class="disabled"><a href="javascript:void(0)" class="nojs">&hellip;</a></li>'); }
        item(i, i, false, i === sekarang, 'Halaman ' + i);
        terakhir = i;
      }
    }
    item('Berikutnya', sekarang + 1, sekarang >= jumlah, false);
  }
  $root.on('click', '.sa-hal', function (e) {
    e.preventDefault(); e.stopPropagation();
    if ($(this).parent().hasClass('disabled')) { return; }
    F.halaman = Number($(this).data('hal'));
    muatData();
  });
  $root.on('change', '#sa-per', function () { F.per = Number($(this).val()); F.halaman = 1; muatData(); });
  $root.on('input', '#sa-cari', function () {
    F.q = $.trim($(this).val());
    F.halaman = 1;
    clearTimeout(timerCari);
    timerCari = setTimeout(muatData, 200);
  });

  // ---------- filter ref: kotak centang ----------
  function labelRef() {
    return F.ref.length ? (F.ref.length <= 2 ? F.ref.join(', ') : F.ref.length + ' ref dipilih') : 'Semua ref';
  }
  function gambarRef() {
    var h = '<div class="sa-ref-h"><button type="button" class="sa-ref-semua">Pilih semua</button><button type="button" class="sa-ref-nol">Hapus pilihan</button></div>';
    $.each(REFS, function (i, r) {
      h += '<label class="sa-ref-i"><input type="checkbox" value="' + r + '"' + ($.inArray(r, F.ref) > -1 ? ' checked' : '') + ' />' +
        '<span class="label label-' + REF_KELAS[r] + '">' + r + '</span><span class="sa-ref-n">' + (perRef[r] || 0) + '</span></label>';
    });
    $('#sa-ref-pop').html(h);
  }
  function segarkanRef() {
    $('#sa-ref-label').text(labelRef());
    $('#sa-ref-btn').toggleClass('act', F.ref.length > 0).attr('aria-expanded', REFBUKA);
  }
  function tutupRef() { REFBUKA = false; $('#sa-ref-pop').addClass('hidden'); segarkanRef(); }
  function terapkanRef() {
    F.ref = $.map($('#sa-ref-pop input:checked'), function (c) { return c.value; });
    F.halaman = 1; segarkanRef(); muatData();
  }
  $root.on('click', '#sa-ref-btn', function (e) {
    e.stopPropagation();
    if (REFBUKA) { tutupRef(); return; }
    tutupDp(); REFBUKA = true; gambarRef(); $('#sa-ref-pop').removeClass('hidden'); segarkanRef();
  });
  $root.on('change', '#sa-ref-pop input', terapkanRef);
  $root.on('click', '.sa-ref-semua', function () { $('#sa-ref-pop input').prop('checked', true); terapkanRef(); });
  $root.on('click', '.sa-ref-nol', function () { $('#sa-ref-pop input').prop('checked', false); terapkanRef(); });

  // ---------- filter tanggal: popover preset + kalender dua bulan ----------
  var DP = { buka: false, a: null, b: null, hov: null, y: 0, m: 0, dua: true };

  function rentangPreset(k) {
    var t = HARI_INI, y = Number(t.slice(0, 4)), m = Number(t.slice(5, 7)) - 1;
    if (k === 'semua') { return ['', '']; }
    if (k === 'hari') { return [t, t]; }
    if (k === 'kemarin') { var d = addD(t, -1); return [d, d]; }
    if (k === '7') { return [addD(t, -6), t]; }
    if (k === '30') { return [addD(t, -29), t]; }
    if (k === 'bulan') { return [y + '-' + pad2(m + 1) + '-01', t]; }
    var py = m ? y : y - 1, pm = m ? m - 1 : 11;
    return [py + '-' + pad2(pm + 1) + '-01', py + '-' + pad2(pm + 1) + '-' + pad2(new Date(Date.UTC(py, pm + 1, 0)).getUTCDate())];
  }
  function labelDp() {
    if (F.pre !== 'kustom') {
      for (var i = 0; i < PRESET.length; i++) { if (PRESET[i][0] === F.pre) { return PRESET[i][1]; } }
      return 'Semua tanggal';
    }
    if (F.dari === F.sampai) { return fmtD(F.dari); }
    return F.dari.slice(0, 4) === F.sampai.slice(0, 4)
      ? fmtPendek(F.dari) + ' - ' + fmtPendek(F.sampai) + ' ' + F.sampai.slice(0, 4)
      : fmtD(F.dari) + ' - ' + fmtD(F.sampai);
  }
  function segarkanTombolDp() {
    $('#sa-dp-label').text(labelDp());
    $('#sa-dp-btn').toggleClass('act', !!(F.dari || F.sampai)).attr('aria-expanded', DP.buka);
  }
  function bulanKal(y, m) {
    var awal = (new Date(Date.UTC(y, m, 1)).getUTCDay() + 6) % 7, n = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
    var h = '<div class="sa-mo"><div class="sa-mt">' + BULAN_PENUH[m] + ' ' + y + '</div><div class="sa-g">';
    $.each(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'], function (i, w) { h += '<span class="sa-w">' + w + '</span>'; });
    for (var i = 0; i < awal; i++) { h += '<span class="sa-d blank"></span>'; }
    for (var d = 1; d <= n; d++) {
      var s = y + '-' + pad2(m + 1) + '-' + pad2(d), dis = s > HARI_INI;
      h += '<button type="button" class="sa-d' + (dis ? ' dis' : '') + (s === HARI_INI ? ' td' : '') + '" data-d="' + s + '"' + (dis ? ' disabled' : '') + '>' + d + '</button>';
    }
    return h + '</div></div>';
  }
  function bulanMaks() {
    return Number(HARI_INI.slice(0, 4)) * 12 + Number(HARI_INI.slice(5, 7)) - 1 - (DP.dua ? 1 : 0);
  }
  function htmlDp() {
    var k = DP.y * 12 + DP.m, y2 = DP.m === 11 ? DP.y + 1 : DP.y, m2 = DP.m === 11 ? 0 : DP.m + 1;
    var side = '';
    $.each(PRESET, function (i, p) { side += '<button type="button" class="sa-pre' + (F.pre === p[0] ? ' on' : '') + '" data-pre="' + p[0] + '">' + p[1] + '</button>'; });
    return '<div class="sa-side">' + side + '</div><div class="sa-main">' +
      '<div class="sa-nav"><button type="button" class="sa-nav-p" aria-label="Bulan sebelumnya">&#8249;</button>' +
      '<button type="button" class="sa-nav-n" aria-label="Bulan berikutnya"' + (k >= bulanMaks() ? ' disabled' : '') + '>&#8250;</button></div>' +
      '<div class="sa-cal">' + bulanKal(DP.y, DP.m) + (DP.dua ? bulanKal(y2, m2) : '') + '</div>' +
      '<div class="sa-ft"><span class="sa-sum" id="sa-sum"></span><span><button type="button" class="btn btn-default sa-batal">Batal</button> ' +
      '<button type="button" class="btn btn-primary sa-terapkan" id="sa-terapkan">Terapkan</button></span></div></div>';
  }
  function catDp() {
    var a = DP.a, e = DP.b || (a && DP.hov) || null, lo = a, hi = e;
    if (a && e && e < a) { lo = e; hi = a; }
    $('#sa-dp-pop .sa-d[data-d]').each(function () {
      var d = this.getAttribute('data-d');
      $(this).toggleClass('st', !!lo && d === lo).toggleClass('en', !!hi && d === hi).toggleClass('in', !!lo && !!hi && d > lo && d < hi);
    });
    $('#sa-sum').html(!a ? 'Pilih tanggal awal' : (!DP.b ? '<b>' + fmtD(a) + '</b> - pilih tanggal akhir' : '<b>' + fmtD(lo) + '</b> - <b>' + fmtD(hi) + '</b>'));
    $('#sa-terapkan').prop('disabled', !a);
  }
  function gambarDp() { $('#sa-dp-pop').html(htmlDp()); catDp(); }
  function tutupDp() { DP.buka = false; $('#sa-dp-pop').addClass('hidden').empty(); segarkanTombolDp(); }
  function terapkanFilter() { F.halaman = 1; tutupDp(); segarkanTombolDp(); muatData(); }

  $root.on('click', '#sa-dp-btn', function (e) {
    e.stopPropagation();
    if (DP.buka) { tutupDp(); return; }
    DP.buka = true; DP.dua = window.innerWidth >= 900;
    DP.a = F.dari || null; DP.b = F.dari ? (F.sampai || F.dari) : null; DP.hov = null;
    var ref = F.dari || HARI_INI;
    var k = Math.min(Number(ref.slice(0, 4)) * 12 + Number(ref.slice(5, 7)) - 1, bulanMaks());
    DP.y = Math.floor(k / 12); DP.m = k % 12;
    $('#sa-dp-pop').removeClass('hidden').css('left', ''); gambarDp(); segarkanTombolDp();
    // Kalau popover melewati tepi kanan layar, geser ke kiri secukupnya.
    var lebih = $('#sa-dp-pop')[0].getBoundingClientRect().right - (window.innerWidth - 12);
    if (lebih > 0 && window.innerWidth > 640) { $('#sa-dp-pop').css('left', -lebih + 'px'); }
  });
  $root.on('click', '.sa-pre', function () {
    var k = String($(this).data('pre')), r = rentangPreset(k);
    F.pre = k; F.dari = r[0]; F.sampai = r[1]; terapkanFilter();
  });
  $root.on('click', '.sa-nav-p, .sa-nav-n', function () {
    var k = DP.y * 12 + DP.m + ($(this).hasClass('sa-nav-p') ? -1 : 1);
    if (k > bulanMaks()) { return; }
    DP.y = Math.floor(k / 12); DP.m = k % 12; gambarDp();
  });
  $root.on('click', '#sa-dp-pop .sa-d[data-d]', function () {
    var d = this.getAttribute('data-d');
    if (!DP.a || DP.b) { DP.a = d; DP.b = null; } else if (d < DP.a) { DP.b = DP.a; DP.a = d; } else { DP.b = d; }
    DP.hov = null; catDp();
  });
  $root.on('mouseover', '#sa-dp-pop .sa-d[data-d]', function () {
    var d = this.getAttribute('data-d');
    if (DP.a && !DP.b && DP.hov !== d) { DP.hov = d; catDp(); }
  });
  $root.on('click', '.sa-terapkan', function () {
    if (!DP.a) { return; }
    F.dari = DP.a; F.sampai = DP.b || DP.a; F.pre = 'kustom'; terapkanFilter();
  });
  $root.on('click', '.sa-batal', tutupDp);
  // Klik di luar / Esc menutup. Dipasang di document, jadi pakai namespace supaya
  // tidak menumpuk tiap view dimuat ulang oleh SPA.
  $(document).off('click.sadp keydown.sadp').on('click.sadp', function (e) {
    if (DP.buka && !$(e.target).closest('#sa-dp').length) { tutupDp(); }
    if (REFBUKA && !$(e.target).closest('#sa-ref').length) { tutupRef(); }
  }).on('keydown.sadp', function (e) {
    if (e.key === 'Escape') {
      if (DP.buka) { tutupDp(); }
      if (REFBUKA) { tutupRef(); }
    }
  });


  $root.on('click', '#sa-reset', function () {
    F.q = ''; F.dari = ''; F.sampai = ''; F.pre = 'semua'; F.ref = []; F.halaman = 1;
    $('#sa-cari').val(''); tutupDp(); tutupRef(); segarkanRef(); muatData();
  });

  segarkanTombolDp();
  segarkanRef();
  muatRingkas();
  muatData();
})();
</script>
<style>
  /* Font template iresis kecil (12-13px); menu ini dibaca dari jauh, jadi dibesarkan (sama dengan menu stok lain). */
  #sa-root .panel-title { font-size: 18px; }
  #sa-root .form-control, #sa-root .btn { height: 44px; font-size: 18px; }
  #sa-root .alert { font-size: 17px; }
  #sa-root .table > thead > tr > th { font-size: 16px; padding: 12px 14px; white-space: nowrap; }
  #sa-root .table > tbody > tr > td { font-size: 17px; padding: 10px 14px; vertical-align: middle; }
  #sa-root .table code { font-size: 16px; font-weight: 600; }
  #sa-root .table > tbody > tr > td:nth-child(-n+10) { white-space: nowrap; }
  #sa-root .table > tbody > tr > td:nth-child(11) { min-width: 360px; }
  #sa-root .table .label { font-size: 13px; padding: 5px 9px; }
  #sa-root #sa-info-tabel { font-size: 16px; }
  #sa-root .pagination > li > a { font-size: 16px; padding: 8px 14px; }
  #sa-root .sa-ic { width: 26px; height: 26px; border-radius: 50%; border: 1px solid #bbb; background: #fff; color: #777; font: 600 14px/1 monospace; cursor: pointer; padding: 0; margin-left: 8px; vertical-align: middle; }
  #sa-root .sa-ic:hover, #sa-root .sa-ic[aria-expanded="true"] { color: #337ab7; border-color: #337ab7; background: #eaf2fa; }

  #sa-root .sa-kartu { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; }
  #sa-root .sa-kpi { flex: 1 1 200px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 18px; background: #f7f8fa; }
  #sa-root .sa-kpi small { display: block; font-size: 15px; color: #666; }
  #sa-root .sa-kpi b { font-size: 32px; }
  #sa-root .sa-grafik .sa-judul { font-size: 17px; font-weight: 600; margin-bottom: 8px; }

  #sa-root .sa-filter { clear: both; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 14px 0; }
  #sa-root .sa-cari { width: 320px; max-width: 100%; border-radius: 22px; padding-left: 18px; }
  #sa-root .sa-dp { position: relative; }
  #sa-root .sa-dp-btn { height: 44px; padding: 0 18px; font-size: 17px; border: 1px solid #ccc; border-radius: 22px; background: #fff; cursor: pointer; }
  #sa-root .sa-dp-btn:hover, #sa-root .sa-dp-btn[aria-expanded="true"] { border-color: #337ab7; }
  #sa-root .sa-dp-btn.act { background: #eaf2fa; border-color: #337ab7; color: #1f5f99; font-weight: 600; }
  #sa-root .sa-dp-pop { position: absolute; left: 0; top: 52px; z-index: 1000; display: flex; background: #fff; border: 1px solid #d1d5db; border-radius: 12px; box-shadow: 0 12px 32px rgba(0,0,0,.22); overflow: hidden; }
  #sa-root .sa-dp-pop.hidden { display: none; }
  #sa-root .sa-side { display: flex; flex-direction: column; gap: 2px; padding: 10px; border-right: 1px solid #e5e7eb; min-width: 190px; background: #f7f8fa; }
  #sa-root .sa-pre { border: 0; background: none; text-align: left; padding: 9px 12px; border-radius: 6px; cursor: pointer; font-size: 16px; height: auto; color: #222; }
  #sa-root .sa-pre:hover { background: #eaf2fa; }
  #sa-root .sa-pre.on { background: #337ab7; color: #fff; font-weight: 600; }
  #sa-root .sa-main { padding: 12px 16px; }
  #sa-root .sa-nav { display: flex; justify-content: space-between; margin-bottom: 4px; }
  #sa-root .sa-nav button { width: 34px; height: 34px; border-radius: 50%; border: 1px solid #ccc; background: #fff; cursor: pointer; font-size: 20px; line-height: 1; padding: 0; }
  #sa-root .sa-nav button:disabled { opacity: .35; cursor: default; }
  #sa-root .sa-cal { display: flex; gap: 24px; }
  #sa-root .sa-mo { width: 294px; }
  #sa-root .sa-mt { text-align: center; font-weight: 600; font-size: 17px; margin: -34px 0 10px; line-height: 34px; pointer-events: none; }
  #sa-root .sa-g { display: grid; grid-template-columns: repeat(7, 42px); gap: 2px 0; }
  #sa-root .sa-w { font-size: 13px; color: #888; text-align: center; line-height: 26px; }
  #sa-root .sa-d { height: 40px; border: 0; background: none; cursor: pointer; font-size: 17px; color: #222; border-radius: 0; padding: 0; }
  #sa-root .sa-d:hover:not(.dis):not(.st):not(.en) { background: #eaf2fa; border-radius: 50%; }
  #sa-root .sa-d.in { background: #eaf2fa; }
  #sa-root .sa-d.st, #sa-root .sa-d.en { background: #337ab7; color: #fff; font-weight: 600; }
  #sa-root .sa-d.st { border-radius: 50% 0 0 50%; }
  #sa-root .sa-d.en { border-radius: 0 50% 50% 0; }
  #sa-root .sa-d.st.en { border-radius: 50%; }
  #sa-root .sa-d.td { box-shadow: inset 0 -2px 0 #337ab7; }
  #sa-root .sa-d.dis { color: #aaa; opacity: .45; cursor: default; }
  #sa-root .sa-d.blank { visibility: hidden; }
  #sa-root .sa-ft { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
  #sa-root .sa-sum { font-size: 16px; color: #666; }
  #sa-root .sa-sum b { color: #222; }
  #sa-root .sa-pager { display: flex; justify-content: flex-end; align-items: center; gap: 10px; flex-wrap: wrap; }
  #sa-root .sa-pager select { width: 84px; }
  @media (max-width: 640px) {
    #sa-root .sa-dp-pop { position: fixed; left: 12px; right: 12px; top: auto; bottom: 12px; max-height: 85vh; overflow: auto; flex-direction: column; }
    #sa-root .sa-side { flex-direction: row; flex-wrap: wrap; border-right: 0; border-bottom: 1px solid #e5e7eb; }
    #sa-root .sa-mo { width: 100%; }
    #sa-root .sa-g { grid-template-columns: repeat(7, 1fr); }
  }


  #sa-root .sa-ref { position: relative; }
  #sa-root .sa-ref-btn { }
  #sa-root #sa-ref-btn.act { background: #eaf2fa; border-color: #337ab7; color: #1f5f99; font-weight: 600; }
  #sa-root .sa-ref-pop { position: absolute; left: 0; top: 52px; z-index: 1000; min-width: 270px; background: #fff; border: 1px solid #d1d5db; border-radius: 12px; box-shadow: 0 12px 32px rgba(0,0,0,.22); padding: 8px; }
  #sa-root .sa-ref-pop.hidden { display: none; }
  #sa-root .sa-ref-h { display: flex; justify-content: space-between; padding: 4px 8px 8px; border-bottom: 1px solid #e5e7eb; margin-bottom: 4px; }
  #sa-root .sa-ref-h button { border: 0; background: none; color: #337ab7; cursor: pointer; font-size: 15px; padding: 0; height: auto; }
  #sa-root .sa-ref-i { display: flex; align-items: center; gap: 12px; padding: 9px 8px; margin: 0; border-radius: 6px; cursor: pointer; font-weight: normal; font-size: 16px; }
  #sa-root .sa-ref-i:hover { background: #eaf2fa; }
  #sa-root .sa-ref-i input { width: 18px; height: 18px; margin: 0; }
  #sa-root .sa-ref-i .label { font-size: 14px; padding: 5px 10px; }
  #sa-root .sa-ref-n { margin-left: auto; color: #888; font-family: monospace; }
</style>
