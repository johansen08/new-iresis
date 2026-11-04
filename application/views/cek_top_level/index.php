<?php
// View menu TIM CS -> Cek Top Level. Dimuat lewat AJAX (SPA). Semua handler diikat ke #tl-cek
// atau memakai namespace .tlcek di document supaya hilang saat user pindah menu.
// Dua panel: kiri scan resi + daftar SKU (hasil Cek terbuka di bawah barisnya: status stok top level lalu stok live dan
// history transfer dari Jubelio, picker/cek-top-level/jubelio), kanan antrian request hari ini.
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Cek Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl" id="tl-cek"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-cek'), timer = null, seq = 0, tmTerbaru = null;
  var resi = null, buka = null, cekHasil = {}, jub = {}, hs = '', filter = 'DIAJUKAN', baru = '', rows = [];
  var PK = {
    DIAJUKAN: ['Diajukan', 'warn'], DITUGASKAN: ['Diproses', 'info'], SELESAI: ['Selesai', 'ok']
  };

  var URUT = ['DIAJUKAN', 'DITUGASKAN', 'SELESAI'];
  var ARTI_STATUS = 'Diajukan: menunggu CS mencetak.\nDiproses: CS sudah mencetak.\nSelesai: CS sudah memasukkan TTL keluar.';
  var INFO_HALAMAN = 'TIM CS\nAntrian diperbarui otomatis tiap 20 detik.';

  $r.html(
    '<div class="tlc-top">' + T.info(INFO_HALAMAN) + '</div>' +
    '<div class="tlc-grid">' +
    '<section class="tl-panel tlc-panel"><div class="tlc-scanrow"><div class="tl-scanbox">' + T.ic('scan', 22) +
    '<input type="text" id="tl-resi" placeholder="Scan, tempel, atau ketik no. resi" autocomplete="off" autocapitalize="off" spellcheck="false"></div>' +
    '<div class="tl-fmsg" id="tl-resi-msg"></div></div><div id="tl-hasil"></div></section>' +
    '<aside class="tl-panel tlc-panel"><div class="tlc-ph"><h2>Request Hari Ini</h2>' + T.info(ARTI_STATUS) + '</div>' +
    '<div class="tlc-tabs" id="tl-tabs"></div><div class="tlc-q" id="tl-q"></div></aside></div>'
  );

  function fokus() { var i = document.getElementById('tl-resi'); if (i) { i.focus(); i.select(); } }

  /** Ambil resi dari server. Isian dan hasil Cek yang terbuka direset kecuali tetap = true (setelah ajukan/gabung). */
  function muat(v, tetap) {
    v = $.trim(v);
    var $msg = $('#tl-resi-msg');
    if (!tetap) { buka = null; cekHasil = {}; jub = {}; hs = ''; }
    if (!v) { $msg.text(''); resi = null; gambarResi(); return; }
    var n = ++seq;
    T.post('picker/cek-top-level/cari', { q: v }, function (d) {
      if (n !== seq) return; // balasan lama, sudah ada pencarian yang lebih baru
      if (d.ditemukan) {
        resi = d.resi;
        $msg.text('');
        $('#tl-resi').val(resi.noresi);
      } else {
        resi = null;
        $msg.text('Resi ' + v + ' tidak ditemukan.');
      }
      gambarResi();
      fokus();
    }, function (m) { if (n === seq) { $msg.text(m); } });
  }

  function gambarResi() {
    var $h = $('#tl-hasil');
    if (!resi) {
      $h.html('<div class="tlc-empty">' + T.ic('scan', 44) + '<b>Menunggu scan resi</b>Daftar SKU resi akan muncul di sini.</div>');
      return;
    }
    var h = '<div class="tlc-rh"><div class="l"><span class="no">' + T.esc(resi.noresi) + '</span>' +
      (resi.mp ? '<span class="tlc-mp">' + T.esc(resi.mp) + '</span>' : '') + '</div>' + T.pill(resi.items.length + ' SKU') + '</div>';
    if (hs) h += '<div class="tlc-hs">' + T.esc(hs) + '</div>';
    $.each(resi.items, function (i, it) {
      var r = it.request, p = r ? PK[r.status] : null;
      var rak = it.no_rak === 'BELUM DITENTUKAN' ? '<span class="rk none">Rak belum ditentukan</span>' : '<span class="rk">' + T.esc(it.no_rak) + '</span>';
      var ac;
      if (r) {
        ac = T.pill(p[0], p[1]) + '<span class="mono">' + T.esc(r.nomor || r.kode) + '</span>' +
          (r.status === 'SELESAI' ? '<button type="button" class="tl-btn sm" data-cek="' + i + '">' + (buka === i ? 'Tutup' : 'Cek lagi') + '</button>' : '');
      } else {
        ac = '<button type="button" class="tl-btn ' + (buka === i ? '' : 'pri') + '" data-cek="' + i + '">' + (buka === i ? 'Tutup' : 'Cek') + '</button>';
      }
      h += '<div class="tlc-row"><div class="tlc-rm"><div><span class="lbl">SKU</span><span class="sk">' + T.esc(it.sku) + '</span></div>' +
        '<div class="rkw"><span class="lbl">Rak display</span>' + rak + '</div><div class="ac">' + ac + '</div></div>' +
        (buka === i ? hasilCek(i) : '') + '</div>';
    });
    $h.html(h);
  }

  function hasilCek(i) {
    var d = cekHasil[i], tp, judul, sub, tombol = '';
    if (!d) return '';
    if (!d.ada_stok) { tp = 'bad'; judul = 'Tidak ada stok top level'; sub = 'Lapor ke CS bila perlu.'; }
    else if (d.aktif && d.joined) { tp = 'info'; judul = 'Sudah dalam request · ' + (d.aktif.nomor || d.aktif.kode); sub = 'Resi ini sudah tergabung.'; }
    else if (d.aktif) {
      tp = 'info'; judul = 'Request berjalan · ' + (d.aktif.nomor || d.aktif.kode); sub = 'Gabungkan resi ini ke request tersebut.';
      tombol = '<button type="button" class="tl-btn pri sm" data-gabung="' + d.aktif.id + '">Gabungkan resi</button>';
    } else {
      tp = 'ok'; judul = 'Stok top level tersedia'; sub = 'Ajukan restok display, lalu serahkan resi ke CS.';
      tombol = '<button type="button" class="tl-btn pri sm" data-ajukan="' + i + '">Request Restok Display</button>';
    }
    return '<div class="tlc-ex"><div class="in ' + tp + '"><div class="t"><b>' + T.esc(judul) + '</b><span class="s">' + T.esc(sub) + '</span></div><div class="a">' + tombol + '</div></div>' + blokJubelio(i) + '</div>';
  }

  // ---------- stok live Jubelio ----------
  var BADGE = [['h', 'On Hand', 'on_hand'], ['o', 'On Order', 'on_order'], ['r', 'On Reserved', 'reserved'], ['a', 'On Available', 'available']];

  function angka(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

  function blokJubelio(i) {
    var j = jub[i], h = '<div class="tlj"><div class="tlj-hd"><b>Stok Jubelio</b>';
    if (!j || j.st === 'load') return h + '</div><div class="tlj-g"><div class="tlj-skl"></div><div class="tlj-skl"></div><div class="tlj-skl"></div></div><div class="tlj-skl" style="margin-top:12px;height:110px"></div></div>';
    if (j.st === 'err') return h + '</div><div class="tlj-err"><span>' + T.esc(j.pesan) + '</span><button type="button" class="tl-btn sm" data-muat="' + i + '">Coba lagi</button></div></div>';
    var d = j.d;
    h += '<span class="tlj-live"><i></i>Live dari Jubelio</span><span class="tlj-mut">Arahkan kursor ke angka untuk melihat keterangan</span><span class="tlj-sp"></span>' +
      '<span class="tlj-mut">Diperbarui ' + T.esc(d.waktu) + '</span><button type="button" class="tl-btn sm" data-muat="' + i + '">Muat ulang</button></div><div class="tlj-g">';
    $.each(d.lokasi, function (n, l) {
      var K = l.id === 0 ? [BADGE[3]] : BADGE;   // Transit: hanya On Available
      var b = '';
      $.each(K, function (x, k) {
        b += '<span class="tlj-b ' + k[0] + (l[k[2]] === 0 ? ' z' : '') + '" data-tip="' + k[1] + '">' + angka(l[k[2]]) + '</span>';
      });
      h += '<div class="tlj-c"><small>' + T.esc(l.nama) + '</small><div class="tlj-bg">' + b + '</div></div>';
    });
    h += '</div><h4 class="tlj-h4">History</h4>';
    if (!d.history.length) return h + '<div class="tlj-mut">Belum ada transfer untuk SKU ini.</div></div>';
    h += '<div class="tlj-tw"><table class="tlj-t"><thead><tr><th>Tanggal</th><th>Terakhir Diubah</th><th>Lokasi</th><th class="n">Qty</th><th class="n">Sisa</th><th>Keterangan</th></tr></thead><tbody>';
    $.each(d.history, function (n, r) {
      h += '<tr><td>' + T.esc(r.tanggal) + '</td><td>' + T.esc(r.diubah) + '</td><td>' + T.esc(r.lokasi) + '</td>' +
        '<td class="n ' + (r.qty > 0 ? 'pl' : 'mi') + '">' + (r.qty > 0 ? '+' : '') + angka(r.qty) + '</td><td class="n">' + angka(r.sisa) + '</td>' +
        '<td>' + (r.keterangan ? T.esc(r.keterangan) : '<span class="tlj-mut">-</span>') + '</td></tr>';
    });
    return h + '</tbody></table></div></div>';
  }

  /** Ambil stok live Jubelio untuk baris i; balasan lama (resi/baris sudah berganti) diabaikan. */
  function muatJubelio(i) {
    var sku = resi.items[i].sku, noresi = resi.noresi;
    jub[i] = { st: 'load' };
    gambarResi();
    T.post('picker/cek-top-level/jubelio', { sku: sku }, function (d) {
      if (!resi || resi.noresi !== noresi || !jub[i]) return;
      jub[i] = { st: 'ok', d: d };
      gambarResi();
    }, function (m) {
      if (!resi || resi.noresi !== noresi || !jub[i]) return;
      jub[i] = { st: 'err', pesan: m || 'Stok Jubelio belum bisa dimuat. Coba lagi.' };
      gambarResi();
    });
  }

  // ---------- popup konfirmasi ----------
  function kv(pasang) {
    var h = '';
    $.each(pasang, function (i, p) { h += '<span>' + T.esc(p[0]) + '</span><b>' + T.esc(p[1]) + '</b>'; });
    return '<div class="tlc-kv">' + h + '</div>';
  }

  function konfirmAjukan(i) {
    var it = resi.items[i];
    var $d = T.modal({
      judul: 'Ajukan Request Restok Display?',
      body: '<div class="tl-db">' + kv([['Resi', resi.noresi], ['SKU', it.sku], ['Rak display', it.no_rak], ['Nomor', 'TTL baru (lanjut dari TTL terakhir)'], ['Jenis', 'Keluar (qty minus)']]) +
        '<div class="tlc-wr">Pengajuan ini langsung mendapat nomor TTL berjenis <b>Keluar</b>. Qty yang dimasukkan CS akan tersimpan <b>minus</b> dan <b>mengurangi stok top level</b>.</div>' +
        '<div class="muted">Setelah diajukan, serahkan resi fisik ke CS. Request masih bisa dibatalkan selama berstatus Diajukan.</div></div>' +
        '<div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Batal</button><button type="button" class="tl-btn pri" data-ya>Ya, Ajukan</button></div>'
    });
    $d.find('[data-ya]').focus();
    $d.on('click', '[data-ya]', function () {
      $(this).prop('disabled', true);
      T.post('picker/cek-top-level/ajukan', { sku: it.sku, noresi: resi.noresi }, function (r) {
        T.closeModal();
        hs = '✓ Request ' + r.nomor + ' dibuat. Serahkan resi fisik ke CS.';
        baru = r.kode; filter = 'DIAJUKAN'; buka = null;
        T.toast('Request ' + r.nomor + ' dibuat.');
        muat(resi.noresi, true); terbaru();
      }, function (m) { T.closeModal(); T.galat(m); buka = null; muat(resi.noresi, true); terbaru(); });
    });
  }

  function konfirmBatal(id) {
    var r = null;
    $.each(rows, function (i, x) { if (+x.id === +id) r = x; });
    if (!r) return;
    var $d = T.modal({
      judul: 'Batalkan request?',
      body: '<div class="tl-db">' + kv([['Nomor TTL', r.nomor], ['SKU', r.sku], ['Resi', r.resi]]) +
        '<div class="tlc-wr">Request dibatalkan dan tidak akan diproses CS. Hanya bisa selama berstatus Diajukan.</div></div>' +
        '<div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Tidak</button><button type="button" class="tl-btn pri bad" data-yabatal>Ya, Batalkan</button></div>'
    });
    $d.find('[data-tl-tutup]').last().focus();
    $d.on('click', '[data-yabatal]', function () {
      $(this).prop('disabled', true);
      T.post('picker/cek-top-level/batal', { id: id }, function (d, j) {
        T.closeModal(); T.toast(j.message); terbaru(); if (resi) muat(resi.noresi, true);
      }, function (m) { T.closeModal(); T.galat(m); terbaru(); });
    });
  }

  // ---------- panel kanan: antrian request hari ini ----------
  function tahap(r) {
    var n = $.inArray(r.status, URUT), jm = function (v) { return v ? '<span class="on">' + T.esc(v) + '</span>' : '<span>—</span>'; };
    var f = function (k) { return k <= n ? 'f' : ''; }, g = function (k) { return k < n ? 'f' : ''; };
    return '<div class="tlc-tlt">' + jm(r.jam_diajukan) + jm(r.jam_cetak) + jm(r.jam_selesai) + '</div>' +
      '<div class="tlc-tl"><i class="' + f(0) + '"></i><u class="' + g(0) + '"></u><i class="' + f(1) + '"></i><u class="' + g(1) + '"></u><i class="' + f(2) + '"></i></div>' +
      '<div class="tlc-tlb"><span>Diajukan</span><span>Diproses</span><span>Selesai</span></div>';
  }

  function gambarAntrian() {
    var cnt = { DIAJUKAN: 0, DITUGASKAN: 0, SELESAI: 0 };
    $.each(rows, function (i, r) { if (cnt[r.status] !== undefined) cnt[r.status]++; });
    var t = '';
    $.each(URUT, function (i, s) {
      t += '<button type="button" class="tlc-s-' + s + '" data-f="' + s + '" aria-pressed="' + (filter === s) + '">' + T.esc(PK[s][0]) + '<span class="c">' + cnt[s] + '</span></button>';
    });
    $('#tl-tabs').html(t);
    var h = '';
    $.each(rows, function (i, r) {
      if (r.status !== filter) return;
      h += '<div class="tlc-qi tlc-s-' + r.status + (baru === r.kode ? ' baru' : '') + '"><div class="tlc-qt"><b>' + T.esc(r.sku) + '</b>' + T.pill(PK[r.status][0], PK[r.status][1]) + '</div>' +
        '<div class="tlc-qs mono">' + T.esc(r.nomor) + '</div>' + tahap(r) +
        (r.status === 'DIAJUKAN' ? '<div class="tlc-qa"><button type="button" class="tl-btn sm dng" data-batal="' + r.id + '">Batalkan</button></div>' : '') + '</div>';
    });
    $('#tl-q').html(h || '<div class="tlc-none">Belum ada request.</div>');
  }

  function terbaru() {
    T.post('picker/cek-top-level/terbaru', {}, function (d) { rows = d.rows || []; gambarAntrian(); });
  }

  $r.on('input', '#tl-resi', function () {
    var v = this.value;
    clearTimeout(timer);
    timer = setTimeout(function () { muat(v); }, 350);
  });
  $r.on('keydown', '#tl-resi', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); muat(this.value); }
  });
  $r.on('click', '[data-cek]', function () {
    var i = +$(this).data('cek');
    if (buka === i) { buka = null; gambarResi(); return; }
    T.post('picker/cek-top-level/cek', { sku: resi.items[i].sku, noresi: resi.noresi }, function (d) {
      cekHasil[i] = d; buka = i; muatJubelio(i);
    }, T.galat);
  });
  $r.on('click', '[data-muat]', function () {
    var i = +$(this).data('muat');
    if (buka === i) muatJubelio(i);
  });
  $r.on('click', '[data-ajukan]', function () { konfirmAjukan(+$(this).data('ajukan')); });
  $r.on('click', '[data-gabung]', function () {
    var id = $(this).data('gabung');
    $(this).prop('disabled', true);
    T.post('picker/cek-top-level/gabung', { id: id, noresi: resi.noresi }, function (r, j) {
      hs = '✓ ' + j.message + ' Serahkan resi fisik ke CS.';
      T.toast(j.message); buka = null; muat(resi.noresi, true); terbaru();
    }, function (m) { T.galat(m); buka = null; muat(resi.noresi, true); });
  });

  $r.on('click', '[data-f]', function () { filter = $(this).data('f'); gambarAntrian(); });
  $r.on('click', '[data-batal]', function () { konfirmBatal($(this).data('batal')); });

  // Scanner mengetik seperti keyboard: tombol apa pun di halaman ini mengosongkan kolom lalu mengisi resi baru.
  $(document).off('keydown.tlcek').on('keydown.tlcek', function (e) {
    if (!$.contains(document, $r[0])) { $(document).off('keydown.tlcek'); clearInterval(tmTerbaru); return; }
    if (T.adaModal() || e.ctrlKey || e.metaKey || e.altKey || !e.key || e.key.length !== 1) return;
    var t = e.target.tagName;
    if (t === 'INPUT' || t === 'SELECT' || t === 'TEXTAREA') return;
    var i = document.getElementById('tl-resi');
    if (i) { i.focus(); i.value = ''; }
  });

  clearInterval(window.__tlCekTimer);
  window.__tlCekTimer = tmTerbaru = setInterval(function () {
    if (!$.contains(document, $r[0])) { clearInterval(window.__tlCekTimer); return; }
    if (!T.adaModal()) terbaru();
  }, 20000);

  gambarResi();
  terbaru();
  fokus();
})();
</script>
