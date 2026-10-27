<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">

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
              <span class="input-group-addon" id="vp-status-cari"><i class="fa fa-search"></i></span>
              <a href="#" class="input-group-addon" id="vp-info-tombol" title="Cara pakai">
                <i class="fa fa-info-circle"></i>
              </a>
            </div>
            <div id="vp-info" class="well well-sm" style="display: none; margin: 8px 0 0;">
              <strong>Cara pakai:</strong> scan atau tempel (<kbd>Ctrl</kbd>+<kbd>V</kbd>) nomor resi.
              Pencarian jalan otomatis dan video langsung diputar. Scan berikutnya otomatis
              menggantikan resi sebelumnya. Tekan <kbd>Esc</kbd> untuk mengosongkan kolom.
            </div>
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
          '<p class="text-warning"><strong>Terbagi ' + list.length + ' bagian</strong> ' +
            '<a href="#" class="vp-info-bagian text-muted" title="Penjelasan"><i class="fa fa-info-circle"></i></a></p>' +
          '<div class="well well-sm vp-info-bagian-isi" style="display: none;">' +
            'Rekaman sempat terputus di tengah packing (biasanya tab packer mati). Tonton berurutan ' +
            'dari Bagian 1; ada jeda beberapa detik antar bagian, yaitu saat rekamannya mati.' +
          '</div>';
      }

      list.forEach(function (v, i) {
        var judul = list.length > 1
          ? 'Bagian ' + (v.bagian || (i + 1)) + ' dari ' + list.length
          : '';

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
                  '<i class="fa fa-download"></i> Unduh WebM</a>' +
              '</div>' +
            '</div>' +
          '</div>';
      });

      $('#vp-hasil').html(html);

      $('#vp-hasil video').each(function (i) {
        var el = this;
        var galat = $(el).siblings('.vp-galat');
        el.addEventListener('error', function () {
          galat.text('Video gagal dimuat. Coba cari ulang, atau pakai tombol Unduh WebM.').show();
        });
        // Scan + Enter adalah interaksi pengguna, jadi autoplay diizinkan browser.
        if (i === 0 && list[0].status !== 'MEREKAM') {
          var p = el.play();
          if (p && p.catch) { p.catch(function () {}); }
        }
      });
    }

    // Nomor resi tidak pernah memuat spasi: buang semua whitespace (spasi, tab,
    // CR/LF sisa scanner, NBSP, zero-width) bukan hanya di ujung.
    function bersihkanResi(teks) {
      return String(teks == null ? '' : teks).replace(/[\s ​-‍﻿]+/g, '');
    }

    var pencarianTertunda = null;
    // Setelah pencarian, isi kolom dianggap "bekas": karakter berikutnya (scan
    // atau ketik) menghapusnya dulu, jadi resi baru tidak ditempel di belakang lama.
    var siapGanti = false;

    function cari(noresiLangsung) {
      if (pencarianTertunda) { clearTimeout(pencarianTertunda); pencarianTertunda = null; }

      var noresi = bersihkanResi(typeof noresiLangsung === 'string' ? noresiLangsung : $('#vp-noresi').val());

      if (noresi === '') {
        $('#vp-noresi').val('');
        return;
      }
      $('#vp-noresi').val(noresi);
      siapGanti = true;

      // Scan beruntun: hasil pencarian lama tidak boleh menimpa yang baru.
      if (xhrCari) { xhrCari.abort(); }

      var ikon = $('#vp-status-cari i').attr('class', 'fa fa-spinner fa-spin');
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
          ikon.attr('class', 'fa fa-search');
        }
      });

      $('#vp-noresi').focus().select();
    }


    $('#vp-info-tombol').on('click', function (e) {
      e.preventDefault();
      $('#vp-info').slideToggle(120);
    });

    $('#vp-hasil').on('click', '.vp-info-bagian', function (e) {
      e.preventDefault();
      $('#vp-hasil .vp-info-bagian-isi').slideToggle(120);
    });

    $('#vp-riwayat').on('click', '.vp-riwayat-item', function (e) {
      e.preventDefault();
      cari(String($(this).data('resi')));
    });

    $('#vp-riwayat').on('click', '#vp-riwayat-hapus', function (e) {
      e.preventDefault();
      try { localStorage.removeItem(KUNCI_RIWAYAT); } catch (err) {}
      gambarRiwayat();
    });

    // Scanner mengetik cepat lalu (biasanya) Enter. Karakter pertama setelah
    // pencarian menghapus isi lama dulu, jadi resi tidak pernah menumpuk.
    $('#vp-noresi').on('keydown', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        cari();
      } else if (e.which === 27) {
        $(this).val('');
        siapGanti = false;
      } else if (siapGanti && e.key && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
        $(this).val('');
        siapGanti = false;
      }
    });

    // Tempel: gantikan seluruh isi (bukan menyisipkan di tengah), bersihkan, cari.
    $('#vp-noresi').on('paste', function (e) {
      var asli = e.originalEvent || e;
      var teks = asli.clipboardData ? asli.clipboardData.getData('text') : (window.clipboardData ? window.clipboardData.getData('Text') : '');
      e.preventDefault();
      cari(teks);
    });

    // Scanner tanpa suffix Enter, atau isian lain: cari otomatis begitu input
    // berhenti sebentar. Batas panjang mencegah mencari di tengah ketikan manual.
    $('#vp-noresi').on('input', function () {
      if (pencarianTertunda) { clearTimeout(pencarianTertunda); }
      var bersih = bersihkanResi($(this).val());
      if (bersih.length < 8) { return; }
      pencarianTertunda = setTimeout(function () { cari(); }, 350);
    });

    // Klik / fokus ke kolom memilih seluruh isi: ketikan berikutnya menggantikannya.
    $('#vp-noresi').on('focus click', function () { this.select(); });

    gambarRiwayat();
    $('#vp-noresi').focus();
  })();
</script>
