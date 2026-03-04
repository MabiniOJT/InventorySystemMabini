<?php
/**
 * Simple CLI Database Installer
 * Imports schema.sql into the database
 */

require_once __DIR__ . '/config/env.php';
loadEnv(__DIR__ . '/.env');

try {
    echo "🔧 Mabini Inventory System - Database Installer\n";
    echo "================================================\n\n";
    
    $host = env('DB_HOST', 'localhost');
    $dbname = env('DB_NAME', 'mabini_inventory');
    $username = env('DB_USER', 'root');
    $password = env('DB_PASS', '');
    
    // Step 1: Connect to MySQL server (without database)
    echo "Step 1: Connecting to MySQL server...\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Connected!\n\n";
    
    // Step 2: Create database
    echo "Step 2: Creating database '$dbname'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    echo "✅ Database ready!\n\n";
    
    // Step 3: Import schema
    echo "Step 3: Importing schema.sql...\n";
    $schemaPath = __DIR__ . '/database/schema.sql';
    
    if (!file_exists($schemaPath)) {
        throw new Exception("Schema file not found: $schemaPath");
    }
    
    $sql = file_get_contents($schemaPath);
    
    // Remove comments and split by semicolons
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $skipped = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $success++;
            echo ".";
            if ($success % 50 == 0) echo "\n";
        } catch (PDOException $e) {
            // Skip errors for already existing tables
            $skipped++;
        }
    }
    
    echo "\n✅ Schema imported! ($success statements executed, $skipped skipped)\n\n";
    
    // Step 4: Verify tables
    echo "Step 4: Verifying tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "  ✓ $table\n";
    }
    
    echo "\n✅ Database setup complete!\n";
    echo "================================================\n";
    echo "Next: Run migrations with: php run_migrations.php\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
