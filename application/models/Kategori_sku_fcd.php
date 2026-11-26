<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PURCHASING -> "Update Kategori SKU": isi tblsku.kategori_sku dari Excel (kolom SKU, Kategori).
 *
 * Aturan:
 *  - SKU di Excel harus SAMA PERSIS (huruf besar/kecil dan spasi dihitung) dengan tblsku.id_sku;
 *  - kategori kosong di Excel = DEFAULT_KATEGORI ('P<30');
 *  - semua-atau-tidak-sama-sekali: satu saja baris bermasalah, tidak ada data yang diubah;
 *  - nilai lama dicatat di tblkategori_sku_riwayat per batch supaya bisa dikembalikan.
 */
class Kategori_sku_fcd extends CI_Model
{
    const DEFAULT_KATEGORI = 'P<30';
    const MAKS_BARIS = 20000;
    const MAKS_PANJANG_KATEGORI = 50;
    const MAKS_GALAT_DITAMPILKAN = 40;

    /**
     * Validasi baris hasil baca Excel lalu (bila lolos semua) update tblsku.
     *
     * @param array  $baris     [{no: nomor baris Excel, sku: string, kategori: string}] (baris kosong sudah dibuang)
     * @param string $nama_file nama berkas asli, untuk riwayat
     * @return array {ok: bool, galat: string[], total_galat: int, total?, berubah?, sama?, batch?}
     */
    public function proses(array $baris, $nama_file, $id_user)
    {
        if (!$baris) {
            return $this->gagal(['Tidak ada data. Isi minimal satu baris di bawah header (SKU, Kategori).']);
        }
        if (count($baris) > self::MAKS_BARIS) {
            return $this->gagal(['Terlalu banyak baris (' . count($baris) . '). Maksimal ' . self::MAKS_BARIS . ' baris per upload; pecah menjadi beberapa berkas.']);
        }

        // SKU di DB: kunci persis (string) -> kategori lama. Kunci huruf kecil dipakai hanya untuk petunjuk salah-huruf.
        $db = [];
        $ci = [];
        foreach ($this->db->query('SELECT id_sku, kategori_sku FROM tblsku')->result_array() as $r) {
            $db['k' . $r['id_sku']] = $r['kategori_sku'];
            $ci[mb_strtolower($r['id_sku'])] = $r['id_sku'];
        }

        $galat = [];
        $lihat = [];   // sku persis -> nomor baris pertama
        $lihat_ci = []; // sku huruf kecil -> nomor baris pertama
        $siap = [];
        foreach ($baris as $b) {
            $no = $b['no'];
            $sku = $b['sku'];
            $kat = $b['kategori'] === '' ? self::DEFAULT_KATEGORI : $b['kategori'];

            if ($sku === '') {
                $galat[] = "Baris $no: kolom SKU kosong padahal kategori terisi.";
                continue;
            }
            if (mb_strlen($sku) > 100) {
                $galat[] = "Baris $no: SKU terlalu panjang (maks 100 karakter).";
                continue;
            }
            if (mb_strlen($kat) > self::MAKS_PANJANG_KATEGORI) {
                $galat[] = "Baris $no: kategori \"" . mb_substr($kat, 0, 20) . "...\" terlalu panjang (maks " . self::MAKS_PANJANG_KATEGORI . ' karakter).';
                continue;
            }
            if (preg_match('/[\x00-\x1F\x7F]/', $kat)) {
                $galat[] = "Baris $no: kategori mengandung karakter kontrol/baris baru.";
                continue;
            }
            $k_ci = mb_strtolower($sku);
            if (isset($lihat[$sku])) {
                $galat[] = "Baris $no: SKU \"$sku\" duplikat dengan baris " . $lihat[$sku] . '.';
                continue;
            }
            if (isset($lihat_ci[$k_ci])) {
                $galat[] = "Baris $no: SKU \"$sku\" kembar (beda huruf besar/kecil saja) dengan baris " . $lihat_ci[$k_ci] . '.';
                continue;
            }
            $lihat[$sku] = $no;
            $lihat_ci[$k_ci] = $no;

            if (!array_key_exists('k' . $sku, $db)) {
                $hint = isset($ci[$k_ci]) ? " (di database tertulis \"" . $ci[$k_ci] . "\")" : '';
                $galat[] = "Baris $no: SKU \"$sku\" tidak sama persis dengan SKU di database$hint.";
                continue;
            }
            $siap[] = ['sku' => $sku, 'lama' => $db['k' . $sku], 'baru' => $kat];
        }

        if ($galat) {
            return $this->gagal($galat);
        }

        // Tulis: satu transaksi, error DB tidak boleh mencetak halaman HTML CI (merusak JSON).
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $batch = date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
        $now = date('Y-m-d H:i:s');
        $berubah = 0;
        $this->db->trans_start();
        $st_riwayat = [];
        foreach ($siap as $s) {
            $this->db->query(
                'UPDATE tblsku SET kategori_sku = ? WHERE id_sku = ? AND BINARY id_sku = BINARY ?',
                [$s['baru'], $s['sku'], $s['sku']]
            );
            if ((string) $s['lama'] !== (string) $s['baru']) {
                $berubah++;
                $st_riwayat[] = ['batch' => $batch, 'id_sku' => $s['sku'], 'kategori_lama' => $s['lama'], 'kategori_baru' => $s['baru'], 'id_user' => (int) $id_user, 'created_at' => $now];
            }
        }
        foreach (array_chunk($st_riwayat, 500) as $potong) {
            $this->db->insert_batch('tblkategori_sku_riwayat', $potong);
        }
        $this->db->insert('tblkategori_sku_upload', ['batch' => $batch, 'nama_file' => mb_substr((string) $nama_file, 0, 255), 'total' => count($siap), 'berubah' => $berubah, 'id_user' => (int) $id_user, 'created_at' => $now]);
        $this->db->trans_complete();
        $ok = $this->db->trans_status() !== FALSE;
        $this->db->db_debug = $db_debug;

        if (!$ok) {
            return $this->gagal(['Database menolak penyimpanan. Tidak ada data yang diubah. Coba lagi; bila berulang hubungi admin.']);
        }
        return ['ok' => TRUE, 'galat' => [], 'total_galat' => 0, 'total' => count($siap), 'berubah' => $berubah, 'sama' => count($siap) - $berubah, 'batch' => $batch, 'nama_file' => $nama_file];
    }

