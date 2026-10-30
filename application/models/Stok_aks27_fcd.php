<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model menu TIM PURCHASING -> "Stok AKS27-RDM".
 *
 * Buku stok pinset BONUS AKS27-RDM di lokasi khusus area packer. Ini stok yang
 * terpisah dari stok AKS27-RDM biasa (tblsku.total_stok, rak/gudang): AKS27-RDM
 * yang DIBELI di sebuah resi diambil dari rak biasa dan tidak pernah menyentuh
 * buku ini; yang memotong hanya 1 pinset bonus per resi pemicu.
 *
 * Sumber baris:
 *  - INPUT   : langsung dari penerimaan transfer (tbltransfer_stok_terima), tidak disalin,
 *              jadi tanggal/qty selalu sama dengan Penerimaan Transfer.
 *  - lainnya : tblstok_aks27 (PENJUALAN, CANCEL, RETURAN, PENYESUAIAN), ditulis menu scan
 *              dan penyesuaian. Baris tidak pernah diubah atau dihapus.
 * Urutan buku = waktu catat (created), lalu sumber, lalu id; saldo (sisa) berjalan
 * dihitung di SQL atas urutan itu, sebelum filter apa pun.
 */
class Stok_aks27_fcd extends CI_Model
{
    const PER_HALAMAN_BOLEH = [10, 20, 50];
    const REFS              = ['INPUT', 'PENYESUAIAN', 'RETURAN', 'CANCEL', 'PENJUALAN'];

    /** Resi dengan scan packer (atau scan HO bila tanpa catatan packer) sebelum tanggal ini tidak memotong stok bonus. */
    const TANGGAL_MULAI_POTONG = '2026-10-28';

    /** Barang bonusnya sendiri: tidak pernah jadi pemicu dan tidak ikut dihitung sebagai qty jual. */
    const KODE_BONUS = 'AKS27-RDM';

    /** Sinkron penuh dijalankan paling sering sekali per sekian detik (penanda berkas di application/cache). */
    const SINKRON_JEDA = 30;

