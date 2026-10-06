<div class="row" id="upload-resi-spesial-container">
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Upload Resi Spesial</strong></h3>
      </div>
      <div class="panel-body">
        <p class="text-muted">
          Upload file Excel (.xlsx/.xls) berisi daftar No Resi untuk di-picker
          sekaligus, tanpa scan satu per satu. Kolom yang dibaca hanya
          <strong>No Resi</strong> -- kolom lain (No Picklist, SKU, No Pesanan)
          boleh ikut ada di file, tidak masalah.
        </p>

        <div class="form-group">
          <label class="control-label">Nama Picker</label>
          <div style="position: relative;">
            <input type="text" id="urs_picker_input" class="form-control" autocomplete="off" placeholder="ketik no absen / nama">
            <div class="urs-saran" id="urs_saran_picker"></div>
          </div>
          <input type="hidden" id="urs_id_pegawaipicker" value="">
        </div>

        <div class="form-group">
          <label class="control-label">Status Performa</label>
          <input type="text" class="form-control" value="1_SKU_PICKER (otomatis untuk semua resi di batch ini)" disabled>
        </div>

        <div class="form-group">
          <label class="control-label">File Excel</label>
          <input type="file" id="urs_file" class="form-control" accept=".xlsx,.xls">
        </div>

        <button type="button" class="btn btn-info" id="urs_btn_validasi">
          <i class="fa fa-check-square-o"></i> Validasi
        </button>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="panel panel-info" id="urs_panel_hasil" style="display:none;">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Hasil Validasi</strong></h3>
      </div>
      <div class="panel-body">
        <div class="alert alert-info" id="urs_ringkasan_validasi"></div>

        <div id="urs_wrap_tidak_valid" style="display:none;">
          <label style="color:#c0392b;">No Resi TIDAK valid:</label>
          <div style="max-height: 260px; overflow-y:auto; border:1px solid #eee; border-radius: 6px;">
            <table class="table table-condensed table-striped" style="margin:0;">
              <thead><tr><th>No Resi</th><th>Alasan</th></tr></thead>
              <tbody id="urs_tbody_tidak_valid"></tbody>
            </table>
          </div>
        </div>

        <div style="margin-top: 15px;">
          <button type="button" class="btn btn-success" id="urs_btn_simpan" style="display:none;">
            <i class="fa fa-save"></i> Lanjutkan Simpan <span id="urs_jumlah_simpan">0</span> Resi Valid
          </button>
          <button type="button" class="btn btn-default" id="urs_btn_batal" style="display:none;">Batal</button>
        </div>
      </div>
    </div>

    <div class="panel panel-success" id="urs_panel_simpan" style="display:none;">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Hasil Simpan</strong></h3>
      </div>
      <div class="panel-body">
        <div class="alert alert-success" id="urs_ringkasan_simpan"></div>
        <div id="urs_wrap_gagal_simpan" style="display:none;">
          <label style="color:#c0392b;">Gagal disimpan:</label>
          <div style="max-height: 260px; overflow-y:auto; border:1px solid #eee; border-radius: 6px;">
            <table class="table table-condensed table-striped" style="margin:0;">
              <thead><tr><th>No Resi</th><th>Alasan</th></tr></thead>
              <tbody id="urs_tbody_gagal_simpan"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  #upload-resi-spesial-container .urs-saran {
    position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
    background: #fff; border: 1px solid #ccc; border-top: none;
    max-height: 220px; overflow-y: auto; display: none;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  #upload-resi-spesial-container .urs-saran-item { padding: 6px 10px; cursor: pointer; display: flex; gap: 8px; align-items: center; }
  #upload-resi-spesial-container .urs-saran-item.aktif, #upload-resi-spesial-container .urs-saran-item:hover { background: #fff3cd; }
  #upload-resi-spesial-container .urs-saran-absen { font-weight: 800; color: #1a2a6c; min-width: 40px; }
  #upload-resi-spesial-container .urs-saran-nama { font-weight: 600; color: #333; flex: 1; }
  #upload-resi-spesial-container .urs-saran-role { font-size: 0.7rem; font-weight: 700; background: #e0e0e0; color: #555; padding: 2px 6px; border-radius: 3px; }
  #upload-resi-spesial-container .urs-saran-kosong { padding: 6px 10px; color: #999; font-style: italic; }
</style>

