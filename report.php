<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays
if (!isset($_SESSION['products'])) $_SESSION['products'] = [];
if (!isset($_SESSION['offices'])) $_SESSION['offices'] = [];
if (!isset($_SESSION['issued'])) $_SESSION['issued'] = [];

$user = $_SESSION['user'];

// Calculate report data
$totalItems = count($_SESSION['products']);
$totalOffices = count($_SESSION['offices']);
$totalIssued = 0;
$totalValue = 0;

foreach ($_SESSION['issued'] as $issue) {
    $totalIssued += floatval($issue['quantity_issued'] ?? 0);
}

foreach ($_SESSION['products'] as $product) {
    $totalValue += floatval($product['price'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .report-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid;
        }
        
        .report-card.green {
            border-left-color: #4CAF50;
        }
        
        .report-card.blue {
            border-left-color: #2196F3;
        }
        
        .report-card.orange {
            border-left-color: #FF9800;
        }
        
        .report-card.purple {
            border-left-color: #9C27B0;
        }
        
        .report-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .report-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .report-card .subtitle {
            color: #999;
            font-size: 12px;
        }
        
        .report-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .report-section h3 {
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-item {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-item label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-size: 14px;
        }
        
        .filter-item select,
        .filter-item input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e8ed;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-export {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            margin-right: 10px;
        }
        
        .btn-export:hover {
            background: #45a049;
        }
        
        .btn-print {
            background: #2196F3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-print:hover {
            background: #0b7dda;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/navbar.php'; ?>

        <div class="content">
            <div class="content-section active">
                <div class="section-header">
                    <h2>Inventory Reports</h2>
                    <div>
                        <button class="btn-export" onclick="exportReport()">📊 Export to Excel</button>
                        <button class="btn-print" onclick="window.print()">🖨️ Print Report</button>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="report-grid">
                    <div class="report-card green">
                        <h3>Total Items</h3>
                        <div class="value"><?php echo $totalItems; ?></div>
                        <div class="subtitle">Items in inventory</div>
                    </div>
                    
                    <div class="report-card blue">
                        <h3>Total Offices</h3>
                        <div class="value"><?php echo $totalOffices; ?></div>
                        <div class="subtitle">Registered offices</div>
                    </div>
                    
                    <div class="report-card orange">
                        <h3>Items Issued</h3>
                        <div class="value"><?php echo $totalIssued; ?></div>
                        <div class="subtitle">Total quantity issued</div>
                    </div>
                    
                    <div class="report-card purple">
                        <h3>Total Value</h3>
                        <div class="value">₱<?php echo number_format($totalValue, 2); ?></div>
                        <div class="subtitle">Inventory value</div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="report-section">
                    <h3>Filter Reports</h3>
                    <div class="filter-section">
                        <div class="filter-item">
                            <label>Report Type</label>
                            <select id="reportType">
                                <option value="all">All Items</option>
                                <option value="issued">Issued Items</option>
                                <option value="available">Available Items</option>
                                <option value="by-office">By Office</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label>Date From</label>
                            <input type="date" id="dateFrom">
                        </div>
                        
                        <div class="filter-item">
                            <label>Date To</label>
                            <input type="date" id="dateTo">
                        </div>
                        
                        <div class="filter-item" style="display: flex; align-items: flex-end;">
                            <button class="btn-primary" onclick="generateReport()">Generate Report</button>
                        </div>
                    </div>
                </div>
                
                <!-- Report Table -->
                <div class="report-section">
                    <h3>Report Details</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Date Issued</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($_SESSION['products'])): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <p>No data available. Add items to generate reports.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($_SESSION['products'] as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                        <td>₱<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                        <td><?php echo htmlspecialchars($product['date_issued'] ?? 'N/A'); ?></td>
                                        <td><span style="color: #4CAF50; font-weight: 600;">Available</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function generateReport() {
            const reportType = document.getElementById('reportType').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            alert(`Generating ${reportType} report from ${dateFrom || 'start'} to ${dateTo || 'today'}...`);
            // Add report generation logic here
        }
        
        function exportReport() {
            alert('Exporting report to Excel...\nThis feature will be implemented with PHPSpreadsheet.');
            // Add export logic here
        }
    </script>
</body>
</html>
