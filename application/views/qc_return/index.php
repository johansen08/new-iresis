<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="kp-filter">
                    <div class="kp-chips" id="kp_chips">
                        <button type="button" class="kp-chip" data-preset="hari_ini">Hari Ini</button>
                        <button type="button" class="kp-chip" data-preset="kemarin">Kemarin</button>
                        <button type="button" class="kp-chip" data-preset="7_hari">7 Hari</button>
                        <button type="button" class="kp-chip" data-preset="bulan_ini">Bulan Ini</button>
                    </div>
                    <?php if (!empty($bisa_diambil)): ?>
                    <button type="button" class="btn btn-primary" id="kp_btn_diambil" disabled onclick="kpBarangDiambil()"><i class="fa fa-shopping-basket"></i> Barang Diambil <span id="kp_jml"></span></button>
                    <?php endif; ?>
                    <?php if (!empty($bisa_cetak)): ?>
                    <button type="button" class="btn btn-warning" id="kp_btn_cetak" disabled onclick="kpCetakSerahTerima()"><i class="fa fa-print"></i> Cetak Serah Terima <span id="kp_jml_cetak"></span></button>
                    <?php endif; ?>
                    <?php if (!empty($bisa_selesai)): ?>
                    <button type="button" class="btn btn-success" id="kp_btn_selesai" disabled onclick="kpSelesai()"><i class="fa fa-check"></i> Selesai <span id="kp_jml_selesai"></span></button>
                    <?php endif; ?>
                    <div class="kp-tanggal" id="kp_tanggal">
                        <i class="fa fa-calendar"></i>
                        <span id="kp_label"></span>
                        <i class="fa fa-angle-down kp-caret"></i>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="table_kesalahan_picker" class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr><?php if (!empty($bisa_diambil) || !empty($bisa_cetak) || !empty($bisa_selesai)): ?><th style="width:30px"><input type="checkbox" id="kp_pilih_semua"></th><?php endif; ?><th>#</th><th>Tanggal</th><th>No Resi</th><th>SKU</th><th>Jenis Masalah</th><th>Qty</th><th>Picker</th><th>Packer</th><th>Status Pengembalian</th></tr>
                        </thead>
                        <tbody id="tbody_kesalahan_picker"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var kpMulai = moment(), kpAkhir = moment();
    var KP_BISA_DIAMBIL = <?= (!empty($bisa_diambil) || !empty($bisa_cetak) || !empty($bisa_selesai)) ? 'true' : 'false' ?>; // tampilkan kolom checkbox

    function kpPreset(nama) {
        switch (nama) {
            case 'kemarin': return [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
            case '7_hari': return [moment().subtract(6, 'days'), moment()];
            case 'bulan_ini': return [moment().startOf('month'), moment()];
            default: return [moment(), moment()];
        }
    }

    // Isi label tanggal + tandai chip yang cocok, lalu muat ulang tabel
    function kpTerapkan(mulai, akhir) {
        kpMulai = mulai;
        kpAkhir = akhir;
        var label = mulai.isSame(akhir, 'day') ?
            mulai.format('D MMM YYYY') :
            mulai.format(mulai.isSame(akhir, 'year') ? 'D MMM' : 'D MMM YYYY') + ' – ' + akhir.format('D MMM YYYY');
        $('#kp_label').text(label);
        $('#kp_chips .kp-chip').each(function() {
            var p = kpPreset($(this).data('preset'));
            $(this).toggleClass('aktif', p[0].isSame(mulai, 'day') && p[1].isSame(akhir, 'day'));
        });
        muatKesalahanPicker();
    }

    $(document).ready(function() {
        $('#kp_tanggal').daterangepicker({
            autoApply: true,
            opens: 'left',
            maxDate: moment(),
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                customRangeLabel: 'Custom',
                weekLabel: 'M',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                firstDay: 1
            },
            startDate: moment(),
            endDate: moment()
        }, function(mulai, akhir) {
            kpTerapkan(mulai, akhir);
        });

        $('#kp_chips').on('click', '.kp-chip', function() {
            var p = kpPreset($(this).data('preset'));
            var dp = $('#kp_tanggal').data('daterangepicker');
            dp.setStartDate(p[0]);
            dp.setEndDate(p[1]);
            kpTerapkan(p[0], p[1]);
        });

        kpTerapkan(moment(), moment());
    });


    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    function muatKesalahanPicker() {
        var $tb = $('#tbody_kesalahan_picker');
        
        $tb.html('<tr><td colspan="10" class="text-center">Memuat...</td></tr>');
        $.ajax({
            url: 'qc_return/kesalahan_picker',
            type: 'POST',
            data: {
                start_date: kpMulai.format('YYYY-MM-DD'),
                end_date: kpAkhir.format('YYYY-MM-DD')
            },
            dataType: 'JSON',
            success: function(res) {
                var list = (res && res.data) ? res.data : [];
                if (!list.length) {
                    $tb.html('<tr><td colspan="10" class="text-center">Tidak ada kesalahan picker pada rentang ini</td></tr>');
                    return;
                }
                var html = '';
                $.each(list, function(i, r) {
                    var jenis = esc(r.type_masalah || '-') + (r.sku_salah ? '<br><small class="text-muted">Terambil: ' + esc(r.sku_salah) + '</small>' : '');
                    var qty = r.qty_bermasalah > 0 ? r.qty_bermasalah + ' / ' + r.qty : r.qty;
                    // Alur: BELUM DIAMBIL -> DIAMBIL RESTOK -> SERAH TERIMA DICETAK -> SELESAI
                    var tahap = r.selesai_at ? 'selesai' : (r.dicetak_at ? 'dicetak' : (r.diambil_baris_at ? 'diambil' : 'belum'));
                    var label = {
                        selesai: '<span class="label label-success">SELESAI</span>',
                        dicetak: '<span class="label label-info">SERAH TERIMA DICETAK</span>',
                        diambil: '<span class="label label-primary">BARANG DIAMBIL RESTOK</span>',
                        belum: '<span class="label label-default">BARANG BELUM DIAMBIL</span>'
                    }[tahap];
                    var status = label;
                    if (r.log_at) {
                        status += '<br><small class="text-muted">' + esc((r.log_absen || '-') + ' - ' + (r.log_nama || '-') + ' - ' + (r.log_role || '-')) +
                            '<br>' + esc(r.log_at) + '</small>';
                    }
                    var cek = KP_BISA_DIAMBIL ? '<td><input type="checkbox" class="kp-cek" data-tahap="' + tahap + '" value="' + r.id_masalahpicker + '"' + (tahap == 'selesai' ? ' disabled' : '') + '></td>' : '';
                    html += '<tr>' + cek + '<td>' + (i + 1) + '</td><td>' + esc(r.created) + '</td><td>' + esc(r.noresi) + '</td><td>' + esc(r.sku) +
                        '</td><td>' + jenis + '</td><td>' + esc(qty) + '</td><td>' + esc(r.nama_picker || '-') +
                        '</td><td>' + esc(r.nama_packer || '-') + '</td><td>' + status + '</td></tr>';
                });
                $tb.html(html);
                kpUpdateTombol();
            },
            error: function() {
                $tb.html('<tr><td colspan="10" class="text-center text-danger">Gagal memuat kesalahan picker</td></tr>');
            }
        });
    }

    // ID baris tercentang pada tahap tertentu
    function kpIds(tahap) {
        return $('.kp-cek:checked').filter(function() {
            return tahap.indexOf($(this).data('tahap')) >= 0;
        }).map(function() { return this.value; }).get();
    }

    // Barang Diambil: untuk baris yang belum diambil. Cetak Serah Terima: baris yang sudah diambil (atau cetak ulang).
    function kpUpdateTombol() {
        var nAmbil = kpIds(['belum']).length;
        var nCetak = kpIds(['diambil', 'dicetak']).length;
        $('#kp_btn_diambil').prop('disabled', nAmbil === 0);
        $('#kp_jml').text(nAmbil ? '(' + nAmbil + ')' : '');
        $('#kp_btn_cetak').prop('disabled', nCetak === 0);
        $('#kp_jml_cetak').text(nCetak ? '(' + nCetak + ')' : '');
        var nSelesai = kpIds(['dicetak']).length;
        $('#kp_btn_selesai').prop('disabled', nSelesai === 0);
        $('#kp_jml_selesai').text(nSelesai ? '(' + nSelesai + ')' : '');
        var total = $('.kp-cek:not(:disabled)').length;
        $('#kp_pilih_semua').prop('checked', total > 0 && $('.kp-cek:checked').length === total);
    }

    $(document).on('change', '.kp-cek', kpUpdateTombol);
    $(document).on('change', '#kp_pilih_semua', function() {
        $('.kp-cek:not(:disabled)').prop('checked', this.checked);
        kpUpdateTombol();
    });

    function kpBarangDiambil() {
        var ids = kpIds(['belum']);
        if (!ids.length) return;
        if (!confirm('Tandai ' + ids.length + ' baris sebagai BARANG DIAMBIL RESTOK? Nama dan waktu akan tercatat dan tidak bisa diubah.')) return;
        $('#kp_btn_diambil').prop('disabled', true);
        $.ajax({
            url: 'qc_return/kesalahan_picker_diambil',
            type: 'POST',
            data: { ids: ids },
            dataType: 'JSON',
            success: function(res) {
                noty({ text: res.message, layout: 'topRight', type: res.code == 200 ? 'success' : 'error', timeout: 3000 });
                muatKesalahanPicker();
            },
            error: function() {
                noty({ text: 'Gagal menyimpan', layout: 'topRight', type: 'error', timeout: 3000 });
                kpUpdateTombol();
            }
        });
    }

    // Selesai (tim retur & webmaster): hanya baris yang sudah SERAH TERIMA DICETAK
    function kpSelesai() {
        var ids = kpIds(['dicetak']);
        if (!ids.length) return;
        if (!confirm('Tandai ' + ids.length + ' baris sebagai SELESAI? Nama dan waktu akan tercatat dan tidak bisa diubah.')) return;
        $('#kp_btn_selesai').prop('disabled', true);
        $.ajax({
            url: 'qc_return/kesalahan_picker_selesai',
            type: 'POST',
            data: { ids: ids },
            dataType: 'JSON',
            success: function(res) {
                noty({ text: res.message, layout: 'topRight', type: res.code == 200 ? 'success' : 'error', timeout: 3000 });
                muatKesalahanPicker();
            },
            error: function() {
                noty({ text: 'Gagal menyimpan', layout: 'topRight', type: 'error', timeout: 3000 });
                kpUpdateTombol();
            }
        });
    }

    // Buka halaman cetak serah terima (tab baru, form POST) untuk baris yang sudah diambil
    function kpCetakSerahTerima() {
        var ids = kpIds(['diambil', 'dicetak']);
        if (!ids.length) return;
        if (!confirm('Cetak serah terima untuk ' + ids.length + ' baris? Status menjadi SERAH TERIMA DICETAK dan nama + waktu tercatat.')) return;
        var $f = $('<form method="POST" action="qc_return/cetak_serah_kesalahan" target="_blank" class="nojs" style="display:none"></form>');
        $.each(ids, function(i, id) {
            $f.append($('<input type="hidden" name="ids[]">').val(id));
        });
        $f.appendTo('body').submit().remove();
        setTimeout(muatKesalahanPicker, 1500);
    }
</script>

<style>
    .kp-filter {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }

    .kp-chips {
        display: inline-flex;
        background: #f1f3f5;
        border-radius: 999px;
        padding: 3px;
    }

    .kp-chip {
        border: 0;
        background: transparent;
        color: #555;
        font-size: 13px;
        padding: 6px 14px;
        border-radius: 999px;
        cursor: pointer;
        transition: background .15s, color .15s;
    }

    .kp-chip:hover {
        color: #1b6ef3;
    }

    .kp-chip.aktif {
        background: #fff;
        color: #1b6ef3;
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .15);
    }

    .kp-tanggal {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 14px;
        background: #fff;
        border: 1px solid #d5dae0;
        border-radius: 999px;
        font-size: 13px;
        color: #333;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
    }

    .kp-tanggal:hover {
        border-color: #1b6ef3;
        box-shadow: 0 0 0 3px rgba(27, 110, 243, .12);
    }

    .kp-tanggal .fa-calendar,
    .kp-caret {
        color: #1b6ef3;
    }
</style>
