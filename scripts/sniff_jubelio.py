"""
Sniff Jubelio - Perekam semua request/response jaringan saat Anda melakukan
proses manual "Upload Retur Jubelio".

Tujuan: menangkap API asli yang dipakai Jubelio saat men-download
"daftar retur penjualan" (tombol Cetak) + request upload ke iresis, supaya
proses bisa dibuat otomatis lewat API (bukan klik tombol browser).

Cara pakai:
    python scripts/sniff_jubelio.py

Lalu di jendela Chrome yang terbuka, lakukan proses manual seperti biasa:
    1. Login ke Jubelio (kalau belum login)
    2. Penjualan > Laporan > pilih "Daftar Retur Penjualan", atur tanggal, klik "Cetak"
       (biarkan tab laporan yang terbuka sampai file selesai ter-download)
    3. (opsional) buka iresis dan upload filenya manual

Semua request akan direkam LANGSUNG ke file (aman walau script ditutup paksa):
    scripts/sniff_out/jubelio_sniff_<timestamp>.jsonl   -> data mentah lengkap
    scripts/sniff_out/jubelio_sniff_<timestamp>.log     -> feed ringkas dibaca manusia

Tekan Ctrl+C di terminal untuk berhenti. Script akan menulis file ringkasan
"..._summary.txt" berisi kandidat endpoint download/export yang paling relevan.

PENTING: file hasil rekaman berisi token & cookie login Anda. Jangan di-commit
ke git / dibagikan sembarangan. Folder sniff_out/ sudah otomatis dilewati.
"""

import json
import sys
import time
import threading
import argparse
from datetime import datetime
from pathlib import Path

import DrissionPage

# ------------------------------------------------------------------ konfigurasi
# Profil Chrome khusus sniff -> Anda cukup login sekali, login tersimpan.
CHROME_USER_DIR = r'C:\MP\chrome_sniff_profile'
OUT_DIR = Path(__file__).resolve().parent / 'sniff_out'

# URL yang dianggap "menarik" untuk ringkasan akhir (case-insensitive).
INTEREST_KEYWORDS = [
    'report', 'reports', 'export', 'download', 'print', 'cetak',
    'retur', 'return', 'sales', 'salesorder', 'penjualan', 'file',
    'upload', 'jubelio', 'iresis',
    # --- tambahan untuk berburu API KEY + endpoint Data API ---
    'api2', 'api-key', 'apikey', 'api_key', 'apikeys', 'access-key',
    'token', 'setting', 'settings', 'integration', 'integrasi',
    'credential', 'oauth', 'secret', 'developer', 'webhook',
]

# Domain Data API resmi Jubelio (target otomasi server-side). Semua request
# ke domain ini otomatis dianggap sangat menarik.
API_DOMAINS = ('api2.jubelio.com', 'api.jubelio.com')

# Nama header/field yang kemungkinan berisi API KEY / token rahasia.
# (dicocokkan case-insensitive sebagai substring nama header/param/field.)
KEY_NAME_HINTS = (
    'authorization', 'api-key', 'apikey', 'api_key', 'x-api-key',
    'access-key', 'access_token', 'token', 'secret', 'client-secret',
    'client_secret', 'x-jubelio', 'x-auth',
)

# Jangan simpan body response yang bukan teks / terlalu besar.
MAX_BODY_CHARS = 200_000
TEXT_CONTENT_TYPES = ('application/json', 'text/', 'application/javascript', 'application/xml')


def log(msg):
    ts = datetime.now().strftime('%H:%M:%S')
    line = f'[{ts}] {msg}'
    try:
        print(line)
    except UnicodeEncodeError:
        print(line.encode('ascii', 'replace').decode())


def is_interesting(url):
    u = (url or '').lower()
    if any(d in u for d in API_DOMAINS):
        return True
    return any(k in u for k in INTEREST_KEYWORDS)


def safe(getter, default=None):
    """Ambil nilai dari packet dengan aman (atribut kadang tidak ada)."""
    try:
        val = getter()
        return val if val is not None else default
    except Exception:
        return default


def extract_response_body(resp):
    """Ambil body response hanya jika berupa teks & tidak terlalu besar."""
    try:
        headers = resp.headers or {}
        ctype = ''
        for k, v in headers.items():
            if k.lower() == 'content-type':
                ctype = (v or '').lower()
                break
        if ctype and not any(ct in ctype for ct in TEXT_CONTENT_TYPES):
            return f'<skip: content-type={ctype}>'
        body = resp.body
        if isinstance(body, (dict, list)):
            body = json.dumps(body, ensure_ascii=False)
        if isinstance(body, bytes):
            return f'<binary {len(body)} bytes>'
        body = str(body)
        if len(body) > MAX_BODY_CHARS:
            return body[:MAX_BODY_CHARS] + f'\n<...terpotong, total {len(body)} char>'
        return body
    except Exception as e:
        return f'<gagal ambil body: {e}>'


