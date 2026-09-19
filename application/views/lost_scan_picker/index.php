<?php
// View menu TIM PICKER -> Laporan Lost Scan Picker. Dimuat lewat AJAX (SPA)
// oleh plugins.js, jadi semua handler diikat ke #lsp-root supaya ikut hilang
// saat pengguna pindah menu -- bukan ke document, yang membuat handler menumpuk.
// Timer segarkan-otomatis dihentikan sendiri begitu #lsp-root lepas dari DOM.
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
$ringkasan = array_merge(
    ['total' => 0, 'dari_packer' => 0, 'dari_ho' => 0, 'tertua' => null, 'tertua_menit' => null, 'lebih_30_menit' => 0],
    (isset($ringkasan) && is_array($ringkasan)) ? $ringkasan : []
);
$interval_segarkan = 30; // detik
?>
<style>
  /* Semua selector diawali #lsp-root supaya tidak bocor ke halaman lain di SPA. */
  #lsp-root .lsp-alur { display:flex; flex-wrap:wrap; align-items:center; gap:6px 0; margin:0 0 15px; padding:10px 12px; background:#f7f9fb; border:1px solid #e6ebf0; border-radius:4px; font-size:12px; color:#555; }
  #lsp-root .lsp-alur .lsp-langkah { display:inline-flex; align-items:center; white-space:nowrap; }
  #lsp-root .lsp-alur .lsp-langkah .lsp-nomor { display:inline-block; width:20px; height:20px; line-height:20px; border-radius:50%; background:#cfd8e0; color:#fff; text-align:center; font-weight:700; margin-right:6px; font-size:11px; }
  #lsp-root .lsp-alur .lsp-langkah.aktif { color:#8a6d3b; font-weight:700; }
  #lsp-root .lsp-alur .lsp-langkah.aktif .lsp-nomor { background:#f0ad4e; }
  #lsp-root .lsp-alur .lsp-panah { margin:0 10px; color:#b5c0ca; }

  #lsp-root .lsp-kartu { display:flex; align-items:center; background:#fff; border:1px solid #e3e8ed; border-left-width:4px; border-radius:4px; padding:10px 12px; margin-bottom:15px; min-height:66px; }
  #lsp-root .lsp-kartu .lsp-kartu-ikon { width:38px; font-size:24px; text-align:center; color:#9aa7b3; margin-right:10px; }
  #lsp-root .lsp-kartu .lsp-kartu-angka { font-size:26px; font-weight:700; line-height:1; color:#333; }
  #lsp-root .lsp-kartu .lsp-kartu-label { font-size:12px; color:#777; margin-top:3px; }
  #lsp-root .lsp-kartu.kuning { border-left-color:#f0ad4e; } #lsp-root .lsp-kartu.kuning .lsp-kartu-ikon { color:#f0ad4e; }
  #lsp-root .lsp-kartu.biru   { border-left-color:#5bc0de; } #lsp-root .lsp-kartu.biru .lsp-kartu-ikon   { color:#5bc0de; }
  #lsp-root .lsp-kartu.navy   { border-left-color:#33414e; } #lsp-root .lsp-kartu.navy .lsp-kartu-ikon   { color:#33414e; }
  #lsp-root .lsp-kartu.merah  { border-left-color:#d9534f; } #lsp-root .lsp-kartu.merah .lsp-kartu-ikon  { color:#d9534f; }
  #lsp-root .lsp-kartu.hijau  { border-left-color:#5cb85c; } #lsp-root .lsp-kartu.hijau .lsp-kartu-ikon  { color:#5cb85c; }

  #lsp-root .nav-tabs > li > a { font-weight:600; }
  #lsp-root .lsp-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:10px; }
  #lsp-root .lsp-toolbar .lsp-toolbar-kanan { margin-left:auto; display:flex; align-items:center; gap:8px; font-size:12px; color:#777; }
  #lsp-root .lsp-toolbar label { font-weight:normal; margin:0; cursor:pointer; }

  #lsp-root table.dataTable td { vertical-align:middle; }
  #lsp-root .lsp-resi { font-weight:700; letter-spacing:1px; font-size:14px; }
  #lsp-root .lsp-waktu { white-space:nowrap; }
  #lsp-root .lsp-usia { display:inline-block; margin-top:3px; font-weight:normal; }
  #lsp-root .lsp-item { white-space:nowrap; line-height:1.7; }
  #lsp-root .lsp-rak { display:inline-block; min-width:44px; text-align:center; padding:1px 6px; border-radius:3px; background:#eef2f6; border:1px solid #d9e1e8; font-weight:700; font-size:11px; color:#33414e; }
  #lsp-root .lsp-qty { color:#777; }
  #lsp-root .lsp-catatan-aksi { display:block; margin-top:4px; line-height:1.2; }
  #lsp-root tr.lsp-lama > td { background-color:#fdf7e7 !important; }
  #lsp-root tr.lsp-sangat-lama > td { background-color:#fbeceb !important; }
  #lsp-root .dataTables_empty { padding:30px 10px !important; color:#888; }
  #lsp-root .lsp-terakhir { white-space:nowrap; }

  #lsp-root .lsp-modal-info { display:flex; flex-wrap:wrap; gap:6px 18px; font-size:12px; color:#666; margin-bottom:12px; }
  #lsp-root .lsp-modal-info strong { color:#333; }
  #lsp-root #lsp-modal-item { background:#f9f9f9; border:1px solid #eee; border-radius:4px; padding:8px 10px; margin-bottom:15px; max-height:180px; overflow:auto; }