    /**
     * Catat pemotongan (PENJUALAN) dan pengembalian cancel (CANCEL) dari data scan resi.
     *
     * Aturan:
     *  - Resi memotong bila berisi minimal satu SKU aktif di tblsku_bonus (selain AKS27-RDM sendiri,
     *    yang dibeli diambil dari rak biasa) dan tanggal scan packer (tblpacking) -- atau scan HO
     *    (tblresikeluar, scan HO / serah terima) bila tanpa catatan packer -- >= TANGGAL_MULAI_POTONG. Tepat 1 pinset per resi.
     *  - Resi berstatus CANCELED atau batal tidak memotong.
     *  - Resi yang sudah tercatat memotong lalu menjadi CANCELED dan belum scan HO: CANCEL +1.
     * Idempoten: kolom `kunci` UNIQUE (JUAL:<noresi>, CANCEL:<noresi>) + INSERT IGNORE, jadi aman
     * dipanggil berulang dan bersamaan. Tidak pernah mengubah atau menghapus baris.
     *
     * @param int|null $id_resi satu resi (dari scan live), atau NULL untuk semua resi pemicu
     * @return array ['jual' => baris PENJUALAN baru, 'cancel' => baris CANCEL baru]
     */
    public function sinkron_scan($id_resi = NULL)
    {
        $bonus  = $this->db->escape(self::KODE_BONUS);
        $mulai  = $this->db->escape(self::TANGGAL_MULAI_POTONG . ' 00:00:00');
        $filter = $id_resi !== NULL ? ' AND d.id_resi = ' . (int) $id_resi : '';

        $this->db->query(
            "INSERT IGNORE INTO tblstok_aks27 (ref, tanggal, jam, masuk, keluar, qty, no_ref, keterangan, flag, kunci, created, createdby)"
            . " SELECT 'PENJUALAN', DATE(COALESCE(s.tgl_packing, s.tgl_ho)), TIME(COALESCE(s.tgl_packing, s.tgl_ho)), 0, 1, t.qty, pr.noresi,"
            . "  CONCAT_WS('; ',"
            . "   IF(t.jml_pemicu > 1 OR t.qty > 1, 'dipukul rata 1 pinset', NULL),"
            . "   IF(t.beli > 0, CONCAT(" . $bonus . ", ' dibeli ', t.beli, ' pcs dari rak biasa, tidak memotong stok ini'), NULL),"
            . "   CONCAT('dari data scan ', IF(s.tgl_packing IS NOT NULL, 'packer', 'HO'))),"
            . "  NULL, CONCAT('JUAL:', pr.noresi), NOW(), 0"
            . " FROM ("
            . "  SELECT d.id_resi, COUNT(DISTINCT d.sku) AS jml_pemicu,"
            . "   (SELECT COALESCE(SUM(x.jumlah), 0) FROM tbldetailprintresi x WHERE x.id_resi = d.id_resi AND x.sku <> " . $bonus . ") AS qty,"
            . "   (SELECT COALESCE(SUM(x.jumlah), 0) FROM tbldetailprintresi x WHERE x.id_resi = d.id_resi AND x.sku = " . $bonus . ") AS beli"
            . "  FROM tbldetailprintresi d JOIN tblsku_bonus b ON b.sku = d.sku AND b.aktif = 1"
            . "  WHERE d.sku <> " . $bonus . $filter . " GROUP BY d.id_resi"
            . " ) t"
            . " JOIN tblprintresi pr ON pr.id_printresi = t.id_resi"
            . " JOIN ("
            . "  SELECT t2.id_resi,"
            . "   (SELECT MIN(p.tanggal_packing) FROM tblpacking p WHERE p.id_resi = t2.id_resi) AS tgl_packing,"
            . "   (SELECT MIN(h.tanggal_resikeluar) FROM tblresikeluar h WHERE h.id_resi = t2.id_resi) AS tgl_ho"
            . "  FROM (SELECT d.id_resi FROM tbldetailprintresi d JOIN tblsku_bonus b ON b.sku = d.sku AND b.aktif = 1"
            . "        WHERE d.sku <> " . $bonus . $filter . " GROUP BY d.id_resi) t2"
            . " ) s ON s.id_resi = pr.id_printresi"
            . " WHERE COALESCE(s.tgl_packing, s.tgl_ho) >= " . $mulai
            . "  AND (pr.status_pesanan IS NULL OR pr.status_pesanan <> 'CANCELED')"
            . "  AND (pr.batal IS NULL OR pr.batal NOT IN ('1'))"
        );
        $jual = (int) $this->db->affected_rows();

        $filter_cancel = $id_resi !== NULL ? ' AND pr.id_printresi = ' . (int) $id_resi : '';
        $this->db->query(
            "INSERT IGNORE INTO tblstok_aks27 (ref, tanggal, jam, masuk, keluar, qty, no_ref, keterangan, flag, kunci, created, createdby)"
            . " SELECT 'CANCEL', CURDATE(), CURTIME(), 1, 0, NULL, j.no_ref, 'batal sebelum HO, pinset kembali otomatis', NULL,"
            . "  CONCAT('CANCEL:', j.no_ref), NOW(), 0"
            . " FROM tblstok_aks27 j JOIN tblprintresi pr ON pr.noresi = j.no_ref"
            . " WHERE j.ref = 'PENJUALAN' AND pr.status_pesanan = 'CANCELED'"
            . "  AND NOT EXISTS (SELECT 1 FROM tblresikeluar h WHERE h.id_resi = pr.id_printresi)" . $filter_cancel
        );
        $cancel = (int) $this->db->affected_rows();

        return ['jual' => $jual, 'cancel' => $cancel];
    }

    /**
     * Sinkron penuh dengan jeda: dipanggil saat menu Stok / saldo dibuka supaya buku selalu mutakhir
     * (menangkap scan HO tanpa catatan packer dan resi yang baru berstatus CANCELED). Tidak pernah
     * melempar galat ke pemanggil; kegagalan hanya dicatat di log.
     */
    public function sinkron_terjadwal($paksa = FALSE)
    {
        $penanda = APPPATH . 'cache/stok_aks27_sinkron.txt';
        if (!$paksa && is_file($penanda) && (time() - (int) @filemtime($penanda)) < self::SINKRON_JEDA) {
            return NULL;
        }
        @file_put_contents($penanda, date('c'), LOCK_EX);
        return $this->sinkron_aman(NULL);
    }

