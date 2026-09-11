"""
Auto Upload Resi - Download Laporan Penjualan (Faktur & Pesanan) dari Jubelio
lalu upload ke iresis.

Tanggal: hari_ini - 3 hari s/d hari_ini
Cara: connect ke Chrome yang sudah buka (port 9601)
"""

import DrissionPage
import requests
import sys
import os
import glob
import time
from datetime import datetime, timedelta
from pathlib import Path

sys.path.insert(0, os.path.dirname(__file__))
from jubelio_credentials import EMAIL as JUBELIO_EMAIL, PASSWORD as JUBELIO_PASSWORD

IRESIS_BASE_URL   = 'http://localhost:8080/new-iresis/index.php/'
from secrets_local import IRESIS_CRON_TOKEN
DOWNLOAD_DIR      = os.path.join(os.path.expanduser('~'), 'Downloads')
CHROME_USER_DIR   = r'C:\MP\chrome_resi_profile'

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

def set_tanggal(page, el, tanggal_str):
    """Set input tanggal (format dd/mm/yyyy) via JS."""
    page.run_js(
        '''
        arguments[0].value = arguments[1];
        arguments[0].dispatchEvent(new Event('input',  {bubbles:true}));
        arguments[0].dispatchEvent(new Event('change', {bubbles:true}));
        ''',
        el, tanggal_str
    )
    page.wait(0.5)

def download_laporan(page, ref_type='Faktur'):
    log(f'Download Laporan Penjualan ({ref_type})...')
    before_download = time.time()

    page.get('https://v2.jubelio.com/sales/reports/')
    page.wait(5)

    # Klik card Penjualan via deskripsinya (bukan nav header)
    page.ele('text:Laporan daftar transaksi penjualan', timeout=20).click()
    page.wait(3)  # tunggu modal terbuka

    # Pilih Pesanan jika bukan default (default = Faktur)
    if ref_type == 'Pesanan':
        log('  Pilih referensi: Pesanan...')
        # Klik dropdown Referensi Transaksi di dalam modal
        page.ele('tag:select', timeout=10).select('Pesanan')
        page.wait(1)

    # Hitung tanggal range
    today      = datetime.now()
    start_date = (today - timedelta(days=3)).strftime('%d/%m/%Y')
    end_date   = today.strftime('%d/%m/%Y')
    log(f'  Tanggal: {start_date} s/d {end_date}')

    # Set tanggal via JS - cari input dengan value format dd/mm/yyyy di dalam modal
    result = page.run_js(f'''
        var inputs = Array.from(document.querySelectorAll("input")).filter(function(el) {{
            var val = el.value || "";
            return val.length === 10 && val.indexOf("/") !== -1;
        }});
        if (inputs.length < 2) {{
            var modal = document.querySelector(".modal-dialog, .modal, .modal-content, [role='dialog']");
            if (modal) {{
                var modalInputs = Array.from(modal.querySelectorAll("input[type='text'], input:not([type])"));
                modalInputs = modalInputs.filter(function(el) {{
                    return el.offsetWidth > 0 && el.offsetHeight > 0;
                }});
                if (modalInputs.length >= 2) {{
                    inputs = modalInputs;
                }}
            }}
        }}
        if (inputs.length >= 2) {{
            [inputs[0], inputs[1]].forEach(function(el, i) {{
                var val = i === 0 ? "{start_date}" : "{end_date}";
                el.value = val;
                el.dispatchEvent(new Event("input",  {{bubbles:true}}));
                el.dispatchEvent(new Event("change", {{bubbles:true}}));
            }});
            return "OK:" + inputs.length;
        }}
        return "FAIL:" + inputs.length;
    ''')
    log(f'  Set tanggal: {result}')
    if result.startswith('FAIL'):
        raise Exception(f'Input tanggal tidak ditemukan di modal ({result})')

    # Tutup kalender jika terbuka (klik area modal di luar input)
    page.run_js("document.querySelector('h2,h3,.modal-title') && document.querySelector('h2,h3,.modal-title').click()")
    page.wait(0.5)

    # Klik Cetak
    log('  Klik Cetak...')
    page.ele('text:Cetak', timeout=20).click()

    # Tunggu download
    log('  Menunggu download xlsx/xls...')
    filepath = wait_for_new_file(DOWNLOAD_DIR, ['*.xlsx', '*.xls'], before_download, timeout=300)
    if not filepath:
        raise TimeoutError(f'Download {ref_type} timeout (5 menit)')

    log(f'  Download OK: {os.path.basename(filepath)}')
    return filepath

def upload_to_iresis(filepath):
    log(f'Upload ke iresis: {os.path.basename(filepath)}')
    url = f'{IRESIS_BASE_URL}cron/auto_upload_resi?token={IRESIS_CRON_TOKEN}'
    with open(filepath, 'rb') as f:
        resp = requests.post(url, files={'receiptFile': (os.path.basename(filepath), f)}, timeout=600)
    
    text = resp.text.strip()
    if not text:
        raise Exception(f"Server returned empty response. HTTP status: {resp.status_code}")
        
    last_line = text.split('\n')[-1]
    try:
        import json
        result = json.loads(last_line)
    except Exception as e:
        log(f"Gagal parse JSON response. Status code: {resp.status_code}")
        log(f"Response text lengkap:\n{text}")
        raise Exception(f"Invalid JSON response from server: {last_line}")
        
    if result.get('success'):
        log(f'Upload OK: {result["message"]}')
    else:
        raise Exception(result.get('message', 'Upload gagal'))
    return result['message']

def main():
    log('=== AUTO UPLOAD RESI ===')
    page = make_page()
    files_downloaded = []

    try:
        login_jubelio(page)

        for ref_type in ['Faktur', 'Pesanan']:
            filepath = download_laporan(page, ref_type)
            files_downloaded.append(filepath)
            upload_to_iresis(filepath)
            time.sleep(2)

        log('=== SELESAI ===')

    except Exception as e:
        log(f'ERROR: {e}')
        sys.exit(1)

    finally:
        try:
            page.quit()
        except Exception:
            pass
        for f in files_downloaded:
            try:
                Path(f).unlink(missing_ok=True)
            except Exception:
                pass

if __name__ == '__main__':
    main()
