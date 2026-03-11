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

print("Items with office_id set:")
cur.execute("""
    SELECT id, item_code, item_name, office_id, quantity_on_hand
    FROM items
    WHERE office_id IS NOT NULL
    ORDER BY office_id, item_code
    LIMIT 20
""")
rows = cur.fetchall()
for r in rows:
    print(f"  [{r['office_id']:2d}] {r['item_code']} - {r['item_name']} (Qty: {r['quantity_on_hand']})")

print(f"\nTotal items with office_id: {len(rows)}")

print("\n\nItems WITHOUT office_id (warehouse):")
cur.execute("""
    SELECT id, item_code, item_name, quantity_on_hand    FROM items
    WHERE office_id IS NULL
    ORDER BY item_code
    LIMIT 10
""")
rows = cur.fetchall()
for r in rows:
    print(f"  {r['item_code']} - {r['item_name']} (Qty: {r['quantity_on_hand']})")

print("\n\nChecking for duplicate item_codes:")
cur.execute("""
    SELECT item_code, COUNT(*) as cnt
    FROM items
    GROUP BY item_code
    HAVING cnt > 1
""")
dupes = cur.fetchall()
if dupes:
    for d in dupes:
        print(f"  ⚠️  {d['item_code']} appears {d['cnt']} times")
else:
    print("  ✓ No duplicate item_codes found")

conn.close()
