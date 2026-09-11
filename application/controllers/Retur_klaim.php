<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Klaim retur ke kurir / marketplace.
 *
 * Dua menu, sengaja dipisah supaya pengaju bukan verifikator:
 *   - retur-klaim/pengajuan  (TIM ACCOUNTING) — ajukan klaim, catat putusan & pergantian
 *   - retur-klaim/verifikasi (TIM FINANCE)    — verifikasi pergantian benar diterima
 *
 * Daftar kandidat klaim dihitung live dari Retur_fcd (rekap proses retur);
 * baris di tblreturklaim baru dibuat saat klaim benar-benar diajukan.
 */
class Retur_klaim extends MY_Controller
{
    const URI_PENGAJUAN  = 'retur-klaim/pengajuan';
    const URI_VERIFIKASI = 'retur-klaim/verifikasi';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('retur_fcd');
        $this->load->model('retur_klaim_fcd');
    }

    // ---------------------------------------------------------------- utilitas

    /**
     * Cek apakah role user punya akses ke sebuah menu.
     * Sidebar sudah menyaring per-role, tapi endpoint POST tetap harus dijaga
     * sendiri — tanpa ini petugas accounting bisa menembak endpoint verifikasi.
     */
    private function _has_menu($uri)
    {
        $role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
        if (empty($role)) return false;

        $menu = $this->db->get_where('menu', array('uri' => $uri))->row();
        if (!$menu) return false;

        return $this->db->where(array('menuid' => $menu->id, 'roleid' => $role))
            ->count_all_results('roleaccess') > 0;
    }

    private function _require($uri)
    {
        if (!$this->_has_menu($uri)) {
            $this->make_ajax_response(403, 'Anda tidak punya akses untuk tindakan ini.');
        }
    }

    /** Rentang tanggal retur; default bulan lalu s/d hari ini. */
    private function _range()
    {
        $start = $this->input->post('start_date') ?: $this->input->get('start_date');
        $end   = $this->input->post('end_date')   ?: $this->input->get('end_date');

        if (empty($start) || empty($end)) {
            $start = date('Y-m-01 00:00:00', strtotime('-2 month'));
            $end   = date('Y-m-d 23:59:59');
        }

        return array($start, $end);
    }

    private function _post_order($valid_columns, $default)
    {
        $order = $this->input->post('order');
        $col   = null;
        $dir   = 'desc';

        if (!empty($order)) {
            foreach ($order as $o) {
                $col = (int) $o['column'];
                $dir = $o['dir'];
            }
        }

        return array(
            isset($valid_columns[$col]) && $valid_columns[$col] ? $valid_columns[$col] : $default,
            $dir,
        );
    }

    private function _search()
    {
        $s = $this->input->post('search');
        return is_array($s) ? (isset($s['value']) ? $s['value'] : '') : $s;
    }

    // =================================================================
    // MENU PENGAJUAN (TIM ACCOUNTING)
    // =================================================================

    public function pengajuan()
    {
        $data['jenis_klaim']  = $this->retur_klaim_fcd->jenis_valid;
        $data['bentuk_ganti'] = $this->retur_klaim_fcd->bentuk_valid;
        $data['sla']          = $this->retur_fcd->rekap_sla();

        $this->show($data, 'retur_klaim/pengajuan');
    }

    /** Kandidat klaim: bermasalah / hilang yang belum pernah diajukan. */
    public function get_kandidat()
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');

        list($start, $end) = $this->_range();
        $sla = $this->retur_fcd->rekap_sla();

        $valid = array(
            0 => null, 1 => 'tanggal_retur', 2 => 'no_resi', 3 => 'nama_toko',
            4 => 'kurir', 5 => 'qty_jubelio', 6 => 'qty_buka', 7 => 'nilai',
            8 => 'umur_hari', 9 => 'bucket',
        );
        list($order, $dir) = $this->_post_order($valid, 'nilai');

        $params = array(
            'bucket' => $this->input->post('bucket'),
            'kurir'  => $this->input->post('kurir'),
            'search' => $this->_search(),
            'start'  => intval($this->input->post('start')),
            'length' => intval($this->input->post('length')),
            'order'  => $order,
            'dir'    => $dir,
        );

        $rows  = $this->retur_fcd->rekap_kandidat_klaim($start, $end, $sla, $params);
        $tot   = $this->retur_fcd->rekap_kandidat_total($start, $end, $sla, $params);
        $total = $tot ? (int) $tot->n : 0;

        $label = array(
            'BERMASALAH'  => array('Isi Bermasalah', '#f97316'),
            'LAYAK_KLAIM' => array('Layak Klaim',    '#dc2626'),
            'HANGUS'      => array('Hangus',         '#7f1d1d'),
        );

        $data = array();
        $no = $params['start'] + 1;
        foreach ($rows as $r) {
            $meta   = isset($label[$r->bucket]) ? $label[$r->bucket] : array($r->bucket, '#64748b');
            $kurang = max(0, (int) $r->qty_jubelio - (int) $r->qty_buka);

            $data[] = array(
                '<input type="checkbox" class="kd-chk" value="' . htmlspecialchars($r->no_resi, ENT_QUOTES, 'UTF-8') . '"'
                    . ' data-nilai="' . (float) $r->nilai . '">',
                $r->tanggal_retur ? date('d/m/Y', strtotime($r->tanggal_retur)) : '-',
                htmlspecialchars($r->no_resi, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars(trim(($r->marketplace ?: '') . ' ' . ($r->nama_toko ?: '')) ?: '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($r->kurir ?: '-', ENT_QUOTES, 'UTF-8'),
                (int) $r->qty_jubelio,
                (int) $r->qty_buka,
                $kurang > 0 ? '<b class="text-danger">' . $kurang . '</b>' : '0',
                'Rp ' . number_format((float) $r->nilai, 0, ',', '.'),
                (int) $r->umur_hari . ' hari',
                '<span class="rk-badge" style="background:' . $meta[1] . '">' . $meta[0] . '</span>',
            );
            $no++;
        }

        echo json_encode(array(
            'draw'            => intval($this->input->post('draw')),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
            'nilai_total'     => $tot ? (float) $tot->nilai : 0,
        ));
        exit();
    }

    /** Simpan pengajuan klaim untuk satu atau banyak resi sekaligus. */
    public function ajukan()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $this->_require(self::URI_PENGAJUAN);

        $resi_list = $this->input->post('resi');
        if (empty($resi_list) || !is_array($resi_list)) {
            $this->make_ajax_response(400, 'Tidak ada resi yang dipilih.');
        }
        if (count($resi_list) > 500) {
            $this->make_ajax_response(400, 'Maksimal 500 resi per pengajuan.');
        }

        $jenis = $this->input->post('jenis_klaim');
        if (!empty($jenis) && !in_array($jenis, $this->retur_klaim_fcd->jenis_valid, true)) {
            $this->make_ajax_response(400, 'Jenis klaim tidak dikenal.');
        }

        list($start, $end) = $this->_range();
        $sla = $this->retur_fcd->rekap_sla();

        // Ambil ulang datanya dari server — jangan percaya nilai dari browser.
        $rows = $this->retur_fcd->rekap_kandidat_by_resi($start, $end, $sla, $resi_list);
        if (empty($rows)) {
            $this->make_ajax_response(404, 'Resi tidak ditemukan pada rentang tanggal ini.');
        }

        $meta = array(
            'no_tiket'    => $this->input->post('no_tiket'),
            'jenis_klaim' => $jenis,
            'catatan'     => $this->input->post('catatan'),
        );

        $res = $this->retur_klaim_fcd->save_ajukan($rows, $meta, $this->data['user']['id_user']);

        $msg = $res['inserted'] . ' klaim berhasil diajukan.';
        if (!empty($res['skipped'])) {
            $msg .= ' ' . count($res['skipped']) . ' resi dilewati karena sudah pernah diklaim ('
                . implode(', ', array_slice($res['skipped'], 0, 5))
                . (count($res['skipped']) > 5 ? ', dst.' : '') . ').';
        }

        $this->make_ajax_response(201, $msg, $res);
    }

    /** Daftar klaim yang sedang berjalan (tab 2 menu pengajuan). */
    public function get_klaim_data()
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');

        $valid = array(
            0 => null, 1 => 'k.tanggal_ajuan', 2 => 'k.no_resi', 3 => 'k.no_tiket',
            4 => 'k.kurir', 5 => 'k.jenis_klaim', 6 => 'k.nilai_klaim',
            7 => 'k.nominal_diterima', 8 => 'k.status_klaim', 9 => 'k.finance_verified',
        );
        list($order, $dir) = $this->_post_order($valid, 'k.tanggal_ajuan');

        $params = array(
            'status'           => $this->input->post('status'),
            'kurir'            => $this->input->post('kurir'),
            'search'           => $this->_search(),
            'start'            => intval($this->input->post('start')),
            'length'           => intval($this->input->post('length')),
            'order'            => $order,
            'dir'              => $dir,
            'siap_verifikasi'  => $this->input->post('siap_verifikasi') ? 1 : 0,
            'finance_verified' => $this->input->post('finance_verified'),
        );

        $rows = $this->retur_klaim_fcd->get_klaim_list($params);
        $tot  = $this->retur_klaim_fcd->get_klaim_total($params);

        $bisa_verifikasi = $this->_has_menu(self::URI_VERIFIKASI);
        $bisa_ajukan     = $this->_has_menu(self::URI_PENGAJUAN);

        $data = array();
        foreach ($rows as $r) {
            $data[] = array(
                $this->_tombol_aksi($r, $bisa_ajukan, $bisa_verifikasi),
                $r->tanggal_ajuan ? date('d/m/Y', strtotime($r->tanggal_ajuan)) : '-',
                htmlspecialchars($r->no_resi, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($r->no_tiket ?: '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($r->kurir ?: '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars(str_replace('_', ' ', $r->jenis_klaim), ENT_QUOTES, 'UTF-8'),
                'Rp ' . number_format((float) $r->nilai_klaim, 0, ',', '.'),
                $r->nominal_diterima === null ? '-' : 'Rp ' . number_format((float) $r->nominal_diterima, 0, ',', '.'),
                $this->_badge_status($r->status_klaim, (int) $r->umur_ajuan),
                $r->finance_verified
                    ? '<span class="rk-badge" style="background:#16a34a">Terverifikasi</span><br><small>'
                        . htmlspecialchars($r->nama_finance ?: '', ENT_QUOTES, 'UTF-8')
                        . ($r->finance_at ? ' · ' . date('d/m H:i', strtotime($r->finance_at)) : '') . '</small>'
                    : '<span class="text-muted">-</span>',
            );
        }

        echo json_encode(array(
            'draw'            => intval($this->input->post('draw')),
            'recordsTotal'    => $tot ? (int) $tot->n : 0,
            'recordsFiltered' => $tot ? (int) $tot->n : 0,
            'data'            => $data,
            'nilai_klaim'     => $tot ? (float) $tot->nilai_klaim : 0,
            'nilai_diterima'  => $tot ? (float) $tot->nilai_diterima : 0,
        ));
        exit();
    }

    private function _badge_status($status, $umur = 0)
    {
        $warna = array(
            'DIAJUKAN'  => '#3b82f6',
            'DISETUJUI' => '#0ea5e9',
            'SEBAGIAN'  => '#f59e0b',
            'DITOLAK'   => '#dc2626',
            'DIGANTI'   => '#8b5cf6',
            'SELESAI'   => '#16a34a',
            'BATAL'     => '#64748b',
        );
        $c = isset($warna[$status]) ? $warna[$status] : '#64748b';

        $html = '<span class="rk-badge" style="background:' . $c . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';

        // Klaim yang menggantung tanpa putusan perlu ditagih balik ke kurir.
        if ($status === 'DIAJUKAN' && $umur > 14) {
            $html .= '<br><small class="text-danger">menunggu ' . $umur . ' hari</small>';
        }

        return $html;
    }

    private function _tombol_aksi($r, $bisa_ajukan, $bisa_verifikasi)
    {
        $id  = (int) $r->id_klaim;
        $btn = array();

        if ($bisa_ajukan && !in_array($r->status_klaim, $this->retur_klaim_fcd->status_final, true)) {
            $btn[] = '<button class="btn btn-xs btn-primary btn-putusan" data-id="' . $id . '" title="Catat putusan kurir">'
                . '<i class="fa fa-gavel"></i></button>';

            if (in_array($r->status_klaim, array('DISETUJUI', 'SEBAGIAN'), true)) {
                $btn[] = '<button class="btn btn-xs btn-success btn-ganti" data-id="' . $id . '" title="Catat pergantian diterima">'
                    . '<i class="fa fa-money"></i></button>';
            }

            $btn[] = '<button class="btn btn-xs btn-default btn-batal" data-id="' . $id . '" title="Batalkan klaim">'
                . '<i class="fa fa-times"></i></button>';
        }

        if ($bisa_verifikasi && $r->status_klaim === 'DIGANTI') {
            $btn[] = '<button class="btn btn-xs btn-warning btn-verif" data-id="' . $id . '" title="Verifikasi finance">'
                . '<i class="fa fa-check"></i></button>';
        }

        return $btn ? implode(' ', $btn) : '<span class="text-muted">-</span>';
    }

    public function simpan_putusan()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $this->_require(self::URI_PENGAJUAN);

        $id     = (int) $this->input->post('id_klaim');
        $status = $this->input->post('status_klaim');

        $klaim = $this->retur_klaim_fcd->get_klaim($id);
        if (!$klaim) {
            $this->make_ajax_response(404, 'Klaim tidak ditemukan.');
        }
        if (in_array($klaim->status_klaim, $this->retur_klaim_fcd->status_final, true)) {
            $this->make_ajax_response(409, 'Klaim sudah ' . $klaim->status_klaim . ' dan tidak bisa diubah.');
        }
        if (!in_array($status, array('DISETUJUI', 'SEBAGIAN', 'DITOLAK'), true)) {
            $this->make_ajax_response(400, 'Putusan harus DISETUJUI, SEBAGIAN, atau DITOLAK.');
        }

        $nominal = $this->input->post('nominal_disetujui');
        if ($status === 'DITOLAK') {
            $nominal = 0;
            if (trim((string) $this->input->post('alasan_tolak')) === '') {
                $this->make_ajax_response(400, 'Alasan penolakan wajib diisi.');
            }
        } elseif ($nominal === null || $nominal === '' || (float) $nominal <= 0) {
            $this->make_ajax_response(400, 'Nominal yang disetujui wajib diisi.');
        }

        $ok = $this->retur_klaim_fcd->update_putusan(
            $id, $status,
            $this->input->post('tanggal_putusan') ?: date('Y-m-d H:i:s'),
            $nominal,
            $this->input->post('alasan_tolak'),
            $this->data['user']['id_user']
        );

        $this->make_ajax_response($ok ? 200 : 500, $ok ? 'Putusan tersimpan.' : 'Gagal menyimpan putusan.');
    }

    public function simpan_pergantian()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $this->_require(self::URI_PENGAJUAN);

        $id     = (int) $this->input->post('id_klaim');
        $bentuk = $this->input->post('bentuk_pergantian');

        $klaim = $this->retur_klaim_fcd->get_klaim($id);
        if (!$klaim) {
            $this->make_ajax_response(404, 'Klaim tidak ditemukan.');
        }
        if (!in_array($klaim->status_klaim, array('DISETUJUI', 'SEBAGIAN'), true)) {
            $this->make_ajax_response(409, 'Pergantian hanya bisa dicatat untuk klaim yang sudah DISETUJUI / SEBAGIAN.');
        }
        if (!in_array($bentuk, $this->retur_klaim_fcd->bentuk_valid, true)) {
            $this->make_ajax_response(400, 'Bentuk pergantian tidak dikenal.');
        }

        $nominal = $this->input->post('nominal_diterima');
        if ($bentuk !== 'BARANG' && ($nominal === null || $nominal === '' || (float) $nominal <= 0)) {
            $this->make_ajax_response(400, 'Nominal yang diterima wajib diisi.');
        }

        $ok = $this->retur_klaim_fcd->update_pergantian(
            $id, $bentuk,
            $this->input->post('tanggal_pergantian') ?: date('Y-m-d H:i:s'),
            $nominal,
            $this->input->post('bukti'),
            $this->data['user']['id_user']
        );

        $this->make_ajax_response($ok ? 200 : 500, $ok ? 'Pergantian tercatat, menunggu verifikasi finance.' : 'Gagal menyimpan.');
    }

    public function batal()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $this->_require(self::URI_PENGAJUAN);

        $id     = (int) $this->input->post('id_klaim');
        $alasan = trim((string) $this->input->post('alasan'));

        if ($alasan === '') {
            $this->make_ajax_response(400, 'Alasan pembatalan wajib diisi.');
        }

        $klaim = $this->retur_klaim_fcd->get_klaim($id);
        if (!$klaim) {
            $this->make_ajax_response(404, 'Klaim tidak ditemukan.');
        }
        if ($klaim->finance_verified) {
            $this->make_ajax_response(409, 'Klaim sudah diverifikasi finance. Minta finance mencabut verifikasi lebih dulu.');
        }

        $ok = $this->retur_klaim_fcd->batal_klaim($id, $alasan, $this->data['user']['id_user']);

        $this->make_ajax_response($ok ? 200 : 500, $ok ? 'Klaim dibatalkan.' : 'Gagal membatalkan klaim.');
    }

    // =================================================================
    // MENU VERIFIKASI (TIM FINANCE)
    // =================================================================

    public function verifikasi()
    {
        $this->show(null, 'retur_klaim/verifikasi');
    }

    public function simpan_verifikasi()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $this->_require(self::URI_VERIFIKASI);

        $id       = (int) $this->input->post('id_klaim');
        $verified = $this->input->post('verified') ? 1 : 0;

        $klaim = $this->retur_klaim_fcd->get_klaim($id);
        if (!$klaim) {
            $this->make_ajax_response(404, 'Klaim tidak ditemukan.');
        }
        if ($verified && $klaim->status_klaim !== 'DIGANTI') {
            $this->make_ajax_response(409, 'Hanya klaim berstatus DIGANTI yang bisa diverifikasi.');
        }
        if ($verified && ($klaim->nominal_diterima === null && $klaim->bentuk_pergantian !== 'BARANG')) {
            $this->make_ajax_response(409, 'Nominal pergantian belum dicatat.');
        }

        $ok = $this->retur_klaim_fcd->verifikasi_finance(
            $id, $verified, $this->input->post('finance_catatan'), $this->data['user']['id_user']
        );

        $this->make_ajax_response(
            $ok ? 200 : 500,
            $ok ? ($verified ? 'Klaim diverifikasi dan ditutup.' : 'Verifikasi dicabut.') : 'Gagal menyimpan.'
        );
    }

    // =================================================================
    // RINGKASAN (dipakai kedua menu + dashboard Rekap Proses Retur)
    // =================================================================

    public function get_stats()
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');

        $start = $this->input->post('start_date');
        $end   = $this->input->post('end_date');
        $kurir = $this->input->post('kurir');

        $per_status = array();
        $total_klaim = 0;
        $nilai_klaim = 0;
        $nilai_diterima = 0;

        foreach ($this->retur_klaim_fcd->stats($start, $end, $kurir) as $s) {
            $per_status[$s->status_klaim] = array(
                'n'         => (int) $s->n,
                'nilai'     => (float) $s->nilai,
                'diterima'  => (float) $s->diterima,
            );
            $total_klaim    += (int) $s->n;
            $nilai_klaim    += (float) $s->nilai;
            $nilai_diterima += (float) $s->diterima;
        }

        echo json_encode(array(
            'per_status'     => $per_status,
            'total_klaim'    => $total_klaim,
            'nilai_klaim'    => $nilai_klaim,
            'nilai_diterima' => $nilai_diterima,
            'recovery_rate'  => $nilai_klaim > 0 ? round($nilai_diterima / $nilai_klaim * 100, 1) : 0,
            'by_kurir'       => $this->retur_klaim_fcd->recovery_by_kurir($start, $end),
            'kurir_list'     => array_map(function ($k) { return $k->kurir; }, $this->retur_klaim_fcd->kurir_list()),
            'alarm'          => $this->retur_klaim_fcd->alarm_count(),
        ));
        exit();
    }

    /** Daftar klaim yang barangnya ternyata datang belakangan. */
    public function get_alarm()
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');

        $rows = $this->retur_klaim_fcd->alarm_barang_datang(100);

        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'id_klaim'   => (int) $r->id_klaim,
                'no_resi'    => $r->no_resi,
                'kurir'      => $r->kurir,
                'status'     => $r->status_klaim,
                'nilai'      => (float) $r->nilai_klaim,
                'diterima'   => $r->nominal_diterima === null ? null : (float) $r->nominal_diterima,
                'verified'   => (int) $r->finance_verified,
                'tgl_datang' => $r->tanggal_datang,
            );
        }

        echo json_encode(array('data' => $out));
        exit();
    }
}
