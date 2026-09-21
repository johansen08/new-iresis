<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Packer extends MY_Controller
{

    /**
     * Berapa lama sebuah resi yang menunggu scan kedua masih dianggap "sedang
     * dikerjakan": rekamannya boleh dilanjutkan otomatis saat halaman dibuka
     * ulang, dan siklusnya belum ditutup paksa oleh bersihkan_scan_kedaluwarsa().
     *
     * Harus lebih longgar dari MAKS_DURASI_DTK di assets/js/packer_video.js (90
     * menit) -- kalau lebih pendek, resi yang memang sedang direkam bisa
     * kedaluwarsa di tengah jalan, dan scan penutupnya malah dianggap scan
     * pertama yang baru. Tetap jauh lebih pendek dari umur session (1 hari),
     * supaya sisa pekerjaan kemarin tidak ikut hidup lagi hari ini.
     */
    const BATAS_LANJUT_REKAM_DETIK = 6000;

    /**
     * Jeda minimum antara scan pertama dan scan kedua sebuah resi.
     *
     * Scan kedua yang datang lebih cepat dari ini hampir pasti bukan tanda
     * packing selesai, melainkan pemicu scanner yang tertekan dua kali atau
     * refleks packer -- barangnya belum sempat dipacking sama sekali. Kalau
     * diterima, resi tertutup tanpa pernah dipacking dan videonya cuma berisi
     * beberapa detik kosong.
     */
    const MIN_JEDA_SCAN_DETIK = 5;

    /**
     * Batas jumlah bagian rekaman untuk satu resi.
     *
     * Bagian baru hanya lahir kalau tab packer mati di tengah packing, jadi dua
     * atau tiga sudah tidak wajar. Angka ini cuma pengaman supaya komputer yang
     * bermasalah tidak mengisi folder dengan ratusan potongan tanpa ada yang
     * menyadarinya.
     */
    const MAKS_BAGIAN_VIDEO = 20;

    /**
     * Umur siklus minimum sebelum resi boleh ditutup otomatis oleh batas durasi
     * rekaman. HARUS sama dengan MAKS_DURASI_DTK di assets/js/packer_video.js.
     *
     * Penutupan otomatis menyimpan resi tanpa scan kedua, jadi tanpa penjagaan
     * ini ia jadi jalan pintas: satu permintaan POST bisa menutup resi yang baru
     * saja discan, tanpa pernah dipacking dan tanpa video.
     */
    const BATAS_TUTUP_OTOMATIS_DETIK = 5400;

    /** Kelonggaran untuk selisih jam browser-server dan waktu tempuh permintaan. */
    const TOLERANSI_TUTUP_DETIK = 60;


	function __construct()
	{
		parent::__construct();

		$this->load->model('employee_fcd');
		$this->load->model('packer_fcd');
        $this->load->model('receipt_fcd');
        $this->load->model('problemtype_fcd');
        $this->load->model('video_packing_fcd');
	}

    public function scan_packer_nonsubmit()
    {
        $data = [];
        $data['total_scan'] = 0;
        $data['nama_picker'] = '-';
        $data['komputer_packer'] = isset($this->data['nama_pk']) ? $this->data['nama_pk'] : (isset($this->data['user']['nama_komputer']) ? $this->data['user']['nama_komputer'] : '-');

        $packer_scan = $this->packer_fcd->get_total_scan_packer_nonsubmit_user($this->data['user']['id_user'])->row();
        if ($packer_scan) {
            $data['total_scan'] = $packer_scan->total_scan;
        }

        // Load session status for packer monitoring
        $this->load->model('packer_monitoring_fcd');
        $session = $this->packer_monitoring_fcd->get_session($this->data['user']['id_user']);
        $data['session_status'] = [
            'masuk' => !empty($session->waktu_masuk),
            'istirahat' => (!empty($session->waktu_istirahat_mulai) && empty($session->waktu_istirahat_selesai)),
            'pulang' => !empty($session->waktu_pulang)
        ];

        $this->show($data, 'packer/scan_packer_nonsubmit');
    }

    public function save_packer_nonsubmit()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');
        
        $packer_data = ['noresi' => $noresi];
        $status_id = $this->determine_status_performa_id();
        if ($status_id) {
            $packer_data['status_performa_id'] = $status_id;
        }

        $save = $this->packer_fcd->save_packer_nonsubmit($packer_data, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message'], $save['data'] ?? null);
        }

        if ($save['affected_rows'] > 0) {
            // Include is_slow and slow_count if available from performance data
            $res_data = [
                'performance' => $save['performance'] ?? null,
                'is_slow' => $save['performance']['is_slow'] ?? 0,
                'slow_count' => $save['performance']['slow_count'] ?? 0
            ];
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA, $res_data);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    public function masalah_picker_save()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $masalah['id_printresi'] = $this->input->post('id_printresi');
        $masalah['noresi'] = $this->input->post('noresi');
        $masalah['sku'] = $this->input->post('sku');
        $masalah['qty'] = $this->input->post('qty');
        $masalah['id_typemasalah'] = $this->input->post('type_masalah');
        $masalah['qty_bermasalah'] = $this->input->post('qty_bermasalah');
        $masalah['sku_salah'] = $this->input->post('sku_salah');

        $save = $this->packer_fcd->save_masalah_picker($masalah, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

	/**
	 * Halaman Scan Resi Packer.
	 *
	 * Halaman ini sekarang HANYA dirender sekali, saat menu dibuka. Isi per resi
	 * (detail SKU, nama picker, total scan) diambil lewat AJAX ke detail_resi(),
	 * dan penyimpanannya lewat save_packer().
	 *
	 * Dulu tiap scan mem-POST ke method ini dan seluruh view dirender ulang,
	 * lalu DataTables menembak get_scan_packer_data() sebagai request kedua.
	 * Karena resi wajib discan dua kali, satu paket memakan 4 request HTTP dan
	 * 4 koneksi MySQL baru -- dan DB ada di mesin lain (lihat secrets.php),
	 * jadi tiap koneksi membayar handshake lewat LAN. Yang lebih merugikan:
	 * plugins.js mengosongkan .page-content-wrap begitu request dikirim,
	 * sehingga input nomor resi hilang dari DOM dan scan yang diketik scanner
	 * selama request berjalan ikut hilang. Itu sumber "antrian" yang dikeluhkan
	 * packer, dan alasan form di view sekarang diberi class "nojs".
	 */
	public function scan_packer()
	{
        $data = [];

        // Initialize default values to prevent undefined variable errors
        $data['total_scan'] = 0;
        $data['nama_picker'] = '-';
        $data['komputer_picker'] = '-';
        $data['komputer_packer'] = isset($this->data['nama_pk']) ? $this->data['nama_pk'] : (isset($this->data['user']['nama_komputer']) ? $this->data['user']['nama_komputer'] : '-');

        // Modal "Submit Masalah Picker" selalu ikut dirender, termasuk saat halaman
        // dibuka lewat GET (belum ada resi yang discan). Tanpa default di bawah,
        // view memicu "Undefined variable $list_type_masalah" dan "$noresi".
        $data['noresi'] = '';
        $data['list_type_masalah'] = $this->problemtype_fcd->get_list();

        // Load session status for packer monitoring
        $this->load->model('packer_monitoring_fcd');
        $session = $this->packer_monitoring_fcd->get_session($this->data['user']['id_user']);
        $data['session_status'] = [
            'masuk' => !empty($session->waktu_masuk),
            'istirahat' => (!empty($session->waktu_istirahat_mulai) && empty($session->waktu_istirahat_selesai)),
            'pulang' => !empty($session->waktu_pulang)
        ];

        $this->show($data);
	}

	/**
	 * Detail satu resi untuk halaman Scan Resi Packer, dalam bentuk JSON.
	 *
	 * Menggantikan dua request lama per scan: POST scan_packer() yang merender
	 * ulang seluruh halaman, plus POST get_scan_packer_data() yang mengisi
	 * DataTables. Sekarang cukup satu request, dan yang dikirim balik hanya
	 * data -- bukan puluhan KB HTML halaman.
	 *
	 * Baris detail diambil dari query yang sama dengan yang dulu dipakai
	 * DataTables (get_receipt_for_packer), tanpa limit: satu resi cuma berisi
	 * beberapa SKU, jadi paginasi server-side di sini hanya menambah request.
	 */
	public function detail_resi()
	{
		if ($this->input->method() == 'get') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		$noresi = trim((string) $this->input->post('noresi'));
		if ($noresi === '') {
			$this->make_ajax_response(400, 'Nomor resi tidak boleh kosong');
		}

		// length = 0 supaya get_receipt_for_packer() tidak memasang LIMIT.
		$rows = $this->receipt_fcd->get_receipt_for_packer(['length' => 0, 'start' => 0], $noresi);

		if (empty($rows)) {
			$this->make_ajax_response(404, 'Nomor resi tidak ditemukan', ['EXCEPTION_CODE' => 'NOT_FOUND']);
		}

		// Join ke tbldetailprintresi bersifat LEFT, jadi resi yang belum punya
		// baris detail tetap balik satu baris dengan sku NULL. Baris itu dibuang
		// di sini supaya tabel di layar kosong, bukan berisi baris hantu.
		$items = [];
		$total_qty = 0;
		foreach ($rows as $row) {
			if (empty($row->sku)) {
				continue;
			}

			$items[] = [
				'sku'           => $row->sku,
				'nama_sku'      => $row->nama_sku ?? '-',
				'jumlah'        => (int) ($row->jumlah ?? 0),
				'no_rak'        => $row->no_rak ?? '-',
				'link_foto'     => foto_sku_url($row->link_foto ?? ''), // salinan lokal bila ada (offline-proof)
				'jenis_packing' => $row->jenis_packing ?? '',
				'nama_picker'   => $row->name ?? ($row->yangambil_pegawai ?? ''),
			];

			$total_qty += (int) ($row->jumlah ?? 0);
		}

		$picker_detail = $this->packer_fcd->get_picker_detail_for_packer($noresi);
		$packer_scan   = $this->packer_fcd->get_total_scan_user($this->data['user']['id_user'])->row();

		$this->make_ajax_response(200, 'Detail resi ditemukan', [
			'noresi'          => $noresi,
			'id_printresi'    => $rows[0]->id_printresi,
			'items'           => $items,
			'total_qty'       => $total_qty,
			'total_items'     => count($items),
			'total_scan'      => $packer_scan ? (int) $packer_scan->total_scan : 0,
			'nama_picker'     => $picker_detail ? $picker_detail->nama_pegawai : '-',
			'komputer_picker' => $picker_detail ? $picker_detail->nama_komputer : '-',
		]);
	}

	/**
	 * Scan Resi Packer versi webcam.
	 *
	 * SENGAJA salinan utuh scan_packer(), bukan berbagi method dengannya. Dua
	 * halaman ini harus berdiri sendiri: versi biasa tetap jadi cadangan kalau
	 * kamera bermasalah, jadi perubahan di halaman webcam tidak boleh merembet
	 * ke sana. Duplikasi di sini disengaja, bukan kelalaian -- jangan disatukan.
	 *
	 * Bedanya dengan versi biasa: setiap resi direkam videonya (lihat
	 * assets/js/packer_video.js), dan aturan double-scan-nya dijaga di session
	 * server lewat handle_double_scan_state_webcam() -- siklus scan pertama
	 * harus bertahan melewati reload maupun tab yang mati, karena rekamannya
	 * ikut bergantung pada siklus itu.
	 *
	 * Halaman ini dibuka untuk webmaster (1) dan client packer (4) -- daftar
	 * role-nya dikunci di ROLE_BOLEH_WEBCAM dan harus sejalan dengan hak akses
	 * menu yang ditanam MY_Controller::run_menu_scan_packer_webcam(). Penjagaan
	 * ditaruh di controller juga, bukan hanya di menu, karena URL-nya bisa
	 * dibuka langsung. Penolakannya lewat show() supaya responsnya tetap JSON
	 * yang valid -- show_404() mengirim halaman HTML dan merusak parsing SPA.
	 */
	const ROLE_BOLEH_WEBCAM = [1, 4];

	public function scan_packer_webcam()
	{
		if (empty($this->data['user']['hakakses'])
			|| !in_array((int) $this->data['user']['hakakses'], self::ROLE_BOLEH_WEBCAM, TRUE)) {
			$this->show(['akses_ditolak' => TRUE], 'packer/scan_packer_webcam');
			return;
		}

        $data = [];

        // Initialize default values to prevent undefined variable errors
        $data['total_scan'] = 0;
        $data['nama_picker'] = '-';
        $data['komputer_picker'] = '-';
        $data['komputer_packer'] = isset($this->data['nama_pk']) ? $this->data['nama_pk'] : (isset($this->data['user']['nama_komputer']) ? $this->data['user']['nama_komputer'] : '-');

        // Modal "Submit Masalah Picker" selalu ikut dirender, termasuk saat halaman
        // dibuka lewat GET (belum ada resi yang discan). Tanpa default di bawah,
        // view memicu "Undefined variable $list_type_masalah" dan "$noresi".
        $data['noresi'] = '';
        $data['list_type_masalah'] = $this->problemtype_fcd->get_list();
        // Load session status for packer monitoring
        $this->load->model('packer_monitoring_fcd');
        $session = $this->packer_monitoring_fcd->get_session($this->data['user']['id_user']);
        $data['session_status'] = [
            'masuk' => !empty($session->waktu_masuk),
            'istirahat' => (!empty($session->waktu_istirahat_mulai) && empty($session->waktu_istirahat_selesai)),
            'pulang' => !empty($session->waktu_pulang)
        ];

        // Siklus scan pertama yang sudah menggantung terlalu lama ditutup di sini,
        // sebelum scan apa pun diproses. Kalau dibiarkan, bilah "menunggu scan
        // kedua" bertahan sampai berjam-jam kemudian dan menolak semua resi lain,
        // sementara rekamannya sendiri sudah lama mati kena batas durasi browser --
        // menutupnya dengan scan kedua cuma menghasilkan packing tanpa video utuh.
        $data['resi_kedaluwarsa'] = $this->bersihkan_scan_kedaluwarsa();

        if ($this->input->method() == 'post') {
            $noresi = trim($this->input->post('noresi'));

            // Status siklus ditentukan lebih dulu, baru detail resinya diambil:
            // scan yang ditolak menampilkan resi yang sedang dikerjakan, bukan
            // resi yang barusan discan.
            //
            // kamera_status dikirim halaman dari PackerVideo. Kalau field-nya
            // tidak ada sama sekali -- browser tanpa JS, atau permintaan yang
            // dirakit di luar halaman -- kamera dianggap tidak siap, karena
            // packing tanpa rekaman memang tidak boleh dimulai.
            $scan = $this->handle_double_scan_state_webcam(
                $noresi,
                (string) $this->input->post('kamera_status'),
                $data['session_status']
            );

            $scan_feedback = $scan ? $scan['feedback'] : null;
            $resi_tampil   = $scan ? $scan['resi_tampil'] : $noresi;

            if ($scan_feedback) {
                $data['scan_feedback'] = $scan_feedback;
            }

            $receipts = $resi_tampil !== ''
                ? $this->receipt_fcd->get_detail_receipt($resi_tampil)->result()
                : [];
            $picker_detail = $resi_tampil !== ''
                ? $this->packer_fcd->get_picker_detail_for_packer($resi_tampil)
                : null;

            $packer_scan = $this->packer_fcd->get_total_scan_user($this->data['user']['id_user'])->row();

            // Always update total_scan from current user
            if($packer_scan) {
                $data['total_scan'] = $packer_scan->total_scan;
            }

            if(!empty($receipts)) {
                $data['noresi'] = $resi_tampil;

                // Get the first receipt for basic info
                $first_receipt = $receipts[0];
                $data['id_printresi'] = $first_receipt->id_printresi;

                // Collect all SKUs and quantities - handle null values
                $data['items'] = [];
                $total_qty = 0;
                foreach ($receipts as $receipt) {
                    $data['items'][] = [
                        'sku' => $receipt->sku ?? '-',
                        'jumlah' => $receipt->jumlah ?? 0,
                        'no_rak' => $receipt->no_rak ?? '-'
                    ];
                    $total_qty += ($receipt->jumlah ?? 0);
                }

                // For backward compatibility, set first item as main
                $data['sku'] = $first_receipt->sku ?? '-';
                $data['qty'] = $first_receipt->jumlah ?? 0;
                $data['no_rak'] = $first_receipt->no_rak ?? '-';
                $data['total_qty'] = $total_qty;
                $data['total_items'] = count($receipts);

                // Update picker details if found
                if($picker_detail) {
                    $data['nama_picker'] = $picker_detail->nama_pegawai;
                    $data['komputer_picker'] = $picker_detail->nama_komputer;
                }
            } elseif ($resi_tampil !== '') {
                // Handle case where no detail records exist
                $data['noresi'] = $resi_tampil;
                $data['error_message'] = 'No detail records found for this receipt number';
            }
        }

        // Resi yang dipakai perekam video. Diambil dari state double-scan di
        // session -- bukan dari $noresi -- supaya rekaman ikut lanjut sendiri
        // saat halaman dibuka lagi setelah tab packer mati di tengah packing.
        $data['video_noresi'] = $this->resi_untuk_rekaman(
            isset($resi_tampil) ? $resi_tampil : null,
            isset($receipts) ? !empty($receipts) : null
        );

        // Resi yang masih menunggu scan kedua, dipakai view untuk menampilkan
        // bilah "Batal Scan". Selalu dihitung ulang di sini supaya tetap muncul
        // walau halaman dibuka lewat GET (mis. setelah tab packer sempat mati).
        $data['scan_aktif'] = $this->resi_menunggu_scan_kedua();

        $data['akses_ditolak'] = FALSE;

        $this->show($data, 'packer/scan_packer_webcam');
	}

    /**
     * Resi mana yang boleh direkam pada request ini.
     *
     * Sumbernya state double-scan di session, yang bertahan melewati reload
     * maupun tab yang mati. Jadi kalau packer sempat scan resi lalu tabnya
     * tertutup, membuka menu ini lagi langsung melanjutkan rekaman untuk resi
     * itu -- sebagai rekaman baru, karena berkas WebM dari dua sesi
     * MediaRecorder tidak bisa disambung jadi satu. Halaman CS memang
     * menampilkan semua rekaman per resi, jadi keduanya tetap kelihatan.
     */
    private function resi_untuk_rekaman($noresi_post, $receipts_ada)
    {
        $state = $this->session->userdata('packer_double_scan_webcam');

        if (!is_array($state) || empty($state['resi']) || empty($state['count'])) {
            return '';
        }

        // State yang sudah lama tidak disentuh bukan pekerjaan yang sedang
        // berjalan -- jangan sampai membuka halaman besok pagi malah merekam resi
        // kemarin sore. Penyaringannya dikerjakan bersama di
        // bersihkan_scan_kedaluwarsa(), yang sudah jalan lebih dulu di
        // scan_packer(), jadi state yang sampai ke sini pasti masih segar.
        $resi = $state['resi'];

        // Pada request POST detail resinya sudah diambil di scan_packer();
        // tidak perlu query ulang.
        if ($noresi_post !== null && $resi === $noresi_post) {
            return $receipts_ada ? $resi : '';
        }

        // Request GET (halaman dibuka lagi): pastikan dulu resinya punya detail
        // sebelum kamera disuruh merekam.
        return $this->receipt_fcd->get_detail_receipt($resi)->num_rows() > 0 ? $resi : '';
    }

    public function get_scan_packer_data($noresi)
    {
        // Decode the noresi parameter in case it contains special characters
        $noresi = urldecode($noresi);

        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $col = 0;
        $dir = '';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }

        $data['dir'] = $dir;

        // Define valid columns for ordering
        $data['valid_columns'] = [
            0 => null,
            1 => null,
            2 => 's.nama_sku',
            3 => 's.jenis_packing',
            4 => 'dr.sku',
            5 => 'dr.jumlah',
            6 => null
        ];

        $data['order'] = isset($data['valid_columns'][$col]) ? $data['valid_columns'][$col] : null;

        try {
            // Get receipt data with proper null handling
            $data_resi = $this->receipt_fcd->get_receipt_for_packer($data, $noresi);
            $total_data_resi = $this->receipt_fcd->get_total_receipt_for_packer($noresi);

            $table_number = $data['start'] + 1;
            $data_masalah_picker = array();

            // Check if we have any detail records
            if (!empty($data_resi)) {
                foreach ($data_resi as $row_masalah) {
                    $sku = $row_masalah->sku ?? '-';
                    $jumlah = $row_masalah->jumlah ?? 0;
                    $no_rak = $row_masalah->no_rak ?? '-';
                    $nama_barang = $row_masalah->nama_sku ?? '-';
                    $link_foto = foto_sku_url($row_masalah->link_foto ?? ''); // salinan lokal bila ada
                    $yangambil_pegawai = $row_masalah->yangambil_pegawai ?? '';
                    $picker_name = $row_masalah->name ?? $yangambil_pegawai; // Use picker_name if available, fallback to yangambil_pegawai

                    $jenis_packing = $row_masalah->jenis_packing ?? '';
                    if ($jenis_packing !== '') {
                        $packing_display = '<button class="btn btn-xs btn-info" disabled style="cursor: default; opacity: 1 !important; font-weight: bold; background-color: #00c0ef !important; color: #fff !important; border: none; pointer-events: none; padding: 4px 8px;"><i class="fa fa-cube"></i> ' . htmlspecialchars($jenis_packing, ENT_QUOTES, 'UTF-8') . '</button>';
                    } else {
                        $packing_display = '<button class="btn btn-xs btn-warning" disabled style="cursor: default; opacity: 1 !important; font-weight: bold; background-color: #f39c12 !important; color: #fff !important; border: none; pointer-events: none; padding: 4px 8px;"><i class="fa fa-warning"></i> Belum diset</button>';
                    }

                    $data_masalah_picker[] = array(
                        $table_number++ . '.',
                        $link_foto ? '<img src="' . htmlspecialchars($link_foto, ENT_QUOTES, 'UTF-8') . '" style="max-width: 100px; max-height: 100px; cursor: pointer;" class="img-thumbnail foto-preview" data-foto="' . htmlspecialchars($link_foto, ENT_QUOTES, 'UTF-8') . '">' : '<span class="text-muted">No Photo</span>',
                        htmlspecialchars($nama_barang, ENT_QUOTES, 'UTF-8'),
                        $packing_display,
                        htmlspecialchars($sku, ENT_QUOTES, 'UTF-8'),
                        $jumlah,
                        '<div class="text-center">
                            <button 
                                class="btn btn-info saveMasalahPicker" 
                                data-id="' . htmlspecialchars($row_masalah->id_printresi, ENT_QUOTES, 'UTF-8') . '"
                                data-noresi="' . htmlspecialchars($row_masalah->noresi, ENT_QUOTES, 'UTF-8') . '"
                                data-sku="' . htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') . '"
                                data-qty="' . htmlspecialchars($jumlah, ENT_QUOTES, 'UTF-8') . '"
                                data-nama-picker="' . htmlspecialchars($picker_name, ENT_QUOTES, 'UTF-8') . '"
                                data-no-rak="' . htmlspecialchars($no_rak, ENT_QUOTES, 'UTF-8') . '"
                            >Masalah Picker</button><br>
                        </div>'
                    );
                }
            } else {
                // When no detail records exist, return empty data
                // The static table will show "No details" message
                $data_masalah_picker = [];
                $total_data_resi = 0;
            }

            $output = array(
                "draw" => $draw,
                "recordsTotal" => $total_data_resi,
                "recordsFiltered" => $total_data_resi,
                "data" => $data_masalah_picker
            );

            echo json_encode($output);
        } catch (Exception $e) {
            // Return error response for DataTables
            $output = array(
                "draw" => $draw,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "error" => "Error: " . $e->getMessage()
            );
            echo json_encode($output);
            log_message('error', 'Error in get_scan_packer_data: ' . $e->getMessage());
        }

        exit();
    }

	// request by ajax
	public function save_packer()
	{
		if ($this->input->method() == 'get') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

        $noresi = $this->input->post('noresi');
        $status_performa_code = $this->input->post('status_performa');
        $save = $this->process_packer_save($noresi, $status_performa_code);

        // Jalur darurat: packer yang kameranya bermasalah boleh menyelesaikan
        // resi di menu biasa. Kalau resi ini sedang terbuka di siklus webcam
        // (scan pertama sudah masuk di sana), siklusnya ditutup di sini supaya
        // menu webcam tidak menganggapnya masih menunggu scan kedua -- dan
        // tidak menghidupkan lagi rekaman untuk resi yang sudah selesai saat
        // menu webcam dibuka kembali. Rekamannya sendiri sudah ditutup browser
        // begitu halaman webcam ditinggalkan (lihat pantauHalaman() di
        // packer_video.js), jadi yang perlu dibereskan di sini hanya state-nya.
        if (!isset($save['error']) || ($save['data']['EXCEPTION_CODE'] ?? '') === 'ALREADY_PACKED') {
            $this->tutup_siklus_scan(trim((string) $noresi));
        }

		// EXCEPTION_CODE ikut dikirim supaya suara gagalnya bisa dibedakan di
		// layar packer (sudah packing / pesanan cancel / lainnya). Dulu hanya
		// jalur auto-save di scan_packer() yang meneruskannya, sementara endpoint
		// ini membuangnya -- sejak double scan pindah ke sisi client, semua
		// kegagalan lewat sini, jadi kodenya wajib ikut.
		if (isset($save['error'])) {
			$this->make_ajax_response($save['code'], $save['message'], $save['data'] ?? null);
		}

		if ($save['affected_rows'] > 0) {
			// Total scan dikirim balik supaya layar tidak perlu request ketiga
			// hanya untuk memperbarui angka penghitung.
			$packer_scan = $this->packer_fcd->get_total_scan_user($this->data['user']['id_user'])->row();

			$this->make_ajax_response(201, SUCCESS_SAVE_DATA, [
				'total_scan' => $packer_scan ? (int) $packer_scan->total_scan : 0,
			]);
		}

		$this->make_ajax_response(200, NOTHING_TO_SAVE);
	}

	/**
	 * Endpoint simpan (tombol Submit) untuk halaman Scan Resi Packer (Webcam).
	 *
	 * SENGAJA kembaran save_packer(), bukan pemakaian ulang: jalur webcam
	 * terikat pada siklus double-scan di session (jeda minimum, tutup siklus)
	 * yang tidak dimiliki Scan Resi Packer biasa, dan versi biasa dipakai
	 * sebagai cadangan yang tidak boleh berubah. Duplikasi di sini disengaja --
	 * jangan disatukan.
	 */
	public function save_packer_webcam()
	{
		if ($this->input->method() == 'get') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

        $noresi = trim((string) $this->input->post('noresi'));
        $status_performa_code = $this->input->post('status_performa');

        // Tombol Submit menutup resi yang sama dengan yang dibuka scan pertama,
        // jadi aturan jeda minimumnya ikut berlaku di sini. Tanpa penjaga ini
        // scan pertama bisa langsung disusul klik Submit, dan resinya tersimpan
        // dengan video sedetik yang tidak menunjukkan proses packing apa pun --
        // persis yang dicegah di jalur scan kedua.
        $sisa = $this->sisa_jeda_scan($noresi);
        if ($sisa > 0) {
            $this->make_ajax_response(400, 'Packing dulu, baru simpan.', [
                'status'     => 'scan_terlalu_cepat',
                'sisa_detik' => $sisa,
            ]);
        }

        $save = $this->process_packer_save_webcam($noresi, $status_performa_code);

        // Siklus ditutup di sini juga, bukan cuma lewat scan kedua, dan -- sama
        // seperti di sana -- apa pun hasil simpannya. Kalau dilewat, resi yang
        // sudah tersimpan tetap tercatat "menunggu scan kedua": bilahnya
        // bertahan di layar dan semua resi berikutnya ditolak dengan alasan resi
        // ini belum selesai.
        $this->tutup_siklus_scan($noresi);

		if (isset($save['error'])) {
			$this->make_ajax_response($save['code'], $save['message']);
		}

		if ($save['affected_rows'] > 0) {
			$this->make_ajax_response(201, SUCCESS_SAVE_DATA);
		}

		$this->make_ajax_response(200, NOTHING_TO_SAVE);
	}

    public function search_packer()
    {
        $this->show();
    }

    public function get_data_packer()
    {
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $col = 0;
        $dir = '';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }

        $data['dir'] = $dir;

        $data['valid_columns'] = array(
            0 => null,
            1 => 'pr.noresi',
            2 => 'u.name',
            3 => 'p.tanggal_packing',
            4 => 'p.tanggal_packing',
            5 => 'p.keterangan',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->packer_fcd->get_data($data);

        $total = $this->packer_fcd->get_total_data($data);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->noresi,
                $row->nama_pegawai,
                date('Y-m-d', strtotime($row->tanggal_packing)),
                date('H:i:s', strtotime($row->tanggal_packing)),
                $row->keterangan,
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        );
        echo json_encode($output);
        exit();
    }

	// Keepalive endpoint untuk prevent session timeout
	public function keepalive()
	{
		// Simple endpoint to keep session alive
		// Just accessing session data will refresh the session timeout
		if ($this->session->userdata('user')) {
			echo json_encode(['status' => 'alive', 'timestamp' => time()]);
		} else {
			set_status_header(401);
			echo json_encode(['status' => 'expired']);
		}
	}

    /**
     * Catatan: handle_double_scan_state() versi halaman biasa sudah DIHAPUS.
     *
     * Aturan "scan dua kali baru tersimpan" sekarang dijaga di browser
     * (scan_packer.php), bukan lagi di session server. Scan pertama cukup
     * memanggil detail_resi() untuk menampilkan isi paket, scan kedua langsung
     * memanggil save_packer(). Selain memangkas request, ini menutup satu bug
     * lama: state-nya tersimpan per session, jadi dua tab browser milik packer
     * yang sama saling mencuri hitungan scan.
     *
     * handle_double_scan_state_webcam() di bawah SENGAJA dibiarkan utuh --
     * halaman webcam punya jalur simpannya sendiri, lihat catatan di sana.
     */

    /**
     * Terima potongan rekaman video packing dari browser packer.
     *
     * Rekaman dikirim bertahap (MediaRecorder timeslice) alih-alih sekali kirim
     * di akhir, karena dua hal: batas upload_max_filesize/post_max_size bawaan
     * XAMPP cuma 2M/8M -- satu video utuh pasti ditolak -- dan kalau tab packer
     * ketutup di tengah packing, bagian yang sudah naik tetap tersimpan dan
     * tetap bisa diputar.
     *
     * Potongan WebM dari MediaRecorder valid kalau digabung berurutan apa
     * adanya (potongan pertama membawa header), jadi cukup di-append.
     */
    public function upload_video_packing()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $kode_sesi = (string) $this->input->post('kode_sesi');
        $noresi    = trim((string) $this->input->post('noresi'));
        $seq       = (int) $this->input->post('seq');
        $terakhir  = (int) $this->input->post('terakhir') === 1;
        $durasi    = (int) $this->input->post('durasi');

        // Kode sesi ikut jadi nama berkas, jadi harus dikunci ketat supaya tidak
        // bisa dipakai keluar dari folder upload.
        if (!preg_match('/^[A-Za-z0-9]{10,40}$/', $kode_sesi)) {
            $this->make_ajax_response(400, 'Kode sesi rekaman tidak valid.');
        }

        if ($noresi === '') {
            $this->make_ajax_response(400, 'Nomor resi tidak boleh kosong.');
        }

        if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            $kode_error = isset($_FILES['chunk']) ? $_FILES['chunk']['error'] : 'tidak ada berkas';
            $this->make_ajax_response(400, 'Potongan video gagal diterima (' . $kode_error . ').');
        }

        $sesi = $this->video_packing_fcd->get_by_kode_sesi($kode_sesi);

        // Nisan dari batalkan_video_packing(): potongan yang masih di jalan saat
        // pembatalan tidak boleh menghidupkan lagi berkas yang sudah dihapus.
        if ($sesi && $sesi->status === 'DIBATALKAN') {
            $this->make_ajax_response(200, 'Rekaman sudah dibatalkan, potongan diabaikan.');
        }

        if ($sesi) {
            // Sesi yang sudah berjalan: nama dan folder berkasnya sudah
            // ditetapkan di potongan pertama, jangan dihitung ulang.
            $nama   = $sesi->nama_file;
            $folder = (string) $sesi->folder;
            $bagian = isset($sesi->bagian) ? (int) $sesi->bagian : 1;
        } else {
            // Nama berkas mengikuti nomor resinya, bukan kode sesi, dan semuanya
            // rata di satu folder -- CS bisa menemukan videonya lewat nama
            // berkas saja.
            //
            // Bagian 2 dan seterusnya lahir kalau tab packer mati di tengah
            // packing lalu rekamannya dilanjutkan. Berkas WebM dari dua sesi
            // MediaRecorder tidak bisa disambung jadi satu, dan potongan
            // lanjutannya tetap bukti yang dibutuhkan -- jadi disimpan
            // berdampingan dengan nama yang mirip, bukan saling menimpa.
            $bagian = $this->video_packing_fcd->bagian_berikutnya($noresi);
            $nama   = $this->video_packing_fcd->nama_file_untuk($noresi, $bagian);
            $folder = '';

            if ($nama === '') {
                $this->make_ajax_response(400, 'Nomor resi tidak bisa dipakai sebagai nama berkas.');
            }

            // Nomor bagian dilewati kalau namanya sudah dipegang resi lain --
            // lihat nama_dipakai_resi_lain(). Lebih baik ada lompatan nomor
            // daripada satu video menimpa video resi lain.
            while ($bagian <= self::MAKS_BAGIAN_VIDEO
                && $this->video_packing_fcd->nama_dipakai_resi_lain($noresi, $nama)) {
                $bagian++;
                $nama = $this->video_packing_fcd->nama_file_untuk($noresi, $bagian);
            }

            if ($bagian > self::MAKS_BAGIAN_VIDEO) {
                $this->make_ajax_response(400, 'Resi ' . $noresi . ' sudah punya '
                    . self::MAKS_BAGIAN_VIDEO . ' bagian rekaman. Laporkan ke IT.');
            }
        }

        $path = $this->video_packing_fcd->folder_path($folder) . $nama;

        // Potongan pertama menimpa, sisanya menyambung. Kalau baris sesinya
        // belum ada (mis. potongan pertama gagal), berkas dimulai dari sini.
        $mode = ($seq === 0 || !$sesi) ? 0 : FILE_APPEND;
        $isi  = file_get_contents($_FILES['chunk']['tmp_name']);

        if ($isi === FALSE || file_put_contents($path, $isi, $mode) === FALSE) {
            $this->make_ajax_response(500, 'Gagal menulis berkas video ke server.');
        }

        $now = date('Y-m-d H:i:s');

        if (!$sesi) {
            // Packing yang selesai sebelum potongan periodik pertama sempat
            // keluar (kurang dari satu jeda chunk) cuma mengirim satu potongan,
            // dan potongan itu sekaligus penutup sesi -- jadi status finalnya
            // harus ikut diputuskan di sini, bukan cuma di cabang update.
            $this->video_packing_fcd->mulai_sesi([
                'noresi'        => $noresi,
                'kode_sesi'     => $kode_sesi,
                'nama_file'     => $nama,
                'folder'        => $folder,
                'bagian'        => $bagian,
                'mime_type'     => 'video/webm',
                'status'        => $terakhir ? 'SELESAI' : 'MEREKAM',
                'durasi_detik'  => $terakhir && $durasi > 0 ? $durasi : 0,
                'ukuran_byte'   => (int) @filesize($path),
                'id_user'       => $this->data['user']['id_user'],
                'nama_komputer' => isset($this->data['nama_pk']) ? $this->data['nama_pk'] : null,
                'mulai_at'      => $now,
                'selesai_at'    => $terakhir ? $now : null,
            ]);
        } else {
            // Durasi ikut diperbarui di setiap potongan, bukan hanya di potongan
            // penutup. Kalau rekaman terputus (tab ditutup, jaringan mati),
            // barisnya tetap punya durasi yang akurat sampai potongan terakhir
            // yang berhasil naik -- selisihnya paling banyak satu jeda chunk.
            $update = ['ukuran_byte' => (int) @filesize($path)];

            if ($durasi > 0) {
                $update['durasi_detik'] = $durasi;
            }

            if ($terakhir) {
                $update['status']     = 'SELESAI';
                $update['selesai_at'] = $now;
            }

            $this->video_packing_fcd->update_sesi($kode_sesi, $update);
        }

        $this->make_ajax_response(201, SUCCESS_SAVE_DATA, [
            'kode_sesi' => $kode_sesi,
            'seq'       => $seq,
            'ukuran'    => (int) @filesize($path),
            'selesai'   => $terakhir,
        ]);
    }

    /**
     * Resi yang saat ini menunggu scan kedua, atau '' kalau tidak ada.
     */
    private function resi_menunggu_scan_kedua()
    {
        $state = $this->session->userdata('packer_double_scan_webcam');

        if (!is_array($state) || empty($state['resi']) || empty($state['count'])) {
            return '';
        }

        return $state['resi'];
    }

    /**
     * Tutup siklus double-scan yang sudah menggantung terlalu lama.
     *
     * Siklus dihitung sejak scan pertama dan tidak pernah kedaluwarsa sendiri:
     * packer yang scan resi lalu ditarik ke pekerjaan lain akan menemukan
     * siklusnya masih terbuka berjam-jam kemudian, dan -- karena scan resi
     * berbeda ditolak -- seluruh resi lain ikut tertolak sampai dia sadar harus
     * menekan Batal Scan. Menutupnya dengan scan kedua juga bukan jalan keluar:
     * rekamannya sudah dihentikan browser di menit ke-10, jadi yang tersimpan
     * adalah packing dengan video yang tidak menggambarkan pekerjaannya.
     *
     * Ambangnya disamakan dengan batas lanjut-rekam supaya cuma ada satu angka
     * yang menentukan sampai kapan sebuah siklus dianggap masih dikerjakan.
     *
     * @return string Resi yang siklusnya baru saja ditutup, atau '' kalau tidak
     *                ada yang kedaluwarsa.
     */
    private function bersihkan_scan_kedaluwarsa()
    {
        $state = $this->session->userdata('packer_double_scan_webcam');

        if (!is_array($state) || empty($state['resi']) || empty($state['count'])) {
            return '';
        }

        // State tanpa 'ts' berasal dari versi sebelum penanda waktu ada; umurnya
        // tidak bisa dipastikan, jadi diperlakukan sebagai kedaluwarsa.
        $ts = isset($state['ts']) ? (int) $state['ts'] : 0;

        if ($ts > 0 && (time() - $ts) <= self::BATAS_LANJUT_REKAM_DETIK) {
            return '';
        }

        $resi = $state['resi'];
        $this->tutup_siklus_scan(null);

        return $resi;
    }

    /**
     * Kosongkan siklus double-scan.
     *
     * $noresi diisi kalau penutupan harus terikat pada resi tertentu -- dipakai
     * jalur simpan manual, supaya Submit atas resi lain tidak ikut menghapus
     * siklus resi yang sedang dipegang. Isi null untuk menutup apa pun yang
     * sedang terbuka.
     *
     * @return bool TRUE kalau memang ada siklus yang ditutup.
     */
    private function tutup_siklus_scan($noresi)
    {
        $state = $this->session->userdata('packer_double_scan_webcam');

        if (!is_array($state) || empty($state['resi'])) {
            return FALSE;
        }

        if ($noresi !== null && $state['resi'] !== $noresi) {
            return FALSE;
        }

        $this->session->set_userdata('packer_double_scan_webcam', ['resi' => null, 'count' => 0]);

        return TRUE;
    }

    /**
     * Berapa detik lagi resi ini boleh ditutup, dihitung dari scan pertamanya.
     *
     * @return int 0 kalau jeda minimum sudah terlewati, atau memang tidak ada
     *             siklus terbuka atas nama resi ini.
     */
    private function sisa_jeda_scan($noresi)
    {
        $state = $this->session->userdata('packer_double_scan_webcam');

        if (!is_array($state) || empty($state['resi']) || empty($state['count'])) {
            return 0;
        }

        if ($state['resi'] !== $noresi || empty($state['ts_pertama'])) {
            return 0;
        }

        $jeda = microtime(TRUE) - (float) $state['ts_pertama'];

        if ($jeda >= self::MIN_JEDA_SCAN_DETIK) {
            return 0;
        }

        return (int) ceil(self::MIN_JEDA_SCAN_DETIK - $jeda);
    }

    /**
     * Batalkan scan pertama yang sedang menunggu.
     *
     * Dipakai kalau barangnya ternyata kurang atau salah dan penyelesaiannya
     * lama: siklus dikembalikan ke kosong supaya packer bisa mengerjakan resi
     * lain, lalu memulai scan pertama yang benar-benar baru begitu barangnya
     * beres. Rekaman yang sudah telanjur berjalan dibuang oleh sisi browser
     * lewat batalkan_video_packing() -- rekaman setengah jalan itu tidak
     * menunjukkan proses packing, dan satu resi hanya boleh punya satu video.
     */
    public function batal_scan()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $resi  = $this->resi_menunggu_scan_kedua();
        $state = $this->session->userdata('packer_double_scan_webcam');

        $this->tutup_siklus_scan(null);

        if ($resi === '') {
            $this->make_ajax_response(200, 'Tidak ada scan yang perlu dibatalkan.', ['resi' => null]);
        }

        // Durasi ikut dicatat karena itu yang membedakan pembatalan wajar dari
        // yang tidak: barang kurang biasanya ketahuan dalam hitungan detik,
        // sedangkan pembatalan setelah beberapa menit berarti packing-nya sudah
        // dikerjakan dan yang dibuang justru rekamannya.
        $durasi = 0;
        if (is_array($state) && !empty($state['ts_pertama'])) {
            $durasi = max(0, (int) round(microtime(TRUE) - (float) $state['ts_pertama']));
        }

        $this->packer_fcd->catat_batal_scan([
            'noresi'        => $resi,
            'id_user'       => $this->data['user']['id_user'],
            'nama_komputer' => isset($this->data['nama_pk']) ? $this->data['nama_pk'] : null,
            'durasi_detik'  => $durasi,
            'tanggal_batal' => date('Y-m-d H:i:s'),
        ]);

        $this->make_ajax_response(200, 'Scan pertama resi ' . $resi . ' dibatalkan.', ['resi' => $resi]);
    }

    /**
     * Tombol "Lapor Lost Scan Picker" di popup NOT_PICKED halaman webcam.
     *
     * Hanya melaporkan resi ke antrean tim picker (sumber PACKER); packer
     * tidak memilih picker. Siklus scan atas nama resi ini -- kalau ada --
     * ditutup supaya tidak ada rekaman yang menggantung menunggu scan kedua.
     */
    public function lapor_lost_scan_picker()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        if (empty($this->data['user']['hakakses'])
            || !in_array((int) $this->data['user']['hakakses'], self::ROLE_BOLEH_WEBCAM, TRUE)) {
            $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu ini.');
        }

        $noresi = trim((string) $this->input->post('noresi'));
        if ($noresi === '') {
            $this->make_ajax_response(400, 'Nomor resi kosong');
        }

        $this->load->model('lost_scan_picker_fcd');
        $lapor = $this->lost_scan_picker_fcd->lapor($noresi, 'PACKER', $this->data['user']);

        $siklus_ditutup = $this->tutup_siklus_scan($noresi);

        $data = [
            'lapor_picker'   => $lapor['status'],
            'siklus_ditutup' => $siklus_ditutup,
            'antrean_picker' => $lapor['pending'] ? [
                'sumber'       => $lapor['pending']['sumber'],
                'nama_pelapor' => $lapor['pending']['nama_pelapor'],
                'waktu_lapor'  => $lapor['pending']['waktu_lapor'],
            ] : NULL,
        ];

        if ($lapor['status'] === 'DIBUAT') {
            $this->make_ajax_response(201, 'Resi ' . $noresi . ' dilaporkan ke tim picker. Tahan paketnya sampai picker ditentukan.', $data);
        }

        $this->make_ajax_response(400, $lapor['message'], $data);
    }

    /**
     * Tutup resi karena rekamannya sudah mencapai batas durasi.
     *
     * Begitu kamera berhenti, resi tidak boleh dibiarkan menggantung: bilahnya
     * akan terus menunggu scan kedua padahal tidak ada lagi yang direkam, dan
     * scan berikutnya justru membuka siklus baru berikut rekaman bagian kedua.
     * Jadi resinya ditutup di sini juga -- tersimpan sebagai selesai di-packing,
     * dengan video bagian pertama sebagai buktinya. Scan sesudahnya akan ditolak
     * dengan popup "Resi Sudah Di-packing", seperti resi selesai lainnya.
     */
    public function tutup_batas_rekam()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = trim((string) $this->input->post('noresi'));
        $state  = $this->session->userdata('packer_double_scan_webcam');

        $resi_aktif = (is_array($state) && !empty($state['resi']) && !empty($state['count']))
            ? $state['resi'] : null;

        if ($noresi === '' || $resi_aktif === null || $resi_aktif !== $noresi) {
            $this->make_ajax_response(400, 'Tidak ada resi yang sedang dipegang.', [
                'resi' => $noresi, 'tersimpan' => FALSE,
            ]);
        }

        // Umur siklus dipakai sebagai bukti bahwa rekamannya memang sudah
        // berjalan sampai batas. Lihat BATAS_TUTUP_OTOMATIS_DETIK.
        $umur = !empty($state['ts_pertama'])
            ? (microtime(TRUE) - (float) $state['ts_pertama'])
            : 0;

        if ($umur < (self::BATAS_TUTUP_OTOMATIS_DETIK - self::TOLERANSI_TUTUP_DETIK)) {
            $this->make_ajax_response(400,
                'Rekaman resi ' . $noresi . ' belum mencapai batas durasi.', [
                    'resi' => $noresi, 'tersimpan' => FALSE,
                ]);
        }

        $save = $this->process_packer_save_webcam($noresi);

        // Siklus ditutup apa pun hasil simpannya, sama seperti jalur scan kedua.
        $this->tutup_siklus_scan($noresi);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message'], [
                'resi' => $noresi, 'tersimpan' => FALSE,
            ]);
        }

        $this->make_ajax_response(201,
            'Resi ' . $noresi . ' ditutup otomatis karena rekaman mencapai batas durasi.', [
                'resi' => $noresi, 'tersimpan' => TRUE,
            ]);
    }

    /**
     * Buang rekaman yang belum selesai: berkasnya dihapus, barisnya disisakan
     * sebagai nisan berstatus DIBATALKAN.
     *
     * Barisnya sengaja tidak ikut dihapus. Saat pembatalan terjadi masih mungkin
     * ada satu potongan yang sedang di jalan; kalau barisnya hilang, potongan
     * itu akan diperlakukan sebagai sesi baru dan berkasnya hidup lagi. Dengan
     * nisan, potongan susulan tahu harus diabaikan.
     */
    public function batalkan_video_packing()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $kode_sesi = (string) $this->input->post('kode_sesi');
        $noresi    = trim((string) $this->input->post('noresi'));

        if (!preg_match('/^[A-Za-z0-9]{10,40}$/', $kode_sesi)) {
            $this->make_ajax_response(400, 'Kode sesi rekaman tidak valid.');
        }

        $sesi = $this->video_packing_fcd->get_by_kode_sesi($kode_sesi);

        if ($sesi) {
            $path = $this->video_packing_fcd->path_berkas($sesi);
            if (is_file($path)) {
                @unlink($path);
            }

            $this->video_packing_fcd->update_sesi($kode_sesi, [
                'status'      => 'DIBATALKAN',
                'ukuran_byte' => 0,
                'selesai_at'  => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Belum ada potongan yang sempat naik. Nisan tetap dibuat supaya
            // potongan yang mungkin menyusul tidak menghidupkan sesi ini.
            $resi_nisan = $noresi !== '' ? $noresi : '-';

            $this->video_packing_fcd->mulai_sesi([
                'noresi'    => $resi_nisan,
                'kode_sesi' => $kode_sesi,
                'nama_file' => $this->video_packing_fcd->nama_file_untuk($resi_nisan),
                'folder'    => '',
                'status'    => 'DIBATALKAN',
                'id_user'   => $this->data['user']['id_user'],
                'mulai_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        $this->make_ajax_response(200, 'Rekaman dibatalkan.', ['kode_sesi' => $kode_sesi]);
    }

    /**
     * Penolakan karena status sesi packer, atau NULL kalau boleh lanjut.
     *
     * Hanya istirahat dan pulang yang menghalangi. Packer yang belum check-in
     * sengaja tetap dibiarkan bekerja: itu keadaan awal setiap pagi, dan
     * menolaknya berarti satu orang yang lupa menekan tombol tidak bisa packing
     * sama sekali.
     */
    private function tolak_karena_sesi($sesi_status)
    {
        if (!is_array($sesi_status)) {
            return NULL;
        }

        if (!empty($sesi_status['istirahat'])) {
            return [
                'feedback' => [
                    'status'     => 'sesi_tidak_aktif',
                    'type'       => 'error',
                    'message'    => 'Status Anda masih ISTIRAHAT. Tekan tombol "Selesai Istirahat" dulu, '
                        . 'baru scan resi lagi.',
                    'auto_saved' => false,
                ],
                'resi_tampil' => '',
            ];
        }

        if (!empty($sesi_status['pulang'])) {
            return [
                'feedback' => [
                    'status'     => 'sesi_tidak_aktif',
                    'type'       => 'error',
                    'message'    => 'Anda sudah Check-out hari ini, jadi resi baru tidak bisa dibuka. '
                        . 'Hubungi atasan kalau memang masih harus packing.',
                    'auto_saved' => false,
                ],
                'resi_tampil' => '',
            ];
        }

        return NULL;
    }

    /**
     * Pesan penolakan kamera, disesuaikan dengan apa yang dilaporkan browser.
     */
    private function pesan_kamera($kamera_status)
    {
        if ($kamera_status === 'membuka') {
            return 'Kamera masih disiapkan. Tunggu sampai panel kamera bertulisan '
                . '"Kamera siap", lalu scan lagi.';
        }

        if ($kamera_status === 'tidak-didukung') {
            return 'Browser di komputer ini tidak bisa merekam video. Packing di menu ini tidak '
                . 'boleh dimulai tanpa rekaman -- lanjutkan di menu Scan Resi Packer biasa '
                . '(tautannya ada di panel kamera) dan laporkan ke IT.';
        }

        return 'Kamera belum siap, jadi packing belum boleh dimulai. Periksa panel kamera di '
            . 'pojok kanan bawah: izinkan akses kamera, atau -- kalau kameranya rusak -- '
            . 'lanjutkan di menu Scan Resi Packer biasa (tautannya ada di panel kamera) '
            . 'dan laporkan ke IT.';
    }

    /**
     * Ubah penolakan dari model jadi umpan balik layar.
     *
     * Dua alasan lama memakai tampilan yang sudah ada supaya packer tidak perlu
     * menghafal popup baru; sisanya -- pesanan batal, pesanan selesai, belum
     * di-picker -- masuk ke satu popup "tidak bisa dipacking" dengan pesan asli
     * dari model, jadi bunyinya sama persis dengan yang selama ini muncul di
     * scan kedua.
     */
    private function feedback_resi_tidak_layak($noresi, $periksa)
    {
        $kode = isset($periksa['data']['EXCEPTION_CODE']) ? $periksa['data']['EXCEPTION_CODE'] : '';

        if ($kode === 'ALREADY_PACKED') {
            // Siapa dan kapan ikut disebutkan: tanpa itu packer tahu resinya
            // ditolak tapi tidak tahu harus bertanya ke siapa, dan resi yang
            // ternyata di-packing orang lain jadi tidak pernah tertelusuri.
            $info  = $this->packer_fcd->info_packing($noresi);
            $oleh  = ($info && !empty($info->nama_pegawai)) ? $info->nama_pegawai : null;
            $waktu = ($info && !empty($info->tanggal_packing))
                ? date('d/m/Y H:i', strtotime($info->tanggal_packing))
                : null;

            $pesan = 'Resi ' . $noresi . ' sudah selesai di-packing';
            if ($oleh !== null) {
                $pesan .= ' oleh ' . $oleh;
            }
            if ($waktu !== null) {
                $pesan .= ' pada ' . $waktu;
            }
            $pesan .= ' dan sudah punya video. Satu resi hanya boleh dua kali scan.';

            return [
                'feedback' => [
                    'status'          => 'resi_sudah_selesai',
                    'type'            => 'error',
                    'message'         => $pesan,
                    'packer_sebelumnya' => $oleh,
                    'waktu_packing'   => $waktu,
                    'auto_saved'      => false,
                ],
                'resi_tampil' => '',
            ];
        }

        // Resi yang sama sekali tidak ada di tblprintresi ditangani penjaga
        // berikutnya, yang pesannya lebih menolong: kemungkinan besar yang
        // discan memang bukan barcode resi.
        if ($kode === 'NOT_FOUND') {
            return NULL;
        }

        // Belum di-picker: bukan dilaporkan lisan ke CS, tapi lewat tombol
        // "Lapor Lost Scan Picker" di popup (Packer::lapor_lost_scan_picker) ->
        // masuk antrean TIM PICKER -> Laporan Lost Scan Picker. Tim picker yang
        // menentukan picker-nya; baru setelah itu resi ini bisa discan ulang.
        // Siklus scan tidak pernah dibuka untuk resi yang ditolak di sini, jadi
        // tidak ada rekaman video yang dimulai.
        if ($kode === 'NOT_PICKED') {
            $this->load->model('lost_scan_picker_fcd');
            $antrean = $this->lost_scan_picker_fcd->cari_pending($noresi);

            if ($antrean) {
                $pesan = 'Resi ' . $noresi . ' belum di-picker dan SUDAH dilaporkan ke tim picker oleh '
                    . ($antrean['nama_pelapor'] ?: '-') . ' (' . $antrean['sumber'] . ') pada '
                    . date('d/m/Y H:i', strtotime($antrean['waktu_lapor']))
                    . '. Tahan paketnya; tunggu tim picker menentukan picker, lalu scan ulang.';
            } else {
                $pesan = 'Resi ' . $noresi . ' belum di-picker. Jangan dipacking dulu -- '
                    . 'tekan Lapor Lost Scan Picker, tahan paketnya, tunggu tim picker menentukan picker, lalu scan ulang.';
            }

            return [
                'feedback' => [
                    'status'         => 'resi_tidak_layak',
                    'type'           => 'error',
                    'message'        => $pesan,
                    'kode_alasan'    => $kode,
                    'noresi'         => $noresi,
                    'lapor_picker'   => $antrean ? FALSE : TRUE,
                    'antrean_picker' => $antrean ? [
                        'sumber'       => $antrean['sumber'],
                        'nama_pelapor' => $antrean['nama_pelapor'],
                        'waktu_lapor'  => $antrean['waktu_lapor'],
                    ] : NULL,
                    'auto_saved'     => false,
                ],
                'resi_tampil' => '',
            ];
        }

        return [
            'feedback' => [
                'status'      => 'resi_tidak_layak',
                'type'        => 'error',
                'message'     => $periksa['message'] . ' (resi ' . $noresi . '). '
                    . 'Jangan dipacking dulu, laporkan ke CS.',
                'kode_alasan' => $kode,
                'auto_saved'  => false,
            ],
            'resi_tampil' => '',
        ];
    }

    /**
     * Mesin status double-scan: satu resi ditutup oleh dua kali scan.
     *
     * Mengembalikan dua hal:
     *   feedback     -> pesan untuk noty/popup di halaman
     *   resi_tampil  -> resi mana yang detailnya ditampilkan. Tidak selalu sama
     *                   dengan resi yang barusan discan: scan yang ditolak tidak
     *                   boleh menggeser layar dari resi yang sedang dikerjakan.
     *
     * Tidak satu pun penolakan di sini menutup rekaman video yang sedang
     * berjalan -- packer masih berada di tengah pekerjaan yang sama.
     *
     * Semua penjaga baru hanya berlaku pada scan PERTAMA. Scan kedua tidak
     * pernah dihalangi: kalau kameranya mati atau jam istirahat keburu datang di
     * tengah packing, packer harus tetap bisa menutup resi yang sudah dipegang.
     * Menolaknya justru menjebak dia dengan siklus terbuka dan rekaman berjalan.
     *
     * @param string $kamera_status Laporan browser: 'siap', 'membuka', 'gagal',
     *                              atau 'tidak-didukung'.
     * @param array  $sesi_status   Status check-in/istirahat/pulang packer.
     */
    private function handle_double_scan_state_webcam($noresi, $kamera_status = 'siap', $sesi_status = [])
    {
        if (empty($noresi)) {
            return null;
        }

        $state = $this->session->userdata('packer_double_scan_webcam');
        if (!is_array($state)) {
            $state = ['resi' => null, 'count' => 0];
        }

        $resi_aktif = (!empty($state['resi']) && !empty($state['count'])) ? $state['resi'] : null;

        // (1) Resi lain masih menunggu scan kedua. Scan resi baru ditolak, bukan
        // menggantikan yang lama seperti perilaku sebelumnya -- kalau digantikan,
        // resi pertama tertinggal setengah jalan tanpa ada yang memberi tahu.
        if ($resi_aktif !== null && $resi_aktif !== $noresi) {
            return [
                'feedback' => [
                    'status'       => 'scan_beda_resi',
                    'type'         => 'warning',
                    'message'      => 'Resi ' . $resi_aktif . ' belum selesai. Scan resi ' . $resi_aktif
                        . ' sekali lagi untuk menutupnya, atau tekan Batal Scan kalau mau ditinggal dulu.',
                    'resi_aktif'   => $resi_aktif,
                    'resi_ditolak' => $noresi,
                    'auto_saved'   => false,
                ],
                'resi_tampil' => $resi_aktif,
            ];
        }

        // (2) Packer yang sedang istirahat atau sudah pulang tidak boleh memulai
        // resi baru. Statusnya selama ini cuma dipakai menyembunyikan tombol,
        // jadi packing yang dikerjakan di luar jam kerja tetap masuk dan
        // membuat data KPI serta monitoring tidak cocok dengan kenyataannya.
        if ($resi_aktif === null) {
            $tolak_sesi = $this->tolak_karena_sesi($sesi_status);
            if ($tolak_sesi) {
                return $tolak_sesi;
            }
        }

        // (3) Kamera wajib siap sebelum resi baru dibuka. Tanpa penjaga ini satu
        // komputer bisa bekerja seharian tanpa satu pun video -- panel kameranya
        // memang berubah merah, tapi letaknya di pojok dan bisa disembunyikan,
        // jadi tidak ada yang menyadarinya sampai CS mencari videonya.
        if ($resi_aktif === null && $kamera_status !== 'siap') {
            return [
                'feedback' => [
                    'status'        => 'kamera_belum_siap',
                    'type'          => 'error',
                    'message'       => $this->pesan_kamera($kamera_status),
                    'kamera_status' => $kamera_status,
                    'auto_saved'    => false,
                ],
                'resi_tampil' => '',
            ];
        }

        // (4) Resi yang memang tidak boleh dipacking dicegat di scan pertama:
        // sudah di-packing, sudah selesai, dibatalkan, atau belum di-picker.
        // Aturannya diambil dari model, sama persis dengan yang dipakai saat
        // menyimpan -- sebelumnya cuma dipakai di scan kedua, jadi packer baru
        // diberi tahu setelah merekam dan mem-packing barangnya, dan video
        // sampahnya tetap tersimpan.
        if ($resi_aktif === null) {
            $periksa = $this->packer_fcd->periksa_kelayakan_packing($noresi);

            if (isset($periksa['error'])) {
                $tolak = $this->feedback_resi_tidak_layak($noresi, $periksa);
                if ($tolak) {
                    return $tolak;
                }
            }
        }

        // (5) Resi yang tidak punya detail sama sekali tidak boleh membuka
        // siklus. Sejak scan resi berbeda ditolak, satu salah scan -- barcode
        // produk, label rak, apa pun yang bukan resi -- akan mengunci packer:
        // siklusnya terbuka atas nama resi hantu dan semua scan berikutnya
        // ditolak sampai Batal Scan ditekan.
        if ($resi_aktif === null && $this->receipt_fcd->get_detail_receipt($noresi)->num_rows() === 0) {
            return [
                'feedback' => [
                    'status'     => 'resi_tidak_dikenal',
                    'type'       => 'error',
                    'message'    => 'Resi ' . $noresi . ' tidak ditemukan. Pastikan yang discan barcode resi, bukan barcode lain.',
                    'auto_saved' => false,
                ],
                'resi_tampil' => '',
            ];
        }

        // (6) Scan pertama: siklus baru dibuka.
        if ($resi_aktif === null) {
            $this->session->set_userdata('packer_double_scan_webcam', [
                'resi'       => $noresi,
                'count'      => 1,
                // microtime dipakai supaya ambang jeda tidak meleset gara-gara
                // pembulatan detik.
                'ts_pertama' => microtime(TRUE),
                // ts terpisah dan berbasis detik; dibaca resi_untuk_rekaman()
                // untuk menilai apakah siklusnya masih segar.
                'ts'         => time(),
            ]);

            return [
                'feedback' => [
                    'status'     => 'need_second_scan',
                    'type'       => 'warning',
                    'message'    => 'Scan ulang nomor resi ' . $noresi . ' satu kali lagi untuk menyimpan.',
                    'auto_saved' => false,
                ],
                'resi_tampil' => $noresi,
            ];
        }

        // (7) Scan kedua yang datang terlalu cepat dianggap tidak pernah terjadi:
        // hitungan tetap 1 supaya packer tinggal packing lalu scan sekali lagi.
        if (!empty($state['ts_pertama'])) {
            $jeda = microtime(TRUE) - (float) $state['ts_pertama'];

            if ($jeda < self::MIN_JEDA_SCAN_DETIK) {
                $state['ts'] = time();
                $this->session->set_userdata('packer_double_scan_webcam', $state);

                return [
                    'feedback' => [
                        'status'      => 'scan_terlalu_cepat',
                        'type'        => 'warning',
                        'message'     => 'Packing dulu, baru scan lagi. Scan kedua baru diterima minimal '
                            . self::MIN_JEDA_SCAN_DETIK . ' detik setelah scan pertama.',
                        'sisa_detik'  => (int) ceil(self::MIN_JEDA_SCAN_DETIK - $jeda),
                        'auto_saved'  => false,
                    ],
                    'resi_tampil' => $noresi,
                ];
            }
        }

        // Scan kedua yang sah: simpan, lalu tutup siklusnya apa pun hasilnya.
        $save = $this->process_packer_save_webcam($noresi);

        $this->tutup_siklus_scan($noresi);

        if (isset($save['error'])) {
            return [
                'feedback' => [
                    'status'     => 'auto_save_failed',
                    'type'       => 'error',
                    'message'    => $save['message'],
                    // Dipakai view untuk memilih suara gagal (sudah packing /
                    // pesanan cancel / lainnya).
                    'exception_code' => isset($save['data']['EXCEPTION_CODE']) ? $save['data']['EXCEPTION_CODE'] : null,
                    'auto_saved' => false,
                ],
                'resi_tampil' => $noresi,
            ];
        }

        return [
            'feedback' => [
                'status'     => 'auto_save_success',
                'type'       => 'success',
                'message'    => 'Nomor resi ' . $noresi . ' berhasil otomatis disimpan.',
                'auto_saved' => true,
            ],
            'resi_tampil' => $noresi,
        ];
    }

    /**
     * Wrapper to reuse save logic both for ajax endpoint and double scan auto save
     */
    private function process_packer_save($noresi, $status_performa_code = null)
    {
        if (empty($noresi)) {
            return ['error' => true, 'code' => 400, 'message' => 'Nomor resi tidak boleh kosong'];
        }

        $packer = ['noresi' => $noresi];
        $status_id = $this->determine_status_performa_id($status_performa_code);
        if ($status_id) {
            $packer['status_performa_id'] = $status_id;
        }

        return $this->packer_fcd->save($packer, $this->data['user']);
    }

    /**
     * Kembaran process_packer_save() khusus jalur simpan halaman webcam.
     * Lihat catatan di save_packer_webcam(): duplikasinya disengaja supaya
     * penambahan rekam video nanti tidak menyentuh jalur simpan yang biasa.
     */
    private function process_packer_save_webcam($noresi, $status_performa_code = null)
    {
        if (empty($noresi)) {
            return ['error' => true, 'code' => 400, 'message' => 'Nomor resi tidak boleh kosong'];
        }

        $packer = ['noresi' => $noresi];
        $status_id = $this->determine_status_performa_id($status_performa_code);
        if ($status_id) {
            $packer['status_performa_id'] = $status_id;
        }

        return $this->packer_fcd->save($packer, $this->data['user']);
    }

    /**
     * Resolve status performa id by priority
     */
    private function determine_status_performa_id($status_performa_code = null)
    {
        // Prioritas: 1) Status dari parameter, 2) Status session user, 3) Default NORMAL_PACKER
        if (!empty($status_performa_code)) {
            $this->load_kpi_model_if_needed();
            $status_id = $this->kpi_fcd->get_status_id_by_name($status_performa_code);
            if ($status_id) {
                return $status_id;
            }
        }

        $user_status_performa = $this->session->userdata('user_status_performa');
        if ($user_status_performa && isset($user_status_performa->id_statusperforma)) {
            return $user_status_performa->id_statusperforma;
        }

        $this->load_kpi_model_if_needed();
        return $this->kpi_fcd->get_status_id_by_name('NORMAL_PACKER');
    }

    private function load_kpi_model_if_needed()
    {
        if (!isset($this->kpi_fcd)) {
            $this->load->model('kpi_fcd');
        }
    }
}