    /** sinkron_scan() yang tidak boleh mengganggu pemanggil (dipakai juga oleh scan packer). */
    public function sinkron_aman($id_resi)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $hasil = NULL;
        try {
            $hasil = $this->sinkron_scan($id_resi);
        } catch (Throwable $e) {
            log_message('error', 'Stok_aks27_fcd::sinkron_scan gagal: ' . $e->getMessage());
        }
        $this->db->db_debug = $debug;
        return $hasil;
    }

    /** Buku lengkap dengan saldo berjalan (derived table, dipakai sebagai "(...) x"). */
    private function sql_buku()
    {
        return "SELECT b.ref, b.tanggal, b.jam, b.masuk, b.keluar, b.qty_jual, b.no_ref, b.keterangan, b.flag, b.urut, b.sumber, b.sid,"
            . " SUM(b.masuk - b.keluar) OVER (ORDER BY b.urut, b.sumber, b.sid) AS sisa"
            . " FROM ("
            . "  SELECT 'INPUT' AS ref, r.tanggal, r.jam, r.qty AS masuk, 0 AS keluar, NULL AS qty_jual,"
            . "   CONCAT('TR-', r.transfer_id) AS no_ref,"
            . "   CONCAT('terima real ', r.qty, ' dari transfer ', t.qty_kirim) AS keterangan, NULL AS flag,"
            . "   r.created AS urut, 0 AS sumber, r.id AS sid"
            . "  FROM tbltransfer_stok_terima r JOIN tbltransfer_stok t ON t.id = r.transfer_id"
            . "  UNION ALL"
            . "  SELECT l.ref, l.tanggal, l.jam, l.masuk, l.keluar, l.qty, l.no_ref, l.keterangan, l.flag, l.created, 1, l.id"
            . "  FROM tblstok_aks27 l"
            . " ) b";
    }

    /** Saldo stok sekarang = total masuk - total keluar. */
    public function saldo()
    {
        $this->sinkron_terjadwal();
        $in  = $this->db->query('SELECT COALESCE(SUM(qty), 0) AS n FROM tbltransfer_stok_terima')->row_array();
        $lain = $this->db->query('SELECT COALESCE(SUM(masuk) - SUM(keluar), 0) AS n FROM tblstok_aks27')->row_array();
        return (int) $in['n'] + (int) $lain['n'];
    }

    /** Kartu ringkasan dan jumlah baris per ref. */
    public function ringkas()
    {
        $this->sinkron_terjadwal();
        $hitung = [];
        foreach (self::REFS as $r) {
            $hitung[$r] = 0;
        }
        $hitung['INPUT'] = (int) $this->db->count_all('tbltransfer_stok_terima');
        foreach ($this->db->query('SELECT ref, COUNT(*) AS n FROM tblstok_aks27 GROUP BY ref')->result_array() as $row) {
            $hitung[$row['ref']] = (int) $row['n'];
        }

        $jalan = $this->db->query('SELECT COALESCE(SUM(qty_kirim), 0) AS n FROM tbltransfer_stok WHERE qty_terima = 0')->row_array();

        return [
            'saldo'      => $this->saldo(),
            'penjualan'  => $hitung['PENJUALAN'],
            'perjalanan' => (int) $jalan['n'],
            'per_ref'    => $hitung,
        ];
    }

    /**
     * @param array $f q, ref (daftar dipisah koma, kosong = semua), dari, sampai (Y-m-d), per, halaman
     * @return array rows, total, halaman, jml_hal, dari, sampai
     */
    public function daftar(array $f)
    {
        $per = (int) (isset($f['per']) ? $f['per'] : 10);
        if (!in_array($per, self::PER_HALAMAN_BOLEH, TRUE)) {
            $per = 10;
        }

        $this->sinkron_terjadwal();
        list($where, $bind) = $this->bangun_filter($f);
        $buku  = $this->sql_buku();
        $total = (int) $this->db->query('SELECT COUNT(*) AS n FROM (' . $buku . ') x' . $where, $bind)->row()->n;

        $jml_hal = max(1, (int) ceil($total / $per));
        $halaman = min(max(1, (int) (isset($f['halaman']) ? $f['halaman'] : 1)), $jml_hal);
        $mulai   = ($halaman - 1) * $per;

        $rows = $this->db->query(
            'SELECT x.* FROM (' . $buku . ') x' . $where . ' ORDER BY x.urut DESC, x.sumber DESC, x.sid DESC LIMIT ' . (int) $mulai . ', ' . (int) $per,
            $bind
        )->result_array();

        $this->lengkapi_resi($rows);
        foreach ($rows as &$r) {
            $r['masuk']  = (int) $r['masuk'];
            $r['keluar'] = (int) $r['keluar'];
            $r['sisa']   = (int) $r['sisa'];
            unset($r['urut'], $r['sumber'], $r['sid']);
        }
        unset($r);

        return [
            'rows'    => $rows,
            'total'   => $total,
            'halaman' => $halaman,
            'jml_hal' => $jml_hal,
            'dari'    => $total ? $mulai + 1 : 0,
            'sampai'  => $total ? $mulai + count($rows) : 0,
        ];
    }

    private function bangun_filter(array $f)
    {
        $w = [];
        $b = [];

        $ref = [];
        if (!empty($f['ref'])) {
            foreach (explode(',', (string) $f['ref']) as $r) {
                $r = strtoupper(trim($r));
                if (in_array($r, self::REFS, TRUE)) {
                    $ref[] = $r;
                }
            }
        }
        if ($ref) {
            $w[] = 'x.ref IN (' . implode(',', array_fill(0, count($ref), '?')) . ')';
            $b   = array_merge($b, $ref);
        }
        if (!empty($f['dari']) && Transfer_stok_fcd::tanggal_sah($f['dari'])) {
            $w[] = 'x.tanggal >= ?';
            $b[] = $f['dari'];
        }
        if (!empty($f['sampai']) && Transfer_stok_fcd::tanggal_sah($f['sampai'])) {
            $w[] = 'x.tanggal <= ?';
            $b[] = $f['sampai'];
        }
        $q = isset($f['q']) ? trim((string) $f['q']) : '';
        if ($q !== '') {
            // Satu teks pencarian untuk yang tampil di tabel: no pesanan/transfer, ref, keterangan,
            // penanda, tanggal, jam, masuk, keluar, sisa, qty jual, dan no pesanan resi.
            $w[] = "(CONCAT_WS(' ', x.no_ref, x.ref, x.keterangan, IFNULL(x.flag, ''), x.tanggal, DATE_FORMAT(x.tanggal, '%d/%m/%Y'),"
                . " IFNULL(x.jam, ''), IF(x.masuk > 0, x.masuk, ''), IF(x.keluar > 0, x.keluar, ''), x.sisa, IFNULL(x.qty_jual, '')) LIKE ? ESCAPE '!'"
                . " OR (x.ref <> 'INPUT' AND x.no_ref <> '' AND EXISTS (SELECT 1 FROM tblprintresi pr JOIN tbldetailprintresi dd ON dd.id_resi = pr.id_printresi"
                . " WHERE pr.noresi = x.no_ref AND dd.no_pesanan LIKE ? ESCAPE '!')))";
            $like = '%' . $this->db->escape_like_str($q) . '%';
            $b[] = $like;
            $b[] = $like;
        }
        return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $b];
    }

    /**
     * No. Resi / No. Transfer dipisah dari no_ref (INPUT = nomor transfer, ref lain = no resi).
     * No. Pesanan untuk baris berbasis resi, serta Tgl QC, Tgl HO, dan status resi untuk baris
     * PENJUALAN: diambil dari data resi (tblprintresi, tbldetailprintresi, tblpacking, tblresikeluar),
     * tidak disimpan di buku stok.
     */
    private function lengkapi_resi(array &$rows)
    {
        $no = [];
        foreach ($rows as $r) {
            if ($r['ref'] !== 'INPUT' && $r['no_ref'] !== '') {
                $no[$r['no_ref']] = TRUE;
            }
        }
        $peta = [];
        if ($no) {
            // Query mentah: select() CI memecah teks di setiap koma, termasuk koma di dalam SEPARATOR.
            $daftar = implode(',', array_map([$this->db, 'escape'], array_keys($no)));
            $hasil  = $this->db->query(
                "SELECT pr.noresi, pr.status_pesanan,"
                . " (SELECT GROUP_CONCAT(DISTINCT dd.no_pesanan SEPARATOR ', ') FROM tbldetailprintresi dd WHERE dd.id_resi = pr.id_printresi) AS no_pesanan,"
                . " (SELECT MIN(p.tanggal_packing) FROM tblpacking p WHERE p.id_resi = pr.id_printresi) AS tgl_qc,"
                . " (SELECT MIN(h.tanggal_resikeluar) FROM tblresikeluar h WHERE h.id_resi = pr.id_printresi) AS tgl_ho"
                . " FROM tblprintresi pr WHERE pr.noresi IN (" . $daftar . ")"
            )->result_array();
            foreach ($hasil as $p) {
                if (!isset($peta[$p['noresi']])) {
                    $peta[$p['noresi']] = $p;
                }
            }
        }
        foreach ($rows as &$r) {
            $resi = ($r['ref'] !== 'INPUT' && $r['no_ref'] !== '');
            $p    = ($resi && isset($peta[$r['no_ref']])) ? $peta[$r['no_ref']] : NULL;
            $jual = ($r['ref'] === 'PENJUALAN' && $p);
            $r['no_transfer'] = $r['ref'] === 'INPUT' ? $r['no_ref'] : '';
            $r['no_resi']     = $resi ? $r['no_ref'] : '';
            $r['no_pesanan']  = $p ? $p['no_pesanan'] : NULL;
            $r['tgl_qc']      = $jual ? $p['tgl_qc'] : NULL;
            $r['tgl_ho']      = $jual ? $p['tgl_ho'] : NULL;
            $r['status']      = $jual ? $p['status_pesanan'] : NULL;
            unset($r['no_ref']);
        }
        unset($r);
    }
}
