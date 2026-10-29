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
            ->select("p.id_cancel_paket AS id, p.id_resi, p.noresi, p.diterima_dari AS dari, p.catatan, p.dicek_at, p.diedit_at,
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
        $disp = [];
        foreach ($this->db->select('id_cancel_paket, sku, SUM(qty) AS q', false)->where_in('id_cancel_paket', $ids)
            ->where('batal_at IS NULL', null, false)->group_by(['id_cancel_paket', 'sku'])->get('tblcancel_display')->result_array() as $d) {
            $disp[$d['id_cancel_paket'] . '|' . strtoupper($d['sku'])] = (int) $d['q'];
        }
        foreach ($items as $it) {
            $key = $it['id_cancel_paket'];
            unset($it['id_cancel_paket']);
            $it['qty_resi'] = (int) $it['qty_resi'];
            $it['qty_ditemukan'] = (int) $it['qty_ditemukan'];
            $it['qty_display'] = $disp[$key . '|' . strtoupper($it['sku'])] ?? 0;
            $per[$key][] = $this->tambah_foto($it);
        }
        foreach ($rows as &$r) {
            $r['id'] = (int) $r['id'];
            $r['tgl'] = substr($r['dicek_at'], 0, 10);
            $r['jam'] = substr($r['dicek_at'], 11, 8);
            $r['diedit'] = $r['diedit_at'] ? substr($r['diedit_at'], 11, 8) : null;
            $r['items'] = $per[$r['id']] ?? [];
            $r['tanpa_resi'] = (int) $r['id_resi'] < 0;
            unset($r['id_resi']);
            $r['selisih'] = false;
            foreach ($r['tanpa_resi'] ? [] : $r['items'] as $it) {
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

        return $ok ? ['ok' => true, 'message' => 'Paket ' . ($paket->noresi !== '' ? $paket->noresi : 'tanpa resi #' . $paket->id_cancel_paket) . ' berhasil diperbarui']
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

        return $ok ? ['ok' => true, 'message' => 'Paket ' . ($paket->noresi !== '' ? $paket->noresi : 'tanpa resi #' . $paket->id_cancel_paket) . ' dihapus']
                   : ['ok' => false, 'message' => 'Gagal menghapus'];
    }

    /**
     * Terima paket tanpa kertas resi (mis. zip / plastik cancel berisi beberapa barang tetapi
     * resinya hilang). Dicatat seperti penerimaan biasa tanpa no. resi: baris tblcancel_paket
     * dengan id_resi NEGATIF (unik, tidak bentrok dengan tblprintresi) dan noresi kosong;
     * semua SKU dihitung sebagai barang ikut serah terima ke restock.
     */
    public function simpan_tanpa_resi($dari, array $items, $catatan, $id_user, $nama_komputer)
    {
        if ($this->mode_arsip()) {
            return ['ok' => false, 'message' => 'Mode Arsip aktif: data tidak bisa disimpan'];
        }
        $dari = strtoupper((string) $dari);
        if (!in_array($dari, self::SUMBER, true)) {
            return ['ok' => false, 'message' => 'Asal paket harus Packer, HO, atau Kurir'];
        }
        $catatan = trim((string) $catatan);
        if ($catatan === '') {
            return ['ok' => false, 'message' => 'Isi catatan untuk paket tanpa resi (mis. ciri zip / keranjang asal)'];
        }
        $s = $this->susun_item([], $items);
        if (isset($s['error'])) {
            return ['ok' => false, 'message' => $s['error']];
        }
        if (!$s['baris']) {
            return ['ok' => false, 'message' => 'Tambahkan minimal 1 SKU dengan qty'];
        }

        $now = date('Y-m-d H:i:s');
        $db_debug_asli = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_start();

        $id_resi = -((int) (microtime(true) * 1000));
        while ($this->db->query('SELECT 1 FROM tblcancel_paket WHERE id_resi = ? FOR UPDATE', [$id_resi])->num_rows()) {
            $id_resi--;
        }
        $this->db->insert('tblcancel_paket', [
            'id_resi'            => $id_resi,
            'noresi'             => '',
            'status'             => 'DICEK',
            'diterima_dari'      => $dari,
            'ditemukan_di'       => $dari,
            'ditemukan_oleh'     => $id_user,
            'ditemukan_komputer' => $nama_komputer,
            'ditemukan_at'       => $now,
            'jumlah_tolak'       => 0,
            'dicek_oleh'         => $id_user,
            'dicek_at'           => $now,
            'catatan'            => substr($catatan, 0, 255),
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);
        $id_paket = (int) $this->db->insert_id();
        foreach ($s['baris'] as $b) {
            $this->db->insert('tblcancel_paket_item', $b + [
                'id_cancel_paket' => $id_paket,
                'id_resi'         => $id_resi,
                'aktif'           => 1,
                'created_at'      => $now,
            ]);
        }
        $this->tulis_log($id_paket, 'TERIMA', $id_user, null, ['dari' => $dari, 'tanpa_resi' => true, 'catatan' => $catatan, 'item' => $s['baris']]);
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
        $this->db->db_debug = $db_debug_asli;

        return $ok ? ['ok' => true, 'id' => $id_paket, 'message' => 'Paket tanpa resi tersimpan (' . count($s['baris']) . ' SKU)']
                   : ['ok' => false, 'message' => 'Gagal menyimpan, coba lagi'];
    }

    // ---------------------------------------------------------------
    //  Barang kembali ke display (tblcancel_display)
    // ---------------------------------------------------------------

    /** Qty yang masih bisa dikembalikan ke display per SKU untuk satu paket (ada - sudah di display). */
    private function sisa_display($id_paket)
    {
        $ada = [];
        $items = $this->db->where('id_cancel_paket', (int) $id_paket)->where('aktif', 1)->get('tblcancel_paket_item')->result_array();
        foreach ($items as $i) {
            $ada[strtoupper($i['sku'])] = ['sku' => $i['sku'], 'sisa' => (int) $i['qty_ditemukan']];
        }
        $sudah = $this->db->select('sku, SUM(qty) AS q', false)->where('id_cancel_paket', (int) $id_paket)->where('batal_at IS NULL', null, false)->group_by('sku')->get('tblcancel_display')->result_array();
        foreach ($sudah as $s) {
            $k = strtoupper($s['sku']);
            if (isset($ada[$k])) {
                $ada[$k]['sisa'] -= (int) $s['q'];
            }
        }
        return $ada;
    }

    private function bersihkan_sumber($sumber)
    {
        $sumber = strtoupper(trim((string) $sumber));
        return in_array($sumber, ['QC', 'RESTOK'], true) ? $sumber : ($sumber === '' ? null : false);
    }

    /** Gabung kiriman klien [['sku','qty'],...] per SKU, buang qty <= 0. NULL bila ada qty tidak wajar. */
    private function gabung_qty(array $items)
    {
        $h = [];
        foreach ($items as $it) {
            $sku = trim((string) ($it['sku'] ?? ''));
            $qty = (int) ($it['qty'] ?? 0);
            if ($sku === '' || $qty <= 0) {
                continue;
            }
            if ($qty > 100000) {
                return null;
            }
            $k = strtoupper($sku);
            $h[$k] = ['sku' => $sku, 'qty' => ($h[$k]['qty'] ?? 0) + $qty];
        }
        return $h;
    }

    /**
     * Catat barang paket diterima yang sudah dikembalikan ke display (per SKU & qty).
     * Qty tidak boleh melebihi qty ada dikurangi yang sudah dikembalikan.
     */
    public function tandai_display($id_paket, array $items, $sumber, $catatan, $id_user)
    {
        if ($this->mode_arsip()) {
            return ['ok' => false, 'message' => 'Mode Arsip aktif: data tidak bisa diubah'];
        }
        $sumber = $this->bersihkan_sumber($sumber);
        if ($sumber === false) {
            return ['ok' => false, 'message' => 'Sumber barang harus QC atau Restok'];
        }
        $pilih = $this->gabung_qty($items);
        if ($pilih === null) {
            return ['ok' => false, 'message' => 'Qty tidak valid'];
        }
        if (!$pilih) {
            return ['ok' => false, 'message' => 'Isi qty barang yang kembali ke display (minimal 1 SKU)'];
        }
        $catatan = trim((string) $catatan);

        $db_debug_asli = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_start();

        $paket = $this->paket_untuk_ubah($id_paket);
        $pesan = null;
        $sisa = [];
        if (!$paket) {
            $pesan = 'Paket tidak ditemukan atau sudah dihapus';
        } else {
            $sisa = $this->sisa_display($paket->id_cancel_paket);
            foreach ($pilih as $k => $p) {
                if (!isset($sisa[$k]) || $sisa[$k]['sisa'] <= 0) {
                    $pesan = 'SKU ' . $p['sku'] . ' tidak ada lagi di paket ini untuk dikembalikan';
                    break;
                }
                if ($p['qty'] > $sisa[$k]['sisa']) {
                    $pesan = 'Qty ' . $p['sku'] . ' melebihi sisa di paket (' . $sisa[$k]['sisa'] . ' pcs)';
                    break;
                }
            }
        }
        if ($pesan !== null) {
            $this->db->trans_complete();
            $this->db->db_debug = $db_debug_asli;
            return ['ok' => false, 'message' => $pesan];
        }

        $now = date('Y-m-d H:i:s');
        $ringkas = [];
        foreach ($pilih as $k => $p) {
            $this->db->insert('tblcancel_display', [
                'id_cancel_paket' => $paket->id_cancel_paket,
                'noresi'          => $paket->noresi,
                'sku'             => $sisa[$k]['sku'],
                'qty'             => $p['qty'],
                'sumber'          => $sumber,
                'catatan'         => $catatan === '' ? null : substr($catatan, 0, 255),
                'dibuat_oleh'     => $id_user,
                'dibuat_at'       => $now,
            ]);
            $ringkas[] = $sisa[$k]['sku'] . ' x' . $p['qty'];
        }
        $this->tulis_log($paket->id_cancel_paket, 'DISPLAY', $id_user, $catatan, ['item' => $ringkas, 'sumber' => $sumber]);
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
        $this->db->db_debug = $db_debug_asli;

        return $ok ? ['ok' => true, 'message' => 'Tercatat kembali ke display: ' . implode(', ', $ringkas)]
                   : ['ok' => false, 'message' => 'Gagal menyimpan catatan'];
    }

    /**
     * Catat barang kembali ke display yang resinya tidak ada / belum diterima
     * (mis. barang dari keranjang cancel tanpa resi). No. resi opsional.
     */
    public function catat_display_bebas($noresi, array $items, $sumber, $catatan, $id_user)
    {
        if ($this->mode_arsip()) {
            return ['ok' => false, 'message' => 'Mode Arsip aktif: data tidak bisa disimpan'];
        }
        $sumber = $this->bersihkan_sumber($sumber);
        if ($sumber === false) {
            return ['ok' => false, 'message' => 'Sumber barang harus QC atau Restok'];
        }
        $pilih = $this->gabung_qty($items);
        if ($pilih === null) {
            return ['ok' => false, 'message' => 'Qty tidak valid'];
        }
        if (!$pilih) {
            return ['ok' => false, 'message' => 'Tambahkan minimal 1 SKU dengan qty'];
        }
        $noresi = trim((string) $noresi);
        $catatan = trim((string) $catatan);
        if ($noresi === '' && $catatan === '') {
            return ['ok' => false, 'message' => 'Isi no. resi, atau catatan bila resinya tidak ada'];
        }
        if ($noresi !== '') {
            $resi = $this->resi_by_noresi($noresi);
            if ($resi && $this->paket_aktif($resi->id_printresi)) {
                return ['ok' => false, 'message' => 'Resi ini sudah diterima tim retur: catat lewat tombol display di barisnya'];
            }
        }
        $baris = [];
        foreach ($pilih as $p) {
            $sku = $this->db->select('id_sku')->get_where('tblsku', ['id_sku' => $p['sku']])->row();
            if (!$sku) {
                return ['ok' => false, 'message' => 'SKU "' . $p['sku'] . '" tidak dikenal di master SKU'];
            }
            $baris[] = ['sku' => $sku->id_sku, 'qty' => $p['qty']];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        foreach ($baris as $b) {
            $this->db->insert('tblcancel_display', [
                'id_cancel_paket' => null,
                'noresi'          => $noresi === '' ? null : substr($noresi, 0, 100),
                'sku'             => $b['sku'],
                'qty'             => $b['qty'],
                'sumber'          => $sumber,
                'catatan'         => $catatan === '' ? null : substr($catatan, 0, 255),
                'dibuat_oleh'     => $id_user,
                'dibuat_at'       => $now,
            ]);
        }
        $this->db->trans_complete();

        return $this->db->trans_status() ? ['ok' => true, 'message' => 'Tercatat kembali ke display (' . count($baris) . ' SKU)']
                                         : ['ok' => false, 'message' => 'Gagal menyimpan catatan'];
    }

    /** Batalkan satu catatan display (salah catat). Barang paket kembali dihitung di serah terima. */
    public function batal_display($id_display, $id_user)
    {
        if ($this->mode_arsip()) {
            return ['ok' => false, 'message' => 'Mode Arsip aktif: data tidak bisa diubah'];
        }
        $this->db->where('id_display', (int) $id_display)->where('batal_at IS NULL', null, false)->update('tblcancel_display', [
            'batal_at'   => date('Y-m-d H:i:s'),
            'batal_oleh' => $id_user,
        ]);
        return $this->db->affected_rows() > 0 ? ['ok' => true, 'message' => 'Catatan display dibatalkan']
                                              : ['ok' => false, 'message' => 'Catatan tidak ditemukan atau sudah dibatalkan'];
    }

    /** Catatan display aktif pada rentang tanggal (dibuat), dengan nama petugas. */
    public function display_baris($start, $end)
    {
        $rows = $this->db
            ->select('d.id_display AS id, d.id_cancel_paket AS id_paket, d.noresi, d.sku, d.qty, d.sumber, d.catatan, d.dibuat_at, u.name AS petugas', false)
            ->from('tblcancel_display d')
            ->join('tbluser u', 'u.id_user = d.dibuat_oleh', 'left')
            ->where('d.batal_at IS NULL', null, false)
            ->where('d.dibuat_at >=', $start . ' 00:00:00')
            ->where('d.dibuat_at <=', $end . ' 23:59:59')
            ->order_by('d.dibuat_at', 'DESC')
            ->order_by('d.id_display', 'DESC')
            ->get()->result_array();
        foreach ($rows as &$r) {
            $r['id'] = (int) $r['id'];
            $r['id_paket'] = $r['id_paket'] === null ? null : (int) $r['id_paket'];
            $r['qty'] = (int) $r['qty'];
            $r['tgl'] = substr($r['dibuat_at'], 0, 10);
            $r['jam'] = substr($r['dibuat_at'], 11, 8);
            unset($r['dibuat_at']);
        }
        return $rows;
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
            ->select('p.id_cancel_paket, p.id_resi, p.dicek_oleh')
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
        $tanpa_resi = [];
        foreach ($paket as $pk) {
            if ((int) $pk['id_resi'] < 0) {
                $tanpa_resi[(int) $pk['id_cancel_paket']] = true;
            }
        }

        // Per paket & SKU: yang sesuai resi dikurangi barang yang sudah kembali ke display.
        $tampung = [];
        $items = $this->db
            ->select('id_cancel_paket, sku, no_rak, qty_resi, qty_ditemukan')
            ->where_in('id_cancel_paket', $ids)
            ->where('aktif', 1)
            ->get('tblcancel_paket_item')->result_array();
        $disp = [];
        $sudah = $this->db
            ->select('id_cancel_paket, sku, SUM(qty) AS q', false)
            ->where_in('id_cancel_paket', $ids)
            ->where('batal_at IS NULL', null, false)
            ->group_by(['id_cancel_paket', 'sku'])
            ->get('tblcancel_display')->result_array();
        foreach ($sudah as $d) {
            $disp[$d['id_cancel_paket'] . '|' . strtoupper($d['sku'])] = (int) $d['q'];
        }
        foreach ($items as $i) {
            $ada = (int) $i['qty_ditemukan'] - ($disp[$i['id_cancel_paket'] . '|' . strtoupper($i['sku'])] ?? 0);
            // paket tanpa resi: semua barang yang ada ikut serah terima (tidak ada qty resi pembanding)
            $qty = isset($tanpa_resi[(int) $i['id_cancel_paket']]) ? max(0, $ada) : max(0, min($ada, (int) $i['qty_resi']));
            if ($qty <= 0) {
                continue;
            }
            $k = strtoupper($i['sku']);
            if (!isset($tampung[$k])) {
                $tampung[$k] = ['sku' => $i['sku'], 'rak' => (string) $i['no_rak'], 'qty' => 0];
            }
            $tampung[$k]['qty'] += $qty;
            if ((string) $i['no_rak'] > $tampung[$k]['rak']) {
                $tampung[$k]['rak'] = (string) $i['no_rak'];
            }
        }
        $det = array_values($tampung);
        usort($det, function ($x, $y) {
            return strcmp($x['rak'], $y['rak']) ?: strcmp($x['sku'], $y['sku']);
        });

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
