"""Check if admin user exists in database"""
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
        cur.execute("SELECT id, username, email, role, status FROM users WHERE email='admin@mabini.com'")
        user = cur.fetchone()
        
        if user:
            print("✅ Admin user found:")
            print(f"   ID: {user['id']}")
            print(f"   Username: {user['username']}")
            print(f"   Email: {user['email']}")
            print(f"   Role: {user['role']}")
            print(f"   Status: {user['status']}")
        else:
            print("❌ Admin user NOT found!")
            print("\nLet me create the admin user...")
            
            # Password hash for 'password'
            password_hash = '$2y$10$CDAhZzWK.AChNWifT6GXmu763.y4ZgOELA49PGLETAmtOfwEcx6UW'
            
            cur.execute(
                "INSERT INTO users (username, password, full_name, email, role, status) "
                "VALUES (%s, %s, %s, %s, %s, %s)",
                ('admin', password_hash, 'System Administrator', 'admin@mabini.com', 'Admin', 'Active')
            )
            conn.commit()
            print("✅ Admin user created successfully!")
            
    conn.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
