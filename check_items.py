"""Check what items are actually in the database"""
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
        cur.execute("""
            SELECT i.*, c.category_name, o.office_name 
            FROM items i 
            LEFT JOIN categories c ON i.category_id = c.id 
            LEFT JOIN offices o ON i.office_id = o.id 
            ORDER BY i.id ASC
            LIMIT 10
        """)
        items = cur.fetchall()
        
        print("First 10 items in database:")
        print("=" * 120)
        print(f"{'ID':<5} {'Code':<15} {'Name':<30} {'Category':<20} {'Qty':<6} {'Unit':<10} {'Office':<20}")
        print("=" * 120)
        
        for item in items:
            print(f"{item['id']:<5} {item['item_code']:<15} {item['item_name']:<30} "
                  f"{(item['category_name'] or 'N/A'):<20} {item['quantity_on_hand']:<6} "
                  f"{item['unit']:<10} {(item['office_name'] or 'N/A'):<20}")
        
        # Count total items
        cur.execute("SELECT COUNT(*) as total FROM items")
        total = cur.fetchone()['total']
        print("=" * 120)
        print(f"Total items in database: {total}")
    
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
    import traceback
    traceback.print_exc()
