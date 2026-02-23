<?php
/**
 * Sample Data Seeder
 * 
 * This script loads the 67 inventory items into the database.
 * Run this after setting up the database to populate it with sample data.
 */

require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_data'])) {
    try {
        $conn = getDatabaseConnection();
        
        // Check if items already exist
        $checkStmt = $conn->query("SELECT COUNT(*) as count FROM items");
        $result = $checkStmt->fetch();
        
        if ($result['count'] > 0) {
            $error = "Items already exist in database. Clear existing items first if you want to reload.";
        } else {
            // Get or create categories
            $categories = [
                'Office Supplies',
                'Writing Instruments',
                'Fastening & Adhesives',
                'Cleaning Supplies',
                'Electronics',
                'General Supplies'
            ];
            
            $categoryIds = [];
            foreach ($categories as $cat) {
                $stmt = $conn->prepare("INSERT IGNORE INTO categories (category_name, status) VALUES (?, 'Active')");
                $stmt->execute([$cat]);
                
                $stmt = $conn->prepare("SELECT id FROM categories WHERE category_name = ?");
                $stmt->execute([$cat]);
                $categoryIds[$cat] = $stmt->fetch()['id'];
            }
            
            // Sample items data
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
            
            // Function to categorize items
            function categorizeItem($description, $categoryIds) {
                $desc = strtolower($description);
                if (strpos($desc, 'paper') !== false || strpos($desc, 'folder') !== false || strpos($desc, 'envelope') !== false) {
                    return $categoryIds['Office Supplies'];
                } elseif (strpos($desc, 'pen') !== false || strpos($desc, 'pencil') !== false || strpos($desc, 'marker') !== false || strpos($desc, 'highlighter') !== false) {
                    return $categoryIds['Writing Instruments'];
                } elseif (strpos($desc, 'tape') !== false || strpos($desc, 'glue') !== false || strpos($desc, 'clip') !== false || strpos($desc, 'stapler') !== false || strpos($desc, 'staple') !== false) {
                    return $categoryIds['Fastening & Adhesives'];
                } elseif (strpos($desc, 'broom') !== false || strpos($desc, 'mop') !== false || strpos($desc, 'dustpan') !== false || strpos($desc, 'garbage') !== false || strpos($desc, 'tissue') !== false || strpos($desc, 'dishwashing') !== false) {
                    return $categoryIds['Cleaning Supplies'];
                } elseif (strpos($desc, 'battery') !== false || strpos($desc, 'calculator') !== false) {
                    return $categoryIds['Electronics'];
                } else {
                    return $categoryIds['General Supplies'];
                }
            }
            
            // Get admin user ID
            $stmt = $conn->query("SELECT id FROM users WHERE role = 'Admin' LIMIT 1");
            $adminUser = $stmt->fetch();
            $createdBy = $adminUser ? $adminUser['id'] : null;
            
            // Insert items
            $insertedCount = 0;
            $stmt = $conn->prepare("
                INSERT INTO items (
                    item_code, item_name, category_id, unit, unit_cost, 
                    quantity_on_hand, reorder_level, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, 10, 'Active', ?)
            ");
            
            foreach ($sampleData as $data) {
                $categoryId = categorizeItem($data['description'], $categoryIds);
                
                $stmt->execute([
                    $data['item_code'],
                    $data['description'],
                    $categoryId,
                    $data['unit'],
                    $data['unit_price'],
                    $data['qty'],
                    $createdBy
                ]);
                $insertedCount++;
            }
            
            $message = "Successfully loaded $insertedCount items into the database!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Check current items count
$itemsCount = 0;
try {
    $conn = getDatabaseConnection();
    $stmt = $conn->query("SELECT COUNT(*) as count FROM items");
    $result = $stmt->fetch();
    $itemsCount = $result['count'];
} catch (Exception $e) {
    $error = "Could not connect to database. Please run setup_database.php first.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Load Sample Data - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .status-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .status {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .status:last-child {
            border-bottom: none;
        }
        
        .status-label {
            font-weight: 600;
            color: #333;
        }
        
        .status-value {
            color: #666;
        }
        
        .btn {
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }
        
        .info-list {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-list h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .info-list ul {
            margin-left: 20px;
            color: #856404;
        }
        
        .info-list li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Load Sample Data</h1>
            <p>Mabini Inventory System</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <strong>✓ Success!</strong><br>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>❌ Error!</strong><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="status-box">
            <h3 style="margin-bottom: 15px; color: #333;">Database Status</h3>
            <div class="status">
                <span class="status-label">Items in Database:</span>
                <span class="status-value"><strong><?php echo $itemsCount; ?></strong></span>
            </div>
            <div class="status">
                <span class="status-label">Sample Items to Load:</span>
                <span class="status-value"><strong>67</strong></span>
            </div>
        </div>
        
        <?php if ($itemsCount == 0): ?>
            <div class="info-list">
                <h3>📦 What will be loaded:</h3>
                <ul>
                    <li>67 inventory items with real data</li>
                    <li>Auto-categorized into 6 categories</li>
                    <li>Item codes from ITEM-003 to ITEM-079</li>
                    <li>Quantities and prices included</li>
                    <li>Ready for immediate use</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="load_data" class="btn">
                    📥 Load Sample Data
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>ℹ️ Items Already Loaded</strong><br>
                Your database already contains <?php echo $itemsCount; ?> items.
            </div>
        <?php endif; ?>
        
        <a href="item-master-list.php" style="text-decoration: none;">
            <button type="button" class="btn btn-secondary">
                Go to Item Master List
            </button>
        </a>
    </div>
</body>
</html>
