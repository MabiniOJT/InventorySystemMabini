#!/usr/bin/env python3
"""
Verify the office items view is working correctly
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

print("\n" + "="*70)
print("VERIFYING OFFICE ITEMS VIEW (Transaction-based)")
print("="*70)

# Get the ACCOUNTING office (ID 11) - has completed transaction ID 3
office_id = 11

print(f"\nOffice: ACCOUNTING (ID: {office_id})")

# Query using the NEW logic (from office_items function)
cur.execute("""
    SELECT i.item_code, i.item_name, c.category_name, o.office_name,
           i.unit, 
           COALESCE(it.quantity_approved, it.quantity) as quantity_issued,
           i.unit_cost, 
           it.updated_at as date_issued,
           it.reference_number,
           it.id as transaction_id
    FROM inventory_transactions it
    INNER JOIN items i ON it.item_id = i.id
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN offices o ON it.office_id = o.id
    WHERE it.office_id = %s 
      AND it.transaction_type = 'ISSUE' 
      AND it.status = 'Completed'
    ORDER BY it.updated_at DESC, i.item_code
""", (office_id,))

items = cur.fetchall()

if items:
    print(f"\n✅ Found {len(items)} completed issue(s) for this office:\n")
    for idx, item in enumerate(items, 1):
        print(f"{idx}. {item['item_code']} - {item['item_name']}")
        print(f"   Quantity: {item['quantity_issued']} {item['unit']}")
        print(f"   Category: {item['category_name']}")
        print(f"   Reference: {item['reference_number']}")
        print(f"   Transaction ID: {item['transaction_id']}")
        print(f"   Date: {item['date_issued']}")
        print()
else:
    print("\n❌ No items found for this office!")
    print("   This means no completed ISSUE transactions exist.")

# Also check overall transaction status
print("\n" + "="*70)
print("ALL COMPLETED ISSUE TRANSACTIONS")
print("="*70)
cur.execute("""
    SELECT it.id, it.reference_number, o.office_name, i.item_name,
           COALESCE(it.quantity_approved, it.quantity) as qty, it.updated_at
    FROM inventory_transactions it
    INNER JOIN offices o ON it.office_id = o.id
    INNER JOIN items i ON it.item_id = i.id
    WHERE it.transaction_type = 'ISSUE' AND it.status = 'Completed'
    ORDER BY it.id DESC
""")
all_completed = cur.fetchall()

for trans in all_completed:
    print(f"[{trans['id']}] {trans['reference_number']} → {trans['office_name']}")
    print(f"    {trans['item_name']} (Qty: {trans['qty']})")
    print(f"    Date: {trans['updated_at']}")
    print()

conn.close()
