<?php
/**
 * Database Setup Script
 * 
 * This script creates the database structure for Mabini Inventory System.
 * Run this once to set up all required tables and initial data.
 */

require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';
$setupComplete = false;

// Check if tables already exist
try {
    $conn = getDatabaseConnection();
    $stmt = $conn->query("SHOW TABLES");
    $tableCount = $stmt->rowCount();
    
    if ($tableCount > 0) {
        $setupComplete = true;
        $message = "Database is already set up with $tableCount tables.";
    }
} catch (Exception $e) {
    // Database might not exist yet
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_database'])) {
    try {
        // First, ensure database exists
        if (!createDatabase()) {
            throw new Exception("Failed to create database");
        }
        
        // Read schema file
        $schemaFile = __DIR__ . '/database/schema.sql';
        
        if (!file_exists($schemaFile)) {
            throw new Exception("Schema file not found at: $schemaFile");
        }
        
        $sql = file_get_contents($schemaFile);
        
        // Connect without specific database first
        $host = env('DB_HOST', 'localhost');
        $username = env('DB_USER', 'root');
        $password = env('DB_PASS', '');
        
        $conn = new PDO("mysql:host=$host", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       strpos($stmt, '--') !== 0 && 
                       strpos($stmt, '/*') !== 0;
            }
        );
        
        // Execute each statement
        $executed = 0;
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $conn->exec($statement);
                    $executed++;
                } catch (PDOException $e) {
                    // Continue on errors (some statements might already exist)
                    if (env('APP_DEBUG', true)) {
                        error_log("SQL Error: " . $e->getMessage());
                    }
                }
            }
        }
        
        $message = "Database setup completed successfully! Executed $executed SQL statements.";
        $setupComplete = true;
        
    } catch (Exception $e) {
        $error = "Setup Error: " . $e->getMessage();
    }
}

// Get database status
$dbExists = checkDatabaseExists();
$dbName = env('DB_NAME', 'mabini_inventory');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .status-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .status {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .status:last-child {
            border-bottom: none;
        }
        
        .status-label {
            font-weight: 600;
            color: #333;
        }
        
        .status-value {
            color: #666;
        }
        
        .status-value.success {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-value.error {
            color: #dc3545;
            font-weight: 600;
        }
        
        .btn {
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }
        
        .info-list {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-list h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .info-list ul {
            margin-left: 20px;
            color: #856404;
        }
        
        .info-list li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Database Setup</h1>
            <p>Mabini Inventory System</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <strong>✓ Success!</strong><br>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>❌ Error!</strong><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="status-box">
            <h3 style="margin-bottom: 15px; color: #333;">System Status</h3>
            <div class="status">
                <span class="status-label">Database Name:</span>
                <span class="status-value"><?php echo htmlspecialchars($dbName); ?></span>
            </div>
            <div class="status">
                <span class="status-label">Database Server:</span>
                <span class="status-value"><?php echo htmlspecialchars(env('DB_HOST', 'localhost')); ?></span>
            </div>
            <div class="status">
                <span class="status-label">Database Exists:</span>
                <span class="status-value <?php echo $dbExists ? 'success' : 'error'; ?>">
                    <?php echo $dbExists ? '✓ Yes' : '✗ No'; ?>
                </span>
            </div>
            <div class="status">
                <span class="status-label">Setup Status:</span>
                <span class="status-value <?php echo $setupComplete ? 'success' : 'error'; ?>">
                    <?php echo $setupComplete ? '✓ Complete' : '✗ Not Set Up'; ?>
                </span>
            </div>
        </div>
        
        <?php if (!$setupComplete): ?>
            <div class="info-list">
                <h3>📋 What will be created:</h3>
                <ul>
                    <li>Users table (with default admin user)</li>
                    <li>Items/Products table</li>
                    <li>Categories table (6 default categories)</li>
                    <li>Offices table (3 default offices)</li>
                    <li>Suppliers table</li>
                    <li>Inventory Transactions table</li>
                    <li>Stock Movements table</li>
                    <li>System views for reporting</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="setup_database" class="btn">
                    🚀 Setup Database Now
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>ℹ️ Database Already Set Up</strong><br>
                Your database structure is ready. You can now proceed to load sample data.
            </div>
            
            <a href="load_sample_data.php" style="text-decoration: none;">
                <button type="button" class="btn">
                    📥 Load Sample Data (67 items)
                </button>
            </a>
        <?php endif; ?>
        
        <a href="index.php" style="text-decoration: none;">
            <button type="button" class="btn btn-secondary">
                ← Back to Login
            </button>
        </a>
    </div>
</body>
</html>
