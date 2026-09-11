"""
Auto Upload Resi via Jubelio core-api (PURE API, TANPA BROWSER)
================================================================
Menggantikan scripts/auto_upload_resi.py (yang buka Chrome via DrissionPage
untuk download xlsx "Laporan Penjualan") dengan panggilan HTTP langsung ke
open.jubelio.com/core-api. Jauh lebih hemat RAM (tanpa Chrome) dan lebih cepat.

Alur:
  1. Login core-api -> token JWT
  2. Ambil daftar order (list, paginated) untuk rentang H-3 s/d hari ini
  3. Tanya ke iresis: status_pesanan SAAT INI untuk tiap noresi -> kalau
     statusnya SAMA dengan status Jubelio sekarang, SKIP (tidak ada yang
     berubah). Ini beda dari sekadar cek "sudah COMPLETED", karena mayoritas
     resi di window H-3 sudah SHIPPED (status final versi Jubelio, walau
     bukan COMPLETED versi iresis) - kalau cuma skip yang COMPLETED, hampir
     semua resi tetap kena fetch detail ulang setiap hari (lambat & boros).
  4. Untuk sisanya (baru / status berubah), ambil detail per order (isi
     SKU/qty) dengan jeda+retry supaya tidak kena rate-limit (core-api tidak
     sekeras api2.jubelio.com, tapi tetap ada limit lunak)
  5. Susun jadi baris data (format sama dengan hasil parsing xlsx lama,
     key A..V) dan kirim sebagai JSON ke cron/auto_upload_resi_api
     (bukan multipart file upload lagi -> tidak perlu PhpSpreadsheet)

Mapping field (divalidasi silang terhadap data asli di tblprintresi):
  noresi             <- tracking_no
  no_pesanan         <- salesorder_no
  marketplace        <- channel_name
  kurir (raw)        <- shipper
  status_pesanan     <- internal_status   (vocab sama: SHIPPED/COMPLETED/dst)
  status_wms         <- wms_status        (kolom lama tblprintresi.status_wms
                        yang SEBELUMNYA tidak pernah diisi oleh pipeline xlsx
                        maupun API - lihat cek_paket_rts.php yang expect nilai
                        "Ready to Ship" di sini. Empiris: wms_status biasanya
                        sama dengan internal_status untuk order yang sudah
                        final/terminal; nilai "Ready to Ship" kemungkinan cuma
                        muncul di endpoint khusus wms/sales/v2/orders/ready-to-ship/
                        atau jendela waktu sempit. Field cuma keisi utk resi
                        BARU / yang statusnya berubah - resi lama yg sudah ada
                        sebelum kolom ini dipakai TIDAK di-backfill otomatis.)
  tanggal_pesan      <- transaction_date  (UTC -> WIB)
  tanggal_bataskirim <- due_date          (UTC -> WIB, endpoint detail)
  tanggal_pengiriman <- awb_created_date  (UTC -> WIB, level item/detail)
  tanggal_selesai    <- completed_date    (UTC -> WIB, endpoint detail)
  sku                <- items[].item_code
  jumlah             <- items[].qty
  no_rak             <- (dikosongkan; otoritas ada di tblsku, bukan Jubelio)
  nomorpicklist      <- (dikosongkan; nomor batch internal iresis)
  tanggal_retur      <- (tidak ada di endpoint ini; retur ditangani terpisah)

Jadwalkan via Windows Task Scheduler, sama seperti auto_upload_resi.py lama.
"""

import json
import os
import sys
import time
from datetime import datetime, timedelta

import requests

sys.path.insert(0, os.path.dirname(__file__))
from jubelio_credentials import EMAIL, PASSWORD

CORE_API_BASE     = 'https://open.jubelio.com/core-api'
IRESIS_BASE_URL   = 'http://localhost:8080/new-iresis/index.php/'
from secrets_local import IRESIS_CRON_TOKEN

DAYS_BACK          = 3      # H-3 s/d hari ini, sama seperti versi browser lama
LIST_PAGE_SIZE     = 200
DETAIL_DELAY       = 0.5    # jeda antar panggilan detail (detik)
DETAIL_MAX_RETRY   = 3
UPLOAD_BATCH_SIZE  = 2000   # baris per POST ke iresis


def log(msg):
    ts = datetime.now().strftime('%H:%M:%S')
    try:
        print(f'[{ts}] {msg}')
    except UnicodeEncodeError:
        print(f'[{ts}] {msg.encode("ascii", "replace").decode()}')


def utc_to_wib_parts(iso_str):
    """'2026-06-30T16:59:59.000Z' (UTC) -> ('2026-07-01', '00:59:59') WIB."""
    if not iso_str:
        return '', ''
    for fmt in ('%Y-%m-%dT%H:%M:%S.%fZ', '%Y-%m-%dT%H:%M:%SZ'):
        try:
            dt = datetime.strptime(iso_str, fmt)
            break
        except ValueError:
            dt = None
    if dt is None:
        return '', ''
    dt_wib = dt + timedelta(hours=7)
    return dt_wib.strftime('%Y-%m-%d'), dt_wib.strftime('%H:%M:%S')


