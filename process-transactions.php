<?php
ob_start();
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$conn = getDatabaseConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $transactionId = $_POST['transaction_id'];
    
    try {
        $conn->beginTransaction();
        
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE inventory_transactions SET status = 'Approved', processed_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'] ?? 1, $transactionId]);
            $_SESSION['success_message'] = 'Transaction approved successfully!';
            
        } elseif ($action === 'complete') {
            // Get transaction details
            $stmt = $conn->prepare("
                SELECT item_id, quantity, transaction_type 
                FROM inventory_transactions 
                WHERE id = ?
            ");
            $stmt->execute([$transactionId]);
            $trans = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($trans && $trans['transaction_type'] === 'Issue') {
                // Deduct from stock
                $stmt = $conn->prepare("
                    UPDATE items 
                    SET quantity_on_hand = quantity_on_hand - ?,
                        updated_at = NOW()
                    WHERE id = ? AND quantity_on_hand >= ?
                ");
                $result = $stmt->execute([$trans['quantity'], $trans['item_id'], $trans['quantity']]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('Insufficient stock to complete this transaction');
                }
                
                // Get new balance
                $stmt = $conn->prepare("SELECT quantity_on_hand FROM items WHERE id = ?");
                $stmt->execute([$trans['item_id']]);
                $newBalance = $stmt->fetchColumn();
                
                // Create stock movement
                $stmt = $conn->prepare("
                    INSERT INTO stock_movements 
                    (item_id, transaction_id, movement_type, quantity, balance_after, created_by)
                    VALUES (?, ?, 'OUT', ?, ?, ?)
                ");
                $stmt->execute([
                    $trans['item_id'],
                    $transactionId,
                    $trans['quantity'],
                    $newBalance,
                    $_SESSION['user_id'] ?? 1
                ]);
            }
            
            // Update transaction status
            $stmt = $conn->prepare("UPDATE inventory_transactions SET status = 'Completed', processed_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'] ?? 1, $transactionId]);
            
            $_SESSION['success_message'] = 'Transaction completed successfully!';
            
        } elseif ($action === 'cancel') {
            $stmt = $conn->prepare("UPDATE inventory_transactions SET status = 'Cancelled', processed_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'] ?? 1, $transactionId]);
            $_SESSION['success_message'] = 'Transaction cancelled';
        }
        
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Location: process-transactions.php');
    exit;
}

// Get all transactions with filtering
$statusFilter = $_GET['status'] ?? 'all';
$typeFilter = $_GET['type'] ?? 'all';

$query = "
    SELECT it.*, 
           o.office_name, 
           i.item_name, 
           i.item_code, 
           i.quantity_on_hand,
           u.full_name as processed_by_name
    FROM inventory_transactions it
    LEFT JOIN offices o ON it.office_id = o.id
    LEFT JOIN items i ON it.item_id = i.id
    LEFT JOIN users u ON it.processed_by = u.id
    WHERE 1=1
";

if ($statusFilter !== 'all') {
    $query .= " AND it.status = " . $conn->quote($statusFilter);
}

if ($typeFilter !== 'all') {
    $query .= " AND it.transaction_type = " . $conn->quote($typeFilter);
}

$query .= " ORDER BY 
    CASE it.status 
        WHEN 'Pending' THEN 1 
        WHEN 'Approved' THEN 2 
        WHEN 'Completed' THEN 3 
        ELSE 4 
    END,
    it.created_at DESC 
    LIMIT 100";

$transactions = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

$user = $_SESSION['user'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Transactions - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .transaction-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #ccc;
            transition: all 0.3s;
        }
        
        .transaction-card.pending {
            border-left-color: #ffc107;
        }
        
        .transaction-card.approved {
            border-left-color: #17a2b8;
        }
        
        .transaction-card.completed {
            border-left-color: #28a745;
        }
        
        .transaction-card.cancelled {
            border-left-color: #dc3545;
        }
        
        .transaction-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .transaction-ref {
            font-weight: 600;
            color: #333;
            font-size: 18px;
        }
        
        .transaction-type {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .type-issue {
            background: #fff3cd;
            color: #856404;
        }
        
        .type-receive {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .transaction-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 13px;
        }
        
        .btn-approve {
            background: #17a2b8;
            color: white;
        }
        
        .btn-approve:hover {
            background: #138496;
        }
        
        .btn-complete {
            background: #28a745;
            color: white;
        }
        
        .btn-complete:hover {
            background: #218838;
        }
        
        .btn-cancel {
            background: #dc3545;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #c82333;
        }
        
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-bar select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            min-width: 200px;
        }
        
        .stock-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 13px;
            color: #856404;
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
                <h1>⚙️ Process Transactions</h1>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Pending Approval</h3>
                    <div class="value" style="color: #ffc107;">
                        <?php echo count(array_filter($transactions, fn($t) => $t['status'] === 'Pending')); ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Approved</h3>
                    <div class="value" style="color: #17a2b8;">
                        <?php echo count(array_filter($transactions, fn($t) => $t['status'] === 'Approved')); ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Completed Today</h3>
                    <div class="value" style="color: #28a745;">
                        <?php echo count(array_filter($transactions, fn($t) => $t['status'] === 'Completed' && date('Y-m-d', strtotime($t['updated_at'])) === date('Y-m-d'))); ?>
                    </div>
                </div>
            </div>
            
            <div class="filter-bar">
                <label style="color: #666; font-weight: 500;">Filter by:</label>
                <select id="statusFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                
                <select id="typeFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="Issue" <?php echo $typeFilter === 'Issue' ? 'selected' : ''; ?>>Issue</option>
                    <option value="Receive" <?php echo $typeFilter === 'Receive' ? 'selected' : ''; ?>>Receive</option>
                </select>
            </div>
            
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <h3>No transactions found</h3>
                    <p>Try adjusting your filters or create a new transaction</p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $trans): 
                    $statusClass = strtolower($trans['status']);
                ?>
                    <div class="transaction-card <?php echo $statusClass; ?>">
                        <div class="transaction-header">
                            <span class="transaction-ref">
                                <?php echo $trans['transaction_type'] === 'Issue' ? '📤' : '📥'; ?> 
                                <?php echo htmlspecialchars($trans['reference_number']); ?>
                            </span>
                            <div style="display: flex; gap: 10px;">
                                <span class="transaction-type type-<?php echo strtolower($trans['transaction_type']); ?>">
                                    <?php echo htmlspecialchars($trans['transaction_type']); ?>
                                </span>
                                <span class="transaction-status status-<?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($trans['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="transaction-details">
                            <?php if ($trans['office_name']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Office/Department</span>
                                <span class="detail-value"><?php echo htmlspecialchars($trans['office_name']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <span class="detail-label">Item</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($trans['item_name']); ?> 
                                    (<?php echo htmlspecialchars($trans['item_code']); ?>)
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Quantity</span>
                                <span class="detail-value"><?php echo number_format($trans['quantity']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Total Cost</span>
                                <span class="detail-value" style="color: #28a745;">₱<?php echo number_format($trans['total_cost'], 2); ?></span>
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
                        
                        <?php if ($trans['transaction_type'] === 'Issue' && $trans['quantity_on_hand'] < $trans['quantity']): ?>
                            <div class="stock-warning">
                                ⚠️ Warning: Current stock (<?php echo $trans['quantity_on_hand']; ?>) is less than requested quantity (<?php echo $trans['quantity']; ?>)
                            </div>
                        <?php endif; ?>
                        
                        <div class="action-buttons">
                            <?php if ($trans['status'] === 'Pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="transaction_id" value="<?php echo $trans['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve btn-sm">✓ Approve</button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this transaction?');">
                                    <input type="hidden" name="transaction_id" value="<?php echo $trans['id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-cancel btn-sm">✗ Cancel</button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($trans['status'] === 'Approved'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Complete this transaction? This will update inventory levels.');">
                                    <input type="hidden" name="transaction_id" value="<?php echo $trans['id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn btn-complete btn-sm">✓ Complete</button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this transaction?');">
                                    <input type="hidden" name="transaction_id" value="<?php echo $trans['id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-cancel btn-sm">✗ Cancel</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const type = document.getElementById('typeFilter').value;
            window.location.href = `process-transactions.php?status=${status}&type=${type}`;
        }
    </script>
</body>
</html>
