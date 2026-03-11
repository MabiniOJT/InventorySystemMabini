#!/usr/bin/env python3
import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'mabini_inventory',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

conn = pymysql.connect(**DB_CONFIG)
cur = conn.cursor()

cur.execute("DESCRIBE stock_movements")
columns = cur.fetchall()

print("stock_movements table columns:")
for col in columns:
    print(f"  {col['Field']} ({col['Type']}) - Null:{col['Null']} - Key:{col['Key']} - Default:{col['Default']}")

cur.execute("SELECT * FROM stock_movements LIMIT 1")
sample = cur.fetchone()
if sample:
    print("\nSample row:")
    for k, v in sample.items():
        print(f"  {k}: {v}")
else:
    print("\nNo rows in table")

conn.close()
