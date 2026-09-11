<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="rk-wrap">

  <div class="rk-header">
    <div>
      <h2 class="rk-title"><i class="fa fa-check-square-o"></i> Verifikasi Klaim Retur</h2>
      <p class="rk-sub">
        Penutup siklus retur: pastikan pergantian dari kurir / marketplace benar-benar diterima,
        lalu tutup klaimnya. Hanya klaim berstatus <b>DIGANTI</b> yang bisa diverifikasi.
      </p>
    </div>
    <div class="rk-filter">
      <button type="button" class="btn btn-primary" id="vf_load"><i class="fa fa-refresh"></i> Muat</button>
    </div>
  </div>

  <div class="rk-alarm" id="vf_alarm" style="display:none;"></div>

  <div class="rk-kpis">
    <div class="rk-card rk-card-amber">
      <div class="rk-card-lbl">Menunggu Verifikasi Anda</div>
      <div class="rk-card-val" id="kpi_menunggu">-</div>
      <div class="rk-card-sub" id="kpi_menunggu_rp">-</div>
    </div>
    <div class="rk-card rk-card-green">
      <div class="rk-card-lbl">Sudah Diverifikasi</div>
      <div class="rk-card-val" id="kpi_selesai">-</div>
      <div class="rk-card-sub" id="kpi_selesai_rp">-</div>
    </div>
    <div class="rk-card">
      <div class="rk-card-lbl">Total Diklaim</div>
      <div class="rk-card-val" id="kpi_diklaim">-</div>
      <div class="rk-card-sub">seluruh klaim tercatat</div>
    </div>
    <div class="rk-card rk-card-dark">
      <div class="rk-card-lbl">Recovery Rate</div>
      <div class="rk-card-val" id="kpi_recovery">-</div>
      <div class="rk-card-sub">nilai kembali &divide; nilai diklaim</div>
    </div>
  </div>

  <div class="rk-row">
    <div class="rk-panel">
      <div class="rk-panel-head"><i class="fa fa-truck"></i> Recovery per Kurir</div>
      <div class="rk-panel-body rk-scroll">
        <table class="rk-matrix" id="vf_kurir"><tbody><tr><td class="rk-empty">Memuat&hellip;</td></tr></tbody></table>
      </div>
    </div>
    <div class="rk-panel">
      <div class="rk-panel-head"><i class="fa fa-exclamation-triangle"></i> Klaim yang Barangnya Datang Belakangan</div>
      <div class="rk-panel-body rk-scroll">
        <table class="rk-matrix" id="vf_alarm_tbl"><tbody><tr><td class="rk-empty">Memuat&hellip;</td></tr></tbody></table>
      </div>
    </div>
  </div>

  <div class="rk-panel">
    <div class="rk-panel-head"><i class="fa fa-list"></i> Daftar Klaim</div>
    <div class="rk-panel-body">
      <div style="margin-bottom:10px;">
        <select id="vf_filter" class="form-control" style="width:auto;display:inline-block;">
          <option value="0">Menunggu verifikasi (DIGANTI)</option>
          <option value="1">Sudah diverifikasi (SELESAI)</option>
          <option value="">Semua</option>
        </select>
        <select id="vf_kurir_sel" class="form-control" style="width:auto;display:inline-block;">
          <option value="">Semua kurir</option>
        </select>
      </div>

      <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover" id="tbl_verif" style="width:100%;">
          <thead>
            <tr>
              <th style="width:70px;">Aksi</th>
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

<!-- MODAL VERIFIKASI -->
<div class="modal fade" id="md_verif" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-check"></i> Verifikasi Pergantian</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" id="vf_id">
        <p class="text-muted">
          Dengan memverifikasi, Anda menyatakan pergantian dari kurir / marketplace sudah benar-benar
          diterima sesuai nominal yang tercatat. Status klaim menjadi <b>SELESAI</b>.
        </p>
        <div class="form-group">
          <label>Catatan Finance <small class="text-muted">(opsional)</small></label>
          <textarea id="vf_catatan" class="form-control" rows="2" placeholder="mis. masuk rekening BCA tgl ..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" id="vf_simpan">Verifikasi &amp; Tutup</button>
      </div>
    </div>
  </div>
