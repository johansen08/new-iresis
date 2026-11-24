"""
Cek Status Jubelio untuk tab "Belum Terima" dan "Sudah Verifikasi" (Daftar Retur Penjualan)
============================================================================================
Dijalankan Windows Task Scheduler 06.30, 11.30, 20.30
(pembungkus: scripts/cek_status_retur_jubelio_senyap.vbs, pendaftar: scripts/daftar_task_cek_status_retur.ps1).

MASALAH YANG DISELESAIKAN
  Saat Excel Daftar Retur Penjualan diunggah, sebuah pesanan berstatus RETURNED. Belakangan pesanan itu bisa berubah
  COMPLETED di Jubelio (mis. pembeli batal retur, dana dicairkan), tetapi data di IRESIS tidak pernah diperbarui sehingga
  resinya tersangkut di tab Belum Terima / Sudah Verifikasi selamanya.

ALUR (hasil sniff 24 Nov 2026, tanpa browser)
  1. POST open.jubelio.com/core-api/login                          -> token JWT
  2. GET  <IRESIS>/cron/kandidat_cek_status_retur?token=...        -> pesanan di tab Belum Terima (+Hangus) & Sudah Verifikasi
                                                                      yang masih BARANG RETUR
  3. GET  open.jubelio.com/core-api/sales/v2/orders/?q=<no pesanan>&page=1&page_size=25&sku_filter=false&...
          -> data[].internal_status (RETURNED / COMPLETED / ...)    (paralel, beberapa pekerja)
  4. POST <IRESIS>/cron/terapkan_status_retur?token=...            -> hanya yang COMPLETED diubah:
          tblreturjubelio.status_retur  BARANG RETUR -> 'SELESAI (PESANAN COMPLETED)'  (final, tidak boleh ditimpa lagi)
          tblprintresi.status_pesanan   RETURNED     -> COMPLETED
     Status Jubelio masih RETURNED (atau lainnya) -> tidak mengubah apa pun.

CATATAN HASIL: setiap run SELALU menulis
  logs/cek_status_retur_jubelio.log               log rinci per tahap (run_id sama di semua baris)
  logs/riwayat_cek_status_retur.csv               SATU BARIS per run: BERHASIL/GAGAL, tahap gagal, alasan, saran, ringkasan
  logs/rekap_cek_status_retur/rekap_<run>.csv     daftar pesanan yang BERUBAH di run itu (tab, resi, pesanan, status lama -> baru)
  logs/rekap_cek_status_retur/tidak_cocok_<run>.csv  pesanan yang tidak ditemukan / error di Jubelio (untuk ditelusuri)
Jejak permanen di database: tabel tblretur_cek_status_log (nilai sebelum & sesudah tiap perubahan).
Run gagal -> exit code 1 (terlihat di "Last Run Result" Task Scheduler). Panduan: docs/CEK_STATUS_RETUR_JUBELIO.md

Pakai manual:
  python scripts/cek_status_retur_jubelio.py --kering            (hanya baca + hitung, TIDAK menulis ke IRESIS)
  python scripts/cek_status_retur_jubelio.py --kering --maks 50  (uji cepat 50 pesanan)
  python scripts/cek_status_retur_jubelio.py                     (jalan sungguhan)
"""

import argparse
import csv
import json
import os
import sys
import threading
import time
import traceback
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime

import requests

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import auto_upload_retur_penjualan as dasar  # noqa: E402  (login, muat_kredensial, TahapGagal, sensor dipakai ulang)

TahapGagal = dasar.TahapGagal
sensor = dasar.sensor

ROOT = dasar.ROOT
LOG_DIR = dasar.LOG_DIR
LOG_FILE = os.path.join(LOG_DIR, 'cek_status_retur_jubelio.log')
RIWAYAT_CSV = os.path.join(LOG_DIR, 'riwayat_cek_status_retur.csv')
REKAP_DIR = os.path.join(LOG_DIR, 'rekap_cek_status_retur')
TMP_DIR = os.path.join(ROOT, 'scratch', 'cek_status_retur')
LOCK_FILE = os.path.join(TMP_DIR, 'berjalan.lock')
LOCK_USANG_DETIK = 3 * 3600

CORE_API = dasar.CORE_API
PEKERJA_DEFAULT = 4
JUBELIO_COBA = 4                 # percobaan per pesanan untuk 429 / 5xx / koneksi putus
BATAS_ERROR_PERSEN = 20.0        # error Jubelio > ini dari total dicek -> run dianggap GAGAL (hasil tidak layak diterapkan)
POST_POTONG = 500                # jumlah hasil per POST ke IRESIS
IRESIS_TIMEOUT = 900

RUN_ID = datetime.now().strftime('%Y%m%d-%H%M%S')
_kunci_cetak = threading.Lock()


