<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Terima Paket Cancel & Laporan Paket Cancel (tim retur). Rujukan: docs/PAKET_CANCEL.md §12.
 *
 * Packer / HO / kurir menyerahkan paket cancel ke tim retur -> scan resi + cocokkan
 * isi (tblcancel_paket_item) -> paket "diterima" (tblcancel_paket.dicek_at terisi,
 * dihapus_at kosong). Serah terima ke restock dicetak per hari dari paket yang
 * diterima pada tanggal itu; tidak ada status & tidak ada tabel serah.
 *
 * Tidak ada DELETE: hapus = tblcancel_paket.dihapus_at terisi; mengganti isi
 * (edit / terima ulang) = baris item lama aktif=0 lalu baris baru. Setiap aksi
 * dicatat di tblcancel_paket_log.
 */
class Cancel_terima_fcd extends CI_Model
{
    const SUMBER = ['PACKER', 'HO', 'KURIR'];

    /** Jenis selisih satu baris item. */
    public static function jenis_item($qty_resi, $qty_ditemukan)
    {
        $qty_resi = (int) $qty_resi;
        $qty_ditemukan = (int) $qty_ditemukan;
        if ($qty_resi === 0) {
            return $qty_ditemukan > 0 ? 'EXTRA' : 'SESUAI';
        }
        if ($qty_ditemukan === $qty_resi) {
            return 'SESUAI';
        }
        return $qty_ditemukan < $qty_resi ? 'KURANG' : 'LEBIH';
    }

    private function mode_arsip()
    {
        return (bool) $this->session->userdata('mode_arsip');
    }

    private function resi_by_noresi($noresi)
    {
        return $this->db->get_where('tblprintresi', ['noresi' => trim((string) $noresi)])->row();
    }

    private function resi_cancel($resi)
    {
        return strtoupper(trim((string) $resi->status_pesanan)) === 'CANCELED' || (string) $resi->batal === '1';
    }

    /** Foto SKU untuk klien: url asli + salinan lokal (assets/js/foto_sku.js memilih yang bisa dimuat). */
    private function tambah_foto(array $row)
    {
        $row['foto']       = trim((string) ($row['link_foto'] ?? ''));
        $row['foto_lokal'] = foto_sku_lokal_url($row['link_foto'] ?? '', $row['foto_lokal'] ?? '');
        unset($row['link_foto']);
        return $row;
    }

    /** Baris isi resi, digabung per SKU (SKU sama di dua baris dijumlah). */
    private function isi_resi($id_resi)
    {
        $rows = $this->db
            ->select('d.sku, MAX(d.no_rak) AS no_rak, SUM(d.jumlah) AS qty, MAX(s.link_foto) AS link_foto, MAX(s.foto_lokal) AS foto_lokal', false)
            ->from('tbldetailprintresi d')
            ->join('tblsku s', 's.id_sku = d.sku', 'left')
            ->where('d.id_resi', (int) $id_resi)
            ->group_by('d.sku')
            ->order_by('MAX(d.no_rak)', 'ASC', false)
            ->order_by('d.sku', 'ASC')
            ->get()->result_array();
        return array_map([$this, 'tambah_foto'], $rows);
    }

    /** Paket yang sedang berstatus "diterima" untuk satu resi (NULL bila belum / sudah dihapus). */
    private function paket_aktif($id_resi)
    {
        return $this->db
            ->where('id_resi', (int) $id_resi)
            ->where('dicek_at IS NOT NULL', null, false)
            ->where('dihapus_at IS NULL', null, false)
            ->get('tblcancel_paket')->row();
    }

    // ─────────────────────────────────────────────
    //  Terima Paket Cancel
    // ─────────────────────────────────────────────

