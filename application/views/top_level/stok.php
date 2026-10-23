<?php
// View menu TIM CS -> Stok Top Level. Dimuat lewat AJAX (SPA).
// Daftar: SKU, total stok top level, aksi Detail. Detail: rak dan stok per rak, bisa diedit (koreksi).
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Stok Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl" id="tl-stok"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-stok'), q = '', rows = [], timer = null;
  var ALASAN = ['Opname', 'Salah catat', 'Barang rusak', 'Pindah rak'];

  $r.html(
    '<div class="tl-ph"><div class="t"><h1>Stok Top Level</h1>' +
    T.info('Total stok top level per SKU.\nKlik Detail untuk melihat stok per rak.\nStok bertambah lewat Upload Top Level dan berkurang saat CS input qty dari slip.') +
    '</div></div><div class="tl-tb"><label class="tl-search">' + T.ic('search') + '<input type="text" id="tl-q" placeholder="Cari SKU" autocomplete="off"></label></div>' +
    '<section class="tl-panel tl-narrow" id="tl-tabel"></section>'
  );

  function tabel() {
    if (!rows.length) { $('#tl-tabel').html(T.empty('db', q ? 'Tidak ada SKU' : 'Belum ada stok top level. Unggah Excel di menu Upload Top Level.')); return; }
    var h = '<div class="tl-tw"><table class="tl-tbl"><thead><tr><th>SKU</th><th class="n">Total stok top level</th><th></th></tr></thead><tbody>';
    $.each(rows, function (i, r) {
      h += '<tr><td><b class="mono">' + T.esc(r.sku) + '</b></td><td class="n">' + r.total + '</td><td class="act"><button type="button" class="tl-btn sm" data-detail="' + T.esc(r.sku) + '">Detail</button></td></tr>';
    });
    $('#tl-tabel').html(h + '</tbody></table></div>');
  }

  function muat() {
    T.post('cs/stok-top-level/data', { q: q }, function (d) { rows = d.rows; tabel(); }, T.galat);
  }

  function detail(sku) {
    T.post('cs/stok-top-level/detail', { sku: sku }, function (d) {
      var $m = T.modal({ judul: 'Stok top level', body: '' });
      var data = d.rows, edit = false, baris = [];

      function lihat() {
        var b = '<div class="tl-db"><div class="mono" style="font-size:18px;font-weight:600">' + T.esc(sku) + '</div>';
        if (!data.length) b += T.empty('db', 'Belum ada stok top level');
        else {
          b += '<div class="tl-tw"><table class="tl-tbl"><thead><tr><th>No rak top level</th><th class="n">Stok</th></tr></thead><tbody>';
          $.each(data, function (i, x) { b += '<tr><td class="mono">' + T.esc(x.rak) + '</td><td class="n">' + x.stok + '</td></tr>'; });
          b += '</tbody></table></div>';
        }
        $m.find('.tl-body').html(b + '</div><div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Tutup</button><button type="button" class="tl-btn pri" data-edit>' + T.ic('edit', 15) + 'Edit</button></div>');
      }

      function ubah() {
        var b = '<div class="tl-db"><div class="mono" style="font-size:18px;font-weight:600">' + T.esc(sku) + '</div><div class="tl-tw"><table class="tl-tbl"><thead><tr><th>No rak top level</th><th class="n">Stok</th></tr></thead><tbody>';
        $.each(baris, function (i, x) {
          b += '<tr data-i="' + i + '"><td>' + (x.lama !== null ? '<span class="mono">' + T.esc(x.rak) + '</span>' : '<input type="text" class="rak" placeholder="Rak baru, mis. TL-F3" value="' + T.esc(x.rak) + '" style="width:100%">') +
            '</td><td class="n"><input type="number" min="0" class="qty" value="' + T.esc(x.qty) + '"></td></tr>';
        });
        b += '</tbody></table></div><div><button type="button" class="tl-btn sm ghost" data-rak>' + T.ic('plus', 14) + 'Tambah rak</button></div>' +
          '<div class="tl-fld">Alasan<select id="tl-alasan">' + $.map(ALASAN, function (a) { return '<option>' + a + '</option>'; }).join('') + '</select></div></div>' +
          '<div class="tl-df"><button type="button" class="tl-btn" data-batal-edit>Batal</button><button type="button" class="tl-btn pri" data-simpan>Simpan</button></div>';
        $m.find('.tl-body').html(b);
      }
      function simpanNilai() {
        $m.find('tbody tr').each(function () {
          var i = +$(this).data('i');
          if (baris[i].lama === null) baris[i].rak = $(this).find('.rak').val();
          baris[i].qty = $(this).find('.qty').val();
        });
      }

      $m.on('click', '[data-edit]', function () {
        edit = true;
        baris = $.map(data, function (x) { return { rak: x.rak, lama: +x.stok, qty: x.stok }; });
        ubah();
      });
      $m.on('click', '[data-batal-edit]', function () { edit = false; lihat(); });
      $m.on('click', '[data-rak]', function () { simpanNilai(); baris.push({ rak: '', lama: null, qty: '' }); var a = $('#tl-alasan').val(); ubah(); $('#tl-alasan').val(a); });
      $m.on('click', '[data-simpan]', function () {
        simpanNilai();
        var kirim = [];
        $.each(baris, function (i, x) {
          if (x.lama === null && $.trim(x.rak) === '' && x.qty === '') return;
          kirim.push({ rak: $.trim(x.rak), qty: x.qty });
        });
        $(this).prop('disabled', true);
        T.post('cs/stok-top-level/koreksi', { sku: sku, alasan: $('#tl-alasan').val(), rows: kirim }, function (r, j) {
          T.closeModal(); T.toast(j.message); muat();
        }, function (m) { T.galat(m); $m.find('[data-simpan]').prop('disabled', false); });
      });
      lihat();
    }, T.galat);
  }

  $r.on('click', '[data-detail]', function () { detail($(this).data('detail')); });
  $r.on('input', '#tl-q', function () { var v = this.value; clearTimeout(timer); timer = setTimeout(function () { q = $.trim(v); muat(); }, 300); });
  muat();
})();
</script>
