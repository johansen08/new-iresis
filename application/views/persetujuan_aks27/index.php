<?php
// View menu TIM PURCHASING -> Persetujuan Stok AKS27. Dimuat lewat AJAX (SPA) oleh plugins.js:
// semua handler diikat ke #pa-root. Tanpa <form> sengaja -- submit form dicegat handler global.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Persetujuan Stok AKS27</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<div id="pa-root">
  <div class="row"><div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Persetujuan Stok AKS27</strong>
          <button type="button" id="pa-ic" class="pa-ic" aria-label="Info halaman" aria-expanded="false">i</button>
        </h3>
      </div>
      <div class="panel-body">
        <div id="pa-info" class="alert alert-info hidden">
          <p><b>Koreksi lupa dimasukkan</b>: resi cancel/retur yang paketnya dibuka packer dan ternyata pinset bonus tidak pernah dimasukkan (+1, pinset masih di rak). <b>Opname</b>: hasil hitung fisik packer; selisih = fisik &minus; stok sistem saat diajukan (bisa + atau &minus;).</p>
          <p style="margin-bottom:0;">Setuju menambah satu baris PENYESUAIAN ke buku Stok AKS27-RDM (baris tidak pernah diubah atau dihapus). Tolak wajib beralasan dan tidak mengubah stok. Keputusan tidak bisa ditarik; salah setuju dikoreksi dengan opname berikutnya.</p>
        </div>

        <div id="pa-pesan" class="alert hidden" role="status" style="margin-bottom:12px;"></div>

        <h3 class="pa-sub">Menunggu keputusan <span id="pa-n" class="badge"></span></h3>
        <div class="table-responsive">
          <table class="table table-bordered" id="pa-tabel">
            <thead><tr><th>Diajukan</th><th>Oleh</th><th>Jenis</th><th>Resi</th><th class="text-right">Fisik</th><th class="text-right">Sistem</th><th class="text-right">Selisih</th><th>Alasan</th><th style="width:230px;">Keputusan</th></tr></thead>
            <tbody><tr><td colspan="9" class="text-muted text-center">Memuat…</td></tr></tbody>
          </table>
        </div>

        <h3 class="pa-sub">Keputusan terakhir</h3>
        <div class="table-responsive">
          <table class="table table-bordered" id="pa-riwayat">
            <thead><tr><th>Diputus</th><th>Oleh</th><th>Jenis</th><th>Resi</th><th class="text-right">Selisih</th><th>Alasan pengajuan</th><th>Hasil</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div></div>
</div>

