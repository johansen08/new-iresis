<link rel="stylesheet" href="assets/css/terima_retur_ho.css?v=<?= @filemtime(FCPATH . 'assets/css/terima_retur_ho.css') ?: '1' ?>">
<?php // HO > Terima Retur. Aturan: docs/TERIMA_RETUR_HO.md ?>
<div class="trh" id="trh_root">

  <!-- 1. Pilih jenis retur (tanpa default: wajib dipilih) -->
  <div class="tr-card">
    <h2>1 · Pilih jenis retur</h2>
    <div class="tr-b">
      <div class="tr-seg" role="radiogroup" aria-label="Jenis retur">
        <button type="button" class="tr-opt" data-jenis="ekspedisi" role="radio" aria-checked="false">
          <span class="t">Retur Ekspedisi</span><span class="s">Gagal kirim, paket dikembalikan kurir</span>
          <span class="k"><kbd>Alt</kbd>+<kbd>1</kbd></span>
        </button>
        <button type="button" class="tr-opt" data-jenis="komplain" role="radio" aria-checked="false">
          <span class="t">Retur Komplain</span><span class="s">Pengembalian barang oleh pembeli</span>
          <span class="k"><kbd>Alt</kbd>+<kbd>2</kbd></span>
        </button>
      </div>
    </div>
  </div>

  <!-- 2. Scan resi + 5 scan terbaru -->
  <div class="tr-card" id="trh_scancard">
    <h2>2 · Scan resi<span class="tr-now" id="trh_now" style="color:#b91c1c">belum pilih jenis</span></h2>
    <div class="tr-b">
      <input type="text" class="form-control tr-scan" id="trh_resi" placeholder="Scan resi di sini…" autocomplete="off" spellcheck="false">

      <div class="tr-res" id="trh_res" aria-live="polite">
        <div class="h"><div class="tr-dot" id="trh_dot">·</div>
          <div><div class="tr-tt" id="trh_tt">Siap scan</div><div class="tr-rn" id="trh_rn">Pilih jenis retur, lalu scan resi</div></div></div>
        <p class="tr-msg" id="trh_msg"></p>
        <div class="tr-meta" id="trh_meta"></div>
      </div>

      <div class="tr-hlabel">5 SCAN TERBARU <span>(sementara, hilang saat halaman ditutup)</span></div>
      <table id="trh_hist"></table>
    </div>
  </div>

  <!-- 3. Hapus resi salah scan -->
  <div class="tr-card">
    <h2>Hapus resi salah scan</h2>
    <div class="tr-b" id="trh_hapus_box">
      <p class="tr-msg" style="margin-top:0">Paket reguler/NDD yang terlanjur terscan sebagai retur? Scan resinya di sini untuk menghapusnya dari data retur. Hanya resi yang belum dibuka (Buka Retur) yang bisa dihapus.</p>
      <input type="text" class="form-control" id="trh_hapus_resi" placeholder="Scan resi yang akan dihapus…" autocomplete="off" spellcheck="false">
      <div class="tr-res" id="trh_hapus_res" style="display:none">
        <div class="h"><div class="tr-dot" id="trh_hapus_dot">·</div>
          <div><div class="tr-tt" id="trh_hapus_tt"></div><div class="tr-rn" id="trh_hapus_rn"></div></div></div>
        <p class="tr-msg" id="trh_hapus_msg"></p>
        <div class="tr-meta" id="trh_hapus_meta"></div>
        <div id="trh_hapus_aksi" style="margin-top:8px;display:none">
          <button type="button" class="btn btn-danger" id="trh_hapus_ya">Hapus dari data retur</button>
          <button type="button" class="btn btn-default" id="trh_hapus_batal">Batal</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
