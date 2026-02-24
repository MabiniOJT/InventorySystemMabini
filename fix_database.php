<?php
/**
 * Complete Database Fix - Run This First!
 * Fixes all missing columns in one go
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Complete Database Fix</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".container{max-width:1000px;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#22c55e;padding:10px 15px;background:#f0fdf4;border-left:4px solid #22c55e;margin:8px 0;border-radius:4px;}";
echo ".error{color:#ef4444;padding:10px 15px;background:#fef2f2;border-left:4px solid #ef4444;margin:8px 0;border-radius:4px;}";
echo ".info{color:#3b82f6;padding:10px 15px;background:#eff6ff;border-left:4px solid #3b82f6;margin:8px 0;border-radius:4px;}";
echo ".warning{color:#f59e0b;padding:10px 15px;background:#fffbeb;border-left:4px solid #f59e0b;margin:8px 0;border-radius:4px;}";
echo "h1{color:#333;margin-bottom:10px;}h2{color:#666;font-size:18px;margin-top:30px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;}";
echo "p{color:#666;line-height:1.6;}ul{margin:10px 0;}li{margin:5px 0;color:#666;}</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔧 Complete Database Fix</h1>";
echo "<p>This script will fix all missing columns in your database tables.</p>";

$totalFixed = 0;
$allErrors = [];

try {
    // ==================== FIX USERS TABLE ====================
    echo "<h2>1. Users Table</h2>";
    
    $stmt = $conn->query("SHOW COLUMNS FROM users");
    $userColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userColumns[] = $row['Field'];
    }
    
    // Add full_name
    if (!in_array('full_name', $userColumns)) {
        try {
            $conn->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(100) DEFAULT '' AFTER password");
            $conn->exec("UPDATE users SET full_name = username WHERE full_name = ''");
            $conn->exec("ALTER TABLE users MODIFY full_name VARCHAR(100) NOT NULL");
            echo "<div class='success'>✅ Added full_name column</div>";
            $totalFixed++;
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ full_name already exists</div>";
    }
    
    // Add email
    if (!in_array('email', $userColumns)) {
        $conn->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100)");
        echo "<div class='success'>✅ Added email column</div>";
        $totalFixed++;
    }
    
    // Add role
    if (!in_array('role', $userColumns)) {
        $conn->exec("ALTER TABLE users ADD COLUMN role ENUM('Admin', 'Staff', 'Viewer') DEFAULT 'Admin'");
        echo "<div class='success'>✅ Added role column</div>";
        $totalFixed++;
    }
    
    // Add status
    if (!in_array('status', $userColumns)) {
        $conn->exec("ALTER TABLE users ADD COLUMN status ENUM('Active', 'Inactive') DEFAULT 'Active'");
        echo "<div class='success'>✅ Added status column</div>";
        $totalFixed++;
    }
    
    // Ensure default admin user exists
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        // Create default admin user (password: admin123)
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->exec("
            INSERT INTO users (username, password, full_name, email, role, status) 
            VALUES ('admin', '$hashedPassword', 'System Administrator', 'admin@mabini.com', 'Admin', 'Active')
        ");
        echo "<div class='success'>✅ Created default admin user (username: admin, password: admin123)</div>";
        $totalFixed++;
    } else {
        echo "<div class='info'>ℹ️ Admin user already exists</div>";
    }
    
    // ==================== FIX ITEMS TABLE ====================
    echo "<h2>2. Items Table</h2>";
    
    $stmt = $conn->query("SHOW COLUMNS FROM items");
    $itemColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $itemColumns[] = $row['Field'];
    }
    
    // Add expiration_date
    if (!in_array('expiration_date', $itemColumns)) {
        $conn->exec("ALTER TABLE items ADD COLUMN expiration_date DATE AFTER location");
        $conn->exec("ALTER TABLE items ADD INDEX idx_expiration_date (expiration_date)");
        echo "<div class='success'>✅ Added expiration_date column and index</div>";
        $totalFixed++;
    } else {
        echo "<div class='info'>ℹ️ expiration_date already exists</div>";
    }
    
    // ==================== FIX INVENTORY_TRANSACTIONS TABLE ====================
    echo "<h2>3. Inventory Transactions Table</h2>";
    
    $stmt = $conn->query("SHOW COLUMNS FROM inventory_transactions");
    $transColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $transColumns[] = $row['Field'];
    }
    
    // Add created_by (who created the transaction)
    if (!in_array('created_by', $transColumns)) {
        $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN created_by INT");
        $conn->exec("ALTER TABLE inventory_transactions ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "<div class='success'>✅ Added created_by column with foreign key</div>";
        $totalFixed++;
    } else {
        echo "<div class='info'>ℹ️ created_by already exists</div>";
    }
    
    // Add processed_by (who approved/processed the transaction)
    if (!in_array('processed_by', $transColumns)) {
        $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN processed_by INT");
        $conn->exec("ALTER TABLE inventory_transactions ADD FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "<div class='success'>✅ Added processed_by column with foreign key</div>";
        $totalFixed++;
    } else {
        echo "<div class='info'>ℹ️ processed_by already exists</div>";
    }
    
    // Add status
    if (!in_array('status', $transColumns)) {
        $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN status ENUM('Pending', 'Approved', 'Completed', 'Cancelled') DEFAULT 'Completed'");
        $conn->exec("ALTER TABLE inventory_transactions ADD INDEX idx_status (status)");
        echo "<div class='success'>✅ Added status column and index</div>";
        $totalFixed++;
    } else {
        echo "<div class='info'>ℹ️ status already exists</div>";
    }
    
    // Add created_at
    if (!in_array('created_at', $transColumns)) {
        $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "<div class='success'>✅ Added created_at column</div>";
        $totalFixed++;
    }
    
    // Add updated_at
    if (!in_array('updated_at', $transColumns)) {
        $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "<div class='success'>✅ Added updated_at column</div>";
        $totalFixed++;
    }
    
    // ==================== SUMMARY ====================
    echo "<h2>📊 Migration Summary</h2>";
    
    if ($totalFixed > 0) {
        echo "<div class='success'><strong>✅ Migration Complete!</strong><br>";
        echo "Successfully added/updated $totalFixed column(s) across all tables.</div>";
    } else {
        echo "<div class='info'><strong>ℹ️ All Up to Date!</strong><br>";
        echo "Your database already has all required columns.</div>";
    }
    
    echo "<h2>🎯 Next Steps</h2>";
    echo "<div style='background:#f9fafb;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<ol style='margin:0;padding-left:20px;'>";
    echo "<li style='margin:10px 0;'><strong>Test the new pages:</strong> Try accessing Process Transactions, Issue Items, etc.</li>";
    echo "<li style='margin:10px 0;'><strong>Create a test transaction:</strong> Go to Issue Items and create a test request</li>";
    echo "<li style='margin:10px 0;'><strong>Process it:</strong> Visit Process Transactions and approve/complete it</li>";
    echo "<li style='margin:10px 0;'><strong>Check reorder management:</strong> See which items need reordering</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p style='margin-top:30px;text-align:center;'>";
    echo "<a href='dashboard.php' style='display:inline-block;background:linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);color:white;padding:15px 30px;border-radius:8px;text-decoration:none;font-weight:bold;margin:5px;font-size:16px;'>Go to Dashboard</a>";
    echo "<a href='process-transactions.php' style='display:inline-block;background:#4CAF50;color:white;padding:15px 30px;border-radius:8px;text-decoration:none;font-weight:bold;margin:5px;font-size:16px;'>Process Transactions</a>";
    echo "<a href='issue-items.php' style='display:inline-block;background:#2196F3;color:white;padding:15px 30px;border-radius:8px;text-decoration:none;font-weight:bold;margin:5px;font-size:16px;'>Issue Items</a>";
    echo "</p>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>❌ Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Please make sure your database connection is working and try again.</p>";
}

echo "</div></body></html>";
?>
