<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$conn = getDatabaseConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_item') {
        try {
            $stmt = $conn->prepare("
                INSERT INTO items (
                    item_code, item_name, category_id, unit, unit_cost,
                    quantity_on_hand, reorder_level, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?)
            ");
            
            $stmt->execute([
                $_POST['item_code'] ?? '',
                $_POST['item_name'] ?? '',
                $_POST['category'] ?? null,
                $_POST['unit'] ?? 'piece',
                $_POST['unit_cost'] ?? 0,
                $_POST['quantity_on_hand'] ?? 0,
                $_POST['reorder_level'] ?? 10,
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
                    status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['item_code'] ?? '',
                $_POST['item_name'] ?? '',
                $_POST['category'] ?? null,
                $_POST['unit'] ?? 'piece',
                $_POST['unit_cost'] ?? 0,
                $_POST['quantity_on_hand'] ?? 0,
                $_POST['reorder_level'] ?? 10,
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
                            <tr>
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
