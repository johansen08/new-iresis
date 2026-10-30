<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model menu TIM PURCHASING -> "Daftar SKU Bonus".
 *
 * Daftar SKU yang kalau ada di sebuah resi membuat resi itu mendapat 1 pinset
 * AKS27-RDM (qty dan jumlah SKU tidak mengubah apa pun: tetap 1 pinset per
 * resi). Tabel: tblsku_bonus (sku UNIQUE). SKU tidak pernah dihapus, hanya
 * dinonaktifkan lewat kolom aktif. Sumber kode SKU yang sah adalah master tblsku.
 */
class Sku_bonus_fcd extends CI_Model
{
    const PER_HALAMAN = 10;

    /** Kode barang bonusnya sendiri; tidak boleh masuk daftar pemicu. */
    const KODE_BONUS = 'AKS27-RDM';

    /** @return array [rows, total, halaman, jumlah_halaman] */
    public function daftar($q, $halaman)
    {
        $q = trim((string) $q);
        $this->db->from('tblsku_bonus');
        if ($q !== '') {
            $this->db->like('sku', $q);
        }
        $total   = (int) $this->db->count_all_results();
        $jml_hal = max(1, (int) ceil($total / self::PER_HALAMAN));
        $halaman = min(max(1, (int) $halaman), $jml_hal);

        $this->db->select('id, sku, aktif')->from('tblsku_bonus');
        if ($q !== '') {
            $this->db->like('sku', $q);
        }
        $rows = $this->db->order_by('sku', 'ASC')
            ->limit(self::PER_HALAMAN, ($halaman - 1) * self::PER_HALAMAN)
            ->get()->result_array();

        return [
            'rows'    => $rows,
            'total'   => $total,
            'halaman' => $halaman,
            'jml_hal' => $jml_hal,
            'dari'    => $total ? (($halaman - 1) * self::PER_HALAMAN) + 1 : 0,
            'sampai'  => $total ? (($halaman - 1) * self::PER_HALAMAN) + count($rows) : 0,
        ];
    }

    /** Saran SKU standar dari master tblsku yang belum ada di daftar bonus. */
    public function saran($q, $batas = 6)
    {
        $q = trim((string) $q);
        $this->db->select('s.id_sku, s.nama_sku')
            ->from('tblsku s')
            ->join('tblsku_bonus b', 'b.sku = s.id_sku', 'left')
            ->where('b.id IS NULL', NULL, FALSE)
            ->where('s.id_sku !=', self::KODE_BONUS);
        if ($q !== '') {
            $this->db->like('s.id_sku', $q);
        }
        return $this->db->order_by('s.id_sku', 'ASC')->limit($batas)->get()->result_array();
    }

    /** Baris daftar bonus untuk satu kode (perbandingan tidak peka huruf besar/kecil), atau NULL. */
    public function cari($sku)
    {
        $row = $this->db->get_where('tblsku_bonus', ['sku' => $sku])->row_array();
        return $row ?: NULL;
    }

    /** Kode kanonik dari master tblsku, atau NULL kalau tidak ada di master. */
    public function kode_master($sku)
    {
        $row = $this->db->select('id_sku')->get_where('tblsku', ['id_sku' => $sku])->row_array();
        return $row ? $row['id_sku'] : NULL;
    }

    /**
     * Tambah satu SKU. Pelindung duplikat berlapis: cek di sini, lalu UNIQUE
     * pada tblsku_bonus.sku kalau dua pengguna menambah bersamaan.
     * @return array ['ok' => bool, 'pesan' => string, 'sku' => string|null]
     */
    public function tambah($sku, $id_user)
    {
        $sku = preg_replace('/\s+/', '', strtoupper(trim((string) $sku)));
        if ($sku === '') {
            return ['ok' => FALSE, 'pesan' => 'Isi kode SKU dulu.', 'sku' => NULL];
        }
        if ($sku === self::KODE_BONUS) {
            return ['ok' => FALSE, 'pesan' => self::KODE_BONUS . ' adalah barang bonusnya, bukan SKU pemicu.', 'sku' => NULL];
        }

        $kode = $this->kode_master($sku);
        if ($kode === NULL) {
            return ['ok' => FALSE, 'pesan' => $sku . ' tidak ada di master SKU. Pilih dari rekomendasi.', 'sku' => NULL];
        }

        $ada = $this->cari($kode);
        if ($ada) {
            return [
                'ok'    => FALSE,
                'pesan' => 'Ditolak: ' . $kode . ' sudah ada di daftar' . ($ada['aktif'] ? '.' : ' dengan status nonaktif. Aktifkan saja dari tabel.'),
                'sku'   => $kode,
            ];
        }

        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->insert('tblsku_bonus', [
            'sku'       => $kode,
            'aktif'     => 1,
            'createdby' => (int) $id_user,
            'created'   => date('Y-m-d H:i:s'),
        ]);
        $galat = $this->db->error();
        $this->db->db_debug = $debug;

        if (!empty($galat['code'])) {
            if ((int) $galat['code'] === 1062) {
                return ['ok' => FALSE, 'pesan' => 'Ditolak: ' . $kode . ' sudah ada di daftar.', 'sku' => $kode];
            }
            return ['ok' => FALSE, 'pesan' => 'Gagal menyimpan SKU. Coba lagi.', 'sku' => NULL];
        }
        return ['ok' => TRUE, 'pesan' => $kode . ' ditambahkan.', 'sku' => $kode];
    }

    /** Balik status aktif satu baris. @return array|null baris sesudah diubah */
    public function ubah_status($id, $id_user)
    {
        $row = $this->db->get_where('tblsku_bonus', ['id' => (int) $id])->row_array();
        if (!$row) {
            return NULL;
        }
        $baru = $row['aktif'] ? 0 : 1;
        $this->db->where('id', (int) $id)->update('tblsku_bonus', [
            'aktif'     => $baru,
            'updatedby' => (int) $id_user,
            'updated'   => date('Y-m-d H:i:s'),
        ]);
        $row['aktif'] = $baru;
        return $row;
    }
}
