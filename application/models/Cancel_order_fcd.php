<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cancel Order (TIM RESI).
 *
 * Sumber data ada dua, keduanya bermuara ke tabel `tblcancelorder`:
 *  1. JUBELIO — hasil sinkron dari tblprintresi.status_pesanan yang berisi
 *     CANCELED / REQUEST_CANCEL (status itu diisi oleh upload resi dari
 *     Jubelio core-api).
 *  2. SCAN    — diinput manual oleh tim resi lewat menu Scan Cek Cancel,
 *     untuk resi yang sudah dibatalkan marketplace tapi belum kebaca Jubelio.
 *
 * Catatan soal "jam & tanggal cancel": Jubelio tidak mengirim timestamp
 * pembatalan, yang tersedia hanya status pesanan. Karena itu tanggal_cancel
 * untuk baris JUBELIO diambil dari tblprintresi.modified_at — yaitu saat
 * iresis PERTAMA KALI melihat status resi berubah jadi cancel — dengan
 * fallback ke tanggal_printresi kalau resi sudah cancel sejak awal diupload.
 * Nilai itu di-snapshot ke tblcancelorder supaya tidak ikut berubah lagi
 * kalau baris tblprintresi diupdate belakangan.
 */
class Cancel_order_fcd extends CI_Model
{
    /** Status pesanan di tblprintresi yang dianggap cancel. */
    const STATUS_CANCEL = ['CANCELED', 'REQUEST_CANCEL'];

    // ─────────────────────────────────────────────
    //  SINKRON DARI JUBELIO
    // ─────────────────────────────────────────────

    /**
     * Tarik resi cancel dari tblprintresi ke tblcancelorder untuk rentang
     * tanggal print resi tertentu. Aman dipanggil berulang (upsert by noresi).
     *
     * @return array ['diproses' => int, 'baru' => int, 'diperbarui' => int]
     */
    public function sync_from_jubelio($start_date, $end_date, $user_id = null)
    {
        $sumber = $this->count_source_cancel($start_date, $end_date);
        if ($sumber === 0) {
            return ['diproses' => 0, 'baru' => 0, 'diperbarui' => 0];
        }

        $sebelum = (int) $this->db->count_all_results('tblcancelorder');

        $sql = "
            INSERT INTO tblcancelorder
                (noresi, no_pesanan, id_printresi, id_marketplace, nama_marketplace, toko,
                 status_marketplace, tanggal_pesan, tanggal_cancel, sumber,
                 terkonfirmasi_jubelio, ada_di_iresis, created_by, created_at, updated_at)
            SELECT
                pr.noresi,
                (SELECT d.no_pesanan FROM tbldetailprintresi d WHERE d.id_resi = pr.id_printresi LIMIT 1),
                pr.id_printresi,
                pr.id_marketplace,
                mp.nama_marketplace,
                NULLIF(TRIM(pr.toko), ''),
                UPPER(TRIM(pr.status_pesanan)),
                pr.tanggal_pesan,
                COALESCE(pr.modified_at, pr.tanggal_printresi),
                'JUBELIO',
                1,
                1,
                ?,
                NOW(),
                NOW()
            FROM tblprintresi pr
            LEFT JOIN tblmarketplace mp ON mp.id_marketplace = pr.id_marketplace
            WHERE pr.tanggal_printresi BETWEEN ? AND ?
              AND UPPER(TRIM(pr.status_pesanan)) IN ('CANCELED', 'REQUEST_CANCEL')
            ON DUPLICATE KEY UPDATE
                no_pesanan            = COALESCE(VALUES(no_pesanan), tblcancelorder.no_pesanan),
                id_printresi          = VALUES(id_printresi),
                id_marketplace        = VALUES(id_marketplace),
                nama_marketplace      = VALUES(nama_marketplace),
                toko                  = COALESCE(VALUES(toko), tblcancelorder.toko),
                status_marketplace    = VALUES(status_marketplace),
                tanggal_pesan         = COALESCE(VALUES(tanggal_pesan), tblcancelorder.tanggal_pesan),
                tanggal_cancel        = CASE WHEN tblcancelorder.sumber = 'JUBELIO'
                                             THEN VALUES(tanggal_cancel)
                                             ELSE tblcancelorder.tanggal_cancel END,
                terkonfirmasi_jubelio = 1,
                ada_di_iresis         = 1,
                updated_at            = NOW()
        ";

        $this->db->query($sql, [$user_id, $start_date, $end_date]);

        $sesudah = (int) $this->db->count_all_results('tblcancelorder');
        $baru    = max(0, $sesudah - $sebelum);

        return [
            'diproses'   => $sumber,
            'baru'       => $baru,
            'diperbarui' => max(0, $sumber - $baru),
        ];
    }

