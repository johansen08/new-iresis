<?php
// View menu TIM PACKER -> Konfirmasi Pinset Kembali. Dimuat lewat AJAX (SPA) oleh plugins.js:
// semua handler diikat ke #pk-root supaya ikut hilang saat user pindah menu. Tanpa <form>
// sengaja -- submit form dicegat handler global.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Konfirmasi Pinset Kembali</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<div id="pk-root">
  <div class="row"><div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Konfirmasi Pinset Kembali</strong>
          <button type="button" id="pk-ic" class="pk-ic" aria-label="Info halaman" aria-expanded="false">i</button>
        </h3>
      </div>
      <div class="panel-body">
        <div id="pk-info" class="alert alert-info hidden">
          <p>Pinset bonus AKS27-RDM yang ikut paket sudah dipotong dari stok saat resi discan packer. Kalau resi itu lalu cancel atau diretur, stok <b>tidak naik sendiri</b>: scan resinya di sini setelah paket dibuka, lalu catat apakah pinsetnya ada.</p>
          <p style="margin-bottom:0;"><b>Ada</b> = stok +1 langsung. <b>Tidak ada</b> karena pembeli tidak mengirim balik = stok tetap. <b>Tidak ada</b> karena lupa dimasukkan saat packing = pinset masih di rak, koreksi +1 diajukan ke Purchasing. <b>Rusak</b> = stok tetap. Satu resi hanya bisa dikonfirmasi sekali.</p>
        </div>

        <div id="pk-pesan" class="alert hidden" role="status" style="margin-bottom:12px;"></div>

        <label for="pk-resi">Scan atau ketik nomor resi</label>
        <div class="pk-baris">
          <input type="text" id="pk-resi" class="form-control" placeholder="No. resi" autocomplete="off" />
          <button type="button" id="pk-cek" class="btn btn-primary">Periksa</button>
        </div>

        <div id="pk-aksi" class="pk-aksi hidden">
          <div class="pk-aksi-h"><code id="pk-a-resi"></code> <span id="pk-a-jenis" class="label label-warning"></span></div>
          <p class="text-muted" style="margin:6px 0 12px;">Buka paketnya. Apakah pinset bonus AKS27-RDM ada?</p>
          <div class="pk-tombol">
            <button type="button" class="btn btn-success pk-pilih" data-pilihan="ADA" data-alasan="">Pinset ADA</button>
            <button type="button" class="btn btn-default pk-pilih" data-pilihan="RUSAK" data-alasan="">Pinset RUSAK</button>
            <button type="button" id="pk-tidak" class="btn btn-warning">Pinset TIDAK ADA</button>
          </div>
          <div id="pk-tidak-box" class="pk-tidak hidden">
            <p style="margin:0 0 8px;">Kenapa tidak ada?</p>
            <button type="button" class="btn btn-default pk-pilih" data-pilihan="TIDAK_ADA" data-alasan="PEMBELI_TIDAK_KIRIM">Pembeli tidak mengirim balik</button>
            <button type="button" class="btn btn-default pk-pilih" data-pilihan="TIDAK_ADA" data-alasan="LUPA_DIMASUKKAN">Lupa dimasukkan saat packing</button>
          </div>
        </div>

        <h3 class="pk-sub">Menunggu konfirmasi <span id="pk-n" class="badge"></span></h3>
        <div class="table-responsive">
          <table class="table table-bordered table-hover" id="pk-tabel">
            <thead><tr><th>No. resi</th><th>Jenis</th><th>Tgl keluar</th><th class="text-right">Umur (hari)</th></tr></thead>
            <tbody><tr><td colspan="4" class="text-muted text-center">Memuat…</td></tr></tbody>
          </table>
        </div>

        <h3 class="pk-sub">Opname stok</h3>
        <p class="text-muted">Hitung pinset bonus AKS27-RDM yang ada di tempat packer sekarang, lalu masukkan jumlahnya. Angka stok sistem tidak ditampilkan; selisihnya dihitung otomatis dan harus disetujui Purchasing sebelum stok berubah.</p>
        <div class="row">
          <div class="col-md-3 col-sm-4 col-xs-12"><label for="pk-fisik">Jumlah fisik</label>
            <input type="number" id="pk-fisik" class="form-control" min="0" step="1" placeholder="0" /></div>
          <div class="col-md-3 col-sm-4 col-xs-12"><label for="pk-alasan">Alasan</label>
            <select id="pk-alasan" class="form-control">
              <option value="HITUNG_ULANG">Hitung ulang rutin</option>
              <option value="BARANG_HILANG">Barang hilang</option>
              <option value="SALAH_CATAT">Salah catat</option>
              <option value="LAINNYA">Lainnya</option>
            </select></div>
          <div class="col-md-4 col-sm-4 col-xs-12"><label for="pk-catatan">Catatan (wajib untuk Lainnya)</label>
            <input type="text" id="pk-catatan" class="form-control" maxlength="150" /></div>
          <div class="col-md-2 col-xs-12"><label>&nbsp;</label><button type="button" id="pk-opname" class="btn btn-primary btn-block">Ajukan</button></div>
        </div>

        <h3 class="pk-sub">Pengajuan terakhir</h3>
        <div class="table-responsive">
          <table class="table table-bordered" id="pk-t-ajuan">
            <thead><tr><th>Tanggal</th><th>Jenis</th><th>Resi</th><th>Alasan</th><th>Status</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>

        <h3 class="pk-sub">Konfirmasi terakhir</h3>
        <div class="table-responsive">
          <table class="table table-bordered" id="pk-t-riwayat">
            <thead><tr><th>Waktu</th><th>No. resi</th><th>Jenis</th><th>Hasil</th><th>Oleh</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div></div>
