#!/usr/bin/env python3
"""
Create suppliers table if it doesn't exist
"""
import pymysql
import sys

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'mabini_inventory',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

def create_suppliers_table():
    try:
        conn = pymysql.connect(**DB_CONFIG)
        print("✓ Connected to database")
        
        with conn.cursor() as cursor:
            # Check if suppliers table exists
            cursor.execute("""
                SELECT COUNT(*) as count 
                FROM information_schema.tables 
                WHERE table_schema = 'mabini_inventory' 
                AND table_name = 'suppliers'
            """)
            result = cursor.fetchone()
            
            if result['count'] > 0:
                print("✓ Suppliers table already exists")
                return
            
            # Create suppliers table
            print("Creating suppliers table...")
            cursor.execute("""
                CREATE TABLE suppliers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    supplier_code VARCHAR(20) UNIQUE NOT NULL,
                    supplier_name VARCHAR(100) NOT NULL,
                    contact_person VARCHAR(100),
                    contact_number VARCHAR(20),
                    email VARCHAR(100),
                    address TEXT,
                    status ENUM('Active', 'Inactive') DEFAULT 'Active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_supplier_code (supplier_code),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB
            """)
            
            # Add some sample suppliers
            print("Adding sample suppliers...")
            sample_suppliers = [
                ('SUP-001', 'ABC Office Supplies', 'Juan Dela Cruz', '0912-345-6789', 'juan@abc.com', 'Manila'),
                ('SUP-002', 'XYZ Trading', 'Maria Santos', '0917-987-6543', 'maria@xyz.com', 'Quezon City'),
                ('SUP-003', 'Local Supplier Inc.', 'Pedro Reyes', '0905-111-2222', 'pedro@local.com', 'Batangas'),
            ]
            
            for supplier in sample_suppliers:
                cursor.execute("""
                    INSERT INTO suppliers 
                    (supplier_code, supplier_name, contact_person, contact_number, email, address)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """, supplier)
            
            conn.commit()
            print(f"✓ Created suppliers table with {len(sample_suppliers)} sample suppliers")
            
    except Exception as e:
        print(f"✗ Error: {e}")
        sys.exit(1)
    finally:
        if conn:
            conn.close()
            print("✓ Database connection closed")

if __name__ == '__main__':
    print("=" * 60)
    print("Creating Suppliers Table")
    print("=" * 60)
    create_suppliers_table()
    print("\n✓ Suppliers table setup complete!")