def login_core_api():
    s = requests.Session()
    s.headers.update({
        'accept': 'application/json',
        'content-type': 'application/json',
        'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/149.0.0.0',
        'origin': 'https://v2.jubelio.com',
        'referer': 'https://v2.jubelio.com/',
    })
    r = s.post(f'{CORE_API_BASE}/login', json={'email': EMAIL, 'password': PASSWORD}, timeout=30)
    if r.status_code != 200:
        raise Exception(f'Login core-api gagal: {r.status_code} {r.text[:200]}')
    token = r.json().get('token')
    if not token:
        raise Exception('Token tidak ditemukan di response login')
    s.headers['authorization'] = token
    return s


def get_with_backoff(s, url, params, tries=DETAIL_MAX_RETRY, wait_base=5):
    r = None
    for i in range(tries):
        r = s.get(url, params=params, timeout=30)
        if r.status_code != 429:
            return r
        wait = wait_base * (i + 1)
        log(f'    429, tunggu {wait}s (retry {i + 1}/{tries})...')
        time.sleep(wait)
    return r


# List cuma perlu ~125 panggilan total (page_size=200 utk ~25rb order) jadi
# aman dibuat jauh lebih sabar - beda dgn detail yg dipanggil ribuan kali.
LIST_MAX_RETRY = 6
LIST_WAIT_BASE = 10


