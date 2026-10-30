<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model menu TIM PACKER -> "Penerimaan Transfer".
 *
 * Packer menghitung ulang pinset AKS27-RDM yang diantar inbound dan memasukkan
 * qty real. Satu transfer hanya diterima SEKALI: berapa pun qty real, transfer
 * langsung selesai; kurang/lebihnya jadi selisih (qty_terima - qty_kirim) yang
 * dilihat Purchasing di menu Transfer Stok. Tambahan stok sesudahnya lewat
 * Penyesuaian (belum dibuat). Tabel: tbltransfer_stok_terima (transfer_id UNIQUE)
 * dan tbltransfer_stok.qty_terima. Baris tidak pernah dihapus.
 */
class Penerimaan_transfer_fcd extends CI_Model
{
    const PER_HALAMAN_BOLEH = [10, 20, 50];
    const QTY_MAKS          = 1000000;

    /** Saldo stok packing. Sementara = total qty yang pernah diterima (Kartu Stok akan mengambil alih). */
    public function saldo()
    {
        $row = $this->db->select('COALESCE(SUM(qty), 0) AS total', FALSE)->get('tbltransfer_stok_terima')->row_array();
        return (int) $row['total'];
    }

    /** Transfer yang belum diterima, terlama dulu. */
    public function menunggu()
    {
        $rows = $this->db->select('id, tanggal, qty_kirim, keterangan')
            ->where('qty_terima', 0)
            ->order_by('id', 'ASC')
            ->get('tbltransfer_stok')->result_array();
        foreach ($rows as &$r) {
            $r['no']        = 'TR-' . $r['id'];
            $r['qty_kirim'] = (int) $r['qty_kirim'];
        }
        unset($r);
        return $rows;
    }

    /**
     * @param array $f q, dari, sampai (Y-m-d), per, halaman
     * @return array rows, total, halaman, jml_hal, dari, sampai
     */
    public function riwayat(array $f)
    {
        $per = (int) (isset($f['per']) ? $f['per'] : 10);
        if (!in_array($per, self::PER_HALAMAN_BOLEH, TRUE)) {
            $per = 10;
        }

        $this->terapkan_filter($f);
        $total   = (int) $this->db->count_all_results();
        $jml_hal = max(1, (int) ceil($total / $per));
        $halaman = min(max(1, (int) (isset($f['halaman']) ? $f['halaman'] : 1)), $jml_hal);

        $this->terapkan_filter($f);
        $rows = $this->db->select("r.id, r.transfer_id, r.tanggal, r.jam, r.qty, COALESCE(NULLIF(u.name, ''), u.username, '') AS penerima", FALSE)
            ->order_by('r.id', 'DESC')
            ->limit($per, ($halaman - 1) * $per)
            ->get()->result_array();
        foreach ($rows as &$r) {
            $r['no']  = 'TR-' . $r['transfer_id'];
            $r['qty'] = (int) $r['qty'];
        }
        unset($r);

        $mulai = ($halaman - 1) * $per;
        return [
            'rows'    => $rows,
            'total'   => $total,
            'halaman' => $halaman,
            'jml_hal' => $jml_hal,
            'dari'    => $total ? $mulai + 1 : 0,
            'sampai'  => $total ? $mulai + count($rows) : 0,
        ];
    }

    private function terapkan_filter(array $f)
    {
        $this->db->from('tbltransfer_stok_terima r')->join('tbluser u', 'u.id_user = r.createdby', 'left');
        if (!empty($f['dari']) && Transfer_stok_fcd::tanggal_sah($f['dari'])) {
            $this->db->where('r.tanggal >=', $f['dari']);
        }
        if (!empty($f['sampai']) && Transfer_stok_fcd::tanggal_sah($f['sampai'])) {
            $this->db->where('r.tanggal <=', $f['sampai']);
        }
        $q = isset($f['q']) ? trim((string) $f['q']) : '';
        if ($q !== '') {
            $teks = "CONCAT_WS(' ', CONCAT('TR-', r.transfer_id), r.tanggal, DATE_FORMAT(r.tanggal, '%d/%m/%Y'),"
                . " IFNULL(r.jam, ''), r.qty, COALESCE(NULLIF(u.name, ''), u.username, ''))";
            $this->db->where($teks . " LIKE '%" . $this->db->escape_like_str($q) . "%' ESCAPE '!'", NULL, FALSE);
        }
    }

