<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Laporan extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('laporan_fcd');
    }

    // ── VIEW METHODS ─────────────────────────────────────────

    public function totalan_picker()
    {
        $data['tanggal'] = date('Y-m-d');
        $data['rows'] = $this->laporan_fcd->get_totalan_picker()->result();
        $this->show($data);
    }

    public function totalan_packer()
    {
        $data['tanggal'] = date('Y-m-d');
        $data['rows'] = $this->laporan_fcd->get_totalan_packer()->result();
        $this->show($data);
    }

    public function sisa_resi()
    {
        $data['tanggal'] = date('Y-m-d');
        $cutoff = $this->laporan_fcd->get_config('batas_kirim_aman') ?: '15:00';
        $data['cutoff'] = $cutoff;
        $data['rows'] = $this->laporan_fcd->get_sisa_resi(null, $cutoff)->result();
        $data['total'] = $this->laporan_fcd->get_total_sisa_resi(null, $cutoff);
        $this->show($data);
    }

    public function paket_keluar()
    {
        $data['tanggal'] = date('Y-m-d');
        $data['rows'] = $this->laporan_fcd->get_paket_keluar()->result();
        $data['total'] = $this->laporan_fcd->get_total_paket_keluar();
        $this->show($data);
    }

    public function rekap_pencapaian()
    {
        $data['message'] = $this->session->flashdata('message');
        $this->show($data);
    }

    public function rekap_target()
    {
        $tanggal = $this->input->get('tanggal') ?: date('Y-m-d');
        $data['tanggal'] = $tanggal;
        $data['pickers'] = $this->laporan_fcd->get_pencapaian_picker($tanggal)->result();
        $data['packers'] = $this->laporan_fcd->get_pencapaian_packer($tanggal)->result();
        $this->show($data);
    }

    public function kelola_target()
    {
        $data['targets'] = $this->laporan_fcd->get_targets()->result();
        $this->show($data);
    }

    public function ekspedisi_urgent()
    {
        $data['rows'] = $this->laporan_fcd->get_ekspedisi_urgent()->result();
        $this->show($data);
    }

    public function tracking_picker()
    {
        $tanggal = $this->input->get('tanggal') ?: date('Y-m-d');
        $data['tanggal'] = $tanggal;
        $data['rows'] = $this->build_tracking_picker_summary($tanggal);
        $this->show($data);
    }

    public function get_data_ekspedisi_urgent_detail()
    {
        $id_kurir = $this->input->post('id_kurir');
        $status   = $this->input->post('status');
        $rows     = $this->laporan_fcd->get_ekspedisi_urgent_detail($id_kurir, $status)->result();

        $data = [];
        $i = 1;
        foreach ($rows as $row) {
            $data[] = [
                $i++,
                $row->noresi,
                $row->nama_kurir,
                date('d-m-Y H:i', strtotime($row->tanggal_printresi)),
            ];
        }

        echo json_encode(['data' => $data]);
        exit();
    }

    public function get_data_tracking_picker()
    {
        $tanggal = $this->input->post('tanggal') ?: date('Y-m-d');
        $rows = $this->build_tracking_picker_summary($tanggal);

        $data = [];
        $i = 1;
        foreach ($rows as $row) {
            $data[] = [
                $i++,
                $row['id_pegawai'],
                $row['nama_pegawai'],
                $row['total_resi'],
                $row['resi_satuan'],
                $row['resi_campuran'],
                $row['lantai_dikunjungi'],
                $row['jumlah_pindah_lantai'],
            ];
        }

        echo json_encode(['data' => $data]);
        exit();
    }

    public function get_data_tracking_picker_detail()
    {
        $tanggal    = $this->input->post('tanggal') ?: date('Y-m-d');
        $id_pegawai = $this->input->post('id_pegawai');
        $resi_list  = $this->laporan_fcd->get_tracking_picker_resi($tanggal, $id_pegawai)->result();
        $detail     = $this->annotate_tracking_picker_floors($resi_list);

        $data = [];
        $i = 1;
        foreach ($detail as $d) {
            $data[] = [
                $i++,
                $d['jam'],
                $d['noresi'],
                ucfirst($d['tipe_resi']),
                $d['jumlah_sku'],
                $d['sku_list'],
                $d['lantai_text'],
                $d['keterangan'],
            ];
        }

        echo json_encode(['data' => $data]);
        exit();
    }

    /**
     * Rekap harian per picker: total resi, satuan/campuran, lantai yang disentuh,
     * dan berapa kali pindah lantai (dihitung dari urutan resi berdasarkan jam ambil).
     */
    private function build_tracking_picker_summary($tanggal)
    {
        $rows = $this->laporan_fcd->get_tracking_picker_resi($tanggal)->result();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->id_pegawai][] = $row;
        }

        $summary = [];
        foreach ($grouped as $id_pegawai => $resi_list) {
            $detail = $this->annotate_tracking_picker_floors($resi_list);

            $satuan = 0;
            $campuran = 0;
            $pindah = 0;
            $lantai_set = [];

            foreach ($detail as $d) {
                if ($d['tipe_resi'] === 'campuran') {
                    $campuran++;
                } else {
                    $satuan++;
                }
                foreach ($d['lantai'] as $l) {
                    $lantai_set[$l] = true;
                }
                if ($d['pindah']) {
                    $pindah++;
                }
            }

            ksort($lantai_set);

            $summary[] = [
                'id_pegawai'           => $id_pegawai,
                'nama_pegawai'         => $resi_list[0]->nama_pegawai,
                'total_resi'           => count($resi_list),
                'resi_satuan'          => $satuan,
                'resi_campuran'        => $campuran,
                'lantai_dikunjungi'    => empty($lantai_set) ? '-' : 'L' . implode(', L', array_keys($lantai_set)),
                'jumlah_pindah_lantai' => $pindah,
            ];
        }

        usort($summary, function ($a, $b) {
            return $b['total_resi'] <=> $a['total_resi'];
        });

        return $summary;
    }

    /**
     * Susun urutan resi picker jadi list beranotasi: tipe resi, lantai yang
     * dilibatkan, dan apakah dia baru pindah lantai dibanding resi sebelumnya.
     *
     * Keterbatasan: dalam SATU resi campuran yang SKU-nya tersebar di beberapa
     * lantai, sistem tidak mencatat urutan pengambilan per-SKU (hanya ada satu
     * timestamp per resi) — jadi "pindah lantai" hanya bisa dideteksi ANTAR resi,
     * bukan di dalam satu resi.
     */
    private function annotate_tracking_picker_floors($resi_list)
    {
        $result = [];
        $prev_floors = [];

        foreach ($resi_list as $row) {
            $floors = $row->lantai_list ? explode(',', $row->lantai_list) : [];
            $tipe = $row->tipe_resi ?: ($row->jumlah_sku > 1 ? 'campuran' : 'satuan');

            $pindah = false;
            if (empty($prev_floors)) {
                $keterangan = 'Resi pertama hari ini';
            } elseif (empty($floors)) {
                $keterangan = 'Lokasi rak tidak terdata';
            } else {
                $overlap = array_intersect($floors, $prev_floors);
                $baru = array_diff($floors, $prev_floors);
                if (empty($baru)) {
                    $keterangan = 'Tetap di L' . implode(', L', $floors);
                } elseif (empty($overlap)) {
                    $pindah = true;
                    $keterangan = 'Pindah L' . implode(',', $prev_floors) . ' → L' . implode(',', $floors);
                } else {
                    $pindah = true;
                    $keterangan = 'Tetap di L' . implode(',', $overlap) . ', tambah ke L' . implode(',', $baru);
                }
            }

            $result[] = [
                'nama_pegawai' => $row->nama_pegawai,
                'jam'         => date('H:i', strtotime($row->tanggal_resiambilbarang)),
                'noresi'      => $row->noresi,
                'tipe_resi'   => $tipe,
                'jumlah_sku'  => (int) $row->jumlah_sku,
                'sku_list'    => $row->sku_list,
                'lantai'      => $floors,
                'lantai_text' => empty($floors) ? '-' : 'L' . implode(', L', $floors),
                'pindah'      => $pindah,
                'keterangan'  => $keterangan,
            ];

            if (!empty($floors)) {
                $prev_floors = $floors;
            }
        }

        return $result;
    }

    /**
     * Gabungkan detail semua picker (urut per picker, lalu per jam) untuk
     * kebutuhan export — dipakai supaya file Excel berisi baris per resi,
     * bukan cuma rekap.
     */
    private function build_tracking_picker_detail_flat($tanggal)
    {
        $rows = $this->laporan_fcd->get_tracking_picker_resi($tanggal)->result();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->id_pegawai][] = $row;
        }

        $flat = [];
        foreach ($grouped as $resi_list) {
            foreach ($this->annotate_tracking_picker_floors($resi_list) as $d) {
                $flat[] = $d;
            }
        }

        return $flat;
    }

    public function export_tracking_picker()
    {
        $tanggal = $this->input->get('tanggal') ?: date('Y-m-d');

        $data['tanggal'] = $tanggal;
        $data['summary'] = $this->build_tracking_picker_summary($tanggal);
        $data['detail']  = $this->build_tracking_picker_detail_flat($tanggal);

        $filename = 'Tracking_Picker_' . $tanggal . '.xls';
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename={$filename}");

        $this->load->view('laporan/export_tracking_picker', $data);
    }

    public function send_wa_ekspedisi_urgent()
    {
        $this->load->library('wa_gateway');

        $msg = $this->laporan_fcd->format_wa_ekspedisi_urgent();
        $result = $this->wa_gateway->send_to_group($msg);

        if (isset($result['error']) && $result['error']) {
            $this->make_ajax_response(500, 'Gagal kirim WA: ' . ($result['message'] ?? 'Unknown error'));
        } else {
            $this->make_ajax_response(200, 'Laporan ekspedisi urgent berhasil dikirim ke WhatsApp.');
        }
    }

    // ── AJAX DATA ENDPOINTS ──────────────────────────────────

    public function get_data_totalan_picker()
    {
        $tanggal = $this->input->post('tanggal') ?: date('Y-m-d');
        $rows = $this->laporan_fcd->get_totalan_picker($tanggal)->result();

        $data = [];
        $i = 1;
        $grand_total = 0;
        foreach ($rows as $row) {
            $grand_total += $row->total_resi;
            $data[] = [
                $i++,
                $row->nama_pegawai,
                $row->total_resi,
                $row->satuan,
                $row->campuran,
                $row->total_sku_qty,
                $row->total_kesalahan,
                $row->total_point,
            ];
        }

        echo json_encode([
            'data'       => $data,
            'grandTotal' => $grand_total,
        ]);
        exit();
    }

    public function get_data_totalan_packer()
    {
        $tanggal = $this->input->post('tanggal') ?: date('Y-m-d');
        $rows = $this->laporan_fcd->get_totalan_packer($tanggal)->result();

        $data = [];
        $i = 1;
        $grand_total = 0;
        foreach ($rows as $row) {
            $grand_total += $row->total_resi;
            $data[] = [
                $i++,
                $row->nama_packer,
                $row->total_resi,
                $row->satuan,
                $row->campuran,
                $row->total_sku_qty,
                $row->total_kesalahan,
                $row->total_point,
            ];
        }

        echo json_encode([
            'data'       => $data,
            'grandTotal' => $grand_total,
        ]);
        exit();
    }

    public function get_data_rekap_picker()
    {
        $start_date = $this->input->post('start_date') ?: date('Y-m-d', strtotime('-7 days'));
        $end_date   = $this->input->post('end_date') ?: date('Y-m-d');

        $rows = $this->laporan_fcd->get_rekap_picker($start_date, $end_date)->result();

        $data = [];
        $i = 1;
        foreach ($rows as $row) {
            $data[] = [
                $i++,
                $row->nama_pegawai,
                $row->tanggal,
                $row->total_resi,
                $row->satuan,
                $row->campuran,
            ];
        }

        echo json_encode(['data' => $data]);
        exit();
    }

    public function get_data_rekap_packer()
    {
        $start_date = $this->input->post('start_date') ?: date('Y-m-d', strtotime('-7 days'));
        $end_date   = $this->input->post('end_date') ?: date('Y-m-d');

        $rows = $this->laporan_fcd->get_rekap_packer($start_date, $end_date)->result();

        $data = [];
        $i = 1;
        foreach ($rows as $row) {
            $data[] = [
                $i++,
                $row->nama_packer,
                $row->tanggal,
                $row->total_resi,
                $row->satuan,
                $row->campuran,
            ];
        }

        echo json_encode(['data' => $data]);
        exit();
    }

    public function get_data_rekap_paket_keluar()
    {
        $start_date = $this->input->post('start_date') ?: date('Y-m-d', strtotime('-7 days'));
        $end_date   = $this->input->post('end_date') ?: date('Y-m-d');

        $rows = $this->laporan_fcd->get_rekap_paket_keluar($start_date, $end_date)->result();

        $data = [];
        $i = 1;
        foreach ($rows as $row) {
            $data[] = [
                $i++,
                $row->nama_kurir,
                $row->tanggal,
                $row->jumlah_paket,
            ];
        }

        echo json_encode(['data' => $data]);
        exit();
    }

    // ── EXCEL EXPORT ─────────────────────────────────────────

    public function export_rekap_picker()
    {
        $start_date = $this->input->get('start_date') ?: date('Y-m-d', strtotime('-7 days'));
        $end_date   = $this->input->get('end_date') ?: date('Y-m-d');

        $data['rows'] = $this->laporan_fcd->get_rekap_picker($start_date, $end_date)->result();
        $data['start_date'] = $start_date;
        $data['end_date']   = $end_date;
        $data['title']      = 'Rekap Picker';

        $filename = 'Rekap_Picker_' . $start_date . '_' . $end_date . '.xls';
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename={$filename}");

        $this->load->view('laporan/export_rekap_picker', $data);
    }

    public function export_rekap_packer()
    {
        $start_date = $this->input->get('start_date') ?: date('Y-m-d', strtotime('-7 days'));
        $end_date   = $this->input->get('end_date') ?: date('Y-m-d');

        $data['rows'] = $this->laporan_fcd->get_rekap_packer($start_date, $end_date)->result();
        $data['start_date'] = $start_date;
        $data['end_date']   = $end_date;
        $data['title']      = 'Rekap Packer';

        $filename = 'Rekap_Packer_' . $start_date . '_' . $end_date . '.xls';
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename={$filename}");

        $this->load->view('laporan/export_rekap_packer', $data);
    }

    public function export_rekap_paket_keluar()
    {
        $start_date = $this->input->get('start_date') ?: date('Y-m-d', strtotime('-7 days'));
        $end_date   = $this->input->get('end_date') ?: date('Y-m-d');

        $data['rows'] = $this->laporan_fcd->get_rekap_paket_keluar($start_date, $end_date)->result();
        $data['start_date'] = $start_date;
        $data['end_date']   = $end_date;
        $data['title']      = 'Rekap Paket Keluar';

        $filename = 'Rekap_Paket_Keluar_' . $start_date . '_' . $end_date . '.xls';
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename={$filename}");

        $this->load->view('laporan/export_rekap_paket_keluar', $data);
    }

    // ── TARGET CRUD ──────────────────────────────────────────

    public function save_target()
    {
        $data = [
            'role'         => $this->input->post('role'),
            'user_id'      => $this->input->post('user_id') ?: null,
            'target'       => intval($this->input->post('target')),
            'berlaku_dari' => $this->input->post('berlaku_dari'),
        ];

        if ($this->input->post('id')) {
            $data['id'] = $this->input->post('id');
        }

        $result = $this->laporan_fcd->save_target($data);
        $this->make_ajax_response(200, 'Target berhasil disimpan.', $result);
    }

    public function delete_target()
    {
        $id = $this->input->post('id');
        $this->laporan_fcd->delete_target($id);
        $this->make_ajax_response(200, 'Target berhasil dihapus.');
    }

    // ── WHATSAPP TRIGGERS ────────────────────────────────────

    public function send_wa_sisa_resi()
    {
        $this->load->library('wa_gateway');

        $msg = $this->laporan_fcd->format_wa_sisa_resi();
        $result = $this->wa_gateway->send_to_group($msg);

        if (isset($result['error']) && $result['error']) {
            $this->make_ajax_response(500, 'Gagal kirim WA: ' . ($result['message'] ?? 'Unknown error'));
        } else {
            $this->make_ajax_response(200, 'Laporan sisa resi berhasil dikirim ke WhatsApp.');
        }
    }

    public function send_wa_paket_keluar()
    {
        $this->load->library('wa_gateway');

        $msg = $this->laporan_fcd->format_wa_paket_keluar();
        $result = $this->wa_gateway->send_to_group($msg);

        if (isset($result['error']) && $result['error']) {
            $this->make_ajax_response(500, 'Gagal kirim WA: ' . ($result['message'] ?? 'Unknown error'));
        } else {
            $this->make_ajax_response(200, 'Rekap paket keluar berhasil dikirim ke WhatsApp.');
        }
    }

    // ── WA STATUS CHECK ──────────────────────────────────────

    public function wa_status()
    {
        $this->load->library('wa_gateway');
        $status = $this->wa_gateway->get_status();
        echo json_encode($status);
        exit();
    }
}