<script>
(function () {
  var $root = $('#pa-root');
  var URL = { data: 'purchasing/persetujuan-aks27/data', putuskan: 'purchasing/persetujuan-aks27/putuskan' };
  var JENIS = { OPNAME: 'Opname', LUPA_MASUK: 'Koreksi lupa dimasukkan' };

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function pesan(tipe, teks) {
    $('#pa-pesan').removeClass('hidden alert-success alert-danger').addClass('alert-' + tipe).text(teks);
  }
  function jsonGagal(xhr) {
    // Bukan JSON = ada output nyasar (warning PHP / halaman error CI).
    pesan('danger', 'Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200));
  }
  function selisih(n) { n = Number(n); return (n > 0 ? '+' : '') + n; }

  $root.on('click', '#pa-ic', function () {
    var $i = $('#pa-info').toggleClass('hidden');
    $(this).attr('aria-expanded', !$i.hasClass('hidden'));
  });

  function muat() {
    $.getJSON(URL.data).done(function (res) {
      if (Number(res.code) !== 200 || !res.data) { pesan('danger', res.message || 'Gagal memuat data.'); return; }
      var d = res.data, $tb = $('#pa-tabel tbody').empty();
      $('#pa-n').text(d.menunggu.length);
      if (!d.menunggu.length) { $tb.append('<tr><td colspan="9" class="text-muted text-center">Tidak ada pengajuan yang menunggu.</td></tr>'); }
      $.each(d.menunggu, function (i, r) {
        $tb.append('<tr data-id="' + esc(r.id) + '"><td>' + esc(r.diajukan) + '</td><td>' + esc(r.pengaju) + '</td><td>' + esc(JENIS[r.jenis] || r.jenis) + '</td><td><code>' + esc(r.noresi || '') + '</code></td>' +
          '<td class="text-right">' + esc(r.qty_fisik === null ? '' : r.qty_fisik) + '</td><td class="text-right">' + esc(r.qty_sistem === null ? '' : r.qty_sistem) + '</td>' +
          '<td class="text-right"><b class="' + (Number(r.selisih) < 0 ? 'text-danger' : 'text-success') + '">' + selisih(r.selisih) + '</b></td><td>' + esc(r.alasan) + '</td>' +
          '<td><input type="text" class="form-control pa-cat" maxlength="150" placeholder="Alasan (wajib bila tolak)" />' +
          '<div style="margin-top:6px;"><button type="button" class="btn btn-success pa-aksi" data-k="setuju">Setuju</button> <button type="button" class="btn btn-danger pa-aksi" data-k="tolak">Tolak</button></div></td></tr>');
      });
      var $r = $('#pa-riwayat tbody').empty();
      if (!d.putus.length) { $r.append('<tr><td colspan="7" class="text-muted text-center">Belum ada keputusan.</td></tr>'); }
      $.each(d.putus, function (i, r) {
        $r.append('<tr><td>' + esc(r.diputus) + '</td><td>' + esc(r.pemutus) + '</td><td>' + esc(JENIS[r.jenis] || r.jenis) + '</td><td><code>' + esc(r.noresi || '') + '</code></td>' +
          '<td class="text-right">' + selisih(r.selisih) + '</td><td>' + esc(r.alasan) + '</td><td>' +
          (r.status === 'DISETUJUI' ? '<span class="label label-success">Disetujui</span>' : '<span class="label label-danger">Ditolak</span>') +
          (r.catatan_putus ? ' <small class="text-muted">' + esc(r.catatan_putus) + '</small>' : '') + '</td></tr>');
      });
    }).fail(jsonGagal);
  }

  $root.on('click', '.pa-aksi', function () {
    var $tr = $(this).closest('tr'), k = String($(this).data('k')), id = $tr.data('id'), cat = $.trim($tr.find('.pa-cat').val());
    if (k === 'tolak' && !cat) { pesan('danger', 'Alasan penolakan wajib diisi.'); $tr.find('.pa-cat').focus(); return; }
    if (!window.confirm((k === 'setuju' ? 'Setujui' : 'Tolak') + ' pengajuan ini?\nKeputusan tidak bisa ditarik.')) { return; }
    var $b = $tr.find('.pa-aksi').prop('disabled', true);
    $.post(URL.putuskan, { id: id, keputusan: k, catatan: cat }, null, 'json').done(function (res) {
      pesan(Number(res.code) === 200 ? 'success' : 'danger', res.message);
      muat();
    }).fail(jsonGagal).always(function () { $b.prop('disabled', false); });
  });

  muat();
})();
</script>
<style>
  /* Font template iresis kecil (12-13px); menu ini dibaca dari jauh, jadi dibesarkan (sama dengan Penerimaan Transfer). */
  #pa-root .panel-title { font-size: 18px; }
  #pa-root .form-control, #pa-root .btn { height: 40px; font-size: 16px; }
  #pa-root .alert { font-size: 17px; }
  #pa-root .table > thead > tr > th { font-size: 15px; padding: 10px 12px; }
  #pa-root .table > tbody > tr > td { font-size: 16px; padding: 9px 12px; vertical-align: middle; }
  #pa-root .table code { font-size: 16px; font-weight: 600; }
  #pa-root .pa-ic { width: 26px; height: 26px; border-radius: 50%; border: 1px solid #bbb; background: #fff; color: #777; font: 600 14px/1 monospace; cursor: pointer; padding: 0; margin-left: 8px; vertical-align: middle; }
  #pa-root .pa-ic:hover, #pa-root .pa-ic[aria-expanded="true"] { color: #337ab7; border-color: #337ab7; background: #eaf2fa; }
  #pa-root .pa-sub { font-size: 20px; font-weight: 600; margin: 22px 0 10px; }
  #pa-root .pa-sub .badge { font-size: 15px; vertical-align: middle; }
</style>
