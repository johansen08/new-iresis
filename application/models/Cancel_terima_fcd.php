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

    /** Kode alasan selisih per jenis (kode => teks tampil). Teks yang sama dipakai klien (terima.php, laporan.php). */
    const ALASAN = [
        'KURANG'       => ['PICKER_KURANG' => 'Picker kurang ambil', 'RUSAK_HILANG' => 'Rusak / hilang di jalan', 'DIPAKAI' => 'Dipakai (display)', 'LAINNYA' => 'Lainnya'],
        'LEBIH'        => ['PICKER_LEBIH' => 'Picker lebih ambil', 'PACKER_TAMBAH' => 'Packer menambah barang', 'LAINNYA' => 'Lainnya'],
        'SALAH_AMBIL'  => ['PICKER_SALAH' => 'Picker salah ambil', 'SALAH_LABEL' => 'Salah label / salah rak', 'LAINNYA' => 'Lainnya'],
        'DI_LUAR_RESI' => ['PAKET_GABUNG' => 'Paket gabungan', 'TITIPAN' => 'Barang titipan', 'LAINNYA' => 'Lainnya'],
    ];

    /**
     * Kelompokkan selisih satu paket menjadi kejadian: KURANG, LEBIH, SALAH_AMBIL (SKU resi yang kurang
     * dipasangkan dengan SKU yang muncul di luar resi, atau yang lebih di resi yang sama), DI_LUAR_RESI.
     * Aturan sama dengan prototype usulan; klien memakai algoritma yang sama (terima.php).
     * @param array $baris [['sku','qty_resi','qty_ditemukan'], ...]
     * @return array ['grup' => [['jenis','sku','pasangan','qty','key'], ...], 'total' => ['KURANG','LEBIH','SALAH_AMBIL','DI_LUAR_RESI']]
     */
    public static function klasifikasi(array $baris, array $salah = null)
    {
        if ($salah !== null) {
            return self::klasifikasi_eksplisit($baris, $salah);
        }
        $kur = $pool = $leb = $luar = [];
        foreach ($baris as $b) {
            $r = (int) $b['qty_resi'];
            $a = (int) $b['qty_ditemukan'];
            if ($r > 0) {
                if ($a < $r) {
                    $kur[] = ['sku' => $b['sku'], 'q' => $r - $a];
                } elseif ($a > $r) {
                    $leb[] = ['sku' => $b['sku'], 'q' => $a - $r, 'jenis' => 'LEBIH'];
                }
            } elseif ($a > 0) {
                $luar[] = ['sku' => $b['sku'], 'q' => $a, 'jenis' => 'DI_LUAR_RESI'];
            }
        }
        $pool = array_merge($luar, $leb);
        $grup = [];
        foreach ($kur as $k) {
            foreach ($pool as &$o) {
                if ($k['q'] <= 0) {
                    break;
                }
                if ($o['q'] <= 0) {
                    continue;
                }
                $t = min($k['q'], $o['q']);
                $grup[] = ['jenis' => 'SALAH_AMBIL', 'sku' => $k['sku'], 'pasangan' => $o['sku'], 'qty' => $t];
                $k['q'] -= $t;
                $o['q'] -= $t;
            }
            unset($o);
            if ($k['q'] > 0) {
                $grup[] = ['jenis' => 'KURANG', 'sku' => $k['sku'], 'pasangan' => null, 'qty' => $k['q']];
            }
        }
        foreach ($pool as $o) {
            if ($o['q'] > 0) {
                $grup[] = ['jenis' => $o['jenis'], 'sku' => $o['sku'], 'pasangan' => null, 'qty' => $o['q']];
            }
        }
        $total = ['KURANG' => 0, 'LEBIH' => 0, 'SALAH_AMBIL' => 0, 'DI_LUAR_RESI' => 0];
        foreach ($grup as &$g) {
            $g['key'] = $g['jenis'] . '|' . strtoupper($g['sku']) . '|' . strtoupper((string) $g['pasangan']);
            $total[$g['jenis']] += $g['qty'];
        }
        unset($g);
        return ['grup' => $grup, 'total' => $total];
    }

    /**
     * Kejadian selisih menurut pilihan petugas (Kurang / Salah), bukan tebakan. Salah ambil hanya terjadi bila
     * petugas mengisinya: SKU resi yang kurang diganti SKU di luar resi. Selisih yang tidak dialokasikan ke
     * salah ambil = KURANG. Barang yang ada lebih dari resi = LEBIH, SKU di luar resi yang tidak dipakai
     * salah ambil = DI_LUAR_RESI.
     * @param array $salah [['sku' => SKU resi, 'pasangan' => SKU yang terambil, 'qty' => n], ...]
     * @return array sama dengan klasifikasi(), atau ['error' => pesan]
     */
    public static function klasifikasi_eksplisit(array $baris, array $salah)
    {
        $info = [];
        foreach ($baris as $b) {
            $info[strtoupper($b['sku'])] = ['sku' => $b['sku'], 'r' => (int) $b['qty_resi'], 'a' => (int) $b['qty_ditemukan']];
        }
        $kurang = $pool = [];
        foreach ($info as $k => $i) {
            $kurang[$k] = $i['r'] > 0 ? max(0, $i['r'] - $i['a']) : 0;
            $pool[$k] = $i['r'] > 0 ? max(0, $i['a'] - $i['r']) : $i['a'];
        }
        $alok = [];
        $pakai_salah = $pakai_pool = [];
        foreach ($salah as $x) {
            $sku = strtoupper(trim((string) ($x['sku'] ?? '')));
            $pas = strtoupper(trim((string) ($x['pasangan'] ?? '')));
            $q = (int) ($x['qty'] ?? 0);
            if ($q < 1) {
                return ['error' => 'Qty salah ambil minimal 1'];
            }
            if (!isset($info[$sku]) || $info[$sku]['r'] <= 0) {
                return ['error' => 'Salah ambil: SKU ' . $sku . ' bukan SKU di resi'];
            }
            if (!isset($info[$pas]) || $info[$pas]['r'] > 0 || $pas === $sku) {
                return ['error' => 'Salah ambil: SKU yang terambil (' . $pas . ') harus SKU di luar resi'];
            }
            $pakai_salah[$sku] = ($pakai_salah[$sku] ?? 0) + $q;
            $pakai_pool[$pas] = ($pakai_pool[$pas] ?? 0) + $q;
            $alok[$sku . '|' . $pas] = ($alok[$sku . '|' . $pas] ?? 0) + $q;
        }
        foreach ($pakai_salah as $k => $q) {
            if ($q > $kurang[$k]) {
                return ['error' => 'Salah ambil ' . $info[$k]['sku'] . ' (' . $q . ') melebihi selisihnya (' . $kurang[$k] . ')'];
            }
        }
        foreach ($pakai_pool as $k => $q) {
            if ($q > $pool[$k]) {
                return ['error' => 'Qty ' . $info[$k]['sku'] . ' yang terambil (' . $q . ') melebihi barang yang ada (' . $pool[$k] . ')'];
            }
        }
        $grup = [];
        foreach ($info as $k => $i) {
            if ($i['r'] <= 0) {
                continue;
            }
            foreach ($alok as $kk => $q) {
                list($a, $pas) = explode('|', $kk, 2);
                if ($a === $k) {
                    $grup[] = ['jenis' => 'SALAH_AMBIL', 'sku' => $i['sku'], 'pasangan' => $info[$pas]['sku'], 'qty' => $q];
                }
            }
            $sisa = $kurang[$k] - ($pakai_salah[$k] ?? 0);
            if ($sisa > 0) {
                $grup[] = ['jenis' => 'KURANG', 'sku' => $i['sku'], 'pasangan' => null, 'qty' => $sisa];
            }
        }
        foreach ([0, 1] as $resi_ada) { // di luar resi dulu, lalu lebih di resi (urutan sama dengan klasifikasi lama)
            foreach ($info as $k => $i) {
                if (($i['r'] > 0) !== (bool) $resi_ada) {
                    continue;
                }
                $sisa = $pool[$k] - ($pakai_pool[$k] ?? 0);
                if ($sisa > 0) {
                    $grup[] = ['jenis' => $resi_ada ? 'LEBIH' : 'DI_LUAR_RESI', 'sku' => $i['sku'], 'pasangan' => null, 'qty' => $sisa];
                }
            }
        }
        $total = ['KURANG' => 0, 'LEBIH' => 0, 'SALAH_AMBIL' => 0, 'DI_LUAR_RESI' => 0];
        foreach ($grup as &$g) {
            $g['key'] = $g['jenis'] . '|' . strtoupper($g['sku']) . '|' . strtoupper((string) $g['pasangan']);
            $total[$g['jenis']] += $g['qty'];
        }
        unset($g);
        return ['grup' => $grup, 'total' => $total];
    }

    /**
     * Beri tiap baris item jenis / kode_alasan / pasangan_sku dan hitung total per jenis.
     * @param array $alasan key kejadian (jenis|SKU|PASANGAN) => kode alasan
     * @param bool  $isi_lainnya kejadian tanpa alasan diisi LAINNYA (dipakai bila ada catatan)
     * @return array ['baris' => [...], 'total' => [...], 'kosong' => jumlah kejadian tanpa alasan]
     */
    private function labeli(array $baris, array $alasan, $isi_lainnya, array $salah = null)
    {
        $k = self::klasifikasi($baris, $salah);
        if (isset($k['error'])) {
            return ['error' => $k['error']];
        }
        $kode = [];
        $kosong = 0;
        foreach ($k['grup'] as $g) {
            if ($salah !== null) { // pilihan Kurang / Salah dari petugas: tanpa kode alasan
                $kode[$g['key']] = null;
                continue;
            }
            $v = isset($alasan[$g['key']]) ? (string) $alasan[$g['key']] : '';
            if (!isset(self::ALASAN[$g['jenis']][$v])) {
                $v = $isi_lainnya ? 'LAINNYA' : null;
                if ($v === null) {
                    $kosong++;
                }
            }
            $kode[$g['key']] = $v;
        }
        foreach ($baris as &$b) {
            $b['jenis'] = 'SESUAI';
            $b['kode_alasan'] = null;
            $b['pasangan_sku'] = null;
            foreach ($k['grup'] as $g) {
                $sama = strcasecmp($g['sku'], $b['sku']) === 0;
                if ($sama || ($g['pasangan'] !== null && strcasecmp($g['pasangan'], $b['sku']) === 0)) {
                    $b['jenis'] = $g['jenis'];
                    $b['kode_alasan'] = $kode[$g['key']];
                    $b['pasangan_sku'] = $g['pasangan'] === null ? null : ($sama ? $g['pasangan'] : $g['sku']);
                    break;
                }
            }
        }
        unset($b);
        return ['baris' => $baris, 'total' => $k['total'], 'kosong' => $kosong, 'grup' => $k['grup']];
    }

    private function kolom_total(array $total)
    {
        return [
            'total_kurang'      => $total['KURANG'],
            'total_lebih'       => $total['LEBIH'],
            'total_salah_ambil' => $total['SALAH_AMBIL'],
            'total_luar_resi'   => $total['DI_LUAR_RESI'],
        ];
    }

    /** tbltypemasalah 6 = SELISIH PAKET CANCEL: tercatat atas nama picker, tanpa poin bonus/KPI, tanpa cetak slip, tidak ke Restock. */
    const TIPE_SELISIH_PAKET_CANCEL = 6;

    /**
     * Baris tblmasalahpicker dari selisih satu paket, per SKU resi:
     * qty_bermasalah = |qty resi - qty ada|. SKU di luar resi dipasangkan ke SKU resi yang
     * kurang (kolom sku_salah); yang tidak terpasang jadi baris sendiri (qty 0).
     * @return array key SKU (upper) => [sku, qty, qty_bermasalah, sku_salah, kurang]
     */
    private function susun_masalah_picker(array $baris, array $grup = null)
    {
        $rows = [];
        $luar = [];
        if ($grup !== null) {
            // Pilihan petugas (kejadian eksplisit): KURANG / SALAH jadi qty bermasalah SKU resi, SALAH memuat SKU yang terambil.
            $resi_qty = [];
            foreach ($baris as $b) {
                $resi_qty[strtoupper($b['sku'])] = (int) $b['qty_resi'];
            }
            foreach ($grup as $g) {
                $k = strtoupper($g['sku']);
                if ($g['jenis'] === 'DI_LUAR_RESI') {
                    $rows[$k] = ['sku' => $g['sku'], 'qty' => 0, 'qty_bermasalah' => (int) $g['qty'], 'sku_salah' => null, 'kurang' => false];
                    continue;
                }
                if (!isset($rows[$k])) {
                    $rows[$k] = ['sku' => $g['sku'], 'qty' => $resi_qty[$k] ?? 0, 'qty_bermasalah' => 0, 'sku_salah' => null, 'kurang' => $g['jenis'] !== 'LEBIH'];
                }
                $rows[$k]['qty_bermasalah'] += (int) $g['qty'];
                if ($g['jenis'] === 'SALAH_AMBIL' && $rows[$k]['sku_salah'] === null) {
                    $rows[$k]['sku_salah'] = $g['pasangan'];
                }
            }
            return $rows;
        }
        foreach ($baris as $b) {
            $resi = (int) $b['qty_resi'];
            $ada  = (int) $b['qty_ditemukan'];
            if ($resi > 0) {
                if ($ada !== $resi) {
                    $rows[strtoupper($b['sku'])] = [
                        'sku' => $b['sku'], 'qty' => $resi, 'qty_bermasalah' => abs($resi - $ada),
                        'sku_salah' => null, 'kurang' => $ada < $resi,
                    ];
                }
            } elseif ($ada > 0) {
                $luar[] = ['sku' => $b['sku'], 'qty' => $ada];
            }
        }
        foreach ($luar as $l) {
            $terpasang = false;
            foreach ($rows as &$r) {
                if ($r['kurang'] && $r['sku_salah'] === null) {
                    $r['sku_salah'] = $l['sku'];
                    $terpasang = true;
                    break;
                }
            }
            unset($r);
            if (!$terpasang) {
                $rows[strtoupper($l['sku'])] = [
                    'sku' => $l['sku'], 'qty' => 0, 'qty_bermasalah' => $l['qty'],
                    'sku_salah' => null, 'kurang' => false,
                ];
            }
        }
        return $rows;
    }

    /**
     * Samakan baris tblmasalahpicker tipe 6 resi ini dengan isi paket (dipanggil di dalam transaksi
     * terima / edit / hapus). Picker diambil dari tblresiambilbarang (tidak disimpan di tabel itu).
     * Tanpa DELETE: SKU yang selisihnya hilang setelah koreksi dinolkan (qty_bermasalah = 0) dan
     * baris itu disembunyikan dari Daftar Masalah Picker. Resi yang sudah dilaporkan packer
     * (tipe lain, SKU sama) tidak ditimpa/digandakan.
     * @return int|string jumlah SKU bermasalah yang tercatat, atau 'tanpa_picker' / 'lewati'
     */
    private function sinkron_masalah_picker($id_resi, $noresi, array $baris, $id_user, array $grup = null)
    {
        if ((int) $id_resi <= 0) {
            return 'lewati';
        }
        if (!$this->db->select('id_resiambilbarang')->where('id_resi', (int) $id_resi)->limit(1)->get('tblresiambilbarang')->row()) {
            return 'tanpa_picker';
        }
        $baru = $this->susun_masalah_picker($baris, $grup);
        $lama = [];
        $ada_rows = $this->db->where('id_printresi', (int) $id_resi)->get('tblmasalahpicker')->result_array();
        $lain = [];
        foreach ($ada_rows as $m) {
            if ((int) $m['id_typemasalah'] === self::TIPE_SELISIH_PAKET_CANCEL) {
                $lama[strtoupper($m['sku'])] = $m;
            } else {
                $lain[strtoupper($m['sku'])] = true;
            }
        }
        $now = date('Y-m-d H:i:s');
        $tercatat = 0;
        foreach ($baru as $k => $r) {
            if (isset($lama[$k])) {
                $this->db->where('id_masalahpicker', $lama[$k]['id_masalahpicker'])->update('tblmasalahpicker', [
                    'qty' => $r['qty'], 'qty_bermasalah' => $r['qty_bermasalah'], 'sku_salah' => $r['sku_salah'],
                    'updated_by' => $id_user, 'updated' => $now,
                ]);
                $tercatat++;
            } elseif (!isset($lain[$k])) {
                $this->db->insert('tblmasalahpicker', [
                    'id_printresi' => (int) $id_resi, 'noresi' => $noresi, 'sku' => $r['sku'],
                    'qty' => $r['qty'], 'id_typemasalah' => self::TIPE_SELISIH_PAKET_CANCEL,
                    'qty_bermasalah' => $r['qty_bermasalah'], 'sku_salah' => $r['sku_salah'],
                    'status' => 0, 'created_by' => $id_user, 'created' => $now,
                ]);
                $tercatat++;
            }
        }
        foreach ($lama as $k => $m) {
            if (!isset($baru[$k]) && (int) $m['qty_bermasalah'] > 0) {
                $this->db->where('id_masalahpicker', $m['id_masalahpicker'])->update('tblmasalahpicker', [
                    'qty_bermasalah' => 0, 'sku_salah' => null, 'updated_by' => $id_user, 'updated' => $now,
                ]);
            }
        }
        return $tercatat;
    }

    private function catatan_picker($hasil)
    {
        if ($hasil === 'tanpa_picker') {
            return '; picker resi tidak terdeteksi, selisih TIDAK dicatat ke picker';
        }
        return is_int($hasil) && $hasil > 0 ? '; ' . $hasil . ' SKU selisih dicatat ke picker' : '';
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

    /** Kejadian selisih paket: baris lama dinonaktifkan (tanpa DELETE), kejadian baru ditulis. */
    private function tulis_selisih($id_paket, $id_resi, array $grup, $now)
    {
        $this->db->where('id_cancel_paket', (int) $id_paket)->update('tblcancel_paket_selisih', ['aktif' => 0]);
        foreach ($grup as $g) {
            $this->db->insert('tblcancel_paket_selisih', [
                'id_cancel_paket' => (int) $id_paket,
                'id_resi'         => $id_resi,
                'jenis'           => $g['jenis'],
                'sku'             => $g['sku'],
                'pasangan_sku'    => $g['pasangan'],
                'qty'             => (int) $g['qty'],
                'aktif'           => 1,
                'created_at'      => $now,
            ]);
        }
    }

    /**
     * Simpan penerimaan satu paket cancel.
     * @param array $items [['sku' => ..., 'qty' => ...], ...] qty yang ADA secara fisik (isi resi + barang di luar resi)
     * @return array ['ok' => bool, 'message' => string, 'selisih' => bool]
     */
    public function simpan($noresi, $dari, array $items, $catatan, $id_user, $nama_komputer, array $alasan = [], array $salah = null)
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
        // Tiap kejadian selisih wajib punya alasan; klien lama yang hanya mengirim catatan diterima (alasan = LAINNYA).
        $lb = $this->labeli($s['baris'], $alasan, $catatan !== '', $salah);
        if (isset($lb['error'])) {
            return ['ok' => false, 'message' => $lb['error']];
        }
        if ($lb['kosong'] > 0) {
            return ['ok' => false, 'message' => 'Pilih alasan untuk ' . $lb['kosong'] . ' kejadian selisih'];
        }
        $s['baris'] = $lb['baris'];

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
        ] + $this->kolom_total($lb['total']);
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
        $this->tulis_selisih($id_paket, $cek['id_resi'], $lb['grup'], $now);
        $this->tulis_log($id_paket, 'TERIMA', $id_user, null, [
            'dari' => $dari, 'selisih' => $s['selisih'], 'catatan' => $catatan, 'item' => $s['baris'], 'kejadian' => $lb['grup'],
        ]);
        $hasil_picker = $this->sinkron_masalah_picker($cek['id_resi'], $cek['noresi'], $s['baris'], $id_user, $salah !== null ? $lb['grup'] : null);
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
        $this->db->db_debug = $db_debug_asli;

        return $ok
            ? ['ok' => true, 'selisih' => $s['selisih'], 'message' => ($s['selisih'] ? 'Tersimpan, ADA SELISIH tercatat' : 'Tersimpan, isi sesuai resi') . $this->catatan_picker($hasil_picker)]
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
                p.serah_cetak_at, p.selesai_at, u.name AS petugas, us.name AS petugas_selesai, r.toko", false)
            ->from('tblcancel_paket p')
            ->join('tbluser u', 'u.id_user = p.dicek_oleh', 'left')
            ->join('tbluser us', 'us.id_user = p.selesai_oleh', 'left')
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
            ->select('i.id_cancel_paket, i.sku, i.no_rak, i.qty_resi, i.qty_ditemukan, i.jenis, i.kode_alasan, i.pasangan_sku, s.link_foto, s.foto_lokal')
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
        $kej = [];
        foreach ($this->db->select('id_cancel_paket, jenis, sku, pasangan_sku, qty')->where_in('id_cancel_paket', $ids)->where('aktif', 1)
            ->order_by('id_selisih', 'ASC')->get('tblcancel_paket_selisih')->result_array() as $k) {
            $kej[$k['id_cancel_paket']][] = ['jenis' => $k['jenis'], 'sku' => $k['sku'], 'pasangan' => $k['pasangan_sku'], 'qty' => (int) $k['qty']];
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
            $r['dicetak'] = $r['serah_cetak_at'] ? substr($r['serah_cetak_at'], 0, 16) : null;
            $r['selesai'] = $r['selesai_at'] ? substr($r['selesai_at'], 0, 16) : null;
            unset($r['serah_cetak_at'], $r['selesai_at']);
            $r['items'] = $per[$r['id']] ?? [];
            $r['tanpa_resi'] = (int) $r['id_resi'] < 0;
            unset($r['id_resi']);
            $r['selisih'] = false;
            $r['grup'] = [];
            $r['jenis_total'] = ['KURANG' => 0, 'LEBIH' => 0, 'SALAH_AMBIL' => 0, 'DI_LUAR_RESI' => 0];
            if (!$r['tanpa_resi']) {
                if (isset($kej[$r['id']])) {
                    // Kejadian tersimpan (pilihan petugas Kurang / Salah).
                    $r['grup'] = $kej[$r['id']];
                    foreach ($r['grup'] as $g) {
                        $r['jenis_total'][$g['jenis']] += $g['qty'];
                    }
                    $r['selisih'] = count($r['grup']) > 0;
                } else {
                    // Paket lama (belum ada kejadian tersimpan): dihitung dari qty dengan aturan lama.
                    $kl = self::klasifikasi($r['items']);
                    $r['jenis_total'] = $kl['total'];
                    $r['selisih'] = count($kl['grup']) > 0;
                    foreach ($kl['grup'] as $g) {
                        $r['grup'][] = ['jenis' => $g['jenis'], 'sku' => $g['sku'], 'pasangan' => $g['pasangan'], 'qty' => $g['qty']];
                    }
                }
                // Paket lama: jenis per SKU belum tersimpan -> isi dari qty (kode alasan tetap kosong, tidak ditebak).
                $legacy = false;
                foreach ($r['items'] as $it) {
                    if ($it['jenis'] === null) {
                        $legacy = true;
                        break;
                    }
                }
                if ($legacy && $r['items']) {
                    $r['items'] = $this->labeli($r['items'], [], false)['baris'];
                }
            }
            unset($r['dicek_at'], $r['diedit_at']);
        }
        return $rows;
    }

    /**
     * Status pengembalian tahap 1: paket yang diterima pada $tanggal ditandai "serah terima
     * dicetak" (hanya yang belum bertanda, jadi cetak ulang tidak menimpa waktu pertama).
     */
    public function tandai_serah_dicetak($tanggal, $id_user, array $ids_pilih = null)
    {
        if ($this->mode_arsip()) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->where('dicek_at IS NOT NULL', null, false)
            ->where('dihapus_at IS NULL', null, false)
            ->where('serah_cetak_at IS NULL', null, false);
        if ($ids_pilih !== null) {
            $this->db->where_in('id_cancel_paket', $ids_pilih ?: [0]);
        } else {
            $this->db->where('dicek_at >=', $tanggal . ' 00:00:00')
                ->where('dicek_at <=', $tanggal . ' 23:59:59');
        }
        $this->db->update('tblcancel_paket', ['serah_cetak_at' => $now, 'serah_cetak_oleh' => $id_user]);
        return $this->db->affected_rows();
    }

    /**
     * Tandai selesai / batal selesai untuk banyak paket sekaligus (yang dicentang di laporan).
     * Paket yang belum dicetak dilewati dan dihitung di 'dilewati'.
     */
    public function tandai_selesai_banyak(array $ids, $selesai, $id_user)
    {
        $ok = 0; $lewat = 0; $gagal = 0;
        foreach (array_unique(array_filter(array_map('intval', $ids))) as $id) {
            $h = $this->tandai_selesai($id, $selesai, $id_user);
            if ($h['ok']) { $ok++; }
            elseif (strpos($h['message'], 'Cetak serah terima dulu') === 0) { $lewat++; }
            else { $gagal++; }
        }
        $pesan = $ok . ' paket ' . ($selesai ? 'ditandai selesai' : 'dibatalkan tanda selesainya')
            . ($lewat ? ', ' . $lewat . ' dilewati karena belum dicetak serah terimanya' : '')
            . ($gagal ? ', ' . $gagal . ' gagal' : '');
        return ['ok' => $ok > 0, 'message' => $ok > 0 ? $pesan : ($lewat ? 'Belum ada yang bisa ditandai: ' . $lewat . ' paket belum dicetak serah terimanya' : 'Tidak ada paket yang diubah'),
                'jumlah' => $ok, 'dilewati' => $lewat, 'gagal' => $gagal];
    }

    /**
     * Status pengembalian tahap 2: centang selesai (atau batalkan centang). Hanya boleh
     * setelah serah terima dicetak.
     */
    public function tandai_selesai($id_paket, $selesai, $id_user)
    {
        if ($this->mode_arsip()) {
            return ['ok' => false, 'message' => 'Mode Arsip aktif: data tidak bisa diubah'];
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
        if ($selesai && $paket->serah_cetak_at === null) {
            $this->db->trans_complete();
            $this->db->db_debug = $db_debug_asli;
            return ['ok' => false, 'message' => 'Cetak serah terima dulu, baru paket bisa ditandai selesai'];
        }
        $now = date('Y-m-d H:i:s');
        $this->db->where('id_cancel_paket', $paket->id_cancel_paket)->update('tblcancel_paket', [
            'selesai_at'   => $selesai ? $now : null,
            'selesai_oleh' => $selesai ? $id_user : null,
            'updated_at'   => $now,
        ]);
        $this->tulis_log($paket->id_cancel_paket, $selesai ? 'SELESAI' : 'BATAL_SELESAI', $id_user, null, null);
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
        $this->db->db_debug = $db_debug_asli;

        return $ok ? ['ok' => true, 'message' => $selesai ? 'Paket ditandai selesai' : 'Tanda selesai dibatalkan', 'selesai' => $selesai ? substr($now, 0, 16) : null]
                   : ['ok' => false, 'message' => 'Gagal menyimpan status'];
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
    public function edit($id_paket, $dari, array $items, $catatan, $id_user, array $salah = null)
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
        $lb = ['total' => ['KURANG' => 0, 'LEBIH' => 0, 'SALAH_AMBIL' => 0, 'DI_LUAR_RESI' => 0], 'grup' => []];
        if (!isset($s['error'])) {
            // Alasan lama dibawa ke kejadian yang sama (jenis|SKU|pasangan); kejadian baru dibiarkan tanpa alasan.
            $alasan_lama = [];
            foreach ($lama as $l) {
                if (!empty($l['kode_alasan']) && !empty($l['jenis']) && $l['jenis'] !== 'SESUAI' && ((int) $l['qty_resi'] > 0 || $l['jenis'] === 'DI_LUAR_RESI')) {
                    $alasan_lama[$l['jenis'] . '|' . strtoupper($l['sku']) . '|' . strtoupper((string) $l['pasangan_sku'])] = $l['kode_alasan'];
                }
            }
            $lb = $this->labeli($s['baris'], $alasan_lama, false, $salah);
            if (isset($lb['error'])) {
                $s['error'] = $lb['error'];
            } else {
                $s['baris'] = $lb['baris'];
            }
        }
        if (isset($s['error'])) {
            $pesan = $s['error'];
        } elseif ($catatan === '' && (((int) $paket->id_resi < 0) || ($s['selisih'] && $salah === null))) {
            $pesan = (int) $paket->id_resi < 0 ? 'Isi catatan: ciri zip / keranjang asal paket tanpa resi' : 'Ada selisih SKU / qty: isi catatan penyebabnya';
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
            if (!$perubahan && $salah !== null) {
                // Qty sama tetapi pilihan Kurang / Salah berbeda
                $ttd = function (array $g) {
                    $h = array_map(function ($x) { return $x['jenis'] . '|' . strtoupper($x['sku']) . '|' . strtoupper((string) $x['pasangan']) . '|' . (int) $x['qty']; }, $g);
                    sort($h);
                    return implode(';', $h);
                };
                $lama_kej = [];
                foreach ($this->db->select('jenis, sku, pasangan_sku, qty')->where('id_cancel_paket', $paket->id_cancel_paket)->where('aktif', 1)
                    ->get('tblcancel_paket_selisih')->result_array() as $k) {
                    $lama_kej[] = ['jenis' => $k['jenis'], 'sku' => $k['sku'], 'pasangan' => $k['pasangan_sku'], 'qty' => $k['qty']];
                }
                if ($ttd($lama_kej) !== $ttd($lb['grup'])) {
                    $perubahan[] = 'Pilihan Kurang / Salah diubah';
                }
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
        ] + $this->kolom_total($lb['total']));
        $this->tulis_selisih($paket->id_cancel_paket, $paket->id_resi, $lb['grup'], $now);
        $this->tulis_log($paket->id_cancel_paket, 'EDIT', $id_user, null, ['perubahan' => $perubahan]);
        $hasil_picker = $this->sinkron_masalah_picker($paket->id_resi, $paket->noresi, $s['baris'], $id_user, $salah !== null ? $lb['grup'] : null);
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
        $this->db->db_debug = $db_debug_asli;

        return $ok ? ['ok' => true, 'message' => 'Paket ' . ($paket->noresi !== '' ? $paket->noresi : 'tanpa resi #' . $paket->id_cancel_paket) . ' berhasil diperbarui' . $this->catatan_picker($hasil_picker)]
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
        // Paket dihapus = salah terima: catatan selisih ke picker dinolkan (terima ulang akan mengisinya lagi).
        $this->sinkron_masalah_picker($paket->id_resi, $paket->noresi, [], $id_user);
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
     * Isi kertas serah terima satu tanggal: per SKU, qty = barang yang BENAR-BENAR ada secara
     * fisik (qty_ditemukan) -- kurang ambil hanya yang ada, lebih ambil dan SKU di luar resi
     * (salah ambil) ikut penuh. Yang sudah dikembalikan ke display dikurangkan.
     * @return array ['det' => [['sku','rak','qty'], ...], 'jumlah_paket' => int, 'petugas' => [..]]
     */
    public function serah_harian($tanggal, array $ids_pilih = null)
    {
        $q = $this->db
            ->select('p.id_cancel_paket, p.id_resi, p.dicek_oleh')
            ->from('tblcancel_paket p')
            ->where('p.dicek_at IS NOT NULL', null, false)
            ->where('p.dihapus_at IS NULL', null, false);
        if ($ids_pilih !== null) {
            // Paket yang dicentang di laporan (boleh lintas tanggal terima)
            $q->where_in('p.id_cancel_paket', $ids_pilih ?: [0]);
        } else {
            $q->where('p.dicek_at >=', $tanggal . ' 00:00:00')
              ->where('p.dicek_at <=', $tanggal . ' 23:59:59');
        }
        $paket = $q->get()->result_array();
        if (!$paket) {
            return ['det' => [], 'jumlah_paket' => 0, 'petugas' => []];
        }
        $ids = array_column($paket, 'id_cancel_paket');

        // Per paket & SKU: fisik yang ada dikurangi barang yang sudah kembali ke display.
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
            $qty = max(0, $ada); // ikut fisik, tidak dibatasi qty resi
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
