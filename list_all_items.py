"""Show all items to verify they're correct"""
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
        # Get all items
        cur.execute("""
            SELECT i.item_code, i.item_name, c.category_name, i.quantity_on_hand 
            FROM items i 
            LEFT JOIN categories c ON i.category_id = c.id 
            ORDER BY i.item_code ASC
        """)
        items = cur.fetchall()
        
        print(f"All {len(items)} items in database:")
        print("=" * 100)
        print(f"{'Item Code':<25} {'Item Name':<45} {'Category':<20} {'Qty':<6}")
        print("=" * 100)
        
        for item in items:
            print(f"{item['item_code']:<25} {item['item_name'][:44]:<45} "
                  f"{(item['category_name'] or 'Uncategorized')[:19]:<20} {item['quantity_on_hand']:<6}")
        
        print("=" * 100)
        print(f"Total: {len(items)} items")
        
        # Group by category
        cur.execute("""
            SELECT c.category_name, COUNT(*) as count 
            FROM items i 
            LEFT JOIN categories c ON i.category_id = c.id 
            GROUP BY c.category_name 
            ORDER BY count DESC
        """)
        categories = cur.fetchall()
        
        print("\nItems by category:")
        for cat in categories:
            cat_name = cat['category_name'] or 'Uncategorized'
            print(f"  {cat_name}: {cat['count']} items")
    
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
