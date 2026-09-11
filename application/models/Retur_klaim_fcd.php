<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model klaim retur ke kurir / marketplace (tabel `tblreturklaim`).
 *
 * Alur status:
 *   DIAJUKAN -> DISETUJUI | SEBAGIAN | DITOLAK -> DIGANTI -> SELESAI
 *   (+ BATAL, dipakai bila barangnya ternyata datang belakangan)
 *
 * Baris hanya dibuat saat klaim benar-benar diajukan. Resi yang layak klaim
 * tapi belum diajukan TIDAK punya baris di sini — daftarnya dihitung live oleh
 * Retur_fcd::rekap_kandidat_klaim().
 */
class Retur_klaim_fcd extends CI_Model
{
    /** Status yang berarti klaim sudah selesai diproses (tidak bisa diubah lagi). */
    public $status_final = array('SELESAI', 'BATAL');

    /** Status yang sah untuk kolom status_klaim. */
    public $status_valid = array('DIAJUKAN', 'DISETUJUI', 'SEBAGIAN', 'DITOLAK', 'DIGANTI', 'SELESAI', 'BATAL');

    /** Jenis klaim yang sah. */
    public $jenis_valid = array('HILANG', 'ISI_KURANG', 'BARANG_TIDAK_ADA', 'RUSAK');

    /** Bentuk pergantian yang sah. */
    public $bentuk_valid = array('UANG', 'POTONG_TAGIHAN', 'BARANG');

    // ---------------------------------------------------------------- ajukan

    /**
     * Simpan pengajuan klaim (bisa banyak resi sekaligus).
     * Resi yang sudah punya klaim dilewati dan dilaporkan balik — UNIQUE
     * uq_klaim_resi jadi penjaga terakhir kalau ada dua request bersamaan.
     *
     * @param  array $rows  baris kandidat dari Retur_fcd::rekap_kandidat_by_resi()
     * @param  array $meta  ['no_tiket', 'jenis_klaim', 'catatan']
     * @return array ['inserted' => int, 'skipped' => string[]]
     */
    public function save_ajukan(array $rows, array $meta, $user_id)
    {
        $inserted = 0;
        $skipped  = array();
        $now      = date('Y-m-d H:i:s');

        foreach ($rows as $r) {
            $no_resi = trim($r->no_resi);
            if ($no_resi === '') continue;

            if ($this->db->where('no_resi', $no_resi)->count_all_results('tblreturklaim') > 0) {
                $skipped[] = $no_resi;
                continue;
            }

            // Jenis klaim mengikuti kondisi barang bila tidak dipilih manual.
            $jenis = !empty($meta['jenis_klaim']) ? $meta['jenis_klaim'] : $this->tebak_jenis($r);

            // Yang diklaim adalah bagian yang TIDAK kembali, bukan seluruh isi paket.
            // Untuk isi kurang (sebagian barang kembali), nilainya diprorata sesuai
            // qty yang hilang; kalau tidak ada satu pun yang kembali, nilai penuh.
            $qty_mp    = max(1, (int) $r->qty_jubelio);
            $qty_klaim = max(0, (int) $r->qty_jubelio - (int) $r->qty_buka);
            $nilai     = (float) $r->nilai;
            if ($qty_klaim > 0 && $qty_klaim < $qty_mp) {
                $nilai = round($nilai * $qty_klaim / $qty_mp, 2);
            }

            $data = array(
                'no_resi'       => $no_resi,
                'no_pesanan'    => $r->no_pesanan,
                'jenis_klaim'   => $jenis,
                'kurir'         => $r->kurir,
                'marketplace'   => $r->marketplace,
                'nama_toko'     => $r->nama_toko,
                'tanggal_retur' => $r->tanggal_retur,
                'qty_klaim'     => $qty_klaim,
                'nilai_klaim'   => $nilai,
                'status_klaim'  => 'DIAJUKAN',
                'no_tiket'      => !empty($meta['no_tiket']) ? $meta['no_tiket'] : null,
                'tanggal_ajuan' => $now,
                'diajukan_by'   => $user_id,
                'catatan'       => !empty($meta['catatan']) ? $meta['catatan'] : null,
                'created_at'    => $now,
            );

            // Insert race-safe: bentrok UNIQUE (dua petugas menekan Ajukan
            // bersamaan) dihitung sebagai "dilewati", bukan error.
            $prev_debug = $this->db->db_debug;
            $this->db->db_debug = FALSE;
            $ok  = $this->db->insert('tblreturklaim', $data);
            $err = $this->db->error();
            $this->db->db_debug = $prev_debug;

            if (!$ok || (isset($err['code']) && (int) $err['code'] === 1062)) {
                $skipped[] = $no_resi;
                continue;
            }
            $inserted++;
        }

        return array('inserted' => $inserted, 'skipped' => $skipped);
    }

