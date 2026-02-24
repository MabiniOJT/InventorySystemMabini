<?php
ob_start(); // Start output buffering to prevent premature output
session_start();
require_once __DIR__ . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$conn = getDatabaseConnection();

// Handle AJAX request for item details
if (isset($_GET['action']) && $_GET['action'] === 'get_item_details' && isset($_GET['id'])) {
    // Clean output buffer to prevent HTML from being sent
    ob_clean();
    
    $itemId = intval($_GET['id']);
    
    try {
        // Fetch item details
        $stmt = $conn->prepare("
            SELECT i.*, c.category_name 
            FROM items i
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE i.id = ?
        ");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Item not found']);
            exit;
        }
        
        // Check if status column exists in inventory_transactions
        $checkStatus = $conn->query("SHOW COLUMNS FROM inventory_transactions LIKE 'status'");
        $hasStatusColumn = $checkStatus->rowCount() > 0;
        
        // Fetch agencies with recent orders
        if ($hasStatusColumn) {
            $stmt = $conn->prepare("
                SELECT DISTINCT o.office_name, it.transaction_date, it.quantity, it.status
                FROM inventory_transactions it
                JOIN offices o ON it.office_id = o.id
                WHERE it.item_id = ? AND it.transaction_type = 'Issue'
                ORDER BY it.transaction_date DESC
                LIMIT 5
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT DISTINCT o.office_name, it.transaction_date, it.quantity,
                       'Completed' as status
                FROM inventory_transactions it
                JOIN offices o ON it.office_id = o.id
                WHERE it.item_id = ? AND it.transaction_type = 'Issue'
                ORDER BY it.transaction_date DESC
                LIMIT 5
            ");
        }
        $stmt->execute([$itemId]);
        $agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['item' => $item, 'agencies' => $agencies], JSON_PRETTY_PRINT);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_item') {
        try {
            $stmt = $conn->prepare("
                INSERT INTO items (
                    item_code, item_name, category_id, unit, unit_cost,
                    quantity_on_hand, reorder_level, expiration_date, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)
            ");
            
            $expiration = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
            
            $stmt->execute([
                $_POST['item_code'] ?? '',
                $_POST['item_name'] ?? '',
                $_POST['category'] ?? null,
                $_POST['unit'] ?? 'piece',
                $_POST['unit_cost'] ?? 0,
                $_POST['quantity_on_hand'] ?? 0,
                $_POST['reorder_level'] ?? 10,
                $expiration,
                $_SESSION['user_id'] ?? null
            ]);
            
            $_SESSION['success_message'] = 'Item added successfully!';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error adding item: ' . $e->getMessage();
        }
        
        header('Location: item-master-list.php');
        exit;
        
    } elseif ($action === 'update_item') {
        try {
            $stmt = $conn->prepare("
                UPDATE items 
                SET item_code = ?, item_name = ?, category_id = ?, unit = ?, 
                    unit_cost = ?, quantity_on_hand = ?, reorder_level = ?, 
                    expiration_date = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $expiration = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
            
            $stmt->execute([
                $_POST['item_code'] ?? '',
                $_POST['item_name'] ?? '',
                $_POST['category'] ?? null,
                $_POST['unit'] ?? 'piece',
                $_POST['unit_cost'] ?? 0,
                $_POST['quantity_on_hand'] ?? 0,
                $_POST['reorder_level'] ?? 10,
                $expiration,
                $_POST['status'] ?? 'Active',
                $_POST['id'] ?? 0
            ]);
            
            $_SESSION['success_message'] = 'Item updated successfully!';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error updating item: ' . $e->getMessage();
        }
        
        header('Location: item-master-list.php');
        exit;
        
    } elseif ($action === 'delete_item') {
        try {
            $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$_POST['id'] ?? 0]);
            
            $_SESSION['success_message'] = 'Item deleted successfully!';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error deleting item: ' . $e->getMessage();
        }
        
        header('Location: item-master-list.php');
        exit;
        
    } elseif ($action === 'upload_excel') {
        require_once 'vendor/autoload.php';
        
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['excel_file']['tmp_name'];
            $fileExtension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
            
            if (in_array($fileExtension, ['xlsx', 'xls', 'csv'])) {
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();
                    
                    $imported = 0;
                    $stmt = $conn->prepare("
                        INSERT INTO items (
                            item_code, item_name, category_id, unit, unit_cost,
                            quantity_on_hand, reorder_level, status, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?)
                    ");
                    
                    // Skip header row
                    for ($i = 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        
                        if (empty($row[0]) && empty($row[1])) continue;
                        
                        $stmt->execute([
                            $row[0] ?? '',
                            $row[1] ?? '',
                            $row[3] ?? null,
                            $row[4] ?? 'piece',
                            $row[5] ?? 0,
                            $row[6] ?? 0,
                            $row[7] ?? 10,
                            $_SESSION['user_id'] ?? null
                        ]);
                        $imported++;
                    }
                    
                    $_SESSION['success_message'] = "Successfully imported $imported items!";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = 'Invalid file format.';
            }
        }
        
        header('Location: item-master-list.php');
        exit;
    }
}

