<?php
/**
 * Comprehensive Database Migration
 * Updates database to latest schema version
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Database Migration - Complete Update</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".container{max-width:900px;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#22c55e;padding:10px 15px;background:#f0fdf4;border-left:4px solid #22c55e;margin:8px 0;border-radius:4px;}";
echo ".error{color:#ef4444;padding:10px 15px;background:#fef2f2;border-left:4px solid #ef4444;margin:8px 0;border-radius:4px;}";
echo ".info{color:#3b82f6;padding:10px 15px;background:#eff6ff;border-left:4px solid #3b82f6;margin:8px 0;border-radius:4px;}";
echo ".warning{color:#f59e0b;padding:10px 15px;background:#fffbeb;border-left:4px solid #f59e0b;margin:8px 0;border-radius:4px;}";
echo "h1{color:#333;margin-bottom:10px;}h2{color:#666;font-size:18px;margin-top:25px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;}";
echo "p{color:#666;line-height:1.6;}ul{margin:10px 0;}li{margin:5px 0;color:#666;}</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔧 Complete Database Migration</h1>";
echo "<p>This script will update your database to the latest schema version.</p>";

$migrationCount = 0;
$errors = [];

try {
    echo "<h2>1. Items Table - Expiration Date</h2>";
    
    // Check if expiration_date column exists in items table
    $stmt = $conn->query("SHOW COLUMNS FROM items LIKE 'expiration_date'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='info'>ℹ️ expiration_date column already exists in items table</div>";
    } else {
        try {
            $conn->exec("ALTER TABLE items ADD COLUMN expiration_date DATE AFTER location");
            echo "<div class='success'>✅ Added expiration_date column to items table</div>";
            $migrationCount++;
            
            $conn->exec("ALTER TABLE items ADD INDEX idx_expiration_date (expiration_date)");
            echo "<div class='success'>✅ Added index for expiration_date</div>";
        } catch (PDOException $e) {
            $errors[] = "Items.expiration_date: " . $e->getMessage();
            echo "<div class='error'>❌ Error adding expiration_date: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    echo "<h2>2. Inventory Transactions - Status Field</h2>";
    
    // Check if status column exists in inventory_transactions table
    $stmt = $conn->query("SHOW COLUMNS FROM inventory_transactions LIKE 'status'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='info'>ℹ️ status column already exists in inventory_transactions table</div>";
    } else {
        try {
            $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN status ENUM('Pending', 'Approved', 'Completed', 'Cancelled') DEFAULT 'Pending' AFTER processed_by");
            echo "<div class='success'>✅ Added status column to inventory_transactions table</div>";
            $migrationCount++;
            
            $conn->exec("ALTER TABLE inventory_transactions ADD INDEX idx_status (status)");
            echo "<div class='success'>✅ Added index for status</div>";
            
            // Update existing records
            $result = $conn->exec("UPDATE inventory_transactions SET status = 'Completed' WHERE status IS NULL OR status = ''");
            echo "<div class='success'>✅ Updated $result existing transaction(s) to 'Completed' status</div>";
        } catch (PDOException $e) {
            $errors[] = "Transactions.status: " . $e->getMessage();
            echo "<div class='error'>❌ Error adding status: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    echo "<h2>📊 Migration Summary</h2>";
    
    if ($migrationCount > 0) {
        echo "<div class='success'><strong>✅ Success!</strong> Applied $migrationCount migration(s) to your database.</div>";
    } else {
        echo "<div class='info'><strong>ℹ️ All up to date!</strong> Your database is already at the latest version.</div>";
    }
    
    if (!empty($errors)) {
        echo "<div class='warning'><strong>⚠️ Some migrations had errors:</strong><ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul></div>";
    }
    
    echo "<h2>🎯 Next Steps</h2>";
    echo "<ul>";
    echo "<li><strong>Expiration Date:</strong> Now available when adding/editing items</li>";
    echo "<li><strong>Transaction Status:</strong> Track order status (Pending/Approved/Completed/Cancelled)</li>";
    echo "<li><strong>Item Details:</strong> Click on any item row to see detailed information</li>";
    echo "</ul>";
    
    echo "<p style='margin-top:30px;'><a href='item-master-list.php' style='display:inline-block;background:linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);color:white;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;'>← Back to Item Master List</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>❌ Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Please check your database connection and try again.</p>";
}

echo "</div></body></html>";
?>