</div>

<style>
.rk-wrap { font-family:'Inter','Segoe UI',sans-serif; color:#0f172a; }
.rk-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:12px; }
.rk-title { font-size:21px; font-weight:800; margin:0 0 4px; }
.rk-title .fa { color:#16a34a; }
.rk-sub { font-size:12px; color:#64748b; margin:0; max-width:660px; }
.rk-filter { display:flex; gap:8px; align-items:center; }
.rk-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:14px; }
.rk-card { background:#fff; border:1px solid #e2e8f0; border-left:4px solid #3b82f6; border-radius:10px; padding:14px 16px; }
.rk-card-green { border-left-color:#22c55e; }
.rk-card-amber { border-left-color:#f59e0b; }
.rk-card-dark  { border-left-color:#7f1d1d; }
.rk-card-lbl { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#64748b; margin-bottom:6px; }
.rk-card-val { font-size:24px; font-weight:800; line-height:1.1; }
.rk-card-sub { font-size:12px; color:#475569; margin-top:3px; }
.rk-panel { background:#fff; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:14px; overflow:hidden; }
.rk-panel-head { padding:11px 16px; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700; background:#f8fafc; }
.rk-panel-head .fa { color:#3b82f6; margin-right:5px; }
.rk-panel-body { padding:14px 16px; }
.rk-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width:1100px) { .rk-row { grid-template-columns:1fr; } }
.rk-scroll { max-height:300px; overflow:auto; }
.rk-empty { color:#94a3b8; font-size:12px; text-align:center; padding:18px 0; }
.rk-matrix { width:100%; border-collapse:collapse; font-size:12px; }
.rk-matrix th { position:sticky; top:0; background:#f8fafc; padding:7px 8px; font-size:10px; font-weight:700;
                text-transform:uppercase; color:#475569; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.rk-matrix td { padding:6px 8px; border-bottom:1px solid #f1f5f9; white-space:nowrap; }
.rk-matrix td.rk-n { text-align:right; font-variant-numeric:tabular-nums; }
.rk-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:10px; font-weight:700; color:#fff; white-space:nowrap; }
.rk-alarm { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; border-radius:8px; padding:10px 14px; font-size:12px; margin-bottom:14px; }
</style>

<script type="text/javascript">
$(document).ready(function () {
  var BASE = '<?= $BASE ?>';

  function rp(n)  { return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }
  function num(n) { return Number(n||0).toLocaleString('id-ID'); }
  function esc(s) { return $('<span>').text(s == null ? '' : s).html(); }

  var dt = $('#tbl_verif').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    order: [[1, 'desc']], deferLoading: 0,
    lengthMenu: [[10,25,50,100],[10,25,50,100]],
    ajax: {
      url: BASE + 'retur-klaim/get-klaim-data',
      type: 'POST',
      data: function (d) {
        d.siap_verifikasi = 1;
        d.finance_verified = $('#vf_filter').val();
        d.kurir = $('#vf_kurir_sel').val() || '';
      }
    },
    columns: [
      {data: 0, orderable: false}, {data: 1}, {data: 2}, {data: 3}, {data: 4},
      {data: 5}, {data: 6}, {data: 7}, {data: 8}, {data: 9}
    ],
    language: { emptyTable: 'Tidak ada klaim pada filter ini', zeroRecords: 'Tidak ada data' }
  });

  function loadStats() {
    $.post(BASE + 'retur-klaim/get-stats', {}, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) { return; } }

      var diganti = res.per_status.DIGANTI || {n: 0, nilai: 0, diterima: 0};
      var selesai = res.per_status.SELESAI || {n: 0, nilai: 0, diterima: 0};

      $('#kpi_menunggu').text(num(diganti.n) + ' klaim');
      $('#kpi_menunggu_rp').text(rp(diganti.diterima));
      $('#kpi_selesai').text(num(selesai.n) + ' klaim');
      $('#kpi_selesai_rp').text(rp(selesai.diterima));
      $('#kpi_diklaim').text(rp(res.nilai_klaim));
      $('#kpi_recovery').text(res.recovery_rate + '%');

      var sel = $('#vf_kurir_sel').val();
      var opts = '<option value="">Semua kurir</option>';
      $.each(res.kurir_list, function (i, k) { opts += '<option value="' + esc(k) + '">' + esc(k) + '</option>'; });
      $('#vf_kurir_sel').html(opts).val(sel);

      // Recovery per kurir
      var $t = $('#vf_kurir').empty();
      if (!res.by_kurir || !res.by_kurir.length) {
        $t.html('<tbody><tr><td class="rk-empty">Belum ada klaim</td></tr></tbody>');
      } else {
        var h = '<thead><tr><th>Kurir</th><th style="text-align:right">Klaim</th>' +
                '<th style="text-align:right">Diklaim</th><th style="text-align:right">Diterima</th>' +
                '<th style="text-align:right">Recovery</th></tr></thead><tbody>';
        $.each(res.by_kurir, function (i, r) {
          var pct = Number(r.diklaim) > 0 ? (Number(r.diterima) / Number(r.diklaim) * 100).toFixed(1) : '0.0';
          h += '<tr><td>' + esc(r.label) + '</td><td class="rk-n">' + num(r.n_klaim) + '</td>' +
               '<td class="rk-n">' + rp(r.diklaim) + '</td><td class="rk-n">' + rp(r.diterima) + '</td>' +
               '<td class="rk-n"><b>' + pct + '%</b></td></tr>';
        });
        $t.html(h + '</tbody>');
      }

      if (res.alarm > 0) {
        $('#vf_alarm').show().html(
          '<i class="fa fa-exclamation-triangle"></i> <b>' + num(res.alarm) + ' klaim</b> ternyata barangnya ' +
          'sudah masuk lewat scan buka retur. Kalau pergantiannya sudah diterima, itu dobel — perlu dikembalikan.'
        );
      } else {
        $('#vf_alarm').hide();
      }
    }, 'json');
  }

  function loadAlarm() {
    $.post(BASE + 'retur-klaim/get-alarm', {}, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) { return; } }
      var $t = $('#vf_alarm_tbl').empty();

      if (!res.data || !res.data.length) {
        $t.html('<tbody><tr><td class="rk-empty">Tidak ada — aman</td></tr></tbody>');
        return;
      }

      var h = '<thead><tr><th>No. Resi</th><th>Kurir</th><th>Status</th>' +
              '<th style="text-align:right">Diterima</th><th>Barang Datang</th></tr></thead><tbody>';
      $.each(res.data, function (i, r) {
        h += '<tr><td>' + esc(r.no_resi) + '</td><td>' + esc(r.kurir) + '</td>' +
             '<td>' + esc(r.status) + (r.verified ? ' <span class="rk-badge" style="background:#dc2626">SUDAH DIBAYAR</span>' : '') + '</td>' +
             '<td class="rk-n">' + (r.diterima === null ? '-' : rp(r.diterima)) + '</td>' +
             '<td>' + (r.tgl_datang ? moment(r.tgl_datang).format('DD/MM/YYYY') : '-') + '</td></tr>';
      });
      $t.html(h + '</tbody>');
    }, 'json');
  }

  $(document).on('click', '.btn-verif', function () {
    $('#vf_id').val($(this).data('id'));
    $('#vf_catatan').val('');
    $('#md_verif').modal('show');
  });

  $('#vf_simpan').on('click', function () {
    var $b = $(this).prop('disabled', true);
    $.post(BASE + 'retur-klaim/simpan-verifikasi', {
      id_klaim: $('#vf_id').val(), verified: 1, finance_catatan: $('#vf_catatan').val()
    }, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) {} }
      alert(res.message || 'Selesai');
      if (res.code === 200) { $('#md_verif').modal('hide'); reload(); }
    }, 'json').fail(function (xhr) {
      alert('Gagal terhubung ke server (HTTP ' + xhr.status + ').');
    }).always(function () { $b.prop('disabled', false); });
  });

  function reload() { dt.ajax.reload(null, false); loadStats(); loadAlarm(); }

  $('#vf_load').on('click', reload);
  $('#vf_filter, #vf_kurir_sel').on('change', function () { dt.ajax.reload(); });

  dt.ajax.reload();
  loadStats();
  loadAlarm();
});
</script>
