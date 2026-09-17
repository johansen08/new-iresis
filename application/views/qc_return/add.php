<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <form class="form-horizontal" action="<?= base_url('qc_return/save') ?>" method="post">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Submit Pengembalian Barang QC</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label">SKU</label>
                        <div class="col-md-9">
                            <input type="text" name="sku" id="sku_autocomplete" class="form-control" placeholder="Masukkan SKU" required autocomplete="off">
                            <div id="sku_results" class="list-group" style="position: absolute; width: 93%; z-index: 1000; display: none; max-height: 200px; overflow-y: auto;"></div>
                            <small id="sku_nama_display" style="color: #27ae60; font-weight: bold; display: none;"><i class="fa fa-check-circle"></i> <span id="sku_nama_text"></span></small>
                            <small id="sku_not_found" style="color: #e74c3c; display: none;"><i class="fa fa-times-circle"></i> SKU tidak ditemukan</small>
                            <small id="sku_ambigu" style="color: #f39c12; display: none;"><i class="fa fa-exclamation-circle"></i> Ada beberapa SKU yang cocok, lengkapi kodenya atau pilih dari daftar</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Quantity</label>
                        <div class="col-md-9">
                            <input type="number" name="qty" class="form-control" placeholder="Jumlah" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Ketersediaan SKU</label>
                        <div class="col-md-9">
                            <label class="radio-inline"><input type="radio" name="ketersediaan" value="Ada Semua" checked> Semua Ada</label>
                            <label class="radio-inline"><input type="radio" name="ketersediaan" value="Ada yang kurang"> Ada yang kurang</label>
                        </div>
                    </div>
                    <div class="form-group" id="qty_kurang_group" style="display: none;">
                        <label class="col-md-3 control-label">Jumlah Kurang (pcs)</label>
                        <div class="col-md-9">
                            <input type="number" name="qty_kurang" id="qty_kurang" class="form-control" placeholder="Jumlah barang tidak ada (pcs)" min="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">No Rak</label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <input type="text" name="no_rak" id="no_rak_input" class="form-control" placeholder="Otomatis dari SKU" required>
                                <span class="input-group-addon" id="rak_loading" style="display:none;"><i class="fa fa-spinner fa-spin"></i></span>
                            </div>
                            <small class="text-muted">Terisi otomatis setelah pilih SKU</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Kondisi</label>
                        <div class="col-md-9">
                            <select name="kondisi" id="kondisi_select" class="form-control select" required>
                                <option value="LEBIH AMBIL">LEBIH AMBIL</option>
                                <option value="SALAH AMBIL">SALAH AMBIL</option>
                                <option value="REJECT">REJECT</option>
                            </select>
                        </div>
                    </div>
                    <div id="reject_reason_group" class="form-group" style="display: none;">
                        <label class="col-md-3 control-label">Keterangan Reject</label>
                        <div class="col-md-9">
                            <select name="keterangan_reject" class="form-control select">
                                <option value="">- Pilih Keterangan -</option>
                                <option value="Robek">Robek</option>
                                <option value="Tali putus">Tali putus</option>
                                <option value="Kotor">Kotor</option>
                                <option value="Noda">Noda</option>
                                <option value="Pengait anting tidak lengkap">Pengait anting tidak lengkap</option>
                                <option value="Karat">Karat</option>
                                <option value="Aksesoris tidak lengkap">Aksesoris tidak lengkap</option>
                                <option value="Patah/Pecah/Retak/Lepas">Patah/Pecah/Retak/Lepas</option>
                                <option value="Tidak bisa dikaitkan">Tidak bisa dikaitkan</option>
                                <option value="Bolong/Rusak">Bolong/Rusak</option>
                                <option value="Bagian ring topi lepas">Bagian ring topi lepas</option>
                                <option value="tali dagu tidak lengkap">tali dagu tidak lengkap</option>
                                <option value="Kait masker tidak ada">Kait masker tidak ada</option>
                                <option value="Jahitan tidak rapih">Jahitan tidak rapih</option>
                                <option value="Logo lepas">Logo lepas</option>
                                <option value="Repair manual">Repair manual</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel-footer text-right">
                    <a href="<?= base_url('qc_return') ?>" class="btn btn-default link">Kembali</a>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
#sku_results .list-group-item {
    cursor: pointer;
}
#sku_results .list-group-item:hover {
    background-color: #f5f5f5;
}
</style>

