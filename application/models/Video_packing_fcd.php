<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Penyimpanan video packing untuk menu Scan Resi Packer (Webcam).
 *
 * Model ini SENGAJA berdiri sendiri dan tidak dipakai oleh halaman Scan Resi
 * Packer biasa. Halaman biasa tetap jadi cadangan saat kamera bermasalah, jadi
 * apa pun yang berubah di sini tidak boleh merembet ke sana.
 *
 * Keputusan penyimpanan yang dikunci:
 *   - Satu resi = satu video. Nama berkas persis <noresi>.webm, tanpa sufiks
 *     bagian dan tanpa timestamp. Kalau berkasnya sudah ada, unggahan DITOLAK,
 *     bukan ditimpa -- karena Packer_fcd::save() sudah menolak double packing,
 *     jadi berkas ganda berarti ada anomali yang perlu dilihat, bukan ditutupi.
 *   - Video disimpan DI LUAR webroot (bawaan C:\video-packing). Folder dasarnya
 *     dibaca dari secrets.php supaya bisa pindah ke drive lain cukup dengan
 *     mengubah satu baris, tanpa menyentuh satu baris pun di database.
 *   - Nol kolom baru di database. Yang dipakai kolom tblpacking.video_path yang
 *     sudah ada, diisi NAMA BERKAS saja -- bukan path, bukan huruf drive.
 *     Kalau path ikut tersimpan, memindahkan folder berarti UPDATE massal.
 *
 * Catatan untuk data lama: 2.837 baris dari percobaan Juni-Juli 2026 berisi
 * format lama "assets/videos/packer/<noresi>_<unixtime>.webm" dan berkasnya
 * sudah tidak ada. Baris itu sengaja TIDAK diubah. Pembacanya cukup menganggap
 * nilai yang mengandung "/" sebagai format lama lalu mengabaikannya.
 */
class Video_packing_fcd extends CI_Model
{
	/** Dipakai kalau tb_config_operasional belum punya barisnya. */
	const RESOLUSI_BAWAAN    = '720p';
	const BATAS_MENIT_BAWAAN = 15;

	const KUNCI_RESOLUSI = 'video_resolusi';
	const KUNCI_BATAS    = 'video_batas_menit';

	/** Dipakai kalau secrets.php belum punya key video_packing_path. */
	const FOLDER_BAWAAN = 'C:/video-packing';

	/** Batas atas ukuran unggahan, sebagai pagar terakhir (byte). */
	const MAKS_UKURAN = 600000000; // 600 MB

	private $resolusi_sah = ['720p', '1080p'];

	public function __construct()
	{
		parent::__construct();
		require_once APPPATH . 'config/secrets_load.php';
	}

	// ── Lokasi berkas ────────────────────────────────────────────────────

	/**
	 * Folder dasar penyimpanan video. Dibaca dari secrets.php (gitignored),
	 * jadi folder dev dan produksi otomatis berbeda tanpa percabangan kode.
	 */
	public function folder()
	{
		$folder = iresis_secret('video_packing_path', self::FOLDER_BAWAAN);

		return rtrim(str_replace('\\', '/', trim((string) $folder)), '/');
	}

	/**
	 * Nomor resi datang dari hasil scan, lalu langsung dipakai jadi nama
	 * berkas. Tanpa daftar-putih ini ada celah path traversal ("../").
	 * Karakter di luar daftar bukan disamarkan tapi membuat resi ditolak --
	 * lebih baik gagal terang-terangan daripada menyimpan ke nama yang salah.
	 */
	public function sanitasi_noresi($noresi)
	{
		$noresi = trim((string) $noresi);

		if ($noresi === '' || strlen($noresi) > 100) {
			return FALSE;
		}

		return preg_match('/^[A-Za-z0-9_-]+$/', $noresi) ? $noresi : FALSE;
	}

	public function nama_berkas($noresi)
	{
		return $noresi . '.webm';
	}

	public function path_berkas($noresi)
	{
		return $this->folder() . '/' . $this->nama_berkas($noresi);
	}

	/**
	 * Memastikan folder ada dan bisa ditulisi. Folder di root C: sering tidak
	 * bisa dibuat oleh proses Apache, jadi kegagalannya dilaporkan apa adanya
	 * supaya ketahuan saat pemasangan, bukan saat packer sedang bekerja.
	 */
	public function folder_siap()
	{
		$folder = $this->folder();

		if (!is_dir($folder) && !@mkdir($folder, 0777, TRUE)) {
			return ['error' => TRUE, 'message' => 'Folder video tidak bisa dibuat: ' . $folder];
		}

		if (!is_writable($folder)) {
			return ['error' => TRUE, 'message' => 'Folder video tidak bisa ditulisi: ' . $folder];
		}

		return ['error' => FALSE, 'folder' => $folder];
	}

	// ── Setelan (tb_config_operasional, nol kolom baru) ──────────────────

	/**
	 * Setelan dibaca dengan pola yang sudah dipakai di Laporan.php: ambil dari
	 * tb_config_operasional, jatuh ke nilai bawaan kalau barisnya belum ada.
	 * Efeknya tidak perlu ada migrasi sama sekali -- barisnya baru lahir kalau
	 * webmaster benar-benar mengubah setelannya.
	 */
	public function ambil_setelan()
	{
		$resolusi = $this->baca_config(self::KUNCI_RESOLUSI);
		$batas    = $this->baca_config(self::KUNCI_BATAS);

		if (!in_array($resolusi, $this->resolusi_sah, TRUE)) {
			$resolusi = self::RESOLUSI_BAWAAN;
		}

		$batas = (int) $batas;
		if ($batas < 1 || $batas > 120) {
			$batas = self::BATAS_MENIT_BAWAAN;
		}

		return ['resolusi' => $resolusi, 'batas_menit' => $batas];
	}

