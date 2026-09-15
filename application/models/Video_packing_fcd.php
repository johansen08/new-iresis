<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Penyimpanan rekaman video packing.
 *
 * Satu baris tblvideopacking = satu sesi rekam (satu resi, satu kali packing).
 * Berkasnya sendiri ditulis bertahap oleh Packer::upload_video_packing karena
 * browser mengirim rekaman per potongan; model ini cuma mengurus metadatanya.
 */
class Video_packing_fcd extends CI_Model
{
    /**
     * Folder root tempat semua rekaman disimpan, kalau secrets.php tidak
     * menentukan lain (kunci 'video_packing_dir').
     *
     * Sengaja DI LUAR document root: berkasnya besar (±3 MB/menit) dan tidak
     * boleh ikut disalin/di-backup bersama kode, dan supaya tidak bisa diunduh
     * siapa pun yang tahu nomor resinya -- videonya hanya bisa diputar lewat
     * Cs::putar_video_packing() yang memeriksa login. Konsekuensinya Apache
     * tidak bisa menyajikan berkasnya langsung; lihat url_video().
     */
    const ROOT_UPLOAD_DEFAULT = 'C:/video-packing/';

    /**
     * Folder root yang berlaku, selalu berakhiran '/'.
     *
     * Dibaca dari secrets.php supaya folder dev dan produksi di PC yang sama
     * tidak saling menimpa (folder dev diarahkan ke tempat lain).
     */
    public function root_upload()
    {
        if (!function_exists('iresis_secret')) {
            require_once APPPATH . 'config/secrets_load.php';
        }

        $root = (string) iresis_secret('video_packing_dir', self::ROOT_UPLOAD_DEFAULT);
        $root = str_replace('\\', '/', trim($root));

        if ($root === '') {
            $root = self::ROOT_UPLOAD_DEFAULT;
        }

        return rtrim($root, '/') . '/';
    }

    /**
     * Path absolut folder penyimpanan, dibuat kalau belum ada.
     *
     * Rekaman baru semuanya ditaruh langsung di folder root ($folder kosong):
     * satu resi satu berkas bernama nomor resinya, jadi tidak perlu dipisah per
     * tanggal lagi. Baris lama yang folder-nya masih berisi tanggal tetap
     * dilayani apa adanya supaya videonya tidak hilang.
     */
    public function folder_path($folder)
    {
        $path = $this->root_upload() . ($folder === '' || $folder === NULL ? '' : $folder . '/');

        if (!is_dir($path)) {
            mkdir($path, 0777, TRUE);
        }

        return $path;
    }

    /**
     * URL untuk memutar satu rekaman, dipakai sebagai src tag <video>.
     *
     * Bukan URL berkas langsung -- foldernya di luar document root -- melainkan
     * endpoint yang mengalirkan isinya (Cs::putar_video_packing). Kuncinya id
     * baris, bukan nama berkas, supaya path apa pun tidak pernah lewat URL.
     */
    public function url_video($row)
    {
        return base_url('cs/putar-video-packing/' . (int) $row->id_videopacking);
    }

    /** Path absolut berkas satu baris rekaman. */
    public function path_berkas($row)
    {
        return $this->root_upload() . $this->sub_path($row);
    }

    /** Subfolder di bawah root tempat hasil konversi MP4 disimpan. */
    const SUBFOLDER_MP4 = 'mp4';

    /**
     * Nama berkas MP4 untuk satu rekaman: nama WebM-nya dengan ekstensi .mp4.
     * Bagian 2+ ikut terbawa (TESTCAM03-2.webm -> TESTCAM03-2.mp4).
     */
    public function nama_mp4_untuk($row)
    {
        return preg_replace('/\.webm$/i', '', (string) $row->nama_file) . '.mp4';
    }

    /**
     * Path absolut hasil MP4. Dipisah ke subfolder mp4/ supaya folder utama
     * tetap satu resi satu berkas WebM, dan supaya bersih-bersih MP4 (yang bisa
     * dibuat ulang kapan saja) tidak perlu menyentuh rekaman aslinya.
     */
    public function path_mp4($row)
    {
        $nama = !empty($row->mp4_nama_file) ? $row->mp4_nama_file : $this->nama_mp4_untuk($row);

        return $this->root_upload() . self::SUBFOLDER_MP4 . '/' . $nama;
    }

    /** Apakah MP4 untuk baris ini sudah jadi dan berkasnya memang ada. */
    public function mp4_siap($row)
    {
        return isset($row->mp4_status) && $row->mp4_status === 'SIAP' && is_file($this->path_mp4($row));
    }

    /** URL unduh MP4 (Cs::unduh_video_mp4); dipakai tombol di halaman CS. */
    public function url_mp4($row)
    {
        return base_url('cs/unduh-video-mp4/' . (int) $row->id_videopacking);
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('tblvideopacking', ['id_videopacking' => (int) $id])->row();
    }

    private function sub_path($row)
    {
        $folder = isset($row->folder) ? (string) $row->folder : '';

        return ($folder === '' ? '' : $folder . '/') . $row->nama_file;
    }

