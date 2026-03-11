"""Check the first few items in database by ID order"""
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
        # Get items ordered by item_code (as the Flask app does)
        cur.execute("""
            SELECT i.id, i.item_code, i.item_name, c.category_name 
            FROM items i 
            LEFT JOIN categories c ON i.category_id=c.id 
            ORDER BY i.item_code ASC
            LIMIT 20
        """)
        items = cur.fetchall()
        
        print("First 20 items (ordered by item_code as Flask app does):")
        print("=" * 80)
        print(f"{'ID':<6} {'Item Code':<15} {'Item Name':<40} {'Category':<15}")
        print("=" * 80)
        
        for item in items:
            print(f"{item['id']:<6} {item['item_code']:<15} {item['item_name']:<40} {(item['category_name'] or 'N/A'):<15}")
        
        print("=" * 80)
        
        # Check for duplicate item codes
        cur.execute("""
            SELECT item_code, COUNT(*) as count 
            FROM items 
            GROUP BY item_code 
            HAVING count > 1
        """)
        duplicates = cur.fetchall()
        
        if duplicates:
            print(f"\n⚠️  Found {len(duplicates)} duplicate item codes:")
            for dup in duplicates:
                print(f"  - '{dup['item_code']}' appears {dup['count']} times")
        else:
            print("\n✅ No duplicate item codes found")
        
        # Check items with invalid codes
        cur.execute("""
            SELECT COUNT(*) as count 
            FROM items 
            WHERE LENGTH(item_code) < 5
        """)
        invalid = cur.fetchone()['count']
        
        if invalid > 0:
            print(f"\n⚠️  Found {invalid} items with short/invalid item codes")
            cur.execute("""
                SELECT id, item_code, item_name 
                FROM items 
                WHERE LENGTH(item_code) < 5 
                LIMIT 10
            """)
            for item in cur.fetchall():
                print(f"  - ID {item['id']}: '{item['item_code']}' - {item['item_name']}")
    
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
