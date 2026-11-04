# -*- coding: utf-8 -*-
"""
Refresh status resi berkala (docs/RANCANGAN_REFRESH_STATUS_RESI.md).

auto_upload_resi_api.py hanya menarik pesanan H-3 s/d hari ini, jadi resi yang lebih
tua membeku di status terakhirnya (mis. SHIPPED padahal sudah COMPLETED di marketplace).
Skrip ini mengecek ulang HANYA resi non-final yang jatuh tempo:

  harian   : umur 4-14 hari, sekali sehari
  mingguan : umur 15-60 hari, sekali seminggu
  backlog  : umur > 60 hari, SEKALI saja per resi

Status final (CANCELED, COMPLETED, RETURNED) tidak pernah dipanggil ke Jubelio.

Alur per mode:
  1. GET cron/kandidat_refresh_status  -> noresi jatuh tempo + rentang tanggalnya
  2. Ambil daftar order Jubelio untuk rentang itu, saring yang noresi-nya kandidat
  3. cron/check_resi_status: status Jubelio == status IRESIS -> cukup ditandai dicek
  4. Status beda -> ambil detail (maks --maks-detail per mode), upload lewat
     cron/auto_upload_resi_api (jalur upsert yang SAMA dengan sinkron harian,
     jadi aturan skip COMPLETED & validasinya tidak diduplikasi)
  5. cron/tandai_status_dicek -> isi tblprintresi.status_dicek_at

Pakai:
  python refresh_status_resi.py --kering --maks-detail 20      # uji: tidak menulis apa pun
  python refresh_status_resi.py --mode harian --maks-detail 100
  python refresh_status_resi.py                                 # semua mode (jadwal malam)

--kering tetap membaca Jubelio tapi TIDAK upload dan TIDAK menandai.
"""
import argparse
import os
import sys
import time
from datetime import datetime, timedelta

import requests

sys.path.insert(0, os.path.dirname(__file__))
import auto_upload_resi_api as base  # noqa: E402  (login, fetch_all_orders, dst. dipakai ulang)
try:  # alamat IRESIS khusus PC ini (opsional) di scripts/secrets_local.py
    from secrets_local import IRESIS_BASE_URL as _URL  # noqa: E402
    base.IRESIS_BASE_URL = _URL
except ImportError:
    pass  # pakai bawaan auto_upload_resi_api.py

MODE_DEFAULT = {
    # mode: (maks_detail, batas_kandidat)
    'harian':   (8000, 20000),
    'mingguan': (3000, 10000),
    'backlog':  (3000, 5000),
}
PAD_DARI_HARI = 5      # transaction_date Jubelio bisa mendahului tanggal cetak resi
PAD_SAMPAI_HARI = 1
TANDAI_BATCH = 2000


def url_cron(nama, **query):
    q = ''.join(f'&{k}={v}' for k, v in query.items())
    return f'{base.IRESIS_BASE_URL}cron/{nama}?token={base.IRESIS_CRON_TOKEN}{q}'


def json_terakhir(r):
    """Cron::_log() boleh menulis baris plaintext sebelum JSON -> ambil baris terakhir."""
    teks = r.text.strip()
    if not teks:
        raise Exception(f'Response kosong (status {r.status_code})')
    import json
    try:
        return json.loads(teks.split('\n')[-1])
    except Exception:
        raise Exception(f'Response bukan JSON (status {r.status_code}): {teks[:300]}')


def ambil_kandidat(mode, batas):
    r = requests.get(url_cron('kandidat_refresh_status', mode=mode, limit=batas), timeout=120)
    hasil = json_terakhir(r)
    if not hasil.get('success'):
        raise Exception(hasil.get('message', 'kandidat gagal'))
    return hasil


def tandai_dicek(noresi_list):
    total = 0
    for i in range(0, len(noresi_list), TANDAI_BATCH):
        batch = noresi_list[i:i + TANDAI_BATCH]
        r = requests.post(url_cron('tandai_status_dicek'), json={'noresi': batch}, timeout=120)
        hasil = json_terakhir(r)
        if not hasil.get('success'):
            raise Exception(hasil.get('message', 'tandai gagal'))
        total += int(hasil.get('ditandai', 0))
    return total


