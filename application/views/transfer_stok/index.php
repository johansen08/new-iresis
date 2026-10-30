<?php
// View menu TIM PURCHASING -> Transfer Stok. Dimuat lewat AJAX (SPA) oleh
// plugins.js: semua handler diikat ke #ts-root supaya ikut hilang saat user
// pindah menu. Tanpa <form> sengaja -- submit form dicegat handler global.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Transfer Stok</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<div id="ts-root" data-hari-ini="<?= htmlspecialchars($hari_ini, ENT_QUOTES) ?>">
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Transfer Stok</strong>
            <button type="button" id="ts-ic" class="ts-ic" aria-label="Info halaman" aria-expanded="false">i</button>
          </h3>
        </div>
        <div class="panel-body">
          <div id="ts-info" class="alert alert-info hidden">
            <p>Purchasing mencatat pinset AKS27-RDM yang dikirim ke lokasi packer. Saldo packing belum berubah sampai packer menerima dan memasukkan qty real.</p>
            <p style="margin-bottom:0;">Status <b>Dalam perjalanan</b> berlaku sampai packer menerima. Satu transfer diterima sekali, lalu <b>Selesai</b>; kurang atau lebihnya tampil di kolom Selisih. Jam hanya terisi bila tanggal transfer adalah hari ini.</p>
          </div>

          <div class="row ts-buat">
            <div class="col-md-2 col-sm-6 col-xs-12">
              <label for="ts-tgl">Tanggal transfer</label>
              <input type="date" id="ts-tgl" class="form-control" value="<?= htmlspecialchars($hari_ini, ENT_QUOTES) ?>" max="<?= htmlspecialchars($hari_ini, ENT_QUOTES) ?>" />
            </div>
            <div class="col-md-2 col-sm-6 col-xs-12">
              <label for="ts-qty">Qty kirim</label>
              <input type="number" id="ts-qty" class="form-control" min="1" step="1" placeholder="0" />
            </div>
            <div class="col-md-6 col-sm-8 col-xs-12">
              <label for="ts-ket">Keterangan</label>
              <input type="text" id="ts-ket" class="form-control" maxlength="255" placeholder="Opsional" autocomplete="off" />
            </div>
            <div class="col-md-2 col-sm-4 col-xs-12">
              <label>&nbsp;</label>
              <button type="button" id="ts-btn-buat" class="btn btn-primary btn-block">Buat transfer</button>
            </div>
          </div>

          <div id="ts-pesan" class="alert hidden" role="status" style="margin:12px 0;"></div>

          <div class="ts-filter">
            <input type="text" id="ts-cari" class="form-control ts-cari" placeholder="Cari no, keterangan, status, qty" autocomplete="off" />
            <div id="ts-dp" class="ts-dp">
              <button type="button" id="ts-dp-btn" class="ts-dp-btn" aria-haspopup="dialog" aria-expanded="false">
                <i class="fa fa-calendar"></i> <span id="ts-dp-label">Semua tanggal</span> <i class="fa fa-caret-down"></i>
              </button>
              <div id="ts-dp-pop" class="ts-dp-pop hidden" role="dialog" aria-label="Pilih rentang tanggal"></div>
            </div>
            <button type="button" id="ts-reset" class="btn btn-default">Reset filter</button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-hover" id="ts-tabel" style="margin-bottom:10px;">
              <thead>
                <tr>
                  <th>No</th><th>Tgl transfer</th><th>Jam</th>
                  <th class="text-right">Qty kirim</th><th class="text-right">Diterima</th><th class="text-right">Selisih</th>
                  <th>Status</th><th>Keterangan</th>
                </tr>
              </thead>
              <tbody><tr><td colspan="8" class="text-muted text-center">Memuat…</td></tr></tbody>
            </table>
          </div>

          <div class="row">
            <div class="col-md-4 col-xs-12 text-muted" id="ts-info-tabel" style="padding-top:7px;"></div>
            <div class="col-md-8 col-xs-12 text-right ts-pager">
              <label for="ts-per" class="text-muted">Per halaman</label>
              <select id="ts-per" class="form-control">
                <option>10</option><option>20</option><option>50</option>
              </select>
              <ul class="pagination" id="ts-halaman" style="margin:0;"></ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="ts-modal" class="ts-modal hidden" role="dialog" aria-modal="true" aria-labelledby="ts-modal-j">
    <div class="ts-modal-box">
      <h3 id="ts-modal-j">Simpan transfer ini?</h3>
      <p>Transfer berikut akan disimpan:</p>
      <table class="ts-ringkas">
        <tr><th>Tanggal transfer</th><td id="ts-m-tgl"></td></tr>
        <tr><th>Jam</th><td id="ts-m-jam"></td></tr>
        <tr><th>Qty kirim</th><td id="ts-m-qty"></td></tr>
        <tr><th>Keterangan</th><td id="ts-m-ket"></td></tr>
      </table>
      <p class="text-muted" style="margin:12px 0 0;">Saldo packing belum berubah. Saldo baru bertambah saat packer menerima dan memasukkan qty real.</p>
      <div class="text-right" style="margin-top:18px;">
        <button type="button" id="ts-m-batal" class="btn btn-default">Batal</button>
        <button type="button" id="ts-m-ok" class="btn btn-primary">Ya, simpan transfer</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var $root = $('#ts-root');
  var HARI_INI = String($root.data('hariIni'));
  var URL = { data: 'purchasing/transfer-stok/data', simpan: 'purchasing/transfer-stok/simpan' };
  var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  var BULAN_PENUH = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  var PRESET = [['semua', 'Semua tanggal'], ['hari', 'Hari ini'], ['kemarin', 'Kemarin'], ['7', '7 hari terakhir'],
                ['30', '30 hari terakhir'], ['bulan', 'Bulan ini'], ['bulanlalu', 'Bulan lalu']];
  var F = { q: '', dari: '', sampai: '', pre: 'semua', per: 10, halaman: 1 };
  var reqData = 0, timerCari = null;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function pad2(n) { return (n < 10 ? '0' : '') + n; }
  function pesan(tipe, teks) {
    $('#ts-pesan').removeClass('hidden alert-success alert-danger alert-warning').addClass('alert-' + tipe).text(teks);
  }
  function jsonGagal(xhr) {
    // Bukan JSON = ada output nyasar (warning PHP / halaman error CI).
    pesan('danger', 'Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200));
  }

  // ---------- tanggal (UTC supaya tidak bergeser zona waktu) ----------
  function iso(ms) { return new Date(ms).toISOString().slice(0, 10); }
  function addD(s, n) { return iso(Date.parse(s) + n * 864e5); }
  function fmtD(s) { return Number(s.slice(8)) + ' ' + BULAN[Number(s.slice(5, 7)) - 1] + ' ' + s.slice(0, 4); }
  function fmtPendek(s) { return Number(s.slice(8)) + ' ' + BULAN[Number(s.slice(5, 7)) - 1]; }

  // ---------- info ----------
  $root.on('click', '#ts-ic', function () {
    var $i = $('#ts-info').toggleClass('hidden');
    $(this).attr('aria-expanded', !$i.hasClass('hidden'));
  });

  // ---------- tabel + pagination ----------
  function muat() {
    var no = ++reqData;
    $.getJSON(URL.data, { q: F.q, dari: F.dari, sampai: F.sampai, per: F.per, halaman: F.halaman }).done(function (res) {
      if (no !== reqData) { return; } // balasan lama, abaikan
      if (Number(res.code) !== 200 || !res.data) { pesan('danger', res.message || 'Gagal memuat daftar.'); return; }
      var d = res.data;
      F.halaman = d.halaman;
      var $tb = $('#ts-tabel tbody').empty();
      if (!d.rows.length) {
        $tb.append('<tr><td colspan="8" class="text-muted text-center">Tidak ada transfer yang cocok dengan filter.</td></tr>');
      }
      $.each(d.rows, function (i, r) {
        var selesai = Number(r.selesai) === 1;
        var sel = r.selisih === null ? '' : (Number(r.selisih) > 0 ? '+' + r.selisih : r.selisih);
        $tb.append('<tr><td><code>' + esc(r.no) + '</code></td><td>' + esc(fmtD(r.tanggal)) + '</td><td><code>' + esc(r.jam || '') + '</code></td>' +
          '<td class="text-right">' + esc(r.qty_kirim) + '</td><td class="text-right">' + esc(r.qty_terima) + '</td><td class="text-right">' + esc(sel) + '</td>' +
          '<td><span class="label label-' + (selesai ? 'success' : 'default') + '">' + (selesai ? 'Selesai' : 'Dalam perjalanan') + '</span></td>' +
          '<td>' + esc(r.keterangan) + '</td></tr>');
      });
      $('#ts-info-tabel').text(d.total ? 'Menampilkan ' + d.dari + '-' + d.sampai + ' dari ' + d.total : '0 transfer');
      gambarHalaman(d.halaman, d.jml_hal);
    }).fail(jsonGagal);
  }
  function gambarHalaman(sekarang, jumlah) {
    var $p = $('#ts-halaman').empty();
    function item(label, hal, nonaktif, aktif, aria) {
      $p.append('<li class="' + (nonaktif ? 'disabled ' : '') + (aktif ? 'active' : '') + '"><a href="javascript:void(0)" class="nojs ts-hal" data-hal="' + hal + '"' +
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
  $root.on('click', '.ts-hal', function (e) {
    e.preventDefault(); e.stopPropagation();
    if ($(this).parent().hasClass('disabled')) { return; }
    F.halaman = Number($(this).data('hal'));
    muat();
  });
  $root.on('change', '#ts-per', function () { F.per = Number($(this).val()); F.halaman = 1; muat(); });

  // ---------- cari: tabel mengikuti ketikan ----------
  $root.on('input', '#ts-cari', function () {
    F.q = $.trim($(this).val());
    F.halaman = 1;
    clearTimeout(timerCari);
    timerCari = setTimeout(muat, 200);
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
    $('#ts-dp-label').text(labelDp());
    $('#ts-dp-btn').toggleClass('act', !!(F.dari || F.sampai)).attr('aria-expanded', DP.buka);
  }
  function bulanKal(y, m) {
    var awal = (new Date(Date.UTC(y, m, 1)).getUTCDay() + 6) % 7, n = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
    var h = '<div class="ts-mo"><div class="ts-mt">' + BULAN_PENUH[m] + ' ' + y + '</div><div class="ts-g">';
    $.each(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'], function (i, w) { h += '<span class="ts-w">' + w + '</span>'; });
    for (var i = 0; i < awal; i++) { h += '<span class="ts-d blank"></span>'; }
    for (var d = 1; d <= n; d++) {
      var s = y + '-' + pad2(m + 1) + '-' + pad2(d), dis = s > HARI_INI;
      h += '<button type="button" class="ts-d' + (dis ? ' dis' : '') + (s === HARI_INI ? ' td' : '') + '" data-d="' + s + '"' + (dis ? ' disabled' : '') + '>' + d + '</button>';
    }
    return h + '</div></div>';
  }
  function bulanMaks() {
    return Number(HARI_INI.slice(0, 4)) * 12 + Number(HARI_INI.slice(5, 7)) - 1 - (DP.dua ? 1 : 0);
  }
  function htmlDp() {
    var k = DP.y * 12 + DP.m, y2 = DP.m === 11 ? DP.y + 1 : DP.y, m2 = DP.m === 11 ? 0 : DP.m + 1;
    var side = '';
    $.each(PRESET, function (i, p) { side += '<button type="button" class="ts-pre' + (F.pre === p[0] ? ' on' : '') + '" data-pre="' + p[0] + '">' + p[1] + '</button>'; });
    return '<div class="ts-side">' + side + '</div><div class="ts-main">' +
      '<div class="ts-nav"><button type="button" class="ts-nav-p" aria-label="Bulan sebelumnya">&#8249;</button>' +
      '<button type="button" class="ts-nav-n" aria-label="Bulan berikutnya"' + (k >= bulanMaks() ? ' disabled' : '') + '>&#8250;</button></div>' +
      '<div class="ts-cal">' + bulanKal(DP.y, DP.m) + (DP.dua ? bulanKal(y2, m2) : '') + '</div>' +
      '<div class="ts-ft"><span class="ts-sum" id="ts-sum"></span><span><button type="button" class="btn btn-default ts-batal">Batal</button> ' +
      '<button type="button" class="btn btn-primary ts-terapkan" id="ts-terapkan">Terapkan</button></span></div></div>';
  }
  function catDp() {
    var a = DP.a, e = DP.b || (a && DP.hov) || null, lo = a, hi = e;
    if (a && e && e < a) { lo = e; hi = a; }
    $('#ts-dp-pop .ts-d[data-d]').each(function () {
      var d = this.getAttribute('data-d');
      $(this).toggleClass('st', !!lo && d === lo).toggleClass('en', !!hi && d === hi).toggleClass('in', !!lo && !!hi && d > lo && d < hi);
    });
    $('#ts-sum').html(!a ? 'Pilih tanggal awal' : (!DP.b ? '<b>' + fmtD(a) + '</b> - pilih tanggal akhir' : '<b>' + fmtD(lo) + '</b> - <b>' + fmtD(hi) + '</b>'));
    $('#ts-terapkan').prop('disabled', !a);
  }
  function gambarDp() { $('#ts-dp-pop').html(htmlDp()); catDp(); }
  function tutupDp() { DP.buka = false; $('#ts-dp-pop').addClass('hidden').empty(); segarkanTombolDp(); }
  function terapkanFilter() { F.halaman = 1; tutupDp(); segarkanTombolDp(); muat(); }

  $root.on('click', '#ts-dp-btn', function (e) {
    e.stopPropagation();
    if (DP.buka) { tutupDp(); return; }
    DP.buka = true; DP.dua = window.innerWidth >= 900;
    DP.a = F.dari || null; DP.b = F.dari ? (F.sampai || F.dari) : null; DP.hov = null;
    var ref = F.dari || HARI_INI;
    var k = Math.min(Number(ref.slice(0, 4)) * 12 + Number(ref.slice(5, 7)) - 1, bulanMaks());
    DP.y = Math.floor(k / 12); DP.m = k % 12;
    $('#ts-dp-pop').removeClass('hidden').css('left', ''); gambarDp(); segarkanTombolDp();
    // Kalau popover melewati tepi kanan layar, geser ke kiri secukupnya.
    var lebih = $('#ts-dp-pop')[0].getBoundingClientRect().right - (window.innerWidth - 12);
    if (lebih > 0 && window.innerWidth > 640) { $('#ts-dp-pop').css('left', -lebih + 'px'); }
  });
  $root.on('click', '.ts-pre', function () {
    var k = String($(this).data('pre')), r = rentangPreset(k);
    F.pre = k; F.dari = r[0]; F.sampai = r[1]; terapkanFilter();
  });
  $root.on('click', '.ts-nav-p, .ts-nav-n', function () {
    var k = DP.y * 12 + DP.m + ($(this).hasClass('ts-nav-p') ? -1 : 1);
    if (k > bulanMaks()) { return; }
    DP.y = Math.floor(k / 12); DP.m = k % 12; gambarDp();
  });
  $root.on('click', '#ts-dp-pop .ts-d[data-d]', function () {
    var d = this.getAttribute('data-d');
    if (!DP.a || DP.b) { DP.a = d; DP.b = null; } else if (d < DP.a) { DP.b = DP.a; DP.a = d; } else { DP.b = d; }
    DP.hov = null; catDp();
  });
  $root.on('mouseover', '#ts-dp-pop .ts-d[data-d]', function () {
    var d = this.getAttribute('data-d');
    if (DP.a && !DP.b && DP.hov !== d) { DP.hov = d; catDp(); }
  });
  $root.on('click', '.ts-terapkan', function () {
    if (!DP.a) { return; }
    F.dari = DP.a; F.sampai = DP.b || DP.a; F.pre = 'kustom'; terapkanFilter();
  });
  $root.on('click', '.ts-batal', tutupDp);
  // Klik di luar / Esc menutup. Dipasang di document, jadi pakai namespace supaya
  // tidak menumpuk tiap view dimuat ulang oleh SPA.
  $(document).off('click.tsdp keydown.tsdp').on('click.tsdp', function (e) {
    if (DP.buka && !$(e.target).closest('#ts-dp').length) { tutupDp(); }
  }).on('keydown.tsdp', function (e) {
    if (e.key === 'Escape') {
      if (!$('#ts-modal').hasClass('hidden')) { $('#ts-modal').addClass('hidden'); }
      else if (DP.buka) { tutupDp(); }
    }
  });

  $root.on('click', '#ts-reset', function () {
    F.q = ''; F.dari = ''; F.sampai = ''; F.pre = 'semua'; F.halaman = 1;
    $('#ts-cari').val(''); tutupDp(); muat();
  });

  // ---------- buat transfer: popup konfirmasi dulu, baru simpan ----------
  $root.on('click', '#ts-btn-buat', function () {
    var tgl = $('#ts-tgl').val(), qty = $.trim($('#ts-qty').val()), ket = $.trim($('#ts-ket').val());
    if (!/^\d{4}-\d{2}-\d{2}$/.test(tgl)) { pesan('danger', 'Tanggal transfer wajib diisi.'); return; }
    if (tgl > HARI_INI) { pesan('danger', 'Tanggal transfer tidak boleh lebih dari hari ini.'); return; }
    if (!/^\d+$/.test(qty) || Number(qty) <= 0) { pesan('danger', 'Qty kirim harus bilangan bulat lebih dari 0.'); return; }
    if (Number(qty) > 1000000) { pesan('danger', 'Qty kirim terlalu besar. Cek lagi angkanya.'); return; }
    $('#ts-m-tgl').text(fmtD(tgl));
    $('#ts-m-jam').text(tgl === HARI_INI ? 'otomatis saat disimpan' : 'dikosongkan');
    $('#ts-m-qty').text(Number(qty) + ' pcs');
    $('#ts-m-ket').text(ket || 'transfer stok');
    $('#ts-modal').removeClass('hidden');
    $('#ts-m-ok').focus();
  });
  $root.on('click', '#ts-m-batal', function () { $('#ts-modal').addClass('hidden'); });
  $root.on('click', '#ts-modal', function (e) { if (e.target === this) { $(this).addClass('hidden'); } });
  $root.on('click', '#ts-m-ok', function () {
    var $b = $(this).prop('disabled', true);
    $.post(URL.simpan, { tanggal: $('#ts-tgl').val(), qty: $.trim($('#ts-qty').val()), keterangan: $.trim($('#ts-ket').val()) }, null, 'json').done(function (res) {
      $('#ts-modal').addClass('hidden');
      if (Number(res.code) === 200) {
        pesan('success', res.message);
        $('#ts-qty').val(''); $('#ts-ket').val('');
        F.halaman = 1;
      } else {
        pesan('danger', res.message);
      }
      muat();
    }).fail(function (xhr) { $('#ts-modal').addClass('hidden'); jsonGagal(xhr); })
      .always(function () { $b.prop('disabled', false); });
  });

  segarkanTombolDp();
  muat();
})();
</script>
<style>
  /* Font template iresis kecil (12-13px); menu ini dibaca dari jauh, jadi dibesarkan (sama dengan Daftar SKU Bonus). */
  #ts-root .panel-title { font-size: 18px; }
  #ts-root label { font-size: 15px; font-weight: 600; color: #555; }
  #ts-root .form-control, #ts-root .btn { height: 44px; font-size: 18px; }
  #ts-root .btn-primary { padding: 0 22px; }
  #ts-root .ts-buat .btn { margin-top: 0; }
  #ts-root .alert { font-size: 17px; }
  #ts-root .table > thead > tr > th { font-size: 16px; padding: 12px 14px; }
  #ts-root .table > tbody > tr > td { font-size: 18px; padding: 10px 14px; vertical-align: middle; }
  #ts-root .table code { font-size: 17px; font-weight: 600; }
  #ts-root .table .label { font-size: 14px; padding: 5px 10px; }
  #ts-root #ts-info-tabel { font-size: 16px; }
  #ts-root .pagination > li > a { font-size: 16px; padding: 8px 14px; }
  #ts-root .ts-ic { width: 26px; height: 26px; border-radius: 50%; border: 1px solid #bbb; background: #fff; color: #777; font: 600 14px/1 monospace; cursor: pointer; padding: 0; margin-left: 8px; vertical-align: middle; }
  #ts-root .ts-ic:hover, #ts-root .ts-ic[aria-expanded="true"] { color: #337ab7; border-color: #337ab7; background: #eaf2fa; }

  #ts-root .ts-buat:after { content: ""; display: table; clear: both; }
  #ts-root .ts-filter { clear: both; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 14px 0; }
  #ts-root .ts-cari { width: 320px; max-width: 100%; border-radius: 22px; padding-left: 18px; }
  #ts-root .ts-dp { position: relative; }
  #ts-root .ts-dp-btn { height: 44px; padding: 0 18px; font-size: 17px; border: 1px solid #ccc; border-radius: 22px; background: #fff; cursor: pointer; }
  #ts-root .ts-dp-btn:hover, #ts-root .ts-dp-btn[aria-expanded="true"] { border-color: #337ab7; }
  #ts-root .ts-dp-btn.act { background: #eaf2fa; border-color: #337ab7; color: #1f5f99; font-weight: 600; }
  #ts-root .ts-dp-pop { position: absolute; left: 0; top: 52px; z-index: 1000; display: flex; background: #fff; border: 1px solid #d1d5db; border-radius: 12px; box-shadow: 0 12px 32px rgba(0,0,0,.22); overflow: hidden; }
  #ts-root .ts-dp-pop.hidden { display: none; }  #ts-root .ts-side { display: flex; flex-direction: column; gap: 2px; padding: 10px; border-right: 1px solid #e5e7eb; min-width: 190px; background: #f7f8fa; }
  #ts-root .ts-pre { border: 0; background: none; text-align: left; padding: 9px 12px; border-radius: 6px; cursor: pointer; font-size: 16px; height: auto; color: #222; }
  #ts-root .ts-pre:hover { background: #eaf2fa; }
  #ts-root .ts-pre.on { background: #337ab7; color: #fff; font-weight: 600; }
  #ts-root .ts-main { padding: 12px 16px; }
  #ts-root .ts-nav { display: flex; justify-content: space-between; margin-bottom: 4px; }
  #ts-root .ts-nav button { width: 34px; height: 34px; border-radius: 50%; border: 1px solid #ccc; background: #fff; cursor: pointer; font-size: 20px; line-height: 1; padding: 0; }
  #ts-root .ts-nav button:disabled { opacity: .35; cursor: default; }
  #ts-root .ts-cal { display: flex; gap: 24px; }
  #ts-root .ts-mo { width: 294px; }
  #ts-root .ts-mt { text-align: center; font-weight: 600; font-size: 17px; margin: -34px 0 10px; line-height: 34px; pointer-events: none; }
  #ts-root .ts-g { display: grid; grid-template-columns: repeat(7, 42px); gap: 2px 0; }
  #ts-root .ts-w { font-size: 13px; color: #888; text-align: center; line-height: 26px; }
  #ts-root .ts-d { height: 40px; border: 0; background: none; cursor: pointer; font-size: 17px; color: #222; border-radius: 0; padding: 0; }
  #ts-root .ts-d:hover:not(.dis):not(.st):not(.en) { background: #eaf2fa; border-radius: 50%; }
  #ts-root .ts-d.in { background: #eaf2fa; }
  #ts-root .ts-d.st, #ts-root .ts-d.en { background: #337ab7; color: #fff; font-weight: 600; }
  #ts-root .ts-d.st { border-radius: 50% 0 0 50%; }
  #ts-root .ts-d.en { border-radius: 0 50% 50% 0; }
  #ts-root .ts-d.st.en { border-radius: 50%; }
  #ts-root .ts-d.td { box-shadow: inset 0 -2px 0 #337ab7; }
  #ts-root .ts-d.dis { color: #aaa; opacity: .45; cursor: default; }
  #ts-root .ts-d.blank { visibility: hidden; }
  #ts-root .ts-ft { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
  #ts-root .ts-sum { font-size: 16px; color: #666; }
  #ts-root .ts-sum b { color: #222; }
  #ts-root .ts-pager { display: flex; justify-content: flex-end; align-items: center; gap: 10px; flex-wrap: wrap; }
  #ts-root .ts-pager select { width: 84px; }
  @media (max-width: 640px) {
    #ts-root .ts-dp-pop { position: fixed; left: 12px; right: 12px; top: auto; bottom: 12px; max-height: 85vh; overflow: auto; flex-direction: column; }
    #ts-root .ts-side { flex-direction: row; flex-wrap: wrap; border-right: 0; border-bottom: 1px solid #e5e7eb; }
    #ts-root .ts-mo { width: 100%; }
    #ts-root .ts-g { grid-template-columns: repeat(7, 1fr); }
  }

  #ts-root .ts-modal { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 100000; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; padding: 16px; }
  #ts-root .ts-modal.hidden { display: none; }
  #ts-root .ts-modal-box { background: #fff; border-radius: 8px; padding: 22px 26px; width: 100%; max-width: 520px; font-size: 17px; }
  #ts-root .ts-modal-box h3 { margin: 0 0 12px; font-size: 22px; }
  #ts-root .ts-ringkas { width: 100%; }
  #ts-root .ts-ringkas th { width: 170px; padding: 5px 0; color: #666; font-weight: 600; vertical-align: top; }
  #ts-root .ts-ringkas td { padding: 5px 0; font-weight: 600; }
</style>
