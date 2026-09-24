<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pemenuhan Kirim Harian (menu Tim Monitoring).
 *
 * Menghitung, untuk satu tanggal D dan satu titik waktu "per jam" (sekarang
 * untuk hari ini, 23.59.59 untuk hari yang sudah lewat), berapa resi wajib
 * keluar hari itu dan sampai tahap mana resi-resi itu sudah diproses.
 * Aturan lengkap dan hasil verifikasinya ada di docs/PEMENUHAN_KIRIM_HARIAN.md.
 *
 * Ringkasnya (standar operasional = standar MP + tambahan TikTok):
 *  - Wajib keluar hari D = sisa hari sebelumnya yang belum keluar (≤ 7 hari)
 *    + resi yang batas kirim MP-nya ≤ D (standar MP; resi tanpa batas kirim,
 *      mis. Lazada dan reseller, ikut pesanan masuk s/d D 12.00)
 *    + pesanan TikTok masuk s/d D 15.00 yang batas kirim MP-nya s/d D+1
 *      (tambahan operasional).
 *  - Cancel (CANCELED / REQUEST_CANCEL / batal = 1, atau tercatat di Daftar
 *    Cancel Order `tblcancelorder`) keluar dari semua angka.
 *  - "Sudah keluar" = ada scan HO, atau status MP SHIPPED / COMPLETED /
 *    RETURNED tanpa scan HO (status itu baru berubah sesudah HO).
 *  - Tahap bertingkat: yang sudah keluar pasti terhitung sudah packer dan
 *    picker; yang sudah packer pasti terhitung sudah picker.
 *
 * Hasil disimpan di berkas cache sebentar supaya banyak layar yang membuka
 * menu ini bersamaan tetap hanya memicu satu hitungan per menit.
 */
class Pemenuhan_kirim_fcd extends CI_Model
{
    /** Resi sisa yang lebih tua dari ini tidak ikut angka harian (dilaporkan terpisah). */
    const HARI_SISA = 7;

    const JAM_WAJIB_SEMUA_MP = '12:00:00';
    const JAM_WAJIB_TIKTOK   = '15:00:00';

    /** Status marketplace yang berarti paket sudah keluar gudang. */
    const STATUS_KELUAR = ['SHIPPED', 'COMPLETED', 'RETURNED'];
    /** Status marketplace yang dianggap cancel (sama dengan Cancel_order_fcd::STATUS_CANCEL). */
    const STATUS_CANCEL = ['CANCELED', 'REQUEST_CANCEL'];

    /**
     * Grup resi. Wajib keluar = KA + TA + TB.
     *  KA sisa kemarin · TA batas kirim MP ≤ hari ini (tanpa batas kirim: pesanan s/d 12.00)
     *  TB TikTok s/d 15.00 berbatas kirim ≤ besok · TX TikTok s/d 15.00 berbatas lusa+
     *  TC boleh besok
     */
    const GRUP = ['KA', 'TA', 'TB', 'TX', 'TC'];

    /**
     * Kategori resi: 0 Spesial, 1 Reguler (keduanya "1 Qty"),
     * 2 1 SKU Qty 2–9, 3 2–9 SKU Qty ≤ 9, 4 Qty > 9 (ketiganya "> 1 Qty"),
     * 5 tanpa rincian SKU.
     */
    const JUMLAH_KATEGORI = 6;

    /**
     * Rata-rata detik packing per paket per kategori, dari jeda antar-scan tiap
     * packer 16–23 Sep 2026 (scan SYNC_FROM_PICKER tidak dihitung, jeda > 10 menit
     * dibuang). Hanya dipakai untuk mengubah kelebihan paket menjadi menit OT.
     */
    const DETIK_PACKING = [0, 38, 43, 50, 218, 0];

    /** Nilai bawaan bila baris tb_config_operasional belum ada. */
    const SETELAN_BAWAAN = [
        'pkh_jam_selesai_packer' => '18:00',
        'pkh_default_packer'     => '8',
        'pkh_batas_per_packer'   => '120',
    ];

    /** Naikkan bila bentuk hasil snapshot()/daftar_belum() berubah, supaya cache lama tidak terbaca. */
    const VERSI_CACHE = 3;

