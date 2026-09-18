<?php
// View menu TIM PICKER -> Laporan Lost Scan Picker. Dimuat lewat AJAX (SPA)
// oleh plugins.js, jadi semua handler diikat ke #lsp-root supaya ikut hilang
// saat pengguna pindah menu -- bukan ke document, yang membuat handler menumpuk.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Laporan Lost Scan Picker</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Hak aksesnya sama dengan menu <em>SCAN COMBINED</em>; minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif;

$rentang_default = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
$reportrange = !empty($reportrange) ? $reportrange : $rentang_default;
$list_picker = (isset($list_picker) && is_array($list_picker)) ? $list_picker : [];
$jumlah_pending = isset($jumlah_pending) ? (int) $jumlah_pending : 0;
?>
<div id="lsp-root">
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Laporan Lost Scan Picker</strong>
            <small class="text-muted" style="margin-left:8px;">resi belum di-picker yang dilaporkan packer/HO -- tentukan picker-nya di sini supaya packer bisa scan ulang</small>
          </h3>
        </div>

        <div class="panel-body">
          <ul class="nav nav-tabs" style="margin-bottom:15px;">
            <li class="active"><a href="#lsp-tab-pending" data-toggle="tab"><i class="fa fa-hourglass-half"></i> Pending <span class="badge" id="lsp-badge-pending" style="background:#f0ad4e;"><?= $jumlah_pending ?></span></a></li>
            <li><a href="#lsp-tab-selesai" data-toggle="tab"><i class="fa fa-check-circle"></i> Selesai</a></li>
          </ul>

          <div class="tab-content">
            <!-- ================= PENDING ================= -->
            <div class="tab-pane fade in active" id="lsp-tab-pending">
              <p class="text-muted" style="margin-bottom:10px;">
                <i class="fa fa-info-circle"></i> Semua laporan yang belum diproses ditampilkan tanpa filter tanggal. Setelah picker ditambahkan, packer bisa scan ulang resi tersebut, lalu HO.
              </p>
              <table id="lsp-table-pending" class="table table-bordered table-striped" style="width:100%;">
                <thead>
                  <tr>
                    <th style="width:40px;">No</th>
                    <th style="width:120px;">Waktu Lapor</th>
                    <th>No Resi</th>
                    <th style="width:80px;">Sumber</th>
                    <th>Pelapor</th>
                    <th>Item (SKU &times; qty @ rak)</th>
                    <th style="width:160px;">Aksi</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <!-- ================= SELESAI ================= -->
            <div class="tab-pane fade" id="lsp-tab-selesai">
              <form class="form-horizontal nojs" id="lsp-form-filter">
                <div class="form-group">
                  <label class="col-md-2 col-xs-12 control-label">Rentang waktu proses</label>
                  <div class="col-md-4 col-xs-12">
                    <input type="text" id="lsp-reportrange" class="form-control" value="<?= htmlspecialchars($reportrange, ENT_QUOTES, 'UTF-8') ?>" />
                  </div>
                  <div class="col-md-2 col-xs-12">
                    <button type="button" class="btn btn-info" id="lsp-btn-cari"><i class="fa fa-search"></i> Cari</button>
                  </div>
                </div>
              </form>
              <table id="lsp-table-selesai" class="table table-bordered table-striped" style="width:100%;">
                <thead>
                  <tr>
                    <th style="width:40px;">No</th>
                    <th style="width:120px;">Waktu Lapor</th>
                    <th>No Resi</th>
                    <th style="width:80px;">Sumber</th>
                    <th>Pelapor</th>
                    <th>Item (SKU &times; qty @ rak)</th>
                    <th>Picker</th>
                    <th>Diproses oleh</th>
                    <th style="width:140px;">Status</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL TAMBAHKAN PICKER ================= -->
  <div id="lsp-modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background:#f5f5f5;">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-user-plus"></i> Tambahkan Picker</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="lsp-modal-id" value="">
          <p style="margin-bottom:6px;">No Resi</p>
          <h3 id="lsp-modal-noresi" style="margin:0 0 12px; font-weight:800; letter-spacing:2px;">-</h3>
          <p style="margin-bottom:6px;">Item</p>
          <div id="lsp-modal-item" style="background:#f9f9f9; border:1px solid #eee; border-radius:6px; padding:8px 10px; margin-bottom:15px; max-height:180px; overflow:auto;"></div>
          <div class="form-group" style="margin-bottom:0;">
            <label for="lsp-modal-picker">Picker yang mengambil resi ini</label>
            <select id="lsp-modal-picker" class="form-control" data-live-search="true" data-size="8" title="Pilih Picker">
              <option value="">Pilih Picker</option>
              <?php foreach ($list_picker as $p) : ?>
                <option value="<?= (int) $p['kode_pegawai'] ?>"><?= htmlspecialchars($p['nama_pegawai'] . ' - ' . $p['kode_pegawai'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($list_picker)) : ?>
              <p class="text-danger" style="margin:8px 0 0;"><i class="fa fa-exclamation-triangle"></i> Master Picker kosong. Tambahkan dulu di menu Master Picker.</p>
            <?php endif; ?>
          </div>
          <p class="text-muted" style="margin:12px 0 0; font-size:12px;">
            <i class="fa fa-info-circle"></i> Baris picking dibuat atas nama picker ini (tanpa KPI) dan lost scan PICKER dicatat. Setelah itu packer bisa scan ulang.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-warning" id="lsp-modal-simpan"><i class="fa fa-save"></i> Simpan &amp; Tambahkan Picker</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var $root = $('#lsp-root');
  var URL = {
    data:   'lost-scan-picker/get-data',
    tambah: 'lost-scan-picker/tambah-picker'
  };

  function notif(teks, tipe) {
    if (typeof noty !== 'undefined') {
      noty({ text: teks, layout: 'topRight', type: tipe || 'success', timeout: 5000 });
    } else {
      alert(teks);
    }
  }
  function rentang() {
    return $('#lsp-reportrange').val() || <?= json_encode($rentang_default) ?>;
  }
  function perbaruiBadge(n) {
    $('#lsp-badge-pending').text(n);
  }

  // ---------- filter tanggal (tab selesai) ----------
  var awal = moment(rentang().split(' - ')[0]);
  var akhir = moment(rentang().split(' - ')[1]);
  $('#lsp-reportrange').daterangepicker({
    timePicker: true, timePicker24Hour: true, startDate: awal, endDate: akhir,
    ranges: {
      'Hari ini': [moment().startOf('day'), moment().endOf('day')],
      'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
      '7 hari terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
      'Bulan ini': [moment().startOf('month'), moment().endOf('month')]
    },
    locale: { format: 'YYYY-MM-DD HH:mm:ss' }
  });
  $('#lsp-reportrange').on('apply.daterangepicker', function () { tableSelesai.ajax.reload(null, true); });
  $root.on('click', '#lsp-btn-cari', function () { tableSelesai.ajax.reload(null, true); });
  $root.on('submit', '#lsp-form-filter', function (e) { e.preventDefault(); tableSelesai.ajax.reload(null, true); });

  // ---------- tabel pending ----------
  var tablePending = $('#lsp-table-pending').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    language: { processing: 'Memproses data...', emptyTable: 'Tidak ada laporan lost scan picker yang menunggu.' },
    order: [[1, 'asc']],
    lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
    ajax: {
      url: URL.data, type: 'POST',
      data: function (d) { d.tab = 'pending'; },
      dataSrc: function (json) { perbaruiBadge(json.jumlah_pending || 0); return json.data; }
    },
    columnDefs: [
      { className: 'text-center', targets: [0, 3, 6] },
      { orderable: false, targets: [0, 5, 6] }
    ]
  });

  // ---------- tabel selesai ----------
  var tableSelesai = $('#lsp-table-selesai').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    language: { processing: 'Memproses data...', emptyTable: 'Tidak ada laporan yang diproses pada rentang ini.' },
    order: [[7, 'desc']],
    lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
    ajax: {
      url: URL.data, type: 'POST',
      data: function (d) { d.tab = 'selesai'; d.reportrange = rentang(); },
      dataSrc: function (json) { perbaruiBadge(json.jumlah_pending || 0); return json.data; }
    },
    columnDefs: [
      { className: 'text-center', targets: [0, 3, 8] },
      { orderable: false, targets: [0, 5] }
    ]
  });

  // DataTables di dalam tab tersembunyi salah menghitung lebar kolom;
  // hitung ulang saat tab ditampilkan.
  $root.on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
    tablePending.columns.adjust();
    tableSelesai.columns.adjust();
  });

  // ---------- modal tambahkan picker ----------
  var adaSelectpicker = (typeof $.fn.selectpicker === 'function');
  if (adaSelectpicker) {
    $('#lsp-modal-picker').selectpicker({ liveSearch: true, size: 8 });
  }

  $root.on('click', '.btn-tambah-picker', function () {
    var $btn = $(this);
    $('#lsp-modal-id').val($btn.data('id'));
    $('#lsp-modal-noresi').text($btn.data('noresi'));
    $('#lsp-modal-item').html($btn.closest('tr').find('.lsp-items').html() || '<em class="text-muted">tidak ada detail item</em>');
    $('#lsp-modal-picker').val('');
    if (adaSelectpicker) $('#lsp-modal-picker').selectpicker('refresh');
    $('#lsp-modal-simpan').prop('disabled', false);
    $('#lsp-modal').modal('show');
  });

  $('#lsp-modal').on('shown.bs.modal', function () {
    if (adaSelectpicker) {
      $('#lsp-modal-picker').selectpicker('toggle');
    } else {
      $('#lsp-modal-picker').focus();
    }
  });

  // Setelah picker dipilih, Enter berikutnya = simpan.
  $root.on('changed.bs.select change', '#lsp-modal-picker', function () {
    if ($(this).val()) $('#lsp-modal-simpan').focus();
  });

  function kirimTambahPicker(idPending, kodePicker, $tombol, sesudah) {
    $tombol.prop('disabled', true);
    $.ajax({
      url: URL.tambah, type: 'POST', dataType: 'json',
      data: { id_pending: idPending, kode_picker: kodePicker },
      success: function (r) {
        if (r.code === 201) {
          notif(r.message, 'success');
          tablePending.ajax.reload(null, false);
          tableSelesai.ajax.reload(null, false);
          if (sesudah) sesudah();
        } else {
          notif(r.message || 'Gagal memproses', 'error');
          // Sudah diproses orang lain -> segarkan supaya barisnya hilang.
          if (r.data && r.data.kode === 'SUDAH_DIPROSES') tablePending.ajax.reload(null, false);
          $tombol.prop('disabled', false);
        }
      },
      error: function () {
        notif('Kesalahan sistem saat memproses laporan', 'error');
        $tombol.prop('disabled', false);
      }
    });
  }

  $root.on('click', '#lsp-modal-simpan', function () {
    var id = $('#lsp-modal-id').val();
    var picker = $('#lsp-modal-picker').val();
    if (!picker) {
      notif('Pilih picker dulu', 'warning');
      if (adaSelectpicker) $('#lsp-modal-picker').selectpicker('toggle');
      return;
    }
    kirimTambahPicker(id, picker, $(this), function () { $('#lsp-modal').modal('hide'); });
  });

  // Picking sudah dibuat di luar alur: tutup laporan tanpa memilih picker.
  $root.on('click', '.btn-tandai-selesai', function () {
    var $btn = $(this);
    if (!confirm('Tutup laporan resi ' + $btn.data('noresi') + ' sebagai selesai di luar alur?')) return;
    kirimTambahPicker($btn.data('id'), '', $btn, null);
  });
})();
</script>