def packet_to_record(packet):
    req = safe(lambda: packet.request)
    resp = safe(lambda: packet.response)

    record = {
        'time': datetime.now().isoformat(timespec='seconds'),
        'method': safe(lambda: packet.method),
        'url': safe(lambda: packet.url),
        'resource_type': safe(lambda: packet.resourceType),
        'is_failed': safe(lambda: packet.is_failed, False),
        'request': {
            'headers': safe(lambda: dict(req.headers), {}) if req else {},
            'post_data': safe(lambda: req.postData) if req else None,
            'params': safe(lambda: req.params) if req else None,
        },
        'response': {
            'status': safe(lambda: resp.status) if resp else None,
            'headers': safe(lambda: dict(resp.headers), {}) if resp else {},
            'body': extract_response_body(resp) if resp else None,
        },
    }
    return record


def _iter_name_value(obj, prefix=''):
    """Ratakan dict/JSON-string jadi pasangan (nama, nilai) untuk dipindai."""
    if isinstance(obj, str):
        s = obj.strip()
        if s.startswith('{') or s.startswith('['):
            try:
                obj = json.loads(s)
            except Exception:
                return
        else:
            return
    if isinstance(obj, dict):
        for k, v in obj.items():
            name = f'{prefix}{k}'
            if isinstance(v, (dict, list)):
                yield from _iter_name_value(v, prefix=f'{name}.')
            else:
                yield name, v
    elif isinstance(obj, list):
        for i, v in enumerate(obj):
            yield from _iter_name_value(v, prefix=f'{prefix}{i}.')


def _looks_like_key_name(name):
    n = (name or '').lower()
    return any(h in n for h in KEY_NAME_HINTS)


def scan_for_secrets(records, keys_path):
    """Kumpulkan semua kandidat API KEY / token + endpoint yang memakainya.

    Sumber pindaian: header request, query param, body request (login),
    dan body response (mis. api2 /login mengembalikan token; halaman setting
    API mengembalikan api_key).
    """
    # value -> {'names': set, 'endpoints': set(method+base), 'where': set}
    found = {}

    def add(value, name, endpoint, where):
        if value is None:
            return
        val = value if isinstance(value, str) else json.dumps(value, ensure_ascii=False)
        val = val.strip()
        # buang nilai yang jelas bukan rahasia (terlalu pendek / boolean / angka)
        if len(val) < 12 or val.lower() in ('true', 'false', 'null', 'bearer'):
            return
        e = found.setdefault(val, {'names': set(), 'endpoints': set(), 'where': set()})
        e['names'].add(name)
        e['endpoints'].add(endpoint)
        e['where'].add(where)

    for r in records:
        method = r.get('method') or '?'
        base = (r.get('url') or '').split('?', 1)[0]
        endpoint = f'{method} {base}'
        on_api_domain = any(d in (r.get('url') or '') for d in API_DOMAINS)
        req = r.get('request') or {}
        resp = r.get('response') or {}

        # 1) header request bernama key-ish
        for hk, hv in (req.get('headers') or {}).items():
            if _looks_like_key_name(hk):
                add(hv, f'req.header:{hk}', endpoint, 'request-header')

        # 2) query params bernama key-ish
        params = req.get('params')
        if isinstance(params, dict):
            for pk, pv in params.items():
                if _looks_like_key_name(pk):
                    add(pv, f'req.param:{pk}', endpoint, 'query-param')

        # 3) body request (mis. payload login) -> ambil field key-ish
        for nm, vv in _iter_name_value(req.get('post_data') or {}):
            if _looks_like_key_name(nm):
                add(vv, f'req.body:{nm}', endpoint, 'request-body')

        # 4) body response -> field key-ish (token/api_key). Untuk domain API,
        #    pindai lebih agresif karena di situlah key biasanya dikembalikan.
        body = resp.get('body')
        for nm, vv in _iter_name_value(body or {}):
            if _looks_like_key_name(nm):
                add(vv, f'resp.body:{nm}', endpoint, 'response-body')
            elif on_api_domain and isinstance(vv, str) and len(vv) >= 24:
                # kandidat longgar khusus domain API (nama field tak baku)
                add(vv, f'resp.body?:{nm}', endpoint, 'response-body(api-domain)')

    lines = ['KANDIDAT API KEY / TOKEN + ENDPOINT PEMAKAINYA',
             '=' * 70,
             'CATATAN: nilai di bawah RAHASIA. Jangan commit / bagikan.',
             f'Total kandidat unik: {len(found)}',
             '']
    if not found:
        lines.append('(tidak ada kandidat key terdeteksi — pastikan Anda membuka '
                     'halaman Setting > API / Integrasi, atau memakai key-nya)')
    # urutkan: yang muncul di banyak tempat & bukan JWT web-login dulu
    def rank(kv):
        val, meta = kv
        is_jwt = val.startswith('eyJ')  # JWT web-login (yg rate-limited)
        return (is_jwt, -len(meta['endpoints']))

    for val, meta in sorted(found.items(), key=rank):
        is_jwt = val.startswith('eyJ')
        tag = ' [JWT web-login — RATE LIMITED]' if is_jwt else ''
        lines.append(f'KEY{tag}:')
        lines.append(f'  value : {val}')
        lines.append(f'  names : {", ".join(sorted(meta["names"]))}')
        lines.append(f'  where : {", ".join(sorted(meta["where"]))}')
        lines.append('  dipakai di endpoint:')
        for ep in sorted(meta['endpoints']):
            lines.append(f'    - {ep}')
        lines.append('')

    keys_path.write_text('\n'.join(lines), encoding='utf-8')
    log(f'File kandidat key ditulis: {keys_path}')
    # tampilkan ringkas di terminal (nilai dipotong biar tidak bocor penuh di layar)
    print('\n=== KANDIDAT API KEY (ringkas) ===')
    for val, meta in sorted(found.items(), key=rank):
        preview = val[:18] + '…' if len(val) > 18 else val
        print(f'  [{"JWT" if val.startswith("eyJ") else "KEY"}] {preview}  '
              f'({", ".join(sorted(meta["names"]))})')
    print(f'  -> nilai lengkap ada di: {keys_path}\n')


