<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stok Retur Buka (TIM RETUR) — barang dari retur yang sudah dibuka dan dikembalikan ke display
 * (tblbukaretur.status_detail_buka = KE_DISPLAY) tetapi stoknya BELUM diubah accounting di Jubelio.
 *
 * Penanda "sudah diubah accounting" = resi sudah diverifikasi di menu Verifikasi Retur
 * (tblreturverifikasi.verified = 1; Verifikasi Retur Komplain memakai tabel yang sama).
 * Begitu resi diverifikasi, barangnya keluar dari stok ini. Hanya membaca data.
 *
 * Bentuk hasil sengaja sama dengan Cancel_terima_fcd::stok_paket_cancel() supaya tampilannya
 * bisa mengikuti menu Stok Paket Cancel.
 */
class Stok_retur_buka_fcd extends CI_Model
{
    /**
     * @param string[]|null $resi_pilih bila diisi, hanya baris dari resi ini yang dihitung (dipakai cetak serah
     *                                  terima dari Daftar Retur). Aturan stoknya TETAP sama, jadi qty di kertas
     *                                  serah terima dan di menu ini selalu sama.
     */
    public function stok(array $resi_pilih = null)
    {
        if ($resi_pilih !== null) {
            $resi_pilih = array_flip(array_map('strtoupper', array_map('strval', $resi_pilih)));
        }
        $baris = $this->db->query(
            "SELECT b.id_bukaretur, b.resi_buka, b.sku, b.qty, b.no_pesanan, b.toko, b.is_komplain,
                    b.tanggal_buka_retur, s.nama_sku, s.no_rak, s.link_foto, s.foto_lokal
             FROM tblbukaretur b
             LEFT JOIN tblsku s ON s.id_sku = b.sku
             WHERE b.status_detail_buka = 'KE_DISPLAY' AND b.qty > 0
               AND NOT EXISTS (SELECT 1 FROM tblreturverifikasi v WHERE v.no_resi = b.resi_buka AND v.verified = 1)
             ORDER BY b.tanggal_buka_retur DESC, b.id_bukaretur DESC"
        )->result_array();

        $per = array();
        $resi_semua = array();
        $resi_komplain = array();
        $total = 0;
        foreach ($baris as $b) {
            if ($resi_pilih !== null && !isset($resi_pilih[strtoupper((string) $b['resi_buka'])])) continue;
            $k = strtoupper((string) $b['sku']);
            $qty = (int) $b['qty'];
            if (!isset($per[$k])) {
                $per[$k] = array(
                    'sku' => (string) $b['sku'], 'nama' => (string) $b['nama_sku'], 'rak' => (string) $b['no_rak'],
                    'foto' => trim((string) $b['link_foto']),
                    'foto_lokal' => foto_sku_lokal_url($b['link_foto'], $b['foto_lokal']),
                    'stok' => 0, 'komplain' => false, 'resi' => array(),
                );
            }
            $per[$k]['stok'] += $qty;
            if ((int) $b['is_komplain'] === 1) {
                $per[$k]['komplain'] = true;
                $resi_komplain[$b['resi_buka']] = true;
            }
            $per[$k]['resi'][] = array(
                'id' => (int) $b['id_bukaretur'],
                'noresi' => (string) $b['resi_buka'],
                'no_pesanan' => (string) $b['no_pesanan'],
                'toko' => (string) $b['toko'],
                'komplain' => (int) $b['is_komplain'] === 1,
                'tgl' => $b['tanggal_buka_retur'] ? substr($b['tanggal_buka_retur'], 0, 10) : '',
                'jam' => $b['tanggal_buka_retur'] ? substr($b['tanggal_buka_retur'], 11, 5) : '',
                'qty' => $qty,
            );
            $resi_semua[$b['resi_buka']] = true;
            $total += $qty;
        }

        $rows = array_values($per);
        foreach ($rows as &$r) {
            $r['jumlah_resi'] = count(array_unique(array_column($r['resi'], 'noresi')));
        }
        unset($r);
        usort($rows, function ($a, $b) {
            return ($b['stok'] <=> $a['stok']) ?: strcmp($a['sku'], $b['sku']);
        });

        return array('rows' => $rows, 'ringkas' => array(
            'sku' => count($rows), 'qty' => $total,
            'resi' => count($resi_semua), 'resi_komplain' => count($resi_komplain),
        ));
    }

    /**
     * Baris kertas serah terima Retur -> Restok dari hasil stok(): satu baris per SKU, qty = stok SKU itu
     * (angka yang sama dengan menu Stok Retur Buka), terurut rak lalu SKU.
     *
     * @return array list of ['sku', 'rak', 'qty']
     */
    public function baris_serah_terima(array $rows)
    {
        $det = array();
        foreach ($rows as $r) {
            if ((int) $r['stok'] <= 0) continue;
            $det[] = array('sku' => $r['sku'], 'rak' => (string) $r['rak'], 'qty' => (int) $r['stok']);
        }
        usort($det, function ($x, $y) { return strcmp($x['rak'], $y['rak']) ?: strcmp($x['sku'], $y['sku']); });
        return $det;
    }
}
