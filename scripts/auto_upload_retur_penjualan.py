"""
Auto Upload Daftar Retur Penjualan (Jubelio -> IRESIS), TANPA BROWSER
=====================================================================
Dijalankan Windows Task Scheduler 06.00, 12.00, 20.00
(pembungkus: scripts/retur_penjualan_senyap.vbs, pendaftar: scripts/daftar_task_retur_penjualan.ps1).

Alur (hasil sniff 24 Nov 2026, tanpa Chrome):
  1. POST open.jubelio.com/core-api/login                      -> token JWT
  2. GET  open.jubelio.com/core-api/reports/customer-retur/date-range/
          ?date_from=1 Jan tahun berjalan &date_to=hari ini 23:59:59 &tz=Asia/Bangkok
          -> {"status":"ok","url":"https://report-prod.jubelio.com/?&token=T"}
  3. GET  https://report-prod.jubelio.com/xlsx/?&token=T       -> file "daftar retur penjualan.xlsx"
          (URL yang sama dengan langkah 2, hanya path "/xlsx/"; token T cukup, tanpa header login)
  4. POST <IRESIS>/cron/auto_upload_retur_jubelio?token=...    (multipart, field jubelioFile)
          -> parse + upsert tblreturjubelio (logika sama dengan upload manual Retur::upload_jubelio)

CATATAN HASIL: setiap run SELALU menulis
  logs/auto_upload_retur_penjualan.log      log rinci (apa yang dikerjakan, tahap demi tahap)
  logs/riwayat_retur_penjualan.csv          SATU BARIS per run: waktu, BERHASIL/GAGAL, tahap gagal,
                                            alasan, saran perbaikan, ringkasan hasil
Run gagal -> exit code 1 (terlihat di kolom "Last Run Result" Task Scheduler), tahap + alasan + saran
perbaikan ada di kedua berkas itu. Panduan baca log: docs/AUTO_UPLOAD_RETUR_PENJUALAN.md.

Pakai manual:  python scripts/auto_upload_retur_penjualan.py
               python scripts/auto_upload_retur_penjualan.py --tanpa-upload   (hanya unduh, uji jalur Jubelio)
"""

import argparse
import csv
import json
import os
import re
import sys
import time
import traceback
from datetime import datetime

import requests

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

ROOT = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..'))
LOG_DIR = os.path.join(ROOT, 'logs')
LOG_FILE = os.path.join(LOG_DIR, 'auto_upload_retur_penjualan.log')
RIWAYAT_CSV = os.path.join(LOG_DIR, 'riwayat_retur_penjualan.csv')
TMP_DIR = os.path.join(ROOT, 'scratch', 'retur_penjualan')   # scratch/ di-gitignore
LOCK_FILE = os.path.join(TMP_DIR, 'berjalan.lock')

CORE_API = 'https://open.jubelio.com/core-api'
REPORT_HOST = 'https://report-prod.jubelio.com'
UA = ('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) '
      'Chrome/157.0.0.0 Safari/537.36')

UNDUH_MAKS_COBA = 3
UNDUH_TIMEOUT_BACA = 600        # detik tanpa data (Jubelio generate 12 MB bisa puluhan detik)
UPLOAD_TIMEOUT = 1800           # parse + insert ribuan baris di PHP
LOCK_USANG_DETIK = 3 * 3600     # lock lebih tua dari ini dianggap sisa run yang mati
FILE_MIN_BYTE = 5 * 1024        # xlsx lebih kecil dari ini pasti bukan laporan utuh

HARI = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

RUN_ID = datetime.now().strftime('%Y%m%d-%H%M%S')


# --------------------------------------------------------------------------- log

class TahapGagal(Exception):
    """Gagal di satu tahap, membawa penyebab dan saran perbaikan untuk riwayat."""

    def __init__(self, tahap, alasan, saran=''):
        super().__init__(alasan)
        self.tahap = tahap
        self.alasan = alasan
        self.saran = saran


def sensor(teks):
    """Buang token/password dari teks sebelum masuk log."""
    teks = str(teks)
    teks = re.sub(r'(token=)[^&\s"\']+', r'\1<disensor>', teks)
    teks = re.sub(r'eyJ[A-Za-z0-9_\-]{10,}\.[A-Za-z0-9_\-]{10,}\.[A-Za-z0-9_\-]{5,}', '<jwt-disensor>', teks)
    return teks


