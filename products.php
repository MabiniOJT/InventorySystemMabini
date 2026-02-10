<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays
if (!isset($_SESSION['products'])) $_SESSION['products'] = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_product') {
        $_SESSION['products'][] = [
            'id' => count($_SESSION['products']) + 1,
            'name' => $_POST['product_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category' => $_POST['category'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'date_issued' => $_POST['date_issued'] ?? date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $_SESSION['success_message'] = 'Product added successfully!';
        header('Location: products.php');
        exit;
    } elseif ($action === 'delete_product') {
        $id = $_POST['id'] ?? 0;
        foreach ($_SESSION['products'] as $key => $product) {
            if ($product['id'] == $id) {
                unset($_SESSION['products'][$key]);
                $_SESSION['products'] = array_values($_SESSION['products']);
                break;
            }
        }
        $_SESSION['success_message'] = 'Product deleted successfully!';
        header('Location: products.php');
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
                        if (empty($row[0]) && empty($row[1]) && empty($row[2])) continue;
                        
                        // Assuming columns: Name, Price, Date Issued
                        $name = $row[0] ?? '';
                        $price = $row[1] ?? 0;
                        $dateIssued = $row[2] ?? date('Y-m-d');
                        
                        // Convert Excel date to PHP date if numeric
                        if (is_numeric($dateIssued)) {
                            $dateIssued = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateIssued)->format('Y-m-d');
                        }
                        
                        if (!empty($name)) {
                            $_SESSION['products'][] = [
                                'id' => count($_SESSION['products']) + 1,
                                'name' => $name,
                                'description' => '',
                                'category' => '',
                                'price' => $price,
                                'date_issued' => $dateIssued,
                                'created_at' => date('Y-m-d H:i:s')
                            ];
                            $imported++;
                        }
                    }
                    
                    $_SESSION['success_message'] = "Successfully imported $imported products from Excel file!";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Error reading Excel file: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = 'Invalid file format. Please upload .xlsx, .xls, or .csv file.';
            }
        } else {
            $_SESSION['error_message'] = 'Please select a file to upload.';
        }
        
        header('Location: products.php');
        exit;
    }
}

$user = $_SESSION['user'];
$products = $_SESSION['products'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/navbar.php'; ?>

        <div class="content">
            <div class="content-section active">
                <div class="section-header">
                    <h2>Product Management</h2>
                    <div>
                        <button class="btn-primary" onclick="showModal('productModal')">+ Add Product</button>
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
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Date Issued</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <p>No products found. Click "Add Product" to get started.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['id']); ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['description'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($product['category'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($product['price'] ?? 0, 2)); ?></td>
                                    <td><?php echo htmlspecialchars($product['date_issued'] ?? ''); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
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
    
    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('productModal')">&times;</span>
            <h2>Add New Product</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_product">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category">
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Date Issued</label>
                    <input type="date" name="date_issued" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <button type="submit" class="btn-primary">Add Product</button>
            </form>
        </div>
    </div>
    
    <!-- Upload Excel Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('uploadModal')">&times;</span>
            <h2>Upload Excel File</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Upload an Excel file with columns: <strong>Name, Price, Date Issued</strong></p>
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

    <script src="script.js"></script>
</body>
</html>
