<?php
/**
 * Reset Admin Password
 * Creates/resets the admin user with a default password
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Reset Admin Password</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".container{max-width:700px;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#22c55e;padding:15px;background:#f0fdf4;border-left:4px solid #22c55e;margin:10px 0;border-radius:4px;}";
echo ".info{color:#3b82f6;padding:15px;background:#eff6ff;border-left:4px solid #3b82f6;margin:10px 0;border-radius:4px;}";
echo ".credentials{background:#f9fafb;padding:20px;border-radius:8px;margin:20px 0;border:2px solid #e5e7eb;}";
echo ".credentials code{background:#fff;padding:2px 8px;border-radius:4px;color:#ef4444;font-weight:bold;}";
echo "h1{color:#333;}p{color:#666;line-height:1.6;}</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔐 Reset Admin Password</h1>";

try {
    // Check for existing users
    $stmt = $conn->query("SELECT id, username, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'><strong>Current users found: " . count($users) . "</strong></div>";
    
    // Set default password: admin123
    $defaultPassword = 'admin123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    if (!empty($users)) {
        // Update existing admin user
        $adminUser = $users[0]; // Get first user
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $adminUser['id']]);
        
        echo "<div class='success'>✅ Password reset successfully!</div>";
        echo "<div class='credentials'>";
        echo "<h3>Login Credentials:</h3>";
        echo "<p><strong>Username:</strong> <code>" . htmlspecialchars($adminUser['username']) . "</code></p>";
        echo "<p><strong>Email:</strong> <code>" . htmlspecialchars($adminUser['email'] ?: 'N/A') . "</code></p>";
        echo "<p><strong>Password:</strong> <code>admin123</code></p>";
        echo "</div>";
        echo "<p style='color:#666;font-size:14px;'>✨ You can now login with either the username or email and the password above.</p>";
        
    } else {
        // No users found, create a new admin user
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, full_name, role, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            'admin',
            'admin@mabini.com',
            $hashedPassword,
            'System Administrator',
            'Admin',
            'Active'
        ]);
        
        echo "<div class='success'>✅ New admin user created successfully!</div>";
        echo "<div class='credentials'>";
        echo "<h3>Login Credentials:</h3>";
        echo "<p><strong>Username:</strong> <code>admin</code></p>";
        echo "<p><strong>Email:</strong> <code>admin@mabini.com</code></p>";
        echo "<p><strong>Password:</strong> <code>admin123</code></p>";
        echo "</div>";
        echo "<p style='color:#666;font-size:14px;'>✨ You can now login with either username or email and the password above.</p>";
    }
    
    echo "<p style='margin-top:30px;'>";
    echo "<a href='index.php' style='display:inline-block;background:#4CAF50;color:white;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;'>→ Go to Login Page</a>";
    echo "</p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>