    /**
     * Hasil scan resi. 'status': tidak_ada | bukan_cancel | sudah_diterima | ok
     */
    public function cek_resi($noresi)
    {
        $noresi = trim((string) $noresi);
        if ($noresi === '') {
            return ['status' => 'tidak_ada'];
        }
        $resi = $this->resi_by_noresi($noresi);
        if (!$resi) {
            return ['status' => 'tidak_ada'];
        }
        $info = [
            'noresi'         => $resi->noresi,
            'id_resi'        => (int) $resi->id_printresi,
            'toko'           => $resi->toko,
            'status_pesanan' => $resi->status_pesanan,
        ];
        if (!$this->resi_cancel($resi)) {
            return ['status' => 'bukan_cancel'] + $info;
        }

        $aktif = $this->paket_aktif($resi->id_printresi);
        if ($aktif) {
            $petugas = $this->db->select('name')->get_where('tbluser', ['id_user' => $aktif->dicek_oleh])->row();
            $info['sudah'] = [
                'dicek_at' => $aktif->dicek_at,
                'dari'     => $aktif->diterima_dari,
                'petugas'  => $petugas ? $petugas->name : null,
            ];
            return ['status' => 'sudah_diterima'] + $info;
        }

        $tolak = $this->db->select('ditemukan_di')->get_where('tblcancel_paket', ['id_resi' => $resi->id_printresi])->row();
        $info['ditolak_di'] = $tolak ? $tolak->ditemukan_di : null;
        $info['isi'] = $this->isi_resi($resi->id_printresi);
        return ['status' => 'ok'] + $info;
    }

    /**
     * Bentuk baris item (SKU resi + SKU di luar resi) dari kiriman klien.
     * @return array ['baris' => [...], 'selisih' => bool] atau ['error' => pesan]
     */
    private function susun_item(array $isi_resi, array $items)
    {
        $fisik = [];
        foreach ($items as $it) {
            $sku = trim((string) ($it['sku'] ?? ''));
            $qty = (int) ($it['qty'] ?? 0);
            if ($sku === '') {
                continue;
            }
            if ($qty < 0 || $qty > 100000) {
                return ['error' => 'Qty tidak valid untuk SKU ' . $sku];
            }
            $key = strtoupper($sku);
            $fisik[$key] = ($fisik[$key] ?? ['sku' => $sku, 'qty' => 0]);
            $fisik[$key]['qty'] += $qty;
        }

        $baris = [];
        $selisih = false;
        foreach ($isi_resi as $r) {
            $key = strtoupper($r['sku']);
            $ada = isset($fisik[$key]) ? $fisik[$key]['qty'] : 0;
            unset($fisik[$key]);
            $baris[] = ['sku' => $r['sku'], 'no_rak' => $r['no_rak'], 'qty_resi' => (int) $r['qty'], 'qty_ditemukan' => $ada];
            if ($ada !== (int) $r['qty']) {
                $selisih = true;
            }
        }
        foreach ($fisik as $f) { // sisa = SKU yang tidak ada di resi
            if ($f['qty'] <= 0) {
                continue;
            }
            $sku = $this->db->select('id_sku, no_rak')->get_where('tblsku', ['id_sku' => $f['sku']])->row();
            if (!$sku) {
                return ['error' => 'SKU "' . $f['sku'] . '" tidak dikenal di master SKU'];
            }
            $baris[] = ['sku' => $sku->id_sku, 'no_rak' => $sku->no_rak, 'qty_resi' => 0, 'qty_ditemukan' => $f['qty']];
            $selisih = true;
        }
        return ['baris' => $baris, 'selisih' => $selisih];
    }

    private function tulis_log($id_paket, $aksi, $id_user, $alasan, $detail)
    {
        $this->db->insert('tblcancel_paket_log', [
            'id_cancel_paket' => (int) $id_paket,
            'aksi'            => $aksi,
            'id_user'         => $id_user,
            'waktu'           => date('Y-m-d H:i:s'),
            'alasan'          => $alasan === null || $alasan === '' ? null : substr($alasan, 0, 255),
            'detail'          => $detail === null ? null : (is_string($detail) ? $detail : json_encode($detail, JSON_UNESCAPED_UNICODE)),
        ]);
    }