    const ISTIRAHAT_MULAI   = '12:00';
    const ISTIRAHAT_SELESAI = '13:00';

    /** Umur cache (detik): hari ini diperbarui tiap menit, hari lewat jarang berubah. */
    const CACHE_HARI_INI = 55;
    const CACHE_HARI_LEWAT = 600;
    const CACHE_TERTUNGGAK = 1800;

    /**
     * Seluruh angka untuk menu ini pada tanggal $tanggal (Y-m-d).
     * Mengembalikan array siap di-json_encode, atau NULL kalau query gagal.
     */
    public function snapshot($tanggal)
    {
        $hari_ini = ($tanggal === date('Y-m-d'));
        $per_jam  = $hari_ini ? date('Y-m-d H:i:s') : $tanggal . ' 23:59:59';

        // Nama database ikut jadi kunci: Mode Arsip membaca DB lain.
        $kunci = 'snap' . self::VERSI_CACHE . '_' . md5($this->db->database . '|' . $tanggal);
        $umur  = $hari_ini ? self::CACHE_HARI_INI : self::CACHE_HARI_LEWAT;

        return $this->hitung_sekali($kunci, $umur, function () use ($tanggal, $hari_ini, $per_jam) {
            $grup = $this->hitung_grup($tanggal, $per_jam);
            if ($grup === NULL) {
                return NULL;
            }
            return [
                'tanggal'         => $tanggal,
                'label_tanggal'   => $this->label_tanggal($tanggal),
                'hari_ini'        => $hari_ini,
                'jam_data'        => substr($per_jam, 11, 5),
                'grup'            => $grup['grup'],
                'cancel_hari_ini' => $grup['cancel'],
                'tertunggak_lama' => $this->tertunggak_lama($tanggal),
                'packer_aktif'    => $this->packer_aktif($per_jam),
                'setelan'         => $this->setelan(),
                'detik_kategori'  => self::DETIK_PACKING,
            ];
        });
    }

