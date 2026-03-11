#!/usr/bin/env python3
"""
Fix stock_movements table to match schema.sql
"""
import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'mabini_inventory',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

conn = pymysql.connect(**DB_CONFIG)
cur = conn.cursor()

try:
    print("Dropping old stock_movements table...")
    cur.execute("DROP TABLE IF EXISTS stock_movements")
    
    print("Creating new stock_movements table with correct schema...")
    cur.execute("""
        CREATE TABLE stock_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            transaction_id INT,
            movement_type ENUM('IN', 'OUT', 'ADJUST') NOT NULL,
            quantity INT NOT NULL,
            balance_after INT NOT NULL,
            reference VARCHAR(100),
            remarks TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (transaction_id) REFERENCES inventory_transactions(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_item (item_id),
            INDEX idx_movement_type (movement_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB
    """)
    
    conn.commit()
    print("✅ stock_movements table recreated successfully!")
    
    # Verify
    cur.execute("DESCRIBE stock_movements")
    columns = cur.fetchall()
    print("\nVerifying columns:")
    for col in columns:
        print(f"  ✓ {col['Field']} ({col['Type']})")
        
except Exception as e:
    conn.rollback()
    print(f"❌ Error: {e}")
    import traceback
    traceback.print_exc()
finally:
    conn.close()