# --------------------------------------------------------------------------- log

def log(pesan):
    baris = '[%s] [%s] %s' % (datetime.now().strftime('%Y-%m-%d %H:%M:%S'), RUN_ID, sensor(pesan))
    with _kunci_cetak:
        try:
            print(baris, flush=True)
        except UnicodeEncodeError:
            print(baris.encode('ascii', 'replace').decode(), flush=True)
        os.makedirs(LOG_DIR, exist_ok=True)
        with open(LOG_FILE, 'a', encoding='utf-8') as f:
            f.write(baris + '\n')


def catat_riwayat(mulai, status, tahap, alasan, saran, ringkasan):
    baru = not os.path.exists(RIWAYAT_CSV)
    os.makedirs(LOG_DIR, exist_ok=True)
    with open(RIWAYAT_CSV, 'a', encoding='utf-8-sig', newline='') as f:
        w = csv.writer(f)
        if baru:
            w.writerow(['run_id', 'mulai', 'selesai', 'durasi_detik', 'status', 'tahap_gagal',
                        'alasan', 'saran_perbaikan', 'ringkasan'])
        selesai = datetime.now()
        w.writerow([RUN_ID, mulai.strftime('%Y-%m-%d %H:%M:%S'), selesai.strftime('%Y-%m-%d %H:%M:%S'),
                    int((selesai - mulai).total_seconds()), status, tahap, sensor(alasan), saran, sensor(ringkasan)])


def tulis_csv(nama, kolom, baris):
    os.makedirs(REKAP_DIR, exist_ok=True)
    path = os.path.join(REKAP_DIR, nama)
    with open(path, 'w', encoding='utf-8-sig', newline='') as f:
        w = csv.writer(f)
        w.writerow(kolom)
        for b in baris:
            w.writerow([b.get(k, '') for k in kolom])
    return path


# --------------------------------------------------------------------------- IRESIS

def url_iresis(base_url, nama):
    return '%scron/%s' % (base_url if base_url.endswith('/') else base_url + '/', nama)


def ambil_json(teks):
    """Endpoint Cron kadang mencetak baris log sebelum JSON-nya; ambil JSON dari baris terakhir yang berbentuk {...}."""
    for baris in reversed(teks.strip().splitlines()):
        baris = baris.strip()
        if baris.startswith('{') and baris.endswith('}'):
            try:
                return json.loads(baris)
            except ValueError:
                continue
    return None


def panggil_iresis(tahap, metode, nama, base_url, token, badan=None):
    url = url_iresis(base_url, nama)
    try:
        r = requests.request(metode, url, params={'token': token}, json=badan, timeout=IRESIS_TIMEOUT)
    except requests.ConnectionError as e:
        raise TahapGagal(tahap, 'Tidak bisa terhubung ke IRESIS %s (%s)' % (url, e),
                         'Apache/XAMPP mati? Cek IRESIS_BASE_URL di scripts/secrets_local.py dan buka alamatnya di browser.')
    except requests.RequestException as e:
        raise TahapGagal(tahap, 'Panggilan ke IRESIS error (%s: %s)' % (type(e).__name__, e),
                         'Timeout: cek beban server / memory_limit PHP.')
    if r.status_code == 403:
        raise TahapGagal(tahap, 'IRESIS menolak (HTTP 403)',
                         'IRESIS_CRON_TOKEN di scripts/secrets_local.py harus sama dengan cron_token di secrets.php.')
    hasil = ambil_json(r.text)
    if hasil is None:
        raise TahapGagal(tahap, 'HTTP %d, respons bukan JSON: %s' % (r.status_code, r.text.strip()[:500] or '(kosong)'),
                         'Ada output PHP nyasar (warning/error) atau route hilang. Baca isinya di kolom alasan; '
                         'cek application/logs/ dan pastikan Cron::%s ada.' % nama)
    if not hasil.get('success'):
        raise TahapGagal(tahap, 'IRESIS menjawab gagal: %s' % hasil.get('message', '(tanpa pesan)'),
                         'Lihat pesan dari IRESIS; cek application/logs/ untuk detail error PHP/DB.')
    return hasil


# --------------------------------------------------------------------------- Jubelio

class PenjagaToken:
    """Login ulang (sekali per pergantian) bila token Jubelio kedaluwarsa di tengah run."""

    def __init__(self, email, password):
        self.email, self.password = email, password
        self.sesi = dasar.login(email, password)
        self.kunci = threading.Lock()
        self.versi = 0

    def segarkan(self, versi_lama):
        with self.kunci:
            if self.versi != versi_lama:      # pekerja lain sudah memperbarui
                return
            log('  Token ditolak (401), login ulang...')
            self.sesi = dasar.login(self.email, self.password)
            self.versi += 1


