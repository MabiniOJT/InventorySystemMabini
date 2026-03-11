"""Verify Engineering Office items"""
import pymysql
import os
from dotenv import load_dotenv

load_dotenv()

DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '')
DB_NAME = os.getenv('DB_NAME', 'mabini_inventory')

try:
    conn = pymysql.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME,
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor
    )
    
    with conn.cursor() as cur:
        # Get Engineering office ID
        cur.execute("SELECT id, office_name FROM offices WHERE office_name = 'ENGINEERING'")
        engineering = cur.fetchone()
        
        if not engineering:
            print("❌ Engineering office not found!")
            conn.close()
            exit(1)
        
        eng_id = engineering['id']
        
        # Get all items assigned to Engineering office
        cur.execute("""
            SELECT i.item_code, i.item_name, c.category_name, i.unit, 
                   i.quantity_on_hand, i.unit_cost, i.date_acquired
            FROM items i
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE i.office_id = %s
            ORDER BY i.item_code
        """, (eng_id,))
        items = cur.fetchall()
        
        print("=" * 100)
        print(f"ITEMS ASSIGNED TO: {engineering['office_name']} OFFICE (ID: {eng_id})")
        print("=" * 100)
        print(f"{'Item Code':<15} {'Item Name':<35} {'Category':<20} {'Unit':<8} {'Qty':<6} {'Cost':<10}")
        print("-" * 100)
        
        total_value = 0
        for item in items:
            qty = item['quantity_on_hand'] or 0
            cost = float(item['unit_cost'] or 0)
            total_cost = qty * cost
            total_value += total_cost
            
            print(f"{item['item_code']:<15} {item['item_name'][:34]:<35} "
                  f"{(item['category_name'] or 'N/A')[:19]:<20} "
                  f"{item['unit']:<8} {qty:<6} ₱{cost:>8.2f}")
        
        print("-" * 100)
        print(f"Total Items: {len(items)}")
        print(f"Total Value: ₱{total_value:,.2f}")
        print("=" * 100)
    
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
    import traceback
    traceback.print_exc()