    /**
     * Upload berhasil yang paling akhir. Upload sebelum tblkategori_sku_upload ada hanya terekam di riwayat
     * (hanya SKU yang berubah), jadi untuk itu total dan nama berkas tidak diketahui (NULL).
     *
     * @return array|null {waktu, oleh, nama_file, total, berubah}
     */
    public function upload_terakhir()
    {
        $u = null;
        // table_exists: database arsip (Mode Arsip) bisa belum punya tabel ini.
        if ($this->db->table_exists('tblkategori_sku_upload')) {
            $u = $this->db->query('SELECT created_at, id_user, nama_file, total, berubah FROM tblkategori_sku_upload ORDER BY created_at DESC, batch DESC LIMIT 1')->row_array();
        }
        if (!$u && $this->db->table_exists('tblkategori_sku_riwayat')) {
            $u = $this->db->query('SELECT MAX(created_at) AS created_at, MAX(id_user) AS id_user, NULL AS nama_file, NULL AS total, COUNT(*) AS berubah
                                     FROM tblkategori_sku_riwayat GROUP BY batch ORDER BY created_at DESC LIMIT 1')->row_array();
        }
        if (!$u) {
            return null;
        }
        $user = $this->db->query('SELECT name, username FROM tbluser WHERE id_user = ?', [(int) $u['id_user']])->row_array();
        $oleh = '';
        if ($user) {
            $oleh = trim((string) $user['name']) !== '' ? trim((string) $user['name']) : trim((string) $user['username']);
        }
        return [
            'waktu'     => $u['created_at'],
            'oleh'      => $oleh,
            'nama_file' => $u['nama_file'],
            'total'     => $u['total'] === null ? null : (int) $u['total'],
            'berubah'   => (int) $u['berubah'],
        ];
    }

    private function gagal(array $galat)
    {
        return ['ok' => FALSE, 'galat' => array_slice($galat, 0, self::MAKS_GALAT_DITAMPILKAN), 'total_galat' => count($galat)];
    }
}
