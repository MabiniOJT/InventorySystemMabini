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

print("Checking transaction_type values:")
cur.execute("SELECT DISTINCT transaction_type FROM inventory_transactions")
types = cur.fetchall()
for t in types:
    print(f"  - '{t['transaction_type']}'")

print("\nChecking inventory_transactions table definition:")
cur.execute("SHOW CREATE TABLE inventory_transactions")
result = cur.fetchone()
create_stmt = result['Create Table']
# Find the transaction_type line
for line in create_stmt.split('\n'):
    if 'transaction_type' in line.lower():
        print(f"  {line.strip()}")

conn.close()
