<?php
/**
 * Create Database Script
 * Creates the mabini_inventory database if it doesn't exist
 */

require_once __DIR__ . '/config/env.php';

// Load .env file
loadEnv(__DIR__ . '/.env');

try {
    $host = env('DB_HOST', 'localhost');
    $dbname = env('DB_NAME', 'mabini_inventory');
    $username = env('DB_USER', 'root');
    $password = env('DB_PASS', '');
    
    // Connect without specifying database
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "✅ Database '$dbname' created successfully!\n";
    echo "Now run: php setup_database.php\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
