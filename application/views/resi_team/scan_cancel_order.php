<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ── Scan Cek Cancel Styles ── */
.sc-header {
    background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 60%, #f97316 100%);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 20px;
    color: #fff;
    box-shadow: 0 8px 32px rgba(220,38,38,0.22);
    position: relative;
    overflow: hidden;
}
.sc-header::after {
    content: '';
    position: absolute;
    right: -40px; top: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
}
.sc-header .header-title { font-size: 21px; font-weight: 800; letter-spacing: 0.5px; font-family: 'Outfit', sans-serif; }
.sc-header .header-sub { font-size: 13px; opacity: 0.85; margin-top: 4px; }

.sc-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); padding: 20px 22px; margin-bottom: 18px; }
.sc-card h4 { margin: 0 0 14px 0; font-size: 15px; font-weight: 700; color: #1e293b; }

.sc-input {
    height: 56px !important;
    font-size: 21px !important;
    font-family: 'Courier New', monospace;
    font-weight: 700;
    text-align: center;
    letter-spacing: 1px;
    border-radius: 10px !important;
    border: 2.5px solid #dc2626 !important;
    text-transform: uppercase;
}
.sc-input:focus { box-shadow: 0 0 0 4px rgba(220,38,38,0.15) !important; outline: none; }

/* Kartu hasil */
.sc-result { border-radius: 14px; padding: 22px 24px; margin-bottom: 18px; display: none; border-left: 8px solid #94a3b8; background: #f8fafc; }
.sc-result.sudah-cancel  { background: #fef2f2; border-left-color: #dc2626; }
.sc-result.cancel-manual { background: #eff6ff; border-left-color: #2563eb; }
.sc-result.belum-cancel  { background: #f0fdf4; border-left-color: #22c55e; }
.sc-result.tidak-ada     { background: #f8fafc; border-left-color: #94a3b8; }

.sc-result .res-verdict { font-size: 26px; font-weight: 800; font-family: 'Outfit', sans-serif; letter-spacing: 0.5px; }
.sc-result.sudah-cancel  .res-verdict { color: #b91c1c; }
.sc-result.cancel-manual .res-verdict { color: #1d4ed8; }
.sc-result.belum-cancel  .res-verdict { color: #15803d; }
.sc-result.tidak-ada     .res-verdict { color: #475569; }
.sc-result .res-resi { font-family: 'Courier New', monospace; font-size: 17px; font-weight: 700; color: #1e293b; margin-top: 2px; }
.sc-result .res-msg { font-size: 13.5px; color: #475569; margin-top: 6px; }

.sc-detail { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
.sc-detail .d-item { background: rgba(255,255,255,0.85); border: 1px solid rgba(0,0,0,0.06); border-radius: 10px; padding: 9px 14px; min-width: 155px; flex: 1; }
.sc-detail .d-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 700; }
.sc-detail .d-value { font-size: 14px; font-weight: 700; color: #1e293b; margin-top: 2px; word-break: break-word; }
.sc-detail .d-value small { display: block; font-weight: 600; color: #64748b; font-size: 12px; font-family: 'Courier New', monospace; }

.sc-manual-box { margin-top: 16px; padding-top: 16px; border-top: 1.5px dashed rgba(0,0,0,0.1); display: none; }
.sc-manual-box label { font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; display: block; }
.sc-manual-box .form-control { height: 36px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-size: 13px; }
.sc-manual-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
.sc-manual-row .fg { display: flex; flex-direction: column; flex: 1; min-width: 180px; }

#tbl-scan-terakhir thead th {
    background: #f1f5f9;
    color: #475569;
    border: none;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 9px 12px;
    white-space: nowrap;
}
#tbl-scan-terakhir tbody td { font-size: 12.5px; padding: 7px 12px; vertical-align: middle; }
.sc-resi-mono { font-family: 'Courier New', monospace; font-weight: 700; font-size: 12px; background: #f8fafc; padding: 2px 6px; border-radius: 4px; }
.sc-badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 10.5px; font-weight: 700; white-space: nowrap; }
.sc-badge-danger  { background: #fee2e2; color: #b91c1c; }
.sc-badge-warning { background: #ffedd5; color: #c2410c; }
.sc-badge-info    { background: #dbeafe; color: #1d4ed8; }
.sc-badge-jubelio { background: #ede9fe; color: #6d28d9; }
.sc-badge-scan    { background: #dcfce7; color: #15803d; }
</style>

<div class="sc-header">
    <div class="header-title"><i class="fa fa-barcode"></i> SCAN CEK CANCEL ORDER</div>
    <div class="header-sub">
        Scan nomor resi untuk mengecek pesanannya sudah cancel atau belum. Resi yang sudah cancel otomatis
        masuk ke Daftar Cancel Order; resi yang belum terbaca cancel di Jubelio bisa ditandai manual di sini.
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="sc-card">
            <h4><i class="fa fa-search" style="color:#dc2626;"></i> Cek Nomor Resi</h4>
            <form id="form-scan-cancel" autocomplete="off">
                <input type="text" id="scan-noresi" class="form-control sc-input" placeholder="SCAN / KETIK NOMOR RESI">
                <div style="margin-top:10px;display:flex;gap:8px;align-items:center;">
                    <button type="submit" class="btn btn-danger" style="border-radius:8px;font-weight:700;padding:7px 20px;">
                        <i class="fa fa-search"></i> Cek Resi
                    </button>
                    <button type="button" id="btn-clear-scan" class="btn btn-default" style="border-radius:8px;">
                        <i class="fa fa-eraser"></i> Bersihkan
                    </button>
                    <small class="text-muted" style="margin-left:auto;">Tekan Enter setelah scan</small>
                </div>
            </form>
        </div>

        <!-- ── Kartu hasil pengecekan ── -->
        <div class="sc-result" id="sc-result">
            <div class="res-verdict" id="res-verdict">&mdash;</div>
            <div class="res-resi" id="res-resi">&mdash;</div>
            <div class="res-msg" id="res-msg"></div>

            <div class="sc-detail">
                <div class="d-item">
                    <div class="d-label">No. Pesanan</div>
                    <div class="d-value" id="res-pesanan">-</div>
                </div>
                <div class="d-item">
                    <div class="d-label">Marketplace</div>
                    <div class="d-value" id="res-marketplace">-</div>
                </div>
                <div class="d-item">
                    <div class="d-label">Status Marketplace</div>
                    <div class="d-value" id="res-status">-</div>
                </div>
                <div class="d-item">
                    <div class="d-label">Tgl &amp; Jam Pesanan</div>
                    <div class="d-value" id="res-tgl-pesan">-</div>
                </div>
                <div class="d-item">
                    <div class="d-label">Tgl &amp; Jam Cancel</div>
                    <div class="d-value" id="res-tgl-cancel">-</div>
                </div>
            </div>

            <!-- ── Input manual: resi cancel yang belum kebaca Jubelio ── -->
            <div class="sc-manual-box" id="sc-manual-box">
                <div style="font-size:12.5px;color:#475569;margin-bottom:10px;">
                    <i class="fa fa-info-circle"></i>
                    Kalau marketplace sudah membatalkan pesanan ini tapi Jubelio belum memperbarui statusnya,
                    tandai manual di bawah ini.
                </div>
                <div class="sc-manual-row">
                    <div class="fg">
                        <label>Tgl &amp; Jam Cancel</label>
                        <input type="datetime-local" id="manual-tanggal" class="form-control">
                    </div>
                    <div class="fg" style="flex:2;">
                        <label>Catatan (opsional)</label>
                        <input type="text" id="manual-catatan" class="form-control" placeholder="mis. dibatalkan pembeli via chat Shopee">
                    </div>
                    <div class="fg" style="flex:0 0 auto;min-width:0;">
                        <label>&nbsp;</label>
                        <button type="button" id="btn-tandai-cancel" class="btn btn-danger" style="height:36px;border-radius:8px;font-weight:700;padding:0 18px;">
                            <i class="fa fa-ban"></i> Tandai Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="sc-card">
            <h4><i class="fa fa-history" style="color:#dc2626;"></i> Cancel Terakhir Tercatat</h4>
            <div style="max-height:420px;overflow-y:auto;">
                <table class="table table-hover" id="tbl-scan-terakhir" style="width:100%;margin-bottom:0;">
                    <thead>
                        <tr>
                            <th>Resi</th>
                            <th>Status</th>
                            <th>Cancel</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-scan-terakhir">
                        <?php if (empty($scan_terakhir)) : ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding:24px;">Belum ada data</td></tr>
                        <?php else : ?>
                            <?php
                            $kelas_status = [
                                'CANCELED'       => 'sc-badge-danger',
                                'REQUEST_CANCEL' => 'sc-badge-warning',
                                'CANCEL MANUAL'  => 'sc-badge-info',
                            ];
                            foreach ($scan_terakhir as $s) :
                                $st = $s['status_marketplace'] ?: '-';
                                $kl = $kelas_status[$st] ?? 'sc-badge-info';
                                $tc = $s['tanggal_cancel'] ? strtotime($s['tanggal_cancel']) : null;
                            ?>
                                <tr>
                                    <td>
                                        <span class="sc-resi-mono"><?= htmlspecialchars($s['noresi']) ?></span><br>
                                        <small class="text-muted"><?= htmlspecialchars($s['nama_marketplace'] ?: '-') ?></small>
                                    </td>
                                    <td>
                                        <span class="sc-badge <?= $kl ?>"><?= htmlspecialchars($st) ?></span><br>
                                        <span class="sc-badge <?= $s['sumber'] === 'SCAN' ? 'sc-badge-scan' : 'sc-badge-jubelio' ?>" style="margin-top:3px;">
                                            <?= $s['sumber'] === 'SCAN' ? 'MANUAL' : 'JUBELIO' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($tc) : ?>
                                            <?= date('d/m/Y', $tc) ?><br><small class="text-muted"><?= date('H:i:s', $tc) ?></small>
                                        <?php else : ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    var resiTerakhir = '';

    function pesan(msg, type) {
        if (typeof noty === 'function') {
            noty({ text: msg, layout: 'topRight', type: type || 'information', timeout: 4000 });
        }
    }

    function bunyi(id) {
        var el = document.getElementById(id);
        if (el) { try { el.currentTime = 0; el.play(); } catch (e) {} }
    }

    function esc(v) {
        return $('<div>').text(v == null || v === '' ? '-' : v).html();
    }

    /* '2026-07-28 14:05:11' -> '28/07/2026 <small>14:05:11</small>' */
    function tglJam(v) {
        if (!v) return '-';
        var p = String(v).split(' ');
        var d = (p[0] || '').split('-');
        if (d.length !== 3) return esc(v);
        return d[2] + '/' + d[1] + '/' + d[0] + '<small>' + esc(p[1] || '') + '</small>';
    }

    var tampilan = {
        SUDAH_CANCEL: {
            kelas   : 'sudah-cancel',
            verdict : '🚫 SUDAH CANCEL',
            manual  : false,
            audio   : 'audio-cancel'
        },
        CANCEL_MANUAL: {
            kelas   : 'cancel-manual',
            verdict : '📝 CANCEL (INPUT MANUAL)',
            manual  : false,
            audio   : 'audio-cancel'
        },
        BELUM_CANCEL: {
            kelas   : 'belum-cancel',
            verdict : '✅ BELUM CANCEL',
            manual  : true,
            audio   : 'audio-alexis'
        },
        TIDAK_ADA_DI_IRESIS: {
            kelas   : 'tidak-ada',
            verdict : '❓ RESI TIDAK DITEMUKAN',
            manual  : true,
            audio   : 'audio-wrong'
        }
    };

    function tampilkanHasil(info, message) {
        var t = tampilan[info.kondisi] || tampilan.TIDAK_ADA_DI_IRESIS;

        $('#sc-result')
            .removeClass('sudah-cancel cancel-manual belum-cancel tidak-ada')
            .addClass(t.kelas)
            .show();

        $('#res-verdict').text(t.verdict);
        $('#res-resi').text(info.noresi);
        $('#res-msg').text(message || '');

        $('#res-pesanan').html(esc(info.no_pesanan));
        $('#res-marketplace').html(
            esc(info.nama_marketplace) + (info.toko ? '<small>' + esc(info.toko) + '</small>' : '')
        );
        $('#res-status').html(esc(info.status_marketplace));
        $('#res-tgl-pesan').html(tglJam(info.tanggal_pesan));
        $('#res-tgl-cancel').html(tglJam(info.tanggal_cancel));

        // Kotak input manual hanya untuk resi yang belum tercatat cancel
        $('#sc-manual-box').toggle(t.manual && !info.sudah_tercatat);
        $('#manual-catatan').val('');
        $('#manual-tanggal').val('');

        bunyi(t.audio);
    }

    function cekResi(noresi) {
        $.ajax({
            url     : 'resi_team/cek_cancel_order',
            type    : 'post',
            dataType: 'json',
            data    : { noresi: noresi },
            success : function (res) {
                if (res.code !== 200 || !res.data) {
                    pesan(res.message || 'Gagal mengecek resi', 'error');
                    bunyi('audio-wrong');
                    return;
                }
                resiTerakhir = res.data.noresi;
                tampilkanHasil(res.data, res.message);
                if (res.data.kondisi === 'SUDAH_CANCEL') muatRiwayat();
            },
            error   : function () {
                pesan('Gagal menghubungi server', 'error');
                bunyi('audio-wrong');
            }
        });
    }

    $('#form-scan-cancel').on('submit', function (e) {
        e.preventDefault();
        var noresi = $('#scan-noresi').val().trim();
        if (!noresi) return false;
        $('#scan-noresi').val('').focus();
        cekResi(noresi);
        return false;
    });

    $('#btn-clear-scan').on('click', function () {
        $('#sc-result').hide();
        $('#scan-noresi').val('').focus();
    });

    /* ── Tandai cancel manual ── */
    $('#btn-tandai-cancel').on('click', function () {
        if (!resiTerakhir) return;

        var $btn = $(this);
        var html = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url     : 'resi_team/simpan_cancel_order',
            type    : 'post',
            dataType: 'json',
            data    : {
                noresi        : resiTerakhir,
                tanggal_cancel: ($('#manual-tanggal').val() || '').replace('T', ' '),
                catatan       : $('#manual-catatan').val()
            },
            success : function (res) {
                pesan(res.message, (res.code === 200 || res.code === 201) ? 'success' : 'error');
                if (res.code === 200 || res.code === 201) {
                    bunyi('audio-cancel');
                    cekResi(resiTerakhir);
                    muatRiwayat();
                }
            },
            error   : function () { pesan('Gagal menyimpan data', 'error'); },
            complete: function () {
                $btn.prop('disabled', false).html(html);
                $('#scan-noresi').focus();
            }
        });
    });

    /* ── Segarkan tabel riwayat tanpa reload halaman ── */
    var kelasStatus = {
        'CANCELED'      : 'sc-badge-danger',
        'REQUEST_CANCEL': 'sc-badge-warning',
        'CANCEL MANUAL' : 'sc-badge-info'
    };

    function muatRiwayat() {
        $.ajax({
            url     : 'resi_team/get_scan_terakhir_cancel',
            type    : 'post',
            dataType: 'json',
            success : function (res) {
                if (res.code !== 200 || !res.data) return;
                var rows = res.data.rows || [];

                if (!rows.length) {
                    $('#tbody-scan-terakhir').html(
                        '<tr><td colspan="3" class="text-center text-muted" style="padding:24px;">Belum ada data</td></tr>'
                    );
                    return;
                }

                var html = '';
                rows.forEach(function (s) {
                    var st = s.status_marketplace || '-';
                    var kl = kelasStatus[st] || 'sc-badge-info';
                    var tc = (s.tanggal_cancel || '').split(' ');
                    var dd = (tc[0] || '').split('-');
                    var tglHtml = dd.length === 3
                        ? dd[2] + '/' + dd[1] + '/' + dd[0] + '<br><small class="text-muted">' + esc(tc[1] || '') + '</small>'
                        : '<span class="text-muted">-</span>';

                    html += '<tr>'
                        + '<td><span class="sc-resi-mono">' + esc(s.noresi) + '</span><br>'
                        + '<small class="text-muted">' + esc(s.nama_marketplace) + '</small></td>'
                        + '<td><span class="sc-badge ' + kl + '">' + esc(st) + '</span><br>'
                        + '<span class="sc-badge ' + (s.sumber === 'SCAN' ? 'sc-badge-scan' : 'sc-badge-jubelio')
                        + '" style="margin-top:3px;">' + (s.sumber === 'SCAN' ? 'MANUAL' : 'JUBELIO') + '</span></td>'
                        + '<td>' + tglHtml + '</td>'
                        + '</tr>';
                });
                $('#tbody-scan-terakhir').html(html);
            }
        });
    }

    $('#scan-noresi').focus();
});
</script>