    /** Jumlah resi cancel di tblprintresi untuk rentang tanggal print resi. */
    public function count_source_cancel($start_date, $end_date)
    {
        $sql = "
            SELECT COUNT(1) AS total
            FROM tblprintresi
            WHERE tanggal_printresi BETWEEN ? AND ?
              AND UPPER(TRIM(status_pesanan)) IN ('CANCELED', 'REQUEST_CANCEL')
        ";
        return (int) $this->db->query($sql, [$start_date, $end_date])->row()->total;
    }

    // ─────────────────────────────────────────────
    //  DAFTAR CANCEL ORDER
    // ─────────────────────────────────────────────

    /**
     * Bangun klausa WHERE bersama untuk daftar / hitung / export.
     * Filter tanggal berlaku pada kolom yang dipilih user (tanggal_cancel
     * atau tanggal_pesan) supaya sesuai konteks yang mereka cari.
     */
    private function _apply_filter(array $p)
    {
        $kolom_tanggal = ($p['filter_tanggal'] ?? 'cancel') === 'pesan'
            ? 'c.tanggal_pesan'
            : 'c.tanggal_cancel';

        $this->db->from('tblcancelorder c');

        if (!empty($p['start_date']) && !empty($p['end_date'])) {
            $this->db->where("$kolom_tanggal >=", $p['start_date']);
            $this->db->where("$kolom_tanggal <=", $p['end_date']);
        }

        if (!empty($p['filter_marketplace'])) {
            $this->db->where('c.id_marketplace', (int) $p['filter_marketplace']);
        }

        if (!empty($p['filter_status'])) {
            $this->db->where('c.status_marketplace', $p['filter_status']);
        }

        if (!empty($p['filter_sumber'])) {
            $this->db->where('c.sumber', $p['filter_sumber']);
        }

        if (!empty($p['search'])) {
            $this->db->group_start();
            $this->db->like('c.noresi', $p['search']);
            $this->db->or_like('c.no_pesanan', $p['search']);
            $this->db->or_like('c.nama_marketplace', $p['search']);
            $this->db->or_like('c.toko', $p['search']);
            $this->db->group_end();
        }
    }

