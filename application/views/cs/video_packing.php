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
              <strong>Cara pakai:</strong> scan atau ketik nomor resi, lalu tekan <kbd>Enter</kbd>.
              Video langsung muncul dan diputar. Tekan <kbd>Esc</kbd> untuk mengosongkan kolom.
            </p>
            <div id="vp-riwayat" style="display: none; margin-top: 4px;"></div>
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
    // Jeda pemantauan antrian MP4. Worker mulai bekerja dalam hitungan detik
    // setelah tombol ditekan dan video packing umumnya pendek (encode ~13 detik
    // per menit video), jadi menit pertama dipantau rapat; setelah itu
    // dilonggarkan karena yang tersisa pasti video panjang atau antrian.
    var JEDA_PANTAU_MP4_AWAL_MS  = 3000;
    var JEDA_PANTAU_MP4_LAMA_MS  = 10000;
    var BATAS_PANTAU_RAPAT_MS    = 60000;
    var pemantauMp4 = null;
    var pemantauMulai = 0;
    var noresiAktif = '';
    var xhrCari = null;
    var KUNCI_RIWAYAT = 'vp_riwayat';
    var MAKS_RIWAYAT  = 6;

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
    // tidak diterima WhatsApp, jadi CS bisa minta versi MP4 -- dibuat oleh
    // worker di server, bukan di dalam request tombol, karena konversinya lama.
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
      if (!aktif) {
        pemantauMulai = 0;
        return;
      }

      if (!pemantauMulai) {
        pemantauMulai = Date.now();
      }
      var jeda = (Date.now() - pemantauMulai) < BATAS_PANTAU_RAPAT_MS
        ? JEDA_PANTAU_MP4_AWAL_MS
        : JEDA_PANTAU_MP4_LAMA_MS;

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
      }, jeda);
    }

    // Riwayat resi terakhir: hanya kemudahan per browser, jadi gagal diam-diam
    // kalau localStorage diblokir.
    function bacaRiwayat() {
      try {
        var a = JSON.parse(localStorage.getItem(KUNCI_RIWAYAT) || '[]');
        return $.isArray(a) ? a : [];
      } catch (e) { return []; }
    }

    function simpanRiwayat(noresi) {
      try {
        var a = bacaRiwayat().filter(function (x) { return x !== noresi; });
        a.unshift(noresi);
        localStorage.setItem(KUNCI_RIWAYAT, JSON.stringify(a.slice(0, MAKS_RIWAYAT)));
      } catch (e) {}
      gambarRiwayat();
    }

    function gambarRiwayat() {
      var a = bacaRiwayat();
      var box = $('#vp-riwayat');
      if (!a.length) { box.hide().empty(); return; }
      var html = '<small class="text-muted">Terakhir dicari:</small> ';
      a.forEach(function (r) {
        html += '<a href="#" class="label label-default vp-riwayat-item" data-resi="' + escapeHtml(r) +
          '" style="margin-right: 4px; font-size: 90%;">' + escapeHtml(r) + '</a>';
      });
      html += ' <a href="#" id="vp-riwayat-hapus" class="text-muted"><small>hapus</small></a>';
      box.html(html).show();
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
              '<video src="' + escapeHtml(v.url) + '" controls playsinline ' +
                'preload="' + (i === 0 ? 'metadata' : 'none') + '" ' +
                'style="width: 100%; max-width: 720px; background: #000;"></video>' +
              '<div class="vp-galat text-danger" style="display: none; margin-top: 6px;"></div>' +
              '<table class="table table-condensed" style="margin: 10px 0 0; max-width: 720px;"><tbody>' +
                '<tr><td class="text-muted" style="width: 120px; border-top: 0;">Packer</td><td style="border-top: 0;"><strong>' +
                  escapeHtml(v.nama_packer) + '</strong> (' + escapeHtml(v.nama_komputer) + ')</td></tr>' +
                '<tr><td class="text-muted">Durasi / ukuran</td><td>' + formatDurasi(v.durasi_detik) +
                  ' &middot; ' + formatUkuran(v.ukuran_byte) + '</td></tr>' +
                '<tr><td class="text-muted">Berkas</td><td><code>' + escapeHtml(v.nama_file || '-') + '</code></td></tr>' +
              '</tbody></table>' +
              '<div style="margin-top: 8px;">' +
                '<a href="' + escapeHtml(v.url) + '?unduh=1" class="btn btn-default btn-sm">' +
                  '<i class="fa fa-download"></i> Unduh WebM</a> ' +
                '<span style="display: inline-block; margin-left: 6px;">' + gambarMp4(v) + '</span>' +
              '</div>' +
            '</div>' +
          '</div>';
      });

      $('#vp-hasil').html(html);

      $('#vp-hasil video').each(function (i) {
        var el = this;
        var galat = $(el).siblings('.vp-galat');
        el.addEventListener('error', function () {
          galat.text('Video gagal dimuat. Coba cari ulang, atau pakai Unduh WebM / Siapkan MP4.').show();
        });
        // Scan + Enter adalah interaksi pengguna, jadi autoplay diizinkan browser.
        if (i === 0 && list[0].status !== 'MEREKAM') {
          var p = el.play();
          if (p && p.catch) { p.catch(function () {}); }
        }
      });

      var adaAntrian = list.some(function (v) {
        return v.mp4 && (v.mp4.status === 'ANTRI' || v.mp4.status === 'PROSES');
      });
      aturPemantauMp4(adaAntrian);
    }

    function cari(noresiLangsung) {
      var noresi = $.trim(typeof noresiLangsung === 'string' ? noresiLangsung : $('#vp-noresi').val());

      if (noresi === '') {
        pesan('warning', 'Nomor resi tidak boleh kosong.');
        return;
      }
      $('#vp-noresi').val(noresi);

      // Scan beruntun: hasil pencarian lama tidak boleh menimpa yang baru.
      if (xhrCari) { xhrCari.abort(); }

      var tombol = $('#vp-cari').prop('disabled', true);
      pesan('info', 'Mencari video untuk resi ' + noresi + '...');

      xhrCari = $.ajax({
        url: urlCari,
        method: 'POST',
        data: { noresi: noresi },
        dataType: 'json',
        success: function (res) {
          if (res && res.status === 401) {
            pesan('danger', 'Sesi login habis. Muat ulang halaman lalu login kembali.');
            return;
          }
          if (!res || res.code !== 200) {
            pesan('warning', res && res.message ? res.message : 'Video tidak ditemukan.');
            noresiAktif = '';
            aturPemantauMp4(false);
            return;
          }
          noresiAktif = noresi;
          simpanRiwayat(noresi);
          gambarHasil(noresi, res.data);
        },
        error: function (xhr, status) {
          if (status === 'abort') return;
          pesan('danger', 'Gagal menghubungi server. Periksa koneksi lalu cari lagi.');
        },
        complete: function () {
          xhrCari = null;
          tombol.prop('disabled', false);
        }
      });

      // Siapkan input untuk scan berikutnya.
      $('#vp-noresi').select();
    }

    $('#vp-cari').on('click', function () { cari(); });

    $('#vp-riwayat').on('click', '.vp-riwayat-item', function (e) {
      e.preventDefault();
      cari(String($(this).data('resi')));
    });

    $('#vp-riwayat').on('click', '#vp-riwayat-hapus', function (e) {
      e.preventDefault();
      try { localStorage.removeItem(KUNCI_RIWAYAT); } catch (err) {}
      gambarRiwayat();
    });

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
      } else if (e.which === 27) {
        $(this).val('');
      }
    });

    gambarRiwayat();
    $('#vp-noresi').focus();
  })();
</script>
