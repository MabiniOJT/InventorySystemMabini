<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .success { color: #28a745; padding: 12px; background: #d4edda; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { color: #721c24; padding: 12px; background: #f8d7da; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { color: #004085; padding: 12px; background: #cce5ff; border-radius: 5px; margin: 10px 0; border-left: 4px solid #007bff; }
        .step { margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔧 Database Setup</h1>

<?php
$host = 'localhost';
$dbname = 'mabini_inventory';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL
    echo "<div class='step'>📡 Connecting to MySQL server...</div>";
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    echo "<div class='success'>✓ Connected to MySQL successfully!</div>";
    
    // Create database
    echo "<div class='step'>🗄️ Creating database '$dbname'...</div>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✓ Database created!</div>";
    
    // Use database
    $pdo->exec("USE `$dbname`");
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Create users table
    echo "<div class='step'>👥 Creating users table...</div>";
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        role ENUM('Admin', 'Staff', 'Viewer') DEFAULT 'Staff',
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_role (role),
        INDEX idx_status (status)
    ) ENGINE=InnoDB");
    echo "<div class='success'>✓ Users table created!</div>";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Insert admin user
    echo "<div class='step'>🔑 Creating admin user...</div>";
    $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
    
    // Check if admin already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin@mabini.com'");
    if ($stmt->fetchColumn() > 0) {
        // Update existing admin
        $pdo->exec("UPDATE users SET password = '$hashedPassword', full_name = 'Admin', role = 'Admin', status = 'Active' WHERE username = 'admin@mabini.com'");
        echo "<div class='success'>✓ Admin user updated!</div>";
    } else {
        // Insert new admin
        $pdo->exec("INSERT INTO users (username, password, full_name, email, role, status) 
                    VALUES ('admin@mabini.com', '$hashedPassword', 'Admin', 'admin@mabini.com', 'Admin', 'Active')");
        echo "<div class='success'>✓ Admin user created!</div>";
    }
    
    // Success message
    echo "<div class='success' style='margin-top: 20px;'>
        <strong>✅ Setup Complete!</strong><br><br>
        <strong>Login Credentials:</strong><br>
        Email: <strong>admin@mabini.com</strong><br>
        Password: <strong>password</strong>
    </div>";
    
    echo "<a href='index.php' class='btn'>← Go to Login Page</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>❌ Database Error:</strong><br>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'><strong>Troubleshooting:</strong><br>";
    echo "1. Make sure XAMPP MySQL is running (green in XAMPP Control Panel)<br>";
    echo "2. Try restarting MySQL in XAMPP<br>";
    echo "3. Check if MySQL is running on port 3306</div>";
}
?>

    </div>
</body>
</html>
