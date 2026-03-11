#!/usr/bin/env python3
"""
Check issue transactions to understand the problem
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

print("=" * 70)
print("RECENT ISSUE TRANSACTIONS")
print("=" * 70)
cur.execute("""
    SELECT it.id, it.reference_number, it.office_id, o.office_name,
           it.item_id, i.item_name, it.quantity, it.quantity_requested, 
           it.quantity_approved, it.status, i.quantity_on_hand
    FROM inventory_transactions it
    LEFT JOIN offices o ON it.office_id = o.id
    LEFT JOIN items i ON it.item_id = i.id
    WHERE it.transaction_type = 'Issue'
    ORDER BY it.id DESC LIMIT 5
""")
rows = cur.fetchall()
for r in rows:
    print(f"\nID: {r['id']} | Ref: {r['reference_number']}")
    print(f"Office: {r['office_name']} (ID: {r['office_id']})")
    print(f"Item: {r['item_name']} (ID: {r['item_id']})")
    print(f"Qty Requested: {r['quantity_requested']} | Approved: {r['quantity_approved']} | Issued: {r['quantity']}")
    print(f"Status: {r['status']} | Current Stock: {r['quantity_on_hand']}")

print("\n" + "=" * 70)
print("ITEMS ASSIGNED TO OFFICES (via office_id)")
print("=" * 70)
cur.execute("""
    SELECT i.id, i.item_code, i.item_name, i.office_id, o.office_name, 
           i.quantity_on_hand
    FROM items i
    LEFT JOIN offices o ON i.office_id = o.id
    WHERE i.office_id IS NOT NULL
    ORDER BY i.office_id, i.item_code
    LIMIT 10
""")
rows = cur.fetchall()
for r in rows:
    print(f"Item: {r['item_code']} - {r['item_name']}")
    print(f"  Office: {r['office_name']} (ID: {r['office_id']}) | Stock: {r['quantity_on_hand']}")

conn.close()
