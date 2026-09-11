<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="rk-wrap">

  <div class="rk-header">
    <div>
      <h2 class="rk-title"><i class="fa fa-gavel"></i> Pengajuan Klaim Retur</h2>
      <p class="rk-sub">
        Ajukan klaim ke kurir / marketplace untuk retur yang barangnya tidak kembali atau isinya kurang,
        lalu catat putusan dan pergantiannya. Penutupan akhir dilakukan tim finance.
      </p>
    </div>
    <div class="rk-filter">
      <input type="text" id="kl_range" class="form-control" readonly style="width:210px;" />
      <button type="button" class="btn btn-primary" id="kl_load"><i class="fa fa-refresh"></i> Muat</button>
    </div>
  </div>

  <div class="rk-alarm" id="kl_alarm" style="display:none;"></div>

  <div class="rk-kpis">
    <div class="rk-card rk-card-red">
      <div class="rk-card-lbl">Kandidat Belum Diajukan</div>
      <div class="rk-card-val" id="kpi_kandidat">-</div>
      <div class="rk-card-sub" id="kpi_kandidat_rp">-</div>
    </div>
    <div class="rk-card">
      <div class="rk-card-lbl">Klaim Berjalan</div>
      <div class="rk-card-val" id="kpi_jalan">-</div>
      <div class="rk-card-sub" id="kpi_jalan_rp">-</div>
    </div>
    <div class="rk-card rk-card-green">
      <div class="rk-card-lbl">Sudah Diterima Kembali</div>
      <div class="rk-card-val" id="kpi_diterima">-</div>
      <div class="rk-card-sub">terverifikasi finance</div>
    </div>
    <div class="rk-card rk-card-dark">
      <div class="rk-card-lbl">Recovery Rate</div>
      <div class="rk-card-val" id="kpi_recovery">-</div>
      <div class="rk-card-sub">nilai kembali &divide; nilai diklaim</div>
    </div>
  </div>

  <ul class="nav nav-tabs" style="margin-bottom:14px;">
    <li class="active"><a href="#tab_kandidat" data-toggle="tab"><i class="fa fa-exclamation-triangle"></i> Kandidat Klaim</a></li>
    <li><a href="#tab_klaim" data-toggle="tab"><i class="fa fa-folder-open"></i> Klaim Berjalan</a></li>
  </ul>

  <div class="tab-content">

    <!-- ============ TAB 1: KANDIDAT ============ -->
    <div class="tab-pane active" id="tab_kandidat">
      <div class="rk-panel">
        <div class="rk-panel-body">
          <div class="row" style="margin-bottom:10px;">
            <div class="col-md-8">
              <select id="kd_bucket" class="form-control" style="width:auto;display:inline-block;">
                <option value="">Semua kategori layak klaim</option>
                <option value="LAYAK_KLAIM">Layak Klaim (masih dalam window)</option>
                <option value="BERMASALAH">Kembali, Isi Bermasalah</option>
                <option value="HANGUS">Hangus (lewat window)</option>
              </select>
              <select id="kd_kurir" class="form-control" style="width:auto;display:inline-block;">
                <option value="">Semua kurir</option>
              </select>
            </div>
            <div class="col-md-4 text-right">
              <span id="kd_terpilih" class="text-muted" style="margin-right:8px;">0 dipilih</span>
              <button type="button" class="btn btn-danger" id="btn_ajukan" disabled>
                <i class="fa fa-gavel"></i> Ajukan Klaim
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover" id="tbl_kandidat" style="width:100%;">
              <thead>
                <tr>
                  <th style="width:34px;"><input type="checkbox" id="kd_all"></th>
                  <th>Tgl Retur</th>
                  <th>No. Resi</th>
                  <th>Toko</th>
                  <th>Kurir</th>
                  <th>Qty MP</th>
                  <th>Qty Diterima</th>
                  <th>Kurang</th>
                  <th>Nilai</th>
                  <th>Umur</th>
                  <th>Kategori</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ TAB 2: KLAIM BERJALAN ============ -->
    <div class="tab-pane" id="tab_klaim">
      <div class="rk-panel">
        <div class="rk-panel-body">
          <div class="row" style="margin-bottom:10px;">
            <div class="col-md-12">
              <select id="kj_status" class="form-control" style="width:auto;display:inline-block;">
                <option value="">Semua status</option>
                <option value="DIAJUKAN">Diajukan (menunggu putusan)</option>
                <option value="DISETUJUI">Disetujui</option>
                <option value="SEBAGIAN">Disetujui sebagian</option>
                <option value="DITOLAK">Ditolak</option>
                <option value="DIGANTI">Sudah diganti (menunggu finance)</option>
                <option value="SELESAI">Selesai</option>
                <option value="BATAL">Batal</option>
              </select>
              <select id="kj_kurir" class="form-control" style="width:auto;display:inline-block;">
                <option value="">Semua kurir</option>
              </select>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover" id="tbl_klaim" style="width:100%;">
              <thead>
                <tr>
                  <th style="width:110px;">Aksi</th>
                  <th>Tgl Ajuan</th>
                  <th>No. Resi</th>
                  <th>No. Tiket</th>
                  <th>Kurir</th>
                  <th>Jenis</th>
                  <th>Nilai Klaim</th>
                  <th>Diterima</th>
                  <th>Status</th>
                  <th>Verifikasi Finance</th>
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

