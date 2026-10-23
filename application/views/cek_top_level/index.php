<?php
// View menu TIM PICKER -> Cek Top Level. Dimuat lewat AJAX (SPA). Semua handler diikat ke #tl-cek
// atau memakai namespace .tlcek di document supaya hilang saat user pindah menu.
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Cek Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl" id="tl-cek"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-cek'), timer = null, seq = 0, resi = null, tmTerbaru = null;
  var PK = {
    DIAJUKAN: ['Menunggu CS', 'info'], DITUGASKAN: ['Restok berjalan', 'info'],
    SELESAI: ['Selesai · ambil resi di CS', 'ok'], BATAL: ['Dibatalkan', '']
  };

  $r.html(
    '<section class="tl-panel tl-scanp"><label class="tl-lbl" for="tl-resi">No. resi</label>' +
    '<div class="tl-scanbox">' + T.ic('scan', 22) +
    '<input type="text" id="tl-resi" placeholder="Scan, tempel, atau ketik no. resi" autocomplete="off" autocapitalize="off" spellcheck="false"></div>' +
    '<div class="tl-fmsg" id="tl-resi-msg"></div></section>' +
    '<div id="tl-hasil"></div><div id="tl-terbaru"></div>'
  );

  function fokus() { var i = document.getElementById('tl-resi'); if (i) { i.focus(); i.select(); } }

  function cari(v) {
    v = $.trim(v);
    var $msg = $('#tl-resi-msg');
    if (!v) { $msg.text(''); resi = null; $('#tl-hasil').empty(); return; }
    var n = ++seq;
    T.post('picker/cek-top-level/cari', { q: v }, function (d) {
      if (n !== seq) return; // balasan lama, sudah ada pencarian yang lebih baru
      if (d.ditemukan) {
        resi = d.resi;
        $msg.text('');
        $('#tl-resi').val(resi.noresi);
        hasil();
        fokus();
      } else {
        resi = null;
        $('#tl-hasil').empty();
        $msg.text('Resi ' + v + ' tidak ditemukan.');
        fokus();
      }
    }, function (m) { if (n === seq) { $msg.text(m); } });
  }

  function hasil() {
    if (!resi) { $('#tl-hasil').empty(); return; }
    var h = '<section class="tl-panel tl-cekpanel"><div class="tl-shead"><div class="l"><h2 class="mono">' + T.esc(resi.noresi) + '</h2>' +
      (resi.mp ? T.pill(resi.mp) : '') + '</div></div><div class="tl-tw"><table class="tl-tbl"><thead><tr><th>SKU</th><th>Rak display</th><th>Status</th><th></th></tr></thead><tbody>';
    $.each(resi.items, function (i, it) {
      var r = it.request, live = r && r.status !== 'SELESAI' && r.status !== 'BATAL', p = r ? PK[r.status] : null;
      h += '<tr><td><b class="mono">' + T.esc(it.sku) + '</b></td><td class="mono">' + T.esc(it.no_rak) + '</td><td>' +
        (live ? T.pill(p[0], p[1]) + ' <span class="mono muted">' + T.esc(r.kode) + '</span>' : '') + '</td><td class="mid">' +
        (live ? '' : '<button type="button" class="tl-btn pri lg" data-cek="' + T.esc(it.sku) + '">Cek</button>') + '</td></tr>';
    });
    $('#tl-hasil').html(h + '</tbody></table></div></section>');
  }

  function res(kelas, ikon, judul, sub) {
    return '<div class="tl-res"><div class="ring ' + kelas + '">' + T.ic(ikon, 26) + '</div><b>' + T.esc(judul) + '</b>' + (sub ? '<span class="muted">' + T.esc(sub) + '</span>' : '') + '</div>';
  }

  function dialogCek(sku, d) {
    var isi, foot = '<button type="button" class="tl-btn" data-tl-tutup>Tutup</button>';
    if (!d.ada_stok) isi = res('bad', 'x', 'Tidak ada stok top level');
    else if (d.aktif && d.joined) isi = res('info', 'check', 'Sudah dalam request', d.aktif.kode);
    else if (d.aktif) {
      isi = res('info', 'alert', 'Request sedang berjalan', d.aktif.kode + ' untuk SKU ini');
      foot += '<button type="button" class="tl-btn pri" data-gabung="' + d.aktif.id + '">Gabungkan resi</button>';
    } else {
      isi = res('ok', 'check', 'Stok top level tersedia');
      foot += '<button type="button" class="tl-btn pri" data-ajukan>Request Restok Display</button>';
    }
    var $d = T.modal({ judul: 'Cek top level', body: '<div class="tl-db"><div class="mono" style="text-align:center">' + T.esc(sku) + '</div>' + isi + '</div><div class="tl-df">' + foot + '</div>' });
    $d.on('click', '[data-ajukan]', function () {
      $(this).prop('disabled', true);
      T.post('picker/cek-top-level/ajukan', { sku: sku, noresi: resi.noresi }, function (r, j) {
        T.closeModal(); T.toast(j.message + ' Serahkan resi ke CS.'); cari(resi.noresi); terbaru();
      }, function (m) { T.closeModal(); T.galat(m); cari(resi.noresi); });
    });
    $d.on('click', '[data-gabung]', function () {
      var id = $(this).data('gabung');
      $(this).prop('disabled', true);
      T.post('picker/cek-top-level/gabung', { id: id, noresi: resi.noresi }, function (r, j) {
        T.closeModal(); T.toast(j.message + ' Serahkan resi ke CS.'); cari(resi.noresi); terbaru();
      }, function (m) { T.closeModal(); T.galat(m); cari(resi.noresi); });
    });
  }

  function terbaru() {
    T.post('picker/cek-top-level/terbaru', {}, function (d) {
      if (!d.rows.length) { $('#tl-terbaru').empty(); return; }
      var h = '<section class="tl-panel"><div class="tl-shead"><div class="l"><h2>Request terbaru</h2>' +
        T.info('Setelah status Selesai, display sudah terisi. Ambil resi dari CS lalu lanjutkan picking.') +
        '</div></div><div class="tl-tw"><table class="tl-tbl"><thead><tr><th>Request</th><th>SKU</th><th>Resi</th><th>Status</th><th></th></tr></thead><tbody>';
      $.each(d.rows, function (i, r) {
        var p = PK[r.status] || ['', ''];
        h += '<tr><td class="mono">' + T.esc(r.kode) + '</td><td class="mono">' + T.esc(r.sku) + '</td><td class="mono">' + T.esc(r.resi) + '</td><td>' + T.pill(p[0], p[1]) + '</td><td class="act">' +
          (r.status === 'DIAJUKAN' ? '<button type="button" class="tl-btn sm ghost dng" data-batal="' + r.id + '">Batalkan</button>' : '') + '</td></tr>';
      });
      $('#tl-terbaru').html(h + '</tbody></table></div></section>');
    });
  }

  $r.on('input', '#tl-resi', function () {
    var v = this.value;
    clearTimeout(timer);
    timer = setTimeout(function () { cari(v); }, 350);
  });
  $r.on('keydown', '#tl-resi', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); cari(this.value); }
  });
  $r.on('click', '[data-cek]', function () {
    var sku = $(this).data('cek');
    T.post('picker/cek-top-level/cek', { sku: sku, noresi: resi.noresi }, function (d) { dialogCek(sku, d); }, T.galat);
  });
  $r.on('click', '[data-batal]', function () {
    var id = $(this).data('batal');
    T.post('picker/cek-top-level/batal', { id: id }, function (d, j) { T.toast(j.message); terbaru(); if (resi) cari(resi.noresi); }, function (m) { T.galat(m); terbaru(); });
  });

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

  terbaru();
  fokus();
})();
</script>
