"""Verify database connection and table structure"""
import pymysql
import os
from dotenv import load_dotenv

load_dotenv()

DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '')
DB_NAME = os.getenv('DB_NAME', 'mabini_inventory')

print("=" * 60)
print("Database Connection Info:")
print(f"  Host: {DB_HOST}")
print(f"  User: {DB_USER}")
print(f"  Database: {DB_NAME}")
print("=" * 60)

try:
    conn = pymysql.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME,
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor
    )
    
    print("\n✅ Connected successfully!")
    
    with conn.cursor() as cur:
        # Check items table structure
        cur.execute("DESCRIBE items")
        columns = cur.fetchall()
        
        print("\nItems table columns:")
        print("-" * 60)
        has_office_id = False
        for col in columns:
            print(f"  {col['Field']:20} {col['Type']:25}")
            if col['Field'] == 'office_id':
                has_office_id = True
        
        print("-" * 60)
        if has_office_id:
            print("✅ office_id column EXISTS")
        else:
            print("❌ office_id column MISSING!")
        
        # Check item count
        cur.execute("SELECT COUNT(*) as total FROM items")
        total = cur.fetchone()['total']
        print(f"\nTotal items in database: {total}")
        
        # Sample query that Flask app uses
        print("\nTesting Flask app query...")
        try:
            cur.execute("""
                SELECT i.*, c.category_name, o.office_name FROM items i 
                LEFT JOIN categories c ON i.category_id=c.id 
                LEFT JOIN offices o ON i.office_id=o.id 
                ORDER BY i.item_code ASC
                LIMIT 1
            """)
            result = cur.fetchone()
            if result:
                print(f"✅ Query successful! Sample item: {result['item_code']} - {result['item_name']}")
        except Exception as e:
            print(f"❌ Query failed: {e}")
    
    conn.close()
    
except Exception as e:
    print(f"\n❌ Error: {e}")