def fetch_all_orders(s, date_from, date_to):
    """Ambil semua order header (paginated) untuk rentang tanggal.

    PENTING: dataset Jubelio berubah live (order baru terus masuk) & pagination
    berbasis offset dengan urutan default "terbaru dulu" -> tiap ada order baru
    semua bergeser turun, sehingga order yang SAMA bisa muncul di 2 halaman.
    Kalau tidak di-dedupe, order kebaca 2x -> insert_receipt MENJUMLAHKAN qty
    (detail_key sama) -> qty jadi 2x lipat. Maka WAJIB dedupe by salesorder_id.
    """
    seen_ids = set()
    orders = []
    dup_count = 0
    page = 1
    while True:
        r = get_with_backoff(s, f'{CORE_API_BASE}/sales/v2/orders/', {
            'page': page, 'page_size': LIST_PAGE_SIZE,
            'transaction_date_from': date_from, 'transaction_date_to': date_to,
        }, tries=LIST_MAX_RETRY, wait_base=LIST_WAIT_BASE)

        if r is None or r.status_code != 200:
            status = r.status_code if r is not None else 'no response (exception)'
            log(f'  List GAGAL TOTAL di page {page} (status {status}) setelah {LIST_MAX_RETRY} retry — '
                f'berhenti, data setelah halaman ini TIDAK terambil di run ini.')
            break

        body = r.json()
        data = body.get('data', [])
        total = body.get('totalCount', 0)

        for o in data:
            sid = o.get('salesorder_id')
            if sid in seen_ids:
                dup_count += 1
                continue
            seen_ids.add(sid)
            orders.append(o)

        log(f'  Page {page}: +{len(data)} order (unik {len(orders)}, dobel dibuang {dup_count})')

        # Berhenti kalau halaman kosong, atau sudah lewati totalCount berdasarkan
        # jumlah baris terbaca (bukan unik) supaya loop pasti berhenti.
        if not data:
            break
        page += 1
        if page > (total // LIST_PAGE_SIZE) + 2:
            break
        time.sleep(0.6)

    if dup_count:
        log(f'  CATATAN: {dup_count} order dobel dibuang (efek pagination live) '
            f'-> mencegah qty ganda.')
    return orders


def check_status_map(noresi_list):
    """Tanya ke iresis: status_pesanan SAAT INI untuk tiap noresi (kalau ada).
    Dipakai untuk skip fetch detail Jubelio kalau status TIDAK BERUBAH -
    jauh lebih efektif daripada cuma skip yang COMPLETED, karena mayoritas
    resi di window H-3 sudah SHIPPED (status "final" versi Jubelio,
    walau bukan COMPLETED versi iresis)."""
    if not noresi_list:
        return {}
    url = f'{IRESIS_BASE_URL}cron/check_resi_status?token={IRESIS_CRON_TOKEN}'
    r = requests.post(url, json={'noresi': noresi_list}, timeout=60)
    r.raise_for_status()
    body = r.json()
    return body.get('status_map', {})


def fetch_order_detail(s, salesorder_id):
    r = get_with_backoff(s, f'{CORE_API_BASE}/sales/orders/{salesorder_id}', {})
    if r is None or r.status_code != 200:
        return None
    return r.json()


def build_rows_for_order(order, detail):
    noresi     = order.get('tracking_no') or ''
    no_pesanan = order.get('salesorder_no') or ''
    if not noresi or not no_pesanan:
        return []

    d_pesan, t_pesan     = utc_to_wib_parts(order.get('transaction_date'))
    d_batas, t_batas      = utc_to_wib_parts(detail.get('due_date'))
    d_selesai, t_selesai = utc_to_wib_parts(detail.get('completed_date'))

    rows = []
    for item in (detail.get('items') or []):
        sku = item.get('item_code')
        if not sku:
            continue
        try:
            qty = int(float(item.get('qty') or 0))
        except (TypeError, ValueError):
            qty = 0

        awb_date = item.get('awb_created_date') or detail.get('awb_created_date')
        d_kirim, t_kirim = utc_to_wib_parts(awb_date)

        # nomorpicklist ADA di Jubelio (field detail.picked_in) begitu order jadi
        # faktur/processing. Divalidasi: cocok 100% dgn nomorpicklist yg diinput staf.
        picked_in = detail.get('picked_in')
        nomorpicklist = str(picked_in) if picked_in not in (None, '', 0, '0') else ''

        rows.append({
            'A': no_pesanan,
            'B': noresi,
            'C': nomorpicklist,
            'D': d_pesan,   'E': t_pesan,
            'H': d_batas,   'I': t_batas,
            'J': d_kirim,   'K': t_kirim,
            'L': d_selesai, 'M': t_selesai,
            'N': order.get('channel_name') or '',
            'P': sku,
            'Q': qty,
            'R': '',
            'S': order.get('shipper') or '',
            'T': order.get('internal_status') or '',
            'U': '', 'V': '',
            'W': detail.get('wms_status') or order.get('wms_status') or '',
        })
    return rows


def upload_rows(rows):
    if not rows:
        return 'Tidak ada baris untuk diupload.'

    url = f'{IRESIS_BASE_URL}cron/auto_upload_resi_api?token={IRESIS_CRON_TOKEN}'
    messages = []
    for i in range(0, len(rows), UPLOAD_BATCH_SIZE):
        batch = rows[i:i + UPLOAD_BATCH_SIZE]
        log(f'  Upload batch {i // UPLOAD_BATCH_SIZE + 1} ({len(batch)} baris)...')
        r = requests.post(url, json=batch, timeout=300)
        text = r.text.strip()
        if not text:
            raise Exception(f'Response kosong dari server (status {r.status_code})')
        # Cron::_log() menulis 1 baris log plaintext sebelum JSON hasil -> ambil baris terakhir saja
        last_line = text.split('\n')[-1]
        try:
            result = json.loads(last_line)
        except Exception:
            raise Exception(f'Response bukan JSON valid (status {r.status_code}): {text[:300]}')
        if not result.get('success'):
            raise Exception(result.get('message', 'Upload gagal'))
        messages.append(result['message'])
        log(f'    OK: {result["message"]}')
    return ' | '.join(messages)


def main():
    log('=== AUTO UPLOAD RESI (Jubelio core-api, pure Python) ===')

    today = datetime.now()
    date_from = (today - timedelta(days=DAYS_BACK)).strftime('%Y-%m-%d')
    date_to   = today.strftime('%Y-%m-%d')
    log(f'Rentang tanggal: {date_from} s/d {date_to}')

    log('Login Jubelio core-api...')
    s = login_core_api()
    log('Login OK.')

    log('Ambil daftar order...')
    orders = fetch_all_orders(s, date_from, date_to)
    log(f'Total order didapat: {len(orders)}')

    orders = [o for o in orders if o.get('tracking_no')]
    log(f'Order dengan tracking_no (resi valid): {len(orders)}')

    tracking_list = [o['tracking_no'] for o in orders]
    log('Cek status existing di iresis (skip yang statusnya tidak berubah)...')
    status_map = check_status_map(tracking_list)
    log(f'Resi sudah tercatat di iresis: {len(status_map)}')

    pending_orders = []
    skip_unchanged = 0
    for o in orders:
        existing_status = status_map.get(o['tracking_no'])
        jubelio_status = (o.get('internal_status') or '').strip().upper()
        if existing_status is not None and existing_status == jubelio_status:
            skip_unchanged += 1
            continue
        pending_orders.append(o)

    log(f'Skip (status tidak berubah): {skip_unchanged}')
    log(f'Perlu ambil detail (baru / status berubah): {len(pending_orders)}')

    all_rows = []
    t_start = time.time()
    for i, order in enumerate(pending_orders, 1):
        detail = fetch_order_detail(s, order['salesorder_id'])
        if detail is None:
            log(f'  [{i}/{len(pending_orders)}] id={order["salesorder_id"]} GAGAL ambil detail, skip (akan dicoba lagi run berikutnya)')
            continue
        rows = build_rows_for_order(order, detail)
        all_rows.extend(rows)
        if i % 50 == 0 or i == len(pending_orders):
            elapsed = time.time() - t_start
            log(f'  [{i}/{len(pending_orders)}] progres... ({elapsed:.0f}s, {len(all_rows)} baris terkumpul)')
        time.sleep(DETAIL_DELAY)

    log(f'Total baris (per SKU) siap upload: {len(all_rows)}')

    if all_rows:
        result_msg = upload_rows(all_rows)
        log(f'Upload selesai: {result_msg}')
    else:
        log('Tidak ada data baru untuk diupload.')

    log('=== SELESAI ===')


if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        log(f'ERROR: {e}')
        sys.exit(1)
