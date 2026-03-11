"""Test password verification"""
import bcrypt
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
        cur.execute("SELECT password FROM users WHERE email='admin@mabini.com'")
        result = cur.fetchone()
        
        if result:
            stored_hash = result['password']
            test_password = 'password'
            
            print(f"Stored hash: {stored_hash}")
            print(f"Test password: {test_password}")
            print()
            
            # PHP bcrypt uses $2y$, Python uses $2b$
            # Need to convert $2y$ to $2a$ or $2b$ for Python bcrypt
            if stored_hash.startswith('$2y$'):
                # Replace $2y$ with $2a$ (compatible with both PHP and Python)
                python_hash = '$2a$' + stored_hash[4:]
                print(f"Converted hash: {python_hash}")
                
                try:
                    if bcrypt.checkpw(test_password.encode(), python_hash.encode()):
                        print("\n✅ Password verification SUCCESSFUL with conversion!")
                        print("\nThe issue: PHP bcrypt ($2y$) vs Python bcrypt ($2a$/$2b$)")
                        print("Solution: Convert $2y$ to $2a$ before verification")
                    else:
                        print("\n❌ Password verification FAILED even with conversion")
                except Exception as e:
                    print(f"\n❌ Error during verification: {e}")
            
            # Also test without conversion
            try:
                if bcrypt.checkpw(test_password.encode(), stored_hash.encode()):
                    print("\n✅ Password verification works without conversion!")
                else:
                    print("\n❌ Password verification FAILED without conversion")
            except Exception as e:
                print(f"\n❌ Error without conversion: {e}")
                
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
