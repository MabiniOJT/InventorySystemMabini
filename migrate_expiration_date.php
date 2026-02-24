<?php
/**
 * Database Migration Helper
 * Adds expiration_date field to items table
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Database Migration</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".container{max-width:800px;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#22c55e;padding:15px;background:#f0fdf4;border-left:4px solid #22c55e;margin:10px 0;}";
echo ".error{color:#ef4444;padding:15px;background:#fef2f2;border-left:4px solid #ef4444;margin:10px 0;}";
echo "h1{color:#333;}p{color:#666;line-height:1.6;}</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔧 Database Migration</h1>";
echo "<p>This script will add the <strong>expiration_date</strong> field to the items table for tracking medical supplies and other perishable items.</p>";

try {
    // Check if column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM items LIKE 'expiration_date'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='error'>⚠️ The expiration_date column already exists. No migration needed.</div>";
    } else {
        // Add the column
        $conn->exec("ALTER TABLE items ADD COLUMN expiration_date DATE AFTER location");
        echo "<div class='success'>✅ Successfully added expiration_date column to items table.</div>";
        
        // Add index
        $conn->exec("ALTER TABLE items ADD INDEX idx_expiration_date (expiration_date)");
        echo "<div class='success'>✅ Successfully added index for expiration_date.</div>";
    }
    
    echo "<p style='margin-top:20px;'>Migration completed successfully! You can now track expiration dates for your items.</p>";
    echo "<p><a href='item-master-list.php' style='color:#4CAF50;text-decoration:none;font-weight:bold;'>← Back to Item Master List</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Migration Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Please check your database connection and try again.</p>";
}

echo "</div></body></html>";
?>
