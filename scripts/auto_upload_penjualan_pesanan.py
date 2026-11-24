"""
Auto Upload Daftar Penjualan (Pesanan) 3 BULAN TERAKHIR (Jubelio -> IRESIS), TANPA BROWSER
==========================================================================================
Dijalankan Windows Task Scheduler tiap hari 22.00
(pembungkus: scripts/penjualan_pesanan_senyap.vbs, pendaftar: scripts/daftar_task_penjualan_pesanan.ps1).

Tujuan: memperbarui STATUS PESANAN (SHIPPED -> COMPLETED/RETURNED/CANCELED, dst.) serta TANGGAL & WAKTU RETUR
(kolom TGL_RETUR/WAKTU_RETUR) untuk semua resi 3 bulan terakhir, memakai laporan yang sama dengan menu
Upload Resi (Excel "Daftar Penjualan", referensi Pesanan, kolom A..W).

Alur (jalur laporan sama dengan auto_upload_retur_penjualan.py, hanya endpoint & parameternya beda):
  1. POST open.jubelio.com/core-api/login                           -> token JWT
  2. GET  open.jubelio.com/core-api/reports/sales-list/date-range/
          ?date_from=..&date_to=..&reference=order&hpp=true&tz=Asia/Bangkok
          -> {"status":"ok","url":"https://report-prod.jubelio.com/?&token=T"}
  3. GET  https://report-prod.jubelio.com/xlsx/?&token=T            -> xlsx "Daftar Penjualan"
  4. POST <IRESIS>/cron/auto_upload_resi?token=...  (multipart, field receiptFile)
          -> Receipt_fcd::insert_receipt(): resi baru di-insert, resi lama diperbarui bila status berubah,
             tanggal_retur diisi dari TGL_RETUR+WAKTU_RETUR; resi COMPLETED tidak diubah (aturan lama).

OPTIMASI
  * 3 bulan (~1 juta baris) TIDAK muat satu xlsx (batas Excel 1.048.576 baris) dan akan membuat PHP/Jubelio
    timeout, jadi rentang dipecah per --potong-hari (bawaan 7 hari), TERBARU DULU: kalau run terpotong,
    data yang paling berguna sudah masuk.
  * Unduhan potongan berikutnya berjalan SEMENTARA potongan sekarang diunggah/diolah PHP (pipeline 1
    langkah ke depan), jadi waktu total ~ max(unduh, upload) per potongan, bukan jumlahnya.
  * Satu login Jubelio untuk seluruh run (diperbarui otomatis bila lebih dari 20 menit).
  * Pembaca xlsx di sisi PHP sudah streaming (Xlsx_cepat) dan upsert-nya batch (insert 500/update 200).

CATATAN HASIL: setiap run SELALU menulis
  logs/auto_upload_penjualan_pesanan.log     log rinci (tiap baris diberi run_id)
  logs/riwayat_penjualan_pesanan.csv         SATU BARIS per run: BERHASIL/GAGAL, tahap gagal, alasan, saran, ringkasan
  logs/riwayat_penjualan_pesanan_potongan.csv  SATU BARIS per potongan tanggal per run (untuk tahu potongan mana yang gagal)
Run gagal (satu potongan atau lebih) -> exit code 1 (terlihat di "Last Run Result" Task Scheduler).
Panduan baca log: docs/AUTO_UPLOAD_PENJUALAN_PESANAN.md.

Pakai manual:  python scripts/auto_upload_penjualan_pesanan.py
               python scripts/auto_upload_penjualan_pesanan.py --tanpa-upload --hari 7   (hanya unduh, uji jalur Jubelio)
               python scripts/auto_upload_penjualan_pesanan.py --hari 14 --potong-hari 7
"""

import argparse
import csv
import os
import re
import sys
import time
import traceback
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timedelta

import requests

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

# Pakai ulang fungsi jalur Jubelio dari skrip retur (login, unduh streaming, validasi file, sensor token).
# Berkas log/lock-nya dialihkan ke milik skrip ini lewat atribut modul (log() membaca global saat dipanggil).
import auto_upload_retur_penjualan as b  # noqa: E402
from auto_upload_retur_penjualan import TahapGagal, log  # noqa: E402

