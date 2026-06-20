<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Laporan_fcd extends CI_Model
{

    // ── TOTALAN PICKER ───────────────────────────────────────

    function get_totalan_picker($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT
                p.nama_pegawai,
                COUNT(1) AS total_resi,
                SUM(CASE WHEN pr.tipe_resi = 'satuan' THEN 1 ELSE 0 END) AS satuan,
                SUM(CASE WHEN pr.tipe_resi = 'campuran' THEN 1 ELSE 0 END) AS campuran
            FROM tblresiambilbarang r
            INNER JOIN tblpegawai p ON p.kode_pegawai = r.yangambil_pegawai
            LEFT JOIN tblprintresi pr ON pr.id_printresi = r.id_resi
            WHERE DATE(r.tanggal_resiambilbarang) = ?
            GROUP BY p.kode_pegawai, p.nama_pegawai
            ORDER BY total_resi DESC
        ";

        return $this->db->query($sql, [$tanggal]);
    }

    // ── TOTALAN PACKER ───────────────────────────────────────

    function get_totalan_packer($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT
                u.name AS nama_packer,
                COUNT(1) AS total_resi,
                SUM(CASE WHEN pr.tipe_resi = 'satuan' THEN 1 ELSE 0 END) AS satuan,
                SUM(CASE WHEN pr.tipe_resi = 'campuran' THEN 1 ELSE 0 END) AS campuran
            FROM tblpacking pk
            INNER JOIN tbluser u ON u.id_user = pk.packer_pegawai
            LEFT JOIN tblprintresi pr ON pr.id_printresi = pk.id_resi
            WHERE DATE(pk.tanggal_packing) = ?
            GROUP BY pk.packer_pegawai, u.name
            ORDER BY total_resi DESC
        ";

        return $this->db->query($sql, [$tanggal]);
    }

    // ── SISA RESI BELUM KIRIM ────────────────────────────────

    function get_sisa_resi($tanggal = null, $jam_cutoff = '15:00')
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $batas = $tanggal . ' ' . $jam_cutoff . ':00';

        $sql = "
            SELECT
                k.nama_kurir,
                COUNT(1) AS jumlah_sisa
            FROM tblprintresi pr
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE DATE(pr.tanggal_printresi) = ?
              AND pr.tanggal_printresi <= ?
              AND (pr.batal IS NULL OR pr.batal = '' OR pr.batal = '0')
              AND pr.id_printresi NOT IN (SELECT id_resi FROM tblresikeluar)
            GROUP BY pr.id_kurir, k.nama_kurir
            ORDER BY jumlah_sisa DESC
        ";

        return $this->db->query($sql, [$tanggal, $batas]);
    }

    function get_sisa_resi_detail($tanggal = null, $jam_cutoff = '15:00')
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $batas = $tanggal . ' ' . $jam_cutoff . ':00';

        $sql = "
            SELECT
                pr.noresi,
                k.nama_kurir,
                pr.tanggal_printresi
            FROM tblprintresi pr
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE DATE(pr.tanggal_printresi) = ?
              AND pr.tanggal_printresi <= ?
              AND (pr.batal IS NULL OR pr.batal = '' OR pr.batal = '0')
              AND pr.id_printresi NOT IN (SELECT id_resi FROM tblresikeluar)
            ORDER BY k.nama_kurir, pr.noresi
        ";

        return $this->db->query($sql, [$tanggal, $batas]);
    }

    function get_total_sisa_resi($tanggal = null, $jam_cutoff = '15:00')
    {
        if (!$tanggal) $tanggal = date('Y-m-d');
        $batas = $tanggal . ' ' . $jam_cutoff . ':00';

        $sql = "
            SELECT COUNT(1) AS total
            FROM tblprintresi pr
            WHERE DATE(pr.tanggal_printresi) = ?
              AND pr.tanggal_printresi <= ?
              AND (pr.batal IS NULL OR pr.batal = '' OR pr.batal = '0')
              AND pr.id_printresi NOT IN (SELECT id_resi FROM tblresikeluar)
        ";

        return $this->db->query($sql, [$tanggal, $batas])->row()->total;
    }

    // ── PAKET KELUAR ─────────────────────────────────────────

    function get_paket_keluar($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT
                k.nama_kurir,
                COUNT(1) AS jumlah_paket
            FROM tblresikeluar rk
            INNER JOIN tblprintresi pr ON pr.id_printresi = rk.id_resi
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE DATE(rk.tanggal_resikeluar) = ?
            GROUP BY pr.id_kurir, k.nama_kurir
            ORDER BY jumlah_paket DESC
        ";

        return $this->db->query($sql, [$tanggal]);
    }

    function get_total_paket_keluar($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT COUNT(1) AS total
            FROM tblresikeluar
            WHERE DATE(tanggal_resikeluar) = ?
        ";

        return $this->db->query($sql, [$tanggal])->row()->total;
    }

    // ── REKAP PENCAPAIAN (RANGE TANGGAL) ─────────────────────

    function get_rekap_picker($start_date, $end_date)
    {
        $sql = "
            SELECT
                p.nama_pegawai,
                DATE(r.tanggal_resiambilbarang) AS tanggal,
                COUNT(1) AS total_resi,
                SUM(CASE WHEN pr.tipe_resi = 'satuan' THEN 1 ELSE 0 END) AS satuan,
                SUM(CASE WHEN pr.tipe_resi = 'campuran' THEN 1 ELSE 0 END) AS campuran
            FROM tblresiambilbarang r
            INNER JOIN tblpegawai p ON p.kode_pegawai = r.yangambil_pegawai
            LEFT JOIN tblprintresi pr ON pr.id_printresi = r.id_resi
            WHERE DATE(r.tanggal_resiambilbarang) BETWEEN ? AND ?
            GROUP BY p.kode_pegawai, p.nama_pegawai, DATE(r.tanggal_resiambilbarang)
            ORDER BY tanggal DESC, total_resi DESC
        ";

        return $this->db->query($sql, [$start_date, $end_date]);
    }

    function get_rekap_packer($start_date, $end_date)
    {
        $sql = "
            SELECT
                u.name AS nama_packer,
                DATE(pk.tanggal_packing) AS tanggal,
                COUNT(1) AS total_resi,
                SUM(CASE WHEN pr.tipe_resi = 'satuan' THEN 1 ELSE 0 END) AS satuan,
                SUM(CASE WHEN pr.tipe_resi = 'campuran' THEN 1 ELSE 0 END) AS campuran
            FROM tblpacking pk
            INNER JOIN tbluser u ON u.id_user = pk.packer_pegawai
            LEFT JOIN tblprintresi pr ON pr.id_printresi = pk.id_resi
            WHERE DATE(pk.tanggal_packing) BETWEEN ? AND ?
            GROUP BY pk.packer_pegawai, u.name, DATE(pk.tanggal_packing)
            ORDER BY tanggal DESC, total_resi DESC
        ";

        return $this->db->query($sql, [$start_date, $end_date]);
    }

    function get_rekap_paket_keluar($start_date, $end_date)
    {
        $sql = "
            SELECT
                k.nama_kurir,
                DATE(rk.tanggal_resikeluar) AS tanggal,
                COUNT(1) AS jumlah_paket
            FROM tblresikeluar rk
            INNER JOIN tblprintresi pr ON pr.id_printresi = rk.id_resi
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE DATE(rk.tanggal_resikeluar) BETWEEN ? AND ?
            GROUP BY pr.id_kurir, k.nama_kurir, DATE(rk.tanggal_resikeluar)
            ORDER BY tanggal DESC, jumlah_paket DESC
        ";

        return $this->db->query($sql, [$start_date, $end_date]);
    }

    // ── TARGET HARIAN ────────────────────────────────────────

    function get_targets($role = null)
    {
        $this->db->select('t.*, p.nama_pegawai, u.name AS nama_user');
        $this->db->from('tb_target_harian t');
        $this->db->join('tblpegawai p', 'p.kode_pegawai = t.user_id', 'left');
        $this->db->join('tbluser u', 'u.id_user = t.user_id', 'left');
        $this->db->where('t.aktif', 1);

        if ($role) {
            $this->db->where('t.role', $role);
        }

        $this->db->order_by('t.role, t.berlaku_dari DESC');

        return $this->db->get();
    }

    function get_active_target($role, $user_id = null, $tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $this->db->where([
            'role'         => $role,
            'aktif'        => 1,
            'berlaku_dari <=' => $tanggal,
        ]);

        if ($user_id) {
            $this->db->where('user_id', $user_id);
        } else {
            $this->db->where('user_id IS NULL');
        }

        $this->db->order_by('berlaku_dari', 'DESC');
        $this->db->limit(1);

        return $this->db->get('tb_target_harian')->row();
    }

    function save_target($data)
    {
        if (!empty($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('tb_target_harian', $data);
        } else {
            $this->db->insert('tb_target_harian', $data);
            $data['id'] = $this->db->insert_id();
        }

        return $data;
    }

    function delete_target($id)
    {
        $this->db->where('id', $id);
        $this->db->update('tb_target_harian', ['aktif' => 0]);

        return $this->db->affected_rows();
    }

    // ── CONFIG OPERASIONAL ───────────────────────────────────

    function get_config($kunci)
    {
        $row = $this->db->get_where('tb_config_operasional', ['kunci' => $kunci])->row();
        return $row ? $row->nilai : null;
    }

    function get_all_config()
    {
        return $this->db->get('tb_config_operasional');
    }

    function save_config($kunci, $nilai)
    {
        $this->db->where('kunci', $kunci);
        $this->db->update('tb_config_operasional', ['nilai' => $nilai]);
        return $this->db->affected_rows();
    }

    // ── PENCAPAIAN TARGET (untuk view progress bar) ──────────

    function get_pencapaian_picker($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT
                p.kode_pegawai AS user_id,
                p.nama_pegawai AS nama,
                COUNT(r.id_resiambilbarang) AS actual,
                COALESCE(
                    (SELECT t.target FROM tb_target_harian t
                     WHERE t.role = 'picker' AND t.aktif = 1
                       AND (t.user_id = p.kode_pegawai OR t.user_id IS NULL)
                       AND t.berlaku_dari <= ?
                     ORDER BY t.user_id DESC, t.berlaku_dari DESC LIMIT 1),
                    0
                ) AS target
            FROM tblpegawai p
            INNER JOIN tblnamaambilbarang nab ON nab.id_pegawai = p.kode_pegawai AND nab.status_aktif = 'AKTIF'
            LEFT JOIN tblresiambilbarang r ON r.yangambil_pegawai = p.kode_pegawai
                AND DATE(r.tanggal_resiambilbarang) = ?
            GROUP BY p.kode_pegawai, p.nama_pegawai
            ORDER BY actual DESC
        ";

        return $this->db->query($sql, [$tanggal, $tanggal]);
    }

    function get_pencapaian_packer($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT
                u.id_user AS user_id,
                u.name AS nama,
                COUNT(pk.id_packing) AS actual,
                COALESCE(
                    (SELECT t.target FROM tb_target_harian t
                     WHERE t.role = 'packer' AND t.aktif = 1
                       AND (t.user_id = u.id_user OR t.user_id IS NULL)
                       AND t.berlaku_dari <= ?
                     ORDER BY t.user_id DESC, t.berlaku_dari DESC LIMIT 1),
                    0
                ) AS target
            FROM tbluser u
            INNER JOIN tblpacking pk ON pk.packer_pegawai = u.id_user
                AND DATE(pk.tanggal_packing) = ?
            GROUP BY u.id_user, u.name
            HAVING actual > 0
            ORDER BY actual DESC
        ";

        return $this->db->query($sql, [$tanggal, $tanggal]);
    }

    // ── FORMAT PESAN WA ──────────────────────────────────────

    function format_wa_sisa_resi($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $cutoff = $this->get_config('batas_kirim_aman') ?: '15:00';
        $data = $this->get_sisa_resi($tanggal, $cutoff)->result();
        $total = $this->get_total_sisa_resi($tanggal, $cutoff);

        if ($total == 0) {
            return "*LAPORAN SISA RESI*\n" .
                   "Tanggal: " . date('d-m-Y', strtotime($tanggal)) . "\n\n" .
                   "Semua resi sudah dikirim. ✅";
        }

        $msg = "*LAPORAN SISA RESI BELUM KIRIM*\n";
        $msg .= "Tanggal: " . date('d-m-Y', strtotime($tanggal)) . "\n";
        $msg .= "Cutoff: " . $cutoff . "\n";
        $msg .= "─────────────────\n";

        foreach ($data as $row) {
            $msg .= "• " . ($row->nama_kurir ?: 'Lainnya') . ": *" . $row->jumlah_sisa . "* resi\n";
        }

        $msg .= "─────────────────\n";
        $msg .= "*TOTAL: " . $total . " resi*";

        return $msg;
    }

    function format_wa_paket_keluar($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $data = $this->get_paket_keluar($tanggal)->result();
        $total = $this->get_total_paket_keluar($tanggal);

        $msg = "*REKAP PAKET KELUAR*\n";
        $msg .= "Tanggal: " . date('d-m-Y', strtotime($tanggal)) . "\n";
        $msg .= "─────────────────\n";

        foreach ($data as $row) {
            $msg .= "• " . ($row->nama_kurir ?: 'Lainnya') . ": *" . $row->jumlah_paket . "* paket\n";
        }

        $msg .= "─────────────────\n";
        $msg .= "*TOTAL: " . $total . " paket*";

        return $msg;
    }

    function format_wa_rekap_target($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $pickers = $this->get_pencapaian_picker($tanggal)->result();
        $packers = $this->get_pencapaian_packer($tanggal)->result();

        $msg = "*PENCAPAIAN TARGET HARIAN*\n";
        $msg .= "Tanggal: " . date('d-m-Y', strtotime($tanggal)) . "\n";
        $msg .= "═════════════════\n";

        $msg .= "\n*PICKER:*\n";
        foreach ($pickers as $row) {
            $pct = $row->target > 0 ? round(($row->actual / $row->target) * 100) : 0;
            $icon = $pct >= 100 ? '✅' : ($pct >= 50 ? '🔶' : '🔴');
            $msg .= "{$icon} {$row->nama}: {$row->actual}";
            if ($row->target > 0) $msg .= "/{$row->target} ({$pct}%)";
            $msg .= "\n";
        }

        $msg .= "\n*PACKER:*\n";
        foreach ($packers as $row) {
            $pct = $row->target > 0 ? round(($row->actual / $row->target) * 100) : 0;
            $icon = $pct >= 100 ? '✅' : ($pct >= 50 ? '🔶' : '🔴');
            $msg .= "{$icon} {$row->nama}: {$row->actual}";
            if ($row->target > 0) $msg .= "/{$row->target} ({$pct}%)";
            $msg .= "\n";
        }

        return $msg;
    }
}
