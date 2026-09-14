<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">

      <div class="panel-heading">
        <h3 class="panel-title"><strong>Video Packing</strong></h3>
      </div>

      <div class="panel-body">
        <!-- Sengaja bukan <form>: submit form di aplikasi ini dibajak SPA
             (plugins.js) dan hasilnya diperlakukan sebagai ganti halaman. -->
        <div class="row">
          <div class="col-md-12">
            <div class="input-group">
              <input
                type="text"
                class="form-control"
                id="vp-noresi"
                placeholder="Scan atau ketik nomor resi"
                autocomplete="off"
              />
              <span class="input-group-btn">
                <button class="btn btn-default" type="button" id="vp-cari">
                  <i class="fa fa-search"></i> Cari
                </button>
              </span>
            </div>
            <p class="help-block" style="margin-top: 6px;">
              Scan resi lalu tekan Enter. Video packing untuk resi tersebut akan langsung diputar.
            </p>
          </div>
        </div>
      </div>

      <div class="panel-body" id="vp-hasil" style="padding-top: 0;"></div>

    </div>
  </div>
</div>

<script type="text/javascript">
  (function () {
    var urlCari = <?= json_encode(base_url('cs/get-video-packing')) ?>;

    function escapeHtml(teks) {
      return $('<div>').text(teks == null ? '' : teks).html();
    }

    function formatDurasi(detik) {
      detik = parseInt(detik, 10) || 0;
      if (detik <= 0) return 'tidak diketahui';
      var m = Math.floor(detik / 60);
      var s = detik % 60;
      return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    // MEREKAM  = packing masih berlangsung saat ini
    // TERPUTUS = rekaman mati di tengah jalan (tab tertutup, jaringan putus);
    //            berkasnya tetap bisa diputar sampai titik putus
    // SELESAI  = rekaman ditutup normal oleh scan kedua
    function labelStatus(status) {
      if (status === 'MEREKAM') {
        return ' <span class="label label-info">sedang merekam</span>';
      }
      if (status === 'TERPUTUS') {
        return ' <span class="label label-warning">rekaman terputus</span>';
      }
      return '';
    }

    function formatUkuran(byte) {
      byte = parseInt(byte, 10) || 0;
      if (byte < 1024 * 1024) {
        return (byte / 1024).toFixed(0) + ' KB';
      }
      return (byte / 1024 / 1024).toFixed(1) + ' MB';
    }

    function pesan(tipe, teks) {
      $('#vp-hasil').html(
        '<div class="alert alert-' + tipe + '" style="margin-bottom: 0;">' + escapeHtml(teks) + '</div>'
      );
    }

    function gambarHasil(noresi, list) {
      var html = '<p><strong>No Resi: ' + escapeHtml(noresi) + '</strong> &mdash; ' +
        list.length + ' video</p>';

      // Lebih dari satu bagian berarti rekamannya sempat terputus di tengah
      // packing -- biasanya tab packer mati. Bagiannya harus ditonton berurutan,
      // jadi itu dikatakan terus terang alih-alih sekadar menomori panel.
      if (list.length > 1) {
        html +=
          '<div class="alert alert-warning">' +
            '<i class="fa fa-info-circle"></i> ' +
            'Rekaman resi ini <strong>terbagi ' + list.length + ' bagian</strong> karena sempat ' +
            'terputus di tengah packing. Tonton berurutan dari Bagian 1; ada jeda beberapa detik ' +
            'antar bagian, yaitu saat rekamannya mati.' +
          '</div>';
      }

      list.forEach(function (v, i) {
        var judul = list.length > 1
          ? 'Bagian ' + (v.bagian || (i + 1)) + ' dari ' + list.length
          : 'Rekaman packing';

        html +=
          '<div class="panel panel-default">' +
            '<div class="panel-heading">' +
              '<strong>' + escapeHtml(judul) + '</strong>' + labelStatus(v.status) +
              '<span class="pull-right text-muted">' + escapeHtml(v.mulai_at) + '</span>' +
            '</div>' +
            '<div class="panel-body">' +
              '<video src="' + escapeHtml(v.url) + '" controls preload="metadata" ' +
                'style="width: 100%; max-width: 640px; background: #000;"></video>' +
              '<div style="margin-top: 8px;">' +
                'Packer: <strong>' + escapeHtml(v.nama_packer) + '</strong> &middot; ' +
                'Komputer: ' + escapeHtml(v.nama_komputer) + ' &middot; ' +
                'Durasi: ' + formatDurasi(v.durasi_detik) + ' &middot; ' +
                'Ukuran: ' + formatUkuran(v.ukuran_byte) + ' &middot; ' +
                'Berkas: <code>' + escapeHtml(v.nama_file || '-') + '</code> &middot; ' +
                '<a href="' + escapeHtml(v.url) + '?unduh=1">Unduh</a>' +
              '</div>' +
            '</div>' +
          '</div>';
      });

      $('#vp-hasil').html(html);
    }

    function cari() {
      var noresi = $.trim($('#vp-noresi').val());

      if (noresi === '') {
        pesan('warning', 'Nomor resi tidak boleh kosong.');
        return;
      }

      pesan('info', 'Mencari video untuk resi ' + noresi + '...');

      $.ajax({
        url: urlCari,
        method: 'POST',
        data: { noresi: noresi },
        dataType: 'json',
        success: function (res) {
          if (!res || res.code !== 200) {
            pesan('warning', res && res.message ? res.message : 'Video tidak ditemukan.');
            return;
          }
          gambarHasil(noresi, res.data);
        },
        error: function () {
          pesan('danger', 'Gagal menghubungi server.');
        }
      });

      // Siapkan input untuk scan berikutnya.
      $('#vp-noresi').select();
    }

    $('#vp-cari').on('click', cari);

    $('#vp-noresi').on('keydown', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        cari();
      }
    });

    $('#vp-noresi').focus();
  })();
</script>
