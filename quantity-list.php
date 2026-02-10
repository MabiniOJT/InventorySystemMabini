<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays
if (!isset($_SESSION['quantities'])) $_SESSION['quantities'] = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_quantity') {
        $_SESSION['quantities'][] = [
            'id' => count($_SESSION['quantities']) + 1,
            'product_name' => $_POST['product_name'] ?? '',
            'quantity' => $_POST['quantity'] ?? '',
            'unit' => $_POST['unit'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $_SESSION['success_message'] = 'Quantity added successfully!';
        header('Location: quantity-list.php');
        exit;
    } elseif ($action === 'delete_quantity') {
        $id = $_POST['id'] ?? 0;
        foreach ($_SESSION['quantities'] as $key => $quantity) {
            if ($quantity['id'] == $id) {
                unset($_SESSION['quantities'][$key]);
                $_SESSION['quantities'] = array_values($_SESSION['quantities']);
                break;
            }
        }
        $_SESSION['success_message'] = 'Quantity deleted successfully!';
        header('Location: quantity-list.php');
        exit;
    }
}

$user = $_SESSION['user'];
$quantities = $_SESSION['quantities'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantity List - Mabini Inventory System</title>
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
                    <h2>Quantity List</h2>
                    <button class="btn-primary" onclick="showModal('quantityModal')">+ Add Quantity</button>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Available Quantity</th>
                            <th>Unit</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quantities)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <p>No quantity data available. Click "Add Quantity" to get started.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($quantities as $quantity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($quantity['id']); ?></td>
                                    <td><?php echo htmlspecialchars($quantity['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($quantity['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($quantity['unit']); ?></td>
                                    <td><?php echo htmlspecialchars($quantity['updated_at']); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_quantity">
                                            <input type="hidden" name="id" value="<?php echo $quantity['id']; ?>">
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
    
    <!-- Quantity Modal -->
    <div id="quantityModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('quantityModal')">&times;</span>
            <h2>Add Quantity</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_quantity">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" required>
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" required>
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <input type="text" name="unit" placeholder="e.g., pcs, kg, liters" required>
                </div>
                <button type="submit" class="btn-primary">Add Quantity</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