<script type="text/javascript">
(function () {
  // Roster diurai di server dari nama "NAMA - JABATAN - NOABSEN", pola sama
  // dengan saran no absen di menu Scan Paket NDD New (Lost Scan).
  var ROSTER_PICKER = <?= json_encode($roster_picker, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var MAKS_SARAN = 8;

  function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

  var $input = $('#urs_picker_input');
  var $saran = $('#urs_saran_picker');
  var $idPicker = $('#urs_id_pegawaipicker');
  var terpilih = null;
  var indexAktif = -1;

  function cari(q) {
    q = (q || '').trim().toLowerCase();
    if (q === '') return ROSTER_PICKER.slice(0, MAKS_SARAN);

    if (/^\d+$/.test(q)) {
      var angka = parseInt(q, 10);
      var persis = [], lain = [];
      $.each(ROSTER_PICKER, function (_, p) {
        if (parseInt(p.no_absen, 10) === angka) persis.push(p);
        else if (p.no_absen.indexOf(q) !== -1) lain.push(p);
      });
      return persis.concat(lain).slice(0, MAKS_SARAN);
    }

    return $.grep(ROSTER_PICKER, function (p) {
      return p.nama.toLowerCase().indexOf(q) !== -1;
    }).slice(0, MAKS_SARAN);
  }

  function render(q) {
    var hasil = cari(q);
    indexAktif = -1;
    terpilih = null;
    $idPicker.val('');

    if (hasil.length === 0) {
      $saran.html('<div class="urs-saran-kosong">Tidak ada yang cocok</div>').show();
      return;
    }

    $saran.html($.map(hasil, function (p, i) {
      return '<div class="urs-saran-item" data-i="' + i + '">' +
        '<span class="urs-saran-absen">' + esc(p.no_absen) + '</span>' +
        '<span class="urs-saran-nama">' + esc(p.nama) + '</span>' +
        '<span class="urs-saran-role">' + esc(p.role) + '</span>' +
        '</div>';
    }).join('')).show();

    $saran.data('hasil', hasil);

    // Satu-satunya saran yang cocok -> anggap terpilih otomatis, tapi
    // field tetap bisa diubah lagi kalau user lanjut mengetik.
    if (hasil.length === 1) {
      pilih(hasil[0]);
    }
  }

  function pilih(p) {
    terpilih = p;
    $input.val(p.nama + ' (' + p.no_absen + ')');
    $idPicker.val(p.id_pegawai);
    $saran.hide();
  }

  $input.on('input focus', function () { render($(this).val()); });
  $input.on('blur', function () { setTimeout(function () { $saran.hide(); }, 150); });

  $saran.on('mousedown', '.urs-saran-item', function (e) {
    e.preventDefault();
    var hasil = $saran.data('hasil') || [];
    var i = $(this).data('i');
    if (hasil[i]) pilih(hasil[i]);
  });

  $input.on('keydown', function (e) {
    var terbuka = $saran.is(':visible');
    if (!terbuka) return;
    var $item = $saran.children('.urs-saran-item');

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      indexAktif = Math.min(indexAktif + 1, $item.length - 1);
      $item.removeClass('aktif').eq(indexAktif).addClass('aktif');
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      indexAktif = Math.max(indexAktif - 1, 0);
      $item.removeClass('aktif').eq(indexAktif).addClass('aktif');
    } else if (e.key === 'Enter') {
      e.preventDefault();
      var hasil = $saran.data('hasil') || [];
      if (indexAktif >= 0 && hasil[indexAktif]) pilih(hasil[indexAktif]);
      else if (hasil.length === 1) pilih(hasil[0]);
    } else if (e.key === 'Escape') {
      $saran.hide();
    }
  });

  // ===== Validasi file =====
  var dataValidTerakhir = [];

  $('#urs_btn_validasi').on('click', function () {
    var file = document.getElementById('urs_file').files[0];

    if (!$idPicker.val()) {
      noty({ text: 'Pilih Nama Picker dari saran dulu (ketik no absen / nama)', layout: 'topRight', type: 'warning', timeout: 3000 });
      $input.focus();
      return;
    }
    if (!file) {
      noty({ text: 'Pilih file Excel dulu', layout: 'topRight', type: 'warning', timeout: 3000 });
      return;
    }

    var formData = new FormData();
    formData.append('resiFile', file);

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memvalidasi...');

    $.ajax({
      url: 'picker/validasi-upload-resi-spesial',
      type: 'post',
      data: formData,
      contentType: false,
      processData: false,
      timeout: 600000,
      success: function (res) {
        if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) {} }

        if (!res || (res.code !== 200 && res.code !== 201)) {
          noty({ text: (res && res.message) || 'Validasi gagal', layout: 'topRight', type: 'error', timeout: 4000 });
          return;
        }

        var d = res.data || res;
        dataValidTerakhir = d.valid || [];

        $('#urs_ringkasan_validasi').html(
          'Total baris di file: <strong>' + d.total_baris + '</strong> | ' +
          'No Resi unik: <strong>' + d.total_unik + '</strong><br>' +
          '<span style="color:#27ae60;">Valid: <strong>' + d.total_valid + '</strong></span> | ' +
          '<span style="color:#c0392b;">Tidak valid: <strong>' + d.total_tidak_valid + '</strong></span>'
        );

        var $tbody = $('#urs_tbody_tidak_valid').empty();
        if (d.tidak_valid && d.tidak_valid.length > 0) {
          $.each(d.tidak_valid, function (_, row) {
            $tbody.append('<tr><td>' + esc(row.noresi) + '</td><td>' + esc(row.alasan) + '</td></tr>');
          });
          $('#urs_wrap_tidak_valid').show();
        } else {
          $('#urs_wrap_tidak_valid').hide();
        }

        $('#urs_panel_simpan').hide();
        $('#urs_panel_hasil').show();

        if (d.total_valid > 0) {
          $('#urs_jumlah_simpan').text(d.total_valid);
          $('#urs_btn_simpan').show();
          $('#urs_btn_batal').show();
        } else {
          $('#urs_btn_simpan').hide();
          $('#urs_btn_batal').hide();
        }
      },
      error: function (xhr) {
        var msg = 'Gagal menghubungi server';
        try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
        noty({ text: msg, layout: 'topRight', type: 'error', timeout: 4000 });
      },
      complete: function () {
        $btn.prop('disabled', false).html('<i class="fa fa-check-square-o"></i> Validasi');
      }
    });
  });

  $('#urs_btn_batal').on('click', function () {
    dataValidTerakhir = [];
    $('#urs_panel_hasil').hide();
  });

  // ===== Simpan setelah konfirmasi =====
  $('#urs_btn_simpan').on('click', function () {
    if (dataValidTerakhir.length === 0) return;

    if (!confirm('Simpan ' + dataValidTerakhir.length + ' resi sebagai di-picker oleh ' + $input.val() + ' dengan status 1_SKU_PICKER?')) {
      return;
    }

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    $('#urs_btn_batal').prop('disabled', true);

    $.ajax({
      url: 'picker/simpan-upload-resi-spesial',
      type: 'post',
      data: {
        id_pegawaipicker: $idPicker.val(),
        list_noresi: dataValidTerakhir
      },
      timeout: 600000,
      success: function (res) {
        if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) {} }

        if (!res || (res.code !== 200 && res.code !== 201)) {
          noty({ text: (res && res.message) || 'Simpan gagal', layout: 'topRight', type: 'error', timeout: 4000 });
          return;
        }

        var d = res.data || res;

        $('#urs_ringkasan_simpan').html(
          'Diminta: <strong>' + d.total_diminta + '</strong> | ' +
          '<span style="color:#27ae60;">Berhasil: <strong>' + d.total_berhasil + '</strong></span> | ' +
          '<span style="color:#c0392b;">Gagal: <strong>' + d.total_gagal + '</strong></span>'
        );

        var $tbody = $('#urs_tbody_gagal_simpan').empty();
        if (d.gagal && d.gagal.length > 0) {
          $.each(d.gagal, function (_, row) {
            $tbody.append('<tr><td>' + esc(row.noresi) + '</td><td>' + esc(row.alasan) + '</td></tr>');
          });
          $('#urs_wrap_gagal_simpan').show();
        } else {
          $('#urs_wrap_gagal_simpan').hide();
        }

        $('#urs_panel_hasil').hide();
        $('#urs_panel_simpan').show();

        noty({ text: 'Berhasil menyimpan ' + d.total_berhasil + ' resi', layout: 'topRight', type: 'success', timeout: 3000 });

        dataValidTerakhir = [];
        document.getElementById('urs_file').value = '';
      },
      error: function (xhr) {
        var msg = 'Gagal menghubungi server';
        try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
        noty({ text: msg, layout: 'topRight', type: 'error', timeout: 4000 });
      },
      complete: function () {
        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Lanjutkan Simpan <span id="urs_jumlah_simpan">0</span> Resi Valid');
        $('#urs_btn_batal').prop('disabled', false);
      }
    });
  });
})();
</script>