    /** Tebak jenis klaim dari kondisi barang bila petugas tidak memilih. */
    public function tebak_jenis($row)
    {
        $status = strtoupper((string) (isset($row->status_list) ? $row->status_list : ''));

        if (strpos($status, 'BARANG_TIDAK_ADA') !== false) return 'BARANG_TIDAK_ADA';
        if ((int) $row->qty_buka > 0) return 'ISI_KURANG';

        return 'HILANG';
    }

    // ------------------------------------------------------------------ baca

    public function get_klaim($id)
    {
        return $this->db->get_where('tblreturklaim', array('id_klaim' => (int) $id))->row();
    }

    private function _build_list($params, $count_only = false)
    {
        if ($count_only) {
            $this->db->select('COUNT(*) AS n, COALESCE(SUM(k.nilai_klaim),0) AS nilai_klaim,'
                . ' COALESCE(SUM(k.nominal_diterima),0) AS nilai_diterima', false);
        } else {
            $this->db->select('k.*, ua.username AS nama_pengaju, uf.username AS nama_finance,'
                . ' DATEDIFF(NOW(), k.tanggal_ajuan) AS umur_ajuan', false);
        }

        $this->db->from('tblreturklaim k');

        if (!$count_only) {
            $this->db->join('tbluser ua', 'ua.id_user = k.diajukan_by', 'left');
            $this->db->join('tbluser uf', 'uf.id_user = k.finance_by', 'left');
        }

        if (!empty($params['status'])) {
            $this->db->where('k.status_klaim', $params['status']);
        }
        if (!empty($params['kurir'])) {
            $this->db->where('k.kurir', $params['kurir']);
        }
        if (isset($params['finance_verified']) && $params['finance_verified'] !== '') {
            $this->db->where('k.finance_verified', (int) $params['finance_verified']);
        }
        // Menu finance hanya mengurus klaim yang pergantiannya sudah dicatat.
        if (!empty($params['siap_verifikasi'])) {
            $this->db->where_in('k.status_klaim', array('DIGANTI', 'SELESAI'));
        }
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $this->db->where('k.tanggal_ajuan >=', $params['start_date']);
            $this->db->where('k.tanggal_ajuan <=', $params['end_date']);
        }
        if (!empty($params['search'])) {
            $s = $params['search'];
            $this->db->group_start()
                ->like('k.no_resi', $s)
                ->or_like('k.no_pesanan', $s)
                ->or_like('k.no_tiket', $s)
                ->or_like('k.kurir', $s)
                ->or_like('k.nama_toko', $s)
                ->group_end();
        }

