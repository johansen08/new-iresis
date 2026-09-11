"""
Auto Upload Retur Jubelio
=========================
Download laporan "Daftar Retur Penjualan" dari Jubelio (rentang 1 Januari s/d
hari ini), lalu upload otomatis ke SIRESI lewat endpoint token
(cron/auto_upload_retur_jubelio). Menggantikan proses manual harian di menu
TIM ACCOUNTING > Upload Retur Jubelio.

Catatan teknis:
  - Download laporan retur Jubelio TIDAK bisa lewat API murni: file di-generate
    oleh Telerik Report Server (report-prod.jubelio.com) yang hanya jalan lewat
    alur klik "Cetak" di aplikasi. Jadi kita pakai otomasi browser (DrissionPage),
    mengikuti pola scripts/auto_upload_resi.py yang sudah terbukti.
  - Upload ke SIRESI memakai endpoint token, sama seperti auto_upload_resi.

Cara pakai:
    python scripts/auto_upload_retur_jubelio.py
    python scripts/auto_upload_retur_jubelio.py --base-url https://siresi-anda.com/index.php/

Jadwalkan harian via Windows Task Scheduler (lihat docs/AUTO_UPLOAD_RETUR_JUBELIO.md).
"""

import argparse
import glob
import json
import os
import sys
import time
from datetime import datetime
from pathlib import Path

import DrissionPage
import requests

sys.path.insert(0, os.path.dirname(__file__))
from jubelio_credentials import EMAIL as JUBELIO_EMAIL, PASSWORD as JUBELIO_PASSWORD

# ------------------------------------------------------------------ konfigurasi

# URL SIRESI PRODUKSI (index.php/). WAJIB diisi / dioverride via --base-url.
# Contoh: 'https://siresi.perusahaan.com/index.php/'
IRESIS_BASE_URL   = 'https://GANTI-DENGAN-DOMAIN-PRODUKSI/index.php/'
from secrets_local import IRESIS_CRON_TOKEN

DOWNLOAD_DIR    = os.path.join(os.path.expanduser('~'), 'Downloads')
CHROME_USER_DIR = r'C:\MP\chrome_resi_profile'   # profil sama dgn auto_upload_resi (sudah login)

# Teks kartu laporan retur di halaman Penjualan > Laporan. Script mencoba
# kandidat ini berurutan (case-insensitive, cocok sebagian). Jika UI Jubelio
# berubah, cukup sesuaikan daftar ini.
RETUR_CARD_TEXTS = [
    'Laporan daftar retur penjualan',
    'daftar retur penjualan',
    'retur penjualan',
    'Daftar Retur',
]


def log(msg):
    ts = datetime.now().strftime('%H:%M:%S')
    try:
        print(f'[{ts}] {msg}')
    except UnicodeEncodeError:
        print(f'[{ts}] {msg.encode("ascii", "replace").decode()}')


def wait_for_new_file(directory, patterns, after_time, timeout=300):
    if isinstance(patterns, str):
        patterns = [patterns]
    start = time.time()
    while time.time() - start < timeout:
        files = []
        for pattern in patterns:
            files.extend(glob.glob(os.path.join(directory, pattern)))
        crdownload = glob.glob(os.path.join(directory, '*.crdownload'))
        for f in sorted(files, key=os.path.getmtime, reverse=True):
            if os.path.getmtime(f) > after_time and not crdownload:
                return f
        time.sleep(3)
    return None


def make_page():
    options = DrissionPage.ChromiumOptions()
    options.set_user_data_path(CHROME_USER_DIR)
    options.set_pref('download.default_directory', DOWNLOAD_DIR)
    options.set_pref('download.prompt_for_download', False)
    options.set_pref('safebrowsing.enabled', False)
    options.set_argument('--no-sandbox')
    options.set_argument('--disable-dev-shm-usage')
    options.set_argument('--window-size=1920,1080')
    page = DrissionPage.ChromiumPage(options)
    page.set.timeouts(base=60)
    page.set.download_path(DOWNLOAD_DIR)
    return page


def login_jubelio(page):
    log('Cek login Jubelio...')
    page.get('https://v2.jubelio.com/')
    page.wait(3)
    if '/auth/login' in page.url:
        log('Belum login, masuk...')
        page.ele('@name=email', timeout=15).clear().input(JUBELIO_EMAIL)
        page.wait(0.5)
        page.ele('@name=password', timeout=15).clear().input(JUBELIO_PASSWORD)
        page.wait(0.5)
        page.ele('@type=submit').click()
        start = time.time()
        while '/auth/login' in page.url and time.time() - start < 30:
            time.sleep(1)
        if '/auth/login' in page.url:
            raise Exception('Login Jubelio gagal')
    log(f'Login OK: {page.url}')


def open_retur_card(page):
    """Klik kartu laporan 'Daftar Retur Penjualan' di halaman reports."""
    for txt in RETUR_CARD_TEXTS:
        el = page.ele(f'text:{txt}', timeout=4)
        if el:
            log(f'  Klik kartu laporan: "{txt}"')
            el.click()
            return True
    return False