def write_summary(records, summary_path):
    """Kelompokkan endpoint menarik supaya mudah dianalisis."""
    seen = {}
    for r in records:
        url = r.get('url') or ''
        if not is_interesting(url):
            continue
        # kunci = method + path (buang query supaya ringkas)
        base = url.split('?', 1)[0]
        key = f"{r.get('method')} {base}"
        entry = seen.setdefault(key, {'count': 0, 'statuses': set(), 'example': r})
        entry['count'] += 1
        if r['response'].get('status') is not None:
            entry['statuses'].add(r['response']['status'])

    lines = []
    lines.append('RINGKASAN ENDPOINT MENARIK (kandidat API download/upload)')
    lines.append('=' * 70)
    lines.append(f'Total request direkam: {len(records)}')
    lines.append(f'Endpoint menarik unik : {len(seen)}')
    lines.append('')

    for key, entry in sorted(seen.items(), key=lambda kv: -kv[1]['count']):
        statuses = ','.join(str(s) for s in sorted(entry['statuses'])) or '-'
        lines.append(f'[{entry["count"]}x] status={statuses}  {key}')
        ex = entry['example']
        pd = ex['request'].get('post_data')
        if pd:
            pd_str = pd if isinstance(pd, str) else json.dumps(pd, ensure_ascii=False)
            lines.append(f'      payload: {pd_str[:500]}')
        auth = ex['request']['headers'].get('authorization') or ex['request']['headers'].get('Authorization')
        if auth:
            lines.append(f'      auth   : {auth[:60]}...')
        lines.append('')

    summary_path.write_text('\n'.join(lines), encoding='utf-8')
    log(f'Ringkasan ditulis: {summary_path}')
    # tampilkan juga di terminal
    print('\n' + '\n'.join(lines[:60]))