    /** 2026-09-20 -> "20 Sep 2026" (sama dengan tampilan tabel). */
    private function tanggal_indo($tanggal)
    {
        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $t = strtotime($tanggal);
        return (int) date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
    }

    /**
     * Terima satu transfer. Jam (jam server) diisi hanya kalau tanggal diterima = hari ini.
     * @return array ['ok' => bool, 'pesan' => string, 'tipe' => 'success'|'warning'|'danger']
     */
    public function terima($id, $tanggal, $qty, $id_user)
    {
        $gagal = function ($pesan) {
            return ['ok' => FALSE, 'pesan' => $pesan, 'tipe' => 'danger'];
        };

        $id = (int) $id;
        if ($id <= 0 || !$this->db->where('id', $id)->count_all_results('tbltransfer_stok')) {
            return $gagal('Pilih transfer lebih dulu.');
        }
        if (!preg_match('/^\d+$/', trim((string) $qty)) || (int) $qty <= 0) {
            return $gagal('Qty real harus bilangan bulat lebih dari 0.');
        }
        $qty = (int) $qty;
        if ($qty > self::QTY_MAKS) {
            return $gagal('Qty real terlalu besar. Cek lagi angkanya.');
        }
        $tanggal = trim((string) $tanggal);
        if (!Transfer_stok_fcd::tanggal_sah($tanggal)) {
            return $gagal('Tanggal diterima wajib diisi.');
        }
        if ($tanggal > date('Y-m-d')) {
            return $gagal('Tanggal diterima tidak boleh lebih dari hari ini.');
        }

        // Baris transfer dikunci supaya dua packer yang menekan Terima bersamaan tidak sama-sama lolos.
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_start();
        $t = $this->db->query('SELECT id, tanggal, qty_kirim, qty_terima FROM tbltransfer_stok WHERE id = ? FOR UPDATE', [$id])->row_array();
        $pesan_gagal = NULL;
        if (!$t) {
            $pesan_gagal = 'Pilih transfer lebih dulu.';
        } elseif ($tanggal < $t['tanggal']) {
            $pesan_gagal = 'Tanggal diterima tidak boleh sebelum tanggal transfer (' . $this->tanggal_indo($t['tanggal']) . ').';
        } elseif ((int) $t['qty_terima'] > 0) {
            $pesan_gagal = 'TR-' . $id . ' sudah diterima. Tambahan stok dibuat lewat Penyesuaian.';
        }

        if ($pesan_gagal === NULL) {
            $this->db->insert('tbltransfer_stok_terima', [
                'transfer_id' => $id,
                'tanggal'     => $tanggal,
                'jam'         => $tanggal === date('Y-m-d') ? date('H:i:s') : NULL,
                'qty'         => $qty,
                'created'     => date('Y-m-d H:i:s'),
                'createdby'   => (int) $id_user,
            ]);
            $this->db->where('id', $id)->update('tbltransfer_stok', [
                'qty_terima' => $qty,
                'updated'    => date('Y-m-d H:i:s'),
                'updatedby'  => (int) $id_user,
            ]);
        }
        $this->db->trans_complete();
        $galat_db = !$this->db->trans_status();
        $this->db->db_debug = $debug;

        if ($pesan_gagal !== NULL) {
            return $gagal($pesan_gagal);
        }
        if ($galat_db) {
            return $gagal('Gagal menyimpan penerimaan. Coba lagi.');
        }

        $df = $qty - (int) $t['qty_kirim'];
        $no = 'TR-' . $id;
        if ($df === 0) {
            return ['ok' => TRUE, 'pesan' => $no . ' selesai, jumlah pas.', 'tipe' => 'success'];
        }
        return [
            'ok'    => TRUE,
            'tipe'  => 'warning',
            'pesan' => $no . ' selesai, ' . ($df > 0 ? 'lebih ' . $df . ' diterima apa adanya' : 'kurang ' . (-$df) . ' tercatat sebagai selisih') . '. Purchasing perlu tahu.',
        ];
    }
}
