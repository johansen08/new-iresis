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
    public function stok(array $resi_pilih = null, $pakai_status_acc = false, $tanpa_komplain = false)
    {
        if ($resi_pilih !== null) {
            $resi_pilih = array_flip(array_map('strtoupper', array_map('strval', $resi_pilih)));
        }
        $kolom = "b.id_bukaretur, b.resi_buka, b.sku, b.qty, b.no_pesanan, b.toko, b.is_komplain,
                    b.tanggal_buka_retur, s.nama_sku, s.no_rak, s.link_foto, s.foto_lokal, s.bundle";
        if ($pakai_status_acc) {
            // Cetak serah terima dari tab Sudah dibuka (Daftar Retur): hanya baris KE_DISPLAY yang belum di-ACC
            // accounting (status_acc = 0) -- aturan yang sama dengan isi tab. Prod dulu, lalu arsip untuk baris
            // yang sudah dipurna dari prod (id yang sama tidak dihitung dua kali).
            $this->config->load('arsip', TRUE, TRUE);
            $arsip = (string) $this->config->item('db_arsip', 'arsip');
            if (!preg_match('/^[A-Za-z0-9_]+$/', $arsip)) $arsip = 'iresis_arsip';
            $sql = "SELECT {$kolom} FROM %s.tblbukaretur b LEFT JOIN tblsku s ON s.id_sku = b.sku
                    WHERE b.status_detail_buka = 'KE_DISPLAY' AND b.qty > 0 AND b.status_acc = 0";
            // Cetak serah terima hanya untuk retur ekspedisi: baris komplain (is_komplain = 1) tidak boleh ikut ke kertas.
            if ($tanpa_komplain) $sql .= " AND (b.is_komplain IS NULL OR b.is_komplain = 0)";
            if ($resi_pilih !== null) {
                if (!$resi_pilih) return array('rows' => array(), 'ringkas' => array('sku' => 0, 'qty' => 0, 'resi' => 0, 'resi_komplain' => 0));
                $in = implode(',', array_map(array($this->db, 'escape'), array_keys($resi_pilih)));
                $sql .= " AND UPPER(b.resi_buka) IN ({$in})";
            }
            $baris = array();
            foreach (array($this->db->database, $arsip) as $schema) {
                foreach ($this->db->query(sprintf($sql, '`' . $schema . '`'))->result_array() as $b) {
                    $baris[(int) $b['id_bukaretur']] = isset($baris[(int) $b['id_bukaretur']]) ? $baris[(int) $b['id_bukaretur']] : $b;
                }
            }
            $baris = array_values($baris);
            usort($baris, function ($x, $y) { return strcmp((string) $y['tanggal_buka_retur'], (string) $x['tanggal_buka_retur']) ?: ($y['id_bukaretur'] <=> $x['id_bukaretur']); });
        } else {
            $baris = $this->db->query(
                "SELECT {$kolom}
                 FROM tblbukaretur b
                 LEFT JOIN tblsku s ON s.id_sku = b.sku
                 WHERE b.status_detail_buka = 'KE_DISPLAY' AND b.qty > 0
                   AND NOT EXISTS (SELECT 1 FROM tblreturverifikasi v WHERE v.no_resi = b.resi_buka AND v.verified = 1)
                 ORDER BY b.tanggal_buka_retur DESC, b.id_bukaretur DESC"
            )->result_array();
        }

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
                    'is_bundle' => strtoupper(trim((string) $b['bundle'])) === 'TRUE',
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
     * SKU bundling (tblsku.bundle = True) diganti SKU anakannya (isi bundle dari Jubelio, qty = qty bundle x qty komponen),
     * karena yang dikembalikan ke display adalah barang anakannya. Anakan yang sama digabung dengan SKU biasa. Bila isi
     * bundle gagal dibaca, baris bundle tetap dicetak apa adanya dengan tanda "(BUNDLE, isi belum terurai)".
     *
     * @return array list of ['sku', 'rak', 'qty']
     */
    public function baris_serah_terima(array $rows)
    {
        $per = array();       // KEY SKU (huruf besar) => baris
        $perlu_rak = array(); // SKU anakan yang rak-nya harus dicari
        foreach ($rows as $r) {
            $qty = (int) $r['stok'];
            if ($qty <= 0) continue;
            $isi = array();
            if (!empty($r['is_bundle'])) {
                try {
                    $this->load->library('jubelio_stok');
                    $isi = $this->jubelio_stok->komponen($r['sku']);
                } catch (Exception $e) {
                    log_message('error', 'Serah terima retur: isi bundle ' . $r['sku'] . ' gagal dibaca: ' . $e->getMessage());
                    $isi = array();
                }
            }
            if (empty($isi)) {
                $k = strtoupper($r['sku']);
                if (isset($per[$k])) { $per[$k]['qty'] += $qty; continue; }
                $per[$k] = array('sku' => $r['sku'] . (!empty($r['is_bundle']) ? ' (BUNDLE, isi belum terurai)' : ''),
                    'rak' => (string) $r['rak'], 'qty' => $qty);
                continue;
            }
            foreach ($isi as $kom) {
                $k = strtoupper($kom['sku']);
                $q = $qty * (int) $kom['qty'];
                if (isset($per[$k])) { $per[$k]['qty'] += $q; continue; }
                $per[$k] = array('sku' => $kom['sku'], 'rak' => '', 'qty' => $q);
                $perlu_rak[$k] = $kom['sku'];
            }
        }
        if ($perlu_rak) {
            $q = $this->db->query(
                'SELECT id_sku, TRIM(COALESCE(no_rak, "")) AS no_rak FROM tblsku WHERE id_sku IN (' . implode(',', array_fill(0, count($perlu_rak), '?')) . ')',
                array_values($perlu_rak)
            )->result_array();
            foreach ($q as $s) {
                $k = strtoupper($s['id_sku']);
                if (isset($per[$k])) $per[$k]['rak'] = (string) $s['no_rak'];
            }
        }
        $det = array_values($per);
        usort($det, function ($x, $y) { return strcmp($x['rak'], $y['rak']) ?: strcmp($x['sku'], $y['sku']); });
        return $det;
    }
}