ROOT = b.ROOT
LOG_DIR = b.LOG_DIR
b.LOG_FILE = os.path.join(LOG_DIR, 'auto_upload_penjualan_pesanan.log')
b.TMP_DIR = os.path.join(ROOT, 'scratch', 'penjualan_pesanan')   # scratch/ di-gitignore
TMP_DIR = b.TMP_DIR
LOCK_FILE = os.path.join(TMP_DIR, 'berjalan.lock')
RIWAYAT_CSV = os.path.join(LOG_DIR, 'riwayat_penjualan_pesanan.csv')
POTONGAN_CSV = os.path.join(LOG_DIR, 'riwayat_penjualan_pesanan_potongan.csv')

RUN_ID = b.RUN_ID
HARI_DEFAULT = 90               # "3 bulan terakhir"
POTONG_DEFAULT = 7              # hari per xlsx (~77 rb baris, ~9 MB)
UPLOAD_TIMEOUT = 1800           # parse + upsert puluhan ribu baris di PHP
LOCK_USANG_DETIK = 6 * 3600     # run penuh bisa berjam-jam; lock lebih tua dari ini dianggap sisa run mati
LOGIN_BARU_SETELAH = 20 * 60    # detik
GAGAL_BERUNTUN_MAKS = 3         # berhenti lebih awal bila 3 potongan berturut-turut gagal (Apache/Jubelio mati)
REFERENSI = 'order'             # "Pesanan" (bukan "invoice"/Faktur)


# --------------------------------------------------------------------------- riwayat

def tulis_csv(path, header, baris):
    baru = not os.path.exists(path)
    os.makedirs(LOG_DIR, exist_ok=True)
    with open(path, 'a', encoding='utf-8-sig', newline='') as f:
        w = csv.writer(f)
        if baru:
            w.writerow(header)
        w.writerow(baris)


def catat_potongan(p):
    tulis_csv(POTONGAN_CSV,
              ['run_id', 'potongan', 'dari', 'sampai', 'status', 'tahap_gagal', 'alasan', 'saran_perbaikan',
               'ukuran_byte', 'detik_unduh', 'detik_upload', 'ringkasan'],
              [RUN_ID, p['no'], p['dari'].strftime('%Y-%m-%d'), p['sampai'].strftime('%Y-%m-%d'), p['status'],
               p.get('tahap', ''), b.sensor(p.get('alasan', '')), p.get('saran', ''), p.get('ukuran', 0),
               p.get('detik_unduh', ''), p.get('detik_upload', ''), b.sensor(p.get('ringkasan', ''))])


def catat_run(mulai, status, tahap, alasan, saran, ringkasan):
    selesai = datetime.now()
    tulis_csv(RIWAYAT_CSV,
              ['run_id', 'mulai', 'selesai', 'durasi_detik', 'status', 'tahap_gagal', 'alasan',
               'saran_perbaikan', 'ringkasan'],
              [RUN_ID, mulai.strftime('%Y-%m-%d %H:%M:%S'), selesai.strftime('%Y-%m-%d %H:%M:%S'),
               int((selesai - mulai).total_seconds()), status, tahap, b.sensor(alasan), saran, b.sensor(ringkasan)])


# --------------------------------------------------------------------------- Jubelio

class SesiJubelio:
    """Satu login untuk seluruh run; login ulang otomatis kalau sudah lebih dari 20 menit."""

    def __init__(self, email, password):
        self.email, self.password = email, password
        self.s, self.t_login = None, 0

    def get(self):
        if self.s is None or time.time() - self.t_login > LOGIN_BARU_SETELAH:
            self.s = b.login(self.email, self.password)
            self.t_login = time.time()
        return self.s


