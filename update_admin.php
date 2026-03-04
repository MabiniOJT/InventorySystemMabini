<?php
/**
 * Update Admin Credentials
 * Updates admin username and password
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "🔐 Updating Admin Credentials\n";
echo "================================\n\n";

try {
    // New credentials
    $newUsername = 'admin@mabini.com';
    $newPassword = 'password';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Check if admin user exists
    $stmt = $conn->query("SELECT id, username FROM users ORDER BY id LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Update existing admin user
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, email = ? WHERE id = ?");
        $stmt->execute([$newUsername, $hashedPassword, $newUsername, $user['id']]);
        
        echo "✅ Admin credentials updated successfully!\n\n";
        echo "Old username: " . $user['username'] . "\n";
        echo "New username: " . $newUsername . "\n";
        echo "New password: " . $newPassword . "\n\n";
        
    } else {
        // Create new admin user
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $newUsername,
            $hashedPassword,
            'System Administrator',
            $newUsername,
            'Admin',
            'Active'
        ]);
        
        echo "✅ Admin user created successfully!\n\n";
        echo "Username: " . $newUsername . "\n";
        echo "Password: " . $newPassword . "\n\n";
    }
    
    echo "================================\n";
    echo "You can now login at: http://localhost:8000\n";
    echo "Username: admin@mabini.com\n";
    echo "Password: password\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