def log(pesan):
    baris = '[%s] [%s] %s' % (datetime.now().strftime('%Y-%m-%d %H:%M:%S'), RUN_ID, sensor(pesan))
    try:
        print(baris)
    except UnicodeEncodeError:
        print(baris.encode('ascii', 'replace').decode())
    os.makedirs(LOG_DIR, exist_ok=True)
    with open(LOG_FILE, 'a', encoding='utf-8') as f:
        f.write(baris + '\n')


def catat_riwayat(mulai, status, tahap, alasan, saran, ringkasan, nama_file='', ukuran=0):
    baru = not os.path.exists(RIWAYAT_CSV)
    os.makedirs(LOG_DIR, exist_ok=True)
    with open(RIWAYAT_CSV, 'a', encoding='utf-8-sig', newline='') as f:
        w = csv.writer(f)
        if baru:
            w.writerow(['run_id', 'mulai', 'selesai', 'durasi_detik', 'status', 'tahap_gagal',
                        'alasan', 'saran_perbaikan', 'ringkasan', 'file', 'ukuran_byte'])
        selesai = datetime.now()
        w.writerow([RUN_ID, mulai.strftime('%Y-%m-%d %H:%M:%S'), selesai.strftime('%Y-%m-%d %H:%M:%S'),
                    int((selesai - mulai).total_seconds()), status, tahap,
                    sensor(alasan), saran, sensor(ringkasan), nama_file, ukuran])


# --------------------------------------------------------------------------- kredensial

def muat_kredensial():
    try:
        from jubelio_credentials import EMAIL, PASSWORD
    except Exception as e:
        raise TahapGagal(
            'PERSIAPAN', 'Kredensial Jubelio tidak terbaca: %s' % e,
            "Isi 'jubelio_email' & 'jubelio_password' di application/config/secrets.php "
            "(dibaca scripts/jubelio_credentials.py).")
    try:
        from secrets_local import IRESIS_CRON_TOKEN, IRESIS_BASE_URL
    except Exception as e:
        raise TahapGagal(
            'PERSIAPAN', 'scripts/secrets_local.py tidak lengkap: %s' % e,
            'Isi IRESIS_CRON_TOKEN dan IRESIS_BASE_URL di scripts/secrets_local.py '
            '(contoh: secrets_local.example.py).')
    return EMAIL, PASSWORD, IRESIS_CRON_TOKEN, IRESIS_BASE_URL


# --------------------------------------------------------------------------- tahap 1-3 (Jubelio)

def tgl_jubelio(dt):
    """Format yang dikirim front-end Jubelio: 'Thu Jan 01 2026 00:00:00 GMT+0700 (Indochina Time)'."""
    return '%s %s %02d %d %s GMT+0700 (Indochina Time)' % (
        HARI[dt.weekday()], BULAN[dt.month - 1], dt.day, dt.year, dt.strftime('%H:%M:%S'))


def login(email, password):
    s = requests.Session()
    s.headers.update({
        'accept': 'application/json', 'content-type': 'application/json', 'user-agent': UA,
        'origin': 'https://v2.jubelio.com', 'referer': 'https://v2.jubelio.com/',
    })
    try:
        r = s.post(CORE_API + '/login', json={'email': email, 'password': password}, timeout=30)
    except requests.RequestException as e:
        raise TahapGagal('LOGIN', 'Tidak bisa menghubungi Jubelio (%s: %s)' % (type(e).__name__, e),
                         'Cek koneksi internet PC ini / apakah open.jubelio.com bisa dibuka di browser.')
    if r.status_code in (400, 401, 403):
        raise TahapGagal('LOGIN', 'Login Jubelio ditolak HTTP %d: %s' % (r.status_code, r.text[:200]),
                         'Password Jubelio kemungkinan diganti / akun dikunci / ada verifikasi tambahan. '
                         "Coba login manual di v2.jubelio.com lalu perbarui 'jubelio_password' di secrets.php.")
    if r.status_code != 200:
        raise TahapGagal('LOGIN', 'Login Jubelio HTTP %d: %s' % (r.status_code, r.text[:200]),
                         'Jika 429/5xx: gangguan/limit sementara di Jubelio, tunggu jadwal berikutnya.')
    try:
        token = r.json().get('token')
    except ValueError:
        token = None
    if not token:
        raise TahapGagal('LOGIN', 'Respons login tidak berisi token: %s' % r.text[:200],
                         'Format respons login Jubelio mungkin berubah; sniff ulang alur login.')
    s.headers['authorization'] = token
    return s


