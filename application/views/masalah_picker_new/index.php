<?php
// View menu TIM CS -> Daftar Masalah Picker New. Dimuat lewat AJAX (SPA) oleh
// plugins.js, jadi semua handler diikat ke #mpn-root supaya ikut hilang saat
// pengguna pindah menu -- bukan ke document, yang membuat handler menumpuk.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Daftar Masalah Picker New</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Hak aksesnya sama dengan menu <em>Daftar Masalah Picker</em> yang lama; minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif;

$rentang_default = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
$reportrange = !empty($reportrange) ? $reportrange : $rentang_default;
?>
<div id="mpn-root">
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Daftar Masalah Picker New</strong>
            <small class="text-muted" style="margin-left:8px;">masalah yang dilaporkan packer dan belum diproses CS</small>
          </h3>
        </div>

        <div class="panel-body">
          <form class="form-horizontal nojs" id="mpn-form-filter">
            <div class="form-group">
              <label class="col-md-2 col-xs-12 control-label">Rentang waktu lapor</label>
              <div class="col-md-3 col-xs-12">
                <input type="text" name="reportrange" id="mpn-reportrange" class="form-control"
                  value="<?= htmlspecialchars($reportrange, ENT_QUOTES, 'UTF-8') ?>" />
              </div>
              <label class="col-md-1 col-xs-12 control-label">Tipe</label>
              <div class="col-md-2 col-xs-12">
                <select id="mpn-tipe" class="form-control">
                  <option value="">Semua tipe</option>
                  <?php foreach ((isset($list_tipe) && is_array($list_tipe) ? $list_tipe : []) as $t) : ?>
                    <option value="<?= (int) $t['id_typemasalah'] ?>"><?= htmlspecialchars($t['type_masalah'], ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2 col-xs-12">
                <button type="button" class="btn btn-info" id="mpn-btn-cari"><i class="fa fa-search"></i> Cari</button>
              </div>
            </div>
          </form>

          <div class="alert alert-warning" id="mpn-alert-luar" style="display:none; margin-bottom:10px;">
            <i class="fa fa-exclamation-triangle"></i>
            Ada <strong id="mpn-luar-jumlah">0</strong> masalah belum diproses di <u>luar</u> rentang tanggal ini
            (tertua <span id="mpn-luar-tertua">-</span>).
            <a href="#" id="mpn-btn-tampilkan-semua" class="btn btn-xs btn-warning" style="margin-left:6px;">Tampilkan semua pending</a>
          </div>

          <div class="row" style="margin-bottom:10px;">
            <div class="col-md-8">
              <button type="button" class="btn btn-warning btn-lg" id="mpn-btn-proses" style="font-weight:bold; color:#333;">
                <i class="fa fa-print"></i> <span id="mpn-btn-proses-label">PROSES KURANGAN &amp; CETAK SEMUA PENDING</span>
              </button>
              <button type="button" class="btn btn-default" id="mpn-btn-bersihkan" style="display:none;">
                <i class="fa fa-times"></i> Bersihkan pilihan (<span id="mpn-jumlah-pilih">0</span>)
              </button>
            </div>
            <div class="col-md-4 text-right">
              <small class="text-muted">
                Picker terdeteksi otomatis dari scan ambil barang. <strong>LEBIH AMBIL</strong> diproses tanpa cetak.
              </small>
            </div>
          </div>

          <table class="table table-striped table-bordered table-condensed" id="mpn-table" style="width:100%">
            <thead>
              <tr>
                <th>#</th>
                <th>No. Resi</th>
                <th>SKU</th>
                <th>SKU Salah</th>
                <th>Nama Barang</th>
                <th>Qty</th>
                <th>Qty Masalah</th>
                <th>Tipe</th>
                <th>Rak</th>
                <th>Picker</th>
                <th>Packer / Pelapor</th>
                <th>Dilaporkan</th>
                <th style="width:40px;"><input type="checkbox" id="mpn-check-halaman" title="Pilih semua di halaman ini"></th>
                <th style="width:80px;">Aksi</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

      <!-- Riwayat proses + cetak ulang -->
      <div class="panel panel-default">
        <div class="panel-heading" style="cursor:pointer;" id="mpn-riwayat-toggle">
          <h3 class="panel-title"><i class="fa fa-history"></i> <strong>Riwayat Proses &amp; Cetak Ulang</strong>
            <small class="text-muted" style="margin-left:8px;">30 proses terakhir — klik untuk buka/tutup</small>
            <span class="pull-right"><i class="fa fa-chevron-down" id="mpn-riwayat-ikon"></i></span>
          </h3>
        </div>
        <div class="panel-body" id="mpn-riwayat-body" style="display:none;">
          <table class="table table-bordered table-condensed" id="mpn-riwayat-table" style="width:100%">
            <thead>
              <tr>
                <th style="width:60px;">#</th>
                <th style="width:130px;">Waktu</th>
                <th>Diproses oleh</th>
                <th style="width:70px;">Picker</th>
                <th style="width:60px;">Item</th>
                <th>Daftar picker di slip</th>
                <th style="width:170px;">Aksi</th>
              </tr>
            </thead>
            <tbody><tr><td colspan="7" class="text-center text-muted">Memuat...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detail -->
  <div id="mpn-modal-detail" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Detail Masalah Picker</h4>
        </div>
        <div class="modal-body" id="mpn-modal-detail-isi"><p>Memuat data...</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Proses -->
  <div id="mpn-modal-proses" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background:#f5f5f5;">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><strong>PROSES KURANGAN &amp; CETAK</strong> <small id="mpn-proses-mode"></small></h4>
        </div>
        <div class="modal-body" id="mpn-modal-proses-isi"><p class="text-center">Memuat data...</p></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-warning btn-lg" id="mpn-btn-submit-proses" style="font-weight:bold; color:#333; min-width:220px;">
            <i class="fa fa-print"></i> PROSES &amp; CETAK
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Cetak Ulang per picker -->
  <div id="mpn-modal-ulang" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Cetak Ulang Proses <span id="mpn-ulang-judul"></span></h4>
        </div>
        <div class="modal-body" id="mpn-modal-ulang-isi"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-primary" id="mpn-btn-ulang-semua"><i class="fa fa-print"></i> Cetak semua picker</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var $root = $('#mpn-root');
  var URL = {
    data:       'masalah-picker-new/get-data',
    detail:     'masalah-picker-new/get-detail',
    preview:    'masalah-picker-new/preview-proses',
    proses:     'masalah-picker-new/proses-cetak',
    riwayat:    'masalah-picker-new/riwayat-cetak',
    cetakUlang: 'masalah-picker-new/data-cetak-ulang'
  };
  var TIPE_LEBIH_AMBIL = 2, TIPE_SALAH_AMBIL = 4;
  var ITEM_PER_HALAMAN = 10; // muat di kertas 100x150mm: tiap item ~10mm (baris SKU + baris packer/tipe), kepala slip ~27mm; diuji visual 17 Sep 2026

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function notif(teks, tipe) {
    if (typeof noty !== 'undefined') {
      noty({ text: teks, layout: 'topRight', type: tipe || 'success', timeout: 5000 });
    } else {
      alert(teks);
    }
  }
  function rentang() {
    return $('#mpn-reportrange').val() || <?= json_encode($rentang_default) ?>;
  }

  // ---------- filter tanggal ----------
  var awal = moment(rentang().split(' - ')[0]);
  var akhir = moment(rentang().split(' - ')[1]);
  $('#mpn-reportrange').daterangepicker({
    timePicker: true, timePicker24Hour: true, startDate: awal, endDate: akhir,
    ranges: {
      'Hari ini': [moment().startOf('day'), moment().endOf('day')],
      'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
      '7 hari terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
      'Bulan ini': [moment().startOf('month'), moment().endOf('month')]
    },
    locale: { format: 'YYYY-MM-DD HH:mm:ss' }
  });
  $('#mpn-reportrange').on('apply.daterangepicker', function () { table.ajax.reload(null, true); });
  $root.on('click', '#mpn-btn-cari', function () { table.ajax.reload(null, true); });
  // Enter di kolom filter = Cari; jangan sampai plugins.js memuat ulang halaman.
  $root.on('submit', '#mpn-form-filter', function (e) { e.preventDefault(); table.ajax.reload(null, true); });
  $root.on('change', '#mpn-tipe', function () { table.ajax.reload(null, true); });

  // ---------- pilihan checkbox (bertahan lintas halaman) ----------
  var terpilih = {};
  function jumlahTerpilih() { return Object.keys(terpilih).length; }
  function perbaruiTombol() {
    var n = jumlahTerpilih();
    if (n > 0) {
      $('#mpn-btn-proses-label').text('PROSES KURANGAN & CETAK TERPILIH (' + n + ')');
      $('#mpn-jumlah-pilih').text(n);
      $('#mpn-btn-bersihkan').show();
    } else {
      $('#mpn-btn-proses-label').text('PROSES KURANGAN & CETAK SEMUA PENDING');
      $('#mpn-btn-bersihkan').hide();
    }
  }
  $root.on('change', '.row-select', function () {
    var id = $(this).val();
    if (this.checked) { terpilih[id] = true; } else { delete terpilih[id]; }
    perbaruiTombol();
  });
  $root.on('change', '#mpn-check-halaman', function () {
    var cek = this.checked;
    $('#mpn-table .row-select').each(function () {
      this.checked = cek;
      if (cek) { terpilih[this.value] = true; } else { delete terpilih[this.value]; }
    });
    perbaruiTombol();
  });
  $root.on('click', '#mpn-btn-bersihkan', function () {
    terpilih = {};
    $('#mpn-table .row-select, #mpn-check-halaman').prop('checked', false);
    perbaruiTombol();
  });

  // ---------- tabel utama ----------
  var table = $('#mpn-table').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    language: { processing: 'Memproses data...', emptyTable: 'Tidak ada masalah picker yang belum diproses pada rentang ini.' },
    order: [[11, 'desc']],
    lengthMenu: [[25, 50, 100, 200, 500], [25, 50, 100, 200, 500]],
    ajax: {
      url: URL.data, type: 'POST',
      data: function (d) { d.reportrange = rentang(); d.tipe = $('#mpn-tipe').val(); },
      dataSrc: function (json) {
        var luar = json.pending_luar_rentang || { jumlah: 0 };
        if (luar.jumlah > 0) {
          $('#mpn-luar-jumlah').text(luar.jumlah);
          $('#mpn-luar-tertua').text(luar.tertua ? moment(luar.tertua).format('DD/MM/YYYY HH:mm') : '-');
          $('#mpn-alert-luar').data('tertua', luar.tertua).show();
        } else {
          $('#mpn-alert-luar').hide();
        }
        return json.data;
      }
    },
    columnDefs: [
      { className: 'text-center', targets: [0, 5, 6, 7, 12, 13] },
      { orderable: false, targets: [0, 12, 13] }
    ],
    drawCallback: function () {
      $('#mpn-table .row-select').each(function () { this.checked = !!terpilih[this.value]; });
      $('#mpn-check-halaman').prop('checked', false);
    }
  });

  $root.on('click', '#mpn-btn-tampilkan-semua', function (e) {
    e.preventDefault();
    var tertua = $('#mpn-alert-luar').data('tertua');
    var dp = $('#mpn-reportrange').data('daterangepicker');
    dp.setStartDate(tertua ? moment(tertua).startOf('day') : moment().subtract(1, 'year').startOf('day'));
    dp.setEndDate(moment().endOf('day'));
    $('#mpn-reportrange').val(dp.startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + dp.endDate.format('YYYY-MM-DD HH:mm:ss'));
    table.ajax.reload(null, true);
  });

  // ---------- detail ----------
  $root.on('click', '.btn-detail-masalah', function () {
    var id = $(this).data('id');
    $('#mpn-modal-detail-isi').html('<p>Memuat data...</p>');
    $('#mpn-modal-detail').modal('show');
    $.post(URL.detail, { id: id }, function (res) {
      if (!res || res.code !== 200) {
        $('#mpn-modal-detail-isi').html('<p class="text-danger">' + esc(res && res.message ? res.message : 'Gagal memuat detail') + '</p>');
        return;
      }
      var d = res.data;
      var baris = function (k, v) { return '<tr><th style="width:35%">' + k + '</th><td>' + v + '</td></tr>'; };
      var html = '<table class="table table-bordered table-condensed">';
      html += baris('No. Resi', esc(d.noresi));
      html += baris('SKU', '<strong>' + esc(d.sku) + '</strong>');
      html += baris('Nama Barang', esc(d.nama_barang));
      html += baris('Tipe Masalah', esc(d.type_masalah));
      if (d.sku_salah) html += baris('SKU yang terambil (salah)', esc(d.sku_salah));
      html += baris('Qty pesanan', esc(d.qty));
      html += baris('Qty bermasalah', '<strong>' + esc(d.qty_bermasalah) + '</strong>');
      html += baris('No. Rak', esc(d.no_rak || '-'));
      html += baris('Picker (dari scan ambil barang)', d.nama_picker ? esc(d.nama_picker) : '<span class="text-danger">Tidak terdeteksi</span>');
      html += baris('Packer / Pelapor', esc(d.nama_packer || '-'));
      html += baris('Dilaporkan', esc(d.created_fmt));
      if (d.updated_fmt) html += baris('Terakhir diubah', esc(d.updated_fmt));
      html += baris('Status', esc(d.status_label));
      html += '</table>';
      if (d.link_foto) {
        html += '<div class="text-center"><img src="' + esc(d.link_foto) + '" data-foto-lokal="' + esc(d.foto_lokal || '') + '" class="img-thumbnail" style="max-width:220px;"></div>';
      }
      $('#mpn-modal-detail-isi').html(html);
    }, 'json').fail(function () {
      $('#mpn-modal-detail-isi').html('<p class="text-danger">Terjadi kesalahan saat memuat detail.</p>');
    });
  });

  // ---------- pratinjau + proses ----------
  function idsTerpilih() { return Object.keys(terpilih); }

  function ringkasanSlip(slips, tanpaCetak) {
    var html = '';
    if (slips.length) {
      html += '<table class="table table-bordered table-condensed" style="margin-bottom:10px;">';
      html += '<thead><tr style="background:#f9f9f9;"><th>Picker</th><th class="text-center" style="width:70px;">Item</th><th class="text-center" style="width:70px;">Qty</th><th>Packer / Pelapor</th><th>SKU</th></tr></thead><tbody>';
      $.each(slips, function (i, s) {
        var qty = 0, packers = {}, skus = [];
        $.each(s.items, function (j, it) {
          qty += Number(it.qty_bermasalah) || 0;
          packers[it.nama_packer] = true;
          skus.push(it.sku);
        });
        var namaPicker = s.kode_picker === null
          ? '<span class="text-danger"><i class="fa fa-question-circle"></i> ' + esc(s.nama_picker) + '</span>'
          : '<strong>' + esc(s.nama_picker) + '</strong>';
        html += '<tr><td>' + namaPicker + '</td><td class="text-center">' + s.items.length + '</td><td class="text-center">' + qty + '</td>' +
          '<td><small>' + esc(Object.keys(packers).join(', ')) + '</small></td>' +
          '<td><small>' + esc(skus.join(', ')) + '</small></td></tr>';
      });
      html += '</tbody></table>';
    } else {
      html += '<p class="text-muted">Tidak ada slip yang perlu dicetak.</p>';
    }
    if (tanpaCetak && tanpaCetak.length) {
      html += '<div class="alert alert-info" style="margin-bottom:0;"><i class="fa fa-info-circle"></i> <strong>' + tanpaCetak.length +
        ' LEBIH AMBIL</strong> ikut ditandai selesai tetapi <u>tidak dicetak</u> (tidak ada barang yang perlu diambil): <small>' +
        esc($.map(tanpaCetak, function (it) { return it.sku + ' x' + it.qty_bermasalah + ' (' + it.nama_picker + ')'; }).join(', ')) + '</small></div>';
    }
    return html;
  }

  $root.on('click', '#mpn-btn-proses', function () {
    var ids = idsTerpilih();
    $('#mpn-proses-mode').text(ids.length ? ids.length + ' item terpilih' : 'semua pending di rentang ' + rentang());
    $('#mpn-modal-proses-isi').html('<p class="text-center">Memuat pratinjau...</p>');
    $('#mpn-btn-submit-proses').prop('disabled', true);
    $('#mpn-modal-proses').modal('show');

    $.post(URL.preview, { reportrange: rentang(), selected_ids: ids }, function (res) {
      if (!res || res.code !== 200) {
        $('#mpn-modal-proses-isi').html('<p class="text-danger text-center">' + esc(res && res.message ? res.message : 'Gagal memuat pratinjau') + '</p>');
        return;
      }
      var d = res.data;
      var judul = '<p><strong>' + d.jumlah_item + ' masalah</strong> akan ditandai selesai; <strong>' + d.slips.length +
        ' slip</strong> (satu per picker) akan dicetak. Reject Display otomatis masuk antrean QC.</p>';
      $('#mpn-modal-proses-isi').html(judul + ringkasanSlip(d.slips, d.tanpa_cetak));
      $('#mpn-btn-submit-proses').prop('disabled', false);
    }, 'json').fail(function () {
      $('#mpn-modal-proses-isi').html('<p class="text-danger text-center">Gagal memuat pratinjau. Coba lagi.</p>');
    });
  });

  $root.on('click', '#mpn-btn-submit-proses', function () {
    var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
    $.post(URL.proses, { reportrange: rentang(), selected_ids: idsTerpilih() }, function (res) {
      $btn.prop('disabled', false).html('<i class="fa fa-print"></i> PROSES &amp; CETAK');
      if (!res || res.code !== 200) {
        notif(res && res.message ? res.message : 'Gagal memproses', 'error');
        if (res && res.code === 409) { table.ajax.reload(null, false); $('#mpn-modal-proses').modal('hide'); }
        return;
      }
      var d = res.data;
      $('#mpn-modal-proses').modal('hide');
      terpilih = {};
      perbaruiTombol();
      table.ajax.reload(null, false);
      muatRiwayat();

      var pesan = d.jumlah_item + ' masalah diproses (proses #' + d.id_proses + '): ' + d.slips.length + ' slip picker';
      if (d.tanpa_cetak.length) pesan += ', ' + d.tanpa_cetak.length + ' LEBIH AMBIL tanpa cetak';
      if (d.reject_ke_qc) pesan += ', ' + d.reject_ke_qc + ' reject masuk QC';
      if (d.jumlah_dilewati) pesan += ', ' + d.jumlah_dilewati + ' dilewati (sudah diproses user lain)';
      notif(pesan + '.', 'success');

      if (d.slips.length) {
        cetakSlip(d);
      } else {
        notif('Tidak ada slip yang dicetak: semua item bertipe LEBIH AMBIL.', 'information');
      }
    }, 'json').fail(function (xhr) {
      $btn.prop('disabled', false).html('<i class="fa fa-print"></i> PROSES &amp; CETAK');
      notif('Terjadi kesalahan saat memproses. Respons server bukan JSON: ' + esc((xhr.responseText || '').substring(0, 200)), 'error');
    });
  });

  // ---------- riwayat & cetak ulang ----------
  var riwayatTerbuka = false;
  $root.on('click', '#mpn-riwayat-toggle', function () {
    riwayatTerbuka = !riwayatTerbuka;
    $('#mpn-riwayat-body').toggle(riwayatTerbuka);
    $('#mpn-riwayat-ikon').toggleClass('fa-chevron-down', !riwayatTerbuka).toggleClass('fa-chevron-up', riwayatTerbuka);
    if (riwayatTerbuka) muatRiwayat();
  });

  function muatRiwayat() {
    if (!riwayatTerbuka) return;
    $.post(URL.riwayat, {}, function (res) {
      var $tb = $('#mpn-riwayat-table tbody');
      if (!res || res.code !== 200 || !res.data.rows.length) {
        $tb.html('<tr><td colspan="7" class="text-center text-muted">Belum ada riwayat proses.</td></tr>');
        return;
      }
      var html = '';
      $.each(res.data.rows, function (i, r) {
        html += '<tr><td>#' + r.id_proses + '</td><td>' + esc(r.waktu_fmt) + '</td><td>' + esc(r.nama_user) + '</td>' +
          '<td class="text-center">' + r.jumlah_picker + '</td><td class="text-center">' + r.jumlah_item + '</td>' +
          '<td><small>' + esc(r.daftar_picker) + '</small></td>' +
          '<td>' + (Number(r.jumlah_picker) > 0
            ? '<button type="button" class="btn btn-xs btn-primary btn-ulang-semua" data-id="' + r.id_proses + '"><i class="fa fa-print"></i> Cetak ulang</button> ' +
              '<button type="button" class="btn btn-xs btn-default btn-ulang-pilih" data-id="' + r.id_proses + '"><i class="fa fa-user"></i> Per picker</button>'
            : '<span class="text-muted">tanpa slip</span>') + '</td></tr>';
      });
      $tb.html(html);
    }, 'json');
  }

  function ambilDataUlang(idProses, kodePicker, lalu) {
    $.post(URL.cetakUlang, { id_proses: idProses, kode_picker: kodePicker === undefined ? '' : kodePicker }, function (res) {
      if (!res || res.code !== 200) { notif(res && res.message ? res.message : 'Gagal memuat data cetak ulang', 'error'); return; }
      lalu(res.data);
    }, 'json').fail(function () { notif('Gagal memuat data cetak ulang.', 'error'); });
  }

  $root.on('click', '.btn-ulang-semua', function () {
    ambilDataUlang($(this).data('id'), undefined, cetakSlip);
  });

  $root.on('click', '.btn-ulang-pilih', function () {
    var id = $(this).data('id');
    ambilDataUlang(id, undefined, function (d) {
      $('#mpn-ulang-judul').text('#' + d.id_proses + ' (' + d.waktu_proses + ')');
      var html = '<table class="table table-bordered table-condensed"><thead><tr><th>Picker</th><th class="text-center" style="width:60px;">Item</th><th style="width:90px;"></th></tr></thead><tbody>';
      $.each(d.slips, function (i, s) {
        html += '<tr><td>' + esc(s.nama_picker) + '</td><td class="text-center">' + s.items.length + '</td>' +
          '<td><button type="button" class="btn btn-xs btn-primary btn-ulang-satu" data-id="' + d.id_proses + '" data-picker="' + (s.kode_picker === null ? 0 : s.kode_picker) + '"><i class="fa fa-print"></i> Cetak</button></td></tr>';
      });
      html += '</tbody></table>';
      $('#mpn-modal-ulang-isi').html(html);
      $('#mpn-btn-ulang-semua').data('id', d.id_proses);
      $('#mpn-modal-ulang').modal('show');
    });
  });
  $root.on('click', '.btn-ulang-satu', function () {
    ambilDataUlang($(this).data('id'), $(this).data('picker'), cetakSlip);
  });
  $root.on('click', '#mpn-btn-ulang-semua', function () {
    ambilDataUlang($(this).data('id'), undefined, cetakSlip);
  });

  // ---------- mesin cetak slip (iframe tersembunyi, kertas 100 x 150 mm) ----------
  function labelTipe(it) {
    if (Number(it.id_typemasalah) === TIPE_SALAH_AMBIL && it.sku_salah) {
      return it.type_masalah + ' (terambil ' + it.sku_salah + ')';
    }
    return it.type_masalah;
  }

  // Urutan baris slip: rak lantai 1 -> 2 -> 3 (karakter pertama kode rak,
  // mis. 1B-A1-1 / 2A-B6-3 / 3C-A2-4), lalu zona-kolom-tingkat; rak kosong
  // atau '-' paling bawah. Server sudah mengurutkan begitu, tapi diulang di
  // sini supaya cetak baru dan cetak ulang pasti sama.
  function rakKosong(r) { return !r || String(r).trim() === '' || String(r).trim() === '-'; }
  function urutRak(items) {
    return items.slice().sort(function (a, b) {
      var ka = rakKosong(a.no_rak), kb = rakKosong(b.no_rak);
      if (ka !== kb) return ka ? 1 : -1;
      if (ka && kb) return 0;
      return String(a.no_rak).localeCompare(String(b.no_rak), undefined, { numeric: true, sensitivity: 'base' });
    });
  }

  function htmlSlip(d) {
    var html = '';
    $.each(d.slips, function (i, s) {
      var items = urutRak(s.items);
      var halaman = [];
      for (var k = 0; k < items.length; k += ITEM_PER_HALAMAN) halaman.push(items.slice(k, k + ITEM_PER_HALAMAN));
      var totalQty = 0;
      $.each(items, function (j, it) { totalQty += Number(it.qty_bermasalah) || 0; });

      $.each(halaman, function (h, chunk) {
        html += '<div class="hal">';
        html += '<div class="judul">SLIP KURANGAN PICKER' + (d.cetak_ulang ? ' <span class="ulang">(CETAK ULANG)</span>' : '') + '</div>';
        html += '<div class="picker">' + esc(s.nama_picker) + '</div>';
        html += '<div class="info">Proses #' + esc(d.id_proses) + ' &middot; ' + esc(d.waktu_proses) + ' &middot; oleh ' + esc(d.nama_user) + '</div>';
        html += '<div class="info">' + items.length + ' item &middot; total qty ' + totalQty + ' &middot; hal ' + (h + 1) + '/' + halaman.length + '</div>';
        html += '<table><thead><tr><th class="c-sku">SKU</th><th class="c-qty">QTY</th><th class="c-rak">RAK</th></tr></thead><tbody>';
        $.each(chunk, function (j, it) {
          html += '<tr class="utama"><td class="c-sku"><div class="sku">' + esc(it.sku) + '</div></td>' +
            '<td class="c-qty">' + esc(it.qty_bermasalah) + '</td><td class="c-rak">' + esc(it.no_rak || '-') + '</td></tr>';
          html += '<tr class="sub"><td colspan="3">Packer <b>' + esc(it.nama_packer) + '</b> &middot; ' + esc(labelTipe(it)) + '</td></tr>';
        });
        html += '</tbody></table>';
        html += '<div class="kaki">Serahkan barang ke packer yang tertulis di tiap baris.</div>';
        html += '</div>';
      });
    });
    return html;
  }

  var CSS_SLIP = '@page{size:100mm 150mm;margin:0}' +
    'html,body{margin:0;padding:0;background:#fff;font-family:Arial,Helvetica,sans-serif;color:#000}' +
    '.hal{width:100mm;height:150mm;box-sizing:border-box;padding:5mm 5mm 4mm;page-break-after:always;overflow:hidden}' +
    '.hal:last-child{page-break-after:auto}' +
    '.judul{font-size:12pt;font-weight:bold;text-align:center;text-transform:uppercase;border-bottom:2px solid #000;padding-bottom:1.5mm;margin-bottom:1.5mm}' +
    '.judul .ulang{font-size:8pt;font-weight:normal}' +
    '.picker{font-size:12pt;font-weight:bold;text-align:center;margin-bottom:1mm;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}' +
    '.info{font-size:7.5pt;text-align:center;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}' +
    'table{width:100%;border-collapse:collapse;table-layout:fixed;margin-top:2mm}' +
    'th{border:1px solid #000;background:#eee;font-size:8pt;padding:1mm;-webkit-print-color-adjust:exact;print-color-adjust:exact}' +
    'td{border-left:1px solid #000;border-right:1px solid #000;padding:0.8mm 1.2mm;vertical-align:top}' +
    'tr.utama td{border-top:1px solid #000}' +
    'tr.sub td{border-bottom:1px solid #000;font-size:6.5pt;color:#222;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding-top:0;padding-bottom:0.8mm}' +
    '.c-sku{width:56%;text-align:left}.c-qty{width:14%;text-align:center}.c-rak{width:30%;text-align:center}' +
    'td.c-qty{font-size:12pt;font-weight:bold}td.c-rak{font-size:10pt;font-weight:bold}' +
    '.sku{font-size:10pt;font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}' +
    '.kaki{font-size:6.5pt;text-align:center;margin-top:2mm;color:#333}';

  function cetakSlip(d) {
    $('#mpn-print-frame').remove();
    var iframe = document.createElement('iframe');
    iframe.id = 'mpn-print-frame';
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:1px;height:1px;border:0;opacity:0;pointer-events:none;';
    document.body.appendChild(iframe);

    var doc = iframe.contentWindow.document;
    doc.open();
    doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Slip Kurangan Picker #' + esc(d.id_proses) + '</title><style>' + CSS_SLIP + '</style></head><body>' + htmlSlip(d) + '</body></html>');
    doc.close();

    setTimeout(function () {
      try {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
      } catch (e) {
        notif('Gagal membuka dialog cetak: ' + e.message + '. Gunakan tombol Cetak ulang di Riwayat.', 'error');
      }
      // iframe dibiarkan sampai dialog cetak ditutup; dibuang saat cetak berikutnya atau pindah menu.
    }, 300);
  }
})();
</script>

<style>
  #mpn-reportrange { background:#fff !important; cursor:pointer !important; color:#555 !important; }
  #mpn-table td { vertical-align: middle; }
  #mpn-riwayat-toggle:hover { background: #f5f5f5; }
</style>