    /** Baris untuk DataTable server-side. */
    public function get_cancel_data(array $p)
    {
        $this->db->select('c.*');
        $this->_apply_filter($p);

        $kolom_urut = [
            'noresi'             => 'c.noresi',
            'no_pesanan'         => 'c.no_pesanan',
            'nama_marketplace'   => 'c.nama_marketplace',
            'status_marketplace' => 'c.status_marketplace',
            'tanggal_pesan'      => 'c.tanggal_pesan',
            'tanggal_cancel'     => 'c.tanggal_cancel',
            'sumber'             => 'c.sumber',
        ];
        $order = $kolom_urut[$p['order'] ?? ''] ?? 'c.tanggal_cancel';
        $dir   = strtolower($p['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $this->db->order_by($order, $dir);
        $this->db->order_by('c.id_cancel', 'DESC');

        $length = (int) ($p['length'] ?? 50);
        if ($length > 0) {
            $this->db->limit($length, (int) ($p['start'] ?? 0));
        }

        return $this->db->get()->result();
    }

    /** Jumlah baris sesuai filter (untuk paging DataTable). */
    public function count_cancel_data(array $p)
    {
        $this->db->select('COUNT(1) AS total');
        $this->_apply_filter($p);
        return (int) $this->db->get()->row()->total;
    }

    /** Ringkasan kartu statistik di atas tabel. */
    public function get_stats(array $p)
    {
        $this->db->select("
            COUNT(1) AS total,
            SUM(CASE WHEN c.status_marketplace = 'CANCELED' THEN 1 ELSE 0 END) AS canceled,
            SUM(CASE WHEN c.status_marketplace = 'REQUEST_CANCEL' THEN 1 ELSE 0 END) AS request_cancel,
            SUM(CASE WHEN c.sumber = 'JUBELIO' THEN 1 ELSE 0 END) AS dari_jubelio,
            SUM(CASE WHEN c.sumber = 'SCAN' THEN 1 ELSE 0 END) AS dari_scan
        ", false);
        $this->_apply_filter($p);
        $row = $this->db->get()->row_array();

        return [
            'total'          => (int) ($row['total'] ?? 0),
            'canceled'       => (int) ($row['canceled'] ?? 0),
            'request_cancel' => (int) ($row['request_cancel'] ?? 0),
            'dari_jubelio'   => (int) ($row['dari_jubelio'] ?? 0),
            'dari_scan'      => (int) ($row['dari_scan'] ?? 0),
        ];
    }

    /** Rincian per marketplace untuk rentang yang sedang difilter. */
    public function get_per_marketplace(array $p)
    {
        $this->db->select("COALESCE(c.nama_marketplace, '-') AS nama_marketplace, COUNT(1) AS jumlah", false);
        $this->_apply_filter($p);
        $this->db->group_by('c.nama_marketplace');
        $this->db->order_by('jumlah', 'DESC');
        return $this->db->get()->result_array();
    }

    /** Semua baris (tanpa paging) untuk export Excel. */
    public function get_export_data(array $p)
    {
        $p['start']  = 0;
        $p['length'] = 0;
        return $this->get_cancel_data($p);
    }

    // ─────────────────────────────────────────────
    //  SCAN / CEK RESI
    // ─────────────────────────────────────────────

    /**
     * Cek satu resi: sudah cancel atau belum.
     *
     * Balikan 'kondisi':
     *  - SUDAH_CANCEL       : status di tblprintresi CANCELED/REQUEST_CANCEL
     *  - CANCEL_MANUAL      : belum cancel di Jubelio, tapi sudah dicatat manual
     *  - BELUM_CANCEL       : resi ada di iresis dan statusnya masih aktif
     *  - TIDAK_ADA_DI_IRESIS: resi tidak ketemu sama sekali
     */
    public function cek_resi($noresi)
    {
        $noresi = strtoupper(trim($noresi));
        pastikan_resi_live($noresi); // resi lama yang sudah diarsipkan ditarik dulu ke prod

        $sql = "
            SELECT
                pr.id_printresi,
                pr.noresi,
                pr.status_pesanan,
                pr.tanggal_pesan,
                pr.tanggal_printresi,
                pr.modified_at,
                pr.id_marketplace,
                NULLIF(TRIM(pr.toko), '') AS toko,
                mp.nama_marketplace,
                (SELECT d.no_pesanan FROM tbldetailprintresi d WHERE d.id_resi = pr.id_printresi LIMIT 1) AS no_pesanan
            FROM tblprintresi pr
            LEFT JOIN tblmarketplace mp ON mp.id_marketplace = pr.id_marketplace
            WHERE pr.noresi = ?
            LIMIT 1
        ";
        $resi = $this->db->query($sql, [$noresi])->row();

        $catatan = $this->db->get_where('tblcancelorder', ['noresi' => $noresi])->row();

        $status_pesanan = $resi ? strtoupper(trim($resi->status_pesanan ?? '')) : null;
        $cancel_jubelio = $status_pesanan && in_array($status_pesanan, self::STATUS_CANCEL, true);

        if (!$resi) {
            $kondisi = $catatan ? 'CANCEL_MANUAL' : 'TIDAK_ADA_DI_IRESIS';
        } elseif ($cancel_jubelio) {
            $kondisi = 'SUDAH_CANCEL';
        } elseif ($catatan) {
            $kondisi = 'CANCEL_MANUAL';
        } else {
            $kondisi = 'BELUM_CANCEL';
        }

        return [
            'kondisi'            => $kondisi,
            'noresi'             => $noresi,
            'no_pesanan'         => $catatan->no_pesanan ?? ($resi->no_pesanan ?? null),
            'status_marketplace' => $cancel_jubelio ? $status_pesanan : ($catatan->status_marketplace ?? $status_pesanan),
            'status_pesanan'     => $status_pesanan,
            'nama_marketplace'   => $resi->nama_marketplace ?? ($catatan->nama_marketplace ?? null),
            'toko'               => $resi->toko ?? ($catatan->toko ?? null),
            'tanggal_pesan'      => $resi->tanggal_pesan ?? ($catatan->tanggal_pesan ?? null),
            'tanggal_cancel'     => $catatan->tanggal_cancel
                ?? ($cancel_jubelio ? ($resi->modified_at ?: $resi->tanggal_printresi) : null),
            'sumber'             => $catatan->sumber ?? null,
            'sudah_tercatat'     => $catatan ? true : false,
            'catatan'            => $catatan->catatan ?? null,
        ];
    }

    /**
     * Simpan hasil scan ke tblcancelorder.
     *
     * $sumber 'JUBELIO' dipakai saat resi memang sudah cancel menurut Jubelio
     * (scan cuma menarik datanya ke daftar), 'SCAN' saat tim resi menandai
     * sendiri resi yang belum kebaca Jubelio.
     */
    public function simpan_scan($noresi, $sumber, $tanggal_cancel, $catatan, $user_id)
    {
        $noresi = strtoupper(trim($noresi));
        $info   = $this->cek_resi($noresi);
        $now    = date('Y-m-d H:i:s');

        $data = [
            'noresi'                => $noresi,
            'no_pesanan'            => $info['no_pesanan'],
            'id_marketplace'        => null,
            'nama_marketplace'      => $info['nama_marketplace'],
            'toko'                  => $info['toko'],
            'status_marketplace'    => $sumber === 'JUBELIO'
                ? ($info['status_pesanan'] ?: 'CANCELED')
                : 'CANCEL MANUAL',
            'tanggal_pesan'         => $info['tanggal_pesan'],
            'tanggal_cancel'        => $tanggal_cancel ?: ($info['tanggal_cancel'] ?: $now),
            'sumber'                => $sumber,
            'terkonfirmasi_jubelio' => $sumber === 'JUBELIO' ? 1 : 0,
            'ada_di_iresis'         => $info['kondisi'] === 'TIDAK_ADA_DI_IRESIS' ? 0 : 1,
            'catatan'               => $catatan ?: null,
            'updated_at'            => $now,
        ];

        // id_printresi & id_marketplace ikut disimpan kalau resinya ada di iresis
        $resi = $this->db->select('id_printresi, id_marketplace')
            ->get_where('tblprintresi', ['noresi' => $noresi])->row();
        if ($resi) {
            $data['id_printresi']   = $resi->id_printresi;
            $data['id_marketplace'] = $resi->id_marketplace;
        }

        $existing = $this->db->get_where('tblcancelorder', ['noresi' => $noresi])->row();

        if ($existing) {
            // Jangan timpa jejak input pertama — cukup segarkan datanya.
            unset($data['noresi'], $data['sumber'], $data['tanggal_cancel']);
            if ($sumber === 'JUBELIO') {
                $data['terkonfirmasi_jubelio'] = 1;
            }
            if (empty($catatan)) {
                unset($data['catatan']);
            }
            $this->db->where('id_cancel', $existing->id_cancel)->update('tblcancelorder', $data);

            return ['status' => 'UPDATE', 'id_cancel' => (int) $existing->id_cancel];
        }

        $data['created_by'] = $user_id;
        $data['created_at'] = $now;
        $this->db->insert('tblcancelorder', $data);

        return ['status' => 'INSERT', 'id_cancel' => (int) $this->db->insert_id()];
    }

    /**
     * Hapus satu baris cancel. Hanya baris hasil input manual (sumber SCAN)
     * yang boleh dihapus — baris JUBELIO akan muncul lagi saat sinkron.
     */
    public function hapus_manual($id_cancel)
    {
        $id_cancel = (int) $id_cancel;
        if ($id_cancel <= 0) {
            return ['sukses' => false, 'pesan' => 'ID tidak valid'];
        }

        $row = $this->db->get_where('tblcancelorder', ['id_cancel' => $id_cancel])->row();
        if (!$row) {
            return ['sukses' => false, 'pesan' => 'Data tidak ditemukan'];
        }
        if ($row->sumber !== 'SCAN') {
            return ['sukses' => false, 'pesan' => 'Hanya data hasil input manual (scan) yang bisa dihapus'];
        }

        $this->db->where('id_cancel', $id_cancel)->where('sumber', 'SCAN')->delete('tblcancelorder');
        return ['sukses' => true, 'pesan' => 'Data cancel manual berhasil dihapus'];
    }

    /** Daftar marketplace untuk isian dropdown filter. */
    public function get_list_marketplace()
    {
        return $this->db->order_by('nama_marketplace', 'ASC')
            ->get('tblmarketplace')->result_array();
    }

    /** Beberapa scan terakhir, ditampilkan sebagai riwayat di halaman scan. */
    public function get_scan_terakhir($limit = 15)
    {
        return $this->db->select('noresi, nama_marketplace, status_marketplace, tanggal_pesan, tanggal_cancel, sumber')
            ->order_by('updated_at', 'DESC')
            ->limit((int) $limit)
            ->get('tblcancelorder')->result_array();
    }
}