def main():
    parser = argparse.ArgumentParser(description='Sniff request Jubelio/iresis untuk otomasi retur.')
    parser.add_argument('--all', action='store_true',
                        help='Rekam SEMUA domain (default: semua tetap direkam, flag ini hanya menonaktifkan filter feed terminal).')
    parser.add_argument('--profile', default=CHROME_USER_DIR,
                        help='Path profil Chrome (default: %(default)s).')
    parser.add_argument('--from-jsonl', metavar='PATH', default=None,
                        help='Jangan buka browser; regenerate summary + keys '
                             'dari file .jsonl hasil rekaman sebelumnya.')
    args = parser.parse_args()

    OUT_DIR.mkdir(parents=True, exist_ok=True)

    # --- mode regenerate dari jsonl (tanpa browser) ------------------------
    if args.from_jsonl:
        src = Path(args.from_jsonl)
        if not src.exists():
            log(f'File tidak ditemukan: {src}')
            return
        records = []
        with open(src, encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line:
                    continue
                try:
                    records.append(json.loads(line))
                except Exception:
                    pass
        log(f'Dibaca {len(records)} record dari {src}')
        base = src.with_suffix('')  # buang .jsonl
        write_summary(records, Path(str(base) + '_summary.txt'))
        scan_for_secrets(records, Path(str(base) + '_keys.txt'))
        log('Selesai (regenerate).')
        return
    stamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    jsonl_path = OUT_DIR / f'jubelio_sniff_{stamp}.jsonl'
    readable_path = OUT_DIR / f'jubelio_sniff_{stamp}.log'
    summary_path = OUT_DIR / f'jubelio_sniff_{stamp}_summary.txt'
    keys_path = OUT_DIR / f'jubelio_sniff_{stamp}_keys.txt'

    log('=== SNIFF JUBELIO (mode: berburu API KEY + endpoint) ===')
    log(f'Output mentah  : {jsonl_path}')
    log(f'Output ringkas : {readable_path}')
    log(f'Kandidat key   : {keys_path}')

    options = DrissionPage.ChromiumOptions()
    options.set_user_data_path(args.profile)
    options.set_argument('--window-size=1400,900')
    page = DrissionPage.ChromiumPage(options)
    page.get('https://v2.jubelio.com/')

    log('Chrome terbuka. Silakan lakukan proses manual Anda sekarang.')
    log('  1) Login Jubelio bila perlu (email sudah tersimpan di profil)')
    log('  2) UNTUK BERBURU API KEY: buka Pengaturan/Setting > "API" / '
        '"Integrasi" / "Developer" — generate / tampilkan API Key,')
    log('     lalu (kalau ada) klik tombol Test/Copy supaya key terkirim '
        'sebagai request.')
    log('  3) Buka juga data yang mau ditarik (mis. Penjualan > Retur) agar '
        'endpoint & param tanggalnya ikut terekam.')
    log('  4) (opsional) Cetak "Daftar Retur Penjualan" seperti biasa.')
    log('Tekan Ctrl+C di sini untuk berhenti & buat ringkasan + file key.')

    # --- multi-tab capture -------------------------------------------------
    # Tombol "Cetak" membuka laporan di TAB BARU (report-prod.jubelio.com),
    # jadi kita pasang listener di setiap tab yang muncul, bukan hanya tab awal.
    records = []
    counter = {'n': 0}
    lock = threading.Lock()
    stop_event = threading.Event()

    fj = open(jsonl_path, 'a', encoding='utf-8')
    fr = open(readable_path, 'a', encoding='utf-8')

    def reader(tab, label):
        try:
            tab.listen.start(True)
        except Exception as e:
            log(f'[{label}] gagal mulai listen: {e}')
            return
        while not stop_event.is_set():
            try:
                for packet in tab.listen.steps(timeout=2):
                    if stop_event.is_set():
                        break
                    rec = packet_to_record(packet)
                    rec['tab'] = label
                    url = rec['url'] or ''
                    short = url if len(url) <= 90 else url[:87] + '...'
                    with lock:
                        counter['n'] += 1
                        n = counter['n']
                        records.append(rec)
                        fj.write(json.dumps(rec, ensure_ascii=False) + '\n')
                        fj.flush()
                        fr.write(f"{rec['time']}  [{label}] {rec['method']:<5} "
                                 f"{str(rec['response'].get('status')):<4} {short}\n")
                        fr.flush()
                    if args.all or is_interesting(url):
                        marker = '>>' if is_interesting(url) else '  '
                        log(f'{marker} #{n} [{label}] {rec["method"]} '
                            f'{rec["response"].get("status")} {short}')
            except Exception:
                # tab mungkin tertutup / belum siap; jeda singkat lalu cek lagi
                time.sleep(0.3)

    threads = {}

    def ensure_tab_listeners():
        try:
            ids = list(page.tab_ids)
        except Exception:
            return
        for i, tid in enumerate(ids):
            if tid in threads:
                continue
            try:
                tab = page if tid == page.tab_id else page.get_tab(tid)
            except Exception:
                continue
            label = f'tab{len(threads) + 1}'
            t = threading.Thread(target=reader, args=(tab, label), daemon=True)
            t.start()
            threads[tid] = t
            log(f'-- merekam {label} (tab {tid[:8]})')

    try:
        while True:
            ensure_tab_listeners()   # tangkap tab baru (mis. report-prod)
            time.sleep(0.3)
    except KeyboardInterrupt:
        log('')
        log(f'Dihentikan. Total {counter["n"]} request direkam.')
    finally:
        stop_event.set()
        try:
            fj.close(); fr.close()
        except Exception:
            pass
        # Tulis summary + keys DULUAN (paling penting). Bungkus terhadap Ctrl+C
        # kedua supaya proses penulisan tidak batal di tengah jalan.
        if records:
            for _ in range(3):
                try:
                    write_summary(records, summary_path)
                    scan_for_secrets(records, keys_path)
                    break
                except KeyboardInterrupt:
                    log('(Ctrl+C diabaikan sampai file hasil selesai ditulis...)')
                    continue
        try:
            page.quit()
        except Exception:
            pass
        log('Selesai.')


if __name__ == '__main__':
    main()