        return $this->db;
    }

    public function get_klaim_list($params)
    {
        $this->_build_list($params);

        $order = !empty($params['order']) ? $params['order'] : 'k.tanggal_ajuan';
        $dir   = (isset($params['dir']) && strtolower($params['dir']) === 'asc') ? 'ASC' : 'DESC';
        $this->db->order_by($order . ' ' . $dir);

        if (isset($params['length']) && (int) $params['length'] > 0) {
            $this->db->limit((int) $params['length'], (int) $params['start']);
        }

        return $this->db->get()->result();
    }

    public function get_klaim_total($params)
    {
        return $this->_build_list($params, true)->get()->row();
    }

    /** Ringkasan per status + recovery rate untuk kartu dashboard. */
    public function stats($start = null, $end = null, $kurir = null)
    {
        $this->db->select('status_klaim, COUNT(*) AS n, COALESCE(SUM(nilai_klaim),0) AS nilai,'
            . ' COALESCE(SUM(nominal_diterima),0) AS diterima,'
            . ' SUM(finance_verified = 1) AS terverifikasi', false)
            ->from('tblreturklaim');

        if (!empty($start) && !empty($end)) {
            $this->db->where('tanggal_ajuan >=', $start)->where('tanggal_ajuan <=', $end);
        }
        if (!empty($kurir)) {
            $this->db->where('kurir', $kurir);
        }

        return $this->db->group_by('status_klaim')->get()->result();
    }

    /** Recovery rate per kurir: berapa rupiah yang benar-benar kembali. */
    public function recovery_by_kurir($start = null, $end = null)
    {
        $this->db->select("COALESCE(NULLIF(kurir,''),'(tanpa kurir)') AS label,"
            . ' COUNT(*) AS n_klaim,'
            . ' COALESCE(SUM(nilai_klaim),0) AS diklaim,'
            . ' COALESCE(SUM(CASE WHEN finance_verified = 1 THEN nominal_diterima ELSE 0 END),0) AS diterima', false)
            ->from('tblreturklaim');

        if (!empty($start) && !empty($end)) {
            $this->db->where('tanggal_ajuan >=', $start)->where('tanggal_ajuan <=', $end);
        }

        return $this->db->group_by('label')->order_by('diklaim', 'DESC')->get()->result();
    }

    /**
     * Alarm: resi yang barangnya baru discan buka SETELAH klaim diajukan.
     * Kalau klaimnya sudah dibayar, perusahaan dapat ganti rugi sekaligus
     * barangnya — harus ditindaklanjuti (batalkan klaim / kembalikan dana).
     *
     * Syarat "setelah tanggal_ajuan" itu penting: klaim ISI_KURANG memang punya
     * baris buka (sebagian barang kembali sebelum klaim), dan itu bukan alarm.
     */
    public function alarm_barang_datang($limit = 50)
    {
        $sql = "SELECT k.id_klaim, k.no_resi, k.kurir, k.status_klaim, k.nilai_klaim,
                       k.nominal_diterima, k.finance_verified,
                       MIN(b.tanggal_buka_retur) AS tanggal_datang
                FROM tblreturklaim k
                JOIN tblbukaretur b
                  ON b.resi_buka = k.no_resi
                 AND b.tanggal_buka_retur > k.tanggal_ajuan
                WHERE k.status_klaim <> 'BATAL'
                GROUP BY k.id_klaim, k.no_resi, k.kurir, k.status_klaim, k.nilai_klaim,
                         k.nominal_diterima, k.finance_verified
                ORDER BY k.finance_verified DESC, tanggal_datang DESC
                LIMIT " . (int) $limit;

        return $this->db->query($sql)->result();
    }

    public function alarm_count()
    {
        $row = $this->db->query(
            "SELECT COUNT(DISTINCT k.id_klaim) AS n
             FROM tblreturklaim k
             JOIN tblbukaretur b
               ON b.resi_buka = k.no_resi
              AND b.tanggal_buka_retur > k.tanggal_ajuan
             WHERE k.status_klaim <> 'BATAL'"
        )->row();

        return $row ? (int) $row->n : 0;
    }

    /** Daftar kurir yang punya klaim (dropdown filter). */
    public function kurir_list()
    {
        return $this->db->select('DISTINCT kurir', false)
            ->from('tblreturklaim')
            ->where('kurir IS NOT NULL', null, false)
            ->where("kurir <> ''", null, false)
            ->order_by('kurir')
            ->get()->result();
    }

    // ------------------------------------------------------------------ ubah

    /** Update dengan penjaga PK — tolak id kosong/0 supaya tidak mass-update. */
    private function _update($id, array $data)
    {
        $id = (int) $id;
        if ($id <= 0) return false;

        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id_klaim', $id);
        $this->db->limit(1);

        return $this->db->update('tblreturklaim', $data);
    }

    public function update_putusan($id, $status, $tanggal, $nominal, $alasan, $user_id)
    {
        return $this->_update($id, array(
            'status_klaim'      => $status,
            'tanggal_putusan'   => $tanggal,
            'nominal_disetujui' => ($nominal === null || $nominal === '') ? null : (float) $nominal,
            'alasan_tolak'      => ($alasan === null || $alasan === '') ? null : $alasan,
            'putusan_by'        => $user_id,
            'updated_by'        => $user_id,
        ));
    }

    public function update_pergantian($id, $bentuk, $tanggal, $nominal, $bukti, $user_id)
    {
        return $this->_update($id, array(
            'status_klaim'       => 'DIGANTI',
            'bentuk_pergantian'  => $bentuk,
            'tanggal_pergantian' => $tanggal,
            'nominal_diterima'   => ($nominal === null || $nominal === '') ? null : (float) $nominal,
            'bukti'              => ($bukti === null || $bukti === '') ? null : $bukti,
            'pergantian_by'      => $user_id,
            'updated_by'         => $user_id,
        ));
    }

    public function verifikasi_finance($id, $verified, $catatan, $user_id)
    {
        $verified = $verified ? 1 : 0;

        return $this->_update($id, array(
            'finance_verified' => $verified,
            'finance_by'       => $verified ? $user_id : null,
            'finance_at'       => $verified ? date('Y-m-d H:i:s') : null,
            'finance_catatan'  => ($catatan === null || $catatan === '') ? null : $catatan,
            // Verifikasi finance = penutup siklus; dicabut kembali ke DIGANTI.
            'status_klaim'     => $verified ? 'SELESAI' : 'DIGANTI',
            'updated_by'       => $user_id,
        ));
    }

    public function batal_klaim($id, $alasan, $user_id)
    {
        return $this->_update($id, array(
            'status_klaim' => 'BATAL',
            'catatan'      => $alasan,
            'updated_by'   => $user_id,
        ));
    }
}