// Fetch items from database with category names
$stmt = $conn->query("
    SELECT i.*, c.category_name 
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    ORDER BY i.item_code ASC
");
$items = $stmt->fetchAll();

// Function to get agencies with recent orders for an item
function getAgenciesWithOrders($conn, $item_id) {
    $stmt = $conn->prepare("
        SELECT DISTINCT o.office_name, it.transaction_date, it.quantity, it.status
        FROM inventory_transactions it
        JOIN offices o ON it.office_id = o.id
        WHERE it.item_id = ? AND it.transaction_type = 'Issue'
        ORDER BY it.transaction_date DESC
        LIMIT 5
    ");
    $stmt->execute([$item_id]);
    return $stmt->fetchAll();
}

// Fetch categories for dropdown
$categoriesStmt = $conn->query("SELECT id, category_name FROM categories WHERE status = 'Active' ORDER BY category_name");
$categories = $categoriesStmt->fetchAll();

// Calculate statistics
$totalItems = count($items);
$lowStockItems = 0;
$totalValue = 0;

foreach ($items as $item) {
    $qty = floatval($item['quantity_on_hand'] ?? 0);
    $reorder = floatval($item['reorder_level'] ?? 0);
    $cost = floatval($item['unit_cost'] ?? 0);
    
    if ($qty <= $reorder && $reorder > 0) {
        $lowStockItems++;
    }
    
    $totalValue += ($qty * $cost);
}

$user = $_SESSION['user'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Master List - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .content-header h1 {
            color: #333;
            font-size: 28px;
            margin: 0;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            color: #333;
            font-size: 28px;
            font-weight: 700;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-size: 14px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            color: white;
        }
        
        thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        tbody td {
            padding: 15px;
            color: #333;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-low {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            font-size: 12px;
            padding: 5px 10px;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            font-size: 12px;
            padding: 5px 10px;
            margin-right: 5px;
        }
        
        .btn-edit:hover {
            background: #e0a800;
        }
        
        .clickable-row {
            cursor: pointer;
        }
        
        .clickable-row:hover {
            background: #e3f2fd !important;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            color: #333;
            margin: 0;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.3s;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 12px;
            }
            
            thead th,
            tbody td {
                padding: 10px 5px;
            }
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
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="content-header">
                <h1>📦 Item Master List</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddModal()">+ Add New Item</button>
                    <button class="btn btn-secondary" onclick="openUploadModal()">📤 Import Excel</button>
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Items</h3>
                    <div class="value"><?php echo $totalItems; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Low Stock Items</h3>
                    <div class="value" style="color: #dc3545;"><?php echo $lowStockItems; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Inventory Value</h3>
                    <div class="value" style="color: #4CAF50;">₱<?php echo number_format($totalValue, 2); ?></div>
                </div>
            </div>
            
            <div class="filter-section">
                <div class="filter-group">
                    <label for="searchItem">Search Items</label>
                    <input type="text" id="searchItem" placeholder="Search by item code or name..." onkeyup="filterTable()">
                </div>
                <div class="filter-group">
                    <label for="filterCategory">Category</label>
                    <select id="filterCategory" onchange="filterTable()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterStatus">Status</label>
                    <select id="filterStatus" onchange="filterTable()">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Low Stock">Low Stock</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="table-container">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <h3>No items found</h3>
                        <p>Start by adding your first item or importing from Excel</p>
                        <br>
                        <a href="load_sample_data.php" class="btn btn-primary">Load Sample Data (67 items)</a>
                    </div>
                <?php else: ?>
                    <table id="itemsTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Stock</th>
                                <th>Unit Cost</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($items as $item): 
                                $qty = floatval($item['quantity_on_hand'] ?? 0);
                                $reorder = floatval($item['reorder_level'] ?? 0);
                                $cost = floatval($item['unit_cost'] ?? 0);
                                $totalCost = $qty * $cost;
                                $isLowStock = ($qty <= $reorder && $reorder > 0);
                                $displayStatus = $isLowStock ? 'Low Stock' : $item['status'];
                            ?>
                            <tr class="clickable-row" onclick="openDetailsModal(event, <?php echo $item['id']; ?>)" style="cursor: pointer;">
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td><?php echo number_format($qty, 0); ?></td>
                                <td>₱<?php echo number_format($cost, 2); ?></td>
                                <td>₱<?php echo number_format($totalCost, 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $isLowStock ? 'low' : strtolower($item['status']); ?>">
                                        <?php echo $displayStatus; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this item?');">
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Item Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Item</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_item">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="item_code">Item Code *</label>
                        <input type="text" id="item_code" name="item_code" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" id="item_name" name="item_name" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="unit">Unit *</label>
                        <select id="unit" name="unit" required>
                            <option value="piece">Piece</option>
                            <option value="box">Box</option>
                            <option value="pack">Pack</option>
                            <option value="bottle">Bottle</option>
                            <option value="ream">Ream</option>
                            <option value="roll">Roll</option>
                            <option value="can">Can</option>
                            <option value="pad">Pad</option>
                            <option value="book">Book</option>
                            <option value="unit">Unit</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="unit_cost">Unit Cost *</label>
                        <input type="number" id="unit_cost" name="unit_cost" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity_on_hand">Quantity on Hand *</label>
                        <input type="number" id="quantity_on_hand" name="quantity_on_hand" required>
                    </div>
                    <div class="form-group">
                        <label for="reorder_level">Reorder Level *</label>
                        <input type="number" id="reorder_level" name="reorder_level" value="10" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="expiration_date">Expiration Date (optional - for medical supplies)</label>
                    <input type="date" id="expiration_date" name="expiration_date">
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Import from Excel</h2>
                <span class="close" onclick="closeUploadModal()">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_excel">
                
                <div class="form-group">
                    <label for="excel_file">Select Excel File (.xlsx, .xls, .csv)</label>
                    <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                </div>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>Excel Format:</strong><br>
                    <small>Columns: Item Code, Item Name, Description, Category ID, Unit, Unit Cost, Quantity, Reorder Level</small>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Item Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Item</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_item">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_item_code">Item Code *</label>
                        <input type="text" id="edit_item_code" name="item_code" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_category">Category *</label>
                        <select id="edit_category" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_item_name">Item Name *</label>
                    <input type="text" id="edit_item_name" name="item_name" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_unit">Unit *</label>
                        <select id="edit_unit" name="unit" required>
                            <option value="piece">Piece</option>
                            <option value="box">Box</option>
                            <option value="pack">Pack</option>
                            <option value="bottle">Bottle</option>
                            <option value="ream">Ream</option>
                            <option value="roll">Roll</option>
                            <option value="can">Can</option>
                            <option value="pad">Pad</option>
                            <option value="book">Book</option>
                            <option value="unit">Unit</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_unit_cost">Unit Cost *</label>
                        <input type="number" id="edit_unit_cost" name="unit_cost" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_quantity_on_hand">Quantity on Hand *</label>
                        <input type="number" id="edit_quantity_on_hand" name="quantity_on_hand" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_reorder_level">Reorder Level *</label>
                        <input type="number" id="edit_reorder_level" name="reorder_level" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_expiration_date">Expiration Date (optional)</label>
                        <input type="date" id="edit_expiration_date" name="expiration_date">
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status *</label>
                        <select id="edit_status" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Item Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Item Details</h2>
                <span class="close" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div id="detailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'block';
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }
        
        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_item_code').value = item.item_code;
            document.getElementById('edit_item_name').value = item.item_name;
            document.getElementById('edit_category').value = item.category_id || '';
            document.getElementById('edit_unit').value = item.unit;
            document.getElementById('edit_unit_cost').value = item.unit_cost;
            document.getElementById('edit_quantity_on_hand').value = item.quantity_on_hand;
            document.getElementById('edit_reorder_level').value = item.reorder_level;
            document.getElementById('edit_expiration_date').value = item.expiration_date || '';
            document.getElementById('edit_status').value = item.status;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function openDetailsModal(event, itemId) {
            // Prevent the row click from triggering during button clicks
            if (event.target.tagName === 'BUTTON' || event.target.tagName === 'INPUT' || event.target.closest('form')) {
                return;
            }
            
            document.getElementById('detailsModal').style.display = 'block';
            document.getElementById('detailsContent').innerHTML = '<p style="text-align:center;padding:20px;">Loading...</p>';
            
            // Fetch item details via AJAX
            fetch('item-master-list.php?action=get_item_details&id=' + itemId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text(); // Get as text first to debug
                })
                .then(text => {
                    try {
                        return JSON.parse(text); // Then parse as JSON
                    } catch (e) {
                        console.error('Response text:', text); // Log the actual response
                        throw new Error('Invalid JSON response from server');
                    }
                })
                .then(data => {
                    // Check if there's an error in the response
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    let html = `
                        <div style="padding: 0 10px;">
                            <h3 style="color: #333; margin-bottom: 20px;">${data.item.item_name}</h3>
                            
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
                                <div>
                                    <p><strong>Item Code:</strong> ${data.item.item_code}</p>
                                    <p><strong>Category:</strong> ${data.item.category_name || 'Uncategorized'}</p>
                                    <p><strong>Unit:</strong> ${data.item.unit}</p>
                                    <p><strong>Unit Cost:</strong> ₱${parseFloat(data.item.unit_cost).toFixed(2)}</p>
                                </div>
                                <div>
                                    <p><strong>Quantity on Hand:</strong> ${data.item.quantity_on_hand}</p>
                                    <p><strong>Reorder Level:</strong> ${data.item.reorder_level}</p>
                                    <p><strong>Total Value:</strong> ₱${(data.item.quantity_on_hand * data.item.unit_cost).toFixed(2)}</p>
                                    <p><strong>Status:</strong> <span class="status-badge status-${data.item.status.toLowerCase()}">${data.item.status}</span></p>
                                </div>
                            </div>
                    `;
                    
                    if (data.item.expiration_date) {
                        const expDate = new Date(data.item.expiration_date);
                        const today = new Date();
                        const daysUntilExpiry = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));
                        let expiryClass = 'status-active';
                        let expiryText = '';
                        
                        if (daysUntilExpiry < 0) {
                            expiryClass = 'status-inactive';
                            expiryText = ' (EXPIRED)';
                        } else if (daysUntilExpiry <= 30) {
                            expiryClass = 'status-low';
                            expiryText = ` (${daysUntilExpiry} days remaining)`;
                        }
                        
                        html += `
                            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                                <strong>⚠️ Expiration Date:</strong> 
                                <span class="status-badge ${expiryClass}">${data.item.expiration_date}${expiryText}</span>
                            </div>
                        `;
                    }
                    
                    if (data.agencies && data.agencies.length > 0) {
                        html += `
                            <h4 style="color: #666; margin-bottom: 15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                                📋 Recent Orders/Issues
                            </h4>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead style="background: #f8f9fa;">
                                        <tr>
                                            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Agency/Office</th>
                                            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Date</th>
                                            <th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Quantity</th>
                                            <th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        data.agencies.forEach(agency => {
                            html += `
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #f0f0f0;">${agency.office_name}</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #f0f0f0;">${agency.transaction_date}</td>
                                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #f0f0f0;">${agency.quantity}</td>
                                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #f0f0f0;">
                                        <span class="status-badge status-${agency.status.toLowerCase()}">${agency.status}</span>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        html += `
                            <div style="text-align: center; padding: 30px; color: #999;">
                                <p>No recent orders or issues for this item.</p>
                            </div>
                        `;
                    }
                    
                    html += '</div>';
                    document.getElementById('detailsContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('detailsContent').innerHTML = '<p style="text-align:center;padding:20px;color:#dc3545;">Error loading item details: ' + error.message + '</p>';
                });
        }
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        function filterTable() {
            const searchValue = document.getElementById('searchItem').value.toLowerCase();
            const categoryValue = document.getElementById('filterCategory').value.toLowerCase();
            const statusValue = document.getElementById('filterStatus').value.toLowerCase();
            
            const table = document.getElementById('itemsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let row of rows) {
                const itemCode = row.cells[1].textContent.toLowerCase();
                const itemName = row.cells[2].textContent.toLowerCase();
                const category = row.cells[3].textContent.toLowerCase();
                const status = row.cells[8].textContent.trim().toLowerCase();
                
                const matchesSearch = itemCode.includes(searchValue) || itemName.includes(searchValue);
                const matchesCategory = categoryValue === '' || category.includes(categoryValue);
                const matchesStatus = statusValue === '' || status.includes(statusValue);
                
                if (matchesSearch && matchesCategory && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
