<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays
if (!isset($_SESSION['items'])) $_SESSION['items'] = [];

// Load sample data if requested
if (isset($_GET['load_sample_data']) && empty($_SESSION['items'])) {
    $sampleData = [
        ['item_code' => 'ITEM-003', 'qty' => 55, 'unit' => 'bottle', 'description' => 'Alcohol, Isopropyl 70%, 500 ml', 'unit_price' => 114.00],
        ['item_code' => 'ITEM-004', 'qty' => 119, 'unit' => 'piece', 'description' => 'Ballpen (Black) 0.7 TIP', 'unit_price' => 7.50],
        ['item_code' => 'ITEM-005', 'qty' => 6, 'unit' => 'piece', 'description' => 'Ballpen (Blue) 0.7 TIP', 'unit_price' => 7.50],
        ['item_code' => 'ITEM-006', 'qty' => 174, 'unit' => 'piece', 'description' => 'Ballpen Black 0.5', 'unit_price' => 7.50],
        ['item_code' => 'ITEM-007', 'qty' => 30, 'unit' => 'piece', 'description' => 'Ballpen Blue 0.5', 'unit_price' => 7.50],
        ['item_code' => 'ITEM-008', 'qty' => 10, 'unit' => 'piece', 'description' => 'Ballpen Red 0.5', 'unit_price' => 7.50],
        ['item_code' => 'ITEM-009', 'qty' => 19, 'unit' => 'pack', 'description' => 'Battery, Dry Cell size AA, 4/pack', 'unit_price' => 94.00],
        ['item_code' => 'ITEM-010', 'qty' => 7, 'unit' => 'pack', 'description' => 'Battery, Dry Cell size AAA 4/pack', 'unit_price' => 128.00],
        ['item_code' => 'ITEM-011', 'qty' => 32, 'unit' => 'box', 'description' => 'Binder Clip Fold Back Clip 25mm', 'unit_price' => 26.00],
        ['item_code' => 'ITEM-013', 'qty' => 9, 'unit' => 'piece', 'description' => 'Broom, Soft (Walis Tambo)', 'unit_price' => 148.00],
        ['item_code' => 'ITEM-014', 'qty' => 4, 'unit' => 'piece', 'description' => 'Broom, (Walis Tingting)', 'unit_price' => 56.00],
        ['item_code' => 'ITEM-015', 'qty' => 55, 'unit' => 'piece', 'description' => 'Brown Envelope Short', 'unit_price' => 3.50],
        ['item_code' => 'ITEM-016', 'qty' => 190, 'unit' => 'piece', 'description' => 'Brown Envelope Legal', 'unit_price' => 4.50],
        ['item_code' => 'ITEM-017', 'qty' => 100, 'unit' => 'piece', 'description' => 'Brown Envelope A4', 'unit_price' => 3.00],
        ['item_code' => 'ITEM-018', 'qty' => 4, 'unit' => 'unit', 'description' => 'Calculator 2-way power 12 digits', 'unit_price' => 568.00],
        ['item_code' => 'ITEM-019', 'qty' => 1, 'unit' => 'pack', 'description' => 'Carbon Paper Blue legal, 100/pack', 'unit_price' => 934.00],
        ['item_code' => 'ITEM-021', 'qty' => 15, 'unit' => 'bottle', 'description' => 'Dishwashing Liquid 475ml', 'unit_price' => 178.00],
        ['item_code' => 'ITEM-023', 'qty' => 4, 'unit' => 'roll', 'description' => 'Double Sided Tape 24mmx10m', 'unit_price' => 40.00],
        ['item_code' => 'ITEM-024', 'qty' => 1, 'unit' => 'piece', 'description' => 'Dustpan, plastic', 'unit_price' => 93.00],
        ['item_code' => 'ITEM-025', 'qty' => 1, 'unit' => 'box', 'description' => 'Envelope Business (white short)', 'unit_price' => 226.00],
        ['item_code' => 'ITEM-026', 'qty' => 3, 'unit' => 'box', 'description' => 'Envelope Business (white long)', 'unit_price' => 369.00],
        ['item_code' => 'ITEM-027', 'qty' => 50, 'unit' => 'piece', 'description' => 'Expandable Brown Envelope Legal', 'unit_price' => 24.00],
        ['item_code' => 'ITEM-028', 'qty' => 30, 'unit' => 'piece', 'description' => 'Expandable Plastic Envelope w/ Handle (Long)', 'unit_price' => 87.00],
        ['item_code' => 'ITEM-029', 'qty' => 400, 'unit' => 'piece', 'description' => 'Folder A4 White 14 pts', 'unit_price' => 7.00],
        ['item_code' => 'ITEM-030', 'qty' => 400, 'unit' => 'piece', 'description' => 'Folder Legal, white 14pts', 'unit_price' => 7.00],
        ['item_code' => 'ITEM-031', 'qty' => 200, 'unit' => 'piece', 'description' => 'Folder Letter, white 14pts', 'unit_price' => 6.00],
        ['item_code' => 'ITEM-032', 'qty' => 300, 'unit' => 'piece', 'description' => 'Folder long Brown (thick)', 'unit_price' => 7.00],
        ['item_code' => 'ITEM-033', 'qty' => 26, 'unit' => 'pack', 'description' => 'Garbage Bag XXL Black', 'unit_price' => 56.00],
        ['item_code' => 'ITEM-034', 'qty' => 16, 'unit' => 'pack', 'description' => 'Glossy Photo Sticker A4 130gsm 5 sheets/pack', 'unit_price' => 43.00],
        ['item_code' => 'ITEM-036', 'qty' => 5, 'unit' => 'bottle', 'description' => 'Glue, multipurpose 130 grams', 'unit_price' => 60.00],
        ['item_code' => 'ITEM-037', 'qty' => 1, 'unit' => 'bottle', 'description' => 'Ink, for Stamp pad, 30ml purple', 'unit_price' => 18.00],
        ['item_code' => 'ITEM-039', 'qty' => 30, 'unit' => 'piece', 'description' => 'Lead Pencil medium size', 'unit_price' => 8.00],
        ['item_code' => 'ITEM-040', 'qty' => 9, 'unit' => 'piece', 'description' => 'Marker White Board black', 'unit_price' => 27.00],
        ['item_code' => 'ITEM-041', 'qty' => 6, 'unit' => 'piece', 'description' => 'Marker, permanent black, broad', 'unit_price' => 41.00],
        ['item_code' => 'ITEM-042', 'qty' => 8, 'unit' => 'piece', 'description' => 'Marker, permanent black, fine', 'unit_price' => 41.00],
        ['item_code' => 'ITEM-043', 'qty' => 4, 'unit' => 'piece', 'description' => 'Mophead, Rayon 500g', 'unit_price' => 243.00],
        ['item_code' => 'ITEM-044', 'qty' => 10, 'unit' => 'can', 'description' => 'Multi Insect Killer Odorless 500ml', 'unit_price' => 518.00],
        ['item_code' => 'ITEM-045', 'qty' => 8, 'unit' => 'roll', 'description' => 'Packaging Tape (Brown) 48mmx50mm', 'unit_price' => 50.00],
        ['item_code' => 'ITEM-046', 'qty' => 11, 'unit' => 'box', 'description' => 'Paper fastener plastic', 'unit_price' => 42.00],
        ['item_code' => 'ITEM-051', 'qty' => 57, 'unit' => 'ream', 'description' => 'Paper, Multipurpose, A4 70gsm', 'unit_price' => 210.00],
        ['item_code' => 'ITEM-052', 'qty' => 65, 'unit' => 'ream', 'description' => 'Paper, Multipurpose, Legal 70gsm', 'unit_price' => 232.00],
        ['item_code' => 'ITEM-053', 'qty' => 11, 'unit' => 'ream', 'description' => 'Paper, Multipurpose, Letter 70gsm', 'unit_price' => 199.00],
        ['item_code' => 'ITEM-054', 'qty' => 6, 'unit' => 'piece', 'description' => 'Pastel Highlighter, Creamy Peach', 'unit_price' => 48.00],
        ['item_code' => 'ITEM-055', 'qty' => 7, 'unit' => 'piece', 'description' => 'Pastel Highlighter, Milky Yellow', 'unit_price' => 48.00],
        ['item_code' => 'ITEM-056', 'qty' => 4, 'unit' => 'piece', 'description' => 'Pastel Highlighter, Cloudy Blue', 'unit_price' => 48.00],
        ['item_code' => 'ITEM-057', 'qty' => 2, 'unit' => 'piece', 'description' => 'Pastel Highlighter, Pink Blush', 'unit_price' => 104.00],
        ['item_code' => 'ITEM-058', 'qty' => 4, 'unit' => 'pack', 'description' => 'Photo Paper A4 (210x297mm) 180gsm 20 Sheets', 'unit_price' => 36.00],
        ['item_code' => 'ITEM-059', 'qty' => 3, 'unit' => 'box', 'description' => 'Push pin, colored 100s/box', 'unit_price' => 92.00],
        ['item_code' => 'ITEM-060', 'qty' => 20, 'unit' => 'book', 'description' => 'Record book 170mm x 280mm 300 Pages', 'unit_price' => 123.00],
        ['item_code' => 'ITEM-061', 'qty' => 8, 'unit' => 'book', 'description' => 'Record Book 500 pages 177mm x 280mm', 'unit_price' => 232.00],
        ['item_code' => 'ITEM-062', 'qty' => 8, 'unit' => 'box', 'description' => 'Rubber band, 225 grams flat', 'unit_price' => 109.00],
        ['item_code' => 'ITEM-063', 'qty' => 5, 'unit' => 'piece', 'description' => 'Sign Pen 0.5 Black', 'unit_price' => 109.00],
        ['item_code' => 'ITEM-064', 'qty' => 114, 'unit' => 'piece', 'description' => 'Sign Pen 0.5 Blue', 'unit_price' => 34.00],
        ['item_code' => 'ITEM-065', 'qty' => 7, 'unit' => 'piece', 'description' => 'Stamp Pad (small)', 'unit_price' => 555.00],
        ['item_code' => 'ITEM-067', 'qty' => 2, 'unit' => 'box', 'description' => 'Stapler Heavy Duty with remover', 'unit_price' => 12.00],
        ['item_code' => 'ITEM-068', 'qty' => 4, 'unit' => 'box', 'description' => 'Staple Wire #10-1M', 'unit_price' => 67.00],
        ['item_code' => 'ITEM-069', 'qty' => 19, 'unit' => 'box', 'description' => 'Staple Wire #35-5M', 'unit_price' => 45.00],
        ['item_code' => 'ITEM-070', 'qty' => 2, 'unit' => 'box', 'description' => 'Staple Wire #23/10', 'unit_price' => 79.00],
        ['item_code' => 'ITEM-071', 'qty' => 1, 'unit' => 'box', 'description' => 'Staple Wire #23/23', 'unit_price' => 39.00],
        ['item_code' => 'ITEM-072', 'qty' => 20, 'unit' => 'pad', 'description' => 'Sticky Notes 3"x5"', 'unit_price' => 53.00],
        ['item_code' => 'ITEM-073', 'qty' => 12, 'unit' => 'roll', 'description' => 'Tape, Masking 24mmx25y', 'unit_price' => 31.00],
        ['item_code' => 'ITEM-074', 'qty' => 24, 'unit' => 'roll', 'description' => 'Tape, transparent 24mmx50m', 'unit_price' => 50.00],
        ['item_code' => 'ITEM-075', 'qty' => 16, 'unit' => 'roll', 'description' => 'Tape, Transparent 48mmx50y', 'unit_price' => 256.00],
        ['item_code' => 'ITEM-077', 'qty' => 30, 'unit' => 'pack', 'description' => 'Toilet Tissue Paper, 2-ply 12 rolls/pack', 'unit_price' => 84.00],
        ['item_code' => 'ITEM-078', 'qty' => 8, 'unit' => 'piece', 'description' => 'Unipin Sign Pen 0.8 Black', 'unit_price' => 84.00],
        ['item_code' => 'ITEM-079', 'qty' => 14, 'unit' => 'box', 'description' => 'Vinyl Paper Clip big', 'unit_price' => 35.00],
    ];
    
    // Categorize items automatically
    function categorizeItem($description) {
        $desc = strtolower($description);
        if (strpos($desc, 'paper') !== false || strpos($desc, 'folder') !== false || strpos($desc, 'envelope') !== false) {
            return 'Office Supplies';
        } elseif (strpos($desc, 'pen') !== false || strpos($desc, 'pencil') !== false || strpos($desc, 'marker') !== false || strpos($desc, 'highlighter') !== false) {
            return 'Writing Instruments';
        } elseif (strpos($desc, 'tape') !== false || strpos($desc, 'glue') !== false || strpos($desc, 'clip') !== false || strpos($desc, 'stapler') !== false || strpos($desc, 'staple') !== false) {
            return 'Fastening & Adhesives';
        } elseif (strpos($desc, 'broom') !== false || strpos($desc, 'mop') !== false || strpos($desc, 'dustpan') !== false || strpos($desc, 'garbage') !== false || strpos($desc, 'tissue') !== false || strpos($desc, 'dishwashing') !== false) {
            return 'Cleaning Supplies';
        } elseif (strpos($desc, 'battery') !== false || strpos($desc, 'calculator') !== false) {
            return 'Electronics';
        } else {
            return 'General Supplies';
        }
    }
    
    $itemId = 1;
    foreach ($sampleData as $data) {
        $_SESSION['items'][] = [
            'id' => $itemId++,
            'item_code' => $data['item_code'],
            'item_name' => $data['description'],
            'description' => '',
            'category' => categorizeItem($data['description']),
            'unit' => $data['unit'],
            'unit_cost' => $data['unit_price'],
            'quantity_on_hand' => $data['qty'],
            'reorder_level' => 10,
            'supplier' => '',
            'location' => '',
            'status' => 'Active',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    $_SESSION['success_message'] = 'Successfully loaded ' . count($sampleData) . ' sample items!';
    header('Location: item-master-list.php');
    exit;
}

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
                        <?php if (empty($items)): ?>
                            <a href="?load_sample_data=1" style="text-decoration: none;">
                                <button class="btn-primary" style="background: #FF9800;">📋 Load Sample Data</button>
                            </a>
                        <?php endif; ?>
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
                        <option value="Writing Instruments">Writing Instruments</option>
                        <option value="Fastening & Adhesives">Fastening & Adhesives</option>
                        <option value="Cleaning Supplies">Cleaning Supplies</option>
                        <option value="Electronics">Electronics</option>
                        <option value="General Supplies">General Supplies</option>
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
                            <option value="Writing Instruments">Writing Instruments</option>
                            <option value="Fastening & Adhesives">Fastening & Adhesives</option>
                            <option value="Cleaning Supplies">Cleaning Supplies</option>
                            <option value="Electronics">Electronics</option>
                            <option value="General Supplies">General Supplies</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit *</label>
                        <select name="unit" required>
                            <option value="piece">Piece</option>
                            <option value="box">Box</option>
                            <option value="pack">Pack</option>
                            <option value="bottle">Bottle</option>
                            <option value="ream">Ream</option>
                            <option value="roll">Roll</option>
                            <option value="pad">Pad</option>
                            <option value="book">Book</option>
                            <option value="can">Can</option>
                            <option value="unit">Unit</option>
                            <option value="set">Set</option>
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
        
        // Filter function
        function filterTable() {
            const searchTerm = document.getElementById('searchItem').value.toLowerCase();
            const categoryFilter = document.getElementById('filterCategory').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                // Skip empty state row
                if (row.cells.length === 1) return;
                
                const text = row.textContent.toLowerCase();
                const category = row.cells[3].textContent.toLowerCase(); // Category column (index 3)
                
                // Check all filters
                const matchesSearch = text.includes(searchTerm);
                const matchesCategory = !categoryFilter || category.includes(categoryFilter);
                const matchesStatus = true; // All items are active in session storage
                
                // Show row only if it matches all filters
                row.style.display = (matchesSearch && matchesCategory && matchesStatus) ? '' : 'none';
            });
        }
        
        // Search functionality
        document.getElementById('searchItem').addEventListener('keyup', filterTable);
        
        // Category filter
        document.getElementById('filterCategory').addEventListener('change', filterTable);
        
        // Status filter
        document.getElementById('filterStatus').addEventListener('change', filterTable);
    </script>
</body>
</html>
