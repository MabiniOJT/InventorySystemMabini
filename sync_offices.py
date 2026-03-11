"""Replace database offices with the correct Mabini offices"""
import pymysql
import os
from dotenv import load_dotenv

load_dotenv()

DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '')
DB_NAME = os.getenv('DB_NAME', 'mabini_inventory')

# Correct offices from offices.php
mabini_offices = [
    'M.O', 'V.M.O', 'HRMO', 'MPDC', 'LCR', 'MBO', 'ACCOUNTING', 'MTO',
    'ASSESSOR', 'LIBRARY', 'RHU', 'MSWD', 'AGRI', 'ENGINEERING', 'MARKET',
    'MDR', 'R.S.I', 'DENTAL', 'M.I', 'NUTRITION', 'MOTORPOOL', 'DILG',
    'OSCA', 'BAWASA', 'BPLO', 'MIDWIFE', 'LEGAL OFFICE', 'GSO'
]

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
        print("=" * 70)
        print("  REPLACING OFFICES WITH MABINI MUNICIPALITY OFFICES")
        print("=" * 70)
        
        # Delete existing offices
        cur.execute("DELETE FROM offices")
        print(f"\n✅ Cleared existing offices")
        
        # Insert Mabini offices
        print(f"\n📝 Adding {len(mabini_offices)} Mabini offices...")
        
        for i, office_name in enumerate(mabini_offices, 1):
            office_code = f"OFF-{i:03d}"
            cur.execute("""
                INSERT INTO offices (office_code, office_name, status)
                VALUES (%s, %s, 'Active')
            """, (office_code, office_name))
            print(f"  {i:2}. {office_code} - {office_name}")
        
        conn.commit()
        
        print("\n" + "=" * 70)
        print("✅ Successfully added all 28 Mabini offices!")
        print("=" * 70)
        
        # Verify
        cur.execute("SELECT COUNT(*) as count FROM offices WHERE status='Active'")
        count = cur.fetchone()['count']
        print(f"\nTotal active offices in database: {count}")
    
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
    import traceback
    traceback.print_exc()
