<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays
if (!isset($_SESSION['products'])) $_SESSION['products'] = [];
if (!isset($_SESSION['costs'])) $_SESSION['costs'] = [];
if (!isset($_SESSION['quantities'])) $_SESSION['quantities'] = [];
if (!isset($_SESSION['issued'])) $_SESSION['issued'] = [];

// Calculate dashboard stats
$totalProducts = count($_SESSION['products']);
$totalQuantity = 0;
foreach ($_SESSION['quantities'] as $qty) {
    $totalQuantity += floatval($qty['quantity']);
}
$totalIssued = 0;
foreach ($_SESSION['issued'] as $issue) {
    $totalIssued += floatval($issue['quantity_issued']);
}
$totalValue = 0;
foreach ($_SESSION['costs'] as $cost) {
    $totalValue += floatval($cost['unit_cost']);
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/navbar.php'; ?>
        
        <div class="content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="number"><?php echo $totalProducts; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Quantity</h3>
                    <div class="number"><?php echo number_format($totalQuantity); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Quantity Issued</h3>
                    <div class="number"><?php echo number_format($totalIssued); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Value</h3>
                    <div class="number">₱<?php echo number_format($totalValue, 2); ?></div>
                </div>
            </div>
            
            <div class="welcome-box">
                <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>! 👋</h2>
                <p>Here's an overview of your inventory system. Use the sidebar to navigate through different sections.</p>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
