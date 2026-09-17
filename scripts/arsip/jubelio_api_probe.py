"""
Jubelio Data API probe
=======================
Tes akses Data API Jubelio v2 (api2.jubelio.com) untuk data RETUR PENJUALAN,
lalu SIMPAN 1 halaman JSON supaya bisa dipetakan ke kolom iresis.

Jalankan sekali:
    python scripts/jubelio_api_probe.py

Hasil disimpan ke: scripts/sniff_out/sales_returns_probe.json
Kirim file itu (atau output terminalnya) ke Claude untuk finalisasi mapping.

Catatan: endpoint data di-throttle (429). Script sudah retry dgn backoff.
Kalau tetap 429 berkali-kali, tunggu ~15-30 menit lalu coba lagi (jangan
di-spam), atau pakai API Key Jubelio bila tersedia.
"""

import json
import os
import sys
import time
from datetime import datetime

import requests

sys.path.insert(0, os.path.dirname(__file__))
from jubelio_credentials import EMAIL, PASSWORD

BASE     = 'https://api2.jubelio.com'

OUT_DIR  = os.path.join(os.path.dirname(__file__), 'sniff_out')
os.makedirs(OUT_DIR, exist_ok=True)


def log(m):
    print(f'[{datetime.now():%H:%M:%S}] {m}')


def get_with_backoff(s, url, params, tries=6):
    for i in range(tries):
        r = s.get(url, params=params, timeout=60)
        if r.status_code != 429:
            return r
        wait = 10 * (i + 1)
        log(f'  429 (rate limit), tunggu {wait}s... (percobaan {i + 1}/{tries})')
        time.sleep(wait)
    return r


def main():
    s = requests.Session()
    s.headers.update({
        'accept': 'application/json',
        'content-type': 'application/json',
        'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/149.0.0.0',
    })

    log('Login Data API (api2.jubelio.com)...')
    r = s.post(f'{BASE}/login', json={'email': EMAIL, 'password': PASSWORD}, timeout=30)
    if r.status_code != 200:
        log(f'Login GAGAL: {r.status_code} {r.text[:200]}')
        return
    token = r.json().get('token')
    s.headers['authorization'] = token
    log('Login OK.')

    today = datetime.now()
    date_from = today.replace(month=1, day=1).strftime('%Y-%m-%d')
    date_to   = today.strftime('%Y-%m-%d')

    # Coba beberapa endpoint kandidat + variasi param tanggal
    endpoints = [
        ('/sales/v2/sales-returns/', {
            'page': 1, 'page_size': 5,
            'transaction_date_from': date_from, 'transaction_date_to': date_to,
        }),
        ('/sales/v2/orders/returned-list/', {
            'page': 1, 'page_size': 5,
            'transaction_date_from': date_from, 'transaction_date_to': date_to,
        }),
        ('/sales/sales-returns/', {
            'page': 1, 'pageSize': 5,
            'transactionDateFrom': date_from, 'transactionDateTo': date_to,
        }),
    ]

    for path, params in endpoints:
        log(f'GET {path} ...')
        r = get_with_backoff(s, BASE + path, params)
        log(f'  status {r.status_code}')
        if r.status_code != 200:
            log(f'  {r.text[:150]}')
            continue

        j = r.json()
        safe = path.strip('/').replace('/', '_')
        out = os.path.join(OUT_DIR, f'probe_{safe}.json')
        with open(out, 'w', encoding='utf-8') as f:
            json.dump(j, f, ensure_ascii=False, indent=1)
        log(f'  TERSIMPAN: {out}')

        # tampilkan struktur ringkas
        if isinstance(j, dict):
            log(f'  TOP KEYS: {list(j.keys())}')
            for cand in ('data', 'sales_returns', 'rows', 'result', 'items'):
                if isinstance(j.get(cand), list) and j[cand]:
                    log(f'  LIST "{cand}" (len {len(j[cand])}) - FIELD ITEM:')
                    log('    ' + ', '.join(j[cand][0].keys()))
                    break
        elif isinstance(j, list) and j:
            log(f'  LIST len {len(j)} - FIELD ITEM: {list(j[0].keys())}')
        log('  >>> Kirim file JSON di atas ke Claude untuk finalisasi mapping. <<<')
        break
    else:
        log('Semua endpoint gagal (kemungkinan rate limit / butuh API Key). '
            'Tunggu 15-30 menit lalu coba lagi.')


if __name__ == '__main__':
    main()