    /**
     * Simpan penerimaan satu paket cancel.
     * @param array $items [['sku' => ..., 'qty' => ...], ...] qty yang ADA secara fisik (isi resi + barang di luar resi)
     * @return array ['ok' => bool, 'message' => string, 'selisih' => bool]
     */
    public function simpan($noresi, $dari, array $items, $catatan, $id_user, $nama_komputer)
    {
        if ($this->mode_arsip()) {
            return ['ok' => false, 'message' => 'Mode Arsip aktif: data tidak bisa disimpan'];
        }
        $dari = strtoupper((string) $dari);
        if (!in_array($dari, self::SUMBER, true)) {
            return ['ok' => false, 'message' => 'Asal paket harus Packer, HO, atau Kurir'];
        }
        $cek = $this->cek_resi($noresi);
        if ($cek['status'] === 'tidak_ada') {
            return ['ok' => false, 'message' => 'Resi tidak ditemukan'];
        }
        if ($cek['status'] === 'bukan_cancel') {
            return ['ok' => false, 'message' => 'Resi ini bukan pesanan CANCEL (status ' . $cek['status_pesanan'] . ')'];
        }
        if ($cek['status'] === 'sudah_diterima') {
            return ['ok' => false, 'message' => 'Resi ini sudah diterima tim retur'];
        }

        $s = $this->susun_item($cek['isi'], $items);
        if (isset($s['error'])) {
            return ['ok' => false, 'message' => $s['error']];
        }
        $catatan = trim((string) $catatan);
        if ($s['selisih'] && $catatan === '') {
            return ['ok' => false, 'message' => 'Ada selisih SKU / qty: isi catatan penyebabnya'];
        }

        $now = date('Y-m-d H:i:s');
        $db_debug_asli = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_start();

        $paket = $this->db->query('SELECT * FROM tblcancel_paket WHERE id_resi = ? FOR UPDATE', [$cek['id_resi']])->row();
        if ($paket && $paket->dicek_at !== null && $paket->dihapus_at === null) {
            $this->db->trans_complete();
            $this->db->db_debug = $db_debug_asli;
            return ['ok' => false, 'message' => 'Resi ini baru saja diterima tim retur'];
        }
        $set = [
            'status'        => 'DICEK',
            'diterima_dari' => $dari,
            'dicek_oleh'    => $id_user,
            'dicek_at'      => $now,
            'diedit_at'     => null,
            'diedit_oleh'   => null,
            'dihapus_at'    => null,
            'dihapus_oleh'  => null,
            'alasan_hapus'  => null,
            'catatan'       => $catatan === '' ? null : substr($catatan, 0, 255),
            'updated_at'    => $now,
        ];
        if ($paket) {
            // Terima ulang setelah dihapus (atau baris dari scan yang ditolak): isi lama dinonaktifkan.
            $this->db->where('id_cancel_paket', $paket->id_cancel_paket)->update('tblcancel_paket', $set);
            $this->db->where('id_cancel_paket', $paket->id_cancel_paket)->update('tblcancel_paket_item', ['aktif' => 0]);
            $id_paket = (int) $paket->id_cancel_paket;
        } else {
            // Resi tidak pernah ditolak di scan mana pun (mis. HO tidak bisa scan): baris dibuat di sini.
            $this->db->insert('tblcancel_paket', $set + [
                'id_resi'            => $cek['id_resi'],
                'noresi'             => $cek['noresi'],
                'ditemukan_di'       => $dari,
                'ditemukan_oleh'     => $id_user,
                'ditemukan_komputer' => $nama_komputer,
                'ditemukan_at'       => $now,
                'jumlah_tolak'       => 0,
                'created_at'         => $now,
            ]);
            $id_paket = (int) $this->db->insert_id();
        }
        foreach ($s['baris'] as $b) {
            $this->db->insert('tblcancel_paket_item', $b + [
                'id_cancel_paket' => $id_paket,
                'id_resi'         => $cek['id_resi'],
                'aktif'           => 1,
                'created_at'      => $now,
            ]);
        }
        $this->tulis_log($id_paket, 'TERIMA', $id_user, null, [
            'dari' => $dari, 'selisih' => $s['selisih'], 'catatan' => $catatan, 'item' => $s['baris'],
        ]);
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
        $this->db->db_debug = $db_debug_asli;

        return $ok
            ? ['ok' => true, 'selisih' => $s['selisih'], 'message' => $s['selisih'] ? 'Tersimpan, ADA SELISIH tercatat' : 'Tersimpan, isi sesuai resi']
            : ['ok' => false, 'message' => 'Gagal menyimpan, coba lagi'];
    }

    // ─────────────────────────────────────────────
    //  Pencarian SKU (barang di luar resi)
    // ─────────────────────────────────────────────

    /** Tambahkan foto (link_foto / foto_lokal) ke baris tblsku hasil pencarian. */
    private function lengkapi_sku(array $rows)
    {
        if (!$rows) {
            return [];
        }
        $ids = array_column($rows, 'id_sku');
        $foto = [];
        foreach ($this->db->select('id_sku, link_foto, foto_lokal')->where_in('id_sku', $ids)->get('tblsku')->result_array() as $f) {
            $foto[$f['id_sku']] = $f;
        }
        foreach ($rows as &$r) {
            $f = $foto[$r['id_sku']] ?? ['link_foto' => '', 'foto_lokal' => ''];
            $r = $this->tambah_foto($r + ['link_foto' => $f['link_foto'], 'foto_lokal' => $f['foto_lokal']]);
        }
        return $rows;
    }

