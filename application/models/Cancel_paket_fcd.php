<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Paket Cancel — jejak scan yang ditolak karena resi cancel, dan alur
 * pengembalian barangnya ke display. Rujukan: docs/PAKET_CANCEL.md.
 *
 * Tahap 1 (live): catat_tolak() dipanggil dari setiap titik yang menolak
 * scan dengan ORDER_CANCELED. Tidak mengubah perilaku penolakan sama sekali;
 * hanya menulis tblcancel_paket_tolak (setiap penolakan) dan, bila barang
 * sudah keluar display, tblcancel_paket (satu per resi, status DITEMUKAN).
 */
class Cancel_paket_fcd extends CI_Model
{
    const TABEL_PAKET = 'tblcancel_paket';
    const TABEL_TOLAK = 'tblcancel_paket_tolak';

    /** Tahap yang sah untuk tblcancel_paket_tolak.tahap. */
    const TAHAP = ['PICKER', 'INBOUND', 'PACKER', 'HO', 'LOST_SCAN'];

    /** Tahap yang sah untuk tblcancel_paket.ditemukan_di (meja fisik). */
    const MEJA = ['PICKER', 'INBOUND', 'PACKER', 'HO'];

    /**
     * Catat satu scan yang ditolak karena resi cancel.
     *
     * TIDAK PERNAH melempar exception atau mengganggu alur scan: semua
     * kegagalan ditelan dan ditulis ke log aplikasi. Pemanggil cukup
     * memanggilnya setelah memutuskan menolak, di luar transaksi yang akan
     * di-rollback (lihat docs/PAKET_CANCEL.md §7.1).
     *
     * @param object|array $resi  Baris tblprintresi: minimal id_printresi & noresi;
     *                            status_pesanan & batal dipakai untuk kolom alasan.
     * @param string       $tahap PICKER | INBOUND | PACKER | HO | LOST_SCAN
     * @param array|null   $user  Session user (id_user, nama_komputer); NULL = ambil dari session.
     * @param array        $opsi  'barang_sudah_diambil' => bool  paksa keputusan buat/tidak paket cancel
     *                            'ditemukan_di'         => string meja fisik untuk baris baru (default = $tahap)
     *                            'keterangan'           => string catatan bebas di log
     * @return bool TRUE bila log tertulis.
     */
    public function catat_tolak($resi, $tahap, $user = null, array $opsi = [])
    {
        try {
            // Mode Arsip: koneksi sedang READ ONLY ke iresis_arsip, jangan menulis.
            if ($this->session->userdata('mode_arsip')) {
                return false;
            }

            $resi = is_array($resi) ? (object) $resi : $resi;
            if (empty($resi) || empty($resi->id_printresi)) {
                return false;
            }

            $tahap = strtoupper((string) $tahap);
            if (!in_array($tahap, self::TAHAP, true)) {
                log_message('error', "Cancel_paket_fcd::catat_tolak: tahap tidak dikenal '$tahap'");
                return false;
            }

            if ($user === null) {
                $user = $this->session->userdata('user');
            }
            $id_user       = !empty($user['id_user']) ? (int) $user['id_user'] : null;
            $nama_komputer = !empty($user['nama_komputer']) ? substr((string) $user['nama_komputer'], 0, 50) : null;

            $id_resi = (int) $resi->id_printresi;
            $noresi  = !empty($resi->noresi) ? substr((string) $resi->noresi, 0, 100) : $this->cari_noresi($id_resi);
            $now     = date('Y-m-d H:i:s');

            $db_debug_asli = $this->db->db_debug;
            $this->db->db_debug = FALSE;

            // 1. Jejak penolakan — selalu.
            $this->db->insert(self::TABEL_TOLAK, [
                'id_resi'       => $id_resi,
                'noresi'        => $noresi,
                'tahap'         => $tahap,
                'alasan'        => $this->alasan_cancel($resi),
                'id_user'       => $id_user,
                'nama_komputer' => $nama_komputer,
                'waktu'         => $now,
                'keterangan'    => isset($opsi['keterangan']) ? substr((string) $opsi['keterangan'], 0, 255) : null,
            ]);
            $log_ok = $this->db->affected_rows() > 0;

            // 2. Paket cancel — hanya bila barang sudah keluar display.
            $sudah_diambil = array_key_exists('barang_sudah_diambil', $opsi)
                ? (bool) $opsi['barang_sudah_diambil']
                : $this->barang_sudah_diambil($id_resi, $tahap);

            if ($sudah_diambil) {
                $ditemukan_di = strtoupper((string) ($opsi['ditemukan_di'] ?? $tahap));
                if (!in_array($ditemukan_di, self::MEJA, true)) {
                    // LOST_SCAN tanpa info meja: paket ditahan di packer/HO, anggap PACKER.
                    $ditemukan_di = 'PACKER';
                }

                $this->db->query(
                    "INSERT INTO " . self::TABEL_PAKET . "
                        (id_resi, noresi, status, ditemukan_di, ditemukan_oleh, ditemukan_komputer, ditemukan_at,
                         jumlah_tolak, tolak_terakhir_di, tolak_terakhir_at, created_at)
                     VALUES (?, ?, 'DITEMUKAN', ?, ?, ?, ?, 1, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        jumlah_tolak      = jumlah_tolak + 1,
                        tolak_terakhir_di = VALUES(tolak_terakhir_di),
                        tolak_terakhir_at = VALUES(tolak_terakhir_at),
                        updated_at        = VALUES(tolak_terakhir_at)",
                    [$id_resi, $noresi, $ditemukan_di, $id_user, $nama_komputer, $now, $tahap, $now, $now]
                );
            }

            $this->db->db_debug = $db_debug_asli;
            return $log_ok;
        } catch (Throwable $e) {
            if (isset($db_debug_asli)) {
                $this->db->db_debug = $db_debug_asli;
            }
            log_message('error', 'Cancel_paket_fcd::catat_tolak gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Baris tblcancel_paket untuk satu resi (NULL bila belum ada).
     */
    public function paket_untuk_resi($id_resi)
    {
        return $this->db->get_where(self::TABEL_PAKET, ['id_resi' => (int) $id_resi])->row();
    }

    /**
     * Riwayat penolakan satu resi, terlama dulu.
     */
    public function riwayat_tolak($id_resi)
    {
        return $this->db
            ->select('t.*, u.name AS nama_user')
            ->from(self::TABEL_TOLAK . ' t')
            ->join('tbluser u', 'u.id_user = t.id_user', 'left')
            ->where('t.id_resi', (int) $id_resi)
            ->order_by('t.waktu', 'ASC')
            ->get()
            ->result();
    }

    // ─────────────────────────────────────────────
    //  Pembantu
    // ─────────────────────────────────────────────

    /** Teks alasan cancel dari baris tblprintresi. */
    private function alasan_cancel($resi)
    {
        $batal = isset($resi->batal) ? (string) $resi->batal : '';
        if ($batal !== '' && $batal !== '0') {
            return 'BATAL_MANUAL';
        }
        $status = isset($resi->status_pesanan) ? strtoupper(trim((string) $resi->status_pesanan)) : '';
        return $status !== '' ? substr($status, 0, 30) : 'CANCEL';
    }

    /**
     * Barang sudah keluar display? Semua meja selain picker berarti paket
     * fisik ada di tangan petugas. Di meja picker, hanya kalau picking sudah
     * pernah dibuat (mis. Update Picker) — scan picker biasa yang ditolak
     * berarti barang belum diambil.
     */
    private function barang_sudah_diambil($id_resi, $tahap)
    {
        if ($tahap !== 'PICKER') {
            return true;
        }
        return $this->db
            ->where('id_resi', (int) $id_resi)
            ->count_all_results('tblresiambilbarang') > 0;
    }

    private function cari_noresi($id_resi)
    {
        $row = $this->db->select('noresi')->get_where('tblprintresi', ['id_printresi' => (int) $id_resi])->row();
        return $row ? substr((string) $row->noresi, 0, 100) : '';
    }
}