def cari_pesanan(pj, no_pesanan):
    """Kembalikan (kode, info). kode: OK | TIDAK_DITEMUKAN | ERROR. info: dict order (OK) atau teks penyebab."""
    params = {'q': no_pesanan, 'page': 1, 'page_size': 25, 'sku_filter': 'false',
              'sort_by': 'transaction_date', 'sort_direction': 'DESC'}
    sebab = ''
    for coba in range(1, JUBELIO_COBA + 1):
        versi = pj.versi
        try:
            r = pj.sesi.get(CORE_API + '/sales/v2/orders/', params=params, timeout=60)
        except requests.RequestException as e:
            sebab = '%s: %s' % (type(e).__name__, e)
            time.sleep(3 * coba)
            continue
        if r.status_code == 401:
            try:
                pj.segarkan(versi)
            except TahapGagal as e:
                return 'ERROR', 'Login ulang gagal: %s' % e.alasan
            sebab = 'HTTP 401'
            continue
        if r.status_code == 429 or r.status_code >= 500:
            sebab = 'HTTP %d' % r.status_code
            time.sleep(5 * coba)
            continue
        if r.status_code != 200:
            return 'ERROR', 'HTTP %d: %s' % (r.status_code, r.text[:150])
        try:
            data = r.json().get('data') or []
        except ValueError:
            return 'ERROR', 'respons bukan JSON: %s' % r.text[:150]
        target = no_pesanan.upper()
        for o in data:                          # cocok persis: nomor SO, lalu nomor referensi marketplace
            if str(o.get('salesorder_no', '')).upper() == target:
                return 'OK', o
        for o in data:
            if str(o.get('ref_no', '')).upper() == target:
                return 'OK', o
        return 'TIDAK_DITEMUKAN', 'Jubelio mengembalikan %d order, tidak ada yang cocok persis' % len(data)
    return 'ERROR', sebab or 'gagal setelah %d percobaan' % JUBELIO_COBA


# --------------------------------------------------------------------------- utama

def ambil_lock():
    os.makedirs(TMP_DIR, exist_ok=True)
    if os.path.exists(LOCK_FILE):
        umur = time.time() - os.path.getmtime(LOCK_FILE)
        if umur < LOCK_USANG_DETIK:
            raise TahapGagal('PERSIAPAN', 'Run sebelumnya masih berjalan (lock %.0f menit)' % (umur / 60),
                             'Tunggu selesai. Kalau yakin tidak ada proses python jalan, hapus scratch/cek_status_retur/berjalan.lock.')
        log('Lock usang (%.0f menit) diabaikan.' % (umur / 60))
    with open(LOCK_FILE, 'w') as f:
        f.write(RUN_ID)


