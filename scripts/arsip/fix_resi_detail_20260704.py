"""
PERBAIKAN SEKALI-JALAN — data rusak run auto_upload_resi_api 2026-07-04
======================================================================
Bug: order dobel (efek pagination live) -> qty ter-GANDAKAN; dan no_rak kosong.
Script ini memperbaiki tbldetailprintresi untuk resi yang di-INSERT run pagi ini,
mengambil qty BENAR dari Jubelio, lalu UPDATE hanya kolom jumlah & no_rak
(non-destruktif, tidak menyentuh header/picklist/pick/pack yang sedang jalan).

Jalankan:
    python scripts/fix_resi_detail_20260704.py <path_affected_noresi.txt>

File input = daftar noresi (satu per baris) yang di-insert run bermasalah.
"""

import json
import os
import sys
import time
from datetime import datetime, timedelta

import requests

sys.path.insert(0, os.path.dirname(__file__))
from auto_upload_resi_api import (
    login_core_api, fetch_all_orders, fetch_order_detail, get_with_backoff,
    CORE_API_BASE, IRESIS_BASE_URL, IRESIS_CRON_TOKEN, DETAIL_DELAY, log,
)

DAYS_BACK_SCAN   = 4     # window untuk cari order (H-3 + buffer 1 hari)
CORRECTION_BATCH = 200   # resi per POST ke endpoint fix


def load_affected(path):
    with open(path, encoding='utf-8') as f:
        return [ln.strip() for ln in f if ln.strip()]


def correct_items_from_detail(detail):
    """Sum qty per item_code (dedupe baris item yang Jubelio pecah)."""
    agg = {}
    for it in (detail.get('items') or []):
        sku = it.get('item_code')
        if not sku:
            continue
        try:
            qty = int(float(it.get('qty') or 0))
        except (TypeError, ValueError):
            qty = 0
        agg[sku] = agg.get(sku, 0) + qty
    return [{'sku': k, 'qty': v} for k, v in agg.items()]


def post_corrections(corrections):
    url = f'{IRESIS_BASE_URL}cron/fix_resi_detail?token={IRESIS_CRON_TOKEN}'
    r = requests.post(url, json={'corrections': corrections}, timeout=300)
    text = r.text.strip()
    last = text.split('\n')[-1]
    try:
        return json.loads(last)
    except Exception:
        raise Exception(f'Response tidak valid (HTTP {r.status_code}): {text[:300]}')


def main():
    if len(sys.argv) < 2:
        log('Usage: python fix_resi_detail_20260704.py <affected_noresi.txt>')
        sys.exit(1)

    affected = set(load_affected(sys.argv[1]))
    log(f'Resi terdampak yang akan diperbaiki: {len(affected)}')

    log('Login Jubelio core-api...')
    s = login_core_api()

    today = datetime.now()
    date_from = (today - timedelta(days=DAYS_BACK_SCAN)).strftime('%Y-%m-%d')
    date_to   = today.strftime('%Y-%m-%d')
    log(f'Ambil daftar order {date_from} s/d {date_to} (untuk map tracking_no -> id)...')
    orders = fetch_all_orders(s, date_from, date_to)

    # map tracking_no -> salesorder_id, hanya untuk resi terdampak
    id_map = {}
    for o in orders:
        tn = o.get('tracking_no')
        if tn and tn in affected:
            id_map[tn] = o['salesorder_id']
    log(f'Resi ketemu dari daftar: {len(id_map)} / {len(affected)}')

    # FALLBACK: pagination live bisa MELEWATKAN sebagian order dari daftar.
    # Untuk resi terdampak yang tidak ketemu, cari satu-satu via q= (terbukti
    # selalu ketemu). Verifikasi tracking_no sama persis sebelum dipakai.
    missing = sorted(affected - set(id_map.keys()))
    if missing:
        log(f'{len(missing)} resi tidak ada di daftar -> cari satu-satu via q= ...')
        for i, noresi in enumerate(missing, 1):
            r = get_with_backoff(s, f'{CORE_API_BASE}/sales/v2/orders/',
                                 {'page': 1, 'page_size': 5, 'q': noresi})
            if r is not None and r.status_code == 200:
                for o in r.json().get('data', []):
                    if o.get('tracking_no') == noresi:
                        id_map[noresi] = o['salesorder_id']
                        break
            if i % 100 == 0:
                log(f'    q-search {i}/{len(missing)} (ketemu total {len(id_map)})')
            time.sleep(DETAIL_DELAY)
    log(f'Total resi siap diperbaiki: {len(id_map)} / {len(affected)}')

    corrections = []
    tot = {'resi_touched': 0, 'qty_fixed': 0, 'rak_fixed': 0, 'picklist_fixed': 0, 'resi_notfound': 0}
    processed = 0
    t0 = time.time()

    for noresi, sid in id_map.items():
        detail = fetch_order_detail(s, sid)
        if detail is None:
            continue
        items = correct_items_from_detail(detail)
        picked_in = detail.get('picked_in')
        nomorpicklist = str(picked_in) if picked_in not in (None, '', 0, '0') else ''
        if items or nomorpicklist:
            corrections.append({
                'noresi': noresi,
                'nomorpicklist': nomorpicklist,
                'items': items,
            })
        processed += 1

        if len(corrections) >= CORRECTION_BATCH:
            res = post_corrections(corrections)
            for k in tot:
                tot[k] += res.get(k, 0)
            corrections = []

        if processed % 100 == 0:
            el = time.time() - t0
            log(f'  {processed}/{len(id_map)} diproses ({el:.0f}s) '
                f'qty_fixed={tot["qty_fixed"]} rak_fixed={tot["rak_fixed"]}')
        time.sleep(DETAIL_DELAY)

    if corrections:
        res = post_corrections(corrections)
        for k in tot:
            tot[k] += res.get(k, 0)

    log('=== SELESAI PERBAIKAN ===')
    log(f'  Resi diperbaiki   : {tot["resi_touched"]}')
    log(f'  qty diperbaiki    : {tot["qty_fixed"]}')
    log(f'  no_rak diisi      : {tot["rak_fixed"]}')
    log(f'  picklist dipulihkan: {tot["picklist_fixed"]}')
    log(f'  resi tak ketemu   : {tot["resi_notfound"]}')


if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        log(f'ERROR: {e}')
        sys.exit(1)
