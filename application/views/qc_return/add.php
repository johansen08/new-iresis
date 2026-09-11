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
    var skuSelectedFromDropdown = false; // flag: cegah blur override klik dropdown

    // ==================== FUNGSI FETCH DETAIL SKU ====================
    function fetchSkuDetail(skuVal) {
        if (!skuVal) return;
        $('#rak_loading').show();
        $('#sku_nama_display').hide();
        $('#sku_not_found').hide();
        $.ajax({
            url: '<?= base_url('qc_return/get_sku_detail') ?>',
            type: 'POST',
            data: { sku: skuVal },
            dataType: 'JSON',
            success: function(res) {
                $('#rak_loading').hide();
                if (res && res.id_sku) {
                    // Isi No Rak otomatis
                    $('#no_rak_input').val(res.no_rak || '');
                    // Tampilkan nama SKU sebagai konfirmasi
                    $('#sku_nama_text').text(res.nama_sku || res.id_sku);
                    $('#sku_nama_display').show();
                    $('#sku_not_found').hide();
                } else {
                    $('#no_rak_input').val('');
                    $('#sku_nama_display').hide();
                    $('#sku_not_found').show();
                }
            },
            error: function() {
                $('#rak_loading').hide();
            }
        });
    }

    // ==================== AUTOCOMPLETE SAAT KETIK ====================
    $('#sku_autocomplete').on('keyup', function(e) {
        var q = $(this).val().trim();
        clearTimeout(timer);

        // Tekan Enter langsung fetch detail SKU
        if (e.which === 13) {
            $('#sku_results').hide();
            fetchSkuDetail(q);
            return;
        }

        if (q.length >= 2) {
            timer = setTimeout(function() {
                $.ajax({
                    url: '<?= base_url('qc_return/get_sku_suggestions') ?>',
                    type: 'GET',
                    data: { q: q },
                    dataType: 'JSON',
                    success: function(res) {
                        var html = '';
                        if (res.results.length > 0) {
                            $.each(res.results, function(i, item) {
                                html += '<a href="javascript:void(0)" class="list-group-item sku-item" data-sku="'+item.id+'" data-rak="'+(item.no_rak || '')+'" data-nama="'+(item.text || '')+'">';
                                html += '<strong>'+item.id+'</strong>';
                                if (item.no_rak) {
                                    html += ' &nbsp; <span class="label label-success">Rak: '+item.no_rak+'</span>';
                                }
                                html += '</a>';
                            });
                            $('#sku_results').html(html).show();
                        } else {
                            $('#sku_results').hide();
                        }
                    }
                });
            }, 300);
        } else {
            $('#sku_results').hide();
            $('#sku_nama_display').hide();
            $('#sku_not_found').hide();
            $('#no_rak_input').val('');
        }
    });

    // ==================== KLIK ITEM AUTOCOMPLETE ====================
    $(document).on('click', '.sku-item', function() {
        var sku  = $(this).data('sku');
        var rak  = $(this).data('rak');
        var nama = $(this).data('nama');
        skuSelectedFromDropdown = true; // tandai: dipilih dari dropdown
        $('#sku_autocomplete').val(sku);
        $('#no_rak_input').val(rak);
        $('#sku_nama_text').text(nama || sku);
        $('#sku_nama_display').show();
        $('#sku_not_found').hide();
        $('#sku_results').hide();
        // Jika rak kosong di dropdown, tetap fetch detail untuk memastikan
        if (!rak) {
            fetchSkuDetail(sku);
        }
    });

    // ==================== BLUR: AUTO FETCH SAAT PINDAH FIELD ====================
    $('#sku_autocomplete').on('blur', function() {
        // Jika dipilih dari dropdown, skip blur handler (blur terpicu sebelum click selesai)
        if (skuSelectedFromDropdown) {
            skuSelectedFromDropdown = false; // reset flag
            return;
        }
        var q = $(this).val().trim();
        if (q.length > 0) {
            setTimeout(function() {
                fetchSkuDetail(q);
            }, 150);
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

