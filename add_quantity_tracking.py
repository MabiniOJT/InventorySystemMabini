#!/usr/bin/env python3
"""
Add quantity_requested and quantity_approved columns to inventory_transactions table
This supports the GSO workflow: Request → Approval → Issuance
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

def add_quantity_columns():
    try:
        conn = pymysql.connect(**DB_CONFIG)
        print("✓ Connected to database")
        
        with conn.cursor() as cursor:
            # Check if columns already exist
            cursor.execute("""
                SELECT COUNT(*) as count 
                FROM information_schema.columns 
                WHERE table_schema = 'mabini_inventory' 
                AND table_name = 'inventory_transactions'
                AND column_name = 'quantity_requested'
            """)
            result = cursor.fetchone()
            
            if result['count'] > 0:
                print("✓ Quantity tracking columns already exist")
                return
            
            print("Adding quantity_requested column...")
            cursor.execute("""
                ALTER TABLE inventory_transactions
                ADD COLUMN quantity_requested INT DEFAULT NULL 
                COMMENT 'Original quantity requested by office'
                AFTER quantity
            """)
            
            print("Adding quantity_approved column...")
            cursor.execute("""
                ALTER TABLE inventory_transactions
                ADD COLUMN quantity_approved INT DEFAULT NULL 
                COMMENT 'Quantity approved by GSO supervisor'
                AFTER quantity_requested
            """)
            
            # Add created_by column if it doesn't exist (for tracking who created the request)
            cursor.execute("""
                SELECT COUNT(*) as count 
                FROM information_schema.columns 
                WHERE table_schema = 'mabini_inventory' 
                AND table_name = 'inventory_transactions'
                AND column_name = 'created_by'
            """)
            result = cursor.fetchone()
            
            if result['count'] == 0:
                print("Adding created_by column...")
                cursor.execute("""
                    ALTER TABLE inventory_transactions
                    ADD COLUMN created_by INT DEFAULT NULL 
                    COMMENT 'GSO staff who entered the request'
                    AFTER processed_by
                """)
                cursor.execute("""
                    ALTER TABLE inventory_transactions
                    ADD CONSTRAINT fk_created_by 
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                """)
            
            # Migrate existing data: copy quantity to quantity_requested for completed transactions
            print("Migrating existing transaction data...")
            cursor.execute("""
                UPDATE inventory_transactions 
                SET quantity_requested = quantity,
                    quantity_approved = quantity
                WHERE transaction_type = 'Issue' 
                AND status IN ('Approved', 'Completed')
            """)
            
            conn.commit()
            print("✓ Database schema updated successfully")
            print("\nNew workflow:")
            print("  1. GSO staff enters quantity_requested (from office request)")
            print("  2. Supervisor approves/modifies → quantity_approved")
            print("  3. Warehouse issues actual amount → quantity")
            
    except Exception as e:
        print(f"✗ Error: {e}")
        sys.exit(1)
    finally:
        if conn:
            conn.close()
            print("✓ Database connection closed")

if __name__ == '__main__':
    print("=" * 70)
    print("Adding Quantity Tracking Columns for GSO Workflow")
    print("=" * 70)
    add_quantity_columns()
    print("\n✓ Migration complete!")
