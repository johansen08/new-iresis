<?php
// View menu TIM PACKER -> Konfirmasi Stok AKS27-RDM. Dimuat lewat AJAX (SPA) oleh plugins.js:
// semua handler diikat ke #pk-root supaya ikut hilang saat user pindah menu. Tanpa <form>
// sengaja -- submit form dicegat handler global.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
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
      <div class="panel-body">
        <div id="pk-info" class="alert alert-info hidden">
          <p>Pinset bonus AKS27-RDM sudah dipotong dari stok saat resi discan packer. Kalau resi itu cancel atau diretur, stok <b>tidak naik sendiri</b>: scan resinya di sini setelah paket dibuka, lalu catat apakah pinsetnya ada.</p>
          <ul>
            <li><b>Ada</b> = stok +1 langsung.</li>
            <li><b>Tidak ada, pembeli tidak mengirim balik</b> = stok tetap.</li>
            <li><b>Tidak ada, lupa dimasukkan saat packing</b> = pinset masih di rak; koreksi +1 diajukan ke Purchasing.</li>
            <li><b>Rusak</b> = stok tetap.</li>
          </ul>
          <p>Satu resi hanya bisa dikonfirmasi sekali.</p>
          <p style="margin-bottom:0;"><b>Opname:</b> hitung pinset di tempat packer, masukkan jumlahnya. Stok sistem tidak ditampilkan; selisih dihitung otomatis dan baru berlaku setelah Purchasing menyetujui.</p>
        </div>

        <div class="pk-scan">
          <label for="pk-resi"><i class="fa fa-barcode"></i> Scan nomor resi</label>
          <button type="button" id="pk-ic" class="pk-ic" aria-label="Info halaman" aria-expanded="false">i</button>
          <input type="text" id="pk-resi" class="form-control" placeholder="Arahkan scanner ke barcode resi" autocomplete="off" />
          <div id="pk-pesan" class="alert hidden" role="status"></div>
        </div>

        <div id="pk-aksi" class="pk-aksi hidden">
          <div class="pk-aksi-h"><code id="pk-a-resi"></code> <span id="pk-a-jenis" class="label label-warning"></span></div>
          <p class="pk-mut">Buka paketnya. Apakah pinset bonus AKS27-RDM ada?</p>
          <div class="pk-tombol">
            <button type="button" class="btn btn-success pk-pilih" data-pilihan="ADA" data-alasan="">Pinset ADA</button>
            <button type="button" class="btn btn-default pk-pilih" data-pilihan="RUSAK" data-alasan="">Pinset RUSAK</button>
            <button type="button" id="pk-tidak" class="btn btn-warning">Pinset TIDAK ADA</button>
          </div>
          <div id="pk-tidak-box" class="pk-tidak hidden">
            <p>Kenapa tidak ada?</p>
            <button type="button" class="btn btn-default pk-pilih" data-pilihan="TIDAK_ADA" data-alasan="PEMBELI_TIDAK_KIRIM">Pembeli tidak mengirim balik</button>
            <button type="button" class="btn btn-default pk-pilih" data-pilihan="TIDAK_ADA" data-alasan="LUPA_DIMASUKKAN">Lupa dimasukkan saat packing</button>
          </div>
        </div>

        <ul class="pk-tab" role="tablist">
          <li class="aktif"><button type="button" data-tab="menunggu">Menunggu konfirmasi <span id="pk-n" class="badge">0</span></button></li>
          <li><button type="button" data-tab="riwayat">Riwayat konfirmasi</button></li>
          <li><button type="button" data-tab="opname">Opname stok</button></li>
        </ul>

        <div class="pk-pane" data-pane="menunggu">
          <div class="table-responsive">
            <table class="table table-hover" id="pk-tabel">
              <thead><tr><th>No. resi</th><th>Jenis</th><th>Tgl keluar</th><th class="text-right">Umur (hari)</th></tr></thead>
              <tbody><tr><td colspan="4" class="pk-mut text-center">Memuat…</td></tr></tbody>
            </table>
          </div>
          <p class="pk-mut">Klik salah satu baris untuk langsung memeriksa resinya.</p>
        </div>

        <div class="pk-pane hidden" data-pane="riwayat">
          <div class="table-responsive">
            <table class="table" id="pk-t-riwayat">
              <thead><tr><th>Waktu</th><th>No. resi</th><th>Jenis</th><th>Hasil</th><th>Oleh</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <div class="pk-pane hidden" data-pane="opname">
          <div class="row pk-opname">
            <div class="col-md-3 col-sm-4 col-xs-12"><label for="pk-fisik">Jumlah fisik (pcs)</label>
              <input type="number" id="pk-fisik" class="form-control" min="0" step="1" placeholder="0" /></div>
            <div class="col-md-3 col-sm-4 col-xs-12"><label for="pk-alasan">Alasan</label>
              <select id="pk-alasan" class="form-control">
                <option value="HITUNG_ULANG">Hitung ulang rutin</option>
                <option value="BARANG_HILANG">Barang hilang</option>
                <option value="SALAH_CATAT">Salah catat</option>
                <option value="LAINNYA">Lainnya</option>
              </select></div>
            <div class="col-md-4 col-sm-4 col-xs-12"><label for="pk-catatan">Catatan <small class="pk-mut">(wajib jika Lainnya)</small></label>
              <input type="text" id="pk-catatan" class="form-control" maxlength="150" /></div>
            <div class="col-md-2 col-xs-12"><label class="hidden-xs">&nbsp;</label><button type="button" id="pk-opname" class="btn btn-primary btn-block">Ajukan</button></div>
          </div>
          <h4 class="pk-sub">Pengajuan terakhir</h4>
          <div class="table-responsive">
            <table class="table" id="pk-t-ajuan">
              <thead><tr><th>Tanggal</th><th>Jenis</th><th>Resi</th><th>Alasan</th><th>Status</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
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
  var baru = false;      // true = isi kolom resi adalah hasil scan sebelumnya; scan berikutnya menimpa, bukan menyambung
  var tundaOtomatis = null;
  var tundaPesan = null;
  var sibuk = false;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function pesan(tipe, teks) {
    clearTimeout(tundaPesan);
    $('#pk-pesan').removeClass('hidden alert-success alert-danger alert-warning').addClass('alert-' + tipe).text(teks);
    if (tipe === 'success') { tundaPesan = setTimeout(function () { $('#pk-pesan').addClass('hidden'); }, 6000); }
  }
  function jsonGagal(xhr) {
    // Bukan JSON = ada output nyasar (warning PHP / halaman error CI).
    pesan('danger', 'Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200));
  }
  function tutupAksi() { aktif = null; $('#pk-aksi').addClass('hidden'); $('#pk-tidak-box').addClass('hidden'); }
  function fokusScan() { $('#pk-resi').focus().select(); }

  $root.on('click', '#pk-ic', function () {
    var $i = $('#pk-info').toggleClass('hidden');
    $(this).attr('aria-expanded', !$i.hasClass('hidden'));
  });

  $root.on('click', '.pk-tab button', function () {
    var t = $(this).data('tab');
    $('.pk-tab li').removeClass('aktif'); $(this).parent().addClass('aktif');
    $('.pk-pane').addClass('hidden').filter('[data-pane="' + t + '"]').removeClass('hidden');
  });

  function muat() {
    $.getJSON(URL.data).done(function (res) {
      if (Number(res.code) !== 200 || !res.data) { pesan('danger', res.message || 'Gagal memuat data.'); return; }
      var d = res.data, $tb = $('#pk-tabel tbody').empty();
      $('#pk-n').text(d.menunggu.length);
      if (!d.menunggu.length) { $tb.append('<tr><td colspan="4" class="pk-mut text-center">Tidak ada resi yang menunggu.</td></tr>'); }
      $.each(d.menunggu, function (i, r) {
        $tb.append('<tr class="pk-baris-resi' + (r.terlambat ? ' danger' : '') + '" data-resi="' + esc(r.noresi) + '" style="cursor:pointer;"><td><code>' + esc(r.noresi) + '</code></td><td>' +
          (r.jenis === 'CANCEL' ? 'Cancel' : 'Retur') + '</td><td>' + esc(r.tgl_jual) + '</td><td class="text-right">' + esc(r.umur) + (r.terlambat ? ' <b class="text-danger">terlambat</b>' : '') + '</td></tr>');
      });
      var $a = $('#pk-t-ajuan tbody').empty();
      if (!d.ajuan.length) { $a.append('<tr><td colspan="5" class="pk-mut text-center">Belum ada pengajuan.</td></tr>'); }
      $.each(d.ajuan, function (i, r) {
        $a.append('<tr><td>' + esc(r.diajukan) + '</td><td>' + (r.jenis === 'OPNAME' ? 'Opname' : 'Koreksi lupa dimasukkan') + '</td><td><code>' + esc(r.noresi || '') + '</code></td><td>' + esc(r.alasan) +
          '</td><td>' + (STATUS_AJUAN[r.status] || '') + (r.catatan_putus ? ' <small class="pk-mut">' + esc(r.catatan_putus) + '</small>' : '') + '</td></tr>');
      });
      var $k = $('#pk-t-riwayat tbody').empty();
      if (!d.kembali.length) { $k.append('<tr><td colspan="5" class="pk-mut text-center">Belum ada konfirmasi.</td></tr>'); }
      $.each(d.kembali, function (i, r) {
        $k.append('<tr><td>' + esc(r.created) + '</td><td><code>' + esc(r.noresi) + '</code></td><td>' + (r.jenis === 'CANCEL' ? 'Cancel' : 'Retur') + '</td><td>' + esc(HASIL[r.status] || r.status) +
          (r.catatan ? ' <small class="pk-mut">' + esc(r.catatan) + '</small>' : '') + '</td><td>' + esc(r.oleh) + '</td></tr>');
      });
    }).fail(jsonGagal);
  }

  function periksa() {
    clearTimeout(tundaOtomatis);
    var resi = $.trim($('#pk-resi').val());
    if (!resi || sibuk) { return; }
    sibuk = true;
    tutupAksi();
    $.post(URL.cek, { noresi: resi }, null, 'json').done(function (res) {
      baru = true;
      if (Number(res.code) !== 200 || !res.data) { pesan('danger', res.message); fokusScan(); return; }
      $('#pk-pesan').addClass('hidden');
      aktif = res.data;
      $('#pk-a-resi').text(aktif.noresi);
      $('#pk-a-jenis').text(aktif.jenis === 'CANCEL' ? 'Paket cancel' : 'Retur');
      $('#pk-aksi').removeClass('hidden');
      fokusScan();   // teks terpilih: scan berikutnya menimpa
    }).fail(jsonGagal).always(function () { sibuk = false; });
  }

  // Scan otomatis diperiksa: scanner mengirim Enter; tempel (paste) dan jeda ketik sesudah resi cukup panjang juga memicu.
  // Scan berikutnya selalu menimpa isi lama (bukan menyambung).
  $root.on('keydown', '#pk-resi', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); periksa(); return; }
    if (baru && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) { $(this).val(''); baru = false; tutupAksi(); }
  });
  $root.on('paste', '#pk-resi', function () {
    if (baru) { $(this).val(''); baru = false; }
    setTimeout(periksa, 50);
  });
  $root.on('input', '#pk-resi', function () {
    clearTimeout(tundaOtomatis);
    if ($.trim($(this).val()).length >= 10) { tundaOtomatis = setTimeout(periksa, 700); }
  });
  $root.on('focus', '#pk-resi', function () { var el = this; setTimeout(function () { el.select(); }, 0); });
  $root.on('click', '.pk-baris-resi', function () { $('#pk-resi').val($(this).data('resi')); periksa(); window.scrollTo(0, 0); });
  $root.on('click', '#pk-tidak', function () { $('#pk-tidak-box').toggleClass('hidden'); });

  $root.on('click', '.pk-pilih', function () {
    if (!aktif) { return; }
    var pilihan = String($(this).data('pilihan')), alasan = String($(this).data('alasan')), teks = $.trim($(this).text());
    if (!window.confirm('Simpan untuk resi ' + aktif.noresi + ': "' + teks + '"?\nKeputusan tidak bisa diubah.')) { return; }
    var $b = $('.pk-pilih').prop('disabled', true);
    $.post(URL.simpan, { noresi: aktif.noresi, pilihan: pilihan, alasan: alasan }, null, 'json').done(function (res) {
      var ok = Number(res.code) === 200;
      pesan(ok ? 'success' : 'danger', res.message);
      if (ok) { tutupAksi(); $('#pk-resi').val(''); baru = false; }
      fokusScan();
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
  fokusScan();
})();
</script>
<style>
  /* Font template iresis kecil (12-13px); menu ini dibaca dari jauh, jadi dibesarkan. Warna .text-muted template
     (#ccd0d9) hampir tak terbaca di latar putih, jadi teks sekunder memakai .pk-mut. */
  #pk-root .panel-body { font-size: 16px; color: #333; }
  #pk-root .panel-title { font-size: 20px; }
  #pk-root label { font-size: 15px; font-weight: 600; color: #444; }
  #pk-root .form-control, #pk-root .btn { height: 46px; font-size: 17px; }
  #pk-root .alert { font-size: 16px; }
  #pk-root .alert ul { margin: 0 0 10px 18px; padding: 0; }
  #pk-root .pk-mut { color: #6b7280; font-size: 15px; }
  #pk-root .pk-ic { width: 26px; height: 26px; border-radius: 50%; border: 1px solid #999; background: #fff; color: #555; font: 600 14px/1 monospace; cursor: pointer; padding: 0; margin-left: 8px; vertical-align: top; }
  #pk-root .pk-ic:hover, #pk-root .pk-ic[aria-expanded="true"] { color: #337ab7; border-color: #337ab7; background: #eaf2fa; }
  #pk-root .pk-scan { display: flow-root; margin: 4px 0 0; }
  #pk-root .pk-scan label { font-size: 17px; display: inline-block; margin-bottom: 6px; }
  #pk-root #pk-resi { height: 64px; font-size: 26px; font-weight: 600; letter-spacing: .5px; border: 2px solid #337ab7; }
  #pk-root #pk-resi:focus { border-color: #e91e63; box-shadow: 0 0 0 3px rgba(233,30,99,.15); }
  #pk-root .pk-scan .alert { margin: 12px 0 0; float: none; width: auto; }
  #pk-root .pk-aksi { clear: both; margin: 16px 0 0; padding: 18px; border: 1px solid #e5e7eb; border-radius: 6px; background: #f7f8fa; }
  #pk-root .pk-aksi.hidden { display: none; }
  #pk-root .pk-aksi-h { font-size: 24px; }
  #pk-root .pk-aksi-h .label { font-size: 14px; padding: 5px 10px; vertical-align: middle; }
  #pk-root .pk-aksi p { margin: 6px 0 12px; }
  #pk-root .pk-tombol .btn { margin: 0 8px 8px 0; padding: 0 24px; min-width: 150px; }
  #pk-root .pk-tidak { margin-top: 6px; padding-top: 10px; border-top: 1px solid #ddd; }
  #pk-root .pk-tidak .btn { margin: 0 8px 8px 0; padding: 0 18px; }
  #pk-root .pk-tab { clear: both; list-style: none; margin: 28px 0 14px; padding: 0; display: flex; flex-wrap: wrap; border-bottom: 2px solid #e5e7eb; }
  #pk-root .pk-tab li { margin-bottom: -2px; }
  #pk-root .pk-tab button { background: none; border: 0; border-bottom: 3px solid transparent; padding: 10px 20px; font-size: 17px; font-weight: 600; color: #6b7280; cursor: pointer; }
  #pk-root .pk-tab button:hover { color: #333; }
  #pk-root .pk-tab li.aktif button { color: #0a2345; border-bottom-color: #e91e63; }
  #pk-root .pk-tab .badge { font-size: 14px; vertical-align: middle; }
  #pk-root .pk-pane.hidden { display: none; }
  #pk-root .table { margin-bottom: 8px; }
  #pk-root .table > thead > tr > th { font-size: 14px; padding: 10px 14px; text-transform: uppercase; letter-spacing: .3px; }
  #pk-root .table > tbody > tr > td { font-size: 16px; padding: 10px 14px; vertical-align: middle; color: #333; }
  #pk-root .table code { font-size: 16px; font-weight: 600; }
  #pk-root .pk-sub { font-size: 18px; font-weight: 600; margin: 26px 0 10px; }
  #pk-root .pk-opname { margin-top: 4px; }
</style>
