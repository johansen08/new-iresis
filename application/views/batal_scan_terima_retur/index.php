<link rel="stylesheet" href="assets/css/terima_retur_ho.css?v=<?= @filemtime(FCPATH . 'assets/css/terima_retur_ho.css') ?: '1' ?>">
<?php // HO > Batal Scan Terima Retur. Aturan: docs/TERIMA_RETUR_HO.md (bagian Batal Scan) ?>
<div class="trh" id="bsr_root">
  <div class="tr-card">
    <h2>Batal scan terima retur</h2>
    <div class="tr-b">
      <p class="tr-msg" style="margin-top:0">Paket reguler/NDD yang terlanjur terscan di menu Terima Retur? Scan resinya di sini untuk menghapusnya dari data retur. Hanya resi yang belum dibuka (Buka Retur) yang bisa dihapus.</p>
      <input type="text" class="form-control tr-scan" id="bsr_resi" placeholder="Scan resi yang akan dihapus…" autocomplete="off" spellcheck="false">
      <div class="tr-res" id="bsr_res" style="display:none">
        <div class="h"><div class="tr-dot" id="bsr_dot">·</div>
          <div><div class="tr-tt" id="bsr_tt"></div><div class="tr-rn" id="bsr_rn"></div></div></div>
        <p class="tr-msg" id="bsr_msg"></p>
        <div class="tr-meta" id="bsr_meta"></div>
        <div id="bsr_aksi" style="margin-top:8px;display:none">
          <button type="button" class="btn btn-danger" id="bsr_ya">Hapus dari data retur</button>
          <button type="button" class="btn btn-default" id="bsr_batal">Batal</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
(function () {
    var URL_HAPUS = '<?= base_url('batal-scan-terima-retur/hapus') ?>';
    var $resi = $('#bsr_resi');
    var menunggu = null;      // resi yang menunggu konfirmasi

    function suara(id) {
        if (typeof suaraScan === 'function') { suaraScan(id); return; }
        var el = document.getElementById(id);
        if (el) { try { el.currentTime = 0; } catch (e) {} el.play(); }
    }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function hidup() { return document.getElementById('bsr_resi') !== null; }   // false setelah pindah menu (SPA)

    function banner(cls, dot, judul, rn, msg, meta, aksi) {
        $('#bsr_res').attr('class', 'tr-res ' + cls).show();
        $('#bsr_dot').text(dot);
        $('#bsr_tt').text(judul);
        $('#bsr_rn').text(rn || '—');
        $('#bsr_msg').text(msg || '');
        $('#bsr_meta').html(meta || '');
        $('#bsr_aksi').toggle(!!aksi);
    }

    function kirim(resi, konfirmasi) {
        banner('wait', '…', 'MEMPROSES', resi, '', '', false);
        $.ajax({
            url: URL_HAPUS, type: 'post', dataType: 'json', cache: false, timeout: 15000,
            data: {resi: resi, konfirmasi: konfirmasi ? '1' : '0'},
            success: function (res) {
                var d = (res && res.data) ? res.data : {};
                var msg = d.message || (res && res.message) || 'Respons server tidak dikenal.';
                if (!hidup()) return;
                if (d.kode === 'konfirmasi') {
                    menunggu = resi;
                    var meta = '';
                    $.each(d.baris || [], function (i, b) { meta += '<span class="tr-pill">' + esc(b.jenis) + ' <b>' + esc(b.tanggal) + '</b></span>'; });
                    if (d.resi_balik) meta = '<span class="tr-pill">Resi balik <b>' + esc(d.resi_balik) + '</b></span>' + meta;
                    banner('dbl', '?', 'HAPUS RESI INI?', d.noresi || resi, 'Baris ini akan dihapus dari data retur. Salinannya disimpan di log salah scan.', meta, true);
                    suara('audio-sudah-scan');
                } else if (d.kode === 'ok') {
                    menunggu = null;
                    banner('ok', '✓', 'DIHAPUS', d.noresi || resi, msg, '', false);
                    suara('audio-alexis');
                    $resi.val('').focus();
                } else {
                    menunggu = null;
                    banner('err', '✕', 'TIDAK DIHAPUS', d.noresi || resi, msg, '', false);
                    suara(d.kode === 'tidak_ditemukan' ? 'audio-tidak-ditemukan' : 'audio-wrong');
                    $resi.val('').focus();
                }
            },
            error: function () {
                menunggu = null;
                if (hidup()) banner('err', '✕', 'GAGAL', resi, 'Terjadi kesalahan jaringan/server. Coba lagi.', '', false);
            }
        });
    }

    $resi.on('keydown', function (e) {
        if (e.which !== 13) return;
        e.preventDefault();
        var v = $.trim(this.value);
        if (!v) return;
        kirim(v, false);
    });
    $('#bsr_ya').on('click', function () { if (menunggu) { var r = menunggu; menunggu = null; kirim(r, true); } });
    $('#bsr_batal').on('click', function () {
        menunggu = null;
        $('#bsr_res').hide();
        $resi.val('').focus();
    });

    // Kursor kembali ke kolom scan setelah klik di luar tombol; dilepas otomatis saat pindah menu.
    $(document).off('click.bsr').on('click.bsr', function (e) {
        if (!hidup()) { $(document).off('.bsr'); return; }
        if ($(e.target).closest('#bsr_aksi').length) return;
        setTimeout(function () { if (hidup()) { $resi.focus(); } }, 0);
    });
    $resi.focus();
})();
</script>
