<?php $mode_reject = !empty($mode_reject); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default kp-root">
            <div class="panel-body">

                <div class="kp-bar">
                    <div class="kp-filter">
                        <div class="kp-chips" id="kp_chips" role="group" aria-label="Pilihan cepat tanggal">
                            <button type="button" class="kp-chip" data-preset="hari_ini">Hari Ini</button>
                            <button type="button" class="kp-chip" data-preset="kemarin">Kemarin</button>
                            <button type="button" class="kp-chip" data-preset="7_hari">7 Hari</button>
                            <button type="button" class="kp-chip" data-preset="bulan_ini">Bulan Ini</button>
                        </div>
                        <button type="button" class="kp-tanggal" id="kp_tanggal" aria-label="Pilih rentang tanggal">
                            <i class="fa fa-calendar"></i>
                            <span id="kp_label"></span>
                            <i class="fa fa-angle-down kp-caret"></i>
                        </button>
                    </div>

                    <?php if ($mode_reject): ?>
                    <div class="kp-aksi">
                        <button type="button" class="kp-btn kp-btn-selesai" id="kp_btn_excel" onclick="kpExportExcel()"><i class="fa fa-file-excel-o"></i><span>Export Excel</span></button>
                    </div>
                    <?php elseif (!empty($bisa_diambil) || !empty($bisa_cetak) || !empty($bisa_selesai)): ?>
                    <div class="kp-aksi">
                        <?php if (!empty($bisa_diambil)): ?>
                        <button type="button" class="kp-btn kp-btn-diambil" id="kp_btn_diambil" disabled onclick="kpBarangDiambil()"><i class="fa fa-shopping-basket"></i><span>Barang Diambil</span><b class="kp-n" id="kp_jml"></b></button>
                        <?php endif; ?>
                        <?php if (!empty($bisa_cetak)): ?>
                        <button type="button" class="kp-btn kp-btn-cetak" id="kp_btn_cetak" disabled onclick="kpCetakSerahTerima()"><i class="fa fa-print"></i><span>Cetak Serah Terima</span><b class="kp-n" id="kp_jml_cetak"></b></button>
                        <?php endif; ?>
                        <?php if (!empty($bisa_selesai)): ?>
                        <button type="button" class="kp-btn kp-btn-selesai" id="kp_btn_selesai" disabled onclick="kpSelesai()"><i class="fa fa-check"></i><span>Selesai</span><b class="kp-n" id="kp_jml_selesai"></b></button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="kp-info">
                    <span id="kp_total">Memuat…</span>
                    <span class="kp-dipilih" id="kp_dipilih"></span>
                </div>

                <div class="kp-scroll">
                    <table id="table_kesalahan_picker" class="kp-table">
                        <thead>
                            <tr>
                                <?php if (!empty($bisa_diambil) || !empty($bisa_cetak) || !empty($bisa_selesai)): ?><th class="kp-c-cek"><input type="checkbox" id="kp_pilih_semua" aria-label="Pilih semua baris"></th><?php endif; ?>
                                <th class="kp-c-no">#</th>
                                <th>Tanggal</th>
                                <th>No Resi</th>
                                <th>SKU</th>
                                <th>Jenis Masalah</th>
                                <th class="kp-c-qty">Qty</th>
                                <th>Picker</th>
                                <th>Packer</th>
                                <th>Status Pengembalian</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_kesalahan_picker"></tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade kp-modal kp-root" id="kp_popup" tabindex="-1" role="dialog" aria-labelledby="kp_popup_judul">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-exclamation-triangle"></i> <span id="kp_popup_judul"></span></h4>
            </div>
            <div class="modal-body" id="kp_popup_isi"></div>
            <div class="modal-footer">
                <button type="button" class="kp-btn kp-btn-sekunder" id="kp_popup_pilih_sesuai" onclick="kpPilihYangSesuai()" style="display:none"><i class="fa fa-check-square-o"></i><span>Hanya pilih baris yang sesuai</span></button>
                <button type="button" class="kp-btn kp-btn-diambil" data-dismiss="modal"><span>Mengerti</span></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var kpMulai = moment(), kpAkhir = moment();
    var KP_BISA_DIAMBIL = <?= (!empty($bisa_diambil) || !empty($bisa_cetak) || !empty($bisa_selesai)) ? 'true' : 'false' ?>; // tampilkan kolom checkbox
    var KP_MODE_REJECT = <?= $mode_reject ? 'true' : 'false' ?>; // menu Barang Reject: hanya jenis REJECT, tombol Export Excel
    var KP_BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    function kpTgl(m, denganTahun) {
        return m.date() + ' ' + KP_BULAN[m.month()] + (denganTahun ? ' ' + m.year() : '');
    }

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
            kpTgl(mulai, true) :
            kpTgl(mulai, !mulai.isSame(akhir, 'year')) + ' – ' + kpTgl(akhir, true);
        $('#kp_label').text(label);
        $('#kp_chips .kp-chip').each(function() {
            var p = kpPreset($(this).data('preset'));
            var aktif = p[0].isSame(mulai, 'day') && p[1].isSame(akhir, 'day');
            $(this).toggleClass('aktif', aktif).attr('aria-pressed', aktif);
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

        kpTata();
        $(window).off('resize.kp').on('resize.kp', kpTata);
        kpTerapkan(moment(), moment());
    });

    // Tabel 10 kolom butuh ~1060px; di bawah itu tiap baris menjadi kartu supaya kolom Status tidak tergeser keluar layar
    function kpTata() {
        var $r = $('.kp-root');
        if (!$r.length) return;
        $r.removeClass('kp-kartu');
        var lebar = $r.find('.kp-scroll').width();
        $r.toggleClass('kp-kartu', lebar < 1060);
    }

    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    // "NAMA - ROLE - NOABSEN" -> nama tebal + "ROLE · NOABSEN" redup (dua baris rapi, tidak terpotong)
    function kpPegawai(teks) {
        if (!teks) return '<span class="kp-redup">-</span>';
        var m = /^(.+?)\s+-\s+(.+?)\s*-\s*(\d+)\s*$/.exec(teks);
        if (!m) return '<span class="kp-nama">' + esc(teks) + '</span>';
        return '<span class="kp-nama">' + esc(m[1]) + '</span><span class="kp-sub">' + esc(m[2]) + ' · ' + esc(m[3]) + '</span>';
    }

    // "2026-10-29 17:33:13" -> [tanggal, jam]
    function kpWaktuBagian(s) {
        var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(s || '');
        if (!m) return [s || '', ''];
        return [parseInt(m[3], 10) + ' ' + KP_BULAN[parseInt(m[2], 10) - 1] + ' ' + m[1], m[4] + ':' + m[5]];
    }

    function kpWaktu(s) {
        var w = kpWaktuBagian(s);
        return '<span class="kp-nama">' + esc(w[0]) + '</span><span class="kp-sub">' + esc(w[1]) + '</span>';
    }

    var KP_STATUS = {
        belum: ['kp-b-belum', 'fa-clock-o', 'BARANG BELUM DIAMBIL'],
        diambil: ['kp-b-diambil', 'fa-shopping-basket', 'BARANG DIAMBIL RESTOK'],
        dicetak: ['kp-b-cetak', 'fa-print', 'SERAH TERIMA DICETAK'],
        selesai: ['kp-b-selesai', 'fa-check-circle', 'SELESAI']
    };

    function muatKesalahanPicker() {
        var $tb = $('#tbody_kesalahan_picker');
        var kolom = KP_BISA_DIAMBIL ? 10 : 9;
        $tb.html('<tr class="kp-kosong"><td colspan="' + kolom + '"><i class="fa fa-spinner fa-spin"></i> Memuat data…</td></tr>');
        $('#kp_total').text('Memuat…');
        $.ajax({
            url: KP_MODE_REJECT ? 'qc_return/barang_reject_data' : 'qc_return/kesalahan_picker',
            type: 'POST',
            data: {
                start_date: kpMulai.format('YYYY-MM-DD'),
                end_date: kpAkhir.format('YYYY-MM-DD')
            },
            dataType: 'JSON',
            success: function(res) {
                var list = (res && res.data) ? res.data : [];
                $('#kp_total').text(list.length + ' data');
                if (!list.length) {
                    $tb.html('<tr class="kp-kosong"><td colspan="' + kolom + '"><i class="fa fa-inbox"></i> ' + (KP_MODE_REJECT ? 'Tidak ada barang reject pada rentang ini' : 'Tidak ada kesalahan picker pada rentang ini') + '</td></tr>');
                    kpUpdateTombol();
                    return;
                }
                var html = '';
                $.each(list, function(i, r) {
                    // Alur: BELUM DIAMBIL -> DIAMBIL RESTOK -> SERAH TERIMA DICETAK -> SELESAI
                    var tahap = r.selesai_at ? 'selesai' : (r.dicetak_at ? 'dicetak' : (r.diambil_baris_at ? 'diambil' : 'belum'));
                    var s = KP_STATUS[tahap];
                    var status = '<span class="kp-badge ' + s[0] + '"><i class="fa ' + s[1] + '"></i>' + s[2] + '</span>';
                    if (r.log_at) {
                        var w = kpWaktuBagian(r.log_at);
                        status += '<span class="kp-log">' + esc((r.log_absen || '-') + ' - ' + (r.log_nama || '-') + ' - ' + (r.log_role || '-')) + '</span>' +
                            '<span class="kp-log">' + esc(w[0] + ', ' + w[1]) + '</span>';
                    }
                    var tipe = (r.type_masalah || '-');
                    var jenis = '<span class="kp-jenis ' + (tipe == 'SALAH AMBIL' || tipe.toUpperCase().indexOf('REJECT') >= 0 ? 'kp-j-salah' : 'kp-j-lebih') + '">' + esc(tipe) + '</span>' +
                        (r.sku_salah ? '<span class="kp-sub">Terambil: ' + esc(r.sku_salah) + '</span>' : '');
                    var qty = KP_MODE_REJECT ? r.qty_bermasalah : (r.qty_bermasalah > 0 ? r.qty_bermasalah + ' / ' + r.qty : r.qty); // reject: qty yang di-reject saja
                    var cek = KP_BISA_DIAMBIL ? '<td class="kp-c-cek" data-label=""><input type="checkbox" class="kp-cek" aria-label="Pilih baris ' + (i + 1) + '" data-tahap="' + tahap + '" value="' + r.id_masalahpicker + '"' + (tahap == 'selesai' ? ' disabled' : '') + '></td>' : '';
                    html += '<tr class="kp-tahap-' + tahap + '">' + cek +
                        '<td class="kp-c-no" data-label="#">' + (i + 1) + '</td>' +
                        '<td data-label="Tanggal">' + kpWaktu(r.created) + '</td>' +
                        '<td data-label="No Resi"><span class="kp-resi">' + esc(r.noresi) + '</span></td>' +
                        '<td data-label="SKU"><span class="kp-sku">' + esc(r.sku) + '</span></td>' +
                        '<td data-label="Jenis Masalah">' + jenis + '</td>' +
                        '<td class="kp-c-qty" data-label="Qty">' + esc(qty) + '</td>' +
                        '<td data-label="Picker">' + kpPegawai(r.nama_picker) + '</td>' +
                        '<td data-label="Packer">' + kpPegawai(r.nama_packer) + '</td>' +
                        '<td class="kp-c-status" data-label="Status Pengembalian">' + status + '</td></tr>';
                });
                $tb.html(html);
                kpUpdateTombol();
            },
            error: function() {
                $('#kp_total').text('');
                $tb.html('<tr class="kp-kosong kp-galat"><td colspan="' + kolom + '"><i class="fa fa-exclamation-triangle"></i> Gagal memuat kesalahan picker. Coba ubah tanggal atau muat ulang halaman.</td></tr>');
            }
        });
    }

    // ID baris tercentang pada tahap tertentu
    function kpIds(tahap) {
        return $('.kp-cek:checked').filter(function() {
            return tahap.indexOf($(this).data('tahap')) >= 0;
        }).map(function() { return this.value; }).get();
    }

    // Urutan status: BELUM DIAMBIL -> BARANG DIAMBIL RESTOK -> SERAH TERIMA DICETAK -> SELESAI
    // Barang Diambil: baris belum diambil. Cetak Serah Terima: sudah diambil (atau cetak ulang). Selesai: sudah dicetak.
    var KP_IZIN = { diambil: ['belum'], cetak: ['diambil', 'dicetak'], selesai: ['dicetak'] };
    var KP_LANGKAH = ['belum', 'diambil', 'dicetak', 'selesai'];
    var KP_NAMA_TAHAP = { belum: 'Barang Belum Diambil', diambil: 'Barang Diambil Restok', dicetak: 'Serah Terima Dicetak', selesai: 'Selesai' };
    var KP_NAMA_AKSI = { diambil: 'Barang Diambil', cetak: 'Cetak Serah Terima', selesai: 'Selesai' };
    var KP_TAHAP_AKSI = { diambil: 'diambil', cetak: 'dicetak', selesai: 'selesai' }; // status yang dihasilkan tombol
    var KP_LANGKAH_DULU = {
        belum: 'Klik <b>Barang Diambil</b> dulu',
        diambil: 'Klik <b>Cetak Serah Terima</b> dulu',
        dicetak: 'Baris ini sudah dicetak',
        selesai: 'Baris ini sudah selesai'
    };

    // Tombol aktif selama ada baris tercentang; kalau urutan tidak sesuai, klik menampilkan pop up penjelasan
    function kpUpdateTombol() {
        var n = $('.kp-cek:checked').length;
        $.each({ diambil: ['#kp_btn_diambil', '#kp_jml'], cetak: ['#kp_btn_cetak', '#kp_jml_cetak'], selesai: ['#kp_btn_selesai', '#kp_jml_selesai'] }, function(aksi, el) {
            var sesuai = kpIds(KP_IZIN[aksi]).length;
            $(el[0]).prop('disabled', n === 0).toggleClass('kp-perlu-cek', n > 0 && sesuai === 0);
            $(el[1]).text(n === 0 ? '' : (sesuai || '!'));
        });

        var total = $('.kp-cek:not(:disabled)').length;
        $('#kp_pilih_semua').prop('checked', total > 0 && n === total).prop('indeterminate', n > 0 && n < total);
        $('#kp_dipilih').text(n ? n + ' dipilih' : '');
        $('.kp-cek').each(function() { $(this).closest('tr').toggleClass('kp-dipilih-baris', this.checked); });
    }

    $(document).on('change', '.kp-cek', kpUpdateTombol);
    $(document).on('change', '#kp_pilih_semua', function() {
        $('.kp-cek:not(:disabled)').prop('checked', this.checked);
        kpUpdateTombol();
    });

    function kpKirim(url, ids) {
        $.ajax({
            url: url,
            type: 'POST',
            data: { ids: ids },
            dataType: 'JSON',
            success: function(res) {
                if (res.code == 409) {
                    // Penjaga di server: urutan status tidak sesuai (mis. data berubah oleh user lain sejak tabel dimuat)
                    kpPopupServer(res.message);
                    muatKesalahanPicker();
                    return;
                }
                noty({ text: res.message, layout: 'topRight', type: res.code == 200 ? 'success' : 'error', timeout: 3000 });
                muatKesalahanPicker();
            },
            error: function() {
                noty({ text: 'Gagal menyimpan', layout: 'topRight', type: 'error', timeout: 3000 });
                kpUpdateTombol();
            }
        });
    }

    // ===== Pop up: urutan status belum terpenuhi =====
    // Mengembalikan true bila semua baris terpilih sesuai untuk aksi; bila tidak, tampilkan pop up dan kembalikan false.
    function kpPeriksa(aksi) {
        var hitung = {}, salah = 0, sesuai = 0;
        $('.kp-cek:checked').each(function() {
            var t = $(this).data('tahap');
            if (KP_IZIN[aksi].indexOf(t) >= 0) {
                sesuai++;
            } else {
                salah++;
                hitung[t] = (hitung[t] || 0) + 1;
            }
        });
        if (!salah) return true;
        kpPopup(aksi, hitung, sesuai);
        return false;
    }

    function kpPopup(aksi, hitung, sesuai) {
        // Ringkasan: "3 baris berstatus Barang Diambil Restok"
        var daftar = '';
        $.each(KP_LANGKAH, function(i, t) {
            if (hitung[t]) {
                daftar += '<li><span class="kp-badge ' + KP_STATUS[t][0] + '"><i class="fa ' + KP_STATUS[t][1] + '"></i>' + KP_STATUS[t][2] + '</span> <b>' + hitung[t] + ' baris</b> &mdash; ' + KP_LANGKAH_DULU[t] + '</li>';
            }
        });
        // Urutan langkah: tandai posisi baris yang tidak sesuai dan langkah yang dibutuhkan tombol
        var perlu = KP_LANGKAH.indexOf(KP_IZIN[aksi][0]); // tahap minimal agar tombol ini boleh
        var stepper = '';
        $.each(KP_LANGKAH, function(i, t) {
            var cls = 'kp-step' + (i === perlu ? ' kp-step-perlu' : '') + (hitung[t] ? ' kp-step-posisi' : '');
            stepper += '<div class="' + cls + '"><span class="kp-step-no">' + (i + 1) + '</span><span class="kp-step-nama">' + KP_NAMA_TAHAP[t] + '</span>' +
                (hitung[t] ? '<span class="kp-step-info">' + hitung[t] + ' baris di sini</span>' : (i === perlu ? '<span class="kp-step-info">dibutuhkan</span>' : '')) + '</div>';
        });
        var html = '<p>Tombol <b>' + KP_NAMA_AKSI[aksi] + '</b> belum bisa dipakai karena urutan status tidak terpenuhi. Status pengembalian harus berurutan:</p>' +
            '<div class="kp-stepper">' + stepper + '</div>' +
            '<p class="kp-popup-sub">Baris yang tidak sesuai:</p><ul class="kp-popup-daftar">' + daftar + '</ul>' +
            '<p class="kp-popup-catatan"><i class="fa fa-info-circle"></i> Tidak ada status yang diubah.</p>';
        kpTampilPopup('Urutan status belum terpenuhi', html, sesuai > 0 ? aksi : null);
    }

    function kpPopupServer(pesan) {
        kpTampilPopup('Urutan status belum terpenuhi', '<p>' + esc(pesan) + '</p>', null);
    }

    function kpTampilPopup(judul, html, aksiSesuai) {
        $('#kp_popup_judul').text(judul);
        $('#kp_popup_isi').html(html);
        $('#kp_popup_pilih_sesuai').toggle(!!aksiSesuai).data('aksi', aksiSesuai || '');
        $('#kp_popup').modal('show');
    }

    // Opsi di pop up: buang centang baris yang tidak sesuai, sisakan yang sesuai
    function kpPilihYangSesuai() {
        var aksi = $('#kp_popup_pilih_sesuai').data('aksi');
        if (aksi) {
            $('.kp-cek:checked').each(function() {
                if (KP_IZIN[aksi].indexOf($(this).data('tahap')) < 0) this.checked = false;
            });
            kpUpdateTombol();
        }
        $('#kp_popup').modal('hide');
    }

    function kpExportExcel() {
        window.location.href = 'qc_return/barang_reject_excel?start_date=' + kpMulai.format('YYYY-MM-DD') + '&end_date=' + kpAkhir.format('YYYY-MM-DD');
    }

    function kpBarangDiambil() {
        if (!kpPeriksa('diambil')) return;
        var ids = kpIds(['belum']);
        if (!ids.length) return;
        if (!confirm('Tandai ' + ids.length + ' baris sebagai BARANG DIAMBIL RESTOK? Nama dan waktu akan tercatat dan tidak bisa diubah.')) return;
        $('#kp_btn_diambil').prop('disabled', true);
        kpKirim('qc_return/kesalahan_picker_diambil', ids);
    }

    // Selesai (tim retur & webmaster): hanya baris yang sudah SERAH TERIMA DICETAK
    function kpSelesai() {
        if (!kpPeriksa('selesai')) return;
        var ids = kpIds(['dicetak']);
        if (!ids.length) return;
        if (!confirm('Tandai ' + ids.length + ' baris sebagai SELESAI? Nama dan waktu akan tercatat dan tidak bisa diubah.')) return;
        $('#kp_btn_selesai').prop('disabled', true);
        kpKirim('qc_return/kesalahan_picker_selesai', ids);
    }

    // Buka halaman cetak serah terima (tab baru, form POST) untuk baris yang sudah diambil
    function kpCetakSerahTerima() {
        if (!kpPeriksa('cetak')) return;
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
    .kp-root {
        --kp-teks: #1e293b;
        --kp-redup: #64748b;
        --kp-garis: #e2e8f0;
        --kp-latar-th: #f1f5f9;
        --kp-biru: #1b6ef3;
        --kp-biru-gelap: #1556c0;
        --kp-amber: #b45309;
        --kp-amber-gelap: #92400e;
        --kp-hijau: #15803d;
        --kp-hijau-gelap: #116632;
        --kp-merah: #be123c;
        --kp-ungu: #6d28d9;
        color: var(--kp-teks);
    }

    /* ===== Toolbar ===== */
    .kp-root .kp-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px 20px;
        margin-bottom: 14px;
    }

    .kp-root .kp-filter {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .kp-root .kp-chips {
        display: inline-flex;
        background: var(--kp-latar-th);
        border-radius: 999px;
        padding: 3px;
        max-width: 100%;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .kp-root .kp-chips::-webkit-scrollbar {
        display: none;
    }

    .kp-root .kp-chip {
        border: 0;
        background: transparent;
        color: var(--kp-redup);
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.2;
        padding: 7px 14px;
        border-radius: 999px;
        cursor: pointer;
        white-space: nowrap;
        transition: background .15s, color .15s;
    }

    .kp-root .kp-chip:hover {
        color: var(--kp-biru);
    }

    .kp-root .kp-chip.aktif {
        background: #fff;
        color: var(--kp-biru);
        box-shadow: 0 1px 3px rgba(15, 23, 42, .18);
    }

    .kp-root .kp-tanggal {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 34px;
        padding: 0 14px;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        color: var(--kp-teks);
        cursor: pointer;
        white-space: nowrap;
        transition: border-color .15s, box-shadow .15s;
    }

    .kp-root .kp-tanggal:hover,
    .kp-root .kp-tanggal:focus-visible {
        border-color: var(--kp-biru);
        box-shadow: 0 0 0 3px rgba(27, 110, 243, .15);
        outline: 0;
    }

    .kp-root .kp-tanggal .fa {
        color: var(--kp-biru);
    }

    /* ===== Tombol aksi: warna tombol = warna status yang dihasilkan ===== */
    .kp-root .kp-aksi {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .kp-root .kp-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 36px;
        padding: 0 16px;
        border: 0;
        border-radius: 8px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        color: #fff;
        cursor: pointer;
        white-space: nowrap;
        transition: background .15s, box-shadow .15s;
    }

    .kp-root .kp-btn .fa {
        font-size: 14px;
        color: inherit;
    }

    .kp-root .kp-btn-diambil { background: var(--kp-biru); }
    .kp-root .kp-btn-diambil:hover:not(:disabled) { background: var(--kp-biru-gelap); }
    .kp-root .kp-btn-cetak { background: var(--kp-amber); }
    .kp-root .kp-btn-cetak:hover:not(:disabled) { background: var(--kp-amber-gelap); }
    .kp-root .kp-btn-selesai { background: var(--kp-hijau); }
    .kp-root .kp-btn-selesai:hover:not(:disabled) { background: var(--kp-hijau-gelap); }

    .kp-root .kp-btn:focus-visible {
        outline: 0;
        box-shadow: 0 0 0 3px rgba(27, 110, 243, .35);
    }

    .kp-root .kp-btn:disabled {
        background: #e2e8f0;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .kp-root .kp-n {
        min-width: 20px;
        height: 20px;
        line-height: 20px;
        padding: 0 6px;
        border-radius: 10px;
        background: rgba(255, 255, 255, .25);
        font-size: 12px;
        text-align: center;
    }

    .kp-root .kp-n:empty {
        display: none;
    }

    /* ===== Info jumlah ===== */
    .kp-root .kp-info {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
        font-size: 13px;
        color: var(--kp-redup);
    }

    .kp-root .kp-dipilih {
        color: var(--kp-biru);
        font-weight: 600;
    }

    /* ===== Tabel ===== */
    .kp-root .kp-scroll {
        max-height: calc(100vh - 250px);
        min-height: 220px;
        overflow: auto;
        border: 1px solid var(--kp-garis);
        border-radius: 10px;
    }

    .kp-root .kp-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
        line-height: 1.35;
    }

    .kp-root .kp-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--kp-latar-th);
        color: #475569;
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        text-align: left;
        padding: 10px 9px;
        border-bottom: 1px solid var(--kp-garis);
        white-space: nowrap;
    }

    .kp-root .kp-table td {
        padding: 10px 9px;
        vertical-align: top;
        border-bottom: 1px solid var(--kp-garis);
        background: #fff;
    }

    .kp-root .kp-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .kp-root .kp-table tbody tr:not(.kp-kosong):hover td {
        background: #f8fafc;
    }

    .kp-root .kp-table tbody tr.kp-dipilih-baris td {
        background: #eff6ff;
    }

    .kp-root .kp-table tbody tr.kp-tahap-selesai td {
        color: #475569;
    }

    .kp-root .kp-c-cek {
        width: 36px;
        text-align: center;
        padding-left: 10px;
        padding-right: 4px;
    }

    .kp-root .kp-c-no {
        width: 40px;
        color: var(--kp-redup);
    }

    .kp-root .kp-c-qty {
        text-align: center;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .kp-root .kp-cek,
    .kp-root #kp_pilih_semua {
        width: 16px;
        height: 16px;
        margin: 0;
        cursor: pointer;
        accent-color: var(--kp-biru);
        vertical-align: middle;
    }

    .kp-root .kp-cek:disabled {
        cursor: not-allowed;
        opacity: .4;
    }

    .kp-root .kp-nama,
    .kp-root .kp-sub,
    .kp-root .kp-log {
        display: block;
    }

    .kp-root .kp-nama {
        font-weight: 600;
        white-space: nowrap;
    }

    .kp-root .kp-sub {
        font-size: 12px;
        color: var(--kp-redup);
        white-space: nowrap;
    }

    .kp-root .kp-redup {
        color: var(--kp-redup);
    }

    .kp-root .kp-resi,
    .kp-root .kp-sku {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        word-break: keep-all;
    }

    .kp-root .kp-sku {
        font-weight: 600;
    }

    .kp-root .kp-jenis {
        display: inline-block;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .2px;
        white-space: nowrap;
    }

    .kp-root .kp-j-salah { color: var(--kp-merah); }
    .kp-root .kp-j-lebih { color: var(--kp-ungu); }

    /* ===== Badge status ===== */
    .kp-root .kp-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: .2px;
        white-space: nowrap;
    }

    .kp-root .kp-badge .fa {
        font-size: 12px;
        color: inherit;
    }

    .kp-root .kp-b-belum { background: #f1f5f9; color: #475569; }
    .kp-root .kp-b-diambil { background: #dbeafe; color: #1d4ed8; }
    .kp-root .kp-b-cetak { background: #fef3c7; color: #92400e; }
    .kp-root .kp-b-selesai { background: #dcfce7; color: #166534; }

    .kp-root .kp-log {
        margin-top: 4px;
        font-size: 12px;
        color: var(--kp-redup);
        white-space: normal;
        max-width: 190px;
    }

    .kp-root .kp-kosong td {
        padding: 48px 12px;
        text-align: center;
        color: var(--kp-redup);
        font-size: 14px;
    }

    .kp-root .kp-kosong .fa {
        display: block;
        margin-bottom: 8px;
        font-size: 28px;
        color: #cbd5e1;
    }

    .kp-root .kp-galat td,
    .kp-root .kp-galat .fa {
        color: var(--kp-merah);
    }

    /* ===== Tablet ===== */
    @media (max-width: 991px) {
        .kp-root .kp-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .kp-root .kp-scroll {
            max-height: none;
        }
    }

    /* ===== Ponsel: tiap baris jadi kartu, status selalu terlihat ===== */
    @media (max-width: 640px) {
        .kp-root .kp-aksi {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .kp-root .kp-btn {
            height: 42px;
            font-size: 14px;
        }

        .kp-root .kp-scroll {
            border: 0;
            overflow: visible;
        }

        .kp-root .kp-table,
        .kp-root .kp-table tbody {
            display: block;
        }

        .kp-root .kp-table thead {
            display: none;
        }

        .kp-root .kp-table tbody tr:not(.kp-kosong) {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 12px;
            padding: 14px;
            margin-bottom: 10px;
            background: #fff;
            border: 1px solid var(--kp-garis);
            border-radius: 12px;
        }

        .kp-root .kp-table tbody tr.kp-dipilih-baris {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .kp-root .kp-table td {
            display: block;
            padding: 0;
            border: 0;
            background: transparent !important;
            min-width: 0;
        }

        .kp-root .kp-table td[data-label]:not([data-label=""]):not(.kp-c-no)::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 2px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
            color: var(--kp-redup);
        }

        .kp-root .kp-c-no {
            display: none !important;
        }

        .kp-root .kp-c-cek {
            position: absolute;
            top: 12px;
            right: 12px;
            width: auto;
            padding: 0;
        }

        .kp-root .kp-cek {
            width: 22px;
            height: 22px;
        }

        .kp-root .kp-c-qty {
            text-align: left;
        }

        .kp-root .kp-c-status {
            grid-column: 1 / -1;
            padding-top: 10px !important;
            border-top: 1px solid var(--kp-garis) !important;
        }

        .kp-root .kp-nama,
        .kp-root .kp-sub,
        .kp-root .kp-log,
        .kp-root .kp-resi {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .kp-root .kp-kosong,
        .kp-root .kp-kosong td {
            display: block;
        }
    }
    /* ===== Tombol aktif tapi urutan belum sesuai: penanda "!" kuning, klik menampilkan pop up ===== */
    .kp-root .kp-perlu-cek .kp-n {
        background: #fbbf24;
        color: #78350f;
    }

    .kp-root .kp-btn-sekunder {
        background: #fff;
        color: var(--kp-teks);
        box-shadow: inset 0 0 0 1px #cbd5e1;
    }

    .kp-root .kp-btn-sekunder:hover:not(:disabled) {
        background: #f1f5f9;
    }

    /* ===== Pop up urutan status ===== */
    .kp-modal .modal-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--kp-teks);
    }

    .kp-modal .modal-title .fa {
        color: #d97706;
        margin-right: 6px;
    }

    .kp-modal .modal-body {
        font-size: 14px;
        line-height: 1.5;
        color: var(--kp-teks);
    }

    .kp-modal .modal-footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .kp-modal .kp-stepper {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin: 12px 0 16px;
    }

    .kp-modal .kp-step {
        position: relative;
        padding: 10px 8px;
        border: 1px solid var(--kp-garis);
        border-radius: 10px;
        background: #f8fafc;
        text-align: center;
        font-size: 12px;
        line-height: 1.3;
        color: var(--kp-redup);
    }

    .kp-modal .kp-step-no {
        display: block;
        width: 22px;
        height: 22px;
        margin: 0 auto 4px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        line-height: 22px;
    }

    .kp-modal .kp-step-nama {
        display: block;
        font-weight: 600;
        color: var(--kp-teks);
    }

    .kp-modal .kp-step-info {
        display: block;
        margin-top: 4px;
        font-size: 11px;
        font-weight: 700;
    }

    .kp-modal .kp-step-perlu {
        border-color: var(--kp-hijau);
        background: #f0fdf4;
    }

    .kp-modal .kp-step-perlu .kp-step-no {
        background: var(--kp-hijau);
        color: #fff;
    }

    .kp-modal .kp-step-perlu .kp-step-info {
        color: var(--kp-hijau);
    }

    .kp-modal .kp-step-posisi {
        border-color: #d97706;
        background: #fffbeb;
    }

    .kp-modal .kp-step-posisi .kp-step-no {
        background: #d97706;
        color: #fff;
    }

    .kp-modal .kp-step-posisi .kp-step-info {
        color: #b45309;
    }

    .kp-modal .kp-popup-sub {
        margin: 0 0 6px;
        font-weight: 700;
    }

    .kp-modal .kp-popup-daftar {
        margin: 0 0 12px;
        padding: 0;
        list-style: none;
    }

    .kp-modal .kp-popup-daftar li {
        padding: 6px 0;
        border-bottom: 1px solid var(--kp-garis);
    }

    .kp-modal .kp-popup-daftar li:last-child {
        border-bottom: 0;
    }

    .kp-modal .kp-popup-catatan {
        margin: 0;
        font-size: 13px;
        color: var(--kp-redup);
    }

    @media (max-width: 520px) {
        .kp-modal .kp-stepper {
            grid-template-columns: 1fr 1fr;
        }

        .kp-modal .modal-footer .kp-btn {
            flex: 1 1 100%;
            height: 42px;
        }
    }

    /* ===== Layar sempit (< 1060px lebar konten): toolbar bertumpuk ===== */
    @media (max-width: 991px) {
        .kp-root .kp-bar {
            flex-direction: column;
            align-items: stretch;
        }
    }

    @media (max-width: 640px) {
        .kp-root .kp-aksi {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .kp-root .kp-btn {
            height: 42px;
            font-size: 14px;
        }
    }

    /* ===== Mode kartu (kelas kp-kartu dipasang JS saat lebar konten < 1060px): status selalu terlihat ===== */
    .kp-root.kp-kartu .kp-scroll {
        max-height: none;
        border: 0;
        overflow: visible;
    }

    .kp-root.kp-kartu .kp-table,
    .kp-root.kp-kartu .kp-table tbody {
        display: block;
    }

    .kp-root.kp-kartu .kp-table thead {
        display: none;
    }

    .kp-root.kp-kartu .kp-table tbody {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 12px;
    }

    .kp-root.kp-kartu .kp-table tbody tr:not(.kp-kosong) {
        position: relative;
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-content: start;
        gap: 10px 12px;
        padding: 14px;
        background: #fff;
        border: 1px solid var(--kp-garis);
        border-radius: 12px;
    }

    .kp-root.kp-kartu .kp-table tbody tr.kp-kosong {
        grid-column: 1 / -1;
    }

    .kp-root.kp-kartu .kp-table tbody tr.kp-dipilih-baris {
        background: #eff6ff;
        border-color: #93c5fd;
    }

    .kp-root.kp-kartu .kp-table td {
        display: block;
        padding: 0;
        border: 0;
        background: transparent !important;
        min-width: 0;
    }

    .kp-root.kp-kartu .kp-table td[data-label]:not([data-label=""]):not(.kp-c-no)::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 2px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: var(--kp-redup);
    }

    .kp-root.kp-kartu .kp-c-no {
        display: none !important;
    }

    .kp-root.kp-kartu .kp-c-cek {
        position: absolute;
        top: 12px;
        right: 12px;
        width: auto;
        padding: 0;
    }

    .kp-root.kp-kartu .kp-cek {
        width: 22px;
        height: 22px;
    }

    .kp-root.kp-kartu .kp-c-qty {
        text-align: left;
    }

    .kp-root.kp-kartu .kp-c-status {
        grid-column: 1 / -1;
        padding-top: 10px !important;
        border-top: 1px solid var(--kp-garis) !important;
    }

    .kp-root.kp-kartu .kp-nama,
    .kp-root.kp-kartu .kp-sub,
    .kp-root.kp-kartu .kp-log,
    .kp-root.kp-kartu .kp-resi {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .kp-root.kp-kartu .kp-kosong td {
        display: block;
    }
</style>
