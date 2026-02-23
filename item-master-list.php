<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays
if (!isset($_SESSION['items'])) $_SESSION['items'] = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_item') {
        $_SESSION['items'][] = [
            'id' => count($_SESSION['items']) + 1,
            'item_code' => $_POST['item_code'] ?? '',
            'item_name' => $_POST['item_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category' => $_POST['category'] ?? '',
            'unit' => $_POST['unit'] ?? '',
            'unit_cost' => $_POST['unit_cost'] ?? 0,
            'quantity_on_hand' => $_POST['quantity_on_hand'] ?? 0,
            'reorder_level' => $_POST['reorder_level'] ?? 0,
            'supplier' => $_POST['supplier'] ?? '',
            'location' => $_POST['location'] ?? '',
            'status' => 'Active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $_SESSION['success_message'] = 'Item added successfully!';
        header('Location: item-master-list.php');
        exit;
    } elseif ($action === 'delete_item') {
        $id = $_POST['id'] ?? 0;
        foreach ($_SESSION['items'] as $key => $item) {
            if ($item['id'] == $id) {
                unset($_SESSION['items'][$key]);
                $_SESSION['items'] = array_values($_SESSION['items']);
                break;
            }
        }
        $_SESSION['success_message'] = 'Item deleted successfully!';
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
                    // Skip header row (first row)
                    for ($i = 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        
                        // Skip empty rows
                        if (empty($row[0]) && empty($row[1])) continue;
                        
                        // Columns: Item Code, Item Name, Description, Category, Unit, Unit Cost, Quantity, Reorder Level
                        $itemCode = $row[0] ?? '';
                        $itemName = $row[1] ?? '';
                        $description = $row[2] ?? '';
                        $category = $row[3] ?? '';
                        $unit = $row[4] ?? 'pcs';
                        $unitCost = $row[5] ?? 0;
                        $quantity = $row[6] ?? 0;
                        $reorderLevel = $row[7] ?? 0;
                        
                        if (!empty($itemName)) {
                            $_SESSION['items'][] = [
                                'id' => count($_SESSION['items']) + 1,
                                'item_code' => $itemCode,
                                'item_name' => $itemName,
                                'description' => $description,
                                'category' => $category,
                                'unit' => $unit,
                                'unit_cost' => $unitCost,
                                'quantity_on_hand' => $quantity,
                                'reorder_level' => $reorderLevel,
                                'supplier' => '',
                                'location' => '',
                                'status' => 'Active',
                                'created_at' => date('Y-m-d H:i:s')
                            ];
                            $imported++;
                        }
                    }
                    
                    $_SESSION['success_message'] = "Successfully imported $imported items from Excel file!";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Error reading Excel file: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = 'Invalid file format. Please upload .xlsx, .xls, or .csv file.';
            }
        } else {
            $_SESSION['error_message'] = 'Please select a file to upload.';
        }
        
        header('Location: item-master-list.php');
        exit;
    }
}

$user = $_SESSION['user'];
$items = $_SESSION['items'];

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
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-mini {
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-mini .number {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-mini .label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .search-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-filter input,
        .search-filter select {
            padding: 10px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
        }
        
        .search-filter input {
            flex: 1;
            min-width: 200px;
        }
        
        .stock-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .stock-ok {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-low {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-out {
            background: #f8d7da;
            color: #721c24;
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
                    <h2>Item Master List</h2>
                    <div>
                        <button class="btn-primary" onclick="showModal('itemModal')">+ Add Item</button>
                        <button class="btn-primary" onclick="showModal('uploadModal')" style="margin-left: 10px;">📤 Upload Excel</button>
                    </div>
                </div>
                
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
                
                <!-- Mini Stats -->
                <div class="stats-mini">
                    <div class="stat-mini">
                        <div class="number"><?php echo $totalItems; ?></div>
                        <div class="label">Total Items</div>
                    </div>
                    <div class="stat-mini">
                        <div class="number"><?php echo $lowStockItems; ?></div>
                        <div class="label">Low Stock</div>
                    </div>
                    <div class="stat-mini">
                        <div class="number">₱<?php echo number_format($totalValue, 2); ?></div>
                        <div class="label">Total Value</div>
                    </div>
                </div>
                
                <!-- Search and Filter -->
                <div class="search-filter">
                    <input type="text" id="searchItem" placeholder="🔍 Search items...">
                    <select id="filterCategory">
                        <option value="">All Categories</option>
                        <option value="Office Supplies">Office Supplies</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Equipment">Equipment</option>
                    </select>
                    <select id="filterStatus">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                
                <table>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <p>No items found. Click "Add Item" to get started.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php 
                            $rowNumber = 1;
                            foreach ($items as $item): 
                                $qty = floatval($item['quantity_on_hand'] ?? 0);
                                $unitCost = floatval($item['unit_cost'] ?? 0);
                                $totalCost = $qty * $unitCost;
                            ?>
                                <tr>
                                    <td><?php echo $rowNumber++; ?></td>
                                    <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?></td>
                                    <td><?php echo number_format($qty, 0); ?></td>
                                    <td>₱<?php echo number_format($unitCost, 2); ?></td>
                                    <td>₱<?php echo number_format($totalCost, 2); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_item">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Item Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close" onclick="closeModal('itemModal')">&times;</span>
            <h2>Add New Item</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_item">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Item Code *</label>
                        <input type="text" name="item_code" required>
                    </div>
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" name="item_name" required>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Description</label>
                        <textarea name="description" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <option value="Office Supplies">Office Supplies</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Consumables">Consumables</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit *</label>
                        <select name="unit" required>
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="box">Box</option>
                            <option value="set">Set</option>
                            <option value="ream">Ream</option>
                            <option value="pack">Pack</option>
                            <option value="unit">Unit</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit Cost *</label>
                        <input type="number" name="unit_cost" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity on Hand *</label>
                        <input type="number" name="quantity_on_hand" required>
                    </div>
                    <div class="form-group">
                        <label>Reorder Level</label>
                        <input type="number" name="reorder_level" value="10">
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <input type="text" name="supplier">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Storage Location</label>
                        <input type="text" name="location" placeholder="e.g., Warehouse A, Shelf 3">
                    </div>
                </div>
                <button type="submit" class="btn-primary">Add Item</button>
            </form>
        </div>
    </div>
    
    <!-- Upload Excel Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('uploadModal')">&times;</span>
            <h2>Upload Excel File</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                Upload an Excel file with columns:<br>
                <strong>Item Code, Item Name, Description, Category, Unit, Unit Cost, Quantity, Reorder Level</strong>
            </p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_excel">
                <div class="form-group">
                    <label>Select Excel File (.xlsx, .xls, .csv)</label>
                    <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                </div>
                <button type="submit" class="btn-primary">Upload & Import</button>
            </form>
        </div>
    </div>

    <script>
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
        
        // Search functionality
        document.getElementById('searchItem').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
