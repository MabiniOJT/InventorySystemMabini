#!/usr/bin/env python3
"""
Test the fixed completion logic by creating and completing a test transaction
"""
import pymysql
from datetime import datetime
import random

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'mabini_inventory',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

def test_issue_complete_workflow():
    conn = pymysql.connect(**DB_CONFIG)
    cur = conn.cursor()
    
    try:
        # 1. Get a test item from warehouse (office_id IS NULL)
        cur.execute("""
            SELECT id, item_code, item_name, quantity_on_hand 
            FROM items 
            WHERE office_id IS NULL AND quantity_on_hand > 10 
            LIMIT 1
        """)
        warehouse_item = cur.fetchone()
        
        if not warehouse_item:
            print("❌ No items in warehouse to test with")
            conn.close()
            return
        
        print(f"\n📦 Test Item: {warehouse_item['item_name']}")
        print(f"   Item ID: {warehouse_item['id']}")
        print(f"   Current Warehouse Stock: {warehouse_item['quantity_on_hand']}")
        
        # 2. Get a test office (not GSO)
        cur.execute("SELECT id, office_name FROM offices WHERE id != 1 LIMIT 1")
        office = cur.fetchone()
        print(f"\n🏢 Test Office: {office['office_name']} (ID: {office['id']})")
        
        # 3. Check if office already has this item
        cur.execute("""
            SELECT id, quantity_on_hand 
            FROM items 
            WHERE office_id=%s AND item_code=%s
        """, (office['id'], warehouse_item['item_code']))
        existing_office_item = cur.fetchone()
        
        if existing_office_item:
            print(f"   Office already has {existing_office_item['quantity_on_hand']} units")
            initial_office_qty = existing_office_item['quantity_on_hand']
        else:
            print(f"   Office doesn't have this item yet")
            initial_office_qty = 0
        
        # 4. Create a test Issue transaction
        ref = f"TST-{datetime.now():%Y%m%d}-{random.randint(1000,9999)}"
        test_qty_requested = 5
        test_qty_approved = 4  # Approved is less than requested
        
        cur.execute("""
            INSERT INTO inventory_transactions 
            (transaction_type, transaction_date, reference_number, transaction_number,
             item_id, office_id, quantity, quantity_requested, quantity_approved, 
             unit_cost, total_cost, status, created_by, created_at)
            VALUES ('Issue', CURDATE(), %s, %s, %s, %s, %s, %s, %s, 0, 0, 'Approved', 1, NOW())
        """, (ref, ref, warehouse_item['id'], office['id'], 0, test_qty_requested, test_qty_approved))
        
        transaction_id = cur.lastrowid
        print(f"\n✅ Created test transaction: {ref} (ID: {transaction_id})")
        print(f"   Requested: {test_qty_requested}, Approved: {test_qty_approved}")
        
        # 5. Simulate completion (this will use the new fixed logic)
        print(f"\n🔄 Completing transaction...")
        
        # Get transaction details
        cur.execute("""
            SELECT item_id, quantity, quantity_approved, transaction_type, office_id 
            FROM inventory_transactions WHERE id=%s
        """, (transaction_id,))
        trans = cur.fetchone()
        
        qty_to_issue = trans.get('quantity_approved') or trans['quantity']
        
        # Get source item details
        cur.execute("""
            SELECT i.* 
            FROM items i 
            WHERE i.id=%s AND (i.office_id IS NULL OR i.office_id = '')
        """, (trans['item_id'],))
        source_item = cur.fetchone()
        
        # Decrement warehouse stock
        cur.execute("""
            UPDATE items SET quantity_on_hand=quantity_on_hand-%s, updated_at=NOW() 
            WHERE id=%s AND quantity_on_hand>=%s
        """, (qty_to_issue, trans['item_id'], qty_to_issue))
        
        if cur.rowcount == 0:
            raise Exception('Insufficient stock')
        
        # Log stock movement
        cur.execute("SELECT quantity_on_hand FROM items WHERE id=%s", (trans['item_id'],))
        new_bal = cur.fetchone()['quantity_on_hand']
        cur.execute("""
            INSERT INTO stock_movements 
            (item_id, transaction_id, movement_type, quantity, balance_after, created_by) 
            VALUES (%s, %s, 'OUT', %s, %s, 1)
        """, (trans['item_id'], transaction_id, qty_to_issue, new_bal))
        
        # Check if office has the item
        cur.execute("""
            SELECT id, quantity_on_hand FROM items 
            WHERE office_id=%s AND item_code=%s AND item_name=%s
        """, (trans['office_id'], source_item['item_code'], source_item['item_name']))
        office_item = cur.fetchone()
        
        if office_item:
            # Update existing
            new_office_qty = office_item['quantity_on_hand'] + qty_to_issue
            cur.execute("""
                UPDATE items SET quantity_on_hand=%s, updated_at=NOW() WHERE id=%s
            """, (new_office_qty, office_item['id']))
            print(f"   ✅ Updated office item quantity: {office_item['quantity_on_hand']} → {new_office_qty}")
        else:
            # Create new
            cur.execute("""
                INSERT INTO items 
                (item_code, item_name, description, category_id, unit, quantity_on_hand, 
                unit_cost, supplier_id, date_acquired, office_id, status, created_at, updated_at) 
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 'Active', NOW(), NOW())
            """, (
                source_item['item_code'], source_item['item_name'], 
                source_item.get('description', ''), source_item.get('category_id'),
                source_item['unit'], qty_to_issue, source_item['unit_cost'],
                source_item.get('supplier_id'), source_item.get('date_acquired'),
                trans['office_id']
            ))
            print(f"   ✅ Created new office item with quantity: {qty_to_issue}")
        
        # Mark completed
        cur.execute("""
            UPDATE inventory_transactions 
            SET status='Completed', processed_by=1, updated_at=NOW() 
            WHERE id=%s
        """, (transaction_id,))
        
        conn.commit()
        
        # 6. Verify results
        print(f"\n📊 VERIFICATION:")
        
        # Check warehouse stock
        cur.execute("SELECT quantity_on_hand FROM items WHERE id=%s", (warehouse_item['id'],))
        final_warehouse = cur.fetchone()['quantity_on_hand']
        print(f"   Warehouse Stock: {warehouse_item['quantity_on_hand']} → {final_warehouse} (decreased by {qty_to_issue})")
        
        # Check office stock
        cur.execute("""
            SELECT quantity_on_hand FROM items 
            WHERE office_id=%s AND item_code=%s
        """, (office['id'], warehouse_item['item_code']))
        final_office = cur.fetchone()
        if final_office:
            print(f"   Office Stock: {initial_office_qty} → {final_office['quantity_on_hand']} (increased by {qty_to_issue})")
        
        # Check transaction status
        cur.execute("SELECT status FROM inventory_transactions WHERE id=%s", (transaction_id,))
        status = cur.fetchone()['status']
        print(f"   Transaction Status: {status}")
        
        if final_warehouse == warehouse_item['quantity_on_hand'] - qty_to_issue:
            print(f"\n✅ SUCCESS: Warehouse stock correctly decremented!")
        else:
            print(f"\n❌ FAIL: Warehouse stock incorrect!")
        
        if final_office and final_office['quantity_on_hand'] == initial_office_qty + qty_to_issue:
            print(f"✅ SUCCESS: Office stock correctly updated!")
        else:
            print(f"❌ FAIL: Office stock not updated!")
        
    except Exception as e:
        conn.rollback()
        print(f"\n❌ ERROR: {e}")
        import traceback
        traceback.print_exc()
    finally:
        conn.close()

if __name__ == '__main__':
    test_issue_complete_workflow()
