<?php
/**
 * Fix Inventory Transactions Table
 * Adds missing columns to match the schema
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Fix Transactions Table</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".container{max-width:900px;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#22c55e;padding:10px 15px;background:#f0fdf4;border-left:4px solid #22c55e;margin:8px 0;border-radius:4px;}";
echo ".error{color:#ef4444;padding:10px 15px;background:#fef2f2;border-left:4px solid #ef4444;margin:8px 0;border-radius:4px;}";
echo ".info{color:#3b82f6;padding:10px 15px;background:#eff6ff;border-left:4px solid #3b82f6;margin:8px 0;border-radius:4px;}";
echo "h1{color:#333;}h2{color:#666;font-size:18px;margin-top:25px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;}";
echo "p{color:#666;line-height:1.6;}</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔧 Fix Inventory Transactions Table</h1>";

$fixed = 0;
$errors = [];

try {
    // Get current columns
    $stmt = $conn->query("SHOW COLUMNS FROM inventory_transactions");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    echo "<h2>Current Columns:</h2>";
    echo "<p style='background:#f9fafb;padding:10px;border-radius:4px;'>" . implode(', ', $existingColumns) . "</p>";
    
    echo "<h2>Adding Missing Columns:</h2>";
    
    // Add processed_by column if missing
    if (!in_array('processed_by', $existingColumns)) {
        try {
            // Find the column before which we should add processed_by
            // It should be after 'remarks'
            if (in_array('remarks', $existingColumns)) {
                $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN processed_by INT AFTER remarks");
            } else {
                $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN processed_by INT");
            }
            echo "<div class='success'>✅ Added processed_by column</div>";
            
            // Add foreign key
            try {
                $conn->exec("ALTER TABLE inventory_transactions ADD FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL");
                echo "<div class='success'>✅ Added foreign key for processed_by</div>";
            } catch (PDOException $e) {
                echo "<div class='info'>ℹ️ Foreign key constraint may already exist or users table missing</div>";
            }
            
            $fixed++;
        } catch (PDOException $e) {
            $errors[] = "processed_by: " . $e->getMessage();
            echo "<div class='error'>❌ Error adding processed_by: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ processed_by column already exists</div>";
    }
    
    // Add status column if missing
    if (!in_array('status', $existingColumns)) {
        try {
            if (in_array('processed_by', $existingColumns)) {
                $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN status ENUM('Pending', 'Approved', 'Completed', 'Cancelled') DEFAULT 'Completed' AFTER processed_by");
            } else {
                $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN status ENUM('Pending', 'Approved', 'Completed', 'Cancelled') DEFAULT 'Completed'");
            }
            echo "<div class='success'>✅ Added status column</div>";
            
            // Add index
            $conn->exec("ALTER TABLE inventory_transactions ADD INDEX idx_status (status)");
            echo "<div class='success'>✅ Added index for status</div>";
            $fixed++;
        } catch (PDOException $e) {
            $errors[] = "status: " . $e->getMessage();
            echo "<div class='error'>❌ Error adding status: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ status column already exists</div>";
    }
    
    // Add created_at if missing
    if (!in_array('created_at', $existingColumns)) {
        try {
            $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            echo "<div class='success'>✅ Added created_at column</div>";
            $fixed++;
        } catch (PDOException $e) {
            echo "<div class='info'>ℹ️ Could not add created_at: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    // Add updated_at if missing
    if (!in_array('updated_at', $existingColumns)) {
        try {
            $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "<div class='success'>✅ Added updated_at column</div>";
            $fixed++;
        } catch (PDOException $e) {
            echo "<div class='info'>ℹ️ Could not add updated_at: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    echo "<h2>📊 Summary</h2>";
    
    if ($fixed > 0) {
        echo "<div class='success'><strong>✅ Success!</strong> Fixed $fixed column(s) in inventory_transactions table.</div>";
        echo "<p>Your database is now up to date and ready to use!</p>";
    } else {
        echo "<div class='info'><strong>ℹ️ All Good!</strong> The inventory_transactions table already has all required columns.</div>";
    }
    
    if (!empty($errors)) {
        echo "<div class='error'><strong>⚠️ Some errors occurred:</strong><ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul></div>";
    }
    
    echo "<p style='margin-top:30px;'>";
    echo "<a href='item-master-list.php' style='display:inline-block;background:linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);color:white;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;margin-right:10px;'>← Back to Item Master List</a>";
    echo "<a href='check_database.php' style='display:inline-block;background:#6c757d;color:white;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;'>Check Database Structure</a>";
    echo "</p>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>❌ Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>
