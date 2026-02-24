<?php
session_start();
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['email'] ?? '';  // Can be username or email
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Please enter both username and password.';
        $_SESSION['old_email'] = $username;
        header('Location: index.php');
        exit;
    }

    try {
        $conn = getDatabaseConnection();
        
        // Check if user exists (by username or email)
        $stmt = $conn->prepare("
            SELECT id, username, password, full_name, email, role, status 
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'Active'
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user'] = $user['full_name'] ?: $user['username'];
            $_SESSION['email'] = $user['email'] ?: '';
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Handle remember me
            if ($remember) {
                setcookie('remember_email', $username, time() + (86400 * 30), '/');
            }
            
            header('Location: dashboard.php');
            exit;
        }

        // Login failed
        $_SESSION['error'] = 'Invalid username or password.';
        $_SESSION['old_email'] = $username;
        header('Location: index.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error. Please try again later.';
        $_SESSION['old_email'] = $username;
        header('Location: index.php');
        exit;
    }
} else {
    // If not POST request, redirect to login page
    header('Location: index.php');
    exit;
}
?>