    /**
     * Nama berkas untuk sebuah resi.
     *
     * Nomor resi ikut jadi nama berkas, jadi harus dikunci sekeras kode sesi
     * dulu: apa pun di luar huruf, angka, titik, strip, dan garis bawah diganti
     * supaya tidak bisa dipakai keluar dari folder upload.
     *
     * Bagian 1 memakai nomor resinya apa adanya. Bagian 2 dan seterusnya diberi
     * akhiran -2, -3, dan seterusnya: namanya sengaja mirip supaya berurutan
     * bersebelahan waktu folder diurutkan, tapi tetap berkas terpisah karena
     * rekaman lanjutan setelah tab mati memang tidak bisa disatukan.
     *
     * @return string Nama berkas, atau '' kalau resinya tidak menyisakan nama
     *                yang aman sama sekali.
     */
    public function nama_file_untuk($noresi, $bagian = 1)
    {
        $bersih = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $noresi);
        $bersih = trim($bersih, '.');
        $bersih = substr($bersih, 0, 100);

        if ($bersih === '' || $bersih === '_') {
            return '';
        }

        $bagian = (int) $bagian;
        if ($bagian > 1) {
            $bersih .= '-' . $bagian;
        }

        return $bersih . '.webm';
    }

    /**
     * Nomor bagian untuk rekaman berikutnya dari sebuah resi.
     *
     * Nisan DIBATALKAN tidak dihitung: berkasnya sudah dibuang, jadi nomornya
     * boleh dipakai ulang dan resi yang batal lalu discan lagi tetap mulai dari
     * bagian 1.
     */
    public function bagian_berikutnya($noresi)
    {
        $row = $this->db
            ->select('MAX(bagian) AS terakhir')
            ->where('noresi', $noresi)
            ->where('status !=', 'DIBATALKAN')
            ->get('tblvideopacking')
            ->row();

        return ($row && $row->terakhir) ? ((int) $row->terakhir + 1) : 1;
    }

    /**
     * Apakah nama berkas ini sudah dipakai resi LAIN.
     *
     * Akhiran bagian memakai karakter yang juga bisa muncul di nomor resi, jadi
     * resi bernama "X-2" menghasilkan nama yang sama dengan bagian 2 milik resi
     * "X". Jarang terjadi, tapi akibatnya video satu resi menimpa video resi
     * lain tanpa jejak -- jadi dicegat, bukan diharapkan tidak terjadi.
     */
    public function nama_dipakai_resi_lain($noresi, $nama_file)
    {
        return (bool) $this->db
            ->where('nama_file', $nama_file)
            ->where('noresi !=', $noresi)
            ->where('status !=', 'DIBATALKAN')
            ->get('tblvideopacking')
            ->row();
    }

    public function get_by_kode_sesi($kode_sesi)
    {
        return $this->db->get_where('tblvideopacking', ['kode_sesi' => $kode_sesi])->row();
    }

    /** Dipanggil saat potongan pertama masuk. */
    public function mulai_sesi($data)
    {
        $this->db->insert('tblvideopacking', $data);
        return $this->db->insert_id();
    }

    public function update_sesi($kode_sesi, $data)
    {
        $this->db->where('kode_sesi', $kode_sesi)->update('tblvideopacking', $data);
        return $this->db->affected_rows();
    }

    /**
     * Rekaman untuk satu resi, diurutkan menurut nomor bagian.
     *
     * Normalnya cuma satu baris. Kalau tab packer sempat mati di tengah packing,
     * isinya beberapa bagian yang harus ditonton berurutan -- karena itu urutannya
     * menaik, bukan terbaru dulu.
     */
    public function get_by_resi($noresi)
    {
        $this->db->select('v.*, u.name AS nama_packer');
        $this->db->from('tblvideopacking v');
        $this->db->join('tbluser u', 'u.id_user = v.id_user', 'left');
        $this->db->where('v.noresi', $noresi);
        // Sesi yang dibatalkan hanya tersisa sebagai nisan tanpa berkas; jangan
        // ikut ditawarkan ke CS.
        $this->db->where('v.status !=', 'DIBATALKAN');
        $this->db->order_by('v.bagian', 'ASC');
        $this->db->order_by('v.mulai_at', 'ASC');

        return $this->db->get()->result();
    }

    // ------------------------------------------------------------ finalisasi

    /**
     * Berapa lama status PROSES boleh bertahan sebelum dianggap ditinggalkan
     * (cron mati di tengah ffmpeg) dan dikembalikan ke antrian. Harus lebih
     * lama dari BATAS_DETIK_TRANSCODE di Video_ffmpeg.
     */
    const PROSES_BASI_MENIT = 90;

    /**
     * Ambil satu rekaman yang belum di-remux dan tandai PROSES -- atomik.
     *
     * Klaimnya lewat UPDATE bersyarat, bukan SELECT lalu UPDATE: cron dijadwalkan
     * tiap menit dan transcode bisa lebih lama dari itu, jadi dua proses cron
     * bisa hidup bersamaan; tanpa ini keduanya me-remux berkas yang sama dan
     * saling menimpa. Hanya rekaman yang sudah selesai/terputus yang diambil --
     * yang masih MEREKAM berkasnya masih terus bertambah.
     *
     * @return object|null Baris yang berhasil diklaim.
     */
    public function klaim_finalisasi()
    {
        $this->lepas_proses_basi('finalisasi', 'BELUM');

        $kandidat = $this->db
            ->select('id_videopacking')
            ->where('finalisasi', 'BELUM')
            ->where_in('status', ['SELESAI', 'TERPUTUS'])
            ->order_by('selesai_at', 'ASC')
            ->order_by('id_videopacking', 'ASC')
            ->limit(1)
            ->get('tblvideopacking')
            ->row();

        if (!$kandidat) {
            return null;
        }

        $this->db
            ->set('finalisasi', 'PROSES')
            ->set('finalisasi_percobaan', 'finalisasi_percobaan + 1', FALSE)
            ->set('finalisasi_at', date('Y-m-d H:i:s'))
            ->where('id_videopacking', $kandidat->id_videopacking)
            ->where('finalisasi', 'BELUM')
            ->update('tblvideopacking');

        return $this->db->affected_rows() > 0 ? $this->get_by_id($kandidat->id_videopacking) : null;
    }

    /**
     * Ambil satu permintaan MP4 yang mengantre dan tandai PROSES -- atomik,
     * alasannya sama dengan klaim_finalisasi(). Rekaman yang remux-nya sedang
     * berjalan dilewati dulu: berkas sumbernya sebentar lagi diganti.
     */
    public function klaim_mp4()
    {
        $this->lepas_proses_basi('mp4_status', 'ANTRI');

        $kandidat = $this->db
            ->select('id_videopacking')
            ->where('mp4_status', 'ANTRI')
            ->where('finalisasi !=', 'PROSES')
            ->where_in('status', ['SELESAI', 'TERPUTUS'])
            ->order_by('mp4_diminta_at', 'ASC')
            ->order_by('id_videopacking', 'ASC')
            ->limit(1)
            ->get('tblvideopacking')
            ->row();

        if (!$kandidat) {
            return null;
        }

        $this->db
            ->set('mp4_status', 'PROSES')
            ->set('mp4_percobaan', 'mp4_percobaan + 1', FALSE)
            ->where('id_videopacking', $kandidat->id_videopacking)
            ->where('mp4_status', 'ANTRI')
            ->update('tblvideopacking');

        return $this->db->affected_rows() > 0 ? $this->get_by_id($kandidat->id_videopacking) : null;
    }

    /** Berapa transcode MP4 yang sedang berjalan (pembatas beban CPU server). */
    public function jumlah_mp4_proses()
    {
        return (int) $this->db->where('mp4_status', 'PROSES')->count_all_results('tblvideopacking');
    }

    /**
     * Kembalikan ke antrian baris yang terlalu lama di PROSES.
     *
     * finalisasi_at diisi saat klaim, jadi ambangnya langsung dari situ. Untuk
     * MP4 tidak ada kolom waktu klaim -- mp4_diminta_at tidak berubah selama
     * proses -- jadi ambangnya dihitung dari waktu permintaan ditambah
     * kelonggaran antrian (permintaan bisa lama menunggu transcode lain).
     */
    private function lepas_proses_basi($kolom, $status_antri)
    {
        if ($kolom === 'finalisasi') {
            $this->db->where('finalisasi_at <', date('Y-m-d H:i:s', time() - (self::PROSES_BASI_MENIT * 60)));
        } else {
            $this->db->where('mp4_diminta_at <', date('Y-m-d H:i:s', time() - (self::PROSES_BASI_MENIT * 120)));
        }

        $this->db->where($kolom, 'PROSES')->update('tblvideopacking', [$kolom => $status_antri]);
    }

    public function update_by_id($id, $data)
    {
        $this->db->where('id_videopacking', (int) $id)->update('tblvideopacking', $data);
        return $this->db->affected_rows();
    }

    /**
     * Catat permintaan MP4 dari CS. Hanya baris TIDAK/GAGAL yang boleh
     * dimasukkan ulang ke antrian; yang sudah ANTRI/PROSES/SIAP dibiarkan.
     *
     * @return bool TRUE kalau baris berhasil dimasukkan ke antrian.
     */
    public function minta_mp4($id, $id_user)
    {
        $this->db
            ->where('id_videopacking', (int) $id)
            ->where_in('mp4_status', ['TIDAK', 'GAGAL'])
            ->update('tblvideopacking', [
                'mp4_status'       => 'ANTRI',
                'mp4_percobaan'    => 0,
                'mp4_pesan'        => NULL,
                'mp4_diminta_at'   => date('Y-m-d H:i:s'),
                'mp4_diminta_oleh' => $id_user ? (int) $id_user : NULL,
            ]);

        return $this->db->affected_rows() > 0;
    }
}
