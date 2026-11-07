<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu HO > Terima Retur (docs/TERIMA_RETUR_HO.md).
 *
 * Satu scan = satu resi = satu baris tblresiretur berstatus "Terima Retur".
 * Dua jenis, dibedakan HANYA oleh kolom is_komplain (struktur data sama dengan
 * menu Scan Retur / Scan Retur Komplain lama, sehingga Buka Retur, laporan, dan
 * rekonsiliasi yang sudah ada tetap membaca baris ini):
 *   EKSPEDISI -> is_komplain = 0  (gagal kirim, paket dikembalikan kurir)
 *   KOMPLAIN  -> is_komplain = 1  (pengembalian barang oleh pembeli)
 *
 * Beda dengan Retur_fcd::save_terima_retur (dipakai menu lama, tidak diubah):
 *   - hasil berupa KODE terstruktur supaya layar bisa memilih suara yang tepat,
 *     bukan kalimat yang harus diurai;
 *   - tidak ada mode update (menu lama me-reset baris dan menghapus tblbukaretur);
 *   - pengecekan double + INSERT dijaga GET_LOCK per resi, karena tblresiretur
 *     tidak punya unique key (id_resi, is_komplain) dan scan beruntun bisa
 *     menembus pengecekan SELECT-lalu-INSERT.
 */
class Terima_retur_ho_fcd extends CI_Model
{
    const JENIS_EKSPEDISI = 'ekspedisi';
    const JENIS_KOMPLAIN  = 'komplain';

    const KODE_OK         = 'ok';
    const KODE_DOUBLE     = 'double';
    const KODE_SALAH      = 'salah_jenis';
    const KODE_CANCEL     = 'cancel';
    const KODE_TIDAK_ADA  = 'tidak_ditemukan';
    const KODE_KOSONG     = 'kosong';
    const KODE_JENIS      = 'jenis_belum_dipilih';
    const KODE_GAGAL      = 'gagal';

    public static function label_jenis($jenis)
    {
        return $jenis === self::JENIS_KOMPLAIN ? 'Retur Komplain' : 'Retur Ekspedisi';
    }

    /** 'ekspedisi'|'komplain' -> 0|1; selain itu null. */
    public static function nilai_komplain($jenis)
    {
        if ($jenis === self::JENIS_EKSPEDISI) {
            return 0;
        }
        if ($jenis === self::JENIS_KOMPLAIN) {
            return 1;
        }
        return null;
    }

