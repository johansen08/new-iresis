<?php
// View menu TIM PACKER -> Salah Ambil Special. Dimuat lewat AJAX (SPA) oleh
// plugins.js: semua handler diikat ke #sas-root supaya ikut hilang saat user
// pindah menu, dan kedua form diberi class "nojs" + preventDefault sendiri --
// tanpa itu handler global plugins.js mengganti seluruh .page-content-wrap
// saat request berjalan, input resi hilang dari DOM, dan scan yang diketik
// scanner selama itu lenyap (lihat komentar di packer/scan_packer.php).
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Salah Ambil Special</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Hak aksesnya sama dengan menu <em>Scan Resi Packer (Webcam)</em>; minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<div id="sas-root">
  <div class="row">
    <div class="col-md-12">

      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Salah Ambil Special</strong>
            <small class="text-muted" style="margin-left:8px;">resi 1 SKU / 1 qty yang salah diambil picker, dilaporkan sekaligus</small>
          </h3>
        </div>
        <div class="panel-body">
          <form class="form-horizontal nojs" id="sas-form-sku" autocomplete="off">
            <div class="form-group" style="margin-bottom:0;">
              <label class="col-md-2 col-xs-12 control-label">SKU seharusnya</label>
              <div class="col-md-3 col-xs-12">
                <input type="text" id="sas-sku-benar" class="form-control" placeholder="mis. BSBI-4" />
                <p class="help-block" id="sas-info-benar" style="margin-bottom:0;"></p>
              </div>
              <label class="col-md-2 col-xs-12 control-label">SKU terambil (salah)</label>
              <div class="col-md-3 col-xs-12">
                <input type="text" id="sas-sku-salah" class="form-control" placeholder="mis. BSBI-5" />
                <p class="help-block" id="sas-info-salah" style="margin-bottom:0;"></p>
              </div>
              <div class="col-md-2 col-xs-12">
                <button type="submit" class="btn btn-primary btn-block" id="sas-btn-kunci"><i class="fa fa-lock"></i> Kunci &amp; Mulai Scan</button>
                <button type="button" class="btn btn-warning btn-block hidden" id="sas-btn-ganti"><i class="fa fa-unlock"></i> Ganti SKU</button>
              </div>
            </div>
            <div class="alert alert-danger hidden" id="sas-pesan-sku" style="margin:10px 0 0 0;"></div>
          </form>
        </div>
      </div>

      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Scan Resi</strong>
            <span class="pull-right">
              <span class="label label-success" id="sas-cnt-ok">Tercatat: 0</span>
              <span class="label label-danger" id="sas-cnt-tolak">Ditolak: 0</span>
            </span>
          </h3>
        </div>
        <div class="panel-body">
          <form class="form-horizontal nojs" id="sas-form-scan" autocomplete="off">
            <div class="form-group">
              <div class="col-md-12">
                <input type="text" id="sas-noresi" class="form-control input-lg"
                  placeholder="Kunci SKU dulu, lalu scan nomor resi di sini" disabled />
              </div>
            </div>
          </form>
          <p class="text-muted" style="margin-bottom:6px;">
            Daftar di bawah hanya untuk sesi ini di layar; catatan resminya ada di menu <em>Daftar Masalah Picker</em>.
          </p>
          <table class="table table-bordered table-striped" id="sas-table" style="margin-bottom:0;">
            <thead>
              <tr>
                <th style="width:50px;">#</th>
                <th style="width:220px;">Nomor resi</th>
                <th>Hasil</th>
                <th style="width:200px;">Picker</th>
                <th style="width:90px;">Jam</th>
              </tr>
            </thead>
            <tbody>
              <tr id="sas-kosong"><td colspan="5" class="text-muted text-center">Belum ada scan</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  var $root = $('#sas-root');
  var URL = {
    cariSku: 'salah-ambil-special/cari-sku',
    cekSku:  'salah-ambil-special/cek-sku',
    scan:    'salah-ambil-special/scan-resi'
  };
  var BARIS_KOSONG = '<tr id="sas-kosong"><td colspan="5" class="text-muted text-center">Belum ada scan</td></tr>';

  var terkunci = false, skuBenar = '', skuSalah = '';
  // Scan diantre dan dikirim satu per satu: scanner bisa menembak lebih cepat
  // dari balasan server, dan urutan baris di tabel harus sama dengan urutan scan.
  var antrian = [], sedangKirim = false;
  var nomor = 0, jumlahOk = 0, jumlahTolak = 0;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  // Semua suara lewat suaraScan (main.php): memotong durasi dan mereset posisi
  // supaya scan kedua yang datang sebelum suara pertama habis tetap berbunyi.
  function bunyi(id) {
    if (typeof suaraScan === 'function') { suaraScan(id); return; }
    var el = document.getElementById(id);
    if (el && typeof el.play === 'function') { el.currentTime = 0; el.play(); }
  }
  function jam() {
    var d = new Date();
    return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2) + ':' + ('0' + d.getSeconds()).slice(-2);
  }
  function perbaruiCounter() {
    $('#sas-cnt-ok').text('Tercatat: ' + jumlahOk);
    $('#sas-cnt-tolak').text('Ditolak: ' + jumlahTolak);
  }
  function infoSku(row) {
    return (row.nama_sku || '-') + ' — rak ' + (row.no_rak || '-');
  }
  function tolakSku(pesan) {
    $('#sas-pesan-sku').removeClass('hidden').text(pesan);
    bunyi('audio-fail');
  }

  // ---------- live search SKU ----------
  // Saran dari master tblsku muncul saat mengetik, supaya kode yang dikunci
  // pasti ada di database (kode SKU panjang seperti 3100A3100B rawan typo).
  // Dropdown dibuat sendiri: jquery-ui.min.js di proyek ini build ringkas
  // (core, widget, mouse, position, efek) TANPA widget autocomplete/menu.
  function pasangSaran($input, $info, $berikutnya) {
    var $saran = $('<ul class="sas-saran hidden"></ul>').insertAfter($input);
    var item = [], aktif = -1, timer = null, requestTerakhir = 0;

    function tutup() { $saran.addClass('hidden').empty(); item = []; aktif = -1; }
    function sorot(i) {
      aktif = i;
      $saran.children().removeClass('aktif').eq(i).addClass('aktif');
    }
    function pilih(i) {
      if (!item[i]) { return; }
      $input.val(item[i].value);
      $info.text(infoSku(item[i]));
      tutup();
      $berikutnya.focus();
    }
    function render() {
      $saran.empty();
      if (item.length === 0) { tutup(); return; }
      $.each(item, function (i, it) {
        $saran.append('<li data-i="' + i + '"><strong>' + esc(it.value) + '</strong> — ' +
          esc(it.nama_sku || '-') + ' <span class="text-muted">(rak ' + esc(it.no_rak || '-') + ')</span></li>');
      });
      $saran.removeClass('hidden');
      sorot(0);
    }
    function cari() {
      var term = $input.val().trim();
      if (term === '' || $input.prop('readonly')) { tutup(); return; }
      var nomorRequest = ++requestTerakhir;
      $.getJSON(URL.cariSku, { term: term })
        .done(function (data) {
          if (nomorRequest !== requestTerakhir) { return; } // balasan lama, abaikan
          item = $.isArray(data) ? data : [];
          render();
        })
        .fail(function () { tutup(); });
    }

    $input.on('input', function () {
      $info.text(''); // ketikan berubah -> keterangan lama tidak berlaku
      clearTimeout(timer);
      timer = setTimeout(cari, 150);
    });
    $input.on('keydown', function (e) {
      var terbuka = !$saran.hasClass('hidden');
      if (e.key === 'ArrowDown' && terbuka) { e.preventDefault(); sorot(Math.min(aktif + 1, item.length - 1)); }
      else if (e.key === 'ArrowUp' && terbuka) { e.preventDefault(); sorot(Math.max(aktif - 1, 0)); }
      else if (e.key === 'Escape' && terbuka) { e.preventDefault(); tutup(); }
      else if (e.key === 'Enter') {
        // Enter: pilih saran yang disorot; kalau tidak ada saran, pindah ke
        // field berikutnya -- jangan submit form dengan field kedua kosong.
        e.preventDefault();
        if (terbuka && aktif >= 0) { pilih(aktif); } else { $berikutnya.focus(); }
      }
    });
    $saran.on('mousedown', 'li', function (e) {
      e.preventDefault(); // jangan blur input sebelum pilih
      pilih(Number($(this).data('i')));
    });
    $input.on('blur', function () { setTimeout(tutup, 150); });
  }
  pasangSaran($('#sas-sku-benar'), $('#sas-info-benar'), $('#sas-sku-salah'));
  pasangSaran($('#sas-sku-salah'), $('#sas-info-salah'), $('#sas-btn-kunci'));

  // ---------- kunci SKU ----------
  $root.on('submit', '#sas-form-sku', function (e) {
    e.preventDefault();
    if (terkunci) { return; }

    var benar = $('#sas-sku-benar').val().trim();
    var salah = $('#sas-sku-salah').val().trim();
    $('#sas-pesan-sku').addClass('hidden').text('');
    $('#sas-btn-kunci').prop('disabled', true);

    $.post(URL.cekSku, { sku_benar: benar, sku_salah: salah }, null, 'json')
      .done(function (res) {
        if (Number(res.code) !== 200 || !res.data) { tolakSku(res.message || 'SKU tidak valid'); return; }
        skuBenar = res.data.sku_benar.id_sku;
        skuSalah = res.data.sku_salah.id_sku;
        $('#sas-sku-benar').val(skuBenar).prop('readonly', true);
        $('#sas-sku-salah').val(skuSalah).prop('readonly', true);
        $('#sas-info-benar').text(infoSku(res.data.sku_benar));
        $('#sas-info-salah').text(infoSku(res.data.sku_salah));
        terkunci = true;
        $('#sas-btn-kunci').addClass('hidden');
        $('#sas-btn-ganti').removeClass('hidden');
        $('#sas-noresi').prop('disabled', false)
          .attr('placeholder', 'Scan nomor resi yang terambil ' + skuSalah + ' (seharusnya ' + skuBenar + ')')
          .focus();
      })
      .fail(function (xhr) {
        // Bukan JSON = ada output nyasar (warning PHP / halaman error CI).
        tolakSku('Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200));
      })
      .always(function () {
        $('#sas-btn-kunci').prop('disabled', false);
      });
  });

  // ---------- ganti SKU ----------
  $root.on('click', '#sas-btn-ganti', function () {
    if (!confirm('Buka kunci SKU dan kosongkan daftar scan di layar?\nResi yang sudah tercatat tetap tersimpan.')) { return; }
    terkunci = false; skuBenar = ''; skuSalah = '';
    antrian = [];
    nomor = 0; jumlahOk = 0; jumlahTolak = 0;
    perbaruiCounter();
    $('#sas-sku-benar, #sas-sku-salah').prop('readonly', false);
    $('#sas-info-benar, #sas-info-salah').text('');
    $('#sas-pesan-sku').addClass('hidden').text('');
    $('#sas-btn-ganti').addClass('hidden');
    $('#sas-btn-kunci').removeClass('hidden');
    $('#sas-noresi').val('').prop('disabled', true).attr('placeholder', 'Kunci SKU dulu, lalu scan nomor resi di sini');
    $('#sas-table tbody').html(BARIS_KOSONG);
    $('#sas-sku-benar').focus();
  });

  // ---------- scan ----------
  // Nilai input langsung diambil lalu dikosongkan, jadi scanner boleh
  // mengetik resi berikutnya kapan saja.
  $root.on('submit', '#sas-form-scan', function (e) {
    e.preventDefault();
    var nilai = $('#sas-noresi').val().trim();
    $('#sas-noresi').val('').focus();
    if (nilai === '' || !terkunci) { return; }
    antrian.push(nilai);
    prosesAntrian();
  });

  function prosesAntrian() {
    if (sedangKirim || antrian.length === 0) { return; }
    sedangKirim = true;
    var noresi = antrian.shift();

    $.post(URL.scan, { noresi: noresi, sku_benar: skuBenar, sku_salah: skuSalah }, null, 'json')
      .done(function (res) {
        if (Number(res.code) === 201 && res.data) {
          tambahBaris(noresi, true, res.data.sku + ' → terambil ' + res.data.sku_salah, res.data.nama_picker);
        } else {
          tambahBaris(noresi, false, res.message || 'Ditolak', '');
        }
      })
      .fail(function (xhr) {
        tambahBaris(noresi, false, 'Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200), '');
      })
      .always(function () {
        sedangKirim = false;
        prosesAntrian();
      });
  }

  function tambahBaris(noresi, ok, keterangan, picker) {
    $('#sas-kosong').remove();
    nomor++;
    if (ok) { jumlahOk++; } else { jumlahTolak++; }
    perbaruiCounter();
    bunyi(ok ? 'audio-alert' : 'audio-fail');

    var label = ok
      ? '<span class="label label-success">Tercatat</span> '
      : '<span class="label label-danger">Ditolak</span> ';
    $('#sas-table tbody').prepend(
      '<tr class="' + (ok ? 'success' : 'danger') + '">' +
        '<td>' + nomor + '</td>' +
        '<td><strong>' + esc(noresi) + '</strong></td>' +
        '<td>' + label + esc(keterangan) + '</td>' +
        '<td>' + esc(picker || '-') + '</td>' +
        '<td>' + jam() + '</td>' +
      '</tr>'
    );
  }

  $('#sas-sku-benar').focus();
})();
</script>

<style>
  #sas-table td { vertical-align: middle; }
  #sas-noresi { font-size: 20px; }
  /* Dropdown saran SKU (buatan sendiri, lihat pasangSaran) */
  .sas-saran {
    position: absolute; left: 15px; right: 15px; z-index: 99999;
    margin: 0; padding: 0; list-style: none;
    max-height: 260px; overflow-y: auto; font-size: 13px;
    background: #fff; border: 1px solid #d1d5db; box-shadow: 0 6px 12px rgba(0,0,0,0.15);
  }
  .sas-saran li { padding: 6px 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
  .sas-saran li:hover, .sas-saran li.aktif { background: #337ab7; color: #fff; }
  .sas-saran li.aktif .text-muted, .sas-saran li:hover .text-muted { color: #dbe7f3; }
  #sas-form-sku .col-md-3 { position: relative; }
</style>
