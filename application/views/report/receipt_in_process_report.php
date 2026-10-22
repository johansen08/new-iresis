<script type="text/javascript">
  // Dideklarasikan sebelum view tab dimuat: skrip tab langsung memakai renderNoResi saat dibuat
  /** Render kolom No. Resi: teks + ikon salin sekali klik. */
  function renderNoResi(data, type) {
    if (type !== 'display' || !data) return data;
    var aman = $('<div>').text(data).html();
    return aman + '<button type="button" class="btn-salin-resi" data-resi="' + aman + '" title="Salin no. resi"><i class="fa fa-copy"></i></button>';
  }

  /** Salin lewat execCommand: jalan di HTTP (klien LAN), asalkan dipanggil dari klik user. */
  function salinTeksLama(teks) {
    var ta = document.createElement('textarea');
    ta.value = teks;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
    return ok;
  }

  /** Salin teks ke clipboard. Clipboard API hanya ada di HTTPS/localhost dan bisa ditolak, maka ada cadangan. */
  function salinTeks(teks) {
    var cadangan = function() {
      return salinTeksLama(teks) ? Promise.resolve() : Promise.reject();
    };
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(teks).catch(cadangan);
    }
    return cadangan();
  }

  $(document).off('click.salinresi').on('click.salinresi', '.btn-salin-resi', function(e) {
    e.stopPropagation();
    var $btn = $(this);
    salinTeks($btn.data('resi') + '').then(function() {
      $btn.addClass('tersalin').attr('title', 'Tersalin').find('i').removeClass('fa-copy').addClass('fa-check');
      setTimeout(function() {
        $btn.removeClass('tersalin').attr('title', 'Salin no. resi').find('i').removeClass('fa-check').addClass('fa-copy');
      }, 1200);
    }, function() {
      window.prompt('Salin no. resi (Ctrl+C):', $btn.data('resi'));
    });
  });
</script>
<div class="row">
  <div class="col-md-12">

    <div class="panel panel-default tabs">
      <ul class="nav nav-tabs nav-justified nav-tabs-ptr">
        <li class="active"><a href="#tab0" data-toggle="tab">Belum Scan Picker</a></li>
        <li><a href="#tab1" data-toggle="tab">Belum Scan Packer</a></li>
        <li><a href="#tab2" data-toggle="tab">Belum Scan HO</a></li>
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

<style>
  .tabs > .panel-body.tab-content { padding: 8px 10px; }
  .btn-salin-resi { margin-left: 6px; padding: 0 3px; color: #999; cursor: pointer; border: 0; background: transparent; opacity: .7; }
  .btn-salin-resi:hover { color: #337ab7; opacity: 1; }
  .btn-salin-resi.tersalin { color: #3c9a3c; opacity: 1; }
</style>
<script type="text/javascript">
  // Header tabel di tab tersembunyi perlu disesuaikan ulang saat tab dibuka
  $(document).off('shown.bs.tab.ptr').on('shown.bs.tab.ptr', 'a[data-toggle="tab"]', function() {
    $($.fn.dataTable.fnTables(true)).each(function() { $(this).DataTable().columns.adjust(); });
  });

  /**
   * Menentukan apakah sebuah resi wajib dikirim hari ini (untuk penanda merah).
   * @param {string} marketplace Nama marketplace (kolom Marketplace)
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