<!-- ============ MODAL: AJUKAN ============ -->
<div class="modal fade" id="md_ajukan" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-gavel"></i> Ajukan Klaim</h4>
      </div>
      <div class="modal-body">
        <p id="aj_ringkas" class="text-muted"></p>
        <div class="form-group">
          <label>Jenis Klaim</label>
          <select id="aj_jenis" class="form-control">
            <option value="">Otomatis (dari kondisi barang)</option>
            <?php foreach ($jenis_klaim as $j): ?>
              <option value="<?= htmlspecialchars($j) ?>"><?= htmlspecialchars(str_replace('_', ' ', $j)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>No. Tiket / Referensi Klaim <small class="text-muted">(opsional, bisa diisi belakangan)</small></label>
          <input type="text" id="aj_tiket" class="form-control" placeholder="mis. TKT-2026-0001">
        </div>
        <div class="form-group">
          <label>Catatan</label>
          <textarea id="aj_catatan" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="aj_simpan">Ajukan</button>
      </div>
    </div>
  </div>
</div>

<!-- ============ MODAL: PUTUSAN ============ -->
<div class="modal fade" id="md_putusan" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-gavel"></i> Putusan Kurir / Marketplace</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" id="pt_id">
        <div class="form-group">
          <label>Putusan</label>
          <select id="pt_status" class="form-control">
            <option value="DISETUJUI">Disetujui penuh</option>
            <option value="SEBAGIAN">Disetujui sebagian</option>
            <option value="DITOLAK">Ditolak</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tanggal Putusan</label>
          <input type="date" id="pt_tanggal" class="form-control">
        </div>
        <div class="form-group" id="pt_grup_nominal">
          <label>Nominal Disetujui (Rp)</label>
          <input type="number" id="pt_nominal" class="form-control" min="0" step="1">
        </div>
        <div class="form-group" id="pt_grup_alasan" style="display:none;">
          <label>Alasan Penolakan</label>
          <textarea id="pt_alasan" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="pt_simpan">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- ============ MODAL: PERGANTIAN ============ -->
<div class="modal fade" id="md_ganti" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-money"></i> Pergantian Diterima</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" id="pg_id">
        <div class="form-group">
          <label>Bentuk Pergantian</label>
          <select id="pg_bentuk" class="form-control">
            <?php foreach ($bentuk_ganti as $b): ?>
              <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars(str_replace('_', ' ', $b)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Tanggal Diterima</label>
          <input type="date" id="pg_tanggal" class="form-control">
        </div>
        <div class="form-group">
          <label>Nominal Diterima (Rp)</label>
          <input type="number" id="pg_nominal" class="form-control" min="0" step="1">
        </div>
        <div class="form-group">
          <label>Bukti / No. Referensi</label>
          <input type="text" id="pg_bukti" class="form-control" placeholder="no. transfer, no. nota potongan, dll.">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" id="pg_simpan">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- ============ MODAL: BATAL ============ -->
<div class="modal fade" id="md_batal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Batalkan Klaim</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" id="bt_id">
        <div class="form-group">
          <label>Alasan</label>
          <textarea id="bt_alasan" class="form-control" rows="3" placeholder="mis. barang ternyata datang tanggal ..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-danger" id="bt_simpan">Batalkan</button>
      </div>
    </div>
  </div>
</div>

<style>
.rk-wrap { font-family:'Inter','Segoe UI',sans-serif; color:#0f172a; }
.rk-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:12px; }
.rk-title { font-size:21px; font-weight:800; margin:0 0 4px; }
.rk-title .fa { color:#dc2626; }
.rk-sub { font-size:12px; color:#64748b; margin:0; max-width:660px; }
.rk-filter { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.rk-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:14px; }
.rk-card { background:#fff; border:1px solid #e2e8f0; border-left:4px solid #3b82f6; border-radius:10px; padding:14px 16px; }
.rk-card-green { border-left-color:#22c55e; }
.rk-card-red   { border-left-color:#dc2626; }
.rk-card-dark  { border-left-color:#7f1d1d; }
.rk-card-lbl { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#64748b; margin-bottom:6px; }
.rk-card-val { font-size:24px; font-weight:800; line-height:1.1; }
.rk-card-sub { font-size:12px; color:#475569; margin-top:3px; }
.rk-panel { background:#fff; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:14px; }
.rk-panel-body { padding:14px 16px; }
.rk-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:10px; font-weight:700; color:#fff; white-space:nowrap; }
.rk-alarm { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; border-radius:8px; padding:10px 14px; font-size:12px; margin-bottom:14px; }
</style>

<script type="text/javascript">
$(document).ready(function () {
  var BASE = '<?= $BASE ?>';
  var pilih = {};   // no_resi -> nilai

  $('#kl_range').daterangepicker({
    ranges: {
      '3 Bulan':   [moment().subtract(2,'month').startOf('month'), moment().endOf('day')],
      '6 Bulan':   [moment().subtract(5,'month').startOf('month'), moment().endOf('day')],
      'Bulan Lalu':[moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')]
    },
    locale: {
      format:'YYYY-MM-DD', separator:' s/d ', applyLabel:'Terapkan', cancelLabel:'Batal',
      customRangeLabel:'Custom', firstDay:1,
      daysOfWeek:['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
      monthNames:['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']
    },
    startDate: moment().subtract(2,'month').startOf('month'),
    endDate:   moment().endOf('day')
  });

  function rp(n)  { return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }
  function num(n) { return Number(n||0).toLocaleString('id-ID'); }
  function range() {
    var v = ($('#kl_range').val() || '').split(' s/d ');
    return { sd: (v[0] || '') + ' 00:00:00', ed: (v[1] || '') + ' 23:59:59' };
  }
  function today() { return moment().format('YYYY-MM-DD'); }

  // ---------- Tabel kandidat ----------
  var dtKandidat = $('#tbl_kandidat').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    order: [[8, 'desc']], deferLoading: 0,
    lengthMenu: [[10,25,50,100],[10,25,50,100]],
    ajax: {
      url: BASE + 'retur-klaim/get-kandidat',
      type: 'POST',
      data: function (d) {
        var r = range();
        d.start_date = r.sd; d.end_date = r.ed;
        d.bucket = $('#kd_bucket').val() || '';
        d.kurir  = $('#kd_kurir').val() || '';
      },
      dataSrc: function (res) {
        $('#kpi_kandidat').text(num(res.recordsTotal) + ' resi');
        $('#kpi_kandidat_rp').text(rp(res.nilai_total));
        return res.data;
      }
    },
    columns: [
      {data: 0, orderable: false}, {data: 1}, {data: 2}, {data: 3}, {data: 4},
      {data: 5}, {data: 6}, {data: 7}, {data: 8}, {data: 9}, {data: 10}
    ],
    language: { emptyTable: 'Tidak ada kandidat klaim pada rentang ini', zeroRecords: 'Tidak ada data' },
    drawCallback: function () {
      // Pertahankan centang saat pindah halaman
      $('#tbl_kandidat .kd-chk').each(function () {
        if (pilih[$(this).val()] !== undefined) $(this).prop('checked', true);
      });
      $('#kd_all').prop('checked', false);
    }
  });

  // ---------- Tabel klaim berjalan ----------
  var dtKlaim = $('#tbl_klaim').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    order: [[1, 'desc']], deferLoading: 0,
    lengthMenu: [[10,25,50,100],[10,25,50,100]],
    ajax: {
      url: BASE + 'retur-klaim/get-klaim-data',
      type: 'POST',
      data: function (d) {
        d.status = $('#kj_status').val() || '';
        d.kurir  = $('#kj_kurir').val() || '';
      }
    },
    columns: [
      {data: 0, orderable: false}, {data: 1}, {data: 2}, {data: 3}, {data: 4},
      {data: 5}, {data: 6}, {data: 7}, {data: 8}, {data: 9}
    ],
    language: { emptyTable: 'Belum ada klaim yang diajukan', zeroRecords: 'Tidak ada data' }
  });

  // ---------- Ringkasan ----------
  function loadStats() {
    $.post(BASE + 'retur-klaim/get-stats', {}, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) { return; } }

      var jalan = 0, jalanRp = 0;
      $.each(res.per_status, function (k, v) {
        if (k !== 'SELESAI' && k !== 'BATAL') { jalan += v.n; jalanRp += v.nilai; }
      });
      var selesai = res.per_status.SELESAI || {n: 0, diterima: 0};

      $('#kpi_jalan').text(num(jalan) + ' klaim');
      $('#kpi_jalan_rp').text(rp(jalanRp));
      $('#kpi_diterima').text(rp(selesai.diterima));
      $('#kpi_recovery').text(res.recovery_rate + '%');

      var sel1 = $('#kd_kurir').val(), sel2 = $('#kj_kurir').val();
      var opts = '<option value="">Semua kurir</option>';
      $.each(res.kurir_list, function (i, k) {
        opts += '<option value="' + $('<span>').text(k).html() + '">' + $('<span>').text(k).html() + '</option>';
      });
      $('#kj_kurir').html(opts).val(sel2);
      if (!$('#kd_kurir option').length) $('#kd_kurir').html(opts).val(sel1);

      if (res.alarm > 0) {
        $('#kl_alarm').show().html(
          '<i class="fa fa-exclamation-triangle"></i> <b>' + num(res.alarm) + ' klaim</b> ternyata barangnya ' +
          'MUNCUL di scan buka retur. Periksa: kalau klaimnya sudah dibayar, perusahaan menerima ganti rugi ' +
          'sekaligus barangnya. Batalkan klaim atau kembalikan dananya.'
        );
      } else {
        $('#kl_alarm').hide();
      }
    }, 'json');
  }

  // ---------- Pilih kandidat ----------
  $(document).on('change', '#tbl_kandidat .kd-chk', function () {
    var v = $(this).val();
    if (this.checked) pilih[v] = parseFloat($(this).data('nilai')) || 0;
    else delete pilih[v];
    updatePilih();
  });

  $('#kd_all').on('change', function () {
    var on = this.checked;
    $('#tbl_kandidat .kd-chk').each(function () {
      $(this).prop('checked', on);
      var v = $(this).val();
      if (on) pilih[v] = parseFloat($(this).data('nilai')) || 0;
      else delete pilih[v];
    });
    updatePilih();
  });

  function updatePilih() {
    var keys = Object.keys(pilih), tot = 0;
    $.each(pilih, function (k, v) { tot += v; });
    $('#kd_terpilih').text(keys.length ? keys.length + ' dipilih · ' + rp(tot) : '0 dipilih');
    $('#btn_ajukan').prop('disabled', keys.length === 0);
  }

  // ---------- Ajukan ----------
  $('#btn_ajukan').on('click', function () {
    var keys = Object.keys(pilih), tot = 0;
    $.each(pilih, function (k, v) { tot += v; });
    $('#aj_ringkas').html('<b>' + keys.length + ' resi</b> akan diajukan, total nilai <b>' + rp(tot) + '</b>.');
    $('#md_ajukan').modal('show');
  });

  $('#aj_simpan').on('click', function () {
    var $b = $(this).prop('disabled', true);
    var r = range();
    $.post(BASE + 'retur-klaim/ajukan', {
      resi: Object.keys(pilih),
      jenis_klaim: $('#aj_jenis').val(),
      no_tiket: $('#aj_tiket').val(),
      catatan: $('#aj_catatan').val(),
      start_date: r.sd, end_date: r.ed
    }, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) {} }
      alert(res.message || 'Selesai');
      if (res.code === 201) {
        pilih = {}; updatePilih();
        $('#md_ajukan').modal('hide');
        $('#aj_tiket').val(''); $('#aj_catatan').val('');
        dtKandidat.ajax.reload(); dtKlaim.ajax.reload(null, false); loadStats();
      }
    }, 'json').fail(function (xhr) {
      alert('Gagal terhubung ke server (HTTP ' + xhr.status + ').');
    }).always(function () { $b.prop('disabled', false); });
  });

  // ---------- Putusan ----------
  $(document).on('click', '.btn-putusan', function () {
    $('#pt_id').val($(this).data('id'));
    $('#pt_status').val('DISETUJUI').trigger('change');
    $('#pt_tanggal').val(today());
    $('#pt_nominal').val(''); $('#pt_alasan').val('');
    $('#md_putusan').modal('show');
  });

  $('#pt_status').on('change', function () {
    var tolak = $(this).val() === 'DITOLAK';
    $('#pt_grup_nominal').toggle(!tolak);
    $('#pt_grup_alasan').toggle(tolak);
  });

  $('#pt_simpan').on('click', function () {
    var $b = $(this).prop('disabled', true);
    $.post(BASE + 'retur-klaim/simpan-putusan', {
      id_klaim: $('#pt_id').val(),
      status_klaim: $('#pt_status').val(),
      tanggal_putusan: $('#pt_tanggal').val() ? $('#pt_tanggal').val() + ' 00:00:00' : '',
      nominal_disetujui: $('#pt_nominal').val(),
      alasan_tolak: $('#pt_alasan').val()
    }, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) {} }
      alert(res.message || 'Selesai');
      if (res.code === 200) { $('#md_putusan').modal('hide'); dtKlaim.ajax.reload(null, false); loadStats(); }
    }, 'json').fail(function (xhr) {
      alert('Gagal terhubung ke server (HTTP ' + xhr.status + ').');
    }).always(function () { $b.prop('disabled', false); });
  });

  // ---------- Pergantian ----------
  $(document).on('click', '.btn-ganti', function () {
    $('#pg_id').val($(this).data('id'));
    $('#pg_tanggal').val(today());
    $('#pg_nominal').val(''); $('#pg_bukti').val('');
    $('#md_ganti').modal('show');
  });

  $('#pg_simpan').on('click', function () {
    var $b = $(this).prop('disabled', true);
    $.post(BASE + 'retur-klaim/simpan-pergantian', {
      id_klaim: $('#pg_id').val(),
      bentuk_pergantian: $('#pg_bentuk').val(),
      tanggal_pergantian: $('#pg_tanggal').val() ? $('#pg_tanggal').val() + ' 00:00:00' : '',
      nominal_diterima: $('#pg_nominal').val(),
      bukti: $('#pg_bukti').val()
    }, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) {} }
      alert(res.message || 'Selesai');
      if (res.code === 200) { $('#md_ganti').modal('hide'); dtKlaim.ajax.reload(null, false); loadStats(); }
    }, 'json').fail(function (xhr) {
      alert('Gagal terhubung ke server (HTTP ' + xhr.status + ').');
    }).always(function () { $b.prop('disabled', false); });
  });

  // ---------- Batal ----------
  $(document).on('click', '.btn-batal', function () {
    $('#bt_id').val($(this).data('id'));
    $('#bt_alasan').val('');
    $('#md_batal').modal('show');
  });

  $('#bt_simpan').on('click', function () {
    var $b = $(this).prop('disabled', true);
    $.post(BASE + 'retur-klaim/batal', {
      id_klaim: $('#bt_id').val(),
      alasan: $('#bt_alasan').val()
    }, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) {} }
      alert(res.message || 'Selesai');
      if (res.code === 200) { $('#md_batal').modal('hide'); dtKlaim.ajax.reload(null, false); loadStats(); }
    }, 'json').fail(function (xhr) {
      alert('Gagal terhubung ke server (HTTP ' + xhr.status + ').');
    }).always(function () { $b.prop('disabled', false); });
  });

  // ---------- Verifikasi finance (kalau user juga punya akses finance) ----------
  $(document).on('click', '.btn-verif', function () {
    if (!confirm('Verifikasi bahwa pergantian benar-benar sudah diterima? Klaim akan ditutup (SELESAI).')) return;
    $.post(BASE + 'retur-klaim/simpan-verifikasi', {
      id_klaim: $(this).data('id'), verified: 1
    }, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) {} }
      alert(res.message || 'Selesai');
      dtKlaim.ajax.reload(null, false); loadStats();
    }, 'json');
  });

  // ---------- Muat ----------
  $('#kl_load').on('click', function () { dtKandidat.ajax.reload(); dtKlaim.ajax.reload(); loadStats(); });
  $('#kd_bucket, #kd_kurir').on('change', function () { dtKandidat.ajax.reload(); });
  $('#kj_status, #kj_kurir').on('change', function () { dtKlaim.ajax.reload(); });

  dtKandidat.ajax.reload();
  dtKlaim.ajax.reload();
  loadStats();
});
</script>