</style>

<div id="lsp-root" data-interval="<?= (int) $interval_segarkan ?>">
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><i class="fa fa-user-plus"></i> <strong>Laporan Lost Scan Picker</strong></h3>
          <ul class="panel-controls">
            <li><a href="#" id="lsp-btn-segarkan" title="Segarkan daftar sekarang"><span class="fa fa-refresh"></span></a></li>
          </ul>
        </div>

        <div class="panel-body">
          <!-- alur singkat: pengganti sub-judul panjang -->
          <div class="lsp-alur">
            <span class="lsp-langkah"><span class="lsp-nomor">1</span> Packer / HO lapor resi belum di-picker</span>
            <span class="lsp-panah"><i class="fa fa-long-arrow-right"></i></span>
            <span class="lsp-langkah aktif"><span class="lsp-nomor">2</span> Tim picker tentukan picker-nya (menu ini)</span>
            <span class="lsp-panah"><i class="fa fa-long-arrow-right"></i></span>
            <span class="lsp-langkah"><span class="lsp-nomor">3</span> Packer scan ulang</span>
            <span class="lsp-panah"><i class="fa fa-long-arrow-right"></i></span>
            <span class="lsp-langkah"><span class="lsp-nomor">4</span> HO scan ulang</span>
          </div>

          <!-- ringkasan antrean; diperbarui setiap tabel dimuat -->
          <div class="row">
            <div class="col-sm-6 col-md-3">
              <div class="lsp-kartu kuning">
                <div class="lsp-kartu-ikon"><i class="fa fa-hourglass-half"></i></div>
                <div>
                  <div class="lsp-kartu-angka" id="lsp-r-total"><?= (int) $ringkasan['total'] ?></div>
                  <div class="lsp-kartu-label">Menunggu picker</div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="lsp-kartu biru">
                <div class="lsp-kartu-ikon"><i class="fa fa-archive"></i></div>
                <div>
                  <div class="lsp-kartu-angka" id="lsp-r-packer"><?= (int) $ringkasan['dari_packer'] ?></div>
                  <div class="lsp-kartu-label">Dilaporkan packer</div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="lsp-kartu navy">
                <div class="lsp-kartu-ikon"><i class="fa fa-truck"></i></div>
                <div>
                  <div class="lsp-kartu-angka" id="lsp-r-ho"><?= (int) $ringkasan['dari_ho'] ?></div>
                  <div class="lsp-kartu-label">Dilaporkan HO</div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="lsp-kartu hijau" id="lsp-kartu-tertua">
                <div class="lsp-kartu-ikon"><i class="fa fa-clock-o"></i></div>
                <div>
                  <div class="lsp-kartu-angka" id="lsp-r-tertua">-</div>
                  <div class="lsp-kartu-label">Paling lama menunggu <span id="lsp-r-lama"></span></div>
                </div>
              </div>
            </div>
          </div>

          <ul class="nav nav-tabs" style="margin-bottom:15px;">
            <li class="active"><a href="#lsp-tab-pending" data-toggle="tab"><i class="fa fa-hourglass-half"></i> Menunggu Picker <span class="badge" id="lsp-badge-pending" style="background:#f0ad4e;"><?= (int) $ringkasan['total'] ?></span></a></li>
            <li><a href="#lsp-tab-selesai" data-toggle="tab"><i class="fa fa-check-circle"></i> Sudah Diproses</a></li>
          </ul>

          <div class="tab-content">
            <!-- ================= PENDING ================= -->
            <div class="tab-pane fade in active" id="lsp-tab-pending">
              <div class="lsp-toolbar">
                <span class="text-muted" style="font-size:12px;">
                  <i class="fa fa-info-circle"></i> Semua laporan yang belum diproses tampil di sini tanpa filter tanggal. Kerjakan yang paling lama dulu.
                </span>
                <div class="lsp-toolbar-kanan">
                  <label><input type="checkbox" id="lsp-auto" checked> Segarkan otomatis tiap <?= (int) $interval_segarkan ?> dtk</label>
                  <span class="lsp-terakhir"><i class="fa fa-clock-o"></i> diperbarui <span id="lsp-terakhir">-</span></span>
                </div>
              </div>
              <table id="lsp-table-pending" class="table table-bordered table-striped" style="width:100%;">
                <thead>
                  <tr>
                    <th style="width:40px;">No</th>
                    <th style="width:110px;">Waktu Lapor</th>
                    <th style="width:180px;">No Resi</th>
                    <th style="width:170px;">Dilaporkan oleh</th>
                    <th>Item (rak &middot; SKU &times; qty)</th>
                    <th style="width:170px;">Aksi</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <!-- ================= SELESAI ================= -->
            <div class="tab-pane fade" id="lsp-tab-selesai">
              <form class="form-inline nojs" id="lsp-form-filter" style="margin-bottom:12px;">
                <div class="form-group">
                  <label for="lsp-reportrange" style="margin-right:6px;">Rentang waktu proses</label>
                  <input type="text" id="lsp-reportrange" class="form-control" style="width:320px; max-width:100%;" value="<?= htmlspecialchars($reportrange, ENT_QUOTES, 'UTF-8') ?>" />
                </div>
                <button type="button" class="btn btn-info" id="lsp-btn-cari"><i class="fa fa-search"></i> Tampilkan</button>
              </form>
              <table id="lsp-table-selesai" class="table table-bordered table-striped" style="width:100%;">
                <thead>
                  <tr>
                    <th style="width:40px;">No</th>
                    <th style="width:100px;">Waktu Lapor</th>
                    <th style="width:180px;">No Resi</th>
                    <th style="width:170px;">Dilaporkan oleh</th>
                    <th>Item (rak &middot; SKU &times; qty)</th>
                    <th>Picker</th>
                    <th style="width:150px;">Diproses oleh</th>
                    <th style="width:150px;">Status</th>
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
          <h3 id="lsp-modal-noresi" style="margin:0 0 8px; font-weight:800; letter-spacing:2px;">-</h3>
          <div class="lsp-modal-info">
            <span>Dilaporkan oleh <strong id="lsp-modal-pelapor">-</strong> <span id="lsp-modal-sumber"></span></span>
            <span>pada <strong id="lsp-modal-waktu">-</strong></span>
            <span>menunggu <strong id="lsp-modal-usia">-</strong></span>
          </div>
          <p style="margin-bottom:6px;"><strong>Item</strong> <span class="text-muted" id="lsp-modal-jumlah-item"></span></p>
          <div id="lsp-modal-item"></div>
          <div class="form-group" style="margin-bottom:0;">
            <label for="lsp-modal-picker">Siapa picker yang mengambil resi ini?</label>
            <select id="lsp-modal-picker" class="form-control" data-live-search="true" data-size="8" title="Ketik nama atau kode picker...">
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
            <i class="fa fa-info-circle"></i> Baris picking dibuat atas nama picker ini (tanpa KPI) dan lost scan PICKER dicatat atas namanya. Setelah itu packer bisa scan ulang.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-warning" id="lsp-modal-simpan"><i class="fa fa-save"></i> Simpan &amp; Tambahkan Picker</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL KONFIRMASI TANDAI SELESAI ================= -->
  <div id="lsp-modal-selesai" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background:#f5f5f5;">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-check"></i> Tandai Selesai</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="lsp-selesai-id" value="">
          <p>Resi <strong id="lsp-selesai-noresi" style="letter-spacing:1px;">-</strong> sudah punya baris picking yang dibuat di luar alur ini (mis. SCAN COMBINED).</p>
          <p style="margin-bottom:0;">Tutup laporan ini <strong>tanpa</strong> memilih picker dan tanpa mencatat lost scan baru?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="lsp-selesai-ya"><i class="fa fa-check"></i> Ya, tutup laporan</button>
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
  var INTERVAL = (parseInt($root.data('interval'), 10) || 30) * 1000;
  var BAHASA_DT = {
    processing: 'Memuat data...',
    search: '', searchPlaceholder: 'Cari resi / pelapor / picker...',
    lengthMenu: 'Tampilkan _MENU_ baris',
    info: 'Menampilkan _START_-_END_ dari _TOTAL_ laporan',
    infoEmpty: 'Tidak ada laporan',
    infoFiltered: '(disaring dari _MAX_ laporan)',
    zeroRecords: 'Tidak ada laporan yang cocok dengan pencarian.',
    paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
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
  function usiaTeks(menit) {
    if (menit === null || menit === undefined) return '-';
    if (menit < 1) return 'baru saja';
    if (menit < 60) return menit + ' mnt';
    if (menit < 1440) { var s = menit % 60; return Math.floor(menit / 60) + ' jam' + (s ? ' ' + s + ' mnt' : ''); }
    return Math.floor(menit / 1440) + ' hari';
  }

  // ---------- ringkasan & jam terakhir diperbarui ----------
  function perbaruiRingkasan(r) {
    if (!r) return;
    $('#lsp-badge-pending').text(r.total);
    $('#lsp-r-total').text(r.total);
    $('#lsp-r-packer').text(r.dari_packer);
    $('#lsp-r-ho').text(r.dari_ho);

    var $kartu = $('#lsp-kartu-tertua').removeClass('hijau kuning merah');
    if (r.total > 0 && r.tertua_menit !== null) {
      $('#lsp-r-tertua').text(usiaTeks(r.tertua_menit));
      $('#lsp-r-lama').text(r.lebih_30_menit > 0 ? '(' + r.lebih_30_menit + ' resi > 30 mnt)' : '');
      $kartu.addClass(r.tertua_menit >= 120 ? 'merah' : (r.tertua_menit >= 30 ? 'kuning' : 'hijau'));
    } else {
      $('#lsp-r-tertua').text('-');
      $('#lsp-r-lama').text('');
      $kartu.addClass('hijau');
    }
    $('#lsp-terakhir').text(moment().format('HH:mm:ss'));
  }
  perbaruiRingkasan(<?= json_encode($ringkasan) ?>);

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
    locale: { format: 'YYYY-MM-DD HH:mm:ss', applyLabel: 'Terapkan', cancelLabel: 'Batal', customRangeLabel: 'Rentang lain' }
  });
  $('#lsp-reportrange').on('apply.daterangepicker', function () { tableSelesai.ajax.reload(null, true); });
  $root.on('click', '#lsp-btn-cari', function () { tableSelesai.ajax.reload(null, true); });
  $root.on('submit', '#lsp-form-filter', function (e) { e.preventDefault(); tableSelesai.ajax.reload(null, true); });

  // ---------- tabel pending ----------
  var tablePending = $('#lsp-table-pending').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    language: $.extend({}, BAHASA_DT, {
      emptyTable: '<i class="fa fa-check-circle fa-2x text-success" style="display:block; margin-bottom:6px;"></i>Antrean kosong -- tidak ada resi yang menunggu picker.'
    }),
    order: [[1, 'asc']],
    lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
    ajax: {
      url: URL.data, type: 'POST',
      data: function (d) { d.tab = 'pending'; },
      dataSrc: function (json) { perbaruiRingkasan(json.ringkasan); return json.data; }
    },
    columnDefs: [
      { className: 'text-center', targets: [0, 5] },
      { orderable: false, targets: [0, 4, 5] }
    ],
    // Warnai baris yang sudah lama menunggu; ambangnya sama dengan badge usia di server.
    createdRow: function (row) {
      var menit = parseInt($(row).find('.lsp-usia').data('menit'), 10);
      if (isNaN(menit)) return;
      if (menit >= 120) $(row).addClass('lsp-sangat-lama');
      else if (menit >= 30) $(row).addClass('lsp-lama');
    }
  });

  // ---------- tabel selesai ----------
  var tableSelesai = $('#lsp-table-selesai').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    language: $.extend({}, BAHASA_DT, { emptyTable: 'Tidak ada laporan yang diproses pada rentang ini.' }),
    order: [[6, 'desc']],
    lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
    ajax: {
      url: URL.data, type: 'POST',
      data: function (d) { d.tab = 'selesai'; d.reportrange = rentang(); },
      dataSrc: function (json) { perbaruiRingkasan(json.ringkasan); return json.data; }
    },
    columnDefs: [
      { className: 'text-center', targets: [0, 7] },
      { orderable: false, targets: [0, 4] }
    ]
  });

  // DataTables di dalam tab tersembunyi salah menghitung lebar kolom;
  // hitung ulang saat tab ditampilkan.
  $root.on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
    tablePending.columns.adjust();
    tableSelesai.columns.adjust();
  });

  // ---------- segarkan manual & otomatis ----------
  function segarkan() {
    tablePending.ajax.reload(null, false);
    if ($('#lsp-tab-selesai').hasClass('active')) tableSelesai.ajax.reload(null, false);
  }
  $root.on('click', '#lsp-btn-segarkan', function (e) {
    e.preventDefault();
    var $ikon = $(this).find('.fa').addClass('fa-spin');
    segarkan();
    setTimeout(function () { $ikon.removeClass('fa-spin'); }, 800);
  });

  // Tidak menyegarkan saat: halaman sudah ditinggalkan (timer dihentikan),
  // checkbox dimatikan, tab browser tidak terlihat, atau ada modal terbuka
  // (supaya baris yang sedang diproses tidak berpindah di bawah kursor).
  var timer = setInterval(function () {
    if (!$.contains(document, $root[0])) { clearInterval(timer); return; }
    if (!$('#lsp-auto').is(':checked')) return;
    if (document.hidden) return;
    if ($root.find('.modal.in').length) return;
    if (!$('#lsp-tab-pending').hasClass('active')) return;
    tablePending.ajax.reload(null, false);
  }, INTERVAL);

  // ---------- modal tambahkan picker ----------
  var adaSelectpicker = (typeof $.fn.selectpicker === 'function');
  if (adaSelectpicker) {
    $('#lsp-modal-picker').selectpicker({ liveSearch: true, size: 8, noneResultsText: 'Tidak ada picker "{0}"' });
  }

  $root.on('click', '.btn-tambah-picker', function () {
    var $btn = $(this);
    var jumlah = parseInt($btn.data('jumlah-item'), 10) || 0;
    $('#lsp-modal-id').val($btn.data('id'));
    $('#lsp-modal-noresi').text($btn.data('noresi'));
    $('#lsp-modal-pelapor').text($btn.data('pelapor'));
    $('#lsp-modal-sumber').html($btn.data('sumber') === 'HO'
      ? '<span class="label label-primary">HO</span>'
      : '<span class="label label-info">PACKER</span>');
    $('#lsp-modal-waktu').text($btn.data('waktu'));
    $('#lsp-modal-usia').text($btn.data('usia'));
    $('#lsp-modal-jumlah-item').text(jumlah ? '(' + jumlah + ' baris)' : '');
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
          if (r.data && r.data.kode === 'SUDAH_DIPROSES') {
            tablePending.ajax.reload(null, false);
            if (sesudah) sesudah();
          }
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

  // ---------- modal tandai selesai (picking sudah dibuat di luar alur) ----------
  $root.on('click', '.btn-tandai-selesai', function () {
    var $btn = $(this);
    $('#lsp-selesai-id').val($btn.data('id'));
    $('#lsp-selesai-noresi').text($btn.data('noresi'));
    $('#lsp-selesai-ya').prop('disabled', false);
    $('#lsp-modal-selesai').modal('show');
  });
  $('#lsp-modal-selesai').on('shown.bs.modal', function () { $('#lsp-selesai-ya').focus(); });
  $root.on('click', '#lsp-selesai-ya', function () {
    kirimTambahPicker($('#lsp-selesai-id').val(), '', $(this), function () { $('#lsp-modal-selesai').modal('hide'); });
  });
})();
</script>
