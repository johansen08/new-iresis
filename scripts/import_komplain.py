import pandas as pd
import pymysql
import math
from datetime import datetime

# Database connection
conn = pymysql.connect(
    host='127.0.0.1',
    user='root',
    password='',
    database='iresis-prod',
    cursorclass=pymysql.cursors.DictCursor
)

file_path = r"C:\Users\User\Downloads\LAPORAN KOMPLAIN - JUNI 2026 (RISNA).xlsx"
df = pd.read_excel(file_path, sheet_name='DATA BASE Laporan Komplain', header=3)

valid_df = df[df['Nomor Resi'].notna()]

# Mapping marketplace
mp_map = {
    'SP': 1, # Shopee
    'LZ': 2, # Lazada
    'TP': 3, # Tokopedia
    'TT': 5, # Tiktok
}

# Counters
inserted = 0
skipped = 0

with conn.cursor() as cursor:
    for index, row in valid_df.iterrows():
        no_resi = str(row['Nomor Resi']).strip()
        if no_resi == 'nan' or not no_resi:
            continue
            
        # Check if already exists
        cursor.execute("SELECT no_resi FROM tblcs_complain WHERE no_resi = %s AND is_deleted = 0", (no_resi,))
        if cursor.fetchone():
            skipped += 1
            continue
            
        mp_str = str(row['MP']).strip().upper()
        id_marketplace = mp_map.get(mp_str, 6) # Default 6 (Reseller/Other)
        
        toko = str(row['Nama Toko']).strip() if pd.notna(row['Nama Toko']) else ''
        kategori_excel = str(row['Kategori']).strip().upper()
        
        # Determine Kategori Komplain
        kategori_db = 'Lainnya'
        if 'KURANG KIRIM' in kategori_excel:
            kategori_db = 'Kurang Kirim'
        elif 'REJECT' in kategori_excel:
            kategori_db = 'Reject'
        elif 'SALAH KIRIM' in kategori_excel:
            kategori_db = 'Salah Kirim'
        elif 'KOSONG' in kategori_excel:
            kategori_db = 'Paket Kosong'
        
        # Find detail masalah to put in 'detail_lainnya' if category is Reject
        detail_lainnya = ''
        if pd.notna(row.get('Unnamed: 28')):
            detail_lainnya = str(row['Unnamed: 28']).strip()
            
        nama_qc = ''
        if pd.notna(row.get('Unnamed: 33')):
            nama_qc = str(row['Unnamed: 33']).strip()
            
        nama_packer = ''
        if pd.notna(row.get('Unnamed: 35')):
            nama_packer = str(row['Unnamed: 35']).strip()
            
        nominal_total = float(row['Nominal']) if pd.notna(row['Nominal']) else 0.0
        
        # Created at and by
        created_at_val = row['Tanggal Input Admin']
        if pd.notna(created_at_val) and isinstance(created_at_val, datetime):
            created_at = created_at_val.strftime('%Y-%m-%d %H:%M:%S')
        else:
            created_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            
        created_by = 167 # Default from excel
        try:
            if pd.notna(row['Nama Yg Input']):
                created_by = int(row['Nama Yg Input'])
        except:
            pass
            
        # Insert master
        sql_master = """
        INSERT INTO tblcs_complain (
            no_resi, id_marketplace, toko, kategori_komplain, detail_lainnya, 
            nama_qc, nama_packer, nominal_total, created_at, created_by, is_deleted
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 0)
        """
        cursor.execute(sql_master, (
            no_resi, id_marketplace, toko, kategori_db, detail_lainnya,
            nama_qc, nama_packer, nominal_total, created_at, created_by
        ))
        
        # Insert detail (we use QTY and Nominal to populate detail)
        sku_str = str(row['SKU']).strip() if pd.notna(row['SKU']) else '-'
        qty_val = float(row['QTY']) if pd.notna(row['QTY']) else 1.0
        # If there's multiple SKUs comma separated, we just put them as one detail row for now to retain information,
        # or we could split. Given varchar(100), let's split.
        skus = [s.strip() for s in sku_str.split(',')] if ',' in sku_str else [sku_str]
        
        # Distribute qty and price
        base_qty = int(qty_val / len(skus)) if len(skus) > 0 and qty_val >= len(skus) else 1
        base_price = nominal_total / len(skus) if len(skus) > 0 else nominal_total
        
        for s in skus:
            sql_detail = """
            INSERT INTO tblcs_complain_detail (
                no_resi, sku, qty, price
            ) VALUES (%s, %s, %s, %s)
            """
            cursor.execute(sql_detail, (no_resi, s[:100], base_qty, base_price))
            
        inserted += 1

conn.commit()
conn.close()

print(f"Migration completed! Inserted {inserted} rows. Skipped {skipped} rows (already exist).")