def ambil_url_laporan(sesi, dari, sampai):
    params = {'date_from': b.tgl_jubelio(dari), 'date_to': b.tgl_jubelio(sampai),
              'reference': REFERENSI, 'hpp': 'true', 'tz': 'Asia/Bangkok'}
    for coba in range(1, 6):
        try:
            r = sesi.get().get(b.CORE_API + '/reports/sales-list/date-range/', params=params, timeout=60)
        except TahapGagal:
            raise
        except requests.RequestException as e:
            raise TahapGagal('URL_LAPORAN', 'Gagal menghubungi Jubelio (%s: %s)' % (type(e).__name__, e),
                             'Cek koneksi internet PC ini.')
        if r.status_code == 429:
            tunggu = 10 * coba
            log('  429 dari sales-list, tunggu %ds (coba %d/5)' % (tunggu, coba))
            time.sleep(tunggu)
            continue
        break
    else:
        raise TahapGagal('URL_LAPORAN', 'Jubelio terus membalas 429 (rate limit) setelah 5 kali coba',
                         'Tunggu jadwal berikutnya; jangan jalankan manual berulang-ulang.')
    if r.status_code in (401, 403):
        raise TahapGagal('URL_LAPORAN', 'Token login ditolak HTTP %d' % r.status_code,
                         'Akun Jubelio mungkin tidak punya akses menu Laporan > Laporan Penjualan.')
    if r.status_code != 200:
        raise TahapGagal('URL_LAPORAN', 'HTTP %d: %s' % (r.status_code, r.text[:300]),
                         'Endpoint laporan berubah? Sniff ulang (Laporan > Daftar Transaksi Penjualan > Pesanan > Cetak).')
    try:
        data = r.json()
    except ValueError:
        raise TahapGagal('URL_LAPORAN', 'Respons bukan JSON: %s' % r.text[:300], 'Endpoint sales-list berubah; sniff ulang.')
    url = data.get('url') if isinstance(data, dict) else None
    if not isinstance(data, dict) or data.get('status') != 'ok' or not url or 'token=' not in url:
        raise TahapGagal('URL_LAPORAN', 'Respons tak terduga: %s' % str(data)[:300],
                         'Struktur respons berubah (diharapkan {"status":"ok","url":"...token=..."}).')
    return url


def unduh_potongan(sesi, p):
    """Jalan di thread latar: minta URL laporan lalu unduh xlsx. Mengembalikan (path, ukuran, detik)."""
    t0 = time.time()
    log('  [potongan %d] unduh %s s/d %s' % (p['no'], p['dari'].strftime('%Y-%m-%d'), p['sampai'].strftime('%Y-%m-%d')))
    viewer = ambil_url_laporan(sesi, p['dari'], p['sampai'])
    path = os.path.join(TMP_DIR, 'penjualan_%s_%02d.xlsx' % (RUN_ID, p['no']))
    b.unduh_xlsx(b.url_xlsx(viewer), path)
    ukuran = b.cek_file(path)
    return path, ukuran, time.time() - t0


# --------------------------------------------------------------------------- IRESIS