	public function simpan_setelan($resolusi, $batas_menit)
	{
		if (!in_array($resolusi, $this->resolusi_sah, TRUE)) {
			return ['error' => TRUE, 'code' => 400, 'message' => 'Resolusi harus 720p atau 1080p'];
		}

		$batas_menit = (int) $batas_menit;
		if ($batas_menit < 1 || $batas_menit > 120) {
			return ['error' => TRUE, 'code' => 400, 'message' => 'Batas rekam harus antara 1 dan 120 menit'];
		}

		$this->tulis_config(self::KUNCI_RESOLUSI, $resolusi, 'Resolusi rekam video packing (720p / 1080p)');
		$this->tulis_config(self::KUNCI_BATAS, (string) $batas_menit, 'Batas menit rekam video packing sebelum dipotong');

		return ['error' => FALSE, 'resolusi' => $resolusi, 'batas_menit' => $batas_menit];
	}

	private function baca_config($kunci)
	{
		$row = $this->db->get_where('tb_config_operasional', ['kunci' => $kunci])->row();

		return $row ? $row->nilai : NULL;
	}

	/**
	 * Upsert. Laporan_fcd::save_config() yang sudah ada hanya UPDATE, jadi
	 * simpanan pertama akan gagal diam-diam selama barisnya belum ada.
	 */
	private function tulis_config($kunci, $nilai, $keterangan)
	{
		$ada = $this->db->get_where('tb_config_operasional', ['kunci' => $kunci])->row();

		if ($ada) {
			$this->db->where('kunci', $kunci)->update('tb_config_operasional', ['nilai' => $nilai]);
			return;
		}

		$this->db->insert('tb_config_operasional', [
			'kunci'      => $kunci,
			'nilai'      => $nilai,
			'keterangan' => $keterangan,
		]);
	}

	// ── Simpan video ─────────────────────────────────────────────────────

	/**
	 * Memindahkan berkas unggahan ke folder video lalu mencatat nama berkasnya
	 * di tblpacking.video_path.
	 *
	 * Urutannya sengaja: berkas dipindah DULU, baru database dicatat. Kalau
	 * pencatatan gagal, berkasnya tetap ada dan bisa dicocokkan ulang dari nama
	 * resi -- kebalikannya (baris tercatat tapi berkas tidak ada) jauh lebih
	 * menyesatkan saat video dicari untuk sengketa.
	 */
	public function simpan_video($noresi_mentah, $berkas)
	{
		$noresi = $this->sanitasi_noresi($noresi_mentah);
		if ($noresi === FALSE) {
			return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi tidak valid untuk nama berkas'];
		}

		if (empty($berkas['tmp_name']) || !is_uploaded_file($berkas['tmp_name'])) {
			return ['error' => TRUE, 'code' => 400, 'message' => 'Berkas video tidak diterima server'];
		}

		if ($berkas['size'] <= 0 || $berkas['size'] > self::MAKS_UKURAN) {
			return ['error' => TRUE, 'code' => 400, 'message' => 'Ukuran video tidak wajar: ' . $berkas['size'] . ' byte'];
		}

		// Video hanya diterima untuk resi yang memang sudah tercatat di-packing.
		// Tanpa pemeriksaan ini endpoint-nya bisa dipakai menaruh berkas
		// sembarangan di folder video.
		$receipt = $this->db
			->select('id_printresi')
			->get_where('tblprintresi', ['noresi' => $noresi])
			->row();

		if (empty($receipt)) {
			return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi tidak ditemukan'];
		}

		$packing = $this->db
			->select('id_packing')
			->get_where('tblpacking', ['id_resi' => $receipt->id_printresi])
			->row();

		if (empty($packing)) {
			return ['error' => TRUE, 'code' => 400, 'message' => 'Resi ini belum tercatat di-packing, video tidak disimpan'];
		}

		$siap = $this->folder_siap();
		if ($siap['error']) {
			return ['error' => TRUE, 'code' => 500, 'message' => $siap['message']];
		}

		$tujuan = $this->path_berkas($noresi);

		// Satu resi satu video: berkas yang sudah ada tidak pernah ditimpa.
		if (file_exists($tujuan)) {
			return ['error' => TRUE, 'code' => 409, 'message' => 'Video untuk resi ini sudah ada, unggahan diabaikan'];
		}

		if (!@move_uploaded_file($berkas['tmp_name'], $tujuan)) {
			return ['error' => TRUE, 'code' => 500, 'message' => 'Gagal memindahkan berkas video ke ' . $tujuan];
		}

		@chmod($tujuan, 0666);

		// Pencatatan ke database: matikan db_debug sementara supaya kegagalan
		// query tidak mencetak halaman HTML CI yang merusak JSON respons.
		$debug_lama = $this->db->db_debug;
		$this->db->db_debug = FALSE;

		$this->db
			->where('id_packing', $packing->id_packing)
			->update('tblpacking', ['video_path' => $this->nama_berkas($noresi)]);

		$tercatat = ($this->db->affected_rows() > 0);
		$this->db->db_debug = $debug_lama;

		return [
			'error'       => FALSE,
			'nama_berkas' => $this->nama_berkas($noresi),
			'ukuran_byte' => filesize($tujuan),
			'tercatat'    => $tercatat,
		];
	}
}
