<?php
/**
 * Database Configuration
 * 
 * This file manages the database connection using PDO.
 * Database credentials are loaded from .env file.
 */

// Load environment variables
require_once __DIR__ . '/env.php';

/**
 * Get database connection (singleton pattern)
 * Returns a PDO connection to the database
 */
function getDatabaseConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $host = env('DB_HOST', 'localhost');
            $dbname = env('DB_NAME', 'mabini_inventory');
            $username = env('DB_USER', 'root');
            $password = env('DB_PASS', '');
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $conn = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            // In production, log this error instead of displaying it
            if (env('APP_DEBUG', true)) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact administrator.");
            }
        }
    }
    
    return $conn;
}

/**
 * Check if database exists
 * Returns true if database exists, false otherwise
 */
function checkDatabaseExists() {
    try {
        $host = env('DB_HOST', 'localhost');
        $username = env('DB_USER', 'root');
        $password = env('DB_PASS', '');
        
        $conn = new PDO("mysql:host=$host", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $dbname = env('DB_NAME', 'mabini_inventory');
        $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create database if it doesn't exist
 */
function createDatabase() {
    try {
        $host = env('DB_HOST', 'localhost');
        $username = env('DB_USER', 'root');
        $password = env('DB_PASS', '');
        $dbname = env('DB_NAME', 'mabini_inventory');
        
        $conn = new PDO("mysql:host=$host", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $conn->exec($sql);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