def upload_ke_iresis(path, base_url, token):
    url = '%scron/auto_upload_resi' % (base_url if base_url.endswith('/') else base_url + '/')
    try:
        with open(path, 'rb') as f:
            r = requests.post(url, params={'token': token},
                              files={'receiptFile': (os.path.basename(path), f,
                                     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')},
                              timeout=UPLOAD_TIMEOUT)
    except requests.ConnectionError as e:
        raise TahapGagal('UPLOAD', 'Tidak bisa terhubung ke IRESIS %s (%s)' % (url, e),
                         'Apache/XAMPP mati? Cek IRESIS_BASE_URL di scripts/secrets_local.py dan buka alamatnya di browser.')
    except requests.RequestException as e:
        raise TahapGagal('UPLOAD', 'Upload ke IRESIS error (%s: %s)' % (type(e).__name__, e),
                         'Timeout: parsing/upsert di PHP lama. Perkecil --potong-hari; cek logs/ aplikasi & memory_limit PHP.')
    if r.status_code == 403:
        raise TahapGagal('UPLOAD', 'IRESIS menolak (HTTP 403)',
                         'IRESIS_CRON_TOKEN di scripts/secrets_local.py harus sama dengan cron_token di secrets.php.')
    hasil = b.ambil_json(r.text)
    if hasil is None:
        potongan = r.text.strip()[:500]
        saran = 'Respons bukan JSON: ada output PHP nyasar (warning/error) atau PHP mati. Baca isinya di kolom alasan.'
        if r.status_code == 413 or 'post_max_size' in potongan.lower() or 'exceeds' in potongan.lower():
            saran = 'File melebihi batas PHP: naikkan upload_max_filesize & post_max_size di php.ini lalu restart Apache.'
        elif 'memory' in potongan.lower():
            saran = 'PHP kehabisan memori: perkecil --potong-hari (mis. 3) atau naikkan memory_limit.'
        raise TahapGagal('UPLOAD', 'HTTP %d, respons bukan JSON: %s' % (r.status_code, potongan or '(kosong)'), saran)
    if not hasil.get('success'):
        pesan = hasil.get('message', '(tanpa pesan)')
        saran = 'Lihat pesan dari IRESIS. '
        if 'File tidak ditemukan' in pesan:
            saran += 'File tidak sampai ke PHP: cek upload_max_filesize/post_max_size di php.ini.'
        elif 'Error' in pesan or 'error' in pesan:
            saran += 'Cek application/logs/ (error DB/PHP) pada waktu run ini; header kolom Jubelio mungkin berubah, bandingkan dengan Receipt_fcd::insert_receipt().'
        else:
            saran += 'Cek application/logs/ untuk detail.'
        raise TahapGagal('UPLOAD', 'IRESIS menjawab gagal: %s' % pesan, saran)
    return hasil


# --------------------------------------------------------------------------- kredensial, lock, bersih

def muat_kredensial():
    try:
        return b.muat_kredensial()
    except TahapGagal:
        raise


def ambil_lock():
    os.makedirs(TMP_DIR, exist_ok=True)
    if os.path.exists(LOCK_FILE):
        umur = time.time() - os.path.getmtime(LOCK_FILE)
        if umur < LOCK_USANG_DETIK:
            raise TahapGagal('PERSIAPAN', 'Run sebelumnya masih berjalan (lock %.0f menit)' % (umur / 60),
                             'Tunggu selesai. Kalau yakin tidak ada proses python jalan, hapus scratch/penjualan_pesanan/berjalan.lock.')
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


def buat_potongan(hari, potong):
    """Rentang [hari ini - (hari-1), hari ini] dipecah per `potong` hari, TERBARU DULU."""
    hari_ini = datetime.now().replace(hour=0, minute=0, second=0, microsecond=0)
    awal = hari_ini - timedelta(days=hari - 1)
    hasil, akhir = [], hari_ini
    while akhir >= awal:
        mulai = max(awal, akhir - timedelta(days=potong - 1))
        hasil.append({'dari': mulai, 'sampai': akhir.replace(hour=23, minute=59, second=59)})
        akhir = mulai - timedelta(days=1)
    for i, p in enumerate(hasil, 1):
        p['no'] = i
    return hasil


# --------------------------------------------------------------------------- utama

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--hari', type=int, default=HARI_DEFAULT, help='jumlah hari ke belakang (bawaan 90 = 3 bulan)')
    ap.add_argument('--potong-hari', type=int, default=POTONG_DEFAULT, help='hari per berkas xlsx (bawaan 7)')
    ap.add_argument('--tanpa-upload', action='store_true', help='hanya unduh & validasi, tidak kirim ke IRESIS')
    args = ap.parse_args()
    hari, potong = max(1, args.hari), max(1, args.potong_hari)

    mulai = datetime.now()
    log('=== AUTO UPLOAD DAFTAR PENJUALAN PESANAN %d HARI (mulai) ===' % hari)
    status, tahap, alasan, saran, ringkasan = 'BERHASIL', '', '', '', ''
    punya_lock = False
    daftar = []
    try:
        ambil_lock()
        punya_lock = True
        bersihkan_file_lama()
        email, password, token_cron, base_url = muat_kredensial()

        daftar = buat_potongan(hari, potong)
        log('Rentang %s s/d %s dipecah jadi %d potongan @%d hari (terbaru dulu)' % (
            daftar[-1]['dari'].strftime('%Y-%m-%d'), daftar[0]['sampai'].strftime('%Y-%m-%d'), len(daftar), potong))

        sesi = SesiJubelio(email, password)
        sesi.get()      # login awal: kalau gagal, langsung berhenti dengan tahap LOGIN
        log('Login Jubelio OK.')

        gagal_beruntun = 0
        with ThreadPoolExecutor(max_workers=1) as unduh:
            # Pipeline: potongan i+1 diunduh sementara potongan i diunggah.
            nxt = unduh.submit(unduh_potongan, sesi, daftar[0])
            for i, p in enumerate(daftar):
                p['status'] = 'BERHASIL'
                path = ''
                try:
                    try:
                        path, p['ukuran'], p['detik_unduh'] = nxt.result()
                        p['detik_unduh'] = round(p['detik_unduh'])
                    finally:
                        # mulai unduh potongan berikut walau yang ini gagal (kecuali sudah ada yang menunggu)
                        nxt = unduh.submit(unduh_potongan, sesi, daftar[i + 1]) if i + 1 < len(daftar) else None
                    log('  [potongan %d/%d] file valid, %d byte' % (p['no'], len(daftar), p['ukuran']))
                    if args.tanpa_upload:
                        p['ringkasan'] = 'Mode --tanpa-upload: unduh OK, tidak dikirim ke IRESIS.'
                    else:
                        t0 = time.time()
                        hasil = upload_ke_iresis(path, base_url, token_cron)
                        p['detik_upload'] = round(time.time() - t0)
                        p['ringkasan'] = str(hasil.get('message', ''))
                        try:
                            os.remove(path)
                            path = ''
                        except OSError:
                            pass
                    log('  [potongan %d/%d] hasil: %s' % (p['no'], len(daftar), p['ringkasan']))
                    gagal_beruntun = 0
                except TahapGagal as e:
                    p.update(status='GAGAL', tahap=e.tahap, alasan=e.alasan, saran=e.saran)
                    gagal_beruntun += 1
                    log('  [potongan %d/%d] GAGAL tahap %s: %s' % (p['no'], len(daftar), e.tahap, e.alasan))
                    log('  Saran: %s' % e.saran)
                    if path and os.path.exists(path):
                        log('  Berkas dipertahankan untuk diperiksa: %s' % path)
                except Exception as e:      # bug tak terduga di satu potongan tidak membuang potongan lain
                    p.update(status='GAGAL', tahap='TAK_TERDUGA', alasan='%s: %s' % (type(e).__name__, e),
                             saran='Error tak terduga; baca traceback di logs/auto_upload_penjualan_pesanan.log.')
                    gagal_beruntun += 1
                    log('  [potongan %d/%d] GAGAL tak terduga: %s' % (p['no'], len(daftar), p['alasan']))
                    for baris in traceback.format_exc().splitlines():
                        log('    ' + baris)
                finally:
                    catat_potongan(p)
                if gagal_beruntun >= GAGAL_BERUNTUN_MAKS and i + 1 < len(daftar):
                    log('Dihentikan: %d potongan berturut-turut gagal; sisa %d potongan tidak dicoba.' % (
                        gagal_beruntun, len(daftar) - i - 1))
                    if nxt is not None:
                        nxt.cancel()
                        try:
                            nxt.result(timeout=900)     # tunggu unduhan yang sudah berjalan agar thread bersih
                        except Exception:
                            pass
                    break

        dicoba = [p for p in daftar if 'status' in p]
        ok = [p for p in dicoba if p['status'] == 'BERHASIL']
        gagal = [p for p in dicoba if p['status'] == 'GAGAL']
        belum = len(daftar) - len(dicoba)
        ringkasan = '%d/%d potongan berhasil, %d gagal, %d tidak dicoba (%d hari)' % (
            len(ok), len(daftar), len(gagal), belum, hari)
        if args.tanpa_upload:
            ringkasan += ' [mode --tanpa-upload]'
        if gagal or belum:
            status = 'GAGAL'
            tahap = gagal[0].get('tahap', 'PERSIAPAN') if gagal else 'PERSIAPAN'
            alasan = '; '.join('potongan %d (%s s/d %s) tahap %s: %s' % (
                p['no'], p['dari'].strftime('%d/%m'), p['sampai'].strftime('%d/%m'), p['tahap'], p['alasan'])
                for p in gagal)[:1500] or 'Sebagian potongan tidak dicoba.'
            saran = gagal[0].get('saran', '') if gagal else ''
            saran += (' Detail per potongan: logs/riwayat_penjualan_pesanan_potongan.csv (filter run_id %s). '
                      'Run berikutnya aman diulang (upsert idempotent).' % RUN_ID)
        log('Ringkasan: ' + ringkasan)
    except TahapGagal as e:
        status, tahap, alasan, saran = 'GAGAL', e.tahap, e.alasan, e.saran
        log('GAGAL di tahap %s: %s' % (tahap, alasan))
        log('Saran: %s' % saran)
    except Exception as e:
        status, tahap, alasan = 'GAGAL', 'TAK_TERDUGA', '%s: %s' % (type(e).__name__, e)
        saran = 'Error tak terduga di script; baca traceback di logs/auto_upload_penjualan_pesanan.log.'
        log('GAGAL tak terduga: %s' % alasan)
        for baris in traceback.format_exc().splitlines():
            log('  ' + baris)
    finally:
        if punya_lock:
            lepas_lock()
        catat_run(mulai, status, tahap, alasan, saran, ringkasan)
        log('=== SELESAI: %s ===' % status)
    return 0 if status == 'BERHASIL' else 1


if __name__ == '__main__':
    sys.exit(main())
