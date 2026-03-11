"""Reset admin password to 'password'"""
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
    # Generate new password hash using Python's bcrypt
    new_password = 'password'
    hashed = bcrypt.hashpw(new_password.encode(), bcrypt.gensalt())
    hashed_str = hashed.decode('utf-8')
    
    print(f"Generating new password hash...")
    print(f"Password: {new_password}")
    print(f"New hash: {hashed_str}")
    print()
    
    # Connect to database
    conn = pymysql.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME,
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor
    )
    
    with conn.cursor() as cur:
        # Update admin password
        cur.execute(
            "UPDATE users SET password = %s WHERE email = 'admin@mabini.com'",
            (hashed_str,)
        )
        conn.commit()
        
        print("✅ Admin password has been reset successfully!")
        print()
        print("📝 Login Credentials:")
        print("   Email: admin@mabini.com")
        print("   Password: password")
        print()
        print("⚠️  Please change this password after logging in!")
        
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