def ambil_url_laporan(s, dari, sampai):
    params = {'date_from': tgl_jubelio(dari), 'date_to': tgl_jubelio(sampai), 'tz': 'Asia/Bangkok'}
    for coba in range(1, 6):
        try:
            r = s.get(CORE_API + '/reports/customer-retur/date-range/', params=params, timeout=60)
        except requests.RequestException as e:
            raise TahapGagal('URL_LAPORAN', 'Gagal menghubungi Jubelio (%s: %s)' % (type(e).__name__, e),
                             'Cek koneksi internet PC ini.')
        if r.status_code == 429:           # endpoint ini terkena rate limit (tercatat di dokumentasi lama)
            tunggu = 10 * coba
            log('  429 dari date-range, tunggu %ds (coba %d/5)' % (tunggu, coba))
            time.sleep(tunggu)
            continue
        break
    else:
        raise TahapGagal('URL_LAPORAN', 'Jubelio terus membalas 429 (rate limit) setelah 5 kali coba',
                         'Tunggu jadwal berikutnya; jangan jalankan manual berulang-ulang.')
    if r.status_code in (401, 403):
        raise TahapGagal('URL_LAPORAN', 'Token login ditolak HTTP %d' % r.status_code,
                         'Akun Jubelio mungkin tidak punya akses menu Laporan > Daftar Retur Penjualan.')
    if r.status_code != 200:
        raise TahapGagal('URL_LAPORAN', 'HTTP %d: %s' % (r.status_code, r.text[:300]),
                         'Endpoint laporan berubah? Sniff ulang (menu Laporan > Daftar Retur Penjualan > Cetak).')
    try:
        data = r.json()
    except ValueError:
        raise TahapGagal('URL_LAPORAN', 'Respons bukan JSON: %s' % r.text[:300],
                         'Endpoint date-range berubah; sniff ulang.')
    url = data.get('url') if isinstance(data, dict) else None
    if data.get('status') != 'ok' or not url or 'token=' not in url:
        raise TahapGagal('URL_LAPORAN', 'Respons tak terduga: %s' % json.dumps(data)[:300],
                         'Struktur respons berubah (diharapkan {"status":"ok","url":"...token=..."}).')
    return url


def url_xlsx(url_viewer):
    """https://report-prod.jubelio.com/?&token=T  ->  https://report-prod.jubelio.com/xlsx/?&token=T"""
    token = re.search(r'token=([^&]+)', url_viewer).group(1)
    return '%s/xlsx/?&token=%s' % (REPORT_HOST, token)


def unduh_xlsx(url, tujuan):
    """Unduh streaming ke file; coba ulang untuk gangguan sementara. Mengembalikan nama file asli."""
    kesalahan = ''
    for coba in range(1, UNDUH_MAKS_COBA + 1):
        t0 = time.time()
        try:
            with requests.get(url, headers={'user-agent': UA, 'referer': 'https://v2.jubelio.com/'},
                              stream=True, timeout=(30, UNDUH_TIMEOUT_BACA)) as r:
                if r.status_code != 200:
                    isi = r.text[:300] if r.status_code != 200 else ''
                    kesalahan = 'HTTP %d: %s' % (r.status_code, isi)
                    if r.status_code in (400, 401, 403, 404):
                        # token/URL salah tidak akan membaik dengan diulang
                        raise TahapGagal(
                            'UNDUH', 'Report server menolak unduhan %s' % kesalahan,
                            'Token laporan ditolak/kedaluwarsa atau jalur /xlsx/ berubah. Sniff ulang '
                            'tombol Cetak > Excel di Daftar Retur Penjualan dan bandingkan URL-nya.')
                    raise requests.RequestException(kesalahan)
                ctype = r.headers.get('content-type', '')
                if 'spreadsheet' not in ctype and 'octet-stream' not in ctype:
                    raise TahapGagal('UNDUH', 'Content-Type bukan xlsx: %s; awal isi: %s' % (ctype, r.text[:300]),
                                     'Report server membalas halaman lain (error/login). Sniff ulang alurnya.')
                nama = re.search(r'filename="?([^";]+)', r.headers.get('content-disposition', ''))
                total = 0
                with open(tujuan, 'wb') as f:
                    for potongan in r.iter_content(1024 * 256):
                        f.write(potongan)
                        total += len(potongan)
                diharap = int(r.headers.get('content-length') or 0)
                if diharap and total != diharap:
                    raise requests.RequestException('terpotong: terunduh %d dari %d byte' % (total, diharap))
            log('  Terunduh %d byte dalam %.0f detik (percobaan %d)' % (total, time.time() - t0, coba))
            return nama.group(1) if nama else os.path.basename(tujuan)
        except TahapGagal:
            raise
        except requests.RequestException as e:
            kesalahan = '%s: %s' % (type(e).__name__, e)
            log('  Unduh gagal percobaan %d/%d: %s' % (coba, UNDUH_MAKS_COBA, kesalahan))
            time.sleep(15 * coba)
    raise TahapGagal('UNDUH', 'Unduh gagal %d kali berturut-turut; terakhir: %s' % (UNDUH_MAKS_COBA, kesalahan),
                     'Koneksi putus/Jubelio lambat. Kalau terjadi di semua jadwal, cek internet PC & status Jubelio.')


