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
    var urlCari     = <?= json_encode(base_url('cs/get-video-packing')) ?>;
    var urlMintaMp4 = <?= json_encode(base_url('cs/minta-video-mp4')) ?>;
    // Jeda pemantauan antrian MP4. Transcode memakan ~15 detik per menit
    // video, jadi tidak ada gunanya menanyakan lebih sering dari ini.
    var JEDA_PANTAU_MP4_MS = 10000;
    var pemantauMp4 = null;
    var noresiAktif = '';

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

    // Bagian MP4 di bawah setiap video. WebM tidak bisa diputar di iPhone dan
    // tidak diterima WhatsApp, jadi CS bisa minta versi MP4 -- dibuat di server
    // oleh cron, bukan saat tombol ditekan, karena konversinya lama.
    function gambarMp4(v) {
      var mp4 = v.mp4 || { status: 'TIDAK' };
      var id  = 'vp-mp4-' + v.id;

      if (v.status === 'MEREKAM') {
        return '<span id="' + id + '" class="text-muted">MP4 bisa disiapkan setelah packing selesai.</span>';
      }

      if (mp4.status === 'SIAP' && mp4.url) {
        return '<span id="' + id + '">' +
          '<a href="' + escapeHtml(mp4.url) + '" class="btn btn-success btn-xs">' +
            '<i class="fa fa-download"></i> Unduh MP4 (' + formatUkuran(mp4.ukuran_byte) + ')' +
          '</a> ' +
          '<small class="text-muted">untuk WhatsApp / iPhone</small>' +
        '</span>';
      }

      if (mp4.status === 'ANTRI' || mp4.status === 'PROSES') {
        var teks = mp4.status === 'PROSES'
          ? 'MP4 sedang dikonversi di server...'
          : 'MP4 mengantre di server...';
        if (mp4.terlalu_lama) {
          teks += ' <span class="text-danger">Sudah ' + mp4.menunggu_menit +
            ' menit belum selesai -- pastikan cron finalisasi_video dan ffmpeg jalan di server.</span>';
        }
        return '<span id="' + id + '"><i class="fa fa-spinner fa-spin"></i> ' + teks + '</span>';
      }

      var catatan = mp4.status === 'GAGAL'
        ? ' <span class="text-danger">Konversi sebelumnya gagal' +
            (mp4.pesan ? ': ' + escapeHtml(mp4.pesan) : '') + '. Coba lagi.</span>'
        : '';

      return '<span id="' + id + '">' +
        '<button type="button" class="btn btn-default btn-xs vp-minta-mp4" data-id="' + v.id + '">' +
          '<i class="fa fa-file-video-o"></i> Siapkan MP4' +
        '</button> ' +
        '<small class="text-muted">untuk dikirim lewat WhatsApp / diputar di iPhone</small>' +
        catatan +
      '</span>';
    }

    // Perbarui hanya bagian MP4 tiap video, bukan seluruh daftar: menggambar
    // ulang daftarnya akan memutus video yang sedang ditonton CS.
    function perbaruiMp4(list) {
      var adaAntrian = false;

      list.forEach(function (v) {
        var el = document.getElementById('vp-mp4-' + v.id);
        if (el) {
          el.outerHTML = gambarMp4(v);
        }
        if (v.mp4 && (v.mp4.status === 'ANTRI' || v.mp4.status === 'PROSES')) {
          adaAntrian = true;
        }
      });

      aturPemantauMp4(adaAntrian);
    }

    function aturPemantauMp4(aktif) {
      if (pemantauMp4) {
        clearTimeout(pemantauMp4);
        pemantauMp4 = null;
      }
      if (!aktif) return;

      pemantauMp4 = setTimeout(function () {
        pemantauMp4 = null;
        if (!noresiAktif) return;

        $.ajax({
          url: urlCari,
          method: 'POST',
          data: { noresi: noresiAktif },
          dataType: 'json',
          success: function (res) {
            if (res && res.code === 200) {
              perbaruiMp4(res.data);
            }
          },
          error: function () {
            // Server sedang tidak bisa dihubungi; coba lagi pada putaran berikutnya.
            aturPemantauMp4(true);
          }
        });
      }, JEDA_PANTAU_MP4_MS);
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
                '<a href="' + escapeHtml(v.url) + '?unduh=1">Unduh WebM</a>' +
              '</div>' +
              '<div style="margin-top: 8px;">' + gambarMp4(v) + '</div>' +
            '</div>' +
          '</div>';
      });

      $('#vp-hasil').html(html);

      var adaAntrian = list.some(function (v) {
        return v.mp4 && (v.mp4.status === 'ANTRI' || v.mp4.status === 'PROSES');
      });
      aturPemantauMp4(adaAntrian);
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
            noresiAktif = '';
            aturPemantauMp4(false);
            return;
          }
          noresiAktif = noresi;
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

    $('#vp-hasil').on('click', '.vp-minta-mp4', function () {
      var tombol = $(this);
      var id = tombol.data('id');

      tombol.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Meminta...');

      $.ajax({
        url: urlMintaMp4 + '/' + id,
        method: 'POST',
        dataType: 'json',
        success: function (res) {
          if (!res || res.code !== 200 || !res.data || !res.data.mp4) {
            tombol.prop('disabled', false).html('<i class="fa fa-file-video-o"></i> Siapkan MP4');
            pesan('warning', res && res.message ? res.message : 'Permintaan MP4 gagal.');
            return;
          }
          perbaruiMp4([{ id: id, status: 'SELESAI', mp4: res.data.mp4 }]);
        },
        error: function () {
          tombol.prop('disabled', false).html('<i class="fa fa-file-video-o"></i> Siapkan MP4');
          pesan('danger', 'Gagal menghubungi server.');
        }
      });
    });

    $('#vp-noresi').on('keydown', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        cari();
      }
    });

    $('#vp-noresi').focus();
  })();
</script>
