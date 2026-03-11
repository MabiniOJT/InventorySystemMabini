"""Check offices in database vs offices.php predefined list"""
import pymysql
import os
from dotenv import load_dotenv

load_dotenv()

DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '')
DB_NAME = os.getenv('DB_NAME', 'mabini_inventory')

# Offices from offices.php
php_offices = [
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
        # Get all offices from database
        cur.execute("SELECT id, office_code, office_name, status FROM offices ORDER BY id")
        db_offices = cur.fetchall()
        
        print("=" * 80)
        print("OFFICES COMPARISON")
        print("=" * 80)
        
        print(f"\nOffices in PHP file (offices.php): {len(php_offices)}")
        print(f"Offices in Database: {len(db_offices)}")
        
        print("\n" + "=" * 80)
        print("Database Offices:")
        print("=" * 80)
        print(f"{'ID':<5} {'Code':<15} {'Office Name':<35} {'Status':<10}")
        print("-" * 80)
        
        db_office_names = []
        for office in db_offices:
            print(f"{office['id']:<5} {office['office_code']:<15} {office['office_name']:<35} {office['status']:<10}")
            db_office_names.append(office['office_name'].upper())
        
        print("\n" + "=" * 80)
        print("PHP Offices List (from offices.php):")
        print("=" * 80)
        for i, office in enumerate(php_offices, 1):
            in_db = "✅" if office.upper() in db_office_names else "❌"
            print(f"{i:2}. {office:<30} {in_db}")
        
        # Check for missing offices
        print("\n" + "=" * 80)
        print("Analysis:")
        print("=" * 80)
        
        missing_in_db = []
        for office in php_offices:
            if office.upper() not in db_office_names:
                missing_in_db.append(office)
        
        extra_in_db = []
        php_upper = [o.upper() for o in php_offices]
        for office in db_offices:
            if office['office_name'].upper() not in php_upper:
                extra_in_db.append(office['office_name'])
        
        if missing_in_db:
            print(f"\n⚠️  Missing in Database ({len(missing_in_db)}):")
            for office in missing_in_db:
                print(f"  - {office}")
        else:
            print("\n✅ All PHP offices are in the database")
        
        if extra_in_db:
            print(f"\n⚠️  Extra in Database (not in PHP list) ({len(extra_in_db)}):")
            for office in extra_in_db:
                print(f"  - {office}")
        else:
            print("\n✅ No extra offices in database")
    
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
    import traceback
    traceback.print_exc()
