<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays
if (!isset($_SESSION['issued'])) $_SESSION['issued'] = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_issued') {
        $_SESSION['issued'][] = [
            'id' => count($_SESSION['issued']) + 1,
            'product_name' => $_POST['product_name'] ?? '',
            'quantity' => $_POST['quantity'] ?? '',
            'issued_to' => $_POST['issued_to'] ?? '',
            'date_issued' => date('Y-m-d H:i:s')
        ];
        $_SESSION['success_message'] = 'Quantity issued successfully!';
        header('Location: quantity-issued.php');
        exit;
    } elseif ($action === 'delete_issued') {
        $id = $_POST['id'] ?? 0;
        foreach ($_SESSION['issued'] as $key => $item) {
            if ($item['id'] == $id) {
                unset($_SESSION['issued'][$key]);
                $_SESSION['issued'] = array_values($_SESSION['issued']);
                break;
            }
        }
        $_SESSION['success_message'] = 'Record deleted successfully!';
        header('Location: quantity-issued.php');
        exit;
    }
}

$user = $_SESSION['user'];
$issued = $_SESSION['issued'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantity Issued - Mabini Inventory System</title>
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
                    <h2>Quantity Issued</h2>
                    <button class="btn-primary" onclick="showModal('issuedModal')">+ Issue Quantity</button>
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
                            <th>Quantity Issued</th>
                            <th>Issued To</th>
                            <th>Date Issued</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($issued)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <p>No issued quantities. Click "Issue Quantity" to get started.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($issued as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['id']); ?></td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($item['issued_to']); ?></td>
                                    <td><?php echo htmlspecialchars($item['date_issued']); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_issued">
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
    
    <!-- Issued Modal -->
    <div id="issuedModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('issuedModal')">&times;</span>
            <h2>Issue Quantity</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_issued">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" required>
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" required>
                </div>
                <div class="form-group">
                    <label>Issued To</label>
                    <input type="text" name="issued_to" placeholder="Person or Department" required>
                </div>
                <button type="submit" class="btn-primary">Issue Quantity</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