<script type="text/javascript">
$(document).ready(function() {
    var timer;
    var skuTerkonfirmasi = ''; // id_sku lengkap yang terakhir berhasil dipilih
    var sedangCari = false;    // ada request tebak-SKU yang belum selesai

    var $sku   = $('#sku_autocomplete');
    var $rak   = $('#no_rak_input');
    var $qty   = $('input[name="qty"]');
    var $hasil = $('#sku_results');

    function sembunyikanStatus() {
        $('#sku_nama_display, #sku_not_found, #sku_ambigu').hide();
    }

    // SKU yang diketik tidak lagi cocok dengan yang terpilih -> rak & konfirmasi jadi basi
    function kosongkanSku() {
        skuTerkonfirmasi = '';
        $rak.val('');
        sembunyikanStatus();
    }

    // Terapkan SKU terpilih: isi kolom SKU dengan kode lengkap + No Rak, tampilkan nama sebagai konfirmasi
    function pilihSku(row, pindahKeQty) {
        skuTerkonfirmasi = row.id_sku;
        $sku.val(row.id_sku);
        $rak.val(row.no_rak || '');
        sembunyikanStatus();
        $('#sku_nama_text').text(row.nama_sku || row.id_sku);
        $('#sku_nama_display').show();
        $hasil.hide().empty();
        if (pindahKeQty) {
            $qty.focus();
        }
    }

    // Render daftar kandidat ke dropdown (dipakai saat mengetik dan saat hasil tebakan ambigu)
    function tampilkanKandidat(list) {
        $hasil.empty();
        if (!list || !list.length) {
            $hasil.hide();
            return;
        }
        $.each(list, function(i, item) {
            var $item = $('<a href="javascript:void(0)" class="list-group-item sku-item"></a>')
                .data('row', item)
                .append($('<strong></strong>').text(item.id_sku));
            if (item.no_rak) {
                $item.append('   ').append($('<span class="label label-success"></span>').text('Rak: ' + item.no_rak));
            }
            $hasil.append($item);
        });
        $hasil.show();
    }

    // ==================== TEBAK SKU DARI TEKS (Enter / pindah field) ====================
    // Server yang memutuskan: cocok persis -> mengandung teks -> akhiran sama. "aks28-2" langsung jadi BM-AKS28-2.
    function cariSku(teks, pindahKeQty) {
        teks = $.trim(teks);
        if (!teks) {
            kosongkanSku();
            return;
        }
        sedangCari = true;
        $('#rak_loading').show();
        sembunyikanStatus();
        $.ajax({
            url: '<?= base_url('qc_return/get_sku_detail') ?>',
            type: 'POST',
            data: { sku: teks },
            dataType: 'JSON',
            success: function(res) {
                var d = (res && res.data) ? res.data : {};
                if (d.status === 'ok' && d.sku) {
                    pilihSku(d.sku, pindahKeQty);
                } else if (d.status === 'ambigu') {
                    // Lebih dari satu SKU cocok: biarkan user memilih dari dropdown (kalau kursor masih di kolom SKU)
                    kosongkanSku();
                    $('#sku_ambigu').show();
                    if ($sku.is(':focus')) {
                        tampilkanKandidat(d.kandidat);
                    }
                } else {
                    kosongkanSku();
                    $('#sku_not_found').show();
                }
            },
            error: function() {
                kosongkanSku();
                $('#sku_not_found').show();
            },
            complete: function() {
                sedangCari = false;
                $('#rak_loading').hide();
            }
        });
    }

    // ==================== ENTER: PILIH SKU, JANGAN SUBMIT FORM ====================
    $sku.on('keydown', function(e) {
        if (e.which !== 13) return;
        e.preventDefault(); // Enter di kolom SKU bukan submit form
        clearTimeout(timer);
        $hasil.hide();
        var q = $.trim($(this).val());
        if (q && q === skuTerkonfirmasi) {
            $qty.focus(); // sudah terpilih, tinggal lanjut ke Quantity
            return;
        }
        cariSku(q, true);
    });

    // ==================== AUTOCOMPLETE SAAT KETIK ====================
    $sku.on('keyup', function(e) {
        // Enter sudah ditangani di keydown; tombol navigasi/modifier tidak mengubah teks
        if (e.which === 13 || e.which === 9 || (e.which >= 16 && e.which <= 18) || (e.which >= 37 && e.which <= 40)) return;
        if (e.which === 27) { // Esc: tutup dropdown
            $hasil.hide();
            return;
        }

        var q = $.trim($(this).val());
        clearTimeout(timer);

        if (q !== skuTerkonfirmasi) {
            kosongkanSku();
        }

        if (q.length >= 2) {
            timer = setTimeout(function() {
                $.ajax({
                    url: '<?= base_url('qc_return/get_sku_suggestions') ?>',
                    type: 'GET',
                    data: { q: q },
                    dataType: 'JSON',
                    success: function(res) {
                        // Abaikan balasan yang datang setelah teks berubah
                        if ($.trim($sku.val()) !== q) return;
                        tampilkanKandidat(res && res.data ? res.data.results : []);
                    }
                });
            }, 300);
        } else {
            $hasil.hide();
        }
    });

    // ==================== KLIK ITEM AUTOCOMPLETE ====================
    // mousedown di-preventDefault supaya kolom SKU tidak blur duluan (blur akan memicu cariSku dengan teks mentah)
    $(document).on('mousedown', '.sku-item', function(e) {
        e.preventDefault();
    });
    $(document).on('click', '.sku-item', function() {
        var row = $(this).data('row');
        if (row) {
            pilihSku(row, true);
        }
    });

    // ==================== BLUR: TEBAK SKU SAAT PINDAH FIELD ====================
    $sku.on('blur', function() {
        if (sedangCari) return;
        var q = $.trim($(this).val());
        if (q && q !== skuTerkonfirmasi) {
            cariSku(q, false);
        }
    });

    // ==================== TUTUP DROPDOWN SAAT KLIK DI LUAR ====================
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#sku_autocomplete, #sku_results').length) {
            $('#sku_results').hide();
        }
    });

    // ==================== KONDISI REJECT ====================
    $('#kondisi_select').on('change', function() {
        if ($(this).val() == 'REJECT') {
            $('#reject_reason_group').show();
            $('select[name="keterangan_reject"]').attr('required', true);
        } else {
            $('#reject_reason_group').hide();
            $('select[name="keterangan_reject"]').attr('required', false).val('');
        }
    });

    // ==================== KETERSEDIAAN SKU ====================
    $('input[name="ketersediaan"]').on('change', function() {
        if ($(this).val() == 'Ada yang kurang') {
            $('#qty_kurang_group').show();
            $('input[name="qty_kurang"]').attr('required', true);
        } else {
            $('#qty_kurang_group').hide();
            $('input[name="qty_kurang"]').attr('required', false).val('');
        }
    });
});
</script>