def lepas_lock():
    try:
        os.remove(LOCK_FILE)
    except OSError:
        pass


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--kering', action='store_true', help='baca + hitung saja, TIDAK menulis ke IRESIS')
    ap.add_argument('--maks', type=int, default=0, help='batasi jumlah pesanan yang dicek (uji)')
    ap.add_argument('--pekerja', type=int, default=PEKERJA_DEFAULT)
    args = ap.parse_args()

    mulai = datetime.now()
    log('=== CEK STATUS RETUR JUBELIO (mulai)%s ===' % (' [KERING: tidak menulis]' if args.kering else ''))
    status, tahap, alasan, saran, ringkasan = 'BERHASIL', '', '', '', ''
    punya_lock = False
    try:
        ambil_lock()
        punya_lock = True
        email, password, token_cron, base_url = dasar.muat_kredensial()

        log('[1/4] Login Jubelio...')
        pj = PenjagaToken(email, password)

        log('[2/4] Ambil kandidat dari IRESIS (tab Belum Terima & Sudah Verifikasi)...')
        kand = panggil_iresis('KANDIDAT', 'GET', 'kandidat_cek_status_retur', base_url, token_cron)['kandidat']
        if args.maks:
            kand = kand[:args.maks]
        tab_n = {}
        for k in kand:
            tab_n[k['tab']] = tab_n.get(k['tab'], 0) + 1
        log('  %d pesanan dicek: %s' % (len(kand), tab_n))
        if not kand:
            ringkasan = 'Tidak ada kandidat (tab Belum Terima & Sudah Verifikasi kosong).'
            log(ringkasan)
            return 0

        log('[3/4] Cek status ke Jubelio (%d pekerja)...' % args.pekerja)
        hasil, tak_cocok, hitung = [], [], {}
        t0 = time.time()
        selesai = 0
        with ThreadPoolExecutor(max_workers=args.pekerja) as ex:
            tugas = {ex.submit(cari_pesanan, pj, k['no_pesanan']): k for k in kand}
            for f in as_completed(tugas):
                k = tugas[f]
                try:
                    kode, info = f.result()
                except Exception as e:      # satu pesanan bermasalah tidak boleh menghentikan sisanya
                    kode, info = 'ERROR', '%s: %s' % (type(e).__name__, e)
                selesai += 1
                if kode == 'OK':
                    st = str(info.get('internal_status') or '').upper() or 'KOSONG'
                    hitung[st] = hitung.get(st, 0) + 1
                    hasil.append({'no_resi': k['no_resi'], 'no_pesanan': k['no_pesanan'], 'tab': k['tab'], 'status_jubelio': st})
                else:
                    hitung[kode] = hitung.get(kode, 0) + 1
                    tak_cocok.append({'tab': k['tab'], 'no_resi': k['no_resi'], 'no_pesanan': k['no_pesanan'],
                                      'marketplace': k.get('marketplace', ''), 'hasil': kode, 'penyebab': info})
                if selesai % 500 == 0:
                    log('  ...%d/%d (%.0f detik) %s' % (selesai, len(kand), time.time() - t0, hitung))
        log('  Selesai cek Jubelio %.0f detik: %s' % (time.time() - t0, hitung))

        n_error = hitung.get('ERROR', 0)
        if tak_cocok:
            p = tulis_csv('tidak_cocok_%s.csv' % RUN_ID, ['tab', 'no_resi', 'no_pesanan', 'marketplace', 'hasil', 'penyebab'], tak_cocok)
            log('  Daftar tidak ditemukan/error: %s' % p)
        if n_error * 100.0 / len(kand) > BATAS_ERROR_PERSEN:
            contoh = next((t['penyebab'] for t in tak_cocok if t['hasil'] == 'ERROR'), '')
            raise TahapGagal('CEK_JUBELIO', '%d dari %d pesanan error saat cek Jubelio (contoh: %s)' % (n_error, len(kand), contoh),
                             'Kemungkinan rate limit 429 / token ditolak / Jubelio gangguan. Kurangi --pekerja, '
                             'cek logs/cek_status_retur_jubelio.log, jalankan ulang di jadwal berikut. Tidak ada data diubah.')

        log('[4/4] Terapkan ke IRESIS%s...' % (' (KERING)' if args.kering else ''))
        tot = {'diperiksa': 0, 'completed': 0, 'diubah': 0, 'baris_retur': 0, 'printresi': 0, 'dilewati': 0}
        perubahan = []
        for i in range(0, len(hasil), POST_POTONG):
            r = panggil_iresis('TERAPKAN', 'POST', 'terapkan_status_retur', base_url, token_cron,
                               {'run_id': RUN_ID, 'kering': bool(args.kering), 'hasil': hasil[i:i + POST_POTONG]})
            for k in tot:
                tot[k] += int(r.get(k, 0))
            perubahan += r.get('perubahan', [])
        kolom = ['tab', 'no_resi', 'no_pesanan', 'status_retur_lama', 'status_retur_baru',
                 'status_pesanan_lama', 'status_pesanan_baru', 'baris_retur']
        prefiks = 'kering' if args.kering else 'rekap'
        if perubahan:
            p = tulis_csv('%s_%s.csv' % (prefiks, RUN_ID), kolom, perubahan)
            log('  Rekap perubahan: %s' % p)
        per_tab = {}
        for c in perubahan:
            per_tab[c['tab']] = per_tab.get(c['tab'], 0) + 1
        ringkasan = ('%sdicek %d | Jubelio %s | COMPLETED %d -> %s %d pesanan (%d baris retur, %d status resi RETURNED->COMPLETED, '
                     '%d dilewati) per tab %s') % (
            '[KERING] ' if args.kering else '', len(kand), json.dumps(hitung, sort_keys=True), tot['completed'],
            'akan diubah' if args.kering else 'diubah', tot['diubah'], tot['baris_retur'], tot['printresi'], tot['dilewati'], per_tab)
        log('Hasil: ' + ringkasan)
    except TahapGagal as e:
        status, tahap, alasan, saran = 'GAGAL', e.tahap, e.alasan, e.saran
        log('GAGAL di tahap %s: %s' % (tahap, alasan))
        log('Saran: %s' % saran)
    except Exception as e:      # bug tak terduga: simpan traceback lengkap supaya mudah ditelusuri
        status, tahap, alasan = 'GAGAL', 'TAK_TERDUGA', '%s: %s' % (type(e).__name__, e)
        saran = 'Error tak terduga di script; baca traceback di logs/cek_status_retur_jubelio.log.'
        log('GAGAL tak terduga: %s' % alasan)
        for baris in traceback.format_exc().splitlines():
            log('  ' + baris)
    finally:
        if punya_lock:
            lepas_lock()
        catat_riwayat(mulai, status, tahap, alasan, saran, ringkasan)
        log('=== SELESAI: %s ===' % status)
    return 0 if status == 'BERHASIL' else 1


if __name__ == '__main__':
    sys.exit(main())
