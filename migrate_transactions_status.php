<?php
/**
 * Database Migration Helper
 * Adds status field to inventory_transactions table if it doesn't exist
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Database Migration - Inventory Transactions</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".container{max-width:800px;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#22c55e;padding:15px;background:#f0fdf4;border-left:4px solid #22c55e;margin:10px 0;}";
echo ".error{color:#ef4444;padding:15px;background:#fef2f2;border-left:4px solid #ef4444;margin:10px 0;}";
echo ".info{color:#3b82f6;padding:15px;background:#eff6ff;border-left:4px solid #3b82f6;margin:10px 0;}";
echo "h1{color:#333;}p{color:#666;line-height:1.6;}</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔧 Database Migration - Inventory Transactions</h1>";
echo "<p>This script will add the <strong>status</strong> field to the inventory_transactions table if it doesn't exist.</p>";

try {
    // Check if status column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM inventory_transactions LIKE 'status'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='info'>ℹ️ The status column already exists in inventory_transactions table. No migration needed.</div>";
    } else {
        // Add the column
        $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN status ENUM('Pending', 'Approved', 'Completed', 'Cancelled') DEFAULT 'Pending' AFTER processed_by");
        echo "<div class='success'>✅ Successfully added status column to inventory_transactions table.</div>";
        
        // Add index
        $conn->exec("ALTER TABLE inventory_transactions ADD INDEX idx_status (status)");
        echo "<div class='success'>✅ Successfully added index for status.</div>";
        
        // Update existing records to 'Completed'
        $conn->exec("UPDATE inventory_transactions SET status = 'Completed' WHERE status IS NULL");
        echo "<div class='success'>✅ Updated existing records to 'Completed' status.</div>";
    }
    
    echo "<p style='margin-top:20px;'>Migration completed successfully!</p>";
    echo "<p><a href='item-master-list.php' style='color:#4CAF50;text-decoration:none;font-weight:bold;'>← Back to Item Master List</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Migration Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Please check your database connection and try again.</p>";
}

echo "</div></body></html>";
?>
