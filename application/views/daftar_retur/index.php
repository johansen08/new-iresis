<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Daftar Retur Penjualan</strong></h3>
      </div>
      <div class="panel-body">

        <p class="text-muted" style="margin-bottom:12px;">
          Retur ekspedisi: paket yang sudah diambil kurir tetapi dikembalikan ke toko oleh ekspedisi.
          Umur dihitung dari tanggal retur.
          <span style="display:inline-block;padding:1px 8px;border-radius:10px;background:#f59e0b;color:#fff;font-weight:600;">&ge; <?= (int) $umur_oranye ?> hari</span> peringatan,
          <span style="display:inline-block;padding:1px 8px;border-radius:10px;background:#dc2626;color:#fff;font-weight:600;">&ge; <?= (int) $umur_merah ?> hari</span> wajib klaim ke ekspedisi.
          Paket yang sudah dibuka tidak diberi warna.
        </p>

        <div class="row">
          <div class="col-md-12">
            <div class="form-horizontal">
              <div class="form-group">
                <label class="col-md-2 control-label">Tanggal Retur</label>
                <div class="col-md-4">
                  <input type="text" id="dr_rentang" class="form-control" placeholder="Pilih rentang tanggal" readonly />
                </div>
                <div class="col-md-6 text-right">
                  <button type="button" class="btn btn-primary" id="dr_tampilkan">
                    <i class="fa fa-search"></i> Tampilkan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr style="margin-top:10px;margin-bottom:20px;" />

        <div class="table-responsive">
          <table class="table table-striped table-bordered table-hover" id="dt_daftar_retur" style="width:100%;">
            <thead>
              <tr>
                <th style="width:50px;">No</th>
                <th>No. Resi</th>
                <th>No. Pesanan</th>
                <th>Tanggal Retur</th>
                <th>Umur</th>
                <th>Ekspedisi</th>
                <th>Marketplace</th>
                <th>Toko</th>
                <th>Nilai</th>
                <th>Posisi Paket</th>
                <th>Sumber Tanggal</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';

    $('#dr_rentang').daterangepicker({
        startDate: moment().startOf('year'),
        endDate: moment(),
        ranges: {
            'Hari Ini'        : [moment(), moment()],
            '7 Hari Terakhir' : [moment().subtract(6, 'days'), moment()],
            '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
            'Bulan Ini'       : [moment().startOf('month'), moment().endOf('month')],
            'Tahun Ini'       : [moment().startOf('year'), moment()]
        },
        locale: {
            format: 'YYYY-MM-DD', separator: ' s/d ',
            applyLabel: 'Terapkan', cancelLabel: 'Batal',
            fromLabel: 'Dari', toLabel: 'Sampai', customRangeLabel: 'Custom',
            daysOfWeek: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
            monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
            firstDay: 1
        }
    });
    $('#dr_rentang').val(moment().startOf('year').format('YYYY-MM-DD') + ' s/d ' + moment().format('YYYY-MM-DD'));

    var tabel = $('#dt_daftar_retur').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[4, 'desc']],          // umur terbesar di atas
        columnDefs: [
            { orderable: false, targets: 0 }
        ],
        ajax: {
            url: BASE + 'retur/get-daftar-retur-penjualan',
            type: 'POST',
            data: function (d) {
                var v = $('#dr_rentang').val().split(' s/d ');
                d.start_date = v[0] || '';
                d.end_date = v[1] || '';
            }
        },
        language: {
            processing: 'Memuat data...',
            emptyTable: 'Tidak ada retur pada rentang tanggal ini',
            zeroRecords: 'Tidak ada data yang cocok'
        }
    });

    $('#dr_tampilkan').on('click', function () { tabel.ajax.reload(); });
});
</script>
