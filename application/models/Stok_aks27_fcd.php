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
    const TITIK_GRAFIK      = 200;

    /** Resi dengan scan packer (atau scan HO bila tanpa catatan packer) sebelum tanggal ini tidak memotong stok bonus. */
    const TANGGAL_MULAI_POTONG = '2026-10-28';

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
        $in  = $this->db->query('SELECT COALESCE(SUM(qty), 0) AS n FROM tbltransfer_stok_terima')->row_array();
        $lain = $this->db->query('SELECT COALESCE(SUM(masuk) - SUM(keluar), 0) AS n FROM tblstok_aks27')->row_array();
        return (int) $in['n'] + (int) $lain['n'];
    }

    /** Kartu ringkasan, jumlah baris per ref, dan titik grafik saldo. */
    public function ringkas()
    {
        $hitung = [];
        foreach (self::REFS as $r) {
            $hitung[$r] = 0;
        }
        $hitung['INPUT'] = (int) $this->db->count_all('tbltransfer_stok_terima');
        foreach ($this->db->query('SELECT ref, COUNT(*) AS n FROM tblstok_aks27 GROUP BY ref')->result_array() as $row) {
            $hitung[$row['ref']] = (int) $row['n'];
        }

        $jalan = $this->db->query('SELECT COALESCE(SUM(qty_kirim), 0) AS n FROM tbltransfer_stok WHERE qty_terima = 0')->row_array();

        $titik = $this->db->query(
            'SELECT x.sisa FROM (' . $this->sql_buku() . ') x ORDER BY x.urut DESC, x.sumber DESC, x.sid DESC LIMIT ' . (int) self::TITIK_GRAFIK
        )->result_array();
        $grafik = array_reverse(array_map('intval', array_column($titik, 'sisa')));

        return [
            'saldo'      => $this->saldo(),
            'penjualan'  => $hitung['PENJUALAN'],
            'perjalanan' => (int) $jalan['n'],
            'per_ref'    => $hitung,
            'grafik'     => $grafik,
            'total_baris' => array_sum($hitung),
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
            // penanda, tanggal, jam, masuk, keluar, sisa, qty jual.
            $w[] = "CONCAT_WS(' ', x.no_ref, x.ref, x.keterangan, IFNULL(x.flag, ''), x.tanggal, DATE_FORMAT(x.tanggal, '%d/%m/%Y'),"
                . " IFNULL(x.jam, ''), IF(x.masuk > 0, x.masuk, ''), IF(x.keluar > 0, x.keluar, ''), x.sisa, IFNULL(x.qty_jual, '')) LIKE ? ESCAPE '!'";
            $b[] = '%' . $this->db->escape_like_str($q) . '%';
        }
        return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $b];
    }

    /**
     * Tgl QC, Tgl HO, dan status resi untuk baris PENJUALAN: diambil dari data resi
     * (tblprintresi, tblpacking, tblscan_ho), tidak disimpan di buku stok.
     */
    private function lengkapi_resi(array &$rows)
    {
        $no = [];
        foreach ($rows as $r) {
            if ($r['ref'] === 'PENJUALAN' && $r['no_ref'] !== '') {
                $no[$r['no_ref']] = TRUE;
            }
        }
        $peta = [];
        if ($no) {
            $this->db->select('pr.noresi, pr.status_pesanan,'
                . ' (SELECT MIN(p.tanggal_packing) FROM tblpacking p WHERE p.id_resi = pr.id_printresi) AS tgl_qc,'
                . ' (SELECT MIN(h.tanggal_scan) FROM tblscan_ho h WHERE h.id_resi = pr.id_printresi) AS tgl_ho', FALSE)
                ->from('tblprintresi pr')->where_in('pr.noresi', array_keys($no));
            foreach ($this->db->get()->result_array() as $p) {
                if (!isset($peta[$p['noresi']])) {
                    $peta[$p['noresi']] = $p;
                }
            }
        }
        foreach ($rows as &$r) {
            $p = isset($peta[$r['no_ref']]) ? $peta[$r['no_ref']] : NULL;
            $r['tgl_qc'] = ($r['ref'] === 'PENJUALAN' && $p) ? $p['tgl_qc'] : NULL;
            $r['tgl_ho'] = ($r['ref'] === 'PENJUALAN' && $p) ? $p['tgl_ho'] : NULL;
            $r['status'] = ($r['ref'] === 'PENJUALAN' && $p) ? $p['status_pesanan'] : NULL;
        }
        unset($r);
    }
}
