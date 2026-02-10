<?php
session_start();

// Demo users database (in production, use a real database)
$users = [
    'admin@mabini.com' => [
        'password' => 'password',
        'name' => 'Admin',
        'role' => 'admin'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please enter both email and password.';
        $_SESSION['old_email'] = $email;
        header('Location: index.php');
        exit;
    }

    // Check if user exists
    if (isset($users[$email])) {
        // Verify password
        if ($users[$email]['password'] === $password) {
            // Login successful
            $_SESSION['user'] = [
                'email' => $email,
                'name' => $users[$email]['name'],
                'role' => $users[$email]['role']
            ];
            
            $_SESSION['logged_in'] = true;
            
            // Handle remember me
            if ($remember) {
                setcookie('remember_email', $email, time() + (86400 * 30), '/');
            }
            
            header('Location: dashboard.php');
            exit;
        }
    }

    // Login failed
    $_SESSION['error'] = 'Invalid email or password.';
    $_SESSION['old_email'] = $email;
    header('Location: index.php');
    exit;
} else {
    // If not POST request, redirect to login page
    header('Location: index.php');
    exit;
}
?>
