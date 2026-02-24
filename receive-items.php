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
    
    if ($action === 'receive_items') {
        try {
            $conn->beginTransaction();
            
            $itemId = $_POST['item_id'];
            $quantity = intval($_POST['quantity']);
            $unitCost = floatval($_POST['unit_cost']);
            $supplierId = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
            $remarks = $_POST['remarks'] ?? '';
            
            // Get item details
            $stmt = $conn->prepare("SELECT item_code, item_name, quantity_on_hand FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception('Item not found');
            }
            
            // Generate reference number
            $refNumber = 'RCV-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            
            // Create transaction
            $stmt = $conn->prepare("
                INSERT INTO inventory_transactions 
                (transaction_type, transaction_date, reference_number, item_id, 
                 quantity, unit_cost, total_cost, remarks, created_by, processed_by, status)
                VALUES ('Receive', CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, 'Completed')
            ");
            
            $userId = $_SESSION['user_id'] ?? 1;
            $totalCost = $quantity * $unitCost;
            $stmt->execute([
                $refNumber,
                $itemId,
                $quantity,
                $unitCost,
                $totalCost,
                $remarks,
                $userId,
                $userId
            ]);
            
            // Update item quantity and cost
            $newQuantity = $item['quantity_on_hand'] + $quantity;
            $stmt = $conn->prepare("
                UPDATE items 
                SET quantity_on_hand = ?, 
                    unit_cost = ?,
                    supplier_id = COALESCE(?, supplier_id),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$newQuantity, $unitCost, $supplierId, $itemId]);
            
            // Create stock movement record
            $stmt = $conn->prepare("
                INSERT INTO stock_movements 
                (item_id, transaction_id, movement_type, quantity, balance_after, reference, remarks, created_by)
                VALUES (?, LAST_INSERT_ID(), 'IN', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $itemId,
                $quantity,
                $newQuantity,
                $refNumber,
                $remarks,
                $_SESSION['user_id'] ?? 1
            ]);
            
            $_SESSION['success_message'] = "Items received successfully! Reference: $refNumber";
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        }
        
        header('Location: receive-items.php');
        exit;
    }
}

// Get recent receipts
$stmt = $conn->query("
    SELECT it.*, i.item_name, i.item_code, u.full_name as processed_by_name
    FROM inventory_transactions it
    LEFT JOIN items i ON it.item_id = i.id
    LEFT JOIN users u ON it.processed_by = u.id
    WHERE it.transaction_type = 'Receive'
    ORDER BY it.created_at DESC
    LIMIT 50
");
$receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get items for dropdown
$items = $conn->query("SELECT id, item_code, item_name, unit_cost, unit FROM items WHERE status = 'Active' ORDER BY item_name")->fetchAll();

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT id, supplier_name FROM suppliers WHERE status = 'Active' ORDER BY supplier_name")->fetchAll();

$user = $_SESSION['user'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Items - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .transaction-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
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
        
        .cost-highlight {
            color: #28a745;
            font-weight: 600;
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
                <h1>📥 Receive Items</h1>
                <button class="btn btn-primary" onclick="openReceiveModal()">+ Record Receipt</button>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Received Today</h3>
                    <div class="value" style="color: #28a745;">
                        <?php echo count(array_filter($receipts, fn($r) => $r['transaction_date'] === date('Y-m-d'))); ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>This Month</h3>
                    <div class="value">
                        <?php echo count(array_filter($receipts, fn($r) => date('Y-m', strtotime($r['transaction_date'])) === date('Y-m'))); ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Value Received</h3>
                    <div class="value" style="color: #4CAF50;">
                        ₱<?php echo number_format(array_sum(array_column($receipts, 'total_cost')), 2); ?>
                    </div>
                </div>
            </div>
            
            <h2 style="margin: 30px 0 20px 0; color: #333;">Recent Receipts</h2>
            
            <?php if (empty($receipts)): ?>
                <div class="empty-state">
                    <h3>No receipts yet</h3>
                    <p>Record your first item receipt to start tracking inventory</p>
                </div>
            <?php else: ?>
                <?php foreach ($receipts as $receipt): ?>
                    <div class="transaction-card">
                        <div class="transaction-header">
                            <span class="transaction-ref">📦 <?php echo htmlspecialchars($receipt['reference_number']); ?></span>
                            <span class="cost-highlight">₱<?php echo number_format($receipt['total_cost'], 2); ?></span>
                        </div>
                        <div class="transaction-details">
                            <div class="detail-item">
                                <span class="detail-label">Item</span>
                                <span class="detail-value"><?php echo htmlspecialchars($receipt['item_name']); ?> (<?php echo htmlspecialchars($receipt['item_code']); ?>)</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Quantity Received</span>
                                <span class="detail-value"><?php echo number_format($receipt['quantity']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Unit Cost</span>
                                <span class="detail-value">₱<?php echo number_format($receipt['unit_cost'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($receipt['transaction_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Processed By</span>
                                <span class="detail-value"><?php echo htmlspecialchars($receipt['processed_by_name'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if (!empty($receipt['remarks'])): ?>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <span class="detail-label">Remarks</span>
                                <span class="detail-value"><?php echo htmlspecialchars($receipt['remarks']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Receive Modal -->
    <div id="receiveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Record Item Receipt</h2>
                <span class="close" onclick="closeReceiveModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="receive_items">
                
                <div class="form-group">
                    <label for="item_id">Item *</label>
                    <select id="item_id" name="item_id" required onchange="updateCost()">
                        <option value="">Select Item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>" 
                                    data-cost="<?php echo $item['unit_cost']; ?>"
                                    data-unit="<?php echo htmlspecialchars($item['unit']); ?>">
                                <?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['item_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">Quantity Received *</label>
                        <input type="number" id="quantity" name="quantity" min="1" required onchange="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="unit_cost">Unit Cost *</label>
                        <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" required onchange="calculateTotal()">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="total_display">Total Cost</label>
                    <input type="text" id="total_display" readonly style="background: #f9fafb; font-weight: 600; color: #28a745;">
                </div>
                
                <div class="form-group">
                    <label for="supplier_id">Supplier (optional)</label>
                    <select id="supplier_id" name="supplier_id">
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>">
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" placeholder="Purchase order number, delivery notes, etc."></textarea>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeReceiveModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Receipt</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openReceiveModal() {
            document.getElementById('receiveModal').style.display = 'block';
        }
        
        function closeReceiveModal() {
            document.getElementById('receiveModal').style.display = 'none';
        }
        
        function updateCost() {
            const select = document.getElementById('item_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const cost = parseFloat(option.dataset.cost);
                document.getElementById('unit_cost').value = cost.toFixed(2);
                calculateTotal();
            }
        }
        
        function calculateTotal() {
            const quantity = parseFloat(document.getElementById('quantity').value) || 0;
            const unitCost = parseFloat(document.getElementById('unit_cost').value) || 0;
            const total = quantity * unitCost;
            
            document.getElementById('total_display').value = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
