#!/usr/bin/env python3
"""
Check the office_id of specific item
"""
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

print("Checking Item ID 1 (Alcohol):")
cur.execute("SELECT id, item_code, item_name, office_id, quantity_on_hand FROM items WHERE id = 1")
item = cur.fetchone()
if item:
    print(f"  ID: {item['id']}")
    print(f"  Code: {item['item_code']}")
    print(f"  Name: {item['item_name']}")
    print(f"  Office ID: {item['office_id']} (NULL means GSO warehouse)")
    print(f"  Stock: {item['quantity_on_hand']}")

print("\n\nAll items in GSO warehouse (office_id IS NULL):")
cur.execute("SELECT id, item_code, item_name, quantity_on_hand FROM items WHERE office_id IS NULL LIMIT 20")
rows = cur.fetchall()
for r in rows:
    print(f"  {r['item_code']} - {r['item_name']} (Stock: {r['quantity_on_hand']})")

conn.close()
