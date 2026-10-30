<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model menu TIM PURCHASING -> "Transfer Stok".
 *
 * Purchasing mencatat pinset AKS27-RDM yang dikirim ke lokasi packer. Saldo
 * packing belum berubah saat transfer dibuat; qty_terima diisi menu Penerimaan
 * Transfer (satu transfer diterima sekali). Status hanya dua: "Dalam
 * perjalanan" selama qty_terima = 0, lalu "Selesai". Kurang/lebihnya terlihat di
 * kolom selisih. Tabel: tbltransfer_stok. Baris tidak pernah dihapus.
 */
class Transfer_stok_fcd extends CI_Model
{
    const PER_HALAMAN_BOLEH = [10, 20, 50];
    const QTY_MAKS          = 1000000;

    /**
     * @param array $f q, dari, sampai (Y-m-d), per, halaman
     * @return array rows, total, halaman, jml_hal, dari, sampai
     */
    public function daftar(array $f)
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
        $rows = $this->db->select('t.id, t.tanggal, t.jam, t.qty_kirim, t.qty_terima, t.keterangan, r.tanggal AS tgl_terima, r.jam AS jam_terima')
            ->order_by('t.id', 'DESC')
            ->limit($per, ($halaman - 1) * $per)
            ->get()->result_array();

        foreach ($rows as &$r) {
            $r['no']         = 'TR-' . $r['id'];
            $r['qty_kirim']  = (int) $r['qty_kirim'];
            $r['qty_terima'] = (int) $r['qty_terima'];
            $r['selesai']    = $r['qty_terima'] > 0 ? 1 : 0;
            $r['selisih']    = $r['qty_terima'] > 0 ? $r['qty_terima'] - $r['qty_kirim'] : NULL;
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
        // Tgl/jam terima dari tbltransfer_stok_terima (satu baris per transfer, diisi menu Penerimaan Transfer).
        $this->db->from('tbltransfer_stok t')->join('tbltransfer_stok_terima r', 'r.transfer_id = t.id', 'left');
        if (!empty($f['dari']) && self::tanggal_sah($f['dari'])) {
            $this->db->where('t.tanggal >=', $f['dari']);
        }
        if (!empty($f['sampai']) && self::tanggal_sah($f['sampai'])) {
            $this->db->where('t.tanggal <=', $f['sampai']);
        }
        $q = isset($f['q']) ? trim((string) $f['q']) : '';
        if ($q !== '') {
            // Satu teks pencarian untuk semua yang tampil di tabel: no TR, tanggal, jam,
            // qty, status, selisih, tgl/jam terima, keterangan.
            $teks = "CONCAT_WS(' ', CONCAT('TR-', t.id), t.tanggal, DATE_FORMAT(t.tanggal, '%d/%m/%Y'), IFNULL(t.jam, ''),"
                . " t.qty_kirim, t.qty_terima, t.keterangan,"
                . " IF(t.qty_terima > 0, 'selesai', 'dalam perjalanan'),"
                . " IF(t.qty_terima = 0, '', IF(t.qty_terima < t.qty_kirim, CONCAT('kurang ', t.qty_kirim - t.qty_terima),"
                . " IF(t.qty_terima > t.qty_kirim, CONCAT('lebih ', t.qty_terima - t.qty_kirim), 'pas'))),"
                . " IFNULL(r.tanggal, ''), IFNULL(DATE_FORMAT(r.tanggal, '%d/%m/%Y'), ''), IFNULL(r.jam, ''))";
            $this->db->where($teks . " LIKE '%" . $this->db->escape_like_str($q) . "%' ESCAPE '!'", NULL, FALSE);
        }
    }

    public static function tanggal_sah($t)
    {
        $d = DateTime::createFromFormat('Y-m-d', (string) $t);
        return $d && $d->format('Y-m-d') === $t;
    }

    /**
     * Buat satu transfer. Jam diisi (jam server saat ini) hanya kalau tanggal
     * transfer = hari ini; tanggal lain jamnya dikosongkan.
     * @return array ['ok' => bool, 'pesan' => string, 'no' => string|null]
     */
    public function simpan($tanggal, $qty, $keterangan, $id_user)
    {
        $tanggal = trim((string) $tanggal);
        if (!self::tanggal_sah($tanggal)) {
            return ['ok' => FALSE, 'pesan' => 'Tanggal transfer wajib diisi.', 'no' => NULL];
        }
        if ($tanggal > date('Y-m-d')) {
            return ['ok' => FALSE, 'pesan' => 'Tanggal transfer tidak boleh lebih dari hari ini.', 'no' => NULL];
        }
        if (!preg_match('/^\d+$/', trim((string) $qty)) || (int) $qty <= 0) {
            return ['ok' => FALSE, 'pesan' => 'Qty kirim harus bilangan bulat lebih dari 0.', 'no' => NULL];
        }
        $qty = (int) $qty;
        if ($qty > self::QTY_MAKS) {
            return ['ok' => FALSE, 'pesan' => 'Qty kirim terlalu besar. Cek lagi angkanya.', 'no' => NULL];
        }
        $keterangan = trim((string) $keterangan);
        if ($keterangan === '') {
            $keterangan = 'transfer stok';
        }
        $keterangan = mb_substr($keterangan, 0, 255);

        $this->db->insert('tbltransfer_stok', [
            'tanggal'    => $tanggal,
            'jam'        => $tanggal === date('Y-m-d') ? date('H:i:s') : NULL,
            'qty_kirim'  => $qty,
            'qty_terima' => 0,
            'keterangan' => $keterangan,
            'created'    => date('Y-m-d H:i:s'),
            'createdby'  => (int) $id_user,
        ]);
        $no = 'TR-' . $this->db->insert_id();
        return [
            'ok'    => TRUE,
            'pesan' => $no . ' dibuat (' . $qty . ' pcs). Saldo packing belum berubah sampai packer menerima.',
            'no'    => $no,
        ];
    }
}