(function () {
    var URL_SCAN = '<?= base_url('terima-retur-ho/scan') ?>';
    var JENIS = {
        ekspedisi: {label: 'Retur Ekspedisi', warna: '#2563eb'},
        komplain:  {label: 'Retur Komplain',  warna: '#c2410c'}
    };
    // Kode hasil -> suara. Semua berkas bawaan proyek (main.php), dipakai juga di menu picker/packer.
    var SUARA = {
        ok: 'audio-alexis',
        salah_menu: 'audio-wrong',
        'double': 'audio-sudah-scan',
        cancel: 'audio-cancel-order',
        tidak_ditemukan: 'audio-tidak-ditemukan',
        salah_jenis: 'audio-wrong',
        jenis_belum_dipilih: 'audio-wrong',
        gagal: 'audio-wrong',
        ditolak: 'audio-wrong'
    };
    var JUDUL = {
        ok: 'DITERIMA',
        salah_menu: 'SALAH MENU SCAN',
        'double': 'DOUBLE — SUDAH DISCAN',
        cancel: 'PAKET CANCEL',
        tidak_ditemukan: 'RESI TIDAK DITEMUKAN',
        salah_jenis: 'SALAH JENIS RETUR',
        jenis_belum_dipilih: 'PILIH JENIS RETUR DULU',
        gagal: 'GAGAL MENYIMPAN',
        ditolak: 'TIDAK ADA AKSES'
    };
    var LABEL_HASIL = {
        ok: 'Diterima', salah_menu: 'Salah menu', 'double': 'Double', cancel: 'Cancel', tidak_ditemukan: 'Tidak ditemukan',
        salah_jenis: 'Salah jenis', jenis_belum_dipilih: 'Jenis belum dipilih', gagal: 'Gagal', ditolak: 'Ditolak'
    };

    var $root = $('#trh_root');
    var $resi = $('#trh_resi');
    var jenis = null;          // sengaja tanpa default: petugas wajib memilih
    var riwayat = [];          // maks. 5, terbaru di depan
    var antrian = [];
    var sibuk = false;
    var seq = 0;

    function suara(id) {
        if (typeof suaraScan === 'function') { suaraScan(id); return; }
        var el = document.getElementById(id);
        if (el) { try { el.currentTime = 0; } catch (e) {} el.play(); }
    }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function hidup() { return document.getElementById('trh_resi') !== null; }   // false setelah pindah menu (SPA)

    function pilihJenis(j) {
        jenis = j;
        $root.find('.tr-opt').each(function () {
            var on = $(this).data('jenis') === j;
            $(this).toggleClass('on', on).attr('aria-checked', on);
        });
        $('#trh_now').text(JENIS[j].label).css('color', JENIS[j].warna);
        $('#trh_scancard').css('--accent', JENIS[j].warna);
        // Ganti jenis = mulai batch baru: banner hasil sebelumnya dikosongkan supaya tidak tertukar.
        $('#trh_res').attr('class', 'tr-res');
        $('#trh_dot').text('·');
        $('#trh_tt').text('Siap scan');
        $('#trh_rn').text(JENIS[j].label + ' — scan resi sekarang');
        $('#trh_msg').text('');
        $('#trh_meta').html('');
        $resi.focus();
    }

    function tampilBanner(r) {
        var kode = r.kode;
        var cls = kode === 'ok' ? 'ok' : (kode === 'double' ? 'dbl' : 'err');
        $('#trh_res').attr('class', 'tr-res ' + cls);
        $('#trh_dot').text(cls === 'ok' ? '✓' : (cls === 'dbl' ? '!' : '✕'));
        $('#trh_tt').text(JUDUL[kode] || 'GAGAL');
        $('#trh_rn').text(r.noresi || r.input || '—');
        $('#trh_msg').text(r.message || '');
        var meta = '';
        if (r.resi_balik) meta += '<span class="tr-pill">Resi balik <b>' + esc(r.resi_balik) + '</b></span>';
        if (r.kurir) meta += '<span class="tr-pill">Kurir <b>' + esc(r.kurir) + '</b></span>';
        if (r.marketplace) meta += '<span class="tr-pill">Marketplace <b>' + esc(r.marketplace) + '</b></span>';
        if (r.status_pesanan) meta += '<span class="tr-pill">Status pesanan <b>' + esc(r.status_pesanan) + '</b></span>';
        $('#trh_meta').html(meta);
    }

    function gambarRiwayat() {
        if (!riwayat.length) {
            $('#trh_hist').html('<tr><td class="tr-empty">Belum ada scan.</td></tr>');
            return;
        }
        var h = '<tr><th>Waktu</th><th>Resi</th><th>Jenis</th><th>Hasil</th></tr>';
        $.each(riwayat, function (i, x) {
            var kls = !x.kode ? 'wait' : (x.kode === 'ok' ? 'ok' : (x.kode === 'double' ? 'dbl' : 'err'));
            var lbl = !x.kode ? 'Memproses…' : (LABEL_HASIL[x.kode] || 'Gagal');
            h += '<tr><td class="mono">' + esc(x.waktu) + '</td><td class="mono">' + esc(x.input) + '</td>' +
                 '<td>' + (x.jenis ? '<span class="tr-badge ' + x.jenis + '">' + esc(JENIS[x.jenis].label) + '</span>' : '—') + '</td>' +
                 '<td><span class="tr-badge ' + kls + '">' + lbl + '</span></td></tr>';
        });
        $('#trh_hist').html(h);
    }

    function jam() {
        var d = new Date(), p = function (n) { return (n < 10 ? '0' : '') + n; };
        return p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
    }

    function selesai(item, r) {
        item.kode = r.kode;
        if (hidup()) {
            r.input = item.input;
            tampilBanner(r);
            gambarRiwayat();
        }
        suara(SUARA[r.kode] || 'audio-wrong');
        if (r.kode === 'salah_menu') {   // alert eksplisit: paket PROCESSING bukan retur
            setTimeout(function () { alert('SALAH MENU SCAN\n\nResi ' + (r.noresi || item.input) + ' masih berstatus PROCESSING (belum dikirim), bukan retur.'); fokus(); }, 50);
        }
    }

    // ---- hapus resi salah scan ----
    var URL_HAPUS = '<?= base_url('terima-retur-ho/hapus') ?>';
    var hapusResi = null;      // resi yang menunggu konfirmasi
    function hapusBanner(cls, dot, judul, rn, msg, meta, aksi) {
        $('#trh_hapus_res').attr('class', 'tr-res ' + cls).show();
        $('#trh_hapus_dot').text(dot);
        $('#trh_hapus_tt').text(judul);
        $('#trh_hapus_rn').text(rn || '—');
        $('#trh_hapus_msg').text(msg || '');
        $('#trh_hapus_meta').html(meta || '');
        $('#trh_hapus_aksi').toggle(!!aksi);
    }
    function hapusKirim(resi, konfirmasi) {
        hapusBanner('wait', '…', 'MEMPROSES', resi, '', '', false);
        $.ajax({
            url: URL_HAPUS, type: 'post', dataType: 'json', cache: false, timeout: 15000,
            data: {resi: resi, konfirmasi: konfirmasi ? '1' : '0'},
            success: function (res) {
                var d = (res && res.data) ? res.data : {};
                var msg = d.message || (res && res.message) || 'Respons server tidak dikenal.';
                if (!hidup()) return;
                if (d.kode === 'konfirmasi') {
                    hapusResi = resi;
                    var meta = '';
                    $.each(d.baris || [], function (i, b) { meta += '<span class="tr-pill">' + esc(b.jenis) + ' <b>' + esc(b.tanggal) + '</b></span>'; });
                    if (d.resi_balik) meta = '<span class="tr-pill">Resi balik <b>' + esc(d.resi_balik) + '</b></span>' + meta;
                    hapusBanner('dbl', '?', 'HAPUS RESI INI?', d.noresi || resi, 'Baris ini akan dihapus dari data retur. Salinannya disimpan di log salah scan.', meta, true);
                    suara('audio-sudah-scan');
                } else if (d.kode === 'ok') {
                    hapusResi = null;
                    hapusBanner('ok', '✓', 'DIHAPUS', d.noresi || resi, msg, '', false);
                    suara('audio-alexis');
                    $('#trh_hapus_resi').val('').focus();
                } else {
                    hapusResi = null;
                    hapusBanner('err', '✕', 'TIDAK DIHAPUS', d.noresi || resi, msg, '', false);
                    suara(d.kode === 'tidak_ditemukan' ? 'audio-tidak-ditemukan' : 'audio-wrong');
                }
            },
            error: function () {
                hapusResi = null;
                if (hidup()) hapusBanner('err', '✕', 'GAGAL', resi, 'Terjadi kesalahan jaringan/server. Coba lagi.', '', false);
            }
        });
    }
    $('#trh_hapus_resi').on('keydown', function (e) {
        if (e.which !== 13) return;
        e.preventDefault();
        var v = $.trim(this.value);
        if (!v) return;
        hapusKirim(v, false);
    });
    $('#trh_hapus_ya').on('click', function () { if (hapusResi) { var r = hapusResi; hapusResi = null; hapusKirim(r, true); } });
    $('#trh_hapus_batal').on('click', function () {
        hapusResi = null;
        $('#trh_hapus_res').hide();
        $('#trh_hapus_resi').val('').focus();
    });

    // Antrian berurutan: scan beruntun tidak saling tumpang tindih di server, kolom tetap langsung kosong.
    function proses() {
        if (sibuk || !antrian.length) return;
        sibuk = true;
        var item = antrian.shift();
        $.ajax({
            url: URL_SCAN, type: 'post', dataType: 'json', cache: false, timeout: 15000,
            data: {resi: item.input, jenis: item.jenis},
            success: function (res) {
                var d = (res && res.data) ? res.data : {};
                if (!d.kode) { d = {kode: 'gagal', message: (res && res.message) || 'Respons server tidak dikenal.'}; }
                if (!d.message) { d.message = (res && res.message) || ''; }
                selesai(item, d);
            },
            error: function (xhr, status) {
                selesai(item, {kode: 'gagal', noresi: item.input,
                    message: status === 'timeout' ? 'Server tidak merespons. Scan ulang resi ini.' : 'Terjadi kesalahan jaringan/server. Scan ulang resi ini.'});
            },
            complete: function () { sibuk = false; proses(); }
        });
    }

    function scan(nilai) {
        nilai = $.trim(nilai);
        if (!nilai) return;
        var item = {id: ++seq, input: nilai, jenis: jenis, waktu: jam(), kode: null};
        riwayat.unshift(item);
        if (riwayat.length > 5) riwayat.length = 5;

        if (!jenis) {   // tanpa jenis: ditolak di layar, tidak dikirim ke server
            selesai(item, {kode: 'jenis_belum_dipilih', noresi: nilai,
                message: 'Pilih dulu jenis retur (Ekspedisi atau Komplain), lalu scan ulang.'});
            return;
        }
        $('#trh_res').attr('class', 'tr-res wait');
        $('#trh_dot').text('…');
        $('#trh_tt').text('MEMPROSES');
        $('#trh_rn').text(nilai);
        $('#trh_msg').text('');
        $('#trh_meta').html('');
        gambarRiwayat();
        antrian.push(item);
        proses();
    }

    // ---- event ----
    $resi.on('keydown', function (e) {
        if (e.which === 13) {            // Enter dari alat scan
            e.preventDefault();
            var v = this.value;
            this.value = '';             // langsung bersih, siap ditimpa resi berikutnya
            scan(v);
        }
    });
    $root.on('click', '.tr-opt', function () { pilihJenis($(this).data('jenis')); });

    // Kunci kursor di kolom scan. Handler dipasang di document dengan namespace dan dilepas
    // otomatis begitu halaman diganti menu lain.
    function fokus() {
        setTimeout(function () {
            if (!hidup()) return;
            var a = document.activeElement;   // jangan rebut fokus dari kolom/tombol hapus
            if (a && $(a).closest('#trh_hapus_box').length) return;
            $resi.focus();
        }, 0);
    }
    $resi.on('blur', fokus);
    $(document).off('click.trh keydown.trh').on('click.trh', function () {
        if (!hidup()) { $(document).off('.trh'); return; }
        fokus();
    }).on('keydown.trh', function (e) {
        if (!hidup()) { $(document).off('.trh'); return; }
        if (e.altKey && e.key === '1') { e.preventDefault(); pilihJenis('ekspedisi'); }
        else if (e.altKey && e.key === '2') { e.preventDefault(); pilihJenis('komplain'); }
    });

    gambarRiwayat();
    $resi.focus();
})();
</script>
