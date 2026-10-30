<?php
// View menu TIM PACKER -> Penerimaan Transfer. Dimuat lewat AJAX (SPA) oleh
// plugins.js: semua handler diikat ke #pt-root supaya ikut hilang saat user
// pindah menu. Tanpa <form> sengaja -- submit form dicegat handler global.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Penerimaan Transfer</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<div id="pt-root" data-hari-ini="<?= htmlspecialchars($hari_ini, ENT_QUOTES) ?>">
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Penerimaan Transfer</strong>
            <button type="button" id="pt-ic" class="pt-ic" aria-label="Info halaman" aria-expanded="false">i</button>
          </h3>
        </div>
        <div class="panel-body">
          <div id="pt-info" class="alert alert-info hidden">
            <p>Inbound mengantar barang ke lokasi packer. Packer menghitung ulang di tempat dan memasukkan qty real yang sampai. Stok packing mengikuti qty real, bukan qty kirim. Satu transfer diterima sekali; kurang atau lebihnya tercatat sebagai selisih.</p>
            <p style="margin-bottom:0;">Tanggal diterima bisa diatur (default hari ini, tidak boleh sebelum tanggal transfer). Jam hanya terisi bila tanggal diterima adalah hari ini.</p>
          </div>

          <div id="pt-pesan" class="alert hidden" role="status" style="margin-bottom:12px;"></div>

          <div id="pt-menunggu"><div class="text-muted">Memuat…</div></div>

          <h3 class="pt-sub">Riwayat penerimaan</h3>
          <div class="pt-filter">
            <input type="text" id="pt-cari" class="form-control pt-cari" placeholder="Cari transfer, qty, penerima, tanggal" autocomplete="off" />
            <div id="pt-dp" class="pt-dp">
              <button type="button" id="pt-dp-btn" class="pt-dp-btn" aria-haspopup="dialog" aria-expanded="false">
                <i class="fa fa-calendar"></i> <span id="pt-dp-label">Semua tanggal</span> <i class="fa fa-caret-down"></i>
              </button>
              <div id="pt-dp-pop" class="pt-dp-pop hidden" role="dialog" aria-label="Pilih rentang tanggal"></div>
            </div>
            <button type="button" id="pt-reset" class="btn btn-default">Reset filter</button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-hover" id="pt-tabel" style="margin-bottom:10px;">
              <thead>
                <tr><th>Tgl diterima</th><th>Jam</th><th>Transfer</th><th class="text-right">Qty real</th><th>Penerima</th></tr>
              </thead>
              <tbody><tr><td colspan="5" class="text-muted text-center">Memuat…</td></tr></tbody>
            </table>
          </div>

          <div class="row">
            <div class="col-md-4 col-xs-12 text-muted" id="pt-info-tabel" style="padding-top:7px;"></div>
            <div class="col-md-8 col-xs-12 text-right pt-pager">
              <label for="pt-per" class="text-muted">Per halaman</label>
              <select id="pt-per" class="form-control">
                <option>10</option><option>20</option><option>50</option>
              </select>
              <ul class="pagination" id="pt-halaman" style="margin:0;"></ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="pt-modal" class="pt-modal hidden" role="dialog" aria-modal="true" aria-labelledby="pt-modal-j">
    <div class="pt-modal-box">
      <h3 id="pt-modal-j">Terima stok ini?</h3>
      <p>Penerimaan berikut akan disimpan:</p>
      <table class="pt-ringkas">
        <tr><th>Transfer</th><td id="pt-m-no"></td></tr>
        <tr><th>Tanggal diterima</th><td id="pt-m-tgl"></td></tr>
        <tr><th>Jam</th><td id="pt-m-jam"></td></tr>
        <tr><th>Qty real</th><td id="pt-m-qty"></td></tr>
        <tr><th>Stok packing</th><td id="pt-m-stok"></td></tr>
        <tr><th>Hasil</th><td id="pt-m-hasil"></td></tr>
      </table>
      <div class="text-right" style="margin-top:18px;">
        <button type="button" id="pt-m-batal" class="btn btn-default">Batal</button>
        <button type="button" id="pt-m-ok" class="btn btn-primary">Ya, terima stok</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var $root = $('#pt-root');
  var HARI_INI = String($root.data('hariIni'));
  var URL = {
    menunggu: 'packer/penerimaan-transfer/menunggu',
    riwayat:  'packer/penerimaan-transfer/riwayat',
    terima:   'packer/penerimaan-transfer/terima'
  };
  var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  var BULAN_PENUH = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  var PRESET = [['semua', 'Semua tanggal'], ['hari', 'Hari ini'], ['kemarin', 'Kemarin'], ['7', '7 hari terakhir'],
                ['30', '30 hari terakhir'], ['bulan', 'Bulan ini'], ['bulanlalu', 'Bulan lalu']];
  var F = { q: '', dari: '', sampai: '', pre: 'semua', per: 10, halaman: 1 };
  var saldo = 0, antri = null, reqRiwayat = 0, timerCari = null;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function pad2(n) { return (n < 10 ? '0' : '') + n; }
  function pesan(tipe, teks) {
    $('#pt-pesan').removeClass('hidden alert-success alert-danger alert-warning').addClass('alert-' + tipe).text(teks);
  }
  function jsonGagal(xhr) {
    // Bukan JSON = ada output nyasar (warning PHP / halaman error CI).
    pesan('danger', 'Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200));
  }
  function iso(ms) { return new Date(ms).toISOString().slice(0, 10); }
  function addD(s, n) { return iso(Date.parse(s) + n * 864e5); }
  function fmtD(s) { return Number(s.slice(8)) + ' ' + BULAN[Number(s.slice(5, 7)) - 1] + ' ' + s.slice(0, 4); }
  function fmtPendek(s) { return Number(s.slice(8)) + ' ' + BULAN[Number(s.slice(5, 7)) - 1]; }
  function hasilLabel(df) {
    if (df === 0) { return '<span class="label label-success">pas, transfer selesai</span>'; }
    return df > 0 ? '<span class="label label-info">lebih ' + df + ', transfer selesai</span>'
                  : '<span class="label label-warning">kurang ' + (-df) + ', transfer selesai</span>';
  }

  // ---------- info ----------
  $root.on('click', '#pt-ic', function () {
    var $i = $('#pt-info').toggleClass('hidden');
    $(this).attr('aria-expanded', !$i.hasClass('hidden'));
  });

  // ---------- transfer yang menunggu ----------
  function muatMenunggu() {
    $.getJSON(URL.menunggu).done(function (res) {
      if (Number(res.code) !== 200 || !res.data) { pesan('danger', res.message || 'Gagal memuat transfer.'); return; }
      saldo = Number(res.data.saldo);
      var $w = $('#pt-menunggu').empty();
      if (!res.data.rows.length) {
        $w.append('<div class="pt-kosong text-muted">Tidak ada transfer yang menunggu.</div>');
        return;
      }
      $.each(res.data.rows, function (i, r) {
        $w.append('<div class="panel panel-default pt-card" data-id="' + esc(r.id) + '" data-kirim="' + esc(r.qty_kirim) + '" data-tgl="' + esc(r.tanggal) + '">' +
          '<div class="panel-body">' +
          '<div class="pt-card-h"><b>' + esc(r.no) + '</b> <span class="text-muted">' + esc(r.keterangan) + '</span>' +
          '<span class="label label-default pull-right">Dalam perjalanan</span></div>' +
          '<div class="pt-kpi"><small>Qty kirim (Purchasing)</small><b>' + esc(r.qty_kirim) + '</b></div>' +
          '<div class="row pt-isi">' +
          '<div class="col-md-3 col-sm-4 col-xs-12"><label>Tanggal diterima</label>' +
          '<input type="date" class="form-control pt-tgl" min="' + esc(r.tanggal) + '" max="' + esc(HARI_INI) + '" value="' + esc(HARI_INI) + '" /></div>' +
          '<div class="col-md-3 col-sm-4 col-xs-12"><label>Qty real hasil hitung</label>' +
          '<input type="number" class="form-control pt-qty" min="1" step="1" placeholder="0" /></div>' +
          '<div class="col-md-2 col-sm-4 col-xs-12"><label>&nbsp;</label><button type="button" class="btn btn-primary btn-block pt-terima">Terima</button></div>' +
          '</div><div class="pt-pv text-muted"></div></div></div>');
      });
    }).fail(jsonGagal);
  }
  $root.on('input', '.pt-qty', function () {
    var $c = $(this).closest('.pt-card'), q = Number($.trim($(this).val()));
    var $pv = $c.find('.pt-pv');
    if (!(q > 0) || Math.floor(q) !== q) { $pv.text(''); return; }
    $pv.html('Stok packing: ' + saldo + ' menjadi <b>' + (saldo + q) + '</b>. Qty kirim ' + $c.data('kirim') + ': ' + hasilLabel(q - Number($c.data('kirim'))));
  });

  // ---------- terima: popup konfirmasi dulu, baru simpan ----------
  $root.on('click', '.pt-terima', function () {
    var $c = $(this).closest('.pt-card'), id = Number($c.data('id')), kirim = Number($c.data('kirim'));
    var tgl = $c.find('.pt-tgl').val(), qs = $.trim($c.find('.pt-qty').val()), min = String($c.data('tgl'));
    if (!/^\d+$/.test(qs) || Number(qs) <= 0) { pesan('danger', 'Qty real harus bilangan bulat lebih dari 0.'); return; }
    if (Number(qs) > 1000000) { pesan('danger', 'Qty real terlalu besar. Cek lagi angkanya.'); return; }
    if (!/^\d{4}-\d{2}-\d{2}$/.test(tgl)) { pesan('danger', 'Tanggal diterima wajib diisi.'); return; }
    if (tgl > HARI_INI) { pesan('danger', 'Tanggal diterima tidak boleh lebih dari hari ini.'); return; }
    if (tgl < min) { pesan('danger', 'Tanggal diterima tidak boleh sebelum tanggal transfer (' + fmtD(min) + ').'); return; }
    var q = Number(qs);
    antri = { id: id, tgl: tgl, qty: q };
    $('#pt-m-no').text('TR-' + id);
    $('#pt-m-tgl').text(fmtD(tgl));
    $('#pt-m-jam').text(tgl === HARI_INI ? 'otomatis saat disimpan' : 'dikosongkan');
    $('#pt-m-qty').text(q + ' pcs');
    $('#pt-m-stok').text(saldo + ' menjadi ' + (saldo + q));
    $('#pt-m-hasil').html(hasilLabel(q - kirim));
    $('#pt-modal').removeClass('hidden');
    $('#pt-m-ok').focus();
  });
  $root.on('click', '#pt-m-batal', function () { $('#pt-modal').addClass('hidden'); antri = null; });
  $root.on('click', '#pt-modal', function (e) { if (e.target === this) { $(this).addClass('hidden'); antri = null; } });
  $root.on('click', '#pt-m-ok', function () {
    if (!antri) { return; }
    var kirim = antri, $b = $(this).prop('disabled', true);
    $.post(URL.terima, { id: kirim.id, tanggal: kirim.tgl, qty: kirim.qty }, null, 'json').done(function (res) {
      $('#pt-modal').addClass('hidden'); antri = null;
      var ok = Number(res.code) === 200;
      pesan(ok ? ((res.data && res.data.tipe) || 'success') : 'danger', res.message);
      if (ok) { F.halaman = 1; }
      muatMenunggu(); muatRiwayat();
    }).fail(function (xhr) { $('#pt-modal').addClass('hidden'); antri = null; jsonGagal(xhr); })
      .always(function () { $b.prop('disabled', false); });
  });

  // ---------- riwayat: tabel + pagination ----------
  function muatRiwayat() {
    var no = ++reqRiwayat;
    $.getJSON(URL.riwayat, { q: F.q, dari: F.dari, sampai: F.sampai, per: F.per, halaman: F.halaman }).done(function (res) {
      if (no !== reqRiwayat) { return; } // balasan lama, abaikan
      if (Number(res.code) !== 200 || !res.data) { pesan('danger', res.message || 'Gagal memuat riwayat.'); return; }
      var d = res.data;
      F.halaman = d.halaman;
      var $tb = $('#pt-tabel tbody').empty();
      if (!d.rows.length) {
        $tb.append('<tr><td colspan="5" class="text-muted text-center">' + (F.q || F.dari || F.sampai ? 'Tidak ada penerimaan yang cocok dengan filter.' : 'Belum ada penerimaan.') + '</td></tr>');
      }
      $.each(d.rows, function (i, r) {
        $tb.append('<tr><td>' + esc(fmtD(r.tanggal)) + '</td><td><code>' + esc(r.jam || '') + '</code></td><td><code>' + esc(r.no) + '</code></td>' +
          '<td class="text-right">' + esc(r.qty) + '</td><td>' + esc(r.penerima) + '</td></tr>');
      });
      $('#pt-info-tabel').text(d.total ? 'Menampilkan ' + d.dari + '-' + d.sampai + ' dari ' + d.total : '0 penerimaan');
      gambarHalaman(d.halaman, d.jml_hal);
    }).fail(jsonGagal);
  }
  function gambarHalaman(sekarang, jumlah) {
    var $p = $('#pt-halaman').empty();
    function item(label, hal, nonaktif, aktif, aria) {
      $p.append('<li class="' + (nonaktif ? 'disabled ' : '') + (aktif ? 'active' : '') + '"><a href="javascript:void(0)" class="nojs pt-hal" data-hal="' + hal + '"' +
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
  $root.on('click', '.pt-hal', function (e) {
    e.preventDefault(); e.stopPropagation();
    if ($(this).parent().hasClass('disabled')) { return; }
    F.halaman = Number($(this).data('hal'));
    muatRiwayat();
  });
  $root.on('change', '#pt-per', function () { F.per = Number($(this).val()); F.halaman = 1; muatRiwayat(); });
  $root.on('input', '#pt-cari', function () {
    F.q = $.trim($(this).val());
    F.halaman = 1;
    clearTimeout(timerCari);
    timerCari = setTimeout(muatRiwayat, 200);
  });

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
    $('#pt-dp-label').text(labelDp());
    $('#pt-dp-btn').toggleClass('act', !!(F.dari || F.sampai)).attr('aria-expanded', DP.buka);
  }
  function bulanKal(y, m) {
    var awal = (new Date(Date.UTC(y, m, 1)).getUTCDay() + 6) % 7, n = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
    var h = '<div class="pt-mo"><div class="pt-mt">' + BULAN_PENUH[m] + ' ' + y + '</div><div class="pt-g">';
    $.each(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'], function (i, w) { h += '<span class="pt-w">' + w + '</span>'; });
    for (var i = 0; i < awal; i++) { h += '<span class="pt-d blank"></span>'; }
    for (var d = 1; d <= n; d++) {
      var s = y + '-' + pad2(m + 1) + '-' + pad2(d), dis = s > HARI_INI;
      h += '<button type="button" class="pt-d' + (dis ? ' dis' : '') + (s === HARI_INI ? ' td' : '') + '" data-d="' + s + '"' + (dis ? ' disabled' : '') + '>' + d + '</button>';
    }
    return h + '</div></div>';
  }
  function bulanMaks() {
    return Number(HARI_INI.slice(0, 4)) * 12 + Number(HARI_INI.slice(5, 7)) - 1 - (DP.dua ? 1 : 0);
  }
  function htmlDp() {
    var k = DP.y * 12 + DP.m, y2 = DP.m === 11 ? DP.y + 1 : DP.y, m2 = DP.m === 11 ? 0 : DP.m + 1;
    var side = '';
    $.each(PRESET, function (i, p) { side += '<button type="button" class="pt-pre' + (F.pre === p[0] ? ' on' : '') + '" data-pre="' + p[0] + '">' + p[1] + '</button>'; });
    return '<div class="pt-side">' + side + '</div><div class="pt-main">' +
      '<div class="pt-nav"><button type="button" class="pt-nav-p" aria-label="Bulan sebelumnya">&#8249;</button>' +
      '<button type="button" class="pt-nav-n" aria-label="Bulan berikutnya"' + (k >= bulanMaks() ? ' disabled' : '') + '>&#8250;</button></div>' +
      '<div class="pt-cal">' + bulanKal(DP.y, DP.m) + (DP.dua ? bulanKal(y2, m2) : '') + '</div>' +
      '<div class="pt-ft"><span class="pt-sum" id="pt-sum"></span><span><button type="button" class="btn btn-default pt-batal">Batal</button> ' +
      '<button type="button" class="btn btn-primary pt-terapkan" id="pt-terapkan">Terapkan</button></span></div></div>';
  }
  function catDp() {
    var a = DP.a, e = DP.b || (a && DP.hov) || null, lo = a, hi = e;
    if (a && e && e < a) { lo = e; hi = a; }
    $('#pt-dp-pop .pt-d[data-d]').each(function () {
      var d = this.getAttribute('data-d');
      $(this).toggleClass('st', !!lo && d === lo).toggleClass('en', !!hi && d === hi).toggleClass('in', !!lo && !!hi && d > lo && d < hi);
    });
    $('#pt-sum').html(!a ? 'Pilih tanggal awal' : (!DP.b ? '<b>' + fmtD(a) + '</b> - pilih tanggal akhir' : '<b>' + fmtD(lo) + '</b> - <b>' + fmtD(hi) + '</b>'));
    $('#pt-terapkan').prop('disabled', !a);
  }
  function gambarDp() { $('#pt-dp-pop').html(htmlDp()); catDp(); }
  function tutupDp() { DP.buka = false; $('#pt-dp-pop').addClass('hidden').empty(); segarkanTombolDp(); }
  function terapkanFilter() { F.halaman = 1; tutupDp(); segarkanTombolDp(); muatRiwayat(); }

  $root.on('click', '#pt-dp-btn', function (e) {
    e.stopPropagation();
    if (DP.buka) { tutupDp(); return; }
    DP.buka = true; DP.dua = window.innerWidth >= 900;
    DP.a = F.dari || null; DP.b = F.dari ? (F.sampai || F.dari) : null; DP.hov = null;
    var ref = F.dari || HARI_INI;
    var k = Math.min(Number(ref.slice(0, 4)) * 12 + Number(ref.slice(5, 7)) - 1, bulanMaks());
    DP.y = Math.floor(k / 12); DP.m = k % 12;
    $('#pt-dp-pop').removeClass('hidden').css('left', ''); gambarDp(); segarkanTombolDp();
    // Kalau popover melewati tepi kanan layar, geser ke kiri secukupnya.
    var lebih = $('#pt-dp-pop')[0].getBoundingClientRect().right - (window.innerWidth - 12);
    if (lebih > 0 && window.innerWidth > 640) { $('#pt-dp-pop').css('left', -lebih + 'px'); }
  });
  $root.on('click', '.pt-pre', function () {
    var k = String($(this).data('pre')), r = rentangPreset(k);
    F.pre = k; F.dari = r[0]; F.sampai = r[1]; terapkanFilter();
  });
  $root.on('click', '.pt-nav-p, .pt-nav-n', function () {
    var k = DP.y * 12 + DP.m + ($(this).hasClass('pt-nav-p') ? -1 : 1);
    if (k > bulanMaks()) { return; }
    DP.y = Math.floor(k / 12); DP.m = k % 12; gambarDp();
  });
  $root.on('click', '#pt-dp-pop .pt-d[data-d]', function () {
    var d = this.getAttribute('data-d');
    if (!DP.a || DP.b) { DP.a = d; DP.b = null; } else if (d < DP.a) { DP.b = DP.a; DP.a = d; } else { DP.b = d; }
    DP.hov = null; catDp();
  });
  $root.on('mouseover', '#pt-dp-pop .pt-d[data-d]', function () {
    var d = this.getAttribute('data-d');
    if (DP.a && !DP.b && DP.hov !== d) { DP.hov = d; catDp(); }
  });
  $root.on('click', '.pt-terapkan', function () {
    if (!DP.a) { return; }
    F.dari = DP.a; F.sampai = DP.b || DP.a; F.pre = 'kustom'; terapkanFilter();
  });
  $root.on('click', '.pt-batal', tutupDp);
  // Klik di luar / Esc menutup. Dipasang di document, jadi pakai namespace supaya
  // tidak menumpuk tiap view dimuat ulang oleh SPA.
  $(document).off('click.ptdp keydown.ptdp').on('click.ptdp', function (e) {
    if (DP.buka && !$(e.target).closest('#pt-dp').length) { tutupDp(); }
  }).on('keydown.ptdp', function (e) {
    if (e.key === 'Escape') {
      if (!$('#pt-modal').hasClass('hidden')) { $('#pt-modal').addClass('hidden'); antri = null; }
      else if (DP.buka) { tutupDp(); }
    }
  });

  $root.on('click', '#pt-reset', function () {
    F.q = ''; F.dari = ''; F.sampai = ''; F.pre = 'semua'; F.halaman = 1;
    $('#pt-cari').val(''); tutupDp(); muatRiwayat();
  });

  segarkanTombolDp();
  muatMenunggu();
  muatRiwayat();
})();
</script>
<style>
  /* Font template iresis kecil (12-13px); menu ini dibaca dari jauh, jadi dibesarkan (sama dengan Daftar SKU Bonus dan Transfer Stok). */
  #pt-root .panel-title { font-size: 18px; }
  #pt-root label { font-size: 15px; font-weight: 600; color: #555; }
  #pt-root .form-control, #pt-root .btn { height: 44px; font-size: 18px; }
  #pt-root .btn-primary { padding: 0 22px; }
  #pt-root .alert { font-size: 17px; }
  #pt-root .table > thead > tr > th { font-size: 16px; padding: 12px 14px; }
  #pt-root .table > tbody > tr > td { font-size: 18px; padding: 10px 14px; vertical-align: middle; }
  #pt-root .table code { font-size: 17px; font-weight: 600; }
  #pt-root #pt-info-tabel { font-size: 16px; }
  #pt-root .pagination > li > a { font-size: 16px; padding: 8px 14px; }
  #pt-root .pt-ic { width: 26px; height: 26px; border-radius: 50%; border: 1px solid #bbb; background: #fff; color: #777; font: 600 14px/1 monospace; cursor: pointer; padding: 0; margin-left: 8px; vertical-align: middle; }
  #pt-root .pt-ic:hover, #pt-root .pt-ic[aria-expanded="true"] { color: #337ab7; border-color: #337ab7; background: #eaf2fa; }

  #pt-root .pt-card { margin-bottom: 12px; }
  #pt-root .pt-card-h { font-size: 20px; margin-bottom: 10px; }
  #pt-root .pt-card-h .label { font-size: 14px; padding: 5px 10px; }
  #pt-root .pt-kpi { display: inline-block; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 16px; margin-bottom: 12px; background: #f7f8fa; }
  #pt-root .pt-kpi small { display: block; font-size: 14px; color: #666; }
  #pt-root .pt-kpi b { font-size: 26px; }
  #pt-root .pt-isi:after { content: ""; display: table; clear: both; }
  #pt-root .pt-pv { margin-top: 10px; font-size: 17px; min-height: 4px; }
  #pt-root .pt-pv .label { font-size: 14px; padding: 4px 9px; }
  #pt-root .pt-kosong { font-size: 18px; padding: 14px 0; }
  #pt-root .pt-sub { font-size: 20px; font-weight: 600; margin: 22px 0 10px; }

  #pt-root .pt-filter { clear: both; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 14px 0; }
  #pt-root .pt-cari { width: 320px; max-width: 100%; border-radius: 22px; padding-left: 18px; }
  #pt-root .pt-dp { position: relative; }
  #pt-root .pt-dp-btn { height: 44px; padding: 0 18px; font-size: 17px; border: 1px solid #ccc; border-radius: 22px; background: #fff; cursor: pointer; }
  #pt-root .pt-dp-btn:hover, #pt-root .pt-dp-btn[aria-expanded="true"] { border-color: #337ab7; }
  #pt-root .pt-dp-btn.act { background: #eaf2fa; border-color: #337ab7; color: #1f5f99; font-weight: 600; }
  #pt-root .pt-dp-pop { position: absolute; left: 0; top: 52px; z-index: 1000; display: flex; background: #fff; border: 1px solid #d1d5db; border-radius: 12px; box-shadow: 0 12px 32px rgba(0,0,0,.22); overflow: hidden; }
  #pt-root .pt-dp-pop.hidden { display: none; }
  #pt-root .pt-side { display: flex; flex-direction: column; gap: 2px; padding: 10px; border-right: 1px solid #e5e7eb; min-width: 190px; background: #f7f8fa; }
  #pt-root .pt-pre { border: 0; background: none; text-align: left; padding: 9px 12px; border-radius: 6px; cursor: pointer; font-size: 16px; height: auto; color: #222; }
  #pt-root .pt-pre:hover { background: #eaf2fa; }
  #pt-root .pt-pre.on { background: #337ab7; color: #fff; font-weight: 600; }
  #pt-root .pt-main { padding: 12px 16px; }
  #pt-root .pt-nav { display: flex; justify-content: space-between; margin-bottom: 4px; }
  #pt-root .pt-nav button { width: 34px; height: 34px; border-radius: 50%; border: 1px solid #ccc; background: #fff; cursor: pointer; font-size: 20px; line-height: 1; padding: 0; }
  #pt-root .pt-nav button:disabled { opacity: .35; cursor: default; }
  #pt-root .pt-cal { display: flex; gap: 24px; }
  #pt-root .pt-mo { width: 294px; }
  #pt-root .pt-mt { text-align: center; font-weight: 600; font-size: 17px; margin: -34px 0 10px; line-height: 34px; pointer-events: none; }
  #pt-root .pt-g { display: grid; grid-template-columns: repeat(7, 42px); gap: 2px 0; }
  #pt-root .pt-w { font-size: 13px; color: #888; text-align: center; line-height: 26px; }
  #pt-root .pt-d { height: 40px; border: 0; background: none; cursor: pointer; font-size: 17px; color: #222; border-radius: 0; padding: 0; }
  #pt-root .pt-d:hover:not(.dis):not(.st):not(.en) { background: #eaf2fa; border-radius: 50%; }
  #pt-root .pt-d.in { background: #eaf2fa; }
  #pt-root .pt-d.st, #pt-root .pt-d.en { background: #337ab7; color: #fff; font-weight: 600; }
  #pt-root .pt-d.st { border-radius: 50% 0 0 50%; }
  #pt-root .pt-d.en { border-radius: 0 50% 50% 0; }
  #pt-root .pt-d.st.en { border-radius: 50%; }
  #pt-root .pt-d.td { box-shadow: inset 0 -2px 0 #337ab7; }
  #pt-root .pt-d.dis { color: #aaa; opacity: .45; cursor: default; }
  #pt-root .pt-d.blank { visibility: hidden; }
  #pt-root .pt-ft { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
  #pt-root .pt-sum { font-size: 16px; color: #666; }
  #pt-root .pt-sum b { color: #222; }
  #pt-root .pt-pager { display: flex; justify-content: flex-end; align-items: center; gap: 10px; flex-wrap: wrap; }
  #pt-root .pt-pager select { width: 84px; }
  @media (max-width: 640px) {
    #pt-root .pt-dp-pop { position: fixed; left: 12px; right: 12px; top: auto; bottom: 12px; max-height: 85vh; overflow: auto; flex-direction: column; }
    #pt-root .pt-side { flex-direction: row; flex-wrap: wrap; border-right: 0; border-bottom: 1px solid #e5e7eb; }
    #pt-root .pt-mo { width: 100%; }
    #pt-root .pt-g { grid-template-columns: repeat(7, 1fr); }
  }

  #pt-root .pt-modal { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 100000; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; padding: 16px; }
  #pt-root .pt-modal.hidden { display: none; }
  #pt-root .pt-modal-box { background: #fff; border-radius: 8px; padding: 22px 26px; width: 100%; max-width: 520px; font-size: 17px; }
  #pt-root .pt-modal-box h3 { margin: 0 0 12px; font-size: 22px; }
  #pt-root .pt-ringkas { width: 100%; }
  #pt-root .pt-ringkas th { width: 170px; padding: 5px 0; color: #666; font-weight: 600; vertical-align: top; }
  #pt-root .pt-ringkas td { padding: 5px 0; font-weight: 600; }
  #pt-root .pt-ringkas .label { font-size: 14px; padding: 4px 9px; }
</style>