    /**
     * Daftar resi wajib keluar yang BELUM keluar pada tanggal $tanggal, untuk
     * popup "Lihat detail" dan unduhan Excel. Memakai subquery yang sama dengan
     * angka kotak (sql_per_resi), jadi resi di daftar = resi yang dihitung
     * "belum". Satu baris per resi, kunci dipendekkan supaya JSON-nya kecil:
     *   id, r no resi, p no pesanan, mp, k kurir, kj jam tutup kurir,
     *   sku [[sku, qty, rak]], j jenis (1 = 1 Qty, 2 = >1 Qty, 0 = tanpa rincian),
     *   g grup, up masuk IRESIS, ps jam pesan, bk batas kirim,
     *   pk jam pick ('' belum, '-' terlewat tapi sudah packing), pn picker, pc jam packing.
     * Belum picker = pk ''; belum packer = pc ''; belum HO = semua baris.
     */
    public function daftar_belum($tanggal)
    {
        $hari_ini = ($tanggal === date('Y-m-d'));
        $per_jam  = $hari_ini ? date('Y-m-d H:i:s') : $tanggal . ' 23:59:59';
        $kunci = 'belum' . self::VERSI_CACHE . '_' . md5($this->db->database . '|' . $tanggal);
        $umur  = $hari_ini ? self::CACHE_HARI_INI : self::CACHE_HARI_LEWAT;

        return $this->hitung_sekali($kunci, $umur, function () use ($tanggal, $hari_ini, $per_jam) {
            $q = $this->db->query("
                SELECT z.id, z.grup, z.kat, z.pick_at, z.pack_at, z.picker_c, z.printed_at, z.masuk, z.btk
                FROM (" . $this->sql_per_resi($tanggal, $per_jam) . ") z
                WHERE z.batal = 0 AND z.grup IN ('KA', 'TA', 'TB') AND z.keluar_at IS NULL
                ORDER BY z.printed_at, z.id
            ");
            if (!$q) {
                log_message('error', 'Pemenuhan_kirim_fcd::daftar_belum gagal: ' . json_encode($this->db->error()));
                return NULL;
            }
            $inti = $q->result_array();
            $rinci = $this->rincian_resi(array_column($inti, 'id'));
            if ($rinci === NULL) {
                return NULL;
            }

            $jam = function ($t) { return $t ? substr($t, 0, 16) : ''; };
            $rows = [];
            foreach ($inti as $z) {
                $id = (int) $z['id'];
                $r  = isset($rinci[$id]) ? $rinci[$id] : ['r' => '', 'p' => '', 'mp' => '-', 'k' => '-', 'kj' => '', 'pn' => '', 'sku' => []];
                $kat = (int) $z['kat'];
                $rows[] = [
                    'id'  => $id,
                    'r'   => $r['r'],
                    'p'   => $r['p'],
                    'mp'  => $r['mp'],
                    'k'   => $r['k'],
                    'kj'  => $r['kj'],
                    'sku' => $r['sku'],
                    'j'   => $kat <= 1 ? 1 : ($kat <= 4 ? 2 : 0),
                    'g'   => $z['grup'],
                    'up'  => $jam($z['printed_at']),
                    'ps'  => $jam($z['masuk']),
                    'bk'  => $z['btk'] ? substr($z['btk'], 0, 10) : '',
                    'pk'  => $z['pick_at'] ? $jam($z['pick_at']) : ($z['picker_c'] ? '-' : ''),
                    'pn'  => $z['pick_at'] ? $r['pn'] : '',
                    'pc'  => $jam($z['pack_at']),
                ];
            }
            return [
                'tanggal'       => $tanggal,
                'label_tanggal' => $this->label_tanggal($tanggal),
                'hari_ini'      => $hari_ini,
                'jam_data'      => substr($per_jam, 11, 5),
                'rows'          => $rows,
            ];
        });
    }

    /**
     * No resi, no pesanan, marketplace, kurir, picker pertama, dan SKU per resi.
     * Dua query per 1.000 id (indeks PK dan id_resi). Kolom toko tidak diambil:
     * kosong di semua resi (dicek 24 Sep 2026).
     */
    protected function rincian_resi(array $ids)
    {
        $hasil = [];
        foreach (array_chunk(array_map('intval', $ids), 1000) as $potong) {
            $daftar = implode(',', $potong);
            $q = $this->db->query("
                SELECT p.id_printresi AS id, p.noresi, m.nama_marketplace AS mp, k.nama_kurir AS kurir, k.jam_batas_kirim AS jam_kurir,
                       (SELECT g.nama_pegawai FROM tblresiambilbarang a JOIN tblpegawai g ON g.kode_pegawai = a.yangambil_pegawai
                        WHERE a.id_resi = p.id_printresi ORDER BY a.tanggal_resiambilbarang LIMIT 1) AS picker
                FROM tblprintresi p
                LEFT JOIN tblmarketplace m ON m.id_marketplace = p.id_marketplace
                LEFT JOIN tblkurir k ON k.id_kurir = p.id_kurir
                WHERE p.id_printresi IN ($daftar)
            ");
            $d = $this->db->query("
                SELECT id_resi, no_pesanan, sku, jumlah, no_rak FROM tbldetailprintresi
                WHERE id_resi IN ($daftar) ORDER BY id_resi, id_detail_resi
            ");
            if (!$q || !$d) {
                log_message('error', 'Pemenuhan_kirim_fcd::rincian_resi gagal: ' . json_encode($this->db->error()));
                return NULL;
            }
            foreach ($q->result_array() as $r) {
                $hasil[(int) $r['id']] = [
                    'r'   => (string) $r['noresi'],
                    'p'   => '',
                    'mp'  => $r['mp'] ?: '-',
                    'k'   => $r['kurir'] ?: '-',
                    'kj'  => (string) $r['jam_kurir'],
                    // "AHMAD - PICKER - 0339" → "AHMAD"
                    'pn'  => $r['picker'] ? trim(explode(' - ', $r['picker'])[0]) : '',
                    'sku' => [],
                ];
            }
            $pesanan = [];
            foreach ($d->result_array() as $r) {
                $id = (int) $r['id_resi'];
                if (!isset($hasil[$id])) {
                    continue;
                }
                $hasil[$id]['sku'][] = [(string) $r['sku'], (int) $r['jumlah'], (string) $r['no_rak']];
                $pesanan[$id][$r['no_pesanan']] = TRUE;
            }
            foreach ($pesanan as $id => $p) {
                $hasil[$id]['p'] = implode(', ', array_keys($p));
            }
        }
        return $hasil;
    }

    /**
     * Satu query: per grup × kategori → [diterima, sudah picker, sudah packer,
     * sudah keluar, keluar tanpa scan HO], plus jumlah cancel hari itu.
     */
    protected function hitung_grup($tanggal, $per_jam)
    {
        $d = $this->db->escape($tanggal . ' 00:00:00');
        $sql = "
            SELECT z.grup, z.kat,
                   SUM(z.batal = 0)                                   AS dit,
                   SUM(z.batal = 0 AND z.picker_c IS NOT NULL)        AS pick,
                   SUM(z.batal = 0 AND z.packer_c IS NOT NULL)        AS pack,
                   SUM(z.batal = 0 AND z.keluar_at IS NOT NULL)       AS keluar,
                   SUM(z.batal = 0 AND z.keluar_at IS NOT NULL AND z.ho_at IS NULL) AS tanpa_ho,
                   SUM(z.batal = 1 AND (z.printed_at >= $d OR z.modified_at >= $d OR z.cancel_catat >= $d)) AS cancel
            FROM (" . $this->sql_per_resi($tanggal, $per_jam) . ") z
            GROUP BY z.grup, z.kat
        ";

        $q = $this->db->query($sql);
        if (!$q) {
            log_message('error', 'Pemenuhan_kirim_fcd::hitung_grup gagal: ' . json_encode($this->db->error()));
            return NULL;
        }

        $kosong = array_fill(0, self::JUMLAH_KATEGORI, [0, 0, 0, 0, 0]);
        $grup = array_fill_keys(self::GRUP, $kosong);
        $cancel = 0;
        foreach ($q->result_array() as $r) {
            $cancel += (int) $r['cancel'];
            if (!isset($grup[$r['grup']])) {
                continue;
            }
            $grup[$r['grup']][(int) $r['kat']] = [
                (int) $r['dit'], (int) $r['pick'], (int) $r['pack'], (int) $r['keluar'], (int) $r['tanpa_ho'],
            ];
        }
        return ['grup' => $grup, 'cancel' => $cancel];
    }

    /**
     * Subquery satu baris per resi yang relevan untuk hari D, dengan kolom:
     * id, printed_at, modified_at, cancel_catat, masuk, btk, batal, grup, kat,
     * pick_at/pack_at/ho_at/keluar_at (s/d $per_jam), picker_c/packer_c (tahap bertingkat).
     * Dipakai hitung_grup() (angka kotak) dan daftar_belum() (popup) supaya
     * keduanya selalu sepakat soal resi mana yang "belum".
     *
     * Dua hal yang membuatnya cepat (±0,5 dtk, sebelumnya 10 dtk), jangan
     * dibongkar tanpa mengukur ulang:
     *  - Resi sisa D-7..D-1 disaring lebih dulu lewat status: yang statusnya
     *    sudah keluar/cancel sebelum hari D tidak perlu dicek scan HO-nya.
     *  - Scan HO/packer/picker dibaca lewat subquery skalar per resi (indeks
     *    id_resi). NOT EXISTS ke tblresikeluar membuat MariaDB 10.4 memindai
     *    seluruh tabel itu (±660 rb baris) lewat materialization.
     */
    protected function sql_per_resi($tanggal, $per_jam)
    {
        $d      = $this->db->escape($tanggal . ' 00:00:00');
        $d_hari = $this->db->escape($tanggal);
        $d1     = $this->db->escape(date('Y-m-d', strtotime($tanggal . ' +1 day')));
        $d7     = $this->db->escape(date('Y-m-d', strtotime($tanggal . ' -' . self::HARI_SISA . ' day')) . ' 00:00:00');
        $d12    = $this->db->escape($tanggal . ' ' . self::JAM_WAJIB_SEMUA_MP);
        $d15    = $this->db->escape($tanggal . ' ' . self::JAM_WAJIB_TIKTOK);
        $per    = $this->db->escape($per_jam);
        $cancel = $this->daftar_sql(self::STATUS_CANCEL);
        $keluar = $this->daftar_sql(self::STATUS_KELUAR);
        $selesai_lama = $this->daftar_sql(array_merge(self::STATUS_KELUAR, self::STATUS_CANCEL));

        $id_pick = $this->id_status_performa('1_SKU_PICKER');
        $id_pack = $this->id_status_performa('1_SKU_PACKER');

        return "
                SELECT y.*,
                       COALESCE(y.pick_at, y.pack_at, y.keluar_at) AS picker_c,
                       COALESCE(y.pack_at, y.keluar_at)            AS packer_c,
                       CASE
                           WHEN y.printed_at < $d THEN 'KA'
                           -- standar MP: batas kirim MP hari ini (atau sudah lewat)
                           WHEN y.btk IS NOT NULL AND DATE(y.btk) <= $d_hari THEN 'TA'
                           -- tanpa batas kirim dari upload (Lazada, reseller): pesanan s/d 12.00
                           WHEN y.btk IS NULL AND y.masuk <= $d12 THEN 'TA'
                           -- tambahan operasional: pesanan TikTok s/d 15.00
                           WHEN y.tt = 1 AND y.masuk <= $d15 AND (y.btk IS NULL OR DATE(y.btk) <= $d1) THEN 'TB'
                           WHEN y.tt = 1 AND y.masuk <= $d15 THEN 'TX'
                           ELSE 'TC'
                       END AS grup,
                       CASE
                           WHEN y.u = 1 AND y.q = 1 THEN
                               -- Spesial: SKU bertanda Special hari ini, atau resi yang sudah diproses lewat jalur 1 SKU
                               IF(y.sp = 1
                                  OR (SELECT COUNT(*) FROM tblresiambilbarang a2 WHERE a2.id_resi = y.id AND a2.status_performa_id = $id_pick) > 0
                                  OR (SELECT COUNT(*) FROM tblpacking k2 WHERE k2.id_resi = y.id AND k2.status_performa_id = $id_pack AND k2.keterangan = 'SYNC_FROM_PICKER') > 0,
                                  0, 1)
                           WHEN y.u = 1 AND y.q BETWEEN 2 AND 9 THEN 2
                           WHEN y.u BETWEEN 2 AND 9 AND y.q <= 9 THEN 3
                           WHEN y.q > 9 THEN 4
                           ELSE 5
                       END AS kat
                FROM (
                    SELECT x.*,
                           IF(x.pick_min <= $per, x.pick_min, NULL) AS pick_at,
                           IF(x.pack_min <= $per, x.pack_min, NULL) AS pack_at,
                           IF(x.ho_min <= $per, x.ho_min, NULL)     AS ho_at,
                           -- keluar tanpa scan HO: status MP sudah keluar dan tidak ada scan HO sama sekali
                           COALESCE(IF(x.ho_min <= $per, x.ho_min, NULL),
                                    IF(x.ho_min IS NULL AND x.st IN ($keluar) AND COALESCE(x.modified_at, x.printed_at) <= $per,
                                       COALESCE(x.modified_at, x.printed_at), NULL)) AS keluar_at
                    FROM (
                        SELECT c.*,
                               IF(c.batal_status = 1 OR c.cancel_catat IS NOT NULL, 1, 0) AS batal,
                               (SELECT MIN(a.tanggal_resiambilbarang) FROM tblresiambilbarang a WHERE a.id_resi = c.id) AS pick_min,
                               (SELECT MIN(k.tanggal_packing) FROM tblpacking k WHERE k.id_resi = c.id)                 AS pack_min,
                               (SELECT MIN(h.tanggal_resikeluar) FROM tblresikeluar h WHERE h.id_resi = c.id)           AS ho_min
                        FROM (
                            SELECT p.id_printresi AS id, p.tanggal_printresi AS printed_at, p.modified_at,
                                   NULLIF(p.tanggal_bataskirim, '0000-00-00 00:00:00') AS btk,
                                   UPPER(TRIM(COALESCE(p.status_pesanan, ''))) AS st,
                                   -- dicatat cancel di Daftar Cancel Order (Scan Cek Cancel / sinkron Jubelio), indeks unik noresi;
                                   -- tidak NULL selama barisnya ada (dipakai juga untuk kolom batal di bawah)
                                   (SELECT COALESCE(co.created_at, co.tanggal_cancel, '1970-01-01') FROM tblcancelorder co
                                    WHERE co.noresi = p.noresi) AS cancel_catat,
                                   IF(UPPER(TRIM(COALESCE(p.status_pesanan, ''))) IN ($cancel) OR p.batal = '1', 1, 0) AS batal_status,
                                   COALESCE(NULLIF(p.tanggal_pesan, '0000-00-00 00:00:00'), p.tanggal_printresi) AS masuk,
                                   IF(p.id_marketplace = 5 OR MAX(d.no_pesanan LIKE 'TT-%') = 1, 1, 0) AS tt,
                                   COUNT(DISTINCT d.sku) AS u,
                                   COALESCE(SUM(d.jumlah), 0) AS q,
                                   COALESCE(MAX(s.is_special), 0) AS sp
                            FROM tblprintresi p
                            LEFT JOIN tbldetailprintresi d ON d.id_resi = p.id_printresi
                            LEFT JOIN tblsku s ON s.id_sku = d.sku
                            WHERE p.tanggal_printresi >= $d7
                              AND p.tanggal_printresi <= $per
                              -- resi sisa yang statusnya sudah keluar/cancel sebelum hari D bukan urusan hari ini
                              AND (p.tanggal_printresi >= $d
                                   OR NOT (UPPER(TRIM(COALESCE(p.status_pesanan, ''))) IN ($selesai_lama)
                                           AND COALESCE(p.modified_at, p.tanggal_printresi) < $d))
                            GROUP BY p.id_printresi
                        ) c
                    ) x
                    -- resi sisa yang sudah di-HO sebelum hari D bukan beban hari ini
                    WHERE x.printed_at >= $d OR x.ho_min IS NULL OR x.ho_min >= $d
                ) y
        ";
    }

    /**
     * Cache berkas + gembok: $hitung dijalankan paling banyak sekali per $umur
     * detik untuk $kunci. Layar lain yang meminta bersamaan menunggu, lalu
     * memakai hasil yang baru ditulis (hitungannya ±1 dtk, jangan diulang).
     * $hitung mengembalikan array, atau NULL bila query gagal (tidak disimpan).
     */
    protected function hitung_sekali($kunci, $umur, callable $hitung)
    {
        $simpan = $this->cache_baca($kunci, $umur);
        if (is_array($simpan)) {
            return $simpan;
        }

        $gembok = @fopen(APPPATH . 'cache/pkh_' . $kunci . '.lock', 'c');
        if ($gembok) {
            @flock($gembok, LOCK_EX);
            $simpan = $this->cache_baca($kunci, $umur);
            if (is_array($simpan)) {
                @flock($gembok, LOCK_UN);
                @fclose($gembok);
                return $simpan;
            }
        }

        $debug_lama = $this->db->db_debug;
        $this->db->db_debug = FALSE;   // error query jangan mencetak halaman HTML di tengah JSON
        try {
            $hasil = $hitung();
            if (is_array($hasil)) {
                $this->cache_tulis($kunci, $hasil);
            }
        } finally {
            $this->db->db_debug = $debug_lama;
            if ($gembok) {
                @flock($gembok, LOCK_UN);
                @fclose($gembok);
            }
        }
        return is_array($hasil) ? $hasil : NULL;
    }

    /**
     * Resi lebih tua dari HARI_SISA yang belum pernah keluar dan tidak cancel.
     * Tidak ikut angka harian; ditampilkan terpisah supaya dibereskan.
     * Memindai sebagian besar tblprintresi (±0,4 dtk), jadi disimpan 30 menit.
     */
    protected function tertunggak_lama($tanggal)
    {
        $kunci = 'lama' . self::VERSI_CACHE . '_' . md5($this->db->database . '|' . $tanggal);
        $simpan = $this->cache_baca($kunci, self::CACHE_TERTUNGGAK);
        if (is_int($simpan)) {
            return $simpan;
        }

        $batas = $this->db->escape(date('Y-m-d', strtotime($tanggal . ' -' . self::HARI_SISA . ' day')) . ' 00:00:00');
        $bukan = $this->daftar_sql(array_merge(self::STATUS_CANCEL, self::STATUS_KELUAR));
        $row = $this->db->query("
            SELECT COUNT(*) AS n FROM tblprintresi p
            WHERE p.tanggal_printresi < $batas
              AND UPPER(TRIM(COALESCE(p.status_pesanan, ''))) NOT IN ($bukan)
              AND p.batal <> '1'
              AND NOT EXISTS (SELECT 1 FROM tblcancelorder co WHERE co.noresi = p.noresi)
              AND (SELECT COUNT(*) FROM tblresikeluar h WHERE h.id_resi = p.id_printresi) = 0
        ");
        $n = $row ? (int) $row->row()->n : 0;
        $this->cache_tulis($kunci, $n);
        return $n;
    }

    /** Jumlah akun packer yang scan packing (bukan sinkron spesial) dalam 60 menit terakhir. */
    protected function packer_aktif($per_jam)
    {
        $per = $this->db->escape($per_jam);
        $row = $this->db->query("
            SELECT COUNT(DISTINCT k.packer_pegawai) AS n FROM tblpacking k
            WHERE k.tanggal_packing > $per - INTERVAL 60 MINUTE AND k.tanggal_packing <= $per
              AND COALESCE(k.keterangan, '') <> 'SYNC_FROM_PICKER'
        ");
        return $row ? (int) $row->row()->n : 0;
    }

    /** Setelan menu dari tb_config_operasional, dengan nilai bawaan. */
    public function setelan()
    {
        $nilai = self::SETELAN_BAWAAN;
        $q = $this->db->where_in('kunci', array_keys($nilai))->get('tb_config_operasional');
        if ($q) {
            foreach ($q->result() as $r) {
                if (trim((string) $r->nilai) !== '') {
                    $nilai[$r->kunci] = trim($r->nilai);
                }
            }
        }
        return [
            'jam_selesai'       => $nilai['pkh_jam_selesai_packer'],
            'default_packer'    => max(1, (int) $nilai['pkh_default_packer']),
            'default_batas'     => max(1, (int) $nilai['pkh_batas_per_packer']),
            'istirahat_mulai'   => self::ISTIRAHAT_MULAI,
            'istirahat_selesai' => self::ISTIRAHAT_SELESAI,
        ];
    }

    protected function id_status_performa($kode)
    {
        $row = $this->db->query(
            "SELECT id_statusperforma FROM tblmasterstatusperforma WHERE kode_status = " . $this->db->escape($kode) . " LIMIT 1"
        );
        return ($row && $row->row()) ? (int) $row->row()->id_statusperforma : 0;
    }

    protected function daftar_sql(array $nilai)
    {
        return implode(', ', array_map([$this->db, 'escape'], $nilai));
    }

    protected function label_tanggal($tanggal)
    {
        $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $t = strtotime($tanggal);
        return $hari[(int) date('w', $t)] . ', ' . (int) date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
    }

    /*
     * Cache berkas sederhana di application/cache. Driver cache bawaan CI3 tidak
     * dipakai: di PHP 8.2 ia memicu "Creation of dynamic property CI_Cache::$file
     * is deprecated", dan dengan display_errors menyala pesan itu ikut tercetak
     * di tengah JSON.
     */
    protected function cache_baca($kunci, $umur_maks)
    {
        $berkas = APPPATH . 'cache/pkh_' . $kunci . '.json';
        if (!is_file($berkas) || (time() - (int) @filemtime($berkas)) >= $umur_maks) {
            return NULL;
        }
        $isi = json_decode((string) @file_get_contents($berkas), TRUE);
        return isset($isi['v']) ? $isi['v'] : NULL;
    }

    protected function cache_tulis($kunci, $nilai)
    {
        @file_put_contents(APPPATH . 'cache/pkh_' . $kunci . '.json', json_encode(['v' => $nilai]), LOCK_EX);
    }
}