def set_date_range(page, start_date, end_date):
    """Isi 2 input tanggal (format dd/mm/yyyy) di dalam modal via JS."""
    result = page.run_js('''
        var inputs = Array.from(document.querySelectorAll("input")).filter(function(el){
            var v = el.value || "";
            return v.length === 10 && v.indexOf("/") !== -1;
        });
        if (inputs.length < 2) {
            var modal = document.querySelector(".modal-dialog, .modal, .modal-content, [role='dialog']");
            if (modal) {
                var mi = Array.from(modal.querySelectorAll("input[type='text'], input:not([type])"))
                              .filter(function(el){ return el.offsetWidth > 0 && el.offsetHeight > 0; });
                if (mi.length >= 2) inputs = mi;
            }
        }
        if (inputs.length >= 2) {
            [inputs[0], inputs[1]].forEach(function(el, i){
                el.value = arguments[i];
                el.dispatchEvent(new Event("input",  {bubbles:true}));
                el.dispatchEvent(new Event("change", {bubbles:true}));
            });
            return "OK:" + inputs.length;
        }
        return "FAIL:" + inputs.length;
    ''', start_date, end_date)
    log(f'  Set tanggal {start_date} s/d {end_date}: {result}')
    if str(result).startswith('FAIL'):
        raise Exception(f'Input tanggal tidak ditemukan di modal ({result})')


def download_retur(page):
    log('Download Laporan Daftar Retur Penjualan...')
    before_download = time.time()

    page.get('https://v2.jubelio.com/sales/reports/')
    page.wait(6)

    if not open_retur_card(page):
        raise Exception('Kartu "Daftar Retur Penjualan" tidak ditemukan. '
                        'Sesuaikan RETUR_CARD_TEXTS di script.')
    page.wait(3)  # tunggu modal terbuka

    today      = datetime.now()
    start_date = today.replace(month=1, day=1).strftime('%d/%m/%Y')   # 1 Januari tahun ini
    end_date   = today.strftime('%d/%m/%Y')
    set_date_range(page, start_date, end_date)

    # Tutup kalender bila terbuka (klik judul modal)
    page.run_js("var t=document.querySelector('h2,h3,.modal-title'); if(t) t.click();")
    page.wait(0.5)

    log('  Klik Cetak...')
    page.ele('text:Cetak', timeout=20).click()

    log('  Menunggu file xlsx/xls ter-download...')
    filepath = wait_for_new_file(DOWNLOAD_DIR, ['*.xlsx', '*.xls'], before_download, timeout=300)
    if not filepath:
        raise TimeoutError('Download retur timeout (5 menit)')
    log(f'  Download OK: {os.path.basename(filepath)}')
    return filepath


def upload_to_iresis(filepath, base_url):
    log(f'Upload ke SIRESI: {os.path.basename(filepath)}')
    url = f'{base_url}cron/auto_upload_retur_jubelio?token={IRESIS_CRON_TOKEN}'
    with open(filepath, 'rb') as f:
        resp = requests.post(url, files={'jubelioFile': (os.path.basename(filepath), f)}, timeout=600)

    text = resp.text.strip()
    if not text:
        raise Exception(f'Server balas kosong. HTTP {resp.status_code}')
    last_line = text.split('\n')[-1]
    try:
        result = json.loads(last_line)
    except Exception:
        log(f'Gagal parse JSON (HTTP {resp.status_code}). Respons:\n{text[:500]}')
        raise Exception(f'Respons server bukan JSON: {last_line[:200]}')

    if result.get('success'):
        log(f'Upload OK: {result["message"]}')
    else:
        raise Exception(result.get('message', 'Upload gagal'))
    return result['message']


def main():
    parser = argparse.ArgumentParser(description='Auto download retur Jubelio + upload ke SIRESI.')
    parser.add_argument('--base-url', default=IRESIS_BASE_URL,
                        help='Base URL SIRESI produksi (harus diakhiri /index.php/).')
    parser.add_argument('--keep-file', action='store_true',
                        help='Jangan hapus file xlsx setelah upload (untuk debug).')
    args = parser.parse_args()

    base_url = args.base_url if args.base_url.endswith('/') else args.base_url + '/'
    if 'GANTI-DENGAN-DOMAIN' in base_url:
        log('ERROR: Base URL produksi belum diisi. Jalankan dengan --base-url https://domain-anda/index.php/')
        sys.exit(2)

    log('=== AUTO UPLOAD RETUR JUBELIO ===')
    page = make_page()
    downloaded = None
    try:
        login_jubelio(page)
        downloaded = download_retur(page)
        upload_to_iresis(downloaded, base_url)
        log('=== SELESAI ===')
    except Exception as e:
        log(f'ERROR: {e}')
        sys.exit(1)
    finally:
        try:
            page.quit()
        except Exception:
            pass
        if downloaded and not args.keep_file:
            try:
                Path(downloaded).unlink(missing_ok=True)
            except Exception:
                pass


if __name__ == '__main__':
    main()
