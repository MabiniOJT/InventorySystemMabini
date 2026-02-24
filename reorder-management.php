<?php
ob_start();
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$conn = getDatabaseConnection();

// Get low stock items
$stmt = $conn->query("
    SELECT i.*, c.category_name, s.supplier_name,
           (i.reorder_level - i.quantity_on_hand) as shortage,
           (i.reorder_level * 2) as suggested_order_qty
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    WHERE i.status = 'Active' 
      AND i.quantity_on_hand <= i.reorder_level
    ORDER BY 
        CASE 
            WHEN i.quantity_on_hand = 0 THEN 1
            WHEN i.quantity_on_hand < i.reorder_level  THEN 2
            ELSE 3
        END,
        i.quantity_on_hand ASC
");
$lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get reorder statistics
$totalLowStock = count($lowStockItems);
$outOfStock = count(array_filter($lowStockItems, fn($item) => $item['quantity_on_hand'] == 0));
$criticalStock = count(array_filter($lowStockItems, fn($item) => $item['quantity_on_hand'] > 0 && $item['quantity_on_hand'] < ($item['reorder_level'] / 2)));

$user = $_SESSION['user'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reorder Management - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .reorder-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #ffc107;
            transition: all 0.3s;
        }
        
        .reorder-card.out-of-stock {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .reorder-card.critical {
            border-left-color: #ff6b6b;
        }
        
        .reorder-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .item-name {
            font-weight: 600;
            color: #333;
            font-size: 18px;
        }
        
        .priority-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .priority-critical {
            background: #dc3545;
            color: white;
        }
        
        .priority-urgent {
            background: #ff6b6b;
            color: white;
        }
        
        .priority-low {
            background: #ffc107;
            color: #333;
        }
        
        .item-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-weight: 500;
            color: #333;
            font-size: 15px;
        }
        
        .stock-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .stock-bar {
            flex: 1;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .stock-fill {
            height: 100%;
            background: linear-gradient(90deg, #dc3545, #ffc107);
            transition: width 0.3s;
        }
        
        .stock-fill.ok {
            background: #28a745;
        }
        
        .reorder-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-reorder {
            background: #4CAF50;
            color: white;
        }
        
        .btn-reorder:hover {
            background: #45a049;
        }
        
        .suggested-order {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 12px 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .supplier-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/navbar.php'; ?>
        
        <div class="content">
            <div class="content-header">
                <h1>🔄 Reorder Management</h1>
                <a href="receive-items.php" class="btn btn-primary">+ Record Purchase</a>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Out of Stock</h3>
                    <div class="value" style="color: #dc3545;"><?php echo $outOfStock; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Critical Stock</h3>
                    <div class="value" style="color: #ff6b6b;"><?php echo $criticalStock; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Low Stock Items</h3>
                    <div class="value" style="color: #ffc107;"><?php echo $totalLowStock; ?></div>
                </div>
            </div>
            
            <h2 style="margin: 30px 0 20px 0; color: #333;">Items Needing Reorder</h2>
            
            <?php if (empty($lowStockItems)): ?>
                <div class="empty-state">
                    <h3>✓ All items are well stocked!</h3>
                    <p>No items currently need reordering</p>
                </div>
            <?php else: ?>
                <?php foreach ($lowStockItems as $item): 
                    $currentStock = floatval($item['quantity_on_hand']);
                    $reorderLevel = floatval($item['reorder_level']);
                    $stockPercentage = $reorderLevel > 0 ? ($currentStock / $reorderLevel) * 100 : 0;
                    
                    if ($currentStock == 0) {
                        $priority = 'critical';
                        $priorityLabel = 'Out of Stock';
                        $cardClass = 'out-of-stock';
                    } elseif ($currentStock < ($reorderLevel / 2)) {
                        $priority = 'urgent';
                        $priorityLabel = 'Urgent';
                        $cardClass = 'critical';
                    } else {
                        $priority = 'low';
                        $priorityLabel = 'Low Stock';
                        $cardClass = '';
                    }
                ?>
                    <div class="reorder-card <?php echo $cardClass; ?>">
                        <div class="item-header">
                            <span class="item-name">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                                <span style="color: #999; font-size: 14px; font-weight: 400;">
                                    (<?php echo htmlspecialchars($item['item_code']); ?>)
                                </span>
                            </span>
                            <span class="priority-badge priority-<?php echo $priority; ?>">
                                <?php echo $priorityLabel; ?>
                            </span>
                        </div>
                        
                        <div class="item-details">
                            <div class="detail-item">
                                <span class="detail-label">Category</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Current Stock</span>
                                <span class="detail-value" style="color: <?php echo $currentStock == 0 ? '#dc3545' : '#ffc107'; ?>;">
                                    <?php echo number_format($currentStock); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Reorder Level</span>
                                <span class="detail-value"><?php echo number_format($reorderLevel); ?> <?php echo htmlspecialchars($item['unit']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Shortage</span>
                                <span class="detail-value" style="color: #dc3545;">
                                    <?php echo number_format($item['shortage']); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Unit Cost</span>
                                <span class="detail-value">₱<?php echo number_format($item['unit_cost'], 2); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Est. Reorder Cost</span>
                                <span class="detail-value" style="color: #28a745;">
                                    ₱<?php echo number_format($item['suggested_order_qty'] * $item['unit_cost'], 2); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="stock-indicator">
                            <span style="font-size: 12px; color: #666; min-width: 80px;">Stock Level:</span>
                            <div class="stock-bar">
                                <div class="stock-fill <?php echo $stockPercentage >= 100 ? 'ok' : ''; ?>" 
                                     style="width: <?php echo min($stockPercentage, 100); ?>%;"></div>
                            </div>
                            <span style="font-size: 13px; font-weight: 600; color: #333; min-width: 50px; text-align: right;">
                                <?php echo number_format($stockPercentage, 0); ?>%
                            </span>
                        </div>
                        
                        <div class="suggested-order">
                            <strong>💡 Suggested Order:</strong> 
                            <?php echo number_format($item['suggested_order_qty']); ?> <?php echo htmlspecialchars($item['unit']); ?>
                            (brings stock to <?php echo number_format($currentStock + $item['suggested_order_qty']); ?> <?php echo htmlspecialchars($item['unit']); ?>)
                        </div>
                        
                        <?php if ($item['supplier_name']): ?>
                            <div class="supplier-info">
                                <strong>📞 Supplier:</strong> <?php echo htmlspecialchars($item['supplier_name']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="reorder-actions" style="margin-top: 15px;">
                            <a href="receive-items.php" class="btn btn-reorder btn-sm">
                                Order <?php echo number_format($item['suggested_order_qty']); ?> <?php echo htmlspecialchars($item['unit']); ?>
                            </a>
                            <a href="item-master-list.php" class="btn btn-secondary btn-sm">
                                View in Master List
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
