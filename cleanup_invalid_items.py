"""Delete items with invalid item codes (cleanup junk data)"""
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
        # Find items with invalid codes (not starting with ITEM- or other standard prefix)
        cur.execute("""
            SELECT id, item_code, item_name 
            FROM items 
            WHERE item_code NOT LIKE 'ITEM-%'
            AND item_code NOT LIKE '%-% %'
            AND LENGTH(item_code) < 7
        """)
        invalid_items = cur.fetchall()
        
        print(f"Found {len(invalid_items)} items with invalid item codes:")
        print("=" * 80)
        
        if len(invalid_items) > 0:
            print("\nItems to be deleted:")
            for item in invalid_items[:20]:  # Show first 20
                print(f"  ID {item['id']:<5} - Code: '{item['item_code']:<10}' - Name: {item['item_name']}")
            
            if len(invalid_items) > 20:
                print(f"  ... and {len(invalid_items) - 20} more")
            
            response = input(f"\nDelete these {len(invalid_items)} items? (yes/no): ")
            
            if response.lower() in ['yes', 'y']:
                # Delete the items
                cur.execute("""
                    DELETE FROM items 
                    WHERE item_code NOT LIKE 'ITEM-%'
                    AND item_code NOT LIKE '%-% %'
                    AND LENGTH(item_code) < 7
                """)
                conn.commit()
                
                print(f"\n✅ Deleted {cur.rowcount} invalid items")
                
                # Show remaining count
                cur.execute("SELECT COUNT(*) as total FROM items")
                total = cur.fetchone()['total']
                print(f"   Remaining items in database: {total}")
                
                # Show new first 10 items
                cur.execute("""
                    SELECT item_code, item_name 
                    FROM items 
                    ORDER BY item_code ASC 
                    LIMIT 10
                """)
                print("\n✅ New first 10 items:")
                for item in cur.fetchall():
                    print(f"  - {item['item_code']}: {item['item_name']}")
            else:
                print("\n❌ Deletion cancelled")
        else:
            print("✅ No invalid items found!")
    
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
    import traceback
    traceback.print_exc()
