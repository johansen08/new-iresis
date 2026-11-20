<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Terima Paket Cancel & Laporan Paket Cancel (tim retur). Alur: docs/PAKET_CANCEL.md §12.
 */
class Paket_cancel extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('cancel_terima_fcd');
    }

    private function id_user()
    {
        return (int) $this->data['user']['id_user'];
    }

    private function tanggal($nilai, $default)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $nilai) ? $nilai : $default;
    }

    // "1,2,3" -> [1,2,3] (id paket yang dicentang di laporan); kosong -> []
    private function daftar_id($nilai)
    {
        $hasil = [];
        foreach (explode(',', (string) $nilai) as $x) {
            if ((int) $x > 0) {
                $hasil[(int) $x] = (int) $x;
            }
        }
        return array_values($hasil);
    }

    // ───────── Terima Paket Cancel ─────────

    public function terima()
    {
        $this->show();
    }

    // Scan resi -> isi seharusnya
    public function cek_resi()
    {
        $this->make_ajax_response(200, 'ok', $this->cancel_terima_fcd->cek_resi($this->input->post('noresi')));
    }

    // Saran SKU saat mengetik (barang di luar resi)
    public function sku_saran()
    {
        $q = trim((string) $this->input->get('q'));
        $this->make_ajax_response(200, 'ok', ['results' => $q === '' ? [] : $this->cancel_terima_fcd->saran_sku($q)]);
    }

    // Tebak satu SKU dari teks
    public function sku_detail()
    {
        $this->make_ajax_response(200, 'ok', $this->cancel_terima_fcd->cari_sku($this->input->post('sku')));
    }

    // Pilihan Kurang / Salah dari petugas: [{sku, pasangan, qty}]; tidak dikirim (klien lama) = null (aturan lama)
    private function daftar_salah()
    {
        $mentah = $this->input->post('salah');
        if ($mentah === null) {
            return null;
        }
        $x = json_decode((string) $mentah, true);
        return is_array($x) ? $x : [];
    }

    public function simpan()
    {
        $items = json_decode((string) $this->input->post('items'), true);
        $alasan = json_decode((string) $this->input->post('alasan'), true);
        $hasil = $this->cancel_terima_fcd->simpan(
            $this->input->post('noresi'),
            $this->input->post('dari'),
            is_array($items) ? $items : [],
            $this->input->post('catatan'),
            $this->id_user(),
            isset($this->data['user']['nama_komputer']) ? $this->data['user']['nama_komputer'] : null,
            is_array($alasan) ? $alasan : [],
            $this->daftar_salah()
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    // ───────── Laporan Paket Cancel ─────────

    public function laporan()
    {
        $this->show();
    }

    // Semua paket diterima pada rentang tanggal (maks 93 hari); filter asal & cari dilakukan di klien.
    public function laporan_data()
    {
        $hari_ini = date('Y-m-d');
        $start = $this->tanggal($this->input->post('start'), $hari_ini);
        $end = $this->tanggal($this->input->post('end'), $start);
        if ($end < $start) {
            list($start, $end) = array($end, $start);
        }
        if (strtotime($end) - strtotime($start) > 92 * 86400) {
            $this->make_ajax_response(400, 'Rentang tanggal maksimal 93 hari', ['ok' => false]);
        }
        $this->make_ajax_response(200, 'ok', ['rows' => $this->cancel_terima_fcd->laporan_baris($start, $end), 'display' => $this->cancel_terima_fcd->display_baris($start, $end), 'ok' => true]);
    }

    public function edit_simpan()
    {
        $items = json_decode((string) $this->input->post('items'), true);
        $hasil = $this->cancel_terima_fcd->edit(
            (int) $this->input->post('id'),
            $this->input->post('dari'),
            is_array($items) ? $items : [],
            $this->input->post('catatan'),
            $this->id_user(),
            $this->daftar_salah()
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    public function hapus()
    {
        $hasil = $this->cancel_terima_fcd->hapus((int) $this->input->post('id'), $this->input->post('alasan'), $this->id_user());
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    // Terima paket tanpa kertas resi (zip berisi beberapa barang): input biasa, no. resi kosong
    public function simpan_tanpa_resi()
    {
        $items = json_decode((string) $this->input->post('items'), true);
        $hasil = $this->cancel_terima_fcd->simpan_tanpa_resi(
            $this->input->post('dari'),
            is_array($items) ? $items : [],
            $this->input->post('catatan'),
            $this->id_user(),
            isset($this->data['user']['nama_komputer']) ? $this->data['user']['nama_komputer'] : null
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    // Barang paket diterima yang kembali ke display (per SKU & qty) + sumber pengganti QC / Restok
    public function tandai_display()
    {
        $items = json_decode((string) $this->input->post('items'), true);
        $hasil = $this->cancel_terima_fcd->tandai_display(
            (int) $this->input->post('id'),
            is_array($items) ? $items : [],
            $this->input->post('sumber'),
            $this->input->post('catatan'),
            $this->id_user()
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    // Barang kembali ke display yang resinya tidak ada / belum diterima (resi opsional)
    public function catat_display()
    {
        $items = json_decode((string) $this->input->post('items'), true);
        $hasil = $this->cancel_terima_fcd->catat_display_bebas(
            $this->input->post('noresi'),
            is_array($items) ? $items : [],
            $this->input->post('sumber'),
            $this->input->post('catatan'),
            $this->id_user()
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    public function batal_display()
    {
        $hasil = $this->cancel_terima_fcd->batal_display((int) $this->input->post('id'), $this->id_user());
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    // Centang "selesai" pada laporan (tahap 2 status pengembalian)
    public function tandai_selesai()
    {
        $selesai = (int) $this->input->post('selesai') === 1;
        $ids = $this->daftar_id($this->input->post('ids'));
        $hasil = $ids ? $this->cancel_terima_fcd->tandai_selesai_banyak($ids, $selesai, $this->id_user())
                      : $this->cancel_terima_fcd->tandai_selesai((int) $this->input->post('id'), $selesai, $this->id_user());
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    // Isi kertas serah terima satu tanggal (pratinjau di halaman laporan)
    public function serah_data()
    {
        $tanggal = $this->tanggal($this->input->post('tanggal'), date('Y-m-d'));
        $ids = $this->daftar_id($this->input->post('ids'));
        $this->make_ajax_response(200, 'ok', ['tanggal' => $tanggal, 'ok' => true] + $this->cancel_terima_fcd->serah_harian($tanggal, $ids ?: null));
    }

    // Halaman cetak A4 (tab baru, bukan JSON)
    public function serah_cetak()
    {
        $tanggal = $this->tanggal($this->input->get_post('tanggal'), date('Y-m-d'));
        $ids = $this->daftar_id($this->input->get_post('ids'));
        $data = $this->cancel_terima_fcd->serah_harian($tanggal, $ids ?: null);
        $data['tanggal'] = $tanggal;
        // Membuka halaman cetak = serah terima dicetak (status pengembalian tahap 1)
        if ($data['jumlah_paket'] > 0) {
            $this->cancel_terima_fcd->tandai_serah_dicetak($tanggal, $this->id_user(), $ids ?: null);
        }
        $this->load->view('paket_cancel/serah_cetak', $data);
    }

    // ───────── Stok Paket Cancel (read-only) ─────────

    public function stok()
    {
        $this->show();
    }

    public function stok_data()
    {
        $hasil = $this->cancel_terima_fcd->stok_paket_cancel();
        $hasil['ok'] = true;
        $this->make_ajax_response(200, 'ok', $hasil);
    }

    /**
     * GET: q, beda ('' / beda / sama), asal (kunci dipisah koma; dipakai bila ada_asal=1, asal kosong = "tanpa asal")
     * -- sama dengan filter di layar. Unduh xlsx rapi: sheet stok per SKU dan sheet rincian per paket.
     */
    public function stok_export()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $this->load->helper('xlsx_rapi');

        $q = $this->input->get('q');
        $q = is_string($q) ? mb_strtolower(trim($q)) : '';
        $beda = $this->input->get('beda');
        $beda = in_array($beda, ['beda', 'sama'], true) ? $beda : '';
        $asal = [];
        if ($this->input->get('ada_asal') === '1') {
            $asal_raw = (string) $this->input->get('asal');
            $asal = ($asal_raw === '') ? [''] : explode(',', $asal_raw);
        }
        $label_asal = ['PACKER' => 'Packer', 'HO' => 'HO', 'KURIR' => 'Kurir', '' => 'Tanpa asal'];

        $hasil = $this->cancel_terima_fcd->stok_paket_cancel();
        $rows = [];
        foreach ($hasil['rows'] as $r) {
            if ($beda === 'beda' && !$r['beda']) continue;
            if ($beda === 'sama' && $r['beda']) continue;
            if ($asal) {
                $cocok = false;
                foreach ($r['paket'] as $p) {
                    if (in_array((string) $p['dari'], $asal, true)) { $cocok = true; break; }
                }
                if (!$cocok) continue;
            }
            if ($q !== '') {
                $gabung = $r['sku'] . ' ' . $r['rak'];
                foreach ($r['paket'] as $p) $gabung .= ' ' . $p['noresi'];
                if (mb_strpos(mb_strtolower($gabung), $q) === false) continue;
            }
            $rows[] = $r;
        }

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ---- Sheet 1: stok per SKU
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Stok per SKU');
        $baris1 = [];
        foreach ($rows as $i => $r) {
            $dari = [];
            foreach ($r['paket'] as $p) {
                $dari[(string) $p['dari']] = $label_asal[(string) $p['dari']] ?? (string) $p['dari'];
            }
            $baris1[] = [$i + 1, $r['sku'], $r['nama'], $r['rak'], (int) $r['stok'], (int) $r['jumlah_paket'],
                $r['beda'] ? 'Beda dari resi' : 'Sesuai resi', implode(', ', $dari)];
        }
        xlsx_tulis_sheet_rapi($ws, ['No', 'SKU', 'Nama SKU', 'No. Rak', 'Stok Real', 'Jumlah Paket', 'Keterangan', 'Asal'],
            $baris1, [6, 26, 40, 14, 12, 14, 18, 22], [2, 3, 4], [5, 6]);

        // ---- Sheet 2: rincian per paket
        $ws2 = $ss->createSheet();
        $ws2->setTitle('Rincian Paket');
        $baris2 = [];
        $no = 1;
        foreach ($rows as $r) {
            foreach ($r['paket'] as $p) {
                if ((int) $p['qty_resi'] === 0) {
                    $ket = $p['pasangan'] ? 'Salah ambil (resi: ' . $p['pasangan'] . ')' : 'Di luar resi';
                } elseif ($p['qty_ada'] > $p['qty_resi']) {
                    $ket = 'Lebih ' . ($p['qty_ada'] - $p['qty_resi']);
                } elseif ($p['qty_ada'] < $p['qty_resi']) {
                    $ket = 'Kurang ' . ($p['qty_resi'] - $p['qty_ada']);
                } else {
                    $ket = 'Sesuai resi';
                }
                $baris2[] = [$no++, $r['sku'], $r['rak'], $p['noresi'] !== '' ? $p['noresi'] : '(paket tanpa resi)',
                    $label_asal[(string) $p['dari']] ?? (string) $p['dari'],
                    $p['tgl'] ? date('d/m/Y', strtotime($p['tgl'])) . ' ' . $p['jam'] : '-',
                    (int) $p['qty_resi'], (int) $p['qty_ada'], (int) $p['kembali'], (int) $p['stok'], $ket,
                    $p['dicetak'] ? 'Ya' : '-'];
            }
        }
        xlsx_tulis_sheet_rapi($ws2, ['No', 'SKU', 'No. Rak', 'No. Resi', 'Asal', 'Diterima', 'Qty Resi', 'Qty Real', 'Kembali ke Display', 'Stok', 'Keterangan', 'Serah Terima Dicetak'],
            $baris2, [6, 26, 14, 26, 12, 18, 10, 10, 18, 10, 30, 20], [2, 3, 4], [7, 8, 9, 10]);

        $ss->setActiveSheetIndex(0);
        $nama_berkas = 'stok-paket-cancel-' . date('Ymd-His') . '.xlsx';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nama_berkas . '"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit();
    }
}