    public function saran_sku($q)
    {
        $this->load->model('qc_return_fcd');
        return $this->lengkapi_sku($this->qc_return_fcd->get_sku_suggestions($q));
    }

    /** Sama dengan Qc_return_fcd::cari_sku() + foto. */
    public function cari_sku($teks)
    {
        $this->load->model('qc_return_fcd');
        $h = $this->qc_return_fcd->cari_sku($teks);
        if (($h['status'] ?? '') === 'ok') {
            $h['sku'] = $this->lengkapi_sku([$h['sku']])[0];
        } elseif (($h['status'] ?? '') === 'ambigu') {
            $h['kandidat'] = $this->lengkapi_sku($h['kandidat']);
        }
        return $h;
    }

    // ─────────────────────────────────────────────
    //  Laporan, edit, hapus
    // ─────────────────────────────────────────────

    /**
     * Paket diterima (belum dihapus) pada rentang tanggal, lengkap dengan item-nya.
     * Maksimal 93 hari supaya balasan tetap ringan.
     */
    public function laporan_baris($start, $end)
    {
        $rows = $this->db
            ->select("p.id_cancel_paket AS id, p.noresi, p.diterima_dari AS dari, p.catatan, p.dicek_at, p.diedit_at,
                u.name AS petugas, r.toko", false)
            ->from('tblcancel_paket p')
            ->join('tbluser u', 'u.id_user = p.dicek_oleh', 'left')
            ->join('tblprintresi r', 'r.id_printresi = p.id_resi', 'left')
            ->where('p.dicek_at IS NOT NULL', null, false)
            ->where('p.dihapus_at IS NULL', null, false)
            ->where('p.dicek_at >=', $start . ' 00:00:00')
            ->where('p.dicek_at <=', $end . ' 23:59:59')
            ->order_by('p.dicek_at', 'DESC')
            ->get()->result_array();
        if (!$rows) {
            return [];
        }

        $ids = array_column($rows, 'id');
        $per = [];
        $items = $this->db
            ->select('i.id_cancel_paket, i.sku, i.no_rak, i.qty_resi, i.qty_ditemukan, s.link_foto, s.foto_lokal')
            ->from('tblcancel_paket_item i')
            ->join('tblsku s', 's.id_sku = i.sku', 'left')
            ->where_in('i.id_cancel_paket', $ids)
            ->where('i.aktif', 1)
            ->order_by('i.id_item', 'ASC')
            ->get()->result_array();
        foreach ($items as $it) {
            $key = $it['id_cancel_paket'];
            unset($it['id_cancel_paket']);
            $it['qty_resi'] = (int) $it['qty_resi'];
            $it['qty_ditemukan'] = (int) $it['qty_ditemukan'];
            $per[$key][] = $this->tambah_foto($it);
        }
        foreach ($rows as &$r) {
            $r['id'] = (int) $r['id'];
            $r['tgl'] = substr($r['dicek_at'], 0, 10);
            $r['jam'] = substr($r['dicek_at'], 11, 8);
            $r['diedit'] = $r['diedit_at'] ? substr($r['diedit_at'], 11, 8) : null;
            $r['items'] = $per[$r['id']] ?? [];
            $r['selisih'] = false;
            foreach ($r['items'] as $it) {
                if (self::jenis_item($it['qty_resi'], $it['qty_ditemukan']) !== 'SESUAI') {
                    $r['selisih'] = true;
                    break;
                }
            }
            unset($r['dicek_at'], $r['diedit_at']);
        }
        return $rows;
    }

    private function paket_untuk_ubah($id_paket)
    {
        return $this->db->query(
            'SELECT * FROM tblcancel_paket WHERE id_cancel_paket = ? AND dicek_at IS NOT NULL AND dihapus_at IS NULL FOR UPDATE',
            [(int) $id_paket]
        )->row();
    }

    /**
     * Edit paket yang sudah diterima: asal, qty ada per SKU, barang di luar resi, catatan.
     * Qty resi tetap (diambil dari item aktif), tidak bisa diubah dari klien.
     */
    public function edit($id_paket, $dari, array $items, $catatan, $id_user)
    {
        if ($this->mode_arsip()) {
            return ['ok' => false, 'message' => 'Mode Arsip aktif: data tidak bisa disimpan'];
        }
        $dari = strtoupper((string) $dari);
        if (!in_array($dari, self::SUMBER, true)) {
            return ['ok' => false, 'message' => 'Asal paket harus Packer, HO, atau Kurir'];
        }

        $db_debug_asli = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_start();

        $paket = $this->paket_untuk_ubah($id_paket);
        if (!$paket) {
            $this->db->trans_complete();
            $this->db->db_debug = $db_debug_asli;
            return ['ok' => false, 'message' => 'Paket tidak ditemukan atau sudah dihapus'];
        }
        $lama = $this->db->where('id_cancel_paket', $paket->id_cancel_paket)->where('aktif', 1)->order_by('id_item')->get('tblcancel_paket_item')->result_array();
        $isi_resi = [];
        $lama_per_sku = [];
        foreach ($lama as $l) {
            $lama_per_sku[strtoupper($l['sku'])] = $l;
            if ((int) $l['qty_resi'] > 0) {
                $isi_resi[] = ['sku' => $l['sku'], 'no_rak' => $l['no_rak'], 'qty' => (int) $l['qty_resi']];
            }
        }

        $s = $this->susun_item($isi_resi, $items);
        $catatan = trim((string) $catatan);
        $pesan = null;
        if (isset($s['error'])) {
            $pesan = $s['error'];
        } elseif ($s['selisih'] && $catatan === '') {
            $pesan = 'Ada selisih SKU / qty: isi catatan penyebabnya';
        }

        // Ringkasan perubahan (untuk log); tanpa perubahan = ditolak.
        $perubahan = [];
        if ($pesan === null) {
            if ($dari !== $paket->diterima_dari) {
                $perubahan[] = 'Asal: ' . $paket->diterima_dari . ' -> ' . $dari;
            }
            $baru_per_sku = [];
            foreach ($s['baris'] as $b) {
                $baru_per_sku[strtoupper($b['sku'])] = $b;
                $o = $lama_per_sku[strtoupper($b['sku'])] ?? null;
                if (!$o) {
                    $perubahan[] = '+ ' . $b['sku'] . ': ' . $b['qty_ditemukan'] . ' pcs (di luar resi)';
                } elseif ((int) $o['qty_ditemukan'] !== $b['qty_ditemukan']) {
                    $perubahan[] = $b['sku'] . ': ' . $o['qty_ditemukan'] . ' -> ' . $b['qty_ditemukan'] . ' pcs';
                }
            }
            foreach ($lama as $o) {
                if (!isset($baru_per_sku[strtoupper($o['sku'])])) {
                    $perubahan[] = '- ' . $o['sku'] . ' dihapus dari paket';
                }
            }
            if ($catatan !== (string) $paket->catatan) {
                $perubahan[] = 'Catatan diubah';
            }
            if (!$perubahan) {
                $pesan = 'Tidak ada perubahan untuk disimpan';
            }
        }
        if ($pesan !== null) {
            $this->db->trans_complete();
            $this->db->db_debug = $db_debug_asli;
            return ['ok' => false, 'message' => $pesan];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->where('id_cancel_paket', $paket->id_cancel_paket)->update('tblcancel_paket_item', ['aktif' => 0]);
        foreach ($s['baris'] as $b) {
            $this->db->insert('tblcancel_paket_item', $b + [
                'id_cancel_paket' => $paket->id_cancel_paket,
                'id_resi'         => $paket->id_resi,
                'aktif'           => 1,
                'created_at'      => $now,
            ]);
        }
        $this->db->where('id_cancel_paket', $paket->id_cancel_paket)->update('tblcancel_paket', [
            'diterima_dari' => $dari,
            'catatan'       => $catatan === '' ? null : substr($catatan, 0, 255),
            'diedit_at'     => $now,
            'diedit_oleh'   => $id_user,
            'updated_at'    => $now,
        ]);
        $this->tulis_log($paket->id_cancel_paket, 'EDIT', $id_user, null, ['perubahan' => $perubahan]);
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
        $this->db->db_debug = $db_debug_asli;

        return $ok ? ['ok' => true, 'message' => 'Paket ' . $paket->noresi . ' berhasil diperbarui']
                   : ['ok' => false, 'message' => 'Gagal menyimpan perubahan'];
    }

    /** Hapus = penanda (soft delete). Resi bisa diterima ulang. */
    public function hapus($id_paket, $alasan, $id_user)
    {
        if ($this->mode_arsip()) {
            return ['ok' => false, 'message' => 'Mode Arsip aktif: data tidak bisa diubah'];
        }
        $alasan = trim((string) $alasan);
        if ($alasan === '') {
            return ['ok' => false, 'message' => 'Isi alasan penghapusan'];
        }

        $db_debug_asli = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_start();

        $paket = $this->paket_untuk_ubah($id_paket);
        if (!$paket) {
            $this->db->trans_complete();
            $this->db->db_debug = $db_debug_asli;
            return ['ok' => false, 'message' => 'Paket tidak ditemukan atau sudah dihapus'];
        }
        $now = date('Y-m-d H:i:s');
        $this->db->where('id_cancel_paket', $paket->id_cancel_paket)->update('tblcancel_paket', [
            'status'       => 'DITEMUKAN',
            'dihapus_at'   => $now,
            'dihapus_oleh' => $id_user,
            'alasan_hapus' => substr($alasan, 0, 255),
            'updated_at'   => $now,
        ]);
        $this->tulis_log($paket->id_cancel_paket, 'HAPUS', $id_user, $alasan, ['dari' => $paket->diterima_dari, 'dicek_at' => $paket->dicek_at]);
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
        $this->db->db_debug = $db_debug_asli;

        return $ok ? ['ok' => true, 'message' => 'Paket ' . $paket->noresi . ' dihapus']
                   : ['ok' => false, 'message' => 'Gagal menghapus'];
    }

    // ─────────────────────────────────────────────
    //  Serah terima harian
    // ─────────────────────────────────────────────

    /**
     * Isi kertas serah terima satu tanggal: per SKU, hanya qty yang cocok dengan resi
     * (yang lebih kecil antara qty resi & qty ada) -- selisih tidak dihitung.
     * @return array ['det' => [['sku','rak','qty'], ...], 'jumlah_paket' => int, 'petugas' => [..]]
     */
    public function serah_harian($tanggal)
    {
        $paket = $this->db
            ->select('p.id_cancel_paket, p.dicek_oleh')
            ->from('tblcancel_paket p')
            ->where('p.dicek_at IS NOT NULL', null, false)
            ->where('p.dihapus_at IS NULL', null, false)
            ->where('p.dicek_at >=', $tanggal . ' 00:00:00')
            ->where('p.dicek_at <=', $tanggal . ' 23:59:59')
            ->get()->result_array();
        if (!$paket) {
            return ['det' => [], 'jumlah_paket' => 0, 'petugas' => []];
        }
        $ids = array_column($paket, 'id_cancel_paket');

        $det = $this->db
            ->select('sku, MAX(no_rak) AS rak, SUM(LEAST(qty_ditemukan, qty_resi)) AS qty', false)
            ->from('tblcancel_paket_item')
            ->where_in('id_cancel_paket', $ids)
            ->where('aktif', 1)
            ->group_by('sku')
            ->having('SUM(LEAST(qty_ditemukan, qty_resi)) >', 0, false)
            ->order_by('MAX(no_rak)', 'ASC', false)
            ->order_by('sku', 'ASC')
            ->get()->result_array();
        foreach ($det as &$d) {
            $d['qty'] = (int) $d['qty'];
        }

        // Petugas penerima hari itu (terbanyak dulu): "NAMA - ROLE - NOABSEN" -> "NOABSEN - NAMA - ROLE"
        $hitung = array_count_values(array_column($paket, 'dicek_oleh'));
        arsort($hitung);
        $petugas = [];
        if ($hitung) {
            $nama = array_column(
                $this->db->select('id_user, name')->where_in('id_user', array_keys($hitung))->get('tbluser')->result_array(),
                'name', 'id_user'
            );
            foreach (array_keys($hitung) as $uid) {
                $n = (string) ($nama[$uid] ?? '');
                $p = array_map('trim', explode(' - ', $n));
                $petugas[] = count($p) >= 3 ? end($p) . ' - ' . $p[0] . ' - ' . $p[1] : $n;
            }
        }
        return ['det' => $det, 'jumlah_paket' => count($paket), 'petugas' => array_values(array_filter($petugas))];
    }
}
