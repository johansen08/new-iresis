/**
 * Rekomendasi SKU untuk kolom "SKU Salah" di modal Submit Masalah Picker
 * (Scan Resi Packer dan Scan Resi Packer Webcam).
 *
 * - Saat mengetik, muncul daftar SKU master yang mengandung teks.
 * - Enter / pindah kolom / klik item: teks diganti SKU standar (id_sku di tblsku).
 * - Kalau tidak ada satu pun SKU yang cocok, teks user dibiarkan apa adanya.
 * - Kalau cocok lebih dari satu, user wajib memilih dari rekomendasi
 *   (server menolak simpan selama masih ambigu, lihat Packer::masalah_picker_save).
 *
 * Memakai endpoint yang sama dengan menu QC Return.
 */
(function ($) {
  'use strict';

  // Halaman dimuat ulang lewat AJAX tanpa reload: jangan pasang handler dua kali.
  if (window.__skuSalahSaranTerpasang) return;
  window.__skuSalahSaranTerpasang = true;

  var SEL = '#sku_salah';
  var timer = null;
  var terkonfirmasi = ''; // id_sku standar terakhir yang dipilih / ditemukan

  function $input() { return $(SEL); }

  function $daftar() {
    var $d = $('#sku_salah_saran');
    if (!$d.length) {
      $d = $('<div id="sku_salah_saran" class="list-group" style="display:none;position:absolute;z-index:10000;max-height:220px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.2);margin:0;"></div>');
      $('body').append($d);
    }
    return $d;
  }

  function letakkan() {
    var $i = $input();
    var o = $i.offset();
    $daftar().css({ top: o.top + $i.outerHeight(), left: o.left, width: $i.outerWidth() });
  }

  function status(teks, kelas) {
    var $s = $('#sku_salah_status');
    if (!$s.length) {
      $s = $('<small id="sku_salah_status" class="help-block" style="margin-bottom:0;"></small>');
      $input().after($s);
    }
    $s.attr('class', 'help-block ' + (kelas || '')).text(teks || '');
  }

  function tutup() { $daftar().hide().empty(); }

  function pilih(row) {
    terkonfirmasi = row.id_sku;
    $input().val(row.id_sku);
    status('SKU standar: ' + (row.nama_sku || row.id_sku), 'text-success');
    tutup();
  }

  function tampilkan(list) {
    var $d = $daftar().empty();
    if (!list || !list.length) { $d.hide(); return; }
    $.each(list, function (i, item) {
      var $a = $('<a href="javascript:void(0)" class="list-group-item sku-salah-item"></a>')
        .data('row', item)
        .append($('<strong></strong>').text(item.id_sku));
      if (item.no_rak) {
        $a.append('  ').append($('<span class="label label-success"></span>').text('Rak: ' + item.no_rak));
      }
      $d.append($a);
    });
    letakkan();
    $d.show();
  }

  // Tebak satu SKU standar dari teks (Enter / pindah kolom).
  function cocokkan(teks) {
    teks = $.trim(teks);
    if (!teks) { status(''); return; }
    $.ajax({
      url: 'qc-return/get-sku-detail',
      type: 'POST',
      data: { sku: teks },
      dataType: 'json'
    }).done(function (res) {
      if ($.trim($input().val()) !== teks) return; // teks sudah berubah lagi
      var d = (res && res.data) ? res.data : {};
      if (d.status === 'ok' && d.sku) {
        pilih(d.sku);
      } else if (d.status === 'ambigu') {
        terkonfirmasi = '';
        status('Ada beberapa SKU yang cocok, pilih salah satu dari rekomendasi.', 'text-warning');
        tampilkan(d.kandidat);
      } else {
        terkonfirmasi = '';
        tutup();
        status('SKU tidak ada di master, akan disimpan sesuai ketikan.', 'text-muted');
      }
    });
  }

  $(document).on('input', SEL, function () {
    var q = $.trim($(this).val());
    clearTimeout(timer);
    if (q !== terkonfirmasi) { terkonfirmasi = ''; status(''); }
    if (q.length < 2) { tutup(); return; }
    timer = setTimeout(function () {
      $.ajax({
        url: 'qc-return/get-sku-suggestions',
        type: 'GET',
        data: { q: q },
        dataType: 'json'
      }).done(function (res) {
        if ($.trim($input().val()) !== q) return;
        tampilkan(res && res.data ? res.data.results : []);
      });
    }, 250);
  });

  $(document).on('keydown', SEL, function (e) {
    if (e.which === 27) { tutup(); return; }
    if (e.which !== 13) return;
    e.preventDefault(); // Enter di kolom ini bukan submit form
    clearTimeout(timer);
    tutup();
    var q = $.trim($(this).val());
    if (q && q !== terkonfirmasi) cocokkan(q);
  });

  $(document).on('blur', SEL, function () {
    var q = $.trim($(this).val());
    if (q && q !== terkonfirmasi) cocokkan(q);
  });

  // mousedown dicegah supaya kolom tidak blur duluan sebelum item terpilih
  $(document).on('mousedown', '.sku-salah-item', function (e) { e.preventDefault(); });
  $(document).on('click', '.sku-salah-item', function () {
    var row = $(this).data('row');
    if (row) pilih(row);
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest(SEL + ', #sku_salah_saran').length) tutup();
  });

  // Modal ditutup / form di-reset / diganti tipe masalah: bersihkan sisa state.
  $(document).on('click', '.btn-cancel-popup, #masalahPickerModal button[type=reset]', function () {
    terkonfirmasi = '';
    tutup();
    status('');
  });
})(jQuery);
