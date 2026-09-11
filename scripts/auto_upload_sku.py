"""
Auto Upload SKU - Download Persediaan Bundle dari Jubelio lalu upload ke iresis.
Chrome launch sendiri (tidak perlu Chrome sudah buka).
"""

import DrissionPage
import requests
import sys
import os
import glob
import time
import json
from datetime import datetime
from pathlib import Path

from jubelio_credentials import EMAIL as JUBELIO_EMAIL, PASSWORD as JUBELIO_PASSWORD
IRESIS_BASE_URL  = 'http://localhost:8080/new-iresis/index.php/'
from secrets_local import IRESIS_CRON_TOKEN
DOWNLOAD_DIR     = os.path.join(os.path.expanduser('~'), 'Downloads')
CHROME_USER_DIR  = r'C:\MP\chrome_sku_profile'

def log(msg):
    ts = datetime.now().strftime('%H:%M:%S')
    try:
        print(f'[{ts}] {msg}')
    except UnicodeEncodeError:
        print(f'[{ts}] {msg.encode("ascii", "replace").decode()}')

def wait_for_new_file(directory, pattern, after_time, timeout=300):
    start = time.time()
    while time.time() - start < timeout:
        files = glob.glob(os.path.join(directory, pattern))
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
    options.set_argument('--start-minimized')
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
            raise Exception('Login gagal')
    log(f'Login OK: {page.url}')

def download_persediaan_bundle(page):
    log('Download Persediaan Bundle...')
    before_download = time.time()

    page.get('https://v2.jubelio.com/inventory/inventory_report')
    page.wait(5)

    # Scroll ke bawah untuk cari card Persediaan Bundle
    page.scroll.to_bottom()
    page.wait(2)

    page.ele('text:Persediaan Bundle', timeout=20).click()
    page.wait(3)

    log('Klik Cetak...')
    page.ele('text:Cetak', timeout=20).click()

    log('Menunggu download...')
    filepath = wait_for_new_file(DOWNLOAD_DIR, 'persediaan*bundle*.xlsx', before_download, timeout=300)
    if not filepath:
        # Fallback: cari xlsx apapun
        filepath = wait_for_new_file(DOWNLOAD_DIR, '*.xlsx', before_download, timeout=60)
    if not filepath:
        raise TimeoutError('Download Persediaan Bundle timeout')

    log(f'Download OK: {os.path.basename(filepath)}')
    return filepath

def upload_to_iresis(filepath):
    log(f'Upload ke iresis: {os.path.basename(filepath)}')
    url = f'{IRESIS_BASE_URL}cron/auto_upload_sku?token={IRESIS_CRON_TOKEN}'
    with open(filepath, 'rb') as f:
        resp = requests.post(url, files={'skuFile': (os.path.basename(filepath), f)}, timeout=600)
    # Response berisi log line + JSON, ambil baris terakhir
    last_line = resp.text.strip().split('\n')[-1]
    result = json.loads(last_line)
    if result.get('success'):
        log(f'Upload OK: {result["message"]}')
    else:
        raise Exception(result.get('message', 'Upload gagal'))

def main():
    log('=== AUTO UPLOAD SKU ===')
    page = make_page()
    filepath = None

    try:
        login_jubelio(page)
        filepath = download_persediaan_bundle(page)
        upload_to_iresis(filepath)
        log('=== SELESAI ===')
    except Exception as e:
        log(f'ERROR: {e}')
        sys.exit(1)
    finally:
        try:
            page.quit()
        except Exception:
            pass
        if filepath:
            try:
                Path(filepath).unlink(missing_ok=True)
            except Exception:
                pass

if __name__ == '__main__':
    main()
