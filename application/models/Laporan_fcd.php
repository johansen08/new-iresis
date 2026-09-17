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
                work.total_resi,
                work.satuan,
                work.campuran,
                COALESCE(work.total_sku_qty, 0) AS total_sku_qty,
                COALESCE(err.total_kesalahan, 0) AS total_kesalahan,
                (COALESCE(work.total_sku_qty, 0) - (COALESCE(err.total_kesalahan, 0) * 50)) AS total_point
            FROM (
                SELECT
                    r.yangambil_pegawai AS peg_id,
                    COUNT(DISTINCT r.id_resi) AS total_resi,
                    SUM(CASE WHEN pr.tipe_resi = 'satuan' THEN 1 ELSE 0 END) AS satuan,
                    SUM(CASE WHEN pr.tipe_resi = 'campuran' THEN 1 ELSE 0 END) AS campuran,
                    SUM(dpr_sum.total_qty) AS total_sku_qty
                FROM tblresiambilbarang r
                LEFT JOIN tblprintresi pr ON pr.id_printresi = r.id_resi
                LEFT JOIN (
                    SELECT id_resi, SUM(CAST(jumlah AS UNSIGNED)) as total_qty
                    FROM tbldetailprintresi
                    GROUP BY id_resi
                ) dpr_sum ON dpr_sum.id_resi = r.id_resi
                WHERE DATE(r.tanggal_resiambilbarang) = ?
                GROUP BY r.yangambil_pegawai
            ) work
            INNER JOIN tblpegawai p ON p.kode_pegawai = work.peg_id
            LEFT JOIN (
                SELECT 
                    r2.yangambil_pegawai AS peg_id,
                    COUNT(mp.id_masalahpicker) AS total_kesalahan
                FROM tblmasalahpicker mp
                JOIN tblresiambilbarang r2 ON r2.id_resi = mp.id_printresi
                WHERE DATE(r2.tanggal_resiambilbarang) = ?
                GROUP BY r2.yangambil_pegawai
            ) err ON err.peg_id = work.peg_id
            ORDER BY total_resi DESC
        ";

        return $this->db->query($sql, [$tanggal, $tanggal]);
    }

    // ── TRACKING PICKER ──────────────────────────────────────

    /**
     * Semua resi yang diambil pada tanggal tsb, urut per picker per jam.
     * lantai_list = angka pertama no_rak tiap SKU di resi itu (format no_rak: [Lantai][Zona]-[Kolom]-[Level], mis. "3B-J1-5").
     * SKU dengan no_rak kosong/tidak diawali angka (lokasi belum terdata) diabaikan dari lantai_list.
     */
    function get_tracking_picker_resi($tanggal, $id_pegawai = null)
    {
        $sql = "
            SELECT
                r.yangambil_pegawai AS id_pegawai,
                p.nama_pegawai,
                r.id_resi,
                r.tanggal_resiambilbarang,
                pr.noresi,
                pr.tipe_resi,
                GROUP_CONCAT(DISTINCT dr.sku ORDER BY dr.sku SEPARATOR ', ') AS sku_list,
                COUNT(DISTINCT dr.sku) AS jumlah_sku,
                GROUP_CONCAT(DISTINCT LEFT(dr.no_rak, 1) ORDER BY LEFT(dr.no_rak, 1) SEPARATOR ',') AS lantai_list
            FROM tblresiambilbarang r
            INNER JOIN tblpegawai p ON p.kode_pegawai = r.yangambil_pegawai
            LEFT JOIN tblprintresi pr ON pr.id_printresi = r.id_resi
            LEFT JOIN tbldetailprintresi dr ON dr.id_resi = r.id_resi AND dr.no_rak REGEXP '^[0-9]'
            WHERE DATE(r.tanggal_resiambilbarang) = ?
        ";
        $params = [$tanggal];

        if (!empty($id_pegawai)) {
            $sql .= " AND r.yangambil_pegawai = ? ";
            $params[] = $id_pegawai;
        }

        $sql .= " GROUP BY r.id_resiambilbarang ORDER BY r.yangambil_pegawai, r.tanggal_resiambilbarang ";

        return $this->db->query($sql, $params);
    }

    // ── TOTALAN PACKER ───────────────────────────────────────

    function get_totalan_packer($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT
                u.name AS nama_packer,
                work.total_resi,
                work.satuan,
                work.campuran,
                COALESCE(work.total_sku_qty, 0) AS total_sku_qty,
                COALESCE(err.total_kesalahan, 0) AS total_kesalahan,
                (COALESCE(work.total_sku_qty, 0) - (COALESCE(err.total_kesalahan, 0) * 50)) AS total_point
            FROM (
                SELECT
                    pk.packer_pegawai AS user_id,
                    COUNT(DISTINCT pk.id_resi) AS total_resi,
                    SUM(CASE WHEN pr.tipe_resi = 'satuan' THEN 1 ELSE 0 END) AS satuan,
                    SUM(CASE WHEN pr.tipe_resi = 'campuran' THEN 1 ELSE 0 END) AS campuran,
                    SUM(dpr_sum.total_qty) AS total_sku_qty
                FROM tblpacking pk
                LEFT JOIN tblprintresi pr ON pr.id_printresi = pk.id_resi
                LEFT JOIN (
                    SELECT id_resi, SUM(CAST(jumlah AS UNSIGNED)) as total_qty
                    FROM tbldetailprintresi
                    GROUP BY id_resi
                ) dpr_sum ON dpr_sum.id_resi = pk.id_resi
                WHERE DATE(pk.tanggal_packing) = ?
                GROUP BY pk.packer_pegawai
            ) work
            INNER JOIN tbluser u ON u.id_user = work.user_id
            LEFT JOIN (
                SELECT 
                    pk2.packer_pegawai AS user_id,
                    COUNT(mp.id_masalahpacker) AS total_kesalahan
                FROM tblmasalahpacker mp
                JOIN tblpacking pk2 ON pk2.id_resi = mp.id_printresi
                WHERE DATE(pk2.tanggal_packing) = ?
                GROUP BY pk2.packer_pegawai
            ) err ON err.user_id = work.user_id
            ORDER BY total_resi DESC
        ";

        return $this->db->query($sql, [$tanggal, $tanggal]);
    }

    // ── SISA RESI BELUM KIRIM ────────────────────────────────

    private function _filter_aktif()
    {
        return "(pr.batal IS NULL OR pr.batal = '' OR pr.batal = '0')
                AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)";
    }

    // Resi dicetak hari ini, belum HO
    // $cutoff: jam batas (format 'HH:MM'), hanya hitung resi yang dicetak sebelum jam ini
    function get_sisa_resi($tanggal = null, $cutoff = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');
        $f = $this->_filter_aktif();

        $cutoff_filter = $cutoff ? "AND TIME(pr.tanggal_printresi) <= ?" : '';
        $params = $cutoff ? [$tanggal, $cutoff] : [$tanggal];

        $sql = "
            SELECT k.nama_kurir, COUNT(1) AS jumlah_sisa
            FROM tblprintresi pr
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE DATE(pr.tanggal_printresi) = ?
              {$cutoff_filter}
              AND {$f}
              AND pr.id_printresi NOT IN (SELECT id_resi FROM tblresikeluar)
            GROUP BY pr.id_kurir, k.nama_kurir
            ORDER BY jumlah_sisa DESC
        ";

        return $this->db->query($sql, $params);
    }

    function get_total_sisa_resi($tanggal = null, $cutoff = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');
        $f = $this->_filter_aktif();

        $cutoff_filter = $cutoff ? "AND TIME(pr.tanggal_printresi) <= ?" : '';
        $params = $cutoff ? [$tanggal, $cutoff] : [$tanggal];

        $sql = "
            SELECT COUNT(1) AS total
            FROM tblprintresi pr
            WHERE DATE(pr.tanggal_printresi) = ?
              {$cutoff_filter}
              AND {$f}
              AND pr.id_printresi NOT IN (SELECT id_resi FROM tblresikeluar)
        ";

        return $this->db->query($sql, $params)->row()->total;
    }

    // Resi dari 5 hari sebelumnya yang belum HO (backlog), per kurir
    function get_sisa_resi_backlog($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');
        $f = $this->_filter_aktif();

        $sql = "
            SELECT k.nama_kurir, COUNT(1) AS jumlah,
                   DATE(pr.tanggal_bataskirim) AS tgl_deadline
            FROM tblprintresi pr
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE DATE(pr.tanggal_printresi) >= DATE_SUB(?, INTERVAL 5 DAY)
              AND DATE(pr.tanggal_printresi) < ?
              AND {$f}
              AND pr.id_printresi NOT IN (SELECT id_resi FROM tblresikeluar)
            GROUP BY pr.id_kurir, k.nama_kurir, DATE(pr.tanggal_bataskirim)
            ORDER BY tgl_deadline ASC, jumlah DESC
        ";

        return $this->db->query($sql, [$tanggal, $tanggal]);
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

    // ── EKSPEDISI URGENT ────────────────────────────────────

    private $kurir_urgent = [21, 12, 11, 10, 8, 18, 9]; // GOTO, NINJA, SICEPAT, SICEPAT-REKOM, LAZADA, CENTRAL CARGO, JNE

    function get_ekspedisi_urgent()
    {
        $kurir_ids = implode(',', $this->kurir_urgent);

        $sql = "
            SELECT
                k.id_kurir,
                k.nama_kurir,
                COUNT(pr.id_printresi) AS total_resi,
                SUM(CASE WHEN rab.id_resi IS NULL THEN 1 ELSE 0 END) AS belum_pick,
                SUM(CASE WHEN rab.id_resi IS NOT NULL AND pk.id_resi IS NULL THEN 1 ELSE 0 END) AS belum_pack,
                SUM(CASE WHEN pk.id_resi IS NOT NULL AND rk.id_resi IS NULL THEN 1 ELSE 0 END) AS belum_ho
            FROM tblprintresi pr
            INNER JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblpacking pk ON pk.id_resi = pr.id_printresi
            LEFT JOIN tblresikeluar rk ON rk.id_resi = pr.id_printresi
            WHERE (pr.batal IS NULL OR pr.batal = '' OR pr.batal = '0')
              AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
              AND pr.id_kurir IN ({$kurir_ids})
              AND (
                DATE(pr.tanggal_printresi) = CURDATE()
                OR (
                  DATE(pr.tanggal_printresi) >= DATE_SUB(CURDATE(), INTERVAL 5 DAY)
                  AND rk.id_resi IS NULL
                )
              )
            GROUP BY k.id_kurir, k.nama_kurir
            ORDER BY total_resi DESC
        ";

        return $this->db->query($sql);
    }

    function get_ekspedisi_urgent_detail($id_kurir, $status)
    {
        $this->db->select('pr.noresi, pr.tanggal_printresi, k.nama_kurir');
        $this->db->from('tblprintresi pr');
        $this->db->join('tblkurir k', 'k.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblresikeluar rk_check', 'rk_check.id_resi = pr.id_printresi', 'left');

        if ($status === 'belum_pick') {
            $this->db->join('tblresiambilbarang rab', 'rab.id_resi = pr.id_printresi', 'left');
            $this->db->where('rab.id_resi IS NULL');
        } elseif ($status === 'belum_pack') {
            $this->db->join('tblresiambilbarang rab', 'rab.id_resi = pr.id_printresi', 'inner');
            $this->db->join('tblpacking pk', 'pk.id_resi = pr.id_printresi', 'left');
            $this->db->where('pk.id_resi IS NULL');
        } elseif ($status === 'belum_ho') {
            $this->db->join('tblpacking pk', 'pk.id_resi = pr.id_printresi', 'inner');
            $this->db->join('tblresikeluar rk', 'rk.id_resi = pr.id_printresi', 'left');
            $this->db->where('rk.id_resi IS NULL');
        }

        $this->db->where("(pr.batal IS NULL OR pr.batal = '' OR pr.batal = '0')");
        $this->db->where("(pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)");
        $this->db->where('pr.id_kurir', $id_kurir);
        $this->db->where("(DATE(pr.tanggal_printresi) = CURDATE() OR (DATE(pr.tanggal_printresi) >= DATE_SUB(CURDATE(), INTERVAL 5 DAY) AND rk_check.id_resi IS NULL))");
        $this->db->order_by('pr.tanggal_printresi', 'ASC');

        return $this->db->get();
    }

    function get_control_pengiriman()
    {
        $sql = "
            SELECT
                COALESCE(k.nama_kurir, 'Lainnya') AS nama_kurir,
                COUNT(pr.id_printresi) AS total_resi,
                SUM(CASE WHEN rab.id_resi IS NULL THEN 1 ELSE 0 END) AS belum_pick,
                SUM(CASE WHEN rab.id_resi IS NOT NULL AND pk.id_resi IS NULL THEN 1 ELSE 0 END) AS belum_pack,
                SUM(CASE WHEN pk.id_resi IS NOT NULL AND rk.id_resi IS NULL THEN 1 ELSE 0 END) AS belum_ho
            FROM tblprintresi pr
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblpacking pk ON pk.id_resi = pr.id_printresi
            LEFT JOIN tblresikeluar rk ON rk.id_resi = pr.id_printresi
            WHERE (pr.batal IS NULL OR pr.batal = '' OR pr.batal = '0')
              AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
              AND (
                DATE(pr.tanggal_printresi) = CURDATE()
                OR (
                  DATE(pr.tanggal_printresi) >= DATE_SUB(CURDATE(), INTERVAL 5 DAY)
                  AND rk.id_resi IS NULL
                )
              )
            GROUP BY k.nama_kurir
            ORDER BY total_resi DESC
        ";

        return $this->db->query($sql);
    }

    // ── AUTO-DETECT LAST ACTIVITY ────────────────────────────

    function get_last_picker_packer_scan()
    {
        $sql = "
            SELECT GREATEST(
                COALESCE((SELECT MAX(tanggal_resiambilbarang) FROM tblresiambilbarang WHERE DATE(tanggal_resiambilbarang) = CURDATE()), '2000-01-01'),
                COALESCE((SELECT MAX(tanggal_packing) FROM tblpacking WHERE DATE(tanggal_packing) = CURDATE()), '2000-01-01')
            ) AS last_scan
        ";
        return $this->db->query($sql)->row()->last_scan;
    }

    function get_last_ho_scan()
    {
        $sql = "SELECT MAX(tanggal_resikeluar) AS last_ho FROM tblresikeluar WHERE DATE(tanggal_resikeluar) = CURDATE()";
        $row = $this->db->query($sql)->row();
        return $row->last_ho ?: '2000-01-01';
    }

    // ── RESI CANCEL ─────────────────────────────────────────

    function get_total_resi_cancel($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT COUNT(1) AS total
            FROM tblprintresi
            WHERE DATE(tanggal_printresi) = ?
              AND (
                (batal IS NOT NULL AND batal != '' AND batal != '0')
                OR status_pesanan LIKE '%CANCEL%'
              )
        ";

        return $this->db->query($sql, [$tanggal])->row()->total;
    }

    // ── LAPORAN PRODUKSI ─────────────────────────────────────

    // Resi masuk (hari ini + 5 hari ke belakang) yang belum selesai, per marketplace
    function get_backlog_per_marketplace($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');
        $f = $this->_filter_aktif();

        $sql = "
            SELECT m.nama_marketplace, COUNT(1) AS total
            FROM tblprintresi pr
            LEFT JOIN tblmarketplace m ON m.id_marketplace = pr.id_marketplace
            LEFT JOIN tblresikeluar rk ON rk.id_resi = pr.id_printresi
            WHERE DATE(pr.tanggal_printresi) >= DATE_SUB(?, INTERVAL 5 DAY)
              AND {$f}
              AND rk.id_resi IS NULL
            GROUP BY m.id_marketplace, m.nama_marketplace
            ORDER BY total DESC
        ";

        return $this->db->query($sql, [$tanggal]);
    }

    // Resi wajib: bataskirim hari ini atau sudah lewat, belum HO, per marketplace
    function get_resi_wajib_marketplace($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');
        $f = $this->_filter_aktif();

        $sql = "
            SELECT m.nama_marketplace,
                   COUNT(1) AS total,
                   SUM(CASE WHEN rab.id_resi IS NULL THEN 1 ELSE 0 END) AS belum_pick,
                   SUM(CASE WHEN rab.id_resi IS NOT NULL AND pk.id_resi IS NULL THEN 1 ELSE 0 END) AS belum_pack,
                   SUM(CASE WHEN pk.id_resi IS NOT NULL THEN 1 ELSE 0 END) AS sudah_pack
            FROM tblprintresi pr
            LEFT JOIN tblmarketplace m ON m.id_marketplace = pr.id_marketplace
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblpacking pk ON pk.id_resi = pr.id_printresi
            LEFT JOIN tblresikeluar rk ON rk.id_resi = pr.id_printresi
            WHERE DATE(pr.tanggal_bataskirim) <= ?
              AND rk.id_resi IS NULL
              AND {$f}
            GROUP BY m.id_marketplace, m.nama_marketplace
            ORDER BY total DESC
        ";

        return $this->db->query($sql, [$tanggal]);
    }

    // Total RTS yang masih aktif (belum HO), per kurir
    function get_total_rts_aktif()
    {
        $f = $this->_filter_aktif();

        $sql = "
            SELECT k.nama_kurir, COUNT(1) AS total
            FROM tblprintresi pr
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE pr.status_rts = 1
              AND pr.id_printresi NOT IN (SELECT id_resi FROM tblresikeluar)
              AND {$f}
            GROUP BY pr.id_kurir, k.nama_kurir
            ORDER BY total DESC
        ";

        return $this->db->query($sql);
    }

    // HO hari ini: total per kurir (paket keluar sudah ada), tapi ini untuk count saja
    function get_total_ho_hari_ini($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "SELECT COUNT(1) AS total FROM tblresikeluar WHERE DATE(tanggal_resikeluar) = ?";
        return $this->db->query($sql, [$tanggal])->row()->total;
    }

    // Resi yang sudah dikerjakan (HO) hari ini per marketplace
    function get_selesai_per_marketplace($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT m.nama_marketplace, COUNT(1) AS total
            FROM tblresikeluar rk
            JOIN tblprintresi pr ON pr.id_printresi = rk.id_resi
            LEFT JOIN tblmarketplace m ON m.id_marketplace = pr.id_marketplace
            WHERE DATE(rk.tanggal_resikeluar) = ?
            GROUP BY m.id_marketplace, m.nama_marketplace
            ORDER BY total DESC
        ";

        return $this->db->query($sql, [$tanggal]);
    }

    function get_paket_keluar_hari_ini($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT k.nama_kurir, COUNT(1) AS total
            FROM tblresikeluar rk
            JOIN tblprintresi pr ON pr.id_printresi = rk.id_resi
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE DATE(rk.tanggal_resikeluar) = ?
            GROUP BY pr.id_kurir, k.nama_kurir
            ORDER BY total DESC
        ";

        return $this->db->query($sql, [$tanggal]);
    }

    // Sisa packingan wajib: bataskirim hari ini, belum pack
    function get_sisa_packing_wajib($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');
        $f = $this->_filter_aktif();

        $sql = "
            SELECT m.nama_marketplace, COUNT(1) AS total
            FROM tblprintresi pr
            LEFT JOIN tblmarketplace m ON m.id_marketplace = pr.id_marketplace
            LEFT JOIN tblpacking pk ON pk.id_resi = pr.id_printresi
            LEFT JOIN tblresikeluar rk ON rk.id_resi = pr.id_printresi
            WHERE DATE(pr.tanggal_bataskirim) <= ?
              AND pk.id_resi IS NULL
              AND rk.id_resi IS NULL
              AND {$f}
            GROUP BY m.id_marketplace, m.nama_marketplace
            ORDER BY total DESC
        ";

        return $this->db->query($sql, [$tanggal]);
    }
}
