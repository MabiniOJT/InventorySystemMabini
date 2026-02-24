<?php
/**
 * Check Current Database Structure
 * Shows what columns exist in your tables
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Database Structure Check</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".container{max-width:1000px;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo "table{width:100%;border-collapse:collapse;margin:15px 0;}";
echo "th{background:#4CAF50;color:white;padding:12px;text-align:left;}";
echo "td{padding:10px;border-bottom:1px solid #ddd;}";
echo "tr:hover{background:#f5f5f5;}";
echo "h2{color:#333;border-bottom:2px solid #4CAF50;padding-bottom:10px;margin-top:30px;}";
echo ".missing{color:#ef4444;font-weight:bold;}</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔍 Database Structure Check</h1>";

try {
    // Check items table
    echo "<h2>Items Table</h2>";
    $stmt = $conn->query("SHOW COLUMNS FROM items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    // Check if expiration_date exists
    $hasExpiration = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'expiration_date') {
            $hasExpiration = true;
            break;
        }
    }
    if (!$hasExpiration) {
        echo "<p class='missing'>⚠️ Missing: expiration_date column</p>";
    } else {
        echo "<p style='color:#22c55e;'>✅ Has expiration_date column</p>";
    }
    
    // Check inventory_transactions table
    echo "<h2>Inventory Transactions Table</h2>";
    $stmt = $conn->query("SHOW COLUMNS FROM inventory_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    // Check for missing columns
    $hasProcessedBy = false;
    $hasStatus = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'processed_by') $hasProcessedBy = true;
        if ($col['Field'] === 'status') $hasStatus = true;
    }
    
    echo "<h3>Required Columns Check:</h3>";
    if (!$hasProcessedBy) {
        echo "<p class='missing'>⚠️ Missing: processed_by column (tracks who processed the transaction)</p>";
    } else {
        echo "<p style='color:#22c55e;'>✅ Has processed_by column</p>";
    }
    
    if (!$hasStatus) {
        echo "<p class='missing'>⚠️ Missing: status column (tracks transaction status)</p>";
    } else {
        echo "<p style='color:#22c55e;'>✅ Has status column</p>";
    }
    
    echo "<p style='margin-top:30px;'><a href='fix_transactions_table.php' style='display:inline-block;background:#4CAF50;color:white;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;'>→ Fix Missing Columns</a></p>";
    echo "<p><a href='item-master-list.php' style='color:#666;'>← Back to Item Master List</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='missing'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>
