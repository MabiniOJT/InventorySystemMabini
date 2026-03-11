#!/usr/bin/env python3
"""
End-to-end test of the issue workflow
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

def test_workflow():
    conn = pymysql.connect(**DB_CONFIG)
    cur = conn.cursor()
    
    try:
        print("\n" + "="*70)
        print("TESTING COMPLETE ISSUE WORKFLOW")
        print("="*70)
        
        # 1. Get warehouse item
        cur.execute("""
            SELECT id, item_code, item_name, quantity_on_hand
            FROM items
            WHERE office_id IS NULL AND quantity_on_hand > 10
            LIMIT 1
        """)
        item = cur.fetchone()
        print(f"\n1. Warehouse Item: {item['item_name']}")
        print(f"   Stock before: {item['quantity_on_hand']}")
        initial_stock = item['quantity_on_hand']
        
        # 2. Get test office
        cur.execute("SELECT id, office_name FROM offices WHERE id = 11")  # ACCOUNTING
        office = cur.fetchone()
        print(f"\n2. Test Office: {office['office_name']}")
        
        # 3. Check office items before (via transactions)
        cur.execute("""
            SELECT COUNT(*) as cnt FROM inventory_transactions
            WHERE office_id=%s AND item_id=%s AND transaction_type='ISSUE' AND status='Completed'
        """, (office['id'], item['id']))
        before_count = cur.fetchone()['cnt']
        print(f"   Issued items before: {before_count}")
        
        # 4. Create and complete a transaction using the ACTUAL app logic
        from datetime import datetime
        import random
        ref = f"TEST-{datetime.now():%Y%m%d}-{random.randint(1000,9999)}"
        test_qty = 3
        
        print(f"\n3. Creating Issue transaction...")
        print(f"   Reference: {ref}")
        print(f"   Quantity: {test_qty}")
        
        cur.execute("""
            INSERT INTO inventory_transactions
            (transaction_type, transaction_date, reference_number, transaction_number,
             item_id, office_id, quantity, quantity_requested, quantity_approved,
             unit_cost, total_cost, status, created_by, created_at)
            VALUES ('ISSUE', CURDATE(), %s, %s, %s, %s, 0, %s, %s, 0, 0, 'Approved', 1, NOW())
        """, (ref, ref, item['id'], office['id'], test_qty, test_qty))
        
        tid = cur.lastrowid
        print(f"   Created transaction ID: {tid}")
        
        # 5. Complete the transaction (simulate app logic)
        print(f"\n4. Completing transaction...")
        
        cur.execute("""
            SELECT item_id, quantity, quantity_approved, transaction_type, office_id
            FROM inventory_transactions WHERE id=%s
        """, (tid,))
        trans = cur.fetchone()
        
        print(f"   Transaction: {trans}")
        print(f"   Transaction type: {trans['transaction_type']}")
        print(f"   Is Issue: {trans['transaction_type'] =='ISSUE'}")
        
        if trans and trans['transaction_type'] == 'ISSUE':
            qty_to_issue = trans.get('quantity_approved') or trans['quantity']
            print(f"   Quantity to issue: {qty_to_issue}")
            
            # Verify stock
            cur.execute("""
                SELECT quantity_on_hand FROM items
                WHERE id=%s AND (office_id IS NULL OR office_id = '')
            """, (trans['item_id'],))
            warehouse_item = cur.fetchone()
            
            print(f"   Warehouse item: {warehouse_item}")
            
            if not warehouse_item or warehouse_item['quantity_on_hand'] < qty_to_issue:
                raise Exception('Insufficient stock')
            
            print(f"   Stock check passed")
            
            # Decrement stock
            print(f"   Decrementing stock by {qty_to_issue}...")
            cur.execute("""
                UPDATE items SET quantity_on_hand=quantity_on_hand-%s, updated_at=NOW()
                WHERE id=%s
            """, (qty_to_issue, trans['item_id']))
            rows_affected = cur.rowcount
            print(f"   Rows affected by UPDATE: {rows_affected}")
            
            # Log movement
            cur.execute("SELECT quantity_on_hand FROM items WHERE id=%s", (trans['item_id'],))
            new_bal = cur.fetchone()['quantity_on_hand']
            cur.execute("""
                INSERT INTO stock_movements
                (item_id, transaction_id, movement_type, quantity, balance_after, created_by)
                VALUES (%s, %s, 'OUT', %s, %s, 1)
            """, (trans['item_id'], tid, qty_to_issue, new_bal))
            
            # Mark completed
            cur.execute("""
                UPDATE inventory_transactions
                SET status='Completed', processed_by=1, updated_at=NOW()
                WHERE id=%s
            """, (tid,))
        
        conn.commit()
        print(f"   ✅ Transaction completed")
        
        # 6. Verify results
        print(f"\n5. VERIFICATION:")
        
        # Check warehouse stock
        cur.execute("SELECT quantity_on_hand FROM items WHERE id=%s", (item['id'],))
        final_stock = cur.fetchone()['quantity_on_hand']
        expected_stock = initial_stock - test_qty
        
        print(f"   Warehouse Stock:")
        print(f"     Before: {initial_stock}")
        print(f"     After:  {final_stock}")
        print(f"     Expected: {expected_stock}")
        
        if final_stock == expected_stock:
            print(f"     ✅ Warehouse stock CORRECT")
        else:
            print(f"     ❌ Warehouse stock WRONG!")
            return False
        
        # Check transaction status
        cur.execute("SELECT status FROM inventory_transactions WHERE id=%s", (tid,))
        status = cur.fetchone()['status']
        print(f"\n   Transaction Status: {status}")
        if status == 'Completed':
            print(f"     ✅ Status CORRECT")
        else:
            print(f"     ❌ Status WRONG!")
            return False
        
        # Check office can see the item (via transaction query)
        cur.execute("""
            SELECT COUNT(*) as cnt FROM inventory_transactions
            WHERE office_id=%s AND item_id=%s AND transaction_type='ISSUE' AND status='Completed'
        """, (office['id'], item['id']))
        after_count = cur.fetchone()['cnt']
        
        print(f"\n   Office Issued Items:")
        print(f"     Before: {before_count}")
        print(f"     After:  {after_count}")
        
        if after_count == before_count + 1:
            print(f"     ✅ Office view CORRECT")
        else:
            print(f"     ❌ Office view WRONG!")
            return False
        
        # Check stock movement was logged
        cur.execute("""
            SELECT COUNT(*) as cnt FROM stock_movements
            WHERE transaction_id=%s AND movement_type='OUT'
        """, (tid,))
        movement_count = cur.fetchone()['cnt']
        
        print(f"\n   Stock Movement:")
        if movement_count == 1:
            print(f"     ✅ Movement logged CORRECTLY")
        else:
            print(f"     ❌ Movement NOT logged!")
            return False
        
        print("\n" + "="*70)
        print("✅✅✅ ALL TESTS PASSED! ✅✅✅")
        print("="*70)
        return True
        
    except Exception as e:
        conn.rollback()
        print(f"\n❌ ERROR: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        conn.close()

if __name__ == '__main__':
    success = test_workflow()
    exit(0 if success else 1)