def cek_file(path):
    ukuran = os.path.getsize(path)
    if ukuran < FILE_MIN_BYTE:
        with open(path, 'rb') as f:
            awal = f.read(300)
        raise TahapGagal('VALIDASI_FILE', 'File terlalu kecil (%d byte): %r' % (ukuran, awal),
                         'Laporan kosong atau berisi pesan error. Cek ada/tidaknya retur di Jubelio.')
    with open(path, 'rb') as f:
        if f.read(2) != b'PK':
            raise TahapGagal('VALIDASI_FILE', 'File bukan xlsx (tanda zip "PK" tidak ada)',
                             'Report server membalas bukan Excel; buka berkas di scratch/retur_penjualan/ untuk melihat isinya.')
    return ukuran


# --------------------------------------------------------------------------- tahap 4 (IRESIS)

def ambil_json(teks):
    """Endpoint Cron mencetak baris log ('[auto_upload_retur_jubelio] OK: ...') sebelum JSON-nya, jadi
    ambil objek JSON dari baris terakhir yang berbentuk {...}."""
    for baris in reversed(teks.strip().splitlines()):
        baris = baris.strip()
        if baris.startswith('{') and baris.endswith('}'):
            try:
                return json.loads(baris)
            except ValueError:
                continue
    return None


def upload_ke_iresis(path, base_url, token):
    url = '%scron/auto_upload_retur_jubelio' % (base_url if base_url.endswith('/') else base_url + '/')
    try:
        with open(path, 'rb') as f:
            r = requests.post(url, params={'token': token},
                              files={'jubelioFile': (os.path.basename(path), f,
                                     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')},
                              timeout=UPLOAD_TIMEOUT)
    except requests.ConnectionError as e:
        raise TahapGagal('UPLOAD', 'Tidak bisa terhubung ke IRESIS %s (%s)' % (url, e),
                         'Apache/XAMPP mati? Cek IRESIS_BASE_URL di scripts/secrets_local.py dan buka alamatnya di browser.')
    except requests.RequestException as e:
        raise TahapGagal('UPLOAD', 'Upload ke IRESIS error (%s: %s)' % (type(e).__name__, e),
                         'Timeout: parsing/insert di PHP lama. Cek logs/ aplikasi & memory_limit PHP.')
    if r.status_code == 403:
        raise TahapGagal('UPLOAD', 'IRESIS menolak (HTTP 403)',
                         'IRESIS_CRON_TOKEN di scripts/secrets_local.py harus sama dengan cron_token di secrets.php.')
    hasil = ambil_json(r.text)
    if hasil is None:
        potongan = r.text.strip()[:500]
        saran = 'Respons bukan JSON, ada output PHP nyasar (warning/error). Baca isinya di kolom alasan.'
        if r.status_code == 413 or 'post_max_size' in potongan.lower() or 'exceeds' in potongan.lower():
            saran = 'File melebihi batas PHP: naikkan upload_max_filesize & post_max_size di php.ini lalu restart Apache.'
        raise TahapGagal('UPLOAD', 'HTTP %d, respons bukan JSON: %s' % (r.status_code, potongan or '(kosong)'), saran)
    if not hasil.get('success'):
        pesan = hasil.get('message', '(tanpa pesan)')
        saran = 'Lihat pesan dari IRESIS. '
        if 'File tidak ditemukan' in pesan:
            saran += 'File tidak sampai ke PHP: cek upload_max_filesize/post_max_size di php.ini (file ~13 MB).'
        elif 'Tidak ada baris data valid' in pesan:
            saran += 'Header kolom xlsx Jubelio mungkin berubah; bandingkan dengan Retur_fcd::parse_jubelio_spreadsheet().'
        else:
            saran += 'Cek logs/ aplikasi untuk detail error PHP/DB.'
        raise TahapGagal('UPLOAD', 'IRESIS menjawab gagal: %s' % pesan, saran)
    return hasil


# --------------------------------------------------------------------------- lock & bersih-bersih

def ambil_lock():
    os.makedirs(TMP_DIR, exist_ok=True)
    if os.path.exists(LOCK_FILE):
        umur = time.time() - os.path.getmtime(LOCK_FILE)
        if umur < LOCK_USANG_DETIK:
            raise TahapGagal('PERSIAPAN', 'Run sebelumnya masih berjalan (lock %.0f menit)' % (umur / 60),
                             'Tunggu selesai. Kalau yakin tidak ada proses python jalan, hapus scratch/retur_penjualan/berjalan.lock.')
        log('Lock usang (%.0f menit) diabaikan.' % (umur / 60))
    with open(LOCK_FILE, 'w') as f:
        f.write(RUN_ID)


def lepas_lock():
    try:
        os.remove(LOCK_FILE)
    except OSError:
        pass


def bersihkan_file_lama(hari=3):
    batas = time.time() - hari * 86400
    for nama in os.listdir(TMP_DIR):
        p = os.path.join(TMP_DIR, nama)
        if nama.endswith('.xlsx') and os.path.getmtime(p) < batas:
            try:
                os.remove(p)
            except OSError:
                pass


# --------------------------------------------------------------------------- utama

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--tanpa-upload', action='store_true', help='hanya unduh & validasi, tidak kirim ke IRESIS')
    args = ap.parse_args()

    mulai = datetime.now()
    log('=== AUTO UPLOAD DAFTAR RETUR PENJUALAN (mulai) ===')
    status, tahap, alasan, saran, ringkasan, nama, ukuran = 'BERHASIL', '', '', '', '', '', 0
    punya_lock = False
    path = ''
    try:
        ambil_lock()
        punya_lock = True
        bersihkan_file_lama()

        email, password, token_cron, base_url = muat_kredensial()

        dari = datetime(mulai.year, 1, 1, 0, 0, 0)
        sampai = mulai.replace(hour=23, minute=59, second=59, microsecond=0)
        log('Rentang laporan: %s s/d %s' % (dari.strftime('%Y-%m-%d'), sampai.strftime('%Y-%m-%d')))

        log('[1/4] Login Jubelio...')
        s = login(email, password)
        log('[2/4] Minta URL laporan Daftar Retur...')
        viewer = ambil_url_laporan(s, dari, sampai)
        path = os.path.join(TMP_DIR, 'retur_penjualan_%s.xlsx' % RUN_ID)
        log('[3/4] Unduh xlsx...')
        nama = unduh_xlsx(url_xlsx(viewer), path)
        ukuran = cek_file(path)
        log('  File valid: %s (%d byte)' % (nama, ukuran))

        if args.tanpa_upload:
            ringkasan = 'Mode --tanpa-upload: unduh OK, tidak dikirim ke IRESIS.'
        else:
            log('[4/4] Upload ke IRESIS...')
            hasil = upload_ke_iresis(path, base_url, token_cron)
            ringkasan = hasil.get('message', '')
            try:
                os.remove(path)     # sukses -> berkas sementara tidak diperlukan lagi
                path = ''
            except OSError:
                pass
        log('Hasil: ' + ringkasan)
    except TahapGagal as e:
        status, tahap, alasan, saran = 'GAGAL', e.tahap, e.alasan, e.saran
        log('GAGAL di tahap %s: %s' % (tahap, alasan))
        log('Saran: %s' % saran)
    except Exception as e:      # bug/error tak terduga: simpan traceback lengkap supaya mudah ditelusuri
        status, tahap, alasan = 'GAGAL', 'TAK_TERDUGA', '%s: %s' % (type(e).__name__, e)
        saran = 'Error tak terduga di script; baca traceback di logs/auto_upload_retur_penjualan.log.'
        log('GAGAL tak terduga: %s' % alasan)
        for baris in traceback.format_exc().splitlines():
            log('  ' + baris)
    finally:
        if punya_lock:
            lepas_lock()
        if status == 'GAGAL' and path and os.path.exists(path):
            log('Berkas dipertahankan untuk diperiksa: %s' % path)
        catat_riwayat(mulai, status, tahap, alasan, saran, ringkasan, nama, ukuran)
        log('=== SELESAI: %s ===' % status)
    return 0 if status == 'BERHASIL' else 1


if __name__ == '__main__':
    sys.exit(main())
