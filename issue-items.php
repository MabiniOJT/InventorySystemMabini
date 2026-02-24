<?php
ob_start();
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$conn = getDatabaseConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_issue') {
        try {
            $conn->beginTransaction();
            
            $itemId = $_POST['item_id'];
            $officeId = $_POST['office_id'];
            $quantity = intval($_POST['quantity']);
            $remarks = $_POST['remarks'] ?? '';
            
            // Get item details and check stock
            $stmt = $conn->prepare("SELECT item_code, item_name, quantity_on_hand, unit_cost FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception('Item not found');
            }
            
            if ($item['quantity_on_hand'] < $quantity) {
                throw new Exception('Insufficient stock. Available: ' . $item['quantity_on_hand']);
            }
            
            // Generate reference number
            $refNumber = 'ISS-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            
            // Create transaction
            $stmt = $conn->prepare("
                INSERT INTO inventory_transactions 
                (transaction_type, transaction_date, reference_number, office_id, item_id, 
                 quantity, unit_cost, total_cost, remarks, created_by, status)
                VALUES ('Issue', CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            
            $totalCost = $quantity * $item['unit_cost'];
            $stmt->execute([
                $refNumber,
                $officeId,
                $itemId,
                $quantity,
                $item['unit_cost'],
                $totalCost,
                $remarks,
                $_SESSION['user_id'] ?? 1
            ]);
            
            $_SESSION['success_message'] = "Issue request created successfully! Reference: $refNumber";
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        }
        
        header('Location: issue-items.php');
        exit;
    }
}

// Get recent transactions
$stmt = $conn->query("
    SELECT it.*, o.office_name, i.item_name, i.item_code, u.full_name as processed_by_name
    FROM inventory_transactions it
    LEFT JOIN offices o ON it.office_id = o.id
    LEFT JOIN items i ON it.item_id = i.id
    LEFT JOIN users u ON it.processed_by = u.id
    WHERE it.transaction_type = 'Issue'
    ORDER BY it.created_at DESC
    LIMIT 50
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get offices for dropdown
$offices = $conn->query("SELECT id, office_name FROM offices WHERE status = 'Active' ORDER BY office_name")->fetchAll();

// Get items for dropdown
$items = $conn->query("SELECT id, item_code, item_name, quantity_on_hand, unit FROM items WHERE status = 'Active' ORDER BY item_name")->fetchAll();

$user = $_SESSION['user'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Items - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .transaction-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #4CAF50;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .transaction-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .transaction-ref {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        
        .transaction-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .transaction-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            color: #666;
            font-size: 14px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 3px;
        }
        
        .detail-value {
            font-weight: 500;
            color: #333;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .item-selector {
            position: relative;
        }
        
        .stock-indicator {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .stock-low {
            color: #dc3545;
        }
        
        .stock-ok {
            color: #28a745;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/navbar.php'; ?>
        
        <div class="content">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="content-header">
                <h1>📤 Issue Items to Offices</h1>
                <button class="btn btn-primary" onclick="openIssueModal()">+ Create Issue Request</button>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Pending Issues</h3>
                    <div class="value" style="color: #ffc107;">
                        <?php echo count(array_filter($transactions, fn($t) => $t['status'] === 'Pending')); ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Completed Today</h3>
                    <div class="value" style="color: #28a745;">
                        <?php echo count(array_filter($transactions, fn($t) => $t['status'] === 'Completed' && $t['transaction_date'] === date('Y-m-d'))); ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Transactions</h3>
                    <div class="value"><?php echo count($transactions); ?></div>
                </div>
            </div>
            
            <h2 style="margin: 30px 0 20px 0; color: #333;">Recent Issue Transactions</h2>
            
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <h3>No transactions yet</h3>
                    <p>Create your first issue request to start tracking item distribution</p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $trans): ?>
                    <div class="transaction-card">
                        <div class="transaction-header">
                            <span class="transaction-ref">📋 <?php echo htmlspecialchars($trans['reference_number']); ?></span>
                            <span class="transaction-status status-<?php echo strtolower($trans['status']); ?>">
                                <?php echo htmlspecialchars($trans['status']); ?>
                            </span>
                        </div>
                        <div class="transaction-details">
                            <div class="detail-item">
                                <span class="detail-label">Office</span>
                                <span class="detail-value"><?php echo htmlspecialchars($trans['office_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Item</span>
                                <span class="detail-value"><?php echo htmlspecialchars($trans['item_name']); ?> (<?php echo htmlspecialchars($trans['item_code']); ?>)</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Quantity</span>
                                <span class="detail-value"><?php echo number_format($trans['quantity']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($trans['transaction_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Processed By</span>
                                <span class="detail-value"><?php echo htmlspecialchars($trans['processed_by_name'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if (!empty($trans['remarks'])): ?>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <span class="detail-label">Remarks</span>
                                <span class="detail-value"><?php echo htmlspecialchars($trans['remarks']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Create Issue Modal -->
    <div id="issueModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Issue Request</h2>
                <span class="close" onclick="closeIssueModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_issue">
                
                <div class="form-group">
                    <label for="office_id">Office/Department *</label>
                    <select id="office_id" name="office_id" required>
                        <option value="">Select Office</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>">
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group item-selector">
                    <label for="item_id">Item *</label>
                    <select id="item_id" name="item_id" required onchange="updateStock()">
                        <option value="">Select Item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>" 
                                    data-stock="<?php echo $item['quantity_on_hand']; ?>"
                                    data-unit="<?php echo htmlspecialchars($item['unit']); ?>">
                                <?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['item_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="stockIndicator" class="stock-indicator"></div>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" placeholder="Purpose or additional notes"></textarea>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeIssueModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Issue Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openIssueModal() {
            document.getElementById('issueModal').style.display = 'block';
        }
        
        function closeIssueModal() {
            document.getElementById('issueModal').style.display = 'none';
        }
        
        function updateStock() {
            const select = document.getElementById('item_id');
            const option = select.options[select.selectedIndex];
            const indicator = document.getElementById('stockIndicator');
            
            if (option.value) {
                const stock = parseInt(option.dataset.stock);
                const unit = option.dataset.unit;
                
                if (stock > 10) {
                    indicator.className = 'stock-indicator stock-ok';
                    indicator.textContent = `✓ Available stock: ${stock} ${unit}`;
                } else if (stock > 0) {
                    indicator.className = 'stock-indicator stock-low';
                    indicator.textContent = `⚠ Low stock: ${stock} ${unit} remaining`;
                } else {
                    indicator.className = 'stock-indicator stock-low';
                    indicator.textContent = '✗ Out of stock';
                }
            } else {
                indicator.textContent = '';
            }
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