    /**
     * Terima satu resi.
     *
     * @return array ['kode', 'message', 'noresi', 'resi_balik' (atau null), 'kurir', 'marketplace', 'status_pesanan']
     */
    public function terima($input, $jenis, $id_user)
    {
        $input = trim((string) $input);
        $hasil = ['kode' => '', 'message' => '', 'noresi' => $input, 'resi_balik' => null,
                  'kurir' => null, 'marketplace' => null, 'status_pesanan' => null];

        $is_komplain = self::nilai_komplain($jenis);
        if ($is_komplain === null) {
            $hasil['kode']    = self::KODE_JENIS;
            $hasil['message'] = 'Pilih dulu jenis retur (Ekspedisi atau Komplain), lalu scan ulang.';
            return $hasil;
        }
        if ($input === '') {
            $hasil['kode']    = self::KODE_KOSONG;
            $hasil['message'] = 'Resi kosong.';
            return $hasil;
        }

        // Resi balik kurir (JZ... dst.) -> resi pengiriman Iresis lewat tblreturjubelio;
        // resi lama yang sudah diarsipkan ikut ditarik ke prod (docs/ARSIP_DATA.md).
        $noresi = resolusi_noresi_retur($input);
        if ($noresi !== $input) {
            $hasil['resi_balik'] = $input;
        }
        $hasil['noresi'] = $noresi;

        $resi = $this->db
            ->select('id_printresi, id_kurir, id_marketplace, noresi, status_pesanan, batal')
            ->get_where('tblprintresi', ['noresi' => $noresi])
            ->row_array();

        if (empty($resi)) {
            $hasil['kode']    = self::KODE_TIDAK_ADA;
            $hasil['message'] = "Resi $noresi tidak ditemukan.";
            return $hasil;
        }

        $hasil['kurir']          = $this->nama('tblkurir', 'id_kurir', 'nama_kurir', $resi['id_kurir']);
        $hasil['marketplace']    = $this->nama('tblmarketplace', 'id_marketplace', 'nama_marketplace', $resi['id_marketplace']);
        $hasil['status_pesanan'] = $resi['status_pesanan'];

        $status = strtoupper(trim((string) $resi['status_pesanan']));
        if (strpos($status, 'CANCEL') !== false || (string) $resi['batal'] === '1') {
            $hasil['kode']    = self::KODE_CANCEL;
            $hasil['message'] = 'Paket berstatus CANCEL, tidak dapat diterima sebagai retur.';
            return $hasil;
        }

        // Kunci per resi: dua scan resi yang sama yang tiba bersamaan (scan beruntun,
        // dua PC) tidak boleh sama-sama lolos cek double lalu sama-sama INSERT.
        $kunci = 'terima_retur_' . (int) $resi['id_printresi'];
        $dapat = $this->db->query('SELECT GET_LOCK(?, 5) AS k', [$kunci])->row();
        if (!$dapat || (int) $dapat->k !== 1) {
            $hasil['kode']    = self::KODE_GAGAL;
            $hasil['message'] = 'Server sibuk memproses resi ini. Scan ulang.';
            return $hasil;
        }

        try {
            $id_resi = (int) $resi['id_printresi'];

            $ada = $this->cari_baris($id_resi, $noresi, $is_komplain);
            if ($ada) {
                $hasil['kode']    = self::KODE_DOUBLE;
                $hasil['message'] = 'Sudah diterima di ' . self::label_jenis($jenis)
                    . ' pada ' . $ada['tanggal_resiretur'] . '.';
                return $hasil;
            }

            $lawan = $this->cari_baris($id_resi, $noresi, $is_komplain ? 0 : 1);
            if ($lawan) {
                $jenis_lawan      = $is_komplain ? self::JENIS_EKSPEDISI : self::JENIS_KOMPLAIN;
                $hasil['kode']    = self::KODE_SALAH;
                $hasil['message'] = 'Resi ini sudah tercatat di ' . self::label_jenis($jenis_lawan)
                    . ' (' . $lawan['tanggal_resiretur'] . '). Ganti jenis ke '
                    . self::label_jenis($jenis_lawan) . ', atau hubungi admin bila jenisnya perlu diubah.';
                return $hasil;
            }

            $prev_debug = $this->db->db_debug;
            $this->db->db_debug = FALSE;
            $ok = $this->db->insert('tblresiretur', [
                'id_resi'           => $id_resi,
                'tanggal_resiretur' => date('Y-m-d H:i:s'),
                'status_retur'      => 'Terima Retur',
                'status_detail'     => null,
                'sudah_cetak'       => '',
                'id_kurir'          => $resi['id_kurir'],
                'id_pegawai'        => $id_user,
                'id_marketplace'    => $resi['id_marketplace'],
                'noresi'            => $resi['noresi'],
                'is_komplain'       => $is_komplain,
            ]);
            $this->db->db_debug = $prev_debug;

            if (!$ok) {
                $hasil['kode']    = self::KODE_GAGAL;
                $hasil['message'] = 'Gagal menyimpan. Scan ulang resi ini.';
                return $hasil;
            }

            $hasil['kode']    = self::KODE_OK;
            $hasil['message'] = 'Tersimpan sebagai ' . self::label_jenis($jenis) . '.';
            return $hasil;
        } finally {
            $this->db->query('SELECT RELEASE_LOCK(?)', [$kunci]);
        }
    }

    /** Baris tblresiretur milik resi pada jenis tertentu (cocok lewat id_resi ATAU noresi; is_komplain NULL = biasa). */
    private function cari_baris($id_resi, $noresi, $is_komplain)
    {
        $this->db->select('id_resiretur, tanggal_resiretur, status_retur')
            ->group_start()
                ->where('id_resi', $id_resi)
                ->or_where('noresi', $noresi)
            ->group_end();

        if ($is_komplain) {
            $this->db->where('is_komplain', 1);
        } else {
            $this->db->group_start()
                ->where('is_komplain', 0)
                ->or_where('is_komplain IS NULL', null, false)
            ->group_end();
        }

        return $this->db->limit(1)->get('tblresiretur')->row_array();
    }

    private function nama($tabel, $kolom_id, $kolom_nama, $id)
    {
        if ($id === null || $id === '') {
            return null;
        }
        $row = $this->db->select($kolom_nama)->get_where($tabel, [$kolom_id => $id])->row_array();
        return $row ? $row[$kolom_nama] : null;
    }
}
