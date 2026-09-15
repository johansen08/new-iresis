<div class="row">
  <div class="col-md-12">

    <div class="panel panel-default tabs">
      <ul class="nav nav-tabs nav-justified">
        <li class="active"><a href="#tab0" data-toggle="tab">Resi Belum Pick</a></li>
        <li><a href="#tab1" data-toggle="tab">Resi Picker Belum Scan Packer</a></li>
        <li><a href="#tab2" data-toggle="tab">Resi Packer Belum Scan Keluar</a></li>
      </ul>
      <div class="panel-body tab-content">
        <div class="tab-pane active" id="tab0">
          <?php $this->load->view('report/receipt_in_process_tab0') ?>
        </div>
        <div class="tab-pane" id="tab1">
          <?php $this->load->view('report/receipt_in_process_tab1') ?>
        </div>
        <div class="tab-pane" id="tab2">
          <?php $this->load->view('report/receipt_in_process_tab2') ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  /**
   * Menentukan apakah sebuah resi wajib dikirim hari ini (untuk penanda merah).
   * @param {string} marketplace Nama marketplace (kolom Market Place)
   * @param {string} scanDate    Tanggal scan resi (YYYY-MM-DD)
   * @param {string} scanTime    Jam scan resi (HH:mm:ss)
   * @param {string} batasKirim  Batas kirim (YYYY-MM-DD HH:mm:ss) atau '-'
   */
  function isWajibKirimHariIni(marketplace, scanDate, scanTime, batasKirim) {
    var today = moment().format('YYYY-MM-DD');

    // Aturan umum: batas kirim jatuh pada hari ini.
    if (batasKirim && batasKirim !== '-') {
      var deadline = moment(batasKirim, 'YYYY-MM-DD HH:mm:ss');
      if (deadline.isValid() && deadline.isSame(moment(), 'day')) {
        return true;
      }
    }

    // Aturan Lazada: resi yang diproses s/d jam 15:00 wajib dikirim hari ini.
    var mp = (marketplace || '').toLowerCase();
    if (mp.indexOf('lazada') !== -1 && scanDate === today && scanTime <= '15:00:00') {
      return true;
    }

    return false;
  }

  /**
   * Mengganti pemicu pencarian bawaan DataTables dengan jeda (debounce).
   *
   * DataTables 1.10.2 hanya men-throttle 400 ms: huruf pertama langsung dikirim
   * ke server, lalu setiap jeda mengetik memicu request baru. Di laporan
   * server-side ini tiap request menjalankan query data + hitung total, jadi
   * request baru dikirim setelah user berhenti mengetik, atau langsung saat Enter.
   * @param {object} api     DataTables API (this.api() di initComplete)
   * @param {string} tableId Selector tabel, mis. '#datatable-receipt-process-tab0'
   */
  function pasangPencarianTertunda(api, tableId) {
    var jeda = 700;
    var timer = null;

    $(tableId + '_filter input')
      .off('keyup.DT search.DT input.DT paste.DT cut.DT')
      .on('keyup.DT input.DT paste.DT cut.DT', function(e) {
        var el = this;
        var cari = function() {
          // Tabel bisa sudah dibuat ulang (klik Tampilkan) atau halaman sudah pindah.
          if (!document.body.contains(el)) return;
          if (api.search() !== el.value) {
            api.search(el.value).draw();
          }
        };

        clearTimeout(timer);
        if (e.type === 'keyup' && e.keyCode === 13) {
          cari();
        } else {
          timer = setTimeout(cari, jeda);
        }
      });
  }
</script>