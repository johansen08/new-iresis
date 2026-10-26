"""
Sinkron nominal pencairan dari Jubelio untuk menu Pencairan Dana (TIM FINANCE).

Untuk pesanan yang sudah COMPLETED di Iresis tetapi nilai_pesanan-nya masih kosong, ambil detail
pesanan di Jubelio lalu kirim:
  nilai_pesanan    <- escrow_list.revenue_amount (cadangan: grand_total)
  dana_diterima_mp <- escrow_list.settlement_amount
Pesanan tanpa escrow_list.settlement_amount (mis. TikTok: Jubelio hanya sampai harga final) tetap
disimpan nilai_pesanan-nya; dana_diterima_mp dibiarkan kosong. Pesanan tanpa nilai sama sekali dilewati. Hanya MEMBACA dari Jubelio; Iresis hanya mengisi yang kosong.

Pakai ulang login/list/detail dari auto_upload_resi_api.py (kredensial dari jubelio_credentials.py).

  python scripts/sinkron_nominal_mp.py            # 30 hari terakhir (tanggal transaksi)
  python scripts/sinkron_nominal_mp.py --hari 90
"""
import argparse
import sys
import time
from datetime import datetime, timedelta

import requests

import auto_upload_resi_api as api
from auto_upload_resi_api import log

BATCH_KIRIM = 500


def angka(v):
    try:
        return float(v)
    except (TypeError, ValueError):
        return None


def tanya_butuh(no_list):
    url = f'{api.IRESIS_BASE_URL}cron/nominal_mp_butuh?token={api.IRESIS_CRON_TOKEN}'
    butuh = []
    for i in range(0, len(no_list), 1000):
        r = requests.post(url, json={'no_pesanan': no_list[i:i + 1000]}, timeout=120)
        r.raise_for_status()
        body = r.json()
        if not body.get('success'):
            raise Exception(body.get('message', 'nominal_mp_butuh gagal'))
        butuh.extend(body.get('butuh', []))
    return butuh


def kirim(baris):
    url = f'{api.IRESIS_BASE_URL}cron/auto_upload_nominal_mp?token={api.IRESIS_CRON_TOKEN}'
    for i in range(0, len(baris), BATCH_KIRIM):
        r = requests.post(url, json=baris[i:i + BATCH_KIRIM], timeout=300)
        hasil = r.json() if r.text.strip() else {}
        if not hasil.get('success'):
            raise Exception(hasil.get('message', f'Upload gagal (status {r.status_code})'))
        log(f'    OK: {hasil["message"]}')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--hari', type=int, default=30, help='rentang tanggal transaksi ke belakang (default 30)')
    args = ap.parse_args()

    log('=== SINKRON NOMINAL PENCAIRAN (Jubelio -> Iresis) ===')
    today = datetime.now()
    dari = (today - timedelta(days=args.hari)).strftime('%Y-%m-%d')
    sampai = today.strftime('%Y-%m-%d')
    log(f'Rentang tanggal transaksi: {dari} s/d {sampai}')

    s = api.login_core_api()
    orders = api.fetch_all_orders(s, dari, sampai)
    selesai = [o for o in orders if (o.get('internal_status') or '').strip().upper() == 'COMPLETED' and o.get('salesorder_no')]
    log(f'Order COMPLETED di Jubelio: {len(selesai)}')

    butuh = set(tanya_butuh([o['salesorder_no'] for o in selesai]))
    target = [o for o in selesai if o['salesorder_no'] in butuh]
    log(f'Perlu nominal (COMPLETED di Iresis, nilai pesanan kosong): {len(target)}')

    baris, tanpa_escrow, t0 = [], 0, time.time()
    for i, o in enumerate(target, 1):
        d = api.fetch_order_detail(s, o['salesorder_id'])
        time.sleep(api.DETAIL_DELAY)
        if d is None:
            continue
        esc = d.get('escrow_list') or {}
        dana = angka(esc.get('settlement_amount'))
        nilai = angka(esc.get('revenue_amount'))
        if nilai is None:
            nilai = angka(d.get('grand_total'))
        if nilai is None:
            tanpa_escrow += 1
            continue
        baris.append({'no_pesanan': o['salesorder_no'], 'nilai_pesanan': nilai, 'dana_diterima_mp': dana})
        if i % 50 == 0 or i == len(target):
            log(f'  [{i}/{len(target)}] ({time.time() - t0:.0f}s, {len(baris)} siap kirim)')
        if len(baris) >= BATCH_KIRIM:
            kirim(baris)
            baris = []

    if baris:
        kirim(baris)
    log(f'Dilewati (tanpa nilai pesanan): {tanpa_escrow}')
    log('=== SELESAI ===')


if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        log(f'ERROR: {e}')
        sys.exit(1)