def proses_mode(s, mode, maks_detail, batas_kandidat, kering):
    base.log(f'=== MODE {mode.upper()} (maks detail {maks_detail}, batas kandidat {batas_kandidat}'
             f'{", KERING" if kering else ""}) ===')

    kand = ambil_kandidat(mode, batas_kandidat)
    due = set(kand['noresi'])
    base.log(f'Kandidat jatuh tempo: {len(due)} (rentang {kand.get("dari")} s/d {kand.get("sampai")})')
    if not due:
        return {'kandidat': 0}

    dari = (datetime.strptime(kand['dari'], '%Y-%m-%d') - timedelta(days=PAD_DARI_HARI)).strftime('%Y-%m-%d')
    sampai = (datetime.strptime(kand['sampai'], '%Y-%m-%d') + timedelta(days=PAD_SAMPAI_HARI)).strftime('%Y-%m-%d')
    base.log(f'Ambil daftar order Jubelio {dari} s/d {sampai}...')
    orders = base.fetch_all_orders(s, dari, sampai)
    orders = [o for o in orders if o.get('tracking_no') in due]
    ada_di_jubelio = {o['tracking_no'] for o in orders}
    tidak_ketemu = due - ada_di_jubelio
    # Daftar order Jubelio dibaca per halaman (offset) dan bergeser kalau ada order baru masuk,
    # sehingga di rentang yang masih "hidup" (mode harian/mingguan) sebagian order terlewat
    # (uji 4 Nov 2026: 13 ribu duplikat, 20-55% kandidat "tidak ketemu" padahal ada).
    # Maka resi tidak ketemu HANYA ditandai di mode backlog (rentang > 60 hari, data statis)
    # dan hanya bila daftar lengkap. Di mode lain dibiarkan -> dicoba lagi di run berikutnya.
    if mode != 'backlog' or not base.LIST_LENGKAP:
        if mode == 'backlog':
            base.log('PERINGATAN: daftar Jubelio tidak lengkap; resi "tidak ketemu" TIDAK ditandai (dicoba lagi besok).')
        tidak_ketemu_ditandai = set()
    else:
        tidak_ketemu_ditandai = tidak_ketemu
    base.log(f'Ketemu di Jubelio: {len(ada_di_jubelio)} | tidak ketemu: {len(tidak_ketemu)}')

    status_map = base.check_status_map([o['tracking_no'] for o in orders])
    tanpa_perubahan = []
    butuh_detail = []
    for o in orders:
        sekarang = status_map.get(o['tracking_no'])
        jubelio = (o.get('internal_status') or '').strip().upper()
        if sekarang is not None and sekarang == jubelio:
            tanpa_perubahan.append(o['tracking_no'])
        else:
            butuh_detail.append(o)
    base.log(f'Status sama (cukup ditandai): {len(tanpa_perubahan)} | status beda (perlu detail): {len(butuh_detail)}')

    antre = butuh_detail[:maks_detail]
    sisa = len(butuh_detail) - len(antre)
    if sisa > 0:
        base.log(f'Melebihi jatah detail: {sisa} resi ditunda ke run berikutnya (tidak ditandai).')

    rows = []
    berhasil = []
    t0 = time.time()
    for i, order in enumerate(antre, 1):
        detail = base.fetch_order_detail(s, order['salesorder_id'])
        if detail is None:
            base.log(f'  [{i}/{len(antre)}] id={order["salesorder_id"]} GAGAL ambil detail, dilewati (tidak ditandai)')
            continue
        r = base.build_rows_for_order(order, detail)
        if r:
            rows.extend(r)
            berhasil.append(order['tracking_no'])
        if i % 50 == 0 or i == len(antre):
            base.log(f'  [{i}/{len(antre)}] ({time.time() - t0:.0f}s, {len(rows)} baris)')
        time.sleep(base.DETAIL_DELAY)

    ringkas = {
        'kandidat': len(due), 'tanpa_perubahan': len(tanpa_perubahan),
        'diperbarui': len(berhasil), 'tidak_ketemu': len(tidak_ketemu), 'ditunda': sisa,
    }

    if kering:
        base.log(f'KERING: tidak upload, tidak menandai. Ringkas: {ringkas}')
        return ringkas

    if rows:
        base.log(f'Upload {len(rows)} baris...')
        base.log(f'Upload selesai: {base.upload_rows(rows)}')

    # Tandai dicek: yang statusnya sama, yang sudah diperbarui, dan yang tidak ada di Jubelio
    # (supaya tidak diulang terus; resi seperti itu tetap kembali lewat jadwal harian/mingguan).
    ditandai = tandai_dicek(sorted(set(tanpa_perubahan) | set(berhasil) | tidak_ketemu_ditandai))
    base.log(f'Ditandai dicek: {ditandai}. Ringkas: {ringkas}')
    return ringkas


def main():
    ap = argparse.ArgumentParser(description='Refresh status resi non-final berkala')
    ap.add_argument('--mode', choices=['harian', 'mingguan', 'backlog', 'semua'], default='semua')
    ap.add_argument('--maks-detail', type=int, default=None, help='jatah panggilan detail Jubelio per mode')
    ap.add_argument('--kering', action='store_true', help='baca saja: tidak upload, tidak menandai')
    args = ap.parse_args()

    base.log('=== REFRESH STATUS RESI ===')
    s = base.login_core_api()
    base.log('Login OK.')

    daftar = ['harian', 'mingguan', 'backlog'] if args.mode == 'semua' else [args.mode]
    gagal = 0
    for mode in daftar:
        maks, batas = MODE_DEFAULT[mode]
        if args.maks_detail is not None:
            maks = args.maks_detail
            batas = min(batas, max(maks * 3, 100))
        try:
            proses_mode(s, mode, maks, batas, args.kering)
        except Exception as e:  # satu mode gagal tidak boleh menggagalkan mode lain
            gagal += 1
            base.log(f'ERROR mode {mode}: {e}')
    base.log('=== SELESAI ===')
    if gagal:
        sys.exit(1)


if __name__ == '__main__':
    main()
