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

    function tglIndo(s) {
      var p = String(s || '').split('-');
      return p.length === 3 ? p[2] + '-' + p[1] + '-' + p[0] : (s || '-');
    }

    // Judul kecil huruf kapital di atas tiap kelompok informasi: membuat urutan
    // baca jelas (resi -> isi resi -> rekaman) tanpa menambah garis dan kotak.
    function judulBagian(teks) {
      return '<div class="text-muted" style="font-size: 11px; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 4px;">' +
        escapeHtml(teks) + '</div>';
    }

    // Satu baris label/nilai pada kartu info packing.
    function barisInfo(label, nilaiHtml, pertama) {
      return '<tr>' +
        '<td class="text-muted" style="width: 38%; white-space: nowrap;' + (pertama ? ' border-top: 0;' : '') + '">' + escapeHtml(label) + '</td>' +
        '<td style="' + (pertama ? 'border-top: 0;' : '') + '">' + nilaiHtml + '</td>' +
      '</tr>';
    }

    // Isi resi: apa yang SEHARUSNYA ada di paket. Dipisah dari info rekaman supaya
    // CS bisa mencocokkan isi video dengan daftar ini sambil menonton.
    function gambarIsiResi(noresi, resi) {
      var html = '<div class="panel panel-default"><div class="panel-body">';

      html += '<div class="row"><div class="col-sm-8">' +
          judulBagian('No resi') +
          '<div style="font-size: 22px; font-weight: 600; line-height: 1.2; word-break: break-all;">' + escapeHtml(noresi) + '</div>';
      if (resi) {
        html += '<div class="text-muted" style="margin-top: 4px;">' + escapeHtml(resi.toko || '-') +
          (resi.status_pesanan ? ' &middot; <span class="label label-default">' + escapeHtml(resi.status_pesanan) + '</span>' : '') +
          '</div>';
      }
      html += '</div>';

      if (resi) {
        html += '<div class="col-sm-4 text-right">' +
          judulBagian('Total isi paket') +
          '<div style="font-size: 22px; font-weight: 600; line-height: 1.2;">' + resi.total_qty + ' pcs</div>' +
          '<div class="text-muted">' + resi.total_sku + ' SKU</div>' +
        '</div>';
      }
      html += '</div>';

      if (!resi || !resi.items || !resi.items.length) {
        html += '<p class="text-muted" style="margin: 12px 0 0;">Daftar SKU untuk resi ini tidak ditemukan.</p>';
        return html + '</div></div>';
      }

      html += '<div style="margin-top: 14px;">' + judulBagian('SKU yang harus ada di resi ini') + '</div>' +
        '<div class="table-responsive"><table class="table table-condensed" style="margin-bottom: 0;">' +
        '<thead><tr>' +
          '<th style="width: 52px;"></th><th>SKU</th><th style="width: 110px;">Rak</th><th class="text-right" style="width: 80px;">Qty</th>' +
        '</tr></thead><tbody>';

      resi.items.forEach(function (it) {
        var foto = it.foto
          ? '<img src="' + escapeHtml(it.foto) + '"' + (it.foto_lokal ? ' data-foto-lokal="' + escapeHtml(it.foto_lokal) + '"' : '') +
            ' alt="" class="vp-foto" style="width: 44px; height: 44px; object-fit: cover; border-radius: 3px; background: #eee;">'
          : '<div style="width: 44px; height: 44px; border-radius: 3px; background: #eee;"></div>';
        var qty = it.qty > 1
          ? '<span class="label label-warning" style="font-size: 15px;">' + it.qty + '</span>'
          : '<span style="font-size: 15px; font-weight: 600;">' + it.qty + '</span>';
        var detail = [it.nama, it.variasi].filter(Boolean).join(' &middot; ');

        html += '<tr>' +
          '<td style="vertical-align: middle;">' + foto + '</td>' +
          '<td style="vertical-align: middle;"><strong>' + escapeHtml(it.sku) + '</strong>' +
            (it.kurang > 0 ? ' <span class="label label-danger">kurang ' + it.kurang + '</span>' : '') +
            (detail ? '<div style="font-size: 12px; color: #444;">' + (it.nama ? escapeHtml(it.nama) : '') +
              (it.variasi ? (it.nama ? ' &middot; ' : '') + '<strong>' + escapeHtml(it.variasi) + '</strong>' : '') + '</div>' : '') +
          '</td>' +
          '<td style="vertical-align: middle;">' + escapeHtml(it.rak || '-') + '</td>' +
          '<td class="text-right" style="vertical-align: middle;">' + qty + '</td>' +
        '</tr>';
      });

      html += '</tbody></table></div>';
      return html + '</div></div>';
    }

    // Satu rekaman: video di kiri (bagian yang paling dicari), info packing di kanan.
    function gambarRekaman(v, i, total) {
      var p = v.packer || { nama: v.nama_packer || '-', jabatan: '', absen: '' };
      var judul = total > 1 ? 'Bagian ' + (v.bagian || (i + 1)) + ' dari ' + total : 'Rekaman packing';
      var selesai = v.jam_selesai
        ? escapeHtml(v.jam_selesai)
        : '<span class="text-muted">' + (v.status === 'MEREKAM' ? 'masih merekam' : 'tidak tercatat') + '</span>';

      return '<div class="panel panel-default">' +
        '<div class="panel-heading">' +
          '<strong>' + escapeHtml(judul) + '</strong>' + labelStatus(v.status) +
        '</div>' +
        '<div class="panel-body"><div class="row">' +
          '<div class="col-md-7">' +
            '<video src="' + escapeHtml(v.url) + '" controls playsinline ' +
              'preload="' + (i === 0 ? 'metadata' : 'none') + '" ' +
              'style="width: 100%; background: #000;"></video>' +
            '<div class="vp-galat text-danger" style="display: none; margin-top: 6px;"></div>' +
            '<div style="margin-top: 8px;">' +
              '<a href="' + escapeHtml(v.url) + '?unduh=1" class="btn btn-default btn-sm">' +
                '<i class="fa fa-download"></i> Unduh WebM</a>' +
            '</div>' +
          '</div>' +
          '<div class="col-md-5">' +
            judulBagian('Info packing') +
            '<table class="table table-condensed" style="margin-bottom: 0;"><tbody>' +
              barisInfo('Packer', '<strong>' + escapeHtml(p.nama) + '</strong>' +
                (p.jabatan ? ' <span class="label label-default">' + escapeHtml(p.jabatan) + '</span>' : ''), true) +
              barisInfo('No absen', p.absen ? '<strong>' + escapeHtml(p.absen) + '</strong>' : '<span class="text-muted">-</span>') +
              barisInfo('Komputer', escapeHtml(v.nama_komputer || '-')) +
              barisInfo('Tanggal', escapeHtml(tglIndo(v.tanggal))) +
              barisInfo('Jam', '<strong>' + escapeHtml(v.jam_mulai || '-') + '</strong> &ndash; <strong>' + selesai + '</strong>') +
              barisInfo('Durasi', escapeHtml(formatDurasi(v.durasi_detik))) +
              barisInfo('Berkas', '<small class="text-muted">' + escapeHtml(v.nama_file || '-') + ' &middot; ' + formatUkuran(v.ukuran_byte) + '</small>') +
            '</tbody></table>' +
          '</div>' +
        '</div></div>' +
      '</div>';
    }

    function gambarHasil(noresi, list, resi) {
      var html = gambarIsiResi(noresi, resi);

      // Lebih dari satu bagian berarti rekamannya sempat terputus di tengah
      // packing -- biasanya tab packer mati. Bagiannya harus ditonton berurutan.
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
        html += gambarRekaman(v, i, list.length);
      });

      $('#vp-hasil').html(html);

      // Foto produk: URL asli dulu, salinan lokal bila gagal dimuat.
      $('#vp-hasil img.vp-foto').each(function () {
        var img = this;
        img.addEventListener('error', function () {
          var lokal = img.getAttribute('data-foto-lokal');
          if (lokal && img.src.indexOf(lokal) === -1) { img.src = lokal; }
        });
      });

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
          gambarHasil(noresi, res.data, res.resi);
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