</div>

<script>
(function () {
  var $root = $('#pk-root');
  var URL = { data: 'packer/pinset-kembali/data', cek: 'packer/pinset-kembali/cek', simpan: 'packer/pinset-kembali/simpan', opname: 'packer/pinset-kembali/opname' };
  var HASIL = { KEMBALI: 'Kembali (+1)', TIDAK_KEMBALI: 'Tidak kembali', TIDAK_PERNAH_MASUK: 'Tidak pernah masuk (koreksi diajukan)', RUSAK: 'Rusak' };
  var STATUS_AJUAN = { MENUNGGU: '<span class="label label-warning">Menunggu Purchasing</span>', DISETUJUI: '<span class="label label-success">Disetujui</span>', DITOLAK: '<span class="label label-danger">Ditolak</span>' };
  var aktif = null;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function pesan(tipe, teks) {
    $('#pk-pesan').removeClass('hidden alert-success alert-danger alert-warning').addClass('alert-' + tipe).text(teks);
  }
  function jsonGagal(xhr) {
    // Bukan JSON = ada output nyasar (warning PHP / halaman error CI).
    pesan('danger', 'Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200));
  }
  function tutupAksi() { aktif = null; $('#pk-aksi').addClass('hidden'); $('#pk-tidak-box').addClass('hidden'); }

  $root.on('click', '#pk-ic', function () {
    var $i = $('#pk-info').toggleClass('hidden');
    $(this).attr('aria-expanded', !$i.hasClass('hidden'));
  });

  function muat() {
    $.getJSON(URL.data).done(function (res) {
      if (Number(res.code) !== 200 || !res.data) { pesan('danger', res.message || 'Gagal memuat data.'); return; }
      var d = res.data, $tb = $('#pk-tabel tbody').empty();
      $('#pk-n').text(d.menunggu.length);
      if (!d.menunggu.length) { $tb.append('<tr><td colspan="4" class="text-muted text-center">Tidak ada resi yang menunggu.</td></tr>'); }
      $.each(d.menunggu, function (i, r) {
        $tb.append('<tr class="pk-baris-resi' + (r.terlambat ? ' danger' : '') + '" data-resi="' + esc(r.noresi) + '" style="cursor:pointer;"><td><code>' + esc(r.noresi) + '</code></td><td>' +
          (r.jenis === 'CANCEL' ? 'Cancel' : 'Retur') + '</td><td>' + esc(r.tgl_jual) + '</td><td class="text-right">' + esc(r.umur) + (r.terlambat ? ' <b class="text-danger">terlambat</b>' : '') + '</td></tr>');
      });
      var $a = $('#pk-t-ajuan tbody').empty();
      if (!d.ajuan.length) { $a.append('<tr><td colspan="5" class="text-muted text-center">Belum ada pengajuan.</td></tr>'); }
      $.each(d.ajuan, function (i, r) {
        $a.append('<tr><td>' + esc(r.diajukan) + '</td><td>' + (r.jenis === 'OPNAME' ? 'Opname' : 'Koreksi lupa dimasukkan') + '</td><td><code>' + esc(r.noresi || '') + '</code></td><td>' + esc(r.alasan) +
          '</td><td>' + (STATUS_AJUAN[r.status] || '') + (r.catatan_putus ? ' <small class="text-muted">' + esc(r.catatan_putus) + '</small>' : '') + '</td></tr>');
      });
      var $k = $('#pk-t-riwayat tbody').empty();
      if (!d.kembali.length) { $k.append('<tr><td colspan="5" class="text-muted text-center">Belum ada konfirmasi.</td></tr>'); }
      $.each(d.kembali, function (i, r) {
        $k.append('<tr><td>' + esc(r.created) + '</td><td><code>' + esc(r.noresi) + '</code></td><td>' + (r.jenis === 'CANCEL' ? 'Cancel' : 'Retur') + '</td><td>' + esc(HASIL[r.status] || r.status) +
          (r.catatan ? ' <small class="text-muted">' + esc(r.catatan) + '</small>' : '') + '</td><td>' + esc(r.oleh) + '</td></tr>');
      });
    }).fail(jsonGagal);
  }

  function periksa() {
    var resi = $.trim($('#pk-resi').val());
    if (!resi) { pesan('danger', 'Scan atau ketik nomor resi dulu.'); return; }
    tutupAksi();
    $.post(URL.cek, { noresi: resi }, null, 'json').done(function (res) {
      if (Number(res.code) !== 200 || !res.data) { pesan('danger', res.message); $('#pk-resi').select(); return; }
      $('#pk-pesan').addClass('hidden');
      aktif = res.data;
      $('#pk-a-resi').text(aktif.noresi);
      $('#pk-a-jenis').text(aktif.jenis === 'CANCEL' ? 'Paket cancel' : 'Retur');
      $('#pk-aksi').removeClass('hidden');
    }).fail(jsonGagal);
  }
  $root.on('click', '#pk-cek', periksa);
  $root.on('keydown', '#pk-resi', function (e) { if (e.key === 'Enter') { e.preventDefault(); periksa(); } });
  $root.on('click', '.pk-baris-resi', function () { $('#pk-resi').val($(this).data('resi')); periksa(); });
  $root.on('click', '#pk-tidak', function () { $('#pk-tidak-box').toggleClass('hidden'); });

  $root.on('click', '.pk-pilih', function () {
    if (!aktif) { return; }
    var pilihan = String($(this).data('pilihan')), alasan = String($(this).data('alasan')), teks = $.trim($(this).text());
    if (!window.confirm('Simpan untuk resi ' + aktif.noresi + ': "' + teks + '"?\nKeputusan tidak bisa diubah.')) { return; }
    var $b = $('.pk-pilih').prop('disabled', true);
    $.post(URL.simpan, { noresi: aktif.noresi, pilihan: pilihan, alasan: alasan }, null, 'json').done(function (res) {
      var ok = Number(res.code) === 200;
      pesan(ok ? 'success' : 'danger', res.message);
      if (ok) { tutupAksi(); $('#pk-resi').val('').focus(); }
      muat();
    }).fail(jsonGagal).always(function () { $b.prop('disabled', false); });
  });

  $root.on('click', '#pk-opname', function () {
    var fisik = $.trim($('#pk-fisik').val());
    if (!/^\d+$/.test(fisik)) { pesan('danger', 'Jumlah fisik harus bilangan bulat 0 atau lebih.'); return; }
    if (!window.confirm('Ajukan opname dengan jumlah fisik ' + fisik + ' pcs?\nStok baru berubah setelah Purchasing menyetujui.')) { return; }
    var $b = $(this).prop('disabled', true);
    $.post(URL.opname, { fisik: fisik, alasan: $('#pk-alasan').val(), catatan: $.trim($('#pk-catatan').val()) }, null, 'json').done(function (res) {
      var ok = Number(res.code) === 200;
      pesan(ok ? 'success' : 'danger', res.message);
      if (ok) { $('#pk-fisik, #pk-catatan').val(''); }
      muat();
    }).fail(jsonGagal).always(function () { $b.prop('disabled', false); });
  });

  muat();
  $('#pk-resi').focus();
})();
</script>
<style>
  /* Font template iresis kecil (12-13px); menu ini dibaca dari jauh, jadi dibesarkan (sama dengan Penerimaan Transfer). */
  #pk-root .panel-title { font-size: 18px; }
  #pk-root label { font-size: 15px; font-weight: 600; color: #555; }
  #pk-root .form-control, #pk-root .btn { height: 44px; font-size: 18px; }
  #pk-root .btn-primary, #pk-root .pk-tombol .btn { padding: 0 22px; }
  #pk-root .alert { font-size: 17px; }
  #pk-root .table > thead > tr > th { font-size: 16px; padding: 10px 14px; }
  #pk-root .table > tbody > tr > td { font-size: 17px; padding: 9px 14px; vertical-align: middle; }
  #pk-root .table code { font-size: 17px; font-weight: 600; }
  #pk-root .pk-ic { width: 26px; height: 26px; border-radius: 50%; border: 1px solid #bbb; background: #fff; color: #777; font: 600 14px/1 monospace; cursor: pointer; padding: 0; margin-left: 8px; vertical-align: middle; }
  #pk-root .pk-ic:hover, #pk-root .pk-ic[aria-expanded="true"] { color: #337ab7; border-color: #337ab7; background: #eaf2fa; }
  #pk-root .pk-baris { display: flex; gap: 10px; max-width: 560px; }
  #pk-root .pk-aksi { margin-top: 16px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 6px; background: #f7f8fa; }
  #pk-root .pk-aksi.hidden { display: none; }
  #pk-root .pk-aksi-h { font-size: 20px; }
  #pk-root .pk-aksi-h .label { font-size: 14px; padding: 5px 10px; }
  #pk-root .pk-tombol .btn { margin: 0 8px 8px 0; }
  #pk-root .pk-tidak { margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd; }
  #pk-root .pk-tidak .btn { margin: 0 8px 8px 0; padding: 0 18px; }
  #pk-root .pk-sub { font-size: 20px; font-weight: 600; margin: 26px 0 10px; }
  #pk-root .pk-sub .badge { font-size: 15px; vertical-align: middle; }
</style>
