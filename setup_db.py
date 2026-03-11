"""
Database Setup Script for Mabini Inventory System
Reads and executes the schema.sql file to create the database and tables
"""
import pymysql
import os
from dotenv import load_dotenv

load_dotenv()

# Database config
DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '')
DB_NAME = os.getenv('DB_NAME', 'mabini_inventory')

def setup_database():
    """Create database and tables from schema.sql"""
    try:
        # Connect to MySQL without specifying database
        print(f"Connecting to MySQL server at {DB_HOST}...")
        conn = pymysql.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASS,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        
        # Read schema file
        schema_file = os.path.join(os.path.dirname(__file__), 'database', 'schema.sql')
        print(f"Reading schema from: {schema_file}")
        
        with open(schema_file, 'r', encoding='utf-8') as f:
            sql_content = f.read()
        
        # Split into individual statements
        statements = [s.strip() for s in sql_content.split(';') if s.strip() 
                     and not s.strip().startswith('--')]
        
        with conn.cursor() as cursor:
            executed = 0
            for statement in statements:
                if statement and not statement.startswith('/*'):
                    try:
                        cursor.execute(statement)
                        executed += 1
                    except Exception as e:
                        # Some statements might fail if already exist, that's ok
                        if 'already exists' not in str(e).lower():
                            print(f"Warning: {e}")
            
            conn.commit()
        
        print(f"\n✅ Database setup completed successfully!")
        print(f"   Executed {executed} SQL statements")
        print(f"\n📝 Default Login Credentials:")
        print(f"   Email: admin@mabini.com")
        print(f"   Password: password")
        print(f"\n⚠️  Please change the default password after first login!")
        
        conn.close()
        return True
        
    except FileNotFoundError:
        print("❌ Error: database/schema.sql file not found!")
        return False
    except pymysql.err.OperationalError as e:
        print(f"❌ Database connection error: {e}")
        print(f"\nPlease make sure:")
        print(f"  - MySQL server is running")
        print(f"  - Database credentials are correct:")
        print(f"    Host: {DB_HOST}")
        print(f"    User: {DB_USER}")
        return False
    except Exception as e:
        print(f"❌ Setup error: {e}")
        return False

if __name__ == '__main__':
    print("=" * 60)
    print("  Mabini Inventory System - Database Setup")
    print("=" * 60)
    print()
    setup_database()
