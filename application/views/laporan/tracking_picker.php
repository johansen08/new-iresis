<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Tracking Picker</h3>
      </div>
      <div class="panel-body">

        <div class="row margin-bottom-15">
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-addon">Tanggal</span>
              <input type="date" class="form-control" id="filter-tanggal" value="<?= $tanggal ?>">
              <span class="input-group-btn">
                <button type="button" class="btn btn-primary" id="btn-filter-tanggal"><i class="fa fa-search"></i> Tampilkan</button>
                <a href="#" class="btn btn-success" id="btn-download-tracking-picker"><i class="fa fa-download"></i> Download Excel</a>
              </span>
            </div>
          </div>
          <div class="col-md-8">
            <p class="text-muted" style="margin:5px 0 0">
              Klik baris picker untuk lihat urutan resi &amp; perpindahan lantai hari itu.
              Lantai diambil dari angka pertama kode rak (mis. <code>3B-J1-5</code> = lantai 3).
              Untuk resi campuran yang SKU-nya tersebar di beberapa lantai, urutan pengambilan
              di dalam resi yang sama tidak tercatat — hanya urutan antar resi.
            </p>
          </div>
        </div>

        <table class="table table-striped table-bordered" id="tbl-tracking-picker">
          <thead>
            <tr>
              <th width="40">No</th>
              <th>Nama Picker</th>
              <th width="100" class="text-center">Total Resi</th>
              <th width="100" class="text-center">Satuan</th>
              <th width="100" class="text-center">Campuran</th>
              <th width="160">Lantai Dikunjungi</th>
              <th width="120" class="text-center">Pindah Lantai</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): $i = 1; foreach ($rows as $row): ?>
            <tr class="row-tracking-picker" style="cursor:pointer" data-id-pegawai="<?= $row['id_pegawai'] ?>" data-nama="<?= htmlspecialchars($row['nama_pegawai']) ?>">
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['nama_pegawai']) ?></td>
              <td class="text-center"><strong><?= $row['total_resi'] ?></strong></td>
              <td class="text-center"><?= $row['resi_satuan'] ?></td>
              <td class="text-center"><?= $row['resi_campuran'] ?></td>
              <td><?= $row['lantai_dikunjungi'] ?></td>
              <td class="text-center">
                <?php if ($row['jumlah_pindah_lantai'] > 0): ?>
                  <span class="badge badge-warning"><?= $row['jumlah_pindah_lantai'] ?>x</span>
                <?php else: ?>
                  <span class="text-muted">0x</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center text-muted">Tidak ada data picker untuk tanggal ini</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row" id="panel-detail-tracking-picker" style="display:none">
  <div class="col-md-12">
    <div class="panel panel-info">
      <div class="panel-heading">
        <h3 class="panel-title">
          <span id="detail-tracking-picker-title">Urutan Resi</span>
          <button type="button" class="close pull-right" id="btn-close-detail-tracking-picker" aria-label="Tutup">&times;</button>
        </h3>
      </div>
      <div class="panel-body">
        <table class="table table-striped table-bordered" id="tbl-detail-tracking-picker" width="100%">
          <thead>
            <tr>
              <th width="40">No</th>
              <th width="70">Jam</th>
              <th>No Resi</th>
              <th width="90">Tipe</th>
              <th width="70" class="text-center">Jml SKU</th>
              <th>SKU</th>
              <th width="90">Lantai</th>
              <th width="220">Keterangan Perpindahan</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  function loadSummary() {
    var tanggal = $('#filter-tanggal').val();
    $('#panel-detail-tracking-picker').hide();

    $.post('laporan/get-data-tracking-picker', { tanggal: tanggal }, function(res) {
      var data = (typeof res === 'string') ? JSON.parse(res) : res;
      var tbody = $('#tbl-tracking-picker tbody');
      tbody.empty();

      if (data.data && data.data.length) {
        data.data.forEach(function(row) {
          var pindahBadge = row[7] > 0
            ? '<span class="badge badge-warning">' + row[7] + 'x</span>'
            : '<span class="text-muted">0x</span>';

          tbody.append(
            $('<tr class="row-tracking-picker" style="cursor:pointer">')
              .attr('data-id-pegawai', row[1])
              .attr('data-nama', row[2])
              .html(
                '<td>' + row[0] + '</td>' +
                '<td>' + row[2] + '</td>' +
                '<td class="text-center"><strong>' + row[3] + '</strong></td>' +
                '<td class="text-center">' + row[4] + '</td>' +
                '<td class="text-center">' + row[5] + '</td>' +
                '<td>' + row[6] + '</td>' +
                '<td class="text-center">' + pindahBadge + '</td>'
              )
          );
        });
      } else {
        tbody.append('<tr><td colspan="7" class="text-center text-muted">Tidak ada data picker untuk tanggal ini</td></tr>');
      }
    });
  }

  function renderDetailRows(rows) {
    var tbody = $('#tbl-detail-tracking-picker tbody');
    tbody.empty();

    if (!rows || !rows.length) {
      tbody.append('<tr><td colspan="8" class="text-center text-muted">Tidak ada resi</td></tr>');
      return;
    }

    rows.forEach(function(row) {
      tbody.append(
        '<tr>' +
          '<td>' + row[0] + '</td>' +
          '<td>' + row[1] + '</td>' +
          '<td>' + row[2] + '</td>' +
          '<td>' + row[3] + '</td>' +
          '<td class="text-center">' + row[4] + '</td>' +
          '<td>' + row[5] + '</td>' +
          '<td>' + row[6] + '</td>' +
          '<td>' + row[7] + '</td>' +
        '</tr>'
      );
    });
  }

  $(document).off('click', '#btn-filter-tanggal').on('click', '#btn-filter-tanggal', loadSummary);

  $(document).off('click', '#btn-download-tracking-picker').on('click', '#btn-download-tracking-picker', function(e) {
    e.preventDefault();
    var tanggal = $('#filter-tanggal').val();
    window.location = 'laporan/export-tracking-picker?tanggal=' + encodeURIComponent(tanggal);
  });

  $(document).off('click', '#btn-close-detail-tracking-picker').on('click', '#btn-close-detail-tracking-picker', function() {
    $('#panel-detail-tracking-picker').hide();
  });

  $(document).off('click', '.row-tracking-picker').on('click', '.row-tracking-picker', function() {
    var idPegawai = $(this).data('id-pegawai');
    var nama = $(this).data('nama');
    var tanggal = $('#filter-tanggal').val();

    $('#detail-tracking-picker-title').text('Urutan Resi - ' + nama);
    $('#tbl-detail-tracking-picker tbody').html('<tr><td colspan="8" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat...</td></tr>');
    $('#panel-detail-tracking-picker').show();

    $('html, body').animate({ scrollTop: $('#panel-detail-tracking-picker').offset().top - 20 }, 300);

    $.post('laporan/get-data-tracking-picker-detail', { id_pegawai: idPegawai, tanggal: tanggal }, function(res) {
      var data = (typeof res === 'string') ? JSON.parse(res) : res;
      renderDetailRows(data.data);
    });
  });

  $(document).on('pjax:end', function() {
    $(document).off('click', '#btn-filter-tanggal');
    $(document).off('click', '#btn-close-detail-tracking-picker');
    $(document).off('click', '.row-tracking-picker');
  });
})();
</script>